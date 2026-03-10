<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmergencyPatient extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'gender',
        'date_of_birth',
        'reference_number',
        'notes',
        'is_active',
        'migrated',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'migrated' => 'boolean',
        'date_of_birth' => 'date',
    ];

    public function emergency()
    {
        return $this->hasOne(Emergency::class);
    }
}
