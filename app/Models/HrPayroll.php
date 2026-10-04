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
        'bonuses_amount',
        'penalties_amount',
        'net_salary',
        'status',
        'payment_date',
        'notes'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'allowances' => 'decimal:2',
        'bonuses_amount' => 'decimal:2',
        'penalties_amount' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function cycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }
}
