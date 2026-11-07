<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthInsuranceStayTier extends Model
{
    protected $table = 'health_insurance_stay_tiers';

    protected $fillable = [
        'name',
        'day_from',
        'day_to',
        'copay_percentage',
        'standard_room_daily_price',
        'notes',
        'sort_order',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_from' => 'integer',
            'day_to' => 'integer',
            'copay_percentage' => 'decimal:2',
            'standard_room_daily_price' => 'decimal:2',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * جلب السعر المعتمد للغرفة العادية لدى الضمان الصحي
     */
    public static function getStandardRoomPrice(): float
    {
        $tier = self::where('is_active', true)->orderBy('sort_order')->first();

        return (float) ($tier?->standard_room_daily_price ?? 200000.00);
    }

    /**
     * جلب شريحة الإقامة المطابقة لرقم اليوم (اليوم 1، 2، 3 ...)
     */
    public static function getTierForDay(int $dayNumber): ?self
    {
        $dayNumber = max(1, $dayNumber);

        return self::where('is_active', true)
            ->where('day_from', '<=', $dayNumber)
            ->where('day_to', '>=', $dayNumber)
            ->orderBy('sort_order')
            ->first();
    }

    /**
     * استرجاع نسبة التحمل لليوم المحدد
     */
    public static function getCopayPercentageForDay(int $dayNumber): float
    {
        $tier = self::getTierForDay($dayNumber);

        return $tier ? (float) $tier->copay_percentage : 75.00;
    }

    /**
     * احتساب مبالغ الليلة الواحدة لمريض الضمان الصحي
     *
     * @param  int  $dayNumber  رقم اليوم (1, 2, 3...)
     * @param  Room|null  $room  الغرفة الفعلية
     * @return array [copay_pct, base_standard_price, regular_patient_share, regular_insurance_share, upgrade_diff, total_patient_share, total_insurance_share]
     */
    public static function calculateNightFigures(int $dayNumber, ?Room $room = null): array
    {
        $standardRoomPrice = self::getStandardRoomPrice();
        $copayPct = self::getCopayPercentageForDay($dayNumber);

        // 1. حصة الغرفة العادية
        // الليلة الأولى مشمولة بالكامل ضمن باقة وتكلفة العملية (0 د.ع على المريض و 0 د.ع على الضمان للغرفة العادية)
        if ($dayNumber === 1) {
            $regularPatientShare = 0.0;
            $regularInsuranceShare = 0.0;
        } else {
            $regularPatientShare = round($standardRoomPrice * ($copayPct / 100.0), 2);
            $regularInsuranceShare = max(0.0, round($standardRoomPrice - $regularPatientShare, 2));
        }

        // 2. فرق ترقية الغرفة الفعلي (يسري حصراً وفقط إذا اختار المريض غرفة أعلى VIP أو VVIP)
        // الغرفة العادية (regular) لا يوجد عليها أي فرق ترقية إطلاقاً (0 د.ع) لأنها معتمدة بسعر 200,000 للضمان
        $roomType = $room ? strtolower($room->room_type ?? 'regular') : 'regular';
        $isUpgrade = in_array($roomType, ['vip', 'vvip']);
        $actualRoomDailyFee = (float) ($room ? $room->daily_fee : $standardRoomPrice);
        $upgradeDiff = $isUpgrade ? max(0.0, round($actualRoomDailyFee - $standardRoomPrice, 2)) : 0.0;

        // 3. الإجماليات
        $totalPatientShare = $regularPatientShare + $upgradeDiff;
        $totalInsuranceShare = $regularInsuranceShare;

        return [
            'day_number' => $dayNumber,
            'copay_percentage' => $copayPct,
            'standard_room_price' => $standardRoomPrice,
            'actual_room_daily_fee' => $actualRoomDailyFee,
            'regular_patient_share' => $regularPatientShare,
            'regular_insurance_share' => $regularInsuranceShare,
            'upgrade_diff' => $upgradeDiff,
            'total_patient_share' => $totalPatientShare,
            'total_insurance_share' => $totalInsuranceShare,
        ];
    }
}
