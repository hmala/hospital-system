<!-- resources/views/layouts/app.blade.php -->
@php
    // عدادات بسيطة للقائمة الجانبية
    $doctorIncompleteVisits = 0;
    $pendingSurgeries = 0;
    $pendingRequests = 0;
    $confirmedAppointments = 0;
    $incompleteVisits = 0;
    $pendingRadiology = 0;
    $pendingLab = 0;
    $pendingSurgeryLabTests = 0;
    $waitingSurgeries = 0;

    if (Auth::check()) {
        try {
            if (Auth::user()->isAdmin() || Auth::user()->isReceptionist() || Auth::user()->isDoctor()) {
                $pendingSurgeries = \App\Models\Surgery::whereIn('status', ['scheduled', 'waiting'])->count();
            }
            if (Auth::user()->isAdmin() || Auth::user()->isReceptionist()) {
                $pendingRequests = \App\Models\Request::where('status', 'pending')->count();
                $confirmedAppointments = \App\Models\Appointment::where('status', 'confirmed')->count();
                $incompleteVisits = \App\Models\Visit::whereIn('status', ['pending', 'in_progress'])->count();
                $pendingRadiology = \App\Models\RadiologyRequest::where('status', 'pending')->count();
                $pendingLab = \App\Models\LabResult::where('status', 'pending')->count();
            }
            if (Auth::user()->hasRole(['lab_staff', 'admin', 'doctor'])) {
                $pendingSurgeryLabTests = \App\Models\SurgeryLabTest::where('status', 'pending')->count();
            }
            if (Auth::user()->hasRole('surgery_staff')) {
                $waitingSurgeries = \App\Models\Surgery::where('status', 'waiting')->count();
            }
            if (Auth::user()->isDoctor()) {
                $doctorIncompleteVisits = \App\Models\Visit::where('doctor_id', Auth::id())
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count();
            }
        } catch (\Exception $e) {
            // في حالة خطأ، نترك القيم الافتراضية
        }
    }
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>نظام المستشفى الأهلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
                    
                    <ul class="nav flex-column p-3">
                        <!-- الرئيسية -->
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i><span> لوحة التحكم</span>
                            </a>
                        </li>

                        <!-- قسم إدارة المرضى -->
                        @role('admin|receptionist|patient')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#patientSection" aria-expanded="false">
                            <span><i class="fas fa-users"></i> إدارة المرضى</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="patientSection">
                        @endrole
                        
                        @role('admin|receptionist')
                        @can('view patients')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}" href="{{ route('patients.index') }}">
                                <i class="fas fa-user-injured"></i><span> المرضى</span>
                            </a>
                        </li>
                        @endcan
                        @can('view inquiries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('inquiry.*') ? 'active' : '' }}" href="{{ route('inquiry.index') }}">
                                <i class="fas fa-concierge-bell"></i><span> الاستعلامات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingRequests }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|cashier')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cashier.*') ? 'active' : '' }}" href="{{ route('cashier.index') }}">
                                <i class="fas fa-cash-register"></i><span> الكاشير</span>
                                @php
                                    $pendingPayments = \App\Models\Appointment::where('payment_status', 'pending')
                                        ->whereIn('status', ['scheduled', 'confirmed'])
                                        ->count();
                                @endphp
                                @if($pendingPayments > 0)
                                    <span class="badge bg-warning ms-2">{{ $pendingPayments }}</span>
                                @endif
                            </a>
                        </li>
                        @endrole

                        @role('admin|patient')
                        @can('view own visits')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patient.visits.*') ? 'active' : '' }}" href="{{ route('patient.visits.index') }}">
                                <i class="fas fa-file-medical"></i><span> زياراتي</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist|patient')
                        </div>
                        @endrole

                        <!-- قسم الأطباء والعيادات -->
                        @role('admin|receptionist|doctor|patient')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#doctorSection" aria-expanded="false">
                            <span><i class="fas fa-stethoscope"></i> الأطباء والعيادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="doctorSection">
                        @endrole

                        @role('admin|receptionist')
                        @can('view doctors')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctors.*') ? 'active' : '' }}" href="{{ route('doctors.index') }}">
                                <i class="fas fa-user-md"></i><span> الأطباء</span>
                            </a>
                        </li>
                        @endcan
                        @can('view departments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.admin') }}">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|patient')
                        @can('view departments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('departments.public') ? 'active' : '' }}" href="{{ route('departments.public') }}">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        @endcan
                        @can('view doctors')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctors.index') ? 'active' : '' }}" href="{{ route('doctors.index') }}">
                                <i class="fas fa-user-md"></i><span> الأطباء</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|doctor')
                        @can('manage own visits')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctor.visits.*') ? 'active' : '' }}" href="{{ route('doctor.visits.index') }}">
                                <i class="fas fa-user-md"></i><span> زياراتي</span>
                                <span class="badge bg-secondary ms-2">{{ $doctorIncompleteVisits }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist|doctor|patient')
                        </div>
                        @endrole

                        <!-- قسم المواعيد والزيارات -->
                        @role('admin|receptionist|patient')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#appointmentSection" aria-expanded="false">
                            <span><i class="fas fa-calendar-alt"></i> المواعيد والزيارات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="appointmentSection">
                        @endrole

                        @role('admin|receptionist')
                        @can('view appointments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                <i class="fas fa-calendar-check"></i><span> المواعيد</span>
                                <span class="badge bg-secondary ms-2">{{ $confirmedAppointments }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('view visits')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}" href="{{ route('visits.index') }}">
                                <i class="fas fa-file-medical"></i><span> الزيارات</span>
                                <span class="badge bg-secondary ms-2">{{ $incompleteVisits }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|patient')
                        @can('create appointments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                <i class="fas fa-calendar-plus"></i><span> حجز موعد</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist|patient')
                        </div>
                        @endrole

                        <!-- قسم العمليات الجراحية -->
                        @role('admin|receptionist|doctor|surgery_staff')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#surgerySection" aria-expanded="false">
                            <span><i class="fas fa-procedures"></i> العمليات الجراحية</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="surgerySection">
                        @endrole

                        @role('admin|receptionist|doctor|surgery_staff')
                        @can('view surgeries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeries.*') ? 'active' : '' }}" href="{{ route('surgeries.index') }}">
                                <i class="fas fa-procedures"></i><span> العمليات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingSurgeries }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|surgery_staff|receptionist')
                        @can('manage surgery waiting list')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeries.waiting') ? 'active' : '' }}" href="{{ route('surgeries.waiting') }}">
                                <i class="fas fa-clock"></i><span> قائمة الانتظار</span>
                                <span class="badge bg-secondary ms-2">{{ $waitingSurgeries }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|surgery_staff')
                        @can('control surgeries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeries.control') ? 'active' : '' }}" href="{{ route('surgeries.control') }}">
                                <i class="fas fa-cogs"></i><span> لوحة التحكم</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist|doctor|surgery_staff')
                        </div>
                        @endrole

                        <!-- قسم المختبر والأشعة -->
                        @role('admin|receptionist|lab_staff|radiology_staff|doctor')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#labSection" aria-expanded="false">
                            <span><i class="fas fa-microscope"></i> المختبر والأشعة</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="labSection">
                        @endrole

                        @role('admin|receptionist')
                        @can('view radiology')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.*') ? 'active' : '' }}" href="{{ route('radiology.index') }}">
                                <i class="fas fa-x-ray"></i><span> الإشعة</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingRadiology }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('create lab tests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.lab-visits.*') ? 'active' : '' }}" href="{{ route('staff.lab-visits.create') }}">
                                <i class="fas fa-flask"></i><span> زيارة مختبرية</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|lab_staff|radiology_staff|pharmacy_staff')
                        @php
                            $staffType = null;
                            if (Auth::user()->hasRole('lab_staff')) $staffType = 'lab';
                            elseif (Auth::user()->hasRole('radiology_staff')) $staffType = 'radiology';
                            elseif (Auth::user()->hasRole('pharmacy_staff')) $staffType = 'pharmacy';
                        @endphp
                        @if($staffType)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.requests.*') ? 'active' : '' }}" href="{{ route('staff.requests.index') }}">
                                <i class="fas fa-tasks"></i><span> الطلبات</span>
                                @if($staffType == 'lab')
                                    <span class="badge bg-secondary ms-2">{{ $pendingLab }}</span>
                                @elseif($staffType == 'radiology')
                                    <span class="badge bg-secondary ms-2">{{ $pendingRadiology }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @endrole

                        @role('admin|lab_staff|receptionist')
                        @can('manage surgery lab tests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-lab-tests.*') ? 'active' : '' }}" href="{{ route('staff.surgery-lab-tests.index') }}">
                                <i class="fas fa-flask"></i><span> تحاليل العمليات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingSurgeryLabTests }}</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('radiology_staff|admin|doctor')
                        @can('view radiology')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-radiology-tests.*') ? 'active' : '' }}" href="{{ route('staff.surgery-radiology-tests.index') }}">
                                <i class="fas fa-x-ray"></i><span> أشعة العمليات</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist|lab_staff|radiology_staff|doctor')
                        </div>
                        @endrole

                        <!-- قسم الإعدادات -->
                        @role('admin|receptionist')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#settingsSection" aria-expanded="false">
                            <span><i class="fas fa-cog"></i> الإعدادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="settingsSection">

                        @role('admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="fas fa-users-cog"></i><span> إدارة المستخدمين</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                                <i class="fas fa-user-shield"></i><span> إدارة الأدوار</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                                <i class="fas fa-key"></i><span> إدارة الصلاحيات</span>
                            </a>
                        </li>
                        @endrole

                        @can('manage radiology types')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.types.*') ? 'active' : '' }}" href="{{ route('radiology.types.index') }}">
                                <i class="fas fa-cogs"></i><span> أنواع الإشعة</span>
                            </a>
                        </li>
                        @endcan
                        @can('view lab tests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('lab-tests.*') ? 'active' : '' }}" href="{{ route('lab-tests.index') }}">
                                <i class="fas fa-flask"></i><span> أنواع التحاليل</span>
                            </a>
                        </li>
                        @endcan
                        @endrole

                        @role('admin|receptionist')
                        </div>
                        @endrole
                    </ul>
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
                                    <i class="fas fa-user-circle me-2"></i>{{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i>تسجيل الخروج
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- محتوى الصفحة -->
                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @yield('scripts')
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