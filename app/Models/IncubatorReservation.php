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
        'gestational_age',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'discharge_date' => 'date',
        'admission_time' => 'datetime:H:i',
        'discharge_time' => 'datetime:H:i',
        'total_cost' => 'decimal:2',
        'expected_duration' => 'integer',
    ];

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
                $reservation->incubator->update(['status' => Incubator::STATUS_OCCUPIED]);
            }
        });

        static::updated(function ($reservation) {
            if (in_array($reservation->status, [self::STATUS_DISCHARGED, self::STATUS_CANCELLED])) {
                // تحرير الحاضنة
                $reservation->incubator->update(['status' => Incubator::STATUS_AVAILABLE]);
            } elseif (in_array($reservation->status, [self::STATUS_PENDING, self::STATUS_ADMITTED])) {
                $reservation->incubator->update(['status' => Incubator::STATUS_OCCUPIED]);
            }
        });
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
