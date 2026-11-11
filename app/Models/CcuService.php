<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CcuService extends Model
{
    use HasFactory;

    protected $table = 'ccu_services';

    protected $fillable = [
        'name',
        'code',
        'official_claim_name',
        'service_type', // package, addon
        'daily_fee',
        'hi_price',
        'is_hi_active',
        'include_bed_fee',
        'is_bundled_lab',
        'is_bundled_radiology',
        'is_bundled_medications',
        'hotel_fee',
        'nursing_fee',
        'device_fee',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'daily_fee' => 'decimal:2',
            'hi_price' => 'decimal:2',
            'is_hi_active' => 'boolean',
            'include_bed_fee' => 'boolean',
            'is_bundled_lab' => 'boolean',
            'is_bundled_radiology' => 'boolean',
            'is_bundled_medications' => 'boolean',
            'hotel_fee' => 'decimal:2',
            'nursing_fee' => 'decimal:2',
            'device_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    const TYPE_PACKAGE = 'package';

    const TYPE_ADDON = 'addon';

    /**
     * الحصول على الأجرة اليومية المعتمدة بناءً على التغطية التأمينية (كاش أو ضمان) وإضافة السرير عند الفصل
     */
    public function getEffectiveFee(string $insuranceType = 'none', float $bedDailyPrice = 0.0): float
    {
        $isInsurance = in_array(strtolower($insuranceType), ['hi', 'health_insurance', 'moi'], true);

        $serviceFee = ($isInsurance && $this->is_hi_active && (float) $this->hi_price > 0)
            ? (float) $this->hi_price
            : (float) $this->daily_fee;

        if (! $this->include_bed_fee && $this->service_type === self::TYPE_PACKAGE && $bedDailyPrice > 0) {
            return $serviceFee + $bedDailyPrice;
        }

        return $serviceFee;
    }

    /**
     * هل الخدمة مخصصة أساساً فقط لجلب تسعيرة الخدمة دون السرير
     */
    public function getServiceOnlyFee(string $insuranceType = 'none'): float
    {
        $isInsurance = in_array(strtolower($insuranceType), ['hi', 'health_insurance', 'moi'], true);

        if ($isInsurance && $this->is_hi_active && (float) $this->hi_price > 0) {
            return (float) $this->hi_price;
        }

        return (float) $this->daily_fee;
    }

    public function admissions(): HasMany
    {
        return $this->hasMany(IcuAdmission::class, 'ccu_service_id');
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(CcuServiceLog::class, 'ccu_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePackages($query)
    {
        return $query->where('service_type', self::TYPE_PACKAGE);
    }

    public function scopeAddons($query)
    {
        return $query->where('service_type', self::TYPE_ADDON);
    }
}
