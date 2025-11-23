@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-search me-2"></i>
                البحث عن مريض
            </h2>
            <p class="text-muted">ابحث عن المريض بالاسم أو رقم الهاتف لإنشاء طلب جديد</p>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <div class="mb-4">
                        <label class="form-label h5">
                            <i class="fas fa-user-search me-2"></i>
                            ابحث عن المريض
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="patientSearch" 
                               placeholder="اكتب اسم المريض أو رقم الهاتف..."
                               autocomplete="off">
                    </div>

                    <!-- نتائج البحث -->
                    <div id="searchResults" class="list-group" style="display: none;"></div>

                    <!-- حالة فارغة -->
                    <div id="emptyState" class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">ابدأ بكتابة اسم المريض للبحث</h5>
                    </div>

                    <!-- حالة التحميل -->
                    <div id="loadingState" class="text-center py-5" style="display: none;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري البحث...</span>
                        </div>
                        <p class="text-muted mt-2">جاري البحث...</p>
                    </div>

                    <!-- لا توجد نتائج -->
                    <div id="noResults" class="text-center py-5" style="display: none;">
                        <i class="fas fa-user-times fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">لا توجد نتائج</h5>
                        <p class="text-muted">لم نجد أي مريض بهذا الاسم أو رقم الهاتف</p>
                        <a href="{{ route('patients.create') }}" class="btn btn-primary mt-3">
                            <i class="fas fa-user-plus me-1"></i>
                            إضافة مريض جديد
                        </a>
                    </div>
                </div>
            </div>

            <!-- روابط سريعة -->
            <div class="mt-4 text-center">
                <a href="{{ route('inquiry.index') }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للرئيسية
                </a>
                <a href="{{ route('patients.create') }}" class="btn btn-outline-primary">
                    <i class="fas fa-user-plus me-1"></i>
                    تسجيل مريض جديد
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let searchTimeout;
const searchInput = document.getElementById('patientSearch');
const searchResults = document.getElementById('searchResults');
const emptyState = document.getElementById('emptyState');
const loadingState = document.getElementById('loadingState');
const noResults = document.getElementById('noResults');

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    // مسح timeout السابق
    clearTimeout(searchTimeout);
    
    // إخفاء جميع الحالات
    searchResults.style.display = 'none';
    emptyState.style.display = 'none';
    loadingState.style.display = 'none';
    noResults.style.display = 'none';
    
    if (query.length < 2) {
        emptyState.style.display = 'block';
        return;
    }
    
    // عرض حالة التحميل
    loadingState.style.display = 'block';
    
    // انتظار 500ms قبل البحث
    searchTimeout = setTimeout(() => {
        fetch(`{{ route('inquiry.search.patients') }}?query=${encodeURIComponent(query)}`)
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Search results:', data);
                loadingState.style.display = 'none';
                
                if (data.length === 0) {
                    noResults.style.display = 'block';
                    return;
                }
                
                // عرض النتائج
                searchResults.innerHTML = '';
                data.forEach(patient => {
                    const item = document.createElement('a');
                    item.href = `{{ route('inquiry.create') }}?patient_id=${patient.id}`;
                    item.className = 'list-group-item list-group-item-action';
                    
                    const phone = patient.user.phone || 'غير متوفر';
                    const age = patient.age || 'غير محدد';
                    
                    item.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">
                                    <i class="fas fa-user me-2"></i>
                                    ${patient.user.name}
                                </h6>
                                <p class="mb-0 text-muted small">
                                    <i class="fas fa-phone me-1"></i>${phone}
                                </p>
                            </div>
                            <div>
                                <span class="badge bg-primary">${age} سنة</span>
                            </div>
                        </div>
                    `;
                    searchResults.appendChild(item);
                });
                
                searchResults.style.display = 'block';
            })
            .catch(error => {
                console.error('خطأ في البحث:', error);
                loadingState.style.display = 'none';
                noResults.style.display = 'block';
            });
    }, 500);
});
</script>
@endsection
