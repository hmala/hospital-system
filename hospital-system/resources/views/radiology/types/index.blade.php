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
                                    <td colspan="8" class="text-center text-muted py-4">
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