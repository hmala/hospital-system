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
                <a href="{{ route('surgeries.waiting') }}" class="btn btn-secondary">
                    <i class="fas fa-eye me-2"></i>عرض الشاشة
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
                                    <th>الوقت</th>
                                    <th>الإجراء</th>
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
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        لا يوجد مرضى في الانتظار
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
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inProgressSurgeries as $surgery)
                                <tr class="table-success bg-opacity-10">
                                    <td>{{ $surgery->patient->user->name }}</td>
                                    <td>{{ $surgery->surgery_type }}</td>
                                    <td>{{ $surgery->doctor->user->name }}</td>
                                    <td>
                                        <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">
                                                <i class="fas fa-check-circle me-1"></i>إكمال
                                            </button>
                                        </form>
                                        <form action="{{ route('surgeries.return-to-waiting', $surgery) }}" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('هل أنت متأكد من إعادة العملية إلى قائمة الانتظار؟')">
                                                <i class="fas fa-undo me-1"></i>إعادة للانتظار
                                            </button>
                                        </form>
                                        <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST" class="d-inline ms-1">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('هل أنت متأكد من إلغاء العملية؟')">
                                                <i class="fas fa-times-circle me-1"></i>إلغاء
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
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
    }, 5000); // 5 ثوانٍ
</script>
@endsection