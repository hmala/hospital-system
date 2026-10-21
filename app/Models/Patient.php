<?php
// app/Models/Patient.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'created_by', 'emergency_contact', 'blood_type', 'medical_history', 'allergies',
        'current_medications', 'insurance_company', 'insurance_number',
        'insurance_type', 'health_insurance_category_id', 'insurance_card_no', 'copay_percentage',
        'national_id', 'first_visit_date', 'notes', 'mother_name', 'country_id',
        'governorate', 'district', 'neighborhood', 'marital_status', 'covered_by_insurance', 'insurance_booklet_number',
        'telegram_chat_id', 'telegram_username'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'first_visit_date' => 'date',
        'copay_percentage' => 'decimal:2',
        'health_insurance_category_id' => 'integer',
        'created_by' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($patient) {
            if (empty($patient->created_by) && auth()->check()) {
                $patient->created_by = auth()->id();
            }
        });

        static::created(function ($patient) {
            if (class_exists(\App\Services\UserActivityLogger::class) && auth()->check()) {
                $patientName = $patient->user?->name ?? 'مريض #' . $patient->id;
                \App\Services\UserActivityLogger::log(
                    action: 'create',
                    category: 'reception',
                    description: "تسجيل وإدخال مريض جديد: {$patientName}",
                    subject: $patient,
                    isSensitive: false,
                    properties: [
                        'patient_id' => $patient->id,
                        'national_id' => $patient->national_id,
                        'insurance_type' => $patient->insurance_type,
                    ]
                );
            }
        });
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function healthInsuranceCategory()
    {
        return $this->belongsTo(HealthInsuranceCategory::class, 'health_insurance_category_id');
    }

    /**
     * الحصول على نسبة الاستقطاع المعتمدة للمريض بناءً على نوع الخدمة والضمان
     */
    public function getCopayPercentageFor(string $serviceType = 'consultation'): float
    {
        if ($this->insurance_type === 'none') {
            return 100.00;
        }

        if ($this->insurance_type === 'hi' && $this->healthInsuranceCategory) {
            return $this->healthInsuranceCategory->getCopayForService($serviceType);
        }

        return (float)($this->copay_percentage ?? 15.00);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // العلاقة الجديدة مع الزيارات
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(PatientDocument::class);
    }

    public function emergencies()
    {
        return $this->hasMany(Emergency::class);
    }

    public function surgeries()
    {
        return $this->hasMany(Surgery::class);
    }

    public function requests()
    {
        return $this->hasManyThrough(Request::class, Visit::class, 'patient_id', 'visit_id', 'id', 'id');
    }

    public function radiologyRequests()
    {
        return $this->hasMany(\App\Models\RadiologyRequest::class);
    }


    public function getFileNumberAttribute()
    {
        return $this->national_id ?? $this->id;
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getAgeAttribute()
    {
        return $this->user && $this->user->date_of_birth ? 
            \Carbon\Carbon::parse($this->user->date_of_birth)->age : null;
    }

    public function getGenderAttribute(): ?string
    {
        return $this->attributes['gender'] ?? ($this->user?->gender ?? null);
    }

    public function getGenderDisplayAttribute(): string
    {
        $g = strtolower((string) ($this->gender ?? ''));
        if (in_array($g, ['male', 'm', 'ذكر'], true)) {
            return 'ذكر';
        }
        if (in_array($g, ['female', 'f', 'أنثى', 'انثى'], true)) {
            return 'أنثى';
        }
        return 'غير محدد';
    }

    /**
     * فحص الحقول الأساسية الإلزامية لمريض الضمان الصحي
     */
    public function getMissingInsuranceFields(): array
    {
        $missing = [];
        $insNum = $this->insurance_card_no ?: ($this->insurance_number ?: $this->insurance_booklet_number);
        if (empty($insNum)) {
            $missing['insurance_number'] = 'رقم بطاقة / دفتر الضمان';
        }
        if (empty($this->national_id)) {
            $missing['national_id'] = 'البطاقة الموحدة / الرقم الوطني';
        }
        if (empty($this->user?->phone)) {
            $missing['phone'] = 'رقم الهاتف';
        }
        if (empty($this->user?->gender)) {
            $missing['gender'] = 'الجنس';
        }
        if (empty($this->user?->date_of_birth)) {
            $missing['date_of_birth'] = 'تاريخ الميلاد';
        }
        return $missing;
    }

    /**
     * هل بيانات الضمان مكتملة بنسبة 100%؟
     */
    public function isInsuranceDataComplete(): bool
    {
        return count($this->getMissingInsuranceFields()) === 0;
    }

    // دالة للحصول على آخر زيارة
    public function getLastVisit()
    {
        return $this->visits()->latest()->first();
    }

    // دالة للحصول على تاريخ آخر زيارة
    public function getLastVisitDate()
    {
        $lastVisit = $this->getLastVisit();
        return $lastVisit ? $lastVisit->visit_date : null;
    }

    // دالة للحصول على عدد الزيارات
    public function getVisitsCount()
    {
        return $this->visits()->count();
    }

    // دالة للحصول على عدد المواعيد
    public function getAppointmentsCount()
    {
        return $this->appointments()->count();
    }

    // دالة للحصول على الزيارات في شهر معين
    public function getVisitsThisMonth()
    {
        return $this->visits()
            ->whereYear('visit_date', now()->year)
            ->whereMonth('visit_date', now()->month)
            ->count();
    }

    public function getNameAttribute()
    {
        return $this->user->name ?? '';
    }

    public function getPhoneAttribute()
    {
        return $this->user->phone ?? '';
    }

    public function getMedicalRecordNumberAttribute()
    {
        return $this->national_id ?? ($this->medical_number ?? ('MRN-' . str_pad($this->id, 5, '0', STR_PAD_LEFT)));
    }
}