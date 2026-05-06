@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-ruler-combined me-2 text-primary"></i>
                        القيم المرجعية — <strong>{{ $labTest->name }}</strong>
                    </h4>
                    <small class="text-muted">{{ $labTest->code }} · {{ $labTest->unit }}</small>
                </div>
                <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> العودة للتحليل
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">

        {{-- جدول القيم الموجودة --}}
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>القيم المرجعية المضافة</h5>
                </div>
                <div class="card-body p-0">
                    @if($references->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                            لا توجد قيم مرجعية بعد
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover table-sm mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>الجنس</th>
                                        <th>الفئة العمرية</th>
                                        <th>المدى</th>
                                        <th>الوحدة</th>
                                        <th>ملاحظة</th>
                                        <th style="width:100px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($references as $ref)
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $ref->gender === 'male' ? 'info' : ($ref->gender === 'female' ? 'danger' : 'secondary') }}">
                                                {{ $ref->gender_display }}
                                            </span>
                                        </td>
                                        <td>{{ $ref->age_range_display }}</td>
                                        <td><strong>{{ $ref->range_display }}</strong></td>
                                        <td>{{ $ref->unit ?? $labTest->unit ?? '—' }}</td>
                                        <td><small class="text-muted">{{ $ref->notes ?? '—' }}</small></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-warning btn-edit"
                                                    data-id="{{ $ref->id }}"
                                                    data-gender="{{ $ref->gender }}"
                                                    data-age-min="{{ $ref->age_min }}"
                                                    data-age-max="{{ $ref->age_max === 999 ? '' : $ref->age_max }}"
                                                    data-ref-min="{{ $ref->ref_min }}"
                                                    data-ref-max="{{ $ref->ref_max }}"
                                                    data-ref-text="{{ $ref->ref_text }}"
                                                    data-unit="{{ $ref->unit }}"
                                                    data-notes="{{ $ref->notes }}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form action="{{ route('lab-tests.references.destroy', [$labTest, $ref]) }}"
                                                      method="POST" class="d-inline"
                                                      onsubmit="return confirm('حذف هذه القيمة المرجعية؟')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
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

        {{-- نموذج الإضافة / التعديل --}}
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0" id="formTitle"><i class="fas fa-plus-circle me-2"></i>إضافة قيمة مرجعية</h5>
                </div>
                <div class="card-body">
                    <form id="refForm" action="{{ route('lab-tests.references.store', $labTest) }}" method="POST">
                        @csrf
                        <input type="hidden" name="_method" id="formMethod" value="POST">

                        <div class="mb-3">
                            <label class="form-label fw-bold">الجنس</label>
                            <select name="gender" id="gender" class="form-select" required>
                                <option value="both">الجميع</option>
                                <option value="male">ذكور</option>
                                <option value="female">إناث</option>
                            </select>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">العمر من (سنة)</label>
                                <input type="number" name="age_min" id="age_min" class="form-control" value="0" min="0" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">العمر إلى</label>
                                <input type="number" name="age_max" id="age_max" class="form-control" placeholder="فارغ = بلا حد" min="0">
                                <small class="text-muted">اتركه فارغاً لـ "فأكثر"</small>
                            </div>
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label fw-bold">الحد الأدنى</label>
                                <input type="number" step="any" name="ref_min" id="ref_min" class="form-control" placeholder="مثال: 70">
                            </div>
                            <div class="col-6">
                                <label class="form-label fw-bold">الحد الأعلى</label>
                                <input type="number" step="any" name="ref_max" id="ref_max" class="form-control" placeholder="مثال: 110">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">مرجع نصي <small class="text-muted">(بديل أو إضافة)</small></label>
                            <input type="text" name="ref_text" id="ref_text" class="form-control" placeholder="مثال: Negative، &lt; 200">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">الوحدة <small class="text-muted">(إن اختلفت)</small></label>
                            <input type="text" name="unit" id="unit" class="form-control" placeholder="{{ $labTest->unit }}">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">ملاحظات</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-fill">
                                <i class="fas fa-save me-1"></i> حفظ
                            </button>
                            <button type="button" class="btn btn-secondary" id="resetForm">إلغاء</button>
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
document.querySelectorAll('.btn-edit').forEach(btn => {
    btn.addEventListener('click', function () {
        const d = this.dataset;
        const form = document.getElementById('refForm');

        form.action = `{{ url('lab-tests/' . $labTest->id . '/references') }}/${d.id}`;
        document.getElementById('formMethod').value = 'PUT';
        document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit me-2"></i>تعديل القيمة المرجعية';

        document.getElementById('gender').value  = d.gender;
        document.getElementById('age_min').value = d.ageMin;
        document.getElementById('age_max').value = d.ageMax;
        document.getElementById('ref_min').value = d.refMin;
        document.getElementById('ref_max').value = d.refMax;
        document.getElementById('ref_text').value = d.refText;
        document.getElementById('unit').value    = d.unit;
        document.getElementById('notes').value   = d.notes;

        form.scrollIntoView({ behavior: 'smooth' });
    });
});

document.getElementById('resetForm').addEventListener('click', function () {
    const form = document.getElementById('refForm');
    form.action = '{{ route("lab-tests.references.store", $labTest) }}';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>إضافة قيمة مرجعية';
    form.reset();
});
</script>
@endsection
