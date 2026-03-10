<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyRadiologyRequestType extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_radiology_request_id',
        'radiology_type_id',
        'result',
        'image_path'
    ];

    public function emergencyRadiologyRequest()
    {
        return $this->belongsTo(EmergencyRadiologyRequest::class);
    }

    public function radiologyType()
    {
        return $this->belongsTo(RadiologyType::class);
    }
}
