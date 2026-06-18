<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorCommissionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'department_id',
        'service_type_id',
        'commission_type',
        'commission_value',
        'fixed_amount',
        'is_active',
        'valid_from',
        'valid_until',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
