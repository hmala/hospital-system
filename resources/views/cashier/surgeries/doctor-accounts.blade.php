@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ترويسة الصفحة --}}
    <div class="row mb-4">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h3 fw-bold text-dark mb-1">
                    <i class="fas fa-wallet text-danger me-2"></i>حسابات الأطباء الجراحين
                </h2>
                <p class="text-muted mb-0">عرض ملخص أتعاب الجراحة، استقطاعات الباقة لصالح المستشفى، والمستحقات والأرصدة المتبقية لكل جراح.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-outline-danger rounded-pill px-3 shadow-sm">
                    <i class="fas fa-procedures me-1"></i> كاشير العمليات
                </a>
                <a href="{{ route('cashier.surgeries.paid') }}" class="btn btn-outline-success rounded-pill px-3 shadow-sm">
                    <i class="fas fa-receipt me-1"></i> سجل المدفوعات
                </a>
                <a href="{{ route('cashier.index') }}" class="btn btn-secondary rounded-pill px-3 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> الكاشير العام
                </a>
            </div>
        </div>
    </div>

    {{-- رسائل التنبيه والنجاح --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات المؤشرات المالية الإجمالية --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">إجمالي العمليات</span>
                    <h3 class="mb-0 fw-bold text-dark">{{ number_format($totals['total_surgeries'] ?? 0) }}</h3>
                    <small class="text-muted">عملية منجزة</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">الأتعاب الاسمية</span>
                    <h4 class="mb-0 fw-bold text-secondary">{{ number_format($totals['total_gross'] ?? 0, 0) }}</h4>
                    <small class="text-muted">د.ع قبل الاستقطاع</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">استقطاعات الباقة</span>
                    <h4 class="mb-0 fw-bold text-danger">{{ number_format($totals['total_deductions'] ?? 0, 0) }}</h4>
                    <small class="text-danger">د.ع لصالح المستشفى</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">صافي الاستحقاق</span>
                    <h4 class="mb-0 fw-bold text-primary">{{ number_format($totals['total_net'] ?? 0, 0) }}</h4>
                    <small class="text-primary">د.ع للجراحين</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <span class="text-muted small fw-bold d-block mb-1">إجمالي المصروف</span>
                    <h4 class="mb-0 fw-bold text-success">{{ number_format($totals['total_paid'] ?? 0, 0) }}</h4>
                    <small class="text-success">د.ع مسدد فعلياً</small>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                <div class="card-body p-3">
                    <span class="text-white-50 small fw-bold d-block mb-1">الرصيد المتبقي</span>
                    <h4 class="mb-0 fw-bold">{{ number_format($totals['total_balance'] ?? 0, 0) }}</h4>
                    <small class="text-white-50">د.ع بذمة المستشفى</small>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول ملخص حسابات الأطباء الجراحين --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fas fa-user-md text-danger"></i>
                <span>قائمة الأطباء الجراحين وأرصدتهم</span>
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                عدد الجراحين: {{ $doctorsSummary->count() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th class="ps-4">الطبيب الجراح</th>
                            <th>التخصص والقسم</th>
                            <th class="text-center">العمليات</th>
                            <th class="text-end">أتعاب الجراحة الاسمية</th>
                            <th class="text-end">استقطاعات الباقة (المستشفى)</th>
                            <th class="text-end">صافي المستحق</th>
                            <th class="text-end">إجمالي المصروف</th>
                            <th class="text-end">الرصيد المتبقي</th>
                            <th class="text-center">آخر صرف</th>
                            <th class="text-center pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctorsSummary as $item)
                            @php
                                $doc = $item->doctor;
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">د. {{ $doc->user?->name ?? 'غير محدد' }}</div>
                                    <small class="text-muted font-monospace">ID: #{{ $doc->id }}</small>
                                </td>
                                <td>
                                    <div class="fw-bold text-secondary">{{ $doc->specialization ?? 'جراحة' }}</div>
                                    <small class="text-muted">{{ $doc->department?->name ?? 'قسم الجراحة' }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                                        {{ $item->surgeries_count }} عملية
                                    </span>
                                </td>
                                <td class="text-end font-monospace text-secondary fw-bold">
                                    {{ number_format($item->gross_fees, 0) }} <small class="text-muted">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-danger fw-bold">
                                    @if($item->package_deductions > 0)
                                        -{{ number_format($item->package_deductions, 0) }} <small class="text-muted">د.ع</small>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end font-monospace text-dark fw-bold">
                                    {{ number_format($item->net_earned, 0) }} <small class="text-muted">د.ع</small>
                                </td>
                                <td class="text-end font-monospace text-success fw-bold">
                                    {{ number_format($item->total_paid, 0) }} <small class="text-muted">د.ع</small>
                                </td>
                                <td class="text-end font-monospace fs-6 fw-bold {{ $item->balance > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($item->balance, 0) }} <small class="text-muted">د.ع</small>
                                </td>
                                <td class="text-center small text-muted">
                                    {{ $item->last_paid_at ? \Carbon\Carbon::parse($item->last_paid_at)->format('Y-m-d') : '-' }}
                                </td>
                                <td class="text-center pe-4">
                                    <a href="{{ route('cashier.surgeries.doctor-account', $doc) }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                                        <i class="fas fa-eye me-1"></i> كشف الحساب والتفاصيل
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-md fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-0 fw-bold">لا يوجد أطباء جراحين مسجلين في النظام حالياً.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
