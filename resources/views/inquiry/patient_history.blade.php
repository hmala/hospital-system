@extends('layouts.app')

@section('title', 'سجل وأرشيف المريض الشامل - الاستعلامات')

@section('content')
<div class="container-fluid py-3">
    {{-- رأس الصفحة وشريط البحث --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: #fff;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-6 col-md-12">
                    <h3 class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <i class="fas fa-folder-open text-warning"></i>
                        سجل وأرشيف المريض الشامل
                    </h3>
                    <p class="mb-0 text-white-50" style="font-size: 0.95rem;">
                        استعراض 360° لملف المريض (العيادات، الطوارئ، العمليات، المختبر، الأشعة) والأرشيف الإلكتروني.
                    </p>
                </div>
                <div class="col-lg-6 col-md-12">
                    <form action="{{ route('inquiry.patients.history') }}" method="GET" class="d-flex gap-2">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-0 text-primary">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" name="search" class="form-control form-control-lg border-0" 
                                   placeholder="ابحث باسم المريض، رقم الهاتف، أو الرقم الطبي..." 
                                   value="{{ request('search', $search ?? '') }}" required autofocus>
                            <button type="submit" class="btn btn-warning px-4 fw-bold">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- نتائج البحث المتعددة إن وجدت --}}
    @if(isset($searchPatients) && $searchPatients->count() > 1 && !$patient)
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-bottom py-3">
                <h5 class="fw-bold text-primary mb-0">
                    <i class="fas fa-users me-2"></i> نتائج البحث المطابقة ({{ $searchPatients->count() }} مريض)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم المريض</th>
                                <th>رقم الهاتف</th>
                                <th>الرقم الوطني / الإضبارة</th>
                                <th>العمر / الجنس</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($searchPatients as $idx => $p)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="fw-bold text-dark">{{ $p->user->name ?? '-' }}</td>
                                    <td>{{ $p->user->phone ?? '-' }}</td>
                                    <td><span class="badge bg-secondary">{{ $p->national_id ?? $p->id }}</span></td>
                                    <td>
                                        {{ $p->age ? $p->age . ' سنة' : '-' }} 
                                        ({{ $p->user->gender == 'male' ? 'ذكر' : ($p->user->gender == 'female' ? 'أنثى' : '-') }})
                                    </td>
                                    <td>
                                        <a href="{{ route('inquiry.patients.history', $p->id) }}" class="btn btn-sm btn-primary rounded-pill px-3">
                                            <i class="fas fa-folder-open me-1"></i> فتح الملف الكامل
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- إذا لم يتم اختيار مريض --}}
    @if(!$patient)
        <div class="card border-0 shadow-sm rounded-4 text-center py-5">
            <div class="card-body py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 100px; height: 100px;">
                        <i class="fas fa-id-card-alt fa-3x"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-secondary">يرجى البحث عن مريض لعرض سجله الطبي وأرشيفه</h4>
                <p class="text-muted">أدخل اسم المريض، رقم هاتفه أو رقمه الطبي في خانة البحث بالأعلى.</p>
            </div>
        </div>
    @else
        {{-- كارت المريض والإجراءات السريعة --}}
        <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
            <div class="card-body p-4">
                <div class="row align-items-center">
                    <div class="col-lg-8 col-md-12">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 70px; height: 70px; font-size: 1.8rem; font-weight: bold;">
                                {{ mb_substr($patient->user->name ?? 'م', 0, 1) }}
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h4 class="fw-bold mb-0 text-dark">{{ $patient->user->name ?? 'مريض بدون اسم' }}</h4>
                                    <span class="badge bg-primary px-3 py-2">رقم الملف: #{{ $patient->national_id ?? $patient->id }}</span>
                                    @if($patient->blood_type)
                                        <span class="badge bg-danger px-2 py-1"><i class="fas fa-tint me-1"></i>{{ $patient->blood_type }}</span>
                                    @endif
                                </div>
                                <div class="text-muted mt-2 d-flex flex-wrap gap-3" style="font-size: 0.9rem;">
                                    <span><i class="fas fa-phone text-success me-1"></i> {{ $patient->user->phone ?? 'لا يوجد' }}</span>
                                    <span><i class="fas fa-birthday-cake text-info me-1"></i> {{ $patient->age ? $patient->age . ' سنة' : 'العمر غير مسجل' }}</span>
                                    <span><i class="fas fa-venus-mars text-primary me-1"></i> {{ $patient->user->gender == 'male' ? 'ذكر' : ($patient->user->gender == 'female' ? 'أنثى' : '-') }}</span>
                                    <span><i class="fas fa-map-marker-alt text-danger me-1"></i> {{ $patient->governorate ?? '' }} {{ $patient->district ? ' - ' . $patient->district : '' }}</span>
                                    @if($patient->covered_by_insurance)
                                        <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i> مؤمن ({{ $patient->insurance_company ?? 'تأمين عام' }})</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- الحساسيات والأمراض المزمنة إن وجدت --}}
                        @if($patient->allergies || $patient->medical_history)
                            <div class="mt-3 p-2 bg-light rounded-3 border-start border-danger border-4 d-flex gap-3 flex-wrap">
                                @if($patient->allergies)
                                    <div><strong class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i> الحساسية:</strong> {{ $patient->allergies }}</div>
                                @endif
                                @if($patient->medical_history)
                                    <div><strong class="text-primary"><i class="fas fa-notes-medical me-1"></i> التاريخ المرضي:</strong> {{ $patient->medical_history }}</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="col-lg-4 col-md-12 text-lg-end text-start mt-3 mt-lg-0">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            {{-- زر السحب بالسكنر --}}
                            <button type="button" class="btn btn-outline-primary rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#scannerModal">
                                <i class="fas fa-print me-1"></i> سحب من السكنر
                            </button>

                            {{-- زر رفع ملف --}}
                            <button type="button" class="btn btn-outline-success rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                                <i class="fas fa-upload me-1"></i> أرشفة ملف
                            </button>

                            {{-- زر طباعة الإضبارة الشاملة --}}
                            <a href="{{ route('inquiry.patients.dossier.print', $patient->id) }}" target="_blank" class="btn btn-outline-dark rounded-pill shadow-sm">
                                <i class="fas fa-file-pdf me-1"></i> طباعة الإضبارة
                            </a>

                            {{-- زر إنشاء طلب استعلامات --}}
                            <a href="{{ route('inquiry.create', ['patient_id' => $patient->id]) }}" class="btn btn-primary rounded-pill shadow-sm">
                                <i class="fas fa-plus me-1"></i> طلب جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- رسائل التنبيه والنجاح --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- إحصائيات سريعة --}}
        <div class="row g-3 mb-4">
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-primary border-3">
                    <div class="text-primary fs-3 mb-1"><i class="fas fa-folder"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->documents->count() }}</h4>
                    <span class="text-muted small">المستندات المؤرشفة</span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-info border-3">
                    <div class="text-info fs-3 mb-1"><i class="fas fa-stethoscope"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->visits->count() }}</h4>
                    <span class="text-muted small">الزيارات والعيادات</span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-danger border-3">
                    <div class="text-danger fs-3 mb-1"><i class="fas fa-ambulance"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->emergencies->count() }}</h4>
                    <span class="text-muted small">حالات الطوارئ</span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-warning border-3">
                    <div class="text-warning fs-3 mb-1"><i class="fas fa-procedures"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->surgeries->count() }}</h4>
                    <span class="text-muted small">العمليات الجراحية</span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-success border-3">
                    <div class="text-success fs-3 mb-1"><i class="fas fa-vial"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->requests->count() }}</h4>
                    <span class="text-muted small">التحاليل المخبرية</span>
                </div>
            </div>
            <div class="col-xl-2 col-md-4 col-6">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white text-center border-bottom border-secondary border-3">
                    <div class="text-secondary fs-3 mb-1"><i class="fas fa-x-ray"></i></div>
                    <h4 class="fw-bold mb-0">{{ $patient->radiologyRequests->count() }}</h4>
                    <span class="text-muted small">فحوصات الأشعة</span>
                </div>
            </div>
        </div>

        {{-- التبويبات الرئيسية --}}
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 border-0" id="patientHistoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="archive-tab" data-bs-toggle="tab" data-bs-target="#archive-pane" type="button" role="tab">
                            <i class="fas fa-folder-open text-primary"></i>
                            الأرشيف الرقمي والمستندات
                            <span class="badge bg-primary rounded-pill">{{ $patient->documents->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="visits-tab" data-bs-toggle="tab" data-bs-target="#visits-pane" type="button" role="tab">
                            <i class="fas fa-calendar-check text-info"></i>
                            الزيارات والعيادات
                            <span class="badge bg-info rounded-pill">{{ $patient->visits->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="emergency-tab" data-bs-toggle="tab" data-bs-target="#emergency-pane" type="button" role="tab">
                            <i class="fas fa-ambulance text-danger"></i>
                            سجل الطوارئ
                            <span class="badge bg-danger rounded-pill">{{ $patient->emergencies->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="surgeries-tab" data-bs-toggle="tab" data-bs-target="#surgeries-pane" type="button" role="tab">
                            <i class="fas fa-procedures text-warning"></i>
                            العمليات الجراحية
                            <span class="badge bg-warning text-dark rounded-pill">{{ $patient->surgeries->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="lab-tab" data-bs-toggle="tab" data-bs-target="#lab-pane" type="button" role="tab">
                            <i class="fas fa-vials text-success"></i>
                            المختبر والتحاليل
                            <span class="badge bg-success rounded-pill">{{ $patient->requests->count() }}</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-3 px-4 fw-bold d-flex align-items-center gap-2 border-0" id="radiology-tab" data-bs-toggle="tab" data-bs-target="#radiology-pane" type="button" role="tab">
                            <i class="fas fa-x-ray text-secondary"></i>
                            قسم الأشعة
                            <span class="badge bg-secondary rounded-pill">{{ $patient->radiologyRequests->count() }}</span>
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="patientHistoryTabsContent">
                    
                    {{-- 1. تبويب الأرشيف الرقمي --}}
                    <div class="tab-pane fade show active" id="archive-pane" role="tabpanel">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-archive text-primary me-2"></i> المستندات المؤرشفة</h5>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#scannerModal">
                                    <i class="fas fa-print me-1"></i> سحب بالسكنر
                                </button>
                                <button type="button" class="btn btn-sm btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#uploadDocModal">
                                    <i class="fas fa-upload me-1"></i> رفع مستند
                                </button>
                            </div>
                        </div>

                        @if($patient->documents->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-folder-plus fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد مستندات مؤرشفة لهذا المريض حتى الآن</h6>
                                <p class="text-muted small">يمكنك سحب هوية المريض أو التقارير الطبية السابقة مباشرة من السكنر أو رفعها يدوياً.</p>
                            </div>
                        @else
                            <div class="row g-3">
                                @foreach($patient->documents as $doc)
                                    <div class="col-xl-3 col-lg-4 col-md-6">
                                        <div class="card h-100 border rounded-3 shadow-none hover-shadow transition">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <span class="badge bg-light text-primary border">
                                                        {{ $doc->category_name }}
                                                    </span>
                                                    <form action="{{ route('inquiry.documents.delete', $doc->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستند؟');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link text-danger p-0 border-0" title="حذف المستند">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>

                                                {{-- معاينة الصورة أو الأيقونة --}}
                                                <div class="text-center py-3 bg-light rounded-2 mb-2 position-relative" style="height: 140px; overflow: hidden; display: flex; align-items: center; justify-content: center;">
                                                    @if($doc->is_image)
                                                        <img src="{{ $doc->file_url }}" alt="{{ $doc->title }}" style="max-height: 100%; max-width: 100%; object-fit: contain;" class="cursor-pointer" onclick="viewImageModal('{{ $doc->file_url }}', '{{ $doc->title }}')">
                                                    @elseif($doc->is_pdf)
                                                        <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                                    @else
                                                        <i class="fas fa-file-alt fa-4x text-secondary"></i>
                                                    @endif
                                                </div>

                                                <h6 class="fw-bold text-dark text-truncate mb-1" title="{{ $doc->title }}">{{ $doc->title }}</h6>
                                                
                                                @if($doc->notes)
                                                    <p class="text-muted small text-truncate mb-2" title="{{ $doc->notes }}">{{ $doc->notes }}</p>
                                                @endif

                                                <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 0.75rem;">
                                                    <span><i class="fas fa-user-edit me-1"></i> {{ $doc->uploader->name ?? 'النظام' }}</span>
                                                    <span><i class="fas fa-clock me-1"></i> {{ $doc->created_at->format('Y-m-d') }}</span>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-white border-top p-2 d-flex gap-1">
                                                <a href="{{ $doc->file_url }}" target="_blank" class="btn btn-xs btn-outline-primary flex-fill py-1" style="font-size: 0.8rem;">
                                                    <i class="fas fa-eye me-1"></i> عرض
                                                </a>
                                                <button type="button" onclick="printDocumentUrl('{{ $doc->file_url }}', '{{ $doc->title }}')" class="btn btn-xs btn-outline-dark flex-fill py-1" style="font-size: 0.8rem;">
                                                    <i class="fas fa-print me-1"></i> طباعة
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- 2. تبويب الزيارات والعيادات --}}
                    <div class="tab-pane fade" id="visits-pane" role="tabpanel">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-calendar-check text-info me-2"></i> سجل العيادات والزيارات</h5>
                        @if($patient->visits->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد زيارات مسجلة لهذا المريض</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ الزيارة</th>
                                            <th>القسم / العيادة</th>
                                            <th>الطبيب المعالج</th>
                                            <th>التشخيص / الشكوى</th>
                                            <th>نوع الزيارة</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->visits as $idx => $visit)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $visit->visit_date ? \Carbon\Carbon::parse($visit->visit_date)->format('Y-m-d') : '-' }}</td>
                                                <td class="fw-bold">{{ $visit->department->name ?? '-' }}</td>
                                                <td>{{ $visit->doctor->user->name ?? '-' }}</td>
                                                <td>
                                                    @if(is_array($visit->diagnosis))
                                                        {{ implode(', ', array_filter($visit->diagnosis)) ?: ($visit->chief_complaint ?? '-') }}
                                                    @elseif(!empty($visit->diagnosis))
                                                        {{ $visit->diagnosis }}
                                                    @elseif(is_array($visit->symptoms))
                                                        {{ implode(', ', array_filter($visit->symptoms)) }}
                                                    @else
                                                        {{ $visit->symptoms ?? ($visit->chief_complaint ?? 'لا يوجد تشخيص مسجل') }}
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-info">{{ $visit->type ?? 'معاينة' }}</span></td>
                                                <td><span class="badge bg-success">{{ $visit->status ?? 'مكتمل' }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- 3. تبويب سجل الطوارئ --}}
                    <div class="tab-pane fade" id="emergency-pane" role="tabpanel">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-ambulance text-danger me-2"></i> سجل حالات الطوارئ</h5>
                        @if($patient->emergencies->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-heartbeat fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد سجلات طوارئ لهذا المريض</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ وتوقيت الدخول</th>
                                            <th>طبيب الطوارئ</th>
                                            <th>مستوى الأولوية</th>
                                            <th>الأعراض / التشخيص</th>
                                            <th>العلامات الحيوية</th>
                                            <th>الإجراء المتخذ</th>
                                            <th>الحالة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->emergencies as $idx => $em)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $em->admission_time ? \Carbon\Carbon::parse($em->admission_time)->format('Y-m-d H:i') : $em->created_at->format('Y-m-d H:i') }}</td>
                                                <td>{{ $em->doctor->user->name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge {{ ($em->priority == 'critical' || $em->priority == 'high') ? 'bg-danger' : ($em->priority == 'medium' ? 'bg-warning text-dark' : 'bg-success') }}">
                                                        {{ $em->priority ?? 'عادي' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if(is_array($em->symptoms))
                                                        {{ implode(', ', array_filter($em->symptoms)) }}
                                                    @elseif(!empty($em->symptoms))
                                                        {{ $em->symptoms }}
                                                    @elseif(is_array($em->diagnosis))
                                                        {{ implode(', ', array_filter($em->diagnosis)) }}
                                                    @else
                                                        {{ $em->diagnosis ?? '-' }}
                                                    @endif
                                                </td>
                                                <td class="small">
                                                    @if(is_array($em->vital_signs))
                                                        @php
                                                            $vParts = [];
                                                            if (!empty($em->vital_signs['blood_pressure'])) $vParts[] = 'BP: ' . $em->vital_signs['blood_pressure'];
                                                            elseif (!empty($em->vital_signs['blood_pressure_systolic']) && !empty($em->vital_signs['blood_pressure_diastolic'])) $vParts[] = 'BP: ' . $em->vital_signs['blood_pressure_systolic'] . '/' . $em->vital_signs['blood_pressure_diastolic'];
                                                            if (!empty($em->vital_signs['pulse'])) $vParts[] = 'Pulse: ' . $em->vital_signs['pulse'];
                                                            elseif (!empty($em->vital_signs['heart_rate'])) $vParts[] = 'HR: ' . $em->vital_signs['heart_rate'];
                                                            if (!empty($em->vital_signs['temperature'])) $vParts[] = 'Temp: ' . $em->vital_signs['temperature'] . '°C';
                                                            if (!empty($em->vital_signs['oxygen_saturation'])) $vParts[] = 'SpO2: ' . $em->vital_signs['oxygen_saturation'] . '%';
                                                        @endphp
                                                        {{ count($vParts) ? implode(' | ', $vParts) : '-' }}
                                                    @elseif(is_string($em->vital_signs))
                                                        {{ $em->vital_signs }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($em->requires_surgery)
                                                        <span class="badge bg-danger"><i class="fas fa-procedures me-1"></i> تحويل عمليات</span>
                                                    @elseif($em->requires_admission)
                                                        <span class="badge bg-info"><i class="fas fa-bed me-1"></i> تحويل رقود</span>
                                                    @else
                                                        <span class="badge bg-secondary">علاج ومغادرة</span>
                                                    @endif
                                                </td>
                                                <td><span class="badge bg-primary">{{ $em->status }}</span></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- 4. تبويب العمليات الجراحية --}}
                    <div class="tab-pane fade" id="surgeries-pane" role="tabpanel">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-procedures text-warning me-2"></i> سجل العمليات الجراحية</h5>
                        @if($patient->surgeries->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-procedures fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد عمليات جراحية مسجلة لهذا المريض</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ العملية</th>
                                            <th>اسم العملية الجراحية</th>
                                            <th>الجراح المسؤول</th>
                                            <th>طبيب التخدير</th>
                                            <th>صالة العمليات</th>
                                            <th>حالة العملية</th>
                                            <th>حالة الفاتورة</th>
                                            <th>المستند / ورقة التحويل</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->surgeries as $idx => $surg)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $surg->scheduled_date ? \Carbon\Carbon::parse($surg->scheduled_date)->format('Y-m-d') : ($surg->created_at ? $surg->created_at->format('Y-m-d') : '-') }}</td>
                                                <td class="fw-bold">{{ $surg->surgery_type ?? ($surg->surgery_name ?? 'عملية جراحية') }}</td>
                                                <td>{{ $surg->doctor->user->name ?? ($surg->surgeon_name ?? '-') }}</td>
                                                <td>{{ $surg->anesthesiologist->user->name ?? '-' }}</td>
                                                <td>{{ $surg->room->name ?? ($surg->room->room_number ?? '-') }}</td>
                                                <td>
                                                    <span class="badge {{ $surg->status == 'completed' ? 'bg-success' : ($surg->status == 'in_progress' ? 'bg-danger' : 'bg-warning text-dark') }}">
                                                        {{ $surg->status == 'completed' ? 'مكتملة' : ($surg->status == 'in_progress' ? 'جارية' : 'مجدولة') }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge {{ $surg->payment_status == 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $surg->payment_status == 'paid' ? 'مسددة' : 'معلقة / غير مسددة' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($surg->referral_letter_path)
                                                        <a href="{{ asset('storage/' . $surg->referral_letter_path) }}" target="_blank" class="btn btn-sm btn-outline-info">
                                                            <i class="fas fa-file-medical me-1"></i> عرض الورقة
                                                        </a>
                                                        <button type="button" onclick="printDocumentUrl('{{ asset('storage/' . $surg->referral_letter_path) }}', 'ورقة تحويل العملية')" class="btn btn-sm btn-outline-dark">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                    @else
                                                        <span class="text-muted small">لا يوجد مستند مرفق</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(\Illuminate\Support\Facades\Route::has('surgeries.print'))
                                                        <a href="{{ route('surgeries.print', $surg->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary" title="طباعة استمارة العملية">
                                                            <i class="fas fa-print me-1"></i> استمارة
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- 5. تبويب المختبر والتحاليل --}}
                    <div class="tab-pane fade" id="lab-pane" role="tabpanel">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-vials text-success me-2"></i> سجل التحاليل المخبرية</h5>
                        @if($patient->requests->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-vial fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد طلبات تحاليل مخبرية مسجلة</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ الطلب</th>
                                            <th>اسم التحليل</th>
                                            <th>الطبيب الطالب</th>
                                            <th>حالة النتيجة</th>
                                            <th>النتائج / الملاحظات</th>
                                            <th>طباعة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->requests as $idx => $req)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $req->created_at->format('Y-m-d') }}</td>
                                                <td class="fw-bold">{{ $req->type_text ?? ($req->description ?? 'طلب') }}</td>
                                                <td>{{ $req->visit->doctor->user->name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge {{ $req->status == 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $req->status_text ?? $req->status }}
                                                    </span>
                                                </td>
                                                <td>{{ $req->description ?? ($req->result ?? '-') }}</td>
                                                <td>
                                                    @if(\Illuminate\Support\Facades\Route::has('staff.requests.print'))
                                                        <a href="{{ route('staff.requests.print', $req->id) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-print me-1"></i> طباعة التقرير
                                                        </a>
                                                    @elseif(\Illuminate\Support\Facades\Route::has('lab-staff.print'))
                                                        <a href="{{ route('lab-staff.print', $req->id) }}" target="_blank" class="btn btn-sm btn-outline-success">
                                                            <i class="fas fa-print me-1"></i> طباعة التقرير
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    {{-- 6. تبويب قسم الأشعة --}}
                    <div class="tab-pane fade" id="radiology-pane" role="tabpanel">
                        <h5 class="fw-bold mb-3 text-dark"><i class="fas fa-x-ray text-secondary me-2"></i> سجل فحوصات الأشعة والرنين والسونار</h5>
                        @if($patient->radiologyRequests->isEmpty())
                            <div class="text-center py-5 bg-light rounded-4">
                                <i class="fas fa-x-ray fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted fw-bold">لا توجد فحوصات أشعة مسجلة لهذا المريض</h6>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>تاريخ الفحص</th>
                                            <th>نوع الأشعة</th>
                                            <th>الطبيب الطالب</th>
                                            <th>المنفذ</th>
                                            <th>حالة الفحص</th>
                                            <th>التقرير الإشعاعي</th>
                                            <th>طباعة</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($patient->radiologyRequests as $idx => $rad)
                                            <tr>
                                                <td>{{ $idx + 1 }}</td>
                                                <td>{{ $rad->created_at->format('Y-m-d') }}</td>
                                                <td class="fw-bold">{{ $rad->radiologyType->name ?? 'فحص إشعاعي' }}</td>
                                                <td>{{ $rad->doctor->user->name ?? '-' }}</td>
                                                <td>{{ $rad->performer->name ?? '-' }}</td>
                                                <td>
                                                    <span class="badge {{ $rad->status == 'completed' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                        {{ $rad->status == 'completed' ? 'مكتمل' : 'قيد الإجراء' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($rad->result && $rad->result->report)
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $rad->result->report }}">
                                                            {{ $rad->result->report }}
                                                        </span>
                                                    @elseif($rad->notes)
                                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="{{ $rad->notes }}">
                                                            {{ $rad->notes }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">لا يوجد تقرير</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(\Illuminate\Support\Facades\Route::has('radiology.print'))
                                                        <a href="{{ route('radiology.print', $rad->id) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-print me-1"></i> طباعة
                                                        </a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>

        {{-- Modal: سحب من السكنر المباشر --}}
        <div class="modal fade" id="scannerModal" tabindex="-1" aria-labelledby="scannerModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="scannerModalLabel">
                            <i class="fas fa-print me-2"></i> سحب مستند مباشر من الماسح الضوئي (Scanner)
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('inquiry.patients.documents.upload', $patient->id) }}" method="POST" id="scannerUploadForm">
                        @csrf
                        <input type="hidden" name="scanned_image_base64" id="scanned_image_base64">

                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">تصنيف المستند <span class="text-danger">*</span></label>
                                    <select name="category" class="form-select" required>
                                        @foreach($categories as $catKey => $catName)
                                            <option value="{{ $catKey }}" {{ $catKey == 'national_id' ? 'selected' : '' }}>{{ $catName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">عنوان المستند <span class="text-danger">*</span></label>
                                    <input type="text" name="title" class="form-control" placeholder="مثال: هوية المريض / تقرير خروج" required value="سحب سكنر - {{ date('Y-m-d') }}">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">ملاحظات إضافية</label>
                                    <input type="text" name="notes" class="form-control" placeholder="أي ملاحظات حول المستند...">
                                </div>

                                {{-- منطقة المعاينة والتحكم بالسكنر --}}
                                <div class="col-12">
                                    <div class="p-3 bg-light rounded-3 text-center border">
                                        <div id="scannerStatusBox" class="mb-3">
                                            <span class="badge bg-secondary p-2"><i class="fas fa-info-circle me-1"></i> جاهز للاتصال بالسكنر المحلي (localhost:5000)</span>
                                        </div>

                                        <div class="d-flex justify-content-center gap-2 mb-3">
                                            <button type="button" class="btn btn-primary px-4 fw-bold shadow-sm" id="btnTriggerScan" onclick="startScannerCapture()">
                                                <i class="fas fa-print me-1"></i> ابدأ المسح الضوئي الآن
                                            </button>
                                        </div>

                                        {{-- معاينة الصورة المسحوبة --}}
                                        <div id="scanPreviewArea" style="display: none;">
                                            <h6 class="fw-bold text-success mb-2"><i class="fas fa-check-circle me-1"></i> تم سحب الصورة بنجاح!</h6>
                                            <img id="scannedImagePreview" src="" alt="معاينة السكنر" class="img-fluid rounded border shadow-sm" style="max-height: 350px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold" id="btnSaveScannedDoc" disabled>
                                <i class="fas fa-save me-1"></i> حفظ في الأرشيف
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: رفع ملف يدوي --}}
        <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-labelledby="uploadDocModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title fw-bold" id="uploadDocModalLabel">
                            <i class="fas fa-upload me-2"></i> رفع وأرشفة مستند للمريض
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form action="{{ route('inquiry.patients.documents.upload', $patient->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">تصنيف المستند <span class="text-danger">*</span></label>
                                <select name="category" class="form-select" required>
                                    @foreach($categories as $catKey => $catName)
                                        <option value="{{ $catKey }}">{{ $catName }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">عنوان المستند <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="مثال: تقرير عملية سابقة / بطاقة الضمان" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">الملف (صورة أو PDF) <span class="text-danger">*</span></label>
                                <input type="file" name="document_file" class="form-control" accept="image/*,.pdf,.doc,.docx" required>
                                <small class="text-muted">الحد الأقصى للملف: 20 ميغابايت</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ملاحظات إضافية</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="أي ملاحظات حول الوثيقة..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                                <i class="fas fa-upload me-1"></i> رفع وحفظ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Modal: عرض الصورة بحجم كامل --}}
        <div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content bg-transparent border-0">
                    <div class="modal-header border-0 pb-0 justify-content-end">
                        <button type="button" class="btn btn-light rounded-circle p-2" data-bs-dismiss="modal"><i class="fas fa-times"></i></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img id="fullImageDisplay" src="" alt="" class="img-fluid rounded-4 shadow-lg" style="max-height: 85vh;">
                        <h5 class="text-white mt-3 fw-bold" id="fullImageTitle"></h5>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script>
function viewImageModal(url, title) {
    document.getElementById('fullImageDisplay').src = url;
    document.getElementById('fullImageTitle').innerText = title;
    var myModal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
    myModal.show();
}

async function startScannerCapture() {
    const statusBox = document.getElementById('scannerStatusBox');
    const previewArea = document.getElementById('scanPreviewArea');
    const previewImg = document.getElementById('scannedImagePreview');
    const base64Input = document.getElementById('scanned_image_base64');
    const saveBtn = document.getElementById('btnSaveScannedDoc');
    const scanBtn = document.getElementById('btnTriggerScan');

    statusBox.innerHTML = '<span class="badge bg-warning text-dark p-2"><i class="fas fa-spinner fa-spin me-1"></i> جاري الاتصال بالماسح الضوئي وإجراء المسح... يرجى الانتظار</span>';
    scanBtn.disabled = true;

    try {
        const response = await fetch('http://127.0.0.1:5000/scan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ dpi: 200, format: 'jpeg' })
        });

        if (!response.ok) {
            throw new Error('فشل السيرفر المحلي في إتمام المسح. تأكد من تشغيل الماسح وتوصيله.');
        }

        const blob = await response.blob();
        const reader = new FileReader();
        reader.onloadend = function() {
            const base64data = reader.result;
            base64Input.value = base64data;
            previewImg.src = base64data;
            previewArea.style.display = 'block';
            saveBtn.disabled = false;
            scanBtn.disabled = false;
            statusBox.innerHTML = '<span class="badge bg-success p-2"><i class="fas fa-check-circle me-1"></i> تم إتمام السحب بنجاح! يمكنك مراجعة الصورة والضغط على حفظ.</span>';
        };
        reader.readAsDataURL(blob);
    } catch (error) {
        console.error(error);
        scanBtn.disabled = false;
        statusBox.innerHTML = '<span class="badge bg-danger p-2"><i class="fas fa-exclamation-triangle me-1"></i> تعذر الاتصال ببرنامج الماسح المحلي (scanner_bridge.py على المنفذ 5000). يرجى التأكد من تشغيله.</span>';
    }
}

function printDocumentUrl(url, title) {
    if (!url) return;
    const printWindow = window.open('', '_blank');
    if (url.toLowerCase().endsWith('.pdf') || url.includes('.pdf')) {
        printWindow.location.href = url;
    } else {
        printWindow.document.write(`
            <!DOCTYPE html>
            <html dir="rtl" lang="ar">
            <head>
                <title>${title || 'طباعة مستند'}</title>
                <style>
                    body { margin: 0; padding: 20px; text-align: center; font-family: sans-serif; }
                    img { max-width: 95%; max-height: 90vh; object-fit: contain; }
                    @media print {
                        body { padding: 0; }
                        img { max-width: 100%; max-height: 100vh; }
                    }
                </style>
            </head>
            <body>
                <h4 style="margin-bottom: 15px;">${title || 'مستند طبي'}</h4>
                <img src="${url}" onload="window.print();" />
            </body>
            </html>
        `);
        printWindow.document.close();
    }
}
</script>
@endsection
