@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="orScheduleApp">
    {{-- Ultra-Modern Hospital EHR Flight Board Styles --}}
    <style>
        :root {
            --or-navy: #0f172a;
            --or-slate-dark: #1e293b;
            --or-primary: #0284c7;
            --or-primary-dark: #0369a1;
            --or-primary-light: #f0f9ff;
            --or-emerald: #10b981;
            --or-amber: #f59e0b;
            --or-rose: #f43f5e;
            --or-border: #e2e8f0;
            --or-card-bg: #ffffff;
        }

        /* Canvas Card Container */
        .or-canvas-card {
            background-color: #ffffff;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.04), 0 4px 6px -2px rgba(15, 23, 42, 0.02);
            border: 1px solid var(--or-border);
        }

        /* Top Executive Header */
        .or-header-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 22px;
        }
        .or-header-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 20px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            flex-shrink: 0;
        }

        /* Modern Table Styling */
        .or-table-shell {
            border: 1px solid var(--or-border);
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
        }
        .or-master-table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 0;
        }
        .or-master-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: 0.8rem;
            letter-spacing: 0.3px;
            padding: 14px 16px;
            border-bottom: 2px solid var(--or-border);
            white-space: nowrap;
        }
        .or-master-table tbody tr {
            transition: all 0.15s ease;
            border-bottom: 1px solid #f1f5f9;
        }
        .or-master-table tbody tr:last-child {
            border-bottom: none;
        }
        .or-master-table tbody tr:hover {
            background-color: #f8fafc;
        }
        .or-master-table tbody td {
            padding: 12px 16px;
            vertical-align: middle;
        }

        /* Patient Avatar */
        .patient-avatar-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 15px;
            color: #ffffff;
            background: linear-gradient(135deg, #0284c7 0%, #1e40af 100%);
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.2);
            flex-shrink: 0;
        }

        /* Status & Timer Badges */
        .badge-stage-pill {
            font-size: 0.78rem;
            padding: 5px 12px;
            border-radius: 30px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
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

        /* Stopwatch Live Capsule */
        .live-stopwatch-capsule {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 3px 8px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Success save highlight */
        .inline-save-success {
            animation: highlight-green 1.5s ease-out;
        }
        @keyframes highlight-green {
            0% { background-color: #dcfce7; border-color: #10b981; }
            100% { background-color: transparent; }
        }

        /* Modern Form Controls Inside Table */
        .or-inline-select {
            border: 1.5px solid var(--or-border);
            border-radius: 10px;
            font-size: 0.82rem;
            font-weight: 600;
            color: #1e293b;
            background-color: #f8fafc;
            padding: 6px 10px;
            transition: all 0.2s;
        }
        .or-inline-select:focus {
            background-color: #ffffff;
            border-color: var(--or-primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }
        .or-inline-time {
            border: 1.5px solid var(--or-border);
            border-radius: 10px;
            font-size: 0.83rem;
            font-weight: 700;
            color: #0f172a;
            background-color: #f8fafc;
            padding: 5px 8px;
            text-align: center;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, monospace;
            transition: all 0.2s;
        }
        .or-inline-time:focus {
            background-color: #ffffff;
            border-color: var(--or-primary);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.12);
        }

        /* Quick action buttons */
        .btn-action-stage {
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 6px 14px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s;
        }
        .btn-action-stage:hover {
            transform: translateY(-1px);
        }

        /* Room badges */
        .room-badge-pill {
            padding: 3px 9px;
            border-radius: 8px;
            font-weight: 800;
            font-size: 0.78rem;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .room-badge-regular {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .room-badge-vip {
            background: #fefce8;
            color: #854d0e;
            border: 1px solid #fde047;
        }
        .room-badge-vvip {
            background: #0f172a;
            color: #fef08a;
            border: 1px solid #334155;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
        }
        .sg-pulse-dot {
            width: 7px;
            height: 7px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 rgba(16, 185, 129, 0.5);
            animation: sgPulse 1.8s infinite;
            margin-left: 4px;
        }
        @keyframes sgPulse {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.6); }
            70% { box-shadow: 0 0 0 8px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
        }

        /* Fullscreen TV Flight Board Display */
        body.tv-display-active .sidebar,
        body.tv-display-active .app-header,
        body.tv-display-active .sidebar-backdrop,
        body.tv-display-active footer {
            display: none !important;
        }
        body.tv-display-active main,
        body.tv-display-active #orScheduleApp {
            margin: 0 !important;
            padding: 8px !important;
            max-width: 100vw !important;
            width: 100vw !important;
        }
        body.tv-display-active .or-canvas-card {
            border-radius: 12px;
            padding: 16px;
        }
    </style>

    {{-- Main Container Card --}}
    <div class="or-canvas-card mb-4">
        {{-- Header Section: Title & Actions --}}
        <div class="or-header-box">
            <div class="d-flex align-items-center gap-3">
                <div class="or-header-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h4 class="mb-0 fw-bolder text-dark" style="font-size: 1.35rem; color: #0f172a;">جدول وقائمة انتظار العمليات الجراحية</h4>
                        <span class="badge bg-light text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold">OR Flight Board</span>
                    </div>
                    <div class="text-muted small mt-1">
                        متابعة وتحديث حالات العمليات، الصالات المخصصة، والمواعيد
                        <span class="mx-1">•</span>
                        <span class="text-dark fw-semibold"><i class="far fa-clock text-primary me-0.5"></i> التوقيت المباشر للمركز</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                @can('create surgeries')
                <a href="{{ route('surgeries.create') }}" class="btn rounded-pill px-3 py-2 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                    <i class="fas fa-plus me-1"></i> حجز عملية جديدة
                </a>
                @endcan

                @can('view surgeries')
                <a href="{{ route('surgeries.index') }}" class="btn btn-outline-secondary bg-white rounded-pill px-3 py-2 shadow-sm fw-semibold" style="border-color: #cbd5e1; color: #334155;">
                    <i class="fas fa-archive me-1 text-secondary"></i> الأرشيف العام
                </a>
                @endcan

                <button type="button" class="btn btn-outline-dark bg-white rounded-pill px-3 py-2 shadow-sm fw-semibold" id="btnToggleTvMode" onclick="toggleTvDisplayMode()" title="عرض مخصص لشاشات الـ TV وممرات العمليات">
                    <i class="fas fa-tv me-1 text-primary"></i> <span id="tvModeText">شاشة العرض (TV)</span>
                </button>

                <button type="button" class="btn btn-light border rounded-pill px-3 py-2 shadow-sm text-dark fw-bold" onclick="location.reload();">
                    <i class="fas fa-rotate me-1 text-primary"></i> تحديث
                </button>
            </div>
        </div>

        {{-- Realtime Search Bar --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div class="position-relative flex-grow-1" style="max-width: 520px;">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text" id="surgerySearchInput" class="form-control rounded-pill ps-5 pe-4 py-2 border" style="background: #f8fafc; font-size: 0.88rem;" placeholder="بحث فوري بالاسم، رقم الملف، الطبيب، العملية، رقم الغرفة...">
            </div>
            <div class="small text-muted fw-semibold">
                عرض <span id="visibleRowCount" class="text-primary font-monospace fw-bold">{{ $daySurgeries->count() }}</span> من أصل <span class="font-monospace fw-bold">{{ $daySurgeries->count() }}</span> عملية
            </div>
        </div>

        {{-- Master Flight Board Table (5 Consolidated Columns) --}}
        <div class="or-table-shell table-responsive">
            <table class="or-master-table">
                <thead>
                    <tr>
                        <th style="width: 18%;">حالة المريض في الصالة</th>
                        <th style="width: 26%;">المريض</th>
                        <th style="width: 24%;">نوع العملية</th>
                        <th style="width: 20%;">الطبيب الجراح</th>
                        <th style="width: 12%;">رقم الغرفة</th>
                    </tr>
                </thead>
                <tbody id="surgeriesTableBody">
                    @forelse($daySurgeries as $surgery)
                    @php
                        $patientName = optional(optional($surgery->patient)->user)->name ?? 'غير محدد';
                        $phone = optional(optional($surgery->patient)->user)->phone;
                        $fileNumber = $surgery->patient->file_number ?? '-';
                        $doctorName = $surgery->doctor->user->name ?? $surgery->surgeon_name ?? 'غير محدد';
                        $room = $surgery->room;
                        $rtype = $room ? $room->room_type : null;
                        $otStation = $surgery->operationTheaterStation;
                        $isOtCompleted = ($surgery->status === 'completed') || ($otStation && $otStation->status === 'completed');
                        $isOtInProgress = ($surgery->status === 'in_progress') || ($otStation && $otStation->status === 'in_progress');

                        $searchData = strtolower(
                            $patientName . ' ' .
                            $fileNumber . ' ' .
                            $surgery->surgery_type . ' ' .
                            $doctorName . ' ' .
                            ($room ? $room->room_number : '')
                        );

                        // Normalized status for interactive KPI filter
                        $normalizedStatus = $surgery->status;
                        if (in_array($normalizedStatus, ['waiting', 'checked_in'])) {
                            $normalizedStatus = 'waiting';
                        } elseif ($isOtCompleted) {
                            $normalizedStatus = 'completed';
                        }

                        // Color theme per presence state (دخل / انتظار / انتهى)
                        if ($isOtCompleted) {
                            $rowTheme = 'row-status-completed';
                            $rowStyle = 'background-color: rgba(16, 185, 129, 0.05); border-right: 4px solid #10b981;';
                            $patientIcon = 'fas fa-check-circle text-success';
                            $patientColor = 'text-success';
                            $badgeHtml = '<span class="badge bg-success text-white py-1.5 px-3 rounded-pill shadow-sm fw-bold"><i class="fas fa-check-double me-1"></i>انتهى (خرج من الصالة)</span>';
                        } elseif ($isOtInProgress) {
                            $rowTheme = 'row-status-in-progress';
                            $rowStyle = 'background-color: rgba(2, 132, 199, 0.05); border-right: 4px solid #0284c7;';
                            $patientIcon = 'fas fa-procedures text-primary';
                            $patientColor = 'text-primary';
                            $badgeHtml = '<span class="badge bg-primary text-white py-1.5 px-3 rounded-pill shadow-sm fw-bold"><span class="sg-pulse-dot me-1"></span>دخل الصالة (جارية)</span>';
                        } elseif ($surgery->status === 'cancelled') {
                            $rowTheme = 'row-status-cancelled';
                            $rowStyle = 'background-color: rgba(239, 68, 68, 0.04); border-right: 4px solid #ef4444;';
                            $patientIcon = 'fas fa-user-times text-danger';
                            $patientColor = 'text-danger';
                            $badgeHtml = '<span class="badge bg-danger text-white py-1.5 px-3 rounded-pill shadow-sm fw-bold"><i class="fas fa-times me-1"></i>ملغاة</span>';
                        } else {
                            $rowTheme = 'row-status-waiting';
                            $rowStyle = 'background-color: rgba(245, 158, 11, 0.05); border-right: 4px solid #f59e0b;';
                            $patientIcon = 'fas fa-user-clock text-warning';
                            $patientColor = 'text-dark';
                            $badgeHtml = '<span class="badge bg-warning text-dark py-1.5 px-3 rounded-pill shadow-sm fw-bold"><i class="fas fa-clock me-1"></i>بالانتظار (لم يدخل بعد)</span>';
                        }
                    @endphp
                    <tr class="surgery-row {{ $rowTheme }}" style="{{ $rowStyle }}" data-search="{{ $searchData }}" data-status="{{ $normalizedStatus }}" id="surgeryRow-{{ $surgery->id }}">
                        {{-- Col 1: Presence in OR (Stage / Status) --}}
                        <td>
                            {!! $badgeHtml !!}
                        </td>

                        {{-- Col 2: Patient --}}
                        <td>
                            <a href="{{ route('surgeries.show', $surgery) }}" class="d-inline-flex align-items-center gap-1.5 fw-bold text-decoration-none" style="font-size: 0.96rem;" title="فتح ملف وتفاصيل العملية">
                                <i class="{{ $patientIcon }} fs-5"></i>
                                <span class="{{ $patientColor }}">{{ $patientName }}</span>
                            </a>
                            <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                <span class="badge bg-white text-muted border font-monospace px-1.5 py-0.5" style="font-size: 0.72rem;">ملف #{{ $fileNumber }}</span>
                                @if($surgery->patient && $surgery->patient->age)
                                    <span class="badge bg-white text-muted border px-1.5 py-0.5" style="font-size: 0.72rem;">{{ $surgery->patient->age }} سنة</span>
                                @endif
                                @if($phone)
                                    <span class="small text-muted" style="font-size: 0.75rem;">
                                        <i class="fas fa-phone-alt me-0.5 text-secondary"></i>{{ $phone }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Col 3: Surgery Type --}}
                        <td>
                            <div class="fw-bold text-dark" style="color: #0f172a !important; font-size: 0.94rem;">
                                {{ $surgery->surgery_type }}
                            </div>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                @if($surgery->surgicalOperation && $surgery->surgicalOperation->category)
                                    <small class="text-muted">{{ $surgery->surgicalOperation->category }}</small>
                                @endif
                                @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                        <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border py-0 px-2 rounded-pill" style="font-size: 0.72rem;">
                                        <i class="fas fa-money-bill-wave me-1"></i>نقدي (كاش)
                                    </span>
                                @endif
                            </div>
                        </td>

                        {{-- Col 4: Surgeon --}}
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <i class="fas fa-user-md text-success"></i>
                                <span class="fw-semibold">
                                    @if($surgery->doctor && $surgery->doctor->user)
                                        د. {{ $surgery->doctor->user->name }}
                                    @elseif($surgery->surgeon_name)
                                        {{ $surgery->surgeon_name }} <span class="badge bg-light text-secondary border">خارجي</span>
                                    @else
                                        <span class="text-muted">غير محدد</span>
                                    @endif
                                </span>
                            </div>
                        </td>

                        {{-- Col 5: Room Number --}}
                        <td>
                            @if($room)
                                <span class="room-badge-pill {{ $rtype === 'vvip' ? 'room-badge-vvip' : ($rtype === 'vip' ? 'room-badge-vip' : 'room-badge-regular') }}">
                                    @if($rtype === 'vvip')
                                        <i class="fas fa-gem text-warning"></i> VVIP {{ $room->room_number }}
                                    @elseif($rtype === 'vip')
                                        <i class="fas fa-crown text-warning"></i> VIP {{ $room->room_number }}
                                    @else
                                        <i class="fas fa-bed text-primary"></i> {{ $room->room_number }}
                                    @endif
                                </span>
                            @else
                                <span class="badge bg-light text-muted border">بدون غرفة</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <div class="py-4">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center p-3 mb-2 text-muted" style="width: 70px; height: 70px;">
                                    <i class="far fa-calendar-times fs-2"></i>
                                </div>
                                <div class="fw-bold fs-6 text-dark">لا توجد عمليات مسجلة في هذا اليوم</div>
                                <div class="small text-muted mt-1">اختر يوماً آخر من التقويم في الأعلى أو قم بحجز عملية جديدة</div>
                                @can('create surgeries')
                                <a href="{{ route('surgeries.create') }}" class="btn btn-sm btn-primary rounded-pill px-3 py-1.5 mt-3 fw-bold">
                                    <i class="fas fa-plus me-1"></i> حجز عملية الآن
                                </a>
                                @endcan
                            </div>
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
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-1 pt-3 px-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-1.5">
                    <i class="fas fa-door-closed text-primary"></i>
                    إضافة صالة جديدة
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1 px-3 pb-3">
                <input type="hidden" id="activeSurgeryIdForNewTheater">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-1 fw-semibold">اسم الصالة</label>
                    <input type="text" id="newTheaterNameModalInput" class="form-control form-control-sm rounded-3 py-1.5" placeholder="مثلاً: صالة 4، صالة القسطرة...">
                    <div id="newTheaterModalError" class="text-danger small mt-1 d-none"></div>
                </div>
                <div class="d-flex justify-content-end gap-1.5 pt-2 border-top">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 fw-bold shadow-sm" id="btnSaveTheaterFromModal">
                        <i class="fas fa-check me-1"></i> حفظ الصالة
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
let currentActiveFilter = 'all';

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

function applyFilters() {
    const searchInput = document.getElementById('surgerySearchInput');
    const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
    const rows = document.querySelectorAll('.surgery-row');
    let visibleCount = 0;

    rows.forEach(row => {
        const searchData = (row.getAttribute('data-search') || '').toLowerCase();
        const matchesSearch = !query || searchData.includes(query);

        if (matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const counterEl = document.getElementById('visibleRowCount');
    if (counterEl) {
        counterEl.textContent = visibleCount;
    }
}

document.addEventListener('DOMContentLoaded', function () {
    // Search Filter
    const searchInput = document.getElementById('surgerySearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
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
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ الصالة';

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
                btnSaveTheater.innerHTML = '<i class="fas fa-check me-1"></i> حفظ الصالة';
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

    // Auto-refresh every 60s for public TV Flight Board display (only when not searching or using modal)
    setInterval(function () {
        const searchInput = document.getElementById('surgerySearchInput');
        const isSearching = searchInput && searchInput.value.trim().length > 0;
        const isModalOpen = document.querySelector('.modal.show');
        if (!isSearching && !isModalOpen) {
            location.reload();
        }
    }, 60000);
});

// Toggle TV Flight Board Fullscreen Display
function toggleTvDisplayMode() {
    document.body.classList.toggle('tv-display-active');
    const isTv = document.body.classList.contains('tv-display-active');
    const tvText = document.getElementById('tvModeText');
    if (tvText) {
        tvText.textContent = isTv ? 'خروج من وضع الـ TV' : 'شاشة العرض (TV)';
    }
    if (isTv && document.documentElement.requestFullscreen) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else if (!isTv && document.exitFullscreen && document.fullscreenElement) {
        document.exitFullscreen().catch(() => {});
    }
}
</script>
@endsection
