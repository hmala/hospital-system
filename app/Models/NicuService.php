<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NicuService extends Model
{
    use HasFactory;

    protected $table = 'nicu_services';

    protected $fillable = [
        'name',
        'code',
        'daily_fee',
        'description',
        'is_active',
    ];

    protected $casts = [
        'daily_fee' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(IncubatorReservation::class, 'nicu_service_id');
    }

    public function serviceLogs(): HasMany
    {
        return $this->hasMany(NicuServiceLog::class, 'nicu_service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
