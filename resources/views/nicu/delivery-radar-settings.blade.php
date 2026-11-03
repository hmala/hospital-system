@extends('layouts.app')

@section('title', 'إعدادات العمليات المشمولة برادار الخدج والولادة')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="radarSettingsApp">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="p-2 rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                    <i class="fas fa-sliders-h fa-lg"></i>
                </span>
                <div>
                    <h3 class="mb-0 fw-bold text-dark">
                        إعدادات العمليات المشمولة برادار الخدج
                    </h3>
                    <p class="text-muted small mb-0">تحكم ديناميكي كامل بالعمليات التي تظهر في شاشة مراقبة واستعداد قسم الخدج (قيصريات، ولادات، عمليات طارئة)</p>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('nicu.delivery-radar.index') }}" class="btn btn-outline-primary shadow-sm rounded-3">
                <i class="fas fa-arrow-right me-1"></i>
                العودة لرادار العمليات
            </a>
            <a href="{{ route('surgical-operations.create') }}" class="btn btn-success shadow-sm rounded-3">
                <i class="fas fa-plus-circle me-1"></i>
                إضافة عملية جديدة
            </a>
        </div>
    </div>

    {{-- رسالة نجاح عائمة (Toast) --}}
    <div id="radarToast" class="alert alert-success border-0 shadow-lg position-fixed bottom-0 start-50 translate-middle-x mb-4 rounded-3 d-none" style="z-index: 1080; min-width: 320px;" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fs-5 me-2"></i>
            <span id="radarToastMsg" class="fw-bold"></span>
        </div>
    </div>

    {{-- بطاقات المؤشرات السريعة --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">العمليات المفعلة بالرادار</span>
                        <h3 class="fw-bold mb-0 text-danger" id="totalActiveRadarOps">{{ $radarOperationsCount }}</h3>
                        <small class="text-muted">تظهر تلقائياً لكادر الخدج</small>
                    </div>
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3">
                        <i class="fas fa-satellite-dish fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">عمليات النسائية والتوليد</span>
                        <h3 class="fw-bold mb-0 text-primary">
                            {{ isset($operationsByCategory['النسائيه والتوليد']) ? $operationsByCategory['النسائيه والتوليد']->count() : (isset($operationsByCategory['النسائية والتوليد']) ? $operationsByCategory['النسائية والتوليد']->count() : 0) }}
                        </h3>
                        <small class="text-muted">عمليات مسجلة بالنظام</small>
                    </div>
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3">
                        <i class="fas fa-baby fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي دليل العمليات</span>
                        <h3 class="fw-bold mb-0 text-dark">{{ $operations->count() }}</h3>
                        <small class="text-muted">عبر كافة أقسام المستشفى</small>
                    </div>
                    <div class="p-3 bg-light text-secondary rounded-3">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول العمليات وأزرار التفعيل --}}
    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
        {{-- شريط البحث والفلترة حسب الفئة --}}
        <div class="row g-3 align-items-center mb-4">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0 text-muted">
                        <i class="fas fa-search"></i>
                    </span>
                    <input type="text" 
                           id="settingsSearchInput" 
                           class="form-control border-start-0 shadow-none" 
                           placeholder="ابحث باسم العملية أو الصنف...">
                </div>
            </div>
            <div class="col-md-4">
                <select id="settingsCategoryFilter" class="form-select shadow-none">
                    <option value="">جميع الأقسام والأصناف ({{ $operations->count() }})</option>
                    @foreach($operationsByCategory as $catName => $catOps)
                        <option value="{{ $catName }}" {{ str_contains($catName, 'توليد') || str_contains($catName, 'نساء') ? 'selected' : '' }}>
                            {{ $catName }} ({{ $catOps->count() }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select id="settingsStatusFilter" class="form-select shadow-none">
                    <option value="">كافة حالات الرادار</option>
                    <option value="active">مفعلة بالرادار فقط</option>
                    <option value="inactive">مستبعدة فقط</option>
                </select>
            </div>
        </div>

        {{-- الجدول --}}
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">#</th>
                        <th style="min-width: 260px;">اسم العملية الجراحية</th>
                        <th style="min-width: 180px;">الصنف / القسم</th>
                        <th class="text-center" style="width: 140px;">الظهور بالرادار</th>
                        <th style="min-width: 180px;">الحالة التوضيحية</th>
                    </tr>
                </thead>
                <tbody id="settingsTableBody">
                    @foreach($operations as $op)
                        @php
                            $isObs = str_contains($op->category, 'توليد') || str_contains($op->category, 'نساء');
                        @endphp
                        <tr class="settings-op-row {{ $op->show_in_nicu_radar ? 'table-danger bg-opacity-10' : '' }}"
                            data-name="{{ mb_strtolower($op->name) }}"
                            data-category="{{ $op->category }}"
                            data-radar="{{ $op->show_in_nicu_radar ? 'active' : 'inactive' }}"
                            id="opRow-{{ $op->id }}">
                            
                            {{-- ID --}}
                            <td>
                                <span class="badge bg-light text-muted border">#{{ $op->id }}</span>
                            </td>

                            {{-- اسم العملية --}}
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="p-2 {{ $isObs ? 'bg-danger bg-opacity-10 text-danger' : 'bg-primary bg-opacity-10 text-primary' }} rounded-3 me-2 flex-shrink-0">
                                        <i class="fas {{ $isObs ? 'fa-baby' : 'fa-procedures' }}"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-dark fs-6">{{ $op->name }}</span>
                                        @if(!$op->is_active)
                                            <span class="badge bg-secondary ms-1 small">معطلة بالقوائم العامة</span>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- الصنف --}}
                            <td>
                                <span class="badge {{ $isObs ? 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25' : 'bg-light text-dark border' }} px-3 py-2 rounded-pill">
                                    <i class="fas fa-folder me-1"></i>
                                    {{ $op->category }}
                                </span>
                            </td>

                            {{-- مفتاح التبديل الفوري --}}
                            <td class="text-center">
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input radar-toggle-switch" 
                                           type="checkbox" 
                                           style="width: 2.8em; height: 1.4em; cursor: pointer;"
                                           data-id="{{ $op->id }}"
                                           data-name="{{ $op->name }}"
                                           {{ $op->show_in_nicu_radar ? 'checked' : '' }}>
                                </div>
                            </td>

                            {{-- شارة الحالة --}}
                            <td id="statusCol-{{ $op->id }}">
                                @if($op->show_in_nicu_radar)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-bold">
                                        <i class="fas fa-check-circle me-1"></i> تظهر في رادار الخدج
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                        <i class="fas fa-eye-slash me-1"></i> مستبعدة من الرادار
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr id="settingsNoResultsRow" style="display: none;">
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="fas fa-search fa-3x mb-3 d-block text-secondary opacity-50"></i>
                            <h6 class="fw-bold text-dark">لا توجد عمليات تطابق معايير الفلترة المحددة</h6>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- سكريبت الفلترة والتعديل الفوري عبر AJAX --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('settingsSearchInput');
    const categoryFilter = document.getElementById('settingsCategoryFilter');
    const statusFilter = document.getElementById('settingsStatusFilter');
    const rows = document.querySelectorAll('.settings-op-row');
    const noResultsRow = document.getElementById('settingsNoResultsRow');
    const totalActiveSpan = document.getElementById('totalActiveRadarOps');
    const toast = document.getElementById('radarToast');
    const toastMsg = document.getElementById('radarToastMsg');

    let toastTimer = null;
    function showToast(message, isError = false) {
        if (!toast) return;
        toast.className = isError 
            ? 'alert alert-danger border-0 shadow-lg position-fixed bottom-0 start-50 translate-middle-x mb-4 rounded-3'
            : 'alert alert-success border-0 shadow-lg position-fixed bottom-0 start-50 translate-middle-x mb-4 rounded-3';
        toastMsg.textContent = message;
        toast.classList.remove('d-none');

        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.add('d-none');
        }, 3000);
    }

    // تصفية الصفوف
    function filterRows() {
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const cat = categoryFilter ? categoryFilter.value : '';
        const status = statusFilter ? statusFilter.value : '';

        let visibleCount = 0;

        rows.forEach(row => {
            const name = row.getAttribute('data-name') || '';
            const rowCat = row.getAttribute('data-category') || '';
            const rowRadar = row.getAttribute('data-radar') || '';

            const matchesQuery = !query || name.includes(query) || rowCat.toLowerCase().includes(query);
            const matchesCat = !cat || rowCat === cat;
            const matchesStatus = !status || rowRadar === status;

            if (matchesQuery && matchesCat && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (noResultsRow) {
            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('input', filterRows);
    if (categoryFilter) categoryFilter.addEventListener('change', filterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRows);

    // تطبيق الفلتر الافتراضي عند فتح الصفحة
    filterRows();

    // التعامل مع التبديل الفوري بالـ Switch
    document.querySelectorAll('.radar-toggle-switch').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const opId = this.getAttribute('data-id');
            const opName = this.getAttribute('data-name');
            const isChecked = this.checked;
            const row = document.getElementById('opRow-' + opId);
            const statusCol = document.getElementById('statusCol-' + opId);

            // تعطيل التبديل مؤقتاً أثناء الإرسال
            this.disabled = true;

            const toggleUrl = "{{ url('nicu/delivery-radar/toggle') }}/" + opId;

            fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                toggle.disabled = false;

                if (data.success) {
                    // تحديث شارة الحالة
                    if (data.show_in_nicu_radar) {
                        row.setAttribute('data-radar', 'active');
                        row.classList.add('table-danger', 'bg-opacity-10');
                        statusCol.innerHTML = `
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill fw-bold">
                                <i class="fas fa-check-circle me-1"></i> تظهر في رادار الخدج
                            </span>
                        `;
                    } else {
                        row.setAttribute('data-radar', 'inactive');
                        row.classList.remove('table-danger', 'bg-opacity-10');
                        statusCol.innerHTML = `
                            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                                <i class="fas fa-eye-slash me-1"></i> مستبعدة من الرادار
                            </span>
                        `;
                    }

                    // تحديث العداد
                    if (totalActiveSpan) {
                        let currentCount = parseInt(totalActiveSpan.textContent) || 0;
                        currentCount = data.show_in_nicu_radar ? currentCount + 1 : Math.max(0, currentCount - 1);
                        totalActiveSpan.textContent = currentCount;
                    }

                    showToast(data.message);
                } else {
                    // التراجع عن التحديد
                    toggle.checked = !isChecked;
                    showToast(data.message || 'حدث خطأ أثناء تعديل الحالة', true);
                }
            })
            .catch(() => {
                toggle.disabled = false;
                toggle.checked = !isChecked;
                showToast('تعذر الاتصال بالسيرفر. يرجى المحاولة لاحقاً', true);
            });
        });
    });
});
</script>
@endsection
