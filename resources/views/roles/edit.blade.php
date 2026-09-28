@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> رجوع لقائمة الأدوار
            </a>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div id="autoSaveBadge" class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 fs-6 shadow-sm d-flex align-items-center gap-2">
                <i class="fas fa-bolt text-warning" id="autoSaveIcon"></i>
                <span id="autoSaveText">الحفظ اللحظي التلقائي مفعّل</span>
            </div>
            <button type="submit" form="rolePermissionsForm" class="btn btn-outline-primary px-3 fw-bold shadow-sm">
                <i class="fas fa-save me-1"></i> حفظ يدوي
            </button>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-xl-11 col-lg-12">
            <div class="card shadow border-0 rounded-3 overflow-hidden">
                <div class="card-header bg-gradient bg-primary text-white py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1 fw-bold">
                                <i class="fas fa-user-shield me-2"></i> تعديل صلاحيات الدور: 
                                <span class="badge bg-light text-primary px-3 py-2 ms-2 fs-6">
                                    @switch($role->name)
                                        @case('admin') مدير النظام @break
                                        @case('admin-hsop') مدير المستشفى @break
                                        @case('hospital_admin') مدير المستشفى @break
                                        @case('doctor') طبيب استشاري @break
                                        @case('patient') مريض @break
                                        @case('receptionist') موظف استقبال @break
                                        @case('cashier') كاشير الصندوق @break
                                        @case('lab_staff') موظف مختبر @break
                                        @case('radiology_staff') موظف أشعة وسونار @break
                                        @case('pharmacy_staff') موظف صيدلية @break
                                        @case('surgery_staff') موظف عمليات @break
                                        @case('nurse') كادر تمريضي @break
                                        @case('emergency_staff') موظف طوارئ @break
                                        @case('consultation_receptionist') موظف استعلامات الاستشارية @break
                                        @default {{ $role->name }}
                                    @endswitch
                                </span>
                            </h4>
                            <small class="text-white-50">تحديد وتخصيص الصلاحيات التشغيلية والإدارية الممنوحة لهذا الدور</small>
                        </div>
                        <div class="text-white-50 small">
                            رمز الدور: <code class="text-white bg-dark bg-opacity-25 px-2 py-1 rounded">{{ $role->name }}</code>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-light bg-opacity-50">
                    <form id="rolePermissionsForm" action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="display_name" value="{{ $role->name }}">

                        <!-- لوحة الإحصائيات السريعة -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 bg-white">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary me-3">
                                            <i class="fas fa-check-circle fa-2x"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 fw-bold text-primary" id="selectedCount">0</h4>
                                            <span class="text-muted small">صلاحية ممنوحة لهذا الدور</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 bg-white">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="rounded-circle bg-info bg-opacity-10 p-3 text-info me-3">
                                            <i class="fas fa-layer-group fa-2x"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 fw-bold text-info" id="totalCount">0</h4>
                                            <span class="text-muted small">إجمالي صلاحيات النظام</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm rounded-3 bg-white">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success me-3">
                                            <i class="fas fa-percentage fa-2x"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h4 class="mb-0 fw-bold text-success" id="percentageCount">0%</h4>
                                                <span class="badge bg-success bg-opacity-10 text-success small">نسبة الوصول</span>
                                            </div>
                                            <div class="progress" style="height: 6px;">
                                                <div class="progress-bar bg-success" id="progressBar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- شريط التصفية والبحث السريع -->
                        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                            <div class="card-body p-3">
                                <div class="row g-2 align-items-center">
                                    <div class="col-lg-5 col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 text-muted"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control bg-light border-0" id="searchPermissions" placeholder="بحث فوري في الصلاحيات بالاسم أو الوظيفة...">
                                        </div>
                                    </div>
                                    <div class="col-lg-7 col-md-6 text-md-end">
                                        <div class="btn-group btn-group-sm flex-wrap" role="group">
                                            <button type="button" class="btn btn-outline-primary quick-select" data-action="all">
                                                <i class="fas fa-check-double me-1"></i> تفعيل الكل
                                            </button>
                                            <button type="button" class="btn btn-outline-info quick-select" data-action="view">
                                                <i class="fas fa-eye me-1"></i> عرض فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-success quick-select" data-action="create">
                                                <i class="fas fa-plus me-1"></i> إضافة فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-warning quick-select" data-action="edit">
                                                <i class="fas fa-edit me-1"></i> تعديل فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-danger quick-select" data-action="delete">
                                                <i class="fas fa-trash me-1"></i> حذف فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary quick-select" data-action="none">
                                                <i class="fas fa-times me-1"></i> إلغاء التحديد
                                            </button>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-light border ms-2" id="expandCollapseAll">
                                            <i class="fas fa-expand-alt"></i> <span>توسيع الكل</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @error('permissions')
                            <div class="alert alert-danger alert-dismissible fade show rounded-3" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i> {{ $message }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @enderror

                        @php
                            // ترتيب الأقسام التشغيلية الـ 9
                            $sections = $moduleOrder ?? \App\Http\Controllers\RoleManagementController::getModuleOrder();
                            $defs = $permissionDefinitions ?? \App\Http\Controllers\RoleManagementController::getPermissionDefinitions();
                        @endphp

                        <!-- أقسام الصلاحيات الـ 9 -->
                        <div class="accordion" id="permissionsAccordion">
                            @foreach($sections as $moduleKey => $moduleInfo)
                            @php
                                $perms = $permissions[$moduleKey] ?? collect();
                                $collapseId = "collapse_" . $moduleKey;
                                $headerId = "heading_" . $moduleKey;
                                $totalInModule = count($perms);
                                if ($totalInModule === 0) continue;

                                $checkedInModule = 0;
                                foreach($perms as $p) {
                                    if (in_array($p->name, $rolePermissions)) {
                                        $checkedInModule++;
                                    }
                                }
                            @endphp
                            <div class="accordion-item border-0 shadow-sm rounded-3 mb-3 overflow-hidden permission-module-item" data-module="{{ $moduleKey }}">
                                <h2 class="accordion-header" id="{{ $headerId }}">
                                    <div class="accordion-button collapsed py-3 px-4 bg-white" 
                                         type="button" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#{{ $collapseId }}" 
                                         aria-expanded="false" 
                                         aria-controls="{{ $collapseId }}">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3 flex-wrap gap-2">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-3 bg-{{ $moduleInfo['color'] }} bg-opacity-10 text-{{ $moduleInfo['color'] }} p-2 me-3 fs-5">
                                                    <i class="fas {{ $moduleInfo['icon'] }}"></i>
                                                </div>
                                                <div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="fw-bold fs-6 text-dark">{{ $moduleInfo['name'] }}</span>
                                                        <span class="badge bg-{{ $moduleInfo['color'] }} bg-opacity-10 text-{{ $moduleInfo['color'] }} border border-{{ $moduleInfo['color'] }} border-opacity-25 module-badge" id="badge_{{ $moduleKey }}">
                                                            {{ $checkedInModule }} / {{ $totalInModule }}
                                                        </span>
                                                    </div>
                                                    <small class="text-muted d-block">{{ $moduleInfo['description'] }}</small>
                                                </div>
                                            </div>

                                            <div class="d-flex align-items-center gap-3 ms-auto" onclick="event.stopPropagation();">
                                                <div class="form-check form-switch m-0 d-flex align-items-center gap-2">
                                                    <input class="form-check-input select-all-module" 
                                                           type="checkbox" 
                                                           role="switch" 
                                                           id="switch_{{ $moduleKey }}"
                                                           data-module="{{ $moduleKey }}"
                                                           {{ $checkedInModule === $totalInModule ? 'checked' : '' }}
                                                           style="cursor: pointer; width: 2.8em; height: 1.4em;">
                                                    <label class="form-check-label small fw-bold text-muted cursor-pointer" for="switch_{{ $moduleKey }}">
                                                        تحديد كامل القسم
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </h2>

                                <div id="{{ $collapseId }}" 
                                     class="accordion-collapse collapse" 
                                     aria-labelledby="{{ $headerId }}" 
                                     data-bs-parent="#permissionsAccordion">
                                    <div class="accordion-body p-0 border-top bg-white">
                                        <div class="table-responsive">
                                            <table class="table table-hover align-middle mb-0">
                                                <thead class="table-light text-muted small">
                                                    <tr>
                                                        <th style="width: 50px;" class="text-center">#</th>
                                                        <th>اسم الصلاحية والوظيفة السريرية / الإدارية</th>
                                                        <th style="width: 140px;" class="text-center">نوع الإجراء</th>
                                                        <th style="width: 130px;" class="text-center">الحالة والتفعيل</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($perms as $index => $permission)
                                                    @php
                                                        $def = $defs[$permission->name] ?? null;
                                                        $permLabel = $def['label'] ?? $permission->name;
                                                        $permAction = $def['action'] ?? 'إجراء';
                                                        $permBadge = $def['badge'] ?? 'secondary';
                                                        $isChecked = in_array($permission->name, $rolePermissions);
                                                    @endphp
                                                    <tr class="permission-row {{ $isChecked ? 'table-active-row' : '' }}">
                                                        <td class="text-center text-muted small fw-semibold">
                                                            {{ $loop->iteration }}
                                                        </td>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <div class="me-2 text-{{ $permBadge }}">
                                                                    <i class="fas fa-check-circle fs-6"></i>
                                                                </div>
                                                                <div>
                                                                    <label for="perm_{{ $permission->id }}" class="fw-bold text-dark mb-0 d-block cursor-pointer permission-label">
                                                                        {{ $permLabel }}
                                                                    </label>
                                                                    <code class="text-muted small user-select-all" style="font-size: 0.78rem;">{{ $permission->name }}</code>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-{{ $permBadge }} bg-opacity-10 text-{{ $permBadge }} border border-{{ $permBadge }} border-opacity-25 px-2 py-1">
                                                                {{ $permAction }}
                                                            </span>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-switch d-inline-block m-0">
                                                                <input class="form-check-input permission-checkbox" 
                                                                       type="checkbox" 
                                                                       name="permissions[]" 
                                                                       value="{{ $permission->name }}" 
                                                                       id="perm_{{ $permission->id }}"
                                                                       data-module="{{ $moduleKey }}"
                                                                       {{ $isChecked ? 'checked' : '' }}
                                                                       style="cursor: pointer; width: 2.5em; height: 1.3em;">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- أزرار الحفظ السفلية -->
                        <div class="card border-0 shadow-sm rounded-3 mt-4 bg-white">
                            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <span class="text-muted small">
                                    <i class="fas fa-info-circle text-primary me-1"></i> عند الحفظ، سيتم تحديث الصلاحيات فوراً وتفريغ الكاش لجميع مستخدمي هذا الدور.
                                </span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary px-3">
                                        <i class="fas fa-times me-1"></i> إلغاء
                                    </a>
                                    <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                                        <i class="fas fa-save me-1"></i> حفظ التعديلات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- حاوية التنبيه اللحظي الفوري -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="permissionLiveToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2 py-2 px-3" id="liveToastMessage">
                <i class="fas fa-check-circle text-success fs-5"></i>
                <span>تم تحديث الصلاحية فورياً</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<style>
    .cursor-pointer { cursor: pointer; }
    .table-active-row {
        background-color: rgba(13, 110, 253, 0.04) !important;
    }
    .accordion-button:not(.collapsed) {
        background-color: #f8fafc !important;
        box-shadow: none !important;
        border-bottom: 1px solid rgba(0,0,0,0.06);
    }
    .accordion-button:focus {
        box-shadow: none !important;
    }
    .form-switch .form-check-input:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    mark {
        background-color: #fef08a;
        color: #854d0e;
        padding: 0.1em 0.3em;
        border-radius: 3px;
        font-weight: 700;
    }
    .text-teal { color: #0d9488 !important; }
    .bg-teal { background-color: #0d9488 !important; }
    .border-teal { border-color: #0d9488 !important; }
    .text-purple { color: #7c3aed !important; }
    .bg-purple { background-color: #7c3aed !important; }
    .border-purple { border-color: #7c3aed !important; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    updateAllCounters();

    // 1. زر توسيع / طي الكل
    let allExpanded = false;
    const expandCollapseBtn = document.getElementById('expandCollapseAll');
    const allCollapses = document.querySelectorAll('.accordion-collapse');
    
    if (expandCollapseBtn) {
        expandCollapseBtn.addEventListener('click', function() {
            allExpanded = !allExpanded;
            allCollapses.forEach(function(collapse) {
                const bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false });
                if (allExpanded) {
                    bsCollapse.show();
                    expandCollapseBtn.innerHTML = '<i class="fas fa-compress-alt"></i> <span>طي الكل</span>';
                } else {
                    bsCollapse.hide();
                    expandCollapseBtn.innerHTML = '<i class="fas fa-expand-alt"></i> <span>توسيع الكل</span>';
                }
            });
        });
    }

    // إعدادات الحفظ اللحظي الفوري عبر AJAX
    const toggleUrl = "{{ route('roles.toggle-permission', $role) }}";
    const csrfToken = "{{ csrf_token() }}";
    const autoSaveIcon = document.getElementById('autoSaveIcon');
    const autoSaveText = document.getElementById('autoSaveText');
    const liveToastEl = document.getElementById('permissionLiveToast');
    const liveToast = liveToastEl ? new bootstrap.Toast(liveToastEl, { delay: 2000 }) : null;
    const liveToastMsg = document.getElementById('liveToastMessage');

    function setSavingState(isSaving, message, isError = false) {
        if (!autoSaveIcon || !autoSaveText) return;
        if (isSaving) {
            autoSaveIcon.className = 'fas fa-spinner fa-spin text-primary';
            autoSaveText.textContent = message || 'جاري الحفظ الفوري...';
        } else if (isError) {
            autoSaveIcon.className = 'fas fa-exclamation-circle text-danger';
            autoSaveText.textContent = message || 'فشل الحفظ!';
        } else {
            autoSaveIcon.className = 'fas fa-check-circle text-success';
            autoSaveText.textContent = message || 'تم الحفظ فورياً وتفريغ الكاش';
            setTimeout(() => {
                autoSaveIcon.className = 'fas fa-bolt text-warning';
                autoSaveText.textContent = 'الحفظ اللحظي التلقائي مفعّل';
            }, 2500);
        }
    }

    function showToast(text, isSuccess = true) {
        if (!liveToast || !liveToastMsg) return;
        liveToastMsg.innerHTML = isSuccess 
            ? `<i class="fas fa-check-circle text-success fs-5"></i> <span>${text}</span>`
            : `<i class="fas fa-times-circle text-danger fs-5"></i> <span>${text}</span>`;
        liveToast.show();
    }

    async function sendToggleRequest(payload, onFailCallback) {
        setSavingState(true);
        try {
            const res = await fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
                throw new Error(data.message || 'حدث خطأ في السيرفر');
            }
            setSavingState(false, data.message);
            showToast(data.message, true);
            return data;
        } catch (err) {
            setSavingState(false, err.message, true);
            showToast('خطأ: ' + err.message, false);
            if (typeof onFailCallback === 'function') {
                onFailCallback();
            }
            return null;
        }
    }

    // 2. مفتاح تحديد كامل القسم (Switch لكل قسم) مع الحفظ اللحظي
    document.querySelectorAll('.select-all-module').forEach(function(switchElem) {
        switchElem.addEventListener('change', function(e) {
            e.stopPropagation();
            const moduleKey = this.dataset.module;
            const isChecked = this.checked;
            const perms = [];
            
            document.querySelectorAll(`.permission-checkbox[data-module="${moduleKey}"]`).forEach(function(checkbox) {
                checkbox.checked = isChecked;
                perms.push(checkbox.value);
                const row = checkbox.closest('tr');
                if (row) {
                    if (isChecked) row.classList.add('table-active-row');
                    else row.classList.remove('table-active-row');
                }
            });

            updateModuleCounter(moduleKey);
            updateGlobalCounter();

            // حفظ فوري للقسم كامل
            if (perms.length > 0) {
                sendToggleRequest({
                    permissions: perms,
                    status: isChecked
                }, () => {
                    // تراجع عند الفشل
                    switchElem.checked = !isChecked;
                    document.querySelectorAll(`.permission-checkbox[data-module="${moduleKey}"]`).forEach(function(cb) {
                        cb.checked = !isChecked;
                        const r = cb.closest('tr');
                        if (r) {
                            if (!isChecked) r.classList.add('table-active-row');
                            else r.classList.remove('table-active-row');
                        }
                    });
                    updateModuleCounter(moduleKey);
                    updateGlobalCounter();
                });
            }
        });
    });

    // 3. عند تغيير أي Checkbox فردي (حفظ فوري لحظي)
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const moduleKey = this.dataset.module;
            const isChecked = this.checked;
            const row = this.closest('tr');
            if (row) {
                if (isChecked) row.classList.add('table-active-row');
                else row.classList.remove('table-active-row');
            }
            updateModuleCounter(moduleKey);
            updateGlobalCounter();

            // إرسال طلب الحفظ اللحظي التلقائي
            sendToggleRequest({
                permission: this.value,
                status: isChecked
            }, () => {
                // تراجع عند الفشل
                checkbox.checked = !isChecked;
                if (row) {
                    if (checkbox.checked) row.classList.add('table-active-row');
                    else row.classList.remove('table-active-row');
                }
                updateModuleCounter(moduleKey);
                updateGlobalCounter();
            });
        });
    });

    // 4. أزرار التحديد السريع مع الحفظ اللحظي
    document.querySelectorAll('.quick-select').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const allCheckboxes = document.querySelectorAll('.permission-checkbox');
            const targetPerms = [];
            const isEnable = (action !== 'none');

            allCheckboxes.forEach(function(checkbox) {
                const permName = checkbox.value.toLowerCase();
                let shouldCheck = false;

                if (action === 'all') {
                    shouldCheck = true;
                } else if (action === 'none') {
                    shouldCheck = false;
                } else if (action === 'view') {
                    shouldCheck = permName.startsWith('view') || permName.includes('.view');
                } else if (action === 'create') {
                    shouldCheck = permName.startsWith('create') || permName.includes('.create');
                } else if (action === 'edit') {
                    shouldCheck = permName.startsWith('edit') || permName.includes('.edit');
                } else if (action === 'delete') {
                    shouldCheck = permName.startsWith('delete') || permName.includes('.delete');
                }

                if (shouldCheck || action === 'none') {
                    targetPerms.push(checkbox.value);
                }

                checkbox.checked = shouldCheck;
                const row = checkbox.closest('tr');
                if (row) {
                    if (shouldCheck) row.classList.add('table-active-row');
                    else row.classList.remove('table-active-row');
                }
            });

            updateAllCounters();

            // حفظ فوري للتحديد السريع
            if (targetPerms.length > 0) {
                sendToggleRequest({
                    permissions: targetPerms,
                    status: isEnable
                });
            }
        });
    });

    // 5. البحث الفوري في الصلاحيات
    const searchInput = document.getElementById('searchPermissions');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const moduleItems = document.querySelectorAll('.permission-module-item');

            moduleItems.forEach(function(moduleItem) {
                const rows = moduleItem.querySelectorAll('.permission-row');
                let moduleHasMatch = false;

                rows.forEach(function(row) {
                    const labelElem = row.querySelector('.permission-label');
                    const codeElem = row.querySelector('code');
                    const text = ((labelElem ? labelElem.textContent : '') + ' ' + (codeElem ? codeElem.textContent : '')).toLowerCase();

                    if (searchTerm === '' || text.includes(searchTerm)) {
                        row.style.display = '';
                        moduleHasMatch = true;

                        if (searchTerm !== '' && labelElem) {
                            const originalText = labelElem.getAttribute('data-original') || labelElem.textContent;
                            if (!labelElem.getAttribute('data-original')) {
                                labelElem.setAttribute('data-original', originalText);
                            }
                            const regex = new RegExp(`(${searchTerm})`, 'gi');
                            labelElem.innerHTML = originalText.replace(regex, '<mark>$1</mark>');
                        } else if (labelElem && labelElem.getAttribute('data-original')) {
                            labelElem.innerHTML = labelElem.getAttribute('data-original');
                        }
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (moduleHasMatch) {
                    moduleItem.style.display = '';
                    if (searchTerm !== '') {
                        const collapse = moduleItem.querySelector('.accordion-collapse');
                        if (collapse) {
                            bootstrap.Collapse.getOrCreateInstance(collapse, { toggle: false }).show();
                        }
                    }
                } else {
                    moduleItem.style.display = 'none';
                }
            });
        });
    }

    // دوال الحساب والتحديث
    function updateModuleCounter(moduleKey) {
        const total = document.querySelectorAll(`.permission-checkbox[data-module="${moduleKey}"]`).length;
        const checked = document.querySelectorAll(`.permission-checkbox[data-module="${moduleKey}"]:checked`).length;
        const badge = document.getElementById(`badge_${moduleKey}`);
        const switchElem = document.getElementById(`switch_${moduleKey}`);

        if (badge) {
            badge.textContent = `${checked} / ${total}`;
        }
        if (switchElem) {
            switchElem.checked = (checked === total && total > 0);
            switchElem.indeterminate = (checked > 0 && checked < total);
        }
    }

    function updateGlobalCounter() {
        const total = document.querySelectorAll('.permission-checkbox').length;
        const checked = document.querySelectorAll('.permission-checkbox:checked').length;
        const percentage = total > 0 ? Math.round((checked / total) * 100) : 0;

        const selCount = document.getElementById('selectedCount');
        const totCount = document.getElementById('totalCount');
        const perCount = document.getElementById('percentageCount');
        const progBar  = document.getElementById('progressBar');

        if (selCount) selCount.textContent = checked;
        if (totCount) totCount.textContent = total;
        if (perCount) perCount.textContent = `${percentage}%`;
        if (progBar)  progBar.style.width = `${percentage}%`;
    }

    function updateAllCounters() {
        document.querySelectorAll('.select-all-module').forEach(function(switchElem) {
            updateModuleCounter(switchElem.dataset.module);
        });
        updateGlobalCounter();
    }
});
</script>
@endsection
