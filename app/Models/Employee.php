<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_code',
        'user_id',
        'department_id',
        'full_name',
        'national_id',
        'gender',
        'date_of_birth',
        'phone',
        'emergency_phone',
        'email',
        'address',
        'blood_group',
        'staff_type',
        'job_title',
        'employment_type',
        'hire_date',
        'contract_end_date',
        'basic_salary',
        'status',
        'medical_license_number',
        'license_expiry_date',
        'syndicate_card_number',
        'sub_specialty',
        'qualification',
        'profile_photo',
        'notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'license_expiry_date' => 'date',
        'basic_salary' => 'decimal:2',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    // التحقق من طبيعة الكادر
    public function isMedicalStaff(): bool
    {
        return in_array($this->staff_type, ['medical', 'nursing', 'technical']);
    }

    // فحص قرب انتهاء إجازة ممارسة المهنة
    public function isLicenseExpiringSoon(int $days = 30): bool
    {
        if (!$this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isFuture() && $this->license_expiry_date->diffInDays(now()) <= $days;
    }

    // فحص انتهاء الرخصة
    public function isLicenseExpired(): bool
    {
        if (!$this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isPast();
    }

    // مسميات الأنواع بالعربية
    public function getStaffTypeNameAttribute(): string
    {
        return match ($this->staff_type) {
            'medical' => 'كادر طبي',
            'nursing' => 'كادر تمريضي',
            'technical' => 'كادر فني ومختبري',
            'administrative' => 'كادر إداري ومالي',
            'service' => 'خدمات وصيانة',
            default => $this->staff_type,
        };
    }

    public function getEmploymentTypeNameAttribute(): string
    {
        return match ($this->employment_type) {
            'full_time' => 'دوام كامل',
            'part_time' => 'دوام جزئي',
            'contract' => 'عقد محدد المدة',
            'daily_shift' => 'أجر يومي / خفارات',
            default => $this->employment_type,
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'active' => 'على رأس العمل',
            'on_leave' => 'في إجازة',
            'suspended' => 'موقوف مؤقتاً',
            'resigned' => 'مستقيل',
            'terminated' => 'منهي خدماته',
            default => $this->status,
        };
    }

    // Scopes للفلترة السريعة
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeMedical($query)
    {
        return $query->whereIn('staff_type', ['medical', 'nursing', 'technical']);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }
}
