@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-flask me-2"></i>
                    إدارة الفحوصات المختبرية
                </h2>
                <a href="{{ route('lab-tests.create') }}" class="btn btn-success">
                    <i class="fas fa-plus me-1"></i>
                    إضافة فحص جديد
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

    <!-- فلاتر البحث -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>
                        فلترة الفحوصات
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('lab-tests.index') }}" class="row g-3">
                        <div class="col-md-4">
                            <label for="category" class="form-label">الفئة</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">جميع الفئات</option>
                                @foreach($categories as $key => $value)
                                    <option value="{{ $key }}" {{ request('category') == $key ? 'selected' : '' }}>
                                        {{ $value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">الحالة</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">جميع الحالات</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>نشط</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>غير نشط</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>
                                بحث
                            </button>
                            <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-1"></i>
                                إعادة تعيين
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- قائمة الفحوصات -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        الفحوصات المختبرية
                        @if(request('category') || request('status'))
                            <small class="text-white-50">(مفلترة)</small>
                        @endif
                    </h5>
                </div>
                <div class="card-body">
                    @if($labTests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>اسم الفحص</th>
                                        <th>الفئة</th>
                                        <th>الوصف</th>
                                        <th>الحالة</th>
                                        <th>تاريخ الإضافة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($labTests as $labTest)
                                    <tr>
                                        <td>
                                            <strong>{{ $labTest->name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">{{ $labTest->category_text }}</span>
                                        </td>
                                        <td>
                                            {{ Str::limit($labTest->description, 50) }}
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $labTest->status_color }}">
                                                {{ $labTest->status_text }}
                                            </span>
                                        </td>
                                        <td>{{ $labTest->created_at->format('Y-m-d') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('lab-tests.show', $labTest) }}"
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('lab-tests.edit', $labTest) }}"
                                                   class="btn btn-sm btn-outline-warning">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('lab-tests.toggle-status', $labTest) }}"
                                                      method="POST" class="d-inline">
                                                    @csrf
                                                    @method('POST')
                                                    <button type="submit" class="btn btn-sm btn-outline-{{ $labTest->is_active ? 'danger' : 'success' }}">
                                                        <i class="fas fa-{{ $labTest->is_active ? 'times' : 'check' }}"></i>
                                                    </button>
                                                </form>
                                                @if(Auth::user()->isAdmin())
                                                    <form action="{{ route('lab-tests.destroy', $labTest) }}"
                                                          method="POST" class="d-inline"
                                                          onsubmit="return confirm('هل أنت متأكد من حذف هذا الفحص؟')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-center mt-4">
                            {{ $labTests->appends(request()->query())->links('pagination::bootstrap-5') }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد فحوصات مختبرية</h5>
                            <p class="text-muted">
                                @if(request('category') || request('status'))
                                    لا توجد فحوصات تطابق معايير البحث المحددة
                                @else
                                    لم يتم إضافة أي فحوصات مختبرية بعد
                                @endif
                            </p>
                            <a href="{{ route('lab-tests.create') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-1"></i>
                                إضافة أول فحص
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection