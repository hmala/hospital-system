<?php

namespace App\Services;

use App\Models\IncubatorReservation;
use App\Models\NicuService;
use App\Models\NicuServiceLog;
use Carbon\Carbon;

class NicuBillingService
{
    /**
     * نقطة الفصل القياسية لليوم الفندقي السريري (الساعة 12:00 ظهراً بتوقيت العراق)
     */
    const CUTOFF_HOUR = 12;

    /**
     * نافذة السماحية بالساعات (6 ساعات قبل وبعد الساعة 12:00 ظهراً)
     */
    const GRACE_PERIOD_HOURS = 6;

    /**
     * حساب تفاصيل الإقامة والتكلفة التراكمية مع تطبيق سماحية الـ 6 ساعات
     */
    public function calculateStayDetails(IncubatorReservation $reservation): array
    {
        $admissionDateTime = Carbon::parse(
            $reservation->admission_date->format('Y-m-d') . ' ' . 
            ($reservation->admission_time ? $reservation->admission_time->format('H:i') : '00:00')
        );

        $endDateTime = $reservation->discharge_date
            ? Carbon::parse(
                $reservation->discharge_date->format('Y-m-d') . ' ' . 
                ($reservation->discharge_time ? $reservation->discharge_time->format('H:i') : '23:59')
            )
            : now();

        $totalHours = max(0, $admissionDateTime->diffInHours($endDateTime));

        // حساب عدد الأيام الفندقية المطبقة وفق قواعد السماحية
        $billableDays = $this->calculateBillableDays($admissionDateTime, $endDateTime);

        // جلب سجلات الخدمات إن وجدت أو إنشاء سجل افتراضي
        $serviceLogs = $reservation->serviceLogs()->with('nicuService')->get();

        $serviceBreakdown = [];
        $totalServiceCost = 0.0;

        if ($serviceLogs->isNotEmpty()) {
            foreach ($serviceLogs as $log) {
                $days = (int)$log->days_count;
                $fee = (float)$log->daily_fee;
                $cost = (float)($log->total_cost ?? ($days * $fee));
                $totalServiceCost += $cost;

                $serviceBreakdown[] = [
                    'service_name' => $log->nicuService?->name ?? 'خدمة رعاية خدج',
                    'daily_fee' => $fee,
                    'days' => $days,
                    'total' => $cost,
                    'start_time' => $log->start_time?->format('Y-m-d H:i'),
                    'end_time' => $log->end_time?->format('Y-m-d H:i'),
                    'reason' => $log->reason_for_switch,
                    'notes' => $log->notes,
                ];
            }
        } else {
            // اعتماد الخدمة الحالية أو الحاضنة
            $dailyFee = 0.0;
            $serviceName = 'حاضنة رعاية الخدج';

            if ($reservation->nicuService) {
                $dailyFee = (float)$reservation->nicuService->daily_fee;
                $serviceName = $reservation->nicuService->name;
            } elseif ($reservation->incubator) {
                $dailyFee = (float)$reservation->incubator->daily_fee;
            }

            $totalServiceCost = $billableDays['days'] * $dailyFee;
            $serviceBreakdown[] = [
                'service_name' => $serviceName,
                'daily_fee' => $dailyFee,
                'days' => $billableDays['days'],
                'total' => $totalServiceCost,
                'start_time' => $admissionDateTime->format('Y-m-d H:i'),
                'end_time' => $endDateTime->format('Y-m-d H:i'),
                'reason' => null,
            ];
        }

        // استشارات الأطباء
        $consultations = $reservation->consultations()->where('status', '!=', 'cancelled')->get();
        $totalConsultationsCost = (float)$consultations->sum('fee');

        // طلبات الفحوصات والأشعة والأدوية
        $medicalOrders = $reservation->medicalOrders()->where('status', '!=', 'cancelled')->get();
        $totalOrdersCost = (float)$medicalOrders->sum('price');

        $ordersByType = [
            'lab' => (float)$medicalOrders->where('order_type', 'lab')->sum('price'),
            'radiology' => (float)$medicalOrders->where('order_type', 'radiology')->sum('price'),
            'medication' => (float)$medicalOrders->where('order_type', 'medication')->sum('price'),
        ];

        $grandTotal = $totalServiceCost + $totalConsultationsCost + $totalOrdersCost;

        return [
            'total_hours' => $totalHours,
            'calculated_days' => $billableDays['days'],
            'admission_grace_applied' => $billableDays['admission_grace'],
            'discharge_grace_applied' => $billableDays['discharge_grace'],
            'grace_notes' => $billableDays['notes'],
            'total_service_cost' => $totalServiceCost,
            'service_breakdown' => $serviceBreakdown,
            'total_consultations_cost' => $totalConsultationsCost,
            'consultations' => $consultations,
            'total_orders_cost' => $totalOrdersCost,
            'orders_by_type' => $ordersByType,
            'medical_orders' => $medicalOrders,
            'grand_total' => $grandTotal,
            'start_datetime' => $admissionDateTime,
            'end_datetime' => $endDateTime,
        ];
    }

