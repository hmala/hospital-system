<!-- resources/views/doctors/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-user-md me-2"></i>إدارة الأطباء</h2>
                <a href="{{ route('doctors.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>إضافة طبيب جديد
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

    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <h6><i class="fas fa-info-circle me-2"></i>معلومات مهمة حول كلمات المرور</h6>
        <p class="mb-1">كلمة المرور الافتراضية للأطباء الجدد: <strong>password</strong></p>
        <p class="mb-0">يمكنك تغيير كلمة المرور من صفحة تعديل بيانات الطبيب</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>الطبيب</th>
                                    <th>النوع</th>
                                    <th>التخصص</th>
                                    <th>العيادة</th>
                                    <th>المؤهلات</th>
                                    <th>أجر الكشف</th>
                                    <th>مواعيد اليوم</th>
                                    <th>الحالة</th>
                                    <th>التوفر اليومي</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($doctors as $doctor)
                                <tr>
                                    <td>{{ ($doctors->currentPage() - 1) * $doctors->perPage() + $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <span class="text-white fw-bold">{{ $doctor->user ? substr($doctor->user->name, 0, 1) : '?' }}</span>
                                            </div>
                                            <div>
                                                <strong>د. {{ $doctor->user ? $doctor->user->name : 'طبيب بدون بيانات' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $doctor->user ? $doctor->user->email : 'لا يوجد بريد' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>@if($doctor->type == 'consultant')<span class="badge bg-info">استشاري</span>@elseif($doctor->type == 'anesthesiologist')<span class="badge bg-warning">تخدير</span>@else<span class="badge bg-danger">جراح</span>@endif</td>
                                    <td><span class="badge bg-info">{{ $doctor->specialization }}</span></td>
                                    <td>{{ $doctor->department ? $doctor->department->name : 'غير محدد' }}</td>
                                    <td>{{ $doctor->qualification }}</td>
                                    <td><span class="text-success">{{ number_format($doctor->consultation_fee) }} د.ع</span></td>
                                    <td><span class="badge bg-primary">{{ $doctor->today_appointments_count }}</span></td>
                                    <td>@if($doctor->is_active)<span class="badge bg-success">نشط</span>@else<span class="badge bg-danger">غير نشط</span>@endif</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($doctor->is_available_today)
                                                <span class="badge bg-success me-2">متوفر</span>
                                                <button type="button" class="btn btn-sm btn-outline-danger toggle-availability" 
                                                        data-doctor-id="{{ $doctor->id }}" 
                                                        data-available="0"
                                                        title="إلغاء التوفر">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-danger me-2">غير متوفر</span>
                                                <button type="button" class="btn btn-sm btn-outline-success toggle-availability" 
                                                        data-doctor-id="{{ $doctor->id }}" 
                                                        data-available="1"
                                                        title="تفعيل التوفر">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('doctors.show', $doctor) }}" class="btn btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('doctors.edit', $doctor) }}" class="btn btn-warning" title="تعديل"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('doctors.destroy', $doctor) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف الطبيب؟')"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-4">
                                        <i class="fas fa-user-md fa-3x mb-3"></i><br>لا توجد أطباء مضافة حتى الآن
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">{{ $doctors->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>.avatar-sm { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: bold; }</style>

<script>
$(document).ready(function() {
    $('.toggle-availability').on('click', function() {
        const button = $(this);
        const doctorId = button.data('doctor-id');
        const available = button.data('available');
        const cell = button.closest('td');
        
        // تعطيل الزر مؤقتاً
        button.prop('disabled', true);
        
        $.ajax({
            url: `/doctors/${doctorId}/availability`,
            method: 'PATCH',
            data: {
                is_available_today: available,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // تحديث الواجهة
                    if (available == 1) {
                        cell.html(`
                            <div class="d-flex align-items-center">
                                <span class="badge bg-success me-2">متوفر</span>
                                <button type="button" class="btn btn-sm btn-outline-danger toggle-availability" 
                                        data-doctor-id="${doctorId}" 
                                        data-available="0"
                                        title="إلغاء التوفر">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        `);
                    } else {
                        cell.html(`
                            <div class="d-flex align-items-center">
                                <span class="badge bg-danger me-2">غير متوفر</span>
                                <button type="button" class="btn btn-sm btn-outline-success toggle-availability" 
                                        data-doctor-id="${doctorId}" 
                                        data-available="1"
                                        title="تفعيل التوفر">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        `);
                    }
                    
                    // إظهار رسالة نجاح
                    toastr.success(response.message);
                }
            },
            error: function(xhr) {
                console.error('خطأ في تحديث التوفر:', xhr.responseText);
                toastr.error('حدث خطأ في تحديث التوفر');
                button.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection