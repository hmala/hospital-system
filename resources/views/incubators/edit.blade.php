@extends('layouts.app')

@section('title', 'تعديل حاضنة / كبسولة رقم ' . $incubator->incubator_number)

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-warning text-dark py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-edit me-2"></i> تعديل حاضنة / كبسولة رقم {{ $incubator->incubator_number }}</h5>
                        <div>
                            <a href="{{ route('incubators.show', $incubator) }}" class="btn btn-sm btn-outline-dark me-1">
                                <i class="fas fa-eye me-1"></i> التفاصيل
                            </a>
                            <a href="{{ route('incubators.index') }}" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-arrow-left me-1"></i> القائمة
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('incubators.update', $incubator) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">رقم الحاضنة أو الكبسولة <span class="text-danger">*</span></label>
                                <input type="text" name="incubator_number" class="form-control @error('incubator_number') is-invalid @enderror" value="{{ old('incubator_number', $incubator->incubator_number) }}" required>
                                @error('incubator_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">النوع <span class="text-danger">*</span></label>
                                <select name="incubator_type" class="form-select @error('incubator_type') is-invalid @enderror" required>
                                    <option value="normal" {{ old('incubator_type', $incubator->incubator_type) == 'normal' ? 'selected' : '' }}>حاضنة عادية</option>
                                    <option value="oxygen" {{ old('incubator_type', $incubator->incubator_type) == 'oxygen' ? 'selected' : '' }}>حاضنة + أكسجين</option>
                                    <option value="phototherapy" {{ old('incubator_type', $incubator->incubator_type) == 'phototherapy' ? 'selected' : '' }}>حاضنة + علاج ضوئي</option>
                                    <option value="capsule" {{ old('incubator_type', $incubator->incubator_type) == 'capsule' ? 'selected' : '' }}>كبسولة علاج ضوئي (Capsule)</option>
                                </select>
                                @error('incubator_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الحالة <span class="text-danger">*</span></label>
                                <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="available" {{ old('status', $incubator->status) == 'available' ? 'selected' : '' }}>متاحة (جاهزة للاستقبال)</option>
                                    <option value="occupied" {{ old('status', $incubator->status) == 'occupied' ? 'selected' : '' }}>محجوزة (مشغولة)</option>
                                    <option value="sterilizing" {{ old('status', $incubator->status) == 'sterilizing' ? 'selected' : '' }}>قيد التعقيم والتطهير</option>
                                    <option value="maintenance" {{ old('status', $incubator->status) == 'maintenance' ? 'selected' : '' }}>صيانة</option>
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الأجرة اليومية الافتراضية (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="1000" name="daily_fee" class="form-control @error('daily_fee') is-invalid @enderror" value="{{ old('daily_fee', $incubator->daily_fee) }}" required>
                                @error('daily_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الغرفة / الردهة المرتبطة</label>
                                <select name="room_id" class="form-select @error('room_id') is-invalid @enderror">
                                    <option value="">-- بدون غرفة محددة --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id', $incubator->room_id) == $room->id ? 'selected' : '' }}>
                                            غرفة {{ $room->room_number }} ({{ $room->floor }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch" {{ old('is_active', $incubator->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="isActiveSwitch">الحاضنة نشطة بالخدمة</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">رابط بث كاميرا المراقبة IP/RTSP</label>
                                <input type="text" name="camera_stream_url" class="form-control @error('camera_stream_url') is-invalid @enderror" value="{{ old('camera_stream_url', $incubator->camera_stream_url) }}" placeholder="http://192.168.1.50/stream/live">
                                <small class="text-muted">يستخدم لبث الفيديو المباشر للأب والأم لدقيقة واحدة عبر الرابط الآمن.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">الوصف والمواصفات</label>
                                <textarea name="description" rows="2" class="form-control">{{ old('description', $incubator->description) }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">ملاحظات إضافية</label>
                                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $incubator->notes) }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="{{ route('incubators.index') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-warning fw-bold px-4">
                                <i class="fas fa-save me-1"></i> حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