    /**
     * حساب الأيام الفندقية الدقيقة مع فحص سماحية الـ 6 ساعات
     */
    public function calculateBillableDays(Carbon $admission, Carbon $discharge): array
    {
        $admissionGrace = false;
        $dischargeGrace = false;
        $notes = [];

        // 1. فحص سماحية الدخول (إذا دخل بين 06:00 صباحاً و 12:00 ظهراً)
        $admissionHour = $admission->hour;
        if ($admissionHour >= (self::CUTOFF_HOUR - self::GRACE_PERIOD_HOURS) && $admissionHour < self::CUTOFF_HOUR) {
            $admissionGrace = true;
            $notes[] = 'شمل بسماحية الدخول الصباحية (أقل من 6 ساعات قبل 12:00 ظهراً) دون فتح يوم إضافي مبكر.';
        }

        // 2. فحص سماحية الخروج (إذا خرج بين 12:00 ظهراً و 18:00 مساءً)
        $dischargeHour = $discharge->hour;
        if ($dischargeHour >= self::CUTOFF_HOUR && $dischargeHour < (self::CUTOFF_HOUR + self::GRACE_PERIOD_HOURS)) {
            $dischargeGrace = true;
            $notes[] = 'شمل بسماحية الخروج المسائية (أقل من 6 ساعات بعد 12:00 ظهراً) معفى من احتساب يوم كامل إضافي.';
        }

        // حساب عدد الأيام الفندقية
        // نبدأ بحساب فرق الأيام الكاملة
        $days = $admission->copy()->startOfDay()->diffInDays($discharge->copy()->startOfDay());

        if ($days == 0) {
            // نفس اليوم: يحتسب يوماً واحداً كحد أدنى
            $days = 1;
        } else {
            // إذا كان الدخول صباحياً ومشمول بالسماحية، نعدل الأساس
            // اليوم الأول دائماً 1، ثم كل تجاوز للظهيرة بدون سماحية يزيد يوماً
            $days = max(1, $days);
            
            // إذا تجاوزت ساعة الخروج الـ 12 ظهراً ولم تشمل بسماحية الخروج (أي خرج بعد 18:00)
            if ($dischargeHour >= (self::CUTOFF_HOUR + self::GRACE_PERIOD_HOURS)) {
                $days += 1;
            }
        }

        return [
            'days' => max(1, (int)$days),
            'admission_grace' => $admissionGrace,
            'discharge_grace' => $dischargeGrace,
            'notes' => $notes,
        ];
    }

