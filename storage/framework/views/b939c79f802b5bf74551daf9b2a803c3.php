<!-- resources/views/dashboard.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h2>
        </div>
    </div>

    <?php if(isset($radiologyStats)): ?>
    <!-- إحصائيات موظف الأشعة -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">طلبات معلقة</h5>
                            <h2 class="mb-0"><?php echo e($radiologyStats['pending']); ?></h2>
                            <small>تحتاج للمعالجة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">مجدولة</h5>
                            <h2 class="mb-0"><?php echo e($radiologyStats['scheduled']); ?></h2>
                            <small>لها موعد محدد</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">قيد التنفيذ</h5>
                            <h2 class="mb-0"><?php echo e($radiologyStats['in_progress']); ?></h2>
                            <small>جاري العمل عليها</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-play fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">اكتملت اليوم</h5>
                            <h2 class="mb-0"><?php echo e($radiologyStats['completed_today']); ?></h2>
                            <small>تمت معالجتها اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات الأشعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-x-ray fa-4x text-primary mb-3"></i>
                    <h4>طلبات الأشعة</h4>
                    <p class="text-muted mb-4">عرض وإدارة جميع طلبات الأشعة</p>
                    <a href="<?php echo e(route('radiology.index')); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        الانتقال إلى طلبات الأشعة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php elseif(isset($labStats)): ?>
    <!-- إحصائيات موظف المختبر -->
    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">طلبات معلقة</h5>
                            <h2 class="mb-0"><?php echo e($labStats['pending']); ?></h2>
                            <small>تحتاج للمعالجة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-flask fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">اكتملت اليوم</h5>
                            <h2 class="mb-0"><?php echo e($labStats['completed_today']); ?></h2>
                            <small>تمت معالجتها اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات المختبر -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-flask fa-4x text-primary mb-3"></i>
                    <h4>طلبات المختبر</h4>
                    <p class="text-muted mb-4">عرض وإدارة جميع طلبات المختبر</p>
                    <a href="<?php echo e(route('staff.requests.index', ['type' => 'lab'])); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        الانتقال إلى طلبات المختبر
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php else: ?>
    <!-- الإحصائيات العامة -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-patient text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">المرضى</h5>
                            <h2 class="mb-0"><?php echo e($stats['totalPatients']); ?></h2>
                            <small>مسجلين في النظام</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-user-injured fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-doctor text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">الأطباء</h5>
                            <h2 class="mb-0"><?php echo e($stats['totalDoctors']); ?></h2>
                            <small>يعملون في المستشفى</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-user-md fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-department text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">العيادات</h5>
                            <h2 class="mb-0"><?php echo e($stats['totalDepartments']); ?></h2>
                            <small>عيادات نشطة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-clinic-medical fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-appointment text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">مواعيد اليوم</h5>
                            <h2 class="mb-0"><?php echo e($stats['todayAppointments']); ?></h2>
                            <small>مجدولة لهذا اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الغرف -->
    <?php if(isset($roomStats) && $roomStats['total'] > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="fas fa-bed me-2 text-danger"></i>حالة الغرف</h5>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="fas fa-bed fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo e($roomStats['total']); ?></h3>
                        <small class="text-muted">إجمالي الغرف</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-success"><?php echo e($roomStats['available']); ?></h3>
                        <small class="text-muted">متاحة</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                        <i class="fas fa-user fa-2x text-danger"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-danger"><?php echo e($roomStats['occupied']); ?></h3>
                        <small class="text-muted">محجوزة</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                        <i class="fas fa-tools fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-warning"><?php echo e($roomStats['maintenance']); ?></h3>
                        <small class="text-muted">صيانة</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- الإجراءات السريعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>الإجراءات السريعة</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 col-6 mb-3">
                            <a href="<?php echo e(route('patients.create')); ?>" class="btn btn-outline-primary btn-lg p-3 rounded-circle">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">مريض جديد</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="<?php echo e(route('appointments.create')); ?>" class="btn btn-outline-success btn-lg p-3 rounded-circle">
                                <i class="fas fa-calendar-plus fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">موعد جديد</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="<?php echo e(route('visits.create')); ?>" class="btn btn-outline-info btn-lg p-3 rounded-circle">
                                <i class="fas fa-file-medical fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">زيارة جديدة</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="<?php echo e(route('surgeries.create')); ?>" class="btn btn-outline-warning btn-lg p-3 rounded-circle">
                                <i class="fas fa-procedures fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">حجز عملية</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="<?php echo e(route('rooms.index')); ?>" class="btn btn-outline-danger btn-lg p-3 rounded-circle">
                                <i class="fas fa-bed fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">إدارة الغرف</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\hospital-system\resources\views/dashboard.blade.php ENDPATH**/ ?>