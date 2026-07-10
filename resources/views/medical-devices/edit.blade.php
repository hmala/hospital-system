@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-stethoscope me-2 text-primary"></i>
                        تعديل بيانات الجهاز الطبي
                    </h2>
                    <p class="text-muted">قم بتحديث بيانات الجهاز الطبي أدناه</p>
                </div>
                <div>
                    <a href="{{ route('medical-devices.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-1"></i>
                        العودة للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-edit me-2 text-primary"></i>
                نموذج تعديل الجهاز الطبي: {{ $medicalDevice->name }}
            </h5>
        </div>
        <div class="card-body">
            <form action="{{ route('medical-devices.update', $medicalDevice) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">اسم الجهاز <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $medicalDevice->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">نوع الجهاز (مثال: تنفس اصطناعي، تخدير، إلخ) <span class="text-danger">*</span></label>
                        <input type="text" name="type" id="type" class="form-control @error('type') is-invalid @enderror" value="{{ old('type', $medicalDevice->type) }}" required>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="supplier" class="form-label">الجهة المزودة (المورد)</label>
                        <input type="text" name="supplier" id="supplier" class="form-control @error('supplier') is-invalid @enderror" value="{{ old('supplier', $medicalDevice->supplier) }}">
                        @error('supplier')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">سعر الجهاز (د.ع) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control @error('price') is-invalid @enderror" value="{{ old('price', $medicalDevice->price) }}" min="0" required>
                        @error('price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="serial_number" class="form-label">الرقم التسلسلي (Serial Number)</label>
                        <input type="text" name="serial_number" id="serial_number" class="form-control @error('serial_number') is-invalid @enderror" value="{{ old('serial_number', $medicalDevice->serial_number) }}">
                        @error('serial_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="purchase_date" class="form-label">تاريخ الشراء</label>
                        <input type="date" name="purchase_date" id="purchase_date" class="form-control @error('purchase_date') is-invalid @enderror" value="{{ old('purchase_date', $medicalDevice->purchase_date ? $medicalDevice->purchase_date->format('Y-m-d') : '') }}">
                        @error('purchase_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label for="last_maintenance_at" class="form-label">تاريخ آخر صيانة</label>
                        <input type="date" name="last_maintenance_at" id="last_maintenance_at" class="form-control @error('last_maintenance_at') is-invalid @enderror" value="{{ old('last_maintenance_at', $medicalDevice->last_maintenance_at ? $medicalDevice->last_maintenance_at->format('Y-m-d') : '') }}">
                        @error('last_maintenance_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">حالة الجهاز <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            <option value="active" {{ old('status', $medicalDevice->status) == 'active' ? 'selected' : '' }}>نشط / متاح</option>
                            <option value="maintenance" {{ old('status', $medicalDevice->status) == 'maintenance' ? 'selected' : '' }}>تحت الصيانة</option>
                            <option value="inactive" {{ old('status', $medicalDevice->status) == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="location_id" class="form-label">القسم / الردهة</label>
                        <select name="location_id" id="location_id" class="form-select @error('location_id') is-invalid @enderror">
                            <option value="">-- غير محدد (عام) --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id', $medicalDevice->location_id) == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        تحديث البيانات
                    </button>
                    <a href="{{ route('medical-devices.index') }}" class="btn btn-light">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
