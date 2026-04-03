<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodBankRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'visit_id',
        'patient_id',
        'department_id',
        'doctor_id',
        'room_no',
        'donor_group',
        'patient_group',
        'donor_weight',
        'recipient_weight',
        'at_room_temp',
        'bovine_albumin',
        'anti_human_globulin',
        'compatibility',
        'bottle_no',
        'operative_date',
        'exp_date',
        'doctor_in_charge',
        'total_amount',
        'status',
        'notes',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'in_progress' => 'قيد التنفيذ',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }
}
