<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NicuDeliveryObservation extends Model
{
    use HasFactory;

    public const STATUS_UNDER_OBSERVATION = 'under_observation';

    public const STATUS_READY_FOR_HANDOVER = 'ready_for_handover';

    public const STATUS_HANDED_OVER = 'handed_over';

    public const STATUS_ADMITTED_TO_NICU = 'admitted_to_nicu';

    public const STATUS_PARTIAL = 'partial';

    protected $fillable = [
        'surgery_id',
        'patient_id',
        'mother_name',
        'mother_age_gender',
        'mother_birth_date',
        'reference_date',
        'department_name',
        'requesting_doctor_name',
        'report_type',
        'stay_type',
        'admission_date',
        'discharge_date',
        'operative_description',
        'delivery_date',
        'delivery_time',
        'case_of_delivery',
        'no_of_borns',
        'pre_delivery_diagnosis',
        'post_delivery_diagnosis',
        'procedure_performed',
        'surgeon_name',
        'anesthesia_type',
        'assessment',
        'history_of_illness',
        'delivery_note',
        'treatments',
        'started_at',
        'target_duration_minutes',
        'completed_at',
        'status',
        'is_billable',
        'observation_fee',
        'payment_status',
        'nicu_doctor_id',
        'nicu_doctor_name',
        'handover_text',
        'recipient_name',
        'recipient_relation',
        'recipient_id_number',
        'recipient_phone',
        'recipient_address',
        'handed_over_at',
        'handed_over_by',
        'signed_form_path',
        'signed_form_uploaded_at',
        'signed_form_uploaded_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'mother_birth_date' => 'date',
            'reference_date' => 'date',
            'admission_date' => 'date',
            'discharge_date' => 'date',
            'delivery_date' => 'date',
            'treatments' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'handed_over_at' => 'datetime',
            'signed_form_uploaded_at' => 'datetime',
            'target_duration_minutes' => 'integer',
            'no_of_borns' => 'integer',
            'is_billable' => 'boolean',
            'observation_fee' => 'decimal:2',
        ];
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function nicuDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nicu_doctor_id');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function signedFormUploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_form_uploaded_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function babies(): HasMany
    {
        return $this->hasMany(NicuDeliveryObservationBaby::class, 'observation_id')->orderBy('id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_UNDER_OBSERVATION => 'تحت المعاينة',
            self::STATUS_READY_FOR_HANDOVER => 'مكتمل المدة / جاهز للتسليم',
            self::STATUS_HANDED_OVER => 'تم التسليم للأهل',
            self::STATUS_ADMITTED_TO_NICU => 'محول للحاضنة (NICU)',
            self::STATUS_PARTIAL => 'جزئي (تسليم + حاضنة)',
            default => (string) $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_UNDER_OBSERVATION => 'bg-warning text-dark',
            self::STATUS_READY_FOR_HANDOVER => 'bg-success text-white',
            self::STATUS_HANDED_OVER => 'bg-primary text-white',
            self::STATUS_ADMITTED_TO_NICU => 'bg-info text-dark',
            self::STATUS_PARTIAL => 'bg-secondary text-white',
            default => 'bg-light text-dark',
        };
    }

    public function getElapsedMinutesAttribute(): int
    {
        if (! $this->started_at) {
            return 0;
        }

        $end = $this->completed_at ?? $this->handed_over_at ?? now();

        return max(0, (int) $this->started_at->diffInMinutes($end));
    }

    public function isDurationCompleted(): bool
    {
        return $this->elapsed_minutes >= max(1, (int) $this->target_duration_minutes);
    }

    public function recalculateStatus(): void
    {
        $babies = $this->babies()->get();

        if ($babies->isEmpty()) {
            return;
        }

        $observingCount = $babies->where('disposition', NicuDeliveryObservationBaby::DISPOSITION_OBSERVING)->count();
        $handedCount = $babies->where('disposition', NicuDeliveryObservationBaby::DISPOSITION_HANDED_OVER)->count();
        $admittedCount = $babies->where('disposition', NicuDeliveryObservationBaby::DISPOSITION_ADMITTED_TO_NICU)->count();

        if ($observingCount === 0) {
            if ($admittedCount > 0 && $handedCount > 0) {
                $this->status = self::STATUS_PARTIAL;
            } elseif ($admittedCount > 0) {
                $this->status = self::STATUS_ADMITTED_TO_NICU;
            } else {
                $this->status = self::STATUS_HANDED_OVER;
            }
            $this->completed_at = $this->completed_at ?? now();
        } elseif ($admittedCount > 0 || $handedCount > 0) {
            $this->status = self::STATUS_PARTIAL;
        } elseif ($this->isDurationCompleted()) {
            $this->status = self::STATUS_READY_FOR_HANDOVER;
        } else {
            $this->status = self::STATUS_UNDER_OBSERVATION;
        }

        $this->save();
    }
}
