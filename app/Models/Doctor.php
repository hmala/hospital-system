<?php
// app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department_id',
        'phone',
        'specialization',
        'qualification',
        'license_number',
        'experience_years',
        'bio',
        'schedule',
        'consultation_fee',
        'max_patients_per_day',
        'is_active'
    ];

    protected $casts = [
        'schedule' => 'array',
        'is_active' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function getTodayAppointmentsCount()
    {
        return $this->appointments()
            ->whereDate('appointment_date', today())
            ->count();
    }

    public function isAvailable($date)
    {
        $appointmentCount = $this->appointments()
            ->whereDate('appointment_date', $date)
            ->count();

        // إذا كان هناك حد أقصى للمرضى يومياً، تحقق منه
        if ($this->max_patients_per_day) {
            return $appointmentCount < $this->max_patients_per_day;
        }

        // خلاف ذلك، افترض أن الطبيب متاح دائماً
        return true;
    }
}