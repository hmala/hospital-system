<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h2>
        </div>
    </div>

    <!-- الإحصائيات -->
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
</div>
@endsection