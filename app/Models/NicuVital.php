<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuVital extends Model
{
    use HasFactory;

    protected $table = 'nicu_vitals';

    protected $fillable = [
        'reservation_id',
        'temperature',
        'incubator_temperature',
        'heart_rate',
        'respiratory_rate',
        'spo2',
        'blood_pressure',
        'weight',
        'feeding_type',
        'feeding_amount_ml',
        'feeding_notes',
        'nurse_id',
        'recorded_at',
    ];

    protected $casts = [
        'temperature' => 'decimal:1',
        'incubator_temperature' => 'decimal:1',
        'heart_rate' => 'integer',
        'respiratory_rate' => 'integer',
        'spo2' => 'integer',
        'weight' => 'integer',
        'feeding_amount_ml' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }
}
