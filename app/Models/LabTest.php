<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTest extends Model
{
    use HasFactory, \App\Traits\HasInsurancePricing;

    protected $fillable = [
        'main_category',
        'subcategory',
        'code',
        'name',
        'unit',
        'description',
        'is_active',
        'price',
        'moi_price',
        'is_moi_active',
        'hi_price',
        'is_hi_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_moi_active' => 'boolean',
        'is_hi_active' => 'boolean',
        'price' => 'decimal:2',
        'moi_price' => 'decimal:2',
        'hi_price' => 'decimal:2',
    ];

    // Accessor للحصول على نص الفئة من كود الفئة الرئيسي
    public function getCategoryTextAttribute()
    {
        $map = [
            'biochemistry' => 'كيمياء سريرية',
            'hematology' => 'أمراض الدم',
            'blood_bank' => 'مصرف الدم',
            'parasitology' => 'الطفيليات',
            'microbiology' => 'الأحياء المجهرية',
            'immunology' => 'المناعة والهرمونات',
            'virology' => 'فــيروسات',
            'hormones' => 'هرمــونات',
            'clinical_immunology' => 'المناعة السريرية',
            'cytology' => 'الخــلايا',
            'miscellaneous' => 'متفـــرقة',
            'other' => 'أخرى'
        ];

        return $map[$this->main_category] ?? $this->main_category;
    }

    // Accessor لنص الحالة
    public function getStatusTextAttribute()
    {
        return $this->is_active ? 'نشط' : 'معطل';
    }

    // Accessor للون الحالة
    public function getStatusColorAttribute()
    {
        return $this->is_active ? 'success' : 'danger';
    }

    // Scope للفحوصات النشطة فقط
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope للبحث حسب الفئة
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Scope للبحث حسب الاسم أو الكود
    public function scopeSearch($query, $term)
    {
        return $query->where('name', 'like', "%{$term}%")
                     ->orWhere('code', 'like', "%{$term}%");
    }

    // ────────────── العلاقات ──────────────

    public function references()
    {
        return $this->hasMany(LabTestReference::class);
    }

    /**
     * جلب المرجع المناسب لمريض
     */
    public function referenceForPatient(string $gender, int $age): ?LabTestReference
    {
        return LabTestReference::forPatient($this->id, $gender, $age);
    }
}
