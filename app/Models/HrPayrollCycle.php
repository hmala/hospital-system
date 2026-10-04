<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrPayrollCycle extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_month',
        'start_date',
        'end_date',
        'status',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function payrolls()
    {
        return $this->hasMany(HrPayroll::class, 'hr_payroll_cycle_id');
    }

    public function actions()
    {
        return $this->hasMany(HrEmployeeAction::class, 'hr_payroll_cycle_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
