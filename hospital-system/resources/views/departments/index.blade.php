<!-- resources/views/departments/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-clinic-medical me-2"></i>إدارة العيادات</h2>
                <a href="{{ route('departments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة عيادة جديدة
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

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>اسم العيادة</th>
                                    <th>النوع</th>
                                    <th>رقم الغرفة</th>
                                    <th>أجر الكشف</th>
                                    <th>أوقات العمل</th>
                                    <th>مواعيد اليوم</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($departments as $department)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $department->name }}</strong></td>
                                    <td><span class="badge bg-info">{{ $department->getTypeText() }}</span></td>
                                    <td>{{ $department->room_number }}</td>
                                    <td><span class="text-success">{{ number_format($department->consultation_fee) }} د.ع</span></td>
                                    <td><small>{{ $department->working_hours_start ? $department->working_hours_start->format('h:i A') : 'غير محدد' }} - {{ $department->working_hours_end ? $department->working_hours_end->format('h:i A') : 'غير محدد' }}</small></td>
                                    <td><span class="badge bg-primary">{{ $department->today_appointments_count }}</span></td>
                                    <td>@if($department->is_active)<span class="badge bg-success">نشط</span>@else<span class="badge bg-danger">غير نشط</span>@endif</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('departments.show', $department) }}" class="btn btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('departments.edit', $department) }}" class="btn btn-warning" title="تعديل"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('departments.destroy', $department) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف العيادة؟')"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="fas fa-clinic-medical fa-3x mb-3"></i><br>لا توجد عيادات مضافة حتى الآن
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">{{ $departments->links() }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection