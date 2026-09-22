<?php
// app/Models/Department.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory, \App\Traits\HasInsurancePricing;

    protected $fillable = [
        'hospital_id',
        'name', 
        'type',
        'room_number',
        'consultation_fee',
        'moi_price',
        'is_moi_active',
        'hi_price',
        'is_hi_active',
        'working_hours_start',
        'working_hours_end',
        'max_patients_per_day',
        'is_active'
    ];

    protected $casts = [
        'working_hours_start' => 'datetime:H:i',
        'working_hours_end' => 'datetime:H:i',
        'is_active' => 'boolean',
        'is_moi_active' => 'boolean',
        'is_hi_active' => 'boolean',
        'consultation_fee' => 'decimal:2',
        'moi_price' => 'decimal:2',
        'hi_price' => 'decimal:2',
    ];

    public function hospital()
    {
        return $this->belongsTo(Hospital::class);
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
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

    public function medicalDevices()
    {
        return $this->hasMany(MedicalDevice::class);
    }
}