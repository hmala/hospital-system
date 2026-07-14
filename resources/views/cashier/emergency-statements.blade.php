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

    {{-- الملخص حسب الطبيب --}}
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-user-md me-2"></i>ملخص الطوارئ حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark me-2">عدد الأطباء: {{ $totals['grouped_count'] ?? 0 }}</span>
                        <span class="badge bg-light text-dark">إجمالي المقبوض: {{ number_format($totals['grouped_total_amount'] ?? 0, 0) }} د.ع</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>أول تاريخ حركة</th>
                                    <th>عدد الحالات</th>
                                    <th>اسم الطبيب</th>
                                    <th>المبلغ المسدد</th>
                                    <th>حصة الطبيب (متابعة)</th>
                                    <th>ربح المستشفى (الصافي)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($groupedRevenues as $group)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $group->first_appointment_date ? \Carbon\Carbon::parse($group->first_appointment_date)->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $group->examination_count }}</td>
                                        <td>د. {{ optional($group->doctor->user)->name ?? '-' }}</td>
                                        <td class="fw-bold">{{ number_format($group->total_amount, 0) }} د.ع</td>
                                        <td class="text-warning fw-bold">{{ number_format($group->doctor_share, 0) }} د.ع</td>
                                        <td class="text-success fw-bold">{{ number_format($group->hospital_share, 0) }} د.ع</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-5">لا توجد بيانات مجمعة حسب الطبيب للطوارئ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if(!empty($groupedRevenues) && $groupedRevenues->count() > 0)
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">الإجمالي</th>
                                        <th>{{ number_format($totals['grouped_total_amount'] ?? 0, 0) }} د.ع</th>
                                        <th>{{ number_format($totals['grouped_total_doctor_share'] ?? 0, 0) }} د.ع</th>
                                        <th>{{ number_format($totals['grouped_total_hospital_share'] ?? 0, 0) }} د.ع</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الحالات المحولة للعمليات حسب الطبيب --}}
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>الحالات المحولة للعمليات حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark">إجمالي المحول للعمليات: {{ $totals['total_referred_surgeries'] ?? 0 }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطبيب</th>
                                    <th>إجمالي حالات الطوارئ المعاينة</th>
                                    <th>الحالات المحولة للعمليات</th>
                                    <th>نسبة التحويل (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($surgeryReferrals as $referral)
                                    @php
                                        $percent = $referral->total_cases > 0 ? round(($referral->referred_count / $referral->total_cases) * 100, 2) : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-end"><strong>د. {{ optional($referral->doctor->user)->name ?? 'طبيب طوارئ' }}</strong></td>
                                        <td>{{ $referral->total_cases }}</td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $referral->doctor_id, 'type' => 'surgery', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-danger fs-6 text-decoration-none" 
                                               title="عرض تفاصيل الحالات">
                                                {{ $referral->referred_count }}
                                            </a>
                                        </td>
                                        <td class="fw-bold text-primary">{{ $percent }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">لا توجد حالات محولة للعمليات للطوارئ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($surgeryReferrals->isNotEmpty())
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">الإجمالي</th>
                                        <th>{{ $surgeryReferrals->sum('total_cases') }}</th>
                                        <th>{{ $totals['total_referred_surgeries'] ?? 0 }}</th>
                                        <th>
                                            @php
                                                $totalCasesSum = $surgeryReferrals->sum('total_cases');
                                                $totalReferredSum = $totals['total_referred_surgeries'] ?? 0;
                                                $overallPercent = $totalCasesSum > 0 ? round(($totalReferredSum / $totalCasesSum) * 100, 2) : 0;
                                            @endphp
                                            {{ $overallPercent }}%
                                        </th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- تحويلات الفحوصات والتحاليل حسب الطبيب --}}
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-microscope me-2"></i>تحويلات المختبر والأشعة والسونار والإيكو والمفراس حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark">عدد السجلات: {{ $diagnosticReferrals->count() }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم الطبيب</th>
                                    <th>إجمالي الحالات</th>
                                    <th>المختبر (Lab)</th>
                                    <th>الأشعة (X-Ray)</th>
                                    <th>السونار (Ultrasound)</th>
                                    <th>الإيكو (Echo)</th>
                                    <th>المفراس ورنين (CT/MRI)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($diagnosticReferrals as $ref)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td class="text-end"><strong>د. {{ optional($ref->doctor->user)->name ?? 'طبيب طوارئ' }}</strong></td>
                                        <td>{{ $ref->total_cases }}</td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $ref->doctor_id, 'type' => 'lab', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-success fs-6 text-decoration-none" title="عرض تفاصيل تحويلات المختبر">
                                                {{ $ref->lab_count }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $ref->doctor_id, 'type' => 'radiology', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-info fs-6 text-dark text-decoration-none" title="عرض تفاصيل تحويلات الأشعة">
                                                {{ $ref->radiology_count }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $ref->doctor_id, 'type' => 'sonar', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-primary fs-6 text-decoration-none" title="عرض تفاصيل تحويلات السونار">
                                                {{ $ref->sonar_count }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $ref->doctor_id, 'type' => 'echo', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-secondary fs-6 text-decoration-none" title="عرض تفاصيل تحويلات الإيكو">
                                                {{ $ref->echo_count }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('cashier.emergency.statements.referrals', ['doctor_id' => $ref->doctor_id, 'type' => 'mri', 'from_date' => $fromDate, 'to_date' => $toDate]) }}" 
                                               class="badge bg-dark fs-6 text-decoration-none" title="عرض تفاصيل تحويلات المفراس ورنين">
                                                {{ $ref->mri_count }}
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">لا توجد بيانات تحويلات تشخيصية للطوارئ.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($diagnosticReferrals->isNotEmpty())
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="2" class="text-end">الإجمالي</th>
                                        <th>{{ $diagnosticReferrals->sum('total_cases') }}</th>
                                        <th>{{ $diagnosticReferrals->sum('lab_count') }}</th>
                                        <th>{{ $diagnosticReferrals->sum('radiology_count') }}</th>
                                        <th>{{ $diagnosticReferrals->sum('sonar_count') }}</th>
                                        <th>{{ $diagnosticReferrals->sum('echo_count') }}</th>
                                        <th>{{ $diagnosticReferrals->sum('mri_count') }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- الملخص الشهري حسب الطبيب --}}
    <div class="row g-4 mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>ملخص الحالات الشهري حسب الطبيب</h5>
                    <div>
                        <span class="badge bg-light text-dark me-2">عدد الأطباء: {{ $totals['monthly_grouped_count'] ?? 0 }}</span>
                        <span class="badge bg-light text-dark">إجمالي الحالات: {{ $totals['monthly_examination_count'] ?? 0 }}</span>
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
                                        <td>د. {{ optional($item->doctor->user)->name ?? '-' }}</td>
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
                                        <td class="fw-bold">{{ number_format($item->total, 0) }}</td>
                                        <td class="percent-change-cell {{ $item->percent_change >= 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $item->percent_change >= 0 ? '+' : '' }}{{ number_format($item->percent_change, 2) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="16" class="text-center text-muted py-5">لا توجد بيانات شهرية لحالات الطوارئ.</td>
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
