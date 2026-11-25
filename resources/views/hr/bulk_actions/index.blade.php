@extends('layouts.app')
@section('title', 'العقوبات والمكافآت الجماعية - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-gavel text-warning me-2"></i>تطبيق العقوبات والمكافآت الجماعية وحوكمة الـ 48 ساعة
            </h4>
            <p class="text-muted small mb-0">
                تطبيق إجراء إداري أو مالي على قسم محدد أو كل المستشفى، مع إتاحة الإلغاء المباشر لـ HR خلال 48 ساعة أو بموافقة مدير الإدارة بعدها.
            </p>
        </div>
        @canany(['manage hr bulk actions', 'manage hr actions'])
            <button class="btn btn-primary fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#newBulkActionModal">
                <i class="fas fa-plus-circle me-1"></i> تطبيق عقوبة / مكافأة جماعية
            </button>
        @endcanany
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach
            </ul>
        </div>
    @endif

    {{-- طلبات الإلغاء بعد 48 ساعة المرفوعة لمدير الإدارة --}}
    @if($pendingBatchRequests->isNotEmpty() || $pendingIndividualRequests->isNotEmpty())
        <div class="card border-0 shadow-sm rounded-3 mb-4 border-start border-4 border-danger">
            <div class="card-header bg-danger bg-opacity-10 py-3">
                <h6 class="fw-bold text-danger mb-0">
                    <i class="fas fa-user-shield me-2"></i>طلبات إلغاء بعد انتهاء الـ 48 ساعة (بانتظار موافقة مدير الإدارة)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small text-center">
                        <thead class="table-light">
                            <tr>
                                <th>النوع</th>
                                <th>الهدف / الموظف</th>
                                <th>الإجراء</th>
                                <th>مقدم الطلب</th>
                                <th>سبب طلب الإلغاء</th>
                                <th>القرار</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingBatchRequests as $bReq)
                                <tr>
                                    <td><span class="badge bg-dark">قرار جماعي #{{ $bReq->id }}</span></td>
                                    <td class="fw-bold">{{ $bReq->scope_label }} ({{ $bReq->target_count }})</td>
                                    <td>{{ $bReq->actionSetting->title ?? '—' }}</td>
                                    <td>{{ $bReq->cancelRequester->name ?? '—' }}</td>
                                    <td class="text-start">{{ $bReq->cancel_request_reason }}</td>
                                    <td>
                                        @can('approve hr action cancellation')
                                            <form action="{{ route('hr.bulk-actions.approve-cancel', $bReq->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success fw-bold" onclick="return confirm('اعتماد إلغاء القرار الجماعي بالكامل؟')">
                                                    <i class="fas fa-check me-1"></i> موافقة وإلغاء
                                                </button>
                                            </form>
                                            <form action="{{ route('hr.bulk-actions.reject-cancel', $bReq->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">رفض</button>
                                            </form>
                                        @else
                                            <span class="badge bg-warning text-dark">بانتظار مدير الإدارة</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach

                            @foreach($pendingIndividualRequests as $iReq)
                                <tr>
                                    <td><span class="badge bg-info text-dark">إجراء فردي #{{ $iReq->id }}</span></td>
                                    <td class="fw-bold">
                                        {{ $iReq->employee->full_name ?? '—' }}
                                        <div class="text-muted fw-normal">{{ $iReq->employee->department->name ?? '' }}</div>
                                    </td>
                                    <td>
                                        {{ $iReq->actionSetting->title ?? '—' }}
                                        <div class="text-muted">{{ number_format(abs($iReq->financial_amount), 0) }} د.ع</div>
                                    </td>
                                    <td>{{ $iReq->cancelRequester->name ?? '—' }}</td>
                                    <td class="text-start">{{ $iReq->cancel_request_reason }}</td>
                                    <td>
                                        @can('approve hr action cancellation')
                                            <form action="{{ route('hr.employees.actions.approve-cancel', $iReq->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success fw-bold" onclick="return confirm('اعتماد إلغاء هذا الإجراء للموظف؟')">
                                                    <i class="fas fa-check me-1"></i> موافقة وإلغاء
                                                </button>
                                            </form>
                                            <form action="{{ route('hr.employees.actions.reject-cancel', $iReq->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">رفض</button>
                                            </form>
                                        @else
                                            <span class="badge bg-warning text-dark">بانتظار مدير الإدارة</span>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- سجل القرارات الجماعية --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0"><i class="fas fa-history text-primary me-2"></i>سجل القرارات الجماعية المطبقة</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>تاريخ القرار</th>
                            <th>النطاق المستهدف</th>
                            <th>الإجراء</th>
                            <th>المشمولون</th>
                            <th>نافذة الـ 48 ساعة</th>
                            <th>الحالة</th>
                            <th>بواسطة</th>
                            <th>التفاصيل والإدارة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            @php
                                $within48 = $batch->isWithin48Hours();
                                $deadline = $batch->cancellable_until ?? $batch->created_at?->copy()->addHours(48);
                            @endphp
                            <tr>
                                <td class="font-monospace">#{{ $batch->id }}</td>
                                <td>{{ $batch->action_date->format('Y-m-d') }}</td>
                                <td class="fw-bold">{{ $batch->scope_label }}</td>
                                <td>
                                    @if($batch->actionSetting?->category === 'penalty')
                                        <span class="badge bg-danger">{{ $batch->actionSetting->title }}</span>
                                    @elseif($batch->actionSetting?->category === 'bonus')
                                        <span class="badge bg-success">{{ $batch->actionSetting->title }}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{ $batch->actionSetting->title ?? '—' }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-primary">{{ $batch->actions_count }} موظف</span>
                                    @if($batch->cancelled_actions_count > 0)
                                        <div class="text-danger mt-1" style="font-size: 0.72rem;">ملغي: {{ $batch->cancelled_actions_count }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($batch->status === 'cancelled')
                                        <span class="badge bg-secondary">مُلغى</span>
                                    @elseif($within48)
                                        <span class="badge bg-success-subtle text-success border border-success">
                                            <i class="fas fa-clock me-1"></i>ساري (متاح لـ HR حتى {{ $deadline?->format('m-d H:i') }})
                                        </span>
                                    @else
                                        <span class="badge bg-dark">
                                            <i class="fas fa-lock me-1"></i>تجاوز 48 ساعة (يتطلب مدير الإدارة)
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if($batch->status === 'cancelled')
                                        <span class="badge bg-secondary">ملغي بالكامل</span>
                                    @elseif($batch->status === 'partially_cancelled')
                                        <span class="badge bg-warning text-dark">ملغي جزئياً</span>
                                    @else
                                        <span class="badge bg-success">ساري المفعول</span>
                                    @endif
                                </td>
                                <td>{{ $batch->creator->name ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('hr.bulk-actions.show', $batch->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i> التفاصيل والإلغاء
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-5 text-muted">لا توجد قرارات عقوبات أو مكافآت جماعية مسجلة بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($batches->hasPages())
            <div class="card-footer bg-white">{{ $batches->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal: تطبيق إجراء جماعي --}}
<div class="modal fade" id="newBulkActionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.bulk-actions.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title fw-bold"><i class="fas fa-gavel text-primary me-2"></i>تطبيق عقوبة / مكافأة جماعية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info small border-0 mb-3">
                        <i class="fas fa-clock me-1"></i>
                        يسري الإجراء فوراً على كافة الموظفين المستهدفين، ويبقى قابلاً للإلغاء المباشر من HR لمدة <strong>48 ساعة</strong>، وبعدها يُلغى فقط <strong>بموافقة مدير الإدارة</strong> (فردياً أو للقرار كله).
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">النطاق المستهدف <span class="text-danger">*</span></label>
                            <select name="scope" id="bulkScopeSelect" class="form-select" required onchange="toggleBulkScopeFields()">
                                <option value="department">قسم محدد</option>
                                <option value="hospital">كل المستشفى (جميع الموظفين النشطين)</option>
                                <option value="selected">تحديد موظفين معينين</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="bulkDepartmentWrapper">
                            <label class="form-label fw-bold">القسم <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select">
                                <option value="">-- اختر القسم --</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 d-none" id="bulkEmployeesWrapper">
                            <label class="form-label fw-bold">اختر الموظفين</label>
                            <select name="employee_ids[]" class="form-select" multiple size="6">
                                @foreach($activeEmployees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->department->name ?? 'بدون قسم' }})</option>
                                @endforeach
                            </select>
                            <div class="form-text small">اضغط Ctrl لاختيار أكثر من موظف.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">الإجراء من اللائحة <span class="text-danger">*</span></label>
                            <select name="hr_action_setting_id" class="form-select" required>
                                <option value="">-- اختر الإجراء --</option>
                                @foreach($actionSettings as $st)
                                    <option value="{{ $st->id }}">
                                        [{{ $st->category === 'penalty' ? 'عقوبة' : ($st->category === 'bonus' ? 'مكافأة' : 'إنذار') }}]
                                        {{ $st->title }}
                                        @if($st->effect_type === 'amount') ({{ number_format($st->effect_value, 0) }} د.ع)
                                        @elseif($st->effect_type === 'days') ({{ $st->effect_value }} يوم)
                                        @elseif($st->effect_type === 'percentage') ({{ $st->effect_value }}%)
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">تاريخ الاستحقاق / الواقعة <span class="text-danger">*</span></label>
                            <input type="date" name="action_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">السبب والتوجيه الإداري <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="اكتب تفاصيل القرار الإداري..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">
                        <i class="fas fa-check-circle me-1"></i> تنفيذ وتطبيق القرار
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleBulkScopeFields() {
    const scope = document.getElementById('bulkScopeSelect').value;
    document.getElementById('bulkDepartmentWrapper').classList.toggle('d-none', scope !== 'department');
    document.getElementById('bulkEmployeesWrapper').classList.toggle('d-none', scope !== 'selected');
}
</script>
@endpush
@endsection
