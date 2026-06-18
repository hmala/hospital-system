<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultationRevenue extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'payment_method',
        'payment_type',
        'cashier_id',
        'appointment_id',
        'patient_id',
        'doctor_id',
        'department_id',
        'service_type_id',
        'examination_count',
        'total_amount',
        'doctor_share',
        'hospital_share',
        'doctor_percentage',
        'movement_type',
        'revenue_date',
        'paid_at',
        'notes',
        'transaction_reference',
    ];

    protected $casts = [
        'revenue_date' => 'date',
        'paid_at' => 'datetime',
        'total_amount' => 'decimal:2',
        'doctor_share' => 'decimal:2',
        'hospital_share' => 'decimal:2',
        'doctor_percentage' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function serviceType()
    {
        return $this->belongsTo(ServiceType::class);
    }
}
