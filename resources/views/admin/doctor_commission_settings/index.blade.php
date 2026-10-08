@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- ترويسة الصفحة -->
    <div class="row mb-3 align-items-center">
        <div class="col-lg-7">
            <h2 class="h4 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success p-2 rounded-3">
                    <i class="fas fa-file-invoice-dollar"></i>
                </span>
                إعدادات عمولات الأطباء وحصص الكشفية
            </h2>
            <p class="text-muted small mb-0">
                تحديد حصة الطبيب والمستشفى من كشفية الاستشارية مع المعاينة التلقائية المباشرة والحفظ الفوري.
            </p>
        </div>
        <div class="col-lg-5 d-flex justify-content-lg-end gap-2 mt-3 mt-lg-0 flex-wrap">
            <button type="button" class="btn btn-success btn-sm px-3 shadow-xs fw-bold" onclick="saveAllRows()" id="btnSaveAll">
                <i class="fas fa-save me-1"></i> حفظ جميع التعديلات
            </button>
            <a href="{{ route('admin.doctor-commission-settings.create') }}" class="btn btn-outline-primary btn-sm px-3 shadow-xs">
                <i class="fas fa-plus me-1"></i> إعداد مخصص
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات الإحصاءات السريعة KPI Cards -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">إجمالي الأطباء</span>
                        <h4 class="fw-bold mb-0 text-dark">{{ $totalDoctors ?? $doctors->total() }}</h4>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fas fa-user-md fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">عمولات محددة</span>
                        <h4 class="fw-bold mb-0 text-success">{{ $assignedDoctors ?? 0 }}</h4>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fas fa-check-double fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">غير محددة</span>
                        <h4 class="fw-bold mb-0 {{ ($unassignedDoctors ?? 0) > 0 ? 'text-warning' : 'text-muted' }}">{{ $unassignedDoctors ?? 0 }}</h4>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                        <i class="fas fa-exclamation-triangle fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-xs bg-white h-100">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block mb-1">أطباء نشطون</span>
                        <h4 class="fw-bold mb-0 text-info">{{ $activeDoctors ?? 0 }}</h4>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fas fa-stethoscope fs-5"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط الفلترة والبحث -->
    <div class="card border-0 shadow-xs mb-3 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('admin.doctor-commission-settings.index') }}" class="row g-2 align-items-center" id="filterForm">
                <!-- البحث النصي -->
                <div class="col-md-5 col-12">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="q" value="{{ old('q', $q ?? request('q')) }}" class="form-control border-start-0 ps-0" placeholder="ابحث باسم الطبيب، القسم، أو المعرف...">
                    </div>
                </div>

                <!-- فلترة بأقسام الاستشارية -->
                <div class="col-md-4 col-6">
                    <select name="department_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- كافة أقسام الاستشارية ({{ $departments->count() }}) --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->name }} ({{ $dept->consultant_doctors_count }} أطباء)
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- فلترة بحالة العمولة -->
                <div class="col-md-2 col-6">
                    <select name="status_filter" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                        <option value="">-- حالة العمولة --</option>
                        <option value="assigned" {{ request('status_filter') == 'assigned' ? 'selected' : '' }}>محددة ✅</option>
                        <option value="unassigned" {{ request('status_filter') == 'unassigned' ? 'selected' : '' }}>غير محددة ⚠️</option>
                    </select>
                </div>

                <!-- زر الإلغاء والبحث -->
                <div class="col-md-1 col-12 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="تطبيق الفلترة">
                        <i class="fas fa-filter"></i>
                    </button>
                    @if(request()->hasAny(['q', 'department_id', 'status_filter']))
                        <a href="{{ route('admin.doctor-commission-settings.index') }}" class="btn btn-outline-secondary btn-sm" title="إلغاء الفلاتر">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول عمولات الأطباء وحصص المستشفى -->
    <div class="card border-0 shadow-sm mb-4">
        <form id="doctor-commission-settings-form" method="POST" action="{{ route('admin.doctor-commission-settings.save') }}">
            @csrf
            <input type="hidden" name="save_mode" id="form_save_mode" value="all">
            <input type="hidden" name="doctor_row" id="form_doctor_row" value="">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="commissionTable">
                    <thead class="table-light text-secondary small text-nowrap">
                        <tr>
                            <th style="width: 50px;" class="text-center">#</th>
                            <th>الطبيب والقسم</th>
                            <th style="width: 140px;" class="text-center">أجرة الكشفية</th>
                            <th style="width: 200px;" class="text-center">حصة الطبيب (د.ع)</th>
                            <th style="width: 220px;" class="text-center">حصة المستشفى (معاينة)</th>
                            <th style="width: 100px;" class="text-center">حالة الطبيب</th>
                            <th style="width: 120px;" class="text-center">حفظ فوري</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($doctors as $index => $doctor)
                            @php
                                $commission = $doctor->currentCommissionSetting;
                                $docFee = (float) ($doctor->consultation_fee ?? 0);
                                $docShare = $commission && $commission->fixed_amount !== null ? (float) $commission->fixed_amount : 0;
                                $hospShare = max(0, $docFee - $docShare);
                                $hospPercent = $docFee > 0 ? round(($hospShare / $docFee) * 100) : 0;
                                $docPercent = $docFee > 0 ? round(($docShare / $docFee) * 100) : 0;
                            @endphp
                            <tr class="doctor-row-item" data-doctor-id="{{ $doctor->id }}" data-fee="{{ $docFee }}">
                                <!-- معرف الطبيب -->
                                <td class="text-center font-monospace text-muted small">
                                    {{ $doctor->id }}
                                    <input type="hidden" name="doctor_id[]" value="{{ $doctor->id }}">
                                    <input type="hidden" name="commission_type[]" value="fixed">
                                </td>

                                <!-- الطبيب والقسم -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.9rem;">
                                            {{ mb_substr($doctor->user?->name ?? 'ط', 0, 1) }}
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block">
                                                {{ $doctor->user?->name ?? 'طبيب #' . $doctor->id }}
                                            </strong>
                                            <div class="d-flex align-items-center gap-1 flex-wrap mt-1">
                                                <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.72rem;">
                                                    <i class="fas fa-hospital me-1"></i> {{ $doctor->department?->name ?? 'غير محدد' }}
                                                </span>
                                                @if(!empty($doctor->specialization))
                                                    <span class="text-muted small" style="font-size: 0.72rem;">
                                                        • {{ $doctor->specialization }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- أجرة الكشفية الأصلية -->
                                <td class="text-center">
                                    @if($docFee > 0)
                                        <span class="badge bg-light text-dark border font-monospace px-2 py-1 fs-7">
                                            {{ number_format($docFee, 0) }} د.ع
                                        </span>
                                    @else
                                        <span class="text-muted small font-monospace">0 د.ع</span>
                                    @endif
                                </td>

                                <!-- حصة الطبيب (إدخال) -->
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" 
                                               step="500" 
                                               min="0"
                                               max="{{ $docFee > 0 ? $docFee : 9999999 }}"
                                               name="fixed_amount[]" 
                                               id="docShareInput-{{ $doctor->id }}"
                                               value="{{ $commission?->fixed_amount ?? '' }}" 
                                               class="form-control form-control-sm font-monospace text-center fw-bold doctor-share-input" 
                                               placeholder="0"
                                               data-doctor-id="{{ $doctor->id }}"
                                               oninput="onDoctorShareChange({{ $doctor->id }})"
                                               onkeydown="handleInputKeydown(event, {{ $doctor->id }})">
                                        <span class="input-group-text bg-light text-muted small px-2">د.ع</span>
                                    </div>
                                </td>

                                <!-- حصة المستشفى (معاينة حية) -->
                                <td class="text-center">
                                    <div id="hospShareBox-{{ $doctor->id }}" class="d-inline-flex align-items-center gap-1 bg-light p-1 px-2 rounded-3 border">
                                        <span class="fw-bold font-monospace text-info hosp-share-val" id="hospShareVal-{{ $doctor->id }}">
                                            {{ number_format($hospShare, 0) }} د.ع
                                        </span>
                                        <span class="badge bg-info-subtle text-info rounded-pill font-monospace hosp-share-percent" id="hospSharePercent-{{ $doctor->id }}" style="font-size: 0.7rem;">
                                            {{ $hospPercent }}%
                                        </span>
                                    </div>
                                </td>

                                <!-- حالة الطبيب -->
                                <td class="text-center">
                                    @if($doctor->is_active)
                                        <span class="badge bg-success-subtle text-success px-2 py-1 font-monospace" style="font-size: 0.72rem;">
                                            <i class="fas fa-check-circle me-1"></i> نشط
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-muted px-2 py-1 font-monospace" style="font-size: 0.72rem;">
                                            غير نشط
                                        </span>
                                    @endif
                                </td>

                                <!-- زر الحفظ الفوري السريع -->
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-success px-2 py-1 btn-save-row" 
                                            id="btnSaveRow-{{ $doctor->id }}"
                                            onclick="saveSingleRow({{ $doctor->id }}, this)"
                                            title="حفظ فوري لحصة هذا الطبيب">
                                        <i class="fas fa-save me-1"></i> حفظ
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-md fa-3x mb-3 text-muted opacity-50 d-block"></i>
                                    لا يوجد أي أطباء يطابقون خيارات البحث والفلترة.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- الترقيم والتنقل -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">
            عرض {{ $doctors->firstItem() ?? 0 }} إلى {{ $doctors->lastItem() ?? 0 }} من أصل {{ $doctors->total() }} طبيب
        </span>
        <div>
            {{ $doctors->links() }}
        </div>
    </div>
</div>

<style>
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .doctor-share-input:focus {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.15);
    }
