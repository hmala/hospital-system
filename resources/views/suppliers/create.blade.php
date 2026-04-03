@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">إضافة مورد جديد</div>
        <div class="card-body">
            <form action="{{ route('suppliers.store') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <label class="form-label">اسم المورد</label>
                    <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    @error('name') <div class="text-danger">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">المسؤول</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">الهاتف</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                </div>

                <div class="mb-3">
                    <label class="form-label">العنوان</label>
                    <textarea name="address" class="form-control">{{ old('address') }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary">حفظ المورد</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">رجوع</a>
            </form>
        </div>
    </div>
</div>
@endsection
