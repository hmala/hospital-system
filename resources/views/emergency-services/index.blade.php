@extends('layouts.app')

@section('styles')
<style>
    .table-services {
        width: 100%;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table-services thead th {
        background-color: #f8fafc !important;
        color: #334155 !important;
        font-weight: 700 !important;
        font-size: 0.85rem !important;
        padding: 12px 14px !important;
        border-bottom: 2px solid #e2e8f0 !important;
        white-space: nowrap !important;
    }
    .table-services tbody td {
        padding: 12px 14px !important;
        vertical-align: middle !important;
        font-size: 0.9rem !important;
        border-bottom: 1px solid #f1f5f9 !important;
        color: #1e293b !important;
    }
    .table-services tbody tr:hover td {
        background-color: #f8fafc !important;
    }
    .status-badge-active {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
    }
    .status-badge-inactive {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.78rem;
        display: inline-flex;
        align-items: center;
    }
</style>
@endsection

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-lg" style="background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); border-radius: 20px;">
                <div class="card-body p-4 text-white">
                    <div class="d-flex justify-content-between align-items-center flex-column flex-md-row gap-3">
                        <div>
                            <h2 class="mb-1 fw-bold">
                                <i class="fas fa-hand-holding-medical me-3"></i>إدارة خدمات الطوارئ
                            </h2>
                            <p class="mb-0 opacity-75">إضافة وتعديل أسعار وتصنيفات الخدمات التمريضية والطبية في قسم الطوارئ</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light text-danger fw-bold px-4 py-2 rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#createServiceModal">
                                <i class="fas fa-plus me-2"></i>إضافة خدمة جديدة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-danger bg-opacity-10 p-3 me-3 text-danger">
                        <i class="fas fa-list-ul fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">إجمالي الخدمات</div>
                        <div class="fs-4 fw-bold text-dark">{{ $stats['total'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">الخدمات المفعلة</div>
                        <div class="fs-4 fw-bold text-success">{{ $stats['active'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                <div class="d-flex align-items-center">
                    <div class="rounded-circle bg-secondary bg-opacity-10 p-3 me-3 text-secondary">
                        <i class="fas fa-pause-circle fa-2x"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold">الخدمات المعطلة</div>
                        <div class="fs-4 fw-bold text-secondary">{{ $stats['inactive'] }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    @if(session('success'))
        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
        </div>
    @endif

    <!-- Search & Filter Card -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <form action="{{ route('emergency-services.index') }}" method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control bg-light border-0" placeholder="بحث باسم الخدمة أو التصنيف..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select bg-light border-0">
                        <option value="">كل التصنيفات</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select bg-light border-0">
                        <option value="">كل الحالات</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>مفعلة</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطلة</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-danger w-100 rounded-pill fw-bold">
                        <i class="fas fa-filter me-1"></i>فلترة
                    </button>
                    @if(request()->hasAny(['search', 'category', 'status']))
                        <a href="{{ route('emergency-services.index') }}" class="btn btn-outline-secondary rounded-pill" title="إعادة تعيين">
                            <i class="fas fa-undo"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Services Table -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark fw-bold">
                <i class="fas fa-list me-2 text-danger"></i>قائمة خدمات الطوارئ
            </h5>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                {{ $services->total() }} خدمة
            </span>
        </div>
        <div class="card-body p-0">
            @if($services->count() > 0)
            <div class="table-responsive w-100 m-0">
                <table class="table table-hover table-services align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>اسم الخدمة</th>
                            <th>التصنيف</th>
                            <th>السعر (د.ع)</th>
                            <th>الحالة</th>
                            <th class="text-center" style="width: 160px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($services as $service)
                        <tr>
                            <td class="text-muted fw-bold">#{{ $service->id }}</td>
                            <td>
                                <div class="fw-bold text-dark">{{ $service->name }}</div>
                            </td>
                            <td>
                                @if($service->category)
                                    <span class="badge bg-light text-secondary border">{{ $service->category }}</span>
                                @else
                                    <span class="text-muted small">عام</span>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-success">
                                    {{ number_format($service->price, 0) }} د.ع
                                </div>
                            </td>
                            <td>
                                @if($service->is_active)
                                    <span class="status-badge-active"><i class="fas fa-check-circle me-1"></i>مفعلة</span>
                                @else
                                    <span class="status-badge-inactive"><i class="fas fa-times-circle me-1"></i>معطلة</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center align-items-center">
                                    <!-- Edit Button -->
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark px-2" title="تعديل الخدمة" data-bs-toggle="modal" data-bs-target="#editServiceModal{{ $service->id }}">
                                        <i class="fas fa-edit text-warning"></i>
                                    </button>

                                    <!-- Toggle Status Button -->
                                    <form action="{{ route('emergency-services.toggle', $service) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm {{ $service->is_active ? 'btn-outline-secondary' : 'btn-outline-success' }} px-2" title="{{ $service->is_active ? 'تعطيل الخدمة' : 'تفعيل الخدمة' }}">
                                            <i class="fas {{ $service->is_active ? 'fa-pause' : 'fa-play' }}"></i>
                                        </button>
                                    </form>

                                    <!-- Delete Button -->
                                    <form action="{{ route('emergency-services.destroy', $service) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف خدمة {{ $service->name }}؟');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2" title="حذف الخدمة">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($services->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $services->links() }}
                </div>
            @endif

            @else
            <div class="text-center py-5">
                <div class="rounded-circle bg-light d-inline-flex p-4 mb-3 text-muted">
                    <i class="fas fa-hand-holding-medical fa-3x"></i>
                </div>
                <h5 class="text-muted fw-bold">لا توجد خدمات طوارئ مطابقة</h5>
                <p class="text-muted small">يمكنك إضافة خدمات جديدة باستخدام زر الإضافة بالأعلى</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('modals')
<!-- Create Service Modal -->
<div class="modal fade" id="createServiceModal" tabindex="-1" aria-labelledby="createServiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('emergency-services.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="createServiceModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>إضافة خدمة طوارئ جديدة
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">اسم الخدمة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="مثال: غسل معدة، سحب دم..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">السعر (د.ع) <span class="text-danger">*</span></label>
                        <input type="number" step="1000" name="price" class="form-control rounded-3" placeholder="مثال: 25000" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">التصنيف (اختياري)</label>
                        <input type="text" name="category" class="form-control rounded-3" placeholder="مثال: تمريض، جراحة صغرى، تنفسية...">
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="createIsActiveSwitch" checked>
                        <label class="form-check-label fw-semibold" for="createIsActiveSwitch">تفعيل الخدمة فوراً</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">إضافة الخدمة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modals -->
@foreach($services as $service)
<div class="modal fade" id="editServiceModal{{ $service->id }}" tabindex="-1" aria-labelledby="editServiceModalLabel{{ $service->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <form action="{{ route('emergency-services.update', $service) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-warning text-dark border-0 py-3 px-4 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="editServiceModalLabel{{ $service->id }}">
                        <i class="fas fa-edit me-2"></i>تعديل خدمة الطوارئ
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">اسم الخدمة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" value="{{ $service->name }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">السعر (د.ع) <span class="text-danger">*</span></label>
                        <input type="number" step="1000" name="price" class="form-control rounded-3" value="{{ (int)$service->price }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">التصنيف (اختياري)</label>
                        <input type="text" name="category" class="form-control rounded-3" value="{{ $service->category }}" placeholder="مثال: تمريض، جراحة صغرى، تنفسية...">
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActiveSwitch{{ $service->id }}" {{ $service->is_active ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="isActiveSwitch{{ $service->id }}">تفعيل الخدمة</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endpush
