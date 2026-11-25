<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrLeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_employee_id',
        'user_id',
        'department_id',
        'leave_type',
        'is_paid',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'dept_manager_id',
        'dept_decision_at',
        'dept_notes',
        'admin_approver_id',
        'admin_decision_at',
        'admin_notes',
        'hr_payroll_cycle_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'dept_decision_at' => 'datetime',
            'admin_decision_at' => 'datetime',
        ];
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function deptManager()
    {
        return $this->belongsTo(User::class, 'dept_manager_id');
    }

    public function adminApprover()
    {
        return $this->belongsTo(User::class, 'admin_approver_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function schedules()
    {
        return $this->hasMany(HrSchedule::class, 'hr_leave_request_id');
    }

    public function getLeaveTypeLabelAttribute(): string
    {
        return match ($this->leave_type) {
            'annual' => 'إجازة اعتيادية',
            'sick' => 'إجازة مرضية',
            'emergency' => 'إجازة طارئة',
            'unpaid' => 'إجازة بدون راتب',
            'other' => 'إجازة أخرى',
            default => $this->leave_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_department' => 'بانتظار موافقة مدير القسم',
            'pending_admin' => 'بانتظار موافقة مدير الإدارة',
            'approved' => 'معتمدة نهائياً',
            'rejected' => 'مرفوضة',
            'cancelled' => 'ملغاة',
            default => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'pending_department' => 'bg-warning text-dark',
            'pending_admin' => 'bg-info text-dark',
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }
}
