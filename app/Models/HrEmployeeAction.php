<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_employee_id',
        'hr_action_setting_id',
        'action_date',
        'reason',
        'applied_effect_type',
        'applied_effect_value',
        'financial_amount',
        'status',
        'created_by',
        'hr_payroll_cycle_id',
    ];

    protected $casts = [
        'action_date' => 'date',
        'applied_effect_value' => 'decimal:2',
        'financial_amount' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function actionSetting()
    {
        return $this->belongsTo(HrActionSetting::class, 'hr_action_setting_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }
}
