<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_category',
        'subcategory',
        'code',
        'name',
        'unit',
        'description',
        'is_active',
        'price',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
}
