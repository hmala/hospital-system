<!-- resources/views/radiology/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-x-ray me-2"></i>إدارة طلبات الإشعة</h2>
                @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist() || Auth::user()->isDoctor())
                <a href="{{ route('radiology.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>طلب إشعة جديد
                </a>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- إحصائيات سريعة -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-patient text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">معلقة</h6>
                            <h3>{{ $requests->where('status', 'pending')->count() }}</h3>
                        </div>
                        <i class="fas fa-clock fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-appointment text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">مجدولة</h6>
                            <h3>{{ $requests->where('status', 'scheduled')->count() }}</h3>
                        </div>
                        <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-doctor text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">قيد التنفيذ</h6>
                            <h3>{{ $requests->where('status', 'in_progress')->count() }}</h3>
                        </div>
                        <i class="fas fa-play fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-department text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">مكتملة</h6>
                            <h3>{{ $requests->where('status', 'completed')->count() }}</h3>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- طلبات الطوارئ - الأشعة -->
    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-ambulance me-2"></i>
                        طلبات أشعة الطوارئ
                        <span class="badge bg-light text-danger ms-2">{{ $emergencyRadiologyRequests->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>أنواع الأشعة</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>وقت الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($emergencyRadiologyRequests as $emRequest)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $emRequest->patient->user->name }}</strong><br>
                                        <small class="text-muted">{{ $emRequest->patient->user->phone ?? 'لا يوجد رقم' }}</small>
                                    </td>
                                    <td>
                                        @foreach($emRequest->radiologyTypes as $type)
                                            <span class="badge bg-info me-1">{{ $type->name }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->priority == 'critical' ? 'danger' : 'warning' }}">
                                            {{ $emRequest->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $emRequest->status_badge_class }}">
                                            {{ $emRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $emRequest->requested_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            @if($emRequest->status == 'pending')
                                                <form action="{{ route('staff.emergency-radiology.start', $emRequest) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-success" title="بدء الإجراء">
                                                        <i class="fas fa-play"></i>
                                                    </button>
                                                </form>
                                            @elseif($emRequest->status == 'in_progress')
                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#completeEmergencyRadiologyModal{{ $emRequest->id }}" title="إكمال">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-success">تم</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>

                                @if($emRequest->status == 'in_progress')
                                <div class="modal fade" id="completeEmergencyRadiologyModal{{ $emRequest->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form action="{{ route('staff.emergency-radiology.complete', $emRequest) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-header bg-success text-white">
                                                    <h5 class="modal-title">
                                                        <i class="fas fa-check-circle me-2"></i>
                                                        إكمال طلب أشعة الطوارئ #{{ $emRequest->emergency_id }}
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-info">
                                                        <strong>المريض:</strong> {{ $emRequest->patient->user->name }}
                                                    </div>
                                                    <h6 class="mb-3">نتائج الفحوصات:</h6>
                                                    @foreach($emRequest->radiologyTypes as $type)
                                                    <div class="mb-3">
                                                        <label class="form-label">
                                                            <strong>{{ $type->name }}</strong>
                                                        </label>
                                                        <textarea name="results[{{ $type->id }}]" class="form-control" rows="3" placeholder="أدخل نتيجة الفحص...">{{ $type->pivot->result ?? '' }}</textarea>
                                                    </div>
                                                    @endforeach
                                                    <div class="mb-3">
                                                        <label class="form-label">ملاحظات إضافية</label>
                                                        <textarea name="notes" class="form-control" rows="2">{{ $emRequest->notes }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-check me-2"></i>
                                                        إكمال وحفظ النتائج
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif

                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- جدول طلبات الإشعة -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">طلبات الإشعة</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>نوع الإشعة</th>
                                    <th>الطبيب المطلب</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($requests as $request)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $request->patient->user->name }}</strong><br>
                                        <small class="text-muted">{{ $request->patient->user->phone ?? 'لا يوجد رقم' }}</small>
                                    </td>
                                    <td>
                                        <strong>{{ $request->radiologyType->name }}</strong><br>
                                        <small class="text-muted">{{ $request->radiologyType->code }}</small>
                                    </td>
                                    <td>
                                        @if($request->doctor)
                                            د. {{ $request->doctor->user->name }}
                                        @else
                                            <span class="text-muted">من الاستعلامات</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->priority_color }}">
                                            {{ $request->priority_text }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $request->status_color }}">
                                            {{ $request->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $request->requested_date->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('radiology.show', $request) }}" class="btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>

                                            @if($request->status === 'completed' && $request->result)
                                            <a href="{{ route('radiology.print', $request) }}" target="_blank" class="btn btn-success" title="طباعة">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            @endif

                                            @if(Auth::user()->isAdmin() || Auth::user()->isReceptionist())
                                            @if($request->status === 'pending')
                                            <a href="{{ route('radiology.edit', $request) }}" class="btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="{{ route('radiology.destroy', $request) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-danger" title="حذف" onclick="return confirm('هل أنت متأكد من حذف هذا الطلب؟')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            @endif
                                            @endif

                                            @if(Auth::user()->hasRole('radiology_staff') && $request->canBePerformed())
                                            <form action="{{ route('radiology.start', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success" title="بدء الإجراء">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                            @endif

                                            @if($request->status === 'in_progress' && Auth::user()->hasRole('radiology_staff'))
                                            <form action="{{ route('radiology.complete', $request) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-primary" title="إكمال">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-x-ray fa-3x mb-3"></i><br>لا توجد طلبات إشعة
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-center mt-4">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(Auth::user()->hasRole('radiology_staff') && isset($newSystemRequests) && $newSystemRequests->count() > 0)
    <!-- جدول طلبات الأشعة من النظام الجديد -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-star me-2"></i>
                        طلبات جديدة من الاستعلامات (تتطلب تحديد أنواع الأشعة)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>الحالة</th>
                                    <th>حالة الدفع</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($newSystemRequests as $request)
                                <tr>
                                    <td><strong>#{{ $request->id }}</strong></td>
                                    <td>
                                        @if($request->visit && $request->visit->patient)
                                        <strong>{{ $request->visit->patient->user->name }}</strong><br>
                                        <small class="text-muted">{{ $request->visit->patient->user->phone ?? 'لا يوجد رقم' }}</small>
                                        @else
                                        <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request->status === 'pending_service_selection')
                                        <span class="badge bg-warning">بانتظار تحديد الأشعة</span>
                                        @elseif($request->status === 'pending')
                                        <span class="badge bg-info">بانتظار الدفع</span>
                                        @else
                                        <span class="badge bg-secondary">{{ $request->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($request->payment_status === 'not_applicable')
                                        <span class="badge bg-secondary">غير مطبق</span>
                                        @elseif($request->payment_status === 'pending')
                                        <span class="badge bg-warning">معلق</span>
                                        @elseif($request->payment_status === 'paid')
                                        <span class="badge bg-success">مدفوع</span>
                                        @else
                                        <span class="badge bg-secondary">{{ $request->payment_status }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $request->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <a href="{{ route('staff.requests.show', $request) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>
                                            @if($request->status === 'pending_service_selection')
                                            تحديد الأشعة المطلوبة
                                            @else
                                            عرض التفاصيل
                                            @endif
                                        </a>
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
</div>

@push('scripts')
<script>
    // Simple polling to reload page every 20 seconds
    setInterval(function(){
        location.reload();
    }, 20000);
</script>
@endpush
@endsection