@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="radiology-hub-content">
    
    <!-- 1. Header & Live Indicator -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3">
                    <i class="fas fa-x-ray fa-2x"></i>
                </div>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        قسم الأشعة والتصوير الطبي
                        @if($selectedCategory === 'ultrasound')
                            <span class="badge bg-info text-white fs-6 ms-2"><i class="fas fa-wave-square me-1"></i> السونار والدوبلر</span>
                        @elseif($selectedCategory === 'mri')
                            <span class="badge bg-purple text-white fs-6 ms-2" style="background-color: #6f42c1;"><i class="fas fa-magnet me-1"></i> الرنين والمفراس</span>
                        @elseif($selectedCategory === 'echo')
                            <span class="badge bg-danger text-white fs-6 ms-2"><i class="fas fa-heartbeat me-1"></i> إيكو القلب</span>
                        @elseif($selectedCategory === 'radiology')
                            <span class="badge bg-secondary text-white fs-6 ms-2"><i class="fas fa-film me-1"></i> الأشعة السينية</span>
                        @else
                            <span class="badge bg-primary text-white fs-6 ms-2"><i class="fas fa-hospital me-1"></i> المركز العام</span>
                        @endif
                    </h3>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle py-1 px-2">
                            <i class="fas fa-circle fa-xs me-1 text-success"></i> طابور مباشر
                        </span>
                        <small class="text-muted" id="last-update-time">آخر تحديث: {{ now()->format('H:i:s') }}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Category Switcher (Admins & General Roles) -->
        @if(auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'receptionist']) || empty($userCategory))
            <div class="d-flex flex-wrap gap-1 bg-white p-1 rounded-pill shadow-xs border">
                <a href="{{ route('radiology-staff.index', ['category' => 'all', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'all' ? 'btn-primary shadow-xs' : 'btn-light text-dark' }}">
                    🌐 الكل
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'ultrasound', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'ultrasound' ? 'btn-info text-white shadow-xs' : 'btn-light text-dark' }}">
                    🔊 السونار
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'radiology', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'radiology' ? 'btn-secondary text-white shadow-xs' : 'btn-light text-dark' }}">
                    🩻 الأشعة
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'mri', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'mri' ? 'btn-dark text-white shadow-xs' : 'btn-light text-dark' }}">
                    🧲 الرنين
                </a>
                <a href="{{ route('radiology-staff.index', ['category' => 'echo', 'tab' => $activeTab, 'date' => $dateFilter]) }}" 
                   class="btn btn-sm rounded-pill px-3 fw-bold {{ $selectedCategory === 'echo' ? 'btn-danger text-white shadow-xs' : 'btn-light text-dark' }}">
                    ❤️ الإيكو
                </a>
            </div>
        @endif
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. KPI Summary Cards Bar -->
    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-primary h-100">
                <small class="text-muted d-block font-sans">إجمالي الطلبات</small>
                <div class="h4 mb-0 fw-bold text-dark">{{ $stats['total'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-warning h-100">
                <small class="text-muted d-block font-sans">طابور الانتظار</small>
                <div class="h4 mb-0 fw-bold text-warning">{{ $stats['waiting'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-info h-100">
                <small class="text-muted d-block font-sans">قيد الفحص والتقرير</small>
                <div class="h4 mb-0 fw-bold text-info">{{ $stats['in_progress'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-success h-100">
                <small class="text-muted d-block font-sans">المكتملة اليوم</small>
                <div class="h4 mb-0 fw-bold text-success">{{ $stats['completed'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-success h-100 bg-success-subtle">
                <small class="text-success fw-bold d-block font-sans">مسدد بالكاشير</small>
                <div class="h4 mb-0 fw-bold text-success">{{ $stats['paid'] }}</div>
            </div>
        </div>
        <div class="col-6 col-md-3 col-xl-2">
            <div class="card border-0 shadow-xs rounded-3 bg-white p-2 text-center border-start border-4 border-danger h-100 bg-danger-subtle">
                <small class="text-danger fw-bold d-block font-sans">بانتظار الصندوق</small>
                <div class="h4 mb-0 fw-bold text-danger">{{ $stats['unpaid'] }}</div>
            </div>
        </div>
    </div>

    <!-- 3. Navigation Tabs & Search Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-3">
        <div class="card-body p-2 p-md-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                
                <!-- Main Status Tabs -->
                <ul class="nav nav-pills gap-1" id="radiologyHubTabs">
                    <li class="nav-item">
                        <a href="{{ route('radiology-staff.index', ['tab' => 'waiting', 'category' => $selectedCategory, 'date' => $dateFilter, 'search' => $search]) }}" 
                           class="nav-link rounded-pill px-3 py-2 fw-bold {{ $activeTab === 'waiting' ? 'active bg-warning text-dark shadow-xs' : 'bg-light text-dark' }}">
                            <i class="fas fa-hourglass-half me-1"></i> طابور الانتظار
                            <span class="badge {{ $activeTab === 'waiting' ? 'bg-dark text-white' : 'bg-warning text-dark' }} ms-1">{{ $stats['waiting'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('radiology-staff.index', ['tab' => 'in_progress', 'category' => $selectedCategory, 'date' => $dateFilter, 'search' => $search]) }}" 
                           class="nav-link rounded-pill px-3 py-2 fw-bold {{ $activeTab === 'in_progress' ? 'active bg-info text-white shadow-xs' : 'bg-light text-dark' }}">
                            <i class="fas fa-stethoscope me-1"></i> قيد الفحص والتقرير
                            <span class="badge bg-white text-info ms-1">{{ $stats['in_progress'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('radiology-staff.index', ['tab' => 'completed', 'category' => $selectedCategory, 'date' => $dateFilter, 'search' => $search]) }}" 
                           class="nav-link rounded-pill px-3 py-2 fw-bold {{ $activeTab === 'completed' ? 'active bg-success text-white shadow-xs' : 'bg-light text-dark' }}">
                            <i class="fas fa-check-circle me-1"></i> المكتملة والطباعة
                            <span class="badge bg-white text-success ms-1">{{ $stats['completed'] }}</span>
                        </a>
                    </li>
                    @if(($stats['emergency'] ?? 0) > 0)
                    <li class="nav-item">
                        <a href="#emergency-section" 
                           class="nav-link rounded-pill px-3 py-2 fw-bold bg-danger-subtle text-danger border border-danger-subtle">
                            <i class="fas fa-ambulance me-1"></i> طوارئ
                            <span class="badge bg-danger text-white ms-1">{{ $stats['emergency'] }}</span>
                        </a>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route('radiology-staff.index', ['tab' => 'all', 'category' => $selectedCategory, 'date' => $dateFilter, 'search' => $search]) }}" 
                           class="nav-link rounded-pill px-3 py-2 fw-bold {{ $activeTab === 'all' ? 'active bg-primary text-white shadow-xs' : 'bg-light text-dark' }}">
                            <i class="fas fa-list me-1"></i> الكل
                            <span class="badge {{ $activeTab === 'all' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $stats['total'] }}</span>
                        </a>
                    </li>
                </ul>

                <!-- Filter & Search Form -->
                <form action="{{ route('radiology-staff.index') }}" method="GET" class="d-flex flex-wrap align-items-center gap-2">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="hidden" name="category" value="{{ $selectedCategory }}">
                    
                    <!-- Date Filter -->
                    <select name="date" class="form-select form-select-sm" style="width: 130px;" onchange="this.form.submit()">
                        <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>📅 اليوم</option>
                        <option value="all" {{ $dateFilter === 'all' ? 'selected' : '' }}>🗂️ كل التواريخ</option>
                    </select>

                    <!-- Search Input -->
                    <div class="input-group input-group-sm" style="min-width: 220px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" value="{{ $search }}" class="form-control border-start-0" placeholder="بحث بالمريض / الفحص...">
                        @if(!empty($search))
                            <a href="{{ route('radiology-staff.index', ['tab' => $activeTab, 'category' => $selectedCategory, 'date' => $dateFilter]) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- 4. Main Requests Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white mb-4">
        <div class="card-header bg-white border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-dark">
                @if($activeTab === 'waiting')
                    <i class="fas fa-hourglass-half text-warning me-2"></i> طابور الانتظار الحالي
                @elseif($activeTab === 'in_progress')
                    <i class="fas fa-stethoscope text-info me-2"></i> الفحوصات الجارية الآن وكتابة النتائج
                @elseif($activeTab === 'completed')
                    <i class="fas fa-check-circle text-success me-2"></i> الفحوصات المكتملة الجاهزة للطباعة
                @else
                    <i class="fas fa-list text-primary me-2"></i> جميع طلبات الأشعة والتصوير
                @endif
                <span class="badge bg-secondary-subtle text-secondary ms-2">{{ $requests->total() }}</span>
            </h6>
        </div>
        <div class="card-body p-0">
            @if($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                        <thead class="table-light text-secondary">
                            <tr>
                                <th style="width: 70px;" class="text-center">#</th>
                                <th>المريض</th>
                                <th>الفحوصات المطلوبة</th>
                                <th style="width: 110px;">القسم</th>
                                <th>الطبيب المحوّل</th>
                                <th style="width: 80px;" class="text-center">الوقت</th>
                                <th style="width: 120px;" class="text-center">حالة الدفع</th>
                                <th style="width: 100px;" class="text-center">الحالة</th>
                                <th style="width: 150px;" class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $req)
                                @php
                                    $isPaid = $req->payment_status === 'paid';
                                    $radNames = $req->radiology_names;
                                    $patient = $req->visit?->patient;
                                    $patientName = $patient?->name ?? $patient?->user?->name ?? 'مريض غير محدد';
                                @endphp
                                <tr class="{{ !$isPaid ? 'table-danger bg-opacity-25' : ($req->status === 'in_progress' ? 'table-info bg-opacity-25' : '') }}">
                                    <!-- Request ID -->
                                    <td class="text-center fw-bold text-dark">
                                        #{{ $req->id }}
                                    </td>

                                    <!-- Patient Info -->
                                    <td>
                                        <div class="fw-bold text-dark">{{ $patientName }}</div>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                                            @if($patient?->gender)
                                                <span>{{ $patient->gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                                            @endif
                                            @if($patient?->age)
                                                <span> • {{ $patient->age }} سنة</span>
                                            @endif
                                            @if($patient?->medical_number)
                                                <span class="badge bg-light text-muted border py-0 px-1 font-monospace ms-1">{{ $patient->medical_number }}</span>
                                            @endif
                                        </small>
                                    </td>

                                    <!-- Radiology Tests List -->
                                    <td>
                                        @if(!empty($radNames))
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($radNames as $rName)
                                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold py-1 px-2">
                                                        <i class="fas fa-x-ray fa-xs me-1"></i> {{ $rName }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="text-muted">{{ $req->description ?: 'طلب أشعة' }}</span>
                                        @endif
                                        @if($req->priority === 'emergency')
                                            <span class="badge bg-danger text-white ms-1">🚨 طارئ</span>
                                        @elseif($req->priority === 'urgent')
                                            <span class="badge bg-warning text-dark ms-1">⚠️ عاجل</span>
                                        @endif
                                    </td>

                                    <!-- Modality Badge -->
                                    <td>
                                        @if($req->subtype === 'ultrasound')
                                            <span class="badge bg-info text-white"><i class="fas fa-wave-square me-1"></i> سونار</span>
                                        @elseif($req->subtype === 'mri')
                                            <span class="badge bg-purple text-white" style="background-color: #6f42c1;"><i class="fas fa-magnet me-1"></i> رنين</span>
                                        @elseif($req->subtype === 'ct')
                                            <span class="badge bg-dark text-white"><i class="fas fa-circle-notch me-1"></i> مفراس</span>
                                        @elseif($req->subtype === 'echo')
                                            <span class="badge bg-danger text-white"><i class="fas fa-heartbeat me-1"></i> إيكو</span>
                                        @else
                                            <span class="badge bg-secondary text-white"><i class="fas fa-film me-1"></i> أشعة عامة</span>
                                        @endif
                                    </td>

                                    <!-- Ordering Doctor -->
                                    <td>
                                        <div class="text-dark small fw-semibold">
                                            د. {{ $req->visit?->doctor?->user?->name ?? 'الاستشارية / الطوارئ' }}
                                        </div>
                                        <small class="text-muted" style="font-size: 0.72rem;">
                                            {{ $req->visit?->department?->name ?? 'العيادة' }}
                                        </small>
                                    </td>

                                    <!-- Time -->
                                    <td class="text-center font-monospace small">
                                        {{ $req->created_at ? $req->created_at->format('H:i') : '—' }}
                                    </td>

                                    <!-- Payment Status -->
                                    <td class="text-center">
                                        @if($isPaid)
                                            <span class="badge bg-success text-white py-1 px-2 shadow-xs fw-bold">
                                                <i class="fas fa-check-circle me-1"></i> مدفوع
                                            </span>
                                        @else
                                            <span class="badge bg-danger text-white py-1 px-2 fw-bold" title="بانتظار السداد بالكاشير">
                                                <i class="fas fa-clock me-1"></i> غير مدفوع
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Request Status -->
                                    <td class="text-center">
                                        @if($req->status === 'completed')
                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2 fw-bold">
                                                ✅ مكتمل
                                            </span>
                                        @elseif($req->status === 'in_progress')
                                            <span class="badge bg-info-subtle text-info border border-info-subtle py-1 px-2 fw-bold">
                                                🩺 قيد الفحص
                                            </span>
                                        @elseif($req->status === 'pending_service_selection')
                                            <span class="badge bg-secondary-subtle text-secondary border py-1 px-2">
                                                بانتظار تحديد
                                            </span>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle py-1 px-2">
                                                ⏳ بالانتظار
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            @if($req->status === 'pending' || $req->status === 'pending_service_selection' || $req->status === 'scheduled')
                                                @if($isPaid)
                                                    <form action="{{ route('radiology-staff.start', $req) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm px-2 fw-bold shadow-xs" title="بدء الفحص واستدعاء المريض">
                                                            <i class="fas fa-play me-1"></i> بدء الفحص
                                                        </button>
                                                    </form>
                                                @else
                                                    <a href="{{ route('radiology-staff.show', $req) }}" class="btn btn-outline-secondary btn-sm px-2" title="معاينة الطلب">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                @endif
                                            @elseif($req->status === 'in_progress')
                                                <a href="{{ route('radiology-staff.show', $req) }}" class="btn btn-success btn-sm px-2 fw-bold shadow-xs" title="إدخال التقرير والنتائج">
                                                    <i class="fas fa-edit me-1"></i> كتابة التقرير
                                                </a>
                                            @elseif($req->status === 'completed')
                                                <a href="{{ route('radiology-staff.show', $req) }}" class="btn btn-outline-info btn-sm px-2" title="عرض النتيجة">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('radiology-staff.show', $req) }}" class="btn btn-outline-secondary btn-sm px-2" title="طباعة النتيجة">
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

                <!-- Pagination -->
                @if($requests->hasPages())
                    <div class="d-flex justify-content-center border-top py-3">
                        {{ $requests->appends(request()->query())->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-5">
                    <i class="fas fa-x-ray fa-3x text-muted mb-3 opacity-50"></i>
                    <h5 class="text-muted fw-bold">لا توجد طلبات في هذا التبويب حالياً</h5>
                    <p class="text-muted small mb-0">ستظهر الطلبات الجديدة تلقائياً فور تحويلها من العيادات الاستشارية أو الطوارئ.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- 5. Emergency Radiology Section (If any) -->
    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
    <div class="card border-0 shadow-sm rounded-3 bg-white border-start border-4 border-danger mb-4" id="emergency-section">
        <div class="card-header bg-danger bg-opacity-10 border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
            <h6 class="mb-0 fw-bold text-danger">
                <i class="fas fa-ambulance me-2"></i> طلبات أشعة الطوارئ العاجلة
            </h6>
            <span class="badge bg-danger">{{ $emergencyRadiologyRequests->count() }}</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.88rem;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 90px;" class="text-center">رقم طوارئ</th>
                            <th>المريض</th>
                            <th>الفحوصات المطلوبة</th>
                            <th style="width: 90px;" class="text-center">الأولوية</th>
                            <th style="width: 80px;" class="text-center">الوقت</th>
                            <th style="width: 100px;" class="text-center">الحالة</th>
                            <th style="width: 120px;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emergencyRadiologyRequests as $emReq)
                        <tr class="{{ $emReq->status === 'pending' ? 'table-warning' : ($emReq->status === 'in_progress' ? 'table-info' : '') }}">
                            <td class="text-center fw-bold text-danger">#{{ $emReq->emergency_id }}</td>
                            <td class="fw-bold">{{ $emReq->patient->user->name ?? $emReq->patient->name ?? 'مريض' }}</td>
                            <td>
                                @foreach($emReq->radiologyTypes as $type)
                                    <span class="badge bg-info text-white me-1">{{ $type->name }}</span>
                                @endforeach
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $emReq->priority === 'critical' ? 'danger' : 'warning text-dark' }}">
                                    {{ $emReq->priority_text }}
                                </span>
                            </td>
                            <td class="text-center small font-monospace">{{ optional($emReq->requested_at)->format('H:i') }}</td>
                            <td class="text-center">
                                <span class="{{ $emReq->status_badge_class }}">{{ $emReq->status_text }}</span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    @if($emReq->status === 'pending')
                                        <form action="{{ route('staff.emergency-radiology.start', $emReq) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="بدء الفحص">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @elseif($emReq->status === 'in_progress')
                                        <a href="{{ route('staff.emergency-radiology.show', $emReq) }}" class="btn btn-success btn-sm" title="إدخال النتائج">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @elseif($emReq->status === 'completed')
                                        <a href="{{ route('staff.emergency-radiology.print', $emReq) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="طباعة">
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
    @endif

</div>

<!-- Realtime Queue Polling -->
<script>
let autoRefreshTimer = setInterval(function() {
    // Only refresh if no modal or input is focused
    if ($('input:focus, select:focus, textarea:focus').length === 0) {
        $.ajax({
            url: window.location.href,
            success: function(response) {
                const parser = new DOMParser();
                const doc = parser.parseFromString(response, 'text/html');
                const newContent = doc.getElementById('radiology-hub-content');
                if (newContent) {
                    const scroll = window.scrollY;
                    $('#radiology-hub-content').html($(newContent).html());
                    window.scrollTo(0, scroll);
                    $('#last-update-time').text('آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ'));
                }
            }
        });
    }
}, 15000);
</script>
@endsection
