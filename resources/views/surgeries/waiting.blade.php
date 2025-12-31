@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-clock me-2"></i>
                    شاشة العمليات (جميع العمليات المعلقة)
                </h2>
                <div>
                    <a href="{{ route('surgeries.control') }}" class="btn btn-primary me-2">
                        <i class="fas fa-cogs me-2"></i>لوحة التحكم
                    </a>
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
        <!-- قائمة الانتظار -->
        <div class="col-lg-6 mb-4">
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
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($waitingSurgeries as $surgery)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $surgery->patient->user->name }}</div>
                                        <small class="text-muted">د. {{ $surgery->doctor->user->name }}</small>
                                    </td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                                    <td>{{ $surgery->scheduled_time }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
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

        <!-- العمليات الجارية -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-procedures me-2"></i>العمليات الجارية</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>المريض</th>
                                    <th>العملية</th>
                                    <th>الطبيب</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inProgressSurgeries as $surgery)
                                <tr class="table-success bg-opacity-10">
                                    <td>{{ $surgery->patient->user->name }}</td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->doctor->user->name }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">
                                        لا توجد عمليات جارية حالياً
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
    // تحديث الصفحة تلقائياً كل 5 ثوانٍ للحصول على البيانات الحديثة
    setInterval(function() {
        location.reload();
    }, 1000); // 5 ثوانٍ
</script>
@endsection
