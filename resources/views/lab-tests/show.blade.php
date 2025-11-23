@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    تفاصيل الفحص المختبري
                </h2>
                <div>
                    <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-warning me-2">
                        <i class="fas fa-edit me-1"></i>
                        تعديل
                    </a>
                    <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        معلومات الفحص
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>اسم الفحص:</strong> {{ $labTest->name }}</p>
                            <p><strong>الفئة:</strong>
                                <span class="badge bg-secondary">{{ $labTest->category_text }}</span>
                            </p>
                            <p><strong>الحالة:</strong>
                                <span class="badge bg-{{ $labTest->status_color }}">
                                    {{ $labTest->status_text }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ الإضافة:</strong> {{ $labTest->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $labTest->updated_at->format('Y-m-d H:i') }}</p>
                            <p><strong>المعرف:</strong> #{{ $labTest->id }}</p>
                        </div>
                    </div>

                    @if($labTest->description)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>الوصف:</strong></p>
                            <p class="text-muted">{{ $labTest->description }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- إجراءات إضافية -->
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        الإجراءات
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('lab-tests.toggle-status', $labTest) }}" method="POST" class="d-inline">
                                @csrf
                                @method('POST')
                                <button type="submit" class="btn btn-{{ $labTest->is_active ? 'danger' : 'success' }} w-100">
                                    <i class="fas fa-{{ $labTest->is_active ? 'times' : 'check' }} me-1"></i>
                                    {{ $labTest->is_active ? 'إلغاء تفعيل الفحص' : 'تفعيل الفحص' }}
                                </button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            @if(Auth::user()->isAdmin())
                                <form action="{{ route('lab-tests.destroy', $labTest) }}" method="POST"
                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الفحص؟ هذا الإجراء لا يمكن التراجع عنه.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger w-100">
                                        <i class="fas fa-trash me-1"></i>
                                        حذف الفحص
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection