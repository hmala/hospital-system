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
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-plus-circle"></i> إضافة دور جديد</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">الاسم الإنجليزي <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       placeholder="مثال: custom_role"
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">استخدم أحرف إنجليزية صغيرة وشرطة سفلية فقط</small>
                            </div>

                            <div class="col-md-6">
                                <label for="display_name" class="form-label">الاسم المعروض <span class="text-danger">*</span></label>
                                <input type="text" 
                                       class="form-control @error('display_name') is-invalid @enderror" 
                                       id="display_name" 
                                       name="display_name" 
                                       value="{{ old('display_name') }}" 
                                       placeholder="مثال: مدير مخصص"
                                       required>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
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
                                        <input class="form-check-input module-checkbox" 
                                               type="checkbox" 
                                               id="module_{{ $loop->index }}"
                                               data-module="{{ $loop->index }}">
                                        <label class="form-check-label fw-bold text-primary" for="module_{{ $loop->index }}">
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
                                                       data-module="{{ $loop->parent->index }}"
                                                       {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}>
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
                                                            // special case lab tests
                                                            if($permission->name === 'view lab tests') {
                                                                $label = 'عرض أنواع التحاليل';
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
                                                                    'lab' => 'المختبر',
                                                                    'emergency' => 'الطوارئ',
                                                                ];
                                                                $label = ($verbMap[$verb] ?? $verb) . ' ' . ($resourceMap[$res] ?? $res);
                                                            }
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
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> حفظ
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
    document.querySelectorAll('.module-checkbox').forEach(function(selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const moduleIndex = this.dataset.module;
            const isChecked = this.checked;
            
            // تحديد/إلغاء تحديد جميع الصلاحيات في هذا القسم
            document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]`).forEach(function(checkbox) {
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
        document.querySelectorAll('.module-checkbox').forEach(function(selectAllCheckbox) {
            const moduleIndex = selectAllCheckbox.dataset.module;
            const moduleCheckboxes = document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]`);
            const checkedCount = document.querySelectorAll(`.permission-checkbox[data-module="${moduleIndex}"]:checked`).length;
            
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
