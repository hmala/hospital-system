<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medicines';

    protected $fillable = [
        'national_code',
        'name',
        'generic_name',
        'dosage_form',
        'strength',
        'barcode',
        'sub_barcode',
        'main_unit',
        'sub_unit',
        'sub_units_count',
        'cost_price',
        'sale_price',
        'sub_unit_sale_price',
        'hi_price',
        'moi_price',
        'is_insurance_covered',
        'min_stock_alert',
        'storage_temperature',
        'requires_prescription',
        'is_controlled',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'sub_units_count' => 'integer',
        'cost_price' => 'float',
        'sale_price' => 'float',
        'sub_unit_sale_price' => 'float',
        'hi_price' => 'float',
        'moi_price' => 'float',
        'is_insurance_covered' => 'boolean',
        'min_stock_alert' => 'integer',
        'requires_prescription' => 'boolean',
        'is_controlled' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * الوجبات / الشحنات الخاصة بالدواء
     */
    public function batches()
    {
        return $this->hasMany(MedicineBatch::class, 'medicine_id');
    }

    /**
     * الوجبات النشطة الصالحة مرتبة بنظام الأقرب انتهاءً أولاً (FEFO)
     */
    public function activeBatches()
    {
        return $this->hasMany(MedicineBatch::class, 'medicine_id')
            ->where('status', 'active')
            ->where('expiry_date', '>=', now()->toDateString())
            ->where(function ($q) {
                $q->where('current_quantity', '>', 0)
                  ->orWhere('current_sub_units', '>', 0);
            })
            ->orderBy('expiry_date', 'asc');
    }

    /**
     * البدائل الدوائية العلمية المرتبطة بهذا الدواء
     */
    public function alternatives()
    {
        return $this->belongsToMany(Medicine::class, 'medicine_alternatives', 'medicine_id', 'alternative_medicine_id')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * الأدوية التي يُعتبر هذا الدواء بديلاً لها
     */
    public function alternativeFor()
    {
        return $this->belongsToMany(Medicine::class, 'medicine_alternatives', 'alternative_medicine_id', 'medicine_id')
            ->withPivot('notes')
            ->withTimestamps();
    }

    /**
     * بنود المبيعات المرتبطة بهذا الدواء
     */
    public function saleItems()
    {
        return $this->hasMany(PharmacySaleItem::class, 'medicine_id');
    }

    /**
     * إجمالي الرصيد المتوفر بالعلب الكاملة
     */
    public function getTotalStockAttribute(): int
    {
        return (int) $this->batches()
            ->where('status', 'active')
            ->where('expiry_date', '>=', now()->toDateString())
            ->sum('current_quantity');
    }

    /**
     * إجمالي الرصيد المتوفر بالوحدات الصغرى (الأشرطة) من العلب المفتوحة
     */
    public function getTotalOpenSubUnitsAttribute(): int
    {
        return (int) $this->batches()
            ->where('status', 'active')
            ->where('expiry_date', '>=', now()->toDateString())
            ->sum('current_sub_units');
    }

    /**
     * إجمالي الرصيد الصافي محسوباً بالوحدات الصغرى (الأشرطة/الحبات)
     */
    public function getTotalSubUnitsStockAttribute(): int
    {
        $factor = max(1, $this->sub_units_count);
        return ($this->total_stock * $factor) + $this->total_open_sub_units;
    }

    /**
     * التحقق من قرب نفاد المخزون
     */
    public function getIsLowStockAttribute(): bool
    {
        return $this->total_stock <= $this->min_stock_alert;
    }

    /**
     * حساب التسعير وحصص المريض والضمان بناءً على نوع الوحدة والجهة ونسبة التحمل
     */
    public function calculatePricing(string $unitType = 'main_unit', ?string $insuranceType = 'none', ?float $copayPercent = 0.00): array
    {
        $insuranceType = strtolower((string) ($insuranceType ?? 'none'));
        $copayPercent  = max(0.00, min(100.00, (float) ($copayPercent ?? 0.00)));
        $isSubUnit     = ($unitType === 'sub_unit');

        // السعر الأساسي للجمهور (نقداً)
        if ($isSubUnit) {
            $regularPrice = ($this->sub_unit_sale_price > 0)
                ? (float) $this->sub_unit_sale_price
                : round((float) $this->sale_price / max(1, $this->sub_units_count), 2);
        } else {
            $regularPrice = (float) $this->sale_price;
        }

        // إذا كان الدواء غير مشمول بالضمان أساساً
        if (!$this->is_insurance_covered || $insuranceType === 'none') {
            return [
                'unit_type'        => $unitType,
                'insurance_type'   => 'none',
                'copay_percentage' => 0.00,
                'is_covered'       => false,
                'regular_price'    => $regularPrice,
                'approved_price'   => $regularPrice,
                'total_amount'     => $regularPrice,
                'patient_share'    => $regularPrice,
                'insurance_share'  => 0.00,
            ];
        }

        // تحديد السعر المعتمد حسب جهة التأمين
        $approvedPrice = $regularPrice;
        if (in_array($insuranceType, ['moi', 'interior_ministry']) && !is_null($this->moi_price) && (float) $this->moi_price > 0) {
            $approvedPrice = $isSubUnit
                ? round((float) $this->moi_price / max(1, $this->sub_units_count), 2)
                : (float) $this->moi_price;
        } elseif (in_array($insuranceType, ['hi', 'health_insurance']) && !is_null($this->hi_price) && (float) $this->hi_price > 0) {
            $approvedPrice = $isSubUnit
                ? round((float) $this->hi_price / max(1, $this->sub_units_count), 2)
                : (float) $this->hi_price;
        }

        $patientShare   = round($approvedPrice * ($copayPercent / 100.0), 2);
        $insuranceShare = round($approvedPrice - $patientShare, 2);

        return [
            'unit_type'        => $unitType,
            'insurance_type'   => $insuranceType,
            'copay_percentage' => $copayPercent,
            'is_covered'       => true,
            'regular_price'    => $regularPrice,
            'approved_price'   => $approvedPrice,
            'total_amount'     => $approvedPrice,
            'patient_share'    => $patientShare,
            'insurance_share'  => $insuranceShare,
        ];
    }
}
