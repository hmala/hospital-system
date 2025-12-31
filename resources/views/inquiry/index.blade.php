@extends('layouts.app')

@section('content')
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
                <div>
                    <a href="{{ route('inquiry.search') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus-circle me-2"></i>
                        طلب جديد
                    </a>
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
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">زيارات اليوم</h6>
                            <h2 class="mb-0">{{ $todayInquiries->total() }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">قيد المعالجة</h6>
                            <h2 class="mb-0">{{ $todayInquiries->where('status', 'in_progress')->count() }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-spinner fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">مكتملة</h6>
                            <h2 class="mb-0">{{ $todayInquiries->where('status', 'completed')->count() }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-check-circle fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">في الانتظار</h6>
                            <h2 class="mb-0">{{ $todayInquiries->where('status', 'pending')->count() }}</h2>
                        </div>
                        <div>
                            <i class="fas fa-clock fa-3x opacity-50"></i>
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
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            زيارات اليوم
                        </h5>
                        <div>
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
                            <table class="table table-hover mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th>الوقت</th>
                                        <th>المريض</th>
                                        <th>العمر</th>
                                        <th>الهاتف</th>
                                        <th>الشكوى</th>
                                        <th>الطبيب</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
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
                                            <strong>{{ $visit->patient->user->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $visit->patient->age }} سنة</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                {{ $visit->patient->phone }}
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
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ Auth::user()->isDoctor() ? route('doctor.visits.show', $visit->id) : route('visits.show', $visit->id) }}" 
                                                   class="btn btn-sm btn-info"
                                                   title="التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-center">
                                {{ $todayInquiries->links() }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات اليوم</h5>
                            <p class="text-muted">ابدأ بإنشاء طلب جديد للمريض</p>
                            <a href="{{ route('inquiry.search') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus-circle me-2"></i>
                                طلب جديد
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- روابط سريعة -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-link me-2"></i>
                        روابط سريعة
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <a href="{{ route('patients.index') }}" class="btn btn-outline-info w-100 mb-2">
                                <i class="fas fa-users me-2"></i>
                                قائمة المرضى
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('patients.create') }}" class="btn btn-outline-success w-100 mb-2">
                                <i class="fas fa-user-plus me-2"></i>
                                تسجيل مريض جديد
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary w-100 mb-2">
                                <i class="fas fa-home me-2"></i>
                                الرئيسية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
