

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-shield"></i> إدارة الأدوار</h2>
        <a href="<?php echo e(route('roles.create')); ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة دور جديد
        </a>
    </div>

    <?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?php echo e(session('success')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <?php echo e(session('error')); ?>

        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-<?php echo e($role->name == 'admin' ? 'danger' : 
                    ($role->name == 'doctor' ? 'success' : 
                    ($role->name == 'patient' ? 'info' : 
                    ($role->name == 'receptionist' ? 'warning' : 'secondary')))); ?> text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php switch($role->name):
                                case ('admin'): ?> <i class="fas fa-crown"></i> مدير النظام <?php break; ?>
                                <?php case ('doctor'): ?> <i class="fas fa-user-md"></i> طبيب <?php break; ?>
                                <?php case ('patient'): ?> <i class="fas fa-user-injured"></i> مريض <?php break; ?>
                                <?php case ('receptionist'): ?> <i class="fas fa-user-tie"></i> موظف استقبال <?php break; ?>
                                <?php case ('lab_staff'): ?> <i class="fas fa-flask"></i> موظف مختبر <?php break; ?>
                                <?php case ('radiology_staff'): ?> <i class="fas fa-x-ray"></i> موظف أشعة <?php break; ?>
                                <?php case ('pharmacy_staff'): ?> <i class="fas fa-pills"></i> موظف صيدلية <?php break; ?>
                                <?php case ('surgery_staff'): ?> <i class="fas fa-procedures"></i> موظف عمليات <?php break; ?>
                                <?php default: ?> <?php echo e($role->name); ?>

                            <?php endswitch; ?>
                        </h5>
                        <?php if(!in_array($role->name, ['admin', 'doctor', 'patient', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff'])): ?>
                        <form action="<?php echo e(route('roles.destroy', $role)); ?>" 
                              method="POST" 
                              class="d-inline"
                              onsubmit="return confirm('هل أنت متأكد من حذف هذا الدور؟')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-sm btn-light">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">
                                <i class="fas fa-users"></i> المستخدمين
                            </span>
                            <span class="badge bg-primary"><?php echo e($role->users_count); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span class="text-muted">
                                <i class="fas fa-key"></i> الصلاحيات
                            </span>
                            <span class="badge bg-info"><?php echo e($role->permissions_count); ?></span>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?php echo e(route('roles.edit', $role)); ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-edit"></i> تعديل الصلاحيات
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>

<style>
    .card {
        transition: transform 0.2s;
    }
    .card:hover {
        transform: translateY(-5px);
    }
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\hospital-system\resources\views/roles/index.blade.php ENDPATH**/ ?>