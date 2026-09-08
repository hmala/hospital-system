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
        $this->handlePaymentRevenue($payment);
    }

    public function updated(Payment $payment): void
    {
        if ($payment->wasChanged('paid_at') && $payment->paid_at !== null) {
            $this->handlePaymentRevenue($payment);
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

        if (!$appointment->doctor) {
            return;
        }

        $setting = $this->getCommissionSetting($appointment);
        $amount = $payment->amount;
        $baseAmount = $this->getCommissionBaseAmount($payment, $appointment);

        $doctorShare = $this->calculateDoctorShare($amount, $baseAmount, $appointment, $setting);
        $hospitalShare = round($amount - $doctorShare, 2);
        $percentage = ((float)$baseAmount != 0.0) ? round((abs($doctorShare) / $baseAmount) * 100, 2) : null;

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

    protected function calculateDoctorShare(float $amount, float $baseAmount, $appointment, $setting): float
    {
        $absAmount = abs($amount);
        $absBaseAmount = abs($baseAmount);

        if ($setting) {
            $type = $setting->commission_type ?? 'percentage';

            if ($type === 'fixed') {
                $fixedVal = (float) ($setting->fixed_amount ?? 0);
                $doctorShare = $fixedVal > 0 ? $fixedVal : (float) ($setting->commission_value ?? 0);
            } elseif ($type === 'custom') {
                $fixedVal = (float) ($setting->fixed_amount ?? 0);
                $doctorShare = $fixedVal > 0 ? $fixedVal : (float) ($setting->commission_value ?? 0);
            } else {
                // percentage
                $perc = (float) ($setting->commission_value ?? 0);
                if ($perc <= 0 && (float)($setting->fixed_amount ?? 0) > 0) {
                    $doctorShare = (float) $setting->fixed_amount;
                } else {
                    $doctorShare = round($absBaseAmount * ($perc / 100), 2);
                }
            }

            $doctorShare = min($absBaseAmount, abs($doctorShare));
            return $amount < 0 ? -$doctorShare : $doctorShare;
        }

        if ($appointment->consultation_fee && (float)$appointment->consultation_fee > 0) {
            $defaultShare = round(abs($appointment->consultation_fee) * 0.7, 2);
            $doctorShare = min($defaultShare, $absAmount);
            return $amount < 0 ? -$doctorShare : $doctorShare;
        }

        $defaultShare = round($absBaseAmount * 0.7, 2);
        return $amount < 0 ? -$defaultShare : $defaultShare;
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

        $account->balance = round($account->balance + $doctorShare, 2);
        $account->total_earned = round($account->total_earned + $doctorShare, 2);
        $account->last_paid_at = $paidAt;
        $account->save();
    }

    protected function syncFinancialTransactions(Payment $payment, float $doctorShare, float $hospitalShare): void
    {
        FinancialTransaction::updateOrCreate(
            [
                'related_type' => Payment::class,
                'related_id' => $payment->id,
                'transaction_type' => 'doctor_payment',
            ],
            [
                'amount' => $doctorShare,
                'currency' => 'IQD',
                'description' => 'حصة الطبيب عن دفعة رقم ' . $payment->receipt_number,
                'performed_by_id' => $payment->cashier_id,
                'performed_at' => $payment->paid_at,
            ]
        );

        FinancialTransaction::updateOrCreate(
            [
                'related_type' => Payment::class,
                'related_id' => $payment->id,
                'transaction_type' => 'hospital_revenue',
            ],
            [
                'amount' => $hospitalShare,
                'currency' => 'IQD',
                'description' => 'ربح المستشفى عن دفعة رقم ' . $payment->receipt_number,
                'performed_by_id' => $payment->cashier_id,
                'performed_at' => $payment->paid_at,
            ]
        );
    }
}
