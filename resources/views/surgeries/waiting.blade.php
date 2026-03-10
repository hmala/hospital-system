@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-clock me-2"></i>
                    قائمة انتظار العمليات الجراحية
                </h2>
                <div>
                    <a href="{{ route('surgeries.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة للعمليات
                    </a>
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

    <div class="row">
        <!-- جدول العمليات المجدولة -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-check me-2"></i>
                        العمليات المجدولة
                        <span class="badge bg-light text-primary ms-2">{{ $scheduledSurgeries->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>العملية</th>
                                    <th>الطبيب</th>
                                    <th>الوقت</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($scheduledSurgeries as $surgery)
                                <tr>
                                    <td><strong>#{{ $surgery->id }}</strong></td>
                                    <td>
                                        <div class="fw-bold">{{ $surgery->patient->user->name }}</div>
                                        <small class="text-muted">{{ $surgery->patient->national_id ?? 'غير محدد' }}</small>
                                    </td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->doctor->user->name }}</td>
                                    <td>
                                        <div>{{ $surgery->scheduled_date->format('Y-m-d') }}</div>
                                        <small>{{ $surgery->scheduled_time }}</small>
                                    </td>
                                    <td>
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-info mb-1">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary mb-1">
                                                <i class="fas fa-sign-in-alt me-1"></i>دخول
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                                        <p class="mb-0">لا توجد عمليات مجدولة</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول العمليات في الانتظار والجارية -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-hourglass-half me-2"></i>
                        العمليات في الانتظار والجارية
                        <span class="badge bg-dark ms-2">{{ $activeSurgeries->count() }}</span>
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>العملية</th>
                                    <th>الطبيب</th>
                                    <th>الوقت</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeSurgeries as $surgery)
                                <tr>
                                    <td><strong>#{{ $surgery->id }}</strong></td>
                                    <td>
                                        <div class="fw-bold">{{ $surgery->patient->user->name }}</div>
                                        <small class="text-muted">{{ $surgery->patient->national_id ?? 'غير محدد' }}</small>
                                    </td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->doctor->user->name }}</td>
                                    <td>
                                        <div>{{ $surgery->scheduled_date->format('Y-m-d') }}</div>
                                        <small>{{ $surgery->scheduled_time }}</small>
                                    </td>
                                    <td>
                                        @if($surgery->status == 'waiting')
                                            <span class="badge bg-warning">في الانتظار</span>
                                        @elseif($surgery->status == 'checked_in')
                                            <span class="badge bg-info">تم التسجيل</span>
                                        @elseif($surgery->status == 'in_progress')
                                            <span class="badge bg-success">جارية</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('surgeries.show', $surgery) }}" class="btn btn-sm btn-info mb-1">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($surgery->status == 'waiting' || $surgery->status == 'checked_in')
                                            <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success mb-1">
                                                    <i class="fas fa-play me-1"></i>بدء
                                                </button>
                                            </form>
                                        @elseif($surgery->status == 'in_progress')
                                            <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger mb-1">
                                                    <i class="fas fa-stop me-1"></i>إنهاء
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                                        <p class="mb-0">لا توجد عمليات في الانتظار حالياً</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // تحديث الصفحة تلقائياً كل 30 ثانية للحصول على البيانات الحديثة
    setInterval(function() {
        location.reload();
    }, 30000);
</script>
@endsection
