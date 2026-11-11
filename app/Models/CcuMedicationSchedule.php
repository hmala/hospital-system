<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcuMedicationSchedule extends Model
{
    use HasFactory;

    protected $table = 'ccu_medication_schedules';

    protected $fillable = [
        'icu_admission_id',
        'doctor_id',
        'type',
        'title',
        'dose_or_amount',
        'route',
        'interval_hours',
        'start_time',
        'next_due_at',
        'last_administered_at',
        'last_administered_by',
        'infusion_rate',
        'instructions',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'next_due_at' => 'datetime',
            'last_administered_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function administeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_administered_by');
    }

    public function getIsDueAttribute(): bool
    {
        if (! $this->is_active || ! $this->next_due_at) {
            return false;
        }

        return $this->next_due_at->lte(now());
    }

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->is_active || ! $this->next_due_at) {
            return false;
        }

        return $this->next_due_at->lte(now()->subMinutes(30));
    }

    public function getDueHumanAttribute(): string
    {
        if (! $this->next_due_at) {
            return 'غير محدد';
        }

        $now = now();
        if ($this->next_due_at->lte($now)) {
            $diffMins = (int) $this->next_due_at->diffInMinutes($now);
            if ($diffMins <= 5) {
                return 'مستحق الآن';
            }
            if ($diffMins < 60) {
                return 'متأخر منذ '.$diffMins.' دقيقة';
            }
            $diffHours = (int) $this->next_due_at->diffInHours($now);

            return 'متأخر منذ '.$diffHours.' ساعة';
        }

        $diffMins = (int) $now->diffInMinutes($this->next_due_at);
        if ($diffMins < 60) {
            return 'بعد '.$diffMins.' دقيقة';
        }
        $diffHours = (int) $now->diffInHours($this->next_due_at);

        return 'بعد '.$diffHours.' ساعة';
    }

    public function getTypeArabicAttribute(): string
    {
        return match ($this->type) {
            'infusion' => 'مضخة تسريب وريدي مستمر (Syringe Pump)',
            'feeding' => 'تغذية أنبوبية وحمية سريرية',
            default => 'علاج دوائي مجدول (MAR)'
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'infusion' => 'fas fa-syringe',
            'feeding' => 'fas fa-utensils',
            default => 'fas fa-pills',
        };
    }
}
