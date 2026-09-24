<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'medicine_id',
        'medicine_name',
        'quantity',
        'unit_type',
        'dosage_frequency',
        'duration_days',
        'instructions',
        'status',
        'dispensed_medicine_id',
        'suggested_medicine_id',
        'substitution_status',
        'substitution_reason',
        'substitution_response_notes',
        'substitution_responded_at',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'duration_days' => 'integer',
        'substitution_responded_at' => 'datetime',
    ];

    // ────────────── العلاقات ──────────────

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function dispensedMedicine()
    {
        return $this->belongsTo(Medicine::class, 'dispensed_medicine_id');
    }

    public function suggestedMedicine()
    {
        return $this->belongsTo(Medicine::class, 'suggested_medicine_id');
    }

    // ────────────── Accessors ──────────────

    public function getUnitNameAttribute()
    {
        if ($this->unit_type === 'sub_unit') {
            return $this->medicine?->sub_unit ?? 'شريط';
        }
        return $this->medicine?->main_unit ?? 'علبة';
    }

    public function getEffectiveMedicineAttribute()
    {
        return $this->dispensedMedicine ?? $this->medicine;
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'قيد الصرف',
            'dispensed' => 'تم الصرف',
            'substituted' => 'صُرف دواء بديل',
            'out_of_stock' => 'غير متوفر',
            'cancelled' => 'ملغي',
            default => $this->status,
        };
    }
}
