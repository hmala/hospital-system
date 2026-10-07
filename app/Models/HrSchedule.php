<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrSchedule extends Model
{
    protected $fillable = [
        'hr_employee_id', 'hr_shift_id', 'shift_date', 'is_off_day', 
        'attendance_status', 'actual_check_in', 'actual_check_out', 
        'notes', 'created_by'
    ];

    protected $casts = [
        'shift_date' => 'date',
        'is_off_day' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function shift()
    {
        return $this->belongsTo(HrShift::class, 'hr_shift_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
