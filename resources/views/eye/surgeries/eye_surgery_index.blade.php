@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-primary mb-1">
                <i class="fas fa-procedures me-2"></i>جدول عمليات وإجراءات وحقن العيون
            </h2>
            <p class="text-muted mb-0 small">متابعة مواعيد عمليات الماء الأبيض (الفـاكو)، زراعة العدسات IOL، جلسات الحقن الشبكي، وجراحات العيون التخصصية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.store.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-boxes me-1"></i>مخزن العيون والعدسات
            </a>
            <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-1"></i>طابور العيون
            </a>
            <a href="{{ route('eye.surgeries.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i>حجز عملية / حقن جديد
            </a>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-primary">
                <span class="text-muted small fw-semibold">إجمالي العمليات</span>
                <h3 class="fw-bold text-dark my-1">{{ $stats['total'] ?? 0 }}</h3>
                <span class="small text-muted">سجل العمليات الكلي</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-info">
                <span class="text-muted small fw-semibold">عمليات اليوم</span>
                <h3 class="fw-bold text-info my-1">{{ $stats['today'] ?? 0 }}</h3>
                <span class="small text-muted">{{ date('Y-m-d') }}</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-warning">
                <span class="text-muted small fw-semibold">قيد الانتظار</span>
                <h3 class="fw-bold text-warning my-1">{{ $stats['scheduled'] ?? 0 }}</h3>
                <span class="small text-muted">مجدولة للعمليات</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-success">
                <span class="text-muted small fw-semibold">المنجزة</span>
                <h3 class="fw-bold text-success my-1">{{ $stats['completed'] ?? 0 }}</h3>
                <span class="small text-muted">عمليات ناجحة</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-teal" style="border-left-color: #20c997 !important;">
                <span class="text-muted small fw-semibold">ماء أبيض (فاكو)</span>
                <h3 class="fw-bold text-teal my-1" style="color: #20c997;">{{ $stats['cataract'] ?? 0 }}</h3>
                <span class="small text-muted">زراعة عدسات IOL</span>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white p-3 text-center border-start border-4 border-indigo" style="border-left-color: #6610f2 !important;">
                <span class="text-muted small fw-semibold">حقن شبكي</span>
                <h3 class="fw-bold my-1" style="color: #6610f2;">{{ $stats['injections'] ?? 0 }}</h3>
                <span class="small text-muted">Anti-VEGF جلسات</span>
            </div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('eye.surgeries.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="بحث باسم المريض، الرقم الطبي، أو الإجراء..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-calendar-alt text-muted"></i></span>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>⏳ مجدولة (Scheduled)</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>⚡ جارية الآن (In Progress)</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ مكتملة (Completed)</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>❌ ملغاة (Cancelled)</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-3">
                        <i class="fas fa-filter me-1"></i>تصفية
                    </button>
                    <a href="{{ route('eye.surgeries.index') }}" class="btn btn-light border px-3">
                        <i class="fas fa-undo me-1"></i>إعادة ضبط
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Surgeries Table -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-alt text-primary me-2"></i>قائمة العمليات والإجراءات</h5>
            <span class="badge bg-light text-dark border">العدد: {{ $surgeries->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 140px;">موعد العملية</th>
                        <th>المريض</th>
                        <th>الإجراء الجراحي</th>
                        <th class="text-center" style="width: 110px;">العين</th>
                        <th>الجراح</th>
                        <th>التخدير</th>
                        <th>العدسة / الدواء</th>
                        <th class="text-center">الحالة</th>
                        <th class="text-center" style="width: 130px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($surgeries as $surgery)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $surgery->surgery_date->format('Y-m-d') }}</div>
                            <div class="small text-muted">{{ $surgery->surgery_date->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $surgery->patient->name }}</div>
                            <div class="small text-muted">
                                <span>MRN: {{ $surgery->patient->medical_record_number ?? 'N/A' }}</span>
                                @if($surgery->patient->phone)
                                    <span> • {{ $surgery->patient->phone }}</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $surgery->procedure_name }}</div>
                            @if(str_contains($surgery->procedure_name, 'ماء أبيض') || str_contains($surgery->procedure_name, 'Phaco'))
                                <span class="badge bg-primary-subtle text-primary small">فاكو + عدسة</span>
                            @elseif(str_contains($surgery->procedure_name, 'حقن') || str_contains($surgery->procedure_name, 'Inject'))
                                <span class="badge bg-purple-subtle text-purple small" style="background-color: #e0dcfc; color: #5236ab;">حقن شبكي</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($surgery->target_eye === 'OD')
                                <span class="badge px-3 py-2 bg-success text-white shadow-sm" title="العين اليمنى">
                                    <i class="fas fa-eye me-1"></i>OD - اليمنى
                                </span>
                            @elseif($surgery->target_eye === 'OS')
                                <span class="badge px-3 py-2 bg-info text-white shadow-sm" title="العين اليسرى">
                                    <i class="fas fa-eye me-1"></i>OS - اليسرى
                                </span>
                            @else
                                <span class="badge px-3 py-2 bg-secondary text-white shadow-sm" title="كلتا العينين">
                                    <i class="fas fa-eye me-1"></i>OU - العينان
                                </span>
                            @endif
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $surgery->doctor->user->name ?? 'طبيب العيون' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $surgery->anesthesia_type }}</span>
                        </td>
                        <td>
                            @if($surgery->iolItem)
                                <div class="small">
                                    <span class="fw-bold text-teal" style="color: #0f766e;"><i class="fas fa-circle-notch me-1"></i>{{ $surgery->iolItem->item_name }}</span>
                                    @if($surgery->iol_power)
                                        <div class="text-muted small">Power: <strong>+{{ $surgery->iol_power }} D</strong></div>
                                    @endif
                                </div>
                            @elseif($surgery->injection_drug)
                                <div class="small">
                                    <span class="fw-bold text-indigo" style="color: #4338ca;"><i class="fas fa-syringe me-1"></i>{{ $surgery->injection_drug }}</span>
                                    @if($surgery->injection_dose)
                                        <div class="text-muted small">Dose: {{ $surgery->injection_dose }}</div>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($surgery->status === 'scheduled')
                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>مجدولة</span>
                            @elseif($surgery->status === 'in_progress')
                                <span class="badge bg-primary text-white"><i class="fas fa-spinner fa-spin me-1"></i>جارية الآن</span>
                            @elseif($surgery->status === 'completed')
                                <span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>مكتملة</span>
                            @else
                                <span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>ملغاة</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('eye.surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-primary" title="عرض تفاصيل وسجل العملية">
                                <i class="fas fa-notes-medical me-1"></i>سجل العملية
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5 text-muted">
                            <i class="fas fa-procedures fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="h5">لا توجد عمليات مسجلة في هذه الفترة</div>
                            <p class="small text-muted">يمكنك إضافة حجز عملية أو جلسة حقن جديدة عبر الزر أعلاه</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($surgeries->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $surgeries->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
