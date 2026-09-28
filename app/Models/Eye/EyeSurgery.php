<?php

namespace App\Models\Eye;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeSurgery extends Model
{
    use HasFactory;

    protected $table = 'eye_surgeries';

    protected $fillable = [
        'patient_id',
        'eye_appointment_id',
        'doctor_id',
        'procedure_name',
        'target_eye',
        'anesthesia_type',
        'iol_item_id',
        'iol_power',
        'iol_serial_number',
        'injection_drug',
        'injection_dose',
        'operative_notes',
        'complications',
        'postop_plan',
        'status',
        'surgery_date',
    ];

    protected $casts = [
        'iol_power'    => 'decimal:2',
        'surgery_date' => 'date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(EyeAppointment::class, 'eye_appointment_id');
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function iolItem(): BelongsTo
    {
        return $this->belongsTo(EyeStoreItem::class, 'iol_item_id');
    }

    public function getAnesthesiaTypeArabicAttribute(): string
    {
        return match ($this->anesthesia_type) {
            'topical'          => 'تخدير موضعي (قطرات)',
            'local_peribulbar' => 'تخدير ناحي (إحصار حول المقلة)',
            'general'          => 'تخدير عام',
            default            => $this->anesthesia_type
        };
    }
}
