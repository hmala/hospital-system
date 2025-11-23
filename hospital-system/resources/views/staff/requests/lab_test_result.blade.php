@php
use App\Models\LabTestResult;

// الحصول على نتائج التحاليل المرتبطة بهذا الطلب
$labTestResult = LabTestResult::where('visit_id', $request->visit_id)
    ->where('request_id', $request->id)
    ->first();

function getTestIcon($testName) {
    $name = strtolower($testName);

    if (strpos($name, 'سكر') !== false || strpos($name, 'glucose') !== false) {
        return 'fas fa-tint text-danger';
    } elseif (strpos($name, 'ضغط') !== false || strpos($name, 'pressure') !== false) {
        return 'fas fa-heartbeat text-danger';
    } elseif (strpos($name, 'كوليسترول') !== false || strpos($name, 'cholesterol') !== false) {
        return 'fas fa-oil-can text-warning';
    } else {
        return 'fas fa-vial text-primary';
    }
}
@endphp

<!-- بيانات التحليل -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="{{ getTestIcon($request->description) }} me-2"></i>
            {{ $request->description }}
        </h5>
    </div>
    <div class="card-body">
        <form action="{{ route('staff.requests.update', $request) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="result_value" class="form-label">قيمة النتيجة</label>
                        <input type="number" step="0.01" class="form-control" id="result_value" 
                            name="result[value]" value="{{ $labTestResult->result_value ?? '' }}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="unit" class="form-label">وحدة القياس</label>
                        <input type="text" class="form-control" id="unit" 
                            name="result[unit]" value="{{ $labTestResult->unit ?? '' }}">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="reference_range" class="form-label">المدى المرجعي</label>
                        <input type="text" class="form-control" id="reference_range" 
                            name="result[reference_range]" value="{{ $labTestResult->reference_range ?? '' }}"
                            placeholder="مثال: 70-140">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">حالة التحليل</label>
                        <select class="form-select" id="status" name="result[status]">
                            <option value="pending" {{ ($labTestResult->status ?? 'pending') == 'pending' ? 'selected' : '' }}>معلق</option>
                            <option value="completed" {{ ($labTestResult->status ?? '') == 'completed' ? 'selected' : '' }}>مكتمل</option>
                            <option value="cancelled" {{ ($labTestResult->status ?? '') == 'cancelled' ? 'selected' : '' }}>ملغي</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="notes" class="form-label">ملاحظات</label>
                <textarea class="form-control" id="notes" name="result[notes]" 
                    rows="3">{{ $labTestResult->notes ?? '' }}</textarea>
            </div>

            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <strong>معلومات مساعدة:</strong>
                <ul class="mb-0">
                    <li>تأكد من إدخال وحدة القياس الصحيحة</li>
                    <li>أدخل المدى المرجعي بالصيغة المناسبة (مثل: 70-140)</li>
                    <li>يمكنك إضافة أي ملاحظات مهمة عن النتيجة</li>
                </ul>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    حفظ النتيجة
                </button>
            </div>
        </form>
    </div>
</div>

@if($labTestResult && $labTestResult->isCompleted())
<!-- عرض نتيجة التحليل -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-check-circle me-2"></i>
            نتيجة التحليل
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>القيمة:</strong> {{ $labTestResult->result_value }} {{ $labTestResult->unit }}</p>
                <p><strong>المدى المرجعي:</strong> {{ $labTestResult->reference_range }}</p>
            </div>
            <div class="col-md-6">
                <p>
                    <strong>الحالة:</strong> 
                    <span class="badge {{ $labTestResult->result_status == 'normal' ? 'bg-success' : 'bg-danger' }}">
                        {{ $labTestResult->result_status_text }}
                    </span>
                </p>
                <p><strong>تاريخ النتيجة:</strong> {{ $labTestResult->completed_at->format('Y-m-d H:i') }}</p>
            </div>
            @if($labTestResult->notes)
            <div class="col-12">
                <p><strong>ملاحظات:</strong> {{ $labTestResult->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endif