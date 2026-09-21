@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- عنوان الصفحة والأزرار -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-pills me-2"></i>دليل الأدوية والمستلزمات الطبية
            </h2>
            <p class="text-muted small mb-0">إدارة الأصناف الدوائية، الرموز الوطنية المعتمدة بالضمان، وتعدد الوحدات (علبة / شريط)</p>
        </div>
        <div class="d-flex gap-2">
            @can('create medicines')
                <a href="{{ route('pharmacy.medicines.import') }}" class="btn btn-outline-success shadow-sm">
                    <i class="fas fa-file-excel me-1"></i> استيراد من Excel / CSV
                </a>
                <a href="{{ route('pharmacy.medicines.create') }}" class="btn btn-primary shadow-sm px-3">
                    <i class="fas fa-plus-circle me-1"></i> إضافة دواء / مستلزم جديد
                </a>
            @endcan
        </div>
    </div>

    <!-- بطاقات الإحصائيات السريعة -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-primary bg-opacity-10 text-primary me-3">
                        <i class="fas fa-capsules fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">إجمالي الأصناف بالدليل</div>
                        <h4 class="fw-bold mb-0 text-dark">{{ number_format($stats['total']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-success bg-opacity-10 text-success me-3">
                        <i class="fas fa-shield-alt fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">مشمولة بالضمان الصحي</div>
                        <h4 class="fw-bold mb-0 text-success">{{ number_format($stats['insurance_covered']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-danger bg-opacity-10 text-danger me-3">
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">أدوية رقابية ومخدرات</div>
                        <h4 class="fw-bold mb-0 text-danger">{{ number_format($stats['controlled']) }}</h4>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 bg-white h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="rounded-circle p-3 bg-info bg-opacity-10 text-info me-3">
                        <i class="fas fa-shapes fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small">الأشكال الصيدلانية</div>
                        <h4 class="fw-bold mb-0 text-info">{{ $stats['forms_count'] }} شكل</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- شريط البحث والفلاتر المتقدمة -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('pharmacy.medicines.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted">بحث سريع (الاسم، العلمي، الباركود، الرمز الوطني)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="ابحث بالاسم أو الباركود أو الرمز..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">الشكل الصيدلاني</label>
                    <select name="dosage_form" class="form-select">
                        <option value="">كل الأشكال</option>
                        @foreach($dosageForms as $form)
                            <option value="{{ $form }}" {{ request('dosage_form') == $form ? 'selected' : '' }}>{{ $form }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">الضمان الصحي</label>
                    <select name="insurance_covered" class="form-select">
                        <option value="">الكل</option>
                        <option value="yes" {{ request('insurance_covered') === 'yes' ? 'selected' : '' }}>مشمول فقط</option>
                        <option value="no" {{ request('insurance_covered') === 'no' ? 'selected' : '' }}>غير مشمول</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">اشتراط وصفة</label>
                    <select name="requires_prescription" class="form-select">
                        <option value="">الكل</option>
                        <option value="yes" {{ request('requires_prescription') === 'yes' ? 'selected' : '' }}>بوصفة فقط</option>
                        <option value="no" {{ request('requires_prescription') === 'no' ? 'selected' : '' }}>بدون وصفة (OTC)</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> تصفية
                    </button>
                    @if(request()->anyFilled(['search', 'dosage_form', 'insurance_covered', 'requires_prescription', 'is_controlled']))
                        <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-outline-secondary" title="إلغاء الفلاتر">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الأدوية والمستلزمات -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-muted small">
                            <th>الرمز / الباركود</th>
                            <th>اسم الدواء / المستلزم</th>
                            <th>الشكل والعيار</th>
                            <th>الوحدات (الكبرى / الصغرى)</th>
                            <th>سعر العلبة (كاش / ضمان)</th>
                            <th>سعر الشريط (كاش)</th>
                            <th>الرصيد الحالي</th>
                            <th>الحالة والاشتراطات</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($medicines as $medicine)
                            <tr>
                                <td>
                                    @if($medicine->national_code)
                                        <span class="badge bg-dark bg-opacity-75 font-monospace mb-1 d-inline-block">{{ $medicine->national_code }}</span><br>
                                    @endif
                                    @if($medicine->barcode)
                                        <small class="text-muted font-monospace"><i class="fas fa-barcode me-1"></i>{{ $medicine->barcode }}</small>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('pharmacy.medicines.show', $medicine->id) }}" class="fw-bold text-decoration-none text-primary">
                                        {{ $medicine->name }}
                                    </a>
                                    @if($medicine->generic_name)
                                        <div class="text-muted small fst-italic">{{ $medicine->generic_name }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary">{{ $medicine->dosage_form ?? 'غير محدد' }}</span>
                                    @if($medicine->strength)
                                        <span class="badge bg-info bg-opacity-10 text-info">{{ $medicine->strength }}</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $medicine->main_unit }}</span>
                                    @if($medicine->sub_units_count > 1)
                                        <span class="text-muted small">({{ $medicine->sub_units_count }} {{ $medicine->sub_unit }})</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ number_format($medicine->sale_price) }} د.ع</div>
                                    @if($medicine->is_insurance_covered)
                                        <div class="text-success small"><i class="fas fa-shield-alt me-1"></i>ضمان: {{ number_format($medicine->hi_price ?? $medicine->sale_price) }} د.ع</div>
                                    @else
                                        <span class="badge bg-warning bg-opacity-10 text-warning small">غير مشمول</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="text-dark">{{ number_format($medicine->sub_unit_sale_price) }} د.ع</span>
                                </td>
                                <td>
                                    @php
                                        $stock = $medicine->total_stock;
                                        $openSubUnits = $medicine->total_open_sub_units;
                                    @endphp
                                    @if($stock <= 0 && $openSubUnits <= 0)
                                        <span class="badge bg-danger">نفد الرصيد</span>
                                    @elseif($medicine->is_low_stock)
                                        <span class="badge bg-warning text-dark">{{ $stock }} علبة</span>
                                        @if($openSubUnits > 0)
                                            <span class="badge bg-light text-dark border">+{{ $openSubUnits }} شريط</span>
                                        @endif
                                    @else
                                        <span class="badge bg-success">{{ $stock }} علبة</span>
                                        @if($openSubUnits > 0)
                                            <span class="badge bg-light text-dark border">+{{ $openSubUnits }} شريط</span>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    @if($medicine->requires_prescription)
                                        <span class="badge bg-danger bg-opacity-10 text-danger mb-1 d-inline-block" title="يشترط وصفة طبية">
                                            <i class="fas fa-file-prescription"></i> وصفة إجبارية
                                        </span>
                                    @endif
                                    @if($medicine->is_controlled)
                                        <span class="badge bg-dark text-warning mb-1 d-inline-block" title="دواء رقابي ومخدر">
                                            <i class="fas fa-lock"></i> رقابي
                                        </span>
                                    @endif
                                    @if(!$medicine->requires_prescription && !$medicine->is_controlled)
                                        <span class="badge bg-success bg-opacity-10 text-success">متاح OTC</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('pharmacy.medicines.show', $medicine->id) }}" class="btn btn-outline-info" title="عرض التفاصيل والوجبات">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @can('edit medicines')
                                            <a href="{{ route('pharmacy.medicines.edit', $medicine->id) }}" class="btn btn-outline-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endcan
                                        @can('delete medicines')
                                            <form action="{{ route('pharmacy.medicines.destroy', $medicine->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من أرشفة هذا الدواء؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="أرشفة / حذف">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-2">لا توجد أدوية أو مستلزمات مطابقة لمعايير البحث.</p>
                                    @can('create medicines')
                                        <a href="{{ route('pharmacy.medicines.create') }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-plus-circle me-1"></i> إضافة دواء جديد الآن
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($medicines->hasPages())
            <div class="card-footer bg-white border-0 py-3">
                {{ $medicines->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
