@extends('layouts.app')

@section('content')
<style>
    .inquiry-stat-card {
        background: rgba(148,163,184,0.18);
        color: #0f172a;
        border: 1px solid rgba(148,163,184,0.22);
        border-radius: 18px;
    }
    .inquiry-stat-card .card-body {
        padding: 1rem 1rem !important;
    }
    .inquiry-stat-card .icon-circle {
        background: rgba(148,163,184,0.16);
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #475569;
    }
    .inquiry-stat-card h6 {
        color: #475569;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }
    .inquiry-stat-card h1 {
        font-size: 2rem;
        margin: 0;
    }
    .inquiry-table {
        background: transparent;
    }
    .inquiry-table thead th {
        background: rgba(191,219,254,0.5);
        color: #1d4ed8;
        border-color: rgba(147,197,253,0.3);
        font-weight: 600;
    }
    .inquiry-table tbody tr {
        background: rgba(238,246,255,0.92);
    }
    .inquiry-table tbody tr:nth-of-type(odd) {
        background: rgba(219,234,254,0.92);
    }
    .inquiry-table tbody tr:hover {
        background: rgba(191,219,254,0.5);
    }
    .inquiry-table td,
    .inquiry-table th {
        border-color: rgba(147,197,253,0.28);
        vertical-align: middle;
        padding: 0.85rem 0.95rem;
        color: #1e3a8a;
    }
    .inquiry-table tbody td small,
    .inquiry-table tbody td .text-muted {
        color: #475569 !important;
    }
    .inquiry-table .badge {
        background: rgba(59,130,246,0.18);
        color: #fff;
        border: 1px solid rgba(59,130,246,0.2);
        text-shadow: none;
    }
    .inquiry-table .badge.bg-secondary {
        background: rgba(107,114,128,0.9);
        color: #fff;
    }
    .inquiry-table .badge.bg-info,
    .inquiry-table .badge.bg-success,
    .inquiry-table .badge.bg-warning {
        color: #fff;
        text-shadow: none;
    }
    .inquiry-actions .btn-info {
        background: #475569;
        border-color: #475569;
        color: #fff;
    }
    .inquiry-actions .btn-info:hover {
        background: #334155;
    }
    .inquiry-quick .card {
        background: rgba(203,213,225,0.16);
        border: 1px solid rgba(148,163,184,0.24);
        border-radius: 18px;
    }
    .inquiry-quick .card-header {
        background: transparent !important;
        color: #475569 !important;
        border-bottom: none !important;
    }
    .btn-outline-info {
        color: #475569;
        border-color: #475569;
    }
    .btn-outline-info:hover {
        background: rgba(71,85,105,0.08);
    }
    .text-muted { color: #6b7280 !important; }
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital me-2"></i>
                        الاستعلامات والاستقبال
                    </h2>
                    <p class="text-muted">إدارة استقبال المرضى وإنشاء الطلبات الطبية</p>
                </div>
               
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm inquiry-stat-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1 text-uppercase">زيارات اليوم</h6>
                            <h1 class="mb-0">{{ $todayInquiries->total() }}</h1>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm inquiry-stat-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1 text-uppercase">قيد المعالجة</h6>
                            <h1 class="mb-0">{{ $todayInquiries->where('status', 'in_progress')->count() }}</h1>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-spinner fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm inquiry-stat-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1 text-uppercase">مكتملة</h6>
                            <h1 class="mb-0">{{ $todayInquiries->where('status', 'completed')->count() }}</h1>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm inquiry-stat-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="mb-1 text-uppercase">في الانتظار</h6>
                            <h1 class="mb-0">{{ $todayInquiries->where('status', 'pending')->count() }}</h1>
                        </div>
                        <div class="icon-circle">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة الزيارات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header border-0 pb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            زيارات اليوم
                        </h5>
                        <div class="d-flex gap-2">
                            <a href="{{ route('inquiry.search') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-plus-circle me-1"></i> طلب جديد
                            </a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-1"></i>
                                تحديث
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($todayInquiries->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 inquiry-table">
                                <thead class="bg-light">
                                    <tr>
                                        <th>وقت الزيارة</th>
                                        <th>اسم المريض</th>
                                        <th>العمر</th>
                                        <th>رقم الهاتف</th>
                                        <th>الشكوى الرئيسية</th>
                                        <th>الطبيب المختص</th>
                                        <th>حالة الزيارة</th>
                                        <th>العمليات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayInquiries as $visit)
                                    <tr>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i>
                                                {{ $visit->visit_time ? \Carbon\Carbon::parse($visit->visit_time)->format('H:i') : '-' }}
                                            </small>
                                        </td>
                                        <td>
                                            <strong>{{ optional($visit->patient)->user->name ?? 'غير محدد' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ optional($visit->patient)->age ?? 'غير محدد' }} سنة</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ optional($visit->patient)->phone ?? 'غير محدد' }}
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ Str::limit($visit->chief_complaint ?? 'لا يوجد', 40) }}
                                            </small>
                                        </td>
                                        <td>
                                            @if($visit->doctor)
                                                <small>د. {{ $visit->doctor->user->name }}</small>
                                            @else
                                                <small class="text-muted">-</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($visit->status == 'in_progress')
                                                <span class="badge bg-info">قيد المعالجة</span>
                                            @elseif($visit->status == 'completed')
                                                <span class="badge bg-success">مكتمل</span>
                                            @elseif($visit->status == 'pending')
                                                <span class="badge bg-warning">في الانتظار</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $visit->status }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm inquiry-actions">
                                                <a href="{{ route('visits.edit', $visit) }}" 
                                                   class="btn btn-sm btn-warning"
                                                   title="تعديل الحجز">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer bg-transparent border-0">
                            <div class="d-flex justify-content-center">
                                {{ $todayInquiries->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات اليوم</h5>
                            <p class="text-muted">ابدأ بإنشاء طلب جديد للمريض</p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="{{ route('inquiry.search') }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-plus-circle me-2"></i>
                                    طلب جديد
                                </a>
                               
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
