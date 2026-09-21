<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacySaleItem extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_sale_items';

    protected $fillable = [
        'sale_id',
        'item_type',
        'medicine_id',
        'service_id',
        'batch_id',
        'unit_type',
        'quantity',
        'unit_price',
        'subtotal',
        'dosage_instructions',
        'duration',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'subtotal' => 'float',
    ];

    public function sale()
    {
        return $this->belongsTo(PharmacySale::class, 'sale_id');
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function service()
    {
        return $this->belongsTo(PharmacyService::class, 'service_id');
    }

    public function batch()
    {
        return $this->belongsTo(MedicineBatch::class, 'batch_id');
    }

    /**
     * اسم العنصر سواء كان دواء أو خدمة
     */
    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'service' && $this->service) {
            return $this->service->name;
        }

        return $this->medicine ? $this->medicine->name : 'صنف غير محدد';
    }

    /**
     * التسمية العربية لنوع الوحدة
     */
    public function getUnitLabelAttribute(): string
    {
        if ($this->item_type === 'service') {
            return 'خدمة';
        }

        if ($this->unit_type === 'sub_unit') {
            return $this->medicine ? $this->medicine->sub_unit : 'شريط';
        }

        return $this->medicine ? $this->medicine->main_unit : 'علبة';
    }
}
