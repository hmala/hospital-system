<?php

namespace App\Http\Controllers;

use App\Models\NicuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NicuServiceController extends Controller
{
    /**
     * عرض قائمة خدمات وباقات الخدج وأسعارها اليومية
     */
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بإدارة أسعار وخدمات الخدج');
        }

        $services = NicuService::withCount('reservations')->orderBy('id')->get();
        return view('nicu.services.index', compact('services'));
    }

    /**
     * إضافة باقة خدمة جديدة
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بإضافة خدمة خدج');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:nicu_services,code',
            'daily_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        NicuService::create($validated);

        return redirect()->route('nicu.services.index')->with('success', 'تم إضافة باقة خدمة الخدج الجديدة بنجاح.');
    }

    /**
     * تحديث سعر وبيانات خدمة الخدج
     */
    public function update(Request $request, NicuService $service)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage incubator reservations'))) {
            abort(403, 'غير مصرح لك بتعديل أسعار خدمات الخدج');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'daily_fee' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $service->update($validated);

        return redirect()->route('nicu.services.index')->with('success', 'تم تحديث سعر وبيانات خدمة (' . $service->name . ') بنجاح.');
    }

    /**
     * تبديل حالة تفعيل الخدمة (Active / Inactive)
     */
    public function toggle(NicuService $service)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage incubator reservations'))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك'], 403);
        }

        $service->update([
            'is_active' => !$service->is_active,
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $service->is_active,
            'message' => $service->is_active ? 'تم تفعيل الخدمة بنجاح' : 'تم تعطيل الخدمة بنجاح'
        ]);
    }
}
