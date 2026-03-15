

<?php $__env->startSection('content'); ?>
<div class="container-fluid" id="cashier-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-cash-register me-2 text-success"></i>
                        محطة الكاشير
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted">
                        إدارة المدفوعات والإيصالات - 
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo e(session('success')); ?>

            <?php if(session('payment_id')): ?>
                <br>
                <div class="mt-2">
                    <a href="<?php echo e(route('cashier.receipt', session('payment_id'))); ?>" 
                       class="btn btn-sm btn-light me-2" 
                       target="_blank">
                        <i class="fas fa-eye me-1"></i>
                        عرض الإيصال
                    </a>
                    <a href="<?php echo e(route('cashier.receipt.print', session('payment_id'))); ?>" 
                       class="btn btn-sm btn-light" 
                       target="_blank">
                        <i class="fas fa-print me-1"></i>
                        طباعة الإيصال
                    </a>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(session('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo e(session('warning')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- لوحة التحكم الرئيسية -->
    <div class="row mb-4">
        <div class="col-12">
                    <!-- إحصائيات اليوم المحسنة -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">إجمالي المبالغ المحصلة اليوم</p>
                                            <h3 class="mb-0 text-success"><?php echo e(number_format($todayStats['total_collected'], 2)); ?> IQD</h3>
                                        </div>
                                        <div class="bg-success bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">أجور الأطباء اليوم</p>
                                            <h3 class="mb-0 text-info"><?php echo e(number_format($todayStats['doctor_fees'], 2)); ?> IQD</h3>
                                        </div>
                                        <div class="bg-info bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-user-md fa-2x text-info"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="text-muted mb-1">ربح المستشفى اليوم</p>
                                            <h3 class="mb-0 text-primary"><?php echo e(number_format($todayStats['hospital_profit'], 2)); ?> IQD</h3>
                                        </div>
                                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-building fa-2x text-primary"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <?php if(auth()->user()->can('view cashier appointments')): ?>
                                <p class="text-muted mb-1">المواعيد المعلقة</p>
                                                <h3 class="mb-0 text-warning"><?php echo e($todayStats['pending_appointments_count']); ?></h3>
                                                <small class="text-muted">
                                                    مواعيد: <?php echo e($todayStats['pending_appointments_count']); ?>

                                                    <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier medical requests')): ?>
                                                    | طلبات: <?php echo e($todayStats['pending_requests_count']); ?>

                                                    <?php endif; ?>
                                                </small>
                                            <?php else: ?>
                                                <p class="text-muted mb-1">المعاملات المعلقة</p>
                                                <h3 class="mb-0 text-warning"><?php echo e($todayStats['pending_appointments_count'] + $todayStats['pending_requests_count']); ?></h3>
                                                <small class="text-muted">
                                                    مواعيد: <?php echo e($todayStats['pending_appointments_count']); ?> | 
                                                    طلبات: <?php echo e($todayStats['pending_requests_count']); ?>

                                                </small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                                            <i class="fas fa-clock fa-2x text-warning"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- عرض الأقسام المعلقة بدون تبويبات -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <!-- مواعيد -->
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>
                                        المواعيد المعلقة - بانتظار الدفع
                                        <?php if(isset($pendingAppointments) && is_object($pendingAppointments) && method_exists($pendingAppointments, 'total') && $pendingAppointments->total() > 0): ?>
                                            <span class="badge bg-warning"><?php echo e($pendingAppointments->total()); ?></span>
                                        <?php endif; ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($pendingAppointments) && is_object($pendingAppointments) && method_exists($pendingAppointments, 'total') && $pendingAppointments->total() > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>رقم الموعد</th>
                                                        <th>المريض</th>
                                                        <th>الطبيب</th>
                                                        <th>التخصص</th>
                                                        <th>أجر الطبيب</th>
                                                        <th>مبلغ الكشف</th>
                                                        <th>ربح المستشفى</th>
                                                        <th>الحالة</th>
                                                        <th>الإجراءات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $pendingAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <?php
                                                            $doctorFee = $appointment->doctor->fee_by_specialization ?? 0;
                                                            $hospitalProfit = $appointment->consultation_fee - $doctorFee;
                                                        ?>
                                                        <tr>
                                                            <td><strong>#<?php echo e($appointment->id); ?></strong></td>
                                                            <td>
                                                                <div><?php echo e(optional(optional($appointment->patient)->user)->name ?? '-'); ?></div>
                                                                <small class="text-muted"><?php echo e(optional($appointment->patient)->national_id ?? 'غير محدد'); ?></small>
                                                    </td>
                                                    <td>د. <?php echo e(optional(optional($appointment->doctor)->user)->name ?? '-'); ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary"><?php echo e($appointment->doctor->specialization ?? 'غير محدد'); ?></span>
                                                    </td>
                                                    <td class="text-info fw-bold"><?php echo e(number_format($doctorFee, 2)); ?> IQD</td>
                                                    <td class="text-success fw-bold"><?php echo e(number_format($appointment->consultation_fee, 2)); ?> IQD</td>
                                                    <td class="text-primary fw-bold"><?php echo e(number_format($hospitalProfit, 2)); ?> IQD</td>
                                                    <td>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>
                                                            معلق
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo e(route('cashier.payment.form', $appointment->id)); ?>" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    <?php echo e($pendingAppointments->links()); ?>

                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد مواعيد معلقة حالياً</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier medical requests')): ?>
                <!-- الطلبات الطبية -->
                    
                    <!-- قائمة الطلبات المعلقة (تحاليل، أشعة، صيدلية) -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-file-medical me-2"></i>
                                الطلبات الطبية المعلقة - بانتظار الدفع
                                <?php if(isset($pendingMedicalRequests) && is_object($pendingMedicalRequests) && $pendingMedicalRequests->count() > 0): ?>
                                    <span class="badge bg-warning"><?php echo e($pendingMedicalRequests->total()); ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php $__empty_1 = true; $__currentLoopData = $pendingMedicalRequests ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php if($loop->first): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>رقم الطلب</th>
                                                <th>النوع</th>
                                                <th>المريض</th>
                                                <th>التفاصيل</th>
                                                <th>التاريخ</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                <?php endif; ?>
                                                <tr>
                                                    <td><strong>#<?php echo e($request->id); ?></strong></td>
                                                    <td>
                                                        <?php if($request->type === 'lab'): ?>
                                                            <span class="badge bg-primary">
                                                                <i class="fas fa-flask"></i> تحاليل
                                                            </span>
                                                        <?php elseif($request->type === 'radiology'): ?>
                                                            <span class="badge bg-info">
                                                                <i class="fas fa-x-ray"></i> أشعة
                                                            </span>
                                                        <?php elseif($request->type === 'pharmacy'): ?>
                                                            <span class="badge bg-success">
                                                                <i class="fas fa-pills"></i> صيدلية
                                                            </span>
                                                        <?php elseif($request->type === 'emergency'): ?>
                                                            <span class="badge bg-danger">
                                                                <i class="fas fa-ambulance"></i> طوارئ
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary"><?php echo e($request->type); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div><?php echo e(optional(optional(optional($request->visit)->patient)->user)->name ?? 'غير محدد'); ?></div>
                                                        <small class="text-muted"><?php echo e(optional(optional($request->visit)->patient)->national_id ?? 'غير محدد'); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php
                                                            $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                                                        ?>
                                                        
                                                        <?php if($request->type === 'lab' && isset($details['lab_test_ids'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-vial"></i> 
                                                                <?php echo e(count($details['lab_test_ids'])); ?> تحليل
                                                                <?php
                                                                    $testNames = [];
                                                                    foreach(array_slice($details['lab_test_ids'], 0, 2) as $testId) {
                                                                        $test = \App\Models\LabTest::find($testId);
                                                                        if($test) $testNames[] = $test->name;
                                                                    }
                                                                ?>
                                                                <br><?php echo e(implode(', ', $testNames)); ?>

                                                                <?php if(count($details['lab_test_ids']) > 2): ?>
                                                                    <br>... و <?php echo e(count($details['lab_test_ids']) - 2); ?> أخرى
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php elseif($request->type === 'radiology' && isset($details['radiology_type_ids'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-camera"></i> 
                                                                <?php echo e(count($details['radiology_type_ids'])); ?> نوع إشعة
                                                                <?php
                                                                    $typeNames = [];
                                                                    foreach(array_slice($details['radiology_type_ids'], 0, 2) as $typeId) {
                                                                        $type = \App\Models\RadiologyType::find($typeId);
                                                                        if($type) $typeNames[] = $type->name;
                                                                    }
                                                                ?>
                                                                <br><?php echo e(implode(', ', $typeNames)); ?>

                                                                <?php if(count($details['radiology_type_ids']) > 2): ?>
                                                                    <br>... و <?php echo e(count($details['radiology_type_ids']) - 2); ?> أخرى
                                                                <?php endif; ?>
                                                            </small>
                                                        <?php elseif($request->type === 'emergency' && isset($details['emergency_priority'])): ?>
                                                            <small class="text-muted">
                                                                <i class="fas fa-exclamation-triangle"></i> 
                                                                <strong>الأولوية:</strong> 
                                                                <?php if($details['emergency_priority'] === 'critical'): ?>
                                                                    <span class="badge badge-sm bg-danger">حرجة</span>
                                                                <?php elseif($details['emergency_priority'] === 'urgent'): ?>
                                                                    <span class="badge badge-sm bg-warning">عاجلة</span>
                                                                <?php elseif($details['emergency_priority'] === 'semi_urgent'): ?>
                                                                    <span class="badge badge-sm bg-info">شبه عاجلة</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-sm bg-secondary">غير عاجلة</span>
                                                                <?php endif; ?>
                                                                <?php if(isset($details['emergency_type'])): ?>
                                                                    <br><strong>النوع:</strong> <?php echo e(\App\Models\Emergency::getEmergencyTypeText($details['emergency_type'])); ?>

                                                                <?php endif; ?>
                                                            </small>
                                                        <?php else: ?>
                                                            <small class="text-muted"><?php echo e($request->description); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small><?php echo e($request->created_at->format('Y-m-d')); ?></small>
                                                        <br>
                                                        <small class="text-muted"><?php echo e($request->created_at->format('H:i')); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation-circle me-1"></i>
                                                            معلق
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo e(route('cashier.request.payment.form', $request->id)); ?>" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                <?php if($loop->last): ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if(isset($pendingMedicalRequests) && method_exists($pendingMedicalRequests, 'links')): ?>
                                <div class="mt-3">
                                    <?php echo e($pendingMedicalRequests->links('pagination::bootstrap-5')); ?>

                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد طلبات معلقة حالياً</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php endif; ?>

                <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier emergency')): ?>
                <!-- خدمات الطوارئ -->
                    
                    <!-- قائمة خدمات الطوارئ المعلقة -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                <i class="fas fa-ambulance me-2"></i>
                                خدمات الطوارئ المعلقة - بانتظار الدفع
                                <?php if(isset($pendingEmergencyPayments) && is_object($pendingEmergencyPayments) && $pendingEmergencyPayments->count() > 0): ?>
                                    <span class="badge bg-danger"><?php echo e($pendingEmergencyPayments->total()); ?></span>
                                <?php endif; ?>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php $__empty_1 = true; $__currentLoopData = $pendingEmergencyPayments ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php if($loop->first): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>رقم الدفعة</th>
                                                <th>المريض</th>
                                                <th>حالة الطوارئ</th>
                                                <th>الخدمات</th>
                                                <th>المبلغ</th>
                                                <th>التاريخ</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                <?php endif; ?>
                                                <tr>
                                                    <td><strong>#<?php echo e($payment->id); ?></strong></td>
                                                    <td>
                                                        <div>
                                                            <?php
                                                                $em = $payment->emergency;
                                                                if ($em->patient) {
                                                                    $pname = optional(optional($em->patient)->user)->name ?? 'غير محدد';
                                                                    $pphone = optional(optional($em->patient)->user)->phone ?? '---';
                                                                } elseif ($em->emergencyPatient) {
                                                                    $pname = $em->emergencyPatient->name;
                                                                    $pphone = $em->emergencyPatient->phone ?? '---';
                                                                } else {
                                                                    $pname = 'غير محدد';
                                                                    $pphone = '---';
                                                                }
                                                            ?>
                                                            <strong><?php echo e($pname); ?></strong>
                                                            <br>
                                                            <small class="text-muted"><?php echo e($pphone); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-<?php echo e($payment->emergency->priority_color); ?>">
                                                            <?php echo e($payment->emergency->priority_text); ?>

                                                        </span>
                                                        <br>
                                                        <small class="text-muted"><?php echo e($payment->emergency->emergency_type_text); ?></small>
                                                    </td>
                                                    <td>
                                                        <small>
                                                            <?php if($payment->appointment_id): ?>
                                                                <span class="badge bg-primary">استشارة طبيب</span>
                                                                <?php if($payment->appointment && $payment->appointment->doctor): ?>
                                                                    <br>
                                                                    <span class="text-muted">د. <?php echo e($payment->appointment->doctor->user->name ?? ''); ?></span>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                            <?php if($payment->emergency->services->count() > 0): ?>
                                                                <?php if($payment->appointment_id): ?><br><?php endif; ?>
                                                                <?php $__currentLoopData = $payment->emergency->services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                    <span class="badge bg-light text-dark"><?php echo e($service->name); ?></span>
                                                                    <?php if(!$loop->last): ?> | <?php endif; ?>
                                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                            <?php endif; ?>
                                                            <?php if(!$payment->appointment_id && $payment->emergency->services->count() == 0): ?>
                                                                <span class="text-muted">-</span>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success"><?php echo e(number_format($payment->amount, 2)); ?> IQD</strong>
                                                    </td>
                                                    <td>
                                                        <small><?php echo e($payment->created_at->format('Y-m-d')); ?></small>
                                                        <br>
                                                        <small class="text-muted"><?php echo e($payment->created_at->format('H:i')); ?></small>
                                                    </td>
                                                    <td>
                                                        <a href="<?php echo e(route('cashier.emergency.payment.form', $payment->id)); ?>" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-money-bill-wave me-1"></i>
                                                            تسديد
                                                        </a>
                                                    </td>
                                                </tr>
                                <?php if($loop->last): ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if(isset($pendingEmergencyPayments) && method_exists($pendingEmergencyPayments, 'links')): ?>
                                <div class="mt-3">
                                    <?php echo e($pendingEmergencyPayments->links('pagination::bootstrap-5')); ?>

                                </div>
                                <?php endif; ?>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <p class="text-muted">لا توجد خدمات طوارئ معلقة حالياً</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>


            </div> <!-- end sections container -->
                <?php endif; ?>
        </div> <!-- end col-12 -->
    </div> <!-- end row mb-4 -->

</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
// تحديث تلقائي للصفحة كل 5 ثواني
setInterval(function() {
    // تحديث الإحصائيات والجدول بدون إعادة تحميل كامل
    $.ajax({
        url: window.location.href,
        type: 'GET',
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('cashier-content');
            
            if (newContent) {
                const currentScroll = window.scrollY;
                $('#cashier-content').html($(newContent).html());
                window.scrollTo(0, currentScroll);
                
                // تحديث الوقت
                const now = new Date();
                const time = now.toLocaleTimeString('ar-IQ');
                $('#last-update').text('آخر تحديث: ' + time);
            }
        },
        error: function(error) {
            console.error('خطأ في التحديث:', error);
        }
    });
}, 5000); // 5 ثواني

$(document).ready(function() {
    const now = new Date();
    const time = now.toLocaleTimeString('ar-IQ');
    $('#last-update').text('آخر تحديث: ' + time);

    // restore tab from URL hash if present
    var hash = window.location.hash;
    if (hash) {
        var btn = $('#pendingTabs button[data-bs-target="' + hash + '"]');
        if (btn.length) {
            btn.tab('show');
        }
    }
});

// keep URL hash in sync with selected tab
$(document).on('shown.bs.tab', '#pendingTabs button', function(e) {
    var target = $(e.target).data('bs-target');
    if (history.replaceState) {
        history.replaceState(null, null, target);
    } else {
        window.location.hash = target;
    }
});
</script>

<style>
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

#live-indicator {
    animation: pulse 2s ease-in-out infinite;
}

#live-indicator i {
    color: #fff;
}

/* تنسيقات الطباعة */
@media print {
    body * {
        visibility: hidden;
    }
    
    #cashier-content, #cashier-content * {
        visibility: visible;
    }
    
    #cashier-content {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
    
    .btn, .card-header .btn {
        display: none !important;
    }
    
    .card {
        border: 1px solid #dee2e6 !important;
        margin-bottom: 20px;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\wamp64\www\hospital-system\resources\views/cashier/index.blade.php ENDPATH**/ ?>