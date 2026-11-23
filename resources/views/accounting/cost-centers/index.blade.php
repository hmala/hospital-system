@extends('accounting.layout')

@section('title', 'شجرة مراكز التكلفة وربحية أقسام المستشفى')

@section('accounting_content')
<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 fs-3">
                <i class="fas fa-layer-group"></i>
            </div>
            <div>
                <span class="text-muted small d-block">إجمالي مراكز التكلفة</span>
                <span class="fs-4 fw-bold text-dark">{{ $stats['total'] }} مركزاً</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-4 fs-3">
                <i class="fas fa-procedures"></i>
            </div>
            <div>
                <span class="text-muted small d-block">مراكز الإنتاج والإيراد الطبي</span>
                <span class="fs-4 fw-bold text-success">{{ $stats['operational'] }} مراكز</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-10 text-info p-3 rounded-4 fs-3">
                <i class="fas fa-microscope"></i>
            </div>
            <div>
                <span class="text-muted small d-block">المختبر والأشعة والتشخيص</span>
                <span class="fs-4 fw-bold text-info">{{ $stats['diagnostic'] }} مراكز</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-4 fs-3">
                <i class="fas fa-boxes"></i>
            </div>
            <div>
                <span class="text-muted small d-block">المخازن والصيدلية والتموين</span>
                <span class="fs-4 fw-bold text-dark">{{ $stats['inventory'] }} مراكز</span>
            </div>
        </div>
    </div>
</div>

<!-- Cost Centers Container -->
<div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h5 class="fw-bold text-dark mb-1">شجرة مراكز التكلفة الميدانية للمستشفى</h5>
            <p class="text-muted small mb-0">توزيع المصاريف والإيرادات لحساب أرباح وخسائر كل قسم وصالة رقود بدقة متناهية</p>
        </div>

        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm rounded-3 px-3 shadow-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newCostCenterModal">
                <i class="fas fa-plus-circle"></i>
                <span>+ إضافة مركز تكلفة جديد</span>
            </button>
        </div>
    </div>

    <!-- Clusters Accordion -->
    <div class="row g-3">
        @foreach($rootCenters as $root)
            <div class="col-12">
                <div class="border rounded-4 overflow-hidden shadow-sm">
                    <!-- Cluster Header -->
                    <div class="bg-light p-3 d-flex justify-content-between align-items-center border-bottom cursor-pointer" 
                         data-bs-toggle="collapse" 
                         data-bs-target="#cluster-{{ $root->id }}">
                        <div class="d-flex align-items-center gap-3">
                            <span class="badge bg-primary fs-6 px-3 py-1 font-monospace">{{ $root->code }}</span>
                            <span class="fw-bold text-dark fs-6">{{ $root->name }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-dark border">{{ $root->children->count() }} فروع رئيسية</span>
                            <i class="fas fa-chevron-down text-muted"></i>
                        </div>
                    </div>

                    <!-- Cluster Body / Children -->
                    <div class="collapse show p-3 bg-white" id="cluster-{{ $root->id }}">
                        <div class="row g-3">
                            @foreach($root->children as $child)
                                <div class="col-md-6 col-lg-4">
                                    <div class="border rounded-3 p-3 bg-slate-50 h-100 hover-shadow transition">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge bg-secondary font-monospace">{{ $child->code }}</span>
                                            <span class="badge bg-success-subtle text-success small">مستوى {{ $child->level }}</span>
                                        </div>
                                        <h6 class="fw-bold text-dark mb-2">{{ $child->name }}</h6>

                                        @if($child->children->count() > 0)
                                            <div class="ps-2 border-start border-2 border-primary border-opacity-50 mt-2">
                                                <small class="text-muted d-block mb-1">المحطات الفرعية التابعة:</small>
                                                @foreach($child->children as $sub)
                                                    <div class="d-flex justify-content-between align-items-center small py-1 border-bottom border-light">
                                                        <span class="text-dark">{{ $sub->name }}</span>
                                                        <span class="font-monospace text-muted small">{{ $sub->code }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal: Add New Cost Center -->
<div class="modal fade" id="newCostCenterModal" tabindex="-1" aria-labelledby="newCostCenterModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('accounting.cost-centers.store') }}" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="newCostCenterModalLabel">
                        <i class="fas fa-plus-circle text-primary me-1"></i> إضافة مركز تكلفة جديد
                    </h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">المركز الأب (العنقود الرئيسي):</label>
                        <select name="parent_id" class="form-select">
                            <option value="">-- كمركز رئيسي مستقل (مستوى 1) --</option>
                            @foreach($rootCenters as $r)
                                <option value="{{ $r->id }}">{{ $r->code }} - {{ $r->name }}</option>
                                @foreach($r->children as $c)
                                    <option value="{{ $c->id }}">&nbsp;&nbsp;&nbsp;↳ {{ $c->code }} - {{ $c->name }}</option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">كود مركز التكلفة:</label>
                        <input type="text" name="code" class="form-control font-monospace" placeholder="مثلاً: 114" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">اسم مركز التكلفة:</label>
                        <input type="text" name="name" class="form-control" placeholder="مثلاً: صالة عمليات جراحة القلب المفتوح" required>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">حفظ مركز التكلفة</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
