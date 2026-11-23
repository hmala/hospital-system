<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransferRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function indexRequests(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'super_admin']);
        $isAdmin = $user && ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || $user->can('manage inventory'));

        $query = StockTransferRequest::with(['fromLocation', 'toLocation', 'requestedBy', 'approvedBy']);

        // إذا كان المستخدم كادر تمريضي أو عهدة قسم وليس مديراً عاماً أو أمين مخزن، نحصر الطلبات بما يخص عهدته أو ما رفعه هو
        $userLocation = null;
        if (! $isAdmin) {
            $userLocationId = $user?->location_id ?: $request->query('location_id');
            if ($userLocationId) {
                $userLocation = Location::find($userLocationId);
            }

            $query->where(function ($q) use ($user, $userLocationId) {
                if ($userLocationId) {
                    $q->where('to_location_id', $userLocationId)
                        ->orWhere('from_location_id', $userLocationId);
                }
                $q->orWhere('requested_by', $user?->id);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('stock-transfers.requests.index', compact('requests', 'isAdmin', 'isSuperAdmin', 'userLocation'));
    }

    public function create(Request $request)
    {
        $user = Auth::user();
        $mainLocations = Location::where('type', 'main')->orderBy('name')->get();
        $subLocations = Location::where('type', 'sub')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        // تحديد ما إذا كان المستخدم مقيداً بمخزنه الفرعي
        $targetLocationId = $request->query('to_location_id') ?: ($user?->location_id ?: null);
        $lockedLocation = null;
        $isLockedToLocation = false;

        if ($targetLocationId) {
            $lockedLocation = $subLocations->firstWhere('id', $targetLocationId) ?? Location::where('type', 'sub')->find($targetLocationId);
        }

        // إذا كان المستخدم ليس مديراً عاماً ولديه مخزن أو تم تمرير مخزن من محطة التمريض، يُقفل الاختيار
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        if ($lockedLocation && (! $isAdmin || $request->has('to_location_id'))) {
            $isLockedToLocation = true;
        }

        return view('stock-transfers.create', compact(
            'mainLocations',
            'subLocations',
            'products',
            'lockedLocation',
            'isLockedToLocation'
        ));
    }

    public function storeRequest(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);

        // إذا كان المستخدم كادر تمريضي ولديه مخزن فرعي محدد، نلزم الطلب بمخزنه لمنع التلاعب
        if (! $isAdmin && $user?->location_id && $request->to_location_id != $user->location_id) {
            return back()->withInput()->withErrors(['to_location_id' => 'غير مصرح لك بطلب مواد لغير مخزن عهدتك المعتمد.']);
        }

        $request->validate([
            'to_location_id' => 'required|exists:locations,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $mainLocation = Location::where('type', 'main')->first();
        if (! $mainLocation) {
            return back()->withInput()->withErrors(['to_location_id' => 'لا يوجد مخزن رئيسي متاح لإنشاء الطلب.']);
        }

        $toLocation = Location::find($request->to_location_id);
        if (! $toLocation || $toLocation->type !== 'sub') {
            return back()->withInput()->withErrors(['to_location_id' => 'يجب اختيار مخزن فرعي صحيح.']);
        }

        $requestedQuantities = [];
        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $qty = (int) $item['qty'];
            $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + $qty;
        }

        $shortages = [];
        foreach ($requestedQuantities as $productId => $qtyNeeded) {
            $availableQty = StockBatch::where('product_id', $productId)
                ->where('location_id', $mainLocation->id)
                ->where('current_qty', '>', 0)
                ->sum('current_qty');

            if ($availableQty < $qtyNeeded) {
                $productName = Product::find($productId)?->name ?? 'هذه المادة';
                $shortages[] = "الكمية المطلوبة من {$productName} ({$qtyNeeded}) تتجاوز المتوفر ({$availableQty})";
            }
        }

        if (! empty($shortages)) {
            return back()->withInput()->withErrors(['items' => $shortages]);
        }

        StockTransferRequest::create([
            'from_location_id' => $mainLocation->id,
            'to_location_id' => $toLocation->id,
            'requested_by' => Auth::id(),
            'items' => $request->items,
            'status' => 'pending',
        ]);

        return redirect()->route('stock-transfers.requests.index')
            ->with('success', 'تم إنشاء طلب المخزون بنجاح. سيتم مراجعته من المخزن الرئيسي.');
    }

    public function editRequest(StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'super_admin']);
        $isOwnerOrCustodian = ($stockTransferRequest->requested_by == $user?->id)
            || ($user?->location_id && $stockTransferRequest->to_location_id == $user->location_id);

        if (! $isSuperAdmin && ! $isOwnerOrCustodian) {
            abort(403, 'غير مصرح لك بتعديل هذا الطلب. التعديل متاح فقط لمقدم الطلب أو مسؤول عهدة الطابق المستلم.');
        }

        if ($stockTransferRequest->status !== 'pending') {
            return redirect()->route('stock-transfers.requests.show', $stockTransferRequest)
                ->withErrors(['status' => 'لا يمكن تعديل الطلب بعد اعتماده أو رفضه.']);
        }

        $mainLocations = Location::where('type', 'main')->orderBy('name')->get();
        $subLocations = Location::where('type', 'sub')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $lockedLocation = $stockTransferRequest->toLocation;
        $isLockedToLocation = ! $isSuperAdmin || (bool) $user?->location_id;

        // تجهيز قائمة المواد الحالية في الطلب
        $rawItems = is_array($stockTransferRequest->items) ? $stockTransferRequest->items : json_decode($stockTransferRequest->items, true);
        $currentItems = [];
        if (! empty($rawItems)) {
            $productIds = collect($rawItems)->pluck('product_id')->filter();
            $fetchedProducts = Product::whereIn('id', $productIds)->get()->keyBy('id');
            foreach ($rawItems as $it) {
                $pid = $it['product_id'] ?? null;
                $prod = $fetchedProducts->get($pid);
                if ($prod) {
                    $currentItems[] = [
                        'product_id' => $prod->id,
                        'name' => $prod->name,
                        'code' => $prod->code ?: 'PRD-'.$prod->id,
                        'unit' => $prod->unit ?: 'وحدة',
                        'qty' => (int) ($it['qty'] ?? 1),
                    ];
                }
            }
        }

        return view('stock-transfers.requests.edit', compact(
            'stockTransferRequest',
            'mainLocations',
            'subLocations',
            'products',
            'lockedLocation',
            'isLockedToLocation',
            'currentItems'
        ));
    }

    public function updateRequest(Request $request, StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'super_admin']);
        $isOwnerOrCustodian = ($stockTransferRequest->requested_by == $user?->id)
            || ($user?->location_id && $stockTransferRequest->to_location_id == $user->location_id);

        if (! $isSuperAdmin && ! $isOwnerOrCustodian) {
            abort(403, 'غير مصرح لك بتعديل هذا الطلب. التعديل متاح فقط لمقدم الطلب أو مسؤول عهدة الطابق المستلم.');
        }

        if ($stockTransferRequest->status !== 'pending') {
            return redirect()->route('stock-transfers.requests.show', $stockTransferRequest)
                ->withErrors(['status' => 'لا يمكن تعديل الطلب بعد اعتماده أو رفضه.']);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $mainLocation = $stockTransferRequest->fromLocation ?: Location::where('type', 'main')->first();
        if (! $mainLocation) {
            return back()->withInput()->withErrors(['items' => 'لا يوجد مخزن رئيسي متاح للطلب.']);
        }

        $requestedQuantities = [];
        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $qty = (int) $item['qty'];
            $requestedQuantities[$productId] = ($requestedQuantities[$productId] ?? 0) + $qty;
        }

        $shortages = [];
        foreach ($requestedQuantities as $productId => $qtyNeeded) {
            $availableQty = StockBatch::where('product_id', $productId)
                ->where('location_id', $mainLocation->id)
                ->where('current_qty', '>', 0)
                ->sum('current_qty');

            if ($availableQty < $qtyNeeded) {
                $productName = Product::find($productId)?->name ?? 'هذه المادة';
                $shortages[] = "الكمية المطلوبة من {$productName} ({$qtyNeeded}) تتجاوز المتوفر بالمخزن الرئيسي ({$availableQty})";
            }
        }

        if (! empty($shortages)) {
            return back()->withInput()->withErrors(['items' => $shortages]);
        }

        $stockTransferRequest->update([
            'items' => array_values($request->items),
        ]);

        return redirect()->route('stock-transfers.requests.show', $stockTransferRequest)
            ->with('success', 'تم تحديث طلبية تعزيز المخزون بنجاح.');
    }

    public function destroyRequest(StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'super_admin']);
        $isOwnerOrCustodian = ($stockTransferRequest->requested_by == $user?->id)
            || ($user?->location_id && $stockTransferRequest->to_location_id == $user->location_id);

        if (! $isSuperAdmin && ! $isOwnerOrCustodian) {
            abort(403, 'غير مصرح لك بإلغاء هذا الطلب. الإلغاء متاح فقط لمقدم الطلب أو مسؤول عهدة الطابق المستلم.');
        }

        if ($stockTransferRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'لا يمكن إلغاء الطلب بعد اعتماده أو رفضه.']);
        }

        $stockTransferRequest->delete();

        return redirect()->route('stock-transfers.requests.index')
            ->with('success', 'تم إلغاء وحذف طلبية تعزيز المخزون بنجاح.');
    }

    public function createReturn(Request $request)
    {
        $user = Auth::user();
        $mainLocations = Location::where('type', 'main')->orderBy('name')->get();
        $subLocations = Location::where('type', 'sub')->orderBy('name')->get();

        // 1. تحديد المخزن الفرعي (مخزن الطابق المصدر)
        $selectedFromLocationId = $request->query('from_location_id');
        if (! $selectedFromLocationId && $user && $user->location_id) {
            $selectedFromLocationId = $user->location_id;
        }

        // قفل المخزن إذا كان المستخدم ممرضاً/أمين عهدة وليس مديراً عاماً
        $isLockedToLocation = false;
        if ($user && $user->location_id && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            $selectedFromLocationId = $user->location_id;
            $isLockedToLocation = true;
        }

        $selectedFromLocation = $selectedFromLocationId ? Location::find($selectedFromLocationId) : $subLocations->first();

        // 2. جلب وجبات المخزن الفرعي المتوفرة فعلياً فقط (current_qty > 0)
        $floorBatches = collect();
        if ($selectedFromLocation) {
            $floorBatches = StockBatch::with('product')
                ->where('location_id', $selectedFromLocation->id)
                ->where('current_qty', '>', 0)
                ->orderBy('expiry_date')
                ->get();
        }

        // 3. التحقق من وجود وجبة محددة مسبقاً (عبر النقر المباشر من جدول عهدة الطابق)
        $preselectedBatch = null;
        if ($request->filled('batch_id')) {
            $preselectedBatch = $floorBatches->firstWhere('id', $request->query('batch_id'))
                ?? StockBatch::with('product')->find($request->query('batch_id'));
        }

        // 4. أسباب الإرجاع القياسية
        $standardReasons = [
            'surplus' => 'فائض عن حاجة المريض / خروج المريض',
            'cancelled_surgery' => 'إلغاء حجز العملية الجراحية',
            'near_expiry' => 'قرب انتهاء الصلاحية للاستبدال',
            'damaged' => 'مادة تالفة أو عبوة غير صالحة',
            'other' => 'أخرى (يرجى التوضيح في الملاحظات)',
        ];

        // 5. تجهيز بيانات الوجبات كـ array للواجهة الأمامية
        $batchesData = $floorBatches->map(function ($b) {
            return [
                'id' => $b->id,
                'product_id' => $b->product_id,
                'name' => $b->product?->name ?? 'مادة غير معروفة',
                'lot' => $b->internal_barcode ?: ($b->manufacturer_lot_number ?: '#'.$b->id),
                'exp' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : 'غير محدد',
                'qty' => $b->current_qty,
                'unit' => $b->product?->unit ?? 'وحدة',
            ];
        })->values();

        $purchase = null;
        if ($request->filled('purchase_id')) {
            $purchase = Purchase::with('items.product')->find($request->purchase_id);
        }

        return view('stock-transfers.return', compact(
            'mainLocations',
            'subLocations',
            'selectedFromLocation',
            'isLockedToLocation',
            'floorBatches',
            'batchesData',
            'preselectedBatch',
            'standardReasons',
            'purchase'
        ));
    }

    public function storeReturn(Request $request)
    {
        $user = Auth::user();

        // 1. التحقق من صحة المدخلات الأساسية
        $request->validate([
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
        ]);

        $fromLocationId = $request->from_location_id;
        $toLocationId = $request->to_location_id;

        // التحقق الأمني: إذا كان المستخدم مرتبطاً بموقع معين وليس مديراً
        if ($user && $user->location_id && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])) {
            if ($fromLocationId != $user->location_id) {
                abort(403, 'غير مصرح لك بتقديم طلب إرجاع نيابة عن مخزن طابق آخر.');
            }
        }

        $fromLocation = Location::find($fromLocationId);
        $toLocation = Location::find($toLocationId);

        if (! $fromLocation || ! $toLocation || $fromLocation->type !== 'sub' || $toLocation->type !== 'main') {
            return back()->withInput()->withErrors(['return' => 'يجب أن يكون الإرجاع من مخزن فرعي إلى مخزن رئيسي فقط.']);
        }

        // 2. تجميع المواد (سواء أُرسلت كـ items مصفوفة متعددة أو حقول مفردة)
        $rawItems = [];
        if ($request->has('items') && is_array($request->items)) {
            $rawItems = $request->items;
        } elseif ($request->filled('product_id')) {
            $rawItems = [
                [
                    'product_id' => $request->product_id,
                    'batch_id' => $request->batch_id,
                    'qty' => $request->qty,
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                ],
            ];
        }

        if (empty($rawItems)) {
            return back()->withInput()->withErrors(['items' => 'يرجى تحديد مادة واحدة على الأقل للإرجاع.']);
        }

        $itemsPayload = [];

        foreach ($rawItems as $index => $item) {
            $productId = $item['product_id'] ?? null;
            $batchId = $item['batch_id'] ?? null;
            $qtyNeeded = (int) ($item['qty'] ?? 0);
            $reason = $item['reason'] ?? ($request->input('reason') ?? 'فائض عن حاجة الطابق');
            $notes = $item['notes'] ?? ($request->input('notes') ?? null);

            if (! $productId || $qtyNeeded < 1) {
                return back()->withInput()->withErrors(['qty' => 'يرجى تحديد المادة والكمية المطلوبة بدقة (الكمية يجب أن تكون 1 على الأقل).']);
            }

            // فحص الرصيد المتاح للوجبة أو للمادة في المخزن الفرعي
            if ($batchId) {
                $batch = StockBatch::where('id', $batchId)
                    ->where('location_id', $fromLocationId)
                    ->first();

                if (! $batch || $batch->current_qty < $qtyNeeded) {
                    $available = $batch ? $batch->current_qty : 0;

                    return back()->withInput()->withErrors(['qty' => "الكمية المطلوبة ({$qtyNeeded}) تتجاوز الرصيد المتاح في الوجبة المحددة ({$available})."]);
                }
            } else {
                $availableQty = StockBatch::where('product_id', $productId)
                    ->where('location_id', $fromLocationId)
                    ->where('current_qty', '>', 0)
                    ->sum('current_qty');

                if ($availableQty < $qtyNeeded) {
                    return back()->withInput()->withErrors(['qty' => "الكمية المطلوبة ({$qtyNeeded}) تتجاوز المخزون المتاح في مخزن هذا الطابق ({$availableQty})."]);
                }
            }

            $itemsPayload[] = [
                'product_id' => (int) $productId,
                'batch_id' => $batchId ? (int) $batchId : null,
                'qty' => $qtyNeeded,
                'reason' => $reason,
                'notes' => $notes,
            ];
        }

        $stockTransferRequest = StockTransferRequest::create([
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'requested_by' => Auth::id(),
            'items' => $itemsPayload,
            'status' => 'pending',
        ]);

        if ($request->filled('from_nursing')) {
            return redirect()->route('nursing-station.index', ['location_id' => $fromLocationId])
                ->with('success', 'تم إنشاء طلب إرجاع المواد للمخزن الرئيسي بنجاح (طلب رقم #'.$stockTransferRequest->id.'). بانتظار اعتماد أمين المخزن.');
        }

        if ($request->filled('from_surgery')) {
            return redirect()->route('surgeries.index', ['tab' => 'or_store', 'location_id' => $fromLocationId])
                ->with('success', 'تم إنشاء طلب إرجاع المواد من مخزن العمليات للمخزن الرئيسي بنجاح (طلب رقم #'.$stockTransferRequest->id.'). بانتظار اعتماد أمين المخزن.');
        }

        return redirect()->route('stock-transfers.requests.index')
            ->with('success', 'تم إنشاء طلب إرجاع المخزون بنجاح (طلب رقم #'.$stockTransferRequest->id.'). لا يتم تنفيذ الإرجاع إلا بعد موافقة المخزن الرئيسي.');
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_location_id' => 'required|exists:locations,id|different:to_location_id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $fromLocationId = $request->from_location_id;
        $toLocationId = $request->to_location_id;

        $items = $request->items;

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                $productId = $item['product_id'];
                $qtyNeeded = $item['qty'];

                $batches = StockBatch::where('product_id', $productId)
                    ->where('location_id', $fromLocationId)
                    ->where('current_qty', '>', 0)
                    ->fefo()
                    ->get();

                $availableQty = $batches->sum('current_qty');
                if ($availableQty < $qtyNeeded) {
                    DB::rollBack();

                    return back()->withInput()->withErrors(['items' => 'الكمية المطلوبة لمادة واحدة أو أكثر تتجاوز المخزون المتاح.']);
                }

                foreach ($batches as $batch) {
                    if ($qtyNeeded <= 0) {
                        break;
                    }

                    $transferQty = min($batch->current_qty, $qtyNeeded);
                    $transferTime = now();

                    if ($transferQty === $batch->current_qty) {
                        // Move entire batch to new location and record transfer time
                        if (! $batch->original_received_at) {
                            $batch->original_received_at = $batch->received_at;
                        }
                        if (! $batch->original_barcode) {
                            $batch->original_barcode = $batch->internal_barcode;
                        }
                        $batch->location_id = $toLocationId;
                        $batch->received_at = $transferTime;
                        $batch->save();
                        $newBatch = $batch;
                    } else {
                        // Split the batch and create a child batch for the moved quantity
                        $batch->current_qty -= $transferQty;
                        $batch->save();

                        $newBatch = StockBatch::create([
                            'product_id' => $batch->product_id,
                            'location_id' => $toLocationId,
                            'purchase_item_id' => $batch->purchase_item_id,
                            'internal_barcode' => $this->generateBatchBarcode($batch->product_id),
                            'original_barcode' => $batch->original_barcode ?: $batch->internal_barcode,
                            'cost_price' => $batch->cost_price,
                            'initial_qty' => $transferQty,
                            'current_qty' => $transferQty,
                            'expiry_date' => $batch->expiry_date,
                            'received_at' => $transferTime,
                            'original_received_at' => $batch->original_received_at ?: $batch->received_at,
                            'parent_batch_id' => $batch->id,
                        ]);
                    }

                    StockMovement::create([
                        'batch_id' => $newBatch->id,
                        'from_location_id' => $fromLocationId,
                        'to_location_id' => $toLocationId,
                        'quantity' => $transferQty,
                        'type' => 'transfer',
                        'user_id' => Auth::id(),
                    ]);

                    $qtyNeeded -= $transferQty;
                }
            }

            DB::commit();

            return redirect()->route('stock-transfers.create')->with('success', 'تم تحويل المخزون بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['transfer' => 'حدث خطأ أثناء التحويل: '.$e->getMessage()]);
        }
    }

    public function showRequest(StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        $isAdmin = $user && ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin'])
            || $user->can('manage inventory'));

        if (! $isAdmin && $user?->location_id) {
            if ($stockTransferRequest->to_location_id != $user->location_id
                && $stockTransferRequest->from_location_id != $user->location_id
                && $stockTransferRequest->requested_by != $user->id) {
                abort(403, 'غير مصرح لك بالاطلاع على طلبات مخازن الطوابق الأخرى.');
            }
        }

        $request = $stockTransferRequest;
        $productIds = collect($request->items)->pluck('product_id')->unique();
        $subLocationBatches = StockBatch::where('location_id', $request->to_location_id)
            ->whereIn('product_id', $productIds)
            ->fefo()
            ->get();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return view('stock-transfers.requests.show', compact('stockTransferRequest', 'subLocationBatches', 'products', 'isAdmin'));
    }

    public function approveRequest(Request $request, StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        if (! ($user && ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || $user->can('manage inventory')))) {
            abort(403, 'غير مصرح لك بالموافقة على طلبات التحويل، الصلاحية مخصصة لأمين المخزن الرئيسي.');
        }

        if ($stockTransferRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'هذا الطلب ليس قيد الانتظار.']);
        }

        DB::beginTransaction();

        try {
            $fromLocationId = $stockTransferRequest->from_location_id;
            $toLocationId = $stockTransferRequest->to_location_id;

            foreach ($stockTransferRequest->items as $item) {
                $productId = $item['product_id'];
                $qtyNeeded = $item['qty'];
                $specifiedBatchId = $item['batch_id'] ?? null;

                $batchQuery = StockBatch::where('product_id', $productId)
                    ->where('location_id', $fromLocationId)
                    ->where('current_qty', '>', 0);

                if ($specifiedBatchId) {
                    $batchQuery->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [(int) $specifiedBatchId]);
                }

                $batches = $batchQuery->fefo()->get();

                $availableQty = $batches->sum('current_qty');
                if ($availableQty < $qtyNeeded) {
                    DB::rollBack();

                    return back()->withErrors(['approve' => 'الكمية المطلوبة لا تتوفر في المخزن المصدر.']);
                }

                foreach ($batches as $batch) {
                    if ($qtyNeeded <= 0) {
                        break;
                    }

                    $transferQty = min($batch->current_qty, $qtyNeeded);
                    $transferTime = now();

                    if ($transferQty === $batch->current_qty) {
                        if (! $batch->original_received_at) {
                            $batch->original_received_at = $batch->received_at;
                        }
                        if (! $batch->original_barcode) {
                            $batch->original_barcode = $batch->internal_barcode;
                        }
                        $batch->location_id = $toLocationId;
                        $batch->received_at = $transferTime;
                        $batch->save();
                        $newBatch = $batch;
                    } else {
                        $batch->current_qty -= $transferQty;
                        $batch->save();

                        $newBatch = StockBatch::create([
                            'product_id' => $batch->product_id,
                            'location_id' => $toLocationId,
                            'purchase_item_id' => $batch->purchase_item_id,
                            'internal_barcode' => $this->generateBatchBarcode($batch->product_id),
                            'original_barcode' => $batch->original_barcode ?: $batch->internal_barcode,
                            'cost_price' => $batch->cost_price,
                            'initial_qty' => $transferQty,
                            'current_qty' => $transferQty,
                            'expiry_date' => $batch->expiry_date,
                            'received_at' => $transferTime,
                            'original_received_at' => $batch->original_received_at ?: $batch->received_at,
                            'parent_batch_id' => $batch->id,
                        ]);
                    }

                    StockMovement::create([
                        'batch_id' => $newBatch->id,
                        'from_location_id' => $fromLocationId,
                        'to_location_id' => $toLocationId,
                        'quantity' => $transferQty,
                        'type' => 'transfer',
                        'user_id' => Auth::id(),
                    ]);

                    $qtyNeeded -= $transferQty;
                }
            }

            $stockTransferRequest->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('stock-transfers.requests.show', $stockTransferRequest)
                ->with('success', 'تمت الموافقة على طلب المخزون وتحويل المواد.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['approve' => 'حدث خطأ أثناء الموافقة: '.$e->getMessage()]);
        }
    }

    public function rejectRequest(Request $request, StockTransferRequest $stockTransferRequest)
    {
        $user = Auth::user();
        if (! ($user && ($user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']) || $user->can('manage inventory')))) {
            abort(403, 'غير مصرح لك برفض طلبات التحويل، الصلاحية مخصصة لأمين المخزن الرئيسي.');
        }

        if ($stockTransferRequest->status !== 'pending') {
            return back()->withErrors(['status' => 'هذا الطلب ليس قيد الانتظار.']);
        }

        $stockTransferRequest->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('stock-transfers.requests.show', $stockTransferRequest)
            ->with('success', 'تم رفض طلب المخزون.');
    }

    private function generateBatchBarcode($productId)
    {
        $prefix = "B{$productId}";
        $date = now()->format('ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        $barcode = "{$prefix}-{$date}-{$random}";

        if (StockBatch::where('internal_barcode', $barcode)->exists()) {
            return $this->generateBatchBarcode($productId);
        }

        return $barcode;
    }
}
