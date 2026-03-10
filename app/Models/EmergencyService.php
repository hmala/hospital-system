<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmergencyService extends Model
{
    protected $fillable = [
        'name',
        'price',
        'category',
        'is_active'
    ];

    public function emergencies(): BelongsToMany
    {
        return $this->belongsToMany(Emergency::class, 'emergency_emergency_service');
    }
}
