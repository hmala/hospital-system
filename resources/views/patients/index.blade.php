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
                <a href="{{ route('patients.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة مريض جديد
                </a>
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
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>معلومات الاتصال</th>
                                    <th>العمر</th>
                                    <th>فصيلة الدم</th>
                                    <th>رقم الطوارئ</th>
                                    <th>عدد الزيارات</th>
                                    <th>آخر زيارة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="patients-tbody">
                                @forelse($patients as $patient)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <span class="text-white fw-bold">
                                                    {{ $patient->user ? substr($patient->user->name, 0, 1) : '?' }}
                                                </span>
                                            </div>
                                            <div>
                                                <strong>{{ $patient->user ? $patient->user->name : 'مريض بدون بيانات' }}</strong>
                                                <br>
                                                <small class="text-muted">
                                                    {{ $patient->user && $patient->user->gender == 'male' ? 'ذكر' : ($patient->user && $patient->user->gender == 'female' ? 'أنثى' : 'غير محدد') }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-phone me-1 text-muted"></i>{{ $patient->user ? $patient->user->phone : 'غير متوفر' }}<br>
                                            <i class="fas fa-envelope me-1 text-muted"></i>{{ $patient->user ? $patient->user->email : 'غير متوفر' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($patient->age)
                                            <span class="badge bg-info">{{ $patient->age }} سنة</span>
                                        @else
                                            <span class="text-muted">---</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($patient->blood_type)
                                            <span class="badge bg-danger">{{ $patient->blood_type }}</span>
                                        @else
                                            <span class="text-muted">---</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $patient->emergency_contact ?? '---' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $patient->total_appointments }}</span>
                                    </td>
                                    <td>
                                        @if($patient->getLastVisitDate())
                                            <small class="text-success">
                                                {{ $patient->getLastVisitDate() ? $patient->getLastVisitDate()->format('Y-m-d') : 'لا توجد' }}
                                            </small>
                                        @else
                                            <span class="text-muted">لا توجد زيارات</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('patients.edit', $patient) }}" 
                                           class="btn btn-warning btn-sm rounded-3 me-1" title="تعديل">
                                            <i class="fas fa-user-edit me-1"></i>تعديل
                                        </a>
                                        <form action="{{ route('patients.destroy', $patient) }}" 
                                              method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm rounded-3" 
                                                    title="حذف" onclick="return confirm('هل أنت متأكد من حذف المريض؟')">
                                                <i class="fas fa-user-times me-1"></i>حذف
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr id="no-results-row">
                                    <td colspan="9" class="text-center text-muted py-4">
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
                <td colspan="9" class="text-center text-muted py-4">
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
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${index + 1}</td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                            <span class="text-white fw-bold">
                                ${patient.user ? patient.user.name.charAt(0) : '?'}
                            </span>
                        </div>
                        <div>
                            <strong>${patient.user ? patient.user.name : 'مريض بدون بيانات'}</strong>
                            <br>
                            <small class="text-muted">
                                ${patient.user && patient.user.gender === 'male' ? 'ذكر' : (patient.user && patient.user.gender === 'female' ? 'أنثى' : 'غير محدد')}
                            </small>
                        </div>
                    </div>
                </td>
                <td>
                    <small>
                        <i class="fas fa-phone me-1 text-muted"></i>${patient.user ? patient.user.phone : 'غير متوفر'}<br>
                        <i class="fas fa-envelope me-1 text-muted"></i>${patient.user ? patient.user.email : 'غير متوفر'}
                    </small>
                </td>
                <td>
                    ${patient.age ? `<span class="badge bg-info">${patient.age} سنة</span>` : '<span class="text-muted">---</span>'}
                </td>
                <td>
                    ${patient.blood_type ? `<span class="badge bg-danger">${patient.blood_type}</span>` : '<span class="text-muted">---</span>'}
                </td>
                <td>
                    <small>${patient.emergency_contact || '---'}</small>
                </td>
                <td>
                    <span class="badge bg-primary">${patient.total_appointments || 0}</span>
                </td>
                <td>
                    ${patient.last_visit_date ? `<small class="text-success">${patient.last_visit_date}</small>` : '<span class="text-muted">لا توجد زيارات</span>'}
                </td>
                <td>
                    <a href="${patientsBaseUrl}/${patient.id}/edit" class="btn btn-warning btn-sm rounded-3 me-1" title="تعديل">
                        <i class="fas fa-user-edit me-1"></i>تعديل
                    </a>
                    <button type="button" class="btn btn-danger btn-sm rounded-3 btn-delete-patient" data-patient-id="${patient.id}" title="حذف">
                        <i class="fas fa-user-times me-1"></i>حذف
                    </button>
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