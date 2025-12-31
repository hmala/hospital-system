<?php
// app/Models/Appointment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id', 
        'department_id',
        'appointment_date',
        'status',
        'reason',
        'notes',
        'consultation_fee',
        'duration',
        'cancellation_reason',
        'confirmed_at',
        'completed_at',
        'cancelled_at'
    ];

    // أسباب الزيارة المحددة
    const VISIT_REASONS = [
        'كشف طبي عام' => 'كشف طبي عام',
        'متابعة حالة مرضية' => 'متابعة حالة مرضية',
        'استشارة طبية' => 'استشارة طبية',
        'فحوصات دورية' => 'فحوصات دورية',
        'طوارئ' => 'طوارئ',
        'علاج أسنان' => 'علاج أسنان',
        'فحص قلب' => 'فحص قلب',
        'فحص نسائي' => 'فحص نسائي',
        'فحص أطفال' => 'فحص أطفال',
        'لقاح' => 'لقاح',
        'أخرى' => 'أخرى'
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // العلاقات
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function visit()
    {
        return $this->hasOne(Visit::class);
    }

    // النطاق (Scopes)
    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('appointment_date', '>=', today())
                    ->whereIn('status', ['scheduled', 'confirmed']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // الدوال المساعدة
    public function getStatusTextAttribute()
    {
        $statuses = [
            'scheduled' => 'مجدول',
            'confirmed' => 'مؤكد',
            'completed' => 'مكتمل', 
            'cancelled' => 'ملغى',
            'no_show' => 'لم يحضر'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'scheduled' => 'warning',
            'confirmed' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            'no_show' => 'secondary'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    public function getDateTimeAttribute()
    {
        return $this->appointment_date;
    }

    public function isUpcoming()
    {
        return $this->appointment_date->gte(now()->startOfDay()) && in_array($this->status, ['scheduled', 'confirmed']);
    }

    public function isPast()
    {
        return $this->appointment_date->lt(now()->startOfDay());
    }

    public function canBeCancelled()
    {
        return $this->isUpcoming() && in_array($this->status, ['scheduled', 'confirmed']) && !$this->visit;
    }

    // تغيير حالة الموعد
    public function confirm()
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_at' => now()
        ]);
    }

    public function complete()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now()
        ]);
    }

    public function cancel($reason = null)
    {
        $this->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now()
        ]);
    }
}