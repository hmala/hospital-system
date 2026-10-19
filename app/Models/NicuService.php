<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NicuService extends Model
{
    use HasFactory;

    protected $table = 'nicu_services';

    protected $fillable = [
        'name',
        'code',
        'daily_fee',
        'hi_price',
        'is_hi_active',
        'official_claim_name',
        'hotel_fee',
        'nursing_fee',
        'device_fee',
        'description',
        'is_active',
    ];

    protected $casts = [
        'daily_fee' => 'decimal:2',
        'hi_price' => 'decimal:2',
        'is_hi_active' => 'boolean',
        'hotel_fee' => 'decimal:2',
        'nursing_fee' => 'decimal:2',
        'device_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * الحصول على الأجرة المعتمدة بناءً على نوع الدخول (كاش أو ضمان)
     */
    public function getEffectiveFee(string $insuranceType = 'none'): float
    {
        if (in_array(strtolower($insuranceType), ['hi', 'health_insurance']) && $this->is_hi_active && (float)$this->hi_price > 0) {
            return (float)$this->hi_price;
        }

        return (float)$this->daily_fee;
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(IncubatorReservation::class, 'nicu_service_id');
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(NicuServiceLog::class, 'nicu_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
