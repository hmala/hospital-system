{{-- resources/views/emergency/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-ambulance me-2"></i>
                    إدارة الطوارئ
                </h2>
                <div>
                    <span class="badge bg-success" id="emergency-live-indicator">
                        <i class="fas fa-circle fa-xs"></i> مباشر
                    </span>
                    <small class="text-muted ms-2" id="emergency-last-update">آخر تحديث: الآن</small>
                </div>
                <div>
                    <a href="{{ route('emergency.dashboard') }}" class="btn btn-info me-2">
                        <i class="fas fa-chart-line me-2"></i>لوحة التحكم
                    </a>
                    <a href="{{ route('emergency.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>حالة طوارئ جديدة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- فلاتر البحث والتصفية -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form action="{{ route('emergency.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="ابحث باسم المريض أو رقم الطوارئ..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="priority" class="form-select">
                        <option value="">جميع الأولويات</option>
                        <option value="critical" @selected(request('priority') == 'critical')>حرجة</option>
                        <option value="high" @selected(request('priority') == 'high')>عالية</option>
                        <option value="medium" @selected(request('priority') == 'medium')>متوسطة</option>
                        <option value="low" @selected(request('priority') == 'low')>منخفضة</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">جميع الحالات</option>
                        <option value="waiting" @selected(request('status') == 'waiting')>في الانتظار</option>
                        <option value="in_triage" @selected(request('status') == 'in_triage')>في التفريغ</option>
                        <option value="in_treatment" @selected(request('status') == 'in_treatment')>في العلاج</option>
                        <option value="discharged" @selected(request('status') == 'discharged')>مغادر</option>
                        <option value="transferred" @selected(request('status') == 'transferred')>محول</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="fas fa-filter"></i> فلترة
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($emergencies->where('payment_status', 'pending')->count() > 0)
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    يوجد {{ $emergencies->where('payment_status', 'pending')->count() }} حالة طوارئ لم تُدفع بعد في الكاشير. .
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive" style="overflow: visible">
                        <table class="table table-hover text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>نتائج التحاليل</th>
                                    <th>نتائج الأشعة</th>
                                    <th>الطبيب المسؤول</th>
                                    <th>وقت الدخول</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($emergencies as $emergency)
                                <tr class="{{ $emergency->payment_status == 'paid' ? 'table-success' : ($emergency->payment_status == 'pending' ? 'table-danger' : ($emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'high' ? 'table-warning' : ''))) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                                                <span class="text-white fw-bold">
                                                    @if($emergency->patient)
                                                        {{ substr($emergency->patient->user->name ?? '؟', 0, 1) }}
                                                    @elseif($emergency->emergencyPatient)
                                                        {{ substr($emergency->emergencyPatient->name ?? '؟', 0, 1) }}
                                                    @else
                                                        ?
                                                    @endif
                                                </span>
                                            </div>
                                            <div>
                                                <strong>
                                                    @if($emergency->patient)
                                                        {{ $emergency->patient->user->name ?? 'مريض بدون بيانات' }}
                                                    @elseif($emergency->emergencyPatient)
                                                        {{ $emergency->emergencyPatient->name }} <small class="text-muted">(طوارئ)</small>
                                                    @else
                                                        مريض غير معروف
                                                    @endif
                                                </strong>
                                                <br>
                                                <small class="text-muted">
                                                    رقم الطوارئ: {{ $emergency->id }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $latestCompletedLab = $emergency->labRequests
                                                ->where('status', 'completed')
                                                ->sortByDesc('completed_at')
                                                ->first();
                                        @endphp
                                        @if($latestCompletedLab)
                                            <span class="badge bg-success mb-2 d-inline-block">مكتمل</span>
                                            @php
                                                $labResults = $latestCompletedLab->labTests
                                                    ->filter(function($test){ return !empty(trim((string)($test->pivot->result ?? ''))); })
                                                    ->values();
                                            @endphp
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success d-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#labResultsModal-{{ $emergency->id }}"
                                                    title="عرض نتائج التحاليل">
                                                <i class="fas fa-vial me-1"></i>
                                                عرض النتائج
                                            </button>
                                            @if($labResults->count())
                                                <small class="text-muted d-block mt-1">{{ $labResults->count() }} نتيجة</small>
                                            @endif
                                        @elseif($emergency->labRequests->whereIn('status', ['pending', 'in_progress'])->count() > 0)
                                            <span class="badge bg-warning text-dark">قيد التنفيذ</span>
                                        @else
                                            <small class="text-muted">لا يوجد طلب</small>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $latestCompletedRadiology = $emergency->radiologyRequests
                                                ->where('status', 'completed')
                                                ->sortByDesc('completed_at')
                                                ->first();
                                        @endphp
                                        @if($latestCompletedRadiology)
                                            <span class="badge bg-success mb-2 d-inline-block">مكتمل</span>
                                            @php
                                                $radiologyResults = $latestCompletedRadiology->radiologyTypes
                                                    ->filter(function($type){ return !empty(trim((string)($type->pivot->result ?? ''))); })
                                                    ->values();
                                            @endphp
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info d-block"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#radiologyResultsModal-{{ $emergency->id }}"
                                                    title="عرض نتائج الأشعة">
                                                <i class="fas fa-x-ray me-1"></i>
                                                عرض النتائج
                                            </button>
                                            @if($radiologyResults->count())
                                                <small class="text-muted d-block mt-1">{{ $radiologyResults->count() }} نتيجة</small>
                                            @endif
                                        @elseif($emergency->radiologyRequests->whereIn('status', ['pending', 'in_progress'])->count() > 0)
                                            <span class="badge bg-warning text-dark">قيد التنفيذ</span>
                                        @else
                                            <small class="text-muted">لا يوجد طلب</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($emergency->doctor)
                                            <small>{{ $emergency->doctor->user->name ?? 'غير محدد' }}</small>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <small>{{ $emergency->created_at->format('d/m/Y H:i') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
                                        @endphp
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fas fa-list-ul me-1"></i>
                                                <span></span>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 220px;">
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#vitalSignsModal-{{ $emergency->id }}">
                                                        <i class="fas fa-heartbeat text-danger me-2"></i>
                                                        <span>قياس علامات حيوية</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#medicalModal-{{ $emergency->id }}">
                                                        <i class="fas fa-notes-medical text-success me-2"></i>
                                                        <span>تشخيص وخدمات</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#treatmentModal-{{ $emergency->id }}">
                                                        <i class="fas fa-pills text-warning me-2"></i>
                                                        <span>علاج الطوارئ</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#treatmentResultsModal-{{ $emergency->id }}">
                                                        <i class="fas fa-eye text-secondary me-2"></i>
                                                        <span>عرض العلاجات</span>
                                                        @if($emergency->treatments->count())
                                                            <span class="badge bg-secondary rounded-pill ms-2">{{ $emergency->treatments->count() }}</span>
                                                        @endif
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#labModal-{{ $emergency->id }}">
                                                        <i class="fas fa-flask text-primary me-2"></i>
                                                        <span>طلب تحاليل</span>
                                                    </button>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#radiologyModal-{{ $emergency->id }}">
                                                        <i class="fas fa-x-ray text-info me-2"></i>
                                                        <span>طلب أشعة</span>
                                                    </button>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item d-flex align-items-center text-start" data-bs-toggle="modal" data-bs-target="#consultationModal-{{ $emergency->id }}">
                                                        <i class="fas fa-calendar-plus text-warning me-2"></i>
                                                        <span>طلب استشارة</span>
                                                    </button>
                                                </li>
                                                @if($emergency->status !== 'transferred' && $emergency->status !== 'discharged')
                                                <li>
                                                    <form action="{{ route('emergency.transfer-to-surgery', $emergency) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى صالة العمليات؟ سيتم تسجيله وترحيل بياناته تلقائياً.')">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item d-flex align-items-center text-start text-danger">
                                                            <i class="fas fa-procedures me-2"></i>
                                                            <span>تحويل إلى العمليات</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <i class="fas fa-ambulance fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">لا توجد حالات طوارئ حالياً</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($emergencies->hasPages())
                    <div class="d-flex justify-content-center mt-4">
                        {{ $emergencies->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- قسم طلبات الخدمات التمريضية -->
    @if($nursingRequests && $nursingRequests->count() > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        طلبات الخدمات التمريضية
                        <span class="badge bg-light text-success ms-2">{{ $nursingRequests->count() }}</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;">رقم</th>
                                    <th>المريض</th>
                                    <th>الطبيب</th>
                                    <th style="width: 200px;">الخدمات</th>
                                    <th style="width: 100px;">الحالة</th>
                                    <th style="width: 100px;">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($nursingRequests as $request)
                                <tr>
                                    <td>#{{ $request->id }}</td>
                                    <td>
                                        <strong>{{ $request->visit->patient->user->name ?? 'غير محدد' }}</strong><br>
                                        <small class="text-muted">{{ $request->visit->patient->phone ?? '' }}</small>
                                    </td>
                                    <td>د. {{ $request->visit->doctor->user->name ?? 'غير محدد' }}</td>
                                    <td>
                                        @php
                                            $nursingDetails = $request->details;
                                            if (is_string($nursingDetails)) {
                                                $nursingDetails = json_decode($nursingDetails, true);
                                            }
                                            $serviceNames = $nursingDetails['nursing_service_names'] ?? [];
                                        @endphp
                                        <div class="d-flex flex-column gap-1">
                                            @foreach($serviceNames as $serviceName)
                                                <span class="badge bg-info">{{ $serviceName }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td>
                                        @if($request->status === 'pending')
                                            <span class="badge bg-warning text-dark">معلق</span>
                                        @elseif($request->status === 'in_progress')
                                            <span class="badge bg-info">قيد التنفيذ</span>
                                        @elseif($request->status === 'completed')
                                            <span class="badge bg-success">مكتمل</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $request->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" 
                                                    class="btn btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#nursingDetailsModal{{ $request->id }}"
                                                    title="عرض التفاصيل">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- modal for nursing request details -->
                                <div class="modal fade" id="nursingDetailsModal{{ $request->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header bg-success text-white">
                                                <h5 class="modal-title">تفاصيل طلب الخدمة التمريضية</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>رقم الطلب</strong></label>
                                                    <p class="form-control-plaintext">#{{ $request->id }}</p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>الخدمات المطلوبة</strong></label>
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($serviceNames as $serviceName)
                                                            <span class="badge bg-info" style="width: fit-content;">{{ $serviceName }}</span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>الحالة الحالية</strong></label>
                                                    <p class="form-control-plaintext">
                                                        @if($request->status === 'pending')
                                                            <span class="badge bg-warning text-dark">معلق</span>
                                                        @elseif($request->status === 'in_progress')
                                                            <span class="badge bg-info">قيد التنفيذ</span>
                                                        @elseif($request->status === 'completed')
                                                            <span class="badge bg-success">مكتمل</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label"><strong>تاريخ الطلب</strong></label>
                                                    <p class="form-control-plaintext">{{ $request->created_at->format('d/m/Y H:i') }}</p>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                @if($request->status === 'pending')
                                                    <form method="POST" action="{{ route('emergency.nursing-request.update', $request) }}" style="display: inline;">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <button type="submit" class="btn btn-primary" onclick="return confirm('تأكيد بدء تنفيذ الخدمة؟')">
                                                            <i class="fas fa-play me-1"></i>بدء التنفيذ
                                                        </button>
                                                    </form>
                                                @elseif($request->status === 'in_progress')
                                                    <form method="POST" action="{{ route('emergency.nursing-request.update', $request) }}" style="display: inline;">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="completed">
                                                        <button type="submit" class="btn btn-success" onclick="return confirm('تأكيد إنهاء الخدمة؟')">
                                                            <i class="fas fa-check me-1"></i>إنهاء الخدمة
                                                        </button>
                                                    </form>
                                                @endif
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

<script>
    function updateEmergencyPaymentStatus() {
        axios.get('{{ route('cashier.emergency.payment.status') }}')
            .then(function(response) {
                var pending = response.data.pending || 0;
                var alertHolder = document.getElementById('emergency-payment-alert-holder');
                if (!alertHolder) {
                    var container = document.querySelector('.container-fluid');
                    var div = document.createElement('div');
                    div.id = 'emergency-payment-alert-holder';
                    div.className = 'row mb-3';
                    container.insertBefore(div, container.firstChild.nextSibling.nextSibling);
                    alertHolder = div;
                }

                if (pending > 0) {
                    alertHolder.innerHTML = '<div class="col-12"><div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle me-2"></i>' +
                        'هناك ' + pending + ' حالة طوارئ غير مدفوعة في الكاشير. <strong>الطباعة مغلقة حتى السداد</strong>.' +
                        '</div></div>';
                } else {
                    alertHolder.innerHTML = '<div class="col-12"><div class="alert alert-success">' +
                        '<i class="fas fa-check-circle me-2"></i>' +
                        'جميع حالات الطوارئ المدعومة حتى الآن تم دفعها في الكاشير.' +
                        '</div></div>';
                }

                var indicator = document.getElementById('emergency-live-indicator');
                if (indicator) {
                    if (pending > 0) {
                        indicator.className = 'badge bg-danger';
                        indicator.innerHTML = '<i class="fas fa-circle fa-xs"></i> غير مدفوع';
                    } else {
                        indicator.className = 'badge bg-success';
                        indicator.innerHTML = '<i class="fas fa-circle fa-xs"></i> مباشر';
                    }
                }

                var lastUpdate = document.getElementById('emergency-last-update');
                if (lastUpdate) {
                    lastUpdate.textContent = 'آخر تحديث: ' + new Date().toLocaleTimeString('ar-IQ');
                }
            })
            .catch(function(error) {
                console.error('خطأ في تحديث حالة الدفع:', error);
            });
    }

    function autoRefreshEmergencyPage() {
        if (document.querySelector('.modal.show')) {
            return;
        }

        window.location.reload();
    }

    document.addEventListener('DOMContentLoaded', function() {
        setInterval(autoRefreshEmergencyPage, 5000);
    });
</script>

<style>
    @@keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    #emergency-live-indicator {
        animation: pulse 2s ease-in-out infinite;
    }

    .emergency-action-btn {
        min-width: 38px;
        padding: 0.45rem 0.55rem;
        border-radius: 0.85rem;
        border: 1px solid transparent;
        color: #ffffff;
        background: #f8fafc;
        transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
    }

    .emergency-action-btn i {
        font-size: 0.9rem;
    }

    .emergency-action-btn:hover,
    .emergency-action-btn:focus {
        transform: translateY(-1px);
        box-shadow: 0 6px 14px rgba(15, 23, 42, 0.12);
    }

    .emergency-action-btn--red {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .emergency-action-btn--green {
        background-color: #198754;
        border-color: #198754;
    }

    .emergency-action-btn--blue {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }

    .emergency-action-btn--teal {
        background-color: #20c997;
        border-color: #20c997;
    }

    .emergency-action-btn--red,
    .emergency-action-btn--yellow {
        background-color: #f59e0b;
        border-color: #f59e0b;
    }

    .emergency-action-btn--green,
    .emergency-action-btn--blue,
    .emergency-action-btn--teal,
    .emergency-action-btn--yellow {
        color: #ffffff;
    }
</style>

@push('modals')
@foreach($emergencies as $emergency)
<!-- Modal للعلامات الحيوية فقط -->
<div class="modal fade" id="vitalSignsModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="vitalSignsModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <div>
                    <h5 class="modal-title" id="vitalSignsModalLabel-{{ $emergency->id }}">
                        <i class="fas fa-heartbeat me-2"></i>
                        قياس العلامات الحيوية - حالة #{{ $emergency->id }}
                    </h5>
                    <small class="text-white-50">{{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد' }}</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.update-vitals', $emergency) }}">
                @csrf
                @method('POST')
                <div class="modal-body">
                    <!-- عرض القراءات السابقة إن وجدت -->
                    @if($emergency->vitalSignReadings && $emergency->vitalSignReadings->count() > 0)
                    <div class="alert alert-info">
                        <strong><i class="fas fa-history me-2"></i>القراءات السابقة ({{ $emergency->vitalSignReadings->count() }} قراءة):</strong>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>ضغط الدم</th>
                                        <th>النبض</th>
                                        <th>الحرارة</th>
                                        <th>التنفس</th>
                                        <th>SpO2</th>
                                        <th>سكر الدم</th>
                                        <th>المسجل</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($emergency->vitalSignReadings->take(5) as $reading)
                                    <tr>
                                        <td class="text-nowrap">
                                            <small>{{ $reading->created_at->format('d/m H:i') }}</small>
                                        </td>
                                        <td>{{ $reading->blood_pressure ?? '---' }}</td>
                                        <td>{{ $reading->heart_rate ?? '---' }}</td>
                                        <td>{{ $reading->temperature ?? '---' }}</td>
                                        <td>{{ $reading->respiratory_rate ?? '---' }}</td>
                                        <td>{{ $reading->oxygen_saturation ?? '---' }}</td>
                                        <td>{{ $reading->blood_glucose ?? '---' }}</td>
                                        <td class="text-nowrap">
                                            <small>{{ optional($reading->recordedBy)->name ?? optional(optional($reading->recordedBy)->doctor)->user->name ?? 'الطبيب غير معروف' }}</small>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <h6 class="text-primary mb-3"><i class="fas fa-plus-circle me-2"></i>قراءة جديدة:</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="blood_pressure_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-heartbeat text-danger me-2"></i>
                                ضغط الدم (mmHg)
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="blood_pressure_{{ $emergency->id }}" 
                                   name="blood_pressure" 
                                   placeholder="120/80"
                                   value="{{ old('blood_pressure') }}">
                            <small class="text-muted">الطبيعي: 120/80 mmHg</small>
                        </div>

                        <div class="col-md-6">
                            <label for="heart_rate_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-heart text-danger me-2"></i>
                                معدل ضربات القلب (bpm)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="heart_rate_{{ $emergency->id }}" 
                                   name="heart_rate" 
                                   placeholder="75"
                                   min="1"
                                   max="300"
                                   value="{{ old('heart_rate', $emergency->heart_rate) }}">
                            <small class="text-muted">الطبيعي: 60-100 نبضة/دقيقة</small>
                        </div>

                        <div class="col-md-4">
                            <label for="temperature_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-thermometer-half text-primary me-2"></i>
                                درجة الحرارة (°C)
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   class="form-control form-control-lg" 
                                   id="temperature_{{ $emergency->id }}" 
                                   name="temperature" 
                                   placeholder="37.0"
                                   min="30"
                                   max="45"
                                   value="{{ old('temperature', $emergency->temperature) }}">
                            <small class="text-muted">الطبيعي: 36.5-37.5°C</small>
                        </div>

                        <div class="col-md-4">
                            <label for="respiratory_rate_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-lungs text-info me-2"></i>
                                معدل التنفس (/دقيقة)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="respiratory_rate_{{ $emergency->id }}" 
                                   name="respiratory_rate" 
                                   placeholder="16"
                                   min="1"
                                   max="100"
                                   value="{{ old('respiratory_rate', $emergency->respiratory_rate) }}">
                            <small class="text-muted">الطبيعي: 12-20 نفس/دقيقة</small>
                        </div>

                        <div class="col-md-4">
                            <label for="oxygen_saturation_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-wind text-success me-2"></i>
                                تشبع الأكسجين (%)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="oxygen_saturation_{{ $emergency->id }}" 
                                   name="oxygen_saturation" 
                                   placeholder="98"
                                   min="1"
                                   max="100"
                                   value="{{ old('oxygen_saturation', $emergency->oxygen_saturation) }}">
                            <small class="text-muted">الطبيعي: 95-100%</small>
                        </div>

                        <div class="col-md-6">
                            <label for="blood_glucose_{{ $emergency->id }}" class="form-label">
                                <i class="fas fa-tint text-warning me-2"></i>
                                سكر الدم (mg/dL)
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   class="form-control form-control-lg" 
                                   id="blood_glucose_{{ $emergency->id }}" 
                                   name="blood_glucose" 
                                   placeholder="120"
                                   value="{{ old('blood_glucose', $emergency->blood_glucose) }}">
                            <small class="text-muted">الطبيعي: 70-140 mg/dL</small>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>تنبيه:</strong> سيتم حفظ هذه القراءة وتحديث السجل الطبي للمريض
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-save me-2"></i>حفظ القراءات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal للتشخيص والخدمات -->
<div class="modal fade" id="medicalModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="medicalModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <div>
                    <h5 class="modal-title" id="medicalModalLabel-{{ $emergency->id }}">
                        <i class="fas fa-notes-medical me-2"></i>
                        التشخيص والخدمات - حالة #{{ $emergency->id }}
                    </h5>
                    <small class="text-white-50">{{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد' }}</small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.update-medical', $emergency) }}">
                @csrf
                @method('POST')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <strong><i class="fas fa-stethoscope me-2"></i>التشخيص والخدمات المقدمة</strong>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">التشخيص (ICD-10)</label>
                                        @php
                                            $diagnosisText = (old('emergency_id') == $emergency->id)
                                                ? old('diagnosis', $emergency->diagnosis)
                                                : $emergency->diagnosis;
                                        @endphp
                                        <input type="hidden" name="emergency_id" value="{{ $emergency->id }}">
                                        <div class="input-group">
                                            <span class="input-group-text bg-primary text-white">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text"
                                                   name="diagnosis"
                                                   class="form-control form-control-lg diagnosis-icd10-input"
                                                   value="{{ $diagnosisText }}"
                                                   placeholder="اختر رمز ICD-10 أو اكتب للتصفية..."
                                                   autocomplete="off"
                                                   list="icd10-list-{{ $emergency->id }}">
                                            <datalist id="icd10-list-{{ $emergency->id }}">
                                                @foreach($icd10Codes as $code)
                                                    <option value="{{ $code->code }} - {{ $code->description_ar ?: $code->description }}"></option>
                                                @endforeach
                                                <option value="أخرى (أدخل يدوياً)"></option>
                                            </datalist>
                                        </div>
                                        <small class="text-muted mt-1">
                                            اختر التشخيص من جدول ICD-10 أو اكتب رمز/وصف التشخيص.
                                        </small>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label fw-bold">الخدمات المقدمة</label>
                                        <div class="service-rows" id="service-rows-{{ $emergency->id }}">
                                            @php
                                                $selectedServiceIds = $emergency->services->pluck('id')->all();
                                            @endphp
                                            @if(count($selectedServiceIds))
                                                @foreach($selectedServiceIds as $serviceId)
                                                    <div class="service-row d-flex gap-2 align-items-start mb-2">
                                                        <select name="service_ids[]" class="form-select">
                                                            <option value="">اختر الخدمة</option>
                                                            @foreach($emergencyServices as $service)
                                                                <option value="{{ $service->id }}" @selected($serviceId == $service->id)>
                                                                    {{ $service->name }} - {{ number_format($service->price) }} IQD
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="service-row d-flex gap-2 align-items-start mb-2">
                                                    <select name="service_ids[]" class="form-select">
                                                        <option value="">اختر الخدمة</option>
                                                        @foreach($emergencyServices as $service)
                                                            <option value="{{ $service->id }}">
                                                                {{ $service->name }} - {{ number_format($service->price) }} IQD
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                        <button type="button" class="btn btn-outline-primary btn-sm add-service-row" data-emergency-id="{{ $emergency->id }}">
                                            <i class="fas fa-plus"></i> إضافة خدمة
                                        </button>
                                        <small class="text-muted d-block mt-2">يمكنك إضافة أكثر من خدمة للحالة الواحدة.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ المعلومات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="treatmentModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="treatmentModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="treatmentModalLabel-{{ $emergency->id }}">
                    <i class="fas fa-pills me-2"></i>
                    إضافة علاج لحالة الطوارئ #{{ $emergency->id }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.treatments.store', $emergency) }}">
                @csrf
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 140px;">نوع العلاج</th>
                                    <th>اسم الدواء / العلاج</th>
                                    <th style="width: 120px;">المرات يومياً</th>
                                    <th style="width: 140px;">الحالة</th>
                                    <th style="width: 140px;">تاريخ البدء</th>
                                    <th style="width: 140px;">تاريخ الانتهاء</th>
                                    <th style="width: 70px;"></th>
                                </tr>
                            </thead>
                            <tbody id="treatment-rows-{{ $emergency->id }}">
                                <tr class="treatment-row">
                                    <td>
                                        <select name="treatments[0][treatment_type]" class="form-select" required>
                                            <option value="">اختر النوع</option>
                                            <option value="medication">دوائي</option>
                                            <option value="injection">إبرة</option>
                                            <option value="drip">محلول</option>
                                            <option value="oxygen">أكسجين</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </td>
                                    <td>
                                        <textarea name="treatments[0][description]" class="form-control" rows="2" placeholder="اسم الدواء أو العلاج..." required></textarea>
                                        <input type="hidden" name="treatments[0][notes]" value="">
                                    </td>
                                    <td>
                                        <input type="number" name="treatments[0][frequency_per_day]" class="form-control" min="1" max="24" placeholder="عدد المرات" aria-label="عدد المرات يومياً">
                                    </td>
                                    <td>
                                        <select name="treatments[0][status]" class="form-select" required>
                                            <option value="planned">مخطط</option>
                                            <option value="in_progress">قيد التنفيذ</option>
                                            <option value="completed">مكتمل</option>
                                            <option value="cancelled">ملغي</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="date" name="treatments[0][started_at]" class="form-control">
                                    </td>
                                    <td>
                                        <input type="date" name="treatments[0][completed_at]" class="form-control">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-treatment-row" aria-label="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button type="button" class="btn btn-outline-primary btn-sm add-treatment-row" data-emergency-id="{{ $emergency->id }}">
                            <i class="fas fa-plus me-1"></i>
                            إضافة علاج
                        </button>
                        <small class="text-muted">أضف علاجاً واحداً أو أكثر ثم احفظ.</small>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning">حفظ العلاجات</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($emergencies as $emergency)
    @php
        $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
    @endphp
<div class="modal fade" id="consultationModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="consultationModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="consultationModalLabel-{{ $emergency->id }}">
                    <i class="fas fa-calendar-plus me-2"></i>
                    إنشاء موعد استشاري لحالة الطوارئ #{{ $emergency->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.create-consultation', $emergency) }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">تاريخ الموعد</label>
                            <input type="date" name="appointment_date" class="form-control" value="{{ now()->format('Y-m-d') }}" min="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">وقت الموعد</label>
                            <input type="time" name="appointment_time" class="form-control" value="{{ now()->addHour()->format('H:00') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">الأطباء الاستشاريون</label>
                            <select name="doctor_ids[]" class="form-select" multiple size="5" required>
                                @php
                                    $daysMap = [
                                        'Saturday' => 'السبت',
                                        'Sunday' => 'الأحد',
                                        'Monday' => 'الإثنين',
                                        'Tuesday' => 'الثلاثاء',
                                        'Wednesday' => 'الأربعاء',
                                        'Thursday' => 'الخميس',
                                        'Friday' => 'الجمعة',
                                    ];
                                    $todayArabic = $daysMap[date('l')] ?? 'السبت';
                                    $consultantDoctors = \App\Models\Doctor::where('type', 'consultant')
                                        ->where('is_active', true)
                                        ->where('is_available_today', true)
                                        ->whereJsonContains('working_days', [$todayArabic])
                                        ->with('user', 'department')
                                        ->get();
                                @endphp
                                @foreach($consultantDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">
                                        {{ $doctor->user?->name ?? 'بدون اسم' }}
                                        @if($doctor->department)
                                            - {{ $doctor->department->name }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">يمكن اختيار أكثر من طبيب بالضغط على Ctrl أو Shift ثم النقر.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">سبب الاستشارة</label>
                            <select name="reason" class="form-select" required>
                                <option value="">اختر السبب</option>
                                <option value="follow_up_emergency">متابعة حالة طوارئ</option>
                                <option value="specialist_consultation">استشارة متخصص</option>
                                <option value="surgery_consultation">استشارة جراحية</option>
                                <option value="chronic_condition">حالة مزمنة</option>
                                <option value="other">أخرى</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">ملاحظات إضافية</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالاستشارة..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-calendar-check me-2"></i>
                        إنشاء الموعد الاستشاري
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($emergencies as $emergency)
<div class="modal fade" id="labModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="labModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labModalLabel-{{ $emergency->id }}">
                    <i class="fas fa-flask me-2"></i>
                    طلب تحاليل طبية - حالة طوارئ #{{ $emergency->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.request-lab', $emergency) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر التحاليل المطلوبة للمريض: <strong>{{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد' }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="urgent">عاجل</option>
                            <option value="critical">حرج</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التحاليل المطلوبة <span class="text-danger">*</span></label>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            @foreach($labTests as $test)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="lab-{{ $emergency->id }}-{{ $test->id }}">
                                    <label class="form-check-label" for="lab-{{ $emergency->id }}-{{ $test->id }}">
                                        {{ $test->name }}
                                        <small class="text-muted">({{ number_format($test->price) }} IQD)</small>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالتحاليل..."></textarea>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>
                        تأكيد طلب التحاليل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($emergencies as $emergency)
<div class="modal fade" id="radiologyModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="radiologyModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="radiologyModalLabel-{{ $emergency->id }}">
                    <i class="fas fa-x-ray me-2"></i>
                    طلب أشعة - حالة طوارئ #{{ $emergency->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.request-radiology', $emergency) }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        اختر أنواع الأشعة المطلوبة للمريض: <strong>{{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد' }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الأولوية <span class="text-danger">*</span></label>
                        <select name="priority" class="form-select" required>
                            <option value="urgent">عاجل</option>
                            <option value="critical">حرج</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">أنواع الأشعة المطلوبة <span class="text-danger">*</span></label>
                        <div class="row g-2" style="max-height: 300px; overflow-y: auto;">
                            @foreach($radiologyTypes as $type)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="radiology_type_ids[]" value="{{ $type->id }}" id="radiology-{{ $emergency->id }}-{{ $type->id }}">
                                    <label class="form-check-label" for="radiology-{{ $emergency->id }}-{{ $type->id }}">
                                        {{ $type->name }}
                                        <small class="text-muted">({{ number_format($type->price) }} IQD)</small>
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات إضافية</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="أي ملاحظات خاصة بالأشعة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer gap-2">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-info">
                        <i class="fas fa-check me-2"></i>
                        تأكيد طلب الأشعة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

@foreach($emergencies as $emergency)
    @php
        $latestCompletedLab = $emergency->labRequests
            ->where('status', 'completed')
            ->sortByDesc('completed_at')
            ->first();
        $labResults = $latestCompletedLab
            ? $latestCompletedLab->labTests->filter(function($test){ return !empty(trim((string)($test->pivot->result ?? ''))); })->values()
            : collect();
    @endphp
    @if($latestCompletedLab)
    <div class="modal fade" id="labResultsModal-{{ $emergency->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-vial me-2"></i>
                        نتائج التحاليل - حالة طوارئ #{{ $emergency->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="result-meta mb-3">
                        <span class="badge bg-success">مكتمل</span>
                        <small class="text-muted ms-2">{{ optional($latestCompletedLab->completed_at)->format('d/m/Y H:i') }}</small>
                    </div>
                    @if($labResults->count())
                        @foreach($labResults as $test)
                            <div class="result-card mb-2">
                                <div class="result-card__title">{{ $test->name }}</div>
                                <div class="result-card__value">{{ $test->pivot->result }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-light border text-muted mb-0">تم إكمال الطلب بدون نتائج مدخلة.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach

@foreach($emergencies as $emergency)
    @php
        $latestCompletedRadiology = $emergency->radiologyRequests
            ->where('status', 'completed')
            ->sortByDesc('completed_at')
            ->first();
        $radiologyResults = $latestCompletedRadiology
            ? $latestCompletedRadiology->radiologyTypes->filter(function($type){ return !empty(trim((string)($type->pivot->result ?? ''))); })->values()
            : collect();
    @endphp
    @if($latestCompletedRadiology)
    <div class="modal fade" id="radiologyResultsModal-{{ $emergency->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-x-ray me-2"></i>
                        نتائج الأشعة - حالة طوارئ #{{ $emergency->id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="result-meta mb-3">
                        <span class="badge bg-success">مكتمل</span>
                        <small class="text-muted ms-2">{{ optional($latestCompletedRadiology->completed_at)->format('d/m/Y H:i') }}</small>
                    </div>
                    @if($radiologyResults->count())
                        @foreach($radiologyResults as $type)
                            <div class="result-card mb-2">
                                <div class="result-card__title">
                                    {{ $type->name }}
                                    @if(!empty($type->pivot->image_path))
                                        <span class="badge bg-light text-dark border ms-2">مرفق</span>
                                    @endif
                                </div>
                                <div class="result-card__value">{{ $type->pivot->result }}</div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-light border text-muted mb-0">تم إكمال الطلب بدون نتائج مدخلة.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    <div class="modal fade" id="treatmentResultsModal-{{ $emergency->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-pills me-2"></i>
                        العلاجات المسجلة - حالة طوارئ #{{ $emergency->id }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    @php
                        $treatmentTypes = [
                            'medication' => 'دوائي',
                            'injection' => 'إبرة',
                            'drip' => 'محلول',
                            'oxygen' => 'أكسجين',
                            'other' => 'أخرى',
                        ];
                        $statusLabels = [
                            'planned' => 'مخطط',
                            'in_progress' => 'قيد التنفيذ',
                            'completed' => 'مكتمل',
                            'cancelled' => 'ملغي',
                        ];
                    @endphp
                    @if($emergency->treatments->count())
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">نوع العلاج</th>
                                        <th>اسم الدواء / العلاج</th>
                                        <th style="width: 120px;">المرات يومياً</th>
                                        <th style="width: 140px;">الحالة</th>
                                        <th style="width: 140px;">تاريخ البدء</th>
                                        <th style="width: 140px;">تاريخ الانتهاء</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($emergency->treatments as $treatment)
                                        <tr>
                                            <td>{{ $treatmentTypes[$treatment->treatment_type] ?? $treatment->treatment_type }}</td>
                                            <td>{{ $treatment->description }}</td>
                                            <td>{{ $treatment->frequency_per_day ? $treatment->frequency_per_day . ' مرة' : '-' }}</td>
                                            <td>{{ $statusLabels[$treatment->status] ?? $treatment->status }}</td>
                                            <td>{{ optional($treatment->started_at)->format('d/m/Y') ?? '-' }}</td>
                                            <td>{{ optional($treatment->completed_at)->format('d/m/Y') ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-light border text-muted mb-0">
                            لا يوجد أي علاج مسجل بعد لهذه الحالة.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endforeach
@endpush

@endsection

@section('scripts')
<script>
document.addEventListener('click', function(event) {
    if (event.target.closest('.add-service-row')) {
        const button = event.target.closest('.add-service-row');
        const emergencyId = button.getAttribute('data-emergency-id');
        const container = document.getElementById(`service-rows-${emergencyId}`);
        if (!container) {
            return;
        }
        const template = document.getElementById('service-row-template');
        const clone = template.content.cloneNode(true);
        container.appendChild(clone);
    }

    if (event.target.closest('.remove-service-row')) {
        const row = event.target.closest('.service-row');
        const container = row.closest('.service-rows');
        if (container && container.querySelectorAll('.service-row').length > 1) {
            row.remove();
        } else if (row) {
            row.querySelector('select').value = '';
        }
    }

    if (event.target.closest('.add-treatment-row')) {
        const button = event.target.closest('.add-treatment-row');
        const emergencyId = button.getAttribute('data-emergency-id');
        const container = document.getElementById(`treatment-rows-${emergencyId}`);
        const template = document.getElementById(`treatment-row-template-${emergencyId}`);
        if (!container || !template) {
            return;
        }
        const index = container.querySelectorAll('.treatment-row').length;
        const clone = template.content.cloneNode(true);
        clone.querySelectorAll('[data-name]').forEach(el => {
            el.setAttribute('name', el.getAttribute('data-name').replace('__index__', index));
        });
        container.appendChild(clone);
    }

    if (event.target.closest('.remove-treatment-row')) {
        const row = event.target.closest('.treatment-row');
        const container = row.closest('tbody');
        if (container && container.querySelectorAll('.treatment-row').length > 1) {
            row.remove();
        } else if (row) {
            row.querySelectorAll('select, textarea, input').forEach(field => {
                field.value = '';
            });
        }
    }
});
</script>

<template id="service-row-template">
    <div class="service-row d-flex gap-2 align-items-start mb-2">
        <select name="service_ids[]" class="form-select">
            <option value="">اختر الخدمة</option>
            @foreach($emergencyServices as $service)
                <option value="{{ $service->id }}">
                    {{ $service->name }} - {{ number_format($service->price) }} IQD
                </option>
            @endforeach
        </select>
        <button type="button" class="btn btn-outline-danger btn-sm remove-service-row" aria-label="حذف">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</template>

@foreach($emergencies as $emergency)
<template id="treatment-row-template-{{ $emergency->id }}">
    <tr class="treatment-row">
        <td>
            <select data-name="treatments[__index__][treatment_type]" class="form-select" required>
                <option value="">اختر النوع</option>
                <option value="medication">دوائي</option>
                <option value="injection">إبرة</option>
                <option value="drip">محلول</option>
                <option value="oxygen">أكسجين</option>
                <option value="other">أخرى</option>
            </select>
        </td>
        <td>
            <textarea data-name="treatments[__index__][description]" class="form-control" rows="2" placeholder="اسم الدواء أو العلاج..." required></textarea>
            <input type="hidden" data-name="treatments[__index__][notes]" value="">
        </td>
        <td>
            <input type="number" data-name="treatments[__index__][frequency_per_day]" class="form-control" min="1" max="24" placeholder="عدد المرات" aria-label="عدد المرات يومياً">
        </td>
        <td>
            <select data-name="treatments[__index__][status]" class="form-select" required>
                <option value="planned">مخطط</option>
                <option value="in_progress">قيد التنفيذ</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
            </select>
        </td>
        <td>
            <input type="date" data-name="treatments[__index__][started_at]" class="form-control">
        </td>
        <td>
            <input type="date" data-name="treatments[__index__][completed_at]" class="form-control">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remove-treatment-row" aria-label="حذف">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    </tr>
</template>
@endforeach

<style>
.medical-modal {
    border: 0;
    overflow: hidden;
}

.medical-modal__header {
    background: linear-gradient(120deg, #f8fafc 0%, #eef2f7 100%);
    border-bottom: 1px solid #e9ecef;
}

.medical-modal .card {
    border-radius: 12px;
}

.medical-modal .card-header {
    border-bottom: 1px solid #eef2f7;
}

.medical-modal .form-control {
    border-radius: 10px;
}

.medical-modal .form-select {
    border-radius: 10px;
}

.result-meta {
    display: flex;
    align-items: center;
    gap: 8px;
}

.result-card {
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 10px 12px;
    background: #f8fafc;
}

.result-card__title {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.result-card__value {
    color: #4b5563;
    white-space: pre-wrap;
}
</style>
@endsection