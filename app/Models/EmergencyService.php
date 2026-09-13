<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmergencyService extends Model
{
    use \App\Traits\HasInsurancePricing;

    protected $fillable = [
        'name',
        'price',
        'moi_price',
        'is_moi_active',
        'hi_price',
        'is_hi_active',
        'category',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'moi_price' => 'decimal:2',
        'hi_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_moi_active' => 'boolean',
        'is_hi_active' => 'boolean',
    ];

    public function emergencies(): BelongsToMany
    {
        return $this->belongsToMany(Emergency::class, 'emergency_emergency_service');
    }
}
