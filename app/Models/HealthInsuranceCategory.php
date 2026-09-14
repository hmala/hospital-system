<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthInsuranceCategory extends Model
{
    use HasFactory;

    protected $table = 'health_insurance_categories';

    protected $fillable = [
        'code',
        'name',
        'description',
        'requires_thermal_stamp',
        'consultation_copay',
        'surgery_copay',
        'lab_copay',
        'radiology_copay',
        'support_services_copay',
        'medication_copay',
        'emergency_copay',
        'dental_copay',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_thermal_stamp' => 'boolean',
        'is_active' => 'boolean',
        'consultation_copay' => 'float',
        'surgery_copay' => 'float',
        'lab_copay' => 'float',
        'radiology_copay' => 'float',
        'support_services_copay' => 'float',
        'medication_copay' => 'float',
        'emergency_copay' => 'float',
        'dental_copay' => 'float',
        'sort_order' => 'integer',
    ];

    public function patients()
    {
        return $this->hasMany(Patient::class, 'health_insurance_category_id');
    }

    /**
     * الحصول على نسبة الاستقطاع المعتمدة لنوع الخدمة المعطى
     *
     * @param string $serviceType 'consultation'|'surgery'|'lab'|'radiology'|'emergency'|'pharmacy'|'support'|'dental'
     * @return float
     */
    public function getCopayForService(string $serviceType): float
    {
        $type = strtolower($serviceType);

        switch ($type) {
            case 'consultation':
            case 'checkup':
            case 'appointment':
            case 'doctor':
                return (float)$this->consultation_copay;

            case 'surgery':
            case 'surgeries':
            case 'operation':
                return (float)$this->surgery_copay;

            case 'lab':
            case 'laboratory':
            case 'lab_test':
                return (float)$this->lab_copay;

            case 'radiology':
            case 'xray':
            case 'sonar':
            case 'ultrasound':
            case 'mri':
            case 'echo':
                return (float)$this->radiology_copay;

            case 'support':
            case 'support_services':
            case 'physiotherapy':
            case 'autism':
                return (float)$this->support_services_copay;

            case 'pharmacy':
            case 'medication':
            case 'medicine':
                return (float)$this->medication_copay;

            case 'emergency':
                return (float)$this->emergency_copay;

            case 'dental':
            case 'dentistry':
                return (float)$this->dental_copay;

            default:
                return (float)$this->consultation_copay;
        }
    }
}
