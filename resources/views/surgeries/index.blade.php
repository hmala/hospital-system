@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-procedures me-2"></i>
                    إدارة العمليات الجراحية
                </h2>
                <div>
                    <a href="{{ route('surgeries.waiting') }}" class="btn btn-info text-white me-2">
                        <i class="fas fa-list-ol me-2"></i>قائمة الانتظار
                    </a>
                    @if(!auth()->user()->isDoctor())
                    <a href="{{ route('surgeries.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>حجز عملية جديدة
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>المريض</th>
                            <th>الطبيب</th>
                            <th>نوع العملية</th>
                            <th>التاريخ</th>
                            <th>الوقت</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($surgeries as $surgery)
                        <tr>
                            <td>
                                <i class="fas fa-user-injured text-primary me-1"></i>
                                {{ $surgery->patient->user->name }}
                            </td>
                            <td>
                                <i class="fas fa-user-md text-success me-1"></i>
                                د. {{ $surgery->doctor->user->name }}
                            </td>
                            <td>{{ $surgery->surgery_type }}</td>
                            <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                            <td>{{ $surgery->scheduled_time }}</td>
                            <td>
                                @if($surgery->status == 'scheduled')
                                    <span class="badge bg-secondary">مجدولة</span>
                                @elseif($surgery->status == 'waiting')
                                    <span class="badge bg-info text-dark">في الانتظار</span>
                                @elseif($surgery->status == 'in_progress')
                                    <span class="badge bg-warning">جارية</span>
                                @elseif($surgery->status == 'completed')
                                    <span class="badge bg-success">مكتملة</span>
                                @else
                                    <span class="badge bg-danger">ملغاة</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-sm btn-warning">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>لا توجد عمليات مسجلة</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($surgeries->hasPages())
            <div class="mt-3">
                {{ $surgeries->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
