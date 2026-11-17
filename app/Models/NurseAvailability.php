<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NurseAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'is_available',
        'assigned_floor',
        'shift',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_available' => 'boolean',
        ];
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeToday($query)
    {
        return $query->where('date', today());
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }
}
