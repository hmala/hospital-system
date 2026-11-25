<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    /**
     * استمارة إنشاء فاتورة مشتريات
     */
    public function index()
    {
        $purchases = Purchase::with('supplier', 'user')
            ->orderBy('received_at', 'desc')
            ->paginate(20);

        $totalPurchasesCount = Purchase::count();
        $totalPurchasesAmount = (float) Purchase::sum('total_amount');
        $receivedPurchasesCount = Purchase::whereNotNull('received_at')->count();
        $pendingPurchasesCount = Purchase::whereNull('received_at')->count();

        return view('purchases.index', compact(
            'purchases',
            'totalPurchasesCount',
            'totalPurchasesAmount',
            'receivedPurchasesCount',
            'pendingPurchasesCount'
        ));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.create', compact('products', 'suppliers'));
    }

    public function show(Purchase $purchase)
    {
        $purchase->load('supplier', 'user', 'items.product', 'items.stockBatch');

        return view('purchases.show', compact('purchase'));
    }

    /**
     * حفظ فاتورة المشتريات وتوليد الوجبات (Batches)
     */
    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|unique:purchases,invoice_number',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::create([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'total_amount' => $this->calculateTotal($request->items),
                'received_at' => now(),
                'user_id' => Auth::id(),
            ]);

            $mainLocation = Location::firstOrCreate([
                'name' => 'المخزن الرئيسي',
                'type' => 'main',
            ]);

            $lineIndex = 1;
            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);

                if ($product->is_perishable && empty($itemData['expiry_date'])) {
                    throw new \InvalidArgumentException("المادة {$product->name} قابلة للتلف ويجب تحديد تاريخ انتهاء.");
                }

                $subtotal = $itemData['qty'] * $itemData['cost_price'];

                $purchaseItem = $purchase->items()->create([
                    'product_id' => $product->id,
                    'qty' => $itemData['qty'],
                    'unit_cost' => $itemData['cost_price'],
                    'subtotal' => $subtotal,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);

                $internalBarcode = $this->generateBatchBarcode($product, $purchase->id, $lineIndex);

                $batch = StockBatch::create([
                    'product_id' => $product->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'location_id' => $mainLocation->id,
                    'internal_barcode' => $internalBarcode,
                    'original_barcode' => $internalBarcode,
                    'cost_price' => $itemData['cost_price'],
                    'initial_qty' => $itemData['qty'],
                    'current_qty' => $itemData['qty'],
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'received_at' => now(),
                    'original_received_at' => now(),
                ]);

                StockMovement::create([
                    'batch_id' => $batch->id,
                    'from_location_id' => $mainLocation->id,
                    'to_location_id' => $mainLocation->id,
                    'quantity' => $itemData['qty'],
                    'type' => 'purchase',
                    'user_id' => Auth::id(),
                ]);

                $lineIndex++;
            }

            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', 'تم حفظ الفاتورة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['purchase' => 'حدث خطأ أثناء المعالجة: '.$e->getMessage()]);
        }
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product', 'items.stockBatch');
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.edit', compact('purchase', 'products', 'suppliers'));
    }

    /**
     * تحديث فاتورة المشتريات ومزامنة الوجبات المخزنية
     */
    public function update(Request $request, Purchase $purchase)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|unique:purchases,invoice_number,'.$purchase->id,
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $purchase->update([
                'supplier_id' => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'total_amount' => $this->calculateTotal($request->items),
            ]);

            $mainLocation = Location::firstOrCreate([
                'name' => 'المخزن الرئيسي',
                'type' => 'main',
            ]);

            $existingItems = $purchase->items()->with('stockBatch')->get()->keyBy('id');
            $submittedItemIds = [];
            $lineIndex = $purchase->items()->count() + 1;

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);

                if ($product->is_perishable && empty($itemData['expiry_date'])) {
                    throw new \InvalidArgumentException("المادة {$product->name} قابلة للتلف ويجب تحديد تاريخ انتهاء.");
                }

                $subtotal = $itemData['qty'] * $itemData['cost_price'];
                $itemId = $itemData['item_id'] ?? null;

                if ($itemId && $existingItems->has($itemId)) {
                    // تحديث عنصر موجود مع حماية الكميات المصروفة أو المنقولة
                    $submittedItemIds[] = $itemId;
                    $purchaseItem = $existingItems->get($itemId);
                    $newQty = (int) $itemData['qty'];

                    if ($purchaseItem->stockBatch) {
                        $batch = $purchaseItem->stockBatch;
                        $consumedOrTransferred = max(0, (int) $batch->initial_qty - (int) $batch->current_qty);
                        $hasExternalMovements = StockMovement::where('batch_id', $batch->id)
                            ->where('type', '!=', 'purchase')
                            ->exists() || $batch->childBatches()->exists();

                        if ($hasExternalMovements && (int) $product->id !== (int) $purchaseItem->product_id) {
                            throw new \InvalidArgumentException("لا يمكن تغيير صنف المادة {$purchaseItem->product?->name} لوجود عمليات صرف أو نقل مخزني تمت عليها.");
                        }

                        if ($newQty < $consumedOrTransferred) {
                            throw new \InvalidArgumentException("لا يمكن تقليل كمية المادة {$product->name} إلى ({$newQty}) لأنه تم صرف أو تحويل ({$consumedOrTransferred}) منها بالفعل.");
                        }

                        $batch->product_id = $product->id;
                        $batch->cost_price = $itemData['cost_price'];
                        $batch->expiry_date = $itemData['expiry_date'] ?? null;
                        $batch->initial_qty = $newQty;
                        $batch->current_qty = $newQty - $consumedOrTransferred;
                        $batch->save();

                        // تحديث حركة الشراء الأصلية
                        StockMovement::where('batch_id', $batch->id)
                            ->where('type', 'purchase')
                            ->update(['quantity' => $newQty]);
                    }

                    $purchaseItem->update([
                        'product_id' => $product->id,
                        'qty' => $newQty,
                        'unit_cost' => $itemData['cost_price'],
                        'subtotal' => $subtotal,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                    ]);
                } else {
                    // إضافة مادة جديدة للفاتورة
                    $purchaseItem = $purchase->items()->create([
                        'product_id' => $product->id,
                        'qty' => $itemData['qty'],
                        'unit_cost' => $itemData['cost_price'],
                        'subtotal' => $subtotal,
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                    ]);

                    $internalBarcode = $this->generateBatchBarcode($product, $purchase->id, $lineIndex);

                    $batch = StockBatch::create([
                        'product_id' => $product->id,
                        'purchase_item_id' => $purchaseItem->id,
                        'location_id' => $mainLocation->id,
                        'internal_barcode' => $internalBarcode,
                        'original_barcode' => $internalBarcode,
                        'cost_price' => $itemData['cost_price'],
                        'initial_qty' => $itemData['qty'],
                        'current_qty' => $itemData['qty'],
                        'expiry_date' => $itemData['expiry_date'] ?? null,
                        'received_at' => now(),
                        'original_received_at' => now(),
                    ]);

                    StockMovement::create([
                        'batch_id' => $batch->id,
                        'from_location_id' => $mainLocation->id,
                        'to_location_id' => $mainLocation->id,
                        'quantity' => $itemData['qty'],
                        'type' => 'purchase',
                        'user_id' => Auth::id(),
                    ]);

                    $lineIndex++;
                }
            }

            // حذف المواد التي أزالها المستخدم من الفاتورة بعد التحقق من عدم صرفها أو نقلها
            foreach ($existingItems as $id => $existingItem) {
                if (! in_array($id, $submittedItemIds)) {
                    if ($existingItem->stockBatch) {
                        $batch = $existingItem->stockBatch;
                        $hasExternalActivity = StockMovement::where('batch_id', $batch->id)
                            ->where('type', '!=', 'purchase')
                            ->exists()
                            || $batch->childBatches()->exists()
                            || $batch->current_qty < $batch->initial_qty;

                        if ($hasExternalActivity) {
                            throw new \InvalidArgumentException("لا يمكن إزالة المادة {$existingItem->product?->name} من الفاتورة لوجود عمليات صرف أو نقل مخزني تمت عليها.");
                        }

                        StockMovement::where('batch_id', $batch->id)->delete();
                        $batch->delete();
                    }
                    $existingItem->delete();
                }
            }

            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', 'تم تحديث فاتورة المشتريات بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['purchase' => 'حدث خطأ أثناء التحديث: '.$e->getMessage()]);
        }
    }

    /**
     * حذف فاتورة المشتريات والوجبات المخزنية غير المصروفة
     */
    public function destroy(Purchase $purchase)
    {
        DB::beginTransaction();

        try {
            $purchase->load('items.stockBatch');

            foreach ($purchase->items as $item) {
                if ($item->stockBatch) {
                    $batchId = $item->stockBatch->id;
                    $hasTransfers = StockMovement::where('batch_id', $batchId)
                        ->where('type', '!=', 'purchase')
                        ->exists();

                    if ($hasTransfers) {
                        return back()->with('error', 'لا يمكن حذف الفاتورة لوجود عمليات صرف أو نقل مخزني تمت على بعض موادها.');
                    }

                    StockMovement::where('batch_id', $batchId)->delete();
                    $item->stockBatch->delete();
                }
                $item->delete();
            }

            $purchase->delete();

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'تم حذف فاتورة المشتريات بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'حدث خطأ أثناء حذف الفاتورة: '.$e->getMessage());
        }
    }

    private function generateBatchBarcode(Product $product, int $purchaseId, int $lineIndex)
    {
        $productCode = $product->code ?: "PRD-{$product->id}";
        $lineCode = str_pad($lineIndex, 2, '0', STR_PAD_LEFT);
        $barcode = "{$productCode}-P{$purchaseId}-L{$lineCode}";

        if (StockBatch::where('internal_barcode', $barcode)->exists()) {
            return $this->generateBatchBarcode($product, $purchaseId, $lineIndex + 1);
        }

        return $barcode;
    }

    private function calculateTotal($items)
    {
        return collect($items)->sum(function ($item) {
            return $item['qty'] * $item['cost_price'];
        });
    }
}
