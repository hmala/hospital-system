<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabResult extends Model
{
    protected $fillable = [
        'visit_id',
        'request_id',
        'test_name',
        'value',
        'unit',
        'status',
        'reference_range',
        'notes'
    ];

    /**
     * العلاقة مع الزيارة
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * العلاقة مع طلب التحليل
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * تحديد حالة نتيجة التحليل (طبيعي، مرتفع، منخفض)
     */
    public function determineStatus($value, $testName): string
    {
        // تحليل القيمة ومقارنتها بالقيم المرجعية
        $testName = strtolower($testName);

        if (strpos($testName, 'سكر') !== false || strpos($testName, 'glucose') !== false) {
            if ($value < 70) return 'low';
            if ($value > 140) return 'high';
            return 'normal';
        }

        if (strpos($testName, 'ضغط') !== false || strpos($testName, 'pressure') !== false) {
            if ($value > 140) return 'high';
            return 'normal';
        }

        if (strpos($testName, 'كوليسترول') !== false || strpos($testName, 'cholesterol') !== false) {
            if ($value > 200) return 'high';
            return 'normal';
        }

        // يمكن إضافة المزيد من التحليلات حسب الحاجة

        return 'normal';
    }

    /**
     * الحصول على المدى المرجعي للتحليل
     */
    public function getReferenceRange($testName): string
    {
        $testName = strtolower($testName);

        if (strpos($testName, 'سكر') !== false || strpos($testName, 'glucose') !== false) {
            return '70-140 mg/dL';
        }

        if (strpos($testName, 'ضغط') !== false || strpos($testName, 'pressure') !== false) {
            return '< 140 mmHg';
        }

        if (strpos($testName, 'كوليسترول') !== false || strpos($testName, 'cholesterol') !== false) {
            return '< 200 mg/dL';
        }

        // يمكن إضافة المزيد من المديات المرجعية حسب الحاجة

        return '';
    }

    /**
     * الحصول على وحدة القياس للتحليل
     */
    public function getUnit($testName): string
    {
        $testName = strtolower($testName);

        if (strpos($testName, 'سكر') !== false || strpos($testName, 'glucose') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'ضغط') !== false || strpos($testName, 'pressure') !== false) {
            return 'mmHg';
        }

        if (strpos($testName, 'كوليسترول') !== false || strpos($testName, 'cholesterol') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'بيليروبين') !== false || strpos($testName, 'bilirubin') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'كرياتينين') !== false || strpos($testName, 'creatinine') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'يوريا') !== false || strpos($testName, 'urea') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'sgot') !== false || strpos($testName, 'ast') !== false) {
            return 'U/L';
        }

        if (strpos($testName, 'sgpt') !== false || strpos($testName, 'alt') !== false) {
            return 'U/L';
        }

        if (strpos($testName, 'الصفائح') !== false || strpos($testName, 'platelets') !== false) {
            return '/µL';
        }

        if (strpos($testName, 'الهيموغلوبين') !== false || strpos($testName, 'hemoglobin') !== false) {
            return 'g/dL';
        }

        if (strpos($testName, 'الكرات البيضاء') !== false || strpos($testName, 'wbc') !== false) {
            return '/µL';
        }

        if (strpos($testName, 'الكرات الحمراء') !== false || strpos($testName, 'rbc') !== false) {
            return 'million/µL';
        }

        if (strpos($testName, 'الهيماتوكريت') !== false || strpos($testName, 'hematocrit') !== false) {
            return '%';
        }

        if (strpos($testName, 'هرمون') !== false || strpos($testName, 'hormone') !== false) {
            return 'mIU/mL';
        }

        if (strpos($testName, 'فيتامين') !== false || strpos($testName, 'vitamin') !== false) {
            return 'ng/mL';
        }

        if (strpos($testName, 'حديد') !== false || strpos($testName, 'iron') !== false) {
            return 'µg/dL';
        }

        if (strpos($testName, 'كالسيوم') !== false || strpos($testName, 'calcium') !== false) {
            return 'mg/dL';
        }

        if (strpos($testName, 'صوديوم') !== false || strpos($testName, 'sodium') !== false) {
            return 'mEq/L';
        }

        if (strpos($testName, 'بوتاسيوم') !== false || strpos($testName, 'potassium') !== false) {
            return 'mEq/L';
        }

        return '';
    }
}