<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
    public function create()
    {
        $products = Product::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('purchases.create', compact('products', 'suppliers'));
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

            $generatedBarcodes = [];

            foreach ($request->items as $itemData) {
                $product = Product::findOrFail($itemData['product_id']);

                // تحقق من expiry_date حسب is_perishable
                if ($product->is_perishable && empty($itemData['expiry_date'])) {
                    throw new \InvalidArgumentException("المادة {$product->name} قابلة للتلف ويجب تحديد تاريخ انتهاء.");
                }

                $internalBarcode = $this->generateBatchBarcode($product->id);

                $batch = StockBatch::create([
                    'product_id'       => $product->id,
                    'internal_barcode' => $internalBarcode,
                    'cost_price'       => $itemData['cost_price'],
                    'initial_qty'      => $itemData['qty'],
                    'current_qty'      => $itemData['qty'],
                    'expiry_date'      => $itemData['expiry_date'] ?? '2099-12-31',
                    'received_at'      => now(),
                ]);

                StockMovement::create([
                    'batch_id'         => $batch->id,
                    'from_location_id' => 1,
                    'to_location_id'   => 1,
                    'quantity'         => $itemData['qty'],
                    'type'             => 'purchase',
                    'user_id'          => Auth::id(),
                ]);

                $generatedBarcodes[] = [
                    'product_name' => $product->name,
                    'barcode'      => $internalBarcode,
                    'qty'          => $itemData['qty'],
                ];
            }

            DB::commit();

            return response()->json([
                'message'  => 'تم استلام القائمة وتوليد الباركودات بنجاح',
                'data'     => $generatedBarcodes,
                'purchase' => $purchase,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'حدث خطأ أثناء المعالجة: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generateBatchBarcode($productId)
    {
        $prefix = "B{$productId}";
        $date   = now()->format('ymd');
        $random = strtoupper(Str::random(4));

        $barcode = "{$prefix}-{$date}-{$random}";

        if (StockBatch::where('internal_barcode', $barcode)->exists()) {
            return $this->generateBatchBarcode($productId);
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
