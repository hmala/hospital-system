<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuCustomMedicine extends Model
{
    use HasFactory;

    protected $table = 'nicu_custom_medicines';

    protected $fillable = [
        'name',
        'generic_name',
        'dosage_form',
        'strength',
        'default_dose',
        'default_route',
        'default_frequency',
        'default_duration_days',
        'notes',
        'usage_count',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'default_duration_days' => 'integer',
            'usage_count' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
