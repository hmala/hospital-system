@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- الترويسة وأزرار العمليات -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h3 fw-bold text-info mb-1">
                <i class="fas fa-boxes me-2"></i>مخزن مستلزمات وعدسات مركز العيون
            </h2>
            <p class="text-muted mb-0 small">إدارة العدسات داخل العين (IOLs)، إبر حقن الشبكية، المحاليل الجراحية، والتجهيز المباشر أو من المخزن الرئيسي للمستشفى</p>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#directPurchaseModal">
                <i class="fas fa-cart-plus me-1"></i>توريد مباشر خاص بالعيون
            </button>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#requisitionModal">
                <i class="fas fa-dolly-flatbed me-1"></i>طلب تجهيز من المخزن الرئيسي
            </button>
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="fas fa-plus me-1"></i>تعريف صنف جديد
            </button>
        </div>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-info text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">إجمالي أصناف العيون</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['total_items'] }}</h3>
                        </div>
                        <i class="fas fa-cubes fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-primary text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">رصيد عدسات IOL المتاح</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['iol_count'] }} <small class="fs-6">عدسة</small></h3>
                        </div>
                        <i class="fas fa-circle-notch fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-warning text-dark h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-dark small fw-semibold">أصناف قاربت على النفاد</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ $stats['low_stock'] }}</h3>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 col-6">
            <div class="card border-0 shadow-sm rounded-3 bg-success text-white h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-white-50 small">القيمة التقديرية للمخزون</div>
                            <h3 class="fw-bold mb-0 mt-1">{{ number_format($stats['total_valuation']) }} <small class="fs-6">د.ع</small></h3>
                        </div>
                        <i class="fas fa-wallet fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- التبويبات الرئيسية لمخزن العيون -->
    <ul class="nav nav-pills nav-fill bg-white p-2 rounded-3 shadow-sm mb-4" id="storeTabs" role="tablist">
        <li class="nav-item">
            <button class="nav-link active fw-bold" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventoryTab">
                <i class="fas fa-boxes me-2"></i>الأصناف والعدسات في المخزن
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="transfers-tab" data-bs-toggle="tab" data-bs-target="#transfersTab">
                <i class="fas fa-truck-loading me-2"></i>طلبات التجهيز من المخزن الرئيسي 
                @if($transferRequests->where('status', 'pending')->count() > 0)
                    <span class="badge bg-warning text-dark ms-1">{{ $transferRequests->where('status', 'pending')->count() }}</span>
                @endif
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link fw-bold" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movementsTab">
                <i class="fas fa-history me-2"></i>سجل الحركات والصرف للعمليات
            </button>
        </li>
    </ul>

    <div class="tab-content" id="storeTabsContent">
        <!-- التبويب 1: رصيد الأصناف والعدسات -->
        <div class="tab-pane fade show active" id="inventoryTab">
            <!-- شريط البحث والفلترة -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-3">
                    <form action="{{ route('eye.store.index') }}" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-5">
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="ابحث باسم الصنف، الكود، أو الموديل..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="category" class="form-select">
                                <option value="">-- كل التصنيفات --</option>
                                <option value="iol_lens" {{ request('category') == 'iol_lens' ? 'selected' : '' }}>عدسات داخل العين (IOL)</option>
                                <option value="retinal_injection" {{ request('category') == 'retinal_injection' ? 'selected' : '' }}>إبر حقن الشبكية</option>
                                <option value="viscoelastic" {{ request('category') == 'viscoelastic' ? 'selected' : '' }}>محاليل لزجة جراحية</option>
                                <option value="surgical_blade" {{ request('category') == 'surgical_blade' ? 'selected' : '' }}>شفرات جراحية</option>
                                <option value="suture" {{ request('category') == 'suture' ? 'selected' : '' }}>خيوط جراحة العيون</option>
                                <option value="general_consumable" {{ request('category') == 'general_consumable' ? 'selected' : '' }}>مستهلكات عامة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">-- كل الأرصدة --</option>
                                <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>قاربت على النفاد</option>
                                <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>نافدة (0)</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-info text-white flex-grow-1">تصفية</button>
                            <a href="{{ route('eye.store.index') }}" class="btn btn-outline-secondary"><i class="fas fa-undo"></i></a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>كود الصنف</th>
                                <th>اسم المستلزم / العدسة</th>
                                <th>التصنيف</th>
                                <th>القوة (Diopter)</th>
                                <th>الشركة / الموديل</th>
                                <th class="text-center">الرصيد المتاح</th>
                                <th class="text-end">سعر التكلفة</th>
                                <th class="text-end">سعر البيع</th>
                                <th class="text-center" style="width: 140px;">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($items as $item)
                            <tr class="{{ $item->is_low_stock ? 'table-warning' : '' }}">
                                <td>
                                    <span class="fw-bold font-monospace text-dark">{{ $item->item_code }}</span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->name }}</div>
                                    <div class="small text-muted">{{ $item->unit }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->category_arabic }}</span>
                                </td>
                                <td>
                                    @if($item->diopter !== null)
                                        <span class="badge bg-primary fs-6 px-2 py-1">+{{ number_format($item->diopter, 2) }} D</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="small fw-semibold">{{ $item->manufacturer ?? '-' }}</div>
                                    <div class="small text-muted">{{ $item->model_number ?? '-' }}</div>
                                </td>
                                <td class="text-center">
                                    @if($item->current_stock <= 0)
                                        <span class="badge bg-danger rounded-pill px-3 py-2 fs-6">نافد (0)</span>
                                    @elseif($item->is_low_stock)
                                        <span class="badge bg-warning text-dark rounded-pill px-3 py-2 fs-6" title="أقل من حد التنبيه">
                                            {{ $item->current_stock }} {{ $item->unit }} ⚠️
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2 fs-6">
                                            {{ $item->current_stock }} {{ $item->unit }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($item->cost_price) }} د.ع</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($item->selling_price) }} د.ع</td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-success" onclick="openDirectPurchaseModal('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ $item->cost_price }}')">
                                        <i class="fas fa-plus"></i> توريد
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fas fa-box-open fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div class="h5">لا توجد أصناف مطابقة في مخزن العيون</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($items->hasPages())
                <div class="card-footer bg-white py-3">
                    {{ $items->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- التبويب 2: طلبات التجهيز الداخلي من المخزن الرئيسي للمستشفى -->
        <div class="tab-pane fade" id="transfersTab">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-dolly text-primary me-2"></i>سجل طلبات التجهيز من المخزن الرئيسي</h5>
                    <button type="button" class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#requisitionModal">
                        <i class="fas fa-plus me-1"></i>طلب تجهيز جديد
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># رقم الطلب</th>
                                <th>تاريخ الطلب</th>
                                <th>المواد والكميات المطلوبة</th>
                                <th>طالب التجهيز</th>
                                <th>الحالة</th>
                                <th class="text-center" style="width: 160px;">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transferRequests as $req)
                            <tr>
                                <td class="fw-bold font-monospace">#{{ $req->id }}</td>
                                <td>{{ $req->created_at->format('Y-m-d h:i A') }}</td>
                                <td>
                                    @php
                                        $reqItems = is_array($req->items) ? $req->items : json_decode($req->items, true);
                                    @endphp
                                    <ul class="list-unstyled mb-0 small">
                                        @foreach($reqItems ?? [] as $rItem)
                                            <li>• <strong>{{ $rItem['name'] ?? $rItem['item_name'] ?? 'مستلزم' }}</strong>: {{ $rItem['quantity'] ?? 1 }} {{ $rItem['unit'] ?? 'قطعة' }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td>{{ $req->requestedBy->name ?? 'أمين مخزن العيون' }}</td>
                                <td>
                                    @if($req->status == 'pending')
                                        <span class="badge bg-warning text-dark px-2 py-1"><i class="fas fa-clock me-1"></i>بانتظار موافقة المخزن الرئيسي</span>
                                    @elseif($req->status == 'approved')
                                        <span class="badge bg-info text-white px-2 py-1"><i class="fas fa-box me-1"></i>جاهز للتسليم والاستلام</span>
                                    @elseif($req->status == 'completed')
                                        <span class="badge bg-success px-2 py-1"><i class="fas fa-check-circle me-1"></i>تم الاستلام والإيداع في مخزن العيون</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $req->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($req->status != 'completed')
                                    <form action="{{ route('eye.store.receiveTransfer', $req) }}" method="POST" onsubmit="return confirm('هل تؤكد استلام هذه المواد وإضافتها إلى رصيد مخزن العيون؟');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check me-1"></i>تأكيد الاستلام والإيداع
                                        </button>
                                    </form>
                                    @else
                                        <span class="text-muted small"><i class="fas fa-check-double text-success me-1"></i>مكتمل</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fas fa-dolly-flatbed fa-3x mb-3 text-secondary opacity-50"></i>
                                    <div class="h5">لا توجد طلبات تجهيز من المخزن الرئيسي حتى الآن</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- التبويب 3: سجل الحركات والصرف للعمليات -->
        <div class="tab-pane fade" id="movementsTab">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-exchange-alt text-primary me-2"></i>آخر حركات التوريد والصرف</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>التاريخ والوقت</th>
                                <th>الصنف</th>
                                <th>نوع الحركة</th>
                                <th class="text-center">الكمية</th>
                                <th class="text-center">الرصيد بعد الحركة</th>
                                <th>البيان / المريض</th>
                                <th>المسؤول</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentMovements as $mov)
                            <tr>
                                <td>{{ $mov->created_at->format('Y-m-d h:i A') }}</td>
                                <td class="fw-bold text-dark">{{ $mov->item->name ?? '-' }}</td>
                                <td>
                                    @if($mov->movement_type == 'direct_purchase')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">توريد مباشر</span>
                                    @elseif($mov->movement_type == 'hospital_transfer_in')
                                        <span class="badge bg-info-subtle text-info border border-info-subtle">استلام من المخزن الرئيسي</span>
                                    @elseif($mov->movement_type == 'surgery_dispense')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">صرف لعملية جراحية</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $mov->movement_type_arabic }}</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold {{ $mov->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $mov->quantity > 0 ? '+' . $mov->quantity : $mov->quantity }}
                                </td>
                                <td class="text-center fw-bold">{{ $mov->balance_after }}</td>
                                <td>
                                    <div>{{ $mov->notes ?? '-' }}</div>
                                    @if($mov->patient)
                                        <div class="small text-primary">المريض: {{ $mov->patient->name }}</div>
                                    @endif
                                </td>
                                <td>{{ $mov->creator->name ?? 'النظام' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">لا توجد حركات مسجلة مؤخراً</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال تعريف صنف جديد -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>تعريف صنف جديد في مخزن العيون</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('eye.store.items.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">كود الصنف <span class="text-danger">*</span></label>
                            <input type="text" name="item_code" class="form-control font-monospace" placeholder="مثال: IOL-ALC-220" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">تصنيف الصنف <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" id="itemCategorySelect" required>
                                <option value="iol_lens">عدسات داخل العين (IOL)</option>
                                <option value="retinal_injection">إبر حقن الشبكية</option>
                                <option value="viscoelastic">محاليل لزجة جراحية</option>
                                <option value="surgical_blade">شفرات جراحية دقيقة</option>
                                <option value="suture">خيوط جراحة العيون</option>
                                <option value="general_consumable" selected>مستهلكات عامة</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label fw-bold">اسم المستلزم / العدسة <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="مثال: Alcon AcrySof IQ Monofocal IOL" required>
                        </div>
                        <div class="col-md-4" id="diopterCol">
                            <label class="form-label fw-bold">قوة العدسة (Diopter)</label>
                            <input type="number" name="diopter" class="form-control" placeholder="مثال: 21.50" step="0.25" min="-10" max="40">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">الموديل / المواصفة</label>
                            <input type="text" name="model_number" class="form-control" placeholder="مثال: SN60WF / 2.2mm">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">الشركة المصنعة</label>
                            <input type="text" name="manufacturer" class="form-control" placeholder="مثال: Alcon / Zeiss">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">وحدة القياس</label>
                            <input type="text" name="unit" class="form-control" value="قطعة" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">الرصيد الافتتاحي</label>
                            <input type="number" name="current_stock" class="form-control" value="0" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">حد تنبيه النفاد</label>
                            <input type="number" name="min_stock_alert" class="form-control" value="5" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">سعر التكلفة (د.ع)</label>
                            <input type="number" name="cost_price" class="form-control" value="0" min="0" step="500" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">سعر البيع / التسعيرة (د.ع)</label>
                            <input type="number" name="selling_price" class="form-control" value="0" min="0" step="500" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success fw-bold">حفظ الصنف في المخزن</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال التوريد المباشر -->
<div class="modal fade" id="directPurchaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-truck me-2"></i>توريد وشراء مباشر خاص بالعيون</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('eye.store.directPurchase') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">الصنف المطلوب توريده <span class="text-danger">*</span></label>
                        <select name="eye_store_item_id" id="directPurchaseItemSelect" class="form-select" required>
                            @foreach($items as $i)
                                <option value="{{ $i->id }}">{{ $i->name }} (الرصيد الحالي: {{ $i->current_stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">الكمية الموردة <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" value="10" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">سعر التكلفة الجديد (د.ع)</label>
                            <input type="number" name="cost_price" id="directPurchaseCostPrice" class="form-control" placeholder="اختياري">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">رقم الوجبة / التشغيلة (Batch/Lot)</label>
                            <input type="text" name="batch_number" class="form-control" placeholder="اختياري">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">تاريخ انتهاء الصلاحية</label>
                            <input type="date" name="expiry_date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">ملاحظات التوريد أو اسم المجهز</label>
                        <input type="text" name="notes" class="form-control" placeholder="مثال: توريد شحنة عدسات من الوكيل المعتمد">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold">تأكيد التوريد وإضافة الرصيد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال طلب تجهيز من المخزن الرئيسي -->
<div class="modal fade" id="requisitionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-dolly-flatbed me-2"></i>إنشاء طلب تجهيز من المخزن الرئيسي للمستشفى</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('eye.store.transferRequisition') }}" method="POST" id="requisitionForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small">
                        <i class="fas fa-info-circle me-1"></i>سيتم إرسال هذا الطلب إلى المخزن الرئيسي لتجهيز المستلزمات الطبية ونقلها كعهدة لمخزن العيون.
                    </div>

                    <div id="requisitionItemsContainer">
                        <div class="row g-2 mb-2 requisition-row">
                            <div class="col-6">
                                <label class="form-label small fw-bold">اسم المستلزم المطلوب</label>
                                <input type="text" class="form-control form-control-sm req-name" placeholder="مثال: شاش معقم للعيون / كانيولا 24G / محاليل Saline" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label small fw-bold">الكمية</label>
                                <input type="number" class="form-control form-control-sm req-qty" value="50" min="1" required>
                            </div>
                            <div class="col-3">
                                <label class="form-label small fw-bold">الوحدة</label>
                                <input type="text" class="form-control form-control-sm req-unit" value="قطعة" required>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="btnAddReqRow">
                        <i class="fas fa-plus me-1"></i>إضافة صنف آخر للطلب
                    </button>

                    <input type="hidden" name="items_data" id="reqItemsDataInput">

                    <div class="mt-3">
                        <label class="form-label fw-bold">ملاحظات إضافية للمخزن الرئيسي</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="أي تفاصيل تخص أولوية التجهيز..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info text-white fw-bold">إرسال الطلب للمخزن العام</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openDirectPurchaseModal(itemId, itemName, costPrice) {
    const select = document.getElementById('directPurchaseItemSelect');
    if (select) select.value = itemId;
    const costInput = document.getElementById('directPurchaseCostPrice');
    if (costInput && costPrice) costInput.value = costPrice;
    const modal = new bootstrap.Modal(document.getElementById('directPurchaseModal'));
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('requisitionItemsContainer');
    const btnAdd = document.getElementById('btnAddReqRow');
    const form = document.getElementById('requisitionForm');
    const dataInput = document.getElementById('reqItemsDataInput');

    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 requisition-row';
            row.innerHTML = `
                <div class="col-6">
                    <input type="text" class="form-control form-control-sm req-name" placeholder="اسم المستلزم المطلوب" required>
                </div>
                <div class="col-3">
                    <input type="number" class="form-control form-control-sm req-qty" value="10" min="1" required>
                </div>
                <div class="col-3 d-flex gap-1">
                    <input type="text" class="form-control form-control-sm req-unit" value="قطعة" required>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.requisition-row').remove()">&times;</button>
                </div>
            `;
            container.appendChild(row);
        });
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            const rows = container.querySelectorAll('.requisition-row');
            const items = [];
            rows.forEach(r => {
                const name = r.querySelector('.req-name').value.trim();
                const qty = parseInt(r.querySelector('.req-qty').value) || 1;
                const unit = r.querySelector('.req-unit').value.trim() || 'قطعة';
                if (name) {
                    items.push({ name: name, quantity: qty, unit: unit });
                }
            });

            if (items.length === 0) {
                e.preventDefault();
                alert('يرجى كتابة صنف واحد على الأقل في الطلب');
                return;
            }

            dataInput.value = JSON.stringify(items);
        });
    }
});
</script>
@endpush
@endsection
