@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" dir="rtl">
    <!-- Top Header -->
    <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1 small text-muted">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none text-muted">الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('treasury.index') }}" class="text-decoration-none text-muted">المالية والخزينة</a></li>
                        <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">المنظومة المالية (MedFinance IFRS)</li>
                    </ol>
                </nav>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3">
                        <i class="fas fa-balance-scale fs-4"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold text-dark">المنظومة المالية والرقابية المتكاملة للمستشفيات (MedFinance IFRS)</h4>
                        <p class="text-muted small mb-0">الربط اللحظي بين دورة الإيراد السريري، استهلاك العمليات، ومراكز التكلفة وفق الدليل العراقي المحدث</p>
                    </div>
                </div>
            </div>

            <!-- Fast Action Buttons -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="{{ route('accounting.vouchers.create', 'receipt') }}" class="btn btn-success btn-sm px-3 rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>+ سند قبض (RV)</span>
                </a>
                <a href="{{ route('accounting.vouchers.create', 'payment') }}" class="btn btn-danger btn-sm px-3 rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>- سند صرف (PV)</span>
                </a>
                <a href="{{ route('accounting.vouchers.create', 'journal') }}" class="btn btn-primary btn-sm px-3 rounded-3 shadow-sm d-flex align-items-center gap-2">
                    <i class="fas fa-file-invoice"></i>
                    <span>+ قيد يومية (JV)</span>
                </a>
            </div>
        </div>

        <hr class="my-3 text-muted opacity-25">

        <!-- Nav Navigation Pills -->
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('accounting.chart.index') }}" 
               class="btn btn-sm rounded-pill px-3 py-2 fw-semibold {{ request()->routeIs('accounting.chart.*') ? 'btn-primary' : 'btn-light text-dark' }}">
                <i class="fas fa-sitemap me-1"></i> شجرة الحسابات والدليل الموحد
            </a>
            <a href="{{ route('accounting.cost-centers.index') }}" 
               class="btn btn-sm rounded-pill px-3 py-2 fw-semibold {{ request()->routeIs('accounting.cost-centers.*') ? 'btn-primary' : 'btn-light text-dark' }}">
                <i class="fas fa-layer-group me-1"></i> مراكز التكلفة (32 مركزاً)
            </a>
            <a href="{{ route('accounting.vouchers.index') }}" 
               class="btn btn-sm rounded-pill px-3 py-2 fw-semibold {{ request()->routeIs('accounting.vouchers.*') ? 'btn-primary' : 'btn-light text-dark' }}">
                <i class="fas fa-book me-1"></i> السندات والقيود اليومية
            </a>
            <a href="{{ route('accounting.settings.index') }}" 
               class="btn btn-sm rounded-pill px-3 py-2 fw-semibold {{ request()->routeIs('accounting.settings.*') ? 'btn-primary' : 'btn-light text-dark' }}">
                <i class="fas fa-cog me-1"></i> الإعدادات
            </a>
            <a href="{{ route('treasury.index') }}" class="btn btn-sm rounded-pill px-3 py-2 fw-semibold btn-light text-muted ms-auto">
                <i class="fas fa-arrow-left me-1"></i> عودة للخزينة المركزية
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-check-circle fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 d-flex align-items-center gap-2 mb-4" role="alert">
            <i class="fas fa-exclamation-triangle fs-5"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> يرجى تصحيح الأخطاء التالية:</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Content Slot -->
    @yield('accounting_content')
</div>
@endsection
