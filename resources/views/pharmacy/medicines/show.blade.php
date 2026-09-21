@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- شريط العنوان والإجراءات -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="h4 fw-bold text-primary mb-0">
                    <i class="fas fa-pills me-2"></i>{{ $medicine->name }}
                </h2>
                @if($medicine->is_insurance_covered)
                    <span class="badge bg-success"><i class="fas fa-shield-alt me-1"></i>مشمول بالضمان</span>
                @else
                    <span class="badge bg-secondary">غير مشمول</span>
                @endif
                @if($medicine->requires_prescription)
                    <span class="badge bg-danger"><i class="fas fa-file-prescription me-1"></i>وصفة إجبارية</span>
                @endif
                @if($medicine->is_controlled)
                    <span class="badge bg-dark text-warning"><i class="fas fa-lock me-1"></i>رقابي</span>
                @endif
            </div>
            <p class="text-muted small mb-0">
                الاسم العلمي: <span class="fw-semibold">{{ $medicine->generic_name ?? 'غير محدد' }}</span>
                @if($medicine->national_code)
                    | الرمز الوطني: <span class="font-monospace fw-bold text-dark">{{ $medicine->national_code }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @can('edit medicines')
                <a href="{{ route('pharmacy.medicines.edit', $medicine->id) }}" class="btn btn-primary btn-sm shadow-sm">
                    <i class="fas fa-edit me-1"></i> تعديل البيانات
                </a>
            @endcan
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    <!-- بطاقات الرصيد والتسعير -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">الرصيد المتوفر بالعلب</span>
                        <i class="fas fa-box text-primary fa-lg"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-0">{{ $medicine->total_stock }} <small class="fs-6 text-muted">{{ $medicine->main_unit }}</small></h3>
                    @if($medicine->total_open_sub_units > 0)
                        <div class="text-info small mt-1">
                            <i class="fas fa-layer-group me-1"></i> بالإضافة إلى {{ $medicine->total_open_sub_units }} {{ $medicine->sub_unit }} (من علب مفتوحة)
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">معامل التحويل للوحدات</span>
                        <i class="fas fa-exchange-alt text-info fa-lg"></i>
                    </div>
                    <h4 class="fw-bold text-dark mb-0">1 {{ $medicine->main_unit }} = {{ $medicine->sub_units_count }} {{ $medicine->sub_unit }}</h4>
                    <div class="text-muted small mt-1">
                        إجمالي المخزون المكافئ: {{ $medicine->total_sub_units_stock }} {{ $medicine->sub_unit }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">سعر البيع نقداً (كاش)</span>
                        <i class="fas fa-money-bill-wave text-success fa-lg"></i>
                    </div>
                    <h3 class="fw-bold text-success mb-0">{{ number_format($medicine->sale_price) }} <small class="fs-6 text-muted">د.ع / {{ $medicine->main_unit }}</small></h3>
                    <div class="text-muted small mt-1">
                        سعر {{ $medicine->sub_unit }}: {{ number_format($medicine->sub_unit_sale_price) }} د.ع
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">سعر معتمد للضمان الصحي</span>
                        <i class="fas fa-shield-alt text-primary fa-lg"></i>
                    </div>
                    <h3 class="fw-bold text-primary mb-0">
                        {{ number_format($medicine->hi_price ?? $medicine->sale_price) }} <small class="fs-6 text-muted">د.ع</small>
                    </h3>
                    <div class="text-muted small mt-1">
                        سعر التكلفة: {{ number_format($medicine->cost_price) }} د.ع
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- وجبات وشحنات الصلاحية FEFO -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="fas fa-boxes me-2"></i>شحنات الدواء ووجبات الصلاحية (نظام FEFO الأقرب انتهاءً أولاً)
                        </h6>
                        <small class="text-muted">يتم الصرف آلياً من الوجبة الأقرب انتهاءً لضمان عدم تلف المخزون</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="text-muted small">
                                    <th>رقم الوجبة (Lot)</th>
                                    <th>تاريخ الانتهاء</th>
                                    <th>المدة المتبقية</th>
                                    <th>الرصيد المتوفر</th>
                                    <th>سعر الشراء</th>
                                    <th>المورد / الشركة</th>
                                    <th>حالة الوجبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($medicine->batches as $batch)
                                    <tr>
                                        <td>
                                            <span class="font-monospace fw-bold text-dark">{{ $batch->batch_number }}</span>
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $batch->expiry_date ? $batch->expiry_date->format('Y-m-d') : '-' }}</span>
                                        </td>
                                        <td>
                                            @if($batch->is_expired)
                                                <span class="badge bg-danger">منتهي الصلاحية</span>
                                            @elseif($batch->is_expiring_soon)
                                                <span class="badge bg-warning text-dark">{{ $batch->days_until_expiry }} يوم متبقي</span>
                                            @else
                                                <span class="badge bg-success bg-opacity-10 text-success">{{ $batch->days_until_expiry }} يوم</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-bold text-dark">{{ $batch->current_quantity }} {{ $medicine->main_unit }}</span>
                                            @if($batch->current_sub_units > 0)
                                                <span class="badge bg-light text-dark border ms-1">+{{ $batch->current_sub_units }} {{ $medicine->sub_unit }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ number_format($batch->purchase_price) }} د.ع</span>
                                        </td>
                                        <td>
                                            <span class="text-muted small">{{ $batch->supplier_name ?? 'غير محدد' }}</span>
                                        </td>
                                        <td>
                                            @if($batch->status === 'active')
                                                <span class="badge bg-success">نشطة</span>
                                            @elseif($batch->status === 'expired')
                                                <span class="badge bg-danger">منتهية</span>
                                            @elseif($batch->status === 'depleted')
                                                <span class="badge bg-secondary">منفذة</span>
                                            @else
                                                <span class="badge bg-warning text-dark">{{ $batch->status }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-boxes fa-2x mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">لا توجد وجبات أو شحنات مسجلة لهذا الدواء حتى الآن.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- جدول احتساب نسب الاستقطاع للضمان الصحي (محاكاة حية) -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-success mb-0">
                        <i class="fas fa-calculator me-2"></i>محاكاة حصص المريض والضمان الصحي حسب الفئات (A إلى I)
                    </h6>
                    <small class="text-muted">الاحتساب المعتمد وفق نسب القطاع الأهلي الرسمية للعلبة الكاملة</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0 align-middle text-center">
                            <thead class="table-light small">
                                <tr>
                                    <th>فئة المريض</th>
                                    <th>نسبة التحمل</th>
                                    <th>سعر الدواء المعتمد</th>
                                    <th>حصة المريض (يسددها بالصيدلية)</th>
                                    <th>حصة الضمان (مطالبة)</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @php
                                    $approved = $medicine->hi_price ?? $medicine->sale_price;
                                    $sampleCategories = [
                                        ['cat' => 'الفئة A, B, C (رعاية اجتماعية/متقاعدين)', 'rate' => 0],
                                        ['cat' => 'الفئة D (موظفي الدولة 5%)', 'rate' => 5],
                                        ['cat' => 'الفئة E, F, G, H (القطاع الخاص 25%)', 'rate' => 25],
                                        ['cat' => 'الفئة I (حالات خاصة 50%)', 'rate' => 50],
                                    ];
                                @endphp
                                @foreach($sampleCategories as $row)
                                    @php
                                        $pShare = round($approved * ($row['rate'] / 100));
                                        $iShare = $approved - $pShare;
                                    @endphp
                                    <tr>
                                        <td class="text-start ps-3 fw-semibold">{{ $row['cat'] }}</td>
                                        <td><span class="badge bg-light text-dark border">{{ $row['rate'] }}%</span></td>
                                        <td>{{ number_format($approved) }} د.ع</td>
                                        <td class="fw-bold text-danger">{{ number_format($pShare) }} د.ع</td>
                                        <td class="fw-bold text-success">{{ number_format($iShare) }} د.ع</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- البدائل الدوائية العلمية ومعلومات الباركود -->
        <div class="col-lg-4">
            <!-- البدائل الدوائية العلمية -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-random me-2 text-info"></i>البدائل العلمية المكافئة
                    </h6>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAlternativeModal">
                        <i class="fas fa-plus"></i> إضافة بديل
                    </button>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($medicine->alternatives as $alt)
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <a href="{{ route('pharmacy.medicines.show', $alt->id) }}" class="fw-bold text-decoration-none text-primary">
                                        {{ $alt->name }}
                                    </a>
                                    <div class="text-muted small">
                                        السعر: {{ number_format($alt->sale_price) }} د.ع | الرصيد: {{ $alt->total_stock }} {{ $alt->main_unit }}
                                    </div>
                                    @if($alt->pivot->notes)
                                        <div class="text-secondary small fst-italic mt-1"><i class="fas fa-info-circle me-1"></i>{{ $alt->pivot->notes }}</div>
                                    @endif
                                </div>
                                <form action="{{ route('pharmacy.medicines.alternatives.remove', [$medicine->id, $alt->id]) }}" method="POST" onsubmit="return confirm('إزالة هذا البديل؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm border-0" title="إزالة الربط">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </li>
                        @empty
                            <li class="list-group-item text-center py-4 text-muted">
                                <i class="fas fa-pills fa-2x mb-2 text-secondary opacity-50"></i>
                                <p class="small mb-0">لم يتم ربط بدائل علمية لهذا الدواء بعد.</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <!-- بطاقة الباركود والمطابقة السريعة -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold text-dark mb-0">
                        <i class="fas fa-barcode me-2"></i>بيانات الباركود والمسح السريع
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">باركود العلبة (الوحدة الكبرى):</label>
                        <div class="p-2 bg-light rounded border text-center font-monospace fw-bold">
                            @if($medicine->barcode)
                                <i class="fas fa-barcode me-1"></i>{{ $medicine->barcode }}
                            @else
                                <span class="text-muted small">لا يوجد باركود مسجل</span>
                            @endif
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted mb-1">باركود الشريط (الوحدة الصغرى):</label>
                        <div class="p-2 bg-light rounded border text-center font-monospace fw-bold">
                            @if($medicine->sub_barcode)
                                <i class="fas fa-barcode me-1"></i>{{ $medicine->sub_barcode }}
                            @else
                                <span class="text-muted small">لا يوجد باركود شريط مسجل</span>
                            @endif
                        </div>
                    </div>

                    @if($medicine->storage_temperature)
                        <div class="mb-2">
                            <span class="badge bg-light text-dark border p-2 w-100 text-start">
                                <i class="fas fa-thermometer-half me-1 text-danger"></i> <strong>الحفظ:</strong> {{ $medicine->storage_temperature }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة بديل دوائي -->
<div class="modal fade" id="addAlternativeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('pharmacy.medicines.alternatives.add', $medicine->id) }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-random me-2 text-primary"></i>ربط بديل دوائي مكافئ</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">اختر البديل العلمي <span class="text-danger">*</span></label>
                        <select name="alternative_medicine_id" class="form-select" required>
                            <option value="">-- اختر الدواء المكافئ --</option>
                            @foreach($availableAlternatives as $altMed)
                                <option value="{{ $altMed->id }}">{{ $altMed->name }} ({{ $altMed->dosage_form }} - {{ $altMed->strength }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملاحظات التكافؤ (اختياري)</label>
                        <input type="text" name="notes" class="form-control" placeholder="مثال: نفس المادة الفعالة، عيار 500mg، شركة أردنية">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-link me-1"></i> تأكيد ربط البديل</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
