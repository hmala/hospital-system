@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="orScheduleApp">
    {{-- CSS Styles --}}
    <style>
        :root {
            --or-teal-deep: #1b4958;
            --or-teal-header: #205869;
            --or-mint-soft: #eaf5f7;
            --or-card-bg: #ffffff;
        }

        .or-canvas-card {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 22px;
            box-shadow: 0 4px 20px -2px rgba(27, 73, 88, 0.07);
            border: 1px solid #e2e8f0;
        }

        /* Weekday navigation tabs */
        .week-nav-scroll {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 6px;
            scrollbar-width: thin;
        }
        .week-day-pill {
            flex: 1;
            min-width: 125px;
            background: #ffffff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 10px 14px;
            text-align: center;
            text-decoration: none;
            color: #475569;
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .week-day-pill:hover {
            transform: translateY(-2px);
            border-color: var(--or-teal-deep);
            color: var(--or-teal-deep);
            box-shadow: 0 4px 12px rgba(27, 73, 88, 0.08);
        }
        .week-day-pill.active {
            background: var(--or-teal-deep);
            border-color: var(--or-teal-deep);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(27, 73, 88, 0.25);
        }
        .week-day-pill.active .badge-count {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
        .week-day-pill .badge-count {
            background: #f1f5f9;
            color: #475569;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
            margin-top: 4px;
        }

        /* Table styling */
        .or-master-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }
        .or-master-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 12px 14px;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        .or-master-table tbody tr {
            transition: all 0.15s ease;
        }
        .or-master-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .or-master-table tbody td {
            padding: 12px 14px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .patient-avatar-circle {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 15px;
            color: #ffffff;
            background: linear-gradient(135deg, #1b4958, #2a738a);
        }

        .badge-stage {
            font-size: 0.8rem;
            padding: 4px 10px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .live-pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulse-ring 1.8s infinite;
        }
        @keyframes pulse-ring {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        .timer-badge-live {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 2px 6px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .inline-save-success {
            animation: highlight-green 1.5s ease-out;
        }
        @keyframes highlight-green {
            0% { background-color: #dcfce7; border-color: #10b981; }
            100% { background-color: transparent; }
        }
    </style>

    {{-- Main Container Card --}}
    <div class="or-canvas-card mb-4">
        {{-- Header Section: Title & Actions --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-procedures fs-5" style="color: var(--or-teal-deep);"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bolder text-dark">جدول وقائمة انتظار العمليات الجراحية</h4>
                        <div class="text-muted small mt-0.5">
                            متابعة وتحديث حالات العمليات، الصالات المخصصة، والمواعيد
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create surgeries')
                <a href="{{ route('surgeries.create') }}" class="btn rounded-pill px-3 py-1.5 fw-bold text-white shadow-sm" style="background: var(--or-teal-deep);">
                    <i class="fas fa-plus me-1"></i> حجز عملية جديدة
                </a>
                @endcan

                @can('manage surgery waiting list')
                <a href="{{ route('surgery-stations.tab-settings.index') }}" class="btn btn-outline-dark bg-white rounded-pill px-3 py-1.5 shadow-sm fw-semibold" title="تخصيص صلاحيات التبويبات السريرية لكل دور">
                    <i class="fas fa-sliders-h text-primary me-1"></i> صلاحيات التبويبات
                </a>
                @endcan

                <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary bg-white rounded-pill px-3 py-1.5 shadow-sm fw-semibold">
                    <i class="fas fa-list me-1"></i> الأرشيف العام
                </a>

                <button type="button" class="btn btn-light border rounded-pill px-3 py-1.5 shadow-sm text-dark fw-bold" onclick="location.reload();">
                    <i class="fas fa-rotate me-1 text-primary"></i> تحديث
                </button>
            </div>
        </div>

        {{-- Weekdays Tabs (تبويبات أيام الأسبوع في الأعلى) --}}
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold text-dark small">
                    <i class="far fa-calendar-alt text-primary me-1"></i> اختر اليوم من الأسبوع:
                </span>
                <span class="small text-muted">
                    تاريخ اليوم المعروض: <strong class="text-dark">{{ $parsedDate->format('Y-m-d') }}</strong>
                </span>
            </div>

            <div class="week-nav-scroll">
                @foreach($weekDays as $day)
                <a href="{{ route('surgeries.waiting', ['date' => $day['date']]) }}" class="week-day-pill {{ $day['is_selected'] ? 'active' : '' }}">
                    <div class="fw-bold" style="font-size: 0.95rem;">
                        {{ $day['day_name'] }}
                        @if($day['is_today'])
                            <span class="badge bg-warning text-dark rounded-circle p-1 ms-1" style="font-size: 0.6rem;" title="اليوم الحالي">●</span>
                        @endif
                    </div>
                    <div class="small opacity-75 font-monospace" style="font-size: 0.8rem;">
                        {{ $day['formatted_date'] }}
                    </div>
                    <span class="badge-count">
                        {{ $day['count'] }} {{ $day['count'] == 1 ? 'عملية' : ($day['count'] == 2 ? 'عمليتان' : 'عمليات') }}
                    </span>
                </a>
                @endforeach
            </div>
        </div>

        {{-- Search & Counter Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pt-2 border-top">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark border rounded-pill px-3 py-1.5 fw-bold">
                    إجمالي عمليات اليوم: <span class="text-primary">{{ $daySurgeries->count() }}</span>
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-2.5 py-1">
                    مجدولة: {{ $scheduledSurgeries->count() }}
                </span>
                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-pill px-2.5 py-1">
                    بالانتظار: {{ $activeSurgeries->whereIn('status', ['waiting', 'checked_in'])->count() }}
                </span>
                <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-pill px-2.5 py-1">
                    داخل الصالة: {{ $activeSurgeries->where('status', 'in_progress')->count() }}
                </span>
            </div>

            <div class="position-relative" style="min-width: 250px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="surgerySearchInput" class="form-control form-control-sm rounded-pill ps-5 pe-3 py-1.5 border" placeholder="بحث باسم المريض، الملف، الصالة، الطبيب...">
            </div>
        </div>

        {{-- Main Surgery Table --}}
        <div class="table-responsive">
            <table class="or-master-table">
                <thead>
                    <tr>
                        <th style="width: 70px;"># الرقم</th>
                        <th>المريض</th>
                        <th>العملية الجراحية</th>
                        <th>الطبيب الجراح</th>
                        <th style="min-width: 190px;">الصالة المخصصة (تحديد مباشر)</th>
                        <th style="min-width: 140px;">الوقت (تحديد مباشر)</th>
                        <th>حالة المسار</th>
                        <th class="text-center" style="width: 170px;">الإجراءات السريعة</th>
                    </tr>
                </thead>
                <tbody id="surgeriesTableBody">
                    @forelse($daySurgeries as $surgery)
                    @php
                        $patientName = $surgery->patient->user->name ?? 'مريض غير مسجل';
                        $fileNumber = $surgery->patient->file_number ?? '-';
                        $doctorName = $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد';
                        $theaterName = $surgery->operationTheater->name ?? null;
                        $rawTime = $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('H:i') : '';

                        $searchData = strtolower(
                            $patientName . ' ' .
                            $fileNumber . ' ' .
                            $surgery->surgery_type . ' ' .
                            $doctorName . ' ' .
                            ($theaterName ?? '')
                        );
                    @endphp
                    <tr class="surgery-row" data-search="{{ $searchData }}" id="surgeryRow-{{ $surgery->id }}">
                        {{-- ID --}}
                        <td>
                            <span class="fw-bold font-monospace text-dark">#{{ $surgery->id }}</span>
                        </td>

                        {{-- Patient --}}
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="patient-avatar-circle">
                                    {{ mb_substr($patientName, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $patientName }}</div>
                                    <div class="small text-muted font-monospace">
                                        ملف: {{ $fileNumber }}
                                        @if($surgery->patient && $surgery->patient->age)
                                            • {{ $surgery->patient->age }} سنة
                                        @endif
                                        @if($surgery->patient && $surgery->patient->gender)
                                            • {{ $surgery->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Surgery Type --}}
                        <td>
                            <div class="fw-bold text-dark" style="color: var(--or-teal-deep) !important;">
                                {{ $surgery->surgery_type }}
                            </div>
                            @if($surgery->additionalOperations && $surgery->additionalOperations->count() > 0)
                                <span class="badge bg-light text-primary border rounded-pill px-2 py-0.5 small mt-0.5">
                                    + {{ $surgery->additionalOperations->count() }} عمليات إضافية
                                </span>
                            @endif
                        </td>

                        {{-- Surgeon --}}
                        <td>
                            <div class="fw-semibold text-dark small">
                                <i class="fas fa-user-md text-primary me-1"></i> د. {{ $doctorName }}
                            </div>
                        </td>

                        {{-- Operation Theater (Inline Select + Quick Add) --}}
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <select class="form-select form-select-sm rounded-3 py-1 or-theater-select"
                                        data-surgery-id="{{ $surgery->id }}"
                                        onchange="inlineSaveTheater(this, {{ $surgery->id }})"
                                        style="font-size: 0.83rem;">
                                    <option value="">-- اختر الصالة --</option>
                                    @foreach($operationTheaters as $theater)
                                        <option value="{{ $theater->id }}" {{ $surgery->operation_theater_id == $theater->id ? 'selected' : '' }}>
                                            {{ $theater->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-primary p-1 rounded-2 shadow-none"
                                        title="إضافة صالة جديدة للقائمة"
                                        onclick="openNewTheaterModal({{ $surgery->id }})"
                                        style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-plus small"></i>
                                </button>
                            </div>
                            <div class="small text-success fw-bold mt-1 d-none save-msg" id="saveTheaterMsg-{{ $surgery->id }}" style="font-size: 0.72rem;">
                                <i class="fas fa-check-circle"></i> تم حفظ الصالة
                            </div>
                        </td>

                        {{-- Time (Inline Time Input) --}}
                        <td>
                            <div>
                                <input type="time" class="form-control form-control-sm font-monospace text-center rounded-3 py-1 or-time-input"
                                       value="{{ $rawTime }}"
                                       data-surgery-id="{{ $surgery->id }}"
                                       onchange="inlineSaveTime(this, {{ $surgery->id }})"
                                       style="font-size: 0.85rem;">
                            </div>
                            <div class="small text-success fw-bold mt-1 d-none save-msg" id="saveTimeMsg-{{ $surgery->id }}" style="font-size: 0.72rem;">
                                <i class="fas fa-check-circle"></i> تم حفظ الوقت
                            </div>

                            @if($surgery->status === 'in_progress')
                                <div class="mt-1">
                                    <span class="timer-badge-live" data-live-timer="true" data-start="{{ $surgery->started_at ? $surgery->started_at->toISOString() : ($surgery->scheduled_time ? $surgery->scheduled_time->toISOString() : '') }}">
                                        <i class="fas fa-stopwatch me-1 text-success"></i><span>00:00:00</span>
                                    </span>
                                </div>
                            @endif
                        </td>

                        {{-- Stage / Status --}}
                        <td>
                            @if($surgery->status === 'scheduled')
                                <span class="badge-stage bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                                    <i class="fas fa-calendar-alt"></i> مجدولة
                                </span>
                            @elseif($surgery->status === 'waiting' || $surgery->status === 'checked_in')
                                <span class="badge-stage bg-warning bg-opacity-10 text-warning border border-warning-subtle">
                                    <i class="fas fa-user-clock"></i> بالانتظار (تحضير)
                                </span>
                            @elseif($surgery->status === 'in_progress')
                                <span class="badge-stage bg-success text-white shadow-sm">
                                    <span class="live-pulse-dot"></span> داخل الصالة (جارية)
                                </span>
                            @elseif($surgery->status === 'completed')
                                <span class="badge-stage bg-secondary text-white">
                                    <i class="fas fa-check-circle"></i> منتهية / خرج
                                </span>
                            @else
                                <span class="badge bg-light text-muted">{{ $surgery->status }}</span>
                            @endif
                        </td>

                        {{-- Actions --}}
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-1">
                                <a href="{{ route('clinical-station.show', $surgery) }}" class="btn btn-sm btn-info text-white rounded-pill px-2.5 py-1 fw-bold shadow-sm" title="المحطة السريرية الموحدة (المقيم والتمريض)">
                                    <i class="fas fa-stethoscope"></i> المحطة السريرية
                                </a>

                                <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-light border rounded-pill px-2 py-1 text-dark" title="عرض ملف العملية">
                                    <i class="fas fa-file-medical text-primary"></i>
                                </a>

                                @if($surgery->status === 'scheduled')
                                    @can('manage surgery waiting list')
                                    <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-2.5 py-1 shadow-sm fw-bold">
                                            <i class="fas fa-sign-in-alt me-0.5"></i> دخول
                                        </button>
                                    </form>
                                    @endcan
                                @elseif($surgery->status === 'waiting' || $surgery->status === 'checked_in')
                                    @can('control surgeries')
                                    <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 shadow-sm fw-bold">
                                            <i class="fas fa-play me-0.5"></i> تحويل وبدء
                                        </button>
                                    </form>
                                    @endcan
                                @elseif($surgery->status === 'in_progress')
                                    @can('control surgeries')
                                    <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-danger rounded-pill px-2.5 py-1 shadow-sm fw-bold" onclick="return confirm('تأكيد إنهاء مرحلة صالة العمليات؟')">
                                            <i class="fas fa-stop me-0.5"></i> إنهاء
                                        </button>
                                    </form>
                                    @endcan
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-calendar-times fa-3x text-muted opacity-25 mb-2"></i>
                            <div class="fw-semibold">لا توجد عمليات مسجلة في هذا اليوم</div>
                            <div class="small text-muted mt-1">اختر يوماً آخر من شريط الأسبوع في الأعلى</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Small Modal ONLY for adding a new Operation Theater (فقط لإضافة صالة جديدة) --}}
<div class="modal fade" id="newTheaterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-1">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-1.5">
                    <i class="fas fa-door-closed text-primary"></i>
                    إضافة صالة جديدة
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1">
                <input type="hidden" id="activeSurgeryIdForNewTheater">
                <div class="mb-2">
                    <label class="form-label small text-muted mb-1">اسم الصالة</label>
                    <input type="text" id="newTheaterNameModalInput" class="form-control form-control-sm rounded-3" placeholder="مثلاً: صالة 4، صالة القسطرة...">
                    <div id="newTheaterModalError" class="text-danger small mt-1 d-none"></div>
                </div>
                <div class="d-flex justify-content-end gap-1.5 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" id="btnSaveTheaterFromModal">
                        <i class="fas fa-check me-1"></i> حفظ
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
let newTheaterModalInstance = null;

// Open New Theater Modal
function openNewTheaterModal(surgeryId) {
    document.getElementById('activeSurgeryIdForNewTheater').value = surgeryId || '';
    document.getElementById('newTheaterNameModalInput').value = '';
    document.getElementById('newTheaterModalError').classList.add('d-none');

    const modalEl = document.getElementById('newTheaterModal');
    if (!newTheaterModalInstance) {
        newTheaterModalInstance = new bootstrap.Modal(modalEl);
    }
    newTheaterModalInstance.show();
    setTimeout(() => {
        document.getElementById('newTheaterNameModalInput').focus();
    }, 300);
}

// Inline Save Theater directly from select
function inlineSaveTheater(selectEl, surgeryId) {
    const theaterId = selectEl.value;
    const msgEl = document.getElementById('saveTheaterMsg-' + surgeryId);

    selectEl.classList.remove('inline-save-success');

    fetch("{{ url('/surgeries') }}/" + surgeryId + "/quick-update-schedule", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            operation_theater_id: theaterId || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            selectEl.classList.add('inline-save-success');
            if (msgEl) {
                msgEl.classList.remove('d-none');
                setTimeout(() => msgEl.classList.add('d-none'), 2500);
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ الصالة');
        }
    })
    .catch(() => alert('تعذر الاتصال بالسيرفر'));
}

// Inline Save Time directly from input
function inlineSaveTime(inputEl, surgeryId) {
    const scheduledTime = inputEl.value;
    const msgEl = document.getElementById('saveTimeMsg-' + surgeryId);

    inputEl.classList.remove('inline-save-success');

    fetch("{{ url('/surgeries') }}/" + surgeryId + "/quick-update-schedule", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": "{{ csrf_token() }}",
            "Accept": "application/json"
        },
        body: JSON.stringify({
            scheduled_time: scheduledTime || null
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            inputEl.classList.add('inline-save-success');
            if (msgEl) {
                msgEl.classList.remove('d-none');
                setTimeout(() => msgEl.classList.add('d-none'), 2500);
            }
        } else {
            alert(data.message || 'حدث خطأ أثناء حفظ الوقت');
        }
    })
    .catch(() => alert('تعذر الاتصال بالسيرفر'));
}

document.addEventListener('DOMContentLoaded', function () {
    // Search Filter
    const searchInput = document.getElementById('surgerySearchInput');
    const rows = document.querySelectorAll('.surgery-row');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            rows.forEach(row => {
                const searchData = (row.getAttribute('data-search') || '').toLowerCase();
                if (!query || searchData.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Save New Theater from modal
    const btnSaveTheater = document.getElementById('btnSaveTheaterFromModal');
    if (btnSaveTheater) {
        btnSaveTheater.addEventListener('click', function () {
            const nameInput = document.getElementById('newTheaterNameModalInput');
            const errDiv = document.getElementById('newTheaterModalError');
            const name = nameInput.value.trim();
            const targetSurgeryId = document.getElementById('activeSurgeryIdForNewTheater').value;

            if (!name) {
                errDiv.textContent = 'يرجى كتابة اسم الصالة';
                errDiv.classList.remove('d-none');
                return;
            }

            btnSaveTheater.disabled = true;
            btnSaveTheater.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> حفظ...';

            fetch("{{ route('surgeries.theaters.quick-store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}",
                    "Accept": "application/json"
                },
                body: JSON.stringify({ name: name })
            })
            .then(res => res.json())
            .then(data => {
                btnSaveTheater.disabled = false;
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ';

                if (data.success && data.theater) {
                    // Append option to all theater selects on the page
                    document.querySelectorAll('.or-theater-select').forEach(select => {
                        const opt = document.createElement('option');
                        opt.value = data.theater.id;
                        opt.textContent = data.theater.name;
                        select.appendChild(opt);

                        // If this select belongs to the surgery where '+' was clicked, select it & save
                        if (targetSurgeryId && select.getAttribute('data-surgery-id') === targetSurgeryId) {
                            select.value = data.theater.id;
                            inlineSaveTheater(select, targetSurgeryId);
                        }
                    });

                    if (newTheaterModalInstance) newTheaterModalInstance.hide();
                } else {
                    errDiv.textContent = data.message || 'حدث خطأ أثناء إضافة الصالة';
                    errDiv.classList.remove('d-none');
                }
            })
            .catch(() => {
                btnSaveTheater.disabled = false;
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ';
                errDiv.textContent = 'تعذر الاتصال بالسيرفر';
                errDiv.classList.remove('d-none');
            });
        });
    }

    // Realtime Stopwatch for in-progress operations
    const liveTimerEls = document.querySelectorAll('[data-live-timer="true"]');
    function updateTimers() {
        const now = new Date();
        liveTimerEls.forEach(el => {
            const startAttr = el.getAttribute('data-start');
            if (!startAttr) return;
            const startTime = new Date(startAttr);
            if (isNaN(startTime.getTime())) return;

            let diffMs = now - startTime;
            if (diffMs < 0) diffMs = 0;

            const totalSec = Math.floor(diffMs / 1000);
            const hrs = String(Math.floor(totalSec / 3600)).padStart(2, '0');
            const mins = String(Math.floor((totalSec % 3600) / 60)).padStart(2, '0');
            const secs = String(totalSec % 60).padStart(2, '0');

            const spanText = el.querySelector('span');
            if (spanText) {
                spanText.textContent = `${hrs}:${mins}:${secs}`;
            }
        });
    }
    updateTimers();
    setInterval(updateTimers, 1000);
});
</script>
@endsection
