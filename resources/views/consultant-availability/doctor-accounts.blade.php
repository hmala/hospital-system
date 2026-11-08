@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    {{-- ترويسة الصفحة --}}
    <div class="row mb-4">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h2 class="h3 fw-bold text-dark mb-1">
                    <i class="fas fa-wallet text-primary me-2"></i>حسابات الأطباء الاستشاريين
                </h2>
                <p class="text-muted mb-0">متابعة وتصفية المستحقات والأرصدة المالية للأطباء الاستشاريين في العيادات الخارجية.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('consultant-availability.index') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                    <i class="fas fa-arrow-left me-1"></i> العودة لتوفر الأطباء
                </a>
                <a href="{{ route('consultant-availability.financial-movements') }}" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                    <i class="fas fa-exchange-alt me-1"></i> الحركات المالية للاستشارية
                </a>
            </div>
        </div>
    </div>

    {{-- بطاقات المؤشرات المالية الإجمالية (KPIs) --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-success text-white">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-white-50 small fw-bold d-block mb-1">إجمالي الأرصدة المتبقية</span>
                        <h3 class="mb-0 fw-bold">{{ number_format($stats['total_balance'], 0) }}</h3>
                        <small class="text-white-50">د.ع بذمة المستشفى</small>
                    </div>
                    <div class="bg-white bg-opacity-25 rounded-circle p-3 text-white">
                        <i class="fas fa-wallet fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي المستحقات المكتسبة</span>
                        <h4 class="mb-0 fw-bold text-primary">{{ number_format($stats['total_earned'], 0) }}</h4>
                        <small class="text-muted">د.ع تاريخياً</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3">
                        <i class="fas fa-hand-holding-usd fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">إجمالي المبالغ المصروفة</span>
                        <h4 class="mb-0 fw-bold text-success">{{ number_format($stats['total_paid'], 0) }}</h4>
                        <small class="text-success">د.ع مسدد فعلياً</small>
                    </div>
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3">
                        <i class="fas fa-receipt fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white border">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold d-block mb-1">الأطباء الاستشاريون</span>
                        <h4 class="mb-0 fw-bold text-dark">
                            {{ $stats['doctors_with_balance'] }} <span class="fs-6 text-muted fw-normal">/ {{ $stats['total_doctors'] }}</span>
                        </h4>
                        <small class="text-muted">طبيب بذمتهم مستحقات نشطة</small>
                    </div>
                    <div class="bg-info bg-opacity-10 text-info rounded-circle p-3">
                        <i class="fas fa-user-md fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- بطاقة البحث والفلاتر --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white border">
        <div class="card-body p-3">
            <form action="{{ route('consultant-availability.doctor-accounts') }}" method="GET" class="row g-2 align-items-center">
                {{-- البحث النصي --}}
                <div class="col-12 col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" 
                               name="search" 
                               class="form-control form-control-sm border-start-0 rounded-end-pill" 
                               placeholder="ابحث باسم الطبيب، التخصص، أو الهاتف..." 
                               value="{{ $search ?? '' }}">
                    </div>
                </div>

                {{-- فلترة حالة الرصيد --}}
                <div class="col-12 col-sm-6 col-md-3">
                    <select name="balance_status" class="form-select form-select-sm rounded-pill">
                        <option value="">جميع حالات الأرصدة</option>
                        <option value="with_balance" {{ ($balanceStatus ?? '') === 'with_balance' ? 'selected' : '' }}>
                            🟢 أطباء لديهم رصيد مستحق (> 0)
                        </option>
                        <option value="zero_balance" {{ ($balanceStatus ?? '') === 'zero_balance' ? 'selected' : '' }}>
                            ⚪ أطباء رصيدهم صفر (تمت التصفية)
                        </option>
                    </select>
                </div>

                {{-- الترتيب --}}
                <div class="col-12 col-sm-6 col-md-2">
                    <select name="sort_by" class="form-select form-select-sm rounded-pill">
                        <option value="balance_desc" {{ ($sortBy ?? '') === 'balance_desc' ? 'selected' : '' }}>الأعلى رصيداً</option>
                        <option value="balance_asc" {{ ($sortBy ?? '') === 'balance_asc' ? 'selected' : '' }}>الأقل رصيداً</option>
                        <option value="earned_desc" {{ ($sortBy ?? '') === 'earned_desc' ? 'selected' : '' }}>الأعلى مستحقات</option>
                        <option value="paid_desc" {{ ($sortBy ?? '') === 'paid_desc' ? 'selected' : '' }}>الأعلى صرفاً</option>
                        <option value="last_paid" {{ ($sortBy ?? '') === 'last_paid' ? 'selected' : '' }}>أحدث تاريخ صرف</option>
                        <option value="name_asc" {{ ($sortBy ?? '') === 'name_asc' ? 'selected' : '' }}>الاسم (أ - ي)</option>
                        <option value="name_desc" {{ ($sortBy ?? '') === 'name_desc' ? 'selected' : '' }}>الاسم (ي - أ)</option>
                    </select>
                </div>

                {{-- أزرار التنفيذ وإعادة التعيين --}}
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-sm">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->filled('search') || request()->filled('balance_status') || (request()->filled('sort_by') && request('sort_by') !== 'balance_desc'))
                        <a href="{{ route('consultant-availability.doctor-accounts') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3" title="إعادة تعيين الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- جدول الأطباء الاستشاريين --}}
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark d-flex align-items-center gap-2">
                <i class="fas fa-user-md text-primary"></i>
                <span>قائمة الأطباء الاستشاريين وأرصدتهم</span>
            </h5>
            <span class="badge bg-light text-secondary border px-3 py-2 rounded-pill">
                عدد النتائج: {{ $consultantDoctors->total() }} طبيب
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap">
                    <thead class="table-light text-secondary small fw-bold">
                        <tr>
                            <th class="ps-4">الطبيب الاستشاري</th>
                            <th>التخصص والقسم</th>
                            <th class="text-end">الرصيد المتبقي</th>
                            <th class="text-end">إجمالي المستحقات</th>
                            <th class="text-end">إجمالي المصروف</th>
                            <th class="text-center">آخر صرف</th>
                            <th class="text-center pe-4">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($consultantDoctors as $doctor)
                            @php
                                $acc = $doctor->financialAccount;
                                $balance = (float) ($acc?->balance ?? 0);
                                $totalEarned = (float) ($acc?->total_earned ?? 0);
                                $totalPaid = (float) ($acc?->total_paid ?? 0);
                            @endphp
                            <tr>
                                {{-- اسم الطبيب --}}
                                <td class="ps-4">
                                    <div class="fw-bold text-dark fs-6">د. {{ $doctor->user?->name ?? 'غير محدد' }}</div>
                                    <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                                        <span class="font-monospace">ID: #{{ $doctor->id }}</span>
                                        @if($doctor->user?->phone)
                                            <span>•</span>
                                            <span><i class="fas fa-phone-alt me-1" style="font-size: 0.7rem;"></i>{{ $doctor->user->phone }}</span>
                                        @endif
                                    </div>
                                </td>

                                {{-- التخصص والقسم --}}
                                <td>
                                    <div class="fw-bold text-secondary">{{ $doctor->specialization ?? '-' }}</div>
                                    <small class="text-muted">{{ $doctor->department?->name ?? '-' }}</small>
                                </td>

                                {{-- الرصيد المتبقي --}}
                                <td class="text-end font-monospace">
                                    @if($balance > 0)
                                        <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 fs-6 rounded-pill fw-bold">
                                            {{ number_format($balance, 0) }} د.ع
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill font-monospace">
                                            0 د.ع (مصفى)
                                        </span>
                                    @endif
                                </td>

                                {{-- إجمالي المستحقات --}}
                                <td class="text-end font-monospace text-primary fw-bold">
                                    {{ number_format($totalEarned, 0) }} <small class="text-muted">د.ع</small>
                                </td>

                                {{-- إجمالي المصروف --}}
                                <td class="text-end font-monospace text-success fw-bold">
                                    {{ number_format($totalPaid, 0) }} <small class="text-muted">د.ع</small>
                                </td>

                                {{-- آخر تاريخ صرف --}}
                                <td class="text-center font-monospace text-muted small">
                                    @if($acc?->last_paid_at)
                                        <span class="badge bg-light text-dark border">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            {{ \Carbon\Carbon::parse($acc->last_paid_at)->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span class="text-muted opacity-50">---</span>
                                    @endif
                                </td>

                                {{-- الإجراءات --}}
                                <td class="text-center pe-4">
                                    <a href="{{ route('consultant-availability.doctor-account', $doctor) }}" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm d-inline-flex align-items-center gap-1">
                                        <i class="fas fa-eye"></i>
                                        <span>كشف الحساب والتفاصيل</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-user-md fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-1 fw-bold fs-6">لا توجد حسابات أطباء مطابقة لمعايير البحث الحالية.</p>
                                    @if(request()->filled('search') || request()->filled('balance_status'))
                                        <a href="{{ route('consultant-availability.doctor-accounts') }}" class="btn btn-sm btn-outline-secondary rounded-pill mt-2">
                                            <i class="fas fa-times me-1"></i> إلغاء الفلاتر وعرض الكل
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- الترقيم والعداد --}}
        @if($consultantDoctors->hasPages())
            <div class="card-footer bg-white py-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="text-muted small">
                    عرض <strong>{{ $consultantDoctors->firstItem() ?? 0 }}</strong> إلى <strong>{{ $consultantDoctors->lastItem() ?? 0 }}</strong> من أصل <strong>{{ $consultantDoctors->total() }}</strong> طبيب استشاري
                </span>
                <div>
                    {{ $consultantDoctors->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
