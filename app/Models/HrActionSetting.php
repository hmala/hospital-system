<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrActionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'title',
        'effect_type',
        'effect_value',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'effect_value' => 'decimal:2',
    ];

    public function employeeActions()
    {
        return $this->hasMany(HrEmployeeAction::class);
    }
}
