<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationProductThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'location_id',
        'product_id',
        'alert_quantity',
        'reorder_level',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
