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
        'overall_score',
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
     * حساب النسبة المئوية العامة لرضا المريض بناءً على التقييمات المدخلة
     */
    public function calculateOverallScore(): float
    {
        $ratingFields = [
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
            $this->doctor_communication,
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
