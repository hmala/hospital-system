

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="mb-4">
        <a href="<?php echo e(route('roles.index')); ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-right"></i> رجوع
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0">
                        <i class="fas fa-edit"></i> تعديل صلاحيات: 
                        <?php switch($role->name):
                            case ('admin'): ?> مدير النظام <?php break; ?>
                            <?php case ('doctor'): ?> طبيب <?php break; ?>
                            <?php case ('patient'): ?> مريض <?php break; ?>
                            <?php case ('receptionist'): ?> موظف استقبال <?php break; ?>
                            <?php case ('cashier'): ?> كاشير <?php break; ?>
                            <?php case ('lab_staff'): ?> موظف مختبر <?php break; ?>
                            <?php case ('radiology_staff'): ?> موظف أشعة <?php break; ?>
                            <?php case ('pharmacy_staff'): ?> موظف صيدلية <?php break; ?>
                            <?php case ('surgery_staff'): ?> موظف عمليات <?php break; ?>
                            <?php case ('nurse'): ?> ممرض <?php break; ?>
                            <?php case ('emergency_staff'): ?> موظف طوارئ <?php break; ?>
                            <?php case ('consultation_receptionist'): ?> موظف استعلامات استشارية <?php break; ?>
                            <?php default: ?> <?php echo e($role->name); ?>

                        <?php endswitch; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo e(route('roles.update', $role)); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div class="mb-3">
                            <label class="form-label">اسم الدور</label>
                            <input type="text" class="form-control" value="<?php echo e($role->name); ?>" disabled>
                            <input type="hidden" name="display_name" value="<?php echo e($role->name); ?>">
                            <small class="text-muted">لا يمكن تعديل اسم الدور</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">الصلاحيات</label>
                            <?php $__errorArgs = ['permissions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <div class="text-danger small mb-2"><?php echo e($message); ?></div>
                            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            
                            <?php $__currentLoopData = $permissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="fas fa-folder"></i> 
                                        <?php switch($module):
                                            case ('patients'): ?> المرضى <?php break; ?>
                                            <?php case ('doctors'): ?> الأطباء <?php break; ?>
                                            <?php case ('departments'): ?> العيادات <?php break; ?>
                                            <?php case ('appointments'): ?> المواعيد <?php break; ?>
                                            <?php case ('visits'): ?> الزيارات <?php break; ?>
                                            <?php case ('surgeries'): ?> العمليات <?php break; ?>
                                            <?php case ('radiology'): ?> الأشعة <?php break; ?>
                                            <?php case ('tests'): ?> التحاليل <?php break; ?>
                                            <?php case ('inquiries'): ?> الاستعلامات <?php break; ?>
                                            <?php case ('rooms'): ?> الغرف <?php break; ?>
                                            <?php case ('cashier'): ?> الكاشير <?php break; ?>
                                            <?php case ('emergencies'): ?> الطوارئ <?php break; ?>
                                            <?php case ('pharmacy'): ?> الصيدلية <?php break; ?>
                                            <?php case ('system'): ?> إدارة النظام <?php break; ?>
                                            <?php case ('consultant'): ?> الأطباء الاستشاريين <?php break; ?>
                                            <?php case ('section'): ?> الأقسام الرئيسية <?php break; ?>
                                            <?php default: ?> <?php echo e($module); ?>

                                        <?php endswitch; ?>
                                    </h6>
                                    <div class="form-check">
                                        <input class="form-check-input select-all-module" 
                                               type="checkbox" 
                                               id="select_all_<?php echo e($module); ?>"
                                               data-module="<?php echo e($module); ?>">
                                        <label class="form-check-label fw-bold text-primary" for="select_all_<?php echo e($module); ?>">
                                            <i class="fas fa-check-double"></i> تحديد الكل
                                        </label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input permission-checkbox" 
                                                       type="checkbox" 
                                                       name="permissions[]" 
                                                       value="<?php echo e($permission->name); ?>" 
                                                       id="perm_<?php echo e($permission->id); ?>"
                                                       data-module="<?php echo e($module); ?>"
                                                       <?php echo e(in_array($permission->name, $rolePermissions) ? 'checked' : ''); ?>>
                                                <label class="form-check-label" for="perm_<?php echo e($permission->id); ?>">
                                                    <?php
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
                                                    ?>
                                                    <?php echo e($label); ?>

                                                </label>
                                            </div>
                                        </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo e(route('roles.index')); ?>" class="btn btn-secondary">
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\hospital-system\resources\views/roles/edit.blade.php ENDPATH**/ ?>