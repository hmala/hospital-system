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
            $reservation->admission_date->format('Y-m-d').' '.
            ($reservation->admission_time ? $reservation->admission_time->format('H:i') : '00:00')
        );

        $endDateTime = $reservation->discharge_date
            ? Carbon::parse(
                $reservation->discharge_date->format('Y-m-d').' '.
                ($reservation->discharge_time ? $reservation->discharge_time->format('H:i') : '23:59')
            )
            : now();

        $totalHours = max(0, $admissionDateTime->diffInHours($endDateTime));

        // حساب عدد الأيام الفندقية المطبقة وفق قواعد السماحية
        $billableDays = $this->calculateBillableDays($admissionDateTime, $endDateTime);

        // جلب سجلات الخدمات إن وجدت أو إنشاء سجل افتراضي
        $serviceLogs = $reservation->serviceLogs()->with('nicuService')->get();

        $isInsured = $reservation->is_insured;
        $gestationalWeeks = $reservation->gestational_weeks;
        $policyCategory = $isInsured ? 'E' : ($reservation->policy_category ?? 'E');

        $serviceBreakdown = [];
        $totalServiceCost = 0.0;
        $totalPatientServiceShare = 0.0;
        $totalInsuranceServiceShare = 0.0;
        $dailyCoverageDetails = [];
        $cumulativeDayCounter = 1;

        if ($serviceLogs->isNotEmpty()) {
            $totalCalculatedDays = $billableDays['days'];
            $closedLogsDays = (int) $serviceLogs->whereNotNull('end_time')->sum('days_count');
            $activeOpenDays = max(1, $totalCalculatedDays - $closedLogsDays);

            foreach ($serviceLogs as $log) {
                // إذا كان السجل مفتوحاً (قيد الرقود)، يُحتسب عدد الأيام الحية تلقائياً حتى اليوم الفندقي الحالي
                $days = $log->end_time === null ? $activeOpenDays : max(1, (int) $log->days_count);
                $fee = (float) $log->daily_fee;
                $cost = (float) ($days * $fee);
                $totalServiceCost += $cost;

                // تحديث السجل المفتوح في قاعدة البيانات بالمدة المحتسبة الحية
                if ($log->end_time === null && ($log->days_count != $days || $log->total_cost != $cost)) {
                    $log->updateQuietly([
                        'days_count' => $days,
                        'total_cost' => $cost,
                    ]);
                }

                $logPatientShare = 0.0;
                $logInsuranceShare = 0.0;
                $logAvgHiaPercent = 0.0;

                if ($isInsured) {
                    $serviceCode = $log->nicuService?->code ?? '';
                    for ($d = 0; $d < $days; $d++) {
                        $currentDayNumber = $cumulativeDayCounter + $d;
                        $cov = $this->getInsuranceCoverageForDay(
                            $currentDayNumber,
                            $fee,
                            $gestationalWeeks,
                            $policyCategory,
                            $serviceCode
                        );
                        $logPatientShare += $cov['patient_share'];
                        $logInsuranceShare += $cov['hia_share'];
                        $dailyCoverageDetails[] = array_merge($cov, [
                            'day_number' => $currentDayNumber,
                            'service_name' => $log->nicuService?->name ?? 'خدمة الخدج',
                        ]);
                    }
                    $logAvgHiaPercent = $cost > 0 ? round(($logInsuranceShare / $cost) * 100, 1) : 0;
                    $totalPatientServiceShare += $logPatientShare;
                    $totalInsuranceServiceShare += $logInsuranceShare;

                    // تحديث مبالغ الحصة في السجل إذا تغيرت
                    if ($log->patient_share != $logPatientShare || $log->insurance_share != $logInsuranceShare) {
                        $log->updateQuietly([
                            'patient_share' => $logPatientShare,
                            'insurance_share' => $logInsuranceShare,
                            'coverage_percentage' => $logAvgHiaPercent,
                        ]);
                    }
                } else {
                    $totalPatientServiceShare += $cost;
                }

                $cumulativeDayCounter += $days;

                $serviceBreakdown[] = [
                    'service_name' => $log->nicuService?->name ?? 'خدمة رعاية خدج',
                    'official_claim_name' => $log->nicuService?->official_claim_name ?? null,
                    'daily_fee' => $fee,
                    'days' => $days,
                    'total' => $cost,
                    'patient_share' => $isInsured ? $logPatientShare : $cost,
                    'insurance_share' => $isInsured ? $logInsuranceShare : 0.0,
                    'hia_percent' => $isInsured ? $logAvgHiaPercent : 0.0,
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
            $officialClaimName = null;
            $serviceCode = '';

            if ($reservation->nicuService) {
                $dailyFee = (float) $reservation->nicuService->getEffectiveFee($reservation->insurance_type ?? 'none');
                $serviceName = $reservation->nicuService->name;
                $officialClaimName = $reservation->nicuService->official_claim_name;
                $serviceCode = $reservation->nicuService->code;
            } elseif ($reservation->incubator) {
                $dailyFee = (float) $reservation->incubator->daily_fee;
            }

            $daysCount = $billableDays['days'];
            $totalServiceCost = $daysCount * $dailyFee;

            if ($isInsured) {
                for ($d = 1; $d <= $daysCount; $d++) {
                    $cov = $this->getInsuranceCoverageForDay(
                        $d,
                        $dailyFee,
                        $gestationalWeeks,
                        $policyCategory,
                        $serviceCode
                    );
                    $totalPatientServiceShare += $cov['patient_share'];
                    $totalInsuranceServiceShare += $cov['hia_share'];
                    $dailyCoverageDetails[] = array_merge($cov, [
                        'day_number' => $d,
                        'service_name' => $serviceName,
                    ]);
                }
            } else {
                $totalPatientServiceShare = $totalServiceCost;
                $totalInsuranceServiceShare = 0.0;
            }

            $serviceBreakdown[] = [
                'service_name' => $serviceName,
                'official_claim_name' => $officialClaimName,
                'daily_fee' => $dailyFee,
                'days' => $daysCount,
                'total' => $totalServiceCost,
                'patient_share' => $totalPatientServiceShare,
                'insurance_share' => $totalInsuranceServiceShare,
                'hia_percent' => $totalServiceCost > 0 ? round(($totalInsuranceServiceShare / $totalServiceCost) * 100, 1) : 0,
                'start_time' => $admissionDateTime->format('Y-m-d H:i'),
                'end_time' => $endDateTime->format('Y-m-d H:i'),
                'reason' => null,
            ];
        }

        // استشارات الأطباء التخصصية
        $consultations = $reservation->consultations()->where('status', '!=', 'cancelled')->get();
        $totalConsultationsCost = 0.0;
        $totalPatientConsultationsShare = 0.0;
        $totalInsuranceConsultationsShare = 0.0;

        foreach ($consultations as $con) {
            $fee = (float) $con->fee;
            $totalConsultationsCost += $fee;

            if ($isInsured && $con->consultation_type === 'internal') {
                // استشارة داخلية: مشمولة بالضمان وفق الفئة E (10% مريض / 90% ضمان)
                $patShare = round($fee * 0.10, 2);
                $insShare = round($fee * 0.90, 2);
                $isCovered = true;
                $covPercent = 90.0;
                $statusText = 'مشمولة بالضمان (90%)';
                $badgeClass = 'bg-success text-white';
            } else {
                // استشارة خارجية أو غير مضمون: 100% كاش على المريض
                $patShare = $fee;
                $insShare = 0.0;
                $isCovered = false;
                $covPercent = 0.0;
                $statusText = $isInsured ? 'خارجية - غير مشمولة (كاش 100%)' : 'نقدي (كاش)';
                $badgeClass = $isInsured ? 'bg-secondary text-white' : 'bg-light text-dark border';
            }

            $con->is_covered = $isCovered;
            $con->coverage_percentage = $covPercent;
            $con->coverage_status_text = $statusText;
            $con->coverage_badge_class = $badgeClass;
            $con->insurance_share = $insShare;
            $con->patient_share = $patShare;

            $totalPatientConsultationsShare += $patShare;
            $totalInsuranceConsultationsShare += $insShare;
        }

        // طلبات الفحوصات المخبرية والأشعة والأدوية
        $medicalOrders = $reservation->medicalOrders()
            ->where('status', '!=', 'cancelled')
            ->with(['labTest', 'radiologyType'])
            ->get();

        $totalOrdersCost = 0.0;
        $totalPatientOrdersShare = 0.0;
        $totalInsuranceOrdersShare = 0.0;

        $totalLabCost = 0.0;
        $insuranceLabShare = 0.0;
        $patientLabShare = 0.0;

        $totalRadCost = 0.0;
        $insuranceRadShare = 0.0;
        $patientRadShare = 0.0;

        $totalMedCost = 0.0;
        $insuranceMedShare = 0.0;
        $patientMedShare = 0.0;

        foreach ($medicalOrders as $ord) {
            $price = (float) $ord->price;
            $isCovered = false;
            $covPercent = 0.0;
            $insShare = 0.0;
            $patShare = $price;
            $statusText = 'غير مشمول (كاش 100%)';
            $badgeClass = 'bg-secondary text-white';

            if ($isInsured) {
                if ($ord->order_type === 'lab') {
                    // فحص مختبر: إذا كان مشمولاً وفعالاً بالضمان
                    $test = $ord->labTest;
                    if ($test && $test->is_hi_active && (float) $test->hi_price > 0) {
                        $isCovered = true;
                        $covPercent = 75.0; // فئة E استقطاع 25% مريض / 75% ضمان
                        $approvedPrice = (float) $test->hi_price;
                        $insShare = round($approvedPrice * 0.75, 2);
                        $patShare = round($approvedPrice * 0.25, 2);
                        $statusText = 'مشمول بالضمان (75%)';
                        $badgeClass = 'bg-success text-white';
                    }
                } elseif ($ord->order_type === 'radiology') {
                    // فحص أشعة وسونار: إذا كان مشمولاً وفعالاً بالضمان
                    $rad = $ord->radiologyType;
                    if ($rad && $rad->is_hi_active && (float) $rad->hi_price > 0) {
                        $isCovered = true;
                        $covPercent = 75.0; // فئة E استقطاع 25% مريض / 75% ضمان
                        $approvedPrice = (float) $rad->hi_price;
                        $insShare = round($approvedPrice * 0.75, 2);
                        $patShare = round($approvedPrice * 0.25, 2);
                        $statusText = 'مشمول بالضمان (75%)';
                        $badgeClass = 'bg-info text-white';
                    }
                }
            } else {
                $statusText = 'نقدي (كاش)';
                $badgeClass = 'bg-light text-dark border';
            }

            $ord->is_covered = $isCovered;
            $ord->coverage_percentage = $covPercent;
            $ord->coverage_status_text = $statusText;
            $ord->coverage_badge_class = $badgeClass;
            $ord->insurance_share = $insShare;
            $ord->patient_share = $patShare;

            $totalOrdersCost += $price;
            $totalInsuranceOrdersShare += $insShare;
            $totalPatientOrdersShare += $patShare;

            if ($ord->order_type === 'lab') {
                $totalLabCost += $price;
                $insuranceLabShare += $insShare;
                $patientLabShare += $patShare;
            } elseif ($ord->order_type === 'radiology') {
                $totalRadCost += $price;
                $insuranceRadShare += $insShare;
                $patientRadShare += $patShare;
            } else {
                $totalMedCost += $price;
                $insuranceMedShare += $insShare;
                $patientMedShare += $patShare;
            }
        }

        $ordersByType = [
            'lab' => $totalLabCost,
            'radiology' => $totalRadCost,
            'medication' => $totalMedCost,
        ];

        $grandTotal = $totalServiceCost + $totalConsultationsCost + $totalOrdersCost;

        $insuranceGrandTotal = $isInsured
            ? ($totalInsuranceServiceShare + $totalInsuranceOrdersShare + $totalInsuranceConsultationsShare)
            : 0.0;

        $patientGrandTotal = $isInsured
            ? ($totalPatientServiceShare + $totalPatientOrdersShare + $totalPatientConsultationsShare)
            : $grandTotal;

        // تحديث الحجز إذا كان تأميناً بالمبالغ التراكمية الشاملة
        if ($isInsured) {
            $reservation->updateQuietly([
                'patient_share_amount' => $patientGrandTotal,
                'insurance_share_amount' => $insuranceGrandTotal,
                'coverage_summary' => [
                    'insurance_type' => $reservation->insurance_type,
                    'policy_category' => $policyCategory,
                    'gestational_weeks' => $gestationalWeeks,
                    'total_service_cost' => $totalServiceCost,
                    'patient_service_share' => $totalPatientServiceShare,
                    'insurance_service_share' => $totalInsuranceServiceShare,
                    'total_lab_cost' => $totalLabCost,
                    'patient_lab_share' => $patientLabShare,
                    'insurance_lab_share' => $insuranceLabShare,
                    'total_radiology_cost' => $totalRadCost,
                    'patient_radiology_share' => $patientRadShare,
                    'insurance_radiology_share' => $insuranceRadShare,
                    'total_consultations_cost' => $totalConsultationsCost,
                    'patient_consultations_share' => $totalPatientConsultationsShare,
                    'insurance_consultations_share' => $totalInsuranceConsultationsShare,
                    'daily_details' => $dailyCoverageDetails,
                ],
            ]);
        } else {
            $reservation->updateQuietly([
                'patient_share_amount' => $grandTotal,
                'insurance_share_amount' => 0.0,
            ]);
        }

        $totalPaid = $reservation->total_paid_amount;
        $netBalance = max(0, $patientGrandTotal - $totalPaid);
        $excessDeposit = max(0, $totalPaid - $patientGrandTotal);

        return [
            'total_hours' => $totalHours,
            'calculated_days' => $billableDays['days'],
            'days_count' => $billableDays['days'],
            'admission_grace_applied' => $billableDays['admission_grace'],
            'discharge_grace_applied' => $billableDays['discharge_grace'],
            'grace_notes' => $billableDays['notes'],
            'is_insured' => $isInsured,
            'insurance_type' => $reservation->insurance_type,
            'policy_category' => $policyCategory,
            'gestational_weeks' => $gestationalWeeks,
            'total_service_cost' => $totalServiceCost,
            'patient_share_total' => $totalPatientServiceShare,
            'insurance_share_total' => $totalInsuranceServiceShare,
            'service_breakdown' => $serviceBreakdown,
            'daily_coverage_details' => $dailyCoverageDetails,
            'total_consultations_cost' => $totalConsultationsCost,
            'patient_consultations_share' => $totalPatientConsultationsShare,
            'insurance_consultations_share' => $totalInsuranceConsultationsShare,
            'consultations' => $consultations,
            'total_orders_cost' => $totalOrdersCost,
            'patient_orders_share' => $totalPatientOrdersShare,
            'insurance_orders_share' => $totalInsuranceOrdersShare,
            'total_lab_cost' => $totalLabCost,
            'patient_lab_share' => $patientLabShare,
            'insurance_lab_share' => $insuranceLabShare,
            'total_radiology_cost' => $totalRadCost,
            'patient_radiology_share' => $patientRadShare,
            'insurance_radiology_share' => $insuranceRadShare,
            'orders_by_type' => $ordersByType,
            'medical_orders' => $medicalOrders,
            'grand_total' => $grandTotal,
            'patient_grand_total' => $patientGrandTotal,
            'insurance_grand_total' => $insuranceGrandTotal,
            'total_paid' => $totalPaid,
            'net_balance' => $netBalance,
            'excess_deposit' => $excessDeposit,
            'payments' => $reservation->payments()->with('cashier')->latest()->get(),
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
            'days' => max(1, (int) $days),
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
     * احتساب تفاصيل التغطية لليوم المحدد وفق قواعد هيئة الضمان الصحي الرسمية للعمر الحملي
     *
     * @param  int  $dayNumber  رقم اليوم التراكمي في الإقامة (1, 2, 3...)
     * @param  float  $dailyFee  الأجرة اليومية المعتمدة
     * @param  int|null  $gestationalWeeks  العمر الحملي بالأسابيع
     * @param  string  $policyCategory  فئة الوثيقة (E, H, A, D...)
     * @param  string  $serviceCode  رمز الخدمة
     */
    public function getInsuranceCoverageForDay(
        int $dayNumber,
        float $dailyFee,
        ?int $gestationalWeeks = null,
        string $policyCategory = 'E',
        string $serviceCode = ''
    ): array {
        $category = strtoupper(trim(str_replace('الفئة', '', $policyCategory)));

        // 1. الفئة A (الرعاية الاجتماعية، ذوي الشهداء) معفاة 100%
        if ($category === 'A') {
            return [
                'hia_percent' => 100.0,
                'patient_percent' => 0.0,
                'hia_share' => $dailyFee,
                'patient_share' => 0.0,
                'tier_label' => 'الفئة A - إعفاء تام (100% ضمان)',
            ];
        }

        // 2. إذا كانت الخدمة رقود اعتيادي خارج الخدج (ردهة أطفال / غرفة منفردة)
        if ($serviceCode === 'pediatric_inpatient') {
            return [
                'hia_percent' => 90.0,
                'patient_percent' => 10.0,
                'hia_share' => round($dailyFee * 0.90, 2),
                'patient_share' => round($dailyFee * 0.10, 2),
                'tier_label' => 'رقود ردهة اعتيادي (90% ضمان / 10% مريض)',
            ];
        }

        // 3. قواعد العمر الحملي الرسمية للخدج
        if ($gestationalWeeks !== null && $gestationalWeeks > 0) {
            if ($gestationalWeeks >= 34) {
                // الفئة الأولى: 34 أسبوع فما فوق (أقصى تغطية 10 أيام)
                if ($dayNumber <= 5) {
                    return [
                        'hia_percent' => 75.0,
                        'patient_percent' => 25.0,
                        'hia_share' => round($dailyFee * 0.75, 2),
                        'patient_share' => round($dailyFee * 0.25, 2),
                        'tier_label' => 'عمر ≥34 أسبوع (الأيام 1-5: 75% ضمان / 25% مريض)',
                    ];
                } elseif ($dayNumber <= 10) {
                    return [
                        'hia_percent' => 50.0,
                        'patient_percent' => 50.0,
                        'hia_share' => round($dailyFee * 0.50, 2),
                        'patient_share' => round($dailyFee * 0.50, 2),
                        'tier_label' => 'عمر ≥34 أسبوع (الأيام 6-10: 50% ضمان / 50% مريض)',
                    ];
                } else {
                    return [
                        'hia_percent' => 0.0,
                        'patient_percent' => 100.0,
                        'hia_share' => 0.0,
                        'patient_share' => $dailyFee,
                        'tier_label' => 'تجاوز الحد الأقصى 10 أيام (100% مريض)',
                    ];
                }
            } elseif ($gestationalWeeks >= 32) {
                // الفئة الثانية: 32 إلى أقل من 34 أسبوع (أقصى تغطية 15 يوماً)
                if ($dayNumber <= 5) {
                    return [
                        'hia_percent' => 75.0,
                        'patient_percent' => 25.0,
                        'hia_share' => round($dailyFee * 0.75, 2),
                        'patient_share' => round($dailyFee * 0.25, 2),
                        'tier_label' => 'عمر 32-34 أسبوع (الأيام 1-5: 75% ضمان / 25% مريض)',
                    ];
                } elseif ($dayNumber <= 10) {
                    return [
                        'hia_percent' => 50.0,
                        'patient_percent' => 50.0,
                        'hia_share' => round($dailyFee * 0.50, 2),
                        'patient_share' => round($dailyFee * 0.50, 2),
                        'tier_label' => 'عمر 32-34 أسبوع (الأيام 6-10: 50% ضمان / 50% مريض)',
                    ];
                } elseif ($dayNumber <= 15) {
                    return [
                        'hia_percent' => 25.0,
                        'patient_percent' => 75.0,
                        'hia_share' => round($dailyFee * 0.25, 2),
                        'patient_share' => round($dailyFee * 0.75, 2),
                        'tier_label' => 'عمر 32-34 أسبوع (الأيام 11-15: 25% ضمان / 75% مريض)',
                    ];
                } else {
                    return [
                        'hia_percent' => 0.0,
                        'patient_percent' => 100.0,
                        'hia_share' => 0.0,
                        'patient_share' => $dailyFee,
                        'tier_label' => 'تجاوز الحد الأقصى 15 يوماً (100% مريض)',
                    ];
                }
            } elseif ($gestationalWeeks >= 28) {
                // الفئة الثالثة: 28 إلى أقل من 32 أسبوع (أقصى تغطية 20 يوماً)
                if ($dayNumber <= 5) {
                    return [
                        'hia_percent' => 75.0,
                        'patient_percent' => 25.0,
                        'hia_share' => round($dailyFee * 0.75, 2),
                        'patient_share' => round($dailyFee * 0.25, 2),
                        'tier_label' => 'عمر 28-32 أسبوع (الأيام 1-5: 75% ضمان / 25% مريض)',
                    ];
                } elseif ($dayNumber <= 10) {
                    return [
                        'hia_percent' => 50.0,
                        'patient_percent' => 50.0,
                        'hia_share' => round($dailyFee * 0.50, 2),
                        'patient_share' => round($dailyFee * 0.50, 2),
                        'tier_label' => 'عمر 28-32 أسبوع (الأيام 6-10: 50% ضمان / 50% مريض)',
                    ];
                } elseif ($dayNumber <= 15) {
                    return [
                        'hia_percent' => 25.0,
                        'patient_percent' => 75.0,
                        'hia_share' => round($dailyFee * 0.25, 2),
                        'patient_share' => round($dailyFee * 0.75, 2),
                        'tier_label' => 'عمر 28-32 أسبوع (الأيام 11-15: 25% ضمان / 75% مريض)',
                    ];
                } elseif ($dayNumber <= 20) {
                    return [
                        'hia_percent' => 10.0,
                        'patient_percent' => 90.0,
                        'hia_share' => round($dailyFee * 0.10, 2),
                        'patient_share' => round($dailyFee * 0.90, 2),
                        'tier_label' => 'عمر 28-32 أسبوع (الأيام 16-20: 10% ضمان / 90% مريض)',
                    ];
                } else {
                    return [
                        'hia_percent' => 0.0,
                        'patient_percent' => 100.0,
                        'hia_share' => 0.0,
                        'patient_share' => $dailyFee,
                        'tier_label' => 'تجاوز الحد الأقصى 20 يوماً (100% مريض)',
                    ];
                }
            } else {
                // الفئة الرابعة: أقل من 28 أسبوع (شديدو الخداجة Extreme Preterm)
                if ($dayNumber <= 5) {
                    return [
                        'hia_percent' => 75.0,
                        'patient_percent' => 25.0,
                        'hia_share' => round($dailyFee * 0.75, 2),
                        'patient_share' => round($dailyFee * 0.25, 2),
                        'tier_label' => 'خداجة شديدة <28 أسبوع (الأيام 1-5: 75% ضمان / 25% مريض)',
                    ];
                } elseif ($dayNumber <= 10) {
                    return [
                        'hia_percent' => 50.0,
                        'patient_percent' => 50.0,
                        'hia_share' => round($dailyFee * 0.50, 2),
                        'patient_share' => round($dailyFee * 0.50, 2),
                        'tier_label' => 'خداجة شديدة <28 أسبوع (الأيام 6-10: 50% ضمان / 50% مريض)',
                    ];
                } elseif ($dayNumber <= 15) {
                    return [
                        'hia_percent' => 25.0,
                        'patient_percent' => 75.0,
                        'hia_share' => round($dailyFee * 0.25, 2),
                        'patient_share' => round($dailyFee * 0.75, 2),
                        'tier_label' => 'خداجة شديدة <28 أسبوع (الأيام 11-15: 25% ضمان / 75% مريض)',
                    ];
                } else {
                    return [
                        'hia_percent' => 10.0,
                        'patient_percent' => 90.0,
                        'hia_share' => round($dailyFee * 0.10, 2),
                        'patient_share' => round($dailyFee * 0.90, 2),
                        'tier_label' => 'خداجة شديدة <28 أسبوع (الأيام 16+: 10% ضمان / 90% مريض)',
                    ];
                }
            }
        }

        // في حال عدم تحديد العمر الحملي: نسبة الضمان الافتراضية للرقود (90% ضمان / 10% مريض)
        return [
            'hia_percent' => 90.0,
            'patient_percent' => 10.0,
            'hia_share' => round($dailyFee * 0.90, 2),
            'patient_share' => round($dailyFee * 0.10, 2),
            'tier_label' => 'نسبة تغطية اعتيادية (90% ضمان / 10% مريض)',
        ];
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
        $isInsured = $reservation->is_insured;
        $effectiveFee = (float) $newService->getEffectiveFee($reservation->insurance_type ?? 'none');

        // جلب آخر سجل خدمة مفتوح
        $lastLog = $reservation->serviceLogs()->whereNull('end_time')->latest('start_time')->first();

        // 1. فحص نافذة التراجع السريع والخطأ (إذا تم التبديل خلال 15 دقيقة)
        if ($lastLog && $lastLog->start_time && $lastLog->start_time->diffInMinutes($now) <= 15) {
            $lastLog->update([
                'nicu_service_id' => $newService->id,
                'daily_fee' => $effectiveFee,
                'total_cost' => ($lastLog->days_count ?: 1) * $effectiveFee,
                'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                'switched_by' => $userId,
                'notes' => 'تصحيح فوري للباقة خلال نافذة التراجع دون فتح يوم جديد.',
            ]);

            $reservation->update(['nicu_service_id' => $newService->id]);
            $this->calculateStayDetails($reservation);

            return $lastLog;
        }

        if ($lastLog) {
            $logStart = Carbon::parse($lastLog->start_time);
            $lastCycle = $this->getHotelCycleDate($logStart);
            $currentCycle = $this->getHotelCycleDate($now);

            if ($lastCycle === $currentCycle) {
                // التبديل حدث ضمن نفس اليوم الفندقي (12:00 ظهراً إلى 12:00 ظهراً التالية)
                // يتم تعديل السجل المفتوح مباشرة (تعديل السجل الحالي) بدلاً من إنشاء سطر مكرر
                $higherFee = max((float) $lastLog->daily_fee, $effectiveFee);

                $lastLog->update([
                    'nicu_service_id' => $newService->id,
                    'daily_fee' => $higherFee,
                    'total_cost' => ($lastLog->days_count ?: 1) * $higherFee,
                    'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                    'switched_by' => $userId,
                    'notes' => 'تم تعديل الباقة في نفس اليوم الفندقي إلى ('.$newService->name.') واعتماد التسعيرة الأعلى ('.number_format($higherFee).' د.ع).',
                ]);

                $reservation->update(['nicu_service_id' => $newService->id]);
                $this->calculateStayDetails($reservation);

                return $lastLog;
            } else {
                // السجل السابق قضى يوماً أو أكثر في دورات فندقية سابقة
                $completedDays = max(1, Carbon::parse($lastCycle)->diffInDays(Carbon::parse($currentCycle)));

                $lastLog->update([
                    'end_time' => $now,
                    'days_count' => $completedDays,
                    'total_cost' => $completedDays * (float) $lastLog->daily_fee,
                ]);

                $reservation->update(['nicu_service_id' => $newService->id]);

                $newLog = NicuServiceLog::create([
                    'reservation_id' => $reservation->id,
                    'nicu_service_id' => $newService->id,
                    'daily_fee' => $effectiveFee,
                    'start_time' => $now,
                    'end_time' => null,
                    'days_count' => 1,
                    'total_cost' => $effectiveFee,
                    'reason_for_switch' => $reason,
                    'switched_by' => $userId,
                ]);

                $this->calculateStayDetails($reservation);

                return $newLog;
            }
        }

        // في حال لم يكن هناك سجل سابق
        $reservation->update(['nicu_service_id' => $newService->id]);
        $newLog = NicuServiceLog::create([
            'reservation_id' => $reservation->id,
            'nicu_service_id' => $newService->id,
            'daily_fee' => $effectiveFee,
            'start_time' => $now,
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => $effectiveFee,
            'reason_for_switch' => $reason,
            'switched_by' => $userId,
        ]);

        $this->calculateStayDetails($reservation);

        return $newLog;
    }
}