</style>
@endsection

@section('scripts')
<script>
// 1. الاحتساب التلقائي المباشر لحصة المستشفى
window.onDoctorShareChange = function(doctorId) {
    const row = document.querySelector(`.doctor-row-item[data-doctor-id="${doctorId}"]`);
    if (!row) return;

    const fee = parseFloat(row.getAttribute('data-fee') || '0');
    const input = document.getElementById(`docShareInput-${doctorId}`);
    const docShare = parseFloat(input.value || '0');

    const hospShare = Math.max(0, fee - docShare);
    const hospPercent = fee > 0 ? Math.round((hospShare / fee) * 100) : 0;

    const hospValEl = document.getElementById(`hospShareVal-${doctorId}`);
    const hospPercentEl = document.getElementById(`hospSharePercent-${doctorId}`);

    if (hospValEl) {
        hospValEl.textContent = hospShare.toLocaleString() + ' د.ع';
    }
    if (hospPercentEl) {
        hospPercentEl.textContent = hospPercent + '%';
    }

    // تمييز بصري للسطر الذي تم تعديله
    row.classList.add('table-warning');
};

// 2. الحفظ بالضغط على زر Enter داخل حقل الإدخال
window.handleInputKeydown = function(e, doctorId) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const btn = document.getElementById(`btnSaveRow-${doctorId}`);
        if (btn) {
            window.saveSingleRow(doctorId, btn);
        }
    }
};

