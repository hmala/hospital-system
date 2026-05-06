@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">طباعة جميع باركودات الدفعات</h4>
            <div>
                <button onclick="window.print()" class="btn btn-primary me-2 btn-print d-print-none">
                    <i class="fas fa-print me-1"></i>طباعة
                </button>
                <a href="{{ route('barcodes.index') }}" class="btn btn-outline-secondary d-print-none">
                    <i class="fas fa-arrow-left me-1"></i>رجوع
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>إجمالي الدفعات:</strong> {{ $batches->count() }} دفعة
                @if(!empty($selectedLocation))
                    <span class="d-block mt-1">
                        <strong>المخزن:</strong> {{ $selectedLocation->name }}
                    </span>
                @endif
            </div>

            <div class="print-area">
                <div class="row gy-3">
                    @foreach($batches as $batch)
                        <div class="col-12">
                            <div class="barcode-card border p-3 text-center mx-auto">
                                <div class="product-name mb-2" title="{{ $batch->product->name }}">{{ $batch->product->name }}</div>
                                <div class="barcode-wrapper mb-2">
                                    <svg class="product-barcode" data-code="{{ $batch->original_barcode ?? $batch->internal_barcode }}"></svg>
                                </div>
                                <div class="barcode-code text-muted small">{{ $batch->original_barcode ?? $batch->internal_barcode }}</div>
                            </div>
                        </div>
                    @endforeach
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
        max-width: 600px;
        margin: 0 auto;
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
    }

    .barcode-code {
        font-size: 0.85rem;
        color: #6c757d;
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
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
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