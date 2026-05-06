@extends('layouts.app')

@section('content')
<div class="container-fluid" id="radiology-requests-content">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-x-ray me-2 text-info"></i>
                        طلبات الأشعة
                        <span class="badge bg-success" id="live-indicator">
                            <i class="fas fa-circle fa-xs"></i> مباشر
                        </span>
                    </h2>
                    <p class="text-muted mb-0">
                        مرحباً {{ auth()->user()->name }} -
                        <small id="last-update">آخر تحديث: الآن</small>
                    </p>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- الطلبات العادية -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>
                طلبات الأشعة العادية
                <span class="badge bg-light text-info ms-2">{{ $requests->total() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            @if($requests->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:70px;">رقم</th>
                                <th>مريض</th>
                                <th>طبيب</th>
                                <th style="width:80px;">وقت</th>
                                <th style="width:90px;">الدفع</th>
                                <th style="width:90px;">حالة</th>
                                <th style="width:80px;">إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($requests as $request)
                            <tr class="{{ $request->payment_status == 'pending' ? 'table-danger' : ($request->payment_status == 'paid' ? 'table-success' : '') }}">
                                <td><strong>#{{ $request->id }}</strong></td>
                                <td>{{ $request->visit?->patient?->user?->name ?? 'غير محدد' }}</td>
                                <td>د. {{ $request->visit?->doctor?->user?->name ?? 'غير محدد' }}</td>
                                <td><small>{{ $request->created_at->format('H:i') }}</small></td>
                                <td>
                                    @if($request->payment_status == 'paid')
                                        <span class="badge bg-success">مدفوع</span>
                                    @elseif($request->payment_status == 'pending')
                                        <span class="badge bg-danger">غير مدفوع</span>
                                    @else
                                        <span class="badge bg-secondary">-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-{{ $request->status == 'completed' ? 'success' : ($request->status == 'pending' ? 'warning' : ($request->status == 'pending_service_selection' ? 'secondary' : 'info')) }}">
                                        {{ $request->status == 'completed' ? 'تم' : ($request->status == 'pending' ? 'معلق' : ($request->status == 'pending_service_selection' ? 'بانتظار تحديد' : 'جاري')) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('radiology-staff.show', $request) }}"
                                       class="btn btn-outline-info btn-sm"
                                       title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center p-3">
                    {{ $requests->links() }}
                </div>
            @else
                <div class="text-center py-5">
                    <i class="fas fa-x-ray fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">لا توجد طلبات أشعة</h5>
                </div>
            @endif
        </div>
    </div>

    <!-- طلبات أشعة الطوارئ -->
    @if(isset($emergencyRadiologyRequests) && $emergencyRadiologyRequests->count() > 0)
    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">
                <i class="fas fa-ambulance me-2"></i>
                <i class="fas fa-x-ray me-2"></i>
                أشعة الطوارئ
                <span class="badge bg-light text-danger ms-2">{{ $emergencyRadiologyRequests->count() }}</span>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:90px;">رقم طوارئ</th>
                            <th>مريض</th>
                            <th>أشعة</th>
                            <th style="width:80px;">أولوية</th>
                            <th style="width:70px;">وقت</th>
                            <th style="width:70px;">حالة</th>
                            <th style="width:120px;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($emergencyRadiologyRequests as $emergencyRequest)
                        <tr class="{{ $emergencyRequest->status == 'pending' ? 'table-warning' : ($emergencyRequest->status == 'in_progress' ? 'table-info' : 'table-success') }}">
                            <td><strong class="text-danger">#{{ $emergencyRequest->emergency_id }}</strong></td>
                            <td>{{ $emergencyRequest->patient->user->name }}</td>
                            <td>
                                @foreach($emergencyRequest->radiologyTypes as $type)
                                    <span class="badge bg-info me-1">{{ $type->name }}</span>
                                @endforeach
                            </td>
                            <td>
                                <span class="badge bg-{{ $emergencyRequest->priority == 'critical' ? 'danger' : 'warning' }}">
                                    {{ $emergencyRequest->priority_text }}
                                </span>
                            </td>
                            <td><small>{{ optional($emergencyRequest->requested_at)->format('H:i') }}</small></td>
                            <td>
                                <span class="{{ $emergencyRequest->status_badge_class }}">{{ $emergencyRequest->status_text }}</span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($emergencyRequest->status == 'pending')
                                        <form action="{{ route('staff.emergency-radiology.start', $emergencyRequest) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-primary btn-sm" title="بدء العمل">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    @elseif($emergencyRequest->status == 'in_progress')
                                        <a href="{{ route('staff.emergency-radiology.show', $emergencyRequest) }}" class="btn btn-success btn-sm" title="إدخال النتائج">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @else
                                        <span class="badge bg-success">تم</span>
                                    @endif
                                    @if($emergencyRequest->status == 'completed')
                                        <a href="{{ route('staff.emergency-radiology.print', $emergencyRequest) }}" class="btn btn-outline-secondary btn-sm" target="_blank" title="طباعة">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
setInterval(function() {
    $.ajax({
        url: window.location.href,
        success: function(response) {
            const parser = new DOMParser();
            const doc = parser.parseFromString(response, 'text/html');
            const newContent = doc.getElementById('radiology-requests-content');
            if (newContent) {
                const scroll = window.scrollY;
                $('#radiology-requests-content').html($(newContent).html());
                window.scrollTo(0, scroll);
                $('#last-update').text('آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ'));
            }
        }
    });
}, 5000);

$(document).ready(function() {
    $('#last-update').text('آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ'));
});
</script>

<style>
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
#live-indicator { animation: pulse 2s ease-in-out infinite; }
.table-sm th { white-space: nowrap; }
.table-sm td { white-space: normal; word-break: break-word; }
.table-responsive table { min-width: max-content; }
</style>
@endsection
