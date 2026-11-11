<?php

namespace App\Services;

use App\Models\CcuService;
use App\Models\CcuServiceLog;
use App\Models\CcuSetting;
use App\Models\CcuStayBracket;
use App\Models\IcuAdmission;
use Carbon\Carbon;
use Database\Seeders\CcuTariffSeeder;
use Illuminate\Support\Facades\Schema;

class CcuBillingService
{
    /**
     * التأكد من وجود الإعدادات والشرائح والباقات الافتراضية داينمكياً
     */
    public function ensureDefaultsExist(): void
    {
        if (Schema::hasTable('ccu_settings') && CcuSetting::count() === 0) {
            CcuTariffSeeder::seedDefaults();
        }
    }

    public function getCutoffHour(): int
    {
        $this->ensureDefaultsExist();

        return max(0, min(23, CcuSetting::getInt('cutoff_hour', 12)));
    }

    public function getGracePeriodHours(): int
    {
        $this->ensureDefaultsExist();

        return max(0, min(12, CcuSetting::getInt('grace_period_hours', 6)));
    }

    /**
     * حساب تفاصيل الإقامة والتكلفة التراكمية والشرائح الداينمكية والضمان
     */
    public function calculateStayDetails(IcuAdmission $admission): array
    {
        $this->ensureDefaultsExist();

        $admissionDateTime = $admission->admission_date
            ? Carbon::parse($admission->admission_date)
            : now();

        $endDateTime = $admission->discharge_date
            ? Carbon::parse($admission->discharge_date)
            : now();

        $totalHours = max(0, $admissionDateTime->diffInHours($endDateTime));
        $billableDays = $this->calculateBillableDays($admissionDateTime, $endDateTime);

        $admission->loadMissing([
            'bed',
            'ccuService',
            'ccuServiceLogs.ccuService',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'ccuConsultations.doctor.user',
            'ccuPayments.cashier',
        ]);

        $isInsured = (bool) $admission->is_insured
            || in_array(strtolower((string) $admission->insurance_type), ['hi', 'health_insurance', 'moi'], true);

        $insuranceType = $isInsured
            ? ($admission->insurance_type && $admission->insurance_type !== 'none' ? $admission->insurance_type : 'hi')
            : 'none';

        $policyCategory = strtoupper(trim(str_replace('الفئة', '', (string) ($admission->policy_category ?? $admission->insurance_category ?? 'E')))) ?: 'E';

        $annualMaxDays = CcuSetting::getInt('annual_max_insured_days', 30);
        $priorAnnualUsedDays = $isInsured ? $this->getPreviousAnnualInsuredDays($admission) : 0;

        $stayLogs = $admission->ccuServiceLogs
            ->where('log_category', '!=', CcuServiceLog::CATEGORY_ADDON_SERVICE)
            ->values();

        $addonLogs = $admission->ccuServiceLogs
            ->where('log_category', CcuServiceLog::CATEGORY_ADDON_SERVICE)
            ->values();

        $bedDailyPrice = (float) ($admission->bed?->daily_price ?? 0);

        $serviceBreakdown = [];
        $addonBreakdown = [];
        $dailyCoverageDetails = [];

        $totalStayServiceCost = 0.0;
        $totalPatientStayShare = 0.0;
        $totalInsuranceStayShare = 0.0;

        $cumulativeDayCounter = 1; // يوم الإقامة داخل هذا الرقود

        if ($stayLogs->isNotEmpty()) {
            $totalCalculatedDays = $billableDays['days'];
            $closedLogsDays = (int) $stayLogs->whereNotNull('end_time')->sum('days_count');
            $activeOpenDays = max(1, $totalCalculatedDays - $closedLogsDays);

            foreach ($stayLogs as $log) {
                $days = $log->end_time === null ? $activeOpenDays : max(1, (int) $log->days_count);
                $serviceFee = (float) $log->daily_fee;
                $bedFee = (float) $log->bed_daily_fee;
                $effectiveDailyRate = $serviceFee + $bedFee;

                $cost = (float) ($days * $effectiveDailyRate);
                $totalStayServiceCost += $cost;

                if ($log->end_time === null && ($log->days_count != $days || (float) $log->total_cost != $cost)) {
                    $log->updateQuietly([
                        'days_count' => $days,
                        'total_cost' => $cost,
                    ]);
                }

                $logPatientShare = 0.0;
                $logInsuranceShare = 0.0;
                $logAvgHiaPercent = 0.0;

                if ($isInsured) {
                    for ($d = 0; $d < $days; $d++) {
                        $stayDayNo = $cumulativeDayCounter + $d;
                        $annualDayNo = $priorAnnualUsedDays + $stayDayNo;

                        $cov = $this->getInsuranceCoverageForDay($annualDayNo, $effectiveDailyRate, $policyCategory);
                        $logPatientShare += $cov['patient_share'];
                        $logInsuranceShare += $cov['hia_share'];

                        $dailyCoverageDetails[] = array_merge($cov, [
                            'stay_day_number' => $stayDayNo,
                            'annual_day_number' => $annualDayNo,
                            'service_name' => $log->ccuService?->name ?? ($log->service_name_snapshot ?: 'إقامة عناية مركزة'),
                            'daily_rate' => $effectiveDailyRate,
                        ]);
                    }

                    $logAvgHiaPercent = $cost > 0 ? round(($logInsuranceShare / $cost) * 100, 1) : 0;
                    $totalPatientStayShare += $logPatientShare;
                    $totalInsuranceStayShare += $logInsuranceShare;

                    if ((float) $log->patient_share != $logPatientShare || (float) $log->insurance_share != $logInsuranceShare) {
                        $log->updateQuietly([
                            'patient_share' => $logPatientShare,
                            'insurance_share' => $logInsuranceShare,
                            'coverage_percentage' => $logAvgHiaPercent,
                        ]);
                    }
                } else {
                    $logPatientShare = $cost;
                    $totalPatientStayShare += $cost;
                }

                $cumulativeDayCounter += $days;

                $serviceBreakdown[] = [
                    'log_id' => $log->id,
                    'service_name' => $log->ccuService?->name ?? ($log->service_name_snapshot ?: 'إقامة العناية المركزة'),
                    'official_claim_name' => $log->ccuService?->official_claim_name ?? null,
                    'daily_fee' => $effectiveDailyRate,
                    'service_only_fee' => $serviceFee,
                    'bed_daily_fee' => $bedFee,
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
            // في حال عدم وجود سجل باقات مخصص بعد: نعتمد الباقة المحددة في الإضبارة أو تسعيرة السرير الأساسية
            $activeService = $admission->ccuService ?? $this->inferDefaultService($admission);
            $daysCount = $billableDays['days'];

            if ($activeService) {
                $serviceName = $activeService->name;
                $officialClaimName = $activeService->official_claim_name;
                if ($activeService->include_bed_fee) {
                    $serviceOnlyFee = $activeService->getServiceOnlyFee($insuranceType);
                    $appliedBedFee = 0.0;
                    $dailyRate = $serviceOnlyFee;
                } else {
                    $serviceOnlyFee = $activeService->getServiceOnlyFee($insuranceType);
                    $appliedBedFee = $bedDailyPrice;
                    $dailyRate = $serviceOnlyFee + $appliedBedFee;
                }
            } else {
                $serviceName = 'أجرة سرير العناية المركزة';
                $officialClaimName = 'رقود ردهة العناية المركزة';
                $serviceOnlyFee = 0.0;
                $appliedBedFee = $bedDailyPrice;
                $dailyRate = $bedDailyPrice;
            }

            $cost = $daysCount * $dailyRate;
            $totalStayServiceCost = $cost;

            if ($isInsured) {
                for ($d = 1; $d <= $daysCount; $d++) {
                    $annualDayNo = $priorAnnualUsedDays + $d;
                    $cov = $this->getInsuranceCoverageForDay($annualDayNo, $dailyRate, $policyCategory);
                    $totalPatientStayShare += $cov['patient_share'];
                    $totalInsuranceStayShare += $cov['hia_share'];
                    $dailyCoverageDetails[] = array_merge($cov, [
                        'stay_day_number' => $d,
                        'annual_day_number' => $annualDayNo,
                        'service_name' => $serviceName,
                        'daily_rate' => $dailyRate,
                    ]);
                }
            } else {
                $totalPatientStayShare = $cost;
                $totalInsuranceStayShare = 0.0;
            }

            $serviceBreakdown[] = [
                'log_id' => null,
                'service_name' => $serviceName,
                'official_claim_name' => $officialClaimName,
                'daily_fee' => $dailyRate,
                'service_only_fee' => $serviceOnlyFee,
                'bed_daily_fee' => $appliedBedFee,
                'days' => $daysCount,
                'total' => $cost,
                'patient_share' => $totalPatientStayShare,
                'insurance_share' => $totalInsuranceStayShare,
                'hia_percent' => $cost > 0 ? round(($totalInsuranceStayShare / $cost) * 100, 1) : 0,
                'start_time' => $admissionDateTime->format('Y-m-d H:i'),
                'end_time' => $endDateTime->format('Y-m-d H:i'),
                'reason' => null,
                'notes' => null,
            ];
        }

        // خدمات الكاش / العناية الإضافية المنفصلة (Addon Custom Services)
        $totalAddonCost = 0.0;
        $totalPatientAddonShare = 0.0;
        $totalInsuranceAddonShare = 0.0;

        foreach ($addonLogs as $addon) {
            $qty = max(1, (int) $addon->days_count);
            $unitFee = (float) $addon->daily_fee;
            $addonTotal = $qty * $unitFee;
            $totalAddonCost += $addonTotal;

            $patShare = $addonTotal;
            $insShare = 0.0;
            $covPct = 0.0;

            if ($isInsured && $addon->ccuService && $addon->ccuService->is_hi_active) {
                $cov = $this->getInsuranceCoverageForDay($priorAnnualUsedDays + 1, $addonTotal, $policyCategory);
                $patShare = $cov['patient_share'];
                $insShare = $cov['hia_share'];
                $covPct = $cov['hia_percent'];
            }

            $totalPatientAddonShare += $patShare;
            $totalInsuranceAddonShare += $insShare;

            $addonBreakdown[] = [
                'log_id' => $addon->id,
                'service_name' => $addon->service_name_snapshot ?: ($addon->ccuService?->name ?? 'خدمة عناية مخصصة'),
                'unit_fee' => $unitFee,
                'quantity' => $qty,
                'total' => $addonTotal,
                'patient_share' => $patShare,
                'insurance_share' => $insShare,
                'hia_percent' => $covPct,
                'notes' => $addon->notes,
                'created_at' => $addon->start_time?->format('Y-m-d H:i') ?? $addon->created_at?->format('Y-m-d H:i'),
            ];
        }

        $totalServiceCost = $totalStayServiceCost + $totalAddonCost;
        $totalPatientServiceShare = $totalPatientStayShare + $totalPatientAddonShare;
        $totalInsuranceServiceShare = $totalInsuranceStayShare + $totalInsuranceAddonShare;

        // الباقة الفعالة لتحديد شمول المختبر والأشعة
        $effectiveActiveService = $this->getCurrentActivePackageService($admission);

        // استشارات الأطباء التخصصية
        $consultations = $admission->ccuConsultations->where('status', '!=', 'cancelled')->values();
        $internalConsHiaPct = CcuSetting::getFloat('internal_consultation_hia_percent', 90.0);
        $catAExempt = CcuSetting::getBool('category_a_exempt', true) && $policyCategory === 'A';

        $totalConsultationsCost = 0.0;
        $totalPatientConsultationsShare = 0.0;
        $totalInsuranceConsultationsShare = 0.0;
        $bundledConsultationsReference = 0.0;

        foreach ($consultations as $con) {
            $origFee = (float) ($con->original_fee > 0 ? $con->original_fee : $con->fee);
            $billingMode = $con->billing_mode ?: 'auto';
            $isBundledCon = ($billingMode === 'bundled');

            $con->original_fee_display = $origFee;
            $con->is_bundled = $isBundledCon;

            if ($isBundledCon) {
                $con->billed_fee = 0.0;
                $con->is_covered = true;
                $con->coverage_percentage = 100.0;
                $con->patient_share = 0.0;
                $con->insurance_share = 0.0;
                $con->coverage_status_text = 'مشمولة ضمن الباقة اليومية (0 د.ع)';
                $con->coverage_badge_class = 'bg-success-subtle text-success border border-success-subtle';
                $bundledConsultationsReference += $origFee;

                continue;
            }

            $con->billed_fee = $origFee;
            $totalConsultationsCost += $origFee;

            if ($isInsured && $con->consultation_type === 'internal') {
                $hiaPct = $catAExempt ? 100.0 : $internalConsHiaPct;
                $insShare = round($origFee * ($hiaPct / 100.0), 2);
                $patShare = round(max(0, $origFee - $insShare), 2);
                $con->is_covered = true;
                $con->coverage_percentage = $hiaPct;
                $con->coverage_status_text = 'مشمولة بالضمان ('.(int) $hiaPct.'%)';
                $con->coverage_badge_class = 'bg-success text-white';
            } else {
                $patShare = $origFee;
                $insShare = 0.0;
                $con->is_covered = false;
                $con->coverage_percentage = 0.0;
                $con->coverage_status_text = $isInsured ? 'خارجية - غير مشمولة (كاش 100%)' : 'نقدي (كاش)';
                $con->coverage_badge_class = $isInsured ? 'bg-secondary text-white' : 'bg-light text-dark border';
            }

            $con->insurance_share = $insShare;
            $con->patient_share = $patShare;

            $totalPatientConsultationsShare += $patShare;
            $totalInsuranceConsultationsShare += $insShare;
        }

        // طلبات المختبر والأشعة (مع ميزة السعر الصفري للباقة + حفظ القيمة الأصلية للمطالبة + التبديل بضغطة زر)
        $medicalOrders = $admission->medicalOrders
            ->whereIn('order_type', ['lab', 'radiology'])
            ->where('status', '!=', 'cancelled')
            ->values();

        $extraLabHiaPct = CcuSetting::getFloat('extra_lab_hia_percent', 75.0);
        $extraRadHiaPct = CcuSetting::getFloat('extra_radiology_hia_percent', 75.0);

        $totalExtraOrdersCost = 0.0;
        $totalPatientOrdersShare = 0.0;
        $totalInsuranceOrdersShare = 0.0;

        $totalLabCost = 0.0;
        $insuranceLabShare = 0.0;
        $patientLabShare = 0.0;
        $bundledLabReference = 0.0;

        $totalRadCost = 0.0;
        $insuranceRadShare = 0.0;
        $patientRadShare = 0.0;
        $bundledRadReference = 0.0;

        $bundledItemsDetails = [];

        foreach ($medicalOrders as $ord) {
            $catalogFallback = $ord->order_type === 'lab'
                ? (float) ($ord->labTest?->price ?? 0)
                : (float) ($ord->radiologyType?->base_price ?? $ord->radiologyType?->price ?? 0);

            $hiFallback = $ord->order_type === 'lab'
                ? ($ord->labTest?->is_hi_active && (float) $ord->labTest?->hi_price > 0 ? (float) $ord->labTest->hi_price : $catalogFallback)
                : ($ord->radiologyType?->is_hi_active && (float) $ord->radiologyType?->hi_price > 0 ? (float) $ord->radiologyType->hi_price : $catalogFallback);

            $origPrice = (float) ($ord->original_price > 0
                ? $ord->original_price
                : ((float) $ord->price > 0 ? $ord->price : ($isInsured ? $hiFallback : $catalogFallback)));

            $billingMode = $ord->billing_mode ?: 'auto';

            if ($billingMode === 'bundled') {
                $isBundled = true;
            } elseif ($billingMode === 'extra') {
                $isBundled = false;
            } else {
                // الوضع التلقائي Auto: يتحقق مما إذا كانت الباقة الحالية شاملة للمختبر أو الأشعة
                $isBundled = false;
                if ($isInsured && $effectiveActiveService) {
                    if ($ord->order_type === 'lab' && $effectiveActiveService->is_bundled_lab) {
                        $isBundled = true;
                    } elseif ($ord->order_type === 'radiology' && $effectiveActiveService->is_bundled_radiology) {
                        $isBundled = true;
                    }
                }
            }

            $ord->original_price_display = $origPrice;
            $ord->catalog_price_display = $catalogFallback > 0 ? $catalogFallback : $origPrice;
            $ord->is_bundled = $isBundled;

            if ($isBundled) {
                $ord->billed_price = 0.0;
                $ord->is_covered = true;
                $ord->coverage_percentage = 100.0;
                $ord->insurance_share = 0.0; // مدرج ضمن أجرة اليومية الثابتة حتى لا يُدبل المبلغ مالياً
                $ord->patient_share = 0.0;
                $ord->coverage_status_text = 'مشمول بالباقة اليومية (0 د.ع)';
                $ord->coverage_badge_class = 'bg-success-subtle text-success border border-success-subtle';

                if ($ord->order_type === 'lab') {
                    $bundledLabReference += $origPrice;
                } else {
                    $bundledRadReference += $origPrice;
                }

                $bundledItemsDetails[] = [
                    'id' => $ord->id,
                    'order_type' => $ord->order_type,
                    'item_name' => $ord->labTest?->name ?? $ord->radiologyType?->name ?? $ord->item_name,
                    'original_price' => $origPrice,
                    'catalog_price' => $ord->catalog_price_display,
                    'ordered_at' => optional($ord->ordered_at)->format('Y-m-d H:i'),
                ];

                continue;
            }

            // احتساب البند كفحص منفرد (Extra)
            $ord->billed_price = $origPrice;
            $isCovered = false;
            $covPercent = 0.0;
            $insShare = 0.0;
            $patShare = $origPrice;
            $statusText = $isInsured ? 'غير مشمول بالضمان (كاش 100%)' : 'نقدي (كاش)';
            $badgeClass = $isInsured ? 'bg-secondary text-white' : 'bg-light text-dark border';

            if ($isInsured) {
                if ($ord->order_type === 'lab') {
                    $test = $ord->labTest;
                    $isActiveHi = $test ? ($test->is_hi_active && (float) $test->hi_price > 0) : false;
                    $approvedPrice = $isActiveHi ? (float) $test->hi_price : $origPrice;

                    if ($isActiveHi || $billingMode === 'extra') {
                        // إذا كان غير مشمول رسمياً بالضمان يظهر كاش 100% ما لم يكن فعالاً بالضمان
                        if ($isActiveHi) {
                            $isCovered = true;
                            $covPercent = $catAExempt ? 100.0 : $extraLabHiaPct;
                            $ord->billed_price = $approvedPrice;
                            $insShare = round($approvedPrice * ($covPercent / 100.0), 2);
                            $patShare = round(max(0, $approvedPrice - $insShare), 2);
                            $statusText = 'محتسب بالضمان ('.(int) $covPercent.'%)';
                            $badgeClass = 'bg-info text-white';
                        } else {
                            $patShare = $origPrice;
                            $statusText = 'غير مشمول بالضمان - كاش 100%';
                        }
                    }
                } elseif ($ord->order_type === 'radiology') {
                    $rad = $ord->radiologyType;
                    $isActiveHi = $rad ? ($rad->is_hi_active && (float) $rad->hi_price > 0) : false;
                    $approvedPrice = $isActiveHi ? (float) $rad->hi_price : $origPrice;

                    if ($isActiveHi) {
                        $isCovered = true;
                        $covPercent = $catAExempt ? 100.0 : $extraRadHiaPct;
                        $ord->billed_price = $approvedPrice;
                        $insShare = round($approvedPrice * ($covPercent / 100.0), 2);
                        $patShare = round(max(0, $approvedPrice - $insShare), 2);
                        $statusText = 'محتسب بالضمان ('.(int) $covPercent.'%)';
                        $badgeClass = 'bg-info text-white';
                    } else {
                        $patShare = $origPrice;
                        $statusText = 'غير مشمول بالضمان - كاش 100%';
                    }
                }
            }

            $ord->is_covered = $isCovered;
            $ord->coverage_percentage = $covPercent;
            $ord->coverage_status_text = $statusText;
            $ord->coverage_badge_class = $badgeClass;
            $ord->insurance_share = $insShare;
            $ord->patient_share = $patShare;

            $billedVal = (float) $ord->billed_price;
            $totalExtraOrdersCost += $billedVal;
            $totalInsuranceOrdersShare += $insShare;
            $totalPatientOrdersShare += $patShare;

            if ($ord->order_type === 'lab') {
                $totalLabCost += $billedVal;
                $insuranceLabShare += $insShare;
                $patientLabShare += $patShare;
            } else {
                $totalRadCost += $billedVal;
                $insuranceRadShare += $insShare;
                $patientRadShare += $patShare;
            }
        }

        $bundledReferenceTotal = $bundledLabReference + $bundledRadReference + $bundledConsultationsReference;

        $grandTotal = $totalServiceCost + $totalConsultationsCost + $totalExtraOrdersCost;
        $referenceGrossWithBundled = $grandTotal + $bundledReferenceTotal;

        $insuranceGrandTotal = $isInsured
            ? ($totalInsuranceServiceShare + $totalInsuranceOrdersShare + $totalInsuranceConsultationsShare)
            : 0.0;

        $patientGrandTotal = $isInsured
            ? ($totalPatientServiceShare + $totalPatientOrdersShare + $totalPatientConsultationsShare)
            : $grandTotal;

        $hiaCollectedAmount = (float) ($admission->hia_collected_amount ?? 0);
        $hiaRemainingBalance = max(0.0, $insuranceGrandTotal - $hiaCollectedAmount);

        $coverageSummary = [
            'insurance_type' => $insuranceType,
            'policy_category' => $policyCategory,
            'annual_max_days' => $annualMaxDays,
            'prior_annual_used_days' => $priorAnnualUsedDays,
            'current_stay_days' => $billableDays['days'],
            'total_annual_days_with_current' => $priorAnnualUsedDays + $billableDays['days'],
            'remaining_annual_days' => max(0, $annualMaxDays - ($priorAnnualUsedDays + $billableDays['days'])),
            'total_service_cost' => $totalServiceCost,
            'total_addon_cost' => $totalAddonCost,
            'patient_service_share' => $totalPatientServiceShare,
            'insurance_service_share' => $totalInsuranceServiceShare,
            'total_lab_cost' => $totalLabCost,
            'patient_lab_share' => $patientLabShare,
            'insurance_lab_share' => $insuranceLabShare,
            'bundled_lab_reference' => $bundledLabReference,
            'total_radiology_cost' => $totalRadCost,
            'patient_radiology_share' => $patientRadShare,
            'insurance_radiology_share' => $insuranceRadShare,
            'bundled_radiology_reference' => $bundledRadReference,
            'total_consultations_cost' => $totalConsultationsCost,
            'patient_consultations_share' => $totalPatientConsultationsShare,
            'insurance_consultations_share' => $totalInsuranceConsultationsShare,
            'bundled_reference_total' => $bundledReferenceTotal,
            'reference_gross_with_bundled' => $referenceGrossWithBundled,
            'bundled_items_details' => $bundledItemsDetails,
            'daily_details' => $dailyCoverageDetails,
        ];

        // مزامنة الإجماليات المالية مباشرة مع سجل الإضبارة لاستخدامها في الخزينة والتقارير
        $admission->updateQuietly([
            'patient_share_amount' => $patientGrandTotal,
            'insurance_share_amount' => $insuranceGrandTotal,
            'bundled_reference_amount' => $bundledReferenceTotal,
            'coverage_summary' => $coverageSummary,
        ]);

        $totalPaid = $admission->total_paid_amount;
        $netBalance = max(0.0, $patientGrandTotal - $totalPaid);
        $excessDeposit = max(0.0, $totalPaid - $patientGrandTotal);

        return [
            'start_datetime' => $admissionDateTime,
            'end_datetime' => $endDateTime,
            'total_hours' => $totalHours,
            'calculated_days' => $billableDays['days'],
            'days_count' => $billableDays['days'],
            'admission_grace_applied' => $billableDays['admission_grace'],
            'discharge_grace_applied' => $billableDays['discharge_grace'],
            'grace_notes' => $billableDays['notes'],
            'is_insured' => $isInsured,
            'insurance_type' => $insuranceType,
            'policy_category' => $policyCategory,
            'active_service' => $effectiveActiveService,
            'annual_max_days' => $annualMaxDays,
            'prior_annual_used_days' => $priorAnnualUsedDays,
            'total_annual_days_with_current' => $priorAnnualUsedDays + $billableDays['days'],
            'remaining_annual_days' => max(0, $annualMaxDays - ($priorAnnualUsedDays + $billableDays['days'])),
            'total_stay_service_cost' => $totalStayServiceCost,
            'total_addon_cost' => $totalAddonCost,
            'total_service_cost' => $totalServiceCost,
            'patient_service_share' => $totalPatientServiceShare,
            'insurance_service_share' => $totalInsuranceServiceShare,
            'service_breakdown' => $serviceBreakdown,
            'addon_breakdown' => $addonBreakdown,
            'daily_coverage_details' => $dailyCoverageDetails,
            'total_lab_cost' => $totalLabCost,
            'insurance_lab_share' => $insuranceLabShare,
            'patient_lab_share' => $patientLabShare,
            'bundled_lab_reference' => $bundledLabReference,
            'total_radiology_cost' => $totalRadCost,
            'insurance_radiology_share' => $insuranceRadShare,
            'patient_radiology_share' => $patientRadShare,
            'bundled_radiology_reference' => $bundledRadReference,
            'total_consultations_cost' => $totalConsultationsCost,
            'insurance_consultations_share' => $totalInsuranceConsultationsShare,
            'patient_consultations_share' => $totalPatientConsultationsShare,
            'bundled_consultations_reference' => $bundledConsultationsReference,
            'bundled_reference_total' => $bundledReferenceTotal,
            'bundled_items_details' => $bundledItemsDetails,
            'reference_gross_with_bundled' => $referenceGrossWithBundled,
            'grand_total' => $grandTotal,
            'insurance_grand_total' => $insuranceGrandTotal,
            'patient_grand_total' => $patientGrandTotal,
            'hia_collected_amount' => $hiaCollectedAmount,
            'hia_remaining_balance' => $hiaRemainingBalance,
            'total_paid' => $totalPaid,
            'net_balance' => $netBalance,
            'excess_deposit' => $excessDeposit,
            'consultations' => $consultations,
            'medical_orders' => $medicalOrders,
            'payments' => $admission->ccuPayments,
        ];
    }

    /**
     * حساب عدد الأيام الفندقية المطبقة وفق قواعد الساعة 12:00 ظهراً وسماحية الساعات الداينميكية
     */
    public function calculateBillableDays(Carbon $admission, Carbon $discharge): array
    {
        if ($discharge->lessThanOrEqualTo($admission)) {
            return [
                'days' => 1,
                'admission_grace' => false,
                'discharge_grace' => false,
                'notes' => ['رقود جديد (الحد الأدنى يوم فندقي واحد)'],
            ];
        }

        $cutoff = $this->getCutoffHour();
        $grace = $this->getGracePeriodHours();

        $admissionGrace = false;
        $dischargeGrace = false;
        $notes = [];

        $admissionHour = $admission->hour + ($admission->minute / 60);
        if ($admissionHour >= ($cutoff - $grace) && $admissionHour < $cutoff) {
            $admissionGrace = true;
            $notes[] = 'دخول صباحي ضمن سماحية الـ '.$grace.' ساعات قبل 12:00 ظهراً';
        }

        $dischargeHour = $discharge->hour + ($discharge->minute / 60);
        if ($dischargeHour > $cutoff && $dischargeHour <= ($cutoff + $grace)) {
            $dischargeGrace = true;
            $notes[] = 'خروج ضمن سماحية الـ '.$grace.' ساعات بعد 12:00 ظهراً (بدون احتساب يوم إضافي)';
        }

        $days = $admission->copy()->startOfDay()->diffInDays($discharge->copy()->startOfDay());

        if ($days == 0) {
            $days = 1;
        } else {
            $days = max(1, $days);
            if ($dischargeHour >= ($cutoff + $grace)) {
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

    public function getHotelCycleDate(Carbon $dateTime): string
    {
        $cutoff = $this->getCutoffHour();

        return ($dateTime->hour < $cutoff)
            ? $dateTime->copy()->subDay()->format('Y-m-d')
            : $dateTime->format('Y-m-d');
    }

    /**
     * إجمالي أيام الرقود السابقة المستخدمة بالضمان الصحي خلال نفس السنة الميلادية للمريض
     */
    public function getPreviousAnnualInsuredDays(IcuAdmission $admission): int
    {
        if (! $admission->patient_id) {
            return 0;
        }

        $year = $admission->admission_date ? $admission->admission_date->year : now()->year;

        $priorAdmissions = IcuAdmission::where('patient_id', $admission->patient_id)
            ->where('id', '!=', $admission->id)
            ->where('is_insured', true)
            ->whereYear('admission_date', $year)
            ->get();

        $usedDays = 0;
        foreach ($priorAdmissions as $prior) {
            $start = Carbon::parse($prior->admission_date);
            $end = $prior->discharge_date ? Carbon::parse($prior->discharge_date) : now();
            $calc = $this->calculateBillableDays($start, $end);
            $usedDays += (int) $calc['days'];
        }

        return $usedDays;
    }

    /**
     * احتساب تفاصيل التغطية لليوم التراكمي المحدد وفق الشرائح الداينميكية بالقاعدة
     */
    public function getInsuranceCoverageForDay(int $cumulativeDayNumber, float $dailyFee, string $policyCategory = 'E'): array
    {
        $this->ensureDefaultsExist();

        $category = strtoupper(trim(str_replace('الفئة', '', $policyCategory))) ?: 'E';

        if (CcuSetting::getBool('category_a_exempt', true) && $category === 'A') {
            return [
                'hia_percent' => 100.0,
                'patient_percent' => 0.0,
                'hia_share' => $dailyFee,
                'patient_share' => 0.0,
                'tier_label' => 'الفئة A - إعفاء تام (100% ضمان)',
            ];
        }

        $annualMaxDays = CcuSetting::getInt('annual_max_insured_days', 30);
        if ($cumulativeDayNumber > $annualMaxDays) {
            $postMaxHia = CcuSetting::getFloat('post_max_days_hia_percent', 0.0);
            $postMaxPat = max(0.0, 100.0 - $postMaxHia);

            return [
                'hia_percent' => $postMaxHia,
                'patient_percent' => $postMaxPat,
                'hia_share' => round($dailyFee * ($postMaxHia / 100.0), 2),
                'patient_share' => round($dailyFee * ($postMaxPat / 100.0), 2),
                'tier_label' => "تجاوز الحد السنوي ({$annualMaxDays} يوم) - ".(int) $postMaxHia.'% ضمان / '.(int) $postMaxPat.'% مريض',
            ];
        }

        // البحث في الشرائح الداينميكية: أولوية للشريحة المخصصة لهذه الفئة إن وجدت ثم ALL
        $brackets = CcuStayBracket::active()->get();
        $matchedBracket = $brackets->first(fn ($b) => strtoupper($b->policy_category) === $category && $b->matchesDay($cumulativeDayNumber))
            ?? $brackets->first(fn ($b) => strtoupper($b->policy_category) === 'ALL' && $b->matchesDay($cumulativeDayNumber));

        if ($matchedBracket) {
            $hiaPct = (float) $matchedBracket->hia_percent;
            $patPct = (float) $matchedBracket->patient_percent;

            return [
                'hia_percent' => $hiaPct,
                'patient_percent' => $patPct,
                'hia_share' => round($dailyFee * ($hiaPct / 100.0), 2),
                'patient_share' => round($dailyFee * ($patPct / 100.0), 2),
                'tier_label' => $matchedBracket->name.' ('.(int) $hiaPct.'% ضمان / '.(int) $patPct.'% مريض)',
            ];
        }

        // احتياط برمجي في حال فراغ الشرائح: تطبيق النسب التنازلية القياسية
        if ($cumulativeDayNumber <= 5) {
            [$hiaPct, $patPct] = [75.0, 25.0];
        } elseif ($cumulativeDayNumber <= 10) {
            [$hiaPct, $patPct] = [50.0, 50.0];
        } elseif ($cumulativeDayNumber <= 15) {
            [$hiaPct, $patPct] = [25.0, 75.0];
        } else {
            [$hiaPct, $patPct] = [10.0, 90.0];
        }

        return [
            'hia_percent' => $hiaPct,
            'patient_percent' => $patPct,
            'hia_share' => round($dailyFee * ($hiaPct / 100.0), 2),
            'patient_share' => round($dailyFee * ($patPct / 100.0), 2),
            'tier_label' => 'شريحة اليوم '.$cumulativeDayNumber.' ('.(int) $hiaPct.'% ضمان / '.(int) $patPct.'% مريض)',
        ];
    }

    /**
     * استنتاج الباقة الافتراضية للإضبارة إذا لم تُحدد صراحةً بعد
     */
    protected function inferDefaultService(IcuAdmission $admission): ?CcuService
    {
        if ($admission->is_ventilated) {
            return CcuService::where('code', 'ventilator_support')->first();
        }

        if ($admission->bed && $admission->bed->bed_type === 'ccu') {
            return CcuService::where('code', 'ccu_cardiac')->first();
        }

        if ($admission->is_insured) {
            return CcuService::where('code', 'oxygen_monitoring')->first();
        }

        return null;
    }

    protected function getCurrentActivePackageService(IcuAdmission $admission): ?CcuService
    {
        $openLog = $admission->ccuServiceLogs
            ->where('log_category', '!=', CcuServiceLog::CATEGORY_ADDON_SERVICE)
            ->whereNull('end_time')
            ->sortByDesc('start_time')
            ->first();

        if ($openLog && $openLog->ccuService) {
            return $openLog->ccuService;
        }

        return $admission->ccuService ?? $this->inferDefaultService($admission);
    }

    /**
     * تبديل باقة الرعاية / الخدمة الأساسية (فنتليتر <-> مراقبة أكسجين <-> CCU <-> كاش سرير منفصل)
     * مع منع ازدواجية احتساب اليوم عند التبديل ضمن نفس الدورة الفندقية
     */
    public function switchService(
        IcuAdmission $admission,
        CcuService $newService,
        ?string $reason = null,
        ?int $userId = null
    ): CcuServiceLog {
        $now = now();
        $isInsured = (bool) $admission->is_insured;
        $insType = $isInsured ? ($admission->insurance_type ?: 'hi') : 'none';
        $bedPrice = (float) ($admission->bed?->daily_price ?? 0);

        $serviceOnlyFee = $newService->getServiceOnlyFee($insType);
        $appliedBedFee = $newService->include_bed_fee ? 0.0 : $bedPrice;
        $effectiveTotalDaily = $serviceOnlyFee + $appliedBedFee;

        $lastLog = $admission->ccuServiceLogs()
            ->where('log_category', '!=', CcuServiceLog::CATEGORY_ADDON_SERVICE)
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        // مزامنة علم جهاز التنفس والوضع
        $isVent = $newService->code === 'ventilator_support' || str_contains(strtolower($newService->name), 'ventilator');

        // 1. نافذة التصحيح السريع (خلال 15 دقيقة)
        if ($lastLog && $lastLog->start_time && $lastLog->start_time->diffInMinutes($now) <= 15) {
            $lastLog->update([
                'ccu_service_id' => $newService->id,
                'service_name_snapshot' => $newService->name,
                'daily_fee' => $serviceOnlyFee,
                'bed_daily_fee' => $appliedBedFee,
                'total_cost' => ($lastLog->days_count ?: 1) * $effectiveTotalDaily,
                'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                'switched_by' => $userId,
                'notes' => 'تصحيح فوري للباقة خلال نافذة التراجع دون احتساب يوم جديد.',
            ]);

            $admission->update([
                'ccu_service_id' => $newService->id,
                'is_ventilated' => $isVent,
                'pricing_mode' => $newService->include_bed_fee ? 'package' : 'bed_plus_services',
            ]);
            $this->calculateStayDetails($admission);

            return $lastLog;
        }

        if ($lastLog) {
            $lastCycle = $this->getHotelCycleDate(Carbon::parse($lastLog->start_time));
            $currentCycle = $this->getHotelCycleDate($now);

            if ($lastCycle === $currentCycle) {
                // التبديل خلال نفس اليوم الفندقي: اعتماد القيمة الأعلى حتى لا يدفع المريض مرتين عن نفس اليوم
                $prevDaily = (float) $lastLog->daily_fee + (float) $lastLog->bed_daily_fee;
                $useHigher = max($prevDaily, $effectiveTotalDaily);

                $lastLog->update([
                    'ccu_service_id' => $newService->id,
                    'service_name_snapshot' => $newService->name,
                    'daily_fee' => $useHigher - $appliedBedFee,
                    'bed_daily_fee' => $appliedBedFee,
                    'total_cost' => ($lastLog->days_count ?: 1) * $useHigher,
                    'reason_for_switch' => $reason ?: $lastLog->reason_for_switch,
                    'switched_by' => $userId,
                    'notes' => 'تم تعديل باقة الرعاية في نفس اليوم الفندقي إلى ('.$newService->name.') واعتماد التسعيرة الأعلى ('.number_format($useHigher).' د.ع).',
                ]);

                $admission->update([
                    'ccu_service_id' => $newService->id,
                    'is_ventilated' => $isVent,
                    'pricing_mode' => $newService->include_bed_fee ? 'package' : 'bed_plus_services',
                ]);
                $this->calculateStayDetails($admission);

                return $lastLog;
            }

            // انتهت أيام الدورة السابقة: غلق السجل القديم وفتح سجل جديد للباقة الجديدة
            $completedDays = max(1, Carbon::parse($lastCycle)->diffInDays(Carbon::parse($currentCycle)));
            $prevDaily = (float) $lastLog->daily_fee + (float) $lastLog->bed_daily_fee;

            $lastLog->update([
                'end_time' => $now,
                'days_count' => $completedDays,
                'total_cost' => $completedDays * $prevDaily,
            ]);
        }

        $admission->update([
            'ccu_service_id' => $newService->id,
            'is_ventilated' => $isVent,
            'pricing_mode' => $newService->include_bed_fee ? 'package' : 'bed_plus_services',
        ]);

        $newLog = CcuServiceLog::create([
            'icu_admission_id' => $admission->id,
            'ccu_service_id' => $newService->id,
            'log_category' => CcuServiceLog::CATEGORY_STAY_PACKAGE,
            'service_name_snapshot' => $newService->name,
            'daily_fee' => $serviceOnlyFee,
            'bed_daily_fee' => $appliedBedFee,
            'start_time' => $now,
            'end_time' => null,
            'days_count' => 1,
            'total_cost' => $effectiveTotalDaily,
            'reason_for_switch' => $reason,
            'switched_by' => $userId,
        ]);

        $this->calculateStayDetails($admission);

        return $newLog;
    }

    /**
     * إضافة خدمة مخصصة منفصلة (كأجهزة الكاش الإضافية أو الخدمات التمريضية الخاصة)
     */
    public function addAddonService(
        IcuAdmission $admission,
        ?CcuService $service,
        string $customName,
        float $unitPrice,
        int $quantity = 1,
        ?string $notes = null,
        ?int $userId = null
    ): CcuServiceLog {
        $qty = max(1, $quantity);
        $log = CcuServiceLog::create([
            'icu_admission_id' => $admission->id,
            'ccu_service_id' => $service?->id,
            'log_category' => CcuServiceLog::CATEGORY_ADDON_SERVICE,
            'service_name_snapshot' => $customName ?: ($service?->name ?? 'خدمة مخصصة'),
            'daily_fee' => $unitPrice,
            'bed_daily_fee' => 0,
            'start_time' => now(),
            'end_time' => now(),
            'days_count' => $qty,
            'total_cost' => $qty * $unitPrice,
            'switched_by' => $userId,
            'notes' => $notes,
        ]);

        $this->calculateStayDetails($admission);

        return $log;
    }
}
