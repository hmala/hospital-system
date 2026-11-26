<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuExtraService extends Model
{
    use HasFactory;

    protected $fillable = [
        'incubator_reservation_id',
        'service_id',
        'name',
        'category',
        'price',
        'quantity',
        'total_amount',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'quantity' => 'integer',
            'total_amount' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'incubator_reservation_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospitalExtraService::class, 'service_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return HospitalExtraService::CATEGORIES[$this->category]['name'] ?? 'خدمات عامة';
    }

    public function getCategoryBadgeClassAttribute(): string
    {
        return HospitalExtraService::CATEGORIES[$this->category]['badge'] ?? 'bg-light text-secondary border';
    }
}
