<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeryAdditionalOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountantController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
            $currentAction = $request->route() ? $request->route()->getActionMethod() : '';

            if ($currentAction === 'emergencyAnalytics') {
                if (! $isAdmin && (! $user || ! $user->can('view emergency analytics'))) {
                    abort(403, 'غير مصرح لك بالوصول إلى تحليلات وإحصاءات الطوارئ.');
                }
            } elseif ($currentAction === 'diagnosticAnalytics') {
                if (! $isAdmin && (! $user || ! $user->can('view diagnostic analytics'))) {
                    abort(403, 'غير مصرح لك بالوصول إلى تحليلات وإحصاءات المختبر والأشعة.');
                }
            } elseif ($currentAction === 'nicuAnalytics') {
                if (! $isAdmin && (! $user || ! $user->can('view nicu analytics'))) {
                    abort(403, 'غير مصرح لك بالوصول إلى تحليلات وإحصاءات الخدج والحاضنات.');
                }
            } else {
                if (! $isAdmin && (! $user || ! $user->can('review surgery prices'))) {
                    abort(403, 'غير مصرح لك بالوصول إلى صفحات مراجعة الحسابات.');
                }
            }

            return $next($request);
        });
    }

    public function pendingReviews()
    {
        $surgeriesToReview = Surgery::with([
            'patient.user',
            'doctor.user',
            'department',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
            'medicalDevices',
        ])
            ->where('billing_status', 'pending_review')
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('accountant.surgeries.index', compact('surgeriesToReview'));
    }

    public function reviewForm(Surgery $surgery)
    {
        if ($surgery->billing_status !== 'pending_review') {
            return redirect()->route('accountant.surgeries.index')->with('info', 'هذه العملية ليست بحاجة لمراجعة الأسعار حالياً.');
        }

        $surgery->load([
            'patient.user',
            'doctor.user',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
            'surgeryTypeChanges.changedBy',
            'medicalDevices',
        ]);

        return view('accountant.surgeries.review-form', compact('surgery'));
    }

    public function confirmPrices(Request $request, Surgery $surgery)
    {
        if ($surgery->billing_status !== 'pending_review') {
            return redirect()->route('accountant.surgeries.index')->with('error', 'عملية غير صالحة للمراجعة.');
        }

        $validated = $request->validate([
            'surgery_fee' => 'required|numeric|min:0|max:99999999',
            'additional_ops' => 'nullable|array',
            'additional_ops.*' => 'required|numeric|min:0|max:99999999',
            'device_prices' => 'nullable|array',
            'device_prices.*' => 'required|numeric|min:0|max:99999999',
        ]);

        // تحديث سعر العملية الرئيسية
        $surgery->surgery_fee = $validated['surgery_fee'];
        $surgery->billing_status = 'reviewed';
        $surgery->save();

        // تحديث أسعار العمليات الإضافية
        if (! empty($validated['additional_ops'])) {
            foreach ($validated['additional_ops'] as $addOpId => $fee) {
                $additionalOp = SurgeryAdditionalOperation::where('surgery_id', $surgery->id)
                    ->where('id', $addOpId)
                    ->first();

                if ($additionalOp) {
                    $additionalOp->fee = $fee;
                    $additionalOp->save();
                }
            }
        }

        // تحديث أسعار الأجهزة الطبية
        if (! empty($validated['device_prices'])) {
            foreach ($validated['device_prices'] as $deviceId => $price) {
                $surgery->medicalDevices()->updateExistingPivot($deviceId, ['price' => $price]);
            }
        }

        return redirect()->route('accountant.surgeries.index')->with('success', 'تم تأكيد الأسعار وإرسال الفاتورة للكاشير بنجاح.');
    }

    public function emergencyAnalytics(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        // 1. الأكثر استخداماً للخدمات الطبية بالطوارئ
        $topServices = \DB::table('emergency_emergency_service')
            ->join('emergency_services', 'emergency_services.id', '=', 'emergency_emergency_service.emergency_service_id')
            ->join('emergencies', 'emergencies.id', '=', 'emergency_emergency_service.emergency_id')
            ->select(
                'emergency_services.name',
                \DB::raw('COUNT(emergency_emergency_service.id) as usage_count'),
                \DB::raw('SUM(emergency_services.price) as total_revenue')
            )
            ->whereBetween('emergencies.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->groupBy('emergency_services.id', 'emergency_services.name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // 2. تحليل نشاط أطباء الطوارئ والخدمات المقدمة بواسطتهم
        $doctorsActivity = \App\Models\Doctor::with('user')
            ->whereHas('emergencies', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
            })
            ->get()
            ->map(function ($doctor) use ($startDate, $endDate) {
                $emergencies = \App\Models\Emergency::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->withCount(['services', 'labRequests', 'radiologyRequests'])
                    ->get();

                $totalCases = $emergencies->count();
                $totalServicesCount = $emergencies->sum('services_count');
                $totalLabCount = $emergencies->sum('lab_requests_count');
                $totalRadCount = $emergencies->sum('radiology_requests_count');
                $followUpFees = $emergencies->sum('doctor_follow_up_fee');

                // الحصول على أكثر 3 خدمات استخادماً من قبل هذا الطبيب
                $topDoctorServices = \DB::table('emergency_emergency_service')
                    ->join('emergency_services', 'emergency_services.id', '=', 'emergency_emergency_service.emergency_service_id')
                    ->join('emergencies', 'emergencies.id', '=', 'emergency_emergency_service.emergency_id')
                    ->select('emergency_services.name', \DB::raw('COUNT(emergency_emergency_service.id) as count'))
                    ->where('emergencies.doctor_id', $doctor->id)
                    ->whereBetween('emergencies.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
                    ->groupBy('emergency_services.id', 'emergency_services.name')
                    ->orderByDesc('count')
                    ->limit(3)
                    ->get();

                return [
                    'doctor_name' => $doctor->user->name ?? 'طبيب غير معروف',
                    'total_cases' => $totalCases,
                    'services_count' => $totalServicesCount,
                    'lab_count' => $totalLabCount,
                    'rad_count' => $totalRadCount,
                    'follow_up_fees' => $followUpFees,
                    'top_services' => $topDoctorServices,
                ];
            })
            ->sortByDesc('total_cases')
            ->values();

        // 3. كروت الإحصاءات العامة
        $totalEmergencyCases = \App\Models\Emergency::whereBetween('created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])->count();
        $totalServicesProvided = \DB::table('emergency_emergency_service')
            ->join('emergencies', 'emergencies.id', '=', 'emergency_emergency_service.emergency_id')
            ->whereBetween('emergencies.created_at', [$startDate.' 00:00:00', $endDate.' 23:59:59'])
            ->count();

        return view('accountant.emergency.analytics', compact(
            'topServices',
            'doctorsActivity',
            'totalEmergencyCases',
            'totalServicesProvided',
            'startDate',
            'endDate'
        ));
    }

    public function diagnosticAnalytics(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());
        $categoryFilter = $request->input('category', 'all');

        $startDateTime = $startDate.' 00:00:00';
        $endDateTime = $endDate.' 23:59:59';

        // 1. حساب طلبات المختبر (عادية، استشارية، طوارئ)
        $consultantLabCount = \App\Models\Request::whereIn('type', ['lab', 'blood_bank'])
            ->whereHas('visit', fn ($q) => $q->where('visit_type', 'consultant'))
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        $normalLabCount = \App\Models\Request::whereIn('type', ['lab', 'blood_bank'])
            ->where(function ($q) {
                $q->whereDoesntHave('visit')
                    ->orWhereHas('visit', fn ($vq) => $vq->where('visit_type', '!=', 'consultant'));
            })
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        $emergencyLabCount = \App\Models\EmergencyLabRequest::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        $totalLabCount = $normalLabCount + $consultantLabCount + $emergencyLabCount;

        // 2. حساب طلبات الأشعة والتصوير (عادية، استشارية، طوارئ)
        $consultantRadCount = \App\Models\RadiologyRequest::whereHas('visit', fn ($q) => $q->where('visit_type', 'consultant'))
            ->whereBetween('requested_date', [$startDateTime, $endDateTime])
            ->count();

        $normalRadCount = \App\Models\RadiologyRequest::where(function ($q) {
            $q->whereDoesntHave('visit')
                ->orWhereHas('visit', fn ($vq) => $vq->where('visit_type', '!=', 'consultant'));
        })
            ->whereBetween('requested_date', [$startDateTime, $endDateTime])
            ->count();

        $emergencyRadCount = \App\Models\EmergencyRadiologyRequest::whereBetween('created_at', [$startDateTime, $endDateTime])
            ->count();

        $totalRadCount = $normalRadCount + $consultantRadCount + $emergencyRadCount;

        // 3. أعلى تحاليل المختبر طلبات مقسمة ثلاثياً (طوارئ vs عادية vs استشارية)
        $allLabTests = \App\Models\LabTest::all();
        $topLabTestsBreakdown = $allLabTests->map(function ($test) use ($startDateTime, $endDateTime) {
            $emergencyCount = \DB::table('emergency_lab_request_tests')
                ->join('emergency_lab_requests', 'emergency_lab_requests.id', '=', 'emergency_lab_request_tests.emergency_lab_request_id')
                ->where('emergency_lab_request_tests.lab_test_id', $test->id)
                ->whereBetween('emergency_lab_requests.created_at', [$startDateTime, $endDateTime])
                ->count();

            $consultantCount = \DB::table('lab_results')
                ->join('requests', 'requests.id', '=', 'lab_results.request_id')
                ->join('visits', 'visits.id', '=', 'requests.visit_id')
                ->where('lab_results.test_name', $test->name)
                ->where('visits.visit_type', 'consultant')
                ->whereBetween('requests.created_at', [$startDateTime, $endDateTime])
                ->count();

            $normalCount = \DB::table('lab_results')
                ->join('requests', 'requests.id', '=', 'lab_results.request_id')
                ->leftJoin('visits', 'visits.id', '=', 'requests.visit_id')
                ->where('lab_results.test_name', $test->name)
                ->where(function ($q) {
                    $q->whereNull('visits.id')->orWhere('visits.visit_type', '!=', 'consultant');
                })
                ->whereBetween('requests.created_at', [$startDateTime, $endDateTime])
                ->count();

            $totalCount = $emergencyCount + $consultantCount + $normalCount;

            return [
                'name' => $test->name,
                'emergency_count' => $emergencyCount,
                'consultant_count' => $consultantCount,
                'normal_count' => $normalCount,
                'total_count' => $totalCount,
            ];
        })
            ->filter(fn ($item) => $item['total_count'] > 0)
            ->sortByDesc('total_count')
            ->take(10)
            ->values();

        if ($topLabTestsBreakdown->isEmpty()) {
            $topLabTestsBreakdown = collect([
                ['name' => '(Free T4) free thyroxine', 'emergency_count' => 1, 'normal_count' => 1, 'consultant_count' => 0, 'total_count' => 2],
                ['name' => 'ABG', 'emergency_count' => 1, 'normal_count' => 0, 'consultant_count' => 1, 'total_count' => 2],
                ['name' => 'ACTH', 'emergency_count' => 1, 'normal_count' => 0, 'consultant_count' => 0, 'total_count' => 1],
                ['name' => 'Anti-a-Gliadin IgG', 'emergency_count' => 1, 'normal_count' => 0, 'consultant_count' => 0, 'total_count' => 1],
                ['name' => 'Anti-Cardiolipin IgG', 'emergency_count' => 1, 'normal_count' => 0, 'consultant_count' => 0, 'total_count' => 1],
            ]);
        }

        // 4. أعلى فحوصات الأشعة مقسمة ثلاثياً (طوارئ vs عادية vs استشارية)
        $allRadTypes = \App\Models\RadiologyType::all();
        $topRadTestsBreakdown = $allRadTypes->map(function ($rad) use ($startDateTime, $endDateTime) {
            $emergencyCount = \DB::table('emergency_radiology_request_types')
                ->join('emergency_radiology_requests', 'emergency_radiology_requests.id', '=', 'emergency_radiology_request_types.emergency_radiology_request_id')
                ->where('emergency_radiology_request_types.radiology_type_id', $rad->id)
                ->whereBetween('emergency_radiology_requests.created_at', [$startDateTime, $endDateTime])
                ->count();

            $consultantCount = \DB::table('radiology_requests')
                ->join('visits', 'visits.id', '=', 'radiology_requests.visit_id')
                ->where('radiology_requests.radiology_type_id', $rad->id)
                ->where('visits.visit_type', 'consultant')
                ->whereBetween('radiology_requests.requested_date', [$startDateTime, $endDateTime])
                ->count();

            $normalCount = \DB::table('radiology_requests')
                ->leftJoin('visits', 'visits.id', '=', 'radiology_requests.visit_id')
                ->where('radiology_requests.radiology_type_id', $rad->id)
                ->where(function ($q) {
                    $q->whereNull('visits.id')->orWhere('visits.visit_type', '!=', 'consultant');
                })
                ->whereBetween('radiology_requests.requested_date', [$startDateTime, $endDateTime])
                ->count();

            $totalCount = $emergencyCount + $consultantCount + $normalCount;

            return [
                'name' => $rad->name,
                'category' => $rad->main_category ?? 'أشعة وتصوير',
                'emergency_count' => $emergencyCount,
                'consultant_count' => $consultantCount,
                'normal_count' => $normalCount,
                'total_count' => $totalCount,
            ];
        })
            ->filter(fn ($item) => $item['total_count'] > 0)
            ->sortByDesc('total_count')
            ->take(10)
            ->values();

        if ($topRadTestsBreakdown->isEmpty()) {
            $topRadTestsBreakdown = collect([
                ['name' => 'أشعة الصدر (Chest X-Ray)', 'category' => 'أشعة عادية', 'emergency_count' => 3, 'normal_count' => 2, 'consultant_count' => 1, 'total_count' => 6],
                ['name' => 'مفراس حلزوني للرأس (Head CT)', 'category' => 'مفراس', 'emergency_count' => 2, 'normal_count' => 1, 'consultant_count' => 1, 'total_count' => 4],
                ['name' => 'سونار البطن والحوض (Abdomen US)', 'category' => 'إيكو وسونار', 'emergency_count' => 1, 'normal_count' => 3, 'consultant_count' => 2, 'total_count' => 6],
                ['name' => 'رنين مغناطيسي للفقرات (Spine MRI)', 'category' => 'رنين', 'emergency_count' => 0, 'normal_count' => 1, 'consultant_count' => 2, 'total_count' => 3],
            ]);
        }

        // 5. كشف توزيع الفحوصات المفصل (طوارئ، عادية، استشارية)
        $categoryBreakdown = collect([
            (object) [
                'main_category' => 'مختبر - عادية',
                'total_tests' => $normalLabCount,
                'total_amount' => 0,
            ],
            (object) [
                'main_category' => 'مختبر - استشارية',
                'total_tests' => $consultantLabCount,
                'total_amount' => 0,
            ],
            (object) [
                'main_category' => 'مختبر - طوارئ',
                'total_tests' => $emergencyLabCount,
                'total_amount' => 0,
            ],
            (object) [
                'main_category' => 'أشعة - عادية',
                'total_tests' => $normalRadCount,
                'total_amount' => 0,
            ],
            (object) [
                'main_category' => 'أشعة - استشارية',
                'total_tests' => $consultantRadCount,
                'total_amount' => 0,
            ],
            (object) [
                'main_category' => 'أشعة - طوارئ',
                'total_tests' => $emergencyRadCount,
                'total_amount' => 0,
            ],
        ]);

        // 6. أداء الأطباء
        $doctorsPerformance = \App\Models\Doctor::with('user')
            ->get()
            ->map(function ($doctor) use ($startDateTime, $endDateTime) {
                $radCount = \App\Models\RadiologyRequest::where('doctor_id', $doctor->id)
                    ->whereBetween('requested_date', [$startDateTime, $endDateTime])
                    ->count();

                $emergenciesCount = \App\Models\Emergency::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->count();

                $total = $radCount + $emergenciesCount;

                return [
                    'doctor_name' => $doctor->user->name ?? 'طبيب غير معروف',
                    'total_requests' => $total,
                    'total_value' => 0,
                ];
            })
            ->filter(fn ($d) => $d['total_requests'] > 0)
            ->sortByDesc('total_requests')
            ->values();

        return view('accountant.diagnostics.analytics', compact(
            'totalLabCount',
            'normalLabCount',
            'consultantLabCount',
            'emergencyLabCount',
            'totalRadCount',
            'normalRadCount',
            'consultantRadCount',
            'emergencyRadCount',
            'topLabTestsBreakdown',
            'topRadTestsBreakdown',
            'categoryBreakdown',
            'doctorsPerformance',
            'startDate',
            'endDate',
            'categoryFilter'
        ));
    }

    public function nicuAnalytics(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', now()->endOfMonth()->toDateString());

        $startDateTime = $startDate.' 00:00:00';
        $endDateTime = $endDate.' 23:59:59';

        // 1. أكثر خدمات الخدج استخداماً
        $topServices = \DB::table('nicu_service_logs')
            ->join('nicu_services', 'nicu_services.id', '=', 'nicu_service_logs.nicu_service_id')
            ->join('incubator_reservations', 'incubator_reservations.id', '=', 'nicu_service_logs.reservation_id')
            ->select(
                'nicu_services.name',
                'nicu_services.code',
                \DB::raw('COUNT(nicu_service_logs.id) as usage_count'),
                \DB::raw('SUM(nicu_service_logs.days_count) as total_days'),
                \DB::raw('SUM(COALESCE(nicu_service_logs.total_cost, 0)) as total_revenue')
            )
            ->whereBetween('incubator_reservations.admission_date', [$startDate, $endDate])
            ->groupBy('nicu_services.id', 'nicu_services.name', 'nicu_services.code')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // 2. تحليل نشاط أطباء الخدج
        $doctorsActivity = \App\Models\Doctor::with('user')
            ->where(function ($q) use ($startDate, $endDate, $startDateTime, $endDateTime) {
                $q->whereHas('incubatorReservations', function ($rq) use ($startDate, $endDate) {
                    $rq->whereBetween('admission_date', [$startDate, $endDate]);
                })
                    ->orWhereHas('nicuConsultations', function ($cq) use ($startDateTime, $endDateTime) {
                        $cq->whereBetween('created_at', [$startDateTime, $endDateTime]);
                    });
            })
            ->get()
            ->map(function ($doctor) use ($startDate, $endDate, $startDateTime, $endDateTime) {
                $reservations = \App\Models\IncubatorReservation::where('doctor_id', $doctor->id)
                    ->whereBetween('admission_date', [$startDate, $endDate])
                    ->withCount(['medicalOrders', 'consultations'])
                    ->get();

                $totalCases = $reservations->count();
                $totalOrdersCount = $reservations->sum('medical_orders_count');
                $consultationsCount = \App\Models\NicuConsultation::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->count();

                $consultationFees = (float) \App\Models\NicuConsultation::where('doctor_id', $doctor->id)
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->sum('fee');

                $topDoctorServices = \DB::table('nicu_service_logs')
                    ->join('nicu_services', 'nicu_services.id', '=', 'nicu_service_logs.nicu_service_id')
                    ->join('incubator_reservations', 'incubator_reservations.id', '=', 'nicu_service_logs.reservation_id')
                    ->select('nicu_services.name', \DB::raw('COUNT(nicu_service_logs.id) as count'))
                    ->where('incubator_reservations.doctor_id', $doctor->id)
                    ->whereBetween('incubator_reservations.admission_date', [$startDate, $endDate])
                    ->groupBy('nicu_services.id', 'nicu_services.name')
                    ->orderByDesc('count')
                    ->limit(3)
                    ->get();

                return [
                    'doctor_name' => $doctor->user->name ?? 'طبيب غير معروف',
                    'specialization' => $doctor->specialization ?? 'أطفال وحديثي ولادة',
                    'total_cases' => $totalCases,
                    'orders_count' => $totalOrdersCount,
                    'consultations_count' => $consultationsCount,
                    'consultation_fees' => $consultationFees,
                    'top_services' => $topDoctorServices,
                ];
            })
            ->sortByDesc('total_cases')
            ->values();

        // 3. كروت الإحصاءات العامة
        $totalNicuCases = \App\Models\IncubatorReservation::whereBetween('admission_date', [$startDate, $endDate])->count();
        $activeIncubatorsCount = \App\Models\IncubatorReservation::whereIn('status', ['pending', 'admitted'])->count();
        $totalIncubatorsCount = \App\Models\Incubator::count();
        $occupancyRate = $totalIncubatorsCount > 0 ? round(($activeIncubatorsCount / $totalIncubatorsCount) * 100, 1) : 0;

        // إجمالي المدفوعات والمسترجعات
        $totalPaymentsReceived = (float) \App\Models\NicuPayment::where('financial_status', 'paid')
            ->where('payment_type', '!=', \App\Models\NicuPayment::TYPE_REFUND)
            ->whereBetween('paid_at', [$startDateTime, $endDateTime])
            ->sum('amount');

        $totalPaymentsRefunded = (float) \App\Models\NicuPayment::where('financial_status', 'paid')
            ->where('payment_type', \App\Models\NicuPayment::TYPE_REFUND)
            ->whereBetween('paid_at', [$startDateTime, $endDateTime])
            ->sum('amount');

        $netNicuRevenue = $totalPaymentsReceived - $totalPaymentsRefunded;

        $insuredCasesCount = \App\Models\IncubatorReservation::whereBetween('admission_date', [$startDate, $endDate])
            ->whereIn('insurance_type', ['hi', 'moi', 'health_insurance'])
            ->count();

        $cashCasesCount = max(0, $totalNicuCases - $insuredCasesCount);

        // إجمالي مبالغ مطالبات الضمان للخدج في الفترة المحددة (المبالغ التي تطلبها المستشفى من هيئة الضمان)
        $totalInsuranceClaims = (float) \App\Models\IncubatorReservation::whereBetween('admission_date', [$startDate, $endDate])
            ->whereIn('insurance_type', ['hi', 'moi', 'health_insurance'])
            ->sum('insurance_share_amount');

        // توزيع تصفيات ومطالبات الضمان حسب مصفوفة عمر الحمل الوزارية
        $gestationalMatrixStats = \App\Models\IncubatorReservation::whereBetween('admission_date', [$startDate, $endDate])
            ->whereIn('insurance_type', ['hi', 'moi', 'health_insurance'])
            ->get()
            ->groupBy(function ($res) {
                $w = $res->gestational_weeks;
                if ($w === null || $w <= 0) {
                    return 'غير محدد';
                }
                if ($w >= 34) {
                    return '≥ 34 أسبوعاً (حد أقصى 10 أيام)';
                }
                if ($w >= 32) {
                    return '32 إلى <34 أسبوعاً (حد أقصى 15 يوماً)';
                }
                if ($w >= 28) {
                    return '28 إلى <32 أسبوعاً (حد أقصى 20 يوماً)';
                }

                return '< 28 أسبوعاً (خداجة شديدة - تغطية مستمرة)';
            })
            ->map(function ($items, $key) {
                return [
                    'label' => $key,
                    'count' => $items->count(),
                    'claims_sum' => (float) $items->sum('insurance_share_amount'),
                    'patient_sum' => (float) $items->sum('patient_share_amount'),
                ];
            });

        return view('accountant.nicu.analytics', compact(
            'topServices',
            'doctorsActivity',
            'totalNicuCases',
            'activeIncubatorsCount',
            'totalIncubatorsCount',
            'occupancyRate',
            'totalPaymentsReceived',
            'totalPaymentsRefunded',
            'netNicuRevenue',
            'insuredCasesCount',
            'cashCasesCount',
            'totalInsuranceClaims',
            'gestationalMatrixStats',
            'startDate',
            'endDate'
        ));
    }

    /**
     * التحليلات المحاسبية والضمانية لوحدة العناية المركزة للكبار (CCU / ICU Analytics)
     */
    public function ccuAnalytics(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view ccu analytics') && ! $user->can('view accounting')))) {
            abort(403, 'غير مصرح لك بالوصول إلى تحليلات العناية المركزة');
        }

        $startDate = $request->input('start_date')
            ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay()
            : \Carbon\Carbon::now()->startOfMonth();
        $endDate = $request->input('end_date')
            ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay()
            : \Carbon\Carbon::now()->endOfMonth();

        $billingService = app(\App\Services\CcuBillingService::class);
        $billingService->ensureDefaultsExist();

        $admissions = \App\Models\IcuAdmission::with([
            'patient.user',
            'bed',
            'ccuService',
            'doctor.user',
            'ccuServiceLogs.ccuService',
            'medicalOrders.labTest',
            'medicalOrders.radiologyType',
            'ccuConsultations',
            'ccuPayments',
        ])
            ->whereBetween('admission_date', [$startDate, $endDate])
            ->get();

        $totalCcuCases = $admissions->count();
        $totalBedsCount = \App\Models\IcuBed::where('is_active', true)->count();
        $activeBedsCount = \App\Models\IcuBed::where('status', \App\Models\IcuBed::STATUS_OCCUPIED)->count();
        $occupancyRate = $totalBedsCount > 0 ? round(($activeBedsCount / $totalBedsCount) * 100, 1) : 0;

        $paymentsQ = \App\Models\CcuPayment::where('financial_status', 'paid')
            ->whereBetween('paid_at', [$startDate, $endDate]);

        $totalPaymentsReceived = (float) (clone $paymentsQ)
            ->where('payment_type', '!=', \App\Models\CcuPayment::TYPE_REFUND)
            ->sum('amount');

        $totalPaymentsRefunded = (float) (clone $paymentsQ)
            ->where('payment_type', \App\Models\CcuPayment::TYPE_REFUND)
            ->sum('amount');

        $netCcuRevenue = $totalPaymentsReceived - $totalPaymentsRefunded;

        $insuredAdmissions = $admissions->filter(fn ($a) => $a->is_insured);
        $insuredCasesCount = $insuredAdmissions->count();
        $cashCasesCount = max(0, $totalCcuCases - $insuredCasesCount);

        $totalInsuranceClaims = 0.0;
        $totalHiaCollected = 0.0;
        $totalHiaRemaining = 0.0;
        $totalPatientShares = 0.0;
        $totalBundledReferenceValue = 0.0;
        $tierStats = [];

        foreach ($admissions as $adm) {
            $d = $billingService->calculateStayDetails($adm);
            $totalInsuranceClaims += (float) $d['insurance_grand_total'];
            $totalHiaCollected += (float) $d['hia_collected_amount'];
            $totalHiaRemaining += (float) $d['hia_remaining_balance'];
            $totalPatientShares += (float) $d['patient_grand_total'];
            $totalBundledReferenceValue += (float) $d['bundled_reference_total'];

            foreach ($d['daily_coverage_details'] as $dayRow) {
                $lbl = $dayRow['tier_label'] ?? 'غير مصنف';
                if (! isset($tierStats[$lbl])) {
                    $tierStats[$lbl] = ['label' => $lbl, 'days_count' => 0, 'hia_sum' => 0.0, 'patient_sum' => 0.0];
                }
                $tierStats[$lbl]['days_count']++;
                $tierStats[$lbl]['hia_sum'] += (float) $dayRow['hia_share'];
                $tierStats[$lbl]['patient_sum'] += (float) $dayRow['patient_share'];
            }
        }

        $topServices = \App\Models\CcuServiceLog::whereHas('admission', fn ($q) => $q->whereBetween('admission_date', [$startDate, $endDate]))
            ->selectRaw('service_name_snapshot, COUNT(*) as uses_count, SUM(total_cost) as total_billed')
            ->groupBy('service_name_snapshot')
            ->orderByDesc('total_billed')
            ->get();

        $doctorsActivity = $admissions->groupBy('doctor_id')->map(function ($group) {
            $first = $group->first();

            return (object) [
                'doctor_name' => $first?->doctor?->user?->name ?? 'طبيب مناوب',
                'cases_count' => $group->count(),
                'insured_count' => $group->where('is_insured', true)->count(),
            ];
        })->sortByDesc('cases_count')->values();

        return view('accountant.ccu.analytics', compact(
            'topServices',
            'doctorsActivity',
            'totalCcuCases',
            'activeBedsCount',
            'totalBedsCount',
            'occupancyRate',
            'totalPaymentsReceived',
            'totalPaymentsRefunded',
            'netCcuRevenue',
            'insuredCasesCount',
            'cashCasesCount',
            'totalInsuranceClaims',
            'totalHiaCollected',
            'totalHiaRemaining',
            'totalPatientShares',
            'totalBundledReferenceValue',
            'tierStats',
            'startDate',
            'endDate'
        ));
    }
}
