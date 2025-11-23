@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-exchange-alt me-2"></i>
                    إدارة التحويلات
                </h2>
                <a href="{{ route('staff.referrals.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i>
                    تحويل جديد
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

    <!-- قائمة التحويلات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        قائمة التحويلات
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($referrals) && $referrals->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>رقم التحويل</th>
                                        <th>المريض</th>
                                        <th>من قسم</th>
                                        <th>إلى قسم</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($referrals as $referral)
                                    <tr>
                                        <td>#{{ $referral->id }}</td>
                                        <td>{{ $referral->patient?->user?->name ?? 'غير متوفر' }}</td>
                                        <td>{{ $referral->from_department?->name ?? 'غير محدد' }}</td>
                                        <td>{{ $referral->to_department?->name ?? 'غير محدد' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $referral->status == 'completed' ? 'success' : ($referral->status == 'pending' ? 'warning' : 'info') }}">
                                                {{ $referral->status == 'completed' ? 'مكتمل' : ($referral->status == 'pending' ? 'في الانتظار' : 'قيد التنفيذ') }}
                                            </span>
                                        </td>
                                        <td>{{ $referral->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('staff.referrals.show', $referral) }}"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $referrals->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد تحويلات</h5>
                            <p class="text-muted">لا توجد تحويلات متاحة حالياً</p>
                            <a href="{{ route('staff.referrals.create') }}" class="btn btn-primary mt-3">
                                <i class="fas fa-plus me-1"></i>
                                إنشاء تحويل جديد
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
