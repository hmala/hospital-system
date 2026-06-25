{{-- resources/views/layouts/app.blade.php --}}
@php
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
    $surgeonStationCount = 0;
    $anesthesiaStationCount = 0;
    $residentStationCount = 0;
    $operationTheaterStationCount = 0;
    $nursingStationCount = 0;

    if (Auth::check()) {
        try {
            // عدادات بناءً على الصلاحيات وليس الأدوار
            if (Auth::user()->can('view surgeries')) {
                $pendingSurgeries = \App\Models\Surgery::whereIn('status', ['scheduled', 'waiting'])->count();
                $waitingSurgeries = \App\Models\Surgery::where('status', 'waiting')->count();
            }

            if (Auth::user()->can('view resident station')) {
                // عداد محطة المقيم (pre_op) - بعد الحجز مباشرة
                $residentStationCount = \App\Models\Surgery::where('status', 'scheduled')
                    ->where(function($q) {
                        $q->whereDoesntHave('residentStations', function($sq) {
                            $sq->where('phase', 'pre_op');
                        })->orWhereHas('residentStations', function($sq) {
                            $sq->where('phase', 'pre_op')
                              ->where('status', '!=', 'completed');
                        });
                    })->count();

                // عداد محطة المقيم (post_op) - بعد التخدير
                $residentStationCount += \App\Models\Surgery::whereHas('anesthesiaStation', function($q) {
                        $q->where('status', 'completed');
                    })
                    ->where(function($q) {
                        $q->whereDoesntHave('residentStations', function($sq) {
                            $sq->where('phase', 'post_op');
                        })->orWhereHas('residentStations', function($sq) {
                            $sq->where('phase', 'post_op')
                              ->where('status', '!=', 'completed');
                        });
                    })->count();
            }

            if (Auth::user()->can('view operation theater station')) {
                // عداد صالة العمليات - بعد المقيم pre_op
                $operationTheaterStationCount = \App\Models\Surgery::whereHas('residentStations', function($q) {
                        $q->where('phase', 'pre_op')
                          ->where('status', 'completed');
                    })
                    ->where(function($q) {
                        $q->whereDoesntHave('operationTheaterStation')
                          ->orWhereHas('operationTheaterStation', function($sq) {
                              $sq->where('status', '!=', 'completed');
                          });
                    })->count();
            }

            if (Auth::user()->can('view surgeon station')) {
                // عداد محطة الجراح - بعد صالة العمليات
                $surgeonStationCount = \App\Models\Surgery::whereHas('operationTheaterStation', function($q) {
                        $q->where('status', 'completed');
                    })
                    ->where(function($q) {
                        $q->whereDoesntHave('surgeonStation')
                          ->orWhereHas('surgeonStation', function($sq) {
                              $sq->where('status', '!=', 'completed');
                          });
                    })->count();
            }

            if (Auth::user()->can('view anesthesia station')) {
                // عداد محطة التخدير - بعد الجراح
                $anesthesiaStationCount = \App\Models\Surgery::whereHas('surgeonStation', function($q) {
                        $q->where('status', 'completed');
                    })
                    ->where(function($q) {
                        $q->whereDoesntHave('anesthesiaStation')
                          ->orWhereHas('anesthesiaStation', function($sq) {
                              $sq->where('status', '!=', 'completed');
                          });
                    })->count();
            }

            if (Auth::user()->can('view nursing station')) {
                // عداد محطة التمريض - بعد المقيم post_op
                $nursingStationCount = \App\Models\Surgery::whereHas('residentStations', function($q) {
                        $q->where('phase', 'post_op')
                          ->where('status', 'completed');
                    })
                    ->where(function($q) {
                        $q->whereDoesntHave('nursingStation')
                          ->orWhereHas('nursingStation', function($sq) {
                              $sq->where('status', '!=', 'completed');
                          });
                    })->count();
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
            if (Auth::user()->hasRole('radiology_staff') || Auth::user()->hasRole('التخدير') || Auth::user()->hasRole('admin')) {
                $pendingSurgeryRadiology = \App\Models\SurgeryRadiologyTest::where('status', 'pending')->count();
            }
            if (Auth::user()->hasRole('التخدير') || Auth::user()->hasRole('admin')) {
                // عداد محطة التخدير - بعد الجراح (موجود مسبقاً بالأعلى ولكن نؤكد عليه هنا إذا لزم الأمر)
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" rel="stylesheet" />
    <!-- select2 bootstrap4 theme removed due to CDN MIME issues; using default styles -->
    <!-- you can download and place a local copy in public/css/select2-bootstrap4-theme.min.css and uncomment below -->
    <!-- <link href="{{ asset('css/select2-bootstrap4-theme.min.css') }}" rel="stylesheet" /> -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        html, body {
            height: 100%;
        }
        body {
            background: linear-gradient(180deg, #dbeafe 0%, #bfdbfe 50%, #93c5fd 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
        }
        .sidebar {
            background: rgba(219, 234, 254, 0.92);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            height: 100vh;
            max-height: 100vh;
            border-left: none;
            position: fixed;
            width: 250px;
            overflow-x: hidden;
            overflow-y: auto;
            padding-bottom: 1.5rem;
            overscroll-behavior: contain;
            scrollbar-width: auto;
            scrollbar-color: rgba(148, 163, 184, 1) rgba(248, 250, 252, 0.85);
        }
        .sidebar::-webkit-scrollbar {
            width: 14px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.12);
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 1);
            border-radius: 12px;
            border: 3px solid rgba(255,255,255,0.08);
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(148, 163, 184, 1);
        }
        .sidebar-header { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 0.5rem; background: transparent; border-radius: 16px; margin: 0.75rem 0.75rem 0 0.75rem; padding: 1.25rem 1rem; }
        .sidebar-header img { background: rgba(59, 130, 246, 0.12); border-radius: 18px; padding: 10px; max-height: 70px; width: auto; border: 1px solid rgba(59, 130, 246, 0.45); box-shadow: 0 6px 18px rgba(59, 130, 246, 0.18); }
        .sidebar-user { color: #1d4ed8; font-weight: 700; display: flex; flex-direction: column; align-items: center; gap: 0.35rem; margin-top: 0.5rem; }
        .sidebar-user i { font-size: 1rem; color: #1d4ed8; }
        .sidebar-user span { display: block; color: #1d4ed8; }
        .sidebar-user .logout-link { color: #1d4ed8; font-size: 0.9rem; text-decoration: none; border: 1px solid rgba(59, 130, 246, 0.45); padding: 0.45rem 0.9rem; border-radius: 12px; background: rgba(59, 130, 246, 0.12); transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease; }
        .sidebar-user .logout-link:hover { background: rgba(59, 130, 246, 0.2); color: #1d4ed8; transform: translateY(-1px); }
        .home-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            margin-top: 0.5rem;
            padding: 0.5rem 0.85rem;
            border-radius: 12px;
            background: rgba(255,255,255,0.06);
            color: #f8fafc;
            border: 1px solid rgba(255,255,255,0.12);
            text-decoration: none;
            transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
        }
        .sidebar .nav-link { color: #1d4ed8; padding: 12px 20px; margin: 2px 0; border-radius: 8px; transition: all 0.3s; font-size: 0.9rem; width: 100%; display: inline-flex; align-items: center; justify-content: flex-start; }
        .sidebar .nav-link:hover { background-color: rgba(59, 130, 246, 0.12); color: #1d4ed8; transform: translateX(-5px); }
        .sidebar .nav-link.active { background: rgba(59, 130, 246, 0.18); color: #1d4ed8; box-shadow: 0 4px 6px rgba(15, 23, 42, 0.08); }
        .sidebar-section-title { color: #1d4ed8; font-size: 0.85rem; font-weight: 700; padding: 12px 20px; margin-top: 10px; cursor: pointer; background: rgba(59, 130, 246, 0.12); border-radius: 8px; transition: all 0.3s; display: flex; justify-content: space-between; align-items: center; }
        .sidebar-section-title:hover { background: rgba(59, 130, 246, 0.18); color: #1d4ed8; }
        .sidebar-section-title i.toggle-icon { transition: transform 0.3s; font-size: 0.8rem; }
        .sidebar-section-title.collapsed i.toggle-icon { transform: rotate(-90deg); }
        .sidebar-divider { border-top: 1px solid rgba(148, 163, 184, 0.25); margin: 10px 15px; }
        .collapse-section { padding-right: 0; }
        .main-content {
            margin-right: 250px;
            padding: 24px;
            transition: all 0.3s ease;
            width: calc(100% - 250px);
            min-height: 100vh;
            background: rgba(219, 234, 254, 0.92);
            border: none;
            box-shadow: none;
            backdrop-filter: blur(16px);
            border-radius: 0;
        }
        .main-content .card:not(.stat-card) {
            background: rgba(219, 234, 254, 0.48) !important;
            border: 1px solid rgba(96, 165, 250, 0.22) !important;
            box-shadow: 0 18px 40px rgba(59, 130, 246, 0.08) !important;
            border-radius: 18px !important;
        }
        .main-content .card:not(.stat-card) .card-body {
            padding: 1rem 1rem !important;
        }
        .main-content .table {
            background: rgba(219, 234, 254, 0.7);
            border-collapse: collapse;
            width: 100%;
            color: #1e3a8a;
        }
        .main-content .table th,
        .main-content .table td {
            border: 1px solid rgba(147, 197, 253, 0.32);
            vertical-align: middle;
            padding: 0.85rem 1rem;
        }
        .main-content .table thead th {
            background: rgba(147, 197, 253, 0.5);
            color: #1d4ed8;
            font-weight: 700;
            border-bottom-width: 2px;
        }
        .main-content .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: rgba(191, 219, 254, 0.72);
        }
        .main-content .table-hover > tbody > tr:hover {
            background-color: rgba(147, 197, 253, 0.24);
        }
        .main-content .table-responsive {
            background: rgba(191, 219, 254, 0.55);
            border: 1px solid rgba(147, 197, 253, 0.32);
            border-radius: 14px;
            padding: 0.6rem;
        }
        .main-content .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,0.12) !important;
        }
        .main-content .bg-warning:not(.stat-card):not(.card.border-0.shadow-sm),
        .main-content .bg-info:not(.stat-card):not(.card.border-0.shadow-sm),
        .main-content .bg-primary:not(.stat-card):not(.card.border-0.shadow-sm),
        .main-content .bg-success:not(.stat-card):not(.card.border-0.shadow-sm) {
            background: rgba(203, 213, 225, 0.24) !important;
            color: #0f172a !important;
        }
        .main-content .text-white:not(.stat-card .text-white) {
            color: #0f172a !important;
        }
        .stat-card {
            border-radius: 18px;
            border: 1px solid rgba(148, 163, 184, 0.18);
            background: rgba(229, 231, 235, 0.45);
            color: #0f172a;
            box-shadow: 0 18px 38px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            position: relative;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
        }
        .small-stat-card {
            min-height: 130px;
            max-height: 150px;
        }
        .small-stat-card .card-body {
            padding: 0.4rem 0.6rem !important;
        }
        .small-stat-card h5.card-title {
            font-size: 0.62rem;
            margin-bottom: 0.15rem;
        }
        .small-stat-card h2.mb-1 {
            font-size: 0.92rem;
        }
        .small-stat-card small {
            font-size: 0.62rem;
        }
        .small-stat-card .fa-2x {
            width: 28px;
            height: 28px;
            line-height: 28px;
        }
        .stat-card::before {
            content: '';
            position: absolute;
            width: 70px;
            height: 70px;
            top: -16px;
            right: -16px;
            background: rgba(15, 23, 42, 0.04);
            border-radius: 50%;
            filter: blur(10px);
        }
        .stat-card .card-body {
            padding: 0.35rem 0.55rem !important;
            position: relative;
            z-index: 1;
        }
        .stat-card .progress {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }
        .stat-card .progress-bar {
            background-color: rgba(255, 255, 255, 0.8);
        }
        .stat-card .row {
            margin: 0;
        }
        .stat-card .row > [class*='col-'] {
            padding-left: 0;
            padding-right: 0;
        }
        /* كارتات الغرف الصغيرة */
        .card.border-0.shadow-sm .card-body {
            padding: 0.3rem 0.5rem !important;
        }
        .card.border-0.shadow-sm .rounded-circle {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card.border-0.shadow-sm h4 {
            font-size: 0.85rem;
            font-weight: 600;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 46px rgba(15, 23, 42, 0.16);
        }
        .stat-card h5.card-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            opacity: 1;
            color: #ffffff;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.22);
        }
        .stat-card h2.mb-0,
        .stat-card h2.mb-1 {
            font-size: 2rem;
            font-weight: 900;
            line-height: 1.02;
            color: #ffffff;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.28);
        }
        .stat-card small {
            display: block;
            margin-top: 0.45rem;
            color: rgba(255, 255, 255, 0.92);
            font-size: 0.9rem;
            font-weight: 600;
            letter-spacing: 0.01em;
        }
        .stat-card .fa-3x,
        .stat-card .fa-4x {
            width: 32px;
            height: 32px;
            line-height: 32px;
            font-size: 0.75rem;
            border-radius: 10px;
            background: rgba(15, 23, 42, 0.06);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12);
            color: #0f172a !important;
        }
        .stat-card .fa {
            opacity: 0.82;
        }
        .stat-card.bg-warning { background: linear-gradient(135deg, #fbbf24, #f59e0b); color: #ffffff; border-color: #f59e0b; }
        .stat-card.bg-info { background: linear-gradient(135deg, #38bdf8, #0ea5e9); color: #ffffff; border-color: #0ea5e9; }
        .stat-card.bg-primary { background: linear-gradient(135deg, #60a5fa, #2563eb); color: #ffffff; border-color: #2563eb; }
        .stat-card.bg-success { background: linear-gradient(135deg, #34d399, #059669); color: #ffffff; border-color: #059669; }
        .stat-card.bg-patient { background: linear-gradient(135deg, #ec4899, #8b5cf6); color: #ffffff; border-color: #8b5cf6; }
        .stat-card.bg-doctor { background: linear-gradient(135deg, #0ea5e9, #0f766e); color: #ffffff; border-color: #0f766e; }
        .stat-card.bg-department { background: linear-gradient(135deg, #818cf8, #4338ca); color: #ffffff; border-color: #4338ca; }
        .stat-card.bg-appointment { background: linear-gradient(135deg, #fb923c, #f97316); color: #ffffff; border-color: #f97316; }
        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar .nav-link span { display: none; }
            .sidebar-section-title { display: none; }
            .main-content { margin-right: 70px; }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- الشريط الجانبي -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-0">
                    <div class="sidebar-header text-center p-3">
                        <img src="{{ asset('images/logo.jpeg') }}" alt="مستشفى الكفاءات الأهلي" class="img-fluid" style="max-height: 70px; width: auto; image-rendering: -webkit-optimize-contrast; image-rendering: crisp-edges;">
                        <div class="sidebar-user">
                            <div><i class="fas fa-user-circle"></i> <span>{{ Auth::user()->name }}</span></div>
                            <a href="{{ route('logout') }}" class="logout-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                تسجيل الخروج
                            </a>
                        </div>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                    <div class="sidebar-item mt-3">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <i class="fas fa-home me-2"></i>الرئيسية
                        </a>
                    </div>

                    <!-- روابط ثابتة حسب الصلاحيات -->
                        
                        @canany(['view patients', 'view inquiries', 'view cashier'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#patientMgmtSection" aria-expanded="false">
                            <span><i class="fas fa-user-injured"></i> إدارة المرضى</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="patientMgmtSection">
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
                                <span class="badge bg-secondary ms-2">{{ $pendingRequestsCount }}</span>
                            </a>
                        </li>
                        @endcan

                        @can('view occupancy')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('inquiry.occupancy') ? 'active' : '' }}" href="{{ route('inquiry.occupancy') }}">
                                <i class="fas fa-bed"></i><span> المرضى المقيمين</span>
                            </a>
                        </li>
                        @endcan

                        @can('view cashier')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cashier.index') || request()->routeIs('cashier.payment.*') || request()->routeIs('cashier.receipt*') ? 'active' : '' }}" href="{{ route('cashier.index') }}">
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
                        @endcan
                        @can('view cashier reports')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cashier.report') ? 'active' : '' }}" href="{{ route('cashier.report') }}">
                                <i class="fas fa-file-invoice-dollar"></i><span> سجل الفواتير</span>
                            </a>
                        </li>
                        @endcan
                        @can('view cashier surgeries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('cashier.surgeries.*') ? 'active' : '' }}" href="{{ route('cashier.surgeries.index') }}">
                                <i class="fas fa-procedures text-danger"></i><span> كاشير العمليات</span>
                                @php
                                    $pendingSurgeryPayments = \App\Models\Surgery::whereIn('payment_status', ['pending', 'partial'])
                                        ->whereIn('status', ['scheduled', 'waiting', 'in_progress', 'completed'])
                                        ->count();
                                @endphp
                                @if($pendingSurgeryPayments > 0)
                                    <span class="badge bg-danger ms-2">{{ $pendingSurgeryPayments }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                    </div> <!-- end patientMgmtSection -->
                    @endcanany

                    <!-- قسم نظام الحسابات -->
                    @canany(['view accounting', 'view accounting reports', 'create expenses'])
                    <div class="sidebar-divider"></div>
                    <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#accountingSection" aria-expanded="false">
                        <span><i class="fas fa-calculator"></i> نظام الحسابات</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="collapse collapse-section" id="accountingSection">
                        @can('view accounting')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('accounting.dashboard') ? 'active' : '' }}" href="{{ route('accounting.dashboard') }}">
                                <i class="fas fa-tachometer-alt"></i><span> لوحة الحسابات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('accounting.expenses.*') ? 'active' : '' }}" href="{{ route('accounting.expenses.index') }}">
                                <i class="fas fa-receipt"></i><span> المصروفات</span>
                                @php
                                    $pendingExpensesCount = \App\Models\Expense::where('status', 'pending')->count();
                                @endphp
                                @if($pendingExpensesCount > 0)
                                    <span class="badge bg-warning ms-2">{{ $pendingExpensesCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        @can('view accounting reports')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('accounting.reports.revenue') ? 'active' : '' }}" href="{{ route('accounting.reports.revenue') }}">
                                <i class="fas fa-chart-bar text-success"></i><span> تقرير الإيرادات</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('accounting.reports.expenses') ? 'active' : '' }}" href="{{ route('accounting.reports.expenses') }}">
                                <i class="fas fa-chart-pie text-danger"></i><span> تقرير المصروفات</span>
                            </a>
                        </li>
                        @endcan
                    </div>
                    @endcanany


                        <!-- قسم الطوارئ -->
                        @canany(['view emergencies', 'create emergencies', 'edit emergencies', 'manage emergency vitals'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#emergencySection" aria-expanded="false">
                            <span><i class="fas fa-ambulance"></i> الطوارئ</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="emergencySection">
                        @can('view emergencies')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('emergency.index') ? 'active' : '' }}" href="{{ route('emergency.index') }}">
                                <i class="fas fa-list"></i><span> حالات الطوارئ</span>
                                @php
                                    $criticalEmergencies = \App\Models\Emergency::where('priority', 'critical')
                                        ->whereNotIn('status', ['discharged', 'transferred'])
                                        ->count();
                                @endphp
                                @if($criticalEmergencies > 0)
                                    <span class="badge bg-danger ms-2">{{ $criticalEmergencies }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                      
                        @can('create emergencies')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('emergency.create') ? 'active' : '' }}" href="{{ route('emergency.create') }}">
                                <i class="fas fa-plus"></i><span> إضافة حالة طوارئ</span>
                            </a>
                        </li>
                        @endcan
                        </div>
                        @endcanany

                        <!-- قسم الأطباء والعيادات -->
                        @canany(['view doctors', 'view departments', 'manage own visits', 'manage consultant availability'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#doctorSection" aria-expanded="false">
                            <span><i class="fas fa-stethoscope"></i> الأطباء والعيادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="doctorSection">

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

                        @can('manage consultant availability')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('consultant-availability.index') ? 'active' : '' }}" href="{{ route('consultant-availability.index') }}">
                                <i class="fas fa-calendar-check"></i><span> توفر الأطباء الاستشاريين</span>
                            </a>
                        </li>
                        @endcan

                        @can('view departments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('departments.public') ? 'active' : '' }}" href="{{ route('departments.public') }}">
                                <i class="fas fa-clinic-medical"></i><span> العيادات</span>
                            </a>
                        </li>
                        @endcan

                        @can('manage own visits')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('doctor.visits.*') ? 'active' : '' }}" href="{{ route('doctor.visits.index') }}">
                                <i class="fas fa-user-md"></i><span> زياراتي</span>
                                <span class="badge bg-secondary ms-2">{{ $doctorIncompleteVisits }}</span>
                            </a>
                        </li>
                        @endcan

                        </div>
                        @endcanany

                        <!-- قسم المواعيد والزيارات -->
                        @canany(['view appointments', 'view visits', 'view own visits', 'create appointments'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#appointmentSection" aria-expanded="false">
                            <span><i class="fas fa-calendar-alt"></i> المواعيد والزيارات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="appointmentSection">

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

                        @can('view own visits')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('patient.visits.*') ? 'active' : '' }}" href="{{ route('patient.visits.index') }}">
                                <i class="fas fa-file-medical"></i><span> زياراتي</span>
                            </a>
                        </li>
                        @endcan

                        @can('create appointments')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                                <i class="fas fa-calendar-plus"></i><span> حجز موعد</span>
                            </a>
                        </li>
                        @endcan

                        </div>
                        @endcanany

                        <!-- قسم العمليات الجراحية -->
                        @canany(['view surgeries', 'create surgeries', 'manage rooms', 'manage surgery waiting list', 'view resident station', 'view operation theater station', 'view surgeon station', 'view anesthesia station', 'view nursing station'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#surgerySection" aria-expanded="false">
                            <span><i class="fas fa-procedures"></i> العمليات الجراحية</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="surgerySection">

                        @can('view surgeries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeries.*') ? 'active' : '' }}" href="{{ route('surgeries.index') }}">
                                <i class="fas fa-procedures"></i><span> العمليات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingSurgeries }}</span>
                            </a>
                        </li>
                        @endcan
                        @can('view surgical operations')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgical-operations.*') ? 'active' : '' }}" href="{{ route('surgical-operations.index') }}">
                                <i class="fas fa-cogs"></i><span> أنواع العمليات</span>
                            </a>
                        </li>
                        @endcan

                        @can('view resident station')
                        @if((!Auth::user()->hasRole('التخدير') && !Auth::user()->hasRole('الجراح')) || Auth::user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('resident-station.*') ? 'active' : '' }}" href="{{ route('resident-station.index') }}">
                                <i class="fas fa-user-graduate"></i><span> محطة المقيم</span>
                                @if($residentStationCount > 0)
                                    <span class="badge bg-primary ms-2">{{ $residentStationCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @endcan
                        
                        @can('view operation theater station')
                        @if((!Auth::user()->hasRole('التخدير') && !Auth::user()->hasRole('الجراح')) || Auth::user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('operation-theater-station.*') ? 'active' : '' }}" href="{{ route('operation-theater-station.index') }}">
                                <i class="fas fa-procedures"></i><span> صالة العمليات</span>
                                @if($operationTheaterStationCount > 0)
                                    <span class="badge bg-danger ms-2">{{ $operationTheaterStationCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @endcan
                        
                        @can('view surgeon station')
                        @if(Auth::user()->hasRole('الجراح') || (!Auth::user()->hasRole('التخدير') && !Auth::user()->hasRole('الجراح')) || Auth::user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeon-station.*') ? 'active' : '' }}" href="{{ route('surgeon-station.index') }}">
                                <i class="fas fa-user-md"></i><span> محطة الجراح</span>
                                @if($surgeonStationCount > 0)
                                    <span class="badge bg-info ms-2">{{ $surgeonStationCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @endcan
                        
                        @can('view anesthesia station')
                        @if(Auth::user()->hasRole('التخدير') || (!Auth::user()->hasRole('التخدير') && !Auth::user()->hasRole('الجراح')) || Auth::user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('anesthesia-station.*') ? 'active' : '' }}" href="{{ route('anesthesia-station.index') }}">
                                <i class="fas fa-syringe"></i><span> محطة التخدير</span>
                                @if($anesthesiaStationCount > 0)
                                    <span class="badge bg-warning ms-2">{{ $anesthesiaStationCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif
                        @endcan

                        @can('view nursing station')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('nursing-station.*') ? 'active' : '' }}" href="{{ route('nursing-station.index') }}">
                                <i class="fas fa-user-nurse"></i><span> محطة التمريض</span>
                                @if($nursingStationCount > 0)
                                    <span class="badge bg-success ms-2">{{ $nursingStationCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan

                        @can('manage rooms')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('rooms.*') ? 'active' : '' }}" href="{{ route('rooms.index') }}">
                                <i class="fas fa-bed text-danger"></i><span> إدارة الغرف</span>
                            </a>
                        </li>
                        @endcan

                        </div>
                        @endcanany

                        <!-- قسم المختبر والأشعة -->
                        @canany(['view radiology', 'create radiology', 'view lab tests', 'create lab tests', 'process pharmacy requests', 'manage surgery lab tests', 'view lab test groups'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#labSection" aria-expanded="false">
                            <span><i class="fas fa-microscope"></i> المختبر والأشعة</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="labSection">

                        @can('view radiology')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.*') ? 'active' : '' }}" href="{{ route('radiology.index') }}">
                                <i class="fas fa-x-ray"></i><span> الإشعة</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingRadiology }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology-staff.*') ? 'active' : '' }}" href="{{ route('radiology-staff.index') }}">
                                <i class="fas fa-user-md"></i><span> طلبات الأشعة</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingRadiology }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('radiology.types.*') ? 'active' : '' }}" href="{{ route('radiology.types.index') }}">
                                <i class="fas fa-file-medical-alt"></i><span> أنواع الأشعة</span>
                            </a>
                        </li>
                        @endcan
                                             @can('view surgeries')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('surgeries.waiting') ? 'active' : '' }}" href="{{ route('surgeries.waiting') }}">
                                <i class="fas fa-clock"></i><span> قائمة الانتظار</span>
                                @if($waitingSurgeries > 0)
                                <span class="badge bg-warning ms-2">{{ $waitingSurgeries }}</span>
                                @endif
                            </a>
                        </li>
                        @endcan
                        @can('process pharmacy requests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.requests.*') ? 'active' : '' }}" href="{{ route('staff.requests.index') }}">
                                <i class="fas fa-tasks"></i><span> الطلبات</span>
                            </a>
                        </li>
                        @endcan

                        @can('view lab tests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('lab.*') ? 'active' : '' }}" href="{{ route('lab.index') }}">
                                <i class="fas fa-flask"></i><span> طلبات المختبر</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingLab }}</span>
                            </a>
                        </li>
                        @endcan

                        @can('view packages')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.packages.*') ? 'active' : '' }}" href="{{ route('admin.packages.index') }}">
                                <i class="fas fa-boxes"></i><span> الباقات</span>
                            </a>
                        </li>
                        @endcan

                        @can('manage surgery lab tests')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-lab-tests.*') ? 'active' : '' }}" href="{{ route('staff.surgery-lab-tests.index') }}">
                                <i class="fas fa-flask"></i><span> تحاليل العمليات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingSurgeryLabTests }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-lab-tests.selection') ? 'active' : '' }}" href="{{ route('staff.surgery-lab-tests.selection') }}">
                                <i class="fas fa-list"></i><span> عمليات تحتاج اختيار تحاليل</span>
                            </a>
                        </li>
                        @endcan

                        @if(auth()->user()->hasRole('radiology_staff') || auth()->user()->hasRole('التخدير') || auth()->user()->hasRole('admin'))
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-radiology-tests.*') ? 'active' : '' }}" href="{{ route('staff.surgery-radiology-tests.index') }}">
                                <i class="fas fa-x-ray"></i><span> أشعة العمليات</span>
                                <span class="badge bg-secondary ms-2">{{ $pendingSurgeryRadiology }}</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('staff.surgery-radiology-tests.selection') ? 'active' : '' }}" href="{{ route('staff.surgery-radiology-tests.selection') }}">
                                <i class="fas fa-list"></i><span> عمليات تحتاج اختيار أشعة</span>
                            </a>
                        </li>
                        @endif

                        @can('view lab test groups')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('lab-tests.groups.*') ? 'active' : '' }}" href="{{ route('lab-tests.groups.index') }}">
                                <i class="fas fa-layer-group"></i> مجموعات المفضلات
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

                        </div>
                        @endcanany

                        <!-- قسم الإدارة والإعدادات -->
                        @canany(['manage users', 'manage roles', 'manage permissions', 'manage radiology types', 'view lab tests'])
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#adminSettingsSection" aria-expanded="false">
                            <span><i class="fas fa-tools"></i> الإدارة والإعدادات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="adminSettingsSection">

                        @can('manage users')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="fas fa-users-cog"></i><span> إدارة المستخدمين</span>
                            </a>
                        </li>
                        @endcan
                        @can('manage roles')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">
                                <i class="fas fa-user-shield"></i><span> إدارة الأدوار</span>
                            </a>
                        </li>
                        @endcan
                        @can('manage permissions')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('permissions.*') ? 'active' : '' }}" href="{{ route('permissions.index') }}">
                                <i class="fas fa-key"></i><span> إدارة الصلاحيات</span>
                            </a>
                        </li>
                        @endcan

                        @hasrole('admin')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('admin.doctor-commission-settings.*') ? 'active' : '' }}" href="{{ route('admin.doctor-commission-settings.index') }}">
                                <i class="fas fa-file-invoice-dollar"></i><span> إعدادات عمولات الأطباء</span>
                            </a>
                        </li>
                        @endhasrole

                        </div>
                        @endcanany

                        @hasanyrole('admin|cashier')
                        <div class="sidebar-divider"></div>
                        <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#accountingSection" aria-expanded="false">
                            <span><i class="fas fa-calculator"></i> الحسابيات</span>
                            <i class="fas fa-chevron-down toggle-icon"></i>
                        </div>
                        <div class="collapse collapse-section" id="accountingSection">
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.doctor-commission-settings.*') ? 'active' : '' }}" href="{{ route('admin.doctor-commission-settings.index') }}">
                                    <i class="fas fa-file-invoice-dollar"></i><span> إعدادات عمولات الأطباء</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('cashier.report') ? 'active' : '' }}" href="{{ route('cashier.report') }}">
                                    <i class="fas fa-chart-line"></i><span> تقارير الحسابات</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('consultant-availability.financial-movements') ? 'active' : '' }}" href="{{ route('consultant-availability.financial-movements') }}">
                                    <i class="fas fa-money-bill-wave"></i><span> الحركات المالية</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('cashier.statements') ? 'active' : '' }}" href="{{ route('cashier.statements') }}">
                                    <i class="fas fa-file-invoice-dollar"></i><span> كشوفات الحسابات</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('consultant-availability.doctor-accounts') ? 'active' : '' }}" href="{{ route('consultant-availability.doctor-accounts') }}">
                                    <i class="fas fa-wallet"></i><span> حسابات الأطباء</span>
                                </a>
                            </li>
                        </div>
                        @endhasanyrole

                   @canany(['manage inventory', 'view products', 'view suppliers', 'view purchases', 'view stock transfers', 'view stock transfer requests'])
                    <div class="sidebar-divider"></div>
                    <div class="sidebar-section-title collapsed" data-bs-toggle="collapse" data-bs-target="#inventorySection" aria-expanded="false">
                        <span><i class="fas fa-warehouse"></i> إدارة المخزون</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <div class="collapse collapse-section" id="inventorySection">
                        @canany(['manage inventory', 'view products'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                                <i class="fas fa-box"></i><span> المواد</span>
                            </a>
                        </li>
                        @endcanany
                        @canany(['manage inventory', 'view suppliers'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                                <i class="fas fa-truck"></i><span> الموردون</span>
                            </a>
                        </li>
                        @endcanany
                        @canany(['manage inventory', 'view inventory', 'create purchases', 'view cashier', 'view purchases'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('inventory.index') ? 'active' : '' }}" href="{{ route('inventory.index') }}">
                                <i class="fas fa-warehouse"></i><span> المخزون</span>
                            </a>
                        </li>
                        @endcanany
                        @can('manage locations')
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('locations.*') ? 'active' : '' }}" href="{{ route('locations.index') }}">
                                <i class="fas fa-map-marker-alt"></i><span> الأقسام</span>
                            </a>
                        </li>
                        @endcan
                        @canany(['view stock transfers', 'view stock transfer requests'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('stock-transfers.create') ? 'active' : '' }}" href="{{ route('stock-transfers.create') }}">
                                <i class="fas fa-exchange-alt"></i><span> نقل المخزون</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('stock-transfers.requests.*') ? 'active' : '' }}" href="{{ route('stock-transfers.requests.index') }}">
                                <i class="fas fa-clipboard-list"></i><span> طلبات النقل</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('stock-transfers.returns.*') ? 'active' : '' }}" href="{{ route('stock-transfers.returns.create') }}">
                                <i class="fas fa-undo"></i><span> إرجاع المخزون</span>
                            </a>
                        </li>
                        @endcanany
                        @canany(['manage inventory', 'create purchases', 'view cashier', 'view purchases'])
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('purchases.index') ? 'active' : '' }}" href="{{ route('purchases.index') }}">
                                <i class="fas fa-list-alt"></i><span> قائمة المشتريات</span>
                            </a>
                        </li>
                        @endcanany
                    </div>
                    @endcanany
                    <!-- نهاية القائمة القديمة -->
                </div>
            </nav>

            <!-- المحتوى الرئيسي -->
            <main class="main-content">
                <!-- محتوى الصفحة -->
                @yield('content')
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

    @stack('modals')
</body>
</html>
