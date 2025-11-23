<!-- resources/views/visits/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-history me-2"></i>سجل الزيارات</h2>
                <a href="{{ route('visits.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>تسجيل زيارة جديدة
                </a>
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
        <div class="col-md-2 col-6">
            <div class="card bg-primary text-white">
                <div class="card-body text-center py-3">
                    <h5 class="mb-0">{{ $visits->total() }}</h5>
                    <small>إجمالي الزيارات</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card bg-success text-white">
                <div class="card-body text-center py-3">
                    <h5 class="mb-0">{{ $visits->where('visit_date', today())->count() }}</h5>
                    <small>زيارات اليوم</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>نوع الزيارة</th>
                                    <th>حالة الزيارة</th>
                                    <th>الشكوى الرئيسية</th>
                                    <th>التشخيص</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($visits as $visit)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</strong></td>
                                    <td>
                                        <a href="{{ route('patients.show', $visit->patient) }}" class="text-decoration-none">
                                            {{ $visit->patient->user->name }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $visit->patient->user->phone }}</small>
                                    </td>
                                    <td>د. {{ $visit->doctor?->user?->name ?? 'غير محدد' }}</td>
                                    <td><span class="badge bg-info">{{ $visit->visit_type_text }}</span></td>
                                    <td>
                                        <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'in_progress' ? 'warning' : ($visit->status == 'cancelled' ? 'danger' : 'secondary')) }}">
                                            {{ $visit->status_text }}
                                        </span>
                                    </td>
                                    <td><small class="text-muted">{{ Str::limit($visit->chief_complaint, 50) }}</small></td>
                                    <td>@if($visit->diagnosis && isset($visit->diagnosis['description']))<small class="text-success">{{ Str::limit($visit->diagnosis['description'], 50) }}</small>@else<span class="text-muted">---</span>@endif</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('visits.show', $visit) }}" class="btn btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('visits.edit', $visit) }}" class="btn btn-warning" title="تعديل"><i class="fas fa-edit"></i></a>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-history fa-3x mb-3"></i><br>لا توجد زيارات مسجلة حتى الآن
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">{{ $visits->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection