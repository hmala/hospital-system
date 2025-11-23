<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestResult extends Model
{
    protected $fillable = [
        'visit_id',
        'request_id',
        'test_name',
        'result_value',
        'unit',
        'reference_range',
        'status',
        'result_status',
        'notes',
        'requested_at',
        'completed_at'
    ];

    protected $dates = [
        'requested_at',
        'completed_at'
    ];

    /**
     * العلاقة مع الزيارة
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * العلاقة مع الطلب
     */
    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class);
    }

    /**
     * تحديد حالة نتيجة التحليل (طبيعي، مرتفع، منخفض)
     */
    public function determineResultStatus(): string
    {
        if (!$this->result_value || !$this->reference_range) {
            return 'normal';
        }

        $testName = strtolower($this->test_name);
        $value = floatval($this->result_value);

        if (strpos($testName, 'سكر') !== false || strpos($testName, 'glucose') !== false) {
            if ($value < 70) return 'low';
            if ($value > 140) return 'high';
            return 'normal';
        }

        // يمكن إضافة المزيد من الفحوصات هنا
        
        return 'normal';
    }

    /**
     * تحديث نتيجة التحليل
     */
    public function updateResult($value, $unit = null, $notes = null): void
    {
        $this->result_value = $value;
        if ($unit) $this->unit = $unit;
        if ($notes) $this->notes = $notes;
        
        $this->result_status = $this->determineResultStatus();
        $this->status = 'completed';
        $this->completed_at = now();
        
        $this->save();
    }

    /**
     * الحصول على النص العربي لحالة النتيجة
     */
    public function getResultStatusTextAttribute(): string
    {
        return match($this->result_status) {
            'high' => 'مرتفع',
            'low' => 'منخفض',
            'normal' => 'طبيعي',
            default => 'غير محدد'
        };
    }

    /**
     * الحصول على النص العربي لحالة التحليل
     */
    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            'pending' => 'معلق',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }
}