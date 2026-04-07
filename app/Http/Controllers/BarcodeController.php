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
