@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-chart-pie me-2 text-danger"></i>
                        تقرير المصروفات
                    </h2>
                    <p class="text-muted">من {{ $fromDate->format('Y/m/d') }} إلى {{ $toDate->format('Y/m/d') }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('accounting.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i> لوحة التحكم
                    </a>
                    <a href="{{ route('accounting.reports.revenue') }}" class="btn btn-outline-success">
                        <i class="fas fa-chart-bar me-1"></i> تقرير الإيرادات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- فلتر التاريخ -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('accounting.reports.expenses') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold">من تاريخ</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date', $fromDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">إلى تاريخ</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date', $toDate->format('Y-m-d')) }}">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-danger w-100">
                        <i class="fas fa-search me-1"></i> عرض التقرير
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- إجمالي المصروفات -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-danger text-white">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-receipt fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">إجمالي المصروفات الموافق عليها</h5>
                        <h2 class="mb-0">{{ number_format($totalExpenses, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="text-muted small mb-1">عدد المصروفات</div>
                    <div class="fs-2 fw-bold text-danger">{{ $dailyExpenses->sum('count') }}</div>
                    <div class="text-muted small">معاملة</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="text-muted small mb-1">متوسط المصروف اليومي</div>
                    @php
                        $days = $fromDate->diffInDays($toDate) + 1;
                        $avgDaily = $days > 0 ? $totalExpenses / $days : 0;
                    @endphp
                    <div class="fs-2 fw-bold text-warning">{{ number_format($avgDaily, 0) }}</div>
                    <div class="text-muted small">دينار / يوم</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- المصروفات حسب الفئة -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-tags me-2 text-danger"></i>المصروفات حسب الفئة</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>الفئة</th>
                                    <th>العدد</th>
                                    <th>الإجمالي</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expensesByCategory as $row)
                                @php $pct = $totalExpenses > 0 ? round(($row->total / $totalExpenses) * 100, 1) : 0; @endphp
                                <tr>
                                    <td><span class="badge bg-secondary">{{ $categoryNames[$row->category] ?? $row->category }}</span></td>
                                    <td>{{ $row->count }}</td>
                                    <td class="fw-bold text-danger">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height:8px;">
                                                <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small>{{ $pct }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">لا توجد مصروفات</td></tr>
                                @endforelse
                            </tbody>
                            @if($expensesByCategory->isNotEmpty())
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td>الإجمالي</td>
                                    <td>{{ $expensesByCategory->sum('count') }}</td>
                                    <td class="text-danger">{{ number_format($totalExpenses, 0) }} د.ع</td>
                                    <td>100%</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- المصروفات حسب طريقة الدفع -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-credit-card me-2 text-danger"></i>المصروفات حسب طريقة الدفع</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>طريقة الدفع</th>
                                    <th>العدد</th>
                                    <th>الإجمالي</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expensesByMethod as $row)
                                @php $pct = $totalExpenses > 0 ? round(($row->total / $totalExpenses) * 100, 1) : 0; @endphp
                                <tr>
                                    <td><span class="badge bg-primary">{{ $methodNames[$row->payment_method] ?? $row->payment_method }}</span></td>
                                    <td>{{ $row->count }}</td>
                                    <td class="fw-bold text-danger">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-fill" style="height:8px;">
                                                <div class="progress-bar bg-danger" style="width:{{ $pct }}%"></div>
                                            </div>
                                            <small>{{ $pct }}%</small>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">لا توجد مصروفات</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المصروفات اليومية -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-day me-2 text-danger"></i>المصروفات اليومية</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>عدد المصروفات</th>
                            <th>الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($dailyExpenses as $row)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($row->expense_date)->format('Y/m/d - l') }}</td>
                            <td>{{ $row->count }}</td>
                            <td class="fw-bold text-danger">{{ number_format($row->total, 0) }} <small class="text-muted">د.ع</small></td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-center text-muted">لا توجد بيانات</td></tr>
                        @endforelse
                    </tbody>
                    @if($dailyExpenses->isNotEmpty())
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td>الإجمالي</td>
                            <td>{{ $dailyExpenses->sum('count') }}</td>
                            <td class="text-danger">{{ number_format($totalExpenses, 0) }} د.ع</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
