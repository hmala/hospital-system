<!-- resources/views/layouts/app.blade.php -->
<?php
    // عدادات بسيطة للقائمة الجانبية
    $doctorIncompleteVisits = 0;
    $pendingSurgeries = 0;
    $pendingRequestsCount = 0;
    $confirmedAppointments = 0;
    $incompleteVisits = 0;
    $pendingRadiology = 0;
    $pendingLab = 0;
    $pendingSurgeryLabTests = 0;
    $waitingSurgeries = 0;

    if (Auth::check()) {
        try {
            // عدادات بناءً على الصلاحيات وليس الأدوار
            if (Auth::user()->can('view surgeries')) {
                $pendingSurgeries = \App\Models\Surgery::whereIn('status', ['scheduled', 'waiting'])->count();
                $waitingSurgeries = \App\Models\Surgery::where('status', 'waiting')->count();
            }
            if (Auth::user()->can('view inquiries')) {
                $pendingRequestsCount = \App\Models\Request::where('status', 'pending')->count();
            }
            if (Auth::user()->can('view appointments')) {
                $confirmedAppointments = \App\Models\Appointment::where('status', 'confirmed')->count();
            }
            if (Auth::user()->can('view visits')) {
                $incompleteVisits = \App\Models\Visit::whereIn('status', ['pending', 'in_progress'])->count();
            }
            if (Auth::user()->can('view radiology')) {
                $pendingRadiology = \App\Models\RadiologyRequest::where('status', 'pending')->count();
            }
            if (Auth::user()->can('view lab tests')) {
                $pendingLab = \App\Models\LabResult::where('status', 'pending')->count();
            }
            if (Auth::user()->can('manage surgery lab tests')) {
                $pendingSurgeryLabTests = \App\Models\SurgeryLabTest::where('status', 'pending')->count();
            }
            if (Auth::user()->can('manage own visits')) {
                $doctorIncompleteVisits = \App\Models\Visit::where('doctor_id', Auth::id())
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count();
            }
        } catch (\Exception $e) {
            // في حالة خطأ، نترك القيم الافتراضية
        }
    }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>نظام المستشفى الأهلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- select2 bootstrap4 theme removed due to CDN MIME issues; using default styles -->
    <!-- you can download and place a local copy in public/css/select2-bootstrap4-theme.min.css and uncomment below -->
    <!-- <link href="<?php echo e(asset('css/select2-bootstrap4-theme.min.css')); ?>" rel="stylesheet" /> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%); min-height: 100vh; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: fixed; width: 250px; overflow-y: auto; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 2px 0; border-radius: 8px; transition: all 0.3s; font-size: 0.9rem; }
        .sidebar .nav-link:hover { background-color: rgba(52, 152, 219, 0.2); color: #3498db; transform: translateX(-5px); }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #3498db, #2980b9); color: white; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3); }
        .sidebar-section-title { color: #bdc3c7; font-size: 0.85rem; font-weight: 600; padding: 12px 20px; margin-top: 10px; cursor: pointer; background: rgba(52, 152, 219, 0.1); border-radius: 8px; transition: all 0.3s; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-section-title:hover { background: rgba(52, 152, 219, 0.2); color: #3498db; }
        .sidebar-section-title i.toggle-icon { transition: transform 0.3s; font-size: 0.8rem; }
        .sidebar-section-title.collapsed i.toggle-icon { transform: rotate(-90deg); }
        .sidebar-divider { border-top: 1px solid rgba(255,255,255,0.1); margin: 10px 15px; }
        .collapse-section { padding-right: 0; }
        .main-content { margin-right: 250px; padding: 20px; transition: all 0.3s; }
        .stat-card { border-radius: 15px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); }
        .bg-patient { background: linear-gradient(45deg, #3498db, #2980b9); }
        .bg-doctor { background: linear-gradient(45deg, #27ae60, #2ecc71); }
        .bg-department { background: linear-gradient(45deg, #e74c3c, #c0392b); }
        .bg-appointment { background: linear-gradient(45deg, #f39c12, #e67e22); }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .sidebar-section-title { display: none; }
            .main-content { margin-right: 70px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-0">
                    <div class="sidebar-header text-center p-3 border-bottom border-secondary">
                        <i class="fas fa-hospital-alt fa-2x text-white mb-2"></i>
                        <h5 class="text-white mb-0">المستشفى الأهلي</h5>
                    </div>

                    <!-- روابط ثابتة حسب الصلاحيات -->
                        
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view patients', 'view inquiries', 'view cashier'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#patientMgmtSection" aria-expanded="false">
                            <span><i class="fas fa-user-injured"></i> إدارة المرضى</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="patientMgmtSection">
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view patients')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('patients.*') ? 'active' : ''); ?>" href="<?php echo e(route('patients.index')); ?>">
                                <i class="fas fa-user-injured"></i><span> المرضى</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view inquiries')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('inquiry.*') ? 'active' : ''); ?>" href="<?php echo e(route('inquiry.index')); ?>">
                                <i class="fas fa-concierge-bell"></i><span> الاستعلامات</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($pendingRequestsCount); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view occupancy')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('inquiry.occupancy') ? 'active' : ''); ?>" href="<?php echo e(route('inquiry.occupancy')); ?>">
                                <i class="fas fa-bed"></i><span> المرضى المقيمين</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('cashier.index') || request()->routeIs('cashier.payment.*') || request()->routeIs('cashier.receipt*') ? 'active' : ''); ?>" href="<?php echo e(route('cashier.index')); ?>">
                                <i class="fas fa-cash-register"></i><span> الكاشير</span>
                                <?php
                                    $pendingPayments = \App\Models\Appointment::where('payment_status', 'pending')
                                        ->whereIn('status', ['scheduled', 'confirmed'])
                                        ->count();
                                ?>
                                <?php if($pendingPayments > 0): ?>
                                    <span class="badge bg-warning ms-2"><?php echo e($pendingPayments); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier reports')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('cashier.report') ? 'active' : ''); ?>" href="<?php echo e(route('cashier.report')); ?>">
                                <i class="fas fa-file-invoice-dollar"></i><span> سجل الفواتير</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view cashier surgeries')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('cashier.surgeries.*') ? 'active' : ''); ?>" href="<?php echo e(route('cashier.surgeries.index')); ?>">
                                <i class="fas fa-procedures text-danger"></i><span> كاشير العمليات</span>
                                <?php
                                    $pendingSurgeryPayments = \App\Models\Surgery::whereIn('payment_status', ['pending', 'partial'])
                                        ->whereIn('status', ['scheduled', 'waiting'])
                                        ->count();
                                ?>
                                <?php if($pendingSurgeryPayments > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo e($pendingSurgeryPayments); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                    </div> <!-- end patientMgmtSection -->
                    <?php endif; ?>

                    <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view own visits')): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo e(request()->routeIs('patient.visits.*') ? 'active' : ''); ?>" href="<?php echo e(route('patient.visits.index')); ?>">
                            <i class="fas fa-file-medical"></i><span> زياراتي</span>
                        </a>
                    </li>
                    <?php endif; ?>
                        <!-- قسم الطوارئ -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view emergencies', 'create emergencies', 'edit emergencies', 'manage emergency vitals'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#emergencySection" aria-expanded="false">
                            <span><i class="fas fa-ambulance"></i> الطوارئ</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="emergencySection">
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view emergencies')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('emergency.index') ? 'active' : ''); ?>" href="<?php echo e(route('emergency.index')); ?>">
                                <i class="fas fa-list"></i><span> حالات الطوارئ</span>
                                <?php
                                    $criticalEmergencies = \App\Models\Emergency::where('priority', 'critical')
                                        ->whereNotIn('status', ['discharged', 'transferred'])
                                        ->count();
                                ?>
                                <?php if($criticalEmergencies > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo e($criticalEmergencies); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view emergencies')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('emergency.patients.*') ? 'active' : ''); ?>" href="<?php echo e(route('emergency.patients.index')); ?>">
                                <i class="fas fa-user-injured"></i><span> مرضى الطوارئ</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'create emergencies')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('emergency.create') ? 'active' : ''); ?>" href="<?php echo e(route('emergency.create')); ?>">
                                <i class="fas fa-plus"></i><span> إضافة حالة طوارئ</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- قسم الأطباء والعيادات -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view doctors', 'view departments', 'manage own visits', 'manage consultant availability'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#doctorSection" aria-expanded="false">
                            <span><i class="fas fa-stethoscope"></i> الأطباء والعيادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="doctorSection">

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view doctors')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('doctors.*') ? 'active' : ''); ?>" href="<?php echo e(route('doctors.index')); ?>">
                                <i class="fas fa-user-md"></i><span> الأطباء</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view departments')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('departments.*') ? 'active' : ''); ?>" href="<?php echo e(route('departments.admin')); ?>">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage consultant availability')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('consultant-availability.*') ? 'active' : ''); ?>" href="<?php echo e(route('consultant-availability.index')); ?>">
                                <i class="fas fa-calendar-check"></i><span> توفر الأطباء الاستشاريين</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view departments')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('departments.public') ? 'active' : ''); ?>" href="<?php echo e(route('departments.public')); ?>">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage own visits')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('doctor.visits.*') ? 'active' : ''); ?>" href="<?php echo e(route('doctor.visits.index')); ?>">
                                <i class="fas fa-user-md"></i><span> زياراتي</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($doctorIncompleteVisits); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <!-- قسم المواعيد والزيارات -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view appointments', 'view visits', 'create appointments'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#appointmentSection" aria-expanded="false">
                            <span><i class="fas fa-calendar-alt"></i> المواعيد والزيارات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="appointmentSection">

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view appointments')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('appointments.*') ? 'active' : ''); ?>" href="<?php echo e(route('appointments.index')); ?>">
                                <i class="fas fa-calendar-check"></i><span> المواعيد</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($confirmedAppointments); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view visits')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('visits.*') ? 'active' : ''); ?>" href="<?php echo e(route('visits.index')); ?>">
                                <i class="fas fa-file-medical"></i><span> الزيارات</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($incompleteVisits); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'create appointments')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('appointments.*') ? 'active' : ''); ?>" href="<?php echo e(route('appointments.index')); ?>">
                                <i class="fas fa-calendar-plus"></i><span> حجز موعد</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <!-- قسم العمليات الجراحية -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view surgeries', 'create surgeries', 'manage rooms', 'manage surgery waiting list'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#surgerySection" aria-expanded="false">
                            <span><i class="fas fa-procedures"></i> العمليات الجراحية</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="surgerySection">

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view surgeries')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('surgeries.*') ? 'active' : ''); ?>" href="<?php echo e(route('surgeries.index')); ?>">
                                <i class="fas fa-procedures"></i><span> العمليات</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($pendingSurgeries); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view surgical operations')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('surgical-operations.*') ? 'active' : ''); ?>" href="<?php echo e(route('surgical-operations.index')); ?>">
                                <i class="fas fa-cogs"></i><span> أنواع العمليات</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage rooms')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('rooms.*') ? 'active' : ''); ?>" href="<?php echo e(route('rooms.index')); ?>">
                                <i class="fas fa-bed text-danger"></i><span> إدارة الغرف</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <!-- قسم المختبر والأشعة -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['view radiology', 'create radiology', 'view lab tests', 'create lab tests', 'process pharmacy requests', 'manage surgery lab tests'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#labSection" aria-expanded="false">
                            <span><i class="fas fa-microscope"></i> المختبر والأشعة</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="labSection">

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view radiology')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('radiology.*') ? 'active' : ''); ?>" href="<?php echo e(route('radiology.index')); ?>">
                                <i class="fas fa-x-ray"></i><span> الإشعة</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($pendingRadiology); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'create lab tests')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('staff.lab-visits.*') ? 'active' : ''); ?>" href="<?php echo e(route('staff.lab-visits.create')); ?>">
                                <i class="fas fa-flask"></i><span> زيارة مختبرية</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view surgeries')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('surgeries.waiting') ? 'active' : ''); ?>" href="<?php echo e(route('surgeries.waiting')); ?>">
                                <i class="fas fa-clock"></i><span> قائمة الانتظار</span>
                                <?php if($waitingSurgeries > 0): ?>
                                <span class="badge bg-warning ms-2"><?php echo e($waitingSurgeries); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'process pharmacy requests')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('staff.requests.*') ? 'active' : ''); ?>" href="<?php echo e(route('staff.requests.index')); ?>">
                                <i class="fas fa-tasks"></i><span> الطلبات</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view lab tests')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('staff.requests.*') ? 'active' : ''); ?>" href="<?php echo e(route('staff.requests.index', ['type' => 'lab'])); ?>">
                                <i class="fas fa-flask"></i><span> طلبات المختبر</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($pendingLab); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage surgery lab tests')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('staff.surgery-lab-tests.*') ? 'active' : ''); ?>" href="<?php echo e(route('staff.surgery-lab-tests.index')); ?>">
                                <i class="fas fa-flask"></i><span> تحاليل العمليات</span>
                                <span class="badge bg-secondary ms-2"><?php echo e($pendingSurgeryLabTests); ?></span>
                            </a>
                        </li>
                        <?php endif; ?>

                        </div>
                        <?php endif; ?>

                        <!-- قسم الإعدادات -->
                        <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['manage users', 'manage roles', 'manage permissions', 'manage radiology types', 'view lab tests'])): ?>
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#settingsSection" aria-expanded="false">
                            <span><i class="fas fa-cog"></i> الإعدادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="settingsSection">

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage users')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('users.*') ? 'active' : ''); ?>" href="<?php echo e(route('users.index')); ?>">
                                <i class="fas fa-users-cog"></i><span> إدارة المستخدمين</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage roles')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('roles.*') ? 'active' : ''); ?>" href="<?php echo e(route('roles.index')); ?>">
                                <i class="fas fa-user-shield"></i><span> إدارة الأدوار</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage permissions')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('permissions.*') ? 'active' : ''); ?>" href="<?php echo e(route('permissions.index')); ?>">
                                <i class="fas fa-key"></i><span> إدارة الصلاحيات</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'manage radiology types')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('radiology.types.*') ? 'active' : ''); ?>" href="<?php echo e(route('radiology.types.index')); ?>">
                                <i class="fas fa-cogs"></i><span> أنواع الإشعة</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if (\Illuminate\Support\Facades\Blade::check('can', 'view lab tests')): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo e(request()->routeIs('lab-tests.*') ? 'active' : ''); ?>" href="<?php echo e(route('lab-tests.index')); ?>">
                                <i class="fas fa-flask"></i><span> أنواع التحاليل</span>
                            </a>
                        </li>
                        <?php endif; ?>

                        </div>
                        <?php endif; ?>
                    <!-- نهاية القائمة القديمة -->
                </div>
            </nav>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- شريط التنقل العلوي -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
                    <div class="container-fluid">
                        <div class="navbar-nav ms-auto">
                            <!-- قائمة المستخدم -->
                            <div class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle me-2"></i><?php echo e(Auth::user()->name); ?>

                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item text-danger" href="<?php echo e(route('logout')); ?>" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                                        </a>
                                        <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST" class="d-none"><?php echo csrf_field(); ?></form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- محتوى الصفحة -->
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // إعدادات toastr
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-left",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut",
            "rtl": true
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>
    <?php echo $__env->yieldContent('scripts'); ?>
    <script>
        // تحديث أيقونات القوائم المنسدلة
        document.addEventListener('DOMContentLoaded', function() {
            const collapsibles = document.querySelectorAll('[data-bs-toggle="collapse"]');
            collapsibles.forEach(function(element) {
                const targetId = element.getAttribute('data-bs-target');
                const target = document.querySelector(targetId);
                
                if (target) {
                    target.addEventListener('show.bs.collapse', function() {
                        element.classList.remove('collapsed');
                    });
                    
                    target.addEventListener('hide.bs.collapse', function() {
                        element.classList.add('collapsed');
                    });
                }
            });
        });

        function reloadRealtimeSections() {
            $('.realtime-section').each(function() {
                var section = $(this);
                var url = section.data('url') || window.location.href;
                $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'html',
                    success: function(data) {
                        var newContent = $(data).find('.realtime-section[data-section="' + section.data('section') + '"]').html();
                        if(newContent) section.html(newContent);
                    }
                });
            });
        }
        setInterval(reloadRealtimeSections, 5000); // كل 5 ثواني
    </script>
</body>
</html>
<?php /**PATH C:\wamp64\www\hospital-system\resources\views/layouts/app.blade.php ENDPATH**/ ?>