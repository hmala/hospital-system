<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryExtraService extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'service_id',
        'name',
        'price',
        'quantity',
        'total_amount',
        'notes',
        'payment_status',
        'payment_id',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function service()
    {
        return $this->belongsTo(HospitalExtraService::class, 'service_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
