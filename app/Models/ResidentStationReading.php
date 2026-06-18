<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStationReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'resident_station_id',
        'resident_id',
        'bp',
        'temp',
        'pr',
        'rr',
        'spo2',
        'clinical_examination',
        'notes',
    ];

    public function residentStation()
    {
        return $this->belongsTo(ResidentStation::class, 'resident_station_id');
    }

    public function resident()
    {
        return $this->belongsTo(Doctor::class, 'resident_id');
    }
}
