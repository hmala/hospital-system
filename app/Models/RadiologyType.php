<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologyType extends Model
{
    use HasFactory;

    protected $fillable = [
        'main_category',
        'subcategory',
        'name',
        'code',
        'description',
        'base_price',
        'estimated_duration',
        'requires_contrast',
        'requires_preparation',
        'preparation_instructions',
        'is_active',
        'notes'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'requires_contrast' => 'boolean',
        'requires_preparation' => 'boolean',
        'is_active' => 'boolean'
    ];

    // العلاقات
    public function requests()
    {
        return $this->hasMany(RadiologyRequest::class);
    }

    // النطاقات
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // الخصائص المساعدة
    public function getDisplayNameAttribute()
    {
        return $this->name . ' (' . $this->code . ')';
    }

    public function requiresPreparation()
    {
        return $this->requires_preparation;
    }
}
