<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrFieldRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_key',
        'field_name_ar',
        'field_type',
        'lookup_category',
        'group_name',
        'is_required',
        'is_locked',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_locked' => 'boolean',
        'sort_order' => 'integer',
    ];

    // علاقة الخيارات إذا كان نوع الحقل قائمة منسدلة
    public function options()
    {
        return $this->hasMany(HrLookupOption::class, 'category', 'lookup_category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    public function activeOptions()
    {
        return $this->options()->where('is_active', true);
    }

    // اسم نوع الحقل بالعربية
    public function getFieldTypeNameAttribute(): string
    {
        return match ($this->field_type) {
            'select' => 'قائمة منسدلة (Dropdown)',
            'text' => 'نص عادي (Text)',
            'number' => 'رقم مالي / حسابي (Number)',
            'date' => 'تاريخ (Date)',
            'file' => 'ملف مرفق (File / PDF)',
            'textarea' => 'نص متعدد الأسطر (Textarea)',
            default => $this->field_type,
        };
    }

    public static function isFieldRequired(string $fieldKey, bool $default = false): bool
    {
        $item = static::where('field_key', $fieldKey)->first();
        return $item ? (bool) $item->is_required : $default;
    }

    public static function getRequirementsMap(): array
    {
        return static::pluck('is_required', 'field_key')->toArray();
    }
}
