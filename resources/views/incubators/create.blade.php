@extends('layouts.app')

@section('title', 'إضافة حاضنة أو كبسولة جديدة')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i> إضافة حاضنة / كبسولة جديدة</h5>
                        <a href="{{ route('incubators.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-left me-1"></i> العودة للقائمة
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('incubators.store') }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">رقم الحاضنة أو الكبسولة <span class="text-danger">*</span></label>
                                <input type="text" name="incubator_number" class="form-control @error('incubator_number') is-invalid @enderror" value="{{ old('incubator_number') }}" placeholder="مثال: 17 أو كبسولة 1" required>
                                @error('incubator_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">النوع <span class="text-danger">*</span></label>
                                <select name="incubator_type" class="form-select @error('incubator_type') is-invalid @enderror" required>
                                    <option value="normal" {{ old('incubator_type') == 'normal' ? 'selected' : '' }}>حاضنة عادية</option>
                                    <option value="oxygen" {{ old('incubator_type') == 'oxygen' ? 'selected' : '' }}>حاضنة + أكسجين</option>
                                    <option value="phototherapy" {{ old('incubator_type') == 'phototherapy' ? 'selected' : '' }}>حاضنة + علاج ضوئي</option>
                                    <option value="capsule" {{ old('incubator_type') == 'capsule' ? 'selected' : '' }}>كبسولة علاج ضوئي (Capsule)</option>
                                </select>
                                @error('incubator_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الغرفة / الردهة المرتبطة</label>
                                <select name="room_id" class="form-select @error('room_id') is-invalid @enderror">
                                    <option value="">-- بدون غرفة محددة --</option>
                                    @foreach($rooms as $room)
                                        <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                            غرفة {{ $room->room_number }} ({{ $room->floor }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('room_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">الأجرة اليومية الافتراضية (د.ع) <span class="text-danger">*</span></label>
                                <input type="number" step="1000" name="daily_fee" class="form-control @error('daily_fee') is-invalid @enderror" value="{{ old('daily_fee', 200000) }}" required>
                                @error('daily_fee')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">رابط بث كاميرا المراقبة IP/RTSP (اختياري)</label>
                                <input type="text" name="camera_stream_url" class="form-control @error('camera_stream_url') is-invalid @enderror" value="{{ old('camera_stream_url') }}" placeholder="http://192.168.1.50/stream/live">
                                <small class="text-muted">يستخدم لبث الفيديو المباشر للأب لدقيقة واحدة عبر الرابط الآمن.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">الوصف والمواصفات</label>
                                <textarea name="description" rows="2" class="form-control" placeholder="مواصفات الجهاز، الشركة المصنعة...">{{ old('description') }}</textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">ملاحظات إضافية</label>
                                <textarea name="notes" rows="2" class="form-control" placeholder="ملاحظات الصيانة والتشغيل...">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                            <a href="{{ route('incubators.index') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="fas fa-save me-1"></i> حفظ الحاضنة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
