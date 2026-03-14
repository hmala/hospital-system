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
                    <a href="{{ route('surgical-operations.create') }}" class="btn btn-sm btn-primary me-2">
                        <i class="fas fa-plus me-1"></i>
                        إضافة عملية جديدة
                    </a>
                    <a href="{{ route('surgical-operations.trashed') }}" class="btn btn-sm btn-warning me-2">
                        <i class="fas fa-trash me-1"></i>
                        المحذوفة
                    </a>
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
                                                <div class="fw-bold text-primary">
                                                    {{ number_format($operation->fee, 0) }} د.ع
                                                </div>
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
                                                @if($canEdit ?? false)
                                                <div class="btn-group" role="group">
                                                    <form action="{{ route('surgical-operations.destroy', $operation) }}" 
                                                          method="POST" 
                                                          class="d-inline"
                                                          onsubmit="return confirm('هل أنت متأكد من حذف العملية: {{ $operation->name }}؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" 
                                                                class="btn btn-sm btn-outline-danger" 
                                                                title="حذف العملية">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                                @else
                                                <span class="text-muted">لا توجد إجراءات</span>
                                                @endif
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

@endsection
