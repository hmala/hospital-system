@extends('layouts.app')

@section('title', 'قائمة وتصنيفات حليب وتغذية الخدج - إعدادات الخدج')

@section('styles')
<style>
    .milk-card {
        transition: all 0.25s ease-in-out;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
    }
    .milk-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.06) !important;
        border-color: #cbd5e1;
    }
    .form-switch .form-check-input {
        width: 2.8em;
        height: 1.4em;
        cursor: pointer;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">

    <!-- شريط تبويبات الإعدادات المشترك -->
    @include('nicu.partials.settings-nav', ['activeTab' => 'milk-types'])

    <!-- رأس الصفحة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h2 class="mb-1 fw-bold text-dark">
                        <i class="fas fa-baby text-warning me-2"></i>
                        قائمة وتصنيفات حليب وتغذية الخدج
                    </h2>
                    <p class="text-muted mb-0">الدليل السريري المعتمد لخيارات الرضاعة والتركيبات الغذائية لأطفال الحاضنات والخدج</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-warning shadow-sm fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#createMilkTypeModal">
                        <i class="fas fa-plus-circle me-1"></i> إضافة نوع حليب جديد
                    </button>
                    <a href="{{ route('nicu.index') }}" class="btn btn-outline-secondary shadow-sm">
                        <i class="fas fa-arrow-right me-1"></i> العودة للوحة الحاضنات
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- التنبيهات -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            {{ session('error') }}
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

    <!-- كروت الإحصائيات السريعة -->
    @php
        $totalMilks = $milkTypes->count();
        $activeMilks = $milkTypes->where('is_active', true)->count();
        $inactiveMilks = $totalMilks - $activeMilks;
        $uniqueCategoriesCount = $milkTypes->whereNotNull('category')->where('category', '!=', '')->pluck('category')->unique()->count();
    @endphp
    <div class="row mb-4 g-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-warning bg-opacity-10 text-dark h-100" style="border-radius: 12px; border-inline-start: 4px solid #f59e0b !important;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block mb-1">إجمالي أنواع الحليب</span>
                        <h3 class="mb-0 fw-bold text-dark">{{ $totalMilks }}</h3>
                    </div>
                    <div class="p-3 bg-warning text-dark rounded-circle">
                        <i class="fas fa-baby-carriage fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-success bg-opacity-10 text-dark h-100" style="border-radius: 12px; border-inline-start: 4px solid #10b981 !important;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block mb-1">المتاح للاختيار (نشط)</span>
                        <h3 class="mb-0 fw-bold text-success">{{ $activeMilks }}</h3>
                    </div>
                    <div class="p-3 bg-success text-white rounded-circle">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-secondary bg-opacity-10 text-dark h-100" style="border-radius: 12px; border-inline-start: 4px solid #64748b !important;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block mb-1">المعطل مؤقتاً</span>
                        <h3 class="mb-0 fw-bold text-secondary">{{ $inactiveMilks }}</h3>
                    </div>
                    <div class="p-3 bg-secondary text-white rounded-circle">
                        <i class="fas fa-ban fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm bg-primary bg-opacity-10 text-dark h-100" style="border-radius: 12px; border-inline-start: 4px solid #3b82f6 !important;">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="small text-muted d-block mb-1">التصنيفات السريرية</span>
                        <h3 class="mb-0 fw-bold text-primary">{{ $uniqueCategoriesCount }}</h3>
                    </div>
                    <div class="p-3 bg-primary text-white rounded-circle">
                        <i class="fas fa-layer-group fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلترة -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
        <div class="card-body p-3">
            <form action="{{ route('nicu.milk-types.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="بحث باسم الحليب، التصنيف، أو الملاحظات..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">جميع التصنيفات السريرية</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>متاح فقط</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطل فقط</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->anyFilled(['search', 'category', 'status']))
                        <a href="{{ route('nicu.milk-types.index') }}" class="btn btn-outline-secondary" title="إعادة تعيين الفلترة">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- بطاقة جدول أنواع وتصنيفات حليب وتغذية الخدج -->
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1 fw-bold text-dark">
                    <i class="fas fa-list-ul text-warning me-2"></i>
                    دليل أنواع وتصنيفات حليب وتغذية الخدج المعتمدة
                </h5>
                <p class="text-muted small mb-0">تظهر هذه الخيارات تلقائياً للأطباء والممرضين في جداول الرضاعة وإضبارة الطفل السريرية</p>
            </div>
            <span class="badge bg-light text-dark border px-3 py-2 fw-bold">
                عدد السجلات: {{ $milkTypes->count() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 50px;">#</th>
                            <th>اسم الحليب / نوع التغذية</th>
                            <th>التصنيف السريري</th>
                            <th class="text-center" style="width: 100px;">الترتيب</th>
                            <th>ملاحظات الاستخدام والحالات المناسبة</th>
                            <th class="text-center" style="width: 140px;">حالة التفعيل</th>
                            <th class="text-center" style="width: 130px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($milkTypes ?? [] as $mIndex => $milkType)
                            <tr id="milk-type-row-{{ $milkType->id }}" class="{{ !$milkType->is_active ? 'opacity-75 bg-light' : '' }}">
                                <td class="text-center fw-bold text-muted">{{ $mIndex + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="p-2 bg-warning-subtle text-warning rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="fas fa-baby-carriage"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold fs-6 text-dark">{{ $milkType->name }}</div>
                                            <small class="text-muted">مُعرّف: #{{ $milkType->id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($milkType->category)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                            {{ $milkType->category }}
                                        </span>
                                    @else
                                        <span class="text-muted small">غير محدد</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold text-secondary">
                                    {{ $milkType->sort_order }}
                                </td>
                                <td>
                                    <small class="text-muted d-block" title="{{ $milkType->notes }}">
                                        {{ $milkType->notes ?: '—' }}
                                    </small>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input milk-type-toggle-switch" 
                                               type="checkbox" 
                                               role="switch" 
                                               data-milk-type-id="{{ $milkType->id }}"
                                               data-toggle-url="{{ route('nicu.milk-types.toggle', $milkType) }}"
                                               {{ $milkType->is_active ? 'checked' : '' }}>
                                    </div>
                                    <div class="small fw-bold status-label-milk-{{ $milkType->id }} {{ $milkType->is_active ? 'text-success' : 'text-danger' }}">
                                        {{ $milkType->is_active ? 'متاح للاختيار' : 'معطل مؤقتاً' }}
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" 
                                                class="btn btn-outline-primary edit-milk-type-btn"
                                                data-milk-type="{{ json_encode($milkType) }}"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editMilkTypeModal"
                                                title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('nicu.milk-types.destroy', $milkType) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا النوع من قائمة الحليب؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-baby fa-3x mb-3 text-secondary opacity-50"></i>
                                    <h5>لا توجد أنواع حليب مسجلة حتى الآن</h5>
                                    <p class="small">انقر على زر "إضافة نوع حليب جديد" لإضافة أول نوع لقائمة الخدج</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- مودال إضافة نوع حليب جديد -->
<div class="modal fade" id="createMilkTypeModal" tabindex="-1" aria-labelledby="createMilkTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="{{ route('nicu.milk-types.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-warning text-dark" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="createMilkTypeModalLabel">
                        <i class="fas fa-baby me-2"></i> إضافة نوع حليب / تغذية خديج جديد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم الحليب أو التركيبة السريرية <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-lg" placeholder="مثال: حليب سيميلاك نيوشور (Neosure)..." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">التصنيف السريري للتغذية</label>
                        <input type="text" name="category" class="form-control" list="milkCategoriesList" placeholder="مثال: حليب خدج صناعي Pre-term...">
                        <datalist id="milkCategoriesList">
                            <option value="حليب أم طبيعي (EBM)">
                            <option value="حليب خدج صناعي (Pre-term Formula)">
                            <option value="حليب قليل التحسس (Hypoallergenic)">
                            <option value="حليب مكثف ومضاد للارتجاع (Anti-Reflux)">
                            <option value="تغذية وريدية كاملة (TPN)">
                            <option value="حليب خالي من اللاكتوز (LF)">
                            <option value="مكمل تغذية خدج (HMF)">
                        </datalist>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ترتيب العرض في القوائم</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0">
                        <small class="text-muted">الرقم الأصغر يظهر أولاً في القوائم المنسدلة.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات الاستخدام السريري ودواعي التغذية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="ملاحظات حول طريقة التحضير، أو الأوزان الموصى بها..."></textarea>
                    </div>

                    <div class="p-3 bg-light rounded-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="create_milk_is_active" value="1" checked>
                            <label class="form-check-label fw-bold text-success" for="create_milk_is_active">تفعيل النوع وجعله متاحاً للاختيار فوراً</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning px-4 fw-bold text-dark">
                        <i class="fas fa-check-circle me-1"></i> حفظ وإضافة النوع
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال تعديل نوع حليب -->
<div class="modal fade" id="editMilkTypeModal" tabindex="-1" aria-labelledby="editMilkTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form id="editMilkTypeForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="editMilkTypeModalLabel">
                        <i class="fas fa-edit me-2"></i> تعديل بيانات نوع الحليب / التغذية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم الحليب أو التركيبة السريرية <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_milk_name" class="form-control form-control-lg" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">التصنيف السريري للتغذية</label>
                        <input type="text" name="category" id="edit_milk_category" class="form-control" list="milkCategoriesList">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ترتيب العرض</label>
                        <input type="number" name="sort_order" id="edit_milk_sort_order" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات الاستخدام السريري ودواعي التغذية</label>
                        <textarea name="notes" id="edit_milk_notes" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="p-3 bg-light rounded-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="edit_milk_is_active" value="1">
                            <label class="form-check-label fw-bold" for="edit_milk_is_active">النوع مفعل ومتاح للاختيار</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-1"></i> حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // تجهيز مودال تعديل نوع الحليب بالبيانات
    const editMilkForm = document.getElementById('editMilkTypeForm');
    const editMilkName = document.getElementById('edit_milk_name');
    const editMilkCategory = document.getElementById('edit_milk_category');
    const editMilkSortOrder = document.getElementById('edit_milk_sort_order');
    const editMilkNotes = document.getElementById('edit_milk_notes');
    const editMilkActive = document.getElementById('edit_milk_is_active');

    document.querySelectorAll('.edit-milk-type-btn').forEach(button => {
        button.addEventListener('click', function () {
            const milkType = JSON.parse(this.getAttribute('data-milk-type'));
            if (editMilkForm) {
                editMilkForm.action = `{{ url('nicu/milk-types') }}/${milkType.id}`;
            }
            if (editMilkName) editMilkName.value = milkType.name || '';
            if (editMilkCategory) editMilkCategory.value = milkType.category || '';
            if (editMilkSortOrder) editMilkSortOrder.value = milkType.sort_order ?? 0;
            if (editMilkNotes) editMilkNotes.value = milkType.notes || '';
            if (editMilkActive) editMilkActive.checked = Boolean(milkType.is_active);
        });
    });

    // تبديل حالة تفعيل نوع الحليب لحظياً عبر AJAX
    document.querySelectorAll('.milk-type-toggle-switch').forEach(toggle => {
        toggle.addEventListener('change', function () {
            const milkTypeId = this.getAttribute('data-milk-type-id');
            const row = document.getElementById(`milk-type-row-${milkTypeId}`);
            const label = document.querySelector(`.status-label-milk-${milkTypeId}`);
            const toggleUrl = this.getAttribute('data-toggle-url') || `{{ url('nicu/milk-types') }}/${milkTypeId}/toggle`;

            fetch(toggleUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (data.is_active) {
                        if (row) row.classList.remove('opacity-75', 'bg-light');
                        if (label) {
                            label.className = `small fw-bold status-label-milk-${milkTypeId} text-success`;
                            label.textContent = 'متاح للاختيار';
                        }
                    } else {
                        if (row) row.classList.add('opacity-75', 'bg-light');
                        if (label) {
                            label.className = `small fw-bold status-label-milk-${milkTypeId} text-danger`;
                            label.textContent = 'معطل مؤقتاً';
                        }
                    }
                } else {
                    alert(data.message || 'حدث خطأ أثناء تحديث الحالة');
                    toggle.checked = !toggle.checked;
                }
            })
            .catch(err => {
                console.error(err);
                alert('فشل الاتصال بالخادم');
                toggle.checked = !toggle.checked;
            });
        });
    });
});
</script>
@endsection
@endsection
