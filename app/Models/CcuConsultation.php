<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcuConsultation extends Model
{
    use HasFactory;

    protected $table = 'ccu_consultations';

    protected $fillable = [
        'icu_admission_id',
        'consultation_type',
        'specialty',
        'doctor_id',
        'external_doctor_name',
        'reason',
        'response',
        'status',
        'priority',
        'fee',
        'original_fee',
        'billing_mode',
        'requested_by',
        'responded_by',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'original_fee' => 'decimal:2',
            'responded_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function getConsultantDisplayNameAttribute(): string
    {
        if ($this->consultation_type === 'external') {
            return $this->external_doctor_name ?: 'استشاري خارجي';
        }

        return $this->doctor?->user?->name ?? $this->doctor?->name ?? 'استشاري داخلي';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'bg-success text-white',
            'cancelled' => 'bg-secondary text-white',
            default => 'bg-warning text-dark',
        };
    }

    public function getStatusNameAttribute(): string
    {
        return match ($this->status) {
            'completed' => 'تم الرد والإنجاز',
            'cancelled' => 'ملغاة',
            default => 'بانتظار رد الاستشاري',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            'stat' => 'bg-danger text-white',
            'urgent' => 'bg-warning text-dark',
            default => 'bg-info text-white',
        };
    }

    public function getPriorityNameAttribute(): string
    {
        return match ($this->priority) {
            'stat' => '🚨 STAT فوري وحرج',
            'urgent' => '⚡ عاجل',
            default => 'اعتيادي',
        };
    }
}
