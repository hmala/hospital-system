<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IcuFluidBalance extends Model
{
    use HasFactory;

    protected $table = 'icu_fluid_balances';

    protected $fillable = [
        'icu_admission_id',
        'recorded_at',
        'recorded_by',
        'intake_iv_fluids',
        'intake_blood_products',
        'intake_enteral_feeding',
        'intake_medications',
        'intake_total',
        'output_urine',
        'output_drains',
        'output_nasogastric',
        'output_other',
        'output_total',
        'net_balance',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'intake_iv_fluids' => 'integer',
            'intake_blood_products' => 'integer',
            'intake_enteral_feeding' => 'integer',
            'intake_medications' => 'integer',
            'intake_total' => 'integer',
            'output_urine' => 'integer',
            'output_drains' => 'integer',
            'output_nasogastric' => 'integer',
            'output_other' => 'integer',
            'output_total' => 'integer',
            'net_balance' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $balance) {
            $balance->intake_total = (int) $balance->intake_iv_fluids +
                (int) $balance->intake_blood_products +
                (int) $balance->intake_enteral_feeding +
                (int) $balance->intake_medications;

            $balance->output_total = (int) $balance->output_urine +
                (int) $balance->output_drains +
                (int) $balance->output_nasogastric +
                (int) $balance->output_other;

            $balance->net_balance = $balance->intake_total - $balance->output_total;
        });
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function recordedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getBalanceBadgeClassAttribute(): string
    {
        if ($this->net_balance > 500) {
            return 'bg-warning text-dark'; // احتباس سوائل
        }
        if ($this->net_balance < -500) {
            return 'bg-danger text-white'; // فقدان سوائل حاد
        }

        return 'bg-success text-white'; // متوازن
    }
}
