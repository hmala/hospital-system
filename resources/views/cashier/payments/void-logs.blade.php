@extends('layouts.app')

@section('content')
<div class="container-fluid py-4 px-lg-4">
    <!-- رأس الصفحة الرقابي الفاخر -->
    <div class="rounded-4 p-4 mb-4 text-white shadow-sm" style="background: linear-gradient(135deg, #1a1e21 0%, #842029 100%);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-danger text-white fw-bold px-3 py-2 rounded-pill mb-2 shadow-sm border border-danger-subtle">
                    <i class="fas fa-shield-alt me-1"></i> وحدة الرقابة المالية والتدقيق الإداري (Audit Trail)
                </span>
                <h3 class="fw-bold mb-1">سجل تدقيق الوصولات الملغاة والمبطلة</h3>
                <p class="mb-0 text-white-50 small">
                    كشف رقابي وحسابي شامل ومفصل يوثق بالثانية كافة عمليات إلغاء الدفع، إعادة فتح الوصولات للتصحيح، وسندات الاسترجاع المالي.
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('cashier.index') }}" class="btn btn-outline-light px-3 rounded-pill">
                    <i class="fas fa-cash-register me-1"></i> محطة الكاشير
                </a>
                <a href="{{ route('cashier.report') }}" class="btn btn-light px-3 rounded-pill text-dark fw-bold">
                    <i class="fas fa-file-invoice-dollar me-1 text-danger"></i> كشف المقبوضات اليومية
                </a>
            </div>
        </div>
    </div>

    <!-- بطاقات الإحصائيات الرقابية الأربع -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-top border-4 border-dark h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block mb-1">إجمالي الحركات الرقابية</span>
                        <h4 class="fw-bold text-dark mb-0">{{ number_format($totalLogsCount) }}</h4>
                    </div>
                    <div class="rounded-circle bg-dark bg-opacity-10 p-3 text-dark">
                        <i class="fas fa-clipboard-list fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-top border-4 border-success h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block mb-1">إعادة فتح للتسديد والتصحيح</span>
                        <h4 class="fw-bold text-success mb-0">{{ number_format($rebillCount) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">Re-billed & Corrected</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success">
                        <i class="fas fa-sync-alt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-top border-4 border-danger h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block mb-1">إلغاء نهائي واسترداد للمريض</span>
                        <h4 class="fw-bold text-danger mb-0">{{ number_format($refundCount) }}</h4>
                        <small class="text-muted" style="font-size: 0.75rem;">Refunded & Cancelled</small>
                    </div>
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger">
                        <i class="fas fa-hand-holding-usd fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 rounded-4 shadow-sm p-3 bg-white border-top border-4 border-primary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small d-block mb-1">إجمالي مبالغ الوصولات المعالجة</span>
                        <h4 class="fw-bold text-primary mb-0">{{ number_format($totalVoidedAmount) }} <small class="fs-6">د.ع</small></h4>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary">
                        <i class="fas fa-coins fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلاتر البحث والتدقيق -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('cashier.payments.void.logs') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="بحث برقم الوصل، اسم المريض، السبب، أو المشرف..." value="{{ $search ?? '' }}">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="void_type" class="form-select">
                        <option value="">كافة أنواع الإجراءات الرقابية</option>
                        <option value="rebill" {{ ($voidType ?? '') === 'rebill' ? 'selected' : '' }}>🔄 إعادة فتح للتسديد والتصحيح (Re-bill)</option>
                        <option value="refund" {{ ($voidType ?? '') === 'refund' ? 'selected' : '' }}>💵 إبطال نهائي واسترداد للمريض (Refund)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <input type="date" name="from_date" class="form-control" title="من تاريخ" value="{{ $fromDate ?? '' }}" placeholder="من تاريخ">
                </div>

                <div class="col-md-2">
                    <input type="date" name="to_date" class="form-control" title="إلى تاريخ" value="{{ $toDate ?? '' }}" placeholder="إلى تاريخ">
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(!empty($search) || !empty($voidType) || !empty($fromDate) || !empty($toDate))
                        <a href="{{ route('cashier.payments.void.logs') }}" class="btn btn-outline-secondary" title="إعادة تعيين">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول سجل التدقيق الرقابي -->
    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
        <div class="p-3 px-4 border-bottom bg-light d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-history text-danger fs-5"></i>
                <h5 class="fw-bold mb-0 text-dark">سجل الحركات المنفذة بالتفصيل</h5>
            </div>
            <span class="badge bg-secondary-subtle text-secondary border">
                إجمالي النتائج: {{ $logs->total() }} سجل
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small">
                    <tr>
                        <th class="ps-4"># / التوقيت الدقيق</th>
                        <th>رقم الوصل الأصلي</th>
                        <th>المريض والتغطية</th>
                        <th>أمين الصندوق (الكاشير)</th>
                        <th>المشرف المنفّذ للإلغاء</th>
                        <th>نوع الإجراء</th>
                        <th>المبلغ المقبوض</th>
                        <th>سبب الإلغاء والتوثيق</th>
                        <th class="text-center pe-4">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark d-block">#{{ $log->id }}</span>
                                <small class="text-muted" style="font-size: 0.75rem;">
                                    {{ $log->created_at->format('Y-m-d') }}
                                    <span class="text-primary fw-bold">{{ $log->created_at->format('H:i:s') }}</span>
                                </small>
                            </td>

                            <td>
                                <span class="badge bg-dark bg-opacity-10 text-dark border px-2 py-1 font-monospace fs-6">
                                    {{ $log->receipt_number }}
                                </span>
                            </td>

                            <td>
                                @php
                                    $patientName = $log->patient?->user?->name ?? 'غير محدد';
                                    $patientId = $log->patient?->national_id ?? ('#' . $log->patient_id);
                                @endphp
                                <strong class="text-dark d-block">{{ $patientName }}</strong>
                                <small class="text-muted">{{ $patientId }}</small>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="fas fa-user-circle text-muted"></i>
                                    <span class="small text-dark">{{ $log->cashier?->name ?? 'غير محدد' }}</span>
                                </div>
                            </td>

                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                                        <i class="fas fa-user-shield me-1"></i> {{ $log->voidedBy?->name ?? 'غير محدد' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                @if($log->void_type === 'rebill')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1-5 rounded-pill shadow-xs">
                                        <i class="fas fa-sync-alt me-1"></i> إعادة فتح للتصحيح
                                    </span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1-5 rounded-pill shadow-xs">
                                        <i class="fas fa-hand-holding-usd me-1"></i> استرداد نقدي
                                    </span>
                                @endif
                            </td>

                            <td>
                                <strong class="text-dark d-block">{{ number_format($log->amount) }} د.ع</strong>
                                @if($log->insurance_share > 0)
                                    <small class="text-muted" style="font-size: 0.72rem;">
                                        حصة الضمان: <span class="text-primary">{{ number_format($log->insurance_share) }}</span>
                                    </small>
                                @endif
                            </td>

                            <td style="max-width: 280px;">
                                <div class="small text-dark text-break bg-light p-2 rounded-3 border">
                                    <i class="fas fa-quote-right text-muted me-1 fa-xs"></i>
                                    {{ $log->void_reason }}
                                </div>
                            </td>

                            <td class="text-center pe-4">
                                @if($log->payment_id)
                                    <a href="{{ route('cashier.receipt', $log->payment_id) }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-xs" title="عرض قيد الوصل">
                                        <i class="fas fa-eye me-1"></i> الوصل
                                    </a>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-check-circle text-success fa-3x mb-3 d-block"></i>
                                    <h6 class="fw-bold">لا توجد حركات إبطال مسجلة</h6>
                                    <p class="small text-muted mb-0">كافة الوصولات المالية سليمة ولم تسجل أي حركات إبطال مطابقة لمعايير البحث.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-top d-flex justify-content-center">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
