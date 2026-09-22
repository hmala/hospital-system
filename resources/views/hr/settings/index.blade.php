@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- الترويسة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-sliders-h me-2"></i>إعدادات حقول الموارد البشرية والقوائم المنسدلة
            </h2>
            <p class="text-muted small mb-0">واجهة موحدة للتحكم بكل حقول التسجيل، تحديد نوع كل حقل (نص/قائمة/تاريخ)، وإدارة خيارات القوائم المنسدلة والإلزامية</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.employees.create') }}" class="btn btn-primary btn-sm shadow-sm">
                <i class="fas fa-user-plus me-1"></i> فتح نموذج التسجيل
            </a>
            <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    <!-- رسائل التنبيه والنجاح -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <ul class="mb-0 small ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات العلوية -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-th-list fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">إجمالي حقول النموذج</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ $fields->count() }} حقل</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger me-3">
                        <i class="fas fa-asterisk fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">الحقول الإجبارية (مطلوبة *)</div>
                        <h4 class="fw-bold mb-0 text-danger">{{ $fields->where('is_required', true)->count() }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-list-ul fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">القوائم المنسدلة (Dropdowns)</div>
                        <h4 class="fw-bold mb-0 text-info">{{ $fields->where('field_type', 'select')->count() }} قوائم</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-hospital fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">الأقسام والعيادات المعتمدة</div>
                        <h4 class="fw-bold mb-0 text-success">{{ $departmentsCount }} قسم</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- الجدول الموحد الشامل لكافة الحقول والقوائم المنسدلة -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div>
                <h6 class="fw-bold mb-0 text-dark">
                    <i class="fas fa-table text-primary me-2"></i>جدول التحكم الشامل بحقول الموارد البشرية
                </h6>
                <small class="text-muted">اضغط زر <strong>[تعديل]</strong> بجانب أي حقل لتغيير نوعه، اسمه، أو إدارة الخيارات التابعة له في القائمة المنسدلة.</small>
            </div>
        </div>

        <div class="card-body p-0">
            <form action="{{ route('hr.settings.update-fields') }}" method="POST" id="bulkFieldsForm">
                @csrf

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th class="ps-3" style="width: 50px;">#</th>
                                <th>اسم الحقل المعروض</th>
                                <th>رمز الحقل (Key)</th>
                                <th>نوع الحقل (Field Type)</th>
                                <th class="text-center" style="width: 170px;">حالة الإلزامية</th>
                                <th>الخيارات المرتبطة (للقوائم المنسدلة)</th>
                                <th class="text-center pe-3" style="width: 140px;">الإجراءات والتحكم</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fields as $index => $field)
                                @php
                                    $category = $field->lookup_category ?? $field->field_key;
                                    $optionsCount = 0;
                                    if ($field->field_key === 'department_id') {
                                        $optionsCount = $departmentsCount;
                                    } elseif ($field->field_type === 'select' || $field->field_key === 'documents') {
                                        $optionsCount = isset($allLookupOptions[$category]) ? $allLookupOptions[$category]->count() : 0;
                                    }
                                @endphp
                                <tr>
                                    <td class="ps-3 text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $field->field_name_ar }}</div>
                                        <small class="text-muted">
                                            @if($field->group_name === 'personal')
                                                <i class="fas fa-user text-secondary me-1" style="font-size: 10px;"></i>بيانات شخصية
                                            @elseif($field->group_name === 'job')
                                                <i class="fas fa-briefcase text-secondary me-1" style="font-size: 10px;"></i>بيانات وظيفية
                                            @elseif($field->group_name === 'medical')
                                                <i class="fas fa-stethoscope text-info me-1" style="font-size: 10px;"></i>تراخيص طبية
                                            @else
                                                <i class="fas fa-folder text-warning me-1" style="font-size: 10px;"></i>مستمسكات ووثائق
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-secondary border font-monospace">{{ $field->field_key }}</span>
                                    </td>
                                    <td>
                                        @if($field->field_type === 'select')
                                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1">
                                                <i class="fas fa-caret-square-down me-1"></i>قائمة منسدلة (Dropdown)
                                            </span>
                                        @elseif($field->field_type === 'date')
                                            <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1">
                                                <i class="fas fa-calendar-alt me-1"></i>تاريخ (Date)
                                            </span>
                                        @elseif($field->field_type === 'number')
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fas fa-hashtag me-1"></i>رقم مالي (Number)
                                            </span>
                                        @elseif($field->field_type === 'file')
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                                <i class="fas fa-file-upload me-1"></i>ملف مرفق (File / PDF)
                                            </span>
                                        @elseif($field->field_type === 'textarea')
                                            <span class="badge bg-purple bg-opacity-10 text-purple border border-purple border-opacity-25 px-2 py-1">
                                                <i class="fas fa-align-left me-1"></i>نص طويل (Textarea)
                                            </span>
                                        @else
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1">
                                                <i class="fas fa-font me-1"></i>نص عادي (Text)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($field->is_locked)
                                            <span class="badge bg-secondary opacity-75" title="حقل أساسي في بنية النظام">
                                                <i class="fas fa-lock me-1"></i>إجباري دائماً
                                            </span>
                                            <input type="hidden" name="required_fields[]" value="{{ $field->field_key }}">
                                        @else
                                            <div class="form-check form-switch d-inline-block">
                                                <input class="form-check-input" type="checkbox" name="required_fields[]" value="{{ $field->field_key }}" id="switch_{{ $field->id }}" {{ $field->is_required ? 'checked' : '' }}>
                                                <label class="form-check-label small fw-bold {{ $field->is_required ? 'text-danger' : 'text-muted' }}" for="switch_{{ $field->id }}">
                                                    {{ $field->is_required ? 'مطلوب *' : 'اختياري' }}
                                                </label>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($field->field_type === 'select' || $field->field_key === 'documents')
                                            @if($field->field_key === 'department_id')
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                                    <i class="fas fa-hospital me-1"></i>أقسام المستشفى ({{ $departmentsCount }})
                                                </span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                                    <i class="fas fa-list-check me-1"></i>{{ $field->field_name_ar }} ({{ $optionsCount }} خيارات)
                                                </span>
                                            @endif
                                        @else
                                            <span class="text-muted small">— لا توجد قائمة</span>
                                        @endif
                                    </td>
                                    <td class="text-center pe-3">
                                        <button type="button" class="btn btn-outline-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#editFieldModal_{{ $field->id }}">
                                            <i class="fas fa-edit me-1"></i> تعديل
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- شريط الحفظ السريع للحقول الإجبارية -->
                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <div class="small text-muted">
                        <i class="fas fa-info-circle me-1 text-primary"></i> يمكنك تغيير أي حقل بين (مطلوب * / اختياري) مباشرة من الجدول والضغط على زر الحفظ.
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> حفظ تعديلات الحقول الإجبارية
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- النوافذ المنبثقة (Modals) لتعديل كل حقل وإدارة خياراته -->
@foreach($fields as $field)
    @php
        $category = $field->lookup_category ?? $field->field_key;
        $currentOptions = isset($allLookupOptions[$category]) ? $allLookupOptions[$category] : collect();
    @endphp
    <div class="modal fade" id="editFieldModal_{{ $field->id }}" tabindex="-1" aria-labelledby="editFieldModalLabel_{{ $field->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title fw-bold" id="editFieldModalLabel_{{ $field->id }}">
                        <i class="fas fa-sliders-h me-1"></i> تعديل الحقل: {{ $field->field_name_ar }} ({{ $field->field_key }})
                    </h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <!-- 1. نموذج تعديل إعدادات الحقل نفسه -->
                    <form action="{{ route('hr.settings.update-field', $field->id) }}" method="POST" class="mb-4 pb-4 border-bottom">
                        @csrf
                        @method('PUT')

                        <h6 class="fw-bold text-dark mb-3"><i class="fas fa-cog text-primary me-1"></i> الإعدادات العامة للحقل</h6>
                        
                        <div class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small fw-bold">اسم الحقل المعروض بالعربية <span class="text-danger">*</span></label>
                                <input type="text" name="field_name_ar" class="form-control" value="{{ $field->field_name_ar }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold">نوع الحقل (Field Type) <span class="text-danger">*</span></label>
                                <select name="field_type" class="form-select" {{ $field->is_locked ? 'disabled' : '' }}>
                                    <option value="text" {{ $field->field_type === 'text' ? 'selected' : '' }}>نص عادي (Text)</option>
                                    <option value="select" {{ $field->field_type === 'select' ? 'selected' : '' }}>قائمة منسدلة (Select Dropdown)</option>
                                    <option value="date" {{ $field->field_type === 'date' ? 'selected' : '' }}>تاريخ (Date)</option>
                                    <option value="number" {{ $field->field_type === 'number' ? 'selected' : '' }}>رقم مالي (Number)</option>
                                    <option value="file" {{ $field->field_type === 'file' ? 'selected' : '' }}>ملف مرفق (File / PDF)</option>
                                    <option value="textarea" {{ $field->field_type === 'textarea' ? 'selected' : '' }}>نص طويل (Textarea)</option>
                                </select>
                                @if($field->is_locked)
                                    <input type="hidden" name="field_type" value="{{ $field->field_type }}">
                                @endif
                            </div>

                            <div class="col-md-3">
                                @if(!$field->is_locked)
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_required" value="1" id="modal_req_{{ $field->id }}" {{ $field->is_required ? 'checked' : '' }}>
                                        <label class="form-check-label small fw-bold text-danger" for="modal_req_{{ $field->id }}">
                                            حقل إجباري مطلوب *
                                        </label>
                                    </div>
                                @else
                                    <span class="badge bg-secondary small mb-2 d-block py-2">إجباري دائماً (مقفل)</span>
                                @endif
                                <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                                    <i class="fas fa-save me-1"></i> حفظ إعدادات الحقل
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- 2. إدارة خيارات القائمة المنسدلة (تظهر إذا كان الحقل Dropdown أو documents) -->
                    @if($field->field_type === 'select' || $field->field_key === 'documents')
                        @if($field->field_key === 'department_id')
                            <!-- إدارة الأقسام -->
                            <div class="p-3 bg-light rounded-3">
                                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-hospital text-success me-1"></i> أقسام وعيادات المستشفى المعتمدة ({{ $departmentsCount }})</h6>
                                <p class="text-muted small mb-3">هذا الحقل مرتبط بجدول أقسام وعيادات المستشفى، ويمكنك إدارة الأقسام والعيادات وتحديثها مباشرة من شاشة إدارة الأقسام.</p>
                                <a href="{{ route('departments.admin') }}" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-external-link-alt me-1"></i> الانتقال لإدارة الأقسام والعيادات
                                </a>
                            </div>
                        @else
                            <div class="bg-light p-3 rounded-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0">
                                        <i class="fas fa-list-ul text-primary me-1"></i> إدارة خيارات القائمة المنسدلة: {{ $field->field_name_ar }} ({{ $currentOptions->count() }} خيار)
                                    </h6>
                                </div>

                                <!-- نموذج إضافة خيار جديد إلى هذه القائمة -->
                                <form action="{{ route('hr.settings.store-option') }}" method="POST" class="row g-2 mb-3 align-items-end p-2 bg-white rounded border">
                                    @csrf
                                    <input type="hidden" name="category" value="{{ $category }}">

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold mb-1">اسم الخيار الجديد بالعربية <span class="text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control form-control-sm" placeholder="مثال: خيار جديد..." required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted mb-1">كود اختياري</label>
                                        <input type="text" name="code" class="form-control form-control-sm" placeholder="code">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold">
                                            <i class="fas fa-plus me-1"></i> إضافة للقائمة
                                        </button>
                                    </div>
                                </form>

                                <!-- جدول الخيارات الحالية -->
                                <div class="table-responsive bg-white rounded border">
                                    <table class="table table-sm table-hover align-middle mb-0">
                                        <thead class="table-light small">
                                            <tr>
                                                <th class="ps-2">#</th>
                                                <th>اسم الخيار</th>
                                                <th>الكود</th>
                                                <th>الحالة</th>
                                                <th class="text-center pe-2" style="width: 110px;">التحكم</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($currentOptions as $optIndex => $opt)
                                                <tr>
                                                    <td class="ps-2 text-muted small">{{ $optIndex + 1 }}</td>
                                                    <td class="fw-semibold text-dark">{{ $opt->name }}</td>
                                                    <td><span class="badge bg-light text-secondary border font-monospace">{{ $opt->code ?? '—' }}</span></td>
                                                    <td>
                                                        @if($opt->is_active)
                                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 10px;">مفعل</span>
                                                        @else
                                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border" style="font-size: 10px;">معطل</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center pe-2">
                                                        <div class="btn-group btn-group-sm">
                                                            <!-- تفعيل/تعطيل -->
                                                            <form action="{{ route('hr.settings.toggle-option', $opt->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <button type="submit" class="btn {{ $opt->is_active ? 'btn-outline-warning' : 'btn-outline-success' }} btn-sm py-0 px-2" title="{{ $opt->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                                    <i class="fas {{ $opt->is_active ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                                                </button>
                                                            </form>
                                                            <!-- حذف -->
                                                            <form action="{{ route('hr.settings.destroy-option', $opt->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل تريد حذف هذا الخيار من القائمة المنسدلة؟')">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-2" title="حذف">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-3 text-muted small">لا توجد خيارات مسجلة حالياً لهذه القائمة المنسدلة.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endpush
