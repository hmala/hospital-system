<!-- resources/views/appointments/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-calendar-check me-2"></i>إدارة المواعيد</h2>
                <a href="{{ route('appointments.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>حجز موعد جديد
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

    <!-- مواعيد اليوم -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>مواعيد اليوم - {{ \Carbon\Carbon::today()->format('Y-m-d') }}</h5>
                </div>
                <div class="card-body">
                    @if($todayAppointments->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>العيادة</th>
                                    <th>السبب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todayAppointments as $appointment)
                                <tr>
                                    <td>{{ $appointment->patient && $appointment->patient->user ? $appointment->patient->user->name : 'مريض غير محدد' }}</td>
                                    <td>د. {{ $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->name : 'طبيب غير محدد' }}</td>
                                    <td>{{ $appointment->department ? $appointment->department->name : 'قسم غير محدد' }}</td>
                                    <td><small class="text-muted">{{ Str::limit($appointment->reason, 30) }}</small></td>
                                    <td><span class="badge bg-{{ $appointment->status_color }}">{{ $appointment->status_text }}</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                            @if($appointment->canBeCancelled())
                                            <button type="button" class="btn btn-warning" title="إلغاء" data-bs-toggle="modal" data-bs-target="#cancelAppointmentModal" data-appointment-id="{{ $appointment->id }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="text-center text-muted mb-0">لا توجد مواعيد لهذا اليوم</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- جميع المواعيد النشطة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">المواعيد النشطة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="active-appointments-table">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>العيادة</th>
                                    <th>أجر الكشف</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeAppointments as $appointment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td><strong>{{ $appointment->appointment_date->format('Y-m-d') }}</strong></td>
                                    <td>{{ $appointment->patient && $appointment->patient->user ? $appointment->patient->user->name : 'مريض غير محدد' }}</td>
                                    <td>د. {{ $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->name : 'طبيب غير محدد' }}</td>
                                    <td>{{ $appointment->department ? $appointment->department->name : 'قسم غير محدد' }}</td>
                                    <td><span class="text-success">{{ number_format($appointment->consultation_fee) }} د.ع</span></td>
                                    <td><span class="badge bg-{{ $appointment->status_color }}">{{ $appointment->status_text }}</span></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('appointments.edit', $appointment) }}" class="btn btn-warning" title="تعديل"><i class="fas fa-edit"></i></a>
                                            @if($appointment->canBeCancelled())
                                            <button type="button" class="btn btn-danger" title="إلغاء" data-bs-toggle="modal" data-bs-target="#cancelAppointmentModal" data-appointment-id="{{ $appointment->id }}">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            @else
                                            <form action="{{ route('appointments.destroy', $appointment) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف الموعد؟')"><i class="fas fa-trash"></i></button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i><br>لا توجد مواعيد نشطة حالياً
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">{{ $activeAppointments->links() }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- المواعيد المكتملة والملغاة -->
    @if($completedAppointments->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">المواعيد المكتملة والملغاة (آخر 10 مواعيد)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th>العيادة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($completedAppointments as $appointment)
                                <tr>
                                    <td>{{ $appointment->appointment_date->format('Y-m-d') }}</td>
                                    <td>{{ $appointment->patient && $appointment->patient->user ? $appointment->patient->user->name : 'مريض غير محدد' }}</td>
                                    <td>د. {{ $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->name : 'طبيب غير محدد' }}</td>
                                    <td>{{ $appointment->department ? $appointment->department->name : 'قسم غير محدد' }}</td>
                                    <td><span class="badge bg-{{ $appointment->status_color }}">{{ $appointment->status_text }}</span></td>
                                    <td>
                                        <a href="{{ route('appointments.show', $appointment) }}" class="btn btn-sm btn-info" title="عرض"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Modal لإلغاء الموعد -->
<div class="modal fade" id="cancelAppointmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إلغاء الموعد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="cancelAppointmentForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>هل أنت متأكد من إلغاء هذا الموعد؟</p>
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">سبب الإلغاء (اختياري)</label>
                        <textarea class="form-control" id="cancellation_reason" name="cancellation_reason" rows="3" placeholder="أدخل سبب الإلغاء..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد الإلغاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// إعداد modal الإلغاء
document.addEventListener('DOMContentLoaded', function() {
    const cancelModal = document.getElementById('cancelAppointmentModal');
    const cancelForm = document.getElementById('cancelAppointmentForm');

    // عند النقر على زر الإلغاء
    document.querySelectorAll('[data-bs-target="#cancelAppointmentModal"]').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const appointmentId = this.getAttribute('data-appointment-id');
            cancelForm.action = `/appointments/${appointmentId}/cancel`;
        });
    });

    // تنظيف الـ form عند إغلاق الـ modal
    cancelModal.addEventListener('hidden.bs.modal', function() {
        cancelForm.reset();
    });
});
</script>

@endsection
@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function reloadAppointmentsTable() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                var newTable = $(data).find('#active-appointments-table').html();
                $('#active-appointments-table').html(newTable);
            }
        });
    }
    setInterval(reloadAppointmentsTable, 5000); // كل 10 ثواني
</script>
@endsection
