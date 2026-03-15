

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital me-2"></i>
                        الاستعلامات والاستقبال
                    </h2>
                    <p class="text-muted">إدارة استقبال المرضى وإنشاء الطلبات الطبية</p>
                </div>
               
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">زيارات اليوم</h6>
                            <h2 class="mb-0"><?php echo e($todayInquiries->total()); ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">قيد المعالجة</h6>
                            <h2 class="mb-0"><?php echo e($todayInquiries->where('status', 'in_progress')->count()); ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-spinner fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">مكتملة</h6>
                            <h2 class="mb-0"><?php echo e($todayInquiries->where('status', 'completed')->count()); ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">في الانتظار</h6>
                            <h2 class="mb-0"><?php echo e($todayInquiries->where('status', 'pending')->count()); ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة الزيارات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            زيارات اليوم
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="<?php echo e(route('inquiry.search')); ?>" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> طلب جديد
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>
                                تحديث
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if($todayInquiries->count() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>الوقت</th>
                                        <th>المريض</th>
                                        <th>العمر</th>
                                        <th>الهاتف</th>
                                        <th>الشكوى</th>
                                        <th>الطبيب</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $todayInquiries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                <?php echo e($visit->visit_time ? \Carbon\Carbon::parse($visit->visit_time)->format('H:i') : '-'); ?>

                                            </small>
                                        </td>
                                        <td>
                                            <strong><?php echo e(optional($visit->patient)->user->name ?? 'غير محدد'); ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo e(optional($visit->patient)->age ?? 'غير محدد'); ?> سنة</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo e(optional($visit->patient)->phone ?? 'غير محدد'); ?>

                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo e(Str::limit($visit->chief_complaint ?? 'لا يوجد', 40)); ?>

                                            </small>
                                        </td>
                                        <td>
                                            <?php if($visit->doctor): ?>
                                                <small>د. <?php echo e($visit->doctor->user->name); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($visit->status == 'in_progress'): ?>
                                                <span class="badge bg-info">قيد المعالجة</span>
                                            <?php elseif($visit->status == 'completed'): ?>
                                                <span class="badge bg-success">مكتمل</span>
                                            <?php elseif($visit->status == 'pending'): ?>
                                                <span class="badge bg-warning">في الانتظار</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?php echo e($visit->status); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?php echo e(Auth::user()->isDoctor() ? route('doctor.visits.show', $visit->id) : route('visits.show', $visit->id)); ?>" 
                                                   class="btn btn-sm btn-info"
                                                   title="التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-center">
                                <?php echo e($todayInquiries->links()); ?>

                            </div>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات اليوم</h5>
                            <p class="text-muted">ابدأ بإنشاء طلب جديد للمريض</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="<?php echo e(route('inquiry.search')); ?>" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    طلب جديد
                                </a>
                               
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- روابط سريعة -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-link me-2"></i>
                        روابط سريعة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="<?php echo e(route('patients.index')); ?>" class="btn btn-outline-info w-100 mb-2">
                                <i class="fas fa-users me-2"></i>
                                قائمة المرضى
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo e(route('patients.create')); ?>" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-user-plus me-2"></i>
                                تسجيل مريض جديد
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="<?php echo e(route('dashboard')); ?>" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\hospital-system\resources\views/inquiry/index.blade.php ENDPATH**/ ?>