<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuVitalSign extends Model
{
    use HasFactory;

    protected $table = 'icu_vital_signs';

    protected $fillable = [
        'icu_admission_id',
        'recorded_at',
        'recorded_by',
        'gcs',
        'heart_rate',
        'systolic_bp',
        'diastolic_bp',
        'mean_arterial_pressure',
        'respiratory_rate',
        'temperature',
        'spo2',
        'oxygen_device',
        'fio2',
        'peep',
        'ventilator_mode',
        'blood_sugar',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'gcs' => 'integer',
            'heart_rate' => 'integer',
            'systolic_bp' => 'integer',
            'diastolic_bp' => 'integer',
            'mean_arterial_pressure' => 'integer',
            'respiratory_rate' => 'integer',
            'temperature' => 'decimal:1',
            'spo2' => 'integer',
            'fio2' => 'integer',
            'peep' => 'decimal:1',
            'blood_sugar' => 'integer',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * حساب الضغط الشرياني الوسطي MAP تلقائياً إذا لم يُدخل: MAP = (2*DBP + SBP) / 3
     */
    public function getCalculatedMapAttribute(): int
    {
        if ($this->mean_arterial_pressure) {
            return $this->mean_arterial_pressure;
        }

        if ($this->systolic_bp && $this->diastolic_bp) {
            return (int) round(($this->systolic_bp + (2 * $this->diastolic_bp)) / 3);
        }

        return 0;
    }

    public function getGcsAssessmentAttribute(): string
    {
        if (! $this->gcs) {
            return 'غير محدد';
        }
        if ($this->gcs >= 13) {
            return 'وعي مستقر / خفيف (13-15)';
        }
        if ($this->gcs >= 9) {
            return 'متوسط الخطورة (9-12)';
        }

        return 'غيبوبة وحالة حرجة جداً (3-8)';
    }

    public function getGcsBadgeClassAttribute(): string
    {
        if (! $this->gcs) {
            return 'bg-secondary';
        }
        if ($this->gcs >= 13) {
            return 'bg-success';
        }
        if ($this->gcs >= 9) {
            return 'bg-warning text-dark';
        }

        return 'bg-danger text-white';
    }
}
