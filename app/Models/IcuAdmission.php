<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IcuAdmission extends Model
{
    use HasFactory;

    protected $table = 'icu_admissions';

    protected $fillable = [
        'patient_id',
        'icu_bed_id',
        'doctor_id',
        'ccu_service_id',
        'admission_source',
        'admission_date',
        'discharge_date',
        'status',
        'primary_diagnosis',
        'icd10_code',
        'initial_gcs',
        'is_ventilated',
        'is_isolated',
        'is_insured',
        'insurance_type',
        'policy_category',
        'pricing_mode',
        'patient_share_amount',
        'insurance_share_amount',
        'bundled_reference_amount',
        'hia_collected_amount',
        'coverage_summary',
        'insurance_category',
        'discharge_summary',
        'discharge_destination',
        'discharged_by',
    ];

    protected function casts(): array
    {
        return [
            'admission_date' => 'datetime',
            'discharge_date' => 'datetime',
            'initial_gcs' => 'integer',
            'is_ventilated' => 'boolean',
            'is_isolated' => 'boolean',
            'is_insured' => 'boolean',
            'patient_share_amount' => 'decimal:2',
            'insurance_share_amount' => 'decimal:2',
            'bundled_reference_amount' => 'decimal:2',
            'hia_collected_amount' => 'decimal:2',
            'coverage_summary' => 'array',
        ];
    }

    const STATUS_ACTIVE = 'active';

    const STATUS_DISCHARGED = 'discharged';

    const STATUS_TRANSFERRED = 'transferred';

    const STATUS_DECEASED = 'deceased';

    const SOURCE_EMERGENCY = 'emergency';

    const SOURCE_OPERATION_THEATER = 'operation_theater';

    const SOURCE_WARD = 'ward';

    const SOURCE_EXTERNAL = 'external_referral';

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function bed(): BelongsTo
    {
        return $this->belongsTo(IcuBed::class, 'icu_bed_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function dischargedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    public function vitals(): HasMany
    {
        return $this->hasMany(IcuVitalSign::class, 'icu_admission_id')->orderBy('recorded_at', 'desc');
    }

    public function latestVital(): HasOne
    {
        return $this->hasOne(IcuVitalSign::class, 'icu_admission_id')->latestOfMany('recorded_at');
    }

    public function fluidBalances(): HasMany
    {
        return $this->hasMany(IcuFluidBalance::class, 'icu_admission_id')->orderBy('recorded_at', 'desc');
    }

    public function latestFluidBalance(): HasOne
    {
        return $this->hasOne(IcuFluidBalance::class, 'icu_admission_id')->latestOfMany('recorded_at');
    }

    public function medicalOrders(): HasMany
    {
        return $this->hasMany(IcuMedicalOrder::class, 'icu_admission_id')->orderBy('created_at', 'desc');
    }

    public function labOrders(): HasMany
    {
        return $this->hasMany(IcuMedicalOrder::class, 'icu_admission_id')->where('order_type', 'lab')->orderBy('ordered_at', 'desc');
    }

    public function radiologyOrders(): HasMany
    {
        return $this->hasMany(IcuMedicalOrder::class, 'icu_admission_id')->where('order_type', 'radiology')->orderBy('ordered_at', 'desc');
    }

    public function medicalRequests(): HasMany
    {
        return $this->hasMany(\App\Models\Request::class, 'icu_admission_id')->orderBy('created_at', 'desc');
    }

    public function careSchedules(): HasMany
    {
        return $this->hasMany(IcuCareSchedule::class, 'icu_admission_id')->orderBy('scheduled_time', 'asc');
    }

    public function ccuDoctorNotes(): HasMany
    {
        return $this->hasMany(CcuDoctorNote::class, 'icu_admission_id')->orderBy('created_at', 'desc');
    }

    public function ccuPinnedDoctorNotes(): HasMany
    {
        return $this->hasMany(CcuDoctorNote::class, 'icu_admission_id')->where('is_pinned', true)->orderBy('pinned_at', 'desc');
    }

    public function ccuMedicationSchedules(): HasMany
    {
        return $this->hasMany(CcuMedicationSchedule::class, 'icu_admission_id')->orderBy('is_active', 'desc')->orderBy('next_due_at', 'asc');
    }

    public function ccuActiveMedicationSchedules(): HasMany
    {
        return $this->hasMany(CcuMedicationSchedule::class, 'icu_admission_id')->where('is_active', true)->orderBy('next_due_at', 'asc');
    }

    public function ccuConsultations(): HasMany
    {
        return $this->hasMany(CcuConsultation::class, 'icu_admission_id')->orderBy('created_at', 'desc');
    }

    public function ccuAttachments(): HasMany
    {
        return $this->hasMany(CcuAttachment::class, 'icu_admission_id')->orderBy('created_at', 'desc');
    }

    public function ccuService(): BelongsTo
    {
        return $this->belongsTo(CcuService::class, 'ccu_service_id');
    }

    public function ccuServiceLogs(): HasMany
    {
        return $this->hasMany(CcuServiceLog::class, 'icu_admission_id')->orderBy('start_time', 'asc');
    }

    public function ccuPayments(): HasMany
    {
        return $this->hasMany(CcuPayment::class, 'icu_admission_id')->orderBy('paid_at', 'desc');
    }

    public function getTotalPaidAmountAttribute(): float
    {
        $paid = (float) $this->ccuPayments()
            ->where('financial_status', 'paid')
            ->where('payment_type', '!=', CcuPayment::TYPE_REFUND)
            ->sum('amount');

        $refunded = (float) $this->ccuPayments()
            ->where('financial_status', 'paid')
            ->where('payment_type', CcuPayment::TYPE_REFUND)
            ->sum('amount');

        return max(0.0, $paid - $refunded);
    }

    public function getInsuranceLabelAttribute(): string
    {
        if (! $this->is_insured && (! $this->insurance_type || $this->insurance_type === 'none')) {
            return 'نقدي (كاش)';
        }

        return match ($this->insurance_type) {
            'moi' => 'ضمان قوى الأمن',
            default => 'ضمان صحي (فئة '.($this->policy_category ?? ($this->insurance_category ?: 'E')).')',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDischarged($query)
    {
        return $query->where('status', self::STATUS_DISCHARGED);
    }

    public function getAdmissionSourceNameAttribute(): string
    {
        return match ($this->admission_source) {
            self::SOURCE_EMERGENCY => 'طوارئ الإنعاش (Emergency)',
            self::SOURCE_OPERATION_THEATER => 'صالات العمليات (Post-Op OR)',
            self::SOURCE_WARD => 'تدهور حالة بالرقود (Ward)',
            self::SOURCE_EXTERNAL => 'إحالة خارجية (External Referral)',
            default => $this->admission_source ?? 'غير محدد',
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'راقد حالياً بالعناية',
            self::STATUS_DISCHARGED => 'تم تخريجه بنجاح',
            self::STATUS_TRANSFERRED => 'تم نقله إلى طابق الرقود',
            self::STATUS_DECEASED => 'وفاة (رحمه الله)',
            default => $this->status,
        };
    }

    public function getStayDurationHoursAttribute(): int
    {
        $end = $this->discharge_date ?? Carbon::now();

        return (int) $this->admission_date->diffInHours($end);
    }

    /**
     * احتساب عدد الأيام الفندقية وفق قاعدة الـ 6 ساعات المعتمدة بالنظام
     */
    public function getStayDaysCountAttribute(): int
    {
        $end = $this->discharge_date ?? Carbon::now();
        $hours = $this->admission_date->diffInHours($end);
        $days = (int) floor($hours / 24);
        $remainingHours = $hours % 24;

        if ($remainingHours >= 6 || $days === 0) {
            $days += 1;
        }

        return max(1, $days);
    }

    public function getTotalNetBalanceAttribute(): int
    {
        return (int) $this->fluidBalances()->sum('net_balance');
    }
}
