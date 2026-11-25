<?php

namespace App\Models\Concerns;

trait HasTherapyDayCounter
{
    /**
     * حساب اليوم الحالي من بدء العلاج وحتى التوقف أو اللحظة الحالية.
     */
    public function getCurrentTherapyDayAttribute(): int
    {
        $start = $this->therapyStartDate()?->copy()->startOfDay() ?? now()->startOfDay();
        $end = ($this->stopped_at ?? now())->copy()->startOfDay();

        return max(1, (int) $start->diffInDays($end) + 1);
    }

    /**
     * صيغة عداد الأيام الإنجليزية المعتمدة سريرياً: Day 1 of 3 أو Day 1.
     */
    public function getTherapyDayLabelAttribute(): string
    {
        $day = $this->current_therapy_day;

        return ($this->duration_days && $this->duration_days > 0)
            ? "Day {$day} of {$this->duration_days}"
            : "Day {$day}";
    }

    /**
     * صيغة عداد الأيام العربية للطباعة والتوضيح.
     */
    public function getTherapyDayArabicAttribute(): string
    {
        $day = $this->current_therapy_day;

        return ($this->duration_days && $this->duration_days > 0)
            ? "اليوم {$day} من {$this->duration_days}"
            : "اليوم {$day}";
    }

    /**
     * هل تجاوز الدواء المدة المقررة بدون إيقاف؟
     */
    public function getIsTherapyOverdueAttribute(): bool
    {
        return ! $this->stopped_at
            && $this->duration_days > 0
            && $this->current_therapy_day > (int) $this->duration_days;
    }

    /**
     * لون الشارة المناسب لحالة عداد الأيام.
     */
    public function getTherapyDayBadgeClassAttribute(): string
    {
        if ($this->stopped_at) {
            return 'bg-secondary text-white';
        }
        if ($this->is_therapy_overdue) {
            return 'bg-danger text-white';
        }
        if ($this->duration_days && $this->current_therapy_day === (int) $this->duration_days) {
            return 'bg-warning text-dark';
        }

        return 'bg-info text-dark';
    }
}
