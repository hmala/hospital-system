@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- رأس الصفحة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-file-invoice me-2"></i>تفاصيل فاتورة الصيدلية: {{ $sale->invoice_number }}
            </h2>
            <p class="text-muted small mb-0">
                تاريخ الإصدار: <span class="fw-semibold">{{ $sale->created_at->format('Y-m-d h:i A') }}</span>
                | الصيدلي المسؤول: <span class="fw-semibold">{{ $sale->dispenser->name ?? ($sale->user->name ?? 'غير محدد') }}</span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('pharmacy.pos.sales.print', $sale->id) }}" target="_blank" class="btn btn-success btn-sm shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة الوصل الحراري 80mm
            </a>
            <a href="{{ route('pharmacy.pos.sales.history') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة لسجل المبيعات
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- بيانات المريض والجهة -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-user-injured me-2 text-primary"></i>بيانات المريض والطلب</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">اسم المريض:</span>
                            <span class="fw-bold text-dark">{{ $sale->patient_name ?? 'مريض مباشر' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">نوع البيع:</span>
                            <span class="fw-semibold">{{ $sale->sale_type }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">جهة التأمين:</span>
                            <span class="fw-bold text-primary">
                                @if($sale->insurance_type === 'health_insurance')
                                    هيئة الضمان الصحي الوطني
                                @elseif($sale->insurance_type === 'interior_ministry')
                                    وزارة الداخلية
                                @else
                                    نقدي (كاش)
                                @endif
                            </span>
                        </li>
                        @if($sale->healthInsuranceCategory)
                            <li class="list-group-item d-flex justify-content-between px-0">
                                <span class="text-muted">فئة الضمان:</span>
                                <span class="badge bg-light text-dark border">الفئة {{ $sale->healthInsuranceCategory->code }} - {{ $sale->healthInsuranceCategory->name }}</span>
                            </li>
                        @endif
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">نسبة الاستقطاع (Co-pay):</span>
                            <span class="fw-bold">{{ $sale->copay_percentage }}%</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">مسار الدفع:</span>
                            <span>{{ $sale->payment_route === 'pharmacy_cashier' ? 'كاشير الصيدلية' : 'الكاشير المركزي' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">حالة الدفع:</span>
                            <span class="badge {{ $sale->payment_status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                {{ $sale->payment_status }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- الملخص المالي -->
            <div class="card border-0 shadow-sm rounded-3 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-receipt me-2 text-success"></i>الخلاصة المالية</h6>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted">المبلغ الإجمالي:</span>
                        <span class="fw-bold fs-5 text-dark">{{ number_format($sale->total_amount) }} د.ع</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2 text-danger">
                        <span class="fw-bold">حصة المريض (المسددة):</span>
                        <span class="fw-bold fs-4">{{ number_format($sale->patient_share) }} د.ع</span>
                    </div>
                    @if($sale->insurance_type !== 'none')
                        <div class="d-flex justify-content-between align-items-center text-success">
                            <span class="fw-bold">حصة التأمين (مطالبة):</span>
                            <span class="fw-bold fs-5">{{ number_format($sale->insurance_share) }} د.ع</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- جدول بنود الفاتورة والوجبات المسحوب منها -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-dark mb-0"><i class="fas fa-pills me-2 text-primary"></i>الأصناف والخدمات المصروفة</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small text-muted">
                                <tr>
                                    <th>#</th>
                                    <th>الصنف / الخدمة</th>
                                    <th>الوحدة</th>
                                    <th>الوجبة المسحوب منها (FEFO)</th>
                                    <th>الكمية</th>
                                    <th>سعر الوحدة</th>
                                    <th>المجموع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $item->item_name }}</span>
                                            @if($item->dosage_instructions)
                                                <div class="text-muted small"><i class="fas fa-info-circle me-1"></i>{{ $item->dosage_instructions }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge bg-light text-dark border">{{ $item->unit_label }}</span></td>
                                        <td>
                                            @if($item->batch)
                                                <span class="font-monospace fw-bold text-primary">{{ $item->batch->batch_number }}</span>
                                                <small class="text-muted d-block">انتهاء: {{ $item->batch->expiry_date ? $item->batch->expiry_date->format('Y-m-d') : '-' }}</small>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td class="fw-bold text-center">{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price) }} د.ع</td>
                                        <td class="fw-bold text-primary">{{ number_format($item->subtotal) }} د.ع</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
