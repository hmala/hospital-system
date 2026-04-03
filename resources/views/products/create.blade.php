@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">إضافة مادة جديدة</div>
        <div class="card-body">
            <form method="POST" action="{{ route('products.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">اسم المادة</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الكود (اختياري، سيولد تلقائياً إذا لم يُدخل)</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code') }}">
                    @error('code') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">الوحدة</label>
                    <select name="unit" class="form-control" required>
                        <option value="">اختر الوحدة</option>
                        <option value="قطعة" {{ old('unit')=='قطعة' ? 'selected' : '' }}>قطعة</option>
                        <option value="علبة" {{ old('unit')=='علبة' ? 'selected' : '' }}>علبة</option>
                        <option value="كرتون" {{ old('unit')=='كرتون' ? 'selected' : '' }}>كرتون</option>
                        <option value="كجم" {{ old('unit')=='كجم' ? 'selected' : '' }}>كجم</option>
                        <option value="لتر" {{ old('unit')=='لتر' ? 'selected' : '' }}>لتر</option>
                        <option value="قطرة" {{ old('unit')=='قطرة' ? 'selected' : '' }}>قطرة</option>
                    </select>
                    @error('unit') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">قابل للتلف</label>
                    <select name="is_perishable" class="form-control" required>
                        <option value="1" {{ old('is_perishable') == '1' ? 'selected' : '' }}>نعم</option>
                        <option value="0" {{ old('is_perishable') === '0' ? 'selected' : '' }}>لا</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">تنبيه الكمية</label>
                    <input type="number" name="alert_quantity" class="form-control" value="{{ old('alert_quantity', 10) }}" min="0" required>
                </div>

                <button type="submit" class="btn btn-primary">حفظ المادة</button>
                <a href="{{ route('products.index') }}" class="btn btn-secondary">رجوع</a>
            </form>
        </div>
    </div>
</div>
@endsection