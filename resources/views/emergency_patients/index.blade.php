<!-- resources/views/emergency_patients/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-injured me-2"></i>سجل مرضى الطوارئ</h2>
                <a href="{{ route('emergency.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة لحالات الطوارئ
                </a>
            </div>
        </div>
    </div>

    <form method="GET" class="row g-3 mb-3">
        <div class="col-md-4">
            <input type="text" name="search" class="form-control" placeholder="بحث باسم، هاتف أو رقم المرجع" value="{{ request('search') }}">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" type="submit"><i class="fas fa-search me-1"></i>بحث</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الهاتف</th>
                            <th>الجنس</th>
                            <th>تاريخ الميلاد</th>
                            <th>المحول</th>
                            <th>الحالة</th>
                            <th>الطوارئ المرتبطة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients as $patient)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $patient->name }}</td>
                            <td>{{ $patient->phone ?? '-' }}</td>
                            <td>{{ $patient->gender ?? '-' }}</td>
                            <td>{{ $patient->date_of_birth ? $patient->date_of_birth->format('d/m/Y') : '-' }}</td>
                            <td>{{ $patient->migrated ? 'نعم' : 'لا' }}</td>
                            <td>{{ $patient->is_active ? 'نشط' : 'غير نشط' }}</td>
                            <td>
                                @if($patient->emergency)
                                    <a href="{{ route('emergency.show', $patient->emergency) }}">#{{ $patient->emergency->id }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('emergency.patients.show', $patient) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">لا يوجد سجلات.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $patients->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
