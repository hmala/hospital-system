<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Request as ServiceRequest;
use App\Models\Payment;
use App\Models\Appointment;
use App\Models\ConsultationRevenue;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. جلب كافة طلبات السونار الخارجي المباشر المدفوعة
        $requests = ServiceRequest::with(['visit.appointment.doctor.currentCommissionSetting', 'visit.patient'])
            ->where(function($q) {
                $q->where('subtype', 'ultrasound')
                  ->orWhere('description', 'LIKE', '%سونار%');
            })
            ->where('payment_status', 'paid')
            ->whereNotNull('payment_id')
            ->get();

        foreach ($requests as $req) {
            $payment = Payment::find($req->payment_id);
            if (!$payment) {
                continue;
            }

            $appointment = $req->visit?->appointment;
            if (!$appointment && $payment->appointment_id) {
                $appointment = Appointment::with(['doctor.currentCommissionSetting', 'patient'])->find($payment->appointment_id);
            }

            // تحديث appointment_id في جدول payments إن كان مفقوداً
            if ($appointment && empty($payment->appointment_id)) {
                $payment->update(['appointment_id' => $appointment->id]);
            }

            $doctor = $appointment?->doctor;
            $setting = $doctor?->currentCommissionSetting;
            $amount = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);

            $docShare = 0;
            if ($setting && $amount > 0) {
                if ($setting->commission_type === 'fixed') {
                    $isInsurance = in_array($payment->insurance_type, ['hi', 'moi']) || ($payment->insurance_share > 0);
                    $fixed = $isInsurance && $setting->hi_fixed_amount > 0 
                        ? (float)$setting->hi_fixed_amount 
                        : (float)$setting->fixed_amount;
                    $docShare = min($amount, $fixed);
                } elseif ($setting->commission_type === 'percentage') {
                    $docShare = round($amount * ($setting->commission_value / 100), 2);
                }
            }
            $hospShare = max(0, round($amount - $docShare, 2));
            $percentage = $amount > 0 ? round(($docShare / $amount) * 100, 2) : 0;

            ConsultationRevenue::updateOrCreate(
                ['payment_id' => $payment->id],
                [
                    'receipt_number' => $payment->receipt_number,
                    'payment_method' => $payment->payment_method ?? 'cash',
                    'payment_type' => 'ultrasound',
                    'cashier_id' => $payment->cashier_id,
                    'appointment_id' => $appointment?->id,
                    'patient_id' => $appointment?->patient_id ?? $req->visit?->patient_id ?? $payment->patient_id,
                    'doctor_id' => $doctor?->id ?? $req->visit?->doctor_id,
                    'department_id' => $appointment?->department_id ?? $req->visit?->department_id,
                    'service_type_id' => null,
                    'examination_count' => 1,
                    'total_amount' => $amount,
                    'doctor_share' => $docShare,
                    'hospital_share' => $hospShare,
                    'doctor_percentage' => $percentage,
                    'movement_type' => 'payment',
                    'revenue_date' => $payment->paid_at ? $payment->paid_at->toDateString() : now()->toDateString(),
                    'paid_at' => $payment->paid_at ?? now(),
                    'notes' => 'حجز سونار خارجي مباشر من الاستعلامات (طلب #' . $req->id . ')',
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        ConsultationRevenue::where('payment_type', 'ultrasound')->delete();
    }
};
