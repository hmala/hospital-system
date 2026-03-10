<!-- resources/views/emergency_patients/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-injured me-2"></i>بيانات مريض طوارئ</h2>
                <a href="{{ route('emergency.patients.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-right me-2"></i>العودة للقائمة
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h4>{{ $patient->name }}</h4>
            <p>الهاتف: {{ $patient->phone ?? '-' }}</p>
            <p>الجنس: {{ $patient->gender ?? '-' }}</p>
            <p>تاريخ الميلاد: {{ $patient->date_of_birth ? $patient->date_of_birth->format('d/m/Y') : '-' }}</p>
            <p>رقم المرجع: {{ $patient->reference_number ?? '-' }}</p>
            <p>الحالة: {{ $patient->is_active ? 'نشط' : 'غير نشط' }}</p>
            <p>محوّل: {{ $patient->migrated ? 'نعم' : 'لا' }}</p>
            @if($patient->emergency)
                <p>مرتبط بحالة طوارئ: <a href="{{ route('emergency.show', $patient->emergency) }}">#{{ $patient->emergency->id }}</a></p>
            @endif
        </div>
    </div>

    <div class="mb-3">
        @if(!$patient->migrated)
            <form method="POST" action="{{ route('emergency.patients.migrate', $patient) }}">
                @csrf
                <button type="submit" class="btn btn-primary">ترحيل إلى المرضى العام</button>
            </form>
        @endif
    </div>
</div>
@endsection
