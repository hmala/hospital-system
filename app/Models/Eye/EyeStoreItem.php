<?php

namespace App\Models\Eye;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EyeStoreItem extends Model
{
    use HasFactory;

    protected $table = 'eye_store_items';

    protected $fillable = [
        'item_code',
        'name',
        'item_name',
        'category',
        'diopter',
        'model_number',
        'manufacturer',
        'unit',
        'current_stock',
        'min_stock_alert',
        'cost_price',
        'selling_price',
        'location_id',
        'is_active',
    ];

    protected $casts = [
        'diopter'          => 'decimal:2',
        'cost_price'       => 'decimal:2',
        'selling_price'    => 'decimal:2',
        'current_stock'    => 'integer',
        'min_stock_alert'  => 'integer',
        'is_active'        => 'boolean',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(EyeStoreMovement::class);
    }

    public function getCategoryArabicAttribute(): string
    {
        return match ($this->category) {
            'iol_lens'           => 'عدسات داخل العين (IOL)',
            'retinal_injection'  => 'إبر حقن الشبكية',
            'viscoelastic'       => 'محاليل لزجة جراحية',
            'surgical_blade'     => 'شفرات جراحية دقيقة',
            'suture'             => 'خيوط جراحة العيون',
            default              => 'مستهلكات عامة'
        };
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock_alert;
    }

    public function getItemNameAttribute(): string
    {
        return $this->attributes['name'] ?? '';
    }

    public function setItemNameAttribute($value): void
    {
        $this->attributes['name'] = $value;
    }
}
