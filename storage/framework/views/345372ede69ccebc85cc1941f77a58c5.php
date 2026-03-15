

<?php $__env->startSection('title', 'المرضى المقيمين في المستشفى'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid occupancy-page">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bed"></i>
                        المرضى المقيمين في المستشفى
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                        $bedReservations = collect($allOccupancies)->where('type_en', 'bed_reservation');
                        $surgeries = collect($allOccupancies)->where('type_en', 'surgery');
                    ?>

                    <ul class="nav nav-tabs mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-bed" data-bs-toggle="tab" data-bs-target="#bed" type="button" role="tab" aria-controls="bed" aria-selected="true">
                                رقود (<?php echo e($bedReservations->count()); ?>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-surgery" data-bs-toggle="tab" data-bs-target="#surgery" type="button" role="tab" aria-controls="surgery" aria-selected="false">
                                عمليات (<?php echo e($surgeries->count()); ?>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="bed" role="tabpanel" aria-labelledby="tab-bed">
                            <?php if($bedReservations->count()): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>المريض</th>
                                                <th>الغرفة</th>
                                                <th>نوع الغرفة</th>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الطبيب</th>
                                                <th>القسم</th>
                                                <th>الحالة</th>
                                                <th>ملاحظات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $bedReservations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reservation): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $record = $reservation['data']; ?>
                                                <tr>
                                                    <td><?php echo e(optional($record->patient->user)->name ?? 'غير معروف'); ?></td>
                                                    <td><?php echo e($record->room?->room_number ?? '-'); ?></td>
                                                    <td>
                                                        <?php if($record->room && $record->room->room_type === 'vip'): ?>
                                                            VIP
                                                        <?php elseif($record->room): ?>
                                                            عادية
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($record->scheduled_date->format('Y-m-d')); ?></td>
                                                    <td><?php echo e($record->scheduled_time->format('H:i')); ?></td>
                                                    <td><?php echo e(optional($record->doctor->user)->name ?? '-'); ?></td>
                                                    <td><?php echo e(optional($record->department)->name ?? '-'); ?></td>
                                                    <td>
                                                        <?php if($record->status === 'pending'): ?>
                                                            <span class="badge bg-warning">قيد الانتظار</span>
                                                        <?php elseif($record->status === 'confirmed'): ?>
                                                            <span class="badge bg-success">مؤكد</span>
                                                        <?php elseif($record->status === 'completed'): ?>
                                                            <span class="badge bg-secondary">مكتمل</span>
                                                        <?php elseif($record->status === 'cancelled'): ?>
                                                            <span class="badge bg-danger">ملغى</span>
                                                        <?php else: ?>
                                                            <?php echo e($record->status); ?>

                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($record->notes ?? '-'); ?></td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bed fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">لا يوجد رقود مؤقت حالياً</h4>
                                    <p class="text-muted">يمكنك إنشاء رقود جديد من صفحة الحجز</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="tab-pane fade" id="surgery" role="tabpanel" aria-labelledby="tab-surgery">
                            <?php if($surgeries->count()): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>المريض</th>
                                                <th>الغرفة</th>
                                                <th>نوع الغرفة</th>
                                                <th>التاريخ</th>
                                                <th>الوقت</th>
                                                <th>الطبيب</th>
                                                <th>القسم</th>
                                                <th>نوع العملية</th>
                                                <th>الحالة</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $__currentLoopData = $surgeries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $surgery): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <?php $record = $surgery['data']; ?>
                                                <tr>
                                                    <td><?php echo e(optional($record->patient->user)->name ?? 'غير معروف'); ?></td>
                                                    <td><?php echo e($record->room?->room_number ?? '-'); ?></td>
                                                    <td>
                                                        <?php if($record->room && $record->room->room_type === 'vip'): ?>
                                                            VIP
                                                        <?php elseif($record->room): ?>
                                                            عادية
                                                        <?php else: ?>
                                                            -
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo e($record->scheduled_date->format('Y-m-d')); ?></td>
                                                    <td><?php echo e($record->scheduled_time->format('H:i')); ?></td>
                                                    <td><?php echo e(optional($record->doctor->user)->name ?? '-'); ?></td>
                                                    <td><?php echo e(optional($record->department)->name ?? '-'); ?></td>
                                                    <td><?php echo e($record->surgery_type ?? '-'); ?></td>
                                                    <td>
                                                        <?php if($record->status === 'scheduled'): ?>
                                                            مجدولة
                                                        <?php elseif($record->status === 'waiting'): ?>
                                                            في الانتظار
                                                        <?php elseif($record->status === 'in_progress'): ?>
                                                            جارية
                                                        <?php elseif($record->status === 'completed'): ?>
                                                            مكتملة
                                                        <?php else: ?>
                                                            <?php echo e($record->status); ?>

                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-procedures fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">لا توجد عمليات محجوزة حالياً</h4>
                                    <p class="text-muted">يمكنك متابعة عملياتك من صفحة العمليات</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>إجمالي رقود:</strong> <?php echo e($bedReservations->count()); ?>

                        </div>
                        <div class="col-md-6 text-left">
                            <strong>إجمالي عمليات:</strong> <?php echo e($surgeries->count()); ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('styles'); ?>
<style>
.occupancy-page {
    background-color: #f0f8ff;
}
.occupancy-page .card {
    border: 2px solid #17a2b8;
}
.occupancy-page table {
    background: #ffffff;
}
.card {
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
}
.card-header {
    border-bottom: 1px solid #dee2e6;
}
.table th {
    vertical-align: middle;
}
.table td {
    vertical-align: middle;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/inquiry/occupancy.blade.php ENDPATH**/ ?>