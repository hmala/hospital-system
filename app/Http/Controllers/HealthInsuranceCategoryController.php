<?php

namespace App\Http\Controllers;

use App\Models\HealthInsuranceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HealthInsuranceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage health insurance'))) {
            abort(403, 'غير مصرح لك بالوصول إلى إدارة نسب استقطاع الضمان الصحي.');
        }

        $categories = HealthInsuranceCategory::orderBy('sort_order')->orderBy('code')->get();
        return view('health-insurance-categories.index', compact('categories'));
    }

    public function update(Request $request, HealthInsuranceCategory $category)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('manage health insurance'))) {
            abort(403, 'غير مصرح لك بتعديل نسب استقطاع الضمان الصحي.');
        }
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'requires_thermal_stamp' => 'nullable|boolean',
            'consultation_copay' => 'required|numeric|min:0|max:100',
            'surgery_copay' => 'required|numeric|min:0|max:100',
            'lab_copay' => 'required|numeric|min:0|max:100',
            'radiology_copay' => 'required|numeric|min:0|max:100',
            'support_services_copay' => 'required|numeric|min:0|max:100',
            'medication_copay' => 'required|numeric|min:0|max:100',
            'emergency_copay' => 'required|numeric|min:0|max:100',
            'dental_copay' => 'required|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data = $request->all();
        $data['requires_thermal_stamp'] = $request->has('requires_thermal_stamp');
        $data['is_active'] = $request->has('is_active');

        $category->update($data);

        return redirect()->route('health-insurance-categories.index')
            ->with('success', "تم تحديث نسب الاستقطاع لـ ({$category->name}) بنجاح");
    }

    public function getCategoriesJson()
    {
        $categories = HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return response()->json($categories);
    }
}
