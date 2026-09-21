<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabTestSubTest extends Model
{
    use HasFactory;

    protected $table = 'lab_test_sub_tests';

    protected $fillable = [
        'lab_test_id',
        'name',
        'unit',
        'reference_range',
        'result_type',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * العلاقة مع الفحص المختبري الرئيسي
     */
    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    /**
     * استخراج الحد الأدنى من المدى الطبيعي
     */
    public function getRefMinAttribute(): ?float
    {
        if (empty($this->reference_range)) return null;
        $clean = str_replace(',', '', $this->reference_range);
        if (preg_match('/([\d\.]+)\s*-\s*([\d\.]+)/', $clean, $m)) {
            return (float) $m[1];
        }
        if (preg_match('/>\s*=?\s*([\d\.]+)/', $clean, $m)) {
            return (float) $m[1];
        }
        return null;
    }

    /**
     * استخراج الحد الأعلى من المدى الطبيعي
     */
    public function getRefMaxAttribute(): ?float
    {
        if (empty($this->reference_range)) return null;
        $clean = str_replace(',', '', $this->reference_range);
        if (preg_match('/([\d\.]+)\s*-\s*([\d\.]+)/', $clean, $m)) {
            return (float) $m[2];
        }
        if (preg_match('/<\s*=?\s*([\d\.]+)/', $clean, $m)) {
            return (float) $m[1];
        }
        return null;
    }
}
