<!-- resources/views/dashboard.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-tachometer-alt me-2"></i>لوحة التحكم</h2>
        </div>
    </div>

    @if(isset($userStats))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">{{ $userStats['name'] }} - {{ $userStats['role'] }}</h5>
                    <div class="row mt-3">
                        @foreach(array_filter($userStats, fn($value, $key) => !in_array($key, ['name','role']), ARRAY_FILTER_USE_BOTH) as $key => $value)
                            <div class="col-md-3 col-sm-6 mb-3">
                                <div class="border rounded p-2 h-100">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                    <p class="mb-0">{{ $value }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

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

    <!-- مخططات بيانية عامة -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>زيارات آخر 7 أيام</h5>
                </div>
                <div class="card-body">
                    <canvas id="visitsByDayChart" height="135"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>المواعيد حسب الحالة</h5>
                </div>
                <div class="card-body">
                    <canvas id="appointmentsStatusChart" height="135"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>عدد المرضى حسب العيادات</h5>
                </div>
                <div class="card-body">
                    <canvas id="patientsByDepartmentChart" height="135"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>المواعيد خلال 6 أشهر</h5>
                </div>
                <div class="card-body">
                    <canvas id="monthlyAppointmentsChart" height="135"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- إحصائيات الغرف -->
    @if(isset($roomStats) && $roomStats['total'] > 0)
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="fas fa-bed me-2 text-danger"></i>حالة الغرف</h5>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                        <i class="fas fa-bed fa-2x text-primary"></i>
                    </div>
                    <div>
                        <h3 class="mb-0">{{ $roomStats['total'] }}</h3>
                        <small class="text-muted">إجمالي الغرف</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-info bg-opacity-25 p-3 me-3">
                        <i class="fas fa-check fa-2x text-info"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-info">{{ $roomStats['active'] }}</h3>
                        <small class="text-muted">الغرف النشطة</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-25 p-3 me-3">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-success">{{ $roomStats['available'] }}</h3>
                        <small class="text-muted">متاحة</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-danger bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-25 p-3 me-3">
                        <i class="fas fa-user fa-2x text-danger"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-danger">{{ $roomStats['occupied'] }}</h3>
                        <small class="text-muted">محجوزة</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle bg-warning bg-opacity-25 p-3 me-3">
                        <i class="fas fa-tools fa-2x text-warning"></i>
                    </div>
                    <div>
                        <h3 class="mb-0 text-warning">{{ $roomStats['maintenance'] }}</h3>
                        <small class="text-muted">صيانة</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @endif

</div>

@if(isset($visitsByDay) || isset($appointmentsByStatus) || isset($patientsByDepartment) || isset($monthlyAppointments))
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            @if(isset($visitsByDay))
            new Chart(document.getElementById('visitsByDayChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json(array_keys($visitsByDay)),
                    datasets: [{
                        label: 'الزيارات',
                        data: @json(array_values($visitsByDay)),
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.2)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            @endif

            @if(isset($appointmentsByStatus))
            new Chart(document.getElementById('appointmentsStatusChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: @json(array_keys($appointmentsByStatus)),
                    datasets: [{
                        data: @json(array_values($appointmentsByStatus)),
                        backgroundColor: [
                            '#ffc107',
                            '#28a745',
                            '#dc3545'
                        ]
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
            @endif

            @if(isset($patientsByDepartment))
            new Chart(document.getElementById('patientsByDepartmentChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: @json($patientsByDepartment instanceof Illuminate\Support\Collection ? $patientsByDepartment->pluck('name')->toArray() : array_column($patientsByDepartment, 'name')),
                    datasets: [{
                        label: 'عدد المرضى',
                        data: @json($patientsByDepartment instanceof Illuminate\Support\Collection ? $patientsByDepartment->pluck('count')->toArray() : array_column($patientsByDepartment, 'count')),
                        backgroundColor: 'rgba(40, 167, 69, 0.5)',
                        borderColor: '#28a745',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } }
                }
            });
            @endif

            @if(isset($monthlyAppointments))
            new Chart(document.getElementById('monthlyAppointmentsChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: @json($monthlyAppointments->pluck('month')->toArray()),
                    datasets: [{
                        label: 'المواعيد',
                        data: @json($monthlyAppointments->pluck('count')->toArray()),
                        borderColor: '#6610f2',
                        backgroundColor: 'rgba(102, 16, 242, 0.2)',
                        fill: true,
                        tension: 0.2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            @endif

        });
    </script>
@endif

@endsection