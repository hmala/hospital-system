<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuConsultation extends Model
{
    use HasFactory;

    protected $table = 'nicu_consultations';

    protected $fillable = [
        'reservation_id',
        'consultation_type',
        'doctor_id',
        'external_doctor_name',
        'specialty',
        'fee',
        'reason',
        'findings',
        'recommendations',
        'status',
        'requested_by',
        'completed_at',
    ];

    protected $casts = [
        'fee' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    const TYPE_INTERNAL = 'internal';
    const TYPE_EXTERNAL = 'external';

    const STATUS_REQUESTED = 'requested';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function internalDoctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function getDoctorDisplayNameAttribute(): string
    {
        if ($this->consultation_type === self::TYPE_INTERNAL && $this->internalDoctor) {
            return $this->internalDoctor->user?->name ?? 'طبيب المستشفى الداخلي';
        }

        return $this->external_doctor_name ?? 'طبيب استشاري خارجي';
    }
}
