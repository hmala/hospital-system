<!-- resources/views/emergency/index.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-ambulance me-2"></i>
                    إدارة الطوارئ
                </h2>
                <div>
                    <a href="<?php echo e(route('emergency.dashboard')); ?>" class="btn btn-info me-2">
                        <i class="fas fa-chart-line me-2"></i>لوحة التحكم
                    </a>
                    <a href="<?php echo e(route('emergency.create')); ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>حالة طوارئ جديدة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo e(session('error')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- فلاتر البحث والتصفية -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="<?php echo e(route('emergency.index')); ?>" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="ابحث باسم المريض أو رقم الطوارئ..." value="<?php echo e(request('search')); ?>">
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-select">
                        <option value="">جميع الأولويات</option>
                        <option value="critical" <?php if(request('priority') == 'critical'): echo 'selected'; endif; ?>>حرجة</option>
                        <option value="high" <?php if(request('priority') == 'high'): echo 'selected'; endif; ?>>عالية</option>
                        <option value="medium" <?php if(request('priority') == 'medium'): echo 'selected'; endif; ?>>متوسطة</option>
                        <option value="low" <?php if(request('priority') == 'low'): echo 'selected'; endif; ?>>منخفضة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="waiting" <?php if(request('status') == 'waiting'): echo 'selected'; endif; ?>>في الانتظار</option>
                        <option value="in_triage" <?php if(request('status') == 'in_triage'): echo 'selected'; endif; ?>>في التفريغ</option>
                        <option value="in_treatment" <?php if(request('status') == 'in_treatment'): echo 'selected'; endif; ?>>في العلاج</option>
                        <option value="discharged" <?php if(request('status') == 'discharged'): echo 'selected'; endif; ?>>مغادر</option>
                        <option value="transferred" <?php if(request('status') == 'transferred'): echo 'selected'; endif; ?>>محول</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-filter"></i> فلترة
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
                                    <th>نوع الطوارئ</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>نتائج التحاليل</th>
                                    <th>نتائج الأشعة</th>
                                    <th>الاستشارة</th>
                                    <th>العلامات الحيوية</th>
                                    <th>الطبيب المسؤول</th>
                                    <th>وقت الدخول</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr class="<?php echo e($emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'high' ? 'table-warning' : '')); ?>">
                                    <td><?php echo e($loop->iteration); ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <span class="text-white fw-bold">
                                                    <?php if($emergency->patient): ?>
                                                        <?php echo e(substr($emergency->patient->user->name ?? '؟', 0, 1)); ?>

                                                    <?php elseif($emergency->emergencyPatient): ?>
                                                        <?php echo e(substr($emergency->emergencyPatient->name ?? '؟', 0, 1)); ?>

                                                    <?php else: ?>
                                                        ?
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                            <div>
                                                <strong>
                                                    <?php if($emergency->patient): ?>
                                                        <?php echo e($emergency->patient->user->name ?? 'مريض بدون بيانات'); ?>

                                                    <?php elseif($emergency->emergencyPatient): ?>
                                                        <?php echo e($emergency->emergencyPatient->name); ?> <small class="text-muted">(طوارئ)</small>
                                                    <?php else: ?>
                                                        مريض غير معروف
                                                    <?php endif; ?>
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    رقم الطوارئ: <?php echo e($emergency->id); ?>

                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo e($emergency->emergency_type_text); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo e($emergency->priority_badge_class); ?>"><?php echo e($emergency->priority_text); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo e($emergency->status_badge_class); ?>"><?php echo e($emergency->status_text); ?></span>
                                    </td>
                                    <td>
                                        <?php
                                            $latestCompletedLab = $emergency->labRequests
                                                ->where('status', 'completed')
                                                ->sortByDesc('completed_at')
                                                ->first();
                                        ?>
                                        <?php if($latestCompletedLab): ?>
                                            <span class="badge bg-success mb-2 d-inline-block">مكتمل</span>
                                            <?php
                                                $labResults = $latestCompletedLab->labTests
                                                    ->filter(function($test){ return !empty(trim((string)($test->pivot->result ?? ''))); })
                                                    ->values();
                                            ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success d-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#labResultsModal-<?php echo e($emergency->id); ?>"
                                                    title="عرض نتائج التحاليل">
                                                <i class="fas fa-vial me-1"></i>
                                                عرض النتائج
                                            </button>
                                            <?php if($labResults->count()): ?>
                                                <small class="text-muted d-block mt-1"><?php echo e($labResults->count()); ?> نتيجة</small>
                                            <?php endif; ?>
                                        <?php elseif($emergency->labRequests->whereIn('status', ['pending', 'in_progress'])->count() > 0): ?>
                                            <span class="badge bg-warning text-dark">قيد التنفيذ</span>
                                        <?php else: ?>
                                            <small class="text-muted">لا يوجد طلب</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                            $latestCompletedRadiology = $emergency->radiologyRequests
                                                ->where('status', 'completed')
                                                ->sortByDesc('completed_at')
                                                ->first();
                                        ?>
                                        <?php if($latestCompletedRadiology): ?>
                                            <span class="badge bg-success mb-2 d-inline-block">مكتمل</span>
                                            <?php
                                                $radiologyResults = $latestCompletedRadiology->radiologyTypes
                                                    ->filter(function($type){ return !empty(trim((string)($type->pivot->result ?? ''))); })
                                                    ->values();
                                            ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info d-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#radiologyResultsModal-<?php echo e($emergency->id); ?>"
                                                    title="عرض نتائج الأشعة">
                                                <i class="fas fa-x-ray me-1"></i>
                                                عرض النتائج
                                            </button>
                                            <?php if($radiologyResults->count()): ?>
                                                <small class="text-muted d-block mt-1"><?php echo e($radiologyResults->count()); ?> نتيجة</small>
                                            <?php endif; ?>
                                        <?php elseif($emergency->radiologyRequests->whereIn('status', ['pending', 'in_progress'])->count() > 0): ?>
                                            <span class="badge bg-warning text-dark">قيد التنفيذ</span>
                                        <?php else: ?>
                                            <small class="text-muted">لا يوجد طلب</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                                <?php
                                                $consultationAppointment = \App\Models\Appointment::where('emergency_id', $emergency->id)->first();
                                            ?>
                                            <?php if($consultationAppointment): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-calendar-check me-1"></i>
                                                    مجدول
                                                </span>
                                                <br>
                                                <small class="text-muted"><?php echo e($consultationAppointment->appointment_date->format('d/m')); ?></small>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#consultationModal-<?php echo e($emergency->id); ?>" title="طلب استشارة">
                                                    <i class="fas fa-plus me-1"></i>
                                                    طلب
                                                </button>
                                            <?php endif; ?>
                                    </td>
                                    <td>
                                        <small>
                                            <?php if($emergency->vitals_last_updated): ?>
                                                ضغط: <?php echo e($emergency->blood_pressure ?? '---'); ?><br>
                                                نبض: <?php echo e($emergency->heart_rate ?? '---'); ?><br>
                                                <span class="text-muted"><?php echo e($emergency->vitals_last_updated->diffForHumans()); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">لم يتم تسجيل</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if($emergency->doctor): ?>
                                            <small><?php echo e($emergency->doctor->user->name ?? 'غير محدد'); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">غير محدد</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo e($emergency->created_at->format('d/m/Y H:i')); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="<?php echo e(route('emergency.show', $emergency)); ?>" class="btn btn-sm btn-outline-primary" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#vitalsModal-<?php echo e($emergency->id); ?>" title="إدخال معلومات طبية">
                                                <i class="fas fa-notes-medical"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#labModal-<?php echo e($emergency->id); ?>" title="طلب تحليل">
                                                <i class="fas fa-flask"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#radiologyModal-<?php echo e($emergency->id); ?>" title="طلب أشعة">
                                                <i class="fas fa-x-ray"></i>
                                            </button>
                                            <?php
                                                $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
                                            ?>
                                            <?php if(!$hasConsultation): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#consultationModal-<?php echo e($emergency->id); ?>" title="طلب استشارة">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="12" class="text-center py-4">
                                        <i class="fas fa-ambulance fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">لا توجد حالات طوارئ حالياً</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if($emergencies->hasPages()): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <?php echo e($emergencies->links()); ?>

                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="vitalsModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-labelledby="vitalsModalLabel-<?php echo e($emergency->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content medical-modal">
            <div class="modal-header medical-modal__header">
                <div>
                    <h5 class="modal-title" id="vitalsModalLabel-<?php echo e($emergency->id); ?>">معلومات طبية للحالة #<?php echo e($emergency->id); ?></h5>
                    <small class="text-muted">حدّث العلامات الحيوية والتشخيص والخدمة المقدمة بسرعة</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="<?php echo e(route('emergency.update-medical', $emergency)); ?>">
                <?php echo csrf_field(); ?>
                <?php echo method_field('POST'); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <strong>العلامات الحيوية</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ضغط الدم</label>
                                            <input type="text" name="blood_pressure" class="form-control" value="<?php echo e($emergency->blood_pressure); ?>" placeholder="120/80">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">معدل ضربات القلب</label>
                                            <input type="number" name="heart_rate" class="form-control" value="<?php echo e($emergency->heart_rate); ?>" placeholder="72">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">درجة الحرارة (°C)</label>
                                            <input type="number" step="0.1" name="temperature" class="form-control" value="<?php echo e($emergency->temperature); ?>" placeholder="37.0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">تشبع الأكسجين (SpO2 %)</label>
                                            <input type="number" name="oxygen_saturation" class="form-control" value="<?php echo e($emergency->oxygen_saturation); ?>" placeholder="98">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">معدل التنفس (دقيقة)</label>
                                            <input type="number" name="respiratory_rate" class="form-control" value="<?php echo e($emergency->respiratory_rate); ?>" placeholder="16">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <strong>التقييم والخدمة</strong>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">التشخيص</label>
                                        <input type="text" name="diagnosis" class="form-control" value="<?php echo e($emergency->diagnosis); ?>" placeholder="اكتب التشخيص هنا...">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">الخدمات المقدمة</label>
                                        <div class="service-rows" id="service-rows-<?php echo e($emergency->id); ?>">
                                            <?php
                                                $selectedServiceIds = $emergency->services->pluck('id')->all();
                                            ?>
                                            <?php if(count($selectedServiceIds)): ?>
                                                <?php $__currentLoopData = $selectedServiceIds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceId): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="service-row d-flex gap-2 align-items-start mb-2">
                                                        <select name="service_ids[]" class="form-select">
                                                            <option value="">اختر الخدمة</option>
                                                            <?php $__currentLoopData = $emergencyServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                <option value="<?php echo e($service->id); ?>" <?php if($serviceId == $service->id): echo 'selected'; endif; ?>>
                                                                    <?php echo e($service->name); ?> - <?php echo e(number_format($service->price)); ?> IQD
                                                                </option>
                                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                        </select>
                                                        <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            <?php else: ?>
                                                <div class="service-row d-flex gap-2 align-items-start mb-2">
                                                    <select name="service_ids[]" class="form-select">
                                                        <option value="">اختر الخدمة</option>
                                                        <?php $__currentLoopData = $emergencyServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <option value="<?php echo e($service->id); ?>">
                                                                <?php echo e($service->name); ?> - <?php echo e(number_format($service->price)); ?> IQD
                                                            </option>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    </select>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm add-service-row" data-emergency-id="<?php echo e($emergency->id); ?>">
                                            <i class="fas fa-plus"></i> إضافة خدمة
                                        </button>
                                        <small class="text-muted d-block mt-2">يمكنك إضافة أكثر من خدمة للحالة الواحدة.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المعلومات</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
    ?>
    <?php if(!$hasConsultation): ?>
