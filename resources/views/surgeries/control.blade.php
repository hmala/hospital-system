@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-cogs me-2"></i>
                    لوحة تحكم العمليات الجراحية
                </h2>
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
        <!-- قائمة الانتظار -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-hourglass-half me-2"></i>قائمة الانتظار</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>المريض</th>
                                    <th>العملية</th>
                                    <th>التاريخ</th>
                                    <th>الوقت</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($surgeries as $surgery)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $surgery->patient->user->name }}</div>
                                        <small class="text-muted">د. {{ $surgery->doctor->user->name }}</small>
                                    </td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                                    <td>{{ $surgery->scheduled_time }}</td>
                                    <td>
                                        @if($surgery->status == 'scheduled')
                                            <form action="{{ route('surgeries.check-in', $surgery) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-check me-1"></i>دخول
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-play me-1"></i>بدء
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        لا توجد عمليات في الانتظار
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- عرض العمليات -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>عرض العمليات</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('surgeries.index') }}" class="btn btn-outline-primary">
                            <i class="fas fa-list me-2"></i>جميع العمليات
                        </a>
                        <a href="{{ route('surgeries.create') }}" class="btn btn-outline-success">
                            <i class="fas fa-plus me-2"></i>إضافة عملية جديدة
                        </a>
                        <a href="{{ route('surgical-operations.index') }}" class="btn btn-outline-info">
                            <i class="fas fa-cogs me-2"></i>إدارة أنواع العمليات
                        </a>
                        <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-bed me-2"></i>إدارة الغرف
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // تحديث الصفحة تلقائياً كل 10 ثوانٍ للحصول على البيانات الحديثة
    setInterval(function() {
        location.reload();
    }, 10000); // 10 ثوانٍ
</script>
@endsection