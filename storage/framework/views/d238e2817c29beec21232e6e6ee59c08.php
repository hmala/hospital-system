<!-- resources/views/patients/index.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-injured me-2"></i>
                    إدارة المرضى
                </h2>
                <a href="<?php echo e(route('patients.create')); ?>" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة مريض جديد
                </a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- شريط البحث -->
    <div class="row mb-4">
        <div class="col-md-6">
            <form action="<?php echo e(route('patients.search')); ?>" method="GET">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="ابحث باسم المريض، الهاتف، البريد أو الرقم الوطني..." value="<?php echo e(request('search')); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($loop->iteration); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <span class="text-white fw-bold">
                                                    <?php echo e($patient->user ? substr($patient->user->name, 0, 1) : '?'); ?>

                                                </span>
                                            </div>
                                            <div>
                                                <strong><?php echo e($patient->user ? $patient->user->name : 'مريض بدون بيانات'); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo e($patient->user && $patient->user->gender == 'male' ? 'ذكر' : ($patient->user && $patient->user->gender == 'female' ? 'أنثى' : 'غير محدد')); ?>

                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <small>
                                            <i class="fas fa-phone me-1 text-muted"></i><?php echo e($patient->user ? $patient->user->phone : 'غير متوفر'); ?><br>
                                            <i class="fas fa-envelope me-1 text-muted"></i><?php echo e($patient->user ? $patient->user->email : 'غير متوفر'); ?>

                                        </small>
                                    </td>
                                    <td>
                                        <?php if($patient->age): ?>
                                            <span class="badge bg-info"><?php echo e($patient->age); ?> سنة</span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($patient->blood_type): ?>
                                            <span class="badge bg-danger"><?php echo e($patient->blood_type); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo e($patient->emergency_contact ?? '---'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo e($patient->total_appointments); ?></span>
                                    </td>
                                    <td>
                                        <?php if($patient->getLastVisitDate()): ?>
                                            <small class="text-success">
                                                <?php echo e($patient->getLastVisitDate() ? $patient->getLastVisitDate()->format('Y-m-d') : 'لا توجد'); ?>

                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">لا توجد زيارات</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo e(route('patients.show', $patient)); ?>" 
                                               class="btn btn-info" title="عرض الملف">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo e(route('patients.edit', $patient)); ?>" 
                                               class="btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="<?php echo e(route('patients.destroy', $patient)); ?>" 
                                                  method="POST" class="d-inline">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="submit" class="btn btn-danger" 
                                                        title="حذف" onclick="return confirm('هل أنت متأكد من حذف المريض؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-user-injured fa-3x mb-3"></i>
                                        <br>
                                        لا توجد مرضى مسجلين حتى الآن
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- الترقيم -->
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo e($patients->links()); ?>

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
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/patients/index.blade.php ENDPATH**/ ?>