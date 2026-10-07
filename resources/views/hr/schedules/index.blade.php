@extends('layouts.app')
@section('title', 'جدول الدوام الأسبوعي - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-alt text-success me-2"></i>جدول الدوام (Roster)</h4>
            <p class="text-muted small mb-0">بناء وتوزيع الشفتات على الموظفين للأيام القادمة</p>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5 text-center">
            <i class="fas fa-calendar-week fa-4x text-muted mb-3 opacity-50"></i>
            <h5>الشاشة قيد البناء</h5>
            <p class="text-muted">هنا سيكون شبكة (Grid) تعرض أسماء الموظفين مقابل أيام الأسبوع لاختيار الشفت بضغطة زر (Excel-like interface).</p>
        </div>
    </div>
</div>
@endsection
