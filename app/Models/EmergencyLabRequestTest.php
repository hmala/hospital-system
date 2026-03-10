<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyLabRequestTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'emergency_lab_request_id',
        'lab_test_id',
        'result'
    ];

    public function emergencyLabRequest()
    {
        return $this->belongsTo(EmergencyLabRequest::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }
}
