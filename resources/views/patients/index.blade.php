<!-- resources/views/patients/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-injured me-2"></i>
                    إدارة المرضى
                </h2>
                @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('create patients'))
                <a href="{{ route('patients.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة مريض جديد
                </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- شريط البحث والفلاتر الذكية -->
    <div class="card shadow-sm border-0 mb-4 bg-light">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <!-- البحث المباشر -->
                <div class="col-xl-4 col-lg-4 col-md-12">
                    <div class="input-group">
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

                <!-- فلتر الموظف المنشئ -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-user-edit text-primary"></i></span>
                        <select id="creator-filter" class="form-select form-select-sm" title="فلترة بحسب الموظف المدخل">
                            <option value="">كل الموظفين المدخلين</option>
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
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-venus-mars text-info"></i></span>
                        <select id="gender-filter" class="form-select form-select-sm" title="فلترة بحسب الجنس">
                            <option value="">الجنس (الكل)</option>
                            <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>ذكر</option>
                            <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>أنثى</option>
                        </select>
                    </div>
                </div>

                <!-- فلتر التاريخ وإعادة الضبط -->
                <div class="col-xl-2 col-lg-2 col-md-3 col-sm-6">
                    <div class="d-flex gap-1 align-items-center">
                        <div class="input-group input-group-sm flex-grow-1">
                            <span class="input-group-text bg-white"><i class="fas fa-calendar-day text-secondary"></i></span>
                            <select id="date-filter" class="form-select form-select-sm" title="فلترة بحسب تاريخ الإدخال">
                                <option value="">كل التواريخ</option>
                                <option value="today" {{ request('date_filter') == 'today' ? 'selected' : '' }}>📅 اليوم</option>
                                <option value="yesterday" {{ request('date_filter') == 'yesterday' ? 'selected' : '' }}>أمس</option>
                                <option value="this_week" {{ request('date_filter') == 'this_week' ? 'selected' : '' }}>هذا الأسبوع</option>
                                <option value="this_month" {{ request('date_filter') == 'this_month' ? 'selected' : '' }}>هذا الشهر</option>
                                <option value="custom" {{ (request('date_from') || request('date_to') || request('date_filter') == 'custom') ? 'selected' : '' }}>تحديد فترة ⏳</option>
                            </select>
                        </div>
                        <button type="button" id="reset-filters" class="btn btn-sm btn-outline-secondary" title="إعادة تعيين الفلاتر">
                            <i class="fas fa-undo"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- صف التواريخ المخصصة (من - إلى) يظهر عند اختيار "تحديد فترة" -->
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
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="patients-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;">#</th>
                                    <th>المريض</th>
                                    <th>معلومات الاتصال</th>
                                    <th>الضمان الصحي ورقم الضمان</th>
                                    <th class="table-primary bg-opacity-25" style="border-bottom: 2px solid #3b82f6;">
                                        <i class="fas fa-sort-amount-down-alt text-primary me-1"></i> تاريخ الإدخال والمنشئ
                                        <span class="badge bg-primary text-white ms-1" style="font-size: 0.68rem;">تنازلي ⬇️</span>
                                    </th>
                                    <th>الزيارات</th>
                                    <th class="text-center" style="width: 140px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="patients-tbody">
                                @forelse($patients as $patient)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold fs-5">
                                                {{ $patient->user ? mb_substr($patient->user->name, 0, 1) : '?' }}
                                            </div>
                                            <div>
                                                <strong class="text-dark d-block">{{ $patient->user ? $patient->user->name : 'مريض بدون بيانات' }}</strong>
                                                <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                                    <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">
                                                        {{ $patient->user && $patient->user->gender == 'male' ? 'ذكر' : ($patient->user && $patient->user->gender == 'female' ? 'أنثى' : 'غير محدد') }}
                                                    </span>
                                                    @if($patient->age)
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.72rem;">{{ $patient->age }} سنة</span>
                                                    @endif
                                                    @if($patient->blood_type)
                                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.72rem;">{{ $patient->blood_type }}</span>
                                                    @endif
                                                </div>
                                                @if($patient->national_id)
                                                    <small class="text-muted font-monospace d-block mt-1" style="font-size: 0.75rem;">
                                                        <i class="fas fa-fingerprint me-1"></i>{{ $patient->national_id }}
                                                    </small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small class="d-block text-dark fw-bold">
                                            <i class="fas fa-phone me-1 text-primary"></i>{{ $patient->user ? $patient->user->phone : 'غير متوفر' }}
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
                                    <td>
                                        @php
                                            $isInsured = $patient->insurance_type !== 'none' || $patient->covered_by_insurance;
                                            $insNum = $patient->insurance_card_no ?: ($patient->insurance_number ?: $patient->insurance_booklet_number);
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

                                        <div class="mt-1">
                                            @if($insNum)
                                                <span class="badge bg-white text-dark border font-monospace px-2 py-1" title="رقم الضمان / البطاقة">
                                                    <i class="fas fa-id-card text-muted me-1"></i>{{ $insNum }}
                                                </span>
                                            @else
                                                <small class="text-muted">-</small>
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
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('edit patients'))
                                            <a href="{{ route('patients.edit', $patient) }}" 
                                               class="btn btn-warning rounded-2 me-1" title="تعديل">
                                                <i class="fas fa-user-edit"></i>
                                            </a>
                                            @endif
                                            @if(Auth::user()->hasRole(['admin', 'admin-hsop', 'hospital_admin']) || Auth::user()->can('delete patients'))
                                            <form action="{{ route('patients.destroy', $patient) }}" 
                                               method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger rounded-2" 
                                                        title="حذف" onclick="return confirm('هل أنت متأكد من حذف المريض؟')">
                                                    <i class="fas fa-user-times"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-results-row">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-user-injured fa-3x mb-3"></i>
                                        <br>
                                        لا توجد مرضى مسجلين حتى الآن
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- الترقيم -->
                    <div class="d-flex justify-content-center mt-4" id="pagination-container">
                        {{ $patients->links('vendor.pagination.bootstrap-5') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
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

    // قراءة CSRF token من meta tag دائماً (لا من قيمة ثابتة)
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

    // تحديث الجدول
    function updateTable(patients) {
        // إزالة الصف "لا توجد نتائج" إذا كان موجوداً
        if (noResultsRow) {
            noResultsRow.remove();
        }

        // مسح الجدول
        patientsTbody.innerHTML = '';

        if (patients.length === 0) {
            // إضافة صف "لا توجد نتائج"
            const noResultsRow = document.createElement('tr');
            noResultsRow.innerHTML = `
                <td colspan="7" class="text-center text-muted py-4">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <br>
                    لا توجد نتائج للبحث
                </td>
            `;
            patientsTbody.appendChild(noResultsRow);
            return;
        }

        // إضافة الصفوف الجديدة
        patients.forEach((patient, index) => {
            // شارة الضمان ورقم البطاقة
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
                : `<div class="mt-1"><small class="text-muted">-</small></div>`;

            const creatorHtml = patient.creator_name || 'النظام';
            const createdAtHtml = patient.created_at_formatted ? patient.created_at_formatted.split(' ') : ['-', ''];

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="fw-bold text-muted">${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-circle me-2 d-flex align-items-center justify-content-center fw-bold fs-5">
                            ${patient.user ? patient.user.name.charAt(0) : '?'}
                        </div>
                        <div>
                            <strong class="text-dark d-block">${patient.user ? patient.user.name : 'مريض بدون بيانات'}</strong>
                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;">
                                    ${patient.user && patient.user.gender === 'male' ? 'ذكر' : (patient.user && patient.user.gender === 'female' ? 'أنثى' : 'غير محدد')}
                                </span>
                                ${patient.age ? `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25" style="font-size: 0.72rem;">${patient.age} سنة</span>` : ''}
                                ${patient.blood_type ? `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" style="font-size: 0.72rem;">${patient.blood_type}</span>` : ''}
                            </div>
                            ${patient.national_id ? `<small class="text-muted font-monospace d-block mt-1" style="font-size: 0.75rem;"><i class="fas fa-fingerprint me-1"></i>${patient.national_id}</small>` : ''}
                        </div>
                    </div>
                </td>
                <td>
                    <small class="d-block text-dark fw-bold">
                        <i class="fas fa-phone me-1 text-primary"></i>${patient.user ? patient.user.phone : 'غير متوفر'}
                    </small>
                    ${patient.emergency_contact ? `<small class="d-block text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-phone-alt me-1 text-danger"></i>طوارئ: ${patient.emergency_contact}</small>` : ''}
                    ${patient.user && patient.user.email && !patient.user.email.includes('@hospital.local') ? `<small class="d-block text-muted mt-1" style="font-size: 0.75rem;"><i class="fas fa-envelope me-1 text-secondary"></i>${patient.user.email}</small>` : ''}
                </td>
                <td>
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
                <td class="text-center">
                    <div class="btn-group btn-group-sm">
                        <a href="${patientsBaseUrl}/${patient.id}/edit" class="btn btn-warning rounded-2 me-1" title="تعديل">
                            <i class="fas fa-user-edit"></i>
                        </a>
                        <button type="button" class="btn btn-danger rounded-2 btn-delete-patient" data-patient-id="${patient.id}" title="حذف">
                            <i class="fas fa-user-times"></i>
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

        // إضافة event listeners لروابط الترقيم
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
    [creatorFilter, insuranceFilter, genderFilter].forEach(el => {
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

    // حذف المريض عبر نموذج HTML مخفي (أكثر موثوقية ضد 419)
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
});
</script>
@endsection