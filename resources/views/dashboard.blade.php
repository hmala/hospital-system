<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700;900&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
    /* Premium Dashboard Overrides */
    body {
        font-family: 'Tajawal', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    }
    
    .dashboard-title-area {
        margin-bottom: 1.5rem;
    }
    
    .dashboard-title {
        font-weight: 900;
        font-size: 1.85rem;
        color: #1e3a8a;
        letter-spacing: -0.02em;
        position: relative;
        display: inline-block;
        padding-bottom: 8px;
    }
    
    .dashboard-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        right: 0;
        width: 60px;
        height: 4px;
        background: linear-gradient(90deg, #3b82f6, #60a5fa);
        border-radius: 2px;
    }

    /* Glassmorphism General Cards */
    .glass-card-dashboard {
        background: rgba(255, 255, 255, 0.45) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        border: 1px solid rgba(255, 255, 255, 0.55) !important;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.05) !important;
        border-radius: 22px !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        overflow: hidden;
    }
    .glass-card-dashboard:hover {
        transform: translateY(-4px);
        box-shadow: 0 16px 36px rgba(59, 130, 246, 0.1) !important;
        border-color: rgba(255, 255, 255, 0.75) !important;
        background: rgba(255, 255, 255, 0.55) !important;
    }
    .glass-card-dashboard .card-header {
        background: transparent !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.35) !important;
        padding: 1.25rem 1.5rem !important;
    }
    .glass-card-dashboard .card-header h5 {
        font-weight: 700;
        color: #1e3a8a;
        margin-bottom: 0;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .glass-card-dashboard .card-body {
        padding: 1.5rem !important;
    }

    /* Welcome Hero Banner */
    .welcome-banner-premium {
        background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 50%, #60a5fa 100%) !important;
        border: none !important;
        border-radius: 24px !important;
        position: relative;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(29, 78, 216, 0.2) !important;
        color: #ffffff !important;
    }
    .welcome-banner-premium::before {
        content: '';
        position: absolute;
        width: 320px;
        height: 320px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
        top: -120px;
        left: -120px;
        border-radius: 50%;
        pointer-events: none;
    }
    .welcome-banner-premium::after {
        content: '';
        position: absolute;
        width: 250px;
        height: 250px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0) 70%);
        bottom: -70px;
        right: 5%;
        border-radius: 50%;
        pointer-events: none;
    }
    .welcome-avatar-wrapper {
        position: relative;
        z-index: 2;
    }
    .welcome-avatar {
        width: 64px;
        height: 64px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #ffffff;
        font-size: 1.85rem;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s ease;
    }
    .welcome-banner-premium:hover .welcome-avatar {
        transform: scale(1.05) rotate(5deg);
    }
    .welcome-badge {
        background: rgba(255, 255, 255, 0.22);
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding: 5px 14px;
        border-radius: 50px;
        font-size: 0.82rem;
        font-weight: 700;
        color: #ffffff;
        backdrop-filter: blur(5px);
        letter-spacing: 0.02em;
    }
    .welcome-title {
        font-weight: 900;
        font-size: 1.95rem;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }
    .welcome-subtitle {
        color: rgba(255, 255, 255, 0.88);
        font-size: 0.98rem;
        font-weight: 500;
    }
    .welcome-stat-card {
        background: rgba(255, 255, 255, 0.11);
        border: 1px solid rgba(255, 255, 255, 0.18);
        border-radius: 18px;
        padding: 1rem 1.25rem;
        text-align: center;
        transition: all 0.3s ease;
        backdrop-filter: blur(8px);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }
    .welcome-stat-card:hover {
        background: rgba(255, 255, 255, 0.18);
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }
    .welcome-stat-card-value {
        font-family: 'Outfit', sans-serif;
        font-size: 1.8rem;
        font-weight: 900;
        line-height: 1.1;
        margin-bottom: 4px;
    }
    .welcome-stat-card-label {
        font-size: 0.82rem;
        color: rgba(255, 255, 255, 0.82);
        font-weight: 600;
    }

    /* Premium Stat Cards (General) */
    .stat-card-premium {
        border-radius: 22px !important;
        border: 1px solid rgba(255, 255, 255, 0.6) !important;
        background: rgba(255, 255, 255, 0.45) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 10px 30px rgba(31, 38, 135, 0.04) !important;
        overflow: hidden;
        position: relative;
    }
    .stat-card-premium:hover {
        transform: translateY(-6px);
        box-shadow: 0 18px 38px rgba(31, 38, 135, 0.09) !important;
        border-color: rgba(255, 255, 255, 0.8) !important;
        background: rgba(255, 255, 255, 0.55) !important;
    }
    .stat-card-premium::before {
        content: '';
        position: absolute;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, rgba(255, 255, 255, 0) 70%);
        top: -40px;
        left: -40px;
        pointer-events: none;
    }
    .stat-card-premium .card-body {
        padding: 1.5rem !important;
    }
    .stat-icon-wrapper {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.65rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }
    .stat-card-premium:hover .stat-icon-wrapper {
        transform: scale(1.1);
    }
    
    .stat-info-area {
        flex-grow: 1;
    }
    .stat-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 0.25rem;
    }
    .stat-card-value {
        font-family: 'Outfit', sans-serif;
        font-size: 2.15rem;
        font-weight: 900;
        color: #0f172a;
        line-height: 1;
        margin-bottom: 2px;
    }
    .stat-card-sub {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: 600;
    }

    /* Gradients and Colors for Icons */
    .icon-patient {
        background: linear-gradient(135deg, #fbcfe8 0%, #ec4899 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(236, 72, 153, 0.2) !important;
    }
    .icon-doctor {
        background: linear-gradient(135deg, #a5f3fc 0%, #06b6d4 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(6, 182, 212, 0.2) !important;
    }
    .icon-dept {
        background: linear-gradient(135deg, #ddd6fe 0%, #8b5cf6 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(139, 92, 246, 0.2) !important;
    }
    .icon-appts {
        background: linear-gradient(135deg, #ffedd5 0%, #f97316 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2) !important;
    }
    .icon-visits {
        background: linear-gradient(135deg, #dcfce7 0%, #10b981 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2) !important;
    }
    .icon-pending {
        background: linear-gradient(135deg, #fef3c7 0%, #f59e0b 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2) !important;
    }
    .icon-scheduled {
        background: linear-gradient(135deg, #e0f2fe 0%, #0284c7 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(2, 132, 199, 0.2) !important;
    }
    .icon-inprogress {
        background: linear-gradient(135deg, #e0e7ff 0%, #6366f1 100%);
        color: #ffffff;
        box-shadow: 0 10px 20px rgba(99, 102, 241, 0.2) !important;
    }

    /* Premium Progress Bar */
    .progress-premium {
        height: 5px;
        background-color: rgba(226, 232, 240, 0.8);
        border-radius: 10px;
        margin-top: 0.75rem;
        overflow: visible;
    }
    .progress-bar-premium {
        border-radius: 10px;
    }

    /* Room Status Cards */
    .room-status-sidebar {
        border: none !important;
        background: rgba(255, 255, 255, 0.45) !important;
        backdrop-filter: blur(14px) !important;
        -webkit-backdrop-filter: blur(14px) !important;
        border: 1px solid rgba(255, 255, 255, 0.55) !important;
        border-radius: 22px !important;
        box-shadow: 0 10px 30px rgba(59, 130, 246, 0.05) !important;
    }
    .room-status-sidebar .card-header {
        border-bottom: 1px solid rgba(255, 255, 255, 0.35) !important;
        background: transparent !important;
        padding: 1.25rem 1.25rem !important;
    }
    .room-status-sidebar .card-header h5 {
        font-weight: 700;
        color: #e11d48;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.1rem;
    }
    .room-status-sidebar .card-body {
        padding: 1.25rem !important;
    }
    
    .room-stat-item {
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        background: rgba(255, 255, 255, 0.35) !important;
        border-radius: 16px !important;
        transition: all 0.2s ease;
    }
    .room-stat-item:hover {
        background: rgba(255, 255, 255, 0.6) !important;
        transform: translateX(-4px);
        border-color: rgba(255, 255, 255, 0.8) !important;
        box-shadow: 0 5px 15px rgba(0,0,0,0.02) !important;
    }
    .room-stat-item .rounded-circle {
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    }
    .room-stat-item h6 {
        font-family: 'Outfit', sans-serif;
        font-size: 1.15rem;
        font-weight: 800;
        color: #0f172a;
    }
    .room-stat-item small {
        font-size: 0.8rem;
        color: #64748b;
        font-weight: 600;
    }

    /* Portal Call-to-action */
    .portal-banner {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid rgba(255, 255, 255, 0.7);
        box-shadow: 0 15px 35px rgba(31, 38, 135, 0.05);
        border-radius: 24px;
        overflow: hidden;
        position: relative;
    }
    .portal-banner-btn {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        color: #ffffff;
        border: none;
        padding: 0.85rem 2rem;
        border-radius: 16px;
        font-weight: 700;
        font-size: 1rem;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(29, 78, 216, 0.25);
    }
    .portal-banner-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 25px rgba(29, 78, 216, 0.35);
        color: #ffffff;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row mb-4 dashboard-title-area animate__animated animate__fadeIn">
        <div class="col-12">
            <h2 class="dashboard-title">
                <i class="fas fa-tachometer-alt me-2 text-primary"></i>
                لوحة التحكم
            </h2>
        </div>
    </div>



    @if(isset($radiologyStats))
    <!-- إحصائيات موظف الأشعة -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">طلبات معلقة</h5>
                        <h2 class="stat-card-value">{{ $radiologyStats['pending'] }}</h2>
                        <span class="stat-card-sub">تحتاج للمعالجة الفورية</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-pending shadow">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">مجدولة</h5>
                        <h2 class="stat-card-value">{{ $radiologyStats['scheduled'] }}</h2>
                        <span class="stat-card-sub">تم حجز موعد لها</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-scheduled shadow">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">قيد التنفيذ</h5>
                        <h2 class="stat-card-value">{{ $radiologyStats['in_progress'] }}</h2>
                        <span class="stat-card-sub">جاري العمل عليها الآن</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-inprogress shadow">
                            <i class="fas fa-play"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">اكتملت اليوم</h5>
                        <h2 class="stat-card-value">{{ $radiologyStats['completed_today'] }}</h2>
                        <span class="stat-card-sub">تمت معالجتها بنجاح اليوم</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-visits shadow">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات الأشعة -->
    <div class="row mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
        <div class="col-12">
            <div class="card portal-banner shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 mb-3 text-primary" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-x-ray fa-3x"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">بوابة طلبات الأشعة</h4>
                        <p class="text-muted mb-4 max-w-md">عرض وتحديث ونتائج فحوصات الأشعة للمرضى في النظام بشكل فوري ومباشر.</p>
                        <a href="{{ route('radiology.index') }}" class="portal-banner-btn">
                            الانتقال إلى طلبات الأشعة
                            <i class="fas fa-arrow-left ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @elseif(isset($labStats))
    <!-- إحصائيات موظف المختبر -->
    <div class="row mb-4">
        <div class="col-xl-6 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">طلبات معلقة</h5>
                        <h2 class="stat-card-value">{{ $labStats['pending'] }}</h2>
                        <span class="stat-card-sub">تحتاج للمعالجة الفورية</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-pending shadow">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">اكتملت اليوم</h5>
                        <h2 class="stat-card-value">{{ $labStats['completed_today'] }}</h2>
                        <span class="stat-card-sub">تم فحصها وتسليمها اليوم</span>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-visits shadow">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات المختبر -->
    <div class="row mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
        <div class="col-12">
            <div class="card portal-banner shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="d-flex flex-column align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 mb-3 text-primary" style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-flask fa-3x"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">بوابة تحاليل المختبر</h4>
                        <p class="text-muted mb-4 max-w-md">إدارة نتائج الفحوصات الطبية والمختبرية، بنك الدم، والطلبات الطبية المختلفة.</p>
                        <a href="{{ route('lab.index') }}" class="portal-banner-btn">
                            الانتقال إلى طلبات المختبر
                            <i class="fas fa-arrow-left ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @else
    <!-- الإحصائيات العامة للمستخدمين الآخرين -->
    <div class="row">
        <div class="col-xl col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">المرضى</h5>
                        <h2 class="stat-card-value">{{ $stats['totalPatients'] }}</h2>
                        <span class="stat-card-sub">مسجلين في النظام</span>
                        <div class="progress-premium">
                            <div class="progress-bar progress-bar-premium icon-patient" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-patient shadow">
                            <i class="fas fa-user-injured"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">الأطباء</h5>
                        <h2 class="stat-card-value">{{ $stats['totalDoctors'] }}</h2>
                        <span class="stat-card-sub">يعملون في المستشفى</span>
                        <div class="progress-premium">
                            <div class="progress-bar progress-bar-premium icon-doctor" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-doctor shadow">
                            <i class="fas fa-user-md"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">العيادات</h5>
                        <h2 class="stat-card-value">{{ $stats['totalDepartments'] }}</h2>
                        <span class="stat-card-sub">عيادة نشطة في النظام</span>
                        <div class="progress-premium">
                            <div class="progress-bar progress-bar-premium icon-dept" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-dept shadow">
                            <i class="fas fa-clinic-medical"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">مواعيد اليوم</h5>
                        <h2 class="stat-card-value">{{ $stats['todayAppointments'] }}</h2>
                        <span class="stat-card-sub">مجدولة لهذا اليوم</span>
                        <div class="progress-premium">
                            <div class="progress-bar progress-bar-premium icon-appts" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-appts shadow">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl col-lg-4 col-md-6 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.5s;">
            <div class="card stat-card-premium h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="stat-info-area">
                        <h5 class="stat-card-title">الزيارات اليوم</h5>
                        <h2 class="stat-card-value">{{ $stats['todayVisits'] }}</h2>
                        <span class="stat-card-sub">تم تسجيلها اليوم</span>
                        <div class="progress-premium">
                            <div class="progress-bar progress-bar-premium icon-visits" role="progressbar" style="width: 55%" aria-valuenow="55" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    <div class="ms-3">
                        <div class="stat-icon-wrapper icon-visits shadow">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- مخططات بيانية عامة مع عمود حالة الغرف -->
    <div class="row mb-4 gx-3 align-items-stretch">
        <div id="dashboardChartsColumn" class="col d-flex flex-column">
            <div class="row gx-3 gy-4 flex-fill">
                <div class="col-md-6 d-flex animate__animated animate__fadeIn" style="animation-delay: 0.6s;">
                    <div class="card glass-card-dashboard flex-fill">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line me-2 text-primary"></i>زيارات آخر 7 أيام</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="visitsByDayChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex animate__animated animate__fadeIn" style="animation-delay: 0.7s;">
                    <div class="card glass-card-dashboard flex-fill">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie me-2 text-warning"></i>المواعيد حسب الحالة</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="appointmentsStatusChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex animate__animated animate__fadeIn" style="animation-delay: 0.8s;">
                    <div class="card glass-card-dashboard flex-fill">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar me-2 text-success"></i>عدد المرضى حسب العيادات</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="patientsByDepartmentChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 d-flex animate__animated animate__fadeIn" style="animation-delay: 0.9s;">
                    <div class="card glass-card-dashboard flex-fill">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-area me-2 text-info"></i>المواعيد خلال 6 أشهر</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyAppointmentsChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div id="roomStatusColumn" class="col-auto d-flex animate__animated animate__fadeInRight" style="max-width: 300px; animation-delay: 0.6s;">
            @if(isset($roomStats) && $roomStats['total'] > 0)
            <div class="card room-status-sidebar flex-fill shadow-sm">
                <div class="card-header">
                    <h5><i class="fas fa-bed me-2 text-danger"></i>حالة الغرف</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card room-stat-item border-0 p-1">
                                <div class="card-body d-flex align-items-center py-2 px-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary me-3">
                                        <i class="fas fa-bed fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted">إجمالي الغرف</small>
                                        <h6 class="mb-0 mt-1">{{ $roomStats['total'] }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card room-stat-item border-0 p-1">
                                <div class="card-body d-flex align-items-center py-2 px-3">
                                    <div class="rounded-circle bg-info bg-opacity-10 text-info me-3">
                                        <i class="fas fa-check fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted">الغرف النشطة</small>
                                        <h6 class="mb-0 mt-1 text-info">{{ $roomStats['active'] }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card room-stat-item border-0 p-1">
                                <div class="card-body d-flex align-items-center py-2 px-3">
                                    <div class="rounded-circle bg-success bg-opacity-10 text-success me-3">
                                        <i class="fas fa-check-circle fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted">متاحة</small>
                                        <h6 class="mb-0 mt-1 text-success">{{ $roomStats['available'] }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card room-stat-item border-0 p-1">
                                <div class="card-body d-flex align-items-center py-2 px-3">
                                    <div class="rounded-circle bg-danger bg-opacity-10 text-danger me-3">
                                        <i class="fas fa-user fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted">محجوزة</small>
                                        <h6 class="mb-0 mt-1 text-danger">{{ $roomStats['occupied'] }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card room-stat-item border-0 p-1">
                                <div class="card-body d-flex align-items-center py-2 px-3">
                                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning me-3">
                                        <i class="fas fa-tools fa-sm"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <small class="text-muted">صيانة</small>
                                        <h6 class="mb-0 mt-1 text-warning">{{ $roomStats['maintenance'] }}</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Helper to generate gradients for line/bar charts
        function getGradient(ctx, colorStart, colorEnd) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 180);
            gradient.addColorStop(0, colorStart);
            gradient.addColorStop(1, colorEnd);
            return gradient;
        }

        function getChartOptions(isDark) {
            return {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: isDark ? '#ffffff' : '#475569',
                            font: {
                                family: 'Tajawal',
                                size: 12,
                                weight: '500'
                            },
                            boxWidth: 12,
                            padding: 10
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleColor: '#ffffff',
                        bodyColor: '#ffffff',
                        titleFont: { family: 'Tajawal', weight: 'bold' },
                        bodyFont: { family: 'Tajawal' },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        rtl: true
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: isDark ? '#cbd5e1' : '#64748b',
                            font: { family: 'Tajawal', size: 11 }
                        },
                        grid: {
                            color: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(15, 23, 42, 0.04)',
                            drawBorder: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: isDark ? '#cbd5e1' : '#64748b',
                            font: { family: 'Outfit', size: 11 }
                        },
                        grid: {
                            color: isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(15, 23, 42, 0.04)',
                            drawBorder: false
                        }
                    }
                }
            };
        }

        const charts = [];

        @if(isset($visitsByDay))
        const visitsCtx = document.getElementById('visitsByDayChart').getContext('2d');
        const visitsGradient = getGradient(visitsCtx, 'rgba(99, 102, 241, 0.35)', 'rgba(99, 102, 241, 0.01)');
        charts.push(new Chart(visitsCtx, {
            type: 'line',
            data: {
                labels: @json(array_keys($visitsByDay)),
                datasets: [{
                    label: 'الزيارات اليومية',
                    data: @json(array_values($visitsByDay)),
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointRadius: 4,
                    backgroundColor: visitsGradient,
                    fill: true,
                    tension: 0.35
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        }));
        @endif

        @if(isset($appointmentsByStatus))
        const apptsCtx = document.getElementById('appointmentsStatusChart').getContext('2d');
        charts.push(new Chart(apptsCtx, {
            type: 'doughnut',
            data: {
                labels: ['معلقة', 'مكتملة', 'ملغاة'],
                datasets: [{
                    data: @json(array_values($appointmentsByStatus)),
                    backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
                    borderWidth: 2,
                    borderColor: document.body.classList.contains('dark-mode') ? '#1e293b' : '#ffffff'
                }]
            },
            options: {
                ...getChartOptions(document.body.classList.contains('dark-mode')),
                cutout: '70%',
                radius: '90%'
            }
        }));
        @endif

        @if(isset($patientsByDepartment))
        const deptCtx = document.getElementById('patientsByDepartmentChart').getContext('2d');
        const deptGradient = getGradient(deptCtx, 'rgba(20, 184, 166, 0.8)', 'rgba(20, 184, 166, 0.3)');
        charts.push(new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: @json($patientsByDepartment instanceof Illuminate\Support\Collection ? $patientsByDepartment->pluck('name')->toArray() : array_column($patientsByDepartment, 'name')),
                datasets: [{
                    label: 'عدد المرضى',
                    data: @json($patientsByDepartment instanceof Illuminate\Support\Collection ? $patientsByDepartment->pluck('count')->toArray() : array_column($patientsByDepartment, 'count')),
                    backgroundColor: deptGradient,
                    borderColor: '#14b8a6',
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        }));
        @endif

        @if(isset($monthlyAppointments))
        const monthlyCtx = document.getElementById('monthlyAppointmentsChart').getContext('2d');
        const monthlyGradient = getGradient(monthlyCtx, 'rgba(139, 92, 246, 0.35)', 'rgba(139, 92, 246, 0.01)');
        charts.push(new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: @json($monthlyAppointments->pluck('month')->toArray()),
                datasets: [{
                    label: 'المواعيد الشهرية',
                    data: @json($monthlyAppointments->pluck('count')->toArray()),
                    borderColor: '#8b5cf6',
                    borderWidth: 3,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointRadius: 4,
                    backgroundColor: monthlyGradient,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: getChartOptions(document.body.classList.contains('dark-mode'))
        }));
        @endif

        function updateCharts() {
            const isDark = document.body.classList.contains('dark-mode');
            charts.forEach(chart => {
                chart.options = { ...chart.options, ...getChartOptions(isDark) };
                if (chart.config.type === 'doughnut') {
                    chart.data.datasets[0].borderColor = isDark ? '#1e293b' : '#ffffff';
                }
                chart.update();
            });
        }

        const darkModeToggle = document.getElementById('darkModeToggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', function() {
                setTimeout(updateCharts, 100);
            });
        }

        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    updateCharts();
                }
            });
        });
        observer.observe(document.body, {
            attributes: true,
            attributeFilter: ['class']
        });

        function syncRoomStatusHeight() {
            const leftCol = document.getElementById('dashboardChartsColumn');
            const rightCol = document.getElementById('roomStatusColumn');
            if (leftCol && rightCol) {
                rightCol.style.minHeight = leftCol.offsetHeight + 'px';
            }
        }

        window.addEventListener('load', function() {
            setTimeout(syncRoomStatusHeight, 100);
        });
        window.addEventListener('resize', function() {
            setTimeout(syncRoomStatusHeight, 50);
        });
    });
</script>

@endsection