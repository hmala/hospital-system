<?php

namespace App\Observers;

use App\Models\ConsultationRevenue;
use App\Models\DoctorCommissionSetting;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Payment;

class PaymentObserver
{
    public function created(Payment $payment): void
    {
        if ((float)$payment->amount < 0) {
            $this->handleRefundFinancialTransaction($payment);
        } else {
            $this->handlePaymentRevenue($payment);
            $this->syncNonAppointmentPayment($payment);
        }
        $this->logPaymentActivity($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('paid_at') && $payment->paid_at !== null) {
            if ((float)$payment->amount < 0) {
                $this->handleRefundFinancialTransaction($payment);
            } else {
                $this->handlePaymentRevenue($payment);
                $this->syncNonAppointmentPayment($payment);
            }
            $this->logPaymentActivity($payment);
        }
    }

    public function deleted(Payment $payment): void
    {
        // حذف أي سندات خزينة أو إيرادات تابعة للدفعة المحذوفة لمنع أي عدم تطابق مستقبلاً
        FinancialTransaction::where('related_type', Payment::class)
            ->where('related_id', $payment->id)
            ->delete();

        ConsultationRevenue::where('payment_id', $payment->id)->delete();
        DoctorDue::where('payment_id', $payment->id)->delete();
    }

    protected function logPaymentActivity(Payment $payment): void
    {
        if (!$payment->paid_at) {
            return;
        }

        $patientName = $payment->patient?->name ?? ($payment->appointment?->patient?->name ?? 'مريض');
        $amountFormatted = number_format(abs((float)$payment->amount), 0);
        $receipt = $payment->receipt_number ? " (وصل #{$payment->receipt_number})" : '';
        $isRefund = (float)$payment->amount < 0;

        if ($isRefund) {
            \App\Services\UserActivityLogger::log(
                action: 'refund',
                category: 'cashier',
                description: "استرجاع مبلغ مالي بقيمة {$amountFormatted} د.ع للمريض: {$patientName}{$receipt}",
                subject: $payment,
                isSensitive: true,
                properties: ['amount' => $payment->amount, 'receipt_number' => $payment->receipt_number]
            );
        } else {
            \App\Services\UserActivityLogger::log(
                action: 'payment',
                category: 'cashier',
                description: "تحصيل دفعة مالية بقيمة {$amountFormatted} د.ع للمريض: {$patientName}{$receipt}",
                subject: $payment,
                isSensitive: false,
                properties: ['amount' => $payment->amount, 'receipt_number' => $payment->receipt_number]
            );
        }
    }

    protected function handlePaymentRevenue(Payment $payment): void
    {
        if (!$payment->paid_at) {
            return;
        }

        if (!$payment->appointment) {
            return;
        }

        $appointment = $payment->appointment->load(['doctor', 'department', 'patient']);

        if ($payment->amount < 0 || $appointment->status === 'cancelled') {
            return;
        }

        if (!$appointment->doctor) {
            return;
        }

        $setting = $this->getCommissionSetting($appointment);
        // Use total_amount for revenue distribution (vital for insurance where amount is just copay)
        $amount = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
        $baseAmount = $this->getCommissionBaseAmount($payment, $appointment);

        $doctorShare = $this->calculateDoctorShare($amount, $baseAmount, $appointment, $setting, $payment);
        $hospitalShare = round($amount - $doctorShare, 2);
        $percentage = ($amount != 0.0) ? round((abs($doctorShare) / abs($amount)) * 100, 2) : null;

        if ($amount < 0) {
            $hospitalShare = round($amount - $doctorShare, 2);
        }

        $revenue = ConsultationRevenue::updateOrCreate(
            ['payment_id' => $payment->id],
            [
                'receipt_number' => $payment->receipt_number,
                'payment_method' => $payment->payment_method,
                'payment_type' => $payment->payment_type,
                'cashier_id' => $payment->cashier_id,
                'appointment_id' => $appointment->id,
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'department_id' => $appointment->department_id,
                'service_type_id' => null,
                'examination_count' => 1,
                'total_amount' => $amount,
                'doctor_share' => $doctorShare,
                'hospital_share' => $hospitalShare,
                'doctor_percentage' => $percentage,
                'movement_type' => $amount < 0 ? 'refund' : 'payment',
                'revenue_date' => $payment->paid_at->toDateString(),
                'paid_at' => $payment->paid_at,
                'notes' => $payment->description ?? null,
                'transaction_reference' => $payment->receipt_number,
            ]
        );

        $this->syncDoctorAccount($appointment->doctor_id, $doctorShare, $payment->paid_at);
        $this->syncFinancialTransactions($payment, $doctorShare, $hospitalShare);
        $this->createDoctorDue($appointment->doctor_id, $payment->id, $doctorShare, $payment->receipt_number);
    }

