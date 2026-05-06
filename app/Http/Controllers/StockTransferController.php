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
    public function indexRequests()
    {
        $requests = StockTransferRequest::with(['fromLocation', 'toLocation', 'requestedBy', 'approvedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('stock-transfers.requests.index', compact('requests'));
    }

    public function create()
    {
        $mainLocations = Location::where('type', 'main')->orderBy('name')->get();
        $subLocations = Location::where('type', 'sub')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('stock-transfers.create', compact('mainLocations', 'subLocations', 'products'));
    }

    public function storeRequest(Request $request)
    {
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

    public function createReturn(Request $request)
    {
        $mainLocations = Location::where('type', 'main')->orderBy('name')->get();
        $subLocations = Location::where('type', 'sub')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        $purchase = null;

        if ($request->filled('purchase_id')) {
            $purchase = Purchase::with('items.product')->find($request->purchase_id);
        }

        return view('stock-transfers.return', compact('mainLocations', 'subLocations', 'products', 'purchase'));
    }

    public function storeReturn(Request $request)
    {
        $request->validate([
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $fromLocationId = $request->from_location_id;
        $toLocationId = $request->to_location_id;
        $productId = $request->product_id;
        $qtyNeeded = $request->qty;

        $fromLocation = Location::find($fromLocationId);
        $toLocation = Location::find($toLocationId);

        if (! $fromLocation || ! $toLocation || $fromLocation->type !== 'sub' || $toLocation->type !== 'main') {
            return back()->withInput()->withErrors(['return' => 'يجب أن يكون الإرجاع من مخزن فرعي إلى مخزن رئيسي فقط.']);
        }

        $batches = StockBatch::where('product_id', $productId)
            ->where('location_id', $fromLocationId)
            ->where('current_qty', '>', 0)
            ->orderByRaw('COALESCE(original_received_at, received_at)')
            ->get();

        $availableQty = $batches->sum('current_qty');
        if ($availableQty < $qtyNeeded) {
            return back()->withInput()->withErrors(['qty' => 'الكمية المطلوبة تتجاوز المخزون المتاح في المخزن المحدد.']);
        }

        StockTransferRequest::create([
            'from_location_id' => $fromLocationId,
            'to_location_id' => $toLocationId,
            'requested_by' => Auth::id(),
            'items' => [
                [
                    'product_id' => $productId,
                    'qty' => $qtyNeeded,
                ],
            ],
            'status' => 'pending',
        ]);

        return redirect()->route('stock-transfers.requests.index')
            ->with('success', 'تم إنشاء طلب إرجاع المخزون. لا يتم تنفيذ الإرجاع إلا بعد موافقة المخزن الرئيسي.');
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
                    ->orderByRaw('COALESCE(original_received_at, received_at)')
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
            return back()->withInput()->withErrors(['transfer' => 'حدث خطأ أثناء التحويل: ' . $e->getMessage()]);
        }
    }

    public function showRequest(StockTransferRequest $stockTransferRequest)
    {
        $request = $stockTransferRequest;
        $productIds = collect($request->items)->pluck('product_id')->unique();
        $subLocationBatches = StockBatch::where('location_id', $request->to_location_id)
            ->whereIn('product_id', $productIds)
            ->orderByRaw('COALESCE(original_received_at, received_at)')
            ->get();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        return view('stock-transfers.requests.show', compact('stockTransferRequest', 'subLocationBatches', 'products'));
    }

    public function approveRequest(Request $request, StockTransferRequest $stockTransferRequest)
    {
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

                $batches = StockBatch::where('product_id', $productId)
                    ->where('location_id', $fromLocationId)
                    ->where('current_qty', '>', 0)
                    ->orderByRaw('COALESCE(original_received_at, received_at)')
                    ->get();

                $availableQty = $batches->sum('current_qty');
                if ($availableQty < $qtyNeeded) {
                    DB::rollBack();
                    return back()->withErrors(['approve' => 'الكمية المطلوبة لا تتوفر في المخزن الرئيسي.']);
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
            return back()->withErrors(['approve' => 'حدث خطأ أثناء الموافقة: ' . $e->getMessage()]);
        }
    }

    public function rejectRequest(Request $request, StockTransferRequest $stockTransferRequest)
    {
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
