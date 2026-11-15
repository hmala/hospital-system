<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrEmployee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_code',
        'user_id',
        'department_id',
        'full_name',
        'national_id',
        'nationality',
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
        // حقول الرواتب الجديدة
        'payment_method', 'bank_name', 'bank_account_number',
        'overtime_hourly_rate',
        'subject_to_social_security', 'social_security_percentage',
        'subject_to_tax', 'tax_percentage',
        // حقول الدوام والشفتات والعطلات
        'schedule_type',
        'default_shift_id',
        'is_split_shift',
        'second_shift_id',
        'custom_start_time',
        'custom_end_time',
        'weekly_off_days',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'hire_date' => 'date',
        'contract_end_date' => 'date',
        'license_expiry_date' => 'date',
        'basic_salary' => 'decimal:2',
        'overtime_hourly_rate' => 'decimal:2',
        'social_security_percentage' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'subject_to_social_security' => 'boolean',
        'subject_to_tax' => 'boolean',
        'is_split_shift' => 'boolean',
        'weekly_off_days' => 'array',
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

    public function defaultShift()
    {
        return $this->belongsTo(HrShift::class, 'default_shift_id');
    }

    public function secondShift()
    {
        return $this->belongsTo(HrShift::class, 'second_shift_id');
    }

    public function schedules()
    {
        return $this->hasMany(HrSchedule::class, 'hr_employee_id');
    }

    public function documents()
    {
        return $this->hasMany(HrEmployeeDocument::class, 'employee_id');
    }

    public function allowances()
    {
        return $this->hasMany(HrEmployeeAllowance::class, 'hr_employee_id');
    }

    public function activeAllowances()
    {
        return $this->hasMany(HrEmployeeAllowance::class, 'hr_employee_id')->where('is_active', true);
    }

    public function loans()
    {
        return $this->hasMany(HrLoan::class, 'hr_employee_id');
    }

    public function activeLoans()
    {
        return $this->hasMany(HrLoan::class, 'hr_employee_id')->where('status', 'active');
    }

    public function absences()
    {
        return $this->hasMany(HrAbsence::class, 'hr_employee_id');
    }

    public function overtimes()
    {
        return $this->hasMany(HrOvertime::class, 'hr_employee_id');
    }

    // التحقق من طبيعة الكادر
    public function isMedicalStaff(): bool
    {
        return in_array($this->staff_type, ['medical', 'nursing', 'technical']);
    }

    // فحص قرب انتهاء إجازة ممارسة المهنة
    public function isLicenseExpiringSoon(int $days = 30): bool
    {
        if (! $this->license_expiry_date) {
            return false;
        }

        return $this->license_expiry_date->isFuture() && $this->license_expiry_date->diffInDays(now()) <= $days;
    }

    // فحص انتهاء الرخصة
    public function isLicenseExpired(): bool
    {
        if (! $this->license_expiry_date) {
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
            'full_time', 'Fulltime' => 'دوام كامل',
            'part_time' => 'دوام جزئي',
            'contract' => 'عقد محدد المدة',
            'gov_contract' => 'عقد وزاري / حكومي',
            'daily_shift' => 'أجر يومي / خفارات',
            default => HrLookupOption::where('category', 'employment_type')
                ->where('code', $this->employment_type)
                ->value('name') ?? (string) $this->employment_type,
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

    public function actions()
    {
        return $this->hasMany(HrEmployeeAction::class, 'hr_employee_id');
    }

    public function getScheduleTypeNameAttribute(): string
    {
        return match ($this->schedule_type) {
            'fixed' => 'دوام ثابت',
            'rotating' => 'خفارات متناوبة',
            default => $this->schedule_type ?? 'دوام ثابت',
        };
    }

    public function getWeeklyOffDaysListAttribute(): array
    {
        return is_array($this->weekly_off_days) ? $this->weekly_off_days : ['friday'];
    }

    public function getWeeklyOffDaysArabicAttribute(): string
    {
        $days = $this->weekly_off_days_list;
        $map = [
            'sunday' => 'الأحد',
            'monday' => 'الاثنين',
            'tuesday' => 'الثلاثاء',
            'wednesday' => 'الأربعاء',
            'thursday' => 'الخميس',
            'friday' => 'الجمعة',
            'saturday' => 'السبت',
        ];

        $arabicDays = array_map(fn ($day) => $map[strtolower($day)] ?? $day, $days);

        return empty($arabicDays) ? 'لا يوجد' : implode('، ', $arabicDays);
    }

    public function isOffOn(string|\Carbon\Carbon $date): bool
    {
        $carbonDate = is_a($date, \Carbon\Carbon::class) ? $date : \Carbon\Carbon::parse($date);
        $dayName = strtolower($carbonDate->format('l'));

        return in_array($dayName, $this->weekly_off_days_list);
    }
}
