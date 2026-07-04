@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-calculator me-2 text-primary"></i>
                        مراجعة أسعار العمليات الجراحية
                    </h2>
                    <p class="text-muted">
                        مراجعة وتعديل أسعار العمليات وتأكيدها بعد التعديل من قبل موظفي العمليات
                    </p>
                </div>
                <div>
                    <a href="{{ route('cashier.surgeries.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>
                        الذهاب لكاشير العمليات
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="card-title mb-0 fw-bold">
                <i class="fas fa-list text-primary me-2"></i>العمليات المعلقة للمراجعة والتسعير
            </h5>
        </div>
        <div class="card-body p-0">
            @if($surgeriesToReview->isEmpty())
                <div class="text-center py-5">
                    <div class="mb-3 text-muted">
                        <i class="fas fa-clipboard-check fa-4x opacity-50"></i>
                    </div>
                    <h5>لا توجد عمليات معلقة للمراجعة حالياً</h5>
                    <p class="text-muted mb-0">جميع العمليات تم تسعيرها وتأكيدها بنجاح.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">المريض</th>
                                <th>نوع العملية الحالي</th>
                                <th>تاريخ التعديل</th>
                                <th class="text-center pe-4">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($surgeriesToReview as $surgery)
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold">{{ $surgery->patient->user->name ?? 'غير معروف' }}</div>
                                        <small class="text-muted">ID: #{{ $surgery->patient_id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2">
                                            {{ $surgery->surgery_type }}
                                        </span>
                                    </td>
                                    <td>
                                        {{ $surgery->updated_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="text-center pe-4">
                                        <a href="{{ route('accountant.surgeries.review', $surgery) }}" class="btn btn-primary btn-sm px-3">
                                            <i class="fas fa-coins me-1"></i>
                                            تسعير العملية
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
