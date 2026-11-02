@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-wallet me-2 text-info"></i>حسابات أطباء الخدج والأطفال</h2>
                <p class="text-muted mb-0">مراقبة وتسجيل صرف مستحقات أطباء الأطفال وحديثي الولادة من استشارات ومتابعة الحواضن.</p>
            </div>
            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة إلى الكاشير الرئيسية
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(count($doctorAccounts) > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>الطبيب</th>
                                        <th>القسم</th>
                                        <th>استشارات الخدج</th>
                                        <th>حالات الحواضن المشرف عليها</th>
                                        <th>إجمالي المستحقات (د.ع)</th>
                                        <th>إجمالي المدفوع للطبيب (د.ع)</th>
                                        <th>الرصيد المتبقي (د.ع)</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctorAccounts as $account)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <div class="fw-bold">د. {{ optional($account['doctor']->user)->name }}</div>
                                                <small class="text-muted">{{ $account['doctor']->specialization ?? 'أطفال وحديثي ولادة' }}</small>
                                            </td>
                                            <td>{{ optional($account['doctor']->department)->name ?? 'قسم الخدج والأطفال' }}</td>
                                            <td class="fw-bold text-success">{{ $account['consultations_count'] }} استشارة</td>
                                            <td class="fw-bold text-primary">{{ $account['supervised_cases_count'] }} حالة</td>
                                            <td class="text-success fw-bold">{{ number_format($account['total_earned'], 0) }}</td>
                                            <td class="text-secondary">{{ number_format($account['total_paid'], 0) }}</td>
                                            <td class="text-info fw-bold fs-6">{{ number_format($account['balance'], 0) }}</td>
                                            <td>
                                                <a href="{{ route('cashier.nicu.doctor-account', $account['doctor']) }}" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-search-dollar me-1"></i>كشف الحساب والصرف
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-user-md fa-3x text-muted mb-3 opacity-50"></i>
                            <p class="text-muted mb-0">لا توجد مستحقات مالية مسجلة لأي طبيب أطفال أو خدج حالياً.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
