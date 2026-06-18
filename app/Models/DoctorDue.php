<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorDue extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'payment_id',
        'amount',
        'status',
        'notes',
        'paid_by_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by_id');
    }
}
