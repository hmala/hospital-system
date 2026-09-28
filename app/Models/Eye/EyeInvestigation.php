<?php

namespace App\Models\Eye;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeInvestigation extends Model
{
    use HasFactory;

    protected $table = 'eye_investigations';

    protected $fillable = [
        'patient_id',
        'eye_appointment_id',
        'investigation_type',
        'eye_target',
        'status',
        'findings',
        'conclusion',
        'measurement_data',
        'attachment_path',
        'requested_by',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'measurement_data' => 'array',
        'performed_at'     => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(EyeAppointment::class, 'eye_appointment_id');
    }

    public function requestedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function performedStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function getInvestigationTypeArabicAttribute(): string
    {
        return match ($this->investigation_type) {
            'oct_macula'          => 'تصوير طبقي للشبكية (OCT Macula)',
            'oct_optic_disc'      => 'تصوير العصب البصري (OCT Disc)',
            'visual_field'        => 'فحص مجال البصر (Visual Field)',
            'pentacam_topography' => 'تضاريس القرنية (Pentacam / Topography)',
            'biometry_iol'        => 'قياس قوة العدسة (Biometry / IOL)',
            'fundus_photography'  => 'تصوير قاع العين الملون (Fundus Photo)',
            'b_scan'              => 'سونار العين (B-Scan Ultrasound)',
            default               => $this->investigation_type
        };
    }

    public function getStatusArabicAttribute(): string
    {
        return match ($this->status) {
            'requested'   => 'مطلوب ⏳',
            'in_progress' => 'قيد الفحص 🔬',
            'completed'   => 'مكتمل ومعتمد ✅',
            'cancelled'   => 'ملغي ❌',
            default       => $this->status
        };
    }
}
