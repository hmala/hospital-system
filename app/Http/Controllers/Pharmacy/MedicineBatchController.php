<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;

class MedicineBatchController extends Controller
{
    /**
     * لوحة متابعة الشحنات والصلاحيات (FEFO Dashboard)
     */
    public function index(Request $request)
    {
        $query = MedicineBatch::with('medicine');

        // فلترة بالدواء
        if ($request->filled('medicine_id')) {
            $query->where('medicine_id', $request->medicine_id);
        }

        // بحث برقم الوجبة أو اسم الدواء
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('batch_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhereHas('medicine', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('generic_name', 'like', "%{$search}%")
                         ->orWhere('national_code', 'like', "%{$search}%");
                  });
            });
        }

        // فلترة الحالة ونطاق الصلاحية
        $status = $request->get('status', 'all');
        $today = now()->toDateString();

        if ($status === 'active') {
            $query->where('status', 'active')
                  ->where('expiry_date', '>=', $today)
                  ->where('current_quantity', '>', 0);
        } elseif ($status === 'expiring_soon') {
            $days = (int) $request->get('days', 90);
            $query->where('status', 'active')
                  ->whereBetween('expiry_date', [$today, now()->addDays($days)->toDateString()])
                  ->where('current_quantity', '>', 0);
        } elseif ($status === 'expired') {
            $query->where(function ($q) use ($today) {
                $q->where('expiry_date', '<', $today)
                  ->orWhere('status', 'expired');
            });
        } elseif ($status === 'quarantined') {
            $query->where('status', 'quarantined');
        } elseif ($status === 'depleted') {
            $query->where('status', 'depleted')
                  ->orWhere(function ($q) {
                      $q->where('current_quantity', 0)
                        ->where('current_sub_units', 0);
                  });
        }

        // ترتيب افتراضي بنظام FEFO (الأقرب انتهاءً أولاً)
        $batches = $query->orderBy('expiry_date', 'asc')->paginate(20)->withQueryString();

        // إحصائيات علوية سريعة للوحة الصلاحيات
        $stats = [
            'total_batches' => MedicineBatch::count(),
            'active_batches' => MedicineBatch::where('status', 'active')
                ->where('expiry_date', '>=', $today)
                ->where('current_quantity', '>', 0)
                ->count(),
            'expiring_soon' => MedicineBatch::where('status', 'active')
                ->whereBetween('expiry_date', [$today, now()->addDays(90)->toDateString()])
                ->where('current_quantity', '>', 0)
                ->count(),
            'expired' => MedicineBatch::where('expiry_date', '<', $today)
                ->orWhere('status', 'expired')
                ->count(),
            'quarantined' => MedicineBatch::where('status', 'quarantined')->count(),
            // القيمة المالية للأدوية القريبة من الانتهاء (أقل من 90 يوم)
            'at_risk_value' => MedicineBatch::where('status', 'active')
                ->whereBetween('expiry_date', [$today, now()->addDays(90)->toDateString()])
                ->selectRaw('SUM(current_quantity * purchase_price) as val')
                ->value('val') ?? 0,
        ];

        $medicinesList = Medicine::where('is_active', true)->orderBy('name')->get(['id', 'name', 'main_unit']);

        return view('pharmacy.batches.index', compact('batches', 'stats', 'medicinesList'));
    }

    /**
     * نموذج توريد وجبة جديدة
     */
    public function create(Request $request)
    {
        $selectedMedicineId = $request->get('medicine_id');
        $medicines = Medicine::where('is_active', true)->orderBy('name')->get();

        return view('pharmacy.batches.create', compact('medicines', 'selectedMedicineId'));
    }

    /**
     * حفظ وجبة جديدة بالدليل
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'medicine_id' => 'required|exists:medicines,id',
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date',
            'initial_quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'received_at' => 'nullable|date',
            'status' => 'required|in:active,quarantined',
        ]);

        $validated['current_quantity'] = (int) $validated['initial_quantity'];
        $validated['current_sub_units'] = 0;
        $validated['received_at'] = $validated['received_at'] ?? now()->toDateString();

        $batch = MedicineBatch::create($validated);

        return redirect()->route('pharmacy.batches.index')
            ->with('success', "تم توريد الوجبة «{$batch->batch_number}» بنجاح للدواء.");
    }

    /**
     * نموذج تعديل الوجبة
     */
    public function edit(MedicineBatch $batch)
    {
        $batch->load('medicine');
        return view('pharmacy.batches.edit', compact('batch'));
    }

    /**
     * تحديث بيانات الوجبة
     */
    public function update(Request $request, MedicineBatch $batch)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string|max:100',
            'expiry_date' => 'required|date',
            'current_quantity' => 'required|integer|min:0',
            'current_sub_units' => 'required|integer|min:0',
            'purchase_price' => 'required|numeric|min:0',
            'supplier_name' => 'nullable|string|max:255',
            'status' => 'required|in:active,expired,depleted,quarantined',
        ]);

        $batch->update($validated);

        return redirect()->route('pharmacy.batches.index')
            ->with('success', "تم تحديث بيانات الوجبة «{$batch->batch_number}» بنجاح.");
    }

    /**
     * تغيير حالة الوجبة سريعاً (حجر / تفعيل / إلغاء حجر)
     */
    public function changeStatus(Request $request, MedicineBatch $batch)
    {
        $request->validate([
            'status' => 'required|in:active,quarantined,expired',
        ]);

        $batch->update(['status' => $request->status]);

        $statusLabels = [
            'active' => 'تفعيل',
            'quarantined' => 'حجر الوجبة ومنع صرفها',
            'expired' => 'تسجيلها كمنتهية الصلاحية',
        ];

        return back()->with('success', "تم {$statusLabels[$request->status]} للوجبة «{$batch->batch_number}».");
    }

    /**
     * حذف الوجبة
     */
    public function destroy(MedicineBatch $batch)
    {
        if ($batch->saleItems()->exists()) {
            return back()->with('error', 'لا يمكن حذف هذه الوجبة لوجود عمليات بيع وصرف مسجلة عليها.');
        }

        $batchNum = $batch->batch_number;
        $batch->delete();

        return redirect()->route('pharmacy.batches.index')
            ->with('success', "تم حذف الوجبة «{$batchNum}» بنجاح.");
    }
}
