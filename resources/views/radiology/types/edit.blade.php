@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">تعديل نوع الإشعة: {{ $type->name }}</div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('radiology.types.update', $type) }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">اسم نوع الإشعة</label>
                                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $type->name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label for="code" class="form-label">الكود</label>
                                <input type="text" id="code" name="code" class="form-control" value="{{ old('code', $type->code) }}" required>
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">الوصف</label>
                                <textarea id="description" name="description" class="form-control" rows="2">{{ old('description', $type->description) }}</textarea>
                            </div>

                            <div class="col-md-4">
                                <label for="base_price" class="form-label">السعر الأساسي</label>
                                <input type="number" step="0.01" min="0" id="base_price" name="base_price" class="form-control" value="{{ old('base_price', $type->base_price) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label for="estimated_duration" class="form-label">المدة التقديرية (بالدقائق)</label>
                                <input type="number" min="1" max="480" id="estimated_duration" name="estimated_duration" class="form-control" value="{{ old('estimated_duration', $type->estimated_duration) }}" required>
                            </div>

                            <div class="col-md-4 d-flex align-items-end">
                                <div class="form-check me-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $type->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="is_active">مُفعّل</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_contrast" name="requires_contrast" value="1" {{ old('requires_contrast', $type->requires_contrast) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_contrast">يتطلب صبغة (Contrast)</label>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requires_preparation" name="requires_preparation" value="1" {{ old('requires_preparation', $type->requires_preparation) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="requires_preparation">يتطلب تحضير مسبق</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="preparation_instructions" class="form-label">تعليمات التحضير</label>
                                <textarea id="preparation_instructions" name="preparation_instructions" class="form-control" rows="3" placeholder="مثال: صيام 6 ساعات، إحضار نتائج سابقة ...">{{ old('preparation_instructions', $type->preparation_instructions) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                            <a href="{{ route('radiology.types.show', $type) }}" class="btn btn-secondary">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection