<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class IcuBed extends Model
{
    use HasFactory;

    protected $table = 'icu_beds';

    protected $fillable = [
        'bed_number',
        'bed_type',
        'room_number',
        'floor',
        'status',
        'daily_price',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    const STATUS_AVAILABLE = 'available';

    const STATUS_OCCUPIED = 'occupied';

    const STATUS_STERILIZING = 'sterilizing';

    const STATUS_MAINTENANCE = 'maintenance';

    const TYPE_GENERAL = 'general';

    const TYPE_ISOLATION = 'isolation';

    const TYPE_CCU = 'ccu';

    const TYPE_NEURO = 'neuro';

    public function admissions(): HasMany
    {
        return $this->hasMany(IcuAdmission::class, 'icu_bed_id');
    }

    public function currentAdmission(): HasOne
    {
        return $this->hasOne(IcuAdmission::class, 'icu_bed_id')->where('status', 'active')->latestOfMany();
    }

    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE && $this->is_active;
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->bed_type) {
            self::TYPE_ISOLATION => 'عزل ضغط سالب',
            self::TYPE_CCU => 'عناية قلبية (CCU)',
            self::TYPE_NEURO => 'عناية عصبية (Neuro)',
            default => 'عناية مركزة عامة (ICU)',
        };
    }

    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->bed_type) {
            self::TYPE_ISOLATION => 'bg-danger text-white',
            self::TYPE_CCU => 'bg-warning text-dark',
            self::TYPE_NEURO => 'bg-purple text-white',
            default => 'bg-primary text-white',
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'شاغر ومتاح',
            self::STATUS_OCCUPIED => 'مشغول بمريض',
            self::STATUS_STERILIZING => 'قيد التعقيم',
            self::STATUS_MAINTENANCE => 'صيانة وخارج الخدمة',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_AVAILABLE => 'bg-success text-white',
            self::STATUS_OCCUPIED => 'bg-danger text-white',
            self::STATUS_STERILIZING => 'bg-warning text-dark',
            self::STATUS_MAINTENANCE => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE)->where('is_active', true);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', self::STATUS_OCCUPIED);
    }

    public function scopeOrderByNumber($query, string $direction = 'asc')
    {
        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $driver = $query->getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return $query->orderByRaw("CAST(bed_number AS INTEGER) {$dir}")
                ->orderBy('bed_number', $dir);
        }

        return $query->orderByRaw("CAST(bed_number AS UNSIGNED) {$dir}")
            ->orderBy('bed_number', $dir);
    }
}
