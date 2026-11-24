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
    /**
     * نموذج توريد وجبة / قائمة أدوية جديدة
     */
    public function create(Request $request)
    {
        $selectedMedicineId = $request->get('medicine_id');
        $medicines = Medicine::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'generic_name', 'dosage_form', 'strength', 'main_unit', 'sub_unit', 'sub_units_count', 'cost_price', 'sale_price', 'sub_unit_sale_price', 'hi_price', 'national_code', 'barcode']);

        return view('pharmacy.batches.create', compact('medicines', 'selectedMedicineId'));
    }

    /**
     * حفظ وجبة أو قائمة توريد وجبات جديدة بالصيدلية
     */
    public function store(Request $request)
    {
        // 1. معالجة توريد قائمة أدوية متعددة البنود
        if ($request->has('items') && is_array($request->items)) {
            $validated = $request->validate([
                'supplier_name' => 'nullable|string|max:255',
                'received_at' => 'nullable|date',
                'status' => 'required|in:active,quarantined',
                'items' => 'required|array|min:1',
                'items.*.medicine_id' => 'required|exists:medicines,id',
                'items.*.batch_number' => 'required|string|max:100',
                'items.*.expiry_date' => 'required|date',
                'items.*.initial_quantity' => 'required|integer|min:1',
                'items.*.bonus_quantity' => 'nullable|integer|min:0',
                'items.*.sub_units_count' => 'nullable|integer|min:1',
                'items.*.purchase_price' => 'required|numeric|min:0',
                'items.*.sale_price' => 'nullable|numeric|min:0',
                'items.*.sub_unit_sale_price' => 'nullable|numeric|min:0',
                'items.*.update_prices' => 'nullable|boolean',
            ], [
                'items.required' => 'يجب إضافة دواء واحد على الأقل في قائمة التوريد.',
                'items.*.medicine_id.required' => 'يرجى اختيار الدواء لكل سطر.',
                'items.*.batch_number.required' => 'رقم الوجبة/التشغيلة مطلوب لكل بند.',
                'items.*.expiry_date.required' => 'تاريخ الانتهاء مطلوب لكل بند.',
                'items.*.initial_quantity.min' => 'الكمية يجب أن تكون 1 على الأقل.',
                'items.*.purchase_price.min' => 'سعر الشراء لا يمكن أن يكون سالباً.',
            ]);

            $createdCount = 0;
            $receivedAt = $validated['received_at'] ?? now()->toDateString();
            $supplierName = $validated['supplier_name'] ?? null;
            $status = $validated['status'];

            \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $receivedAt, $supplierName, $status, &$createdCount) {
                foreach ($validated['items'] as $item) {
                    $orderedQty = (int) $item['initial_quantity'];
                    $bonusQty = isset($item['bonus_quantity']) ? max(0, (int) $item['bonus_quantity']) : 0;
                    $totalReceivedBoxes = $orderedQty + $bonusQty;

                    // احتساب التكلفة الفعلية للعلبة مع احتساب البونص
                    $boxPurchasePrice = (float) $item['purchase_price'];
                    $effectiveBoxCost = $totalReceivedBoxes > 0
                        ? round(($orderedQty * $boxPurchasePrice) / $totalReceivedBoxes, 2)
                        : $boxPurchasePrice;

                    MedicineBatch::create([
                        'medicine_id' => $item['medicine_id'],
                        'batch_number' => trim($item['batch_number']),
                        'expiry_date' => $item['expiry_date'],
                        'initial_quantity' => $totalReceivedBoxes,
                        'current_quantity' => $totalReceivedBoxes,
                        'current_sub_units' => 0,
                        'purchase_price' => $effectiveBoxCost,
                        'supplier_name' => $supplierName,
                        'received_at' => $receivedAt,
                        'status' => $status,
                    ]);

                    // تحديث أسعار الدواء المرجعية في الدليل إذا تم تحديد ذلك أو لتحديث سعر آخر تكلفة
                    $medicine = Medicine::find($item['medicine_id']);
                    if ($medicine) {
                        $updateData = ['cost_price' => $boxPurchasePrice];

                        if (isset($item['sub_units_count']) && (int) $item['sub_units_count'] > 0) {
                            $updateData['sub_units_count'] = (int) $item['sub_units_count'];
                        }

                        if (! empty($item['update_prices'])) {
                            if (isset($item['sale_price']) && (float) $item['sale_price'] > 0) {
                                $updateData['sale_price'] = (float) $item['sale_price'];
                            }
                            if (isset($item['sub_unit_sale_price']) && (float) $item['sub_unit_sale_price'] > 0) {
                                $updateData['sub_unit_sale_price'] = (float) $item['sub_unit_sale_price'];
                            }
                        }

                        $medicine->update($updateData);
                    }

                    $createdCount++;
                }
            });

            return redirect()->route('pharmacy.batches.index')
                ->with('success', "تم توريد وإيداع قائمة الأدوية بنجاح ({$createdCount} وجبة مسجلة برصيد الصيدلية).");
        }

        // 2. معالجة الإدخال الفردي لدواء واحد (Backward Compatibility)
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
