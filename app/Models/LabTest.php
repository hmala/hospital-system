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
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Accessor للحصول على نص الفئة
    public function getCategoryTextAttribute()
    {
        $categories = [
            'كيمياء سريرية' => 'كيمياء سريرية',
            'أمراض الدم' => 'أمراض الدم',
            'مصرف الدم' => 'مصرف الدم',
            'الطفيليات' => 'الطفيليات',
            'الأحياء المجهرية' => 'الأحياء المجهرية',
            'مصل الأمصال والمناعة' => 'مصل الأمصال والمناعة',
            'الفيروسات' => 'الفيروسات',
            'هرمونات' => 'هرمونات',
            'المناعة السريرية' => 'المناعة السريرية',
            'الخلايا' => 'الخلايا',
            'متفرقة' => 'متفرقة',
            'اخرى' => 'اخرى',
        ];

        return $categories[$this->category] ?? $this->category;
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
