@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    {{ __("تفاصيل الفحص المختبري") }}
                </h2>
                <div>
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests'))
                        <a href="{{ route('lab-tests.sub-tests.index', $labTest) }}" class="btn btn-success me-2">
                            <i class="fas fa-list-check me-1"></i>
                            {{ __("الفحوصات الفرعية") }}
                            @if($labTest->subTests()->count() > 0)
                                <span class="badge bg-light text-success ms-1">{{ $labTest->subTests()->count() }}</span>
                            @endif
                        </a>
                        <a href="{{ route('lab-tests.references.index', $labTest) }}" class="btn btn-primary me-2">
                            <i class="fas fa-ruler-combined me-1"></i>
                            {{ __("القيم المرجعية") }}
                        </a>
                        <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-warning me-2">
                            <i class="fas fa-edit me-1"></i>
                            {{ __("تعديل") }}
                        </a>
                    @endif
                    <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        {{ __("العودة للقائمة") }}
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
                        {{ __("معلومات الفحص") }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @if($labTest->code)
                            <p><strong>{{ __("الكود:") }}</strong> <code>{{ $labTest->code }}</code></p>
                            @endif
                            <p><strong>{{ __("اسم الفحص:") }}</strong> {{ $labTest->name }}</p>
                            <p><strong>{{ __("الفئة الرئيسية:") }}</strong>
                                <span class="badge bg-secondary">{{ $labTest->category_text }}</span>
                            </p>
                            @if($labTest->subcategory)
                            <p><strong>{{ __("الفئة الفرعية:") }}</strong>
                                <span class="badge bg-info">{{ $labTest->subcategory }}</span>
                            </p>
                            @endif
                            @if($labTest->unit)
                            <p><strong>{{ __("وحدة القياس:") }}</strong> {{ $labTest->unit }}</p>
                            @endif
                            <p><strong>{{ __("الحالة:") }}</strong>
                                <span class="badge bg-{{ $labTest->status_color }}">
                                    {{ $labTest->status_text }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>{{ __("تاريخ الإضافة:") }}</strong> {{ $labTest->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>{{ __("آخر تحديث:") }}</strong> {{ $labTest->updated_at->format('Y-m-d H:i') }}</p>
                            <p><strong>{{ __("المعرف:") }}</strong> #{{ $labTest->id }}</p>
                            @if($labTest->price !== null)
                            <p><strong>{{ __("السعر:") }}</strong> {{ number_format($labTest->price,0) }} {{ __("د.ع") }}</p>
                            @endif
                        </div>
                    </div>

                    @if($labTest->description)
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <p><strong>{{ __("الوصف:") }}</strong></p>
                            <p class="text-muted">{{ $labTest->description }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- إجراءات إضافية -->
            @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests') || Auth::user()->can('delete lab tests'))
            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>
                        {{ __("الإجراءات") }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests'))
                        <div class="col-md-6">
                            <form action="{{ route('lab-tests.toggle-status', $labTest) }}" method="POST" class="d-inline">
                                @csrf
                                @method('POST')
                                <button type="submit" class="btn btn-{{ $labTest->is_active ? 'danger' : 'success' }} w-100">
                                    <i class="fas fa-{{ $labTest->is_active ? 'times' : 'check' }} me-1"></i>
                                    {{ $labTest->is_active ? __("إلغاء تفعيل الفحص") : __("تفعيل الفحص") }}
                                </button>
                            </form>
                        </div>
                        @endif
                        @if(Auth::user()->isAdmin() || Auth::user()->can('delete lab tests'))
                        <div class="col-md-6">
                            <form action="{{ route('lab-tests.destroy', $labTest) }}" method="POST"
                                  onsubmit="return confirm('{{ __('هل أنت متأكد من حذف هذا الفحص؟ هذا الإجراء لا يمكن التراجع عنه.') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger w-100">
                                    <i class="fas fa-trash me-1"></i>
                                    {{ __("حذف الفحص") }}
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection