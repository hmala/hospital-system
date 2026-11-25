@extends('layouts.app')

@section('title', 'دليل الأدوية المضافة للخدج - إعدادات الخدج')

@section('content')
<div class="container-fluid py-4">
    @include('nicu.partials.settings-nav', ['activeTab' => 'custom-medicines'])

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-capsules text-info me-2"></i>
                        دليل الأدوية والتركيبات المضافة في قسم الخدج (NICU)
                    </h2>
                    <p class="text-muted mb-0">يتم حفظ أي دواء يكتبه طبيب الخدج وغير موجود بالصيدلية هنا تلقائياً ليظهر بالبحث الفوري لجميع الحاضنات دون التداخل مع جرد الصيدلية.</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-info text-white shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createCustomMedModal">
                        <i class="fas fa-plus-circle me-1"></i> إضافة دواء جديد للدليل
                    </button>
                    <a href="{{ route('nicu.index') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> العودة للوحة الحاضنات
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-info bg-opacity-10 h-100 rounded-4 border-start border-4 border-info">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block">إجمالي الأدوية المضافة بالخدج</span>
                        <h3 class="mb-0 fw-bold text-dark">{{ $stats['total'] }}</h3>
                    </div>
                    <div class="p-3 bg-info text-white rounded-circle"><i class="fas fa-pills fa-lg"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 h-100 rounded-4 border-start border-4 border-success">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block">المفعل بالبحث التلقائي</span>
                        <h3 class="mb-0 fw-bold text-success">{{ $stats['active'] }}</h3>
                    </div>
                    <div class="p-3 bg-success text-white rounded-circle"><i class="fas fa-check-circle fa-lg"></i></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 h-100 rounded-4 border-start border-4 border-warning">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block">إجمالي مرات الوصف والاستخدام</span>
                        <h3 class="mb-0 fw-bold text-dark">{{ number_format($stats['total_usages']) }}</h3>
                    </div>
                    <div class="p-3 bg-warning text-dark rounded-circle"><i class="fas fa-fire fa-lg"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الأدوية المضافة -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-light border-0 p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list me-2 text-info"></i> سجل الأدوية والتركيبات المسجلة</h5>
            <form action="{{ route('nicu.custom-medicines.index') }}" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="بحث بالاسم أو التركيز..." value="{{ $search }}">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
                @if($search !== '')
                    <a href="{{ route('nicu.custom-medicines.index') }}" class="btn btn-sm btn-outline-secondary">مسح</a>
                @endif
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">#</th>
                            <th>اسم الدواء / التركيبة</th>
                            <th>الجرعة الافتراضية</th>
                            <th>المسار والتكرار</th>
                            <th class="text-center">المدة (أيام)</th>
                            <th class="text-center">الاستخدام</th>
                            <th>المضيف</th>
                            <th class="text-center">الحالة</th>
                            <th class="text-center pe-3">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customMedicines as $med)
                            <tr>
                                <td class="ps-3 text-muted fw-bold">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $med->name }}</div>
                                    <small class="text-muted">
                                        {{ $med->generic_name ?: '' }}
                                        {{ $med->dosage_form ? ' • '.$med->dosage_form : '' }}
                                        {{ $med->strength ? ' • '.$med->strength : '' }}
                                    </small>
                                </td>
                                <td><span class="badge bg-light text-dark border">{{ $med->default_dose ?: '—' }}</span></td>
                                <td class="small text-muted">
                                    {{ $med->default_route ?: '—' }}
                                    @if($med->default_frequency)
                                        <div class="text-dark">{{ $med->default_frequency }}</div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($med->default_duration_days)
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $med->default_duration_days }} أيام</span>
                                    @else
                                        <span class="text-muted small">مستمر</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning-subtle text-dark border rounded-pill px-2.5 py-1">
                                        <i class="fas fa-fire text-danger me-1"></i> {{ $med->usage_count }}
                                    </span>
                                </td>
                                <td class="small text-muted">{{ $med->creator?->name ?? 'طبيب الخدج' }}</td>
                                <td class="text-center">
                                    <form action="{{ route('nicu.custom-medicines.toggle', $med) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $med->is_active ? 'btn-success-subtle text-success' : 'btn-secondary-subtle text-secondary' }} rounded-pill px-3 border">
                                            {{ $med->is_active ? 'مفعل' : 'معطل' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="text-center pe-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5"
                                            onclick='openEditCustomMedModal(@json($med))'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="{{ route('nicu.custom-medicines.destroy', $med) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدواء من دليل الخدج؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5"><i class="fas fa-trash-alt"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-capsules fa-3x mb-2 opacity-25"></i>
                                    <p class="mb-0">لا توجد أدوية مضافة يدوياً بالخدج بعد. أي دواء يكتبه الطبيب بالوصفة ولا يوجد بالصيدلية سيُحفظ هنا تلقائياً.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($customMedicines->hasPages())
            <div class="card-footer bg-white border-0 d-flex justify-content-center">
                {{ $customMedicines->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal إضافة دواء -->
<div class="modal fade" id="createCustomMedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-info text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-1"></i> إضافة دواء جديد لدليل الخدج</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('nicu.custom-medicines.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">اسم الدواء / التركيبة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="مثال: Caffeine Citrate 20mg/ml">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">الاسم العلمي:</label>
                            <input type="text" name="generic_name" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">التركيز / الشكل:</label>
                            <input type="text" name="strength" class="form-control form-control-sm" placeholder="20mg/ml">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">الجرعة الافتراضية:</label>
                            <input type="text" name="default_dose" class="form-control form-control-sm" placeholder="مثال: 10 mg">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">أيام الاستخدام الافتراضية:</label>
                            <input type="number" name="default_duration_days" min="1" max="365" class="form-control form-control-sm" placeholder="مثال: 5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">المسار الافتراضي:</label>
                            <input type="text" name="default_route" class="form-control form-control-sm" placeholder="وريدي IV">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">التكرار الافتراضي:</label>
                            <input type="text" name="default_frequency" class="form-control form-control-sm" placeholder="كل 12 ساعة (BID)">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">ملاحظات سريرية:</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold px-4">حفظ في دليل الخدج</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تعديل دواء -->
<div class="modal fade" id="editCustomMedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-primary text-white rounded-top-4">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-1"></i> تعديل بيانات الدواء بدليل الخدج</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCustomMedForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="mb-2">
                        <label class="form-label small fw-bold">اسم الدواء <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="editMedName" class="form-control form-control-sm" required>
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-bold">الاسم العلمي:</label>
                            <input type="text" name="generic_name" id="editMedGeneric" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">التركيز:</label>
                            <input type="text" name="strength" id="editMedStrength" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">الجرعة الافتراضية:</label>
                            <input type="text" name="default_dose" id="editMedDose" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">أيام الاستخدام الافتراضية:</label>
                            <input type="number" name="default_duration_days" id="editMedDays" min="1" max="365" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">المسار الافتراضي:</label>
                            <input type="text" name="default_route" id="editMedRoute" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">التكرار الافتراضي:</label>
                            <input type="text" name="default_frequency" id="editMedFreq" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label small fw-bold">ملاحظات سريرية:</label>
                        <textarea name="notes" id="editMedNotes" rows="2" class="form-control form-control-sm"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-bold px-4">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditCustomMedModal(med) {
    document.getElementById('editCustomMedForm').action = `{{ url('nicu/custom-medicines') }}/${med.id}`;
    document.getElementById('editMedName').value = med.name || '';
    document.getElementById('editMedGeneric').value = med.generic_name || '';
    document.getElementById('editMedStrength').value = med.strength || '';
    document.getElementById('editMedDose').value = med.default_dose || '';
    document.getElementById('editMedDays').value = med.default_duration_days || '';
    document.getElementById('editMedRoute').value = med.default_route || '';
    document.getElementById('editMedFreq').value = med.default_frequency || '';
    document.getElementById('editMedNotes').value = med.notes || '';
    new bootstrap.Modal(document.getElementById('editCustomMedModal')).show();
}
</script>
@endsection
