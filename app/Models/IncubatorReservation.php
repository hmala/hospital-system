<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class IncubatorReservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'baby_name',
        'incubator_id',
        'nicu_service_id',
        'doctor_id',
        'department_id',
        'admission_date',
        'admission_time',
        'discharge_date',
        'discharge_time',
        'expected_duration',
        'status',
        'total_cost',
        'medical_notes',
        'admission_notes',
        'discharge_notes',
        'birth_weight',
        'current_weight',
        'gestational_age',
        'twin_group_id',
        'admission_source',
        'discharge_type',
        'camera_token',
        'camera_active',
        'insurance_type',
        'policy_category',
        'encounter_number',
        'insurance_card_no',
        'patient_share_amount',
        'insurance_share_amount',
        'coverage_summary',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'admission_time' => 'datetime:H:i',
        'discharge_time' => 'datetime:H:i',
        'total_cost' => 'decimal:2',
        'patient_share_amount' => 'decimal:2',
        'insurance_share_amount' => 'decimal:2',
        'coverage_summary' => 'array',
        'expected_duration' => 'integer',
        'current_weight' => 'integer',
        'camera_active' => 'boolean',
    ];

    /**
     * استخراج عدد أسابيع الحمل كرقم صحيح
     */
    public function getGestationalWeeksAttribute(): ?int
    {
        if (empty($this->gestational_age)) {
            return null;
        }
        if (preg_match('/(\d+)/', (string)$this->gestational_age, $matches)) {
            return (int)$matches[1];
        }
        return null;
    }

    /**
     * فحص هل الحجز مشمول بالضمان الصحي
     */
    public function getIsInsuredAttribute(): bool
    {
        return in_array(strtolower((string)($this->insurance_type ?? 'none')), ['hi', 'moi', 'health_insurance']);
    }

    /**
     * حالات الحجز
     */
    const STATUS_PENDING = 'pending';
    const STATUS_ADMITTED = 'admitted';
    const STATUS_DISCHARGED = 'discharged';
    const STATUS_TRANSFERRED = 'transferred';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * العلاقة مع المريض
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * العلاقة مع الحاضنة
     */
    public function incubator(): BelongsTo
    {
        return $this->belongsTo(Incubator::class);
    }

    /**
     * العلاقة مع باقة خدمة الخدج
     */
    public function nicuService(): BelongsTo
    {
        return $this->belongsTo(NicuService::class, 'nicu_service_id');
    }

    /**
     * سجلات تتبع الخدمات اليومية
     */
    public function serviceLogs()
    {
        return $this->hasMany(NicuServiceLog::class, 'reservation_id')->orderBy('start_time', 'asc');
    }

    /**
     * سجل العلامات الحيوية والوزن والرضاعة
     */
    public function vitals()
    {
        return $this->hasMany(NicuVital::class, 'reservation_id')->orderBy('recorded_at', 'desc');
    }

    /**
     * استشارات الأطباء التخصصية
     */
    public function consultations()
    {
        return $this->hasMany(NicuConsultation::class, 'reservation_id')->orderBy('created_at', 'desc');
    }

    /**
     * طلبات الفحوصات والأشعة والأدوية السريرية
     */
    public function medicalOrders()
    {
        return $this->hasMany(NicuMedicalOrder::class, 'reservation_id')->orderBy('ordered_at', 'desc');
    }

    /**
     * الطلبات الطبية المركزية الموحدة (مختبر / أشعة)
     */
    public function medicalRequests()
    {
        return $this->hasMany(\App\Models\Request::class, 'incubator_reservation_id')->orderBy('created_at', 'desc');
    }

    /**
     * نتائج التحاليل المخبرية التفصيلية المرتبطة بالحاضنة
     */
    public function labResults()
    {
        return $this->hasMany(\App\Models\LabResult::class, 'incubator_reservation_id')->orderBy('created_at', 'desc');
    }

    /**
     * سجل إعطاء وتطبيق العلاجات التمريضية (MAR)
     */
    public function medicationAdministrations()
    {
        return $this->hasMany(NicuMedicationAdministration::class, 'reservation_id')->orderBy('given_at', 'desc');
    }

    /**
     * المدفوعات والأمانات المسددة في الصندوق (جدول nicu_payments المستقل)
     */
    public function payments()
    {
        return $this->hasMany(NicuPayment::class, 'incubator_reservation_id')->orderBy('paid_at', 'desc');
    }

    public function nicuPayments()
    {
        return $this->hasMany(NicuPayment::class, 'incubator_reservation_id')->orderBy('paid_at', 'desc');
    }

    /**
     * إجمالي المبالغ المدفوعة والأمانات
     */
    public function getTotalPaidAmountAttribute(): float
    {
        $paid = (float) $this->payments()
            ->where('financial_status', 'paid')
            ->where('payment_type', '!=', NicuPayment::TYPE_REFUND)
            ->sum('amount');

        $refunded = (float) $this->payments()
            ->where('financial_status', 'paid')
            ->where('payment_type', NicuPayment::TYPE_REFUND)
            ->sum('amount');

        return max(0, $paid - $refunded);
    }

    /**
     * العلاقة مع الطبيب
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * العلاقة مع القسم
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * الحصول على مسمى الطفل الخديج المعتمد
     */
    public function getBabyTitleAttribute(): string
    {
        if (!empty($this->baby_name)) {
            return $this->baby_name;
        }
        return 'طفل ' . ($this->patient?->name ?? 'غير محدد');
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_ADMITTED => 'داخل الحاضنة',
            self::STATUS_DISCHARGED => 'تم الخروج',
            self::STATUS_TRANSFERRED => 'تم النقل',
            self::STATUS_CANCELLED => 'ملغي',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ADMITTED => 'success',
            self::STATUS_DISCHARGED => 'info',
            self::STATUS_TRANSFERRED => 'primary',
            self::STATUS_CANCELLED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * حساب مدة الإقامة الفعلية بالأيام
     */
    public function getActualDurationAttribute(): ?int
    {
        if (!$this->discharge_date) {
            return null;
        }

        return $this->admission_date->diffInDays($this->discharge_date) + 1;
    }

    /**
     * حساب المدة الحالية للمرضى الذين لا يزالون في الحاضنة
     */
    public function getCurrentDurationAttribute(): int
    {
        if ($this->status === self::STATUS_DISCHARGED) {
            return $this->actual_duration ?? 0;
        }

        return $this->admission_date->diffInDays(now()) + 1;
    }

    /**
     * حساب التكلفة المتوقعة
     */
    public function calculateExpectedCost(): float
    {
        if (!$this->incubator) {
            return 0;
        }

        return $this->expected_duration * $this->incubator->daily_fee;
    }

    /**
     * حساب التكلفة الفعلية
     */
    public function calculateActualCost(): float
    {
        if (!$this->incubator) {
            return 0;
        }

        $duration = $this->status === self::STATUS_DISCHARGED 
            ? $this->actual_duration 
            : $this->current_duration;

        return ($duration ?? 0) * $this->incubator->daily_fee;
    }

    /**
     * تحديث حالة الحاضنة عند الحفظ
     */
    protected static function booted()
    {
        static::created(function ($reservation) {
            if (in_array($reservation->status, [self::STATUS_PENDING, self::STATUS_ADMITTED])) {
                $reservation->incubator?->update(['status' => Incubator::STATUS_OCCUPIED]);
            }
        });

        static::updated(function ($reservation) {
            if ($reservation->status === self::STATUS_DISCHARGED) {
                // نقل الحاضنة لمرحلة التعقيم والتطهير
                $reservation->incubator?->update(['status' => Incubator::STATUS_STERILIZING]);
            } elseif ($reservation->status === self::STATUS_CANCELLED) {
                $reservation->incubator?->update(['status' => Incubator::STATUS_AVAILABLE]);
            } elseif (in_array($reservation->status, [self::STATUS_PENDING, self::STATUS_ADMITTED])) {
                $reservation->incubator?->update(['status' => Incubator::STATUS_OCCUPIED]);
            }
        });
    }

    /**
     * عدد الساعات الفعلية منذ الدخول
     */
    public function getDurationHoursAttribute(): int
    {
        $admissionDateTime = Carbon::parse($this->admission_date->format('Y-m-d') . ' ' . ($this->admission_time ? $this->admission_time->format('H:i') : '00:00'));
        $endDateTime = $this->discharge_date 
            ? Carbon::parse($this->discharge_date->format('Y-m-d') . ' ' . ($this->discharge_time ? $this->discharge_time->format('H:i') : '23:59'))
            : now();

        return max(0, $admissionDateTime->diffInHours($endDateTime));
    }

    /**
     * فارق الوزن بالجرام
     */
    public function getWeightDifferenceAttribute(): ?int
    {
        if ($this->birth_weight && $this->current_weight) {
            return (int)$this->current_weight - (int)$this->birth_weight;
        }
        return null;
    }

    /**
     * مسمى نوع الخروج بالعربية
     */
    public function getDischargeTypeNameAttribute(): string
    {
        return match($this->discharge_type) {
            'improved' => 'خروج تحسن واستقرار (عادي)',
            'step_down' => 'تحويل إلى ردهة الأطفال العادية',
            'transferred_out' => 'تحويل إلى مستشفى خارجي تخصصي',
            'dama' => 'خروج على مسؤولية الأهل (DAMA)',
            'expired' => 'وفاة المولود',
            default => 'غير محدد',
        };
    }

    /**
     * مسمى مصدر الدخول بالعربية
     */
    public function getAdmissionSourceNameAttribute(): string
    {
        return match($this->admission_source) {
            'c_section' => 'صالة ولادة / عمليات قيصرية داخلية',
            'external' => 'محال من خارج المستشفى (OUT)',
            default => 'دخول داخلي (IN)',
        };
    }

    /**
     * scope للحجوزات النشطة
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_ADMITTED]);
    }

    /**
     * scope للحجوزات المكتملة
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DISCHARGED);
    }
}
