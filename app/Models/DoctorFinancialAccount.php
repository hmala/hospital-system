<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorFinancialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'balance',
        'total_earned',
        'total_paid',
        'last_paid_at',
        'notes',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'last_paid_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
