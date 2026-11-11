<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CcuStayBracket extends Model
{
    use HasFactory;

    protected $table = 'ccu_stay_brackets';

    protected $fillable = [
        'name',
        'policy_category',
        'min_day',
        'max_day',
        'hia_percent',
        'patient_percent',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'min_day' => 'integer',
            'max_day' => 'integer',
            'hia_percent' => 'decimal:2',
            'patient_percent' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('min_day');
    }

    /**
     * التحقق مما إذا كان رقم اليوم التراكمي ينتمي لهذه الشريحة
     */
    public function matchesDay(int $dayNumber): bool
    {
        if ($dayNumber < $this->min_day) {
            return false;
        }

        if ($this->max_day !== null && $dayNumber > $this->max_day) {
            return false;
        }

        return true;
    }
}
