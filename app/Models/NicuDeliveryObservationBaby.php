<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuDeliveryObservationBaby extends Model
{
    use HasFactory;

    public const DISPOSITION_OBSERVING = 'observing';

    public const DISPOSITION_HANDED_OVER = 'handed_over';

    public const DISPOSITION_ADMITTED_TO_NICU = 'admitted_to_nicu';

    protected $fillable = [
        'observation_id',
        'born_name',
        'live_status',
        'gender',
        'weight_kg',
        'disposition',
        'incubator_reservation_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:3',
        ];
    }

    public function observation(): BelongsTo
    {
        return $this->belongsTo(NicuDeliveryObservation::class, 'observation_id');
    }

    public function incubatorReservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'incubator_reservation_id');
    }

    public function getWeightGramsAttribute(): ?int
    {
        if ($this->weight_kg === null) {
            return null;
        }

        return (int) round(((float) $this->weight_kg) * 1000);
    }

    public function getDispositionLabelAttribute(): string
    {
        return match ($this->disposition) {
            self::DISPOSITION_OBSERVING => 'تحت المعاينة',
            self::DISPOSITION_HANDED_OVER => 'تم التسليم للأهل',
            self::DISPOSITION_ADMITTED_TO_NICU => 'محول لحاضنة NICU',
            default => (string) $this->disposition,
        };
    }
}
