@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i> توليد مسير رواتب لشهر جديد</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> سيقوم النظام تلقائياً بـ:
                        <ul class="mb-0 mt-2">
                            <li>جلب الراتب الأساسي لكل الموظفين الفعالين.</li>
                            <li>احتساب المكافآت والعقوبات غير المرحلة حتى تاريخ نهاية الفترة.</li>
                            <li>ترحيل المكافآت والعقوبات وإضافتها تلقائياً ضمن المسير.</li>
                        </ul>
                    </div>

                    <form action="{{ route('hr.payrolls.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">دورة الرواتب (الشهر) <span class="text-danger">*</span></label>
                            <input type="month" name="cycle_month" class="form-control" value="{{ date('Y-m') }}" required>
                            <small class="text-muted">مثال: 2026-10</small>
                            @error('cycle_month') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">تاريخ بداية الاحتساب <span class="text-danger">*</span></label>
                                <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-01') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تاريخ نهاية الاحتساب <span class="text-danger">*</span></label>
                                <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-t') }}" required>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <a href="{{ route('hr.payrolls.index') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-success"><i class="fas fa-cog fa-spin-hover"></i> توليد المسير الآن</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