<div class="modal fade" id="consultationModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-labelledby="consultationModalLabel-<?php echo e($emergency->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="consultationModalLabel-<?php echo e($emergency->id); ?>">
                    <i class="fas fa-calendar-plus me-2"></i>
                    إنشاء موعد استشاري لحالة الطوارئ #<?php echo e($emergency->id); ?>

                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="<?php echo e(route('emergency.create-consultation', $emergency)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الموعد</label>
                            <input type="date" name="appointment_date" class="form-control" value="<?php echo e(now()->format('Y-m-d')); ?>" min="<?php echo e(now()->format('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">وقت الموعد</label>
                            <input type="time" name="appointment_time" class="form-control" value="<?php echo e(now()->addHour()->format('H:00')); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الطبيب الاستشاري</label>
                            <select name="doctor_id" class="form-select" required>
                                <option value="">اختر الطبيب</option>
                                <?php
                                    $consultantDoctors = \App\Models\Doctor::where('type', 'consultant')
                                        ->where('is_active', true)
                                        ->where('is_available_today', true)
                                        ->with('user', 'department')
                                        ->get();
                                ?>
                                <?php $__currentLoopData = $consultantDoctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($doctor->id); ?>">
                                        د. <?php echo e($doctor->user->name); ?> - <?php echo e($doctor->department->name ?? 'غير محدد'); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <?php if($consultantDoctors->isEmpty()): ?>
                                    <option value="" disabled>لا يوجد أطباء استشاريون متاحون اليوم</option>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">سبب الاستشارة</label>
                            <select name="reason" class="form-select" required>
                                <option value="">اختر السبب</option>
                                <option value="follow_up_emergency">متابعة حالة طوارئ</option>
                                <option value="specialist_consultation">استشارة متخصص</option>
                                <option value="surgery_consultation">استشارة جراحية</option>
                                <option value="chronic_condition">حالة مزمنة</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالاستشارة..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-calendar-check me-2"></i>
                        إنشاء الموعد الاستشاري
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="labModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-labelledby="labModalLabel-<?php echo e($emergency->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labModalLabel-<?php echo e($emergency->id); ?>">
                    <i class="fas fa-flask me-2"></i>
                    طلب تحاليل طبية - حالة طوارئ #<?php echo e($emergency->id); ?>

                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="<?php echo e(route('emergency.request-lab', $emergency)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر التحاليل المطلوبة للمريض: <strong><?php echo e($emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد'); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="urgent">عاجل</option>
                            <option value="critical">حرج</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التحاليل المطلوبة <span class="text-danger">*</span></label>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            <?php $__currentLoopData = $labTests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $test): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="lab_test_ids[]" value="<?php echo e($test->id); ?>" id="lab-<?php echo e($emergency->id); ?>-<?php echo e($test->id); ?>">
                                    <label class="form-check-label" for="lab-<?php echo e($emergency->id); ?>-<?php echo e($test->id); ?>">
                                        <?php echo e($test->name); ?>

                                        <small class="text-muted">(<?php echo e(number_format($test->price)); ?> IQD)</small>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالتحاليل..."></textarea>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>
                        تأكيد طلب التحاليل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="modal fade" id="radiologyModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-labelledby="radiologyModalLabel-<?php echo e($emergency->id); ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="radiologyModalLabel-<?php echo e($emergency->id); ?>">
                    <i class="fas fa-x-ray me-2"></i>
                    طلب أشعة - حالة طوارئ #<?php echo e($emergency->id); ?>

                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="<?php echo e(route('emergency.request-radiology', $emergency)); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر أنواع الأشعة المطلوبة للمريض: <strong><?php echo e($emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد'); ?></strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="urgent">عاجل</option>
                            <option value="critical">حرج</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">أنواع الأشعة المطلوبة <span class="text-danger">*</span></label>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            <?php $__currentLoopData = $radiologyTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="radiology_type_ids[]" value="<?php echo e($type->id); ?>" id="radiology-<?php echo e($emergency->id); ?>-<?php echo e($type->id); ?>">
                                    <label class="form-check-label" for="radiology-<?php echo e($emergency->id); ?>-<?php echo e($type->id); ?>">
                                        <?php echo e($type->name); ?>

                                        <small class="text-muted">(<?php echo e(number_format($type->price)); ?> IQD)</small>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالأشعة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check me-2"></i>
                        تأكيد طلب الأشعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $latestCompletedLab = $emergency->labRequests
            ->where('status', 'completed')
            ->sortByDesc('completed_at')
            ->first();
        $labResults = $latestCompletedLab
            ? $latestCompletedLab->labTests->filter(function($test){ return !empty(trim((string)($test->pivot->result ?? ''))); })->values()
            : collect();
    ?>
    <?php if($latestCompletedLab): ?>
    <div class="modal fade" id="labResultsModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-vial me-2"></i>
                        نتائج التحاليل - حالة طوارئ #<?php echo e($emergency->id); ?>

                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="result-meta mb-3">
                        <span class="badge bg-success">مكتمل</span>
                        <small class="text-muted ms-2"><?php echo e(optional($latestCompletedLab->completed_at)->format('d/m/Y H:i')); ?></small>
                    </div>
                    <?php if($labResults->count()): ?>
                        <?php $__currentLoopData = $labResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $test): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="result-card mb-2">
                                <div class="result-card__title"><?php echo e($test->name); ?></div>
                                <div class="result-card__value"><?php echo e($test->pivot->result); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <div class="alert alert-light border text-muted mb-0">تم إكمال الطلب بدون نتائج مدخلة.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__currentLoopData = $emergencies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emergency): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $latestCompletedRadiology = $emergency->radiologyRequests
            ->where('status', 'completed')
            ->sortByDesc('completed_at')
            ->first();
        $radiologyResults = $latestCompletedRadiology
            ? $latestCompletedRadiology->radiologyTypes->filter(function($type){ return !empty(trim((string)($type->pivot->result ?? ''))); })->values()
            : collect();
    ?>
    <?php if($latestCompletedRadiology): ?>
    <div class="modal fade" id="radiologyResultsModal-<?php echo e($emergency->id); ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-x-ray me-2"></i>
                        نتائج الأشعة - حالة طوارئ #<?php echo e($emergency->id); ?>

                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="result-meta mb-3">
                        <span class="badge bg-success">مكتمل</span>
                        <small class="text-muted ms-2"><?php echo e(optional($latestCompletedRadiology->completed_at)->format('d/m/Y H:i')); ?></small>
                    </div>
                    <?php if($radiologyResults->count()): ?>
                        <?php $__currentLoopData = $radiologyResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="result-card mb-2">
                                <div class="result-card__title">
                                    <?php echo e($type->name); ?>

                                    <?php if(!empty($type->pivot->image_path)): ?>
                                        <span class="badge bg-light text-dark border ms-2">مرفق</span>
                                    <?php endif; ?>
                                </div>
                                <div class="result-card__value"><?php echo e($type->pivot->result); ?></div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php else: ?>
                        <div class="alert alert-light border text-muted mb-0">تم إكمال الطلب بدون نتائج مدخلة.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('click', function(event) {
    if (event.target.closest('.add-service-row')) {
        const button = event.target.closest('.add-service-row');
        const emergencyId = button.getAttribute('data-emergency-id');
        const container = document.getElementById(`service-rows-${emergencyId}`);
        if (!container) {
            return;
        }
        const template = document.getElementById('service-row-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }

    if (event.target.closest('.remove-service-row')) {
        const row = event.target.closest('.service-row');
        const container = row.closest('.service-rows');
        if (container && container.querySelectorAll('.service-row').length > 1) {
            row.remove();
        } else if (row) {
            row.querySelector('select').value = '';
        }
    }
});
</script>

<template id="service-row-template">
    <div class="service-row d-flex gap-2 align-items-start mb-2">
        <select name="service_ids[]" class="form-select">
            <option value="">اختر الخدمة</option>
            <?php $__currentLoopData = $emergencyServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($service->id); ?>">
                    <?php echo e($service->name); ?> - <?php echo e(number_format($service->price)); ?> IQD
                </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

<style>
.medical-modal {
    border: 0;
    overflow: hidden;
}

.medical-modal__header {
    background: linear-gradient(120deg, #f8fafc 0%, #eef2f7 100%);
    border-bottom: 1px solid #e9ecef;
}

.medical-modal .card {
    border-radius: 12px;
}

.medical-modal .card-header {
    border-bottom: 1px solid #eef2f7;
}

.medical-modal .form-control {
    border-radius: 10px;
}

.medical-modal .form-select {
    border-radius: 10px;
}

.result-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.result-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 10px 12px;
    background: #f8fafc;
}

.result-card__title {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.result-card__value {
    color: #4b5563;
    white-space: pre-wrap;
}
</style>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/emergency/index.blade.php ENDPATH**/ ?>