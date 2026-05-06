@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> رجوع
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-edit"></i> تعديل صلاحيات: 
                        @switch($role->name)
                            @case('admin') مدير النظام @break
                            @case('doctor') طبيب @break
                            @case('patient') مريض @break
                            @case('receptionist') موظف استقبال @break
                            @case('cashier') كاشير @break
                            @case('lab_staff') موظف مختبر @break
                            @case('radiology_staff') موظف أشعة @break
                            @case('pharmacy_staff') موظف صيدلية @break
                            @case('surgery_staff') موظف عمليات @break
                            @case('nurse') ممرض @break
                            @case('emergency_staff') موظف طوارئ @break
                            @case('consultation_receptionist') موظف استعلامات استشارية @break
                            @default {{ $role->name }}
                        @endswitch
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label class="form-label">اسم الدور</label>
                            <input type="text" class="form-control" value="{{ $role->name }}" disabled>
                            <input type="hidden" name="display_name" value="{{ $role->name }}">
                            <small class="text-muted">لا يمكن تعديل اسم الدور</small>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-bold fs-5 mb-0">
                                    <i class="fas fa-shield-alt text-primary"></i> إدارة الصلاحيات
                                </label>
                                <div class="d-flex gap-2">
                                    <div class="input-group input-group-sm" style="max-width: 250px;">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchPermissions" placeholder="البحث في الصلاحيات...">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="expandCollapseAll">
                                        <i class="fas fa-expand-alt"></i> توسيع الكل
                                    </button>
                                </div>
                            </div>
                            
                            <!-- إحصائيات الصلاحيات -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="card border-primary shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-check-circle fa-2x text-primary mb-2"></i>
                                            <h5 class="mb-0" id="selectedCount">0</h5>
                                            <small class="text-muted">صلاحية محددة</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-info shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-list fa-2x text-info mb-2"></i>
                                            <h5 class="mb-0" id="totalCount">0</h5>
                                            <small class="text-muted">إجمالي الصلاحيات</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card border-success shadow-sm">
                                        <div class="card-body text-center">
                                            <i class="fas fa-percentage fa-2x text-success mb-2"></i>
                                            <h5 class="mb-0" id="percentageCount">0%</h5>
                                            <small class="text-muted">نسبة الاكتمال</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- أزرار التحديد السريع -->
                            <div class="card mb-3 border-secondary">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <small class="text-muted fw-bold">
                                                <i class="fas fa-bolt"></i> تحديد سريع:
                                            </small>
                                        </div>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-info quick-select" data-action="view">
                                                <i class="fas fa-eye"></i> عرض فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-success quick-select" data-action="create">
                                                <i class="fas fa-plus"></i> إنشاء فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-warning quick-select" data-action="edit">
                                                <i class="fas fa-edit"></i> تعديل فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-danger quick-select" data-action="delete">
                                                <i class="fas fa-trash"></i> حذف فقط
                                            </button>
                                            <button type="button" class="btn btn-outline-success quick-select" data-action="all">
                                                <i class="fas fa-check-double"></i> تحديد الكل
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary quick-select" data-action="none">
                                                <i class="fas fa-times"></i> إلغاء الكل
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            @error('permissions')
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle"></i> {{ $message }}
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            @enderror
                            
                            @php
                                // ترتيب الأقسام حسب الأولوية
                                $moduleOrder = [
                                    'section' => ['name' => 'الأقسام الرئيسية', 'icon' => 'fa-th-large', 'color' => 'primary'],
                                    'patients' => ['name' => 'المرضى', 'icon' => 'fa-user-injured', 'color' => 'info'],
                                    'doctors' => ['name' => 'الأطباء', 'icon' => 'fa-user-md', 'color' => 'success'],
                                    'departments' => ['name' => 'العيادات', 'icon' => 'fa-clinic-medical', 'color' => 'teal'],
                                    'appointments' => ['name' => 'المواعيد', 'icon' => 'fa-calendar-check', 'color' => 'warning'],
                                    'visits' => ['name' => 'الزيارات', 'icon' => 'fa-notes-medical', 'color' => 'indigo'],
                                    'emergencies' => ['name' => 'الطوارئ', 'icon' => 'fa-ambulance', 'color' => 'danger'],
                                    'surgeries' => ['name' => 'العمليات الجراحية', 'icon' => 'fa-procedures', 'color' => 'purple'],
                                    'radiology' => ['name' => 'الأشعة', 'icon' => 'fa-x-ray', 'color' => 'cyan'],
                                    'tests' => ['name' => 'التحاليل المخبرية', 'icon' => 'fa-vial', 'color' => 'pink'],
                                    'inquiries' => ['name' => 'الاستعلامات', 'icon' => 'fa-question-circle', 'color' => 'secondary'],
                                    'cashier' => ['name' => 'الكاشير والمدفوعات', 'icon' => 'fa-cash-register', 'color' => 'success'],
                                    'pharmacy' => ['name' => 'الصيدلية', 'icon' => 'fa-pills', 'color' => 'green'],
                                    'rooms' => ['name' => 'إدارة الغرف', 'icon' => 'fa-bed', 'color' => 'orange'],
                                    'consultant' => ['name' => 'الأطباء الاستشاريين', 'icon' => 'fa-user-tie', 'color' => 'dark'],
                                    'system' => ['name' => 'إدارة النظام', 'icon' => 'fa-cogs', 'color' => 'dark'],
                                ];
                                
                                // ترتيب الأقسام
                                $sortedPermissions = [];
                                foreach ($moduleOrder as $key => $value) {
                                    if (isset($permissions[$key])) {
                                        $sortedPermissions[$key] = $permissions[$key];
                                    }
                                }
                                // إضافة أي أقسام أخرى غير مدرجة
                                foreach ($permissions as $key => $value) {
                                    if (!isset($sortedPermissions[$key])) {
                                        $sortedPermissions[$key] = $value;
                                    }
                                }
                            @endphp
                            
                            <div class="accordion" id="permissionsAccordion">
                                @foreach($sortedPermissions as $module => $perms)
                                @php
                                    $moduleInfo = $moduleOrder[$module] ?? ['name' => $module, 'icon' => 'fa-folder', 'color' => 'secondary'];
                                    $collapseId = "collapse_" . str_replace([' ', '-'], '_', $module);
                                @endphp
                                <div class="accordion-item border shadow-sm mb-3 rounded">
                                    <h2 class="accordion-header" id="heading_{{ $module }}">
                                        <button class="accordion-button collapsed bg-light" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#{{ $collapseId }}" 
                                                aria-expanded="false" 
                                                aria-controls="{{ $collapseId }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                                <div>
                                                    <i class="fas {{ $moduleInfo['icon'] }} text-{{ $moduleInfo['color'] }} me-2"></i>
                                                    <span class="fw-bold">{{ $moduleInfo['name'] }}</span>
                                                    <span class="badge bg-{{ $moduleInfo['color'] }} ms-2">{{ count($perms) }}</span>
                                                </div>
                                                <div class="form-check form-switch" onclick="event.stopPropagation();">
                                                    <input class="form-check-input select-all-module" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           id="select_all_{{ $module }}"
                                                           data-module="{{ $module }}">
                                                    <label class="form-check-label fw-bold text-{{ $moduleInfo['color'] }}" for="select_all_{{ $module }}">
                                                        <i class="fas fa-check-double"></i> تحديد الكل
                                                    </label>
                                                </div>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="{{ $collapseId }}" 
                                         class="accordion-collapse collapse" 
                                         aria-labelledby="heading_{{ $module }}" 
                                         data-bs-parent="#permissionsAccordion">
                                        <div class="accordion-body bg-light bg-opacity-25">
                                            <div class="row g-3">
                                                @foreach($perms as $permission)
                                                @php
                                                    // ترجمة صلاحيات الأقسام الرئيسية
                                                    $sectionPermissions = [
                                                        'view patient management section' => ['label' => 'عرض قسم إدارة المرضى', 'icon' => 'fa-eye', 'color' => 'info'],
                                                        'view emergency section' => ['label' => 'عرض قسم الطوارئ', 'icon' => 'fa-eye', 'color' => 'danger'],
                                                        'view doctors section' => ['label' => 'عرض قسم الأطباء والعيادات', 'icon' => 'fa-eye', 'color' => 'success'],
                                                        'view appointments section' => ['label' => 'عرض قسم المواعيد والزيارات', 'icon' => 'fa-eye', 'color' => 'warning'],
                                                        'view surgeries section' => ['label' => 'عرض قسم العمليات الجراحية', 'icon' => 'fa-eye', 'color' => 'purple'],
                                                        'view lab section' => ['label' => 'عرض قسم المختبر والأشعة', 'icon' => 'fa-eye', 'color' => 'cyan'],
                                                        'view settings section' => ['label' => 'عرض قسم الإعدادات', 'icon' => 'fa-eye', 'color' => 'dark'],
                                                        'view stock transfers' => ['label' => 'عرض نقل المخزون', 'icon' => 'fa-exchange-alt', 'color' => 'teal'],
                                                        'view stock transfer requests' => ['label' => 'عرض طلبات نقل المخزون', 'icon' => 'fa-clipboard-list', 'color' => 'teal'],
                                                    ];
                                                    
                                                    if (isset($sectionPermissions[$permission->name])) {
                                                        $permLabel = $sectionPermissions[$permission->name]['label'];
                                                        $permIcon = $sectionPermissions[$permission->name]['icon'];
                                                        $permColor = $sectionPermissions[$permission->name]['color'];
                                                    } else {
                                                        $parts = explode(' ', $permission->name);
                                                        $verb = $parts[0] ?? '';
                                                        $res = $parts[1] ?? '';
                                                        
                                                        $verbMap = [
                                                            'view' => ['label' => 'عرض', 'icon' => 'fa-eye', 'color' => 'info'],
                                                            'create' => ['label' => 'إنشاء', 'icon' => 'fa-plus-circle', 'color' => 'success'],
                                                            'edit' => ['label' => 'تعديل', 'icon' => 'fa-edit', 'color' => 'warning'],
                                                            'delete' => ['label' => 'حذف', 'icon' => 'fa-trash-alt', 'color' => 'danger'],
                                                            'manage' => ['label' => 'إدارة', 'icon' => 'fa-tasks', 'color' => 'primary'],
                                                            'cancel' => ['label' => 'إلغاء', 'icon' => 'fa-times-circle', 'color' => 'danger'],
                                                            'process' => ['label' => 'معالجة', 'icon' => 'fa-cog', 'color' => 'secondary'],
                                                            'control' => ['label' => 'التحكم في', 'icon' => 'fa-sliders-h', 'color' => 'dark'],
                                                        ];
                                                        
                                                        $resourceMap = [
                                                            'patients' => 'المرضى',
                                                            'doctors' => 'الأطباء',
                                                            'departments' => 'العيادات',
                                                            'appointments' => 'المواعيد',
                                                            'visits' => 'الزيارات',
                                                            'surgeries' => 'العمليات',
                                                            'radiology' => 'الأشعة',
                                                            'tests' => 'التحاليل',
                                                            'inquiries' => 'الاستعلامات',
                                                            'lab' => 'المختبر',
                                                            'pharmacy' => 'الصيدلية',
                                                            'referrals' => 'التحويلات',
                                                            'consultant' => 'الاستشاريين',
                                                            'cashier' => 'الكاشير',
                                                            'types' => 'أنواع التحاليل',
                                                            'rooms' => 'الغرف',
                                                            'emergencies' => 'الطوارئ',
                                                            'users' => 'المستخدمين',
                                                            'roles' => 'الأدوار',
                                                            'permissions' => 'الصلاحيات',
                                                            'own' => 'الخاصة',
                                                            'surgery' => 'العمليات',
                                                            'emergency' => 'الطوارئ',
                                                        ];
                                                        
                                                        $verbInfo = $verbMap[$verb] ?? ['label' => $verb, 'icon' => 'fa-circle', 'color' => 'secondary'];
                                                        $permLabel = $verbInfo['label'] . ' ' . ($resourceMap[$res] ?? $res);
                                                        $permIcon = $verbInfo['icon'];
                                                        $permColor = $verbInfo['color'];
                                                    }
                                                @endphp
                                                <div class="col-md-6 col-lg-4">
                                                    <div class="permission-item p-2 rounded border border-{{ $permColor }} border-opacity-25">
                                                        <div class="form-check">
                                                            <input class="form-check-input permission-checkbox" 
                                                                   type="checkbox" 
                                                                   name="permissions[]" 
                                                                   value="{{ $permission->name }}" 
                                                                   id="perm_{{ $permission->id }}"
                                                                   data-module="{{ $module }}"
                                                                   {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                            <label class="form-check-label d-flex align-items-center" for="perm_{{ $permission->id }}">
                                                                <i class="fas {{ $permIcon }} text-{{ $permColor }} me-2"></i>
                                                                <span class="permission-label">{{ $permLabel }}</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('roles.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* تحسين تصميم الأكورديون */
    .accordion-button:not(.collapsed) {
        background-color: #f8f9fa;
        color: #212529;
        box-shadow: none;
    }
    
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,.125);
    }
    
    .accordion-item {
        border: 1px solid rgba(0,0,0,.125) !important;
        overflow: hidden;
    }
    
    .accordion-button::after {
        margin-right: auto;
        margin-left: 0;
    }
    
    /* تصميم عناصر الصلاحيات */
    .permission-item {
        background-color: #fff;
        transition: all 0.3s ease;
    }
    
    .permission-item:hover {
        background-color: #f8f9fa;
        transform: translateY(-2px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .permission-item .form-check {
        margin-bottom: 0;
    }
    
    .permission-item .form-check-input:checked ~ .form-check-label {
        font-weight: 600;
    }
    
    .permission-item .form-check-input:checked ~ .form-check-label .permission-label {
        color: #0d6efd;
    }
    
    .permission-label {
        font-size: 0.9rem;
        line-height: 1.4;
    }
    
    /* تحسين مظهر المفتاح */
    .form-check-input[type="checkbox"]:checked {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
    
    .form-switch .form-check-input {
        cursor: pointer;
        width: 3em;
        height: 1.5em;
    }
    
    .form-switch .form-check-input:checked {
        background-color: #198754;
        border-color: #198754;
    }
    
    /* ألوان مخصصة */
    .text-teal { color: #20c997; }
    .text-indigo { color: #6610f2; }
    .text-purple { color: #6f42c1; }
    .text-pink { color: #d63384; }
    .text-cyan { color: #0dcaf0; }
    .text-green { color: #198754; }
    .text-orange { color: #fd7e14; }
    
    .bg-teal { background-color: #20c997; }
    .bg-indigo { background-color: #6610f2; }
    .bg-purple { background-color: #6f42c1; }
    .bg-pink { background-color: #d63384; }
    .bg-cyan { background-color: #0dcaf0; }
    .bg-green { background-color: #198754; }
    .bg-orange { background-color: #fd7e14; }
    
    .border-teal { border-color: #20c997 !important; }
    .border-indigo { border-color: #6610f2 !important; }
    .border-purple { border-color: #6f42c1 !important; }
    .border-pink { border-color: #d63384 !important; }
    .border-cyan { border-color: #0dcaf0 !important; }
    .border-green { border-color: #198754 !important; }
    .border-orange { border-color: #fd7e14 !important; }
    
    /* شريط التقدم */
    .permission-progress {
        height: 4px;
        background: linear-gradient(90deg, #0d6efd 0%, #198754 100%);
        border-radius: 2px;
        margin-top: 0.5rem;
    }
    
    /* تحسين الشارات */
    .badge {
        font-weight: 500;
        padding: 0.35em 0.65em;
    }
    
    /* رسوم متحركة */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .accordion-item {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    /* تمييز نتائج البحث */
    mark {
        background-color: #fff3cd;
        color: #856404;
        padding: 0.1em 0.3em;
        border-radius: 3px;
        font-weight: 600;
    }
    
    /* تحسين حقل البحث */
    #searchPermissions:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديث حالة "تحديد الكل" عند تحميل الصفحة
    updateSelectAllStatus();
    
    // زر توسيع/طي الكل
    let allExpanded = false;
    const expandCollapseBtn = document.getElementById('expandCollapseAll');
    const allCollapses = document.querySelectorAll('.accordion-collapse');
    
    if (expandCollapseBtn) {
        expandCollapseBtn.addEventListener('click', function() {
            allExpanded = !allExpanded;
            
            allCollapses.forEach(function(collapse) {
                const bsCollapse = new bootstrap.Collapse(collapse, {
                    toggle: false
                });
                
                if (allExpanded) {
                    bsCollapse.show();
                    expandCollapseBtn.innerHTML = '<i class="fas fa-compress-alt"></i> طي الكل';
                } else {
                    bsCollapse.hide();
                    expandCollapseBtn.innerHTML = '<i class="fas fa-expand-alt"></i> توسيع الكل';
                }
            });
        });
    }
    
    // عند النقر على "تحديد الكل" لقسم معين
    document.querySelectorAll('.select-all-module').forEach(function(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function(e) {
            e.stopPropagation(); // منع فتح/إغلاق الأكورديون
            
            const module = this.dataset.module;
            const isChecked = this.checked;
            
            // تحديد/إلغاء تحديد جميع الصلاحيات في هذا القسم
            document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`).forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
            
            // إضافة تأثير بصري
            const accordionBody = this.closest('.accordion-item').querySelector('.accordion-body');
            if (accordionBody) {
                accordionBody.style.transition = 'background-color 0.3s';
                accordionBody.style.backgroundColor = isChecked ? '#d4edda' : '';
                setTimeout(() => {
                    accordionBody.style.backgroundColor = '';
                }, 500);
            }
        });
    });
    
    // عند تغيير أي صلاحية، تحديث حالة "تحديد الكل"
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateSelectAllStatus();
            
            // إضافة تأثير بصري للعنصر المحدد
            const permItem = this.closest('.permission-item');
            if (permItem) {
                if (this.checked) {
                    permItem.style.backgroundColor = '#e7f3ff';
                    setTimeout(() => {
                        permItem.style.backgroundColor = '';
                    }, 300);
                }
            }
        });
    });
    
    // أزرار التحديد السريع
    document.querySelectorAll('.quick-select').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const action = this.dataset.action;
            const allCheckboxes = document.querySelectorAll('.permission-checkbox');
            
            allCheckboxes.forEach(function(checkbox) {
                const permName = checkbox.value.toLowerCase();
                
                if (action === 'all') {
                    checkbox.checked = true;
                } else if (action === 'none') {
                    checkbox.checked = false;
                } else if (action === 'view') {
                    checkbox.checked = permName.startsWith('view');
                } else if (action === 'create') {
                    checkbox.checked = permName.startsWith('create');
                } else if (action === 'edit') {
                    checkbox.checked = permName.startsWith('edit');
                } else if (action === 'delete') {
                    checkbox.checked = permName.startsWith('delete');
                }
            });
            
            updateSelectAllStatus();
            
            // إضافة تأثير بصري
            const accordionBody = document.getElementById('permissionsAccordion');
            if (accordionBody) {
                accordionBody.style.transition = 'opacity 0.2s';
                accordionBody.style.opacity = '0.7';
                setTimeout(() => {
                    accordionBody.style.opacity = '1';
                }, 200);
            }
        });
    });
    
    // دالة لتحديث حالة "تحديد الكل" لكل قسم
    function updateSelectAllStatus() {
        document.querySelectorAll('.select-all-module').forEach(function(selectAllCheckbox) {
            const module = selectAllCheckbox.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            const checkedCount = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]:checked`).length;
            const totalCount = moduleCheckboxes.length;
            
            // تحديث الشارة بعدد الصلاحيات المحددة
            const badge = selectAllCheckbox.closest('.accordion-item')?.querySelector('.badge');
            if (badge && checkedCount > 0) {
                badge.textContent = `${checkedCount}/${totalCount}`;
            } else if (badge) {
                badge.textContent = totalCount;
            }
            
            // إذا كانت جميع الصلاحيات محددة
            if (checkedCount === totalCount && totalCount > 0) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            }
            // إذا كان بعضها محدد
            else if (checkedCount > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            }
            // إذا لم يكن أي منها محدد
            else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        });
        
        // تحديث عداد إجمالي الصلاحيات المحددة
        updateTotalPermissionsCount();
    }
    
    // دالة لتحديث العداد الإجمالي
    function updateTotalPermissionsCount() {
        const totalChecked = document.querySelectorAll('.permission-checkbox:checked').length;
        const totalPermissions = document.querySelectorAll('.permission-checkbox').length;
        const percentage = totalPermissions > 0 ? Math.round((totalChecked / totalPermissions) * 100) : 0;
        
        // تحديث عناصر الإحصائيات
        const selectedCountElem = document.getElementById('selectedCount');
        const totalCountElem = document.getElementById('totalCount');
        const percentageCountElem = document.getElementById('percentageCount');
        
        if (selectedCountElem) selectedCountElem.textContent = totalChecked;
        if (totalCountElem) totalCountElem.textContent = totalPermissions;
        if (percentageCountElem) percentageCountElem.textContent = percentage + '%';
        
        // تغيير لون البطاقة حسب النسبة
        const percentageCard = percentageCountElem?.closest('.card');
        if (percentageCard) {
            percentageCard.classList.remove('border-success', 'border-warning', 'border-danger');
            if (percentage >= 80) {
                percentageCard.classList.add('border-success');
            } else if (percentage >= 50) {
                percentageCard.classList.add('border-warning');
            } else {
                percentageCard.classList.add('border-danger');
            }
        }
    }
    
    // فتح القسم الأول تلقائياً إذا لم يكن هناك صلاحيات محددة
    const firstCollapse = document.querySelector('.accordion-collapse');
    if (firstCollapse) {
        const totalChecked = document.querySelectorAll('.permission-checkbox:checked').length;
        if (totalChecked === 0) {
            new bootstrap.Collapse(firstCollapse, {
                show: true
            });
        }
    }
    
    // وظيفة البحث في الصلاحيات
    const searchInput = document.getElementById('searchPermissions');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const accordionItems = document.querySelectorAll('.accordion-item');
            
            accordionItems.forEach(function(item) {
                const permissionItems = item.querySelectorAll('.permission-item');
                let hasVisibleItems = false;
                
                permissionItems.forEach(function(permItem) {
                    const label = permItem.querySelector('.permission-label');
                    if (label) {
                        const text = label.textContent.toLowerCase();
                        
                        if (searchTerm === '' || text.includes(searchTerm)) {
                            permItem.style.display = '';
                            permItem.closest('.col-md-6').style.display = '';
                            hasVisibleItems = true;
                            
                            // تمييز النص المطابق
                            if (searchTerm !== '') {
                                const originalText = label.textContent;
                                const regex = new RegExp(`(${searchTerm})`, 'gi');
                                label.innerHTML = originalText.replace(regex, '<mark>$1</mark>');
                            } else {
                                label.innerHTML = label.textContent;
                            }
                        } else {
                            permItem.style.display = 'none';
                            permItem.closest('.col-md-6').style.display = 'none';
                        }
                    }
                });
                
                // إخفاء/إظهار القسم بالكامل
                if (hasVisibleItems) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
                
                // فتح الأقسام التي تحتوي على نتائج بحث
                if (searchTerm !== '' && hasVisibleItems) {
                    const collapse = item.querySelector('.accordion-collapse');
                    if (collapse && !collapse.classList.contains('show')) {
                        new bootstrap.Collapse(collapse, {
                            show: true
                        });
                    }
                }
            });
            
            // عرض رسالة إذا لم يتم العثور على نتائج
            const visibleItems = document.querySelectorAll('.accordion-item[style=""]').length;
            let noResultsMsg = document.getElementById('noSearchResults');
            
            if (visibleItems === 0 && searchTerm !== '') {
                if (!noResultsMsg) {
                    noResultsMsg = document.createElement('div');
                    noResultsMsg.id = 'noSearchResults';
                    noResultsMsg.className = 'alert alert-info text-center';
                    noResultsMsg.innerHTML = '<i class="fas fa-info-circle"></i> لم يتم العثور على صلاحيات مطابقة للبحث';
                    document.getElementById('permissionsAccordion').appendChild(noResultsMsg);
                }
            } else if (noResultsMsg) {
                noResultsMsg.remove();
            }
        });
        
        // مسح البحث عند الضغط على Escape
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.dispatchEvent(new Event('input'));
            }
        });
    }
});
</script>
@endsection
