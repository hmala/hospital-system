<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surgery extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'department_id',
        'visit_id',
        'surgery_type',
        'description',
        'scheduled_date',
        'scheduled_time',
        'started_at',
        'status',
        'payment_status',
        'payment_id',
        'surgery_fee',
        'referral_source',
        'external_doctor_name',
        'external_hospital_name',
        'referral_notes',
        'notes',
        'post_op_notes',
        'diagnosis',
        'pre_op_medications',
        'estimated_duration',
        'required_tests',
        'anesthesia_type',
        'anesthesiologist_id',
        'anesthesiologist_2_id',
        'surgical_assistant_name',
        'start_time',
        'end_time',
        'referring_physician',
        'surgery_classification',
        'supplies',
        'surgery_category',
        'surgery_type_detail',
        'anesthesia_position',
        'asa_classification',
        'surgical_complexity',
        'surgical_notes',
        'treatment_plan',
        'follow_up_date',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'started_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'follow_up_date' => 'date',
        'status' => 'string',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function labTests()
    {
        return $this->hasMany(SurgeryLabTest::class);
    }

    public function radiologyTests()
    {
        return $this->hasMany(SurgeryRadiologyTest::class);
    }

    public function surgeryTreatments()
    {
        return $this->hasMany(SurgeryTreatment::class)->orderBy('sort_order');
    }

    public function anesthesiologist()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_id');
    }

    public function anesthesiologist2()
    {
        return $this->belongsTo(Doctor::class, 'anesthesiologist_2_id');
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'scheduled' => 'مجدولة',
            'in_progress' => 'جارية',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            default => $this->status
        };
    }
}
