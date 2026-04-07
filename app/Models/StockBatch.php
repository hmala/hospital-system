<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StockBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'purchase_item_id',
        'location_id',
        'internal_barcode',
        'original_barcode',
        'supplier_barcode',
        'manufacturer_lot_number',
        'cost_price',
        'initial_qty',
        'current_qty',
        'expiry_date',
        'received_at',
        'original_received_at',
        'parent_batch_id',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'datetime',
        'original_received_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function purchaseItem()
    {
        return $this->belongsTo(PurchaseItem::class, 'purchase_item_id');
    }

    public function parentBatch()
    {
        return $this->belongsTo(StockBatch::class, 'parent_batch_id');
    }

    public function childBatches()
    {
        return $this->hasMany(StockBatch::class, 'parent_batch_id');
    }

    public function scopeForLocation(Builder $query, $locationId = null)
    {
        return $locationId ? $query->where('location_id', $locationId) : $query;
    }

    public function scopeAvailable(Builder $query)
    {
        return $query->where('current_qty', '>', 0);
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'batch_id');
    }
}
