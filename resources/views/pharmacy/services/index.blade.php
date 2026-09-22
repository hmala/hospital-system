@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- عنوان الصفحة وزر الإضافة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="h4 fw-bold text-primary mb-1">
                <i class="fas fa-syringe me-2"></i>الخدمات الصيدلانية غير المخزنية
            </h2>
            <p class="text-muted small mb-0">إدارة الخدمات السريرية السريعة المقدمة داخل الصيدلية (ضرب إبر، قياس ضغط، قياس سكر) بدون التأثير على المخزون</p>
        </div>
        <div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                <i class="fas fa-plus-circle me-1"></i> إضافة خدمة صيدلانية جديدة
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
        </div>
    @endif

    <!-- جدول الخدمات -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-muted small">
                            <th>#</th>
                            <th>اسم الخدمة</th>
                            <th>سعر البيع نقداً (كاش)</th>
                            <th>سعر الضمان الصحي</th>
                            <th>سعر وزارة الداخلية</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                            <th class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($services as $service)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="fw-bold text-dark">{{ $service->name }}</td>
                                <td><span class="fw-bold text-success">{{ number_format($service->price) }} د.ع</span></td>
                                <td>
                                    @if($service->hi_price)
                                        <span class="text-primary">{{ number_format($service->hi_price) }} د.ع</span>
                                    @else
                                        <span class="text-muted small">كاش ({{ number_format($service->price) }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($service->moi_price)
                                        <span class="text-secondary">{{ number_format($service->moi_price) }} د.ع</span>
                                    @else
                                        <span class="text-muted small">كاش ({{ number_format($service->price) }})</span>
                                    @endif
                                </td>
                                <td>
                                    @if($service->is_active)
                                        <span class="badge bg-success">مفعلة</span>
                                    @else
                                        <span class="badge bg-secondary">معطلة</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $service->notes ?? '-' }}</small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form action="{{ route('pharmacy.services.toggle', $service->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-warning" title="{{ $service->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                <i class="fas fa-power-off"></i>
                                            </button>
                                        </form>
                                        <form action="{{ route('pharmacy.services.destroy', $service->id) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الخدمة؟')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal تعديل الخدمة -->
                            <div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <form action="{{ route('pharmacy.services.update', $service->id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-content border-0 shadow">
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>تعديل الخدمة الصيدلانية</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">اسم الخدمة <span class="text-danger">*</span></label>
                                                    <input type="text" name="name" class="form-control" value="{{ $service->name }}" required>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">سعر الكاش <span class="text-danger">*</span></label>
                                                        <input type="number" step="0.01" name="price" class="form-control" value="{{ $service->price }}" required>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">سعر الضمان</label>
                                                        <input type="number" step="0.01" name="hi_price" class="form-control" value="{{ $service->hi_price }}">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label small fw-bold">سعر الداخلية</label>
                                                        <input type="number" step="0.01" name="moi_price" class="form-control" value="{{ $service->moi_price }}">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-bold">ملاحظات</label>
                                                    <textarea name="notes" class="form-control" rows="2">{{ $service->notes }}</textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> حفظ التعديلات</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fas fa-syringe fa-3x mb-3 text-secondary opacity-50"></i>
                                    <p class="mb-2">لا توجد خدمات صيدلانية مسجلة حتى الآن.</p>
                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                                        <i class="fas fa-plus-circle me-1"></i> إضافة خدمة جديدة الآن
                                    </button>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal إضافة خدمة جديدة -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('pharmacy.services.store') }}" method="POST">
            @csrf
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2 text-primary"></i>إضافة خدمة صيدلانية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">اسم الخدمة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: ضرب إبرة عضلية، قياس ضغط، غيار جروح" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">سعر الكاش <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="2000" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">سعر الضمان</label>
                            <input type="number" step="0.01" name="hi_price" class="form-control" placeholder="اختياري" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">سعر الداخلية</label>
                            <input type="number" step="0.01" name="moi_price" class="form-control" placeholder="اختياري" min="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ملاحظات</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="ملاحظات أو اشتراطات تقديم الخدمة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> حفظ الخدمة</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
