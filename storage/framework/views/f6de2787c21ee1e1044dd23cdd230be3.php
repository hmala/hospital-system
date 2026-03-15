<!-- resources/views/patients/create.blade.php -->


<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-user-plus me-2"></i>
                    إضافة مريض جديد
                </h2>
                <a href="<?php echo e(route('patients.index')); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">البيانات الأساسية</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('patients.store')); ?>">
                        <?php echo csrf_field(); ?>

                        <?php if($errors->has('duplicate_patient')): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?php echo e($errors->first('duplicate_patient')); ?>

                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- الاسم -->
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">الاسم الكامل *</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo e(old('name')); ?>"
                                       required 
                                       placeholder="ادخل الاسم الكامل">
                                <?php $__errorArgs = ['name'];
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

                            <!-- البريد الإلكتروني -->
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">البريد الإلكتروني</label>
                                <input type="email" 
                                       class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo e(old('email')); ?>"
                                       placeholder="example@email.com">
                                <?php $__errorArgs = ['email'];
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

                        <div class="row">
                            <!-- الهاتف -->
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">رقم الهاتف *</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo e(old('phone')); ?>"
                                       required 
                                       placeholder="ادخل رقم الهاتف">
                                <?php $__errorArgs = ['phone'];
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

                            <!-- تاريخ الميلاد -->
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">تاريخ الميلاد *</label>
                                <input type="date" 
                                       class="form-control <?php $__errorArgs = ['date_of_birth'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="date_of_birth" 
                                       name="date_of_birth" 
                                       value="<?php echo e(old('date_of_birth')); ?>"
                                       required>
                                <?php $__errorArgs = ['date_of_birth'];
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

                        <div class="row">
                            <!-- النوع -->
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">الجنس *</label>
                                <select class="form-select <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="gender" name="gender" required>
                                    <option value="">اختر النوع</option>
                                    <option value="male" <?php echo e(old('gender') == 'male' ? 'selected' : ''); ?>>ذكر</option>
                                    <option value="female" <?php echo e(old('gender') == 'female' ? 'selected' : ''); ?>>أنثى</option>
                                </select>
                                <?php $__errorArgs = ['gender'];
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

                            <!-- العنوان -->
                           
                        </div>

                        <div class="row">
                            <!-- اسم الأم الثلاثي -->
                            <div class="col-md-6 mb-3">
                                <label for="mother_name" class="form-label">اسم الأم الثلاثي *</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['mother_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="mother_name" 
                                       name="mother_name" 
                                       value="<?php echo e(old('mother_name')); ?>"
                                       required
                                       placeholder="ادخل اسم الأم الثلاثي">
                                <?php $__errorArgs = ['mother_name'];
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

                            <!-- الحالة الاجتماعية -->
                            <div class="col-md-6 mb-3">
                                <label for="marital_status" class="form-label">الحالة الاجتماعية *</label>
                                <select class="form-select <?php $__errorArgs = ['marital_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="marital_status" name="marital_status" required>
                                    <option value="">اختر الحالة الاجتماعية</option>
                                    <option value="أعزب" <?php echo e(old('marital_status') == 'أعزب' ? 'selected' : ''); ?>>أعزب</option>
                                    <option value="متزوج" <?php echo e(old('marital_status') == 'متزوج' ? 'selected' : ''); ?>>متزوج</option>
                                    <option value="مطلق" <?php echo e(old('marital_status') == 'مطلق' ? 'selected' : ''); ?>>مطلق</option>
                                    <option value="أرمل" <?php echo e(old('marital_status') == 'أرمل' ? 'selected' : ''); ?>>أرمل</option>
                                </select>
                                <?php $__errorArgs = ['marital_status'];
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

                        <hr>

                        <h6 class="mb-3">المعلومات الطبية</h6>

                        <div class="row">
                            <!-- رقم الطوارئ -->
                            <div class="col-md-6 mb-3">
                                <label for="emergency_contact" class="form-label">رقم الطوارئ</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['emergency_contact'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="emergency_contact" 
                                       name="emergency_contact" 
                                       value="<?php echo e(old('emergency_contact')); ?>"
                                       placeholder="رقم شخص للطوارئ">
                                <?php $__errorArgs = ['emergency_contact'];
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

                            <!-- فصيلة الدم -->
                            <div class="col-md-6 mb-3">
                                <label for="blood_type" class="form-label">فصيلة الدم</label>
                                <select class="form-select <?php $__errorArgs = ['blood_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="blood_type" name="blood_type">
                                    <option value="">اختر فصيلة الدم</option>
                                    <option value="A+" <?php echo e(old('blood_type') == 'A+' ? 'selected' : ''); ?>>A+</option>
                                    <option value="A-" <?php echo e(old('blood_type') == 'A-' ? 'selected' : ''); ?>>A-</option>
                                    <option value="B+" <?php echo e(old('blood_type') == 'B+' ? 'selected' : ''); ?>>B+</option>
                                    <option value="B-" <?php echo e(old('blood_type') == 'B-' ? 'selected' : ''); ?>>B-</option>
                                    <option value="AB+" <?php echo e(old('blood_type') == 'AB+' ? 'selected' : ''); ?>>AB+</option>
                                    <option value="AB-" <?php echo e(old('blood_type') == 'AB-' ? 'selected' : ''); ?>>AB-</option>
                                    <option value="O+" <?php echo e(old('blood_type') == 'O+' ? 'selected' : ''); ?>>O+</option>
                                    <option value="O-" <?php echo e(old('blood_type') == 'O-' ? 'selected' : ''); ?>>O-</option>
                                </select>
                                <?php $__errorArgs = ['blood_type'];
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

                        <div class="row">
                            <!-- الرقم الوطني -->
                            <div class="col-md-6 mb-3">
                                <label for="national_id" class="form-label">الرقم الوطني *</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['national_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="national_id" 
                                       name="national_id" 
                                       value="<?php echo e(old('national_id')); ?>"
                                       required
                                       placeholder="الرقم الوطني">
                                <?php $__errorArgs = ['national_id'];
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

                            <!-- هل مشمول بالضمان -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">هل مشمول بالضمان؟ *</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="covered_by_insurance" id="covered_yes" value="1" <?php echo e(old('covered_by_insurance') == '1' ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="covered_yes">نعم</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="covered_by_insurance" id="covered_no" value="0" <?php echo e(old('covered_by_insurance') == '0' ? 'checked' : ''); ?>>
                                    <label class="form-check-label" for="covered_no">كلا</label>
                                </div>
                                <?php $__errorArgs = ['covered_by_insurance'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row">
                            <!-- رقم دفتر التأمين -->
                            <div class="col-md-6 mb-3" id="insurance_booklet_div" style="display: none;">
                                <label for="insurance_booklet_number" class="form-label">رقم دفتر التأمين</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['insurance_booklet_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="insurance_booklet_number" 
                                       name="insurance_booklet_number" 
                                       value="<?php echo e(old('insurance_booklet_number')); ?>"
                                       placeholder="رقم دفتر التأمين">
                                <?php $__errorArgs = ['insurance_booklet_number'];
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

                        <div class="row">
                            <!-- البلد -->
                            <div class="col-md-3 mb-3">
                                <label for="country" class="form-label">البلد</label>
                                <select class="form-select <?php $__errorArgs = ['country'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="country" name="country">
                                    <option value="">اختر البلد</option>
                                    <?php $__currentLoopData = $countries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $countryItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($countryItem->id); ?>" <?php echo e(old('country') == $countryItem->id ? 'selected' : ($countryItem->id == $iraq_id ? 'selected' : '')); ?>><?php echo e($countryItem->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['country'];
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

                            <!-- المحافظة -->
                            <div class="col-md-3 mb-3">
                                <label for="governorate" class="form-label">المحافظة</label>
                                <select class="form-select <?php $__errorArgs = ['governorate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                        id="governorate" name="governorate">
                                    <option value="">اختر المحافظة</option>
                                    <?php $__currentLoopData = $governorates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $governorate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($governorate->name); ?>" <?php echo e(old('governorate') == $governorate->name ? 'selected' : ''); ?>><?php echo e($governorate->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['governorate'];
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

                            <!-- القضاء -->
                            <div class="col-md-3 mb-3">
                                <label for="district" class="form-label">القضاء</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['district'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="district" 
                                       name="district" 
                                       value="<?php echo e(old('district')); ?>"
                                       placeholder="القضاء">
                                <?php $__errorArgs = ['district'];
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

                            <!-- الناحية -->
                            <div class="col-md-3 mb-3">
                                <label for="neighborhood" class="form-label">الناحية</label>
                                <input type="text" 
                                       class="form-control <?php $__errorArgs = ['neighborhood'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" 
                                       id="neighborhood" 
                                       name="neighborhood" 
                                       value="<?php echo e(old('neighborhood')); ?>"
                                       placeholder="الناحية">
                                <?php $__errorArgs = ['neighborhood'];
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

                        <hr>

                    
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ المريض
                            </button>
                            <a href="<?php echo e(route('patients.index')); ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات سريعة -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>معلومات مهمة</h6>
                </div>
                <div class="card-body">
                    
                    <hr>
                    <h6>إرشادات:</h6>
                    <ul class="small text-muted">
                        <li>جميع الحقول المميزة بـ * إجبارية</li>
                        <li>يجب إدخال اسم الأم، الحالة الاجتماعية، حالة الضمان، والرقم الوطني</li>
                        <li>البريد الإلكتروني ورقم الطوارئ غير مطلوبين لكن يُستحسن إدخالهما</li>
                        <li>رقم الطوارئ مهم للحالات الطارئة</li>
                        <li>الايميل مهم للتواصل مع المريض</li>
                        <li>تاريخ الميلاد مهم لتحديد العمر</li>
                        <li>يرجى التأكد من صحة المعلومات قبل الحفظ</li>

                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->startSection('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // حساب العمر تلقائياً
    const dateOfBirthInput = document.getElementById('date_of_birth');
    
    dateOfBirthInput.addEventListener('change', function() {
        const birthDate = new Date(this.value);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        
        // التحقق من عيد الميلاد لهذا العام
        const monthDiff = today.getMonth() - birthDate.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        // يمكن إظهار العمر في مكان ما إذا أردت
        console.log('العمر:', age, 'سنة');
    });

    // التحكم في إظهار حقل رقم دفتر التأمين
    const coveredYes = document.getElementById('covered_yes');
    const coveredNo = document.getElementById('covered_no');
    const insuranceBookletDiv = document.getElementById('insurance_booklet_div');

    function toggleInsuranceBooklet() {
        if (coveredYes.checked) {
            insuranceBookletDiv.style.display = 'block';
        } else {
            insuranceBookletDiv.style.display = 'none';
        }
    }

    coveredYes.addEventListener('change', toggleInsuranceBooklet);
    coveredNo.addEventListener('change', toggleInsuranceBooklet);

    // التحقق الأولي عند تحميل الصفحة
    toggleInsuranceBooklet();
});
</script>
<?php $__env->stopSection(); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/hinpabye/public_html/hospital-system/resources/views/patients/create.blade.php ENDPATH**/ ?>