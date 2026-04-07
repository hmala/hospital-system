@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-1">طباعة باركودات الفاتورة</h4>
                <p class="text-muted mb-0 small">فاتورة رقم: {{ $purchase->invoice_number }} - المورد: {{ $purchase->supplier->name }}</p>
            </div>
            <button onclick="window.print()" class="btn btn-primary d-print-none">
                <i class="bi bi-printer"></i> طباعة الكل
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @foreach($purchase->items as $item)
                    @if($item->stockBatch)
                    <div class="col-md-6 col-lg-4">
                        <div class="barcode-label border p-3 text-center" style="page-break-inside: avoid;">
                            <h6 class="mb-2">{{ $item->product->name }}</h6>
                            
                            <!-- QR Code -->
                            <div class="mb-2">
                                <canvas class="qrcode" data-batch-id="{{ $item->stockBatch->id }}" 
                                        data-barcode="{{ $item->stockBatch->internal_barcode }}"
                                        data-product="{{ $item->product->name }}"
                                        data-expiry="{{ $item->stockBatch->expiry_date ? $item->stockBatch->expiry_date->format('Y-m-d') : '' }}"
                                        data-supplier-barcode="{{ $item->stockBatch->supplier_barcode ?? '' }}"
                                        data-lot="{{ $item->stockBatch->manufacturer_lot_number ?? '' }}"></canvas>
                            </div>

                            <!-- Barcode -->
                            <div class="mb-2">
                                <svg class="barcode" data-code="{{ $item->stockBatch->internal_barcode }}"></svg>
                            </div>

                            <div class="small text-muted">
                                <div><strong>الكود:</strong> {{ $item->stockBatch->internal_barcode }}</div>
                                <div><strong>الكمية:</strong> {{ $item->qty }} {{ $item->product->unit }}</div>
                                @if($item->stockBatch->expiry_date)
                                <div><strong>انتهاء:</strong> {{ $item->stockBatch->expiry_date->format('Y-m-d') }}</div>
                                @endif
                                @if($item->stockBatch->manufacturer_lot_number)
                                <div><strong>دفعة:</strong> {{ $item->stockBatch->manufacturer_lot_number }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Include QRCode and JsBarcode libraries -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Generate QR Codes
        document.querySelectorAll('.qrcode').forEach(canvas => {
            const qrData = JSON.stringify({
                batch_id: canvas.dataset.batchId,
                internal_barcode: canvas.dataset.barcode,
                product_name: canvas.dataset.product,
                expiry_date: canvas.dataset.expiry,
                supplier_barcode: canvas.dataset.supplierBarcode,
                lot_number: canvas.dataset.lot
            });

            QRCode.toCanvas(canvas, qrData, {
                width: 150,
                margin: 1
            });
        });

        // Generate Barcodes
        document.querySelectorAll('.barcode').forEach(svg => {
            JsBarcode(svg, svg.dataset.code, {
                format: "CODE128",
                width: 1.5,
                height: 50,
                displayValue: true,
                fontSize: 12
            });
        });
    });
</script>

<style>
    @media print {
        .d-print-none {
            display: none !important;
        }
        .barcode-label {
            border: 2px solid #000 !important;
            page-break-inside: avoid;
            margin-bottom: 10px;
        }
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
@endsection
