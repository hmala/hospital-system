<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnesthesiaStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'anesthesiologist_id',
        'anesthesiologist_2_id',
        'anesthesia_type',
        'surgical_assistant_name',
        'notes',
        'start_time',
        'end_time',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function anesthesiologist()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function anesthesiologist2()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_2_id');
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