// 3. الحفظ الفوري لسطر طبيب واحد عبر AJAX
window.saveSingleRow = function(doctorId, btn) {
    const row = document.querySelector(`.doctor-row-item[data-doctor-id="${doctorId}"]`);
    if (!row) return;

    const input = document.getElementById(`docShareInput-${doctorId}`);
    const fixedAmount = input ? input.value : '';

    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> حفظ...';
    }

    const form = document.getElementById('doctor-commission-settings-form');
    const formData = new FormData(form);
    formData.set('save_mode', 'row');
    formData.set('doctor_row', doctorId);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.className = 'btn btn-sm btn-success px-2 py-1 btn-save-row';
            btn.innerHTML = '<i class="fas fa-check me-1"></i> تم الحفظ';
            setTimeout(() => {
                btn.className = 'btn btn-sm btn-outline-success px-2 py-1 btn-save-row';
                btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
            }, 2000);
        }

        row.classList.remove('table-warning');
        row.classList.add('table-success');
        setTimeout(() => row.classList.remove('table-success'), 1500);

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم حفظ العمولة بنجاح 💾',
                timer: 1500,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.className = 'btn btn-sm btn-outline-danger px-2 py-1 btn-save-row';
            btn.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> خطأ';
            setTimeout(() => {
                btn.className = 'btn btn-sm btn-outline-success px-2 py-1 btn-save-row';
                btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
            }, 2000);
        }
        console.error('Error saving commission setting:', err);
    });
};

// 4. حفظ جميع التعديلات في الصفحة دفعة واحدة
window.saveAllRows = function() {
    const btn = document.getElementById('btnSaveAll');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> جاري الحفظ...';
    }

    const form = document.getElementById('doctor-commission-settings-form');
    const formData = new FormData(form);
    formData.set('save_mode', 'all');

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-1"></i> تم حفظ الكل بنجاح';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ جميع التعديلات';
            }, 2000);
        }

        document.querySelectorAll('.doctor-row-item.table-warning').forEach(row => {
            row.classList.remove('table-warning');
        });

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'تم حفظ جميع العمولات بنجاح 💾',
                timer: 2000,
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ جميع التعديلات';
        }
        console.error('Error saving all commission settings:', err);
    });
};
</script>
@endsection
