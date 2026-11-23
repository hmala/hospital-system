<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStationReadingDrain extends Model
{
    use HasFactory;

    protected $table = 'resident_station_reading_drains';

    protected $fillable = [
        'resident_station_reading_id',
        'drain_number',
        'drain_label',
        'amount',
    ];

    protected $casts = [
        'drain_number' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function reading()
    {
        return $this->belongsTo(ResidentStationReading::class, 'resident_station_reading_id');
    }
}
