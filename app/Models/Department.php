<?php
// app/Models/Department.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'hospital_id',
        'name', 
        'type',
        'room_number',
        'consultation_fee',
        'working_hours_start',
        'working_hours_end',
        'max_patients_per_day',
        'is_active'
    ];

    protected $casts = [
        'working_hours_start' => 'datetime:H:i',
        'working_hours_end' => 'datetime:H:i',
        'is_active' => 'boolean'
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // دالة للحصول على عدد المواعيد اليوم
    public function getTodayAppointmentsCount()
    {
        return $this->appointments()
            ->whereDate('appointment_date', today())
            ->count();
    }
    // في app/Models/Department.php أضف هذه الدالة:

public function getTypeText()
{
    $types = [
        'internal' => 'باطنية',
        'surgery' => 'جراحة',
        'pediatrics' => 'أطفال',
        'obstetrics' => 'نسائية',
        'orthopedics' => 'عظام',
        'cardiology' => 'قلب',
        'dentistry' => 'أسنان',
        'dermatology' => 'جلدية',
        'emergency' => 'طوارئ',
        'other' => 'أخرى'
    ];

    return $types[$this->type] ?? $this->type;
}
}