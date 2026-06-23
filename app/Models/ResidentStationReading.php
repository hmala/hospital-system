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
        'pain_score',
        'rbs',
        'gcs',
        'crt',
        'intake_iv_fluids',
        'intake_oral',
        'intake_blood',
        'output_urine',
        'output_drain',
        'output_gtube_ng',
        'output_vomiting',
        'output_stool',
        'fluid_balance',
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
