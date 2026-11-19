<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurgeryConsultation extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'doctor_id',
        'specialty',
        'reason',
        'priority',
        'status',
        'consultant_response',
        'consultant_recommendations',
        'fee',
        'payment_status',
        'payment_id',
        'is_package_included',
        'patient_share',
        'insurance_share',
        'requested_by',
        'responded_by',
        'responded_at',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'patient_share' => 'decimal:2',
        'insurance_share' => 'decimal:2',
        'is_package_included' => 'boolean',
        'responded_at' => 'datetime',
    ];

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function respondedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPaid(): bool
    {
        return $this->payment_status === 'paid';
    }

    public function priorityLabel(): string
    {
        return match ($this->priority) {
            'urgent' => 'عاجلة (Urgent)',
            'stat' => 'طارئة فورية (STAT)',
            default => 'اعتيادية (Routine)',
        };
    }

    public function priorityBadgeClass(): string
    {
        return match ($this->priority) {
            'urgent' => 'bg-warning text-dark border border-warning-subtle',
            'stat' => 'bg-danger text-white shadow-xs',
            default => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'completed' => 'مكتملة ومردود عليها',
            'cancelled' => 'ملغاة',
            default => 'قيد الانتظار',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'completed' => 'bg-success text-white shadow-xs',
            'cancelled' => 'bg-secondary text-white',
            default => 'bg-warning text-dark border border-warning-subtle',
        };
    }
}
