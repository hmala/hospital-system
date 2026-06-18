<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStationFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'resident_station_id',
        'resident_id',
        'resident_name',
        'follow_up_date',
        'session',
        'notes',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function residentStation()
    {
        return $this->belongsTo(ResidentStation::class, 'resident_station_id');
    }

    public function resident()
    {
        return $this->belongsTo(Doctor::class, 'resident_id');
    }
}
