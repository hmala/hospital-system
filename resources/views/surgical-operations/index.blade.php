@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-procedures me-2 text-primary"></i>
                        إدارة أجور العمليات الجراحية
                    </h2>
                    <p class="text-muted">تحديث وإدارة أسعار العمليات الجراحية</p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-times-circle me-2"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">إجمالي العمليات</p>
                            <h3 class="mb-0 text-primary">{{ $operations->count() }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="fas fa-procedures fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">لها أجور محددة</p>
                            <h3 class="mb-0 text-success">{{ $operations->where('fee', '>', 0)->count() }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="fas fa-check fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">بدون أجور</p>
                            <h3 class="mb-0 text-warning">{{ $operations->where('fee', 0)->count() }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1">عدد الأصناف</p>
                            <h3 class="mb-0 text-info">{{ $operations->unique('category')->count() }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 p-3 rounded">
                            <i class="fas fa-folder fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة العمليات مجمعة حسب الصنف -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    قائمة العمليات الجراحية
                </h5>
                <div>
                    @if($canEdit ?? false)
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                        <i class="fas fa-edit me-1"></i>
                        تحديث جماعي
                    </button>
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            @php
                $operationsByCategory = $operations->groupBy('category');
            @endphp

            <div class="accordion" id="operationsAccordion">
                @foreach($operationsByCategory as $category => $categoryOperations)
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading{{ $loop->index }}">
                        <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse{{ $loop->index }}" 
                                aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                <div>
                                    <i class="fas fa-folder-open text-primary me-2"></i>
                                    <strong>{{ $category }}</strong>
                                </div>
                                <div>
                                    <span class="badge bg-primary me-2">{{ $categoryOperations->count() }} عملية</span>
                                    <span class="badge bg-success me-2">{{ $categoryOperations->where('fee', '>', 0)->count() }} مسعرة</span>
                                    @if($categoryOperations->where('fee', 0)->count() > 0)
                                        <span class="badge bg-warning">{{ $categoryOperations->where('fee', 0)->count() }} بدون سعر</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                    </h2>
                    <div id="collapse{{ $loop->index }}" 
                         class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                         data-bs-parent="#operationsAccordion">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>اسم العملية</th>
                                            <th style="width: 200px;">الأجر الحالي (د.ع)</th>
                                            <th style="width: 150px;" class="text-center">الحالة</th>
                                            <th style="width: 150px;" class="text-center">الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($categoryOperations as $operation)
                                        <tr>
                                            <td>{{ $operation->id }}</td>
                                            <td>
                                                <strong>{{ $operation->name }}</strong>
                                                @if($operation->is_active)
                                                    <span class="badge bg-success ms-2">نشط</span>
                                                @else
                                                    <span class="badge bg-secondary ms-2">غير نشط</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($canEdit ?? false)
                                                <form action="{{ route('surgical-operations.update-fee', $operation->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" 
                                                               name="fee" 
                                                               class="form-control" 
                                                               value="{{ $operation->fee }}" 
                                                               min="0" 
                                                               step="1000"
                                                               required>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save"></i>
                                                        </button>
                                                    </div>
                                                </form>
                                                @else
                                                <div class="fw-bold text-primary">
                                                    {{ number_format($operation->fee, 0) }} د.ع
                                                </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($operation->fee > 0)
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>
                                                        محدد
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                                        غير محدد
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <button type="button" 
                                                        class="btn btn-sm btn-outline-primary" 
                                                        onclick="quickUpdate({{ $operation->id }}, '{{ $operation->name }}', {{ $operation->fee }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- Modal تحديث سريع -->
<div class="modal fade" id="quickUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    تحديث أجر العملية
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickUpdateForm" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">اسم العملية</label>
                        <input type="text" class="form-control" id="operationName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">الأجر الجديد (دينار عراقي)</label>
                        <input type="number" 
                               name="fee" 
                               id="operationFee" 
                               class="form-control form-control-lg" 
                               min="0" 
                               step="1000" 
                               required>
                        <small class="text-muted">يمكن استخدام مضاعفات 1000 (مثال: 50000، 100000، 500000)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>
                        إلغاء
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>
                        حفظ التحديث
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal تحديث جماعي -->
<div class="modal fade" id="bulkUpdateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>
                    تحديث جماعي للأجور
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    يمكنك تحديد نسبة أو قيمة ثابتة لتطبيقها على جميع العمليات أو صنف معين
                </p>
                <form action="{{ route('surgical-operations.bulk-update') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">الصنف (اختياري)</label>
                            <select name="category" class="form-select">
                                <option value="">جميع الأصناف</option>
                                @foreach($operationsByCategory as $category => $ops)
                                    <option value="{{ $category }}">{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">نوع التحديث</label>
                            <select name="update_type" class="form-select" required>
                                <option value="set">تعيين قيمة ثابتة</option>
                                <option value="increase">زيادة بنسبة مئوية</option>
                                <option value="decrease">تخفيض بنسبة مئوية</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label fw-bold">القيمة</label>
                            <input type="number" name="value" class="form-control" min="0" required>
                            <small class="text-muted">للقيمة الثابتة: أدخل المبلغ بالدينار، للنسبة المئوية: أدخل الرقم (مثال: 10 لـ 10%)</small>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check me-1"></i>
                            تطبيق التحديث
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    function quickUpdate(id, name, currentFee) {
        document.getElementById('operationName').value = name;
        document.getElementById('operationFee').value = currentFee;
        document.getElementById('quickUpdateForm').action = '/surgical-operations/' + id + '/update-fee';
        
        var modal = new bootstrap.Modal(document.getElementById('quickUpdateModal'));
        modal.show();
    }
</script>
@endsection