    protected function createDoctorDue(int $doctorId, int $paymentId, float $doctorShare, ?string $receiptNumber): void
    {
        if ($doctorShare <= 0) {
            return;
        }

        DoctorDue::updateOrCreate(
            [
                'doctor_id' => $doctorId,
                'payment_id' => $paymentId,
            ],
            [
                'amount' => round($doctorShare, 2),
                'status' => 'pending',
                'notes' => 'مستحقات الطبيب من دفعة رقم ' . $receiptNumber,
            ]
        );
    }

    protected function getCommissionSetting($appointment)
    {
        $doctorId = $appointment->doctor_id;
        $departmentId = $appointment->department_id;

        $setting = DoctorCommissionSetting::where('doctor_id', $doctorId)
            ->where('is_active', true)
            ->where(function ($query) use ($departmentId) {
                $query->whereNull('department_id');

                if ($departmentId) {
                    $query->orWhere('department_id', $departmentId);
                }
            })
            ->where(function ($query) {
                $today = now()->toDateString();

                $query->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $today);
            })
            ->where(function ($query) {
                $today = now()->toDateString();

                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $today);
            })
            ->orderByRaw('department_id IS NULL, department_id DESC')
            ->first();

        return $setting;
    }

    protected function getCommissionBaseAmount(Payment $payment, $appointment): float
    {
        if ($appointment->consultation_fee && (float)$appointment->consultation_fee > 0) {
            return abs((float)$appointment->consultation_fee);
        }

        if ($appointment->doctor && $appointment->doctor->consultation_fee && (float)$appointment->doctor->consultation_fee > 0) {
            return abs((float)$appointment->doctor->consultation_fee);
        }

        return abs((float)$payment->amount);
    }

    protected function calculateDoctorShare(float $amount, float $baseAmount, $appointment, $setting, $payment = null): float
    {
        $absAmount = abs($amount);
        $absBaseAmount = abs($baseAmount);
        $isInsurance = $payment && (
            (float)($payment->insurance_share ?? 0) > 0 ||
            in_array($payment->insurance_type ?? '', ['hi', 'moi', 'interior_ministry', 'health_insurance']) ||
            ($appointment && in_array($appointment->insurance_type ?? '', ['hi', 'moi']))
        );

        if ($setting) {
            $type = $setting->commission_type ?? 'fixed';

            if ($type === 'fixed' || $type === 'custom') {
                $normalFixed = (float) ($setting->fixed_amount ?? 0);
                if ($isInsurance) {
                    $hiFixed = (float) ($setting->hi_fixed_amount ?? 0);
                    // إذا لم تكن محددة للطبيب في الضمان، القاعدة الثابتة للمستشفى هي 15,000 د.ع للطبيب (من كشفية الضمان 25,000)
                    $fixedVal = $hiFixed > 0 ? $hiFixed : 15000.00;
                } else {
                    $fixedVal = $normalFixed;
                }

                $doctorShare = $fixedVal > 0 ? $fixedVal : (float) ($setting->commission_value ?? 0);
            } else {
                // percentage (إذا كانت مسجلة تاريخياً كنسبة)
                $perc = (float) ($setting->commission_value ?? 0);
                if ($perc <= 0 && (float)($setting->fixed_amount ?? 0) > 0) {
                    $doctorShare = (float) $setting->fixed_amount;
                } elseif ($perc > 0) {
                    $doctorShare = round($absAmount * ($perc / 100), 2);
                } else {
                    $doctorShare = 0.00;
                }
            }

            // صمام أمان صارم: حصة الطبيب لا يمكن أن تتجاوز إجمالي المبلغ المحصل للكشفية نهائياً لمنع أي حصة سالبة للمستشفى
            $doctorShare = min($absAmount, abs($doctorShare));
            return $amount < 0 ? -$doctorShare : $doctorShare;
        }

        // في حال عدم وجود إعداد عمولة مسجل للطبيب ولكنها كشفية ضمان صحي
        if ($isInsurance) {
            $defaultHiShare = min($absAmount, 15000.00);
            return $amount < 0 ? -$defaultHiShare : $defaultHiShare;
        }

        // في حال عدم وجود إعداد عمولة مسجل للطبيب، تكون حصته 0.00 (لا توجد نسبة افتراضية تخمينية 70%)
        // وتبقى كامل الكشفية للمستشفى لحين ضبط المبلغ الثابت للطبيب
        return 0.00;
    }

    protected function syncDoctorAccount(int $doctorId, float $doctorShare, $paidAt): void
    {
        $account = DoctorFinancialAccount::firstOrCreate(
            ['doctor_id' => $doctorId],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_paid' => 0,
            ]
        );

        // احتساب دقيق غير قابل للتكرار بالاعتماد على سجل المستحقات الفعلي
        $totalEarned = (float) DoctorDue::where('doctor_id', $doctorId)->sum('amount');
        $totalPaid = (float) ($account->total_paid ?? 0);

        $account->total_earned = $totalEarned;
        $account->balance = round($totalEarned - $totalPaid, 2);
        $account->last_paid_at = $paidAt;
        $account->save();
    }

    protected function syncFinancialTransactions(Payment $payment, float $doctorShare, float $hospitalShare): void
    {
        // حذف أي قيد قديم من نوع doctor_payment لمنع الازدواجية مع سندات الصرف
        FinancialTransaction::where('related_type', Payment::class)
            ->where('related_id', $payment->id)
            ->where('transaction_type', 'doctor_payment')
            ->delete();

        FinancialTransaction::updateOrCreate(
            [
                'related_type' => Payment::class,
                'related_id' => $payment->id,
                'transaction_type' => 'hospital_revenue',
            ],
            [
                'voucher_type' => 'inflow',
                'category' => 'consultation',
                'voucher_number' => $payment->receipt_number,
                'amount' => (float)$payment->amount > 0 ? (float)$payment->amount : (float)$payment->total_amount,
                'currency' => 'IQD',
                'payment_method' => $payment->payment_method ?: 'cash',
                'description' => 'مقبوضات كشفية إيصال رقم ' . $payment->receipt_number,
                'notes' => 'حصة الطبيب المستحقة: ' . number_format($doctorShare) . ' د.ع | حصة المستشفى: ' . number_format($hospitalShare) . ' د.ع',
                'performed_by_id' => $payment->cashier_id,
                'performed_at' => $payment->paid_at,
            ]
        );
    }

    protected function syncNonAppointmentPayment(Payment $payment): void
    {
        if (!$payment->paid_at || $payment->appointment_id) {
            return;
        }

        FinancialTransaction::updateOrCreate(
            [
                'related_type' => Payment::class,
                'related_id' => $payment->id,
                'transaction_type' => 'hospital_revenue',
            ],
            [
                'voucher_type' => 'inflow',
                'category' => $payment->payment_type ?: 'general_income',
                'voucher_number' => $payment->receipt_number,
                'amount' => (float)$payment->amount > 0 ? (float)$payment->amount : (float)$payment->total_amount,
                'currency' => 'IQD',
                'payment_method' => $payment->payment_method ?: 'cash',
                'description' => 'مقبوضات ' . (Payment::PAYMENT_TYPES[$payment->payment_type] ?? 'خدمات') . ' إيصال رقم ' . $payment->receipt_number,
                'notes' => $payment->description,
                'performed_by_id' => $payment->cashier_id,
                'performed_at' => $payment->paid_at,
            ]
        );
    }

    protected function handleRefundFinancialTransaction(Payment $payment): void
    {
        if (!$payment->paid_at) {
            return;
        }

        $category = 'general_income';
        if ($payment->appointment_id || $payment->payment_type === 'appointment') {
            $category = 'consultation';
        } elseif ($payment->payment_type && in_array($payment->payment_type, ['lab', 'radiology', 'pharmacy', 'emergency', 'surgery'])) {
            $category = $payment->payment_type;
        }

        FinancialTransaction::updateOrCreate(
            [
                'related_type' => Payment::class,
                'related_id' => $payment->id,
            ],
            [
                'voucher_type' => 'outflow',
                'transaction_type' => 'expense',
                'category' => $category,
                'voucher_number' => $payment->receipt_number,
                'amount' => abs((float)$payment->amount),
                'currency' => 'IQD',
                'payment_method' => $payment->payment_method ?: 'cash',
                'description' => 'استرجاع مقبوضات إيصال رقم ' . $payment->receipt_number,
                'notes' => $payment->description ?: 'سند صرف استرجاع نقدي',
                'performed_by_id' => $payment->cashier_id ?: auth()->id(),
                'performed_at' => $payment->paid_at,
            ]
        );
    }
}
