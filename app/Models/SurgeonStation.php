<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeonStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'surgeon_id',
        'resident_assigned_id',
        'notes',
        'treatment_plan',
        'monitoring_protocol',
        'required_fluids',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'required_fluids' => 'array',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function surgeon()
    {
        return $this->belongsTo(Doctor::class, 'surgeon_id');
    }

    public function residentAssigned()
    {
        return $this->belongsTo(Doctor::class, 'resident_assigned_id');
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
