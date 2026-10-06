<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAbsence extends Model
{
    protected $fillable = [
        'hr_employee_id', 'absence_date', 'days_count', 'type',
        'is_excused', 'deduction_amount', 'status',
        'hr_payroll_cycle_id', 'reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'absence_date'     => 'date',
        'days_count'       => 'decimal:2',
        'is_excused'       => 'boolean',
        'deduction_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * حساب قيمة الخصم بناءً على الراتب الأساسي للموظف
     */
    public function calculateDeduction(): float
    {
        if ($this->is_excused) return 0;

        $dailyRate = $this->employee->basic_salary / 30;
        return round($dailyRate * $this->days_count, 2);
    }

    public function getTypeArabicAttribute(): string
    {
        return match($this->type) {
            'absence'     => 'غياب',
            'late'        => 'تأخير',
            'early_leave' => 'انصراف مبكر',
            default       => $this->type,
        };
    }
}
