@extends('layouts.app')
@section('title', 'توليد مسير راتب جديد - HR')

@section('content')
<div class="container-fluid max-w-1000">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-magic text-primary me-2"></i>توليد مسير رواتب جديد</h4>
            <p class="text-muted small mb-0">سيتم سحب الراتب الأساسي، المخصصات، الاستقطاعات، الغيابات، والإضافي تلقائياً.</p>
        </div>
        <a href="{{ route('hr.payrolls.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> عودة للقائمة
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form action="{{ route('hr.payrolls.store') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-md-12">
                        <label class="form-label fw-bold">شهر الدورة (الاسم) <span class="text-danger">*</span></label>
                        <input type="text" name="cycle_month" class="form-control form-control-lg bg-light" placeholder="مثال: كانون الثاني 2024 أو 2024-01" required value="{{ date('Y-m') }}">
                        <div class="form-text">هذا الاسم سيظهر في القوائم وسجلات البنك.</div>
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-success">تاريخ بداية الدورة <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" required value="{{ date('Y-m-01') }}">
                    </div>
                    
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-danger">تاريخ نهاية الدورة <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" required value="{{ date('Y-m-t') }}">
                    </div>

                    <div class="col-12 mt-4">
                        <div class="alert alert-info border-0 shadow-sm rounded-3">
                            <h6 class="fw-bold"><i class="fas fa-robot me-2"></i> ما الذي سيحدث الآن؟</h6>
                            <ul class="mb-0 small">
                                <li>سيبحث النظام عن جميع الموظفين <strong>النشطين</strong>.</li>
                                <li>سيتم جلب الراتب الأساسي والمخصصات الثابتة.</li>
                                <li>سيتم احتساب جميع <strong>الغيابات والتأخيرات</strong> ضمن فترة الدورة وخصمها آلياً.</li>
                                <li>سيتم احتساب <strong>ساعات العمل الإضافي</strong> وإضافتها.</li>
                                <li>سيتم خصم <strong>أقساط السلف</strong> الشهرية بشكل آلي.</li>
                                <li>سيتم تطبيق عقوبات ومكافآت الإدارة (Pending).</li>
                                <li>النتيجة: <strong>شيت رواتب مركزي واحد (مسودة) قابل للتعديل الفوري</strong> قبل الاعتماد.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-12 text-end">
                        <hr class="my-3">
                        <button type="submit" class="btn btn-primary fw-bold px-5" onclick="this.innerHTML='<i class=\'fas fa-spinner fa-spin me-2\'></i> جاري التوليد...'; this.classList.add('disabled'); this.form.submit();">
                            <i class="fas fa-cogs me-2"></i> بدء التوليد الآلي
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
