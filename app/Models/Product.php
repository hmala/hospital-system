<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

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
}
