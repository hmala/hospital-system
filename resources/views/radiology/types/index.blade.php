<!-- resources/views/radiology/types/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-cogs me-2"></i>إدارة أنواع الإشعة</h2>
                <a href="{{ route('radiology.types.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة نوع جديد
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- الفلاتر -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                    <h6 class="mb-0"><i class="fas fa-filter me-2"></i>خيارات الفلترة والبحث</h6>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['search', 'is_active', 'requires_contrast', 'requires_preparation', 'min_price', 'max_price']) ? 'show' : '' }}" id="filterCollapse">
                    <div class="card-body">
                        <form method="GET" action="{{ route('radiology.types.index') }}" id="filterForm">
                            <div class="row g-3">
                                <!-- البحث -->
                                <div class="col-md-4">
                                    <label class="form-label">البحث</label>
                                    <input type="text" name="search" class="form-control" placeholder="ابحث بالاسم أو الكود..." value="{{ request('search') }}">
                                </div>

                                <!-- التصنيف الرئيسي -->
                                <div class="col-md-3">
                                    <label class="form-label">التصنيف الرئيسي</label>
                                    <select name="main_category" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach($mainCategories as $cat)
                                            <option value="{{ $cat }}" {{ request('main_category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- التصنيف الفرعي -->
                                <div class="col-md-3">
                                    <label class="form-label">التصنيف الفرعي</label>
                                    <select name="subcategory" class="form-select">
                                        <option value="">الكل</option>
                                        @foreach($subcategories as $sub)
                                            <option value="{{ $sub }}" {{ request('subcategory') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- الحالة -->
                                <div class="col-md-2">
                                    <label class="form-label">الحالة</label>
                                    <select name="is_active" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>نشط</option>
                                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>معطل</option>
                                    </select>
                                </div>

                                <!-- مادة تباين -->
                                <div class="col-md-2">
                                    <label class="form-label">مادة تباين</label>
                                    <select name="requires_contrast" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="1" {{ request('requires_contrast') === '1' ? 'selected' : '' }}>يتطلب</option>
                                        <option value="0" {{ request('requires_contrast') === '0' ? 'selected' : '' }}>لا يتطلب</option>
                                    </select>
                                </div>

                                <!-- تحضير -->
                                <div class="col-md-2">
                                    <label class="form-label">تحضير</label>
                                    <select name="requires_preparation" class="form-select">
                                        <option value="">الكل</option>
                                        <option value="1" {{ request('requires_preparation') === '1' ? 'selected' : '' }}>يتطلب</option>
                                        <option value="0" {{ request('requires_preparation') === '0' ? 'selected' : '' }}>لا يتطلب</option>
                                    </select>
                                </div>

                                <!-- الترتيب -->
                                <div class="col-md-2">
                                    <label class="form-label">الترتيب حسب</label>
                                    <select name="sort_by" class="form-select">
                                        <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>الاسم</option>
                                        <option value="code" {{ request('sort_by') === 'code' ? 'selected' : '' }}>الكود</option>
                                        <option value="base_price" {{ request('sort_by') === 'base_price' ? 'selected' : '' }}>السعر</option>
                                        <option value="estimated_duration" {{ request('sort_by') === 'estimated_duration' ? 'selected' : '' }}>المدة</option>
                                        <option value="created_at" {{ request('sort_by') === 'created_at' ? 'selected' : '' }}>تاريخ الإضافة</option>
                                    </select>
                                </div>

                                <!-- نطاق السعر -->
                                <div class="col-md-3">
                                    <label class="form-label">السعر الأدنى (د.ع)</label>
                                    <input type="number" name="min_price" class="form-control" placeholder="من" value="{{ request('min_price') }}" min="0">
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">السعر الأعلى (د.ع)</label>
                                    <input type="number" name="max_price" class="form-control" placeholder="إلى" value="{{ request('max_price') }}" min="0">
                                </div>

                                <!-- اتجاه الترتيب -->
                                <div class="col-md-2">
                                    <label class="form-label">الاتجاه</label>
                                    <select name="sort_dir" class="form-select">
                                        <option value="asc" {{ request('sort_dir') === 'asc' ? 'selected' : '' }}>تصاعدي</option>
                                        <option value="desc" {{ request('sort_dir') === 'desc' ? 'selected' : '' }}>تنازلي</option>
                                    </select>
                                </div>

                                <!-- الأزرار -->
                                <div class="col-md-4">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-2"></i>بحث
                                    </button>
                                    <a href="{{ route('radiology.types.index') }}" class="btn btn-secondary">
                                        <i class="fas fa-redo me-2"></i>إعادة تعيين
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عداد النتائج -->
    @if(request()->hasAny(['search', 'is_active', 'requires_contrast', 'requires_preparation', 'min_price', 'max_price']))
    <div class="row mb-2">
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                تم العثور على <strong>{{ $types->total() }}</strong> نتيجة
                @if(request('search'))
                    للبحث عن: <strong>"{{ request('search') }}"</strong>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- جدول أنواع الإشعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">أنواع الإشعة المتاحة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>التصنيف الرئيسي</th>
                                    <th>التصنيف الفرعي</th>
                                    <th>الاسم</th>
                                    <th>الرمز</th>
                                    <th>السعر الأساسي</th>
                                    <th>المدة المقدرة</th>
                                    <th>مادة تباين</th>
                                    <th>تحضير</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($types as $type)
                                <tr>
                                    <td>
                                        @if($type->main_category)
                                            <span class="badge bg-primary">{{ $type->main_category }}</span>
                                        @else
                                            <span class="badge bg-secondary">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type->subcategory)
                                            <span class="badge bg-info">{{ $type->subcategory }}</span>
                                        @else
                                            <span class="badge bg-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ $type->name }}</strong>
                                        @if($type->description)
                                        <br><small class="text-muted">{{ Str::limit($type->description, 50) }}</small>
                                        @endif
                                    </td>
                                    <td><code>{{ $type->code }}</code></td>
                                    <td><span class="text-success">{{ number_format($type->base_price) }} د.ع</span></td>
                                    <td>{{ $type->estimated_duration }} دقيقة</td>
                                    <td>
                                        @if($type->requires_contrast)
                                            <span class="badge bg-warning">نعم</span>
                                        @else
                                            <span class="badge bg-secondary">لا</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type->requires_preparation)
                                            <span class="badge bg-info">نعم</span>
                                        @else
                                            <span class="badge bg-secondary">لا</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($type->is_active)
                                            <span class="badge bg-success">نشط</span>
                                        @else
                                            <span class="badge bg-danger">معطل</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('radiology.types.show', $type) }}" class="btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('radiology.types.edit', $type) }}" class="btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('radiology.types.toggle', $type) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn {{ $type->is_active ? 'btn-secondary' : 'btn-success' }}" title="{{ $type->is_active ? 'تعطيل' : 'تفعيل' }}">
                                                    <i class="fas {{ $type->is_active ? 'fa-ban' : 'fa-check' }}"></i>
                                                </button>
                                            </form>
                                            @if($type->requests()->count() == 0)
                                            <form action="{{ route('radiology.types.destroy', $type) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا النوع؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-cogs fa-3x mb-3"></i><br>لا توجد أنواع إشعة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $types->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection