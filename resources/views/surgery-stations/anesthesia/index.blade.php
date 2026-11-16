@extends('layouts.app')

@section('content')
<style>
    .hover-primary:hover {
        color: #0284c7 !important;
        text-decoration: underline !important;
    }
    .surgery-link-row {
        transition: background-color 0.15s ease-in-out;
    }
    .surgery-link-row:hover {
        background-color: rgba(2, 132, 199, 0.035) !important;
    }
</style>

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
                                استعراض كافة عمليات صالة العمليات، تعيين أطباء التخدير، وتوثيق الإجراءات والمتابعة السريرية
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
                <div class="col-12 col-md-5 col-lg-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="search" id="filterSearch" value="{{ request('search') }}"
                               class="form-control form-control-sm border-start-0 ps-0"
                               placeholder="بحث باسم المريض، رقم الملف، العملية..."
                               autocomplete="off">
                        @if(request('search'))
                            <button type="button" class="btn btn-outline-secondary border-start-0" onclick="clearSearch()" title="مسح">
                                <i class="fas fa-times"></i>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Anesthesiologist Filter --}}
                @if($isSharedAccount)
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="anesthesiologist_id" id="filterAnesthesiologist" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">👨‍⚕️ كل العمليات بالصالة</option>
                            <option value="unassigned" {{ request('anesthesiologist_id') === 'unassigned' ? 'selected' : '' }}>⚠️ بانتظار تعيين طبيب تخدير</option>
                            @foreach($anesthesiologists as $doctor)
                                <option value="{{ $doctor->id }}" {{ request('anesthesiologist_id') == $doctor->id ? 'selected' : '' }}>
                                    {{ $doctor->user?->name ?? 'طبيب تخدير #'.$doctor->id }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-6 col-md-3 col-lg-3">
                        <select name="anesthesiologist_id" id="filterAnesthesiologist" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">👨‍⚕️ كل العمليات بالصالة</option>
                            @if(auth()->user()->doctor)
                                <option value="{{ auth()->user()->doctor->id }}" {{ request('anesthesiologist_id') == auth()->user()->doctor->id ? 'selected' : '' }}>
                                    ⭐ د. {{ auth()->user()->name }} (عملياتي)
                                </option>
                            @endif
                            <option value="unassigned" {{ request('anesthesiologist_id') === 'unassigned' ? 'selected' : '' }}>⚠️ بانتظار تعيين طبيب تخدير</option>
                        </select>
                    </div>
                @endif

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
                    @if(request()->hasAny(['search', 'anesthesiologist_id', 'date']))
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
                            <th class="pe-4 py-3">طبيب التخدير</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        @forelse($surgeries as $surgery)
                            @php
                                $patientName = $surgery->patient?->user?->name ?? '';
                                $fileNumber = $surgery->patient?->file_number ?? '';
                                $surgeryName = $surgery->surgery_name ?? '';
                                $anesthetistName = $surgery->anesthesiaStation?->anesthesiologist?->user?->name
                                    ?? $surgery->anesthesiologist?->user?->name
                                    ?? '';
                                $searchString = mb_strtolower(trim("{$patientName} {$fileNumber} {$surgeryName} {$anesthetistName}"));
                            @endphp
                            <tr class="anesthesia-row surgery-link-row"
                                data-search="{{ $searchString }}"
                                data-anesthesiologist-id="{{ $surgery->anesthesiaStation?->anesthesiologist_id ?? $surgery->anesthesiologist_id ?? 'unassigned' }}"
                                data-date="{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : '' }}">
                                {{-- Patient Column (Direct Clickable Link) --}}
                                <td class="ps-4 py-3">
                                    <div class="d-flex align-items-center gap-2.5">
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="text-decoration-none">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-xs" 
                                                 style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); font-size: 13px;">
                                                {{ mb_substr($patientName ?: 'م', 0, 1) }}
                                            </div>
                                        </a>
                                        <div>
                                            <a href="{{ route('surgeries.show', $surgery) }}" class="fw-bold text-dark text-decoration-none hover-primary d-inline-flex align-items-center gap-1" title="عرض وتعديل تفاصيل العملية">
                                                <span>{{ $patientName ?: '-' }}</span>
                                                <i class="fas fa-external-link-alt text-muted" style="font-size: 10px;"></i>
                                            </a>
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

                                {{-- Surgery Column (Direct Clickable Link) --}}
                                <td class="py-3">
                                    <a href="{{ route('surgeries.show', $surgery) }}" class="fw-bold text-dark text-decoration-none hover-primary" title="عرض تفاصيل العملية">
                                        {{ $surgeryName ?: '-' }}
                                    </a>
                                    @if($surgery->department)
                                        <div class="text-muted small">{{ $surgery->department->name }}</div>
                                    @endif
                                </td>

                                {{-- Date & Time Column --}}
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

                                {{-- Anesthesiologist Column --}}
                                <td class="pe-4 py-3">
                                    @if($anesthetistName)
                                        <div class="d-flex align-items-center gap-1.5">
                                            <i class="fas fa-user-md text-info"></i>
                                            <span class="fw-semibold text-dark">{{ $anesthetistName }}</span>
                                        </div>
                                    @else
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="text-decoration-none" title="انقر لتعيين طبيب التخدير وتفاصيل العملية">
                                            <span class="badge bg-warning bg-opacity-15 text-warning border border-warning border-opacity-50 rounded-pill px-2.5 py-1 fw-bold">
                                                <i class="fas fa-user-plus me-1"></i> بانتظار التعيين
                                            </span>
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr id="emptyRow">
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <div class="py-4">
                                        <i class="fas fa-procedures text-muted fa-3x mb-3 opacity-25"></i>
                                        <h6 class="fw-bold text-secondary">لا توجد عمليات مطابقة للفلاتر المحددة بمحطة التخدير</h6>
                                        <p class="text-muted small mb-0">تظهر هنا تلقائياً العمليات المحالة للتخدير</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                        <tr id="noResultsClientRow" style="display: none;">
                            <td colspan="4" class="text-center py-5 text-muted">
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
