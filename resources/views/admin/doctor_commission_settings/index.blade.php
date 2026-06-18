@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>إعدادات عمولات الأطباء</h3>
        <a href="{{ route('admin.doctor-commission-settings.create') }}" class="btn btn-primary">إضافة إعداد جديد</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('admin.doctor-commission-settings.index') }}" class="d-flex gap-2 flex-wrap">
            <input type="search" name="q" value="{{ old('q', $q ?? request('q')) }}" class="form-control form-control-sm" placeholder="ابحث عن الطبيب أو القسم">
            <button type="submit" class="btn btn-outline-secondary btn-sm">بحث</button>
        </form>
    </div>

    <div class="table-responsive">
        <form id="doctor-commission-settings-form" method="POST" action="{{ route('admin.doctor-commission-settings.save') }}">
            @csrf
            <input type="hidden" name="save_mode" value="row">

            <div class="mb-3 d-flex flex-wrap align-items-center gap-2">
                <button type="submit" name="save_mode" value="all" class="btn btn-success btn-sm">حفظ الكل</button>
            </div>

            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الطبيب</th>
                        <th>أجرة الطبيب</th>
                        <th>القسم</th>
                        <th>حصة الطبيب</th>
                        <th>حالة الطبيب</th>
                        <th>حفظ</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doctors as $doctor)
                        @php
                            $commission = $doctor->currentCommissionSetting;
                        @endphp
                        <tr>
                            <td>{{ $doctor->id }}</td>
                            <td>
                                <div>{{ $doctor->user?->name ?? 'طبيب #' . $doctor->id }}</div>
                                <div class="small text-muted">{{ $doctor->department?->name ?? 'غير محدد' }}</div>
                            </td>
                            <td>{{ $doctor->consultation_fee ? number_format($doctor->consultation_fee, 0) . ' د.ع' : '—' }}</td>
                            <td>
                                {{ $doctor->department?->name ?? 'عام' }}
                            </td>
                            <td>
                                <input type="hidden" name="doctor_id[]" value="{{ $doctor->id }}">
                                <input type="hidden" name="commission_type[]" value="fixed">
                                <input type="number" step="0.01" name="fixed_amount[]" value="{{ old('fixed_amount.' . $loop->index, $commission?->fixed_amount ?? '') }}" class="form-control form-control-sm" placeholder="حصة الطبيب">
                            </td>
                            <td>
                                @if($doctor->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">غير نشط</span>
                                @endif
                            </td>
                            <td>
                                <button type="submit" name="doctor_row" value="{{ $doctor->id }}" class="btn btn-sm btn-primary">حفظ</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </form>
    </div>

    <div class="mt-3">{{ $doctors->links() }}</div>
</div>
@endsection
