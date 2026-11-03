<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgicalOperation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NicuDeliveryRadarController extends Controller
{
    /**
     * عرض رادار صالات العمليات والولادة المخصص لقسم الخدج
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('view nicu delivery radar') && ! $user->hasRole(['nicu_doctor', 'nicu_nurse'])))) {
            abort(403, 'غير مصرح لك بالوصول إلى رادار الولادات والعمليات لقسم الخدج');
        }

        $selectedDate = $request->query('date', today()->format('Y-m-d'));
        try {
            $parsedDate = Carbon::createFromFormat('Y-m-d', $selectedDate);
        } catch (\Exception $e) {
            $parsedDate = today();
            $selectedDate = $parsedDate->format('Y-m-d');
        }

        // جلب العمليات الجراحية المعتمدة للظهور في رادار الخدج
        $radarOperations = SurgicalOperation::where('show_in_nicu_radar', true)->get(['id', 'name']);
        $radarOperationIds = $radarOperations->pluck('id')->toArray();
        $radarOperationNames = $radarOperations->pluck('name')->toArray();

        // إغلاق شرط الفلترة للعمليات التابعة للرادار
        $radarFilter = function ($q) use ($radarOperationIds, $radarOperationNames) {
            $q->where(function ($sub) use ($radarOperationIds, $radarOperationNames) {
                if (! empty($radarOperationIds)) {
                    $sub->whereIn('surgical_operation_id', $radarOperationIds);
                }
                if (! empty($radarOperationNames)) {
                    $sub->orWhereIn('surgery_type', $radarOperationNames);
                }
                if (! empty($radarOperationIds)) {
                    $sub->orWhereHas('additionalOperations', function ($add) use ($radarOperationIds) {
                        $add->whereIn('surgical_operation_id', $radarOperationIds);
                    });
                }
            });
        };

        // إعداد شريط أيام الأسبوع السبعة (من السبت إلى الجمعة)
        $startOfWeek = $parsedDate->copy()->startOfWeek(Carbon::SATURDAY);
        $weekDays = [];
        $arabicDayNames = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];

        for ($i = 0; $i < 7; $i++) {
            $d = $startOfWeek->copy()->addDays($i);
            $dayKey = $d->format('Y-m-d');
            $count = Surgery::whereDate('scheduled_date', $dayKey)
                ->where('status', '!=', 'cancelled')
                ->where($radarFilter)
                ->count();

            $englishDay = $d->format('l');
            $arabicDay = $arabicDayNames[$englishDay] ?? $englishDay;

            $weekDays[] = [
                'date' => $dayKey,
                'day_name' => $arabicDay,
                'formatted_date' => $d->format('d/m'),
                'is_selected' => $dayKey === $selectedDate,
                'is_today' => $d->isToday(),
                'count' => $count,
            ];
        }

        // جلب عمليات اليوم المحدد
        $surgeries = Surgery::with([
            'patient.user',
            'doctor.user',
            'anesthesiologist.user',
            'department',
            'room',
            'operationTheater',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
        ])
            ->whereDate('scheduled_date', $selectedDate)
            ->where('status', '!=', 'cancelled')
            ->where($radarFilter)
            ->orderBy('scheduled_time', 'asc')
            ->get();

        // إذا كان العرض لليوم الحالي، جلب العمليات النشطة الجارية حالياً حتى وإن كان تاريخ جدولتها مختلفاً
        if ($parsedDate->isToday()) {
            $extraActive = Surgery::with([
                'patient.user',
                'doctor.user',
                'anesthesiologist.user',
                'department',
                'room',
                'operationTheater',
                'surgicalOperation',
                'additionalOperations.surgicalOperation',
            ])
                ->whereIn('status', ['waiting', 'in_progress'])
                ->whereDate('scheduled_date', '!=', $selectedDate)
                ->where($radarFilter)
                ->get();

            $surgeries = $surgeries->merge($extraActive)->unique('id')->values();
        }

        // ترتيب العمليات حسب الأولوية التشغيلية (الجارية أولاً، ثم بالانتظار، ثم المجدولة، ثم المكتملة)
        $statusPriority = [
            'in_progress' => 1,
            'waiting' => 2,
            'scheduled' => 3,
            'completed' => 4,
            'checked_in' => 5,
        ];

        $surgeries = $surgeries->sortBy([
            fn ($a, $b) => ($statusPriority[$a->status] ?? 99) <=> ($statusPriority[$b->status] ?? 99),
            ['scheduled_time', 'asc'],
        ])->values();

        // إحصائيات سريعة للرادار
        $stats = [
            'total' => $surgeries->count(),
            'scheduled' => $surgeries->where('status', 'scheduled')->count(),
            'waiting' => $surgeries->where('status', 'waiting')->count(),
            'in_progress' => $surgeries->where('status', 'in_progress')->count(),
            'completed' => $surgeries->where('status', 'completed')->count(),
        ];

        $canManageSettings = $isAdmin || ($user && ($user->can('manage nicu delivery radar settings') || $user->hasRole('nicu_doctor')));

        return view('nicu.delivery-radar', compact(
            'surgeries',
            'weekDays',
            'selectedDate',
            'parsedDate',
            'stats',
            'radarOperationNames',
            'canManageSettings'
        ));
    }

    /**
     * شاشة التحكم الديناميكية بالعمليات المشمولة برادار الخدج
     */
    public function settings(Request $request): View
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('manage nicu delivery radar settings') && ! $user->hasRole('nicu_doctor')))) {
            abort(403, 'غير مصرح لك بإدارة إعدادات رادار الخدج');
        }

        $operations = SurgicalOperation::orderBy('category')
            ->orderBy('name')
            ->get();

        $operationsByCategory = $operations->groupBy('category');
        $radarOperationsCount = $operations->where('show_in_nicu_radar', true)->count();

        return view('nicu.delivery-radar-settings', compact(
            'operations',
            'operationsByCategory',
            'radarOperationsCount'
        ));
    }

    /**
     * تبديل فوري لتفعيل/إلغاء تضمين العملية في رادار الخدج عبر AJAX
     */
    public function toggleOperation(Request $request, SurgicalOperation $surgicalOperation): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('manage nicu delivery radar settings') && ! $user->hasRole('nicu_doctor')))) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل إعدادات الرادار',
            ], 403);
        }

        $surgicalOperation->show_in_nicu_radar = ! $surgicalOperation->show_in_nicu_radar;
        $surgicalOperation->save();

        return response()->json([
            'success' => true,
            'show_in_nicu_radar' => (bool) $surgicalOperation->show_in_nicu_radar,
            'operation_id' => $surgicalOperation->id,
            'operation_name' => $surgicalOperation->name,
            'message' => $surgicalOperation->show_in_nicu_radar
                ? "تم تضمين العملية '{$surgicalOperation->name}' في رادار الخدج بنجاح."
                : "تم استبعاد العملية '{$surgicalOperation->name}' من رادار الخدج.",
        ]);
    }
}
