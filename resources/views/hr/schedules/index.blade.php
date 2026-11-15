@extends('layouts.app')
@section('title', 'جدول الدوام والخفارات - HR')

@section('content')
<div class="container-fluid schedule-roster-container px-3 py-2">
    <!-- Top Action & Navigation Bar -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2 no-print bg-white p-2 rounded-3 shadow-sm border">
        <div class="d-flex align-items-center gap-2">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                <i class="fas fa-calendar-alt text-primary me-2"></i>جدول الدوام والخفارات
            </h5>
            <span class="badge bg-light text-dark border px-2 py-1 small">
                {{ $startDate->format('Y-m-d') }} &larr; {{ $endDate->format('Y-m-d') }}
            </span>
        </div>

        <!-- Center: Quick Nav & View Mode -->
        <div class="d-flex gap-2 align-items-center">
            <div class="btn-group btn-group-sm shadow-xs">
                <a href="{{ route('hr.schedules.index', ['start_date' => $prevDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-secondary" title="الفترة السابقة">
                    <i class="fas fa-chevron-right"></i> السابق
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $currentPeriodDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-primary fw-bold" title="الانتقال إلى اليوم الحالي">
                    <i class="fas fa-dot-circle text-danger me-1"></i> الحالي
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $nextDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-secondary" title="الفترة التالية">
                    التالي <i class="fas fa-chevron-left"></i>
                </a>
            </div>

            <div class="btn-group btn-group-sm shadow-xs">
                <a href="{{ route('hr.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'view_mode' => 'week', 'department_id' => $departmentId]) }}" 
                   class="btn {{ $viewMode === 'week' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                    أسبوعي (7 أيام)
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'view_mode' => 'month', 'department_id' => $departmentId]) }}" 
                   class="btn {{ $viewMode === 'month' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                    شهري كامل
                </a>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="d-flex gap-1 align-items-center">
            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#autoFillModal" title="تطبيق دوام الموظفين الافتراضي">
                <i class="fas fa-magic me-1"></i> تعبئة تلقائية
            </button>
            <form action="{{ route('hr.schedules.copy-last-week') }}" method="POST" class="d-inline-block">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="department_id" value="{{ $departmentId }}">
                <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('نسخ جدول الأسبوع الماضي؟')" title="نسخ جدول الأسبوع الماضي">
                    <i class="fas fa-copy"></i>
                </button>
            </form>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearRangeModal" title="تفريغ الجدول">
                <i class="fas fa-trash-alt"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-dark" onclick="window.print()" title="طباعة رسمية">
                <i class="fas fa-print me-1"></i> طباعة
            </button>
            <button type="submit" form="rosterForm" class="btn btn-sm btn-success fw-bold px-3 shadow-sm">
                <i class="fas fa-save me-1"></i> حفظ
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm py-2 px-3 small border-0 mb-2 no-print" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm py-2 px-3 small border-0 mb-2 no-print" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Compact KPI & Summary Strip -->
    <div class="d-flex flex-wrap align-items-center justify-content-between bg-white border rounded-3 px-3 py-1 mb-2 no-print small shadow-xs">
        <div class="d-flex align-items-center gap-3">
            <span><i class="fas fa-users text-primary me-1"></i> الكوادر الظاهرة: <strong id="visibleStaffCount">{{ $employees->count() }}</strong></span>
            <span class="text-muted">|</span>
            <span><i class="fas fa-clock text-warning me-1"></i> إجمالي نوبات العمل: <strong class="text-success">{{ $kpiStats['total_shifts'] ?? 0 }}</strong></span>
            <span class="text-muted">|</span>
            <span><i class="fas fa-coffee text-secondary me-1"></i> أيام الراحة (Off): <strong>{{ $kpiStats['total_off'] ?? 0 }}</strong></span>
            <span class="text-muted">|</span>
            <span><i class="fas fa-layer-group text-danger me-1"></i> كادر الوجبتين: <strong class="badge bg-warning text-dark">{{ $kpiStats['split_count'] ?? 0 }}</strong></span>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <button type="button" class="btn btn-link text-warning p-0 text-decoration-none small fw-bold" data-bs-toggle="modal" data-bs-target="#swapModal">
                <i class="fas fa-exchange-alt me-1"></i> تبادل خفارات
            </button>
            <span class="text-muted">•</span>
            <button type="button" class="btn btn-link text-danger p-0 text-decoration-none small fw-bold" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                <i class="fas fa-ambulance me-1"></i> بديل طوارئ
            </button>
        </div>
    </div>

    <!-- Unified Sleek Filter & Quick Stamp Palette Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-2 no-print bg-light">
        <div class="card-body p-2">
            <div class="row g-2 align-items-center">
                <!-- Department & Search & Type Filter -->
                <div class="col-xl-6 col-lg-7 d-flex gap-2 align-items-center flex-wrap">
                    <!-- Dept Filter -->
                    <form action="{{ route('hr.schedules.index') }}" method="GET" class="d-inline-block m-0" id="quickDeptForm">
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">
                        <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                        <select name="department_id" class="form-select form-select-sm py-1 shadow-xs" style="max-width: 170px;" onchange="document.getElementById('quickDeptForm').submit()">
                            <option value="">جميع الأقسام</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </form>

                    <!-- Instant Search Input -->
                    <div class="input-group input-group-sm shadow-xs" style="max-width: 190px;">
                        <span class="input-group-text bg-white border-end-0 py-0"><i class="fas fa-search text-muted small"></i></span>
                        <input type="text" id="rosterSearchInput" class="form-control form-control-sm border-start-0 py-1" placeholder="بحث بالاسم أو الوظيفة..." onkeyup="filterRosterRows()">
                    </div>

                    <!-- Filter Pills -->
                    <div class="btn-group btn-group-sm shadow-xs" role="group">
                        <button type="button" class="btn btn-outline-secondary active filter-pill-btn py-1" onclick="setRosterScheduleFilter('all', this)">الكل</button>
                        <button type="button" class="btn btn-outline-secondary filter-pill-btn py-1" onclick="setRosterScheduleFilter('fixed', this)">ثابت</button>
                        <button type="button" class="btn btn-outline-secondary filter-pill-btn py-1" onclick="setRosterScheduleFilter('rotating', this)">خفارات</button>
                        <button type="button" class="btn btn-outline-secondary filter-pill-btn py-1" onclick="setRosterScheduleFilter('split', this)">وجبتين</button>
                    </div>
                </div>

                <!-- Quick Stamp Tool Palette -->
                <div class="col-xl-6 col-lg-5 d-flex justify-content-xl-end align-items-center gap-1 flex-wrap">
                    <span class="small fw-bold text-muted me-1"><i class="fas fa-stamp text-primary"></i> أداة الختم السريع:</span>
                    <button type="button" class="btn btn-xs btn-outline-dark stamp-btn active py-1 px-2" data-val="" data-color="" onclick="selectStamp(this)" title="مسح / فراغ">
                        <i class="fas fa-eraser"></i>
                    </button>
                    <button type="button" class="btn btn-xs btn-secondary stamp-btn py-1 px-2" data-val="off" data-color="#6c757d" onclick="selectStamp(this)" title="راحة Off">
                        Off
                    </button>
                    @foreach($shifts as $shift)
                        <button type="button" class="btn btn-xs text-white stamp-btn py-1 px-2 fw-bold" 
                                style="background-color: {{ $shift->color_code }}; border-color: {{ $shift->color_code }}; font-size: 0.75rem;" 
                                data-val="{{ $shift->id }}" 
                                data-color="{{ $shift->color_code }}" 
                                onclick="selectStamp(this)"
                                title="{{ $shift->name }} ({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})">
                            {{ Str::limit($shift->name, 8, '') }}
                        </button>
                    @endforeach
                    <div class="form-check form-switch m-0 ms-2 pt-1">
                        <input class="form-check-input" type="checkbox" id="ajaxAutoSaveToggle" checked>
                        <label class="form-check-label small text-muted" for="ajaxAutoSaveToggle" title="حفظ فوري عبر AJAX">حفظ تلقائي</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Hospital Print Header (Visible Only on Print) -->
    <div class="d-none d-print-block print-header mb-3 text-center border-bottom pb-2">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-start">
                <h6 class="fw-bold mb-0">جمهورية العراق</h6>
                <p class="small mb-0">وزارة الصحة / إدارة المستشفى</p>
                <p class="small fw-bold mb-0">قسم الموارد البشرية - جدول الدوام والخفارات</p>
            </div>
            <div class="text-center">
                <h5 class="fw-bold mb-1">جدول الخفارات والدوام المعتمد</h5>
                <p class="mb-0 fw-bold small">الفترة: {{ $startDate->format('Y-m-d') }} &larr; {{ $endDate->format('Y-m-d') }} | القسم: {{ $departments->firstWhere('id', $departmentId)->name ?? 'كافة الأقسام' }}</p>
            </div>
            <div class="text-end">
                <p class="small mb-0">تاريخ الطباعة: {{ now()->format('Y-m-d') }}</p>
                <p class="small text-muted mb-0">وثيقة رسمية معتمدة</p>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive roster-table-wrapper" style="max-height: 72vh;">
            <form action="{{ route('hr.schedules.store') }}" method="POST" id="rosterForm">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                
                <table class="table table-bordered table-hover align-middle mb-0 text-center roster-table" style="min-width: {{ count($dates) * 110 + 230 }}px;" id="rosterMainTable">
                    <!-- Table Head -->
                    <thead class="bg-light sticky-top shadow-xs" style="z-index: 10;">
                        <tr>
                            <th class="align-middle text-start roster-emp-th py-2 px-3" style="width: 230px; min-width: 230px; position: sticky; right: 0; background-color: #f8f9fa; z-index: 11;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-dark small">الموظف / القسم</span>
                                    <span class="badge bg-secondary text-white" style="font-size: 0.6rem;">النمط</span>
                                </div>
                            </th>
                            @foreach($dates as $date)
                                @php
                                    $isToday = $date->isToday();
                                    $isFriday = $date->isFriday();
                                @endphp
                                <th class="align-middle roster-date-th py-1 px-1 {{ $isToday ? 'bg-primary bg-opacity-10 text-primary border-primary' : '' }} {{ $isFriday ? 'bg-secondary bg-opacity-10' : '' }}" style="width: 110px; min-width: 110px;">
                                    <div class="fw-bold small {{ $isToday ? 'text-primary' : '' }}">
                                        {{ $date->locale('ar')->translatedFormat('l') }}
                                        @if($isToday)
                                            <span class="badge bg-danger ms-1" style="font-size: 0.55rem;">اليوم</span>
                                        @endif
                                    </div>
                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $date->format('m/d') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <!-- Table Body -->
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $empSchedules = $existingSchedules->get($emp->id, collect());
                                $isSplit = (bool) $emp->is_split_shift;
                                $scheduleType = $emp->schedule_type ?? 'rotating';
                                if ($isSplit) {
                                    $filterType = 'split';
                                } elseif ($scheduleType === 'fixed') {
                                    $filterType = 'fixed';
                                } else {
                                    $filterType = 'rotating';
                                }
                            @endphp
                            <tr class="roster-emp-row {{ $isSplit ? 'split-shift-row' : '' }}" data-schedule-type="{{ $filterType }}" data-emp-name="{{ strtolower($emp->full_name) }}" data-emp-job="{{ strtolower($emp->job_title) }}" data-emp-id="{{ $emp->id }}">
                                <!-- Sticky Employee Info Cell -->
                                <td class="text-start roster-emp-cell py-1 px-2" style="position: sticky; right: 0; background-color: white; z-index: 5;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="fw-bold text-dark emp-name-text" style="font-size: 0.85rem;">{{ $emp->full_name }}</span>
                                        <div class="d-flex gap-1 align-items-center">
                                            @if($isSplit)
                                                <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.6rem;" title="دوام وجبتين">وجبتين</span>
                                            @elseif($scheduleType === 'fixed')
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary" style="font-size: 0.6rem;" title="دوام ثابت">ثابت</span>
                                            @else
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info" style="font-size: 0.6rem;" title="خفارات متناوبة">متناوب</span>
                                            @endif

                                            <!-- Row Menu -->
                                            <div class="dropdown no-print">
                                                <button class="btn btn-sm btn-light border-0 py-0 px-1 text-muted" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v" style="font-size: 0.7rem;"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                    <li><h6 class="dropdown-header py-1 small">تعبئة سريعة لـ {{ Str::words($emp->full_name, 2, '') }}</h6></li>
                                                    @if($emp->defaultShift)
                                                        <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '{{ $emp->default_shift_id }}', '{{ $emp->defaultShift->color_code }}', {{ $isSplit ? "'".$emp->second_shift_id."'" : 'null' }}, {{ $isSplit ? "'".($emp->secondShift->color_code ?? '')."'" : 'null' }})"><i class="fas fa-magic text-primary me-2"></i>تطبيق الشفت الدائم</a></li>
                                                    @endif
                                                    @foreach($shifts as $sh)
                                                        <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '{{ $sh->id }}', '{{ $sh->color_code }}', null, null)"><i class="fas fa-clock me-2" style="color: {{ $sh->color_code }}"></i>تعبئة الكل بـ {{ $sh->name }}</a></li>
                                                    @endforeach
                                                    <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', 'off', '#6c757d', 'off', '#6c757d')"><i class="fas fa-coffee text-secondary me-2"></i>تعبئة الكل راحة (Off)</a></li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li><a class="dropdown-item py-1 text-danger" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '', '', '', '')"><i class="fas fa-eraser me-2"></i>مسح أسبوع الموظف</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-muted emp-job-text" style="font-size: 0.7rem;">
                                        {{ $emp->department->name ?? '—' }} | {{ $emp->job_title }}
                                        @if($isSplit && $emp->defaultShift && $emp->secondShift)
                                            <div class="text-secondary" style="font-size: 0.65rem;">
                                                <i class="fas fa-sun text-warning me-1"></i>{{ $emp->defaultShift->name }} + <i class="fas fa-moon text-primary me-1"></i>{{ $emp->secondShift->name }}
                                            </div>
                                        @elseif($emp->defaultShift)
                                            <span class="text-secondary ms-1">({{ $emp->defaultShift->name }})</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Shift Day Cells -->
                                @foreach($dates as $date)
                                    @php
                                        $dateStr = $date->format('Y-m-d');
                                        $daySchedules = $empSchedules->filter(function($item) use ($dateStr) { 
                                            return $item->shift_date->format('Y-m-d') === $dateStr; 
                                        });

                                        $val1 = '';
                                        $color1 = '';
                                        $val2 = '';
                                        $color2 = '';

                                        if ($daySchedules->isNotEmpty()) {
                                            $isOff = $daySchedules->contains('is_off_day', true);
                                            if ($isOff) {
                                                $val1 = 'off';
                                                $color1 = '#6c757d';
                                                $val2 = 'off';
                                                $color2 = '#6c757d';
                                            } else {
                                                $firstSch = $daySchedules->first();
                                                if ($firstSch && $firstSch->hr_shift_id) {
                                                    $val1 = $firstSch->hr_shift_id;
                                                    $color1 = $firstSch->shift->color_code ?? '#0d6efd';
                                                }
                                                $secondSch = $daySchedules->skip(1)->first();
                                                if ($secondSch && $secondSch->hr_shift_id) {
                                                    $val2 = $secondSch->hr_shift_id;
                                                    $color2 = $secondSch->shift->color_code ?? '#ffc107';
                                                }
                                            }
                                        } elseif ($emp->schedule_type === 'fixed') {
                                            if ($emp->isOffOn($date)) {
                                                $val1 = 'off';
                                                $color1 = '#6c757d';
                                                $val2 = 'off';
                                                $color2 = '#6c757d';
                                            } else {
                                                if ($emp->default_shift_id) {
                                                    $val1 = $emp->default_shift_id;
                                                    $color1 = $emp->defaultShift->color_code ?? '#0d6efd';
                                                }
                                                if ($isSplit && $emp->second_shift_id) {
                                                    $val2 = $emp->second_shift_id;
                                                    $color2 = $emp->secondShift->color_code ?? '#ffc107';
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="p-1 roster-cell position-relative" data-date="{{ $dateStr }}" data-emp-id="{{ $emp->id }}" data-is-split="{{ $isSplit ? '1' : '0' }}">
                                        @if($isSplit)
                                            <!-- Double Shift / Split Shift Box (الوجبتين) -->
                                            <div class="split-shift-box d-flex flex-column gap-1">
                                                <!-- Slot 1 (الوجبة الأولى) -->
                                                <div class="input-group input-group-xs" title="الوجبة الأولى (الصباحية)">
                                                    <span class="input-group-text p-0 px-1 border-0 text-muted" style="font-size: 0.6rem; background: #e9ecef;">1</span>
                                                    <select name="schedules[{{ $emp->id }}][{{ $dateStr }}][slot1]" 
                                                            class="form-select roster-select roster-slot1 text-center py-0" 
                                                            data-emp-id="{{ $emp->id }}"
                                                            data-date="{{ $dateStr }}"
                                                            data-slot="slot1"
                                                            style="color: {{ $color1 ? '#fff' : 'inherit' }}; background-color: {{ $color1 ? $color1 : 'transparent' }}; border-color: {{ $color1 ? $color1 : '#dee2e6' }}; font-size: 0.72rem; height: 24px;"
                                                            onchange="updateSelectColor(this, true)"
                                                            onclick="handleCellSlotStampClick(this, event)">
                                                        <option value="" style="color: black; background: white;">— فراغ —</option>
                                                        <option value="off" data-color="#6c757d" style="color: white; background: #6c757d;" {{ $val1 === 'off' ? 'selected' : '' }}>راحه (Off)</option>
                                                        @foreach($shifts as $shift)
                                                            <option value="{{ $shift->id }}" data-color="{{ $shift->color_code }}" style="color: white; background: {{ $shift->color_code }};" {{ $val1 == $shift->id ? 'selected' : '' }}>
                                                                {{ $shift->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Slot 2 (الوجبة الثانية) -->
                                                <div class="input-group input-group-xs" title="الوجبة الثانية (المسائية)">
                                                    <span class="input-group-text p-0 px-1 border-0 text-muted" style="font-size: 0.6rem; background: #e9ecef;">2</span>
                                                    <select name="schedules[{{ $emp->id }}][{{ $dateStr }}][slot2]" 
                                                            class="form-select roster-select roster-slot2 text-center py-0" 
                                                            data-emp-id="{{ $emp->id }}"
                                                            data-date="{{ $dateStr }}"
                                                            data-slot="slot2"
                                                            style="color: {{ $color2 ? '#fff' : 'inherit' }}; background-color: {{ $color2 ? $color2 : 'transparent' }}; border-color: {{ $color2 ? $color2 : '#dee2e6' }}; font-size: 0.72rem; height: 24px;"
                                                            onchange="updateSelectColor(this, true)"
                                                            onclick="handleCellSlotStampClick(this, event)">
                                                        <option value="" style="color: black; background: white;">— فراغ —</option>
                                                        <option value="off" data-color="#6c757d" style="color: white; background: #6c757d;" {{ $val2 === 'off' ? 'selected' : '' }}>راحه (Off)</option>
                                                        @foreach($shifts as $shift)
                                                            <option value="{{ $shift->id }}" data-color="{{ $shift->color_code }}" style="color: white; background: {{ $shift->color_code }};" {{ $val2 == $shift->id ? 'selected' : '' }}>
                                                                {{ $shift->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Single Shift Box (دوام اعتيادي) -->
                                            <select name="schedules[{{ $emp->id }}][{{ $dateStr }}][slot1]" 
                                                    class="form-select roster-select roster-slot1 text-center py-1" 
                                                    data-emp-id="{{ $emp->id }}"
                                                    data-date="{{ $dateStr }}"
                                                    data-slot="slot1"
                                                    style="color: {{ $color1 ? '#fff' : 'inherit' }}; background-color: {{ $color1 ? $color1 : 'transparent' }}; border-color: {{ $color1 ? $color1 : '#dee2e6' }}; font-size: 0.75rem; height: 28px;"
                                                    onchange="updateSelectColor(this, true)"
                                                    onclick="handleCellSlotStampClick(this, event)">
                                                <option value="" style="color: black; background: white;">— فراغ —</option>
                                                <option value="off" data-color="#6c757d" style="color: white; background: #6c757d;" {{ $val1 === 'off' ? 'selected' : '' }}>يوم راحة (Off)</option>
                                                @foreach($shifts as $shift)
                                                    <option value="{{ $shift->id }}" data-color="{{ $shift->color_code }}" style="color: white; background: {{ $shift->color_code }};" {{ $val1 == $shift->id ? 'selected' : '' }}>
                                                        {{ $shift->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        <!-- Print-only View -->
                                        <div class="d-none d-print-block print-cell-label small fw-bold">
                                            @if($val1 === 'off' || $val2 === 'off')
                                                <span class="text-secondary">Off</span>
                                            @elseif($isSplit)
                                                @php
                                                    $s1 = $shifts->firstWhere('id', $val1);
                                                    $s2 = $shifts->firstWhere('id', $val2);
                                                @endphp
                                                <span>{{ $s1 ? $s1->name : '—' }}</span>
                                                <br>
                                                <span class="text-primary">{{ $s2 ? $s2->name : '—' }}</span>
                                            @elseif($val1)
                                                @php $s1 = $shifts->firstWhere('id', $val1); @endphp
                                                <span>{{ $s1 ? $s1->name : '—' }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($dates) + 1 }}" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-slash fa-2x mb-2 text-muted opacity-50"></i>
                                    <div>لا يوجد موظفون لعرضهم في هذا القسم أو الفلتر.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <!-- Clean & Streamlined Daily Shift Coverage Footer -->
                    <tfoot class="bg-light sticky-bottom shadow-xs border-top" style="z-index: 9;">
                        <tr class="coverage-row align-middle">
                            <td class="text-start roster-emp-cell bg-light py-2 px-3" style="position: sticky; right: 0; z-index: 6;">
                                <div class="fw-bold text-dark small"><i class="fas fa-chart-pie text-primary me-1"></i> إجمالي التغطية اليومية</div>
                                <div class="text-muted" style="font-size: 0.65rem;">إجمالي الكوادر المداومة</div>
                            </td>
                            @foreach($dates as $date)
                                @php
                                    $dStr = $date->format('Y-m-d');
                                    $dayCov = $dailyCoverage[$dStr] ?? ['shifts' => [], 'off' => 0, 'empty' => 0, 'total' => 0];
                                @endphp
                                <td class="p-1 coverage-cell text-center" data-date="{{ $dStr }}">
                                    <!-- Compact Horizontal Badge Strip -->
                                    <div class="d-flex flex-wrap gap-1 justify-content-center align-items-center coverage-badges-wrapper">
                                        @foreach($shifts as $sh)
                                            @php $cnt = $dayCov['shifts'][$sh->id] ?? 0; @endphp
                                            <span class="badge rounded-pill text-white cov-shift-badge px-1 py-0" 
                                                  data-shift-id="{{ $sh->id }}" 
                                                  style="background-color: {{ $sh->color_code }}; font-size: 0.65rem; min-width: 20px;" 
                                                  title="{{ $sh->name }}: {{ $cnt }} كادر">
                                                {{ Str::limit($sh->name, 4, '') }}: <strong class="badge-num">{{ $cnt }}</strong>
                                            </span>
                                        @endforeach
                                        <span class="badge rounded-pill bg-secondary text-white cov-off-badge px-1 py-0" 
                                              style="font-size: 0.65rem;" 
                                              title="أيام راحة (Off): {{ $dayCov['off'] ?? 0 }}">
                                            Off: <strong class="off-num">{{ $dayCov['off'] ?? 0 }}</strong>
                                        </span>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>

    <!-- Official Print Signatures -->
    <div class="d-none d-print-block print-signatures mt-4 pt-3 border-top">
        <div class="row text-center">
            <div class="col-4">
                <p class="fw-bold mb-3 small">مسؤول القسم / الشعبة</p>
                <p class="text-muted small">التوقيع: ...........................</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-3 small">مدقق جدول الموارد البشرية</p>
                <p class="text-muted small">التوقيع: ...........................</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-3 small">اعتماد إدارة المستشفى</p>
                <p class="text-muted small">الختم والتوقيع: ...........................</p>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- Auto-Fill Defaults Modal -->
<div class="modal fade" id="autoFillModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.schedules.auto-fill-defaults') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title fw-bold"><i class="fas fa-magic me-2"></i>تعبئة الدوام الافتراضي تلقائياً</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-muted small mb-2">
                        يقوم هذا الإجراء بتطبيق إعدادات كل موظف (الشفت الدائم + أيام الاستراحة + شفت الوجبة الثانية لموظفي الوجبتين) آلياً على كامل أيام الفترة المعروضة بضغطة زر واحدة.
                    </p>
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">

                    <div class="alert alert-info border-0 py-2 px-3 small mb-2">
                        <i class="fas fa-info-circle me-1"></i> الفترة: <strong>{{ $startDate->format('Y-m-d') }}</strong> إلى <strong>{{ $endDate->format('Y-m-d') }}</strong>
                        <br>
                        القسم: <strong>{{ $departments->firstWhere('id', $departmentId)->name ?? 'كافة الأقسام' }}</strong>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwriteCheck">
                        <label class="form-check-label fw-bold small text-dark" for="overwriteCheck">
                            استبدال الشفتات المجدولة مسبقاً (إعادة بناء وتحديث كامل للفترة)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">إذا لم تفعل هذا الخيار، سيتم تعبئة الأيام الفارغة فقط مع الحفاظ على التعديلات اليدوية.</small>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold px-3"><i class="fas fa-check me-1"></i> تطبيق الدوام الافتراضي</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear Range Modal -->
<div class="modal fade" id="clearRangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.schedules.clear-range') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title fw-bold"><i class="fas fa-trash-alt me-2"></i>تفريغ الجدول</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <p class="text-danger fw-bold small mb-1">هل أنت متأكد من تفريغ كافة شفتات الفترة؟</p>
                    <p class="text-muted small mb-0">سيتم حذف شفتات الفترة المحددة للقسم للبدء من الصفر.</p>
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-danger fw-bold px-3">نعم، تفريغ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Swap Modal -->
<div class="modal fade" id="swapModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.schedules.swap') }}" method="POST">
                @csrf
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title fw-bold text-dark"><i class="fas fa-exchange-alt me-2"></i>تبادل شفتات / خفارات</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">تاريخ التبادل</label>
                        <input type="date" name="swap_date" class="form-control form-control-sm" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">الموظف الأول</label>
                        <select name="employee1_id" class="form-select form-select-sm" required>
                            <option value="">اختر الموظف...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->job_title }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-center my-1 text-muted small"><i class="fas fa-arrow-up"></i> تبادل <i class="fas fa-arrow-down"></i></div>
                    <div class="mb-2">
                        <label class="form-label small fw-bold">الموظف الثاني (البديل)</label>
                        <select name="employee2_id" class="form-select form-select-sm" required>
                            <option value="">اختر الموظف...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->job_title }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-warning fw-bold px-3">تأكيد التبادل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Emergency Cover Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white py-2">
                <h6 class="modal-title fw-bold"><i class="fas fa-ambulance me-2"></i>البحث عن بديل للطوارئ</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-2">اختر اليوم والقسم ليعرض لك النظام الموظفين المتاحين في (يوم راحة Off) لتغطية النقص.</p>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">التاريخ</label>
                        <input type="date" id="emDate" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">القسم</label>
                        <select id="emDept" class="form-select form-select-sm">
                            <option value="">أي قسم</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-danger w-100 mb-3 fw-bold" onclick="searchEmergencyCover()">
                    <i class="fas fa-search me-1"></i> بحث عن المتاحين
                </button>

                <div id="emResults" class="d-none">
                    <h6 class="fw-bold mb-2 text-success small">المتاحون في هذا اليوم (Off):</h6>
                    <ul class="list-group small" id="emList">
                        <!-- Populated dynamically -->
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Toast for AJAX Auto-save Feedback -->
<div class="position-fixed bottom-0 start-0 p-3" style="z-index: 9999;">
    <div id="rosterToast" class="toast align-items-center text-white bg-dark border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body small py-2" id="rosterToastBody">
                <i class="fas fa-check-circle text-success me-1"></i> تم الحفظ التلقائي بنجاح.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ================= STYLES ================= -->
<style>
    .schedule-roster-container {
        font-family: inherit;
    }
    .roster-select {
        border-radius: 4px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .roster-cell:hover {
        background-color: #f4f8ff;
    }
    .split-shift-row {
        background-color: #fffdf5;
    }
    .split-shift-box {
        min-width: 90px;
    }
    .btn-xs {
        padding: 2px 6px;
        font-size: 0.72rem;
        border-radius: 4px;
    }
    .stamp-btn.active {
        box-shadow: 0 0 0 2px #0d6efd !important;
        transform: scale(1.06);
        font-weight: bold;
    }

    /* Print Styles */
    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }
        body {
            background: white !important;
            font-size: 10px !important;
        }
        .no-print, .btn, .navbar, .sidebar, .modal, .toast, .dropdown {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
        .roster-table-wrapper {
            max-height: none !important;
            overflow: visible !important;
        }
        .roster-table {
            min-width: 100% !important;
            width: 100% !important;
            font-size: 9px !important;
        }
        .roster-select, .input-group-text {
            display: none !important;
        }
        .print-cell-label {
            display: block !important;
        }
        .roster-emp-th, .roster-emp-cell {
            position: static !important;
            background-color: white !important;
        }
        .sticky-top, .sticky-bottom {
            position: static !important;
        }
        .coverage-row {
            page-break-inside: avoid;
        }
    }
</style>

<!-- ================= SCRIPTS ================= -->
<script>
    var activeStampVal = null;
    var activeStampColor = '';

    function selectStamp(btn) {
        document.querySelectorAll('.stamp-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeStampVal = btn.getAttribute('data-val');
        activeStampColor = btn.getAttribute('data-color') || '';
    }

    function handleCellSlotStampClick(selectObj, event) {
        if (activeStampVal === null) return;

        selectObj.value = activeStampVal;
        updateSelectColor(selectObj, true);
    }

    function updateSelectColor(selectObj, triggerAjax) {
        var selectedOption = selectObj.options[selectObj.selectedIndex];
        var color = selectedOption.getAttribute('data-color');
        
        if (color) {
            selectObj.style.backgroundColor = color;
            selectObj.style.color = 'white';
            selectObj.style.borderColor = color;
        } else {
            selectObj.style.backgroundColor = 'transparent';
            selectObj.style.color = 'inherit';
            selectObj.style.borderColor = '#dee2e6';
        }

        // If user set slot1 to 'off' and it's split shift, sync slot2 to off as well
        var cell = selectObj.closest('.roster-cell');
        if (cell && cell.getAttribute('data-is-split') === '1') {
            var slot = selectObj.getAttribute('data-slot');
            var otherSelect = (slot === 'slot1') ? cell.querySelector('.roster-slot2') : cell.querySelector('.roster-slot1');
            
            if (selectObj.value === 'off' && otherSelect && otherSelect.value !== 'off') {
                otherSelect.value = 'off';
                otherSelect.style.backgroundColor = '#6c757d';
                otherSelect.style.color = 'white';
                otherSelect.style.borderColor = '#6c757d';
            }
        }

        recalculateCoverage();
        checkFatigue();

        // AJAX Auto-Save if enabled
        var autoSaveEnabled = document.getElementById('ajaxAutoSaveToggle') && document.getElementById('ajaxAutoSaveToggle').checked;
        if (triggerAjax && autoSaveEnabled) {
            saveCellAjax(selectObj);
        }
    }

    function saveCellAjax(selectObj) {
        var empId = selectObj.getAttribute('data-emp-id');
        var date = selectObj.getAttribute('data-date');
        var slot = selectObj.getAttribute('data-slot');
        var val = selectObj.value;

        var cell = selectObj.closest('.roster-cell');
        var otherVal = '';
        if (cell && cell.getAttribute('data-is-split') === '1') {
            var otherSelect = (slot === 'slot1') ? cell.querySelector('.roster-slot2') : cell.querySelector('.roster-slot1');
            if (otherSelect) {
                otherVal = otherSelect.value;
            }
        }

        fetch('{{ route("hr.schedules.update-cell") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                hr_employee_id: empId,
                shift_date: date,
                slot: slot,
                shift_value: val,
                other_value: otherVal
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showRosterToast('تم حفظ التعديل لحظياً');
            }
        })
        .catch(err => {
            console.error('AJAX cell update error:', err);
        });
    }

    function showRosterToast(msg) {
        var toastEl = document.getElementById('rosterToast');
        if (toastEl) {
            document.getElementById('rosterToastBody').innerHTML = '<i class="fas fa-check-circle text-success me-1"></i> ' + msg;
            var toast = new bootstrap.Toast(toastEl, { delay: 1500 });
            toast.show();
        }
    }

    function fillEmployeeRow(empId, shift1Val, shift1Color, shift2Val, shift2Color) {
        var row = document.querySelector('tr[data-emp-id="' + empId + '"]');
        if (!row) return;

        var selects1 = row.querySelectorAll('select.roster-slot1');
        selects1.forEach(sel => {
            sel.value = shift1Val || '';
            updateSelectColor(sel, false);
        });

        var selects2 = row.querySelectorAll('select.roster-slot2');
        selects2.forEach(sel => {
            sel.value = shift2Val || '';
            updateSelectColor(sel, false);
        });

        recalculateCoverage();
        checkFatigue();
        showRosterToast('تم تعبئة أسبوع الموظف. اضغط "حفظ" للتثبيت النهائي.');
    }

    function recalculateCoverage() {
        var table = document.getElementById('rosterMainTable');
        if (!table) return;

        var covCells = table.querySelectorAll('tfoot td.coverage-cell');
        covCells.forEach(covTd => {
            var dateStr = covTd.getAttribute('data-date');
            if (!dateStr) return;

            // Gather all selects for this date
            var selects = table.querySelectorAll('tbody select.roster-select[data-date="' + dateStr + '"]');
            var counts = { off: 0 };
            
            // To prevent double counting off for the same employee who has two slots
            var countedOffEmps = new Set();

            selects.forEach(sel => {
                var row = sel.closest('tr');
                if (row && row.style.display === 'none') return; // ignore hidden filtered rows

                var empId = sel.getAttribute('data-emp-id');
                var val = sel.value;

                if (val === 'off') {
                    if (!countedOffEmps.has(empId)) {
                        counts.off = (counts.off || 0) + 1;
                        countedOffEmps.add(empId);
                    }
                } else if (val) {
                    counts[val] = (counts[val] || 0) + 1;
                }
            });

            // Update badges in this column
            var offBadge = covTd.querySelector('.off-num');
            if (offBadge) offBadge.textContent = counts.off || 0;

            covTd.querySelectorAll('.cov-shift-badge').forEach(badge => {
                var shId = badge.getAttribute('data-shift-id');
                var numSpan = badge.querySelector('.badge-num');
                if (numSpan) {
                    numSpan.textContent = counts[shId] || 0;
                }
            });
        });
    }

    function checkFatigue() {
        var rows = document.querySelectorAll('tr.roster-emp-row');
        rows.forEach(row => {
            var selects = row.querySelectorAll('select.roster-slot1');
            var workDaysCount = 0;
            selects.forEach(select => {
                if (select.value !== '' && select.value !== 'off') {
                    workDaysCount++;
                }
            });
            
            var nameCell = row.querySelector('td.roster-emp-cell');
            if (!nameCell) return;
            var existingBadge = nameCell.querySelector('.fatigue-badge');
            if (workDaysCount >= 6) {
                if (!existingBadge) {
                    var b = document.createElement('div');
                    b.className = 'fatigue-badge mt-1';
                    b.innerHTML = '<span class="badge bg-danger" style="font-size: 0.6rem;" title="يعمل 6 أيام أو أكثر دون راحة"><i class="fas fa-exclamation-triangle"></i> إرهاق (' + workDaysCount + ' أيام)</span>';
                    nameCell.appendChild(b);
                } else {
                    existingBadge.querySelector('.badge').innerHTML = '<i class="fas fa-exclamation-triangle"></i> إرهاق (' + workDaysCount + ' أيام)';
                }
            } else {
                if (existingBadge) {
                    existingBadge.remove();
                }
            }
        });
    }

    // Client-side instant filter by name or job
    function filterRosterRows() {
        var q = (document.getElementById('rosterSearchInput').value || '').trim().toLowerCase();
        var rows = document.querySelectorAll('tr.roster-emp-row');
        var visibleCount = 0;

        rows.forEach(row => {
            var name = row.getAttribute('data-emp-name') || '';
            var job = row.getAttribute('data-emp-job') || '';
            var type = row.getAttribute('data-schedule-type') || 'all';

            var matchesText = (q === '' || name.includes(q) || job.includes(q));
            var matchesType = true;
            if (window.currentScheduleTypeFilter && window.currentScheduleTypeFilter !== 'all') {
                matchesType = (type === window.currentScheduleTypeFilter);
            }

            if (matchesText && matchesType) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        var badge = document.getElementById('visibleStaffCount');
        if (badge) {
            badge.textContent = visibleCount;
        }

        recalculateCoverage();
    }

    window.currentScheduleTypeFilter = 'all';
    function setRosterScheduleFilter(type, btn) {
        document.querySelectorAll('.filter-pill-btn').forEach(b => b.classList.remove('active', 'btn-primary'));
        document.querySelectorAll('.filter-pill-btn').forEach(b => b.classList.add('btn-outline-secondary'));
        btn.classList.add('active', 'btn-primary');
        btn.classList.remove('btn-outline-secondary');
        
        window.currentScheduleTypeFilter = type;
        filterRosterRows();
    }

    function searchEmergencyCover() {
        var date = document.getElementById('emDate').value;
        var dept = document.getElementById('emDept').value;
        if (!date) return;
        
        document.getElementById('emList').innerHTML = '<li class="list-group-item text-center py-2"><i class="fas fa-spinner fa-spin"></i> جاري البحث...</li>';
        document.getElementById('emResults').classList.remove('d-none');

        fetch(`{{ route("hr.schedules.emergency-cover-search") }}?date=${date}&department_id=${dept}`)
            .then(res => res.json())
            .then(data => {
                var html = '';
                if (data.length === 0) {
                    html = '<li class="list-group-item text-danger text-center py-2">لا يوجد أي موظف في يوم راحة (Off).</li>';
                } else {
                    data.forEach(emp => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center py-1">
                                    <div><strong>${emp.name}</strong> <small class="text-muted">(${emp.job || '—'})</small></div>
                                    <span class="badge bg-success" style="font-size: 0.65rem;">متاح (Off)</span>
                                 </li>`;
                    });
                }
                document.getElementById('emList').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('emList').innerHTML = '<li class="list-group-item text-danger py-2">حدث خطأ في الاتصال.</li>';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        checkFatigue();
    });
</script>
@endsection