    /**
     * الحصول على تاريخ بداية الدورة الفندقية السريرية (الساعة 12:00 ظهراً بتوقيت العراق)
     */
    public function getHotelCycleDate(Carbon $dateTime): string
    {
        return ($dateTime->hour < self::CUTOFF_HOUR)
            ? $dateTime->copy()->subDay()->format('Y-m-d')
            : $dateTime->format('Y-m-d');
    }

    /**
     * تبديل الخدمة السريرية للطفل مع حماية منع تكرار وازدواجية الأيام في نفس اليوم الفندقي
     */
    public function switchService(
        IncubatorReservation $reservation,
        NicuService $newService,
        ?string $reason = null,
        ?int $userId = null
    ): NicuServiceLog {
        $now = now();

        // جلب آخر سجل خدمة مفتوح
        $lastLog = $reservation->serviceLogs()->whereNull('end_time')->latest('start_time')->first();

        // 1. فحص نافذة التراجع السريع والخطأ (إذا تم التبديل خلال 15 دقيقة)
        if ($lastLog && $lastLog->start_time && $lastLog->start_time->diffInMinutes($now) <= 15) {
            $lastLog->update([
                'nicu_service_id' => $newService->id,
                'daily_fee' => $newService->daily_fee,
                'total_cost' => ($lastLog->days_count ?: 1) * (float)$newService->daily_fee,
                'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                'switched_by' => $userId,
                'notes' => 'تصحيح فوري للباقة خلال نافذة التراجع دون فتح يوم جديد.',
            ]);

            $reservation->update(['nicu_service_id' => $newService->id]);
            return $lastLog;
        }

        if ($lastLog) {
            $logStart = Carbon::parse($lastLog->start_time);
            $lastCycle = $this->getHotelCycleDate($logStart);
            $currentCycle = $this->getHotelCycleDate($now);

            if ($lastCycle === $currentCycle) {
                // التبديل حدث ضمن نفس اليوم الفندقي (12:00 ظهراً إلى 12:00 ظهراً التالية)
                // يتم تعديل السجل المفتوح مباشرة (تعديل السجل الحالي) بدلاً من إنشاء سطر مكرر
                $higherFee = max((float)$lastLog->daily_fee, (float)$newService->daily_fee);

                $lastLog->update([
                    'nicu_service_id' => $newService->id,
                    'daily_fee' => $higherFee,
                    'total_cost' => ($lastLog->days_count ?: 1) * $higherFee,
                    'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                    'switched_by' => $userId,
                    'notes' => 'تم تعديل الباقة في نفس اليوم الفندقي إلى (' . $newService->name . ') واعتماد التسعيرة الأعلى (' . number_format($higherFee) . ' د.ع).',
                ]);

                $reservation->update(['nicu_service_id' => $newService->id]);

                return $lastLog;
            } else {
                // السجل السابق قضى يوماً أو أكثر في دورات فندقية سابقة
                $completedDays = max(1, Carbon::parse($lastCycle)->diffInDays(Carbon::parse($currentCycle)));

                $lastLog->update([
                    'end_time' => $now,
                    'days_count' => $completedDays,
                    'total_cost' => $completedDays * (float)$lastLog->daily_fee,
                ]);

                $reservation->update(['nicu_service_id' => $newService->id]);

                return NicuServiceLog::create([
                    'reservation_id' => $reservation->id,
                    'nicu_service_id' => $newService->id,
                    'daily_fee' => $newService->daily_fee,
                    'start_time' => $now,
                    'end_time' => null,
                    'days_count' => 1,
                    'total_cost' => $newService->daily_fee,
                    'reason_for_switch' => $reason,
                    'switched_by' => $userId,
                ]);
            }
        }

        // في حال لم يكن هناك سجل سابق
        $reservation->update(['nicu_service_id' => $newService->id]);
        return NicuServiceLog::create([
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $newService->id,
            'daily_fee' => $newService->daily_fee,
            'start_time' => $now,
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => $newService->daily_fee,
            'reason_for_switch' => $reason,
            'switched_by' => $userId,
        ]);
    }
}
