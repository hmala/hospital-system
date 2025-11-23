@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-edit me-2"></i>
                    تعديل الفحص المختبري
                </h2>
                <div>
                    <a href="{{ route('lab-tests.show', $labTest) }}" class="btn btn-info me-2">
                        <i class="fas fa-eye me-1"></i>
                        عرض
                    </a>
                    <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        تعديل بيانات الفحص المختبري
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('lab-tests.update', $labTest) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">اسم الفحص <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                       value="{{ old('name', $labTest->name) }}" required>
                                <div class="form-text">أدخل اسم الفحص المختبري باللغة العربية</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">الفئة <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">اختر الفئة</option>
                                    @foreach($categories as $key => $value)
                                        <option value="{{ $key }}" {{ old('category', $labTest->category) == $key ? 'selected' : '' }}>
                                            {{ $value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">الوصف</label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="4" placeholder="وصف تفصيلي للفحص المختبري">{{ old('description', $labTest->description) }}</textarea>
                            <div class="form-text">وصف الفحص والمعلومات المهمة عنه</div>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                                       {{ old('is_active', $labTest->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    الفحص نشط
                                </label>
                            </div>
                            <div class="form-text">إذا تم إلغاء التفعيل، لن يظهر الفحص في قوائم الاختيار</div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i>
                                حفظ التغييرات
                            </button>
                            <a href="{{ route('lab-tests.show', $labTest) }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection