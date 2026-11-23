@extends('layouts.app')

@section('title', __('جدول توفر ودوام التمريض وتوزيع الطوابق'))

@push('styles')
<style>
    .nurse-card {
        border-radius: 14px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid #e2e8f0;
    }
    .nurse-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    }
    .pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
        70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }
    .stat-chip {
        border-radius: 12px;
        padding: 1rem 1.25rem;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .form-switch .form-check-input {
        width: 3em;
        height: 1.6em;
        cursor: pointer;
    }
    .badge-floor {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        color: #334155;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 999px;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4 px-lg-5">

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Header Section --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2.5">
                    <i class="fas fa-user-nurse fa-2x"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0 text-dark">{{ __('جدول توفر ودوام التمريض اليومي') }}</h3>
                    <p class="text-muted small mb-0">{{ __('تحديد الممرضين المتاحين اليوم بالدوام، توزيع الطوابق، وضبط صلاحيات دخول النظام') }}</p>
                </div>
            </div>
        </div>

        {{-- Date Navigation Picker --}}
        <div class="d-flex align-items-center gap-2 bg-white p-2 rounded-4 border shadow-sm">
            <a href="{{ route('nurse-availability.index', ['date' => \Carbon\Carbon::parse($selectedDate)->subDay()->toDateString(), 'floor' => $floorFilter, 'status' => $statusFilter]) }}" 
               class="btn btn-sm btn-light rounded-pill px-3" title="{{ __('اليوم السابق') }}">
                <i class="fas {{ app()->getLocale() === 'en' ? 'fa-chevron-left' : 'fa-chevron-right' }}"></i>
            </a>

            <form method="GET" action="{{ route('nurse-availability.index') }}" class="d-flex align-items-center gap-2" id="dateFilterForm">
                <input type="hidden" name="floor" value="{{ $floorFilter }}">
                <input type="hidden" name="status" value="{{ $statusFilter }}">
                <input type="hidden" name="search" value="{{ $search }}">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0"><i class="far fa-calendar-alt text-primary"></i></span>
                    <input type="date" name="date" class="form-control border-0 fw-bold text-center" 
                           value="{{ $selectedDate }}" onchange="document.getElementById('dateFilterForm').submit()">
                </div>
            </form>

            <a href="{{ route('nurse-availability.index', ['date' => \Carbon\Carbon::parse($selectedDate)->addDay()->toDateString(), 'floor' => $floorFilter, 'status' => $statusFilter]) }}" 
               class="btn btn-sm btn-light rounded-pill px-3" title="{{ __('اليوم التالي') }}">
                <i class="fas {{ app()->getLocale() === 'en' ? 'fa-chevron-right' : 'fa-chevron-left' }}"></i>
            </a>

            @if($selectedDate !== today()->toDateString())
                <a href="{{ route('nurse-availability.index', ['date' => today()->toDateString(), 'floor' => $floorFilter, 'status' => $statusFilter]) }}" 
                   class="btn btn-sm btn-primary rounded-pill px-3">
                    {{ __('العودة لليوم') }}
                </a>
            @endif
        </div>
    </div>

    {{-- Stats Cards Row --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-chip d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-bold d-block">{{ __('إجمالي الكادر التمريضي') }}</span>
                    <h3 class="fw-bold mb-0 text-dark mt-1">{{ $totalNurses }}</h3>
                </div>
                <div class="rounded-circle bg-light text-primary p-3">
                    <i class="fas fa-users fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-chip d-flex align-items-center justify-content-between border-success border-opacity-25 bg-success bg-opacity-10">
                <div>
                    <span class="text-success small fw-bold d-block">{{ __('المتاحين بالدوام اليوم') }}</span>
                    <h3 class="fw-bold mb-0 text-success mt-1" id="statAvailableCount">{{ $availableCount }}</h3>
                </div>
                <div class="rounded-circle bg-success text-white p-3 shadow-xs">
                    <i class="fas fa-check-circle fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-chip d-flex align-items-center justify-content-between border-danger border-opacity-25 bg-danger bg-opacity-10">
                <div>
                    <span class="text-danger small fw-bold d-block">{{ __('خارج الدوام (مغلق)') }}</span>
                    <h3 class="fw-bold mb-0 text-danger mt-1" id="statOffDutyCount">{{ $offDutyCount }}</h3>
                </div>
                <div class="rounded-circle bg-danger text-white p-3 shadow-xs">
                    <i class="fas fa-ban fa-lg"></i>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="stat-chip d-flex align-items-center justify-content-between border-info border-opacity-25 bg-info bg-opacity-10">
                <div>
                    <span class="text-info small fw-bold d-block">{{ __('نسبة تغطية الشفت') }}</span>
                    <h3 class="fw-bold mb-0 text-info mt-1" id="statCoverageRate">{{ $coverageRate }}%</h3>
                </div>
                <div class="rounded-circle bg-info text-white p-3 shadow-xs">
                    <i class="fas fa-chart-pie fa-lg"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar & Quick Actions --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                
                {{-- Search & Filters Form --}}
                <form method="GET" action="{{ route('nurse-availability.index') }}" class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                    <input type="hidden" name="date" value="{{ $selectedDate }}">

                    {{-- Search Input --}}
                    <div class="input-group input-group-sm" style="max-width: 260px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="بحث بالاسم أو البريد..." value="{{ $search }}">
                        @if($search)
                            <a href="{{ route('nurse-availability.index', ['date' => $selectedDate, 'floor' => $floorFilter, 'status' => $statusFilter]) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        @endif
                    </div>

                    {{-- Floor Filter Dropdown --}}
                    <select name="floor" class="form-select form-select-sm" style="max-width: 200px;" onchange="this.form.submit()">
                        <option value="">🏢 كل الطوابق والأقسام</option>
                        @foreach($floors as $fl)
                            <option value="{{ $fl }}" {{ $floorFilter === $fl ? 'selected' : '' }}>
                                {{ $fl }} ({{ $floorCounts[$fl] ?? 0 }})
                            </option>
                        @endforeach
                    </select>

                    {{-- Status Filter Buttons --}}
                    <div class="btn-group btn-group-sm" role="group">
                        <a href="{{ route('nurse-availability.index', ['date' => $selectedDate, 'search' => $search, 'floor' => $floorFilter, 'status' => 'all']) }}" 
                           class="btn {{ $statusFilter === 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">
                            الكل ({{ $totalNurses }})
                        </a>
                        <a href="{{ route('nurse-availability.index', ['date' => $selectedDate, 'search' => $search, 'floor' => $floorFilter, 'status' => 'available']) }}" 
                           class="btn {{ $statusFilter === 'available' ? 'btn-success' : 'btn-outline-secondary' }}">
                            المتاحين ({{ $availableCount }})
                        </a>
                        <a href="{{ route('nurse-availability.index', ['date' => $selectedDate, 'search' => $search, 'floor' => $floorFilter, 'status' => 'off_duty']) }}" 
                           class="btn {{ $statusFilter === 'off_duty' ? 'btn-danger' : 'btn-outline-secondary' }}">
                            خارج الدوام ({{ $offDutyCount }})
                        </a>
                    </div>
                </form>

                {{-- Bulk Actions Dropdown --}}
                <div class="d-flex align-items-center gap-2">
                    <form method="POST" action="{{ route('nurse-availability.bulk-update') }}" class="d-inline" 
                          onsubmit="return confirm('تأكيد تفعيل دوام جميع الممرضين لهذا اليوم؟ سيتاح لهم فتح النظام.');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="floor" value="{{ $floorFilter }}">
                        <input type="hidden" name="action" value="set_all_available">
                        <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 shadow-xs">
                            <i class="fas fa-check-double me-1"></i> تفعيل دوام الكل اليوم
                        </button>
                    </form>

                    <form method="POST" action="{{ route('nurse-availability.bulk-update') }}" class="d-inline" 
                          onsubmit="return confirm('تأكيد إيقاف دوام جميع الممرضين لهذا اليوم؟ لن يتمكنوا من تسجيل الدخول للنظام.');">
                        @csrf
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <input type="hidden" name="floor" value="{{ $floorFilter }}">
                        <input type="hidden" name="action" value="set_all_off_duty">
                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-xs">
                            <i class="fas fa-ban me-1"></i> إيقاف دوام الكل
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Nurses Table Card --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary small fw-bold" style="width: 25%;">الممرض / الكادر</th>
                        <th class="py-3 text-secondary small fw-bold text-center" style="width: 20%;">حالة الدوام (دخول النظام)</th>
                        <th class="py-3 text-secondary small fw-bold" style="width: 25%;">🏢 الطابق / القسم لليوم</th>
                        <th class="py-3 text-secondary small fw-bold" style="width: 15%;">⏰ شفت العمل</th>
                        <th class="pe-4 py-3 text-secondary small fw-bold text-center" style="width: 15%;">الحالة السريرية</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($nurses as $nurse)
                        @php
                            $record = $nurse->nurseAvailabilities->first();
                            $isAvailable = $record ? (bool) $record->is_available : false;
                            $assignedFloor = $record?->assigned_floor;
                            $assignedShift = $record?->shift ?? 'morning';
                        @endphp
                        <tr id="nurse-row-{{ $nurse->id }}">
                            {{-- Nurse Info --}}
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" 
                                         style="width: 44px; height: 44px; font-size: 1.1rem;">
                                        {{ mb_substr($nurse->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark">{{ $nurse->name }}</h6>
                                        <div class="d-flex align-items-center gap-2 mt-0.5 text-muted small">
                                            @if($nurse->phone)
                                                <span><i class="fas fa-phone fa-xs text-secondary me-1"></i>{{ $nurse->phone }}</span>
                                            @else
                                                <span><i class="fas fa-envelope fa-xs text-secondary me-1"></i>{{ $nurse->email }}</span>
                                            @endif
                                            @if($nurse->hasRole('head_nurse'))
                                                <span class="badge bg-purple text-white px-2 py-0.5 rounded-pill" style="font-size: 0.7rem; background-color: #7c3aed;">مشرف تمريض</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Availability Switch (Toggle) --}}
                            <td class="py-3 text-center">
                                <div class="d-flex flex-column align-items-center justify-content-center">
                                    <div class="form-check form-switch mb-1">
                                        <input class="form-check-input availability-switch" type="checkbox" role="switch"
                                               data-nurse-id="{{ $nurse->id }}"
                                               id="switch-{{ $nurse->id }}"
                                               {{ $isAvailable ? 'checked' : '' }}>
                                    </div>
                                    <span class="small fw-bold text-muted switch-label" id="label-{{ $nurse->id }}">
                                        {{ $isAvailable ? 'متاح بالدوام' : 'خارج الدوام' }}
                                    </span>
                                </div>
                            </td>

                            {{-- Assigned Floor Selector --}}
                            <td class="py-3">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light text-secondary"><i class="fas fa-building"></i></span>
                                    <select class="form-select form-select-sm floor-selector rounded-end-3" 
                                            data-nurse-id="{{ $nurse->id }}">
                                        <option value="">-- لم يحدد الطابق --</option>
                                        @foreach($floors as $fl)
                                            <option value="{{ $fl }}" {{ $assignedFloor === $fl ? 'selected' : '' }}>
                                                {{ $fl }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </td>

                            {{-- Assigned Shift Selector --}}
                            <td class="py-3">
                                <select class="form-select form-select-sm shift-selector rounded-3" 
                                        data-nurse-id="{{ $nurse->id }}">
                                    @foreach($shifts as $sKey => $sLabel)
                                        <option value="{{ $sKey }}" {{ $assignedShift === $sKey ? 'selected' : '' }}>
                                            {{ $sLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            {{-- Live Badge Status --}}
                            <td class="pe-4 py-3 text-center">
                                <span class="badge rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 status-badge-{{ $nurse->id }} {{ $isAvailable ? 'bg-success bg-opacity-10 text-success border border-success-subtle' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle' }}">
                                    @if($isAvailable)
                                        <span class="pulse-dot bg-success"></span>
                                        <span>نشط بالدوام</span>
                                    @else
                                        <i class="fas fa-lock text-secondary fa-xs"></i>
                                        <span>مغلق الدخول</span>
                                    @endif
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-user-slash fa-3x mb-3 text-secondary text-opacity-50"></i>
                                <h5 class="fw-bold">لا يوجد ممرضين يطابقون خيارات البحث</h5>
                                <p class="small mb-0">يرجى تعديل الفلاتر أو التاريخ لإظهار الكادر.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Toast Feedback Notification Container (Medical Light Theme) --}}
<div class="position-fixed bottom-0 start-0 p-3" style="z-index: 1090;">
    <div id="availabilityToast" class="toast align-items-center bg-white border border-success border-opacity-50 text-dark rounded-4 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex align-items-center py-1">
            <div class="toast-body d-flex align-items-center gap-2 px-3 py-2">
                <i class="fas fa-check-circle text-success fs-5" id="toastIcon"></i>
                <span id="toastMessage" class="fw-bold text-dark">تم الحفظ بنجاح</span>
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectedDate = '{{ $selectedDate }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const toastEl = document.getElementById('availabilityToast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');
    const bsToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 2500 }) : null;

    function showToast(message, isSuccess = true) {
        if (typeof toastr !== 'undefined') {
            if (isSuccess) {
                toastr.success(message);
            } else {
                toastr.error(message);
            }
            return;
        }

        if (!toastEl) return;
        toastMessage.textContent = message;
        toastIcon.className = isSuccess ? 'fas fa-check-circle text-success fs-5' : 'fas fa-exclamation-triangle text-danger fs-5';
        toastEl.className = isSuccess 
            ? 'toast align-items-center bg-white border border-success border-opacity-50 text-dark rounded-4 shadow-lg' 
            : 'toast align-items-center bg-white border border-danger border-opacity-50 text-dark rounded-4 shadow-lg';
        bsToast.show();
    }

    // 1. Live Toggle Availability Switch
    document.querySelectorAll('.availability-switch').forEach(switchInput => {
        switchInput.addEventListener('change', function() {
            const nurseId = this.dataset.nurseId;
            const isChecked = this.checked;
            const labelEl = document.getElementById('label-' + nurseId);
            const badgeEl = document.querySelector('.status-badge-' + nurseId);

            fetch(`{{ url('nurse-availability') }}/${nurseId}/update-availability`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    is_available: isChecked ? 1 : 0,
                    date: selectedDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, true);
                    if (labelEl) labelEl.textContent = data.status_text;

                    if (badgeEl) {
                        if (isChecked) {
                            badgeEl.className = `badge rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 status-badge-${nurseId} bg-success bg-opacity-10 text-success border border-success-subtle`;
                            badgeEl.innerHTML = `<span class="pulse-dot bg-success"></span><span>نشط بالدوام</span>`;
                        } else {
                            badgeEl.className = `badge rounded-pill px-3 py-1.5 fw-bold d-inline-flex align-items-center gap-1.5 status-badge-${nurseId} bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle`;
                            badgeEl.innerHTML = `<i class="fas fa-lock text-secondary fa-xs"></i><span>مغلق الدخول</span>`;
                        }
                    }

                    // Dynamically recalculate stats counter
                    const statAvailEl = document.getElementById('statAvailableCount');
                    const statOffEl = document.getElementById('statOffDutyCount');
                    const statRateEl = document.getElementById('statCoverageRate');
                    const total = {{ $totalNurses }};

                    if (statAvailEl && statOffEl) {
                        let curAvail = parseInt(statAvailEl.textContent) || 0;
                        curAvail = isChecked ? curAvail + 1 : Math.max(0, curAvail - 1);
                        let curOff = Math.max(0, total - curAvail);
                        statAvailEl.textContent = curAvail;
                        statOffEl.textContent = curOff;
                        if (statRateEl && total > 0) {
                            statRateEl.textContent = Math.round((curAvail / total) * 100) + '%';
                        }
                    }
                } else {
                    showToast('حدث خطأ أثناء التحديث', false);
                }
            })
            .catch(err => {
                showToast('تعذر الاتصال بالسيرفر', false);
            });
        });
    });

    // 2. Live Change Assigned Floor
    document.querySelectorAll('.floor-selector').forEach(floorSelect => {
        floorSelect.addEventListener('change', function() {
            const nurseId = this.dataset.nurseId;
            const newFloor = this.value;

            fetch(`{{ url('nurse-availability') }}/${nurseId}/update-floor`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    assigned_floor: newFloor,
                    date: selectedDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, true);
                } else {
                    showToast('تعذر تحديث الطابق', false);
                }
            })
            .catch(() => showToast('خطأ في الاتصال', false));
        });
    });

    // 3. Live Change Assigned Shift
    document.querySelectorAll('.shift-selector').forEach(shiftSelect => {
        shiftSelect.addEventListener('change', function() {
            const nurseId = this.dataset.nurseId;
            const newShift = this.value;

            fetch(`{{ url('nurse-availability') }}/${nurseId}/update-shift`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shift: newShift,
                    date: selectedDate
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, true);
                } else {
                    showToast('تعذر تحديث الشفت', false);
                }
            })
            .catch(() => showToast('خطأ في الاتصال', false));
        });
    });
});
</script>
@endpush
