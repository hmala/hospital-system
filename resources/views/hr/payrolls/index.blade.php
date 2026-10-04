@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0 text-primary"><i class="fas fa-money-check-alt text-success me-2"></i> مسير الرواتب (Payroll)</h3>
        <a href="{{ route('hr.payrolls.create') }}" class="btn btn-success shadow-sm">
            <i class="fas fa-plus-circle"></i> توليد مسير رواتب لشهر جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>الشهر</th>
                            <th>من تاريخ</th>
                            <th>إلى تاريخ</th>
                            <th>الحالة</th>
                            <th>تاريخ التوليد</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $cycle)
                        <tr>
                            <td class="fw-bold"><span class="badge bg-primary fs-6">{{ $cycle->cycle_month }}</span></td>
                            <td>{{ $cycle->start_date->format('Y-m-d') }}</td>
                            <td>{{ $cycle->end_date->format('Y-m-d') }}</td>
                            <td>
                                @if($cycle->status == 'draft')
                                    <span class="badge bg-secondary"><i class="fas fa-pencil-alt"></i> مسودة</span>
                                @elseif($cycle->status == 'approved')
                                    <span class="badge bg-success"><i class="fas fa-check-double"></i> معتمد</span>
                                @else
                                    <span class="badge bg-info text-dark"><i class="fas fa-money-bill-wave"></i> مدفوع</span>
                                @endif
                            </td>
                            <td>{{ $cycle->created_at->format('Y-m-d H:i') }}</td>
                            <td>
                                <a href="{{ route('hr.payrolls.show', $cycle->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> عرض وتعديل تفاصيل الرواتب
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-folder-open fs-1 mb-3 text-light"></i><br>
                                لم يتم توليد أي مسير رواتب بعد.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
