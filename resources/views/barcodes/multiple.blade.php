@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">طباعة باركودات متعددة</h4>
            <button onclick="window.print()" class="btn btn-primary d-print-none">
                <i class="bi bi-printer"></i> طباعة ({{ $batches->count() }})
            </button>
        </div>
        <div class="card-body p-4">
            <div class="row g-3">
                @foreach($batches as $batch)
                <div class="col-md-6 col-lg-4">
                    <div class="barcode-label border p-3 text-center" style="page-break-inside: avoid;">
                        <h6 class="mb-2">{{ $batch->product->name }}</h6>
                        
                        <!-- QR Code -->
                        <div class="mb-2">
                            <canvas class="qrcode" data-batch-id="{{ $batch->id }}" 
                                    data-barcode="{{ $batch->internal_barcode }}"
                                    data-product="{{ $batch->product->name }}"
                                    data-location="{{ $batch->location->name }}"
                                    data-expiry="{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '' }}"
                                    data-supplier-barcode="{{ $batch->supplier_barcode ?? '' }}"
                                    data-lot="{{ $batch->manufacturer_lot_number ?? '' }}"></canvas>
                        </div>

                        <!-- Barcode -->
                        <div class="mb-2">
                            <svg class="barcode" data-code="{{ $batch->internal_barcode }}"></svg>
                        </div>

                        <div class="small text-muted">
                            <div><strong>الكود:</strong> {{ $batch->internal_barcode }}</div>
                            <div><strong>الموقع:</strong> {{ $batch->location->name }}</div>
                            <div><strong>الكمية:</strong> {{ $batch->current_qty }} {{ $batch->product->unit }}</div>
                            @if($batch->expiry_date)
                            <div><strong>انتهاء:</strong> {{ $batch->expiry_date->format('Y-m-d') }}</div>
                            @endif
                            @if($batch->manufacturer_lot_number)
                            <div><strong>دفعة:</strong> {{ $batch->manufacturer_lot_number }}</div>
                            @endif
                        </div>
                    </div>
                </div>
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
                location: canvas.dataset.location,
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
