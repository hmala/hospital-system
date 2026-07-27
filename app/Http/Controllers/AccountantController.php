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
            if (!$user || (!$user->hasRole('admin') && !$user->can('review surgery prices'))) {
                abort(403, 'غير مصرح لك بالوصول إلى صفحات مراجعة الحسابات.');
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
            'medicalDevices'
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
            'medicalDevices'
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
        if (!empty($validated['additional_ops'])) {
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
        if (!empty($validated['device_prices'])) {
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
            ->whereBetween('emergencies.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('emergency_services.id', 'emergency_services.name')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // 2. تحليل نشاط أطباء الطوارئ والخدمات المقدمة بواسطتهم
        $doctorsActivity = \App\Models\Doctor::with('user')
            ->whereHas('emergencies', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->get()
            ->map(function($doctor) use ($startDate, $endDate) {
                $emergencies = \App\Models\Emergency::where('doctor_id', $doctor->id)
                    ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
                    ->whereBetween('emergencies.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
        $totalEmergencyCases = \App\Models\Emergency::whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])->count();
        $totalServicesProvided = \DB::table('emergency_emergency_service')
            ->join('emergencies', 'emergencies.id', '=', 'emergency_emergency_service.emergency_id')
            ->whereBetween('emergencies.created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
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
        $categoryFilter = $request->input('category', 'all'); // all, lab, radiology, echo, ct_scan, mri

        // 1. طلبات الفحوصات من الجدول العام Request والـ RadiologyRequest
        $labRequestsQuery = \App\Models\Request::where('type', 'lab')
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $radiologyRequestsQuery = \App\Models\RadiologyRequest::with('radiologyType')
            ->whereBetween('requested_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        $totalLabCount = $labRequestsQuery->count();
        $totalRadCount = $radiologyRequestsQuery->count();

        // 2. الفحوصات الأكثر إيراداً وطلباً بالأشعة (مفراس، سونار/إيكو، رنين، أشعة عادية)
        $topRadiologyTypes = \DB::table('radiology_requests')
            ->join('radiology_types', 'radiology_types.id', '=', 'radiology_requests.radiology_type_id')
            ->select(
                'radiology_types.name',
                'radiology_types.main_category',
                \DB::raw('COUNT(radiology_requests.id) as usage_count'),
                \DB::raw('SUM(COALESCE(radiology_requests.total_cost, radiology_types.base_price)) as total_revenue')
            )
            ->whereBetween('radiology_requests.requested_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('radiology_types.id', 'radiology_types.name', 'radiology_types.main_category')
            ->orderByDesc('usage_count')
            ->limit(10)
            ->get();

        // 3. كشف توزيع الإيرادات حسب الوحدة (المختبر، السونار والإيكو، المفراس، الرنين، الأشعة العادية)
        $categoryBreakdown = \DB::table('radiology_requests')
            ->join('radiology_types', 'radiology_types.id', '=', 'radiology_requests.radiology_type_id')
            ->select(
                'radiology_types.main_category',
                \DB::raw('COUNT(radiology_requests.id) as total_tests'),
                \DB::raw('SUM(COALESCE(radiology_requests.total_cost, radiology_types.base_price)) as total_amount')
            )
            ->whereBetween('radiology_requests.requested_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->groupBy('radiology_types.main_category')
            ->get();

        // 4. أداء وحركة الأطباء المسجلين/الفنيين للأشعة والمختبر
        $doctorsPerformance = \App\Models\Doctor::with('user')
            ->whereHas('radiologyRequests', function($q) use ($startDate, $endDate) {
                $q->whereBetween('requested_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->get()
            ->map(function($doctor) use ($startDate, $endDate) {
                $radReqs = \App\Models\RadiologyRequest::where('doctor_id', $doctor->id)
                    ->whereBetween('requested_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->get();

                return [
                    'doctor_name' => $doctor->user->name ?? 'طبيب غير معروف',
                    'total_requests' => $radReqs->count(),
                    'total_value' => $radReqs->sum('total_cost'),
                ];
            })
            ->sortByDesc('total_requests')
            ->values();

        return view('accountant.diagnostics.analytics', compact(
            'totalLabCount',
            'totalRadCount',
            'topRadiologyTypes',
            'categoryBreakdown',
            'doctorsPerformance',
            'startDate',
            'endDate',
            'categoryFilter'
        ));
    }
}
