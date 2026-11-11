<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcuAttachment extends Model
{
    use HasFactory;

    protected $table = 'ccu_attachments';

    protected $fillable = [
        'icu_admission_id',
        'title',
        'attachment_type',
        'file_path',
        'file_name',
        'file_size',
        'notes',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->attachment_type) {
            'ecg' => 'fas fa-heartbeat text-danger',
            'imaging' => 'fas fa-x-ray text-info',
            'lab_external' => 'fas fa-vial text-primary',
            'referral' => 'fas fa-ambulance text-warning',
            default => 'fas fa-file-medical text-secondary',
        };
    }

    public function getTypeNameAttribute(): string
    {
        return match ($this->attachment_type) {
            'ecg' => 'تخطيط قلب ECG',
            'imaging' => 'أشعة أو سونار خارجي',
            'lab_external' => 'تحليل مختبري خارجي',
            'referral' => 'كتاب إحالة / نقل مريض',
            default => 'تقرير طبي عام',
        };
    }
}
