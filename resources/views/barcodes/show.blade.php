@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">طباعة باركود الدفعة</h4>
            <div>
                <button onclick="window.print()" class="btn btn-primary btn-print d-print-none">
                    <i class="fas fa-print me-1"></i>طباعة
                </button>
                <a href="{{ route('barcodes.index') }}" class="btn btn-outline-secondary d-print-none">
                    <i class="fas fa-arrow-left me-1"></i>رجوع
                </a>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row mb-4 d-print-none">
                <div class="col-md-6">
                    <h6 class="text-muted">معلومات الدفعة</h6>
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
                            <th>رقم الدفعة:</th>
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

            <div class="print-area">
                <div class="barcode-card border p-3 mb-2 text-center">
                    <div class="product-name mb-2" title="{{ $batch->product->name }}">{{ $batch->product->name }}</div>
                    <div class="barcode-wrapper mb-2">
                        <svg class="product-barcode" data-code="{{ $batch->original_barcode ?? $batch->internal_barcode }}"></svg>
                    </div>
                    <div class="barcode-code text-muted small">
                        {{ $batch->original_barcode ?? $batch->internal_barcode }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.product-barcode').forEach(function(svg) {
            const code = svg.dataset.code;
            if (!code) {
                return;
            }

            JsBarcode(svg, code, {
                format: 'CODE128',
                width: 1.1,
                height: 30,
                displayValue: false,
                margin: 2,
            });
        });
    });
</script>
@endsection

@section('styles')
<style>
    .barcode-card {
        background: white;
        border-radius: 4px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        max-height: 48px;
        max-width: 600px;
        margin: 20px auto;
    }

    .product-name {
        font-size: 0.95rem;
        font-weight: 600;
        line-height: 1.2;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .barcode-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .btn-print,
    .btn-print:focus,
    .btn-print:active,
    .btn-print.focus {
        border-radius: 0.8rem;
        outline: none !important;
        box-shadow: none !important;
    }

    @media print {
        body * {
            visibility: hidden;
        }
        .print-area,
        .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: auto;
        }
        .barcode-card {
            width: 100%;
            max-width: 550px;
            border: 1px solid #000 !important;
            box-shadow: none;
            border-radius: 3px;
            padding: 4px 8px !important;
            margin-bottom: 4px !important;
            page-break-inside: avoid;
            max-height: 38px;
        }
        .product-name {
            font-size: 0.85rem;
        }
        @page {
            margin: 0.5cm;
        }
    }
</style>
@endsection
