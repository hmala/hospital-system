<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    public const TYPE_MEDICATION = 'medication';

    public const TYPE_SUPPLY = 'supply';

    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
        'is_perishable',
        'alert_quantity',
        'description',
        'reorder_level',
        'storage_conditions',
    ];

    protected $casts = [
        'is_perishable' => 'boolean',
    ];

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function locationThresholds()
    {
        return $this->hasMany(LocationProductThreshold::class);
    }

    public function getAlertQuantityForLocation($locationId = null)
    {
        if ($locationId && $threshold = $this->locationThresholds->firstWhere('location_id', $locationId)) {
            return $threshold->alert_quantity;
        }

        return $this->alert_quantity;
    }

    public function getReorderLevelForLocation($locationId = null)
    {
        if ($locationId && $threshold = $this->locationThresholds->firstWhere('location_id', $locationId)) {
            return $threshold->reorder_level;
        }

        return $this->reorder_level;
    }

    public function resolveItemType(): string
    {
        $category = mb_strtolower(trim((string) ($this->category ?? '')));
        if ($category === '') {
            return self::TYPE_SUPPLY;
        }

        $medicationKeywords = [
            'أدوية',
            'ادوية',
            'دواء',
            'مغذي',
            'مغذيات',
            'علاج',
            'علاجات',
            'محلول',
            'محاليل',
            'pharma',
            'pharmacy',
            'medicine',
            'medicines',
            'medication',
            'medications',
            'drug',
            'drugs',
            'iv fluid',
            'infusion',
            'injection',
            'antibiotic',
            'ampoule',
            'vial',
            'tablet',
            'syrup',
        ];

        foreach ($medicationKeywords as $keyword) {
            if (str_contains($category, $keyword)) {
                return self::TYPE_MEDICATION;
            }
        }

        if (preg_match('/\bmeds?\b/i', $category) === 1) {
            return self::TYPE_MEDICATION;
        }

        return self::TYPE_SUPPLY;
    }

    public function isMedication(): bool
    {
        return $this->resolveItemType() === self::TYPE_MEDICATION;
    }

    public function isSupply(): bool
    {
        return $this->resolveItemType() === self::TYPE_SUPPLY;
    }
}
