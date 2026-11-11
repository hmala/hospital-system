<?php

namespace App\Http\Controllers;

use App\Models\CcuService;
use App\Models\CcuSetting;
use App\Models\CcuStayBracket;
use Database\Seeders\CcuTariffSeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CcuServiceController extends Controller
{
    protected function authorizeManage(): void
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('manage ccu services') && ! $user->can('manage icu beds')))) {
            abort(403, 'غير مصرح لك بإدارة أسعار وباقات وشرائح العناية المركزة الداينميكية');
        }
    }

    /**
     * لوحة التحكم الداينمك للأسعار والشرائح والضوابط (العناية المركزة كبار)
     */
    public function index()
    {
        $this->authorizeManage();

        if (CcuSetting::count() === 0 || CcuStayBracket::count() === 0) {
            CcuTariffSeeder::seedDefaults();
        }

        $packages = CcuService::withCount('admissions')
            ->where('service_type', CcuService::TYPE_PACKAGE)
            ->orderBy('id')
            ->get();

        $addons = CcuService::where('service_type', CcuService::TYPE_ADDON)
            ->orderBy('id')
            ->get();

        $brackets = CcuStayBracket::orderBy('sort_order')->orderBy('min_day')->get();
        $settings = CcuSetting::orderBy('id')->get();

        return view('ccu.services.index', compact('packages', 'addons', 'brackets', 'settings'));
    }

    /**
     * إضافة باقة أو خدمة عناية مركزة جديدة (كاش أو ضمان)
     */
    public function store(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:ccu_services,code',
            'service_type' => 'required|in:package,addon',
            'daily_fee' => 'required|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'official_claim_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'nullable',
            'is_hi_active' => 'nullable',
            'include_bed_fee' => 'nullable',
            'is_bundled_lab' => 'nullable',
            'is_bundled_radiology' => 'nullable',
            'is_bundled_medications' => 'nullable',
        ]);

        $validated['hi_price'] = $validated['hi_price'] ?? 0;
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['is_hi_active'] = $request->boolean('is_hi_active', false);
        $validated['include_bed_fee'] = $request->boolean('include_bed_fee', $validated['service_type'] === CcuService::TYPE_PACKAGE);
        $validated['is_bundled_lab'] = $request->boolean('is_bundled_lab', false);
        $validated['is_bundled_radiology'] = $request->boolean('is_bundled_radiology', false);
        $validated['is_bundled_medications'] = $request->boolean('is_bundled_medications', false);

        CcuService::create($validated);

        return redirect()->route('ccu.services.index')->with('success', 'تم إضافة الخدمة / الباقة الجديدة إلى تسعيرة العناية المركزة بنجاح.');
    }

    /**
     * تحديث تسعيرة وخصائص باقة أو خدمة العناية المركزة
     */
    public function update(Request $request, CcuService $service)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'service_type' => 'nullable|in:package,addon',
            'daily_fee' => 'required|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'official_claim_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $validated['hi_price'] = $validated['hi_price'] ?? 0;
        $validated['is_active'] = $request->has('is_active');
        $validated['is_hi_active'] = $request->has('is_hi_active');
        $validated['include_bed_fee'] = $request->has('include_bed_fee');
        $validated['is_bundled_lab'] = $request->has('is_bundled_lab');
        $validated['is_bundled_radiology'] = $request->has('is_bundled_radiology');
        $validated['is_bundled_medications'] = $request->has('is_bundled_medications');

        $service->update($validated);

        return redirect()->route('ccu.services.index')->with('success', "تم تحديث تسعيرة وإعدادات ({$service->name}) بنجاح.");
    }

    /**
     * تبديل حالة تفعيل الخدمة الفوري
     */
    public function toggle(CcuService $service)
    {
        $this->authorizeManage();

        $service->update(['is_active' => ! $service->is_active]);

        return response()->json([
            'success' => true,
            'is_active' => $service->is_active,
            'message' => $service->is_active ? 'تم تفعيل الخدمة بنجاح' : 'تم تعطيل الخدمة بنجاح',
        ]);
    }

    /**
     * إضافة شريحة تغطية أيام رقود جديدة
     */
    public function storeBracket(Request $request)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'policy_category' => 'nullable|string|max:20',
            'min_day' => 'required|integer|min:1',
            'max_day' => 'nullable|integer|gte:min_day',
            'hia_percent' => 'required|numeric|min:0|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $hia = (float) $validated['hia_percent'];
        CcuStayBracket::create([
            'name' => $validated['name'],
            'policy_category' => strtoupper(trim($validated['policy_category'] ?: 'ALL')),
            'min_day' => $validated['min_day'],
            'max_day' => $validated['max_day'] ?? null,
            'hia_percent' => $hia,
            'patient_percent' => round(100.0 - $hia, 2),
            'sort_order' => $validated['sort_order'] ?? (CcuStayBracket::count() + 1),
            'is_active' => true,
        ]);

        return redirect()->route('ccu.services.index')->with('active_tab', 'brackets-pane')->with('success', 'تم إضافة شريحة الأيام الجديدة بنجاح.');
    }

    /**
     * تعديل شريحة أيام ونسب تغطية
     */
    public function updateBracket(Request $request, CcuStayBracket $bracket)
    {
        $this->authorizeManage();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'policy_category' => 'nullable|string|max:20',
            'min_day' => 'required|integer|min:1',
            'max_day' => 'nullable|integer|gte:min_day',
            'hia_percent' => 'required|numeric|min:0|max:100',
        ]);

        $hia = (float) $validated['hia_percent'];
        $bracket->update([
            'name' => $validated['name'],
            'policy_category' => strtoupper(trim($validated['policy_category'] ?: 'ALL')),
            'min_day' => $validated['min_day'],
            'max_day' => $validated['max_day'] ?? null,
            'hia_percent' => $hia,
            'patient_percent' => round(100.0 - $hia, 2),
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()->route('ccu.services.index')->with('active_tab', 'brackets-pane')->with('success', "تم تحديث شريحة ({$bracket->name}) بنجاح.");
    }

    /**
     * حذف شريحة
     */
    public function destroyBracket(CcuStayBracket $bracket)
    {
        $this->authorizeManage();
        $bracket->delete();

        return redirect()->route('ccu.services.index')->with('active_tab', 'brackets-pane')->with('success', 'تم حذف الشريحة بنجاح.');
    }

    /**
     * حفظ الضوابط والقواعد العامة الداينمكية
     */
    public function updateSettings(Request $request)
    {
        $this->authorizeManage();

        $data = $request->input('settings', []);
        foreach ($data as $key => $value) {
            CcuSetting::where('key', $key)->update(['value' => (string) $value]);
        }

        // دعم الحقول المرسلة كصندوق اختيار Boolean
        CcuSetting::where('key', 'category_a_exempt')->update([
            'value' => $request->has('settings.category_a_exempt') ? '1' : '0',
        ]);

        return redirect()->route('ccu.services.index')->with('active_tab', 'settings-pane')->with('success', 'تم حفظ القواعد والضوابط الداينميكية للعناية المركزة بنجاح.');
    }
}
