<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStation extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'resident_id',
        'phase',
        'chief_complaint',
        'history_present_illness',
        'past_medical_hx',
        'past_surgical_hx',
        'drug_hx',
        'drug_allergy',
        'clinical_examination',
        'bp',
        'temp',
        'pr',
        'rr',
        'spo2',
        'pain_score',
        'rbs',
        'gcs',
        'crt',
        'intake_iv_fluids',
        'intake_oral',
        'intake_blood',
        'output_urine',
        'output_drain',
        'output_gtube_ng',
        'output_vomiting',
        'output_stool',
        'fluid_balance',
        'review_of_other_systems',
        'notes',
        'post_op_notes',
        'treatment_plan',
        'follow_up_date',
        'status',
        'started_at',
        'completed_at',
        'vitals_recorded_at',
        'vitals_recorded_by',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'vitals_recorded_at' => 'datetime',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function resident()
    {
        return $this->belongsTo(Doctor::class, 'resident_id');
    }

    public function vitalsRecordedBy()
    {
        return $this->belongsTo(User::class, 'vitals_recorded_by');
    }

    /**
     * هل يحق للمستخدم تعديل العلامات الحيوية الحالية (خلال 30 دقيقة لنفس الحساب أو للأدمن حصراً)
     */
    public function canUserEditVitals(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // 1. مدير النظام يملك صلاحية التعديل والتصحيح دائماً
        if ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            return true;
        }

        // 2. إذا لم يتم تسجيل قراءة سابقة بعد، يتاح الإدخال لأي مستخدم مصرح له بالكتابة
        if (! $this->vitals_recorded_at || ! $this->vitals_recorded_by) {
            return true;
        }

        // 3. يجب أن يكون التعديل من نفس الحساب الذي سجل القراءة حصراً
        if ((int) $this->vitals_recorded_by !== (int) $user->id) {
            return false;
        }

        // 4. يجب أن يكون التعديل خلال 30 دقيقة من وقت التسجيل
        return now()->lte($this->vitals_recorded_at->copy()->addMinutes(30));
    }

    public function readings()
    {
        return $this->hasMany(ResidentStationReading::class, 'resident_station_id')->orderByDesc('created_at')->orderByDesc('id');
    }

    public function followUps()
    {
        return $this->hasMany(ResidentStationFollowUp::class, 'resident_station_id')->orderBy('follow_up_date')->orderByDesc('created_at');
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}
