@extends('layouts.app')
@section('title', 'إعدادات الشفتات - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-calendar-day text-primary me-2"></i>إعدادات الشفتات (الورديات)</h4>
            <p class="text-muted small mb-0">إدارة أنواع الشفتات وأوقاتها لربطها بجدول الدوام</p>
        </div>
        <button class="btn btn-primary fw-bold shadow-sm">
            <i class="fas fa-plus me-1"></i> إضافة شفت جديد
        </button>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-5 text-center">
            <i class="fas fa-tools fa-4x text-muted mb-3 opacity-50"></i>
            <h5>الشاشة قيد البناء</h5>
            <p class="text-muted">هنا سيتم عرض جميع الشفتات (الصباحي، المسائي، الخفارات) لتقوم بتعريف أوقاتها وألوانها.</p>
        </div>
    </div>
</div>
@endsection
