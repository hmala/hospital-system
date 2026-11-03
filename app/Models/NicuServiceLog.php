<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuServiceLog extends Model
{
    use HasFactory;

    protected $table = 'nicu_service_logs';

    protected $fillable = [
        'reservation_id',
        'nicu_service_id',
        'daily_fee',
        'start_time',
        'end_time',
        'days_count',
        'total_cost',
        'reason_for_switch',
        'switched_by',
        'notes',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'daily_fee' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'days_count' => 'integer',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function nicuService(): BelongsTo
    {
        return $this->belongsTo(NicuService::class, 'nicu_service_id');
    }

    public function switchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'switched_by');
    }
}
