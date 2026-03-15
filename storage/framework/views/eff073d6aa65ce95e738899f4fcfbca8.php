

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital-user me-2"></i>
                        إنشاء طلب جديد - الاستعلامات
                    </h2>
                    <p class="text-muted">اختر نوع الخدمة المطلوبة للمريض</p>
                </div>
                <a href="<?php echo e(route('inquiry.search')); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة
                </a>
            </div>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <script>
            // بعد عرض رسالة النجاح، إعادة تعيين الحالة حتى يمكن إنشاء طلب جديد بسهولة
            document.addEventListener('DOMContentLoaded', function() {
                // تأخير قصير للسماح بعرض الرسالة قبل المسح
                setTimeout(() => {
                    // إلغاء تحديد البطاقات
                    document.querySelectorAll('.request-card').forEach(card => card.classList.remove('selected'));
                    // إخفاء قسم التفاصيل
                    const details = document.getElementById('requestDetails');
                    if (details) details.style.display = 'none';
                    // مسح الأنواع المختارة
                    selectedTypes.clear();
                    updateFormFields();
                }, 500);
            });
        </script>
    <?php endif; ?>

    <!-- معلومات المريض -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        بيانات المريض
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>الاسم:</strong>
                            <p class="text-muted"><?php echo e($patient->user->name); ?></p>
                        </div>
                        <div class="col-md-2">
                            <strong>العمر:</strong>
                            <p class="text-muted"><?php echo e($patient->age); ?> سنة</p>
                        </div>
                        <div class="col-md-2">
                            <strong>الجنس:</strong>
                            <p class="text-muted">
                                <?php if($patient->user->gender == 'male'): ?>
                                    <i class="fas fa-mars text-primary"></i> ذكر
                                <?php elseif($patient->user->gender == 'female'): ?>
                                    <i class="fas fa-venus text-danger"></i> أنثى
                                <?php else: ?>
                                    غير محدد
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-3">
                            <strong>رقم الهاتف:</strong>
                            <p class="text-muted"><?php echo e($patient->user->phone ?? 'غير متوفر'); ?></p>
                        </div>
                        <div class="col-md-2">
                            <strong>العنوان:</strong>
                            <p class="text-muted"><?php echo e($patient->user->address ?? 'غير متوفر'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- أنواع الطلبات -->
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="fas fa-list-check me-2"></i>
                اختر نوع الخدمة المطلوبة
                <small class="text-muted d-block mt-1">انقر على البطاقات لاختيار الخدمات (يمكن اختيار أكثر من خدمة)</small>
            </h4>
        </div>
    </div>

    <form action="<?php echo e(route('inquiry.store')); ?>" method="POST" id="requestForm">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="patient_id" value="<?php echo e($patient->id); ?>">
        <div id="requestTypesContainer">
            <!-- سيتم إضافة حقول request_type[] هنا عبر JavaScript -->
        </div>

        <div class="row g-4 mb-4">
            <!-- بطاقة المختبر -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="lab" onclick="toggleRequestType('lab')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-flask fa-4x text-primary"></i>
                        </div>
                        <h5 class="card-title">تحاليل طبية</h5>
                        <p class="card-text text-muted small">
                            فحوصات مخبرية وتحاليل الدم والبول
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <?php if($requestTypes['lab']['departments']->count() > 0): ?>
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php $__currentLoopData = $requestTypes['lab']['departments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><i class="fas fa-check-circle text-success"></i> <?php echo e($dept->name); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-primary">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الأشعة -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="radiology" onclick="toggleRequestType('radiology')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-x-ray fa-4x text-info"></i>
                        </div>
                        <h5 class="card-title">الأشعة</h5>
                        <p class="card-text text-muted small">
                            أشعة عادية، مقطعية، وتصوير بالرنين
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <?php if($requestTypes['radiology']['departments']->count() > 0): ?>
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php $__currentLoopData = $requestTypes['radiology']['departments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><i class="fas fa-check-circle text-success"></i> <?php echo e($dept->name); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-info">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الصيدلية -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="pharmacy" onclick="toggleRequestType('pharmacy')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-pills fa-4x text-success"></i>
                        </div>
                        <h5 class="card-title">الصيدلية</h5>
                        <p class="card-text text-muted small">
                            صرف أدوية ومستلزمات طبية
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <?php if($requestTypes['pharmacy']['departments']->count() > 0): ?>
                                <strong>الأقسام المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php $__currentLoopData = $requestTypes['pharmacy']['departments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><i class="fas fa-check-circle text-success"></i> <?php echo e($dept->name); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-danger">لا توجد أقسام متاحة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-success">محدد</span>
                    </div>
                </div>
            </div>

            <!-- بطاقة الكشف الطبي -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 shadow-sm request-card" data-type="checkup" onclick="toggleRequestType('checkup')">
                    <div class="card-body text-center p-4">
                        <div class="mb-3">
                            <i class="fas fa-stethoscope fa-4x text-warning"></i>
                        </div>
                        <h5 class="card-title">كشف طبي</h5>
                        <p class="card-text text-muted small">
                            استشارة طبية وكشف في العيادات
                        </p>
                        <div class="departments-list small text-muted" style="display: none;">
                            <?php if($requestTypes['checkup']['departments']->count() > 0): ?>
                                <strong>العيادات المتاحة:</strong>
                                <ul class="list-unstyled mt-2">
                                    <?php $__currentLoopData = $requestTypes['checkup']['departments']->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><i class="fas fa-check-circle text-success"></i> <?php echo e($dept->name); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <?php if($requestTypes['checkup']['departments']->count() > 3): ?>
                                        <li class="text-muted">... و <?php echo e($requestTypes['checkup']['departments']->count() - 3); ?> أخرى</li>
                                    <?php endif; ?>
                                </ul>
                            <?php else: ?>
                                <span class="text-danger">لا توجد عيادات متاحة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center" style="display: none;">
                        <span class="badge bg-warning">محدد</span>
                    </div>
                </div>
            </div>



            <!-- بطاقة حجز عملية جراحية -->
            <div class="col-md-6 col-lg-3">
                <a href="<?php echo e(route('surgeries.create', ['patient_id' => $patient->id])); ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm request-card surgery-card" style="cursor: pointer;">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-procedures fa-4x text-danger"></i>
                            </div>
                            <h5 class="card-title text-dark">حجز عملية جراحية</h5>
                            <p class="card-text text-muted small">
                                حجز موعد لإجراء عملية جراحية
                            </p>
                            <div class="mt-2">
                                <span class="badge bg-danger">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    انتقال لنموذج الحجز
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- بطاقة رقود مبدايا -->
            <div class="col-md-6 col-lg-3">
                <a href="<?php echo e(route('bed-reservations.create', ['patient_id' => $patient->id])); ?>" class="text-decoration-none">
                    <div class="card h-100 shadow-sm request-card surgery-card" style="cursor: pointer;">
                        <div class="card-body text-center p-4">
                            <div class="mb-3">
                                <i class="fas fa-bed fa-4x text-info"></i>
                            </div>
                            <h5 class="card-title text-dark">حجز رقود مبدئي</h5>
                            <p class="card-text text-muted small">
                                احجز سريراً للإقامة أو التحضير للعملية
                            </p>
                            <div class="mt-2">
                                <span class="badge bg-info">
                                    <i class="fas fa-external-link-alt me-1"></i>
                                    انتقال لنموذج الحجز
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- نموذج التفاصيل -->
        <div id="requestDetails" style="display: none;">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-edit me-2"></i>
                                تفاصيل الطلب
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- حقول عامة - تظهر للكشف الطبي والصيدلية فقط -->
                            <div id="generalFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="description" class="form-label">
                                            <i class="fas fa-comment-medical me-1"></i>
                                            وصف الحالة / التفاصيل <span class="text-danger general-required">*</span>
                                        </label>
                                        <textarea class="form-control <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                                  id="description" 
                                                  name="description" 
                                                  rows="4" 
                                                  placeholder="اكتب وصفاً تفصيلياً للحالة أو الخدمة المطلوبة..."><?php echo e(old('description')); ?></textarea>
                                        <?php $__errorArgs = ['description'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="doctor_id" class="form-label">
                                            <i class="fas fa-user-md me-1"></i>
                                            الطبيب <span class="text-danger checkup-required" style="display: none;">*</span>
                                        </label>
                                        <select class="form-select <?php $__errorArgs = ['doctor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                                id="doctor_id" 
                                                name="doctor_id">
                                            <option value="">اختر الطبيب</option>
                                            <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($doctor->id); ?>" 
                                                        data-department="<?php echo e($doctor->department_id); ?>"
                                                        <?php if(old('doctor_id') == $doctor->id): echo 'selected'; endif; ?>>
                                                    د. <?php echo e($doctor->user->name); ?> - <?php echo e($doctor->specialization); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['doctor_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالكشف الطبي -->
                            <div id="checkupFields" style="display: none;">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="department_id" class="form-label">
                                            <i class="fas fa-clinic-medical me-1"></i>
                                            العيادة <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select <?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                                id="department_id" 
                                                name="department_id">
                                            <option value="">اختر العيادة</option>
                                            <?php $__currentLoopData = $requestTypes['checkup']['departments']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($dept->id); ?>" <?php if(old('department_id') == $dept->id): echo 'selected'; endif; ?>>
                                                    <?php echo e($dept->name); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['department_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="appointment_date" class="form-label">
                                            <i class="fas fa-calendar me-1"></i>
                                            تاريخ الموعد
                                        </label>
                                        <input type="date" 
                                               class="form-control <?php $__errorArgs = ['appointment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                               id="appointment_date" 
                                               name="appointment_date"
                                               value="<?php echo e(old('appointment_date', date('Y-m-d'))); ?>"
                                               min="<?php echo e(date('Y-m-d')); ?>">
                                        <?php $__errorArgs = ['appointment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالتحاليل -->
                            <div id="labFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب تحويل عام للمختبر. 
                                            سيقوم موظف المختبر لاحقاً بتحديد التحاليل المطلوبة بالتفصيل قبل الدفع.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- حقول خاصة بالأشعة -->
                            <div id="radiologyFields" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>ملاحظة:</strong> سيتم إنشاء طلب تحويل عام لقسم الأشعة. 
                                            سيقوم موظف الأشعة لاحقاً بتحديد أنواع الأشعة المطلوبة بالتفصيل قبل الدفع.
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <div class="row mt-3">
                                <div class="col-12" id="autoReferContainer"">
                                    <div class="form-check">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               value="1" 
                                               id="autoRefer" 
                                               name="auto_refer"
                                               <?php echo e(old('auto_refer') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="autoRefer">
                                            <strong>التحويل التلقائي</strong> - الانتقال مباشرة لصفحة التحويل بعد إنشاء الطلب
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <span id="submitBtnText">إنشاء الطلب</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ملخص العملية -->
                    <div class="alert alert-info mt-3" role="alert" id="infoAlert">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>
                            ملاحظة
                        </h6>
                        <ul class="mb-0" id="infoList">
                            <li>سيتم إنشاء طلب جديد في قسم الاستعلامات</li>
                            <li>يمكنك بعد ذلك تحويل المريض للقسم المناسب</li>
                            <li>أو اختر "التحويل التلقائي" للانتقال مباشرة</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.request-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.request-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.request-card.selected {
    border-color: #0d6efd;
    background-color: #e3f2fd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.request-card.selected .card-footer {
    display: block !important;
}

.request-card.selected .departments-list {
    display: block !important;
}

.surgery-card:hover {
    border-color: #dc3545;
    background-color: #fff5f5;
}

.surgery-card:hover .fa-procedures {
    transform: scale(1.1);
    transition: transform 0.3s ease;
}
</style>

<script>
let selectedTypes = new Set();

function toggleRequestType(type) {
    const card = document.querySelector(`.request-card[data-type="${type}"]`);
    
    if (selectedTypes.has(type)) {
        // إلغاء التحديد
        selectedTypes.delete(type);
        card.classList.remove('selected');
        console.log('تم إلغاء اختيار:', type);
    } else {
        // إضافة التحديد
        selectedTypes.add(type);
        card.classList.add('selected');
        console.log('تم اختيار:', type);
    }
    
    // تحديث حقول النموذج
    updateFormFields();
    
    // عرض/إخفاء نموذج التفاصيل
    const details = document.getElementById('requestDetails');
    if (selectedTypes.size > 0) {
        details.style.display = 'block';
        updateDetailsForm();
    } else {
        details.style.display = 'none';
    }
}

function updateFormFields() {
    const container = document.getElementById('requestTypesContainer');
    container.innerHTML = '';
    
    selectedTypes.forEach(type => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'request_type[]';
        input.value = type;
        container.appendChild(input);
    });
}

function updateDetailsForm() {
    const checkupFields = document.getElementById('checkupFields');
    const labFields = document.getElementById('labFields');
    const radiologyFields = document.getElementById('radiologyFields');
    const generalFields = document.getElementById('generalFields');
    const autoReferContainer = document.getElementById('autoReferContainer');
    const submitBtnText = document.getElementById('submitBtnText');
    const infoList = document.getElementById('infoList');
    
    // إخفاء جميع الحقول الخاصة أولاً
    checkupFields.style.display = 'none';
    labFields.style.display = 'none';
    radiologyFields.style.display = 'none';
    generalFields.style.display = 'none';
    autoReferContainer.style.display = 'none';
    
    // إظهار الحقول حسب الأنواع المحددة
    if (selectedTypes.has('checkup')) {
        checkupFields.style.display = 'block';
        generalFields.style.display = 'block';
    }
    
    if (selectedTypes.has('lab')) {
        labFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('radiology')) {
        radiologyFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('pharmacy')) {
        generalFields.style.display = 'block';
        autoReferContainer.style.display = 'block';
    }
    
    if (selectedTypes.has('emergency')) {
        // حقول الطوارئ
        document.getElementById('emergencyFields').style.display = 'block';
    }
    
    // تحديث نص الزر والملاحظات
    if (selectedTypes.size === 1) {
        const type = Array.from(selectedTypes)[0];
        if (type === 'checkup') {
            submitBtnText.textContent = 'حجز موعد';
            infoList.innerHTML = `
                <li>سيتم حجز موعد للمريض مع الطبيب المحدد</li>
                <li>يمكن تحديد تاريخ الموعد أو اختيار اليوم</li>
                <li>سيتم إنشاء موعد في نظام المواعيد</li>
            `;
        } else if (type === 'lab') {
            submitBtnText.textContent = 'طلب تحاليل';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب تحاليل للمريض</li>
                <li>المريض يذهب للكاشير لدفع الأجور</li>
                <li>بعد الدفع، يتوجه للمختبر لإجراء التحاليل</li>
            `;
        } else if (type === 'radiology') {
            submitBtnText.textContent = 'طلب أشعة';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب أشعة للمريض</li>
                <li>المريض يذهب للكاشير لدفع الأجور</li>
                <li>بعد الدفع، يتوجه لقسم الأشعة لإجراء التصوير</li>
            `;
        } else {
            submitBtnText.textContent = 'إنشاء الطلب';
            infoList.innerHTML = `
                <li>سيتم إنشاء طلب جديد في قسم الاستعلامات</li>
                <li>يمكنك بعد ذلك تحويل المريض للقسم المناسب</li>
                <li>أو اختر "التحويل التلقائي" للانتقال مباشرة</li>
            `;
        }
    } else {
        submitBtnText.textContent = `إنشاء ${selectedTypes.size} طلبات`;
        infoList.innerHTML = `
            <li>سيتم إنشاء ${selectedTypes.size} طلبات مختلفة للمريض</li>
            <li>كل طلب سيتم معالجته حسب نوعه</li>
            <li>المريض سيحتاج للدفع لكل خدمة على حدة</li>
        `;
    }
    
    // التمرير السلس للنموذج
    setTimeout(() => {
        document.getElementById('requestDetails').scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }, 100);
}

// عند اختيار طبيب، ملء العيادة تلقائياً
document.getElementById('doctor_id').addEventListener('change', function() {
    if (selectedType === 'checkup' && this.value) {
        const selectedOption = this.options[this.selectedIndex];
        const departmentId = selectedOption.getAttribute('data-department');
        
        if (departmentId) {
            document.getElementById('department_id').value = departmentId;
        }
    }
});

// التحقق قبل الإرسال
document.getElementById('requestForm').addEventListener('submit', function(e) {
    if (selectedTypes.size === 0) {
        e.preventDefault();
        alert('يرجى اختيار نوع الخدمة أولاً');
        return false;
    }
    
    // التحقق من وصف الحالة للخدمات التي تحتاجها
    if (selectedTypes.has('checkup') || selectedTypes.has('pharmacy')) {
        const description = document.getElementById('description').value.trim();
        if (!description) {
            e.preventDefault();
            alert('يرجى كتابة وصف للحالة');
            document.getElementById('description').focus();
            return false;
        }
    }
    
    // إذا كان كشف طبي، التحقق من الطبيب والعيادة
    if (selectedTypes.has('checkup')) {
        const doctorId = document.getElementById('doctor_id').value;
        const departmentId = document.getElementById('department_id').value;
        
        if (!doctorId) {
            e.preventDefault();
            alert('يرجى اختيار الطبيب');
            document.getElementById('doctor_id').focus();
            return false;
        }
        
        if (!departmentId) {
            e.preventDefault();
            alert('يرجى اختيار العيادة');
            document.getElementById('department_id').focus();
            return false;
        }
    }
    
    // التحقق من حقول الطوارئ إذا تم اختيارها
    if (selectedTypes.has('emergency')) {
        const priority = document.getElementById('emergency_priority').value;
        const type = document.getElementById('emergency_type').value;
        const symptoms = document.getElementById('emergency_symptoms').value.trim();
        
        if (!priority || !type || !symptoms) {
            e.preventDefault();
            alert('يرجى ملء جميع حقول الطوارئ');
            return false;
        }
    }
});

// تحديث عداد التحاليل المختارة
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('lab-test-checkbox')) {
        const checkedCount = document.querySelectorAll('.lab-test-checkbox:checked').length;
        const counter = document.getElementById('labSelectedCount');
        if (checkedCount > 0) {
            counter.innerHTML = `<i class="fas fa-check-circle text-success"></i> تم اختيار ${checkedCount} تحليل`;
        } else {
            counter.innerHTML = '';
        }
    }
});

// وظيفة البحث في التحاليل
const labSearchInput = document.getElementById('labSearchInput');
const clearLabSearch = document.getElementById('clearLabSearch');

if (labSearchInput) {
    labSearchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim().toLowerCase();
        const labItems = document.querySelectorAll('#labTestsContainer .form-check');
        const labCategories = document.querySelectorAll('#labTestsContainer > div');
        
        labItems.forEach(item => {
            const label = item.querySelector('label');
            const text = label ? label.textContent.toLowerCase() : '';
            
            if (text.includes(searchTerm) || searchTerm === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
        
        // إخفاء/إظهار الفئات الفارغة
        labCategories.forEach(category => {
            const visibleItems = category.querySelectorAll('.form-check:not([style*="display: none"])');
            if (visibleItems.length === 0 && searchTerm !== '') {
                category.style.display = 'none';
            } else {
                category.style.display = '';
            }
        });
    });
    
    clearLabSearch.addEventListener('click', function() {
        labSearchInput.value = '';
        labSearchInput.dispatchEvent(new Event('input'));
        labSearchInput.focus();
    });
}

// إذا كان هناك خطأ في الصيغة، عرض النموذج مباشرة
<?php if($errors->any()): ?>
    window.addEventListener('DOMContentLoaded', function() {
        const oldTypes = <?php echo json_encode(old('request_type', []), 512) ?>;
        if (oldTypes && oldTypes.length > 0) {
            oldTypes.forEach(type => {
                toggleRequestType(type);
            });
        }
    });
<?php endif; ?>
</script>

<style>

</style>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/inquiry/create.blade.php ENDPATH**/ ?>