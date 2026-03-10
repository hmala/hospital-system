@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2 class="mb-1">
                    <i class="fas fa-ambulance me-2 text-danger"></i>
                    <i class="fas fa-x-ray me-2"></i>
                    طلب أشعة طوارئ #{{ $emergencyRadiology->emergency_id }}
                </h2>
                <p class="text-muted mb-0">
                    المريض: {{ $emergencyRadiology->patient->user->name }}
                </p>
            </div>
            <div class="d-flex gap-2">
                @if($emergencyRadiology->status === 'completed')
                    <a href="{{ route('staff.emergency-radiology.print', $emergencyRadiology) }}" target="_blank" class="btn btn-success">
                        <i class="fas fa-print me-1"></i>
                        طباعة
                    </a>
                @endif
                <a href="{{ route('staff.requests.index', ['type' => 'radiology']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-right me-1"></i>
                    رجوع
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

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
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>بيانات الطلب</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>رقم الطوارئ:</strong> #{{ $emergencyRadiology->emergency_id }}</p>
                    <p class="mb-2"><strong>الأولوية:</strong>
                        <span class="badge bg-{{ $emergencyRadiology->priority == 'critical' ? 'danger' : 'warning' }}">
                            {{ $emergencyRadiology->priority_text }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>الحالة:</strong>
                        <span class="badge bg-{{ $emergencyRadiology->status_badge_class }}">
                            {{ $emergencyRadiology->status_text }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>وقت الطلب:</strong> {{ $emergencyRadiology->requested_at?->format('Y-m-d H:i') }}</p>
                    @if($emergencyRadiology->completed_at)
                        <p class="mb-0"><strong>وقت الإكمال:</strong> {{ $emergencyRadiology->completed_at->format('Y-m-d H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>إجراء طلب الأشعة</h5>
                </div>
                <div class="card-body">
                    @if($emergencyRadiology->status == 'pending')
                        <form action="{{ route('staff.emergency-radiology.start', $emergencyRadiology) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-play me-1"></i>
                                بدء الإجراء
                            </button>
                        </form>
                    @else
                        <form action="{{ route('staff.emergency-radiology.complete', $emergencyRadiology) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <h6 class="mb-3">نتائج الفحوصات:</h6>
                            @foreach($emergencyRadiology->radiologyTypes as $type)
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="mb-3">
                                        <i class="fas fa-x-ray me-1"></i>
                                        {{ $type->name }}
                                    </h6>

                                    <div class="mb-3">
                                        <label class="form-label">النتيجة</label>
                                        <textarea
                                            name="results[{{ $type->id }}]"
                                            class="form-control"
                                            rows="3"
                                            placeholder="أدخل نتيجة الفحص...">{{ old('results.' . $type->id, $type->pivot->result ?? '') }}</textarea>
                                    </div>

                                    <div>
                                        <label class="form-label">ملف/صورة الفحص (اختياري)</label>
                                        <input type="file" name="images[{{ $type->id }}]" class="form-control" accept="image/*,.pdf">
                                        @if(!empty($type->pivot->image_path))
                                            <small class="d-block mt-2">
                                                <a href="{{ asset('storage/' . $type->pivot->image_path) }}" target="_blank">
                                                    عرض الملف المرفوع الحالي
                                                </a>
                                            </small>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            <div class="mb-3">
                                <label class="form-label">ملاحظات إضافية</label>
                                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $emergencyRadiology->notes) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>
                                {{ $emergencyRadiology->status === 'completed' ? 'تحديث النتائج' : 'حفظ النتائج وإكمال الطلب' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
