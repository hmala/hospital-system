<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function create()
    {
        $locations = Location::orderBy('name')->get();
        $products = Product::orderBy('name')->get();

        return view('stock-transfers.create', compact('locations', 'products'));
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
            ->orderBy('received_at')
            ->get();

        $availableQty = $batches->sum('current_qty');
        if ($availableQty < $qtyNeeded) {
            return back()->withInput()->withErrors(['qty' => 'الكمية المطلوبة تتجاوز المخزون المتاح في المخزن المحدد.']);
        }

        DB::beginTransaction();

        try {
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
                    'type' => 'return',
                    'user_id' => Auth::id(),
                ]);

                $qtyNeeded -= $transferQty;
            }

            DB::commit();

            return redirect()->route('stock-transfers.returns.create')->with('success', 'تم إرجاع المخزون إلى المخزن الرئيسي بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['return' => 'حدث خطأ أثناء الإرجاع: ' . $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_location_id' => 'required|exists:locations,id|different:to_location_id',
            'to_location_id' => 'required|exists:locations,id',
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
        ]);

        $fromLocationId = $request->from_location_id;
        $toLocationId = $request->to_location_id;
        $productId = $request->product_id;
        $qtyNeeded = $request->qty;

        $batches = StockBatch::where('product_id', $productId)
            ->where('location_id', $fromLocationId)
            ->where('current_qty', '>', 0)
            ->orderBy('received_at')
            ->get();

        $availableQty = $batches->sum('current_qty');
        if ($availableQty < $qtyNeeded) {
            return back()->withInput()->withErrors(['qty' => 'الكمية المطلوبة تتجاوز المخزون المتاح في المخزن المحدد.']);
        }

        DB::beginTransaction();

        try {
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

            DB::commit();

            return redirect()->route('stock-transfers.create')->with('success', 'تم تحويل المخزون بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['transfer' => 'حدث خطأ أثناء التحويل: ' . $e->getMessage()]);
        }
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
