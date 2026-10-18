<!-- resources/views/patients/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-injured me-2 text-primary"></i>
                    إدارة وسجلات المرضى
                </h2>
                @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('create patients'))
                <a href="{{ route('patients.create') }}" class="btn btn-primary shadow-sm">
                    <i class="fas fa-plus me-2"></i>إضافة مريض جديد
                </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات السريعة ومتابعة اكتمال السجلات (KPIs) -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 border-start border-primary border-4 h-100 kpi-card" data-filter="" style="cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block small fw-bold">إجمالي المرضى بالمنظومة</span>
                        <h3 class="mb-0 fw-bold text-primary mt-1">{{ number_format($kpis['total'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.72rem;">كافة السجلات المسجلة (الكل)</small>
                    </div>
                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-users fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 border-start border-success border-4 h-100 kpi-card" data-insurance="hi" style="cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block small fw-bold">مشمولون بالضمان الصحي</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">{{ number_format($kpis['hi'] ?? 0) }}</h3>
                        <small class="text-success" style="font-size: 0.72rem;">هيئة الضمان الصحي (HI)</small>
                    </div>
                    <div class="rounded-circle bg-success bg-opacity-10 text-success p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-shield-alt fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 border-start border-warning border-4 h-100 kpi-card" data-completion="incomplete" style="cursor: pointer; transition: all 0.2s ease; background-color: #fffdf5;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-dark d-block small fw-bold">
                            <span class="badge bg-warning text-dark me-1">تنبيه</span> بانتظار إكمال البيانات
                        </span>
                        <h3 class="mb-0 fw-bold text-warning mt-1">{{ number_format($kpis['incomplete'] ?? 0) }}</h3>
                        <small class="text-danger fw-semibold" style="font-size: 0.72rem;"><i class="fas fa-hand-pointer me-1"></i>اضغط هنا لفلترة النواقص فوراً</small>
                    </div>
                    <div class="rounded-circle bg-warning bg-opacity-15 text-warning p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-user-edit fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-12">
            <div class="card shadow-sm border-0 border-start border-info border-4 h-100 kpi-card" data-completion="complete" style="cursor: pointer; transition: all 0.2s ease;">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted d-block small fw-bold">سجلات مكتملة ومدققة</span>
                        <h3 class="mb-0 fw-bold text-info mt-1">{{ number_format($kpis['complete'] ?? 0) }}</h3>
                        <small class="text-muted" style="font-size: 0.72rem;">كاملة البيانات 100% ✓</small>
                    </div>
                    <div class="rounded-circle bg-info bg-opacity-10 text-info p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                        <i class="fas fa-check-circle fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلاتر الذكية -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- البحث المباشر -->
                <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" id="search-input" class="form-control border-start-0" placeholder="ابحث بالاسم، الهاتف، الرقم الوطني، رقم الضمان..." value="{{ request('search') }}">
                        <button class="btn btn-outline-secondary" type="button" id="clear-search" title="مسح البحث">
                            <i class="fas fa-times"></i>
                        </button>
                        <div class="input-group-text d-none bg-white" id="search-loading">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">جاري البحث...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- فلتر حالة اكتمال الملف -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-clipboard-check text-warning"></i></span>
                        <select id="completion-filter" class="form-select form-select-sm" title="فلترة بحسب حالة اكتمال الملف">
                            <option value="">حالة اكتمال الملف (الكل)</option>
                            <option value="incomplete" {{ request('completion_status') == 'incomplete' ? 'selected' : '' }}>⚠️ بانتظار إكمال البيانات</option>
                            <option value="complete" {{ request('completion_status') == 'complete' ? 'selected' : '' }}>✅ مكتمل ومدقق</option>
                        </select>
                    </div>
                </div>

                <!-- فلتر الضمان الصحي -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-shield-alt text-success"></i></span>
                        <select id="insurance-filter" class="form-select form-select-sm" title="فلترة بحسب حالة الضمان">
                            <option value="">كل حالات الضمان</option>
                            <option value="hi" {{ request('insurance_type') == 'hi' ? 'selected' : '' }}>🛡️ ضمان صحي (HI)</option>
                            <option value="moi" {{ request('insurance_type') == 'moi' ? 'selected' : '' }}>👮 قوى الأمن / الداخلية</option>
                            <option value="covered" {{ request('insurance_type') == 'covered' ? 'selected' : '' }}>✅ مشمول بالضمان</option>
                            <option value="cash" {{ request('insurance_type') == 'cash' ? 'selected' : '' }}>💵 كاش (غير مشمول)</option>
                        </select>
                    </div>
                </div>

                <!-- فلتر الجنس -->
                <div class="col-xl-1 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-venus-mars text-info"></i></span>
                        <select id="gender-filter" class="form-select form-select-sm" title="فلترة بحسب الجنس">
                            <option value="">الجنس</option>
                            <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>
                </div>

                <!-- فلتر الموظف المنشئ -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-user-edit text-primary"></i></span>
                        <select id="creator-filter" class="form-select form-select-sm" title="فلترة بحسب الموظف المدخل">
                            <option value="">كل المدخلين</option>
                            <option value="system" {{ request('created_by') === 'system' ? 'selected' : '' }}>النظام (بدون موظف)</option>
                            @if(isset($creators))
                                @foreach($creators as $cr)
                                    <option value="{{ $cr->id }}" {{ request('created_by') == $cr->id ? 'selected' : '' }}>
                                        {{ $cr->name }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                </div>

                <!-- فلتر التاريخ وإعادة الضبط -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="d-flex gap-1 align-items-center">
                        <div class="input-group input-group-sm flex-grow-1">
                            <span class="input-group-text bg-white"><i class="fas fa-calendar-day text-secondary"></i></span>
                            <select id="date-filter" class="form-select form-select-sm" title="فلترة بحسب تاريخ الإدخال">
                                <option value="">التاريخ</option>
                                <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>📅 اليوم</option>
                                <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>أمس</option>
                                <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                                <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                                <option value="custom" {{ (request('date_from') || request('date_to') || request('date_filter') == 'custom') ? 'selected' : '' }}>فترة ⏳</option>
                            </select>
                        </div>
                        <button type="button" id="reset-filters" class="btn btn-sm btn-outline-secondary" title="إعادة تعيين الفلاتر">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- صف التواريخ المخصصة (من - إلى) -->
            <div class="row g-2 mt-2 pt-2 border-top {{ (request('date_from') || request('date_to') || request('date_filter') == 'custom') ? '' : 'd-none' }}" id="custom-date-row">
                <div class="col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">من تاريخ:</span>
                        <input type="date" id="date-from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white">إلى تاريخ:</span>
                        <input type="date" id="date-to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 d-flex align-items-center">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1 text-primary"></i>
                        تحديث فوري وتلقائي لقائمة المرضى بحسب الفلاتر المحددة.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="patients-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th>المريض</th>
                                    <th class="text-center" style="width: 140px;">
                                        <i class="fas fa-clipboard-check text-primary me-1"></i>حالة السجل
                                    </th>
                                    <th>معلومات الاتصال</th>
                                    <th>الضمان ورقم البطاقة</th>
                                    <th class="table-primary bg-opacity-25" style="border-bottom: 2px solid #3b82f6;">
                                        <i class="fas fa-sort-amount-down-alt text-primary me-1"></i> تاريخ الإدخال
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 0.68rem;">تنازلي ⬇️</span>
                                    </th>
                                    <th>الزيارات</th>
                                    <th class="text-center" style="width: 170px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="patients-tbody">
                                @forelse($patients as $patient)
                                @php
                                    $insNum = $patient->insurance_card_no ?: ($patient->insurance_number ?: $patient->insurance_booklet_number);
                                    $missingPhone = empty($patient->user?->phone);
                                    $missingNid = empty($patient->national_id);
                                    $missingGender = empty($patient->user?->gender);
                                    $missingAge = empty($patient->age);
                                    $missingIns = ($patient->insurance_type === 'hi' && empty($insNum));
                                    $isInc = $missingPhone || $missingNid || $missingGender || $missingAge || $missingIns;
                                @endphp
                                <tr id="patient-row-{{ $patient->id }}">
                                    <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold fs-5">
                                                {{ $patient->user ? mb_substr($patient->user->name, 0, 1) : '?' }}
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block patient-name-text">{{ $patient->user ? $patient->user->name : 'مريض بدون بيانات' }}</strong>

                                                <div class="d-flex align-items-center gap-1 mt-1 flex-wrap patient-meta-badges">
                                                    <span class="badge {{ $patient->user && $patient->user->gender ? 'bg-light text-secondary border' : 'bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50' }} patient-gender-badge" style="font-size: 0.70rem;">
                                                        {{ $patient->user && $patient->user->gender == 'male' ? 'ذكر' : ($patient->user && $patient->user->gender == 'female' ? 'أنثى' : '⚠️ الجنس غير محدد') }}
                                                    </span>
                                                    @if($patient->age)
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 patient-age-badge" style="font-size: 0.70rem;">{{ $patient->age }} سنة</span>
                                                    @else
                                                        <span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 patient-age-badge" style="font-size: 0.70rem;">⚠️ العمر غير مسجل</span>
                                                    @endif
                                                    @if($patient->blood_type)
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.70rem;">{{ $patient->blood_type }}</span>
                                                    @endif
                                                </div>

                                                <div class="patient-nid-container">
                                                    @if($patient->national_id)
                                                        <small class="text-muted font-monospace d-block mt-1" style="font-size: 0.75rem;">
                                                            <i class="fas fa-fingerprint me-1"></i>{{ $patient->national_id }}
                                                        </small>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center status-col">
                                        @if($isInc)
                                            <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                                <i class="fas fa-exclamation-triangle text-warning me-1"></i>بحاجة إكمال
                                            </span>
                                            <div class="d-flex flex-wrap gap-1 justify-content-center mt-1 missing-tags-box">
                                                @if($missingAge)
                                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75" style="font-size: 0.65rem;">
                                                        <i class="fas fa-hourglass-half text-warning me-1"></i>العمر مفقود
                                                    </span>
                                                @endif
                                                @if($missingPhone)
                                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.65rem;">
                                                        <i class="fas fa-phone-slash me-1"></i>هاتف مفقود
                                                    </span>
                                                @endif
                                                @if($missingIns)
                                                    <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75" style="font-size: 0.65rem;">
                                                        <i class="fas fa-id-card-alt text-warning me-1"></i>رقم الضمان مفقود
                                                    </span>
                                                @endif
                                                @if($missingNid)
                                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.65rem;">
                                                        <i class="fas fa-fingerprint me-1"></i>البطاقة الوطنية
                                                    </span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.72rem;">
                                                <i class="fas fa-check-circle me-1"></i>مكتمل ✓
                                            </span>
                                        @endif
                                    </td>
                                    <td class="phone-col">
                                        <small class="d-block text-dark fw-bold phone-text">
                                            @if($patient->user && $patient->user->phone)
                                                <i class="fas fa-phone me-1 text-primary"></i>{{ $patient->user->phone }}
                                            @else
                                                <span class="text-danger"><i class="fas fa-phone-slash me-1"></i>غير متوفر</span>
                                            @endif
                                        </small>
                                        @if($patient->emergency_contact)
                                            <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-phone-alt me-1 text-danger"></i>طوارئ: {{ $patient->emergency_contact }}
                                            </small>
                                        @endif
                                        @if($patient->user && $patient->user->email && !str_contains($patient->user->email, '@hospital.local'))
                                            <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">
                                                <i class="fas fa-envelope me-1 text-secondary"></i>{{ $patient->user->email }}
                                            </small>
                                        @endif
                                    </td>
                                    <td class="insurance-col">
                                        @php
                                            $isInsured = $patient->insurance_type !== 'none' || $patient->covered_by_insurance;
                                        @endphp
                                        @if($patient->insurance_type === 'hi')
                                            <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1">
                                                <i class="fas fa-shield-alt"></i> ضمان صحي (HI)
                                            </span>
                                            @if($patient->healthInsuranceCategory)
                                                <small class="d-block text-muted" style="font-size: 0.75rem;">{{ $patient->healthInsuranceCategory->name }} (تحمّل {{ (float)$patient->copay_percentage }}%)</small>
                                            @endif
                                        @elseif($patient->insurance_type === 'moi')
                                            <span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1">
                                                <i class="fas fa-user-shield"></i> قوى الأمن / الداخلية
                                            </span>
                                        @elseif($isInsured)
                                            <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1">
                                                <i class="fas fa-check-circle"></i> مشمول بالضمان
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border px-2 py-1 rounded-pill mb-1">
                                                <i class="fas fa-times-circle text-muted me-1"></i> غير مشمول (كاش)
                                            </span>
                                        @endif

                                        <div class="mt-1 insurance-num-box">
                                            @if($insNum)
                                                <span class="badge bg-white text-dark border font-monospace px-2 py-1" title="رقم الضمان / البطاقة">
                                                    <i class="fas fa-id-card text-muted me-1"></i>{{ $insNum }}
                                                </span>
                                            @else
                                                <span class="badge bg-light text-danger border border-danger border-opacity-25" style="font-size: 0.70rem;">
                                                    <i class="fas fa-exclamation-circle me-1"></i>بدون رقم بطاقة
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="fw-bold text-dark small">
                                                <i class="fas fa-calendar-alt text-primary me-1"></i>{{ $patient->created_at ? $patient->created_at->format('Y-m-d') : '-' }}
                                            </span>
                                            <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.75rem;">
                                                <span><i class="fas fa-clock me-1"></i>{{ $patient->created_at ? $patient->created_at->format('h:i A') : '' }}</span>
                                                @if($patient->created_at)
                                                    <span>• ({{ $patient->created_at->diffForHumans() }})</span>
                                                @endif
                                            </div>
                                            <div class="mt-1">
                                                <span class="badge bg-light text-dark border" title="الموظف الذي أنشأ السجل">
                                                    <i class="fas fa-user-edit text-primary me-1"></i>{{ $patient->creator ? $patient->creator->name : 'النظام' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill px-2 py-1 mb-1">{{ $patient->total_appointments }} زيارة</span>
                                        @if($patient->getLastVisitDate())
                                            <small class="d-block text-success" style="font-size: 0.75rem;">
                                                <i class="fas fa-clock me-1"></i>{{ $patient->getLastVisitDate()->format('Y-m-d') }}
                                            </small>
                                        @else
                                            <small class="text-muted d-block" style="font-size: 0.75rem;">لا توجد زيارات</small>
                                        @endif
                                    </td>
                                    <td class="text-center actions-col">
                                        <div class="d-flex justify-content-center align-items-center gap-1">
                                            @if($isInc)
                                            <!-- زر إكمال البيانات السريع المباشر -->
                                            <button type="button" 
                                                    class="btn btn-warning btn-sm rounded-2 fw-bold btn-quick-complete" 
                                                    data-patient-id="{{ $patient->id }}" 
                                                    data-name="{{ $patient->user?->name }}" 
                                                    data-phone="{{ $patient->user?->phone }}" 
                                                    data-gender="{{ $patient->user?->gender }}" 
                                                    data-ins="{{ $insNum }}" 
                                                    data-nid="{{ $patient->national_id }}" 
                                                    data-age="{{ $patient->age }}"
                                                    data-dob="{{ $patient->user?->date_of_birth ? $patient->user->date_of_birth->format('Y-m-d') : '' }}"
                                                    data-cat="{{ $patient->healthInsuranceCategory?->name }}"
                                                    title="إكمال وتدقيق بيانات المريض سريعاً">
                                                <i class="fas fa-user-edit me-1"></i>إكمال
                                            </button>
                                            @endif

                                            @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('edit patients'))
                                            <a href="{{ route('patients.edit', $patient) }}" 
                                               class="btn btn-outline-secondary btn-sm rounded-2" title="تعديل تفصيلي كامل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @endif

                                            @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('delete patients'))
                                            <form action="{{ route('patients.destroy', $patient) }}" 
                                               method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm rounded-2" 
                                                        title="حذف" onclick="return confirm('هل أنت متأكد من حذف المريض؟')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-results-row">
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-user-injured fa-3x mb-3 text-secondary"></i>
                                        <br>
                                        لا توجد مرضى مسجلين حتى الآن
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- الترقيم -->
                    <div class="d-flex justify-content-center p-3 border-top" id="pagination-container">
                        {{ $patients->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- تصميم حركات صف الإكمال السريع المدمج -->
<style>
.quick-edit-row {
    animation: fadeInDrawer 0.25s ease-in-out;
}
@keyframes fadeInDrawer {
    from { opacity: 0; transform: translateY(-6px); }
    to { opacity: 1; transform: translateY(0); }
}
.quick-edit-card {
    background: #fffdf5;
    border: 1px solid #fde047;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(234, 179, 8, 0.12);
}
</style>

<style>

.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.kpi-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

.search-loading {
    opacity: 0.6;
    pointer-events: none;
}

#search-input {
    transition: all 0.3s ease;
}

#search-input:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.pagination {
    justify-content: center !important;
    flex-wrap: wrap !important;
}
.pagination .page-item {
    margin: 0 0.1rem;
}
.pagination .page-link {
    padding: 0.35rem 0.65rem !important;
    font-size: 0.85rem !important;
    min-width: auto !important;
    min-height: auto !important;
    height: auto !important;
    width: auto !important;
}
.pagination .page-link i {
    font-size: 0.85rem !important;
}
.pagination .page-item.active .page-link,
.pagination .page-item.disabled .page-link {
    box-shadow: none !important;
}
.pagination .page-item.active .page-link {
    padding: 0.35rem 0.7rem !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const clearSearchBtn = document.getElementById('clear-search');
    const completionFilter = document.getElementById('completion-filter');
    const creatorFilter = document.getElementById('creator-filter');
    const insuranceFilter = document.getElementById('insurance-filter');
    const genderFilter = document.getElementById('gender-filter');
    const dateFilter = document.getElementById('date-filter');
    const dateFromInput = document.getElementById('date-from');
    const dateToInput = document.getElementById('date-to');
    const customDateRow = document.getElementById('custom-date-row');
    const resetFiltersBtn = document.getElementById('reset-filters');

    const patientsTable = document.getElementById('patients-table');
    const patientsTbody = document.getElementById('patients-tbody');
    const paginationContainer = document.getElementById('pagination-container');
    const noResultsRow = document.getElementById('no-results-row');
    const patientsBaseUrl = '{{ route("patients.index") }}'.replace(/\/+$/, '');

    // قراءة CSRF token
    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    let searchTimeout;

    // تجميع معاملات البحث والفلاتر
    function getFilterParams(page = 1) {
        var params = new URLSearchParams();
        const search = searchInput ? searchInput.value.trim() : '';
        if (search) params.set('search', search);

        if (completionFilter && completionFilter.value) params.set('completion_status', completionFilter.value);
        if (creatorFilter && creatorFilter.value) params.set('created_by', creatorFilter.value);
        if (insuranceFilter && insuranceFilter.value) params.set('insurance_type', insuranceFilter.value);
        if (genderFilter && genderFilter.value) params.set('gender', genderFilter.value);

        if (dateFilter && dateFilter.value) {
            params.set('date_filter', dateFilter.value);
            if (dateFilter.value === 'custom') {
                if (dateFromInput && dateFromInput.value) params.set('date_from', dateFromInput.value);
                if (dateToInput && dateToInput.value) params.set('date_to', dateToInput.value);
            }
        }
        params.set('page', page);
        return params;
    }

    // دالة البحث مع الفلاتر ورقم الصفحة
    function performSearchWithPage(page) {
        page = page || 1;
        document.getElementById('search-loading').classList.remove('d-none');
        patientsTable.classList.add('search-loading');

        var params = getFilterParams(page);

        fetch(patientsBaseUrl + '?' + params.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            }
        })
        .then(function(response) {
            if (!response.ok) throw new Error('HTTP ' + response.status);
            return response.json();
        })
        .then(function(data) {
            updateTable(Array.isArray(data.patients) ? data.patients : []);
            updatePagination(data.pagination || '');
            document.getElementById('search-loading').classList.add('d-none');
            patientsTable.classList.remove('search-loading');
        })
        .catch(function(err) {
            console.error('خطأ في تصفية وبحث المرضى:', err);
            document.getElementById('search-loading').classList.add('d-none');
            patientsTable.classList.remove('search-loading');
        });
    }

    function performSearch() {
        performSearchWithPage(1);
    }

    // ربط النقر على بطاقات الإحصائيات (KPI Cards) لتطبيق الفلترة السريعة
    document.querySelectorAll('.kpi-card').forEach(card => {
        card.addEventListener('click', function() {
            if (this.dataset.filter === 'all') {
                if (completionFilter) completionFilter.value = '';
                if (insuranceFilter) insuranceFilter.value = '';
            } else if (this.dataset.insurance) {
                if (insuranceFilter) insuranceFilter.value = this.dataset.insurance;
                if (completionFilter) completionFilter.value = '';
            } else if (this.dataset.completion) {
                if (completionFilter) completionFilter.value = this.dataset.completion;
            }
            performSearchWithPage(1);
        });
    });

    // تحديث الجدول عند تلقي استجابة AJAX
    function updateTable(patients) {
        if (noResultsRow) noResultsRow.remove();
        patientsTbody.innerHTML = '';

        if (patients.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-3x mb-3 text-secondary"></i>
                    <br>
                    لا توجد نتائج مطابقة للفلاتر المحددة
                </td>
            `;
            patientsTbody.appendChild(emptyRow);
            return;
        }

        patients.forEach((patient, index) => {
            let insBadge = '';
            if (patient.insurance_type === 'hi') {
                insBadge = `<span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1"><i class="fas fa-shield-alt"></i> ضمان صحي (HI)</span>`;
                if (patient.insurance_category) {
                    insBadge += `<small class="d-block text-muted" style="font-size: 0.75rem;">${patient.insurance_category} (تحمّل ${patient.copay_percentage || 0}%)</small>`;
                }
            } else if (patient.insurance_type === 'moi') {
                insBadge = `<span class="badge bg-primary bg-opacity-15 text-primary border border-primary border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1"><i class="fas fa-user-shield"></i> قوى الأمن / الداخلية</span>`;
            } else if (patient.covered_by_insurance) {
                insBadge = `<span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-50 px-2 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-1 mb-1"><i class="fas fa-check-circle"></i> مشمول بالضمان</span>`;
            } else {
                insBadge = `<span class="badge bg-light text-secondary border px-2 py-1 rounded-pill mb-1"><i class="fas fa-times-circle text-muted me-1"></i> غير مشمول (كاش)</span>`;
            }

            const insNumHtml = patient.insurance_number 
                ? `<div class="mt-1"><span class="badge bg-white text-dark border font-monospace px-2 py-1"><i class="fas fa-id-card text-muted me-1"></i>${patient.insurance_number}</span></div>` 
                : `<div class="mt-1"><span class="badge bg-light text-danger border border-danger border-opacity-25" style="font-size: 0.70rem;"><i class="fas fa-exclamation-circle me-1"></i>بدون رقم بطاقة</span></div>`;

            const creatorHtml = patient.creator_name || 'النظام';
            const createdAtHtml = patient.created_at_formatted ? patient.created_at_formatted.split(' ') : ['-', ''];

            // بناء شارات النواقص
            const missing = patient.missing_fields || [];
            let missingTagsHtml = '';
            if (patient.is_incomplete) {
                if (missing.includes('age')) {
                    missingTagsHtml += `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75" style="font-size: 0.68rem;"><i class="fas fa-hourglass-half text-warning me-1"></i>العمر مفقود</span> `;
                }
                if (missing.includes('phone')) {
                    missingTagsHtml += `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.68rem;"><i class="fas fa-phone-slash me-1"></i>هاتف مفقود</span> `;
                }
                if (missing.includes('insurance_number')) {
                    missingTagsHtml += `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75" style="font-size: 0.68rem;"><i class="fas fa-id-card-alt text-warning me-1"></i>رقم الضمان مفقود</span> `;
                }
                if (missing.includes('national_id')) {
                    missingTagsHtml += `<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25" style="font-size: 0.68rem;"><i class="fas fa-fingerprint me-1"></i>البطاقة الوطنية</span> `;
                }
            }

            const statusBadgeHtml = patient.is_incomplete
                ? `<span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75 px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;"><i class="fas fa-exclamation-triangle text-warning me-1"></i>بحاجة إكمال</span>`
                : `<span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-0.5 rounded-pill" style="font-size: 0.68rem;"><i class="fas fa-check-circle me-1"></i>مكتمل ✓</span>`;

            const quickCompleteBtnHtml = patient.is_incomplete
                ? `<button type="button" class="btn btn-warning btn-sm rounded-2 fw-bold btn-quick-complete" 
                          data-patient-id="${patient.id}" 
                          data-name="${patient.user ? patient.user.name : ''}" 
                          data-phone="${patient.user ? (patient.user.phone || '') : ''}" 
                          data-gender="${patient.user ? (patient.user.gender || '') : ''}" 
                          data-ins="${patient.insurance_number || ''}" 
                          data-nid="${patient.national_id || ''}" 
                          data-age="${patient.age || ''}" 
                          data-dob="${patient.dob || ''}" 
                          data-cat="${patient.insurance_category || 'ضمان صحي'}"
                          title="إكمال وتدقيق بيانات المريض سريعاً">
                        <i class="fas fa-user-edit me-1"></i>إكمال
                   </button>`
                : '';

            const phoneHtml = patient.user && patient.user.phone 
                ? `<i class="fas fa-phone me-1 text-primary"></i>${patient.user.phone}`
                : `<span class="text-danger"><i class="fas fa-phone-slash me-1"></i>غير متوفر</span>`;

            const row = document.createElement('tr');
            row.id = `patient-row-${patient.id}`;
            row.innerHTML = `
                <td class="fw-bold text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold fs-5">
                            ${patient.user ? patient.user.name.charAt(0) : '?'}
                        </div>
                        <div>
                            <strong class="text-dark d-block patient-name-text">${patient.user ? patient.user.name : 'مريض بدون بيانات'}</strong>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap patient-meta-badges">
                                <span class="badge ${patient.user && patient.user.gender ? 'bg-light text-secondary border' : 'bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50'} patient-gender-badge" style="font-size: 0.70rem;">
                                    ${patient.user && patient.user.gender === 'male' ? 'ذكر' : (patient.user && patient.user.gender === 'female' ? 'أنثى' : '⚠️ الجنس غير محدد')}
                                </span>
                                ${patient.age ? `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 patient-age-badge" style="font-size: 0.70rem;">${patient.age} سنة</span>` : `<span class="badge bg-warning bg-opacity-15 text-dark border border-warning border-opacity-50 patient-age-badge" style="font-size: 0.70rem;">⚠️ العمر غير مسجل</span>`}
                                ${patient.blood_type ? `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.70rem;">${patient.blood_type}</span>` : ''}
                            </div>
                            <div class="patient-nid-container">
                                ${patient.national_id ? `<small class="text-muted font-monospace d-block mt-1" style="font-size: 0.75rem;"><i class="fas fa-fingerprint me-1"></i>${patient.national_id}</small>` : ''}
                            </div>
                        </div>
                    </div>
                </td>
                <td class="text-center status-col">
                    ${patient.is_incomplete ? `
                        <span class="badge bg-warning bg-opacity-25 text-dark border border-warning border-opacity-75 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.72rem;">
                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>بحاجة إكمال
                        </span>
                        <div class="d-flex flex-wrap gap-1 justify-content-center mt-1 missing-tags-box">
                            ${missingTagsHtml}
                        </div>
                    ` : `
                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.72rem;">
                            <i class="fas fa-check-circle me-1"></i>مكتمل ✓
                        </span>
                    `}
                </td>
                <td class="phone-col">
                    <small class="d-block text-dark fw-bold phone-text">${phoneHtml}</small>
                    ${patient.emergency_contact ? `<small class="d-block text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-phone-alt me-1 text-danger"></i>طوارئ: ${patient.emergency_contact}</small>` : ''}
                    ${patient.user && patient.user.email && !patient.user.email.includes('@hospital.local') ? `<small class="d-block text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-envelope me-1 text-secondary"></i>${patient.user.email}</small>` : ''}
                </td>
                <td class="insurance-col">
                    ${insBadge}
                    ${insNumHtml}
                </td>
                <td>
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark small">
                            <i class="fas fa-calendar-alt text-primary me-1"></i>${createdAtHtml[0] || '-'}
                        </span>
                        <div class="d-flex align-items-center gap-1 text-muted" style="font-size: 0.75rem;">
                            <span><i class="fas fa-clock me-1"></i>${createdAtHtml.slice(1).join(' ')}</span>
                            ${patient.created_at_diff ? `<span>• (${patient.created_at_diff})</span>` : ''}
                        </div>
                        <div class="mt-1">
                            <span class="badge bg-light text-dark border" title="الموظف الذي أنشأ السجل">
                                <i class="fas fa-user-edit text-primary me-1"></i>${creatorHtml}
                            </span>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-primary rounded-pill px-2 py-1 mb-1">${patient.total_appointments || 0} زيارة</span>
                    ${patient.last_visit_date ? `<small class="d-block text-success" style="font-size: 0.75rem;"><i class="fas fa-clock me-1"></i>${patient.last_visit_date}</small>` : '<small class="text-muted d-block" style="font-size: 0.75rem;">لا توجد زيارات</small>'}
                </td>
                <td class="text-center actions-col">
                    <div class="d-flex justify-content-center align-items-center gap-1">
                        ${quickCompleteBtnHtml}
                        <a href="${patientsBaseUrl}/${patient.id}/edit" class="btn btn-outline-secondary btn-sm rounded-2" title="تعديل تفصيلي كامل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-2 btn-delete-patient" data-patient-id="${patient.id}" title="حذف">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            `;
            patientsTbody.appendChild(row);
        });
    }

    // تحديث الترقيم
    function updatePagination(paginationHtml) {
        paginationContainer.innerHTML = paginationHtml;
        const paginationLinks = paginationContainer.querySelectorAll('a');
        paginationLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const url = new URL(this.href);
                const page = url.searchParams.get('page') || 1;
                performSearchWithPage(page);
            });
        });
    }

    // ربط فلاتر القوائم المنسدلة
    [completionFilter, creatorFilter, insuranceFilter, genderFilter].forEach(el => {
        if (el) {
            el.addEventListener('change', function() {
                performSearchWithPage(1);
            });
        }
    });

    // فلتر التاريخ
    if (dateFilter) {
        dateFilter.addEventListener('change', function() {
            if (this.value === 'custom') {
                if (customDateRow) customDateRow.classList.remove('d-none');
            } else {
                if (customDateRow) customDateRow.classList.add('d-none');
                performSearchWithPage(1);
            }
        });
    }

    // مدخلات التواريخ المخصصة
    [dateFromInput, dateToInput].forEach(el => {
        if (el) {
            el.addEventListener('change', function() {
                performSearchWithPage(1);
            });
        }
    });

    // إعادة تعيين الفلاتر
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (completionFilter) completionFilter.value = '';
            if (creatorFilter) creatorFilter.value = '';
            if (insuranceFilter) insuranceFilter.value = '';
            if (genderFilter) genderFilter.value = '';
            if (dateFilter) dateFilter.value = '';
            if (dateFromInput) dateFromInput.value = '';
            if (dateToInput) dateToInput.value = '';
            if (customDateRow) customDateRow.classList.add('d-none');
            performSearchWithPage(1);
        });
    }

    // البحث التلقائي بالنص
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearchWithPage(1);
            }, 300);
        });

        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                performSearchWithPage(1);
            }
        });
    }

    // مسح خانة البحث
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            performSearchWithPage(1);
        });
    }

    // فتح / إغلاق صف التحرير السريع المدمج مباشرة تحت المريض
    document.addEventListener('click', function(e) {
        // إغلاق الصف عند الضغط على زر إلغاء
        const closeBtn = e.target.closest('.btn-close-drawer');
        if (closeBtn) {
            const drawerRow = closeBtn.closest('.quick-edit-row');
            if (drawerRow) drawerRow.remove();
            return;
        }

        const btn = e.target.closest('.btn-quick-complete');
        if (!btn) return;

        const patientId = btn.getAttribute('data-patient-id');
        const parentRow = document.getElementById(`patient-row-${patientId}`);
        if (!parentRow) return;

        // إذا كان الصف مفتوحاً مسبقاً لهذا المريض، يتم إغلاقه (Toggle)
        const existingDrawer = document.getElementById(`quick-edit-row-${patientId}`);
        if (existingDrawer) {
            existingDrawer.remove();
            return;
        }

        // إغلاق أي صف تحرير مفتوح لمريض آخر للحفاظ على نظافة الجدول
        document.querySelectorAll('.quick-edit-row').forEach(row => row.remove());

        const name = btn.getAttribute('data-name') || 'المريض';
        const phone = btn.getAttribute('data-phone') || '';
        const gender = btn.getAttribute('data-gender') || '';
        const ins = btn.getAttribute('data-ins') || '';
        const nid = btn.getAttribute('data-nid') || '';
        const age = btn.getAttribute('data-age') || '';
        const dob = btn.getAttribute('data-dob') || '';
        const cat = btn.getAttribute('data-cat') || 'ضمان صحي';

        // إنشاء صف التحرير المدمج مباشرة تحت صف المريض
        const drawerTr = document.createElement('tr');
        drawerTr.id = `quick-edit-row-${patientId}`;
        drawerTr.className = 'quick-edit-row';
        drawerTr.innerHTML = `
            <td colspan="8" class="p-3 bg-warning bg-opacity-10 border-top border-bottom border-warning">
                <div class="quick-edit-card p-3 bg-white border border-warning rounded-3 shadow-sm">
                    <form class="inline-quick-form no-loader" data-patient-id="${patientId}">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-user-edit me-1"></i>إكمال بيانات سريعة</span>
                                <strong class="text-dark fs-6"><i class="fas fa-user text-primary me-1"></i>${name}</strong>
                                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50">${cat}</span>
                                <small class="text-muted">• أدخل البيانات الناقصة أدناه واضغط "حفظ واعتماد" لتحديث السجل مباشرة</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-close-drawer py-1 px-2" title="إلغاء وإغلاق">
                                <i class="fas fa-times me-1"></i>إلغاء
                            </button>
                        </div>

                        <div class="inline-alert alert d-none mb-3 py-2 px-3 small" role="alert"></div>

                        <div class="row g-2 align-items-end">
                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fas fa-phone text-primary me-1"></i>رقم الهاتف <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light"><i class="fas fa-phone text-primary"></i></span>
                                    <input type="tel" class="form-control in-phone" value="${phone}" placeholder="07XXXXXXXXX" required dir="ltr">
                                </div>
                            </div>

                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fas fa-venus-mars text-info me-1"></i>الجنس <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm in-gender" required>
                                    <option value="">-- اختر --</option>
                                    <option value="male" ${gender === 'male' ? 'selected' : ''}>ذكر</option>
                                    <option value="female" ${gender === 'female' ? 'selected' : ''}>أنثى</option>
                                </select>
                            </div>

                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fas fa-calendar-alt text-primary me-1"></i>تاريخ الميلاد
                                </label>
                                <input type="date" class="form-control form-control-sm in-dob font-monospace" value="${dob}">
                            </div>

                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fas fa-id-card text-success me-1"></i>رقم بطاقة الضمان
                                </label>
                                <input type="text" class="form-control form-control-sm in-ins font-monospace" value="${ins}" placeholder="رقم البطاقة">
                            </div>

                            <div class="col-xl-2 col-md-4 col-sm-6">
                                <label class="form-label fw-bold text-dark small mb-1">
                                    <i class="fas fa-fingerprint text-secondary me-1"></i>البطاقة الموحدة
                                </label>
                                <input type="text" class="form-control form-control-sm in-nid font-monospace" value="${nid}" placeholder="أرقام فقط" dir="ltr">
                            </div>

                            <div class="col-xl-2 col-md-4 col-sm-12 d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm fw-bold flex-grow-1 in-submit-btn">
                                    <span class="spinner-border spinner-border-sm me-1 d-none in-spinner" role="status"></span>
                                    <i class="fas fa-save me-1 in-save-icon"></i>حفظ واعتماد
                                </button>
                                <button type="button" class="btn btn-light btn-sm border btn-close-drawer">
                                    إلغاء
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </td>
        `;

        // إدراج الصف مباشرة أسفل صف المريض
        parentRow.insertAdjacentElement('afterend', drawerTr);

        // التركيز التلقائي على أول حقل ناقص
        const phoneInput = drawerTr.querySelector('.in-phone');
        const genderSelect = drawerTr.querySelector('.in-gender');
        const dobInput = drawerTr.querySelector('.in-dob');
        const insInput = drawerTr.querySelector('.in-ins');
        const nidInput = drawerTr.querySelector('.in-nid');

        if (!phone) {
            phoneInput.focus();
        } else if (!gender) {
            genderSelect.focus();
        } else if (!dob) {
            dobInput.focus();
        } else if (!ins) {
            insInput.focus();
        } else {
            nidInput.focus();
        }
    });

    // معالجة إرسال نموذج التحرير السريع المدمج عبر AJAX
    document.addEventListener('submit', function(e) {
        const form = e.target.closest('.inline-quick-form');
        if (!form) return;

        e.preventDefault();
        e.stopPropagation();
        const patientId = form.getAttribute('data-patient-id');
        if (!patientId) return;

        const parentRow = document.getElementById(`patient-row-${patientId}`);
        const drawerRow = document.getElementById(`quick-edit-row-${patientId}`);
        const alertBox = form.querySelector('.inline-alert');
        const submitBtn = form.querySelector('.in-submit-btn');
        const spinner = form.querySelector('.in-spinner');
        const saveIcon = form.querySelector('.in-save-icon');

        const phoneVal = form.querySelector('.in-phone').value.trim();
        const genderVal = form.querySelector('.in-gender').value;
        const dobVal = form.querySelector('.in-dob') ? form.querySelector('.in-dob').value.trim() : '';
        const insVal = form.querySelector('.in-ins').value.trim();
        const nidVal = form.querySelector('.in-nid').value.trim();

        submitBtn.disabled = true;
        spinner.classList.remove('d-none');
        saveIcon.classList.add('d-none');
        alertBox.classList.add('d-none');

        const payload = {
            phone: phoneVal,
            gender: genderVal,
            date_of_birth: dobVal,
            insurance_number: insVal,
            national_id: nidVal,
        };

        fetch(`${patientsBaseUrl}/${patientId}/quick-update`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join('<br>') : 'تعذر حفظ البيانات'));
            }
            return data;
        })
        .then(data => {
            alertBox.className = 'inline-alert alert alert-success mb-3 py-2 px-3 small';
            alertBox.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${data.message}`;
            alertBox.classList.remove('d-none');

            // تحديث بيانات صف المريض في الجدول مباشرة دون الحاجة لإعادة تحميل الصفحة
            if (parentRow) {
                // تحديث الهاتف
                const phoneText = parentRow.querySelector('.phone-text');
                if (phoneText) {
                    phoneText.innerHTML = `<i class="fas fa-phone me-1 text-primary"></i>${phoneVal}`;
                }

                // تحديث وسم الجنس
                const genderBadge = parentRow.querySelector('.patient-gender-badge');
                if (genderBadge) {
                    genderBadge.className = 'badge bg-light text-secondary border patient-gender-badge';
                    genderBadge.textContent = (genderVal === 'male') ? 'ذكر' : 'أنثى';
                }

                // تحديث وسم العمر
                const ageBadge = parentRow.querySelector('.patient-age-badge');
                if (data.patient && data.patient.age) {
                    if (ageBadge) {
                        ageBadge.className = 'badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 patient-age-badge';
                        ageBadge.textContent = `${data.patient.age} سنة`;
                    } else {
                        const metaBadges = parentRow.querySelector('.patient-meta-badges');
                        if (metaBadges) {
                            const newAgeSpan = document.createElement('span');
                            newAgeSpan.className = 'badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 patient-age-badge';
                            newAgeSpan.style.fontSize = '0.70rem';
                            newAgeSpan.textContent = `${data.patient.age} سنة`;
                            metaBadges.appendChild(newAgeSpan);
                        }
                    }
                }

                // تحديث وسم رقم الضمان
                const insNumBox = parentRow.querySelector('.insurance-num-box');
                if (insNumBox && insVal) {
                    insNumBox.innerHTML = `<span class="badge bg-white text-dark border font-monospace px-2 py-1"><i class="fas fa-id-card text-muted me-1"></i>${insVal}</span>`;
                }

                // تحديث الرقم الوطني
                const nidBox = parentRow.querySelector('.patient-nid-container');
                if (nidBox && nidVal) {
                    nidBox.innerHTML = `<small class="text-muted font-monospace d-block mt-1" style="font-size: 0.75rem;"><i class="fas fa-fingerprint me-1"></i>${nidVal}</small>`;
                }

                // تحديث سمات زر الإكمال السريع بالبيانات الجديدة
                const quickBtn = parentRow.querySelector('.btn-quick-complete');
                if (quickBtn && data.patient) {
                    quickBtn.setAttribute('data-phone', data.patient.phone || '');
                    quickBtn.setAttribute('data-gender', data.patient.gender || '');
                    quickBtn.setAttribute('data-age', data.patient.age || '');
                    quickBtn.setAttribute('data-dob', data.patient.date_of_birth || '');
                    quickBtn.setAttribute('data-ins', data.patient.insurance_number || '');
                    quickBtn.setAttribute('data-nid', data.patient.national_id || '');
                }

                // تحديث عمود حالة السجل إلى مكتمل
                const statusCol = parentRow.querySelector('.status-col');
                if (statusCol) {
                    statusCol.innerHTML = `
                        <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-50 px-2 py-1 rounded-pill fw-bold" style="font-size: 0.72rem;">
                            <i class="fas fa-check-circle me-1"></i>مكتمل ✓
                        </span>
                    `;
                }

                // إزالة زر إكمال من عمود الإجراءات
                if (quickBtn) {
                    quickBtn.remove();
                }

                // وميض أخضر خفيف احتفالاً بالاكتمال
                parentRow.classList.add('table-success');
            }

            // إغلاق الصف المنزلق بنعومة بعد ثانية واحدة
            setTimeout(() => {
                if (drawerRow) drawerRow.remove();
                if (parentRow) parentRow.classList.remove('table-success');
            }, 800);
        })
        .catch(err => {
            alertBox.className = 'inline-alert alert alert-danger mb-3 py-2 px-3 small';
            alertBox.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> ${err.message}`;
            alertBox.classList.remove('d-none');
        })
        .finally(() => {
            submitBtn.disabled = false;
            spinner.classList.add('d-none');
            saveIcon.classList.remove('d-none');
            if (window.hidePageLoader) {
                window.hidePageLoader(true);
            }
        });
    });

    // حذف المريض
    patientsTbody.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn-delete-patient');
        if (!btn) return;

        if (!confirm('هل أنت متأكد من حذف المريض؟')) return;

        const patientId = btn.getAttribute('data-patient-id');
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = patientsBaseUrl + '/' + patientId;
        form.style.display = 'none';
        form.innerHTML = `
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="${getCsrfToken()}">
        `;

        document.body.appendChild(form);
        form.submit();
    });

    // فتح دراور الإكمال السريع تلقائياً إذا تم التوجيه من إشعار الضمان
    const urlParams = new URLSearchParams(window.location.search);
    const openQuickId = urlParams.get('open_quick');
    if (openQuickId) {
        setTimeout(() => {
            const targetBtn = document.querySelector(`.btn-quick-complete[data-id="${openQuickId}"]`);
            if (targetBtn) {
                targetBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetBtn.click();
            }
        }, 350);
    }
});
</script>
@endsection