<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPayroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_payroll_cycle_id',
        'hr_employee_id',
        'basic_salary',
        'allowances',
        'overtime_amount',
        'absence_deduction',
        'loan_deduction',
        'social_security_amount',
        'tax_amount',
        'bonuses_amount',
        'penalties_amount',
        'net_salary',
        'payment_method',
        'is_paid',
        'paid_at',
        'status',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'overtime_amount' => 'decimal:2',
        'absence_deduction' => 'decimal:2',
        'loan_deduction' => 'decimal:2',
        'social_security_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'bonuses_amount' => 'decimal:2',
        'penalties_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'payment_date' => 'date',
    ];

    public function cycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function getTotalEarningsAttribute(): float
    {
        return (float) ($this->basic_salary + $this->allowances + $this->overtime_amount + $this->bonuses_amount);
    }

    public function getTotalDeductionsAttribute(): float
    {
        return (float) ($this->penalties_amount + $this->absence_deduction + $this->loan_deduction + $this->social_security_amount + $this->tax_amount);
    }
}
