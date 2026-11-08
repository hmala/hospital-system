<?php

namespace App\Http\Controllers;

use App\Models\HealthInsuranceCategory;
use App\Models\HealthInsuranceStayTier;
use App\Models\Room;
use Illuminate\Http\Request;

class HealthInsuranceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
            if (! $isAdmin && (! $user || ! $user->can('manage health insurance'))) {
                abort(403, 'غير مصرح لك بالوصول إلى إدارة نسب استقطاع الضمان الصحي.');
            }

            return $next($request);
        })->except(['getCategoriesJson']);
    }

    public function index()
    {
        $categories = HealthInsuranceCategory::orderBy('sort_order')->orderBy('code')->get();
        $stayTiers = HealthInsuranceStayTier::orderBy('sort_order')->get();
        $standardRoomPrice = HealthInsuranceStayTier::getStandardRoomPrice();

        $roomSamples = [
            'regular' => (float) (Room::where('room_type', 'regular')->value('daily_fee') ?? 250000.0),
            'vip' => (float) (Room::where('room_type', 'vip')->value('daily_fee') ?? 350000.0),
            'vvip' => (float) (Room::where('room_type', 'vvip')->value('daily_fee') ?? 450000.0),
        ];

        return view('health-insurance-categories.index', compact('categories', 'stayTiers', 'standardRoomPrice', 'roomSamples'));
    }

    public function updateStayTier(Request $request, HealthInsuranceStayTier $tier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'day_from' => 'required|integer|min:1',
            'day_to' => 'required|integer|min:'.$request->input('day_from', 1),
            'copay_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $tier->update([
            'name' => $request->input('name'),
            'day_from' => (int) $request->input('day_from'),
            'day_to' => (int) $request->input('day_to'),
            'copay_percentage' => (float) $request->input('copay_percentage'),
            'notes' => $request->input('notes'),
            'is_active' => $request->has('is_active'),
        ]);

        session()->flash('active_tab', 'stay');

        return redirect()->route('health-insurance-categories.index')
            ->with('success', "تم تحديث شريحة الإقامة ({$tier->name}) بنجاح");
    }

    public function updateStandardPrice(Request $request)
    {
        $request->validate([
            'standard_room_daily_price' => 'required|numeric|min:0',
        ]);

        $newPrice = (float) $request->input('standard_room_daily_price');

        HealthInsuranceStayTier::query()->update([
            'standard_room_daily_price' => $newPrice,
        ]);

        session()->flash('active_tab', 'stay');

        return redirect()->route('health-insurance-categories.index')
            ->with('success', 'تم تحديث السعر اليومي المعتمد للغرفة العادية لدى الضمان إلى '.number_format($newPrice, 0).' د.ع');
    }

    public function update(Request $request, HealthInsuranceCategory $category)
    {
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
