<div class="card">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">تفاصيل إعداد العمولة</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ $action }}">
            @csrf
            @if($method === 'PUT')
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">الطبيب</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">اختر طبيباً</option>
                        @foreach($doctors as $doctor)
                            <option value="{{ $doctor->id }}" {{ old('doctor_id', $setting->doctor_id ?? '') == $doctor->id ? 'selected' : '' }}>{{ $doctor->user?->name ?? 'طبيب #' . $doctor->id }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">القسم (اختياري)</label>
                    <select name="department_id" class="form-select">
                        <option value="">عام</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id', $setting->department_id ?? '') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع الخدمة (اختياري)</label>
                    <select name="service_type_id" class="form-select">
                        <option value="">عام</option>
                        @foreach($serviceTypes as $serviceType)
                            <option value="{{ $serviceType->id }}" {{ old('service_type_id', $setting->service_type_id ?? '') == $serviceType->id ? 'selected' : '' }}>{{ $serviceType->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">نوع العمولة</label>
                    <input type="hidden" name="commission_type" value="fixed">
                    <div class="form-control-plaintext">مبلغ ثابت</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">قيمة العمولة</label>
                    <input type="number" step="0.01" name="commission_value" value="0" class="form-control" readonly>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">المبلغ الثابت</label>
                    <input type="number" step="0.01" name="fixed_amount" value="{{ old('fixed_amount', $setting->fixed_amount ?? '') }}" class="form-control" placeholder="ادخل مبلغاً ثابتاً عند الحاجة">
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">سريان من</label>
                    <input type="date" name="valid_from" value="{{ old('valid_from', isset($setting) ? optional($setting->valid_from)->format('Y-m-d') : '') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">سريان إلى</label>
                    <input type="date" name="valid_until" value="{{ old('valid_until', isset($setting) ? optional($setting->valid_until)->format('Y-m-d') : '') }}" class="form-control">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="3">{{ old('notes', $setting->notes ?? '') }}</textarea>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $setting->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">نشط</label>
            </div>

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <button class="btn btn-primary">حفظ</button>
            <a href="{{ route('admin.doctor-commission-settings.index') }}" class="btn btn-secondary">إلغاء</a>
        </form>
    </div>
</div>
