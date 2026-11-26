<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalExtraService extends Model
{
    use HasFactory;

    public const DEPARTMENTS = [
        'general' => 'العمليات والخدمات العامة',
        'nicu' => 'قسم الخدج والحاضنات (NICU)',
        'all' => 'مشترك (كافة الأقسام)',
    ];

    public const CATEGORIES = [
        'medical' => ['name' => 'فحوصات وسريرية', 'icon' => 'fa-heartbeat', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle'],
        'clinical' => ['name' => 'سريرية وإجراءات', 'icon' => 'fa-stethoscope', 'badge' => 'bg-danger-subtle text-danger border border-danger-subtle'],
        'care' => ['name' => 'عناية ومستلزمات', 'icon' => 'fa-baby-carriage', 'badge' => 'bg-teal-subtle text-success border border-success-subtle'],
        'accommodation' => ['name' => 'فندقية ومرافقين', 'icon' => 'fa-bed', 'badge' => 'bg-primary-subtle text-primary border border-primary-subtle'],
        'administrative' => ['name' => 'إدارية وطبلة ووثائق', 'icon' => 'fa-file-invoice', 'badge' => 'bg-info-subtle text-info border border-info-subtle'],
        'decorating' => ['name' => 'تزيين الغرف', 'icon' => 'fa-ribbon', 'badge' => 'bg-warning-subtle text-dark border border-warning-subtle'],
        'fine' => ['name' => 'غرامات ومخالفات', 'icon' => 'fa-exclamation-triangle', 'badge' => 'bg-warning-subtle text-dark border border-warning'],
        'general' => ['name' => 'خدمات عامة', 'icon' => 'fa-tag', 'badge' => 'bg-light text-secondary border'],
    ];

    protected $fillable = [
        'name',
        'code',
        'category',
        'department',
        'default_price',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGeneral($query)
    {
        return $query->whereIn('department', ['general', 'all']);
    }

    public function scopeForNicu($query)
    {
        return $query->whereIn('department', ['nicu', 'all']);
    }

    public function getDepartmentLabelAttribute(): string
    {
        return self::DEPARTMENTS[$this->department ?? 'general'] ?? self::DEPARTMENTS['general'];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category ?? 'general']['name'] ?? 'خدمات عامة';
    }
}
