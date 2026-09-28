<?php

namespace App\Models\Eye;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EyeInvoice extends Model
{
    use HasFactory;

    protected $table = 'eye_invoices';

    protected $fillable = [
        'invoice_number',
        'patient_id',
        'eye_appointment_id',
        'total_amount',
        'patient_share',
        'insurance_share',
        'discount',
        'net_amount',
        'paid_amount',
        'payment_method',
        'insurance_type',
        'status',
        'cashier_id',
        'reconciled_with_hospital',
        'reconciled_at',
    ];

    protected $casts = [
        'total_amount'             => 'decimal:2',
        'patient_share'            => 'decimal:2',
        'insurance_share'          => 'decimal:2',
        'discount'                 => 'decimal:2',
        'net_amount'               => 'decimal:2',
        'paid_amount'              => 'decimal:2',
        'reconciled_with_hospital' => 'boolean',
        'reconciled_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (empty($model->invoice_number)) {
                $model->invoice_number = 'INV-EYE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(EyeAppointment::class, 'eye_appointment_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(EyeInvoiceItem::class);
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'paid'           => 'مدفوع بالكامل',
            'partially_paid' => 'مدفوع جزئياً',
            'refunded'       => 'مسترجع',
            'pending'        => 'معلق',
            default          => $this->status
        };
    }
}
