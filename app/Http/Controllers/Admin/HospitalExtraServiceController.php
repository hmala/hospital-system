<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HospitalExtraService;
use App\Services\UserActivityLogger;
use Illuminate\Http\Request;

class HospitalExtraServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'مدير النظام']);
            $hasPerm = $user && (
                $user->can('manage settings')
                || $user->can('manage users')
                || $user->can('manage roles')
                || $user->can('manage nicu services')
                || $user->can('process surgery payments')
            );
            if (! $isAdmin && ! $hasPerm) {
                abort(403, 'غير مصرح لك بإدارة الخدمات المتفرقة للمستشفى.');
            }

            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $department = $request->input('department', 'all_depts');
        $category = $request->input('category', 'all');
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('search', ''));

        $query = HospitalExtraService::query();

        if ($department !== 'all_depts' && array_key_exists($department, HospitalExtraService::DEPARTMENTS)) {
            $query->where('department', $department);
        }

        if ($category !== 'all' && array_key_exists($category, HospitalExtraService::CATEGORIES)) {
            $query->where('category', $category);
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $services = $query->orderBy('department')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $stats = [
            'total' => HospitalExtraService::count(),
            'active' => HospitalExtraService::where('is_active', true)->count(),
            'general' => HospitalExtraService::where('department', 'general')->count(),
            'nicu' => HospitalExtraService::where('department', 'nicu')->count(),
            'shared' => HospitalExtraService::where('department', 'all')->count(),
        ];

        $departments = HospitalExtraService::DEPARTMENTS;
        $categories = HospitalExtraService::CATEGORIES;

        return view('admin.extra-services.index', compact(
            'services',
            'stats',
            'departments',
            'categories',
            'department',
            'category',
            'status',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'department' => 'required|in:general,nicu,all',
            'category' => 'required|in:'.implode(',', array_keys(HospitalExtraService::CATEGORIES)),
            'default_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $service = HospitalExtraService::create([
            'name' => trim($validated['name']),
            'code' => $validated['code'] ? trim($validated['code']) : null,
            'department' => $validated['department'],
            'category' => $validated['category'],
            'default_price' => (float) $validated['default_price'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        UserActivityLogger::log(
            action: 'extra_service_catalog_created',
            category: 'settings',
            description: "إضافة خدمة متفرقة جديدة: {$service->name} ({$service->department_label}) بسعر ".number_format($service->default_price, 0).' د.ع',
            subject: $service,
            isSensitive: false
        );

        return redirect()->route('admin.extra-services.index', ['department' => $request->input('redirect_department', 'all_depts')])
            ->with('success', 'تمت إضافة الخدمة المتفرقة "'.$service->name.'" بنجاح.');
    }

    public function update(Request $request, HospitalExtraService $extraService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'department' => 'required|in:general,nicu,all',
            'category' => 'required|in:'.implode(',', array_keys(HospitalExtraService::CATEGORIES)),
            'default_price' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $extraService->update([
            'name' => trim($validated['name']),
            'code' => $validated['code'] ? trim($validated['code']) : null,
            'department' => $validated['department'],
            'category' => $validated['category'],
            'default_price' => (float) $validated['default_price'],
            'is_active' => $request->boolean('is_active', true),
        ]);

        UserActivityLogger::log(
            action: 'extra_service_catalog_updated',
            category: 'settings',
            description: "تعديل الخدمة المتفرقة: {$extraService->name} بسعر ".number_format($extraService->default_price, 0).' د.ع',
            subject: $extraService,
            isSensitive: false
        );

        return redirect()->back()
            ->with('success', 'تم تحديث الخدمة المتفرقة "'.$extraService->name.'" بنجاح.');
    }

    public function updateQuickPrice(Request $request, HospitalExtraService $extraService)
    {
        $validated = $request->validate([
            'default_price' => 'required|numeric|min:0',
        ]);

        $price = (float) $validated['default_price'];
        $extraService->update(['default_price' => $price]);

        UserActivityLogger::log(
            action: 'extra_service_catalog_price_updated',
            category: 'settings',
            description: "تحديث سريع لسعر الخدمة المتفرقة ({$extraService->name}) إلى ".number_format($price, 0).' د.ع',
            subject: $extraService,
            isSensitive: false
        );

        return response()->json([
            'success' => true,
            'default_price' => $price,
            'formatted_price' => number_format($price, 0).' د.ع',
        ]);
    }

    public function toggle(HospitalExtraService $extraService)
    {
        $extraService->update(['is_active' => ! $extraService->is_active]);

        UserActivityLogger::log(
            action: 'extra_service_catalog_toggled',
            category: 'settings',
            description: ($extraService->is_active ? 'تفعيل' : 'تعطيل')." الخدمة المتفرقة: {$extraService->name}",
            subject: $extraService,
            isSensitive: false
        );

        return redirect()->back()
            ->with('success', 'تم '.($extraService->is_active ? 'تفعيل' : 'تعطيل').' خدمة "'.$extraService->name.'" بنجاح.');
    }

    public function destroy(HospitalExtraService $extraService)
    {
        $name = $extraService->name;
        $extraService->delete();

        UserActivityLogger::log(
            action: 'extra_service_catalog_deleted',
            category: 'settings',
            description: "حذف الخدمة المتفرقة من الدليل: {$name}",
            subject: null,
            isSensitive: false
        );

        return redirect()->back()
            ->with('success', 'تم حذف الخدمة المتفرقة "'.$name.'" من الدليل بنجاح.');
    }
}
