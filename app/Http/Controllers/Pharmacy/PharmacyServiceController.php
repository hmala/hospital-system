<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\PharmacyService;
use Illuminate\Http\Request;

class PharmacyServiceController extends Controller
{
    /**
     * عرض قائمة الخدمات الصيدلانية غير المخزنية
     */
    public function index()
    {
        $services = PharmacyService::orderBy('name')->get();
        return view('pharmacy.services.index', compact('services'));
    }

    /**
     * إضافة خدمة صيدلانية جديدة
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:pharmacy_services,name',
            'price' => 'required|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = true;
        PharmacyService::create($validated);

        return redirect()->route('pharmacy.services.index')
            ->with('success', 'تمت إضافة الخدمة الصيدلانية بنجاح.');
    }

    /**
     * تحديث بيانات الخدمة
     */
    public function update(Request $request, PharmacyService $service)
    {
        $validated = $request->validate([
            'name' => "required|string|max:255|unique:pharmacy_services,name,{$service->id}",
            'price' => 'required|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $service->update($validated);

        return redirect()->route('pharmacy.services.index')
            ->with('success', 'تم تحديث الخدمة الصيدلانية بنجاح.');
    }

    /**
     * تفعيل أو تعطيل الخدمة
     */
    public function toggleStatus(PharmacyService $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        $statusText = $service->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()->route('pharmacy.services.index')
            ->with('success', "تم {$statusText} الخدمة «{$service->name}» بنجاح.");
    }

    /**
     * حذف خدمة
     */
    public function destroy(PharmacyService $service)
    {
        $name = $service->name;
        $service->delete();

        return redirect()->route('pharmacy.services.index')
            ->with('success', "تم حذف الخدمة «{$name}» بنجاح.");
    }
}
