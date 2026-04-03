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
        'schedule',
        'consultation_fee',
        'start_time',
        'end_time',
        'working_days',
        'is_active',
        'is_available_today',
        'available_date'
    ];

    protected $casts = [
        'schedule' => 'array',
        'working_days' => 'array',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_active' => 'boolean',
        'is_available_today' => 'boolean',
        'available_date' => 'date'
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
        // التحقق من أن الطبيب نشط
        if (!$this->is_active) {
            return false;
        }

        // التحقق من أيام العمل
        if ($this->working_days) {
            $dayOfWeek = strtolower(date('l', strtotime($date)));
            $daysMap = [
                'saturday' => 'السبت',
                'sunday' => 'الأحد',
                'monday' => 'الإثنين',
                'tuesday' => 'الثلاثاء',
                'wednesday' => 'الأربعاء',
                'thursday' => 'الخميس',
                'friday' => 'الجمعة'
            ];
            
            $dayInArabic = $daysMap[$dayOfWeek] ?? null;
            if (!in_array($dayInArabic, $this->working_days)) {
                return false;
            }
        }

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