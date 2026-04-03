<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incubator extends Model
{
    use HasFactory;

    protected $fillable = [
        'incubator_number',
        'incubator_type',
        'status',
        'room_id',
        'daily_fee',
        'description',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'daily_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * أنواع الحاضنات
     */
    const TYPE_NORMAL = 'normal';
    const TYPE_OXYGEN = 'oxygen';
    const TYPE_PHOTOTHERAPY = 'phototherapy';

    /**
     * حالات الحاضنة
     */
    const STATUS_AVAILABLE = 'available';
    const STATUS_OCCUPIED = 'occupied';
    const STATUS_MAINTENANCE = 'maintenance';

    /**
     * العلاقة مع الغرفة
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * العلاقة مع الحجوزات
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(IncubatorReservation::class);
    }

    /**
     * الحجز النشط الحالي
     */
    public function activeReservation()
    {
        return $this->hasOne(IncubatorReservation::class)
                    ->whereIn('status', ['pending', 'admitted'])
                    ->latest();
    }

    /**
     * الحصول على اسم نوع الحاضنة بالعربية
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->incubator_type) {
            self::TYPE_OXYGEN => 'حاضنة + أكسجين',
            self::TYPE_PHOTOTHERAPY => 'حاضنة + علاج ضوئي',
            default => 'حاضنة عادية',
        };
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute(): string
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
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AVAILABLE => 'success',
            self::STATUS_OCCUPIED => 'danger',
            self::STATUS_MAINTENANCE => 'warning',
            default => 'secondary',
        };
    }

    /**
     * الحصول على لون نوع الحاضنة
     */
    public function getTypeColorAttribute(): string
    {
        return match($this->incubator_type) {
            self::TYPE_OXYGEN => 'info',
            self::TYPE_PHOTOTHERAPY => 'purple',
            default => 'primary',
        };
    }

    /**
     * الحصول على أيقونة نوع الحاضنة
     */
    public function getTypeIconAttribute(): string
    {
        return match($this->incubator_type) {
            self::TYPE_OXYGEN => 'fa-lungs',
            self::TYPE_PHOTOTHERAPY => 'fa-sun',
            default => 'fa-baby',
        };
    }

    /**
     * التحقق من توفر الحاضنة
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    /**
     * scope للحاضنات المتاحة
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)
                     ->where('is_active', true);
    }

    /**
     * scope للحاضنات المحجوزة
     */
    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    /**
     * scope حسب النوع
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('incubator_type', $type);
    }
}
