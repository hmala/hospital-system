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
        'unit',
        'is_perishable',
        'alert_quantity',
    ];

    protected $casts = [
        'is_perishable' => 'boolean',
    ];

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }
}
