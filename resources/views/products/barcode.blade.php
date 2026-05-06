@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">طباعة باركود المنتج</h4>
            <a href="{{ route('products.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> رجوع
            </a>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">معلومات المنتج</h6>
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th>اسم المنتج:</th>
                            <td>{{ $product->name }}</td>
                        </tr>
                        <tr>
                            <th>التصنيف:</th>
                            <td>{{ $product->category }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="text-center mb-4">
                <button onclick="window.print()" class="btn btn-primary btn-lg px-5 btn-print">
                    <i class="fas fa-print me-2"></i>طباعة الباركود
                </button>
            </div>

            <hr>

            <!-- منطقة الطباعة -->
            <div class="print-area">
                <div class="barcode-card border d-flex align-items-center justify-content-between p-2 mb-2">
                    <div class="product-name flex-grow-1 text-truncate me-3" title="{{ $product->name }}">{{ $product->name }}</div>
                    <div class="barcode-wrapper">
                        <svg class="product-barcode" data-code="{{ $product->code ?? $product->id }}"></svg>
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
        .print-area, .print-area * {
            visibility: visible;
        }
        .print-area {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            width: auto;
        }
        .barcode-label {
            border: 1px solid #000 !important;
            page-break-inside: avoid;
            padding: 4px 8px !important;
            max-height: 38px;
            max-width: 550px;
        }
        .product-name {
            font-size: 0.85rem;
        }
    }
</style>
@endsection
