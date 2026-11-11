@extends('layouts.app')

@section('title', 'التحكم الداينمك بأسعار وشرائح العناية المركزة (CCU Tariffs)')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    @php
        $activeTab = session('active_tab', request('tab', 'packages-pane'));
    @endphp

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h3 class="mb-1 fw-bold text-dark">
                <i class="fas fa-sliders-h text-primary me-2"></i>
                التحكم الداينمك لأسعار وشرائح العناية المركزة للكبار (CCU / ICU)
            </h3>
            <p class="text-muted small mb-0">
                إدارة باقات الرقود الرسمية (فنتليتر / أكسجين / CCU / غرف منفردة)، تسعيرة الكاش والخدمات المنفصلة، شرائح الضمان التنازلية (1-30 يوم)، ونسب التغطية.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('icu.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-heartbeat me-1"></i> رادار العناية المركزة
            </a>
            <a href="{{ route('cashier.ccu.statements') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-file-invoice-dollar me-1"></i> كشوفات حسابات CCU
            </a>
            <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addCcuServiceModal">
                <i class="fas fa-plus me-1"></i> إضافة باقة / خدمة جديدة
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-4 border-success" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <ul class="mb-0 small">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- التبويبات الرئيسية الثلاثة -->
    <ul class="nav nav-pills mb-4 bg-white p-2 rounded-3 shadow-xs gap-2" id="ccuControlTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $activeTab === 'packages-pane' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#packages-pane" type="button">
                <i class="fas fa-layer-group me-1"></i> الباقات اليومية والخدمات المنفصلة ({{ $packages->count() + $addons->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $activeTab === 'brackets-pane' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#brackets-pane" type="button">
                <i class="fas fa-percentage me-1"></i> شرائح تغطية الضمان حسب الأيام ({{ $brackets->count() }})
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-bold {{ $activeTab === 'settings-pane' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#settings-pane" type="button">
                <i class="fas fa-cogs me-1"></i> الضوابط العامة (ساعة القطع / السقف السنوي / النسب)
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- =================== التبويب 1: الباقات والخدمات المنفصلة =================== -->
        <div class="tab-pane fade {{ $activeTab === 'packages-pane' ? 'show active' : '' }}" id="packages-pane">
            <!-- جدول الباقات الأساسية للرقود -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-primary mb-0">
                        <i class="fas fa-bed me-1"></i> باقات الإقامة والرقود اليومية (شاملة أو سرير منفصل)
                    </h6>
                    <span class="badge bg-light text-dark border">مرتبطة بالوثيقة الرسمية وتسعيرة الكاش</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">اسم الباقة والكود</th>
                                <th>سعر الكاش / اليوم</th>
                                <th>سعر الضمان / اليوم</th>
                                <th>احتساب السرير</th>
                                <th>الخدمات المشمولة بالباقة (0 د.ع)</th>
                                <th>الحالة</th>
                                <th>تعديل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($packages as $srv)
                                <tr>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $srv->name }}</div>
                                        <small class="text-muted font-monospace">{{ $srv->code }}</small>
                                        @if($srv->official_claim_name)
                                            <div class="text-primary" style="font-size: 11px;">{{ $srv->official_claim_name }}</div>
                                        @endif
                                    </td>
                                    <td class="fw-bold font-monospace">{{ number_format($srv->daily_fee) }} د.ع</td>
                                    <td class="font-monospace">
                                        @if($srv->is_hi_active)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ number_format($srv->hi_price) }} د.ع</span>
                                        @else
                                            <span class="badge bg-light text-muted border">غير مفعل للضمان</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($srv->include_bed_fee)
                                            <span class="badge bg-primary-subtle text-primary">شامل أجرة السرير</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle">+ سعر السرير منفصلاً</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-1 flex-wrap">
                                            @if($srv->is_bundled_lab)<span class="badge bg-info text-dark">المختبر</span>@endif
                                            @if($srv->is_bundled_radiology)<span class="badge bg-purple text-white" style="background:#6f42c1;">الأشعة</span>@endif
                                            @if($srv->is_bundled_medications)<span class="badge bg-secondary">الأدوية الروتينية</span>@endif
                                            @if(!$srv->is_bundled_lab && !$srv->is_bundled_radiology && !$srv->is_bundled_medications)
                                                <span class="text-muted">إقامة وتمريض فقط (البنود منفصلة)</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm toggle-ccu-service-btn {{ $srv->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                                data-url="{{ route('ccu.services.toggle', $srv->id) }}">
                                            {{ $srv->is_active ? 'مفعل' : 'معطل' }}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCcuServiceModal{{ $srv->id }}">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- جدول الخدمات الإضافية المنفصلة (لنظام الكاش والخدمات الفردية) -->
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-warning text-dark mb-0">
                        <i class="fas fa-plus-square text-warning me-1"></i> خدمات إضافية وأجهزة منفصلة (الكاش والخدمات المخصصة)
                    </h6>
                    <span class="badge bg-warning-subtle text-dark border border-warning-subtle">تُضاف فوق أجرة السرير أو الباقة</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center small">
                        <thead class="table-light">
                            <tr>
                                <th class="text-start">اسم الخدمة المخصصة / الجهاز</th>
                                <th>سعر الكاش (للوحدة/اليوم)</th>
                                <th>سعر الضمان</th>
                                <th>الحالة</th>
                                <th>تعديل</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($addons as $srv)
                                <tr>
                                    <td class="text-start">
                                        <div class="fw-bold text-dark">{{ $srv->name }}</div>
                                        <small class="text-muted font-monospace">{{ $srv->code }}</small>
                                    </td>
                                    <td class="fw-bold font-monospace">{{ number_format($srv->daily_fee) }} د.ع</td>
                                    <td class="font-monospace">
                                        @if($srv->is_hi_active)
                                            <span class="badge bg-success-subtle text-success">{{ number_format($srv->hi_price) }} د.ع</span>
                                        @else
                                            <span class="text-muted">نقدي / كاش</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button"
                                                class="btn btn-sm toggle-ccu-service-btn {{ $srv->is_active ? 'btn-success' : 'btn-outline-secondary' }}"
                                                data-url="{{ route('ccu.services.toggle', $srv->id) }}">
                                            {{ $srv->is_active ? 'مفعل' : 'معطل' }}
                                        </button>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCcuServiceModal{{ $srv->id }}">
                                            <i class="fas fa-edit"></i> تعديل
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted py-4">لا توجد خدمات إضافية معرفة بعد.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =================== التبويب 2: شرائح تغطية الضمان التنازلية =================== -->
        <div class="tab-pane fade {{ $activeTab === 'brackets-pane' ? 'show active' : '' }}" id="brackets-pane">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h6 class="fw-bold text-success mb-0">
                                <i class="fas fa-chart-bar me-1"></i> شرائح نسب تحمل هيئة الضمان الصحي والمريض (من اليوم 1 إلى 30)
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0 text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start">اسم الشريحة</th>
                                        <th>فئة الضمان</th>
                                        <th>من يوم</th>
                                        <th>إلى يوم</th>
                                        <th>نسبة الضمان %</th>
                                        <th>نسبة المريض %</th>
                                        <th>مفعل</th>
                                        <th>حفظ / حذف</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($brackets as $br)
                                        <tr>
                                            <form action="{{ route('ccu.brackets.update', $br->id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <td class="text-start">
                                                    <input type="text" name="name" value="{{ $br->name }}" class="form-control form-control-sm" required>
                                                </td>
                                                <td style="width: 85px;">
                                                    <input type="text" name="policy_category" value="{{ $br->policy_category }}" class="form-control form-control-sm text-center font-monospace">
                                                </td>
                                                <td style="width: 75px;">
                                                    <input type="number" name="min_day" value="{{ $br->min_day }}" class="form-control form-control-sm text-center" min="1" required>
                                                </td>
                                                <td style="width: 75px;">
                                                    <input type="number" name="max_day" value="{{ $br->max_day }}" class="form-control form-control-sm text-center">
                                                </td>
                                                <td style="width: 90px;">
                                                    <input type="number" step="0.5" name="hia_percent" value="{{ (float) $br->hia_percent }}" class="form-control form-control-sm text-center fw-bold text-success" min="0" max="100" required>
                                                </td>
                                                <td class="fw-bold text-danger font-monospace">{{ (float) $br->patient_percent }}%</td>
                                                <td>
                                                    <input type="checkbox" name="is_active" value="1" class="form-check-input" {{ $br->is_active ? 'checked' : '' }}>
                                                </td>
                                                <td class="text-nowrap">
                                                    <button type="submit" class="btn btn-sm btn-primary" title="حفظ"><i class="fas fa-save"></i></button>
                                            </form>
                                                    <form action="{{ route('ccu.brackets.destroy', $br->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل تريد حذف هذه الشريحة؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                                                    </form>
                                                </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fas fa-plus-circle text-primary me-1"></i> إضافة شريحة أيام تغطية جديدة
                        </h6>
                        <form action="{{ route('ccu.brackets.store') }}" method="POST">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small fw-bold">اسم الشريحة</label>
                                <input type="text" name="name" class="form-control form-control-sm" required placeholder="مثال: الأيام الخمسة الأولى">
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <label class="form-label small fw-bold">فئة الوثيقة</label>
                                    <input type="text" name="policy_category" class="form-control form-control-sm" value="ALL" placeholder="ALL أو E">
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">من اليوم</label>
                                    <input type="number" name="min_day" class="form-control form-control-sm" min="1" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label small fw-bold">إلى اليوم</label>
                                    <input type="number" name="max_day" class="form-control form-control-sm" min="1">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold">نسبة تغطية الضمان الصحي (%)</label>
                                <input type="number" step="0.5" min="0" max="100" name="hia_percent" class="form-control form-control-sm" required placeholder="75">
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">إضافة الشريحة</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- =================== التبويب 3: الإعدادات والضوابط العامة =================== -->
        <div class="tab-pane fade {{ $activeTab === 'settings-pane' ? 'show active' : '' }}" id="settings-pane">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <form action="{{ route('ccu.settings.update') }}" method="POST">
                    @csrf
                    <h6 class="fw-bold text-dark mb-3">
                        <i class="fas fa-cogs text-primary me-1"></i> الضوابط والقواعد الحسابية الداينمكية لوحدة العناية المركزة
                    </h6>
                    <div class="row g-3">
                        @foreach($settings as $setting)
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 bg-light h-100">
                                    <label class="form-label fw-bold text-dark mb-1">{{ $setting->label }}</label>
                                    @if($setting->key === 'category_a_exempt')
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" name="settings[category_a_exempt]" value="1" id="setting_{{ $setting->key }}" {{ $setting->value === '1' ? 'checked' : '' }}>
                                            <label class="form-check-label small" for="setting_{{ $setting->key }}">تفعيل إعفاء الفئة A بنسبة 100% على حساب الضمان</label>
                                        </div>
                                    @else
                                        <input type="number" step="1" name="settings[{{ $setting->key }}]" value="{{ $setting->value }}" class="form-control form-control-sm mb-1 font-monospace fw-bold">
                                    @endif
                                    @if($setting->description)
                                        <small class="text-muted d-block">{{ $setting->description }}</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ كافة الضوابط والإعدادات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- مودالات تعديل الباقات والخدمات -->
@foreach($packages->merge($addons) as $srv)
<div class="modal fade" id="editCcuServiceModal{{ $srv->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('ccu.services.update', $srv->id) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold">تعديل: {{ $srv->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-bold">اسم الخدمة / الباقة</label>
                        <input type="text" name="name" value="{{ $srv->name }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">النوع</label>
                        <select name="service_type" class="form-select form-select-sm">
                            <option value="package" {{ $srv->service_type === 'package' ? 'selected' : '' }}>باقة رقود يومية</option>
                            <option value="addon" {{ $srv->service_type === 'addon' ? 'selected' : '' }}>خدمة مخصصة منفصلة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">سعر الكاش (د.ع)</label>
                        <input type="number" name="daily_fee" value="{{ (float) $srv->daily_fee }}" class="form-control form-control-sm" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">تسعيرة الضمان الصحي (د.ع)</label>
                        <input type="number" name="hi_price" value="{{ (float) $srv->hi_price }}" class="form-control form-control-sm" min="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">المسمى الرسمي في مطالبة الضمان الصحي</label>
                        <input type="text" name="official_claim_name" value="{{ $srv->official_claim_name }}" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3 p-2 bg-light rounded border small">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $srv->is_active ? 'checked' : '' }}>
                                <label class="form-check-label">الخدمة مفعلة</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_hi_active" value="1" {{ $srv->is_hi_active ? 'checked' : '' }}>
                                <label class="form-check-label">معتمدة للضمان</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="include_bed_fee" value="1" {{ $srv->include_bed_fee ? 'checked' : '' }}>
                                <label class="form-check-label">تشمل أجرة السرير (باقة شاملة)</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bundled_lab" value="1" {{ $srv->is_bundled_lab ? 'checked' : '' }}>
                                <label class="form-check-label">تشمل التحاليل (0 على المريض)</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bundled_radiology" value="1" {{ $srv->is_bundled_radiology ? 'checked' : '' }}>
                                <label class="form-check-label">تشمل الأشعة (0 على المريض)</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bundled_medications" value="1" {{ $srv->is_bundled_medications ? 'checked' : '' }}>
                                <label class="form-check-label">تشمل الأدوية الروتينية</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">ملاحظات / وصف</label>
                        <textarea name="description" rows="2" class="form-control form-control-sm">{{ $srv->description }}</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold">حفظ التغييرات</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- مودال إضافة باقة / خدمة جديدة -->
<div class="modal fade" id="addCcuServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('ccu.services.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">إضافة باقة رقود أو خدمة مخصصة جديدة (CCU)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">اسم الخدمة / الباقة *</label>
                        <input type="text" name="name" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">الكود البرمجي *</label>
                        <input type="text" name="code" class="form-control form-control-sm font-monospace" required placeholder="custom_icu_pkg">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">النوع *</label>
                        <select name="service_type" class="form-select form-select-sm" required>
                            <option value="package">باقة رقود يومية</option>
                            <option value="addon">خدمة مخصصة منفصلة</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">سعر الكاش (د.ع) *</label>
                        <input type="number" name="daily_fee" class="form-control form-control-sm" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">تسعيرة الضمان الصحي (د.ع)</label>
                        <input type="number" name="hi_price" class="form-control form-control-sm" min="0" value="0">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">المسمى الرسمي في مطالبة الضمان</label>
                        <input type="text" name="official_claim_name" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3 p-2 bg-light rounded border small">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
                                <label class="form-check-label">مفعل</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_hi_active" value="1">
                                <label class="form-check-label">يغطيها الضمان</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="include_bed_fee" value="1" checked>
                                <label class="form-check-label">شامل أجرة السرير</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bundled_lab" value="1">
                                <label class="form-check-label">يشمل التحاليل</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bundled_radiology" value="1">
                                <label class="form-check-label">يشمل الأشعة</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-sm btn-primary fw-bold">إضافة وحفظ</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.querySelectorAll('.toggle-ccu-service-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const url = this.dataset.url;
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        }).then(r => r.json()).then(data => {
            if (data.success) {
                this.className = 'btn btn-sm toggle-ccu-service-btn ' + (data.is_active ? 'btn-success' : 'btn-outline-secondary');
                this.textContent = data.is_active ? 'مفعل' : 'معطل';
            }
        });
    });
});
</script>
@endsection
