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
        'is_active'
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    /**
     * Get the surgeries that use this operation.
     */
    public function surgeries()
    {
        return $this->hasMany(Surgery::class, 'surgical_operation_id');
    }
}
