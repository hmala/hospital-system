<?php

namespace App\Models\Eye;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EyeAppointment extends Model
{
    use HasFactory;

    protected $table = 'eye_appointments';

    protected $fillable = [
        'appointment_number',
        'patient_id',
        'doctor_id',
        'visit_type',
        'queue_number',
        'status',
        'chief_complaint',
        'insurance_type',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->appointment_number)) {
                $today = date('Ymd');
                $nextQueue = $model->queue_number ?? 1;
                $model->appointment_number = 'EYE-' . $today . '-' . str_pad($nextQueue, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(EyeInvoice::class);
    }

    public function latestInvoice(): HasOne
    {
        return $this->hasOne(EyeInvoice::class)->latestOfMany();
    }

    public function examination(): HasOne
    {
        return $this->hasOne(EyeExamination::class);
    }

    public function investigations(): HasMany
    {
        return $this->hasMany(EyeInvestigation::class);
    }

    public function surgeries(): HasMany
    {
        return $this->hasMany(EyeSurgery::class);
    }

    /**
     * ترجمة مسمى نوع المراجعة
     */
    public function getVisitTypeArabicAttribute(): string
    {
        return match ($this->visit_type) {
            'consultation'   => 'كشف استشاري',
            'optometry'      => 'فحص بصريات ونظارات',
            'investigation'  => 'فحص أجهزة (OCT / مجال بصر)',
            'procedure'      => 'إجراء / حقن شبكية',
            'follow_up'      => 'مراجعة دورية',
            default          => 'كشف عام'
        };
    }

    /**
     * ترجمة حالة الموعد
     */
    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'waiting'          => 'في الانتظار',
            'dilated'          => 'تم توسيع الحدقة',
            'in_clinic'        => 'في عيادة الفحص',
            'in_investigation' => 'في غرفة الأجهزة',
            'completed'        => 'مكتمل',
            'cancelled'        => 'ملغي',
            default            => $this->status
        };
    }

    public function getAppointmentDateAttribute()
    {
        return $this->created_at;
    }
}
