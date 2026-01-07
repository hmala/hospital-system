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
        'type',
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

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function patients()
    {
        return $this->belongsToMany(Patient::class, 'visits')
            ->distinct();
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

    // دالة لحساب أجر الطبيب بناءً على التخصص
    public function getFeeBySpecializationAttribute()
    {
        // إذا كان هناك أجر محدد للطبيب، استخدمه
        if ($this->consultation_fee) {
            return $this->consultation_fee;
        }

        // خلاف ذلك، استخدم الأجر الافتراضي حسب التخصص
        $fees = [
            'استشاري' => 50000,    // 50,000 IQD
            'تخدير' => 75000,      // 75,000 IQD
            'جراح' => 100000,      // 100,000 IQD
            'طبيب عام' => 25000,   // 25,000 IQD
            'أخصائي' => 40000,     // 40,000 IQD
        ];

        return $fees[$this->specialization] ?? 30000; // أجر افتراضي 30,000 IQD
    }
}