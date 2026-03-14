@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-trash-restore text-warning me-2"></i>
                        العمليات الجراحية المحذوفة
                    </h2>
                    <p class="text-muted">استعادة العمليات الجراحية المحذوفة</p>
                </div>
                <a href="{{ route('surgical-operations.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة للقائمة النشطة
                </a>
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

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- قائمة العمليات المحذوفة -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">
                <i class="fas fa-trash me-2"></i>
                العمليات المحذوفة
            </h5>
        </div>
        <div class="card-body">
            @if($trashedOperations->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم العملية</th>
                                <th>الصنف</th>
                                <th>الأجر الأخير</th>
                                <th>تاريخ الحذف</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($trashedOperations as $operation)
                            <tr>
                                <td>{{ $operation->id }}</td>
                                <td>
                                    <strong>{{ $operation->name }}</strong>
                                    <br>
                                    <small class="text-muted">كان نشط: {{ $operation->is_active ? 'نعم' : 'لا' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ $operation->category }}</span>
                                </td>
                                <td>
                                    <span class="fw-bold text-primary">
                                        {{ number_format($operation->fee, 0) }} د.ع
                                    </span>
                                </td>
                                <td>
                                    <span class="text-danger">
                                        <i class="fas fa-calendar-times me-1"></i>
                                        {{ $operation->deleted_at->format('Y-m-d H:i') }}
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        منذ {{ $operation->deleted_at->diffForHumans() }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <form action="{{ route('surgical-operations.restore', $operation->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success" title="استعادة العملية">
                                            <i class="fas fa-undo me-1"></i>
                                            استعادة
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-trash fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد عمليات محذوفة</h5>
                    <p class="text-muted">جميع العمليات الجراحية نشطة</p>
                    <a href="{{ route('surgical-operations.index') }}" class="btn btn-primary">
                        <i class="fas fa-list me-1"></i>
                        عرض العمليات النشطة
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection