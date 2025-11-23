@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>
                            تعديل الاستعلام #{{ $visit->id }}
                        </h4>
                        <div>
                            <a href="{{ route('inquiry.show', $visit->id) }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>العودة للتفاصيل
                            </a>
                        </div>
                    </div>
                </div>

                <form action="{{ route('inquiry.update', $visit->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="card-body">
                        <!-- معلومات المريض (للعرض فقط) -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-injured me-2"></i>معلومات المريض
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <p><strong>الاسم:</strong> {{ $visit->patient->user->name }}</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>العمر:</strong> {{ $visit->patient->age ?? 'غير محدد' }} سنة</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>الهاتف:</strong> {{ $visit->patient->user->phone ?? 'غير محدد' }}</p>
                                            </div>
                                            <div class="col-md-3">
                                                <p><strong>تاريخ الزيارة:</strong> {{ $visit->visit_date->format('Y-m-d H:i') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- تعديل الاستعلام -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card border-primary">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-notes-medical me-2"></i>تفاصيل الاستعلام
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="chief_complaint" class="form-label">
                                                <i class="fas fa-comment-medical me-1"></i>الشكوى الرئيسية <span class="text-danger">*</span>
                                            </label>
                                            <textarea class="form-control @error('chief_complaint') is-invalid @enderror"
                                                      id="chief_complaint"
                                                      name="chief_complaint"
                                                      rows="4"
                                                      placeholder="اكتب الشكوى الرئيسية للمريض..."
                                                      required>{{ old('chief_complaint', $visit->chief_complaint) }}</textarea>
                                            @error('chief_complaint')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="mb-3">
                                            <label for="notes" class="form-label">
                                                <i class="fas fa-sticky-note me-1"></i>الملاحظات الإضافية
                                            </label>
                                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                                      id="notes"
                                                      name="notes"
                                                      rows="3"
                                                      placeholder="أي ملاحظات إضافية...">{{ old('notes', $visit->notes) }}</textarea>
                                            @error('notes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">
                                                        <i class="fas fa-tasks me-1"></i>حالة الاستعلام <span class="text-danger">*</span>
                                                    </label>
                                                    <select class="form-select @error('status') is-invalid @enderror"
                                                            id="status"
                                                            name="status"
                                                            required>
                                                        <option value="">اختر الحالة</option>
                                                        <option value="pending" {{ old('status', $visit->status) == 'pending' ? 'selected' : '' }}>
                                                            في الانتظار
                                                        </option>
                                                        <option value="in_progress" {{ old('status', $visit->status) == 'in_progress' ? 'selected' : '' }}>
                                                            قيد التنفيذ
                                                        </option>
                                                        <option value="completed" {{ old('status', $visit->status) == 'completed' ? 'selected' : '' }}>
                                                            مكتمل
                                                        </option>
                                                        <option value="cancelled" {{ old('status', $visit->status) == 'cancelled' ? 'selected' : '' }}>
                                                            ملغي
                                                        </option>
                                                    </select>
                                                    @error('status')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="doctor_id" class="form-label">
                                                        <i class="fas fa-user-md me-1"></i>الطبيب المعالج
                                                    </label>
                                                    <select class="form-select select2 @error('doctor_id') is-invalid @enderror"
                                                            id="doctor_id"
                                                            name="doctor_id">
                                                        <option value="">اختر الطبيب (اختياري)</option>
                                                        @foreach($doctors as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ old('doctor_id', $visit->doctor_id) == $doctor->id ? 'selected' : '' }}>
                                                            {{ $doctor->user->name }} - {{ $doctor->department->name ?? 'غير محدد' }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    @error('doctor_id')
                                                        <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- ملخص الطلبات المرتبطة -->
                                @if($visit->requests && $visit->requests->count() > 0)
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-tasks me-2"></i>الطلبات المرتبطة
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        @foreach($visit->requests as $request)
                                        <div class="mb-3 p-2 border rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>
                                                        @if($request->type == 'lab')
                                                            <i class="fas fa-flask text-primary me-1"></i>تحاليل طبية
                                                        @elseif($request->type == 'radiology')
                                                            <i class="fas fa-x-ray text-info me-1"></i>أشعة
                                                        @elseif($request->type == 'pharmacy')
                                                            <i class="fas fa-pills text-success me-1"></i>صيدلية
                                                        @elseif($request->type == 'checkup')
                                                            <i class="fas fa-stethoscope text-warning me-1"></i>كشف طبي
                                                        @else
                                                            {{ $request->type }}
                                                        @endif
                                                    </strong>
                                                    <br>
                                                    <small class="text-muted">{{ Str::limit($request->description, 50) }}</small>
                                                </div>
                                                <span class="badge
                                                    @if($request->status == 'pending') bg-warning
                                                    @elseif($request->status == 'in_progress') bg-primary
                                                    @elseif($request->status == 'completed') bg-success
                                                    @elseif($request->status == 'cancelled') bg-danger
                                                    @else bg-secondary
                                                    @endif">
                                                    @if($request->status == 'pending') في الانتظار
                                                    @elseif($request->status == 'in_progress') قيد التنفيذ
                                                    @elseif($request->status == 'completed') مكتمل
                                                    @elseif($request->status == 'cancelled') ملغي
                                                    @else {{ $request->status }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <!-- معلومات إضافية -->
                                <div class="card border-info">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-info-circle me-2"></i>معلومات إضافية
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <p><strong>رقم الاستعلام:</strong> #{{ $visit->id }}</p>
                                        <p><strong>نوع الزيارة:</strong> {{ $visit->visit_type }}</p>
                                        <p><strong>تاريخ الإنشاء:</strong> {{ $visit->created_at->format('Y-m-d H:i') }}</p>
                                        <p><strong>آخر تحديث:</strong> {{ $visit->updated_at->format('Y-m-d H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- أزرار الإجراءات -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="fas fa-save me-2"></i>حفظ التغييرات
                                        </button>
                                        <a href="{{ route('inquiry.show', $visit->id) }}" class="btn btn-secondary btn-lg ms-2">
                                            <i class="fas fa-times me-2"></i>إلغاء
                                        </a>
                                    </div>
                                    <div>
                                        <a href="{{ route('inquiry.index') }}" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>العودة للقائمة
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'اختر...',
        allowClear: true,
        width: '100%'
    });
});
</script>
@endsection