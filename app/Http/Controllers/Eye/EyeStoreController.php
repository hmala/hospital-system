<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Eye\EyeStoreItem;
use App\Models\Eye\EyeStoreMovement;
use App\Models\Location;
use App\Models\Patient;
use App\Models\StockTransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EyeStoreController extends Controller
{
    /**
     * شاشة مخزن مستلزمات وعدسات العيون
     */
    public function index(Request $request)
    {
        $query = EyeStoreItem::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('status')) {
            if ($request->status === 'low_stock') {
                $query->whereColumn('current_stock', '<=', 'min_stock_alert');
            } elseif ($request->status === 'out_of_stock') {
                $query->where('current_stock', '<=', 0);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('item_code', 'like', "%{$search}%")
                  ->orWhere('model_number', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->paginate(25)->withQueryString();

        // إحصائيات المخزن
        $stats = [
            'total_items'    => EyeStoreItem::count(),
            'total_stock'    => EyeStoreItem::sum('current_stock'),
            'low_stock'      => EyeStoreItem::whereColumn('current_stock', '<=', 'min_stock_alert')->count(),
            'iol_count'      => EyeStoreItem::where('category', 'iol_lens')->sum('current_stock'),
            'total_valuation'=> (float) EyeStoreItem::selectRaw('SUM(current_stock * cost_price) as val')->value('val'),
        ];

        // طلبات التجهيز الداخلي الأخيرة من المخزن الرئيسي
        $eyeLocation = Location::where('name', 'like', '%عيون%')->first();
        $transferRequests = collect();
        if ($eyeLocation) {
            $transferRequests = StockTransferRequest::with(['requestedBy', 'approvedBy'])
                ->where('to_location_id', $eyeLocation->id)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        }

        // آخر الحركات المخزنية
        $recentMovements = EyeStoreMovement::with(['item', 'patient', 'creator'])
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get();

        return view('eye.store.eye_store_index', compact('items', 'stats', 'transferRequests', 'recentMovements', 'eyeLocation'));
    }

    /**
     * حفظ صنف جديد في مخزن العيون
     */
    public function store(Request $request)
    {
        $request->validate([
            'item_code'       => 'required|string|unique:eye_store_items,item_code',
            'name'            => 'required|string',
            'category'        => 'required|string',
            'diopter'         => 'nullable|numeric|between:-10,40',
            'model_number'    => 'nullable|string',
            'manufacturer'    => 'nullable|string',
            'unit'            => 'required|string',
            'current_stock'   => 'required|integer|min:0',
            'min_stock_alert' => 'required|integer|min:1',
            'cost_price'      => 'required|numeric|min:0',
            'selling_price'   => 'required|numeric|min:0',
        ]);

        $eyeLocation = Location::where('name', 'like', '%عيون%')->first();

        $itemData = $request->all();
        $itemData['location_id'] = $eyeLocation ? $eyeLocation->id : null;

        $item = EyeStoreItem::create($itemData);

        // إذا كان هناك رصيد افتتاحي يتم تسجيل حركة
        if ($item->current_stock > 0) {
            EyeStoreMovement::create([
                'eye_store_item_id' => $item->id,
                'movement_type'     => 'direct_purchase',
                'quantity'          => $item->current_stock,
                'balance_after'     => $item->current_stock,
                'notes'             => 'رصيد افتتاحي للصنف',
                'created_by'        => Auth::id(),
            ]);
        }

        return back()->with('success', "تمت إضافة الصنف ({$item->name}) إلى مخزن العيون بنجاح.");
    }

    /**
     * توريد مباشر وشراء خاص لمخزن العيون
     */
    public function directPurchase(Request $request)
    {
        $request->validate([
            'eye_store_item_id' => 'required|exists:eye_store_items,id',
            'quantity'          => 'required|integer|min:1',
            'cost_price'        => 'nullable|numeric|min:0',
            'batch_number'      => 'nullable|string',
            'expiry_date'       => 'nullable|date',
            'notes'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $item = EyeStoreItem::findOrFail($request->eye_store_item_id);
            $newBalance = $item->current_stock + $request->quantity;

            $item->update([
                'current_stock' => $newBalance,
                'cost_price'    => $request->cost_price ?? $item->cost_price,
            ]);

            EyeStoreMovement::create([
                'eye_store_item_id' => $item->id,
                'movement_type'     => 'direct_purchase',
                'quantity'          => $request->quantity,
                'balance_after'     => $newBalance,
                'batch_number'      => $request->batch_number,
                'expiry_date'       => $request->expiry_date,
                'notes'             => $request->notes ?? 'توريد وشراء مباشر خاص بالعيون',
                'created_by'        => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', "تم توريد ({$request->quantity} {$item->unit}) بنجاح لصالح ({$item->name}). الرصيد الجديد: {$newBalance}");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء التوريد: ' . $e->getMessage());
        }
    }

    /**
     * إنشاء طلب تجهيز داخلي من المخزن الرئيسي للمستشفى
     */
    public function createTransferRequisition(Request $request)
    {
        $request->validate([
            'items_data' => 'required|string', // JSON string من العناصر المطلوبة
            'notes'      => 'nullable|string',
        ]);

        $mainLocation = Location::where('type', 'main')->first();
        $eyeLocation  = Location::where('name', 'like', '%عيون%')->first();

        if (!$mainLocation || !$eyeLocation) {
            return back()->with('error', 'لم يتم العثور على موقع المخزن الرئيسي أو مخزن العيون في النظام.');
        }

        $items = json_decode($request->items_data, true);
        if (empty($items)) {
            return back()->with('error', 'يجب تحديد صنف واحد على الأقل للطلب.');
        }

        DB::beginTransaction();
        try {
            $requisition = StockTransferRequest::create([
                'from_location_id' => $mainLocation->id,
                'to_location_id'   => $eyeLocation->id,
                'requested_by'     => Auth::id(),
                'items'            => $items,
                'status'           => 'pending',
            ]);

            DB::commit();

            return back()->with('success', "تم إرسال طلب التجهيز رقم (#{$requisition->id}) إلى المخزن الرئيسي بنجاح وبانتظار الموافقة.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء إرسال طلب التجهيز: ' . $e->getMessage());
        }
    }

    /**
     * تأكيد استلام المواد من المخزن الرئيسي وإضافتها لرصيد مخزن العيون
     */
    public function receiveTransfer(Request $request, StockTransferRequest $transferRequest)
    {
        if ($transferRequest->status === 'completed') {
            return back()->with('info', 'هذا الطلب تم استلامه مسبقاً.');
        }

        $items = is_array($transferRequest->items) ? $transferRequest->items : json_decode($transferRequest->items, true);

        DB::beginTransaction();
        try {
            foreach ($items as $reqItem) {
                $itemName = $reqItem['name'] ?? $reqItem['item_name'] ?? 'مستلزم عيون';
                $quantity = (int) ($reqItem['quantity'] ?? 1);
                $unit     = $reqItem['unit'] ?? 'قطعة';

                // البحث عن الصنف في مخزن العيون بالاسم أو الكود أو إنشائه
                $eyeItem = EyeStoreItem::firstOrCreate(
                    ['name' => $itemName],
                    [
                        'item_code'       => 'EYE-GEN-' . rand(1000, 9999),
                        'category'        => 'general_consumable',
                        'unit'            => $unit,
                        'current_stock'   => 0,
                        'min_stock_alert' => 5,
                        'cost_price'      => 0,
                        'selling_price'   => 0,
                    ]
                );

                $newBalance = $eyeItem->current_stock + $quantity;
                $eyeItem->update(['current_stock' => $newBalance]);

                EyeStoreMovement::create([
                    'eye_store_item_id'         => $eyeItem->id,
                    'movement_type'             => 'hospital_transfer_in',
                    'quantity'                  => $quantity,
                    'balance_after'             => $newBalance,
                    'stock_transfer_request_id' => $transferRequest->id,
                    'notes'                     => "تجهيز مستلم من المخزن الرئيسي بموجب طلب رقم (#{$transferRequest->id})",
                    'created_by'                => Auth::id(),
                ]);
            }

            $transferRequest->update([
                'status'      => 'completed',
                'approved_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', "تم تأكيد استلام الشحنة من المخزن الرئيسي بنجاح وإيداع الأصناف في رصيد مخزن العيون.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء استلام المواد: ' . $e->getMessage());
        }
    }

    /**
     * صرف صنف لمريض / عملية جراحية
     */
    public function dispenseItem(Request $request)
    {
        $request->validate([
            'eye_store_item_id' => 'required|exists:eye_store_items,id',
            'patient_id'        => 'required|exists:patients,id',
            'quantity'          => 'required|integer|min:1',
            'surgery_id'        => 'nullable|exists:eye_surgeries,id',
            'notes'             => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $item = EyeStoreItem::findOrFail($request->eye_store_item_id);

            if ($item->current_stock < $request->quantity) {
                return back()->with('error', "الرصيد المتاح من ({$item->name}) غير كافٍ. المتوفر حالياً: {$item->current_stock}");
            }

            $newBalance = $item->current_stock - $request->quantity;
            $item->update(['current_stock' => $newBalance]);

            EyeStoreMovement::create([
                'eye_store_item_id' => $item->id,
                'movement_type'     => 'surgery_dispense',
                'quantity'          => -$request->quantity,
                'balance_after'     => $newBalance,
                'patient_id'        => $request->patient_id,
                'surgery_id'        => $request->surgery_id,
                'notes'             => $request->notes ?? "صرف لصالح مريض/عملية",
                'created_by'        => Auth::id(),
            ]);

            DB::commit();

            return back()->with('success', "تم صرف ({$request->quantity} {$item->unit}) بنجاح للمريض. الرصيد المتبقي: {$newBalance}");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء الصرف: ' . $e->getMessage());
        }
    }
}
