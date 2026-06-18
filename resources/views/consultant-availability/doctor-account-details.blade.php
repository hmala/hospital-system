@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-user-md me-2 text-primary"></i>حساب الدكتور {{ optional($doctor->user)->name }}</h2>
                <p class="text-muted mb-0">الاطّلاع على رصيد الطبيب وحركاته المالية.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('consultant-availability.doctor-accounts') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>العودة لحسابات الأطباء
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    @endif

    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">الرصيد الحالي</h6>
                    <div class="display-6 fw-bold">{{ number_format(optional($doctor->financialAccount)->balance ?? 0, 2) }} IQD</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي المستحقات</h6>
                    <div class="display-6 fw-bold">{{ number_format(optional($doctor->financialAccount)->total_earned ?? 0, 2) }} IQD</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">إجمالي المدفوع</h6>
                    <div class="display-6 fw-bold">{{ number_format(optional($doctor->financialAccount)->total_paid ?? 0, 2) }} IQD</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">آخر تحديث</h6>
                    <div class="display-6 fw-bold">{{ optional(optional($doctor->financialAccount)->last_paid_at)->format('Y-m-d') ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('consultant-availability.doctor-payout', $doctor) }}" class="row g-3 align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label for="amount" class="form-label">مبلغ الصرف</label>
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control" placeholder="0.00" value="{{ optional($doctor->financialAccount)->balance ?? 0 }}" required aria-describedby="amountHelp">
                            <div id="amountHelp" class="form-text">اكتب المبلغ الذي تريد صرفه للطبيب. القيمة الافتراضية هي رصيد الطبيب المتاح.</div>
                        </div>
                        <div class="col-md-5">
                            <label for="notes" class="form-label">ملاحظات (اختياري)</label>
                            <input type="text" id="notes" name="notes" class="form-control" placeholder="مثلاً: صرف عن شهر مايو أو بدل خدمة" aria-describedby="notesHelp">
                            <div id="notesHelp" class="form-text">يمكن تسجيل سبب الصرف أو ملاحظة توضيحية.</div>
                        </div>
                        <div class="col-md-4 d-flex gap-2">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-hand-holding-dollar me-2"></i>صرف للطبيب
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-hand-holding-dollar me-2"></i>المستحقات المدفوعة</h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('consultant-availability.doctor-account', $doctor) }}" class="row g-3 align-items-end mb-4">
                        <div class="col-md-2">
                            <label for="from_date" class="form-label">من تاريخ</label>
                            <input type="date" id="from_date" name="from_date" class="form-control" value="{{ old('from_date', $fromDate ?? '') }}">
                        </div>
                        <div class="col-md-2">
                            <label for="to_date" class="form-label">إلى تاريخ</label>
                            <input type="date" id="to_date" name="to_date" class="form-control" value="{{ old('to_date', $toDate ?? '') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="payout_search" class="form-label">بحث في المستحقات المدفوعة</label>
                            <input type="search" id="payout_search" name="payout_search" class="form-control" placeholder="بحث بالوصف أو المبلغ" value="{{ old('payout_search', $payoutSearch ?? '') }}">
                        </div>
                        <div class="col-md-5 d-flex flex-wrap gap-2 align-items-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>بحث
                            </button>
                            <a href="{{ route('consultant-availability.doctor-account', $doctor) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>مسح
                            </a>
                            <a href="{{ route('consultant-availability.doctor-account.export', ['doctor' => $doctor]) }}?{{ http_build_query(array_filter(['from_date' => $fromDate, 'to_date' => $toDate, 'payout_search' => $payoutSearch])) }}" class="btn btn-success">
                                <i class="fas fa-file-excel me-2"></i>تصدير Excel
                            </a>
                            <button type="button" class="btn btn-outline-dark" onclick="window.print()">
                                <i class="fas fa-file-pdf me-2"></i>طباعة PDF
                            </button>
                        </div>
                    </form>

                    @if($paidDues->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>المسؤول</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paidDues as $due)
                                        <tr>
                                            <td>{{ $paidDues->firstItem() + $loop->index }}</td>
                                            <td>{{ optional($due->paid_at)->format('Y-m-d H:i') ?? optional($due->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td class="fw-bold text-success">{{ number_format($due->amount, 2) }} IQD</td>
                                            <td>{{ optional($due->paidBy)->name ?? '-' }}</td>
                                            <td>{{ $due->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $paidDues->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد مستحقات مدفوعة لهذا الطبيب ضمن الفلتر الحالي.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>الحرجات المالية المستحقة</h5>
                </div>
                <div class="card-body">
                    @if($pendingDues->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>الوصف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($pendingDues as $due)
                                        <tr>
                                            <td>{{ $pendingDues->firstItem() + $loop->index }}</td>
                                            <td>{{ optional($due->created_at)->format('Y-m-d H:i') ?? '-' }}</td>
                                            <td class="fw-bold text-warning">{{ number_format($due->amount, 2) }} IQD</td>
                                            <td>{{ $due->notes ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            {{ $pendingDues->withQueryString()->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="text-muted">لا توجد حرَكات مالية مستحقة لهذا الطبيب ضمن الفلتر الحالي.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
