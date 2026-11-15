<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgicalAssistant extends Model
{
    use HasFactory;

    public const TYPE_SURGICAL = 'surgical';

    public const TYPE_ANESTHESIA = 'anesthesia';

    protected $fillable = [
        'name',
        'type',
        'phone',
        'notes',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope a query to only include active assistants.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include surgical assistants.
     */
    public function scopeSurgical(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SURGICAL);
    }

    /**
     * Scope a query to only include anesthesia assistants.
     */
    public function scopeAnesthesia(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ANESTHESIA);
    }

    /**
     * Get the count of surgeries where this assistant is assigned.
     */
    public function getSurgeriesCountAttribute(): int
    {
        if ($this->type === self::TYPE_ANESTHESIA) {
            return Surgery::where('anesthesia_assistant_name', $this->name)->count();
        }

        return Surgery::where('surgical_assistant_name', $this->name)->count();
    }
}
