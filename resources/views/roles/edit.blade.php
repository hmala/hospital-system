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
                            <label class="form-label fw-bold">الصلاحيات</label>
                            @error('permissions')
                                <div class="text-danger small mb-2">{{ $message }}</div>
                            @enderror
                            
                            @foreach($permissions as $module => $perms)
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-folder"></i> 
                                        @switch($module)
                                            @case('patients') المرضى @break
                                            @case('doctors') الأطباء @break
                                            @case('departments') العيادات @break
                                            @case('appointments') المواعيد @break
                                            @case('visits') الزيارات @break
                                            @case('surgeries') العمليات @break
                                            @case('radiology') الأشعة @break
                                            @case('tests') التحاليل @break
                                            @case('inquiries') الاستعلامات @break
                                            @case('rooms') الغرف @break
                                            @case('cashier') الكاشير @break
                                            @case('emergencies') الطوارئ @break
                                            @case('pharmacy') الصيدلية @break
                                            @case('system') إدارة النظام @break
                                            @case('consultant') الأطباء الاستشاريين @break
                                            @case('section') الأقسام الرئيسية @break
                                            @default {{ $module }}
                                        @endswitch
                                    </h6>
                                    <div class="form-check">
                                        <input class="form-check-input select-all-module" 
                                               type="checkbox" 
                                               id="select_all_{{ $module }}"
                                               data-module="{{ $module }}">
                                        <label class="form-check-label fw-bold text-primary" for="select_all_{{ $module }}">
                                            <i class="fas fa-check-double"></i> تحديد الكل
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($perms as $permission)
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="{{ $permission->name }}" 
                                                       id="perm_{{ $permission->id }}"
                                                       data-module="{{ $module }}"
                                                       {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="perm_{{ $permission->id }}">
                                                    @php
                                                        // ترجمة صلاحيات الأقسام الرئيسية
                                                        $sectionPermissions = [
                                                            'view patient management section' => 'عرض قسم إدارة المرضى',
                                                            'view emergency section' => 'عرض قسم الطوارئ',
                                                            'view doctors section' => 'عرض قسم الأطباء والعيادات',
                                                            'view appointments section' => 'عرض قسم المواعيد والزيارات',
                                                            'view surgeries section' => 'عرض قسم العمليات الجراحية',
                                                            'view lab section' => 'عرض قسم المختبر والأشعة',
                                                            'view settings section' => 'عرض قسم الإعدادات',
                                                        ];
                                                        
                                                        if (isset($sectionPermissions[$permission->name])) {
                                                            $label = $sectionPermissions[$permission->name];
                                                        } else {
                                                            $parts = explode(' ', $permission->name);
                                                            $verb = $parts[0] ?? '';
                                                            $res = $parts[1] ?? '';
                                                            $verbMap = [
                                                                'view' => 'عرض',
                                                                'create' => 'إنشاء',
                                                                'edit' => 'تعديل',
                                                                'delete' => 'حذف',
                                                                'manage' => 'إدارة',
                                                                'cancel' => 'إلغاء',
                                                                'process' => 'معالجة',
                                                                'control' => 'التحكم في',
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
                                                                'inquiries' => 'الاستعلامات',                                                            'lab' => 'المختبر',                                                                'pharmacy' => 'الصيدلية',
                                                                'referrals' => 'التحويلات',
                                                                'consultant' => 'الاستشاريين',
                                                                'cashier' => 'الكاشير',
                                                                'types' => 'أنواع التحاليل',
                                                                'rooms' => 'الغرف',
                                                                'emergencies' => 'الطوارئ',
                                                                'users' => 'المستخدمين',                                                            'lab' => 'المختبر',                                                                'roles' => 'الأدوار',
                                                                'permissions' => 'الصلاحيات',
                                                                'own' => 'الخاصة',
                                                                'surgery' => 'العمليات',
                                                                'lab' => 'المختبر',
                                                                'emergency' => 'الطوارئ',
                                                            ];
                                                            $label = ($verbMap[$verb] ?? $verb) . ' ' . ($resourceMap[$res] ?? $res);
                                                        }
                                                    @endphp
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
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
    .form-check {
        padding: 0.5rem;
        border-radius: 5px;
        transition: background-color 0.2s;
    }
    .form-check:hover {
        background-color: #f8f9fa;
    }
    .form-check-input:checked ~ .form-check-label {
        font-weight: 600;
        color: #0d6efd;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تحديث حالة "تحديد الكل" عند تحميل الصفحة
    updateSelectAllStatus();
    
    // عند النقر على "تحديد الكل" لقسم معين
    document.querySelectorAll('.select-all-module').forEach(function(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const isChecked = this.checked;
            
            // تحديد/إلغاء تحديد جميع الصلاحيات في هذا القسم
            document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`).forEach(function(checkbox) {
                checkbox.checked = isChecked;
            });
        });
    });
    
    // عند تغيير أي صلاحية، تحديث حالة "تحديد الكل"
    document.querySelectorAll('.permission-checkbox').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            updateSelectAllStatus();
        });
    });
    
    // دالة لتحديث حالة "تحديد الكل" لكل قسم
    function updateSelectAllStatus() {
        document.querySelectorAll('.select-all-module').forEach(function(selectAllCheckbox) {
            const module = selectAllCheckbox.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]`);
            const checkedCount = document.querySelectorAll(`.permission-checkbox[data-module="${module}"]:checked`).length;
            
            // إذا كانت جميع الصلاحيات محددة
            if (checkedCount === moduleCheckboxes.length) {
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
    }
});
</script>
@endsection
