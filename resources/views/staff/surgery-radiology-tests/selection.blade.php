@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-x-ray me-2"></i>
                    العمليات التي تحتاج اختيار أشعة
                </h2>
                <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>عودة إلى طلبات الأشعة
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-search me-2"></i>
                البحث والتصفية
            </h6>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('staff.surgery-radiology-tests.selection') }}" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">بحث</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="ابحث باسم المريض أو نوع العملية...">
                    </div>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">من تاريخ</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">إلى تاريخ</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search me-1"></i>بحث
                    </button>
                    <a href="{{ route('staff.surgery-radiology-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-undo me-1"></i>إعادة تعيين
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h6 class="mb-0">
                <i class="fas fa-list me-2"></i>
                العمليات الجراحية التي تحتاج اختيار أشعة
            </h6>
        </div>
        <div class="card-body">
            @if($surgeries->count() > 0)
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="text-center" style="width: 60px;">#</th>
                            <th>المريض</th>
                            <th>نوع العملية</th>
                            <th>تاريخ العملية</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center" style="width: 170px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($surgeries as $surgery)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>
                                <div class="fw-bold">{{ optional($surgery->patient->user)->name ?? 'غير معروف' }}</div>
                                <div class="text-muted">ID: {{ $surgery->patient_id }}</div>
                            </td>
                            <td>{{ $surgery->surgery_type }}</td>
                            <td>
                                {{ optional($surgery->scheduled_date)->format('Y-m-d') ?? 'غير محدد' }}
                                <br>
                                <small class="text-muted">{{ optional($surgery->scheduled_time)->format('H:i') ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-{{ $surgery->status == 'waiting' ? 'warning' : ($surgery->status == 'in_progress' ? 'info' : ($surgery->status == 'scheduled' ? 'secondary' : 'dark')) }}">
                                    {{ ucfirst($surgery->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <form action="{{ route('staff.surgery-radiology-tests.create-selection', $surgery) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check-square me-1"></i>اختر الأشعة
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $surgeries->links() }}
            </div>
            @else
            <div class="alert alert-info">
                لا توجد الآن عمليات تحتاج اختيار أشعة.
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
