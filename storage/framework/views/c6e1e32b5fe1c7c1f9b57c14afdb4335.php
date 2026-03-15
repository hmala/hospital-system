<!-- resources/views/emergency/create.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-plus-circle me-2"></i>
                    إضافة حالة طوارئ جديدة
                </h2>
                <a href="<?php echo e(route('emergency.index')); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">بيانات حالة الطوارئ</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('emergency.store')); ?>">
                        <?php echo csrf_field(); ?>

                        <div class="row">
                            <!-- اختيار المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">المريض *</label>
                                <div class="input-group">
                                    <select class="form-select <?php $__errorArgs = ['patient_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            id="patient_id"
                                            name="patient_id"
                                            required>
                                        <option value="">اختر المريض</option>
                                        <?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($patient->id); ?>" <?php echo e(old('patient_id') == $patient->id ? 'selected' : ''); ?>>
                                                <?php echo e($patient->user->name ?? 'مريض بدون بيانات'); ?> - <?php echo e($patient->user->phone ?? ''); ?>

                                            </option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-secondary" id="newPatientBtn" title="مريض جديد">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                </div>
                                <?php $__errorArgs = ['patient_id'];
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

                            <!-- حقول إنشاء مريض جديد -->
                            <div class="col-md-12 mb-3" id="newPatientFields" style="display: none;">
                                <div class="card border-info p-3">
                                    <h6 class="mb-3 text-info">بيانات المريض الجديد</h6>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">الاسم الكامل *</label>
                                            <input type="text" class="form-control <?php $__errorArgs = ['new_patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_patient_name" id="new_patient_name" value="<?php echo e(old('new_patient_name')); ?>">
                                            <?php $__errorArgs = ['new_patient_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">رقم الهاتف</label>
                                            <input type="text" class="form-control <?php $__errorArgs = ['new_patient_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_patient_phone" id="new_patient_phone" value="<?php echo e(old('new_patient_phone')); ?>">
                                            <?php $__errorArgs = ['new_patient_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">الجنس</label>
                                            <select class="form-select <?php $__errorArgs = ['new_patient_gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_patient_gender" id="new_patient_gender">
                                                <option value="" <?php if(old('new_patient_gender')==''): echo 'selected'; endif; ?>>غير محدد</option>
                                                <option value="male" <?php if(old('new_patient_gender')=='male'): echo 'selected'; endif; ?>>ذكر</option>
                                                <option value="female" <?php if(old('new_patient_gender')=='female'): echo 'selected'; endif; ?>>أنثى</option>
                                            </select>
                                            <?php $__errorArgs = ['new_patient_gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">تاريخ الميلاد</label>
                                            <input type="date" class="form-control <?php $__errorArgs = ['new_patient_dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" name="new_patient_dob" id="new_patient_dob" value="<?php echo e(old('new_patient_dob')); ?>">
                                            <?php $__errorArgs = ['new_patient_dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><div class="invalid-feedback"><?php echo e($message); ?></div><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        </div>

                                    </div>
                                </div>
                            </div>


                        <div class="row">
                            <!-- الأولوية -->
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">الأولوية *</label>
                                <select class="form-select <?php $__errorArgs = ['priority'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="priority"
                                        name="priority"
                                        required>
                                    <option value="">اختر الأولوية</option>
                                    <option value="critical" <?php echo e(old('priority') == 'critical' ? 'selected' : ''); ?>>حرجة</option>
                                    <option value="urgent" <?php echo e(old('priority') == 'urgent' ? 'selected' : ''); ?>>عاجلة</option>
                                    <option value="semi_urgent" <?php echo e(old('priority') == 'semi_urgent' ? 'selected' : ''); ?>>شبه عاجلة</option>
                                    <option value="non_urgent" <?php echo e(old('priority') == 'non_urgent' ? 'selected' : ''); ?>>غير عاجلة</option>
                                </select>
                                <?php $__errorArgs = ['priority'];
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

                            <!-- الطبيب المسؤول -->
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">الطبيب المسؤول</label>
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
                                    <option value="">اختر الطبيب (اختياري)</option>
                                    <?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($doctor->id); ?>" <?php echo e(old('doctor_id') == $doctor->id ? 'selected' : ''); ?>>
                                            <?php echo e($doctor->user->name ?? 'طبيب بدون بيانات'); ?> - <?php echo e($doctor->specialization ?? ''); ?>

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

                        <!-- description removed - not needed for quick booking -->


                        <!-- required actions removed -->

                        <div class="d-flex justify-content-end">
                            <a href="<?php echo e(route('emergency.index')); ?>" class="btn btn-secondary me-2">إلغاء</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ حالة الطوارئ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('newPatientBtn').addEventListener('click', function() {
        const fields = document.getElementById('newPatientFields');
        if (!fields) return;
        if (fields.style.display === 'block') {
            fields.style.display = 'none';
            document.getElementById('patient_id').setAttribute('required', 'required');
        } else {
            fields.style.display = 'block';
            document.getElementById('patient_id').removeAttribute('required');
        }
    });

    document.querySelector('form').addEventListener('submit', function(e) {
        const fields = document.getElementById('newPatientFields');
        if (fields && fields.style.display === 'block') {
            const name = document.getElementById('new_patient_name').value.trim();
            if (!name) {
                e.preventDefault();
                alert('يرجى إدخال اسم المريض الجديد');
                document.getElementById('new_patient_name').focus();
            }
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/emergency/create.blade.php ENDPATH**/ ?>