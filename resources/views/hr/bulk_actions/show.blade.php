@extends('layouts.app')
@section('title', 'تفاصيل القرار الجماعي #' . $batch->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-file-signature text-primary me-2"></i>تفاصيل القرار الجماعي #{{ $batch->id }}: {{ $batch->actionSetting->title ?? '—' }}
            </h4>
            <p class="text-muted small mb-0">
                النطاق: <strong>{{ $batch->scope_label }}</strong> | التاريخ: {{ $batch->action_date->format('Y-m-d') }} | بواسطة: {{ $batch->creator->name ?? '—' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.bulk-actions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للسجل الجماعي
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    @php
        $within48 = $batch->isWithin48Hours();
        $hasPending = $batch->actions->where('status', 'pending')->isNotEmpty();
        $deadline = $batch->cancellable_until ?? $batch->created_at?->copy()->addHours(48);
    @endphp

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="mb-1"><strong>السبب:</strong> {{ $batch->reason }}</div>
                <div class="small text-muted">
                    نافذة الإلغاء المباشر (48 ساعة):
                    @if($within48)
                        <span class="badge bg-success">مفتوحة لـ HR حتى {{ $deadline?->format('Y-m-d H:i') }}</span>
                    @else
                        <span class="badge bg-dark">منتهية — يتطلب إلغاؤها موافقة مدير الإدارة</span>
                    @endif
                </div>
            </div>
            <div>
                @if($hasPending)
                    @if($within48 || auth()->user()->can('approve hr action cancellation'))
                        <form action="{{ route('hr.bulk-actions.cancel', $batch->id) }}" method="POST" class="d-inline">
                            @csrf
                            <input type="hidden" name="cancellation_reason" value="إلغاء القرار الجماعي بالكامل">
                            <button type="submit" class="btn btn-danger fw-bold" onclick="return confirm('هل أنت متأكد من إلغاء القرار الجماعي بالكامل لكل الموظفين المعلقين؟')">
                                <i class="fas fa-ban me-1"></i> إلغاء القرار الجماعي بالكامل
                            </button>
                        </form>
                    @elseif($batch->hasPendingCancellationRequest())
                        <span class="badge bg-warning text-dark p-2">تم إرسال طلب إلغاء لمدير الإدارة ({{ $batch->cancelRequester->name ?? '' }})</span>
                    @else
                        <button type="button" class="btn btn-outline-danger fw-bold" data-bs-toggle="modal" data-bs-target="#requestBatchCancelModal">
                            <i class="fas fa-paper-plane me-1"></i> طلب إلغاء القرار كله (موافقة مدير الإدارة)
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3">
            <h6 class="fw-bold mb-0">الموظفون المشمولون بالقرار ({{ $batch->actions->count() }}) — يمكنك الإلغاء على المستوى الفردي أيضاً</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>الرقم الوظيفي</th>
                            <th class="text-start">الموظف / القسم</th>
                            <th>الأثر المالي</th>
                            <th>الحالة</th>
                            <th>الإجراءات الفردية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($batch->actions as $action)
                            <tr class="{{ $action->isCancelled() ? 'table-light text-muted' : '' }}">
                                <td class="font-monospace">{{ $action->employee->employee_code ?? '—' }}</td>
                                <td class="text-start fw-bold">
                                    <a href="{{ route('hr.employees.show', $action->hr_employee_id) }}" class="text-decoration-none">
                                        {{ $action->employee->full_name ?? '—' }}
                                    </a>
                                    <div class="text-muted fw-normal">{{ $action->employee->department->name ?? '—' }}</div>
                                </td>
                                <td class="fw-bold {{ $action->financial_amount < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($action->financial_amount, 0) }} د.ع
                                </td>
                                <td>
                                    @if($action->isCancelled())
                                        <span class="badge bg-secondary">ملغي ({{ $action->cancelledBy->name ?? '' }})</span>
                                    @elseif($action->status === 'processed')
                                        <span class="badge bg-success">مُرحّل ({{ $action->payrollCycle->cycle_month ?? '' }})</span>
                                    @elseif($action->hasPendingCancellationRequest())
                                        <span class="badge bg-warning text-dark">طلب إلغاء بانتظار مدير الإدارة</span>
                                    @else
                                        <span class="badge bg-primary">ساري (بانتظار الراتب)</span>
                                    @endif
                                </td>
                                <td>
                                    @if($action->status === 'pending')
                                        @if($action->isWithin48Hours() || auth()->user()->can('approve hr action cancellation'))
                                            <form action="{{ route('hr.employees.actions.cancel', $action->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="cancellation_reason" value="إلغاء فردي من القرار الجماعي">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('إلغاء الإجراء لهذا الموظف فقط؟')">
                                                    <i class="fas fa-ban me-1"></i> إلغاء فردي
                                                </button>
                                            </form>
                                        @elseif($action->hasPendingCancellationRequest())
                                            <span class="text-muted">بانتظار مدير الإدارة</span>
                                        @else
                                            <form action="{{ route('hr.employees.actions.request-cancel', $action->id) }}" method="POST" class="d-inline-flex gap-1 align-items-center justify-content-center">
                                                @csrf
                                                <input type="text" name="cancel_request_reason" class="form-control form-control-sm" style="max-width: 170px;" placeholder="سبب طلب الإلغاء" required>
                                                <button type="submit" class="btn btn-sm btn-outline-warning text-dark">طلب إلغاء</button>
                                            </form>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Modal: طلب إلغاء دفعة بعد 48 ساعة --}}
<div class="modal fade" id="requestBatchCancelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.bulk-actions.request-cancel', $batch->id) }}" method="POST">
                @csrf
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title fw-bold">رفع طلب إلغاء قرار جماعي لمدير الإدارة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <label class="form-label fw-bold">سبب طلب الإلغاء <span class="text-danger">*</span></label>
                    <textarea name="cancel_request_reason" class="form-control" rows="3" required placeholder="اكتب سبب طلب إلغاء هذا القرار بعد مرور 48 ساعة..."></textarea>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-warning fw-bold text-dark">إرسال لمدير الإدارة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
