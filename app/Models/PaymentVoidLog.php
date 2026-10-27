<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentVoidLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'receipt_number',
        'patient_id',
        'cashier_id',
        'voided_by',
        'amount',
        'patient_share',
        'insurance_share',
        'void_type',
        'void_reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'insurance_share' => 'decimal:2',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }
}
