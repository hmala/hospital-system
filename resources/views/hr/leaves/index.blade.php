@extends('layouts.app')
@section('title', 'إدارة واعتماد الإجازات - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-plane-departure text-primary me-2"></i>إدارة واعتماد طلبات الإجازات
            </h4>
            <p class="text-muted small mb-0">مسار الاعتماد: الموظف ⬅️ موافقة وتصنيف مدير القسم ⬅️ الاعتماد النهائي من مدير الإدارة (وتثبيت الروستر تلقائياً).</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm btn-sm" data-bs-toggle="modal" data-bs-target="#proxyLeaveModal">
            <i class="fas fa-plus me-1"></i> رفع طلب إجازة لموظف
        </button>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger border-0 shadow-sm">
            <ul class="mb-0 small">@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
        </div>
    @endif

    {{-- فلاتر --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('hr.leaves.index') }}" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">كل الحالات</option>
                        <option value="pending_department" {{ request('status') === 'pending_department' ? 'selected' : '' }}>بانتظار مدير القسم</option>
                        <option value="pending_admin" {{ request('status') === 'pending_admin' ? 'selected' : '' }}>بانتظار مدير الإدارة</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>معتمدة نهائياً</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>مرفوضة</option>
                    </select>
                </div>
                @if($canViewAll)
                <div class="col-md-4">
                    <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">كل الأقسام</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </form>
        </div>
    </div>

    {{-- الجدول --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th class="text-start">الموظف / القسم</th>
                            <th>النوع / الأجر</th>
                            <th>الفترة والأيام</th>
                            <th class="text-start">السبب</th>
                            <th>الحالة والمسار</th>
                            <th>إجراءات القرار</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($leaveRequests as $req)
                            @php
                                $user = auth()->user();
                                $isAssignedDeptManager = $req->department && (int) $req->department->manager_id === (int) $user->id;
                                $canDeptDecide = $req->status === 'pending_department' && ($user->isAdminDirector() || $isAssignedDeptManager || ($user->can('approve department leaves') && in_array($req->department_id, $managedDeptIds, true)));
                                $deptManagerId = $req->department?->manager_id;
                                $isDeptMgrLeave = $deptManagerId && $req->employee?->user_id && (int) $deptManagerId === (int) $req->employee->user_id;
                                $canFinalDecide = in_array($req->status, ['pending_admin', 'pending_department'], true) && ($user->isAdminDirector() || $user->can('approve admin leaves') || (($user->hasRole('hr_manager') || $user->can('manage hr actions')) && (!$deptManagerId || $isDeptMgrLeave)));
                            @endphp
                            <tr>
                                <td class="font-monospace">#{{ $req->id }}</td>
                                <td class="text-start fw-bold">
                                    {{ $req->employee->full_name ?? '—' }}
                                    <div class="text-muted fw-normal">{{ $req->department->name ?? 'بدون قسم' }}</div>
                                </td>
                                <td>
                                    <div class="fw-bold">{{ $req->leave_type_label }}</div>
                                    <span class="badge {{ $req->is_paid ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                        {{ $req->is_paid ? 'براتب' : 'بدون راتب' }}
                                    </span>
                                </td>
                                <td>
                                    <div>{{ $req->start_date->format('Y-m-d') }} &larr; {{ $req->end_date->format('Y-m-d') }}</div>
                                    <span class="badge bg-light text-dark border mt-1">{{ $req->days_count }} يوم</span>
                                </td>
                                <td class="text-start">{{ Str::limit($req->reason, 55) }}</td>
                                <td>
                                    <span class="badge {{ $req->status_badge_class }}">{{ $req->status_label }}</span>
                                    @if($req->deptManager)
                                        <div class="text-muted mt-1" style="font-size: 0.72rem;">القسم: {{ $req->deptManager->name }}</div>
                                    @endif
                                    @if($req->adminApprover)
                                        <div class="text-muted mt-1" style="font-size: 0.72rem;">الإدارة: {{ $req->adminApprover->name }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($canDeptDecide)
                                        <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#deptDecisionModal{{ $req->id }}">
                                            <i class="fas fa-user-check me-1"></i> قرار مدير القسم
                                        </button>
                                        <div class="modal fade" id="deptDecisionModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow text-start">
                                                    <form action="{{ route('hr.leaves.dept-approve', $req->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header bg-primary bg-opacity-10">
                                                            <h6 class="modal-title fw-bold">تصنيف وقرار مدير القسم: {{ $req->employee->full_name }}</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-3">
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold small">تصنيف الإجازة المعتمد</label>
                                                                <select name="leave_type" class="form-select form-select-sm">
                                                                    <option value="annual" {{ $req->leave_type === 'annual' ? 'selected' : '' }}>اعتيادية</option>
                                                                    <option value="sick" {{ $req->leave_type === 'sick' ? 'selected' : '' }}>مرضية</option>
                                                                    <option value="emergency" {{ $req->leave_type === 'emergency' ? 'selected' : '' }}>طارئة</option>
                                                                    <option value="unpaid" {{ $req->leave_type === 'unpaid' ? 'selected' : '' }}>بدون راتب</option>
                                                                    <option value="other" {{ $req->leave_type === 'other' ? 'selected' : '' }}>أخرى</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold small">احتساب الراتب</label>
                                                                <select name="is_paid" class="form-select form-select-sm">
                                                                    <option value="1" {{ $req->is_paid ? 'selected' : '' }}>براتب كامل</option>
                                                                    <option value="0" {{ !$req->is_paid ? 'selected' : '' }}>بدون راتب (تخصم)</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-0">
                                                                <label class="form-label fw-bold small">ملاحظات مدير القسم</label>
                                                                <textarea name="dept_notes" class="form-control form-control-sm" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="submit" class="btn btn-sm btn-success fw-bold">موافقة وإرسال لمدير الإدارة</button>
                                                        </div>
                                                    </form>
                                                    <form action="{{ route('hr.leaves.dept-reject', $req->id) }}" method="POST" class="px-3 pb-3">
                                                        @csrf
                                                        <input type="hidden" name="dept_notes" value="رفض من مدير القسم">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('رفض طلب الإجازة؟')">رفض الطلب</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($canFinalDecide && $req->status === 'pending_admin')
                                        <button class="btn btn-sm btn-success fw-bold" data-bs-toggle="modal" data-bs-target="#adminDecisionModal{{ $req->id }}">
                                            <i class="fas fa-check-double me-1"></i> الاعتماد النهائي
                                        </button>
                                        <div class="modal fade" id="adminDecisionModal{{ $req->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow text-start">
                                                    <form action="{{ route('hr.leaves.admin-approve', $req->id) }}" method="POST">
                                                        @csrf
                                                        <div class="modal-header bg-success bg-opacity-10">
                                                            <h6 class="modal-title fw-bold">الاعتماد النهائي للإجازة: {{ $req->employee->full_name }}</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-3">
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold small">نوع الإجازة</label>
                                                                <select name="leave_type" class="form-select form-select-sm">
                                                                    <option value="annual" {{ $req->leave_type === 'annual' ? 'selected' : '' }}>اعتيادية</option>
                                                                    <option value="sick" {{ $req->leave_type === 'sick' ? 'selected' : '' }}>مرضية</option>
                                                                    <option value="emergency" {{ $req->leave_type === 'emergency' ? 'selected' : '' }}>طارئة</option>
                                                                    <option value="unpaid" {{ $req->leave_type === 'unpaid' ? 'selected' : '' }}>بدون راتب</option>
                                                                    <option value="other" {{ $req->leave_type === 'other' ? 'selected' : '' }}>أخرى</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label fw-bold small">حالة الراتب</label>
                                                                <select name="is_paid" class="form-select form-select-sm">
                                                                    <option value="1" {{ $req->is_paid ? 'selected' : '' }}>براتب</option>
                                                                    <option value="0" {{ !$req->is_paid ? 'selected' : '' }}>بدون راتب</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-0">
                                                                <label class="form-label fw-bold small">ملاحظات الإدارة</label>
                                                                <textarea name="admin_notes" class="form-control form-control-sm" rows="2"></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer bg-light">
                                                            <button type="submit" class="btn btn-sm btn-success fw-bold">اعتماد نهائي وتثبيت بالروستر</button>
                                                        </div>
                                                    </form>
                                                    <form action="{{ route('hr.leaves.admin-reject', $req->id) }}" method="POST" class="px-3 pb-3">
                                                        @csrf
                                                        <input type="hidden" name="admin_notes" value="رفض من مدير الإدارة">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" onclick="return confirm('رفض طلب الإجازة نهائياً؟')">رفض الطلب</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(!$canDeptDecide && !($canFinalDecide && $req->status === 'pending_admin'))
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-5 text-muted">لا توجد طلبات إجازة مطابقة.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($leaveRequests->hasPages())
            <div class="card-footer bg-white">{{ $leaveRequests->links() }}</div>
        @endif
    </div>
</div>

{{-- Modal: رفع إجازة بالنيابة عن موظف --}}
<div class="modal fade" id="proxyLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.leaves.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title fw-bold">رفع طلب إجازة بالنيابة عن موظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">الموظف <span class="text-danger">*</span></label>
                        <select name="hr_employee_id" class="form-select" required>
                            <option value="">-- اختر الموظف --</option>
                            @foreach($employeesForProxy as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->department->name ?? 'بدون قسم' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">نوع الإجازة <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select" required>
                            <option value="annual">اعتيادية</option>
                            <option value="sick">مرضية</option>
                            <option value="emergency">طارئة</option>
                            <option value="unpaid">بدون راتب</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">من تاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">إلى تاريخ <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold">السبب <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold">حفظ وإرسال للموافقات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
