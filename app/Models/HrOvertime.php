<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrOvertime extends Model
{
    protected $fillable = [
        'hr_employee_id', 'overtime_date', 'input_type',
        'hours_count', 'hourly_rate_used', 'manual_amount',
        'total_amount', 'status', 'hr_payroll_cycle_id',
        'description', 'created_by',
    ];

    protected $casts = [
        'overtime_date'    => 'date',
        'hours_count'      => 'decimal:2',
        'hourly_rate_used' => 'decimal:2',
        'manual_amount'    => 'decimal:2',
        'total_amount'     => 'decimal:2',
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
     * حساب إجمالي مبلغ الإضافي بناءً على طريقة الإدخال
     */
    public static function calculateTotal(string $inputType, ?float $hours, ?float $rate, ?float $manual): float
    {
        if ($inputType === 'hours' && $hours && $rate) {
            return round($hours * $rate, 2);
        }
        return round($manual ?? 0, 2);
    }
}
