<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        return view('purchases.index', compact('purchases'));
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
            'supplier_id'    => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|unique:purchases,invoice_number',
            'items'          => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty'        => 'required|integer|min:1',
            'items.*.cost_price' => 'required|numeric|min:0',
            'items.*.expiry_date'=> 'nullable|date',
        ]);

        DB::beginTransaction();

        try {
            $purchase = Purchase::create([
                'supplier_id'    => $request->supplier_id,
                'invoice_number' => $request->invoice_number,
                'total_amount'   => $this->calculateTotal($request->items),
                'received_at'    => now(),
                'user_id'        => Auth::id(),
            ]);

            $lineIndex = 1;
            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);

                if ($product->is_perishable && empty($itemData['expiry_date'])) {
                    throw new \InvalidArgumentException("المادة {$product->name} قابلة للتلف ويجب تحديد تاريخ انتهاء.");
                }

                $subtotal = $itemData['qty'] * $itemData['cost_price'];

                $purchaseItem = $purchase->items()->create([
                    'product_id'  => $product->id,
                    'qty'         => $itemData['qty'],
                    'unit_cost'   => $itemData['cost_price'],
                    'subtotal'    => $subtotal,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);

                $internalBarcode = $this->generateBatchBarcode($product, $purchase->id, $lineIndex);

                $mainLocation = Location::firstOrCreate([
                    'name' => 'المخزن الرئيسي',
                    'type' => 'main',
                ]);

                $batch = StockBatch::create([
                    'product_id'       => $product->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'location_id'      => $mainLocation->id,
                    'internal_barcode' => $internalBarcode,
                    'original_barcode' => $internalBarcode,
                    'cost_price'       => $itemData['cost_price'],
                    'initial_qty'      => $itemData['qty'],
                    'current_qty'      => $itemData['qty'],
                    'expiry_date'      => $itemData['expiry_date'] ?? null,
                    'received_at'      => now(),
                    'original_received_at' => now(),
                ]);

                StockMovement::create([
                    'batch_id'         => $batch->id,
                    'from_location_id' => 1,
                    'to_location_id'   => 1,
                    'quantity'         => $itemData['qty'],
                    'type'             => 'purchase',
                    'user_id'          => Auth::id(),
                ]);

                $lineIndex++;
            }

            DB::commit();

            return redirect()->route('purchases.show', $purchase)->with('success', 'تم حفظ الفاتورة بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()->withErrors(['purchase' => 'حدث خطأ أثناء المعالجة: ' . $e->getMessage()]);
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
