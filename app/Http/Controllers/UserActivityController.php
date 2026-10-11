<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserActivityController extends Controller
{
    protected function authorizeActivityAccess(): void
    {
        $user = Auth::user();
        if (!$user) {
            abort(403);
        }

        $isAdmin = $user->hasAnyRole(['admin', 'super_admin', 'hospital_admin', 'مدير النظام']);
        if (!$isAdmin && !$user->can('view activity logs')) {
            abort(403, 'غير مصرح لك باستعراض سجل حركات ونشاط الموظفين والرقابة الإدارية.');
        }
    }

    public function index(Request $request)
    {
        $this->authorizeActivityAccess();

        // 1. حساب بطاقات المؤشرات (KPI Cards)
        $totalToday = UserActivity::today()->count();
        $sensitiveToday = UserActivity::today()->sensitiveOnly()->count();
        
        // المتواجدون الآن (نشاط خلال آخر 5 دقائق)
        $fiveMinutesAgo = now()->subMinutes(5);
        $fifteenMinutesAgo = now()->subMinutes(15);
        $activeNowCount = User::where('last_activity_at', '>=', $fiveMinutesAgo)->count();

        // الموظفون الذين سجلوا دخولاً أو نشاطاً اليوم
        $staffLoggedInTodayCount = User::where(function($q) {
            $q->whereDate('last_login_at', today())
              ->orWhereDate('last_activity_at', today());
        })->count();

        // 2. قائمة المتواجدين حالياً وسجل الدخول اليومي
        $onlineUsers = User::with('roles')
            ->where(function($q) use ($fifteenMinutesAgo) {
                $q->where('last_activity_at', '>=', $fifteenMinutesAgo)
                  ->orWhereDate('last_login_at', today());
            })
            ->orderByRaw('CASE WHEN last_activity_at >= ? THEN 1 ELSE 2 END', [$fiveMinutesAgo])
            ->orderBy('last_activity_at', 'desc')
            ->limit(30)
            ->get();

        // 3. إنتاجية الموظفين اليوم (Staff Productivity Breakdown)
        $staffProductivity = UserActivity::today()
            ->select(
                'user_id',
                'user_name',
                'role_name',
                DB::raw('COUNT(*) as total_actions'),
                DB::raw('SUM(CASE WHEN category = "reception" THEN 1 ELSE 0 END) as reception_count'),
                DB::raw('SUM(CASE WHEN category = "cashier" THEN 1 ELSE 0 END) as cashier_count'),
                DB::raw('SUM(CASE WHEN category = "doctor" THEN 1 ELSE 0 END) as doctor_count'),
                DB::raw('SUM(CASE WHEN category = "lab" THEN 1 ELSE 0 END) as lab_count'),
                DB::raw('SUM(CASE WHEN category = "radiology" THEN 1 ELSE 0 END) as radiology_count'),
                DB::raw('SUM(CASE WHEN category = "treasury" THEN 1 ELSE 0 END) as treasury_count'),
                DB::raw('SUM(CASE WHEN is_sensitive = 1 THEN 1 ELSE 0 END) as sensitive_count')
            )
            ->whereNotNull('user_id')
            ->groupBy('user_id', 'user_name', 'role_name')
            ->orderByDesc('total_actions')
            ->get();

        // 4. استعلام سجل الحركات والتايم لاين مع الفلاتر
        $query = UserActivity::with('user');

        // فلتر البحث النصي
        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%{$s}%")
                  ->orWhere('user_name', 'like', "%{$s}%")
                  ->orWhere('role_name', 'like', "%{$s}%")
                  ->orWhere('ip_address', 'like', "%{$s}%");
            });
        }

        // فلتر المستخدم
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        // فلتر القسم / التصنيف
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // فلتر الحركات الحساسة فقط
        if ($request->boolean('sensitive_only')) {
            $query->sensitiveOnly();
        }

        // فلتر التاريخ
        $dateFilter = $request->get('date_filter', 'today');
        if ($dateFilter === 'today') {
            $query->today();
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('created_at', today()->subDay());
        } elseif ($dateFilter === 'this_week') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'custom') {
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }
        }

        $activities = $query->latest('created_at')->paginate(25)->withQueryString();

        // قوائم الفلترة
        $usersList = User::select('id', 'name')->orderBy('name')->get();
        $categoriesList = [
            'all' => 'كافة الأقسام والتصنيفات',
            'reception' => 'الاستقبال والحجوزات',
            'cashier' => 'الصندوق والكاشير',
            'doctor' => 'العيادات والأطباء',
            'lab' => 'المختبر والتحاليل',
            'radiology' => 'الأشعة والتصوير',
            'treasury' => 'الخزينة والمصروفات',
            'surgery' => 'العمليات والرقود',
            'emergency' => 'الطوارئ والإسعاف',
            'pharmacy' => 'الصيدلية والعلاج',
            'auth' => 'المصادقة والأمان',
            'security' => 'الرقابة والعمليات الحساسة',
        ];

        return view('activity-logs.index', compact(
            'activities',
            'totalToday',
            'activeNowCount',
            'sensitiveToday',
            'staffLoggedInTodayCount',
            'onlineUsers',
            'staffProductivity',
            'usersList',
            'categoriesList'
        ));
    }

    /**
     * جلب أحدث الحركات عبر AJAX لتحديث الشريط المباشر
     */
    public function feedAjax(Request $request)
    {
        $this->authorizeActivityAccess();

        $lastId = $request->get('last_id', 0);
        $newActivities = UserActivity::where('id', '>', $lastId)
            ->latest('id')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $newActivities->count(),
            'activities' => $newActivities->map(function ($act) {
                return [
                    'id' => $act->id,
                    'user_name' => $act->user_name,
                    'role_name' => $act->role_name,
                    'action' => $act->action,
                    'category' => $act->category,
                    'category_label' => $act->category_label,
                    'category_color' => $act->category_color,
                    'category_icon' => $act->category_icon,
                    'description' => $act->description,
                    'is_sensitive' => $act->is_sensitive,
                    'time_formatted' => $act->created_at->format('h:i:s A'),
                    'time_ago' => $act->created_at->diffForHumans(),
                    'ip_address' => $act->ip_address,
                ];
            }),
        ]);
    }

    /**
     * تنظيف وأرشفة السجلات القديمة (أكثر من 90 يوم)
     */
    public function purgeOld(Request $request)
    {
        $user = Auth::user();
        if (!$user || (!$user->hasRole('admin') && !$user->can('delete activity logs'))) {
            abort(403, 'غير مصرح لك بحذف وتنظيف سجل الحركات.');
        }

        $days = (int)$request->get('days', 90);
        if ($days < 30) {
            $days = 30; // الأمان: منع حذف حركات أقل من شهر
        }

        $cutoff = now()->subDays($days);
        $deletedCount = UserActivity::where('created_at', '<', $cutoff)->delete();

        \App\Services\UserActivityLogger::log(
            action: 'purge_logs',
            category: 'security',
            description: "تنظيف وأرشفة {$deletedCount} حركة من سجل النشاطات الأقدم من {$days} يوماً",
            isSensitive: true
        );

        return redirect()->route('activity-logs.index')
            ->with('success', "تم تنظيف وأرشفة {$deletedCount} حركة أقدم من {$days} يوماً بنجاح.");
    }

    /**
     * تصدير الحركات إلى كشف CSV
     */
    public function export(Request $request)
    {
        $this->authorizeActivityAccess();

        $query = UserActivity::latest('created_at');

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->boolean('sensitive_only')) {
            $query->sensitiveOnly();
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $items = $query->limit(5000)->get();

        $filename = 'activity_logs_' . now()->format('Y_m_d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($items) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Arabic support
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['#', 'التاريخ والوقت', 'المستخدم', 'الدور / الصفة', 'القسم / التصنيف', 'تفاصيل الحركة', 'عملية حساسة', 'عنوان IP']);

            foreach ($items as $item) {
                fputcsv($handle, [
                    $item->id,
                    $item->created_at->format('Y-m-d H:i:s'),
                    $item->user_name,
                    $item->role_name,
                    $item->category_label,
                    $item->description,
                    $item->is_sensitive ? 'نعم (حساسة)' : 'لا',
                    $item->ip_address,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
