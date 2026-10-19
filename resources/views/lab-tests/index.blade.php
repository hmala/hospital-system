@extends('layouts.app')

@section('content')
<div class="container-fluid pb-5">
    <!-- الترويسة وأزرار الإجراءات الرئيسية -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="fw-bold mb-0 text-dark">
                        <i class="fas fa-flask text-primary me-2"></i>
                        إدارة ودليل الفحوصات المختبرية
                    </h3>
                    <small class="text-muted">التحكم بتشغيل وإطفاء الفحوصات في محطات النظام والضمان الصحي وتحديث الأسعار</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('lab-tests.export', request()->query()) }}" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i> تصدير إكسل
                    </a>
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests'))
                        <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#importLabTestsModal">
                            <i class="fas fa-file-import me-1"></i> استيراد وتحديث إكسل
                        </button>
                    @endif
                    <a href="{{ route('lab-tests.pricing-settings.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-tags me-1"></i> إعدادات الأسعار والتصنيفات
                    </a>
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('admin') || Auth::user()->can('manage lab tests') || Auth::user()->can('edit lab tests'))
                        <a href="{{ route('lab-tests.entry-settings') }}" class="btn btn-outline-dark btn-sm">
                            <i class="fas fa-sliders-h me-1"></i> أنماط إدخال النتائج
                        </a>
                    @endif
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('create lab tests'))
                        <a href="{{ route('lab-tests.create') }}" class="btn btn-success btn-sm fw-bold">
                            <i class="fas fa-plus me-1"></i> إضافة فحص جديد
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقات الإحصائيات السريعة -->
    @if(isset($stats))
    <div class="row g-3 mb-3">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-primary">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block">إجمالي الفحوصات</span>
                            <h4 class="mb-0 fw-bold text-dark mt-1">{{ number_format($stats['total']) }}</h4>
                        </div>
                        <div class="p-2 rounded-circle bg-primary-subtle text-primary fs-5">
                            <i class="fas fa-vials"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-success">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block">النشطة عاماً (بالمحطات)</span>
                            <h4 class="mb-0 fw-bold text-success mt-1">{{ number_format($stats['active']) }}</h4>
                        </div>
                        <div class="p-2 rounded-circle bg-success-subtle text-success fs-5">
                            <i class="fas fa-check-double"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-danger">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block">المعطلة عاماً (محجوبة)</span>
                            <h4 class="mb-0 fw-bold text-danger mt-1">{{ number_format($stats['inactive']) }}</h4>
                        </div>
                        <div class="p-2 rounded-circle bg-danger-subtle text-danger fs-5">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100 border-start border-4 border-info">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-muted small fw-bold d-block">المشمولة بالضمان</span>
                            <h4 class="mb-0 fw-bold text-info mt-1">{{ number_format($stats['hi_active']) }}</h4>
                            <small class="text-muted" style="font-size: 0.72rem;">غير مشمولة: {{ number_format($stats['hi_inactive']) }}</small>
                        </div>
                        <div class="p-2 rounded-circle bg-info-subtle text-info fs-5">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط التحكم الكلي والذكي بنقرة واحدة -->
    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests'))
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-light border border-primary-subtle">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary p-2 rounded-circle fs-6">
                        <i class="fas fa-bolt"></i>
                    </span>
                    <div>
                        <h6 class="mb-0 fw-bold text-dark">التحكم الذكي الكلي بالفحوصات</h6>
                        <small class="text-muted">إيقاف أو تشغيل مجموعات الفحوصات وفق تغطية الضمان الصحي بضغطة زر واحدة</small>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkDisableModal">
                        <i class="fas fa-power-off me-1"></i> إطفاء كافة الفحوصات خارج الضمان ({{ number_format($stats['hi_unpriced']) }})
                    </button>
                    <form action="{{ route('lab-tests.bulk-disable-non-insurance') }}" method="POST" class="d-inline" onsubmit="return confirm('هل تريد تفعيل جميع الفحوصات المسعرة بالضمان ({{ $stats['hi_active'] }} فحص) عاماً وبالضمان في كافة المحطات؟')">
                        @csrf
                        <input type="hidden" name="action" value="enable_all_insured">
                        <button type="submit" class="btn btn-success btn-sm fw-bold px-3 shadow-sm">
                            <i class="fas fa-shield-check me-1"></i> تفعيل كافة المسعرة بالضمان ({{ number_format($stats['hi_active']) }})
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif
    @endif

    <!-- فلاتر البحث والفرز المتقدمة -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('lab-tests.index') }}" class="row g-2 align-items-end">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="main_category" class="form-label small fw-bold mb-1">التصنيف الرئيسي</label>
                            <select class="form-select form-select-sm" id="main_category" name="main_category">
                                <option value="">جميع الأقسام الرئيسية</option>
                                @foreach($mainCategories as $mainCat)
                                    <option value="{{ $mainCat }}" {{ request('main_category') == $mainCat ? 'selected' : '' }}>
                                        {{ $mainCat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="subcategory" class="form-label small fw-bold mb-1">التصنيف الفرعي</label>
                            <select class="form-select form-select-sm" id="subcategory" name="subcategory">
                                <option value="">جميع التصنيفات الفرعية</option>
                                @foreach($subcategories as $subcat)
                                    <option value="{{ $subcat }}" {{ request('subcategory') == $subcat ? 'selected' : '' }}>
                                        {{ $subcat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="status" class="form-label small fw-bold mb-1">الحالة بالمحطات</label>
                            <select class="form-select form-select-sm" id="status" name="status">
                                <option value="">جميع الحالات العامة</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>🟢 نشط فقط (يظهر بالمحطات)</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>🔴 معطل فقط (محجوب بالمحطات)</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="insurance_status" class="form-label small fw-bold mb-1">مظلة الضمان الصحي</label>
                            <select class="form-select form-select-sm" id="insurance_status" name="insurance_status">
                                <option value="">جميع التحاليل</option>
                                <option value="insured" {{ request('insurance_status') === 'insured' ? 'selected' : '' }}>🛡️ مشمول بالضمان فقط</option>
                                <option value="non_insured" {{ request('insurance_status') === 'non_insured' ? 'selected' : '' }}>⚠️ خارج الضمان (غير مغطى)</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label for="search" class="form-label small fw-bold mb-1">بحث بالاسم أو الكود</label>
                            <input type="text" class="form-control form-control-sm" id="search" name="search" 
                                   placeholder="اسم الفحص أو الكود..." value="{{ request('search') }}">
                        </div>
                        <div class="col-lg-1 col-md-2 col-sm-3">
                            <label for="per_page" class="form-label small fw-bold mb-1">العدد/صفحة</label>
                            <select class="form-select form-select-sm" id="per_page" name="per_page">
                                <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                                <option value="200" {{ request('per_page') == 200 ? 'selected' : '' }}>200</option>
                                <option value="500" {{ request('per_page') == 500 ? 'selected' : '' }}>500</option>
                            </select>
                        </div>
                        <div class="col-lg-1 col-md-2 col-sm-3 d-flex gap-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100" title="تطبيق الفلتر">
                                <i class="fas fa-search"></i>
                            </button>
                            <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary btn-sm" title="إعادة تعيين">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول قائمة الفحوصات والتحكم اللحظي -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-list-check text-primary me-2"></i>
                        جدول الفحوصات المختبرية ({{ number_format($labTests->total()) }} فحص)
                        @if(request('main_category') || request('subcategory') || request('status') || request('insurance_status') || request('search'))
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">مفلترة</span>
                        @endif
                    </h5>
                    <div class="text-muted small">
                        <i class="fas fa-info-circle me-1 text-primary"></i> التبديل عبر المفاتيح يحفظ فورياً بدون إعادة تحميل الصفحة
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($labTests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="labTestsTable">
                                <thead class="table-light text-nowrap">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" class="form-check-input cursor-pointer" id="selectAllCheckbox" title="تحديد الكل في هذه الصفحة">
                                        </th>
                                        <th style="width: 90px;">الكود</th>
                                        <th>اسم الفحص المختبري</th>
                                        <th>التصنيف والقسم</th>
                                        <th>سعر الكاش</th>
                                        <th>سعر الضمان</th>
                                        <th class="text-center" style="width: 150px;">
                                            الحالة العامة
                                            <i class="fas fa-question-circle text-muted" title="عند الإطفاء يختفي الفحص كلياً من كافة محطات الأطباء والاستعلامات والطوارئ"></i>
                                        </th>
                                        <th class="text-center" style="width: 150px;">
                                            الضمان الصحي
                                            <i class="fas fa-question-circle text-muted" title="عند الإطفاء يختفي الفحص عن مرضى الضمان الصحي فقط"></i>
                                        </th>
                                        <th class="text-center" style="width: 140px;">الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labTests as $labTest)
                                    @php
                                        $hasHiPrice = ($labTest->hi_price > 0);
                                    @endphp
                                    <tr id="row_lab_{{ $labTest->id }}" class="{{ !$labTest->is_active ? 'table-light opacity-75' : '' }}">
                                        <td class="text-center">
                                            <input type="checkbox" class="form-check-input lab-select-chk cursor-pointer" value="{{ $labTest->id }}">
                                        </td>
                                        <td>
                                            @if($labTest->code)
                                                <span class="badge bg-light text-dark border font-monospace">{{ $labTest->code }}</span>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark">{{ $labTest->name }}</div>
                                            @if($labTest->unit)
                                                <small class="text-muted">{{ $labTest->unit }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($labTest->main_category)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle mb-1 d-inline-block">{{ $labTest->main_category }}</span>
                                            @endif
                                            @if($labTest->subcategory)
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle d-inline-block">{{ $labTest->subcategory }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($labTest->price !== null && $labTest->price > 0)
                                                <strong class="text-success">{{ number_format($labTest->price, 0) }}</strong> <small class="text-muted">د.ع</small>
                                            @else
                                                <span class="text-muted small">0 د.ع</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($hasHiPrice)
                                                <strong class="text-info">{{ number_format($labTest->hi_price, 0) }}</strong> <small class="text-muted">د.ع</small>
                                            @else
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small">غير مسعر</span>
                                            @endif
                                        </td>
                                        
                                        <!-- مفتاح التبديل للحالة العامة -->
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="form-check form-switch p-0 m-0 d-inline-block">
                                                    <input class="form-check-input toggle-status-switch cursor-pointer" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           id="switch_active_{{ $labTest->id }}"
                                                           data-id="{{ $labTest->id }}"
                                                           data-field="is_active"
                                                           style="width: 2.5em; height: 1.3em;"
                                                           {{ $labTest->is_active ? 'checked' : '' }}
                                                           {{ (!Auth::user()->isAdmin() && !Auth::user()->hasRole('lab_staff') && !Auth::user()->can('edit lab tests')) ? 'disabled' : '' }}>
                                                </div>
                                                <span class="badge status-badge mt-1 {{ $labTest->is_active ? 'bg-success' : 'bg-danger' }}" id="badge_active_{{ $labTest->id }}" style="font-size: 0.68rem;">
                                                    {{ $labTest->is_active ? 'نشط بالمحطات' : 'معطل ومحجوب' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- مفتاح التبديل للضمان الصحي -->
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <div class="form-check form-switch p-0 m-0 d-inline-block">
                                                    <input class="form-check-input toggle-status-switch cursor-pointer" 
                                                           type="checkbox" 
                                                           role="switch"
                                                           id="switch_hi_{{ $labTest->id }}"
                                                           data-id="{{ $labTest->id }}"
                                                           data-field="is_hi_active"
                                                           style="width: 2.5em; height: 1.3em;"
                                                           {{ $labTest->is_hi_active ? 'checked' : '' }}
                                                           {{ (!Auth::user()->isAdmin() && !Auth::user()->hasRole('lab_staff') && !Auth::user()->can('edit lab tests')) ? 'disabled' : '' }}>
                                                </div>
                                                <span class="badge hi-badge mt-1 {{ $labTest->is_hi_active ? 'bg-info' : 'bg-secondary' }}" id="badge_hi_{{ $labTest->id }}" style="font-size: 0.68rem;">
                                                    {{ $labTest->is_hi_active ? 'مشمول بالضمان' : 'خارج الضمان' }}
                                                </span>
                                            </div>
                                        </td>

                                        <!-- الإجراءات -->
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="{{ route('lab-tests.show', $labTest) }}" class="btn btn-outline-primary" title="استعراض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if(Auth::user()->isAdmin() || Auth::user()->hasRole('lab_staff') || Auth::user()->can('edit lab tests'))
                                                    <a href="{{ route('lab-tests.sub-tests.index', $labTest) }}" class="btn btn-outline-success" title="الفحوصات الفرعية (Sub-Tests)">
                                                        <i class="fas fa-list-check"></i>
                                                    </a>
                                                    <a href="{{ route('lab-tests.references.index', $labTest) }}" class="btn btn-outline-info" title="القيم المرجعية">
                                                        <i class="fas fa-ruler-combined"></i>
                                                    </a>
                                                    <a href="{{ route('lab-tests.edit', $labTest) }}" class="btn btn-outline-warning" title="تعديل">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                @endif
                                                @if(Auth::user()->isAdmin() || Auth::user()->can('delete lab tests'))
                                                    <form action="{{ route('lab-tests.destroy', $labTest) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الفحص نهائياً؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- ترقيم الصفحات -->
                        <div class="d-flex justify-content-between align-items-center flex-wrap p-3 border-top">
                            <div class="small text-muted">
                                عرض من {{ $labTests->firstItem() }} إلى {{ $labTests->lastItem() }} من إجمالي {{ number_format($labTests->total()) }} فحص
                            </div>
                            <div>
                                {{ $labTests->links('pagination::bootstrap-5') }}
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد فحوصات مختبرية مطابقة</h5>
                            <p class="text-muted small">جرب تغيير معايير البحث أو الفلترة</p>
                            <a href="{{ route('lab-tests.index') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-undo me-1"></i> إعادة تعيين الفلاتر
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- شريط الإجراءات الجماعية العائم في الأسفل (يظهر عند تحديد عناصر) -->
<div id="bulkActionsFloatingBar" class="position-fixed shadow-lg p-3 rounded-4 bg-dark text-white border border-secondary" 
     style="bottom: 25px; left: 50%; transform: translateX(-50%); z-index: 1060; min-width: 320px; max-width: 90vw; display: none; transition: all 0.3s ease;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary px-3 py-2 rounded-pill fs-6" id="bulkSelectedBadge">0 فحص محدد</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-success btn-sm rounded-pill px-3 fw-bold btn-bulk-apply" data-field="is_active" data-status="active">
                <i class="fas fa-check-circle me-1"></i> تفعيل عام بالمحطات
            </button>
            <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold btn-bulk-apply" data-field="is_active" data-status="inactive">
                <i class="fas fa-ban me-1"></i> تعطيل عام وحجب
            </button>
            <button type="button" class="btn btn-info btn-sm rounded-pill px-3 fw-bold text-white btn-bulk-apply" data-field="is_hi_active" data-status="active">
                <i class="fas fa-shield-alt me-1"></i> تفعيل بالضمان
            </button>
            <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold text-dark btn-bulk-apply" data-field="is_hi_active" data-status="inactive">
                <i class="fas fa-shield-slash me-1"></i> إيقاف عن الضمان
            </button>
            <button type="button" class="btn btn-outline-light btn-sm rounded-pill px-2" id="btnCancelBulkSelection" title="إلغاء التحديد">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- مودال تأكيد إطفاء الفحوصات خارج الضمان كلياً -->
<div class="modal fade" id="bulkDisableModal" tabindex="-1" aria-labelledby="bulkDisableModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('lab-tests.bulk-disable-non-insurance') }}" method="POST">
            @csrf
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-danger text-white py-3 rounded-top-4">
                    <h5 class="modal-title fs-6 fw-bold" id="bulkDisableModalLabel">
                        <i class="fas fa-power-off me-2"></i> إطفاء وتعطيل الفحوصات غير المشمولة بالضمان
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-warning small rounded-3 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        يوجد في النظام <strong>{{ number_format($stats['hi_unpriced'] ?? 0) }}</strong> فحص مختبري بدون تسعير للضمان الصحي (خارج الضمان).
                    </div>
                    <p class="small text-dark mb-3">اختر نمط الإطفاء المطلوب تطبيقه فوراً على كافة هذه الفحوصات:</p>

                    <div class="form-check p-3 border rounded-3 mb-2 bg-light cursor-pointer">
                        <input class="form-check-input" type="radio" name="action" id="action_general" value="general_disable" checked>
                        <label class="form-check-label small fw-bold text-dark cursor-pointer ms-2" for="action_general">
                            <i class="fas fa-eye-slash text-danger me-1"></i> إطفاء عام من كافة المحطات (تنزيلها تماماً)
                            <div class="text-muted fw-normal small mt-1">تختفي هذه الفحوصات كلياً وفورياً من محطة الطبيب والاستعلامات والطوارئ حتى تعيد تفعيلها.</div>
                        </label>
                    </div>

                    <div class="form-check p-3 border rounded-3 bg-light cursor-pointer">
                        <input class="form-check-input" type="radio" name="action" id="action_hi" value="hi_disable">
                        <label class="form-check-label small fw-bold text-dark cursor-pointer ms-2" for="action_hi">
                            <i class="fas fa-shield-slash text-warning me-1"></i> إيقاف عن الضمان الصحي فقط
                            <div class="text-muted fw-normal small mt-1">تختفي فقط أمام مرضى الضمان الصحي، مع إمكانية بقائها متاحة لمرضى الدفع النقدي (الكاش).</div>
                        </label>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger btn-sm fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fas fa-check me-1"></i> تأكيد وتنفيذ الإطفاء
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- نافذة استيراد وتحديث الأسعار من ملف إكسل -->
<div class="modal fade" id="importLabTestsModal" tabindex="-1" aria-labelledby="importLabTestsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('lab-tests.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-info text-white py-3 rounded-top-4">
                    <h5 class="modal-title fs-6 fw-bold" id="importLabTestsModalLabel">
                        <i class="fas fa-file-excel me-2"></i> استيراد وتحديث أسعار التحاليل من ملف إكسل
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border border-info-subtle small mb-3 rounded-3">
                        <div class="fw-bold mb-1 text-primary"><i class="fas fa-info-circle me-1"></i> آلية المطابقة والتحديث الذكي:</div>
                        <ul class="mb-0 ps-3 text-muted">
                            <li>يمكنك تصدير الجدول الحالي أولاً عبر زر <strong>تصدير إكسل</strong> وتعديل الأسعار فيه ثم إعادة رفعه هنا.</li>
                            <li>يقوم النظام بمطابقة التحاليل تلقائياً برقم <strong>المعرف (# ID)</strong> أو <strong>الرمز الطبي (Code)</strong> أو الاسم.</li>
                            <li>يتم تحديث: سعر الكاش، سعر الضمان، حالة شمول الضمان، وحالة التفعيل (نشط/معطل).</li>
                            <li>الصيغ المدعومة: <code>.xlsx</code>, <code>.xls</code>, <code>.csv</code> (الحد الأقصى: 10 ميغابايت).</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="excel_file_lab_idx" class="form-label small fw-bold text-dark">
                            <i class="fas fa-cloud-arrow-up me-1 text-primary"></i> اختر ملف الإكسل المحدث:
                        </label>
                        <input type="file" name="excel_file" id="excel_file_lab_idx" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info text-white btn-sm fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fas fa-upload me-1"></i> بدء الاستيراد والتحديث
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- توست الإشعارات الفوري السلس -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1090;">
    <div id="liveFeedbackToast" class="toast align-items-center text-white bg-dark border-0 shadow-lg rounded-3" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2" id="toastMessage">
                <i class="fas fa-check-circle text-success fs-5"></i>
                <span>تم التحديث بنجاح</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const toastEl = document.getElementById('liveFeedbackToast');
    const toastMessage = document.getElementById('toastMessage');
    const bsToast = toastEl ? new bootstrap.Toast(toastEl, { delay: 3500 }) : null;

    function showToast(msg, isSuccess = true) {
        if (!bsToast) return;
        const iconClass = isSuccess ? 'fa-check-circle text-success' : 'fa-exclamation-triangle text-danger';
        toastMessage.innerHTML = `<i class="fas ${iconClass} fs-5"></i> <span>${msg}</span>`;
        bsToast.show();
    }

    // ====== 1. التبديل الفوري للمفتاح الفردي (Single Quick Toggle AJAX) ======
    const toggleSwitches = document.querySelectorAll('.toggle-status-switch');
    toggleSwitches.forEach(sw => {
        sw.addEventListener('change', function () {
            const testId = this.dataset.id;
            const field = this.dataset.field;
            const isChecked = this.checked;
            const row = document.getElementById(`row_lab_${testId}`);
            const badge = document.getElementById(field === 'is_active' ? `badge_active_${testId}` : `badge_hi_${testId}`);

            // تعطيل مؤقت لمنع النقرات المتكررة
            this.disabled = true;

            const url = `{{ url('/lab-tests') }}/${testId}/quick-toggle`;

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    field: field,
                    value: isChecked ? 1 : 0
                })
            })
            .then(res => res.json())
            .then(data => {
                this.disabled = false;
                if (data.success) {
                    showToast(data.message, true);

                    // تحديث الشارة البصرية
                    if (field === 'is_active') {
                        if (badge) {
                            badge.className = `badge status-badge mt-1 ${data.is_active ? 'bg-success' : 'bg-danger'}`;
                            badge.textContent = data.is_active ? 'نشط بالمحطات' : 'معطل ومحجوب';
                        }
                        if (row) {
                            if (data.is_active) {
                                row.classList.remove('table-light', 'opacity-75');
                            } else {
                                row.classList.add('table-light', 'opacity-75');
                            }
                        }
                    } else if (field === 'is_hi_active') {
                        if (badge) {
                            badge.className = `badge hi-badge mt-1 ${data.is_hi_active ? 'bg-info' : 'bg-secondary'}`;
                            badge.textContent = data.is_hi_active ? 'مشمول بالضمان' : 'خارج الضمان';
                        }
                    }
                } else {
                    // تراجع
                    this.checked = !isChecked;
                    showToast(data.message || 'حدث خطأ أثناء التحديث', false);
                }
            })
            .catch(err => {
                this.disabled = false;
                this.checked = !isChecked;
                showToast('تعذر الاتصال بالخادم', false);
            });
        });
    });

    // ====== 2. التحديد الجماعي والشريط العائم (Bulk Selection & Floating Bar) ======
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const rowCheckboxes = document.querySelectorAll('.lab-select-chk');
    const floatingBar = document.getElementById('bulkActionsFloatingBar');
    const selectedBadge = document.getElementById('bulkSelectedBadge');
    const cancelBulkBtn = document.getElementById('btnCancelBulkSelection');

    function updateFloatingBar() {
        const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
        const count = checkedBoxes.length;

        if (count > 0) {
            floatingBar.style.display = 'block';
            selectedBadge.textContent = `${count} فحص محدد`;
        } else {
            floatingBar.style.display = 'none';
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.checked = (count > 0 && count === rowCheckboxes.length);
            selectAllCheckbox.indeterminate = (count > 0 && count < rowCheckboxes.length);
        }
    }

    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function () {
            const isChecked = this.checked;
            rowCheckboxes.forEach(cb => {
                cb.checked = isChecked;
            });
            updateFloatingBar();
        });
    }

    rowCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateFloatingBar);
    });

    if (cancelBulkBtn) {
        cancelBulkBtn.addEventListener('click', function () {
            rowCheckboxes.forEach(cb => cb.checked = false);
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
            updateFloatingBar();
        });
    }

    // ====== 3. تنفيذ الإجراء الجماعي (Bulk Actions Execution) ======
    const bulkApplyBtns = document.querySelectorAll('.btn-bulk-apply');
    bulkApplyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const field = this.dataset.field;
            const status = this.dataset.status;
            const checkedBoxes = Array.from(rowCheckboxes).filter(cb => cb.checked);
            const ids = checkedBoxes.map(cb => cb.value);

            if (ids.length === 0) {
                showToast('يرجى اختيار فحص واحد على الأقل', false);
                return;
            }

            const actionLabel = this.textContent.trim();
            if (!confirm(`هل أنت متأكد من (${actionLabel}) لـ ${ids.length} فحص محدد؟`)) {
                return;
            }

            // تعطيل الأزرار أثناء المعالجة
            bulkApplyBtns.forEach(b => b.disabled = true);

            fetch(`{{ route('lab-tests.bulk-toggle-status') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ids: ids,
                    field: field,
                    status: status
                })
            })
            .then(res => res.json())
            .then(data => {
                bulkApplyBtns.forEach(b => b.disabled = false);
                if (data.success) {
                    showToast(data.message, true);

                    // تحديث واجهات الصفوف المحددة فورياً
                    const isActive = (status === 'active');
                    ids.forEach(id => {
                        if (field === 'is_active') {
                            const sw = document.getElementById(`switch_active_${id}`);
                            const badge = document.getElementById(`badge_active_${id}`);
                            const row = document.getElementById(`row_lab_${id}`);
                            if (sw) sw.checked = isActive;
                            if (badge) {
                                badge.className = `badge status-badge mt-1 ${isActive ? 'bg-success' : 'bg-danger'}`;
                                badge.textContent = isActive ? 'نشط بالمحطات' : 'معطل ومحجوب';
                            }
                            if (row) {
                                if (isActive) row.classList.remove('table-light', 'opacity-75');
                                else row.classList.add('table-light', 'opacity-75');
                            }
                        } else if (field === 'is_hi_active') {
                            const sw = document.getElementById(`switch_hi_${id}`);
                            const badge = document.getElementById(`badge_hi_${id}`);
                            if (sw) sw.checked = isActive;
                            if (badge) {
                                badge.className = `badge hi-badge mt-1 ${isActive ? 'bg-info' : 'bg-secondary'}`;
                                badge.textContent = isActive ? 'مشمول بالضمان' : 'خارج الضمان';
                            }
                        }
                    });

                    // إلغاء التحديد وإخفاء الشريط
                    rowCheckboxes.forEach(cb => cb.checked = false);
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    updateFloatingBar();
                } else {
                    showToast(data.message || 'فشلت العملية', false);
                }
            })
            .catch(err => {
                bulkApplyBtns.forEach(b => b.disabled = false);
                showToast('تعذر الاتصال بالخادم', false);
            });
        });
    });
});
</script>
@endpush
@endsection