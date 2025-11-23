@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2>
                <i class="fas fa-procedures me-2"></i>
                تحويل المريض لحجز عملية جراحية
            </h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-warning">
            <h5 class="mb-0">
                <i class="fas fa-user-injured me-2"></i>
                بيانات المريض: {{ $visit->patient->user->name }}
            </h5>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                سيتم تحويل المريض للاستعلامات لحجز العملية بعد إكمال الإجراءات المطلوبة (الدفع - التحاليل - الأشعة)
            </div>

            <form action="{{ route('doctor.visits.mark-needs-surgery', $visit) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="mb-4">
                    <label for="surgery_notes" class="form-label">
                        <i class="fas fa-notes-medical me-2"></i>
                        ملاحظات العملية المطلوبة <span class="text-danger">*</span>
                    </label>
                    <textarea name="surgery_notes" id="surgery_notes" class="form-control @error('surgery_notes') is-invalid @enderror" 
                              rows="6" required 
                              placeholder="مثال:&#10;- نوع العملية: استئصال الزائدة الدودية&#10;- التحاليل المطلوبة: CBC، وظائف كلى&#10;- الأشعة المطلوبة: أشعة بطن&#10;- ملاحظات أخرى: العملية عاجلة">{{ old('surgery_notes') }}</textarea>
                    @error('surgery_notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <small class="text-muted">
                        اكتب جميع التفاصيل المهمة: نوع العملية، التحاليل المطلوبة، الأشعة المطلوبة، درجة الأولوية
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-paper-plane me-1"></i>
                        تحويل للاستعلامات
                    </button>
                    <a href="{{ route('doctor.visits.show', $visit) }}" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
