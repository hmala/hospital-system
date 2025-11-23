<!-- resources/views/radiology/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-x-ray me-2"></i>إدارة طلبات الإشعة</h2>
                @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist() || Auth::user()->isDoctor())
                <a href="{{ route('radiology.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>طلب إشعة جديد
                </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-patient text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">معلقة</h6>
                            <h3>{{ $requests->where('status', 'pending')->count() }}</h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-appointment text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">مجدولة</h6>
                            <h3>{{ $requests->where('status', 'scheduled')->count() }}</h3>
                        </div>
                        <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-doctor text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">قيد التنفيذ</h6>
                            <h3>{{ $requests->where('status', 'in_progress')->count() }}</h3>
                        </div>
                        <i class="fas fa-play fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-department text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">مكتملة</h6>
                            <h3>{{ $requests->where('status', 'completed')->count() }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول طلبات الإشعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">طلبات الإشعة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>نوع الإشعة</th>
                                    <th>الطبيب المطلب</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $request->patient->user->name }}</strong><br>
                                        <small class="text-muted">{{ $request->patient->user->phone ?? 'لا يوجد رقم' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $request->radiologyType->name }}</strong><br>
                                        <small class="text-muted">{{ $request->radiologyType->code }}</small>
                                    </td>
                                    <td>د. {{ $request->doctor->user->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $request->priority_color }}">
                                            {{ $request->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->status_color }}">
                                            {{ $request->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $request->requested_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('radiology.show', $request) }}" class="btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($request->status === 'completed' && $request->result)
                                            <a href="{{ route('radiology.print', $request) }}" target="_blank" class="btn btn-success" title="طباعة">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @endif

                                            @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                                            @if($request->status === 'pending')
                                            <a href="{{ route('radiology.edit', $request) }}" class="btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('radiology.destroy', $request) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @endif

                                            @if(Auth::user()->hasRole('radiology_staff') && $request->canBePerformed())
                                            <form action="{{ route('radiology.start', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success" title="بدء الإجراء">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                            @endif

                                            @if($request->status === 'in_progress' && Auth::user()->hasRole('radiology_staff'))
                                            <form action="{{ route('radiology.complete', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-primary" title="إكمال">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-x-ray fa-3x mb-3"></i><br>لا توجد طلبات إشعة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection