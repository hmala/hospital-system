@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-chart-bar me-2 text-success"></i>
                        تقرير الإيرادات
                    </h2>
                    <p class="text-muted">من {{ $fromDate->format('Y/m/d') }} إلى {{ $toDate->format('Y/m/d') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i> لوحة التحكم
                    </a>
                    <a href="{{ route('accounting.reports.expenses') }}" class="btn btn-outline-danger">
                        <i class="fas fa-chart-pie me-1"></i> تقرير المصروفات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- فلتر التاريخ -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accounting.reports.revenue') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date', $fromDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date', $toDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ملخص مالي -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-coins fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">إجمالي الإيرادات</h5>
                        <h2 class="mb-0">{{ number_format($totalRevenue, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-receipt fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">إجمالي المصروفات</h5>
                        <h2 class="mb-0">{{ number_format($totalExpenses, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card {{ $netProfit >= 0 ? 'bg-primary' : 'bg-warning' }} text-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-balance-scale fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">صافي الربح</h5>
                        <h2 class="mb-0">{{ number_format($netProfit, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- الإيرادات حسب النوع -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>الإيرادات حسب نوع الخدمة</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>نوع الخدمة</th>
                                    <th>عدد المعاملات</th>
                                    <th>إجمالي الإيراد</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueByType as $row)
                                @php $pct = $totalRevenue > 0 ? round(($row->total / $totalRevenue) * 100, 1) : 0; @endphp
                                <tr>
                                    <td><span class="badge bg-info">{{ $paymentTypeNames[$row->payment_type] ?? $row->payment_type }}</span></td>
                                    <td>{{ $row->count }}</td>
                                    <td class="fw-bold text-success">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height:8px;">
                                                <div class="progress-bar bg-success" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small>{{ $pct }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">لا توجد إيرادات</td></tr>
                                @endforelse
                            </tbody>
                            @if($revenueByType->isNotEmpty())
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>الإجمالي</td>
                                    <td>{{ $revenueByType->sum('count') }}</td>
                                    <td class="text-success">{{ number_format($totalRevenue, 0) }} د.ع</td>
                                    <td>100%</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- الإيرادات حسب طريقة الدفع -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2 text-primary"></i>الإيرادات حسب طريقة الدفع</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>طريقة الدفع</th>
                                    <th>عدد المعاملات</th>
                                    <th>إجمالي الإيراد</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueByMethod as $row)
                                @php $pct = $totalRevenue > 0 ? round(($row->total / $totalRevenue) * 100, 1) : 0; @endphp
                                <tr>
                                    <td><span class="badge bg-primary">{{ $paymentMethodNames[$row->payment_method] ?? $row->payment_method }}</span></td>
                                    <td>{{ $row->count }}</td>
                                    <td class="fw-bold text-success">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height:8px;">
                                                <div class="progress-bar bg-primary" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small>{{ $pct }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">لا توجد إيرادات</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الإيرادات اليومية -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-day me-2 text-success"></i>الإيرادات اليومية</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>عدد المعاملات</th>
                            <th>إجمالي الإيراد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyRevenue as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->date)->format('Y/m/d - l') }}</td>
                            <td>{{ $row->count }}</td>
                            <td class="fw-bold text-success">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
                        @endforelse
                    </tbody>
                    @if($dailyRevenue->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>الإجمالي</td>
                            <td>{{ $dailyRevenue->sum('count') }}</td>
                            <td class="text-success">{{ number_format($totalRevenue, 0) }} د.ع</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
