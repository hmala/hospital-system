<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SurgicalOperation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'fee',
        'description',
        'is_active',
        'show_in_nicu_radar',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean',
        'show_in_nicu_radar' => 'boolean',
    ];

    /**
     * Scope to filter operations shown in NICU radar.
     */
    public function scopeNicuRadar($query)
    {
        return $query->where('show_in_nicu_radar', true);
    }

    /**
     * Get the surgeries that use this operation.
     */
    public function surgeries()
    {
        return $this->hasMany(Surgery::class, 'surgical_operation_id');
    }
}
