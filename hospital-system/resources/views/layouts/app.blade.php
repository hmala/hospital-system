<!-- resources/views/layouts/app.blade.php -->
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
        .sidebar { background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%); min-height: 100vh; box-shadow: 0 0 10px rgba(0,0,0,0.1); position: fixed; width: 250px; }
        .sidebar .nav-link { color: #ecf0f1; padding: 12px 20px; margin: 2px 0; border-radius: 8px; transition: all 0.3s; font-size: 0.9rem; }
        .sidebar .nav-link:hover { background-color: rgba(52, 152, 219, 0.2); color: #3498db; transform: translateX(-5px); }
        .sidebar .nav-link.active { background: linear-gradient(135deg, #3498db, #2980b9); color: white; box-shadow: 0 4px 6px rgba(52, 152, 219, 0.3); }
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
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i><span> لوحة التحكم</span>
                            </a>
                        </li>

                        <!-- روابط الإداري والرسبشن -->
                        @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patients.*') ? 'active' : '' }}" href="{{ route('patients.index') }}">
                                <i class="fas fa-user-injured"></i><span> المرضى</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('inquiry.*') ? 'active' : '' }}" href="{{ route('inquiry.index') }}">
                                <i class="fas fa-concierge-bell"></i><span> الاستعلامات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctors.*') ? 'active' : '' }}" href="{{ route('doctors.index') }}">
                                <i class="fas fa-user-md"></i><span> الأطباء</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('departments.*') ? 'active' : '' }}" href="{{ route('departments.index') }}">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                <i class="fas fa-calendar-check"></i><span> المواعيد</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('visits.*') ? 'active' : '' }}" href="{{ route('visits.index') }}">
                                <i class="fas fa-file-medical"></i><span> الزيارات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.*') ? 'active' : '' }}" href="{{ route('radiology.index') }}">
                                <i class="fas fa-x-ray"></i><span> الإشعة</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.types.*') ? 'active' : '' }}" href="{{ route('radiology.types.index') }}">
                                <i class="fas fa-cogs"></i><span> أنواع الإشعة</span>
                            </a>
                        </li>
                        @endif

                        <!-- روابط الطبيب -->
                        @if(Auth::user()->isDoctor())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctor.visits.*') ? 'active' : '' }}" href="{{ route('doctor.visits.index') }}">
                                <i class="fas fa-user-md"></i><span> زياراتي</span>
                            </a>
                        </li>
                        @endif

                        <!-- روابط المريض -->
                        @if(Auth::user()->isPatient())
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patient.visits.*') ? 'active' : '' }}" href="{{ route('patient.visits.index') }}">
                                <i class="fas fa-file-medical"></i><span> زياراتي</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                <i class="fas fa-calendar-plus"></i><span> حجز موعد</span>
                            </a>
                        </li>
                        @endif

                        <!-- روابط الموظفين الطبيين -->
                        @if(Auth::user()->isStaff() || Auth::user()->hasRole('receptionist'))
                        @php
                            $staffType = null;
                            if (Auth::user()->hasRole('lab_staff')) $staffType = 'lab';
                            elseif (Auth::user()->hasRole('radiology_staff')) $staffType = 'radiology';
                            elseif (Auth::user()->hasRole('pharmacy_staff')) $staffType = 'pharmacy';
                        @endphp
                        @if($staffType)
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.requests.*') ? 'active' : '' }}" href="{{ route('staff.requests.index', ['type' => $staffType]) }}">
                                <i class="fas fa-tasks"></i><span> الطلبات</span>
                            </a>
                        </li>
                        @endif

                        <!-- رابط التحويلات للموظفين الطبيين -->
                        @if(Auth::user()->isStaff() || Auth::user()->hasRole('receptionist'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.referrals.*') ? 'active' : '' }}" href="{{ route('staff.referrals.index') }}">
                                <i class="fas fa-exchange-alt"></i><span> التحويلات</span>
                            </a>
                        </li>
                        @endif

                        <!-- رابط إنشاء زيارة مختبرية مباشرة (لموظفي الاستقبال) -->
                        @if(Auth::user()->hasRole('receptionist'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.lab-visits.*') ? 'active' : '' }}" href="{{ route('staff.lab-visits.create') }}">
                                <i class="fas fa-flask"></i><span> زيارة مختبرية</span>
                            </a>
                        </li>
                        @endif
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- المحتوى الرئيسي -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- شريط التنقل العلوي -->
                <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow-sm mb-4">
                    <div class="container-fluid">
                        <div class="navbar-nav ms-auto">
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
</body>
</html>