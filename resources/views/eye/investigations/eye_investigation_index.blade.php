@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- الترويسة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-purple mb-1" style="color: #6f42c1;">
                <i class="fas fa-microscope me-2"></i>فحوصات وأجهزة مركز العيون التشخيصية
            </h2>
            <p class="text-muted mb-0 small">إدارة فحوصات الرنين الطبقي للشبكية (OCT)، الساحة ومجال البصر، وتضاريس القرنية (Pentacam)</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-primary">
                <i class="fas fa-users me-1"></i>طابور العيون
            </a>
            <a href="{{ route('eye.investigations.create') }}" class="btn text-white" style="background-color: #6f42c1;">
                <i class="fas fa-plus me-1"></i>طلب فحص جهاز جديد
            </a>
        </div>
    </div>

    <!-- جدول فحوصات الأجهزة -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-alt text-primary me-2"></i>سجل طلبات ونتائج الأجهزة</h5>
            <span class="badge bg-light text-dark border">العدد: {{ $investigations->total() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>التاريخ</th>
                        <th>المريض</th>
                        <th>نوع فحص الجهاز</th>
                        <th>العين المستهدفة</th>
                        <th>الطبيب الطالب</th>
                        <th>الحالة</th>
                        <th>التقرير المرفق</th>
                        <th class="text-center" style="width: 140px;">الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($investigations as $inv)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $inv->created_at->format('Y-m-d') }}</div>
                            <div class="small text-muted">{{ $inv->created_at->format('h:i A') }}</div>
                        </td>
                        <td>
                            <div class="fw-bold text-primary">{{ $inv->patient->name }}</div>
                            <div class="small text-muted">{{ $inv->patient->phone ?? 'بدون هاتف' }}</div>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $inv->investigation_type_arabic }}</span>
                        </td>
                        <td>
                            <span class="badge {{ $inv->eye_target == 'OD' ? 'bg-primary' : ($inv->eye_target == 'OS' ? 'bg-purple' : 'bg-success') }}" style="{{ $inv->eye_target == 'OS' ? 'background-color: #6f42c1;' : '' }}">
                                {{ $inv->eye_target == 'OD' ? 'اليمنى (OD)' : ($inv->eye_target == 'OS' ? 'اليسرى (OS)' : 'العينين (OU)') }}
                            </span>
                        </td>
                        <td>{{ $inv->requestedDoctor->name ?? 'طبيب العيون' }}</td>
                        <td>
                            @if($inv->status == 'completed')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check-circle me-1"></i>مكتمل ومعتمد
                                </span>
                            @elseif($inv->status == 'requested')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                    <i class="fas fa-clock me-1"></i>بانتظار الفحص
                                </span>
                            @else
                                <span class="badge bg-secondary">{{ $inv->status_arabic }}</span>
                            @endif
                        </td>
                        <td>
                            @if($inv->attachment_path)
                                <a href="{{ asset('storage/' . $inv->attachment_path) }}" class="btn btn-sm btn-outline-info" target="_blank">
                                    <i class="fas fa-file-pdf me-1"></i>عرض التقرير
                                </a>
                            @else
                                <span class="text-muted small">لا يوجد مرفق</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('eye.investigations.show', $inv) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>التفاصيل
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-microscope fa-3x mb-3 text-secondary opacity-50"></i>
                            <div class="h5">لا توجد طلبات فحص أجهزة مسجلة</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($investigations->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $investigations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
