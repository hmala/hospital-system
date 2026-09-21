@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-list-check me-2 text-primary"></i>
                        الفحوصات والمعايير الفرعية — <strong>{{ $labTest->name }}</strong>
                    </h4>
                    <small class="text-muted">
                        {{ $labTest->code ? $labTest->code . ' · ' : '' }}{{ $labTest->main_category }} 
                        @if($labTest->subcategory) · {{ $labTest->subcategory }} @endif
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('lab-tests.references.index', $labTest) }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-ruler-combined me-1"></i> القيم المرجعية
                    </a>
                    <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i> العودة للتحليل
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- جدول الفحوصات الفرعية الموجودة --}}
        <div class="col-lg-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>قائمة الفحوصات الفرعية المضافة</h5>
                    <span class="badge bg-light text-primary fs-6">{{ $subTests->count() }} معيار</span>
                </div>
                <div class="card-body p-0">
                    @if($subTests->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block text-secondary"></i>
                            <h6 class="fw-bold">لا توجد فحوصات فرعية مضافة بعد</h6>
                            <p class="small text-muted mb-0">استخدم النموذج المقابل لإضافة معايير ومكونات هذا التحليل (مثل WBC, RBC, PLT...)</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 50px;" class="text-center">#</th>
                                        <th>اسم الفحص الفرعي</th>
                                        <th>المدى المرجعي (Reference Range)</th>
                                        <th>الوحدة</th>
                                        <th>نوع النتيجة</th>
                                        <th style="width: 70px;" class="text-center">الترتيب</th>
                                        <th>ملاحظات</th>
                                        <th style="width: 100px;" class="text-center">إجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subTests as $index => $sub)
                                    <tr>
                                        <td class="text-center text-muted fw-bold">{{ $index + 1 }}</td>
                                        <td>
                                            <strong class="text-dark">{{ $sub->name }}</strong>
                                        </td>
                                        <td>
                                            @if($sub->reference_range)
                                                <span class="badge bg-light text-primary border border-primary-subtle fw-bold font-monospace">{{ $sub->reference_range }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sub->unit)
                                                <span class="badge bg-light text-dark border">{{ $sub->unit }}</span>
                                            @else
                                                <span class="text-muted small">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sub->result_type == 'numeric')
                                                <span class="badge bg-info-subtle text-info border border-info-subtle">رقمي</span>
                                            @elseif($sub->result_type == 'positive_negative')
                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle">موجب/سالب</span>
                                            @else
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">نصي</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary">{{ $sub->sort_order }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $sub->notes ?: '—' }}</small>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning btn-edit-sub"
                                                    data-id="{{ $sub->id }}"
                                                    data-name="{{ $sub->name }}"
                                                    data-unit="{{ $sub->unit }}"
                                                    data-reference-range="{{ $sub->reference_range }}"
                                                    data-result-type="{{ $sub->result_type }}"
                                                    data-sort-order="{{ $sub->sort_order }}"
                                                    data-notes="{{ $sub->notes }}"
                                                    title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('lab-tests.sub-tests.destroy', [$labTest, $sub]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الفحص الفرعي؟')">
                                                    @csrf 
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- نموذج الإضافة / التعديل الجانبي بنفس تصميم القيم المرجعية --}}
        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white py-2" id="formCardHeader">
                    <h5 class="mb-0" id="formTitle">
                        <i class="fas fa-plus-circle me-2"></i>إضافة فحص فرعي جديد
                    </h5>
                </div>
                <div class="card-body">
                    <form id="subTestForm" action="{{ route('lab-tests.sub-tests.store', $labTest) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold">اسم الفحص الفرعي <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required placeholder="مثال: WBC أو Hemoglobin أو pH">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">وحدة القياس</label>
                                <input type="text" name="unit" id="unit" class="form-control" placeholder="مثال: g/dL أو /µL">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">نوع النتيجة</label>
                                <select name="result_type" id="result_type" class="form-select">
                                    <option value="numeric">رقمي (Numeric)</option>
                                    <option value="text">نصي (Text)</option>
                                    <option value="positive_negative">موجب / سالب</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">المدى المرجعي الطبيعي (Reference Range)</label>
                            <input type="text" name="reference_range" id="reference_range" class="form-control font-monospace" placeholder="مثال: 13.5-17.5 أو 4.0-11.0 أو < 200">
                            <div class="form-text small text-muted">اكتب المدى بتنسيق (أدنى - أعلى) مثل <code>70-110</code> أو <code>&lt; 200</code> ليتم تقييم النتيجة تلقائياً.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">الترتيب في التقرير</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="{{ $subTests->count() + 1 }}" min="0">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="ملاحظات إضافية (اختياري)"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-fill fw-bold" id="submitBtn">
                                <i class="fas fa-save me-1"></i> حفظ
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetBtn" style="display: none;">
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.btn-edit-sub').forEach(btn => {
    btn.addEventListener('click', function () {
        const d = this.dataset;
        const form = document.getElementById('subTestForm');
        const header = document.getElementById('formCardHeader');
        const submitBtn = document.getElementById('submitBtn');
        const resetBtn = document.getElementById('resetBtn');

        form.action = `{{ url('lab-tests/' . $labTest->id . '/sub-tests') }}/${d.id}`;
        document.getElementById('formMethod').value = 'PUT';
        
        header.className = 'card-header bg-warning text-dark py-2';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>تعديل الفحص الفرعي: ' + d.name;
        submitBtn.className = 'btn btn-warning flex-fill fw-bold text-dark';
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ التعديل';
        resetBtn.style.display = 'inline-block';

        document.getElementById('name').value = d.name || '';
        document.getElementById('unit').value = d.unit || '';
        document.getElementById('reference_range').value = d.referenceRange || '';
        document.getElementById('result_type').value = d.resultType || 'numeric';
        document.getElementById('sort_order').value = d.sortOrder || '1';
        document.getElementById('notes').value = d.notes || '';

        form.scrollIntoView({ behavior: 'smooth' });
    });
});

document.getElementById('resetBtn').addEventListener('click', function () {
    const form = document.getElementById('subTestForm');
    const header = document.getElementById('formCardHeader');
    const submitBtn = document.getElementById('submitBtn');

    form.action = '{{ route("lab-tests.sub-tests.store", $labTest) }}';
    document.getElementById('formMethod').value = 'POST';
    
    header.className = 'card-header bg-success text-white py-2';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>إضافة فحص فرعي جديد';
    submitBtn.className = 'btn btn-success flex-fill fw-bold';
    submitBtn.innerHTML = '<i class="fas fa-save me-1"></i> حفظ';
    this.style.display = 'none';
    
    form.reset();
    document.getElementById('sort_order').value = '{{ $subTests->count() + 1 }}';
});
</script>
@endsection
