@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">طباعة باركود الدفعة</h4>
            <button onclick="window.print()" class="btn btn-primary d-print-none">
                <i class="bi bi-printer"></i> طباعة
            </button>
        </div>
        <div class="card-body p-4">
            <!-- معلومات الدفعة -->
            <div class="row mb-4 d-print-none">
                <div class="col-md-6">
                    <h6 class="text-muted">معلومات المادة</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>المادة:</th>
                            <td>{{ $batch->product->name }}</td>
                        </tr>
                        <tr>
                            <th>الكمية الحالية:</th>
                            <td>{{ $batch->current_qty }} {{ $batch->product->unit }}</td>
                        </tr>
                        <tr>
                            <th>الموقع:</th>
                            <td>{{ $batch->location->name }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">معلومات الباركود</h6>
                    <table class="table table-sm">
                        <tr>
                            <th>الباركود الداخلي:</th>
                            <td><code>{{ $batch->internal_barcode }}</code></td>
                        </tr>
                        @if($batch->supplier_barcode)
                        <tr>
                            <th>باركود المورد:</th>
                            <td><code>{{ $batch->supplier_barcode }}</code></td>
                        </tr>
                        @endif
                        @if($batch->manufacturer_lot_number)
                        <tr>
                            <th>رقم دفعة المصنع:</th>
                            <td><code>{{ $batch->manufacturer_lot_number }}</code></td>
                        </tr>
                        @endif
                        @if($batch->expiry_date)
                        <tr>
                            <th>تاريخ الانتهاء:</th>
                            <td>{{ $batch->expiry_date->format('Y-m-d') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- منطقة الطباعة -->
            <div class="print-area text-center">
                <div class="barcode-label border p-4 mx-auto" style="max-width: 400px;">
                    <h5 class="mb-3">{{ $batch->product->name }}</h5>
                    
                    <!-- QR Code -->
                    <div class="mb-3">
                        <canvas id="qrcode"></canvas>
                    </div>

                    <!-- Barcode -->
                    <div class="mb-3">
                        <svg id="barcode"></svg>
                    </div>

                    <div class="small text-muted">
                        <div><strong>الكود:</strong> {{ $batch->internal_barcode }}</div>
                        @if($batch->expiry_date)
                        <div><strong>انتهاء:</strong> {{ $batch->expiry_date->format('Y-m-d') }}</div>
                        @endif
                        @if($batch->manufacturer_lot_number)
                        <div><strong>دفعة:</strong> {{ $batch->manufacturer_lot_number }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include QRCode and JsBarcode libraries -->
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<script>
    // Generate QR Code
    const qrData = JSON.stringify({
        batch_id: {{ $batch->id }},
        product_id: {{ $batch->product_id }},
        internal_barcode: "{{ $batch->internal_barcode }}",
        product_name: "{{ $batch->product->name }}",
        expiry_date: "{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '' }}",
        @if($batch->supplier_barcode)
        supplier_barcode: "{{ $batch->supplier_barcode }}",
        @endif
        @if($batch->manufacturer_lot_number)
        lot_number: "{{ $batch->manufacturer_lot_number }}"
        @endif
    });

    QRCode.toCanvas(document.getElementById('qrcode'), qrData, {
        width: 200,
        margin: 2
    });

    // Generate Barcode
    JsBarcode("#barcode", "{{ $batch->internal_barcode }}", {
        format: "CODE128",
        width: 2,
        height: 60,
        displayValue: true,
        fontSize: 14
    });
</script>

<style>
    @media print {
        .d-print-none {
            display: none !important;
        }
        .print-area {
            margin-top: 0;
        }
        .barcode-label {
            border: 2px solid #000 !important;
            page-break-inside: avoid;
        }
    }
</style>
@endsection
