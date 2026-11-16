@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    {{-- Executive Header --}}
    <div class="card border-0 shadow-sm mb-3" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); border-radius: 14px;">
        <div class="card-body p-3 p-md-4 text-white">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px; background: rgba(14, 165, 233, 0.2); border: 1.5px solid rgba(14, 165, 233, 0.4);">
                        <i class="fas fa-syringe text-info fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h4 class="fw-bold mb-0 text-white">محطة طبيب التخدير</h4>
                            @if($isSharedAccount)
                                <span class="badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-2.5 py-1 rounded-pill small">
                                    <i class="fas fa-hospital me-1"></i> صالة العمليات - الحساب الموحد لكافة أطباء التخدير
                                </span>
                            @else
                                <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-50 px-2.5 py-1 rounded-pill small">
                                    <i class="fas fa-user-md me-1"></i> د. {{ auth()->user()->name }} (حساب خاص)
                                </span>
                            @endif
                        </div>
                        <p class="text-white-50 mb-0 small mt-1">
                            @if($isSharedAccount)
                                حساب موحد لمحطة صالة العمليات يتيح متابعة وتوثيق كافة إجراءات التخدير لجميع العمليات وإصدار توجيهات الإفاقة
                            @else
                                متابعة وتوثيق إجراءات التخدير الخاصة بك بعد الصالة وتحديد بروتوكول الإفاقة والمتابعة السريرية
                            @endif
                        </p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-light btn-sm fw-semibold rounded-pill px-3 shadow-sm">
                        <i class="fas fa-procedures me-1"></i> لوحة الصالات
                    </a>
                    <a href="{{ route('anesthesia-station.index') }}" class="btn btn-info text-white btn-sm fw-bold rounded-pill px-3 shadow-sm">
                        <i class="fas fa-sync-alt me-1"></i> تحديث فوري
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-3" role="alert">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Search & Filter Bar --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-2.5 p-md-3">
            <form action="{{ route('anesthesia-station.index') }}" method="GET" id="anesthesiaFilterForm" class="row g-2 align-items-center">
                {{-- Live Search Box --}}
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" id="filterSearch" value="{{ request('search') }}"
                               class="form-control form-control-sm border-start-0 ps-0"
                               placeholder="بحث بالمريض، الملف، العملية، التخدير..."
                               autocomplete="off">
                        @if(request('search'))
                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearSearch()" title="مسح">
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Anesthesiologist Filter (For Unified Account) or Personal Doctor Badge --}}
                @if($isSharedAccount)
                    <div class="col-6 col-md-3 col-lg-2">
                        <select name="anesthesiologist_id" id="filterAnesthesiologist" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">👨‍⚕️ كل أطباء التخدير</option>
                            @foreach($anesthesiologists as $doctor)
                                <option value="{{ $doctor->id }}" {{ request('anesthesiologist_id') == $doctor->id ? 'selected' : '' }}>
                                    {{ $doctor->user?->name ?? 'طبيب تخدير #'.$doctor->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-6 col-md-3 col-lg-2">
                        <div class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 w-100 py-2 d-flex align-items-center justify-content-center text-truncate" title="عملياتك الخاصة فقط">
                            <i class="fas fa-user-md me-1"></i> د. {{ auth()->user()->name }}
                        </div>
                    </div>
                @endif

                {{-- Status Filter --}}
                <div class="col-6 col-md-3 col-lg-2">
                    <select name="status" id="filterStatus" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="all">⚡ كل الحالات</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>⏳ بانتظار المعاينة</option>
                        <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>🔄 قيد المتابعة</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>✅ مكتملة ومُعتمدة</option>
                    </select>
                </div>

                {{-- Date Filter --}}
                <div class="col-12 col-md-4 col-lg-3">
                    <div class="input-group input-group-sm">
                        <input type="date" name="date" id="filterDate" value="{{ request('date') }}"
                               class="form-control form-control-sm" title="تاريخ العملية" onchange="this.form.submit()">
                        <button type="button" class="btn btn-sm {{ request('date') === 'today' ? 'btn-info text-white' : 'btn-outline-secondary' }}" onclick="filterQuickDate('today')" title="عمليات اليوم">
                            اليوم
                        </button>
                        <button type="button" class="btn btn-sm {{ !request('date') || request('date') === 'all' ? 'btn-info text-white' : 'btn-outline-secondary' }}" onclick="filterQuickDate('all')" title="عرض كل التواريخ">
                            الكل
                        </button>
                    </div>
                </div>

                {{-- Action & Reset Buttons --}}
                <div class="col-12 col-md-auto ms-auto d-flex align-items-center gap-1.5">
                    <button type="submit" class="btn btn-info text-white btn-sm fw-bold px-3 shadow-xs">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->hasAny(['search', 'anesthesiologist_id', 'status', 'date']))
                        <a href="{{ route('anesthesia-station.index') }}" class="btn btn-light btn-sm border text-secondary shadow-xs" title="إعادة ضبط الفلاتر">
                            <i class="fas fa-redo me-1"></i> ضبط
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- Main Table Card --}}
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                <i class="fas fa-list-alt text-info"></i>
                قائمة العمليات بمحطة التخدير
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fw-semibold" id="casesCountBadge">
                عرض {{ $surgeries->count() }} حالة
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="anesthesiaTable" style="font-size: 0.92rem;">
                    <thead class="bg-light text-secondary text-uppercase fw-semibold" style="font-size: 0.8rem; letter-spacing: 0.5px;">
                        <tr>
                            <th class="ps-4 py-3">المريض</th>
                            <th class="py-3">العملية الجراحية</th>
                            <th class="py-3">موعد العملية</th>
                            <th class="py-3">طبيب التخدير</th>
                            <th class="py-3">نوع التخدير</th>
                            <th class="py-3 text-center">حالة المحطة</th>
                            <th class="pe-4 py-3 text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($surgeries as $surgery)
                            @php
                                $stationStatus = $surgery->anesthesiaStation?->status ?? 'pending';
                                $isCompleted = ($stationStatus === 'completed');
                                $isInProgress = ($stationStatus === 'in_progress');
                                $patientName = $surgery->patient?->user?->name ?? '';
                                $fileNumber = $surgery->patient?->file_number ?? '';
                                $surgeryName = $surgery->surgery_name ?? '';
                                $anesthetistName = $surgery->anesthesiaStation?->anesthesiologist?->user?->name
                                    ?? $surgery->anesthesiologist?->user?->name
                                    ?? '';
                                $anesthesiaType = $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? '';
                                $searchString = mb_strtolower(trim("{$patientName} {$fileNumber} {$surgeryName} {$anesthetistName} {$anesthesiaType}"));
                            @endphp
                            <tr class="anesthesia-row"
                                data-search="{{ $searchString }}"
                                data-anesthesiologist-id="{{ $surgery->anesthesiaStation?->anesthesiologist_id ?? $surgery->anesthesiologist_id ?? '' }}"
                                data-status="{{ $stationStatus }}"
                                data-date="{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '' }}">
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                                             style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); font-size: 13px;">
                                            {{ mb_substr($patientName ?: 'م', 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $patientName ?: '-' }}</div>
                                            <div class="text-muted small">
                                                <span>ملف: #{{ $fileNumber ?: '-' }}</span>
                                                @if($surgery->patient?->age)
                                                    <span class="mx-1">•</span>
                                                    <span>{{ $surgery->patient->age }} سنة</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3">
                                    <span class="fw-bold text-dark">{{ $surgeryName ?: '-' }}</span>
                                    @if($surgery->department)
                                        <div class="text-muted small">{{ $surgery->department->name }}</div>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <div class="fw-semibold text-dark">
                                        <i class="far fa-calendar-alt text-muted me-1"></i>
                                        {{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '-' }}
                                    </div>
                                    <div class="text-muted small">
                                        <i class="far fa-clock text-muted me-1"></i>
                                        {{ $surgery->scheduled_time ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : '-' }}
                                    </div>
                                </td>
                                <td class="py-3">
                                    @if($anesthetistName)
                                        <div class="d-flex align-items-center gap-1.5">
                                            <i class="fas fa-user-md text-info"></i>
                                            <span class="fw-semibold text-dark">{{ $anesthetistName }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-muted border">غير محدد</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    @if($anesthesiaType)
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2.5 py-1 fw-semibold">
                                            {{ $anesthesiaType }}
                                        </span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td class="py-3 text-center">
                                    @if($isCompleted)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-check-circle me-1"></i> مكتملة ومُعتمدة
                                        </span>
                                    @elseif($isInProgress)
                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-spinner fa-spin me-1"></i> قيد المتابعة
                                        </span>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1.5 fw-bold">
                                            <i class="fas fa-hourglass-half me-1"></i> بانتظار المعاينة
                                        </span>
                                    @endif
                                </td>
                                <td class="pe-4 py-3 text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-1.5 flex-wrap">
                                        <a href="{{ route('anesthesia-station.show', $surgery) }}" class="btn btn-sm btn-info text-white fw-bold px-3 py-1.5 shadow-sm rounded-pill" title="فتح المحطة السريرية للتخدير">
                                            <i class="fas fa-syringe me-1"></i> فتح المحطة
                                        </a>
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-outline-secondary fw-semibold px-2.5 py-1.5 shadow-sm rounded-pill" title="عرض السجل العام للعملية">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-procedures text-muted fa-3x mb-3 opacity-25"></i>
                                        <h6 class="fw-bold text-secondary">لا توجد عمليات مطابقة للفلاتر المحددة بمحطة التخدير</h6>
                                        <p class="text-muted small mb-0">تظهر هنا تلقائياً العمليات المحالة للتخدير</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsClientRow" style="display: none;">
                            <td colspan="7" class="text-center py-5 text-muted">
                                <div class="py-4">
                                    <i class="fas fa-search text-muted fa-3x mb-3 opacity-25"></i>
                                    <h6 class="fw-bold text-secondary">لا توجد نتائج تطابق البحث اللحظي</h6>
                                    <p class="text-muted small mb-0">جرّب البحث بكلمات أخرى أو إفراغ خانة البحث</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('filterSearch');
    const tableRows = document.querySelectorAll('.anesthesia-row');
    const countBadge = document.getElementById('casesCountBadge');
    const noResultsRow = document.getElementById('noResultsClientRow');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            tableRows.forEach(row => {
                const text = row.getAttribute('data-search') || '';
                if (!query || text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (countBadge) {
                countBadge.textContent = 'عرض ' + visibleCount + ' حالة';
            }

            if (noResultsRow) {
                noResultsRow.style.display = (visibleCount === 0 && tableRows.length > 0) ? '' : 'none';
            }
        });
    }
});

function clearSearch() {
    const searchInput = document.getElementById('filterSearch');
    if (searchInput) {
        searchInput.value = '';
        document.getElementById('anesthesiaFilterForm').submit();
    }
}

function filterQuickDate(dateVal) {
    const dateInput = document.getElementById('filterDate');
    if (dateInput) {
        if (dateVal === 'today') {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            dateInput.value = `${year}-${month}-${day}`;
        } else {
            dateInput.value = '';
        }
        document.getElementById('anesthesiaFilterForm').submit();
    }
}
</script>
@endsection
