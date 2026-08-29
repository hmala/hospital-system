@extends('layouts.app')

@section('content')
<div class="container-fluid">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-calculator me-2 text-primary"></i>
                        لوحة نظام الحسابات
                    </h2>
                    <p class="text-muted">نظرة عامة على الوضع المالي للمستشفى</p>
                </div>
                <div class="d-flex gap-2">
                    @can('view accounting reports')
                    <a href="{{ route('accounting.reports.revenue') }}" class="btn btn-outline-success">
                        <i class="fas fa-chart-bar me-1"></i> تقرير الإيرادات
                    </a>
                    <a href="{{ route('accounting.reports.expenses') }}" class="btn btn-outline-danger">
                        <i class="fas fa-chart-pie me-1"></i> تقرير المصروفات
                    </a>
                    @endcan
                    @can('create expenses')
                    <a href="{{ route('accounting.expenses.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i> إضافة مصروف
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- بطاقات الملخص المالي - اليوم -->
    <h5 class="fw-bold text-muted mb-3"><i class="fas fa-calendar-day me-1"></i> إحصائيات اليوم</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card stat-card bg-success text-white h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-arrow-up fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">إيرادات اليوم</h5>
                        <h2 class="mb-0">{{ number_format($todayRevenue, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card bg-danger text-white h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-arrow-down fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">مصروفات اليوم</h5>
                        <h2 class="mb-0">{{ number_format($todayExpenses, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card {{ $todayNet >= 0 ? 'bg-primary' : 'bg-warning' }} text-white h-100">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-circle bg-white bg-opacity-25 p-3">
                        <i class="fas fa-balance-scale fa-2x text-white"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">صافي اليوم</h5>
                        <h2 class="mb-0">{{ number_format($todayNet, 0) }}</h2>
                        <small>دينار</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقات الملخص المالي - الشهر -->
    <h5 class="fw-bold text-muted mb-3"><i class="fas fa-calendar-alt me-1"></i> إحصائيات الشهر الحالي</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-coins fa-2x text-success"></i>
                        </div>
                        <div>
                            <div class="text-muted small">إيرادات الشهر</div>
                            <div class="fs-4 fw-bold text-success">{{ number_format($monthRevenue, 0) }}</div>
                            <div class="text-muted small">دينار</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-danger h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                            <i class="fas fa-receipt fa-2x text-danger"></i>
                        </div>
                        <div>
                            <div class="text-muted small">مصروفات الشهر</div>
                            <div class="fs-4 fw-bold text-danger">{{ number_format($monthExpenses, 0) }}</div>
                            <div class="text-muted small">دينار</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-chart-line fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="text-muted small">صافي الشهر</div>
                            <div class="fs-4 fw-bold {{ $monthNet >= 0 ? 'text-primary' : 'text-warning' }}">{{ number_format($monthNet, 0) }}</div>
                            <div class="text-muted small">دينار</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- بطاقات الملخص المالي - السنة -->
    <h5 class="fw-bold text-muted mb-3"><i class="fas fa-calendar me-1"></i> إحصائيات السنة الحالية</h5>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">إجمالي إيرادات السنة</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($yearRevenue, 0) }} <small class="fs-6">د.ع</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">إجمالي مصروفات السنة</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($yearExpenses, 0) }} <small class="fs-6">د.ع</small></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">صافي ربح السنة</div>
                    <div class="fs-3 fw-bold {{ $yearNet >= 0 ? 'text-primary' : 'text-warning' }}">{{ number_format($yearNet, 0) }} <small class="fs-6">د.ع</small></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- مخطط الإيرادات والمصروفات -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2 text-primary"></i>الإيرادات والمصروفات - آخر 30 يوم</h5>
                </div>
                <div class="card-body">
                    <canvas id="financialChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- توزيع الإيرادات حسب النوع -->
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-success"></i>الإيرادات حسب النوع (هذا الشهر)</h5>
                </div>
                <div class="card-body">
                    @php
                        $typeColors = [
                            'appointment' => '#3b82f6',
                            'lab'         => '#8b5cf6',
                            'radiology'   => '#06b6d4',
                            'pharmacy'    => '#10b981',
                            'surgery'     => '#f59e0b',
                            'emergency'   => '#ef4444',
                            'other'       => '#6b7280',
                        ];
                        $typeNames = \App\Models\Payment::PAYMENT_TYPES;
                    @endphp
                    @if($revenueByType->isEmpty())
                        <p class="text-center text-muted py-4">لا توجد إيرادات هذا الشهر</p>
                    @else
                        @foreach($revenueByType as $type => $data)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="small fw-bold">{{ $typeNames[$type] ?? $type }}</span>
                                <span class="small text-muted">{{ number_format($data->total, 0) }} د.ع</span>
                            </div>
                            @php
                                $monthTotal = $revenueByType->sum('total');
                                $pct = $monthTotal > 0 ? round(($data->total / $monthTotal) * 100) : 0;
                            @endphp
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" style="width: {{ $pct }}%; background-color: {{ $typeColors[$type] ?? '#6b7280' }};"></div>
                            </div>
                        </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- آخر المدفوعات -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-money-bill-wave me-2 text-success"></i>آخر الإيرادات</h5>
                    @can('view accounting reports')
                    <a href="{{ route('accounting.reports.revenue') }}" class="btn btn-sm btn-outline-success">عرض الكل</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>المريض</th>
                                    <th>النوع</th>
                                    <th>المبلغ</th>
                                    <th>التاريخ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestPayments as $payment)
                                <tr>
                                    <td>
                                        @if($payment->patient)
                                            {{ $payment->patient->user->name ?? 'غير محدد' }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ \App\Models\Payment::PAYMENT_TYPES[$payment->payment_type] ?? $payment->payment_type }}</span>
                                    </td>
                                    <td class="fw-bold text-success">{{ number_format($payment->amount, 0) }}</td>
                                    <td class="text-muted small">{{ $payment->paid_at ? $payment->paid_at->format('d/m H:i') : '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted">لا توجد مدفوعات</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- آخر المصروفات -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-file-invoice me-2 text-danger"></i>آخر المصروفات
                        @if($pendingExpensesCount > 0)
                            <span class="badge bg-warning ms-2">{{ $pendingExpensesCount }} معلق</span>
                        @endif
                    </h5>
                    @can('view accounting')
                    <a href="{{ route('accounting.expenses.index') }}" class="btn btn-sm btn-outline-danger">عرض الكل</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>العنوان</th>
                                    <th>الفئة</th>
                                    <th>المبلغ</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($latestExpenses as $expense)
                                <tr>
                                    <td>{{ Str::limit($expense->title, 25) }}</td>
                                    <td><span class="badge bg-secondary">{{ \App\Models\Expense::CATEGORIES[$expense->category] ?? $expense->category }}</span></td>
                                    <td class="fw-bold text-danger">{{ number_format($expense->amount, 0) }}</td>
                                    <td>
                                        @if($expense->status === 'approved')
                                            <span class="badge bg-success">موافق</span>
                                        @elseif($expense->status === 'pending')
                                            <span class="badge bg-warning">معلق</span>
                                        @else
                                            <span class="badge bg-danger">مرفوض</span>
                                        @endif
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

</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    const ctx = document.getElementById('financialChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($chartLabels),
            datasets: [
                {
                    label: 'الإيرادات',
                    data: @json($chartRevenue),
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16,185,129,0.1)',
                    fill: true,
                    tension: 0.4,
                },
                {
                    label: 'المصروفات',
                    data: @json($chartExpenses),
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239,68,68,0.1)',
                    fill: true,
                    tension: 0.4,
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: value => value.toLocaleString('ar-IQ') + ' د.ع'
                    }
                }
            }
        }
    });
</script>
@endsection
