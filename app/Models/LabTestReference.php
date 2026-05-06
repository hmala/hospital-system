<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTestReference extends Model
{
    use HasFactory;

    protected $table = 'lab_test_references';

    protected $fillable = [
        'lab_test_id',
        'gender',
        'age_min',
        'age_max',
        'ref_min',
        'ref_max',
        'ref_text',
        'unit',
        'notes',
    ];

    protected $casts = [
        'ref_min'  => 'float',
        'ref_max'  => 'float',
        'age_min'  => 'integer',
        'age_max'  => 'integer',
    ];

    // ────────────── العلاقات ──────────────

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    // ────────────── Accessors ──────────────

    /**
     * نص المدى المرجعي للعرض: "70 - 110" أو النص المخصص
     */
    public function getRangeDisplayAttribute(): string
    {
        if ($this->ref_text) {
            return $this->ref_text;
        }

        if ($this->ref_min !== null && $this->ref_max !== null) {
            return $this->ref_min . ' - ' . $this->ref_max;
        }

        if ($this->ref_min !== null) {
            return '≥ ' . $this->ref_min;
        }

        if ($this->ref_max !== null) {
            return '< ' . $this->ref_max;
        }

        return '—';
    }

    /**
     * نص الفئة العمرية للعرض
     */
    public function getAgeRangeDisplayAttribute(): string
    {
        if ($this->age_min === 0 && $this->age_max >= 999) {
            return 'جميع الأعمار';
        }

        if ($this->age_max >= 999) {
            return $this->age_min . ' سنة فأكثر';
        }

        return $this->age_min . ' - ' . $this->age_max . ' سنة';
    }

    /**
     * نص الجنس للعرض
     */
    public function getGenderDisplayAttribute(): string
    {
        return match ($this->gender) {
            'male'   => 'ذكور',
            'female' => 'إناث',
            default  => 'الجميع',
        };
    }

    // ────────────── Helpers ──────────────

    /**
     * تقييم قيمة مُدخَلة: normal | high | low | text_match | unknown
     */
    public function evaluate(string|float|null $value): string
    {
        if ($value === null || $value === '') {
            return 'unknown';
        }

        // إذا كان المرجع نصياً (مثل Negative / Positive)
        if ($this->ref_text && !$this->ref_min && !$this->ref_max) {
            return strtolower(trim((string) $value)) === strtolower(trim($this->ref_text))
                ? 'normal'
                : 'abnormal';
        }

        $numeric = is_numeric($value) ? (float) $value : null;

        if ($numeric === null) {
            return 'unknown';
        }

        if ($this->ref_min !== null && $numeric < $this->ref_min) {
            return 'low';
        }

        if ($this->ref_max !== null && $numeric > $this->ref_max) {
            return 'high';
        }

        return 'normal';
    }

    // ────────────── Scope ──────────────

    /**
     * جلب المرجع المناسب لمريض بحسب الجنس والعمر
     */
    public static function forPatient(int $labTestId, string $gender, int $age): ?self
    {
        return static::where('lab_test_id', $labTestId)
            ->where(function ($q) use ($gender) {
                $q->where('gender', $gender)->orWhere('gender', 'both');
            })
            ->where('age_min', '<=', $age)
            ->where('age_max', '>=', $age)
            ->orderByRaw("CASE WHEN gender = ? THEN 0 ELSE 1 END", [$gender]) // الجنس المحدد أولاً
            ->first();
    }
}
