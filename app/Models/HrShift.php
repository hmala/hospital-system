<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'start_time', 'end_time', 'color_code', 'is_active', 'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
