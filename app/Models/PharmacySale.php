<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacySale extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_sales';

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'doctor_id',
        'visit_id',
        'patient_name',
        'patient_phone',
        'sale_type',
        'total_amount',
        'patient_share',
        'insurance_share',
        'insurance_type',
        'health_insurance_category_id',
        'insurance_card_no',
        'copay_percentage',
        'claim_status',
        'payment_status',
        'dispensing_status',
        'payment_route',
        'is_held',
        'payment_id',
        'user_id',
        'dispensed_by',
        'dispensed_at',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'patient_share' => 'float',
        'insurance_share' => 'float',
        'copay_percentage' => 'float',
        'is_held' => 'boolean',
        'dispensed_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id');
    }

    public function healthInsuranceCategory()
    {
        return $this->belongsTo(HealthInsuranceCategory::class, 'health_insurance_category_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class, 'payment_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function dispenser()
    {
        return $this->belongsTo(User::class, 'dispensed_by');
    }

    public function items()
    {
        return $this->hasMany(PharmacySaleItem::class, 'sale_id');
    }

    public function scopeHeld($query)
    {
        return $query->where('is_held', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_held', false);
    }

    public function scopePendingCashier($query)
    {
        return $query->where('payment_status', 'pending_cashier');
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', now()->toDateString());
    }

    /**
     * توليد رقم فاتورة تلقائي فريد بتنسيق PH-YYYYMMDD-####
     */
    public static function generateInvoiceNumber(): string
    {
        $prefix = 'PH-' . date('Ymd') . '-';
        $lastSale = self::where('invoice_number', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = 1;
        if ($lastSale) {
            $lastSequence = (int) substr($lastSale->invoice_number, -4);
            $nextNumber = $lastSequence + 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
