<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CcuServiceLog extends Model
{
    use HasFactory;

    protected $table = 'ccu_service_logs';

    protected $fillable = [
        'icu_admission_id',
        'ccu_service_id',
        'log_category',
        'service_name_snapshot',
        'daily_fee',
        'bed_daily_fee',
        'start_time',
        'end_time',
        'days_count',
        'total_cost',
        'patient_share',
        'insurance_share',
        'coverage_percentage',
        'reason_for_switch',
        'switched_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'daily_fee' => 'decimal:2',
            'bed_daily_fee' => 'decimal:2',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'days_count' => 'integer',
            'total_cost' => 'decimal:2',
            'patient_share' => 'decimal:2',
            'insurance_share' => 'decimal:2',
            'coverage_percentage' => 'decimal:2',
        ];
    }

    const CATEGORY_STAY_PACKAGE = 'stay_package';

    const CATEGORY_ADDON_SERVICE = 'addon_service';

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function ccuService(): BelongsTo
    {
        return $this->belongsTo(CcuService::class, 'ccu_service_id');
    }

    public function switchedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'switched_by');
    }
}
