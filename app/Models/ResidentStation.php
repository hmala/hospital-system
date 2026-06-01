<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'resident_id',
        'phase',
        'chief_complaint',
        'history_present_illness',
        'past_medical_hx',
        'past_surgical_hx',
        'drug_hx',
        'drug_allergy',
        'clinical_examination',
        'bp',
        'temp',
        'pr',
        'rr',
        'spo2',
        'review_of_other_systems',
        'notes',
        'post_op_notes',
        'treatment_plan',
        'follow_up_date',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function resident()
    {
        return $this->belongsTo(Doctor::class, 'resident_id');
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
