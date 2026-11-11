<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcuDoctorNote extends Model
{
    use HasFactory;

    protected $table = 'ccu_doctor_notes';

    protected $fillable = [
        'icu_admission_id',
        'doctor_id',
        'note_text',
        'priority',
        'is_pinned',
        'created_by',
        'pinned_at',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'pinned_at' => 'datetime',
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
            'stat' => '🚨 فوري وحرج جداً (STAT)',
            'urgent' => '⚡ عاجل (Urgent)',
            default => 'اعتيادي (Routine)',
        };
    }
}
