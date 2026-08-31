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

    public function emergencies()
    {
        return $this->hasMany(Emergency::class);
    }

    public function radiologyRequests()
    {
        return $this->hasMany(RadiologyRequest::class);
    }

    public function scopeAnesthesia($query)
    {
        return $query->where('is_active', true)
            ->where(function ($query) {
                $query->where('type', 'anesthesiologist')
                    ->orWhere('type', 'anesthesia')
                    ->orWhere('specialization', 'تخدير')
                    ->orWhereHas('user.roles', function ($roleQuery) {
                        $roleQuery->whereIn('name', ['التخدير', 'Anesthesia']);
                    });
            });
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

    public function financialAccount()
    {
        return $this->hasOne(DoctorFinancialAccount::class);
    }

    public function dues()
    {
        return $this->hasMany(DoctorDue::class);
    }

    public function commissionSettings()
    {
        return $this->hasMany(DoctorCommissionSetting::class);
    }

    public function currentCommissionSetting()
    {
        return $this->hasOne(DoctorCommissionSetting::class)
            ->latest('id');
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

    /**
     * Scope to filter doctors working on a specific Arabic day name.
     */
    public function scopeWorkingOnDay($query, $day)
    {
        $unicodeEscaped = trim(json_encode($day), '"');
        return $query->where(function ($q) use ($day, $unicodeEscaped) {
            $q->whereJsonContains('working_days', $day)
              ->orWhereJsonContains('working_days', [$day])
              ->orWhere('working_days', 'like', '%"' . $day . '"%')
              ->orWhere('working_days', 'like', '%' . $unicodeEscaped . '%');
        });
    }

    /**
     * Set working days attribute with explicit UTF-8 unescaped unicode JSON encoding.
     */
    public function setWorkingDaysAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['working_days'] = json_encode(array_values($value), JSON_UNESCAPED_UNICODE);
        } elseif (is_string($value)) {
            $this->attributes['working_days'] = $value;
        } else {
            $this->attributes['working_days'] = null;
        }
    }

    /**
     * Encode the given value to JSON with unescaped unicode so Arabic characters are saved clearly.
     *
     * @param  mixed  $value
     * @param  int  $flags
     * @return string|false
     */
    protected function asJson($value, $flags = 0)
    {
        return json_encode($value, $flags | JSON_UNESCAPED_UNICODE);
    }
}