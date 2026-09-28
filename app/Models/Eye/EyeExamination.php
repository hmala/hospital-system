<?php

namespace App\Models\Eye;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeExamination extends Model
{
    use HasFactory;

    protected $table = 'eye_examinations';

    protected $fillable = [
        'eye_appointment_id',
        'patient_id',
        'doctor_id',
        // حدة الإبصار
        'va_od_unaided',
        'va_os_unaided',
        'va_od_corrected',
        'va_os_corrected',
        'va_od_pinhole',
        'va_os_pinhole',
        // الانكسار والنظارة
        'ref_od_sphere',
        'ref_od_cylinder',
        'ref_od_axis',
        'ref_od_add',
        'ref_os_sphere',
        'ref_os_cylinder',
        'ref_os_axis',
        'ref_os_add',
        'pupillary_distance',
        // ضغط العين
        'iop_od',
        'iop_os',
        'iop_method',
        'iop_time',
        // المصباح الشقي
        'is_od_wnl',
        'is_os_wnl',
        'lids_adnexa_od',
        'lids_adnexa_os',
        'conjunctiva_sclera_od',
        'conjunctiva_sclera_os',
        'cornea_od',
        'cornea_os',
        'anterior_chamber_od',
        'anterior_chamber_os',
        'iris_pupil_od',
        'iris_pupil_os',
        'lens_od',
        'lens_os',
        // قاع العين والشبكية
        'vitreous_od',
        'vitreous_os',
        'cup_to_disc_ratio_od',
        'cup_to_disc_ratio_os',
        'macula_od',
        'macula_os',
        'retina_periphery_od',
        'retina_periphery_os',
        // التشخيص والقرار
        'diagnosis',
        'clinical_notes',
        'management_plan',
        'has_glasses_prescription',
    ];

    protected $casts = [
        'ref_od_sphere'            => 'decimal:2',
        'ref_od_cylinder'          => 'decimal:2',
        'ref_od_axis'              => 'integer',
        'ref_od_add'               => 'decimal:2',
        'ref_os_sphere'            => 'decimal:2',
        'ref_os_cylinder'          => 'decimal:2',
        'ref_os_axis'              => 'integer',
        'ref_os_add'               => 'decimal:2',
        'pupillary_distance'       => 'decimal:1',
        'iop_od'                   => 'decimal:1',
        'iop_os'                   => 'decimal:1',
        'is_od_wnl'                => 'boolean',
        'is_os_wnl'                => 'boolean',
        'has_glasses_prescription' => 'boolean',
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
}
