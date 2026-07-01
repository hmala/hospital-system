<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurgeryType extends Model
{
    protected $fillable = ['name', 'description', 'is_active'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
