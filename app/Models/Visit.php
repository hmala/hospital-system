<?php
// app/Models/Visit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'department_id',
        'appointment_id',
        'visit_date',
        'visit_time',
        'visit_type',
        'chief_complaint',
        'physical_examination',
        'diagnosis',
        'treatment_plan',
        'notes',
        'status',
        'vital_signs',
        'needs_surgery',
        'surgery_notes'
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'vital_signs' => 'array',
        'diagnosis' => 'array'
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

    // العلاقة الجديدة مع الموعد
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    // العلاقة مع الطلبات
    public function requests()
    {
        return $this->hasMany(Request::class);
    }

    // العلاقة مع الأدوية والعلاجات الموصوفة
    public function prescribedMedications()
    {
        return $this->hasMany(PrescribedMedication::class);
    }

    // الحصول على الأدوية فقط
    public function medications()
    {
        return $this->prescribedMedications()->where('item_type', 'medication');
    }

        // العلاقة مع العلاجات فقط
    public function treatments()
    {
        return $this->prescribedMedications()->where('item_type', 'treatment');
    }

    // العلاقة مع نتائج التحاليل المخبرية
    public function labResults()
    {
        return $this->hasMany(LabResult::class);
    }

    // العلاقة مع طلبات الأشعة
    public function radiologyRequests()
    {
        return $this->hasMany(RadiologyRequest::class);
    }

    // العلاقة مع العملية الجراحية
    public function surgery()
    {
        return $this->hasOne(Surgery::class);
    }

    // الحصول على آخر نتائج التحاليل للزيارة
    public function getLatestLabResults()
    {
        return $this->labResults()
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('test_name')
            ->map(function ($group) {
                return $group->first();
            });
    }
    public function getVisitTypeTextAttribute()
    {
        $types = [
            'checkup' => 'كشف دوري',
            'followup' => 'متابعة',
            'emergency' => 'طوارئ',
            'surgery' => 'عملية جراحية',
            'lab' => 'مختبر',
            'radiology' => 'أشعة'
        ];

        return $types[$this->visit_type] ?? $this->visit_type;
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'in_progress' => 'قيد الفحص',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'in_progress' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}