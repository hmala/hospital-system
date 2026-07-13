@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-invoice-dollar me-2 text-danger"></i>كشوفات حسابات الطوارئ</h2>
                <p class="text-muted mb-0">تقرير تفصيلي بالإيرادات حسب البند والخدمات المقدمة.</p>
            </div>
            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى الكاشير الرئيسية
            </a>
        </div>
    </div>

    {{-- الفلاتر --}}
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="GET" action="{{ route('cashier.emergency.statements') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate) }}">
                        </div>
                        <div class="col-md-4">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate) }}">
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-filter me-2"></i>تصفية
                            </button>
                            <a href="{{ route('cashier.emergency.statements') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-redo me-2"></i>مسح
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقات الملخص المالي للبند --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-primary">
                <div class="card-body">
                    <h6 class="mb-1 opacity-75">إجمالي الخدمات</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalServices, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-success">
                <div class="card-body">
                    <h6 class="mb-1 opacity-75">إجمالي التحاليل</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalLabs, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-info">
                <div class="card-body">
                    <h6 class="mb-1 opacity-75">إجمالي الأشعة</h6>
                    <h3 class="fw-bold mb-0">{{ number_format($totalRadiology, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-white bg-warning">
                <div class="card-body">
                    <h6 class="mb-1 opacity-75 text-dark">أجور متابعة الطبيب</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalFollowUps, 0) }} د.ع</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- إجمالي المقبوضات الكلي --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm bg-light text-center py-3">
                <h6 class="text-muted mb-1">الصافي الكلي المقبوض للطوارئ</h6>
                <h2 class="fw-bold text-danger">{{ number_format($totalCollected, 0) }} د.ع</h2>
            </div>
        </div>
    </div>

    {{-- كشف الجدول --}}
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if($paginatedStatements->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المريض</th>
                                        <th>الخدمات (د.ع)</th>
                                        <th>التحاليل (د.ع)</th>
                                        <th>الأشعة (د.ع)</th>
                                        <th>المتابعة (د.ع)</th>
                                        <th>الإجمالي المقبوض</th>
                                        <th>رقم الإيصال</th>
                                        <th>أمين الصندوق</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paginatedStatements as $item)
                                        <tr>
                                            <td>{{ $paginatedStatements->firstItem() + $loop->index }}</td>
                                            <td>{{ $item['payment']->paid_at ? $item['payment']->paid_at->format('Y-m-d H:i') : '-' }}</td>
                                            <td>
                                                <div class="fw-bold">{{ optional(optional($item['payment']->emergency)->patient->user)->name ?? optional($item['payment']->patient->user)->name ?? 'غير معروف' }}</div>
                                                <small class="text-muted">ID: #{{ $item['payment']->patient_id }}</small>
                                            </td>
                                            <td class="text-primary">{{ number_format($item['services_sum'], 0) }}</td>
                                            <td class="text-success">{{ number_format($item['labs_sum'], 0) }}</td>
                                            <td class="text-info">{{ number_format($item['radiology_sum'], 0) }}</td>
                                            <td class="text-warning fw-bold">{{ number_format($item['follow_up_sum'], 0) }}</td>
                                            <td class="fw-bold text-danger">{{ number_format($item['total'], 0) }} د.ع</td>
                                            <td><code>{{ $item['payment']->receipt_number }}</code></td>
                                            <td>{{ optional($item['payment']->cashier)->name ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $paginatedStatements->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد سجلات كشوفات مالية للطوارئ.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
