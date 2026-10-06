<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrEmployeeAllowance extends Model
{
    protected $fillable = [
        'hr_employee_id', 'title', 'amount', 'is_active', 'notes', 'created_by',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
