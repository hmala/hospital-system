@extends('layouts.app')

@section('title', 'إدارة رموز ICD10')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-book-medical me-2"></i>
                        إدارة رموز ICD10
                    </h4>
                    <div>
                        <a href="{{ route('icd10.create') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-1"></i>
                            إضافة رمز جديد
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <!-- فلترة البحث -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control"
                                       placeholder="البحث بالرمز أو الوصف..."
                                       value="{{ $search }}">
                                <select name="category" class="form-select">
                                    <option value="">جميع الفئات</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}" {{ $category == $cat ? 'selected' : '' }}>
                                            {{ $cat }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit" class="btn btn-outline-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                                @if($search || $category)
                                    <a href="{{ route('icd10.index') }}" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                @endif
                            </form>
                        </div>
                        <div class="col-md-4 text-end">
                            <small class="text-muted">
                                إجمالي الرموز: {{ $icd10Codes->total() }}
                            </small>
                        </div>
                    </div>

                    <!-- جدول الرموز -->
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>الرمز</th>
                                    <th>الوصف (إنجليزي)</th>
                                    <th>الوصف (عربي)</th>
                                    <th>الفئة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($icd10Codes as $code)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary fs-6">{{ $code->code }}</span>
                                        </td>
                                        <td>
                                            <strong>{{ Str::limit($code->description, 50) }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ Str::limit($code->description_ar, 50) }}</span>
                                        </td>
                                        <td>
                                            @if($code->category)
                                                <span class="badge bg-secondary">{{ $code->category }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="{{ route('icd10.show', $code) }}"
                                                   class="btn btn-outline-info"
                                                   title="عرض">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('icd10.edit', $code) }}"
                                                   class="btn btn-outline-warning"
                                                   title="تعديل">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('icd10.destroy', $code) }}"
                                                      method="POST"
                                                      class="d-inline"
                                                      onsubmit="return confirm('هل أنت متأكد من حذف هذا الرمز؟')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger"
                                                            title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                                <p>لا توجد رموز ICD10</p>
                                                @if($search || $category)
                                                    <a href="{{ route('icd10.index') }}" class="btn btn-sm btn-outline-primary">
                                                        إزالة الفلاتر
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- التنقل بين الصفحات -->
                    @if($icd10Codes->hasPages())
                        <div class="d-flex justify-content-center mt-4">
                            {{ $icd10Codes->appends(request()->query())->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table th {
    border-top: none;
    font-weight: 600;
}

.table-responsive {
    border-radius: 0.375rem;
}

.badge {
    font-size: 0.875em;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}
</style>
@endpush