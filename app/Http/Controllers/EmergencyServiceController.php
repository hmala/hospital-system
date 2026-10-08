<?php

namespace App\Http\Controllers;

use App\Models\EmergencyService;
use Illuminate\Http\Request;

class EmergencyServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $user = auth()->user();
            if (!$user) {
                abort(403);
            }

            $hasPerm = false;
            try {
                $hasPerm = $user->can('manage emergency services');
            } catch (\Throwable $e) {
                $hasPerm = false;
            }

            if (!$hasPerm) {
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

        if ($request->filled('insurance_filter')) {
            if ($request->insurance_filter === 'hi_active') {
                $query->where('is_hi_active', true);
            } elseif ($request->insurance_filter === 'hi_inactive') {
                $query->where(function($q) {
                    $q->where('is_hi_active', false)->orWhereNull('is_hi_active');
                });
            } elseif ($request->insurance_filter === 'moi_active') {
                $query->where('is_moi_active', true);
            } elseif ($request->insurance_filter === 'moi_inactive') {
                $query->where(function($q) {
                    $q->where('is_moi_active', false)->orWhereNull('is_moi_active');
                });
            }
        }

        $services = $query->orderBy('name', 'asc')->paginate(30)->withQueryString();
        
        $categories = EmergencyService::select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        $stats = [
            'total' => EmergencyService::count(),
            'active' => EmergencyService::where('is_active', true)->count(),
            'inactive' => EmergencyService::where('is_active', false)->count(),
            'hi_active' => EmergencyService::where('is_hi_active', true)->count(),
            'hi_inactive' => EmergencyService::where(function($q) {
                $q->where('is_hi_active', false)->orWhereNull('is_hi_active');
            })->count(),
        ];

        return view('emergency-services.index', compact('services', 'categories', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_moi_active'] = $request->has('is_moi_active');
        $validated['is_hi_active'] = $request->has('is_hi_active');

        EmergencyService::create($validated);

        return redirect()->route('emergency-services.index')
            ->with('success', 'تمت إضافة خدمة الطوارئ بنجاح');
    }

    public function update(Request $request, EmergencyService $emergencyService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'category' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_moi_active'] = $request->has('is_moi_active');
        $validated['is_hi_active'] = $request->has('is_hi_active');

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

    /**
     * تبديل فوري لأي خاصية (AJAX Quick Toggle)
     */
    public function quickToggle(Request $request, EmergencyService $emergencyService)
    {
        $field = $request->input('field');
        if (!in_array($field, ['is_hi_active', 'is_moi_active', 'is_active'])) {
            return response()->json(['success' => false, 'message' => 'حقل غير صالح'], 422);
        }

        $newValue = $request->has('value') 
            ? filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN)
            : !$emergencyService->{$field};

        $emergencyService->update([$field => $newValue]);

        $fieldLabels = [
            'is_hi_active' => 'الضمان الصحي (HI)',
            'is_moi_active' => 'ضمان الداخلية (MOI)',
            'is_active' => 'حالة الخدمة',
        ];

        $actionText = $newValue ? 'تفعيل' : 'تعطيل';
        $label = $fieldLabels[$field] ?? $field;

        return response()->json([
            'success' => true,
            'message' => "تم {$actionText} {$label} لخدمة ({$emergencyService->name}) بنجاح",
            'new_state' => $newValue,
        ]);
    }

    /**
     * تنفيذ إجراء جماعي على الخدمات المحددة (Bulk Actions)
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $serviceIds = $request->input('service_ids', []);

        if (empty($serviceIds) || !is_array($serviceIds)) {
            return redirect()->back()->with('error', 'يرجى تحديد خدمة واحدة على الأقل لتنفيذ الإجراء الجماعي.');
        }

        $count = count($serviceIds);

        switch ($action) {
            case 'hi_enable':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_hi_active' => true]);
                $msg = "تم تفعيل التغطية بالضمان الصحي لـ ({$count}) خدمة بنجاح 🛡️";
                break;

            case 'hi_disable':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_hi_active' => false]);
                $msg = "تم استبعاد ({$count}) خدمة من الضمان الصحي (لتصبح كاش عادي) 🚫";
                break;

            case 'moi_enable':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_moi_active' => true]);
                $msg = "تم تفعيل ضمان الداخلية لـ ({$count}) خدمة بنجاح 👮";
                break;

            case 'moi_disable':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_moi_active' => false]);
                $msg = "تم استبعاد ({$count}) خدمة من ضمان الداخلية 🚫";
                break;

            case 'activate':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_active' => true]);
                $msg = "تم تفعيل ({$count}) خدمة بنجاح ✅";
                break;

            case 'deactivate':
                EmergencyService::whereIn('id', $serviceIds)->update(['is_active' => false]);
                $msg = "تم تعطيل ({$count}) خدمة بنجاح ⏸️";
                break;

            case 'delete':
                $usedCount = EmergencyService::whereIn('id', $serviceIds)
                    ->whereHas('emergencies')
                    ->count();

                $deleted = EmergencyService::whereIn('id', $serviceIds)
                    ->whereDoesntHave('emergencies')
                    ->delete();

                if ($usedCount > 0) {
                    $msg = "تم حذف ({$deleted}) خدمة، وتخطي ({$usedCount}) خدمة لأنها مرتبطة بسجلات سابقة.";
                } else {
                    $msg = "تم حذف ({$deleted}) خدمة بنجاح 🗑️";
                }
                break;

            default:
                return redirect()->back()->with('error', 'إجراء جماعي غير معروف.');
        }

        return redirect()->back()->with('success', $msg);
    }
}
