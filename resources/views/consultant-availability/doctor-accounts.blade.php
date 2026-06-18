@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-wallet me-2 text-primary"></i>حسابات الأطباء الاستشاريين</h2>
                <p class="text-muted mb-0">عرض ملخص الرصيد والمستحقات لكل طبيب استشاري.</p>
            </div>
            <a href="{{ route('consultant-availability.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>العودة لتوفر الأطباء
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>الطبيب</th>
                                    <th>التخصص</th>
                                    <th>القسم</th>
                                    <th>الرصيد الحالي</th>
                                    <th>إجمالي المستحقات</th>
                                    <th>إجمالي المدفوع</th>
                                    <th>آخر صرف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($consultantDoctors as $doctor)
                                    <tr>
                                        <td>د. {{ optional($doctor->user)->name ?? 'غير محدد' }}</td>
                                        <td>{{ $doctor->specialization ?? '-' }}</td>
                                        <td>{{ optional($doctor->department)->name ?? '-' }}</td>
                                        <td class="fw-bold">{{ number_format(optional($doctor->financialAccount)->balance ?? 0, 2) }} IQD</td>
                                        <td>{{ number_format(optional($doctor->financialAccount)->total_earned ?? 0, 2) }} IQD</td>
                                        <td>{{ number_format(optional($doctor->financialAccount)->total_paid ?? 0, 2) }} IQD</td>
                                        <td>{{ optional(optional($doctor->financialAccount)->last_paid_at)->format('Y-m-d') ?? '-' }}</td>
                                        <td>
                                            <a href="{{ route('consultant-availability.doctor-account', $doctor) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye me-1"></i>التفاصيل
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">لا توجد حسابات أطباء للاستشاريين.</td>
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
