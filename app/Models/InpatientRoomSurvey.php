<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InpatientRoomSurvey extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'surgery_id',
        'patient_id',
        'doctor_id',
        'room_id',
        'location_id',
        'room_type',
        'survey_date',
        'status',
        'cleanliness_room',
        'cleanliness_bathroom',
        'temperature_ac',
        'lighting',
        'quietness_privacy',
        'bed_comfort',
        'control_panel',
        'storage_space',
        'furniture_safety',
        'entertainment',
        'room_notes',
        'top_three_improvements',
        'patient_suggestions',
        'recommend_score_nps',
        'doctor_communication',
        'doctor_notes',
        // الورقة 2: تقييم طاقم التمريض (6 بنود + ملاحظات)
        'nursing_greeting',
        'nursing_skill',
        'nursing_care_attention',
        'nursing_instructions',
        'nursing_response_speed',
        'nursing_privacy_respect',
        'nursing_notes',
        // الورقة 2: تقييم قسم المختبر والتحاليل (5 بنود + ملاحظات)
        'lab_prep_instructions',
        'lab_sampling_skill',
        'lab_hygiene_sterilization',
        'lab_results_speed',
        'lab_staff_behavior',
        'lab_notes',
        // الورقة 2: تقييم الصيدلية والتعامل مع الأدوية (5 بنود + ملاحظات)
        'pharmacy_dispense_speed',
        'pharmacy_instruction_clarity',
        'pharmacy_comprehension_check',
        'pharmacy_respect_politeness',
        'pharmacy_med_availability',
        'pharmacy_notes',
        // بيانات وتوقيع المريض (أسفل الورقة 2)
        'respondent_name',
        'respondent_signature',
        'respondent_phone',
        'respondent_email',
        // الدرجات الفرعية والإجمالية
        'overall_score',
        'room_score',
        'nursing_score',
        'lab_score',
        'pharmacy_score',
        'submitted_via',
        'submitted_by_user_id',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'survey_date' => 'date',
            'completed_at' => 'datetime',
            'top_three_improvements' => 'array',
            'overall_score' => 'float',
            'room_score' => 'float',
            'nursing_score' => 'float',
            'lab_score' => 'float',
            'pharmacy_score' => 'float',
            'recommend_score_nps' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InpatientRoomSurvey $survey) {
            if (empty($survey->uuid)) {
                $survey->uuid = (string) Str::uuid();
            }
            if (empty($survey->survey_date)) {
                $survey->survey_date = now()->toDateString();
            }
        });
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(Surgery::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * احتساب نسبة رضا المريض عن مرافق الغرفة (10 بنود)
     */
    public function calculateRoomScore(): ?float
    {
        $fields = [
            $this->cleanliness_room,
            $this->cleanliness_bathroom,
            $this->temperature_ac,
            $this->lighting,
            $this->quietness_privacy,
            $this->bed_comfort,
            $this->control_panel,
            $this->storage_space,
            $this->furniture_safety,
            $this->entertainment,
        ];
        return $this->computeAveragePercentage($fields);
    }

    /**
     * احتساب نسبة رضا المريض عن طاقم التمريض (6 بنود - الورقة 2)
     */
    public function calculateNursingScore(): ?float
    {
        $fields = [
            $this->nursing_greeting,
            $this->nursing_skill,
            $this->nursing_care_attention,
            $this->nursing_instructions,
            $this->nursing_response_speed,
            $this->nursing_privacy_respect,
        ];
        return $this->computeAveragePercentage($fields);
    }

    /**
     * احتساب نسبة رضا المريض عن قسم المختبر والتحاليل (5 بنود - الورقة 2)
     */
    public function calculateLabScore(): ?float
    {
        $fields = [
            $this->lab_prep_instructions,
            $this->lab_sampling_skill,
            $this->lab_hygiene_sterilization,
            $this->lab_results_speed,
            $this->lab_staff_behavior,
        ];
        return $this->computeAveragePercentage($fields);
    }

    /**
     * احتساب نسبة رضا المريض عن الصيدلية والتعامل مع الأدوية (5 بنود - الورقة 2)
     */
    public function calculatePharmacyScore(): ?float
    {
        $fields = [
            $this->pharmacy_dispense_speed,
            $this->pharmacy_instruction_clarity,
            $this->pharmacy_comprehension_check,
            $this->pharmacy_respect_politeness,
            $this->pharmacy_med_availability,
        ];
        return $this->computeAveragePercentage($fields);
    }

    /**
     * دالة مساعدة لحساب متوسط النسبة المئوية لمجموعة بنود
     */
    protected function computeAveragePercentage(array $fields): ?float
    {
        $answered = array_filter($fields, fn ($val) => ! is_null($val) && $val > 0);
        if (empty($answered)) {
            return null;
        }
        return round((array_sum($answered) / (count($answered) * 5)) * 100, 1);
    }

    /**
     * حساب النسبة المئوية العامة لرضا المريض بناءً على جميع التقييمات المدخلة
     */
    public function calculateOverallScore(): float
    {
        $ratingFields = [
            // الغرفة
            $this->cleanliness_room,
            $this->cleanliness_bathroom,
            $this->temperature_ac,
            $this->lighting,
            $this->quietness_privacy,
            $this->bed_comfort,
            $this->control_panel,
            $this->storage_space,
            $this->furniture_safety,
            $this->entertainment,
            // الطبيب
            $this->doctor_communication,
            // التمريض (الورقة 2)
            $this->nursing_greeting,
            $this->nursing_skill,
            $this->nursing_care_attention,
            $this->nursing_instructions,
            $this->nursing_response_speed,
            $this->nursing_privacy_respect,
            // المختبر (الورقة 2)
            $this->lab_prep_instructions,
            $this->lab_sampling_skill,
            $this->lab_hygiene_sterilization,
            $this->lab_results_speed,
            $this->lab_staff_behavior,
            // الصيدلية (الورقة 2)
            $this->pharmacy_dispense_speed,
            $this->pharmacy_instruction_clarity,
            $this->pharmacy_comprehension_check,
            $this->pharmacy_respect_politeness,
            $this->pharmacy_med_availability,
        ];

        $answered = array_filter($ratingFields, fn ($val) => ! is_null($val) && $val > 0);

        if (empty($answered)) {
            return 0.0;
        }

        $sum = array_sum($answered);
        $maxPossible = count($answered) * 5;

        return round(($sum / $maxPossible) * 100, 1);
    }

    public static function ratingLabels(): array
    {
        return [
            5 => 'ممتاز',
            4 => 'جيد جداً',
            3 => 'جيد',
            2 => 'مقبول',
            1 => 'ضعيف',
        ];
    }

    public function getRatingLabel(?int $score): string
    {
        return self::ratingLabels()[$score] ?? 'غير محدد';
    }
}
