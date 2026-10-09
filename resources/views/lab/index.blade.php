@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" id="lab-requests-content">
    
    <!-- 1. الهيدر العلوي وشريط الإحصاءات السريعة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white p-2 rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 46px; height: 46px;">
                <i class="fas fa-flask fs-4"></i>
            </div>
            <div>
                <h4 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                    طلبات وفحوصات المختبر
                    <span class="badge bg-success shadow-sm" id="live-indicator" style="font-size: 0.72rem;">
                        <i class="fas fa-circle fa-xs me-1"></i> مباشر
                    </span>
                </h4>
                <small class="text-muted">
                    مرحباً <strong>{{ auth()->user()->name }}</strong> •
                    <span id="last-update" class="text-secondary">آخر تحديث: الآن</span>
                </small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            @can('create lab tests')
                <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-primary btn-sm fw-semibold">
                    <i class="fas fa-vial me-1"></i> دليل وأسعار التحاليل
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- 2. بطاقات الأقسام الثلاثة: الاستشارية، المختبر المباشر، والطوارئ -->
    <div class="row g-2 mb-3">
        <!-- أ. طلبات العيادات الاستشارية -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('lab.index', ['source' => 'consultation', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($sourceFilter ?? '') == 'consultation' ? 'border-bottom border-primary border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">طلبات العيادات الاستشارية</span>
                        <h4 class="mb-0 fw-bold text-primary font-monospace">{{ $counts['consultation'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">تحاليل محولة من الأطباء</small>
                    </div>
                    <div class="bg-primary-subtle text-primary p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-user-md fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- ب. طلبات المختبر المباشرة (الخارجية) -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('lab.index', ['source' => 'direct', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($sourceFilter ?? '') == 'direct' ? 'border-bottom border-info border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">طلبات المختبر المباشرة</span>
                        <h4 class="mb-0 fw-bold text-info font-monospace">{{ $counts['direct'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">مراجعي الصندوق والمختبر العام</small>
                    </div>
                    <div class="bg-info-subtle text-info p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-vial fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- ج. تحاليل قسم الطوارئ -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('lab.index', ['source' => 'emergency', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($sourceFilter ?? '') == 'emergency' ? 'border-bottom border-danger border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">تحاليل قسم الطوارئ</span>
                        <h4 class="mb-0 fw-bold text-danger font-monospace">{{ $counts['emergency'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">حالات الطوارئ الحرجة</small>
                    </div>
                    <div class="bg-danger-subtle text-danger p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-ambulance fs-5"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- د. كافة الطلبات (إجمالي) -->
        <div class="col-xl-3 col-md-6">
            <a href="{{ route('lab.index', ['source' => 'all', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
               class="card shadow-sm border-0 text-decoration-none h-100 {{ ($sourceFilter ?? 'all') == 'all' ? 'border-bottom border-secondary border-4 shadow' : '' }}" 
               style="background: #ffffff; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1 fw-semibold">كافة طلبات المختبر</span>
                        <h4 class="mb-0 fw-bold text-dark font-monospace">{{ $counts['all'] ?? 0 }}</h4>
                        <small class="text-muted" style="font-size: 0.7rem;">مكتمل: {{ $counts['completed'] ?? 0 }} • معلق: {{ $counts['pending'] ?? 0 }}</small>
                    </div>
                    <div class="bg-secondary-subtle text-secondary p-2 rounded-circle text-center" style="width: 42px; height: 42px;">
                        <i class="fas fa-layer-group fs-5"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- 3. شريط الفلترة والبحث والتبويبات -->
    <div class="card shadow-sm border-0 bg-white mb-3 rounded-3">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center justify-content-between">
                <!-- التبويبات العلوية الثلاثية -->
                <div class="col-lg-7 col-12">
                    <ul class="nav nav-pills gap-1" id="labRequestsTabs">
                        <li class="nav-item">
                            <a href="{{ route('lab.index', ['source' => 'consultation', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
                               class="nav-link {{ ($sourceFilter ?? '') == 'consultation' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2">
                                <i class="fas fa-user-md"></i>
                                <span>الاستشارية</span>
                                <span class="badge {{ ($sourceFilter ?? '') == 'consultation' ? 'bg-white text-primary' : 'bg-primary text-white' }} rounded-pill">{{ $counts['consultation'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('lab.index', ['source' => 'direct', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
                               class="nav-link {{ ($sourceFilter ?? '') == 'direct' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2">
                                <i class="fas fa-vial"></i>
                                <span>طلبات مباشرة</span>
                                <span class="badge {{ ($sourceFilter ?? '') == 'direct' ? 'bg-white text-info' : 'bg-info text-white' }} rounded-pill">{{ $counts['direct'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('lab.index', ['source' => 'emergency', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
                               class="nav-link {{ ($sourceFilter ?? '') == 'emergency' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2 text-danger">
                                <i class="fas fa-ambulance"></i>
                                <span>الطوارئ</span>
                                <span class="badge {{ ($sourceFilter ?? '') == 'emergency' ? 'bg-white text-danger' : 'bg-danger text-white' }} rounded-pill">{{ $counts['emergency'] ?? 0 }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('lab.index', ['source' => 'all', 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" 
                               class="nav-link {{ ($sourceFilter ?? 'all') == 'all' ? 'active' : '' }} fw-bold small py-1 px-3 d-flex align-items-center gap-2">
                                <i class="fas fa-list"></i>
                                <span>الكل</span>
                                <span class="badge {{ ($sourceFilter ?? 'all') == 'all' ? 'bg-white text-dark' : 'bg-secondary text-white' }} rounded-pill">{{ $counts['all'] ?? 0 }}</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- شريط البحث والفلترة -->
                <div class="col-lg-5 col-12">
                    <form method="GET" action="{{ route('lab.index') }}" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                        <input type="hidden" name="source" value="{{ request('source', 'all') }}">
                        <input type="hidden" name="status" value="{{ request('status', 'all') }}">
                        
                        <div class="btn-group btn-group-sm" role="group">
                            <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => request('status', 'all'), 'date' => 'today', 'search' => request('search')]) }}" 
                               class="btn {{ ($dateFilter ?? '') == 'today' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                                اليوم
                            </a>
                            <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => request('status', 'all'), 'date' => 'all', 'search' => request('search')]) }}" 
                               class="btn {{ ($dateFilter ?? 'all') == 'all' ? 'btn-primary fw-bold' : 'btn-outline-secondary' }}">
                                كافة التواريخ
                            </a>
                        </div>

                        <div class="input-group input-group-sm" style="max-width: 240px;">
                            <input type="text" name="search" id="lab-search-input" class="form-control" 
                                   placeholder="بحث باسم المريض، رقم..." 
                                   value="{{ request('search') }}">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            @if(request('search'))
                                <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => request('status', 'all'), 'date' => request('date', 'all')]) }}" class="btn btn-outline-secondary" title="إلغاء البحث">
                                    <i class="fas fa-times"></i>
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. جدول الطلبات الموحد والوحيد في الشاشة -->
    <div class="card shadow-sm border-0 bg-white rounded-3 overflow-hidden">
        <!-- شريط فلترة حالة الفحص السريعة -->
        <div class="p-2 px-3 bg-light border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-muted small fw-bold"><i class="fas fa-filter text-secondary me-1"></i> حالة الفحص:</span>
                
                <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => 'all', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                   class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? 'all') == 'all' ? 'btn-secondary text-white fw-bold shadow-sm' : 'btn-outline-secondary' }}">
                    الكل <span class="badge bg-white text-dark ms-1 rounded-pill font-monospace">{{ $counts['tab_total'] ?? $counts['all'] }}</span>
                </a>

                <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => 'pending', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                   class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? '') == 'pending' ? 'btn-warning text-dark fw-bold shadow-sm' : 'btn-outline-warning text-dark' }}">
                    <i class="fas fa-hourglass-half me-1"></i> بانتظار الفحص / معلق 
                    <span class="badge bg-dark text-white ms-1 rounded-pill font-monospace">{{ $counts['pending'] ?? 0 }}</span>
                </a>

                <a href="{{ route('lab.index', ['source' => request('source', 'all'), 'status' => 'completed', 'date' => request('date', 'all'), 'search' => request('search')]) }}" 
                   class="btn btn-sm py-1 px-3 rounded-pill {{ ($statusFilter ?? '') == 'completed' ? 'btn-success text-white fw-bold shadow-sm' : 'btn-outline-success' }}">
                    <i class="fas fa-check-circle me-1"></i> المكتملة والمعتمدة 
                    <span class="badge bg-white text-success ms-1 rounded-pill font-monospace">{{ $counts['completed'] ?? 0 }}</span>
                </a>
            </div>

            <div class="d-flex align-items-center gap-2 small text-muted">
                <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-success p-1 rounded-circle"></span> مكتمل</span>
                <span class="d-inline-flex align-items-center gap-1"><span class="badge bg-warning p-1 rounded-circle"></span> معلق</span>
            </div>
        </div>

        <div class="card-body p-0">
            @if(($sourceFilter ?? '') === 'emergency')
                <!-- أ. جدول تحاليل الطوارئ الحرجة -->
                @if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="labEmergencyTable">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="text-center" style="width: 80px;">رقم الطوارئ</th>
                                    <th>المريض</th>
                                    <th>التحاليل المطلوبة</th>
                                    <th style="width: 110px;" class="text-center">الأولوية</th>
                                    <th style="width: 140px;" class="text-center">حالة الفحص</th>
                                    <th style="width: 150px;" class="text-center">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyLabRequests as $emergencyRequest)
                                    @php
                                        $emCompleted = $emergencyRequest->status === 'completed';
                                        $emInProgress = $emergencyRequest->status === 'in_progress';
                                    @endphp
                                    <tr style="{{ $emCompleted ? 'border-right: 4px solid #16a34a; background-color: #fafdfb;' : ($emInProgress ? 'border-right: 4px solid #0284c7; background-color: #f0f9ff;' : 'border-right: 4px solid #dc2626; background-color: #ffffff;') }}">
                                        <td class="text-center font-monospace fw-bold text-danger">
                                            #{{ $emergencyRequest->emergency_id }}
                                        </td>
                                        <td>
                                            <strong class="text-dark d-block">{{ $emergencyRequest->patient?->user?->name ?? 'غير محدد' }}</strong>
                                            <small class="text-muted">
                                                {{ $emergencyRequest->patient?->age ? ($emergencyRequest->patient->age . ' سنة') : '' }}
                                            </small>
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($emergencyRequest->labTests as $t)
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $t->name }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-{{ $emergencyRequest->priority == 'critical' ? 'danger' : 'warning text-dark' }} fw-bold">
                                                {{ $emergencyRequest->priority_text }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($emCompleted)
                                                <span class="badge bg-success py-1.5 px-2.5 shadow-sm fw-semibold">
                                                    <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد
                                                </span>
                                            @elseif($emInProgress)
                                                <span class="badge bg-info text-white py-1.5 px-2.5 shadow-sm fw-semibold">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> قيد الإجراء
                                                </span>
                                            @else
                                                <span class="badge bg-danger text-white py-1.5 px-2.5 shadow-sm fw-bold">
                                                    <i class="fas fa-exclamation-triangle me-1"></i> بانتظار الفحص
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($emergencyRequest->status == 'pending')
                                                <form action="{{ route('staff.emergency-lab.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary fw-bold shadow-sm px-3">
                                                        <i class="fas fa-play me-1"></i> بدء الفحص
                                                    </button>
                                                </form>
                                            @elseif($emergencyRequest->status == 'in_progress')
                                                <button type="button" class="btn btn-sm btn-success fw-bold shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#completeEmergencyLabModal{{ $emergencyRequest->id }}">
                                                    <i class="fas fa-check me-1"></i> إدخال النتائج
                                                </button>
                                            @else
                                                <a href="{{ route('staff.emergency-lab.print', $emergencyRequest) }}" class="btn btn-sm btn-success fw-bold px-2 py-1 shadow-sm" target="_blank">
                                                    <i class="fas fa-print me-1"></i> طباعة
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- الترقيم والصفحات للطوارئ -->
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted small">
                            عرض <strong>{{ $emergencyLabRequests->firstItem() ?? 0 }}</strong> إلى <strong>{{ $emergencyLabRequests->lastItem() ?? 0 }}</strong> من إجمالي <strong>{{ $emergencyLabRequests->total() }}</strong> طلب طوارئ
                        </span>
                        <div>
                            {{ $emergencyLabRequests->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-ambulance fa-3x text-muted opacity-50 mb-3"></i>
                        <h6 class="text-dark fw-bold">لا توجد تحاليل طوارئ تطابق هذا الفلتر</h6>
                        <p class="text-muted small mb-0">يمكنك التبديل بين التبويبات أو إزالة فلتر الحالة للاطلاع على بقية الطلبات.</p>
                    </div>
                @endif

            @else
                <!-- ب. جدول طلبات المختبر (الاستشارية / المباشرة / الكل) -->
                @if(isset($requests) && $requests->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="labRequestsTable">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th class="text-center" style="width: 75px;"># الطلب</th>
                                    <th>بيانات المريض</th>
                                    <th>المصدر / الطبيب</th>
                                    <th>نوع الطلب والمرفقات</th>
                                    <th style="width: 140px;">تاريخ ووقت الطلب</th>
                                    <th style="width: 110px;" class="text-center">حالة الدفع</th>
                                    <th style="width: 140px;" class="text-center">حالة الفحص</th>
                                    <th style="width: 170px;" class="text-center">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $req)
                                    @php
                                        $det = is_string($req->details) ? json_decode($req->details, true) : $req->details;
                                        if (!is_array($det)) $det = [];
                                        $isBloodBank = $req->type === 'blood_bank' || data_get($det, 'blood_bank', false);
                                        $hasAttachment = !empty($det['attachment']);
                                        $testAttachments = is_array($det['test_attachments'] ?? null) ? $det['test_attachments'] : [];
                                        $totalAttachmentsCount = ($hasAttachment ? 1 : 0) + count($testAttachments);

                                        // استخراج أسماء الفحوصات الطبية
                                        $tests = [];
                                        if (!empty($det['lab_test_ids'])) {
                                            $ids = is_array($det['lab_test_ids']) ? $det['lab_test_ids'] : explode(',', (string) $det['lab_test_ids']);
                                            $tests = \App\Models\LabTest::whereIn('id', array_filter($ids))->pluck('name')->toArray();
                                        }
                                        if (empty($tests) && !empty($det['tests'])) {
                                            $tests = is_array($det['tests']) ? $det['tests'] : explode(',', (string) $det['tests']);
                                        }
                                        if (empty($tests) && !empty($req->description)) {
                                            $tests = [$req->description];
                                        }
                                        $tests = array_filter(array_map('trim', $tests));

                                        $patient = $req->visit?->patient;
                                        $patientUser = $patient?->user;
                                        $isConsultation = !empty($req->visit?->doctor_id);
                                        $isCompleted = $req->status === 'completed';
                                        $isInProgress = $req->status === 'in_progress';
                                    @endphp
                                    <tr style="{{ $isCompleted ? 'border-right: 4px solid #16a34a; background-color: #fafdfb;' : ($isInProgress ? 'border-right: 4px solid #0284c7; background-color: #f0f9ff;' : 'border-right: 4px solid #f59e0b; background-color: #ffffff;') }}">
                                        <!-- رقم الطلب -->
                                        <td class="text-center">
                                            <span class="badge {{ $isCompleted ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-light text-dark border' }} font-monospace fw-bold">
                                                #{{ $req->id }}
                                            </span>
                                        </td>

                                        <!-- بيانات المريض -->
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="{{ $isCompleted ? 'bg-success-subtle text-success' : 'bg-primary-subtle text-primary' }} rounded-circle p-2 text-center" style="width: 34px; height: 34px;">
                                                    <i class="fas fa-user small"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-dark d-block">{{ $patientUser?->name ?? 'غير محدد' }}</strong>
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        ملف: #{{ $patient?->national_id ?: ($patient?->id ?? '-') }}
                                                        @if($patient?->age) • {{ $patient->age }} سنة @endif
                                                        @if($patient?->blood_type) • <span class="text-danger fw-bold">{{ $patient->blood_type }}</span> @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- المصدر والطبيب المحول -->
                                        <td>
                                            @if($isConsultation)
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="fas fa-user-md text-primary small me-1"></i>
                                                    <span class="text-dark fw-semibold small">د. {{ $req->visit?->doctor?->user?->name }}</span>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">
                                                    عيادة استشارية
                                                </span>
                                            @else
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="fas fa-vial text-info small me-1"></i>
                                                    <span class="text-dark fw-semibold small">مختبر خارجي مباشر</span>
                                                </div>
                                                <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.68rem;">
                                                    استقبال الصندوق
                                                </span>
                                            @endif
                                        </td>

                                        <!-- نوع الطلب والفحوصات والمرفقات -->
                                        <td>
                                            <div class="d-flex flex-column gap-1">
                                                <!-- الفحوصات المطلوبة -->
                                                <div class="d-flex flex-wrap align-items-center gap-1">
                                                    @if($isBloodBank)
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-tint me-1"></i> مصرف الدم
                                                        </span>
                                                    @else
                                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                            <i class="fas fa-flask me-1"></i> تحاليل
                                                        </span>
                                                    @endif

                                                    @foreach(array_slice($tests, 0, 3) as $tName)
                                                        <span class="badge bg-light text-dark border font-monospace" style="font-size: 0.72rem;">
                                                            {{ $tName }}
                                                        </span>
                                                    @endforeach
                                                    @if(count($tests) > 3)
                                                        <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.68rem;" title="{{ implode(', ', array_slice($tests, 3)) }}">
                                                            +{{ count($tests) - 3 }} فحص
                                                        </span>
                                                    @endif
                                                </div>

                                                <!-- المرفقات وتقارير الأجهزة -->
                                                @if($totalAttachmentsCount > 0)
                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                        @if($hasAttachment)
                                                            <a href="{{ route('lab.attachment', $req) }}" 
                                                               target="_blank" 
                                                               class="badge bg-info-subtle text-info border border-info text-decoration-none shadow-sm py-1 px-2 d-inline-flex align-items-center gap-1" 
                                                               title="معاينة التقرير المرفق العام">
                                                                <i class="fas fa-paperclip"></i>
                                                                <span>تقرير عام ↗</span>
                                                            </a>
                                                        @endif

                                                        @foreach($testAttachments as $tName => $tData)
                                                            <a href="{{ route('lab.attachment', ['request' => $req, 'test' => $tName]) }}" 
                                                               target="_blank" 
                                                               class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none shadow-sm py-1 px-2 d-inline-flex align-items-center gap-1" 
                                                               title="معاينة تقرير {{ $tName }}">
                                                                <i class="fas fa-paperclip"></i>
                                                                <span>{{ Str::limit($tName, 12) }} ↗</span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- تاريخ ووقت الطلب -->
                                        <td>
                                            <div class="text-dark small fw-semibold">
                                                <i class="fas fa-calendar-alt text-muted me-1 small"></i> {{ $req->created_at->format('Y-m-d') }}
                                            </div>
                                            <small class="text-muted font-monospace" style="font-size: 0.72rem;">
                                                <i class="fas fa-clock me-1"></i> {{ $req->created_at->format('H:i A') }}
                                            </small>
                                        </td>

                                        <!-- حالة الدفع -->
                                        <td class="text-center">
                                            @if($req->payment_status == 'paid')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle py-1 px-2">
                                                    <i class="fas fa-check-circle me-1"></i> مسدد
                                                </span>
                                            @elseif($req->payment_status == 'pending')
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle py-1 px-2">
                                                    <i class="fas fa-exclamation-circle me-1"></i> غير مسدد
                                                </span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border py-1 px-2">-</span>
                                            @endif
                                        </td>

                                        <!-- حالة الفحص -->
                                        <td class="text-center">
                                            @if($isCompleted)
                                                <span class="badge bg-success py-1.5 px-2.5 shadow-sm fw-semibold" style="font-size: 0.78rem;">
                                                    <i class="fas fa-check-circle me-1"></i> مكتمل ومعتمد
                                                </span>
                                            @elseif($isInProgress)
                                                <span class="badge bg-info text-white py-1.5 px-2.5 shadow-sm fw-semibold" style="font-size: 0.78rem;">
                                                    <i class="fas fa-spinner fa-spin me-1"></i> قيد الإجراء
                                                </span>
                                            @elseif($req->status == 'pending_service_selection')
                                                <span class="badge bg-secondary py-1.5 px-2.5" style="font-size: 0.78rem;">
                                                    بانتظار تحديد
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark py-1.5 px-2.5 shadow-sm border border-warning fw-bold" style="font-size: 0.78rem;">
                                                    <i class="fas fa-hourglass-half me-1"></i> بانتظار الفحص
                                                </span>
                                            @endif
                                        </td>

                                        <!-- الإجراءات -->
                                        <td class="text-center">
                                            <div class="d-flex align-items-center justify-content-center gap-1">
                                                @if($isCompleted)
                                                    <a href="{{ route('lab.show', $req) }}" 
                                                       class="btn btn-sm btn-outline-primary fw-semibold px-2 py-1 shadow-sm" 
                                                       title="معاينة أو تعديل النتائج">
                                                        <i class="fas fa-eye me-1"></i> معاينة
                                                    </a>
                                                    <a href="{{ route('lab.print', $req) }}" 
                                                       class="btn btn-sm btn-success fw-bold px-2 py-1 shadow-sm" 
                                                       target="_blank" 
                                                       title="طباعة التقرير الطبي">
                                                        <i class="fas fa-print me-1"></i> طباعة
                                                    </a>
                                                @else
                                                    <a href="{{ route('lab.show', $req) }}" 
                                                       class="btn btn-sm btn-primary fw-bold px-3 py-1 shadow-sm" 
                                                       title="إدخال نتائج الفحص واعتمادها">
                                                        <i class="fas fa-bolt me-1"></i> إدخال النتائج
                                                    </a>
                                                    <a href="{{ route('lab.print', $req) }}" 
                                                       class="btn btn-sm btn-outline-dark fw-bold px-2 py-1 shadow-sm" 
                                                       target="_blank" 
                                                       title="طباعة طلب التحاليل / أمر العمل">
                                                        <i class="fas fa-print me-1"></i> طباعة الطلب
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- الترقيم والصفحات -->
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted small">
                            عرض <strong>{{ $requests->firstItem() ?? 0 }}</strong> إلى <strong>{{ $requests->lastItem() ?? 0 }}</strong> من إجمالي <strong>{{ $requests->total() }}</strong> طلب
                        </span>
                        <div>
                            {{ $requests->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="bg-light p-3 rounded-circle d-inline-flex mb-3 text-muted">
                            <i class="fas fa-flask fa-3x opacity-50"></i>
                        </div>
                        <h6 class="text-dark fw-bold">لا توجد طلبات مختبر تطابق هذا القسم</h6>
                        <p class="text-muted small mb-0">يمكنك التبديل بين التبويبات أعلاه للاطلاع على بقية الطلبات.</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- مودالات إدخال نتائج الطوارئ المعتمدة -->
<div id="emergency-lab-modals-container">
    @if(isset($emergencyLabRequests) && $emergencyLabRequests->count() > 0)
        @foreach($emergencyLabRequests as $emergencyRequest)
            @if($emergencyRequest->status == 'in_progress')
            <div class="modal fade" id="completeEmergencyLabModal{{ $emergencyRequest->id }}" tabindex="-1" aria-hidden="true" data-bs-backdrop="false" data-bs-focus="false">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
                        <div class="modal-header bg-gradient-dark text-white p-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary text-white rounded-circle p-2 me-3 shadow-sm d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-flask"></i>
                                </div>
                                <div>
                                    <h5 class="modal-title fw-bold mb-0">إدخال نتائج تحاليل الطوارئ #{{ $emergencyRequest->emergency_id }}</h5>
                                    <small class="text-white-50">{{ $emergencyRequest->patient?->user?->name }}</small>
                                </div>
                            </div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>

                        <form action="{{ route('staff.emergency-lab.complete', $emergencyRequest) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="modal-body p-4" style="background-color: #f8fafc;">
                                @foreach($emergencyRequest->labTests as $test)
                                    @php
                                        $patientGender = $emergencyRequest->patient->gender ?? $emergencyRequest->patient->user->gender ?? 'male';
                                        $patientAge = $emergencyRequest->patient->age ?? 30;
                                        $refObj = $test->referenceForPatient($patientGender, (int)$patientAge) ?? $test->references->first();
                                        $refRangeText = $refObj?->range_display ?? '';
                                        $refMin = $refObj?->ref_min ?? '';
                                        $refMax = $refObj?->ref_max ?? '';
                                        $unit = $test->unit ?: ($refObj?->unit ?? '');

                                        if (empty($refRangeText)) {
                                            $labResultHelper = new \App\Models\LabResult();
                                            $refRangeText = $labResultHelper->getReferenceRange($test->name);
                                            if (empty($unit)) $unit = $labResultHelper->getUnit($test->name);
                                        }
                                    @endphp

                                    <div class="test-card p-3 mb-3 bg-white rounded-3 border shadow-sm">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-dark">
                                                <i class="fas fa-microscope text-primary me-2"></i> {{ $test->name }}
                                            </strong>
                                            @if($refRangeText)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                    المعدل الطبيعي: {{ $refRangeText }} {{ $unit }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="row g-2 align-items-center">
                                            <div class="col-md-5">
                                                <label class="form-label small text-muted mb-1">النتيجة</label>
                                                <input type="text" 
                                                       name="results[{{ $test->id }}][value]" 
                                                       class="form-control form-control-sm fw-bold font-monospace" 
                                                       placeholder="أدخل النتيجة" 
                                                       required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted mb-1">الوحدة</label>
                                                <input type="text" name="results[{{ $test->id }}][unit]" class="form-control form-control-sm bg-light" value="{{ $unit }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small text-muted mb-1">المرجع</label>
                                                <input type="text" name="results[{{ $test->id }}][reference]" class="form-control form-control-sm bg-light" value="{{ $refRangeText }}">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <div class="mt-3">
                                    <label class="form-label fw-bold text-dark small">
                                        <i class="fas fa-comment-dots text-primary me-1"></i> ملاحظات فنية
                                    </label>
                                    <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="اكتب أي ملاحظات اختيارية..."></textarea>
                                </div>
                            </div>

                            <div class="modal-footer bg-white border-top-0 p-3">
                                <button type="button" class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-success btn-sm px-4 fw-bold shadow-sm">
                                    <i class="fas fa-check-circle me-1"></i> اعتماد وحفظ النتيجة
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endif
        @endforeach
    @endif
</div>

<script>
// تحديث حي ذكي في الخلفية
setInterval(function() {
    if (document.querySelectorAll('.modal.show').length > 0) {
        return;
    }
    fetch(window.location.href)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('lab-requests-content');
            if (newContent) {
                const activeTabId = document.querySelector('#labRequestsTabs .nav-link.active')?.id;
                const scrollPos = window.scrollY;
                document.getElementById('lab-requests-content').innerHTML = newContent.innerHTML;
                if (activeTabId) {
                    const tabBtn = document.getElementById(activeTabId);
                    if (tabBtn) tabBtn.classList.add('active');
                }
                window.scrollTo(0, scrollPos);
                const lastUpdateEl = document.getElementById('last-update');
                if (lastUpdateEl) {
                    lastUpdateEl.textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ');
                }
            }
        })
        .catch(err => console.log('Live refresh skipped:', err));
}, 10000);
</script>

<style>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
#live-indicator { animation: pulse 2s ease-in-out infinite; }
#labRequestsTabs .nav-link {
    border-radius: 20px;
    background-color: #f1f5f9;
    color: #475569;
    transition: all 0.2s ease;
}
#labRequestsTabs .nav-link.active {
    background-color: #2563eb;
    color: #ffffff;
}
#labRequestsTabs .nav-link.active .badge {
    background-color: #ffffff !important;
    color: #2563eb !important;
}
#labRequestsTabs .nav-link#pills-emergency-tab.active {
    background-color: #dc2626 !important;
    color: #ffffff !important;
}
#labRequestsTabs .nav-link#pills-emergency-tab.active .badge {
    background-color: #ffffff !important;
    color: #dc2626 !important;
}
</style>
@endsection
