@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-wallet me-2 text-info"></i>كشف حساب الخدج والحاضنات للطبيب</h2>
                <p class="text-muted mb-0">كشف تفصيلي باستشارات الحواضن والحالات المشرف عليها وسجل الصرف للطبيب د. {{ optional($doctor->user)->name }}.</p>
            </div>
            <a href="{{ route('cashier.nicu.doctor-accounts') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-2"></i>العودة لحسابات أطباء الخدج
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- بطاقات الملخص المالي للطبيب --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-success">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">إجمالي المستحقات المكتسبة</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($totalEarned, 0) }} د.ع</h2>
                    <small>استشارات الخدج المنجزة</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-secondary">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">إجمالي المبالغ المصروفة</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($totalPaid, 0) }} د.ع</h2>
                    <small>المدفوعة نقداً للطبيب من الخدج</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-white bg-info">
                <div class="card-body text-center">
                    <h6 class="mb-1 opacity-75">الرصيد المتبقي مستحق الصرف</h6>
                    <h2 class="fw-bold mb-0">{{ number_format($balance, 0) }} د.ع</h2>
                    <small>جاهز للصرف</small>
                </div>
            </div>
        </div>
    </div>

    {{-- زر وأكشن تسجيل دفعة صرف --}}
    @if($balance > 0)
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent py-3">
                <h5 class="card-title mb-0 fw-bold"><i class="fas fa-hand-holding-usd text-info me-2"></i>تسجيل دفعة صرف مستحقات لطبيب الخدج</h5>
            </div>
            <div class="card-body">
                <form action="{{ route('cashier.nicu.doctor-payout', $doctor) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label for="amount" class="form-label fw-bold">المبلغ المراد صرفه للطبيب (د.ع)</label>
                        <input type="number" id="amount" name="amount" class="form-control" max="{{ $balance }}" min="1000" step="250" placeholder="مثال: 50000" required>
                    </div>
                    <div class="col-md-5">
                        <label for="notes" class="form-label fw-bold">ملاحظات وبيان الصرف</label>
                        <input type="text" id="notes" name="notes" class="form-control" placeholder="مثال: صرف مستحقات استشارات الخدج للفترة الحالية">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-info text-white w-100 fw-bold">
                            <i class="fas fa-check-circle me-1"></i>تسجيل صرف الدفعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- تفاصيل الاستشارات والحالات والمدفوعات --}}
    <div class="row g-4">
        {{-- الاستشارات والحالات المكتسبة --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-stethoscope text-info me-2"></i>سجل استشارات الخدج المنجزة</h5>
                    <span class="badge bg-info bg-opacity-10 text-info">{{ $consultations->total() }} استشارة</span>
                </div>
                <div class="card-body">
                    @if($consultations->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المولود / الحاضنة</th>
                                        <th>التخصص والسبب</th>
                                        <th>قيمة الاستشارة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($consultations as $item)
                                        <tr>
                                            <td>{{ $consultations->firstItem() + $loop->index }}</td>
                                            <td>{{ $item->completed_at ? $item->completed_at->format('Y-m-d H:i') : $item->created_at->format('Y-m-d') }}</td>
                                            <td>
                                                <div class="fw-bold">{{ $item->reservation?->baby_title ?? 'طفل غير محدد' }}</div>
                                                <small class="text-muted">حاضنة: {{ $item->reservation?->incubator?->incubator_number ?? '—' }}</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">{{ $item->specialty ?: 'أطفال' }}</span>
                                                <div class="small text-muted">{{ Str::limit($item->reason, 40) }}</div>
                                            </td>
                                            <td class="fw-bold text-success">{{ number_format($item->fee, 0) }} د.ع</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $consultations->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <p class="text-muted text-center py-4 mb-0">لا توجد استشارات منجزة مدفوعة مسجلة لهذا الطبيب.</p>
                    @endif
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-baby text-primary me-2"></i>الحالات المشرف عليها كطبيب رئيسي</h5>
                    <span class="badge bg-primary bg-opacity-10 text-primary">{{ $supervisedCases->total() }} حالة</span>
                </div>
                <div class="card-body">
                    @if($supervisedCases->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>المولود</th>
                                        <th>الحاضنة</th>
                                        <th>الدخول</th>
                                        <th>الحالة</th>
                                        <th>إجراء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($supervisedCases as $sc)
                                        <tr>
                                            <td>{{ $supervisedCases->firstItem() + $loop->index }}</td>
                                            <td>
                                                <strong>{{ $sc->baby_title }}</strong>
                                            </td>
                                            <td>حاضنة {{ $sc->incubator?->incubator_number ?? '—' }}</td>
                                            <td>{{ $sc->admission_date->format('Y-m-d') }}</td>
                                            <td><span class="badge bg-{{ $sc->status_color }}">{{ $sc->status_name }}</span></td>
                                            <td>
                                                <a href="{{ route('nicu.statement', $sc) }}" class="btn btn-xs btn-outline-info">
                                                    كشف الحساب
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-2">
                            {{ $supervisedCases->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <p class="text-muted text-center py-3 mb-0">لا توجد حالات مسجلة تحت إشراف هذا الطبيب.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- الحركات المالية (الصرف) --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i>سجل دفعات الصرف المالي</h5>
                </div>
                <div class="card-body">
                    @if($payouts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ</th>
                                        <th>المبلغ</th>
                                        <th>بواسطة</th>
                                        <th>ملاحظات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payouts as $payout)
                                        <tr>
                                            <td>{{ $payouts->firstItem() + $loop->index }}</td>
                                            <td>{{ $payout->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="fw-bold text-danger">{{ number_format($payout->amount, 0) }} د.ع</td>
                                            <td>{{ optional($payout->paidBy)->name ?? '-' }}</td>
                                            <td><span class="small text-muted">{{ $payout->notes }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $payouts->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <p class="text-muted text-center py-5">لا توجد دفعات صرف مسجلة لهذا الطبيب لقسم الخدج.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
