@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-invoice-dollar me-2 text-primary"></i>كشوفات الحسابات</h2>
                <p class="text-muted mb-0">واجهة جديدة لعرض كشوفات المحاسبة المالية والتقارير الخاصة.</p>
            </div>
          
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <strong>ملاحظة:</strong> هذه الصفحة جاهزة لعرض الكشوفات التي تريد إضافتها لاحقًا.
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <form method="GET" action="{{ route('cashier.statements') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="from_date" class="form-label">من تاريخ</label>
                    <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">إلى تاريخ</label>
                    <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-4 d-flex flex-column gap-2 align-items-end">
                    <button type="submit" class="btn btn-primary w-100">عرض</button>
                    <a href="{{ route('cashier.statements.export', request()->only(['from_date', 'to_date'])) }}" class="btn btn-success w-100">
                        <i class="fas fa-file-excel me-2"></i>تصدير إكسل
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>كشف أطباء الاستشارية</h5>
                    <div>
                        <span class="badge bg-light text-dark me-2">عدد السجلات: {{ $totals['count'] ?? 0 }}</span>
                        <span class="badge bg-light text-dark">إجمالي المدفوع: {{ number_format($totals['total_amount'] ?? 0, 0) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ</th>
                                    <th>عدد الفحوصات</th>
                                    <th>نوع الخدمة</th>
                                    <th>اسم الطبيب</th>
                                    <th>اسم المريض</th>
                                    <th>المبلغ المسدد</th>
                                    <th>حصة الطبيب</th>
                                    <th>ربح المستشفى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenues as $revenue)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ optional($revenue->appointment)->appointment_date ? \Carbon\Carbon::parse($revenue->appointment->appointment_date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $revenue->examination_count ?? 1 }}</td>
                                        <td>{{ optional($revenue->serviceType)->name ?? '-' }}</td>
                                        <td>{{ optional($revenue->doctor->user)->name ?? '-' }}</td>
                                        <td>{{ optional($revenue->appointment->patient->user)->name ?? optional($revenue->patient->user)->name ?? '-' }}</td>
                                        <td>{{ number_format($revenue->total_amount, 0) }}</td>
                                        <td>{{ number_format($revenue->doctor_share, 0) }}</td>
                                        <td>{{ number_format($revenue->hospital_share, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">لا توجد بيانات لهذا الكشف.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(!empty($revenues) && $revenues->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="6" class="text-end">الإجمالي</th>
                                        <th>{{ number_format($totals['total_amount'] ?? 0, 0) }}</th>
                                        <th>{{ number_format($totals['total_doctor_share'] ?? 0, 0) }}</th>
                                        <th>{{ number_format($totals['total_hospital_share'] ?? 0, 0) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>الملخص حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark me-2">عدد الأطباء: {{ $totals['grouped_count'] ?? 0 }}</span>
                        <span class="badge bg-light text-dark">إجمالي المدفوع: {{ number_format($totals['grouped_total_amount'] ?? 0, 0) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ</th>
                                    <th>عدد الفحوصات</th>
                                    <th>نوع الخدمة</th>
                                    <th>اسم الطبيب</th>
                                    <th>المبلغ المسدد</th>
                                    <th>حصة الطبيب</th>
                                    <th>ربح المستشفى</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupedRevenues as $group)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $group->first_appointment_date ? \Carbon\Carbon::parse($group->first_appointment_date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $group->examination_count }}</td>
                                        <td>جميع الخدمات</td>
                                        <td>{{ optional($group->doctor->user)->name ?? '-' }}</td>
                                        <td>{{ number_format($group->total_amount, 0) }}</td>
                                        <td>{{ number_format($group->doctor_share, 0) }}</td>
                                        <td>{{ number_format($group->hospital_share, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-5">لا توجد بيانات مجمعة حسب الطبيب.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(!empty($groupedRevenues) && $groupedRevenues->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="5" class="text-end">الإجمالي</th>
                                        <th>{{ number_format($totals['grouped_total_amount'] ?? 0, 0) }}</th>
                                        <th>{{ number_format($totals['grouped_total_doctor_share'] ?? 0, 0) }}</th>
                                        <th>{{ number_format($totals['grouped_total_hospital_share'] ?? 0, 0) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>الملخص الشهري حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark me-2">عدد الأطباء: {{ $totals['monthly_grouped_count'] ?? 0 }}</span>
                        <span class="badge bg-light text-dark">إجمالي الفحوصات: {{ $totals['monthly_examination_count'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطبيب</th>
                                    @foreach($monthNames as $monthName)
                                        <th>{{ $monthName }}</th>
                                    @endforeach
                                    <th>الإجمالي</th>
                                    <th>النسبة %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthlyDoctorSummary as $item)
                                    @php $maxMonthValue = max($item->months) ?: 0; @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ optional($item->doctor->user)->name ?? '-' }}</td>
                                        @foreach($item->months as $month => $count)
                                            @php $trendClass = $item->month_trends[$month] ?? 'flat'; @endphp
                                            <td>
                                                <div class="trend-cell trend-{{ $trendClass }}">
                                                    <div class="trend-bar trend-bar-{{ $trendClass }}" style="width: {{ $maxMonthValue ? round($count / $maxMonthValue * 100, 2) : 0 }}%;"></div>
                                                    <span class="trend-value">
                                                        @if($trendClass === 'up')
                                                            <span class="trend-arrow">↑</span>
                                                        @elseif($trendClass === 'down')
                                                            <span class="trend-arrow">↓</span>
                                                        @else
                                                            <span class="trend-arrow">—</span>
                                                        @endif
                                                        {{ number_format($count, 0) }}
                                                    </span>
                                                </div>
                                            </td>
                                        @endforeach
                                        <td>{{ number_format($item->total, 0) }}</td>
                                        <td class="percent-change-cell {{ $item->percent_change >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $item->percent_change >= 0 ? '+' : '' }}{{ number_format($item->percent_change, 2) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center text-muted py-5">لا توجد بيانات شهرية حسب الطبيب.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(!empty($monthlyDoctorSummary) && $monthlyDoctorSummary->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">الإجمالي</th>
                                        @foreach($monthlyTotals as $count)
                                            <th>{{ number_format($count, 0) }}</th>
                                        @endforeach
                                        <th>{{ number_format($totals['monthly_examination_count'] ?? 0, 0) }}</th>
                                        <th>-</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('styles')
<style>
    .trend-cell {
        position: relative;
        min-width: 90px;
        min-height: 32px;
        padding: 0.2rem 0.35rem;
        border-radius: 0.65rem;
        background: #f8f9fa;
        overflow: hidden;
    }

    .trend-bar {
        position: absolute;
        top: 50%;
        left: 0;
        height: 10px;
        border-radius: 999px;
        background: rgba(108, 117, 125, 0.2);
        transform: translateY(-50%);
    }

    .trend-bar.trend-bar-up {
        background: linear-gradient(90deg, rgba(25, 135, 84, 0.95), rgba(75, 192, 105, 0.85));
    }

    .trend-bar.trend-bar-down {
        background: linear-gradient(90deg, rgba(220, 53, 69, 0.95), rgba(255, 159, 164, 0.85));
    }

    .trend-bar.trend-bar-flat {
        background: linear-gradient(90deg, rgba(255, 193, 7, 0.95), rgba(255, 236, 179, 0.85));
    }

    .trend-value {
        position: relative;
        z-index: 1;
        font-size: 0.9rem;
        font-weight: 600;
        color: #212529;
    }

    .trend-cell.up .trend-arrow,
    .trend-cell.up .trend-value {
        color: #198754;
    }

    .trend-cell.down .trend-arrow,
    .trend-cell.down .trend-value {
        color: #dc3545;
    }

    .trend-cell.flat .trend-arrow,
    .trend-cell.flat .trend-value {
        color: #d39e00;
    }

    .percent-change-cell.text-success {
        font-weight: 700;
        color: #198754 !important;
    }

    .percent-change-cell.text-danger {
        font-weight: 700;
        color: #dc3545 !important;
    }
</style>
@endsection
