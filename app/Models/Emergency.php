<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Emergency extends Model
{
    protected $fillable = [
        'patient_id',
        'doctor_id',
        'nurse_id',
        'priority',
        'status',
        'emergency_type',
        'symptoms',
        'initial_assessment',
        'treatment_given',
        'notes',
        'vital_signs',
        'admission_time',
        'discharge_time',
        'room_assigned',
        'requires_surgery',
        'is_active',
        'diagnosis',
        'service_provided',
        'payment_status',
        'payment_id',
        'emergency_patient_id',
        'patient_migrated',
        'doctor_follow_up_fee',
        'follow_up_payment_id',
    ];

    protected $casts = [
        'vital_signs' => 'array',
        'admission_time' => 'datetime',
        'discharge_time' => 'datetime',
        'requires_surgery' => 'boolean',
        'doctor_follow_up_fee' => 'integer',
        'is_active' => 'boolean',
        'patient_migrated' => 'boolean',
        'emergency_patient_id' => 'integer',
    ];

    // العلاقات
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function emergencyPatient(): BelongsTo
    {
        return $this->belongsTo(EmergencyPatient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(EmergencyService::class, 'emergency_emergency_service');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(EmergencyTreatment::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function followUpPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'follow_up_payment_id');
    }

    public function labRequests()
    {
        return $this->hasMany(EmergencyLabRequest::class);
    }

    public function radiologyRequests(): HasMany
    {
        return $this->hasMany(EmergencyRadiologyRequest::class);
    }
    /**
     * العلاقة مع قراءات العلامات الحيوية
     */
    public function vitalSignReadings(): HasMany
    {
        return $this->hasMany(EmergencyVitalSign::class)->latest();
    }

    /**
     * آخر قراءة للعلامات الحيوية
     */
    public function latestVitalSign(): HasOne
    {
        return $this->hasOne(EmergencyVitalSign::class)->latestOfMany();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(\App\Models\Appointment::class);
    }

    // مساعدات
    public function getPriorityColorAttribute()
    {
        return match($this->priority) {
            'critical' => 'danger',
            'urgent' => 'warning',
            'semi_urgent' => 'info',
            'non_urgent' => 'secondary',
            default => 'secondary'
        };
    }

    public function getPriorityTextAttribute()
    {
        return match($this->priority) {
            'critical' => 'حرجة',
            'urgent' => 'عاجلة',
            'semi_urgent' => 'شبه عاجلة',
            'non_urgent' => 'غير عاجلة',
            default => 'غير محدد'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'waiting' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'transferred' => 'primary',
            'discharged' => 'secondary',
            default => 'secondary'
        };
    }

    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'waiting' => 'في الانتظار',
            'in_progress' => 'قيد المعالجة',
            'completed' => 'مكتملة',
            'transferred' => 'محولة',
            'discharged' => 'مغادرة',
            default => 'غير محدد'
        };
    }

    public function getPriorityBadgeClassAttribute()
    {
        return 'bg-' . $this->priority_color;
    }

    public function getStatusBadgeClassAttribute()
    {
        return 'bg-' . $this->status_color;
    }

    public function getBloodPressureAttribute()
    {
        // Try to get from latest vital sign reading first, fallback to JSON column
        $latest = $this->latestVitalSign;
        return $latest ? $latest->blood_pressure : ($this->vital_signs['blood_pressure'] ?? null);
    }

    public function getHeartRateAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->heart_rate : ($this->vital_signs['heart_rate'] ?? null);
    }

    public function getTemperatureAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->temperature : ($this->vital_signs['temperature'] ?? null);
    }

    public function getOxygenSaturationAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->oxygen_saturation : ($this->vital_signs['oxygen_saturation'] ?? null);
    }

    public function getRespiratoryRateAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->respiratory_rate : ($this->vital_signs['respiratory_rate'] ?? null);
    }

    public function getBloodGlucoseAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->blood_glucose : ($this->vital_signs['blood_glucose'] ?? null);
    }

    public function getVitalsLastUpdatedAttribute()
    {
        $latest = $this->latestVitalSign;
        return $latest ? $latest->created_at : (isset($this->vital_signs['updated_at']) ? \Carbon\Carbon::parse($this->vital_signs['updated_at']) : null);
    }

    public function getEmergencyTypeTextAttribute()
    {
        return self::getEmergencyTypeText($this->emergency_type);
    }

    /**
     * Get emergency type text statically
     */
    public static function getEmergencyTypeText($type)
    {
        return match($type) {
            'trauma' => 'إصابات وكسور',
            'cardiac' => 'مشاكل قلبية',
            'respiratory' => 'مشاكل تنفسية',
            'neurological' => 'مشاكل عصبية',
            'poisoning' => 'تسمم',
            'burns' => 'حروق',
            'allergic' => 'الحساسية الشديدة',
            'pediatric' => 'طوارئ أطفال',
            'obstetric' => 'طوارئ نساء وولادة',
            'general' => 'طوارئ عامة',
            default => 'غير محدد'
        };
    }
}
