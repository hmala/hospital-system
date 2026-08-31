<!-- resources/views/radiology/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-x-ray me-2 text-primary"></i>
                    إدارة قسم الأشعة
                </h2>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-success">
                        <i class="fas fa-circle fa-xs me-1"></i> مباشر
                    </span>
                    @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist() || Auth::user()->isDoctor())
                    <a href="{{ route('radiology.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>طلب إشعة جديد
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- تبويبات الفلترة السريعة وفلتر البحث والتاريخ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <!-- التبويبات -->
                <ul class="nav nav-pills gap-2" id="radiologyTabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active bg-primary text-white fw-bold" id="all-tab" data-bs-toggle="pill" data-bs-target="#tab-all" type="button">
                            <i class="fas fa-list me-1"></i> جميع الطلبات 
                            <span class="badge bg-white text-primary ms-1">{{ $requests->total() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link bg-light text-dark fw-bold" id="pending-tab" data-bs-toggle="pill" data-bs-target="#tab-pending" type="button">
                            <i class="fas fa-clock me-1 text-warning"></i> بانتظار الإجراء
                            <span class="badge bg-warning text-dark ms-1">{{ $requests->where('status', 'pending')->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link bg-light text-dark fw-bold" id="progress-tab" data-bs-toggle="pill" data-bs-target="#tab-progress" type="button">
                            <i class="fas fa-play me-1 text-info"></i> قيد التنفيذ
                            <span class="badge bg-info text-white ms-1">{{ $requests->where('status', 'in_progress')->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link bg-light text-dark fw-bold" id="completed-tab" data-bs-toggle="pill" data-bs-target="#tab-completed" type="button">
                            <i class="fas fa-check-circle me-1 text-success"></i> المكتملة
                            <span class="badge bg-success text-white ms-1">{{ $requests->where('status', 'completed')->count() }}</span>
                        </button>
                    </li>
                    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
                    <li class="nav-item">
                        <button class="nav-link bg-light text-dark fw-bold" id="emergency-tab" data-bs-toggle="pill" data-bs-target="#tab-emergency" type="button">
                            <i class="fas fa-ambulance me-1 text-danger"></i> طوارئ
                            <span class="badge bg-danger text-white ms-1">{{ $emergencyRadiologyRequests->count() }}</span>
                        </button>
                    </li>
                    @endif
                </ul>

                <!-- نموذج البحث -->
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="input-group" style="min-width: 260px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" id="radiologyTableSearch" class="form-control border-start-0" placeholder="بحث سريع بالمريض أو نوع الفحص...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- طلبات الطوارئ العاجلة (تنبيه علوي إذا وجدت) -->
    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress'])->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="border-radius: 12px; border-right: 5px solid #dc3545 !important;">
                <div class="card-header bg-danger bg-opacity-10 text-danger d-flex justify-content-between align-items-center py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-ambulance me-2"></i>
                        طلبات أشعة الطوارئ العاجلة
                        <span class="badge bg-danger ms-2">{{ $emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress'])->count() }}</span>
                    </h5>
                    <small class="fw-bold">يرجى المعالجة فوراً</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th class="text-start">المريض</th>
                                    <th>أنواع الأشعة</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>وقت الطلب</th>
                                    <th style="width: 100px;">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress']) as $emRequest)
                                <tr class="{{ $emRequest->priority == 'critical' ? 'table-danger' : 'table-warning' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold; font-size: 1.1rem;">
                                                {{ mb_substr(optional($emRequest->patient)->user->name ?? $emRequest->emergency->patient_name ?? 'ط', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong class="text-dark">{{ optional($emRequest->patient)->user->name ?? $emRequest->emergency->patient_name ?? 'مريض طوارئ' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone-alt fa-xs me-1"></i>{{ optional($emRequest->patient)->user->phone ?? $emRequest->emergency->phone ?? 'لا يوجد رقم' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @foreach($emRequest->radiologyTypes as $type)
                                            <span class="badge bg-danger bg-opacity-75 me-1 mb-1">{{ $type->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->priority == 'critical' ? 'danger' : 'warning text-dark' }} px-3 py-2">
                                            <i class="fas fa-bolt me-1"></i>{{ $emRequest->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->status_badge_class }} px-3 py-2">
                                            {{ $emRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted fw-semibold">
                                            <i class="fas fa-clock me-1"></i>{{ $emRequest->requested_at->format('H:i') }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $emRequest->requested_at->format('Y-m-d') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('staff.emergency-radiology.show', $emRequest) }}" class="btn btn-primary px-3 shadow-sm" title="عرض وإجراء الفحص">
                                                <i class="fas fa-eye me-1"></i> عرض
                                            </a>
                                            @if($emRequest->status == 'completed')
                                            <a href="{{ route('staff.emergency-radiology.print', $emRequest) }}" target="_blank" class="btn btn-success" title="طباعة">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- محتوى الجداول حسب التبويب -->
    <div class="tab-content" id="radiologyTabContent">
        <!-- التبويب: الكل -->
        <div class="tab-pane fade show active" id="tab-all" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark">
                            <i class="fas fa-th-list me-2 text-primary"></i>قائمة طلبات الفحص الإشعاعي
                        </h5>
                        <span class="text-muted small">إجمالي السجلات: {{ $requests->total() }}</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0" id="mainRadiologyTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">#</th>
                                    <th class="text-start">المريض</th>
                                    <th>نوع الفحص الإشعاعي</th>
                                    <th>الطبيب المحول</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>وقت الطلب</th>
                                    <th style="width: 110px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr class="request-row status-{{ $request->status }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 42px; height: 42px; font-weight: bold; font-size: 1.15rem; min-width: 42px;">
                                                {{ mb_substr($request->patient->user->name ?? '؟', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong class="text-dark fs-6">{{ $request->patient->user->name ?? 'مريض غير معروف' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone-alt fa-xs me-1"></i>{{ $request->patient->user->phone ?? 'لا يوجد رقم' }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary mb-1">
                                            {{ $request->radiologyType->category ?? 'أشعة عامة' }}
                                        </span>
                                        <br>
                                        <strong class="text-primary">{{ $request->radiologyType->name ?? '-' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $request->radiologyType->code ?? '' }}</small>
                                    </td>
                                    <td>
                                        @if($request->doctor)
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-user-md me-1 text-success"></i>د. {{ $request->doctor->user->name }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-muted border">
                                                <i class="fas fa-hospital me-1"></i>من الاستعلامات
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->priority_color }} px-3 py-2">
                                            {{ $request->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->status_color }} px-3 py-2">
                                            {{ $request->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted fw-semibold">
                                            <i class="fas fa-calendar-alt me-1"></i>{{ $request->requested_date->format('Y-m-d') }}
                                        </span>
                                        <br>
                                        <small class="text-muted">{{ $request->requested_date->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm shadow-sm">
                                            <a href="{{ route('radiology.show', $request) }}" class="btn btn-primary px-3" title="عرض التفاصيل وإجراء الفحص">
                                                <i class="fas fa-eye me-1"></i> عرض
                                            </a>

                                            @if($request->status === 'completed' && $request->result)
                                            <a href="{{ route('radiology.print', $request) }}" target="_blank" class="btn btn-success" title="طباعة التقرير">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @endif

                                            @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                                                @if($request->status === 'pending')
                                                <a href="{{ route('radiology.edit', $request) }}" class="btn btn-warning" title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('radiology.destroy', $request) }}" method="POST" class="d-inline">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="fas fa-x-ray fa-4x mb-3 text-secondary opacity-50"></i>
                                        <h5 class="fw-bold">لا توجد طلبات أشعة مسجلة حالياً</h5>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($requests->hasPages())
                    <div class="d-flex justify-content-center p-3 border-top">
                        {{ $requests->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- التبويب: بانتظار الإجراء -->
        <div class="tab-pane fade" id="tab-pending" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-warning bg-opacity-10 py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-clock text-warning me-2"></i>طلبات بانتظار الإجراء
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-start">المريض</th>
                                    <th>نوع الفحص</th>
                                    <th>الطبيب</th>
                                    <th>الأولوية</th>
                                    <th>وقت الطلب</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests->where('status', 'pending') as $request)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-warning text-dark d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                                {{ mb_substr($request->patient->user->name ?? '؟', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $request->patient->user->name ?? 'مريض غير معروف' }}</strong><br>
                                                <small class="text-muted">{{ $request->patient->user->phone ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="text-primary">{{ $request->radiologyType->name ?? '-' }}</strong></td>
                                    <td>{{ $request->doctor ? 'د. ' . $request->doctor->user->name : 'الاستعلامات' }}</td>
                                    <td><span class="badge bg-{{ $request->priority_color }}">{{ $request->priority_text }}</span></td>
                                    <td>{{ $request->requested_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('radiology.show', $request) }}" class="btn btn-sm btn-primary px-3">
                                            <i class="fas fa-eye me-1"></i> عرض
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center py-4 text-muted">لا توجد طلبات معلقة</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- التبويب: قيد التنفيذ -->
        <div class="tab-pane fade" id="tab-progress" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-info bg-opacity-10 py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-play text-info me-2"></i>طلبات قيد التنفيذ داخل الغرفة
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-start">المريض</th>
                                    <th>نوع الفحص</th>
                                    <th>الطبيب</th>
                                    <th>وقت الطلب</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests->where('status', 'in_progress') as $request)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-info text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                                {{ mb_substr($request->patient->user->name ?? '؟', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $request->patient->user->name ?? 'مريض غير معروف' }}</strong><br>
                                                <small class="text-muted">{{ $request->patient->user->phone ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="text-primary">{{ $request->radiologyType->name ?? '-' }}</strong></td>
                                    <td>{{ $request->doctor ? 'د. ' . $request->doctor->user->name : 'الاستعلامات' }}</td>
                                    <td>{{ $request->requested_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('radiology.show', $request) }}" class="btn btn-sm btn-primary px-3">
                                            <i class="fas fa-eye me-1"></i> إكمال الفحص
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد طلبات قيد التنفيذ حالياً</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- التبويب: المكتملة -->
        <div class="tab-pane fade" id="tab-completed" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-success bg-opacity-10 py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-check-circle text-success me-2"></i>الفحوصات المكتملة
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-start">المريض</th>
                                    <th>نوع الفحص</th>
                                    <th>الطبيب</th>
                                    <th>وقت الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests->where('status', 'completed') as $request)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                                {{ mb_substr($request->patient->user->name ?? '؟', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ $request->patient->user->name ?? 'مريض غير معروف' }}</strong><br>
                                                <small class="text-muted">{{ $request->patient->user->phone ?? '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="text-primary">{{ $request->radiologyType->name ?? '-' }}</strong></td>
                                    <td>{{ $request->doctor ? 'د. ' . $request->doctor->user->name : 'الاستعلامات' }}</td>
                                    <td>{{ $request->requested_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('radiology.show', $request) }}" class="btn btn-primary px-2">
                                                <i class="fas fa-eye me-1"></i> عرض
                                            </a>
                                            <a href="{{ route('radiology.print', $request) }}" target="_blank" class="btn btn-success px-2">
                                                <i class="fas fa-print me-1"></i> طباعة
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد فحوصات مكتملة</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
        <!-- التبويب: جميع طلبات الطوارئ -->
        <div class="tab-pane fade" id="tab-emergency" role="tabpanel">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-danger bg-opacity-10 py-3 border-bottom">
                    <h5 class="mb-0 fw-bold text-danger">
                        <i class="fas fa-ambulance me-2"></i>سجل أشعة الطوارئ
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-center mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-start">المريض</th>
                                    <th>أنواع الأشعة</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>وقت الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyRadiologyRequests as $emRequest)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center me-3 shadow-sm" style="width: 40px; height: 40px; font-weight: bold;">
                                                {{ mb_substr(optional($emRequest->patient)->user->name ?? $emRequest->emergency->patient_name ?? 'ط', 0, 1) }}
                                            </div>
                                            <div>
                                                <strong>{{ optional($emRequest->patient)->user->name ?? $emRequest->emergency->patient_name ?? 'مريض طوارئ' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ optional($emRequest->patient)->user->phone ?? $emRequest->emergency->phone ?? 'لا يوجد رقم' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @foreach($emRequest->radiologyTypes as $type)
                                            <span class="badge bg-info me-1">{{ $type->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->priority == 'critical' ? 'danger' : 'warning text-dark' }}">
                                            {{ $emRequest->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->status_badge_class }}">
                                            {{ $emRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $emRequest->requested_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('staff.emergency-radiology.show', $emRequest) }}" class="btn btn-primary px-3">
                                                <i class="fas fa-eye me-1"></i> عرض
                                            </a>
                                            @if($emRequest->status == 'completed')
                                            <a href="{{ route('staff.emergency-radiology.print', $emRequest) }}" target="_blank" class="btn btn-success">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<script>
// بحث فوري داخل الجدول
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('radiologyTableSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#mainRadiologyTable tbody tr');
            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
@endsection