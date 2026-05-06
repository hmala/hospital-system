<?php

namespace App\Http\Controllers;

use App\Models\StockBatch;
use App\Models\Purchase;
use Illuminate\Http\Request;

class BarcodeController extends Controller
{
    /**
     * عرض قائمة الدفعات الجاهزة لطباعة الباركود
     */
    public function index()
    {
        $batches = StockBatch::with(['product', 'location', 'purchaseItem.purchase'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('barcodes.index', compact('batches'));
    }

    /**
     * عرض صفحة طباعة باركودات دفعة واحدة
     */
    public function show(StockBatch $batch)
    {
        $batch->load(['product', 'location', 'purchaseItem.purchase.supplier']);

        return view('barcodes.show', compact('batch'));
    }

    /**
     * طباعة باركودات لكل دفعات فاتورة معينة
     */
    public function showPurchaseBarcodes(Purchase $purchase)
    {
        $purchase->load(['items.stockBatch.product', 'supplier']);

        return view('barcodes.purchase', compact('purchase'));
    }

    /**
     * صفحة طباعة جميع باركود الدفعات
     */
    public function printAll(Request $request)
    {
        $query = StockBatch::with(['product', 'location'])
            ->where('current_qty', '>', 0);

        $selectedLocation = null;
        if ($request->filled('location_id')) {
            $selectedLocation = \App\Models\Location::find($request->location_id);
            if ($selectedLocation) {
                $query->where('location_id', $selectedLocation->id);
            }
        } else {
            // إذا لم يتم تحديد location، استخدم location المستخدم إذا كان مرتبطاً
            $userLocationId = auth()->user()->location_id;
            if ($userLocationId) {
                $selectedLocation = \App\Models\Location::find($userLocationId);
                if ($selectedLocation) {
                    $query->where('location_id', $userLocationId);
                }
            }
        }

        $batches = $query->orderBy('created_at', 'desc')->get();

        return view('barcodes.print-all', compact('batches', 'selectedLocation'));
    }

    /**
     * طباعة باركودات متعددة
     */
    public function printMultiple(Request $request)
    {
        $batchIds = $request->input('batch_ids', []);
        $batches = StockBatch::with(['product', 'location'])
            ->whereIn('id', $batchIds)
            ->get();

        return view('barcodes.multiple', compact('batches'));
    }
}
