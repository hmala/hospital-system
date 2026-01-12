<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h2>
        </div>
    </div>

    @if(isset($radiologyStats))
    <!-- إحصائيات موظف الأشعة -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">طلبات معلقة</h5>
                            <h2 class="mb-0">{{ $radiologyStats['pending'] }}</h2>
                            <small>تحتاج للمعالجة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-clock fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">مجدولة</h5>
                            <h2 class="mb-0">{{ $radiologyStats['scheduled'] }}</h2>
                            <small>لها موعد محدد</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-calendar-check fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">قيد التنفيذ</h5>
                            <h2 class="mb-0">{{ $radiologyStats['in_progress'] }}</h2>
                            <small>جاري العمل عليها</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-play fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">اكتملت اليوم</h5>
                            <h2 class="mb-0">{{ $radiologyStats['completed_today'] }}</h2>
                            <small>تمت معالجتها اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات الأشعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-x-ray fa-4x text-primary mb-3"></i>
                    <h4>طلبات الأشعة</h4>
                    <p class="text-muted mb-4">عرض وإدارة جميع طلبات الأشعة</p>
                    <a href="{{ route('radiology.index') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        الانتقال إلى طلبات الأشعة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @elseif(isset($labStats))
    <!-- إحصائيات موظف المختبر -->
    <div class="row">
        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">طلبات معلقة</h5>
                            <h2 class="mb-0">{{ $labStats['pending'] }}</h2>
                            <small>تحتاج للمعالجة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-flask fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-md-6 mb-4">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">اكتملت اليوم</h5>
                            <h2 class="mb-0">{{ $labStats['completed_today'] }}</h2>
                            <small>تمت معالجتها اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- رابط سريع لطلبات المختبر -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-flask fa-4x text-primary mb-3"></i>
                    <h4>طلبات المختبر</h4>
                    <p class="text-muted mb-4">عرض وإدارة جميع طلبات المختبر</p>
                    <a href="{{ route('staff.requests.index', ['type' => 'lab']) }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>
                        الانتقال إلى طلبات المختبر
                    </a>
                </div>
            </div>
        </div>
    </div>

    @else
    <!-- الإحصائيات العامة -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-patient text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">المرضى</h5>
                            <h2 class="mb-0">{{ $stats['totalPatients'] }}</h2>
                            <small>مسجلين في النظام</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-user-injured fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-doctor text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">الأطباء</h5>
                            <h2 class="mb-0">{{ $stats['totalDoctors'] }}</h2>
                            <small>يعملون في المستشفى</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-user-md fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-department text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">العيادات</h5>
                            <h2 class="mb-0">{{ $stats['totalDepartments'] }}</h2>
                            <small>عيادات نشطة</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-clinic-medical fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card bg-appointment text-white">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">مواعيد اليوم</h5>
                            <h2 class="mb-0">{{ $stats['todayAppointments'] }}</h2>
                            <small>مجدولة لهذا اليوم</small>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-calendar-day fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الإجراءات السريعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>الإجراءات السريعة</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('patients.create') }}" class="btn btn-outline-primary btn-lg p-3 rounded-circle">
                                <i class="fas fa-user-plus fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">مريض جديد</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('appointments.create') }}" class="btn btn-outline-success btn-lg p-3 rounded-circle">
                                <i class="fas fa-calendar-plus fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">موعد جديد</p>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <a href="{{ route('visits.create') }}" class="btn btn-outline-info btn-lg p-3 rounded-circle">
                                <i class="fas fa-file-medical fa-2x"></i>
                            </a>
                            <p class="mt-2 mb-0">زيارة جديدة</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection