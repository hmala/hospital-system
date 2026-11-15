@extends('layouts.app')
@section('title', 'جدول الدوام والخفارات - HR')

@section('content')
<div class="container-fluid schedule-roster-container">
    <!-- Header Section -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2 no-print">
        <div>
            <h4 class="fw-bold text-dark mb-1">
                <i class="fas fa-calendar-alt text-primary me-2"></i>جدول الدوام والخفارات (Hospital Roster)
            </h4>
            <p class="text-muted small mb-0">جدولة وتوزيع الشفتات والخفارات الأسبوعية والشهرية مع كشف التغطية الذكي</p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Navigation buttons -->
            <div class="btn-group shadow-sm">
                <a href="{{ route('hr.schedules.index', ['start_date' => $prevDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-secondary btn-sm" title="الفترة السابقة">
                    <i class="fas fa-chevron-right me-1"></i> السابق
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $currentPeriodDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-primary btn-sm fw-bold" title="الانتقال إلى اليوم / الفترة الحالية">
                    <i class="fas fa-dot-circle text-danger me-1"></i> اليوم / الحالي
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $nextDate, 'view_mode' => $viewMode, 'department_id' => $departmentId]) }}" class="btn btn-outline-secondary btn-sm" title="الفترة التالية">
                    التالي <i class="fas fa-chevron-left ms-1"></i>
                </a>
            </div>

            <!-- View Mode Switcher -->
            <div class="btn-group shadow-sm">
                <a href="{{ route('hr.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'view_mode' => 'week', 'department_id' => $departmentId]) }}" 
                   class="btn btn-sm {{ $viewMode === 'week' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                    <i class="fas fa-calendar-week me-1"></i> أسبوعي (7 أيام)
                </a>
                <a href="{{ route('hr.schedules.index', ['start_date' => $startDate->format('Y-m-d'), 'view_mode' => 'month', 'department_id' => $departmentId]) }}" 
                   class="btn btn-sm {{ $viewMode === 'month' ? 'btn-primary fw-bold' : 'btn-outline-primary' }}">
                    <i class="fas fa-calendar-alt me-1"></i> شهري كامل
                </a>
            </div>

            <!-- Print Button -->
            <button type="button" class="btn btn-sm btn-outline-dark fw-bold shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> طباعة الجدول الرسمي
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 no-print" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 no-print" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Control & Filter Toolbar -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 no-print">
        <div class="card-body p-3">
            <div class="row g-3 align-items-center">
                <!-- Department Filter Form -->
                <div class="col-xl-4 col-lg-5">
                    <form action="{{ route('hr.schedules.index') }}" method="GET" class="row g-2 align-items-center" id="deptFilterForm">
                        <input type="hidden" name="view_mode" value="{{ $viewMode }}">
                        <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                        <div class="col-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light"><i class="fas fa-hospital-user text-muted"></i></span>
                                <select name="department_id" class="form-select form-select-sm" onchange="document.getElementById('deptFilterForm').submit()">
                                    <option value="">جميع الأقسام والعيادات</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-4">
                            <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate->format('Y-m-d') }}" onchange="document.getElementById('deptFilterForm').submit()">
                        </div>
                    </form>
                </div>

                <!-- Live Client Search & Quick Filter Pills -->
                <div class="col-xl-5 col-lg-7">
                    <div class="d-flex gap-2 align-items-center">
                        <div class="input-group input-group-sm flex-grow-1">
                            <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" id="rosterSearchInput" class="form-control form-control-sm border-start-0" placeholder="بحث فوري بالاسم أو الوظيفة..." onkeyup="filterRosterRows()">
                        </div>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary active filter-pill-btn" onclick="setRosterScheduleFilter('all', this)">الكل</button>
                            <button type="button" class="btn btn-outline-secondary filter-pill-btn" onclick="setRosterScheduleFilter('fixed', this)">ثابت</button>
                            <button type="button" class="btn btn-outline-secondary filter-pill-btn" onclick="setRosterScheduleFilter('rotating', this)">خفارات</button>
                            <button type="button" class="btn btn-outline-secondary filter-pill-btn" onclick="setRosterScheduleFilter('split', this)">وجبتين</button>
                        </div>
                    </div>
                </div>

                <!-- Emergency & Swap Triggers -->
                <div class="col-xl-3 col-lg-12 d-flex justify-content-xl-end gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#swapModal">
                        <i class="fas fa-exchange-alt me-1 text-warning"></i> تبادل خفارات
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                        <i class="fas fa-ambulance me-1"></i> بديل طوارئ
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stamp Tool (أداة الختم والتلوين السريع) -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-light no-print border-start border-4 border-primary">
        <div class="card-body p-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-2 py-1"><i class="fas fa-stamp me-1"></i> أداة الختم السريع</span>
                <span class="small text-muted d-none d-md-inline">اختر شفت ثم انقر على أي خلية لتعيينه وتلوينه وحفظه فوراً:</span>
            </div>
            <div class="d-flex flex-wrap gap-1 align-items-center" id="stampPalette">
                <button type="button" class="btn btn-sm btn-outline-dark stamp-btn active" data-val="" data-color="" onclick="selectStamp(this)">
                    <i class="fas fa-eraser me-1"></i> مسح (فراغ)
                </button>
                <button type="button" class="btn btn-sm btn-secondary stamp-btn" data-val="off" data-color="#6c757d" onclick="selectStamp(this)">
                    <i class="fas fa-coffee me-1"></i> راحة (Off)
                </button>
                @foreach($shifts as $shift)
                    <button type="button" class="btn btn-sm text-white stamp-btn" 
                            style="background-color: {{ $shift->color_code }}; border-color: {{ $shift->color_code }}; opacity: 0.9;" 
                            data-val="{{ $shift->id }}" 
                            data-color="{{ $shift->color_code }}" 
                            onclick="selectStamp(this)">
                        <i class="fas fa-clock me-1"></i> {{ $shift->name }}
                        <small class="opacity-75 ms-1">({{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }})</small>
                    </button>
                @endforeach
                <div class="vr mx-2"></div>
                <div class="form-check form-switch m-0 pt-1">
                    <input class="form-check-input" type="checkbox" id="ajaxAutoSaveToggle" checked>
                    <label class="form-check-label small fw-bold text-dark" for="ajaxAutoSaveToggle">حفظ فوري تلقائي (AJAX)</label>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Hospital Print Header (Visible Only on Print) -->
    <div class="d-none d-print-block print-header mb-4 text-center border-bottom pb-3">
        <div class="d-flex justify-content-between align-items-center">
            <div class="text-start">
                <h6 class="fw-bold mb-0">جمهورية العراق</h6>
                <p class="small mb-0">وزارة الصحة / دائرة صحة بغداد</p>
                <p class="small fw-bold mb-0">إدارة المستشفى - قسم الموارد البشرية</p>
            </div>
            <div class="text-center">
                <h4 class="fw-bold mb-1">جدول توزيع الدوام والخفارات الرسمية (Roster)</h4>
                <p class="mb-0 fw-bold">الفترة: من {{ $startDate->format('Y-m-d') }} إلى {{ $endDate->format('Y-m-d') }}</p>
                <p class="small text-muted mb-0">القسم: {{ $departments->firstWhere('id', $departmentId)->name ?? 'كافة الأقسام' }}</p>
            </div>
            <div class="text-end">
                <p class="small mb-0">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
                <p class="small mb-0">رمز الوثيقة: HR-ROST-{{ $startDate->format('Ymd') }}</p>
            </div>
        </div>
    </div>

    <!-- Main Grid Card -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 no-print">
            <div class="d-flex align-items-center gap-2">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="fas fa-calendar-check text-success me-1"></i>
                    الجدول للفترة: <span class="badge bg-light text-dark border">{{ $startDate->format('Y-m-d') }}</span> إلى <span class="badge bg-light text-dark border">{{ $endDate->format('Y-m-d') }}</span>
                </h6>
                <span class="badge bg-primary bg-opacity-10 text-primary fw-normal" id="visibleEmpCountBadge">
                    عدد الكوادر: {{ $employees->count() }} موظف
                </span>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <!-- Auto-Fill Defaults Button -->
                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#autoFillModal">
                    <i class="fas fa-magic me-1"></i> تعبئة الدوام الافتراضي تلقائياً
                </button>
                <!-- Copy Last Week Button -->
                <form action="{{ route('hr.schedules.copy-last-week') }}" method="POST" class="d-inline-block">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                    <button type="submit" class="btn btn-sm btn-outline-info fw-bold" onclick="return confirm('هل أنت متأكد من جلب جدول الأسبوع الماضي؟ سيتم استبدال جدول هذا الأسبوع بالبيانات المستنسخة.')">
                        <i class="fas fa-copy me-1"></i> نسخ الأسبوع الماضي
                    </button>
                </form>
                <!-- Clear Range Button -->
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearRangeModal">
                    <i class="fas fa-trash-alt me-1"></i> تفريغ
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('rosterForm').reset(); reapplyAllColors();">
                    <i class="fas fa-undo me-1"></i> تراجع
                </button>
                <button type="submit" form="rosterForm" class="btn btn-sm btn-success fw-bold px-3 shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ كافة التعديلات
                </button>
            </div>
        </div>

        <div class="table-responsive roster-table-wrapper" style="max-height: 70vh;">
            <form action="{{ route('hr.schedules.store') }}" method="POST" id="rosterForm">
                @csrf
                <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                
                <table class="table table-bordered table-hover align-middle mb-0 text-center roster-table" style="min-width: {{ count($dates) * 115 + 240 }}px;" id="rosterMainTable">
                    <thead class="bg-light sticky-top shadow-sm" style="z-index: 10;">
                        <tr>
                            <th class="align-middle text-start roster-emp-th" style="width: 240px; min-width: 240px; position: sticky; right: 0; background-color: #f8f9fa; z-index: 11;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">الموظف / القسم</span>
                                    <span class="badge bg-secondary text-white small" style="font-size: 0.65rem;">النوع</span>
                                </div>
                            </th>
                            @foreach($dates as $date)
                                @php
                                    $isToday = $date->isToday();
                                    $isFriday = $date->isFriday();
                                @endphp
                                <th class="align-middle roster-date-th {{ $isToday ? 'bg-primary bg-opacity-10 text-primary border-primary' : '' }} {{ $isFriday ? 'bg-secondary bg-opacity-10' : '' }}" style="width: 115px; min-width: 115px;">
                                    <div class="fw-bold {{ $isToday ? 'text-primary' : '' }}">
                                        {{ $date->locale('ar')->translatedFormat('l') }}
                                        @if($isToday)
                                            <span class="badge bg-danger ms-1" style="font-size: 0.6rem;">اليوم</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">{{ $date->format('m/d') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($employees as $emp)
                            @php
                                $empSchedules = $existingSchedules->get($emp->id, collect());
                                $scheduleType = $emp->schedule_type ?? 'rotating';
                                if ($emp->is_split_shift) {
                                    $filterType = 'split';
                                } elseif ($scheduleType === 'fixed') {
                                    $filterType = 'fixed';
                                } else {
                                    $filterType = 'rotating';
                                }
                            @endphp
                            <tr class="roster-emp-row" data-schedule-type="{{ $filterType }}" data-emp-name="{{ strtolower($emp->full_name) }}" data-emp-job="{{ strtolower($emp->job_title) }}" data-emp-id="{{ $emp->id }}">
                                <td class="text-start roster-emp-cell" style="position: sticky; right: 0; background-color: white; z-index: 5;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="fw-bold text-dark emp-name-text" style="font-size: 0.88rem;">{{ $emp->full_name }}</span>
                                        <div class="d-flex gap-1 align-items-center">
                                            @if($emp->is_split_shift)
                                                <span class="badge bg-warning text-dark" style="font-size: 0.62rem;" title="دوام وجبتين">وجبتين</span>
                                            @elseif($scheduleType === 'fixed')
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary" style="font-size: 0.62rem;" title="دوام ثابت">ثابت</span>
                                            @else
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info" style="font-size: 0.62rem;" title="خفارات متناوبة">متناوب</span>
                                            @endif
                                            <!-- Row Quick Fill Dropdown -->
                                            <div class="dropdown no-print">
                                                <button class="btn btn-sm btn-light border-0 py-0 px-1 text-muted" type="button" data-bs-toggle="dropdown" title="تعبئة سريعة لأسبوع هذا الموظف">
                                                    <i class="fas fa-ellipsis-v" style="font-size: 0.75rem;"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-sm small">
                                                    <li><h6 class="dropdown-header py-1 small">تعبئة سريعة لـ {{ Str::words($emp->full_name, 2, '') }}</h6></li>
                                                    @if($emp->defaultShift)
                                                        <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '{{ $emp->default_shift_id }}', '{{ $emp->defaultShift->color_code }}')"><i class="fas fa-magic text-primary me-2"></i>تطبيق الشفت الدائم ({{ $emp->defaultShift->name }})</a></li>
                                                    @endif
                                                    @foreach($shifts as $sh)
                                                        <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '{{ $sh->id }}', '{{ $sh->color_code }}')"><i class="fas fa-clock me-2" style="color: {{ $sh->color_code }}"></i>تعبئة الكل بـ {{ $sh->name }}</a></li>
                                                    @endforeach
                                                    <li><a class="dropdown-item py-1" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', 'off', '#6c757d')"><i class="fas fa-coffee text-secondary me-2"></i>تعبئة الكل راحة (Off)</a></li>
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li><a class="dropdown-item py-1 text-danger" href="javascript:void(0)" onclick="fillEmployeeRow('{{ $emp->id }}', '', '')"><i class="fas fa-eraser me-2"></i>مسح أسبوع الموظف</a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="small text-muted emp-job-text" style="font-size: 0.73rem;">
                                        {{ $emp->department->name ?? '—' }} | {{ $emp->job_title }}
                                        @if($emp->defaultShift)
                                            <span class="text-secondary ms-1">({{ $emp->defaultShift->name }})</span>
                                        @endif
                                    </div>
                                </td>
                                @foreach($dates as $date)
                                    @php
                                        $dateStr = $date->format('Y-m-d');
                                        $daySchedule = $empSchedules->first(function($item) use ($dateStr) { 
                                            return $item->shift_date->format('Y-m-d') === $dateStr; 
                                        });
                                        $currentVal = '';
                                        $currentColor = '';
                                        if ($daySchedule) {
                                            if ($daySchedule->is_off_day) {
                                                $currentVal = 'off';
                                                $currentColor = '#6c757d';
                                            } else if ($daySchedule->hr_shift_id) {
                                                $currentVal = $daySchedule->hr_shift_id;
                                                $currentColor = $daySchedule->shift->color_code ?? '';
                                            }
                                        } else if ($emp->schedule_type === 'fixed') {
                                            if ($emp->isOffOn($date)) {
                                                $currentVal = 'off';
                                                $currentColor = '#6c757d';
                                            } elseif ($emp->default_shift_id) {
                                                $currentVal = $emp->default_shift_id;
                                                $currentColor = $emp->defaultShift->color_code ?? '#0d6efd';
                                            }
                                        }
                                    @endphp
                                    <td class="p-1 roster-cell position-relative" data-date="{{ $dateStr }}" data-emp-id="{{ $emp->id }}" onclick="handleCellStampClick(this, event)">
                                        <select name="schedules[{{ $emp->id }}][{{ $dateStr }}]" 
                                                class="form-select roster-select text-center" 
                                                data-emp-id="{{ $emp->id }}"
                                                data-date="{{ $dateStr }}"
                                                style="color: {{ $currentColor ? '#fff' : 'inherit' }}; background-color: {{ $currentColor ? $currentColor : 'transparent' }}; border-color: {{ $currentColor ? $currentColor : '#dee2e6' }};"
                                                onchange="updateSelectColor(this, true)">
                                            <option value="" style="color: black; background: white;">— فراغ —</option>
                                            <option value="off" data-color="#6c757d" style="color: white; background: #6c757d;" {{ $currentVal === 'off' ? 'selected' : '' }}>يوم راحة (Off)</option>
                                            @foreach($shifts as $shift)
                                                <option value="{{ $shift->id }}" data-color="{{ $shift->color_code }}" style="color: white; background: {{ $shift->color_code }};" {{ $currentVal == $shift->id ? 'selected' : '' }}>
                                                    {{ $shift->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <!-- Print-only text -->
                                        <div class="d-none d-print-block print-cell-label small fw-bold">
                                            @if($currentVal === 'off')
                                                <span class="text-secondary">Off</span>
                                            @elseif($currentVal)
                                                @php $sObj = $shifts->firstWhere('id', $currentVal); @endphp
                                                <span>{{ $sObj ? $sObj->name : $currentVal }}</span>
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
                    <!-- Shift Coverage Footer Row (كشف التغطية والجاهزية اليومية) -->
                    <tfoot class="bg-light sticky-bottom shadow-sm border-top-2" style="z-index: 9;">
                        <tr class="fw-bold coverage-row">
                            <td class="text-start roster-emp-cell bg-light" style="position: sticky; right: 0; z-index: 6;">
                                <div class="fw-bold text-dark"><i class="fas fa-chart-pie text-primary me-1"></i> إجمالي التغطية اليومية</div>
                                <div class="small text-muted" style="font-size: 0.7rem;">حساب تلقائي لعدد الكوادر المداومة</div>
                            </td>
                            @foreach($dates as $date)
                                @php
                                    $dStr = $date->format('Y-m-d');
                                    $dayCov = $dailyCoverage[$dStr] ?? ['shifts' => [], 'off' => 0, 'empty' => 0, 'total' => 0];
                                @endphp
                                <td class="p-1 coverage-cell text-start small" data-date="{{ $dStr }}">
                                    <div class="d-flex flex-column gap-1 coverage-badges" style="font-size: 0.72rem;">
                                        @foreach($shifts as $sh)
                                            @php $cnt = $dayCov['shifts'][$sh->id] ?? 0; @endphp
                                            <div class="d-flex justify-content-between align-items-center px-1 rounded" style="background-color: {{ $sh->color_code }}15; border-right: 3px solid {{ $sh->color_code }};">
                                                <span style="color: {{ $sh->color_code }}; font-weight: 600;">{{ Str::limit($sh->name, 7, '') }}:</span>
                                                <span class="badge rounded-pill text-white cov-count-badge" data-shift-id="{{ $sh->id }}" style="background-color: {{ $sh->color_code }}; font-size: 0.65rem;">{{ $cnt }}</span>
                                            </div>
                                        @endforeach
                                        <div class="d-flex justify-content-between align-items-center px-1 bg-secondary bg-opacity-10 rounded border-end border-3 border-secondary">
                                            <span class="text-secondary fw-bold">راحة (Off):</span>
                                            <span class="badge rounded-pill bg-secondary cov-off-badge" style="font-size: 0.65rem;">{{ $dayCov['off'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    </tfoot>
                </table>
            </form>
        </div>
    </div>

    <!-- Official Print Sign-off (Visible only on print) -->
    <div class="d-none d-print-block print-signatures mt-5 pt-4 border-top">
        <div class="row text-center">
            <div class="col-4">
                <p class="fw-bold mb-4">مسؤول القسم / الشعبة</p>
                <p class="text-muted small">التوقيع: .......................................</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-4">مدقق جدول الموارد البشرية</p>
                <p class="text-muted small">التوقيع: .......................................</p>
            </div>
            <div class="col-4">
                <p class="fw-bold mb-4">اعتماد مدير المستشفى / المعاون</p>
                <p class="text-muted small">الختم والتوقيع: .......................................</p>
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
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-magic me-2"></i>تعبئة الدوام الافتراضي تلقائياً</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        يقوم هذا الأمر بقراءة إعدادات كل موظف (الشفت الدائم، وأيام الاستراحة الأسبوعية المسجلة في إضبارته) وتطبيقها آلياً على كامل أيام الفترة المعروضة بضغطة زر واحدة.
                    </p>
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">

                    <div class="alert alert-info border-0 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> الفترة المحددة: من <strong>{{ $startDate->format('Y-m-d') }}</strong> إلى <strong>{{ $endDate->format('Y-m-d') }}</strong>
                        <br>
                        القسم: <strong>{{ $departments->firstWhere('id', $departmentId)->name ?? 'كافة الأقسام' }}</strong>
                    </div>

                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="overwrite" value="1" id="overwriteCheck">
                        <label class="form-check-label fw-bold small text-dark" for="overwriteCheck">
                            استبدال الشفتات المجدولة مسبقاً (إعادة بناء وتحديث كامل للفترة)
                        </label>
                    </div>
                    <small class="text-muted d-block">إذا لم تفعل هذا الخيار، سيتم تعبئة الأيام الفارغة فقط مع الحفاظ على أي شفت قمت بتحديده يدوياً.</small>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold px-4"><i class="fas fa-check me-1"></i> تطبيق الدوام الافتراضي</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear Range Modal -->
<div class="modal fade" id="clearRangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('hr.schedules.clear-range') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-trash-alt me-2"></i>تفريغ وإعادة تعيين جدول الفترة</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-danger fw-bold mb-2">تحذير: هل أنت متأكد من تفريغ كافة شفتات الفترة المحددة؟</p>
                    <p class="text-muted small mb-0">سيتم حذف جميع الشفتات المسجلة من <strong>{{ $startDate->format('Y-m-d') }}</strong> إلى <strong>{{ $endDate->format('Y-m-d') }}</strong> للقسم المحدد، لتتمكن من إعادة بنائها من الصفر.</p>
                    <input type="hidden" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
                    <input type="hidden" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
                    <input type="hidden" name="department_id" value="{{ $departmentId }}">
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4">نعم، تفريغ الجدول</button>
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
                <div class="modal-header bg-warning">
                    <h5 class="modal-title fw-bold text-dark"><i class="fas fa-exchange-alt me-2"></i>تبادل شفتات / خفارات</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">اختر التاريخ واسم الموظفين ليتم تبديل شفتاتهم في ذلك اليوم فوراً.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">تاريخ التبادل <span class="text-danger">*</span></label>
                        <input type="date" name="swap_date" class="form-control" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">الموظف الأول (سيتنازل عن شفته) <span class="text-danger">*</span></label>
                        <select name="employee1_id" class="form-select" required>
                            <option value="">اختر الموظف...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->job_title }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="text-center my-2 text-muted"><i class="fas fa-arrow-up"></i> تبادل <i class="fas fa-arrow-down"></i></div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">الموظف الثاني (البديل) <span class="text-danger">*</span></label>
                        <select name="employee2_id" class="form-select" required>
                            <option value="">اختر الموظف...</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->job_title }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning fw-bold px-4">تأكيد التبادل</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Emergency Cover Modal -->
<div class="modal fade" id="emergencyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-ambulance me-2"></i>البحث عن بديل للطوارئ</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">اختر اليوم والقسم ليعرض لك النظام الموظفين الذين في "يوم راحة (Off)" ومتاحين لتغطية النقص.</p>
                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label fw-bold">تاريخ النقص</label>
                        <input type="date" id="emDate" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-bold">القسم</label>
                        <select id="emDept" class="form-select">
                            <option value="">أي قسم</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <button type="button" class="btn btn-danger w-100 mb-4 fw-bold" onclick="searchEmergencyCover()">
                    <i class="fas fa-search me-1"></i> بحث عن المتاحين للعمل
                </button>

                <div id="emResults" class="d-none">
                    <h6 class="fw-bold mb-2 text-success">المتاحون في هذا اليوم (Off أو غير مجدولين):</h6>
                    <ul class="list-group" id="emList">
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
            <div class="toast-body" id="rosterToastBody">
                <i class="fas fa-check-circle text-success me-1"></i> تم الحفظ التلقائي بنجاح.
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- ================= STYLES ================= -->
<style>
    .roster-select {
        font-size: 0.8rem;
        padding: 3px 4px;
        height: 30px;
        border-radius: 4px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .roster-cell:hover {
        background-color: #f0f7ff;
    }
    .stamp-btn {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .stamp-btn.active {
        box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.4) !important;
        transform: scale(1.04);
        font-weight: bold;
    }
    .stamp-active-cell {
        cursor: crosshair !important;
    }

    /* Print Styles */
    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            background: white !important;
            font-size: 11px !important;
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
            font-size: 10px !important;
        }
        .roster-select {
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
    // Global active stamp
    var activeStampVal = null;
    var activeStampColor = '';

    function selectStamp(btn) {
        document.querySelectorAll('.stamp-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeStampVal = btn.getAttribute('data-val');
        activeStampColor = btn.getAttribute('data-color') || '';
    }

    function handleCellStampClick(cell, event) {
        // If the user clicked directly on the select element, let normal select work
        if (event.target.tagName.toLowerCase() === 'select') {
            return;
        }
        
        // If active stamp is set (or empty to clear), apply it to the select in this cell
        var select = cell.querySelector('select');
        if (!select) return;

        if (activeStampVal !== null) {
            select.value = activeStampVal;
            updateSelectColor(select, true);
        }
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
        var val = selectObj.value;

        fetch('{{ route("hr.schedules.update-cell") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                hr_employee_id: empId,
                shift_date: date,
                shift_value: val
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
            var toast = new bootstrap.Toast(toastEl, { delay: 1800 });
            toast.show();
        }
    }

    function fillEmployeeRow(empId, shiftVal, shiftColor) {
        var row = document.querySelector('tr[data-emp-id="' + empId + '"]');
        if (!row) return;

        var selects = row.querySelectorAll('select.roster-select');
        selects.forEach(sel => {
            sel.value = shiftVal;
            updateSelectColor(sel, false);
        });

        // If AJAX autosave is on, submit full form or trigger save
        showRosterToast('تم تعبئة أسبوع الموظف. اضغط "حفظ كافة التعديلات" للتثبيت.');
    }

    function reapplyAllColors() {
        document.querySelectorAll('select.roster-select').forEach(sel => {
            var selectedOption = sel.options[sel.selectedIndex];
            var color = selectedOption.getAttribute('data-color');
            if (color) {
                sel.style.backgroundColor = color;
                sel.style.color = 'white';
                sel.style.borderColor = color;
            } else {
                sel.style.backgroundColor = 'transparent';
                sel.style.color = 'inherit';
                sel.style.borderColor = '#dee2e6';
            }
        });
        recalculateCoverage();
        checkFatigue();
    }

    function recalculateCoverage() {
        var table = document.getElementById('rosterMainTable');
        if (!table) return;

        var dateCells = table.querySelectorAll('thead th.roster-date-th');
        var dates = [];
        dateCells.forEach(th => {
            // Find corresponding date from body cells
        });

        var covCells = table.querySelectorAll('tfoot td.coverage-cell');
        covCells.forEach(covTd => {
            var dateStr = covTd.getAttribute('data-date');
            if (!dateStr) return;

            // Count for this date
            var selects = table.querySelectorAll('tbody select.roster-select[data-date="' + dateStr + '"]');
            var counts = { off: 0 };
            selects.forEach(sel => {
                var row = sel.closest('tr');
                if (row && row.style.display === 'none') return; // ignore hidden filtered rows

                var val = sel.value;
                if (val === 'off') {
                    counts.off = (counts.off || 0) + 1;
                } else if (val) {
                    counts[val] = (counts[val] || 0) + 1;
                }
            });

            // Update badges in this covTd
            var offBadge = covTd.querySelector('.cov-off-badge');
            if (offBadge) offBadge.textContent = counts.off || 0;

            covTd.querySelectorAll('.cov-count-badge').forEach(badge => {
                var shId = badge.getAttribute('data-shift-id');
                badge.textContent = counts[shId] || 0;
            });
        });
    }

    function checkFatigue() {
        var rows = document.querySelectorAll('tr.roster-emp-row');
        rows.forEach(row => {
            var selects = row.querySelectorAll('select.roster-select');
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
                    b.innerHTML = '<span class="badge bg-danger" style="font-size: 0.62rem;" title="تنبيه إرهاق: يعمل الموظف 6 أيام متواصلة أو أكثر دون راحة"><i class="fas fa-exclamation-triangle"></i> تنبيه إرهاق (' + workDaysCount + ' أيام)</span>';
                    nameCell.appendChild(b);
                } else {
                    existingBadge.querySelector('.badge').innerHTML = '<i class="fas fa-exclamation-triangle"></i> تنبيه إرهاق (' + workDaysCount + ' أيام)';
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
        var activeFilterPill = document.querySelector('.filter-pill-btn.active').textContent.trim();
        
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

        var badge = document.getElementById('visibleEmpCountBadge');
        if (badge) {
            badge.textContent = 'يظهر: ' + visibleCount + ' من أصل ' + rows.length + ' موظف';
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

    // Emergency Cover Search via AJAX
    function searchEmergencyCover() {
        var date = document.getElementById('emDate').value;
        var dept = document.getElementById('emDept').value;
        if (!date) return;
        
        document.getElementById('emList').innerHTML = '<li class="list-group-item text-center"><i class="fas fa-spinner fa-spin"></i> جاري البحث عن الكوادر المتاحة...</li>';
        document.getElementById('emResults').classList.remove('d-none');

        fetch(`{{ route("hr.schedules.emergency-cover-search") }}?date=${date}&department_id=${dept}`)
            .then(res => res.json())
            .then(data => {
                var html = '';
                if (data.length === 0) {
                    html = '<li class="list-group-item text-danger text-center">لا يوجد أي موظف في يوم راحة (Off) في هذا اليوم.</li>';
                } else {
                    data.forEach(emp => {
                        html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div><strong>${emp.name}</strong> <br><small class="text-muted">${emp.job || '—'}</small></div>
                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> متاح للعمل (Off)</span>
                                 </li>`;
                    });
                }
                document.getElementById('emList').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('emList').innerHTML = '<li class="list-group-item text-danger">حدث خطأ في الاتصال.</li>';
            });
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        checkFatigue();
    });
</script>
@endsection
