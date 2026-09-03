<?php

namespace App\Http\Controllers;

use App\Models\EmergencyService;
use Illuminate\Http\Request;

class EmergencyServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user() || (!auth()->user()->hasRole(['admin', 'accountant']) && !auth()->user()->can('manage emergency services'))) {
                abort(403, 'غير مصرح لك بالوصول إلى إدارة خدمات وأسعار الطوارئ.');
            }
            return $next($request);
        });
    }

    public function index(Request $request)
    {
        $query = EmergencyService::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $services = $query->orderBy('name', 'asc')->paginate(20)->withQueryString();
        
        $categories = EmergencyService::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $stats = [
            'total' => EmergencyService::count(),
            'active' => EmergencyService::where('is_active', true)->count(),
            'inactive' => EmergencyService::where('is_active', false)->count(),
        ];

        return view('emergency-services.index', compact('services', 'categories', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        EmergencyService::create($validated);

        return redirect()->route('emergency-services.index')
            ->with('success', 'تمت إضافة خدمة الطوارئ بنجاح');
    }

    public function update(Request $request, EmergencyService $emergencyService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $emergencyService->update($validated);

        return redirect()->route('emergency-services.index')
            ->with('success', 'تم تحديث خدمة الطوارئ بنجاح');
    }

    public function destroy(EmergencyService $emergencyService)
    {
        if ($emergencyService->emergencies()->exists()) {
            return redirect()->route('emergency-services.index')
                ->with('error', 'لا يمكن حذف هذه الخدمة لأنها مرتبطة بسجلات طوارئ سابقة. يمكنك تعطيلها بدلاً من ذلك.');
        }

        $emergencyService->delete();

        return redirect()->route('emergency-services.index')
            ->with('success', 'تم حذف خدمة الطوارئ بنجاح');
    }

    public function toggleStatus(EmergencyService $emergencyService)
    {
        $emergencyService->update([
            'is_active' => !$emergencyService->is_active
        ]);

        $statusText = $emergencyService->is_active ? 'تفعيل' : 'تعطيل';
        return redirect()->route('emergency-services.index')
            ->with('success', "تم {$statusText} الخدمة بنجاح");
    }
}
