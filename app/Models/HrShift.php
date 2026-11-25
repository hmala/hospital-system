<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrShift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'duration_hours',
        'call_rate',
        'shift_category',
        'color_code',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'duration_hours' => 'decimal:2',
            'call_rate' => 'decimal:2',
        ];
    }

    public static function calculateHoursBetween(string $startTime, string $endTime): float
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        return round($start->diffInMinutes($end) / 60, 2);
    }

    public function getShiftCategoryLabelAttribute(): string
    {
        return match ($this->shift_category) {
            'nursing' => 'تمريض (8 ساعات)',
            'resident' => 'أطباء مقيمين (12 ساعة)',
            'emergency_doctor' => 'طبيب طوارئ (Call)',
            'general' => 'عام',
            default => $this->shift_category ?? 'عام',
        };
    }
}
