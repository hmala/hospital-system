<!-- resources/views/radiology/create.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>إنشاء طلب إشعة جديد</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('radiology.store') }}" method="POST">
                        @csrf

                        <div class="row">
                            <!-- اختيار المريض -->
                            <div class="col-md-6 mb-3">
                                <label for="patient_id" class="form-label">المريض <span class="text-danger">*</span></label>
                                @if(isset($patient))
                                    <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                    <input type="text" class="form-control" value="{{ $patient->user->name }}" readonly>
                                @else
                                    <select name="patient_id" id="patient_id" class="form-select @error('patient_id') is-invalid @enderror" required>
                                        <option value="">اختر المريض</option>
                                        @foreach(\App\Models\Patient::with('user')->get() as $p)
                                        <option value="{{ $p->id }}" {{ old('patient_id') == $p->id ? 'selected' : '' }}>
                                            {{ $p->user->name }} - {{ $p->user->phone ?? 'لا يوجد رقم' }}
                                        </option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('patient_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- اختيار الطبيب -->
                            <div class="col-md-6 mb-3">
                                <label for="doctor_id" class="form-label">الطبيب المطلب <span class="text-danger">*</span></label>
                                @if(isset($doctor))
                                    <input type="hidden" name="doctor_id" value="{{ $doctor->id }}">
                                    <input type="text" class="form-control" value="د. {{ $doctor->user->name }}" readonly>
                                @else
                                    <select name="doctor_id" id="doctor_id" class="form-select @error('doctor_id') is-invalid @enderror" required>
                                        <option value="">اختر الطبيب</option>
                                        @foreach($doctors as $d)
                                        <option value="{{ $d->id }}" {{ old('doctor_id') == $d->id ? 'selected' : '' }}>
                                            د. {{ $d->user->name }} - {{ $d->specialization ?? 'غير محدد' }}
                                        </option>
                                        @endforeach
                                    </select>
                                @endif
                                @error('doctor_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- نوع الإشعة -->
                            <div class="col-md-6 mb-3">
                                <label for="radiology_type_id" class="form-label">نوع الإشعة <span class="text-danger">*</span></label>
                                <select name="radiology_type_id" id="radiology_type_id" class="form-select @error('radiology_type_id') is-invalid @enderror" required>
                                    <option value="">اختر نوع الإشعة</option>
                                    @foreach($radiologyTypes as $type)
                                    <option value="{{ $type->id }}" {{ old('radiology_type_id') == $type->id ? 'selected' : '' }}>
                                        {{ $type->name }} ({{ $type->code }}) - {{ number_format($type->base_price) }} د.ع
                                    </option>
                                    @endforeach
                                </select>
                                @error('radiology_type_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- الأولوية -->
                            <div class="col-md-6 mb-3">
                                <label for="priority" class="form-label">الأولوية <span class="text-danger">*</span></label>
                                <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror" required>
                                    <option value="normal" {{ old('priority') == 'normal' ? 'selected' : '' }}>عادي</option>
                                    <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>عاجل</option>
                                    <option value="emergency" {{ old('priority') == 'emergency' ? 'selected' : '' }}>طوارئ</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- الإشارة السريرية -->
                        <div class="mb-3">
                            <label for="clinical_indication" class="form-label">الإشارة السريرية <span class="text-danger">*</span></label>
                            <textarea name="clinical_indication" id="clinical_indication" class="form-control @error('clinical_indication') is-invalid @enderror" rows="3" placeholder="وصف الإشارة السريرية للإشعة..." required>{{ old('clinical_indication') }}</textarea>
                            @error('clinical_indication')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- تعليمات خاصة -->
                            <div class="col-md-6 mb-3">
                                <label for="specific_instructions" class="form-label">تعليمات خاصة</label>
                                <textarea name="specific_instructions" id="specific_instructions" class="form-control @error('specific_instructions') is-invalid @enderror" rows="3" placeholder="تعليمات خاصة للإشعة...">{{ old('specific_instructions') }}</textarea>
                                @error('specific_instructions')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- ملاحظات -->
                            <div class="col-md-6 mb-3">
                                <label for="notes" class="form-label">ملاحظات إضافية</label>
                                <textarea name="notes" id="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="ملاحظات إضافية...">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- تاريخ الطلب -->
                        <div class="mb-3">
                            <label for="requested_date" class="form-label">تاريخ ووقت الطلب <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="requested_date" id="requested_date" class="form-control @error('requested_date') is-invalid @enderror" value="{{ old('requested_date', now()->format('Y-m-d\TH:i')) }}" required>
                            @error('requested_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @if(isset($visitId))
                            <input type="hidden" name="visit_id" value="{{ $visitId }}">
                        @endif

                        <div class="d-flex justify-content-between">
                            <a href="{{ route('radiology.index') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary">إنشاء الطلب</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// تحديث معلومات نوع الإشعة عند الاختيار
document.getElementById('radiology_type_id').addEventListener('change', function() {
    const typeId = this.value;
    if (typeId) {
        // يمكن إضافة AJAX لعرض تفاصيل النوع المحدد
        console.log('Selected type:', typeId);
    }
});
</script>
@endsection