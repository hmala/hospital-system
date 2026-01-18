@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-procedures me-2 text-danger"></i>
                        كاشير العمليات الجراحية
                    </h2>
                    <p class="text-muted">
                        إدارة مدفوعات العمليات الجراحية والفحوصات المطلوبة
                    </p>
                </div>
                <div>
                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>
                        العودة للكاشير الرئيسي
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            @if(session('payment_id'))
                <br>
                <div class="mt-2">
                    <a href="{{ route('cashier.receipt', session('payment_id')) }}" 
                       class="btn btn-sm btn-light me-2" 
                       target="_blank">
                        <i class="fas fa-eye me-1"></i>
                        عرض الإيصال
                    </a>
                    <a href="{{ route('cashier.receipt.print', session('payment_id')) }}" 
                       class="btn btn-sm btn-light" 
                       target="_blank">
                        <i class="fas fa-print me-1"></i>
                        طباعة الإيصال
                    </a>
                </div>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            {{ session('warning') }}
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

    <!-- إحصائيات العمليات -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">العمليات المعلقة</p>
                            <h3 class="mb-0 text-warning">{{ $surgeryStats['pending_count'] }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">عمليات مدفوعة اليوم</p>
                            <h3 class="mb-0 text-success">{{ $surgeryStats['today_paid'] }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">إيرادات اليوم</p>
                            <h3 class="mb-0 text-primary">{{ number_format($surgeryStats['today_revenue'], 0) }} IQD</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-money-bill-wave fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة العمليات المعلقة -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="fas fa-procedures me-2"></i>
                العمليات الجراحية بانتظار الدفع
                @if(is_object($pendingSurgeries) && $pendingSurgeries->count() > 0)
                    <span class="badge bg-light text-danger">{{ $pendingSurgeries->total() }}</span>
                @endif
            </h5>
        </div>
        <div class="card-body">
            @if(is_object($pendingSurgeries) && $pendingSurgeries->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>رقم العملية</th>
                                <th>المريض</th>
                                <th>نوع العملية</th>
                                <th>الطبيب</th>
                                <th>القسم</th>
                                <th>التاريخ المحدد</th>
                                <th>التحاليل المطلوبة</th>
                                <th>الأشعة المطلوبة</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingSurgeries as $surgery)
                                <tr>
                                    <td><strong>#{{ $surgery->id }}</strong></td>
                                    <td>
                                        <div>{{ $surgery->patient->user->name }}</div>
                                        <small class="text-muted">{{ $surgery->patient->national_id ?? 'غير محدد' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-danger">{{ $surgery->surgery_type }}</span>
                                    </td>
                                    <td>د. {{ $surgery->doctor->user->name }}</td>
                                    <td>{{ $surgery->department->name }}</td>
                                    <td>
                                        <div>{{ $surgery->scheduled_date->format('Y-m-d') }}</div>
                                        <small class="text-muted">{{ $surgery->scheduled_time->format('H:i') }}</small>
                                    </td>
                                    <td>
                                        @if($surgery->labTests->count() > 0)
                                            <span class="badge bg-primary">
                                                <i class="fas fa-flask"></i> {{ $surgery->labTests->count() }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($surgery->radiologyTests->count() > 0)
                                            <span class="badge bg-info">
                                                <i class="fas fa-x-ray"></i> {{ $surgery->radiologyTests->count() }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning">
                                            <i class="fas fa-exclamation-circle me-1"></i>
                                            معلق
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('cashier.surgery.payment.form', $surgery->id) }}" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-money-bill-wave me-1"></i>
                                            تسديد
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $pendingSurgeries->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">لا توجد عمليات معلقة حالياً</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
