@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-plus-circle me-2 text-primary"></i>
                        إضافة غرفة جديدة
                    </h2>
                    <p class="text-muted mb-0">إضافة غرفة جديدة للمستشفى</p>
                </div>
                <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i>
                    العودة
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-bed me-2"></i>
                        بيانات الغرفة
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('rooms.store') }}" method="POST">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="room_number" class="form-label">
                                    <i class="fas fa-hashtag me-1"></i>
                                    رقم الغرفة <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control @error('room_number') is-invalid @enderror" 
                                       id="room_number" name="room_number" value="{{ old('room_number') }}" 
                                       placeholder="مثال: 101" required>
                                @error('room_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="room_type" class="form-label">
                                    <i class="fas fa-star me-1"></i>
                                    نوع الغرفة <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('room_type') is-invalid @enderror" 
                                        id="room_type" name="room_type" required>
                                    <option value="regular" {{ old('room_type') == 'regular' ? 'selected' : '' }}>عادية</option>
                                    <option value="vip" {{ old('room_type') == 'vip' ? 'selected' : '' }}>VIP</option>
                                </select>
                                @error('room_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="floor" class="form-label">
                                    <i class="fas fa-layer-group me-1"></i>
                                    الطابق
                                </label>
                                <input type="text" class="form-control @error('floor') is-invalid @enderror" 
                                       id="floor" name="floor" value="{{ old('floor') }}" 
                                       placeholder="مثال: الطابق الأول">
                                @error('floor')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="daily_fee" class="form-label">
                                    <i class="fas fa-money-bill me-1"></i>
                                    الأجرة اليومية (د.ع) <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control @error('daily_fee') is-invalid @enderror" 
                                       id="daily_fee" name="daily_fee" value="{{ old('daily_fee', 0) }}" 
                                       min="0" step="1000" required>
                                @error('daily_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="beds_count" class="form-label">
                                    <i class="fas fa-bed me-1"></i>
                                    عدد الأسرّة <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control @error('beds_count') is-invalid @enderror" 
                                       id="beds_count" name="beds_count" value="{{ old('beds_count', 1) }}" 
                                       min="1" max="10" required>
                                @error('beds_count')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">المزايا</label>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="has_bathroom" 
                                               name="has_bathroom" value="1" {{ old('has_bathroom', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_bathroom">
                                            <i class="fas fa-bath text-info me-1"></i> حمام خاص
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="has_tv" 
                                               name="has_tv" value="1" {{ old('has_tv') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_tv">
                                            <i class="fas fa-tv text-primary me-1"></i> تلفزيون
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="has_ac" 
                                               name="has_ac" value="1" {{ old('has_ac', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_ac">
                                            <i class="fas fa-snowflake text-info me-1"></i> تكييف
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>
                                وصف الغرفة
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="وصف إضافي للغرفة...">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_active" 
                                       name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">
                                    الغرفة نشطة ومتاحة للحجز
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('rooms.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i> إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> حفظ الغرفة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
