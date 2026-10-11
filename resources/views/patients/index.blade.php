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

    <!-- شريط البحث -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" id="search-input" class="form-control" placeholder="ابحث باسم المريض، الهاتف، البريد أو الرقم الوطني..." value="{{ request('search') }}">
                <button class="btn btn-outline-secondary" type="button" id="clear-search" title="مسح البحث">
                    <i class="fas fa-times"></i>
                </button>
                <div class="input-group-text d-none" id="search-loading">
                    <div class="spinner-border spinner-border-sm" role="status">
                        <span class="visually-hidden">جاري البحث...</span>
                    </div>
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

    // دالة البحث
    function performSearch(searchTerm) {
        performSearchWithPage(searchTerm, 1);
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
                const page = url.searchParams.get('page');
                const searchTerm = searchInput.value.trim();

                if (searchTerm) {
                    performSearchWithPage(searchTerm, page);
                } else {
                    window.location.href = this.href;
                }
            });
        });
    }

    // البحث مع رقم الصفحة
    function performSearchWithPage(searchTerm, page) {
        page = page || 1;
        document.getElementById('search-loading').classList.remove('d-none');
        patientsTable.classList.add('search-loading');

        var params = new URLSearchParams();
        params.set('search', searchTerm);
        params.set('page', page);

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
            console.error('خطأ في البحث:', err);
            document.getElementById('search-loading').classList.add('d-none');
            patientsTable.classList.remove('search-loading');
        });
    }

    // البحث التلقائي
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();

        // مسح المهلة السابقة
        clearTimeout(searchTimeout);

        // انتظار 300ms قبل البحث لتجنب الطلبات المتكررة
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });

    // مسح البحث
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        performSearch('');
        searchInput.focus();
    });

    // البحث عند الضغط على Enter
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const searchTerm = this.value.trim();
            performSearch(searchTerm);
        }
    });

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