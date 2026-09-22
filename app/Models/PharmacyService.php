<?php

namespace App\Models;

use App\Traits\HasInsurancePricing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyService extends Model
{
    use HasFactory, HasInsurancePricing;

    protected $table = 'pharmacy_services';

    protected $fillable = [
        'name',
        'price',
        'hi_price',
        'moi_price',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'price' => 'float',
        'hi_price' => 'float',
        'moi_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function saleItems()
    {
        return $this->hasMany(PharmacySaleItem::class, 'service_id');
    }
}
