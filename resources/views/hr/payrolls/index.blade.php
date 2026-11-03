@extends('layouts.app')
@section('title', 'مسير الرواتب - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-file-invoice-dollar text-primary me-2"></i>دورات ومسير الرواتب</h4>
            <p class="text-muted small mb-0">إدارة الرواتب الشهرية وتوليد مسير الدفع</p>
        </div>
        <a href="{{ route('hr.payrolls.create') }}" class="btn btn-primary fw-bold px-4 shadow-sm">
            <i class="fas fa-plus me-1"></i> توليد مسير راتب جديد
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-center">
                    <thead class="bg-light text-secondary small text-uppercase">
                        <tr>
                            <th class="py-3">شهر الدورة</th>
                            <th>من تاريخ</th>
                            <th>إلى تاريخ</th>
                            <th>عدد الموظفين</th>
                            <th>حالة المسير</th>
                            <th>الاعتماد</th>
                            <th>الخيارات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cycles as $cycle)
                        <tr>
                            <td class="fw-bold text-primary">{{ $cycle->cycle_month }}</td>
                            <td>{{ $cycle->start_date->format('Y-m-d') }}</td>
                            <td>{{ $cycle->end_date->format('Y-m-d') }}</td>
                            <td><span class="badge bg-secondary rounded-pill px-3">{{ $cycle->payrolls()->count() }}</span></td>
                            <td>
                                @if($cycle->status === 'draft')
                                    <span class="badge bg-warning text-dark"><i class="fas fa-pen"></i> مسودة (قيد التعديل)</span>
                                @elseif($cycle->status === 'approved')
                                    <span class="badge bg-success"><i class="fas fa-check-double"></i> معتمد ومُرحل</span>
                                @endif
                            </td>
                            <td class="small text-muted">
                                @if($cycle->approved_by)
                                    مُعتمد من {{ $cycle->approver->name ?? 'مستخدم' }}<br>
                                    {{ $cycle->approved_at->format('Y-m-d') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('hr.payrolls.show', $cycle->id) }}" class="btn btn-sm btn-outline-primary" title="عرض الشيت">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($cycle->status === 'draft')
                                <form action="{{ route('hr.payrolls.destroy', $cycle->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذه المسودة وإعادة جميع القيود لحالة الانتظار؟')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                <h5>لا توجد أي دورات رواتب</h5>
                                <p class="mb-0">انقر على "توليد مسير راتب جديد" للبدء.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 py-3">
            {{ $cycles->links() }}
        </div>
    </div>
</div>
@endsection
