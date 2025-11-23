@extends('layouts.app')

@section('content')
<style>
.hover-lift:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-stethoscope me-2"></i>
                    لوحة تحكم الطبيب
                </h2>
                <small class="text-muted">مرحباً د. {{ auth()->user()->name }}</small>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($error))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ $error }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(auth()->user()->isDoctor())
    <!-- زيارات اليوم -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-day me-2"></i>
                        زيارات اليوم - {{ \Carbon\Carbon::today()->format('Y-m-d') }}
                    </h5>
                </div>
                <div class="card-body">
                    @if($todayVisits->count() > 0)
                        <div class="row">
                            @foreach($todayVisits as $visit)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100 border-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'cancelled' ? 'danger' : 'warning') }} 
                                     style="border-width: 3px; transition: transform 0.3s ease;">
                                    <div class="card-header bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'cancelled' ? 'danger' : 'warning') }} text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-{{ $visit->status == 'completed' ? 'check-circle' : ($visit->status == 'cancelled' ? 'times-circle' : 'clock') }} me-2"></i>
                                            {{ $visit->patient->user->name }}
                                        </h6>
                                        <small class="text-white-50">
                                            {{ $visit->visit_time ?: 'غير محدد' }}
                                        </small>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>النوع:</strong> {{ $visit->visit_type_text }}
                                        </p>
                                        <p class="mb-2">
                                            <strong>الشكوى:</strong>
                                            <small>{{ Str::limit($visit->chief_complaint, 50) }}</small>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-{{ $visit->status == 'completed' ? 'success' : ($visit->status == 'cancelled' ? 'danger' : 'warning') }}">
                                                <i class="fas fa-{{ $visit->status == 'completed' ? 'check' : ($visit->status == 'cancelled' ? 'times' : 'spinner fa-spin') }}"></i>
                                                {{ $visit->status == 'completed' ? 'مكتمل' : ($visit->status == 'cancelled' ? 'ملغي' : 'قيد الفحص') }}
                                            </span>
                                            <a href="{{ route('doctor.visits.show', $visit) }}"
                                               class="btn btn-sm btn-outline-primary hover-lift">
                                                فحص
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات مقررة اليوم</h5>
                            <p class="text-muted">ستظهر هنا الزيارات المقررة لليوم</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- زيارات مختبرية سريعة -->
    @if(auth()->user()->hasRole('receptionist') || auth()->user()->hasRole('admin'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        زيارات مختبرية سريعة
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="mb-0">للمرضى الذين يحتاجون تحاليل فقط دون زيارة طبية كاملة</p>
                            <small class="text-white-50">سيتم تحديد التحاليل المطلوبة من قبل فني المختبر</small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="{{ route('staff.lab-visits.create') }}" class="btn btn-light">
                                <i class="fas fa-plus me-1"></i>
                                إنشاء زيارة مختبرية
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- الطلبات الطبية -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-flask me-2"></i>
                        الطلبات الطبية الأخيرة
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($doctorRequests) && $doctorRequests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>النوع</th>
                                        <th>المريض</th>
                                        <th>التفاصيل</th>
                                        <th>الحالة</th>
                                        <th>التاريخ</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($doctorRequests as $request)
                                    <tr class="border-{{ $request->status == 'completed' ? 'success' : ($request->status == 'cancelled' ? 'danger' : 'warning') }}" 
                                        style="border-left-width: 5px;">
                                        <td>
                                            <span class="badge bg-{{ $request->type == 'lab' ? 'primary' : ($request->type == 'radiology' ? 'info' : 'success') }}">
                                                <i class="fas fa-{{ $request->type == 'lab' ? 'flask' : ($request->type == 'radiology' ? 'x-ray' : 'stethoscope') }}"></i>
                                                {{ $request->type_text }}
                                            </span>
                                        </td>
                                        <td>{{ $request->visit->patient->user->name }}</td>
                                        <td>{{ Str::limit($request->details['description'] ?? '', 30) }}</td>
                                        <td>
                                            <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'cancelled' ? 'danger' : 'warning') }}">
                                                <i class="fas fa-{{ $request->status == 'completed' ? 'check' : ($request->status == 'cancelled' ? 'times' : 'spinner fa-spin') }}"></i>
                                                {{ $request->status_text }}
                                            </span>
                                        </td>
                                        <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            @if($request->status == 'completed')
                                                <button class="btn btn-sm btn-success" disabled>
                                                    <i class="fas fa-eye"></i> عرض النتائج
                                                </button>
                                            @elseif($request->status == 'pending')
                                                <form action="{{ route('doctor.requests.update', $request) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('هل أنت متأكد من الإلغاء؟')">
                                                        <i class="fas fa-times"></i> إلغاء
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-flask fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد طلبات طبية حديثة</h5>
                            <p class="text-muted">ستظهر هنا الطلبات الطبية الأخيرة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- زيارات مكتملة سابقة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        التاريخ الطبي - الزيارات المكتملة
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($completedVisits) && $completedVisits->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-success">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الوقت</th>
                                        <th>المريض</th>
                                        <th>النوع</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($completedVisits as $visit)
                                    <tr class="border-success" style="border-left-width: 5px;">
                                        <td>{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</td>
                                        <td>{{ $visit->visit_time ?: 'غير محدد' }}</td>
                                        <td>{{ $visit->patient->user->name }}</td>
                                        <td>{{ $visit->visit_type_text }}</td>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check"></i>
                                                مكتمل
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('doctor.visits.show', $visit) }}"
                                               class="btn btn-sm btn-outline-success hover-lift">
                                                <i class="fas fa-eye me-1"></i>
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد زيارات مكتملة سابقة</h5>
                            <p class="text-muted">ستظهر هنا الزيارات المكتملة السابقة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- زيارات غير مكتملة سابقة -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        زيارات غير مكتملة سابقة
                    </h5>
                </div>
                <div class="card-body">
                    @if(isset($incompleteVisits) && $incompleteVisits->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-warning">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الوقت</th>
                                        <th>المريض</th>
                                        <th>النوع</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($incompleteVisits as $visit)
                                    <tr class="border-warning" style="border-left-width: 5px;">
                                        <td>{{ $visit->visit_date ? $visit->visit_date->format('Y-m-d') : 'غير محدد' }}</td>
                                        <td>{{ $visit->visit_time ?: 'غير محدد' }}</td>
                                        <td>{{ $visit->patient->user->name }}</td>
                                        <td>{{ $visit->visit_type_text }}</td>
                                        <td>
                                            <span class="badge bg-{{ $visit->status == 'cancelled' ? 'danger' : 'warning' }}">
                                                <i class="fas fa-{{ $visit->status == 'cancelled' ? 'times' : 'spinner fa-spin' }}"></i>
                                                {{ $visit->status == 'cancelled' ? 'ملغي' : 'قيد الفحص' }}
                                            </span>
                                        </td>
                                        <td>
                                            <a href="{{ route('doctor.visits.show', $visit) }}"
                                               class="btn btn-sm btn-outline-warning hover-lift">
                                                <i class="fas fa-eye me-1"></i>
                                                عرض
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">جميع الزيارات السابقة مكتملة</h5>
                            <p class="text-muted">لا توجد زيارات غير مكتملة سابقة</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- المواعيد المجدولة -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-plus me-2"></i>
                        المواعيد المجدولة
                    </h5>
                </div>
                <div class="card-body">
                    @if($appointments->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الوقت</th>
                                        <th>المريض</th>
                                        <th>القسم</th>
                                        <th>الحالة</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($appointments as $appointment)
                                    <tr class="border-success" style="border-left-width: 5px;">
                                        <td>{{ $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : 'غير محدد' }}</td>
                                        <td>{{ $appointment->appointment_date->format('H:i') }}</td>
                                        <td>{{ $appointment->patient->user->name }}</td>
                                        <td>{{ $appointment->department->name }}</td>
                                        <td>
                                            <span class="badge bg-{{ $appointment->status_color }}">
                                                <i class="fas fa-{{ $appointment->status == 'completed' ? 'check' : ($appointment->status == 'cancelled' ? 'times' : 'clock') }}"></i>
                                                {{ $appointment->status_text }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($appointment->status == 'confirmed' || $appointment->status == 'scheduled')
                                                <form action="{{ route('doctor.appointments.convert', $appointment->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-sm btn-primary hover-lift">
                                                        <i class="fas fa-user-md me-1"></i>
                                                        بدء الفحص
                                                    </button>
                                                </form>
                                            @elseif($appointment->status == 'completed')
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>
                                                    تم إنهاؤه
                                                </span>
                                            @elseif($appointment->status == 'cancelled')
                                                <span class="text-muted">
                                                    <i class="fas fa-times-circle me-1"></i>
                                                    ملغى
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">لا توجد مواعيد مجدولة</h5>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection