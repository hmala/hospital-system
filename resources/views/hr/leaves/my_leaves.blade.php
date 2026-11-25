@extends('layouts.app')
@section('title', 'بوابتي — طلبات الإجازة الإلكترونية')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-calendar-minus text-primary me-2"></i>بوابتي — طلبات الإجازة الإلكترونية
            </h4>
            <p class="text-muted small mb-0">تقديم طلب إجازة إلكترونياً ومتابعة موافقة مدير القسم ومدير الإدارة.</p>
        </div>
        @if($employee)
            <div class="d-flex gap-2">
                <a href="{{ route('my.roster') }}" class="btn btn-outline-info text-dark fw-bold btn-sm">
                    <i class="fas fa-calendar-alt me-1"></i> جدولي الشهري
                </a>
                <button class="btn btn-primary fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#submitMyLeaveModal">
                    <i class="fas fa-plus me-1"></i> تقديم طلب إجازة جديد
                </button>
            </div>
        @endif
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

    @if(!$employee)
        <div class="alert alert-warning border-0 shadow-sm p-4 text-center">
            <i class="fas fa-user-slash fa-2x mb-2"></i>
            <h6 class="fw-bold">حسابك غير مرتبط بملف موظف في الموارد البشرية (HR)</h6>
            <p class="mb-0 small">يرجى الطلب من قسم الموارد البشرية ربط حسابك الوظيفي في ملف الموظف لتفعيل تقديم الإجازات وعرض الجدول.</p>
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="text-muted small">الموظف / القسم</div>
                        <div class="fw-bold">{{ $employee->full_name }}</div>
                        <div class="small text-primary">{{ $employee->department->name ?? 'بدون قسم' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="text-muted small">مدير القسم المباشر</div>
                        <div class="fw-bold">{{ $employee->department?->manager?->name ?? 'يُرفع مباشرة لـ HR / مدير الإدارة' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="text-muted small">الرصيد السنوي الاسترشادي</div>
                        <div class="fw-bold text-success fs-5">{{ $employee->annual_leave_balance ?? 21 }} يوم</div>
                        <div class="small text-muted">تقييم النوع (براتب/بدون راتب) يحدده المدير</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h6 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>سجل طلباتي</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>النوع المعتمد</th>
                                <th>من تاريخ</th>
                                <th>إلى تاريخ</th>
                                <th>الأيام</th>
                                <th>الأجر</th>
                                <th class="text-start">السبب</th>
                                <th>الحالة</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($requests as $req)
                                <tr>
                                    <td class="font-monospace">#{{ $req->id }}</td>
                                    <td class="fw-bold">{{ $req->leave_type_label }}</td>
                                    <td>{{ $req->start_date->format('Y-m-d') }}</td>
                                    <td>{{ $req->end_date->format('Y-m-d') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $req->days_count }} يوم</span></td>
                                    <td>
                                        @if($req->is_paid)
                                            <span class="badge bg-success-subtle text-success border border-success">براتب</span>
                                        @else
                                            <span class="badge bg-danger-subtle text-danger border border-danger">بدون راتب</span>
                                        @endif
                                    </td>
                                    <td class="text-start">{{ Str::limit($req->reason, 60) }}</td>
                                    <td>
                                        <span class="badge {{ $req->status_badge_class }}">{{ $req->status_label }}</span>
                                        @if($req->dept_notes)
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">القسم: {{ $req->dept_notes }}</div>
                                        @endif
                                        @if($req->admin_notes)
                                            <div class="text-muted mt-1" style="font-size: 0.72rem;">الإدارة: {{ $req->admin_notes }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($req->status, ['pending_department', 'pending_admin']))
                                            <form action="{{ route('my.leaves.cancel', $req->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('إلغاء طلب الإجازة؟')">إلغاء</button>
                                            </form>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="9" class="py-4 text-muted">لم تقدم أي طلبات إجازة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($requests instanceof \Illuminate\Pagination\LengthAwarePaginator && $requests->hasPages())
                <div class="card-footer bg-white">{{ $requests->links() }}</div>
            @endif
        </div>
    @endif
</div>

@if($employee)
<div class="modal fade" id="submitMyLeaveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('my.leaves.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary bg-opacity-10">
                    <h5 class="modal-title fw-bold">تقديم طلب إجازة إلكتروني</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">نوع الإجازة المطلوب <span class="text-danger">*</span></label>
                        <select name="leave_type" class="form-select" required>
                            <option value="annual">إجازة اعتيادية</option>
                            <option value="sick">إجازة مرضية</option>
                            <option value="emergency">إجازة طارئة</option>
                            <option value="unpaid">إجازة بدون راتب</option>
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
                        <label class="form-label fw-bold">السبب / الملاحظات <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="اكتب سبب الإجازة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
