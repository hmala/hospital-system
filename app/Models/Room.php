<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'room_type',
        'floor',
        'daily_fee',
        'status',
        'description',
        'beds_count',
        'has_bathroom',
        'has_tv',
        'has_ac',
        'is_active',
    ];

    protected $casts = [
        'daily_fee' => 'decimal:2',
        'has_bathroom' => 'boolean',
        'has_tv' => 'boolean',
        'has_ac' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * أنواع الغرف
     */
    const TYPE_REGULAR = 'regular';
    const TYPE_VIP = 'vip';
    const TYPE_VVIP = 'vvip';

    /**
     * حالات الغرفة
     */
    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_MAINTENANCE = 'maintenance';

    /**
     * العمليات المرتبطة بهذه الغرفة
     */
    public function surgeries()
    {
        return $this->hasMany(Surgery::class);
    }

    /**
     * حجوزات الرقود المرتبطة بهذه الغرفة
     */
    public function bedReservations()
    {
        return $this->hasMany(BedReservation::class);
    }

    /**
     * الحاضنات المرتبطة بهذه الغرفة
     */
    public function incubators()
    {
        return $this->hasMany(Incubator::class);
    }

    /**
     * جلب المريض المقيم حالياً في الغرفة إن وجد
     */
    public function getCurrentOccupantAttribute()
    {
        // 1. فحص العمليات الجراحية النشطة (لم يخرج المريض بعد)
        $activeSurgery = $this->surgeries()
            ->with(['patient.user', 'doctor.user'])
            ->whereNull('discharged_at')
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
            ->latest('scheduled_date')
            ->first();

        if ($activeSurgery && $activeSurgery->patient) {
            return (object)[
                'type' => 'عملية جراحية',
                'patient_name' => $activeSurgery->patient?->user?->name ?? 'غير معروف',
                'patient_phone' => $activeSurgery->patient?->user?->phone ?? '-',
                'doctor_name' => $activeSurgery->doctor?->user?->name ?? '-',
                'date' => $activeSurgery->scheduled_date,
                'status' => $activeSurgery->status,
                'type_color' => 'danger'
            ];
        }

        // 2. فحص حجوزات الرقود المؤكدة
        $activeBed = $this->bedReservations()
            ->with(['patient.user', 'doctor.user'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->latest('scheduled_date')
            ->first();

        if ($activeBed && $activeBed->patient) {
            return (object)[
                'type' => 'رقود',
                'patient_name' => $activeBed->patient?->user?->name ?? 'غير معروف',
                'patient_phone' => $activeBed->patient?->user?->phone ?? '-',
                'doctor_name' => $activeBed->doctor?->user?->name ?? '-',
                'date' => $activeBed->scheduled_date,
                'status' => $activeBed->status,
                'type_color' => 'info'
            ];
        }

        return null;
    }

    /**
     * الحصول على اسم نوع الغرفة بالعربية
     */
    public function getRoomTypeNameAttribute()
    {
        return match($this->room_type) {
            self::TYPE_VVIP => 'VVIP',
            self::TYPE_VIP => 'VIP',
            default => 'عادية',
        };
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute()
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'متاحة',
            self::STATUS_OCCUPIED => 'محجوزة',
            self::STATUS_MAINTENANCE => 'صيانة',
            default => 'غير معروف',
        };
    }

    /**
     * الحصول على لون الحالة
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_OCCUPIED => 'danger',
            self::STATUS_MAINTENANCE => 'warning',
            default => 'secondary',
        };
    }

    /**
     * الحصول على لون نوع الغرفة
     */
    public function getRoomTypeColorAttribute()
    {
        return match($this->room_type) {
            self::TYPE_VVIP => 'dark',
            self::TYPE_VIP => 'warning',
            default => 'primary',
        };
    }

    /**
     * نطاق الغرف المتاحة
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)->where('is_active', true);
    }

    /**
     * نطاق الغرف النشطة
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * نطاق حسب النوع
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('room_type', $type);
    }

    /**
     * الحصول على المزايا كنص
     */
    public function getFeaturesTextAttribute()
    {
        $features = [];
        if ($this->has_bathroom) $features[] = 'حمام خاص';
        if ($this->has_tv) $features[] = 'تلفزيون';
        if ($this->has_ac) $features[] = 'تكييف';
        if ($this->beds_count > 1) $features[] = $this->beds_count . ' أسرّة';
        
        return implode(' • ', $features);
    }
}
