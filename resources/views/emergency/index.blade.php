{{-- resources/views/emergency/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-ambulance me-2 text-danger"></i>
                    إدارة الطوارئ
                </h2>
                <div>
                    <span class="badge bg-success" id="emergency-live-indicator">
                        <i class="fas fa-circle fa-xs"></i> مباشر
                    </span>
                    <small class="text-muted ms-2" id="emergency-last-update">آخر تحديث: الآن</small>
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

    <!-- تبويبات الفلترة السريعة وفلتر التاريخ -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 15px;">
        <div class="card-body p-3">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
                <!-- التبويبات -->
                <ul class="nav nav-pills gap-2">
                    <li class="nav-item">
                        <a class="nav-link {{ (request('filter', 'today') === 'today' && !request('date')) ? 'active bg-primary' : 'bg-light text-dark' }} fw-bold" href="{{ route('emergency.index', ['filter' => 'today']) }}">
                            <i class="fas fa-calendar-day me-1"></i> حالات اليوم 
                            <span class="badge bg-white text-primary ms-1">{{ $stats['today'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ (request('filter') === 'active' && !request('date')) ? 'active bg-success' : 'bg-light text-dark' }} fw-bold" href="{{ route('emergency.index', ['filter' => 'active']) }}">
                            <i class="fas fa-heartbeat me-1"></i> النشطة حالياً
                            <span class="badge bg-white text-success ms-1">{{ $stats['active'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ (request('filter') === 'discharged' && !request('date')) ? 'active bg-secondary' : 'bg-light text-dark' }} fw-bold" href="{{ route('emergency.index', ['filter' => 'discharged']) }}">
                            <i class="fas fa-user-check me-1"></i> المغادرين
                            <span class="badge bg-white text-secondary ms-1">{{ $stats['discharged'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ (request('filter') === 'transferred' && !request('date')) ? 'active bg-info text-dark' : 'bg-light text-dark' }} fw-bold" href="{{ route('emergency.index', ['filter' => 'transferred']) }}">
                            <i class="fas fa-exchange-alt me-1"></i> المحولين
                            <span class="badge bg-white text-dark ms-1">{{ $stats['transferred'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ (request('filter') === 'all' && !request('date')) ? 'active bg-dark' : 'bg-light text-dark' }} fw-bold" href="{{ route('emergency.index', ['filter' => 'all']) }}">
                            <i class="fas fa-archive me-1"></i> السجل الكامل
                            <span class="badge bg-white text-dark ms-1">{{ $stats['total'] ?? 0 }}</span>
                        </a>
                    </li>
                </ul>

                <!-- نموذج البحث والتاريخ -->
                <form action="{{ route('emergency.index') }}" method="GET" class="d-flex flex-wrap gap-2 align-items-center">
                    @if(request('filter') && !request('date'))
                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif
                    <div class="input-group" style="min-width: 170px;">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-calendar-alt text-primary"></i></span>
                        <input type="date" name="date" class="form-control border-start-0" value="{{ request('date') }}" title="تاريخ الحالات" onchange="this.form.submit()">
                    </div>
                    <div class="input-group" style="min-width: 220px;">
                        <input type="text" name="search" class="form-control" placeholder="بحث باسم أو رقم المريض..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                    @if(request('date') || request('search') || (request('filter') && request('filter') !== 'today'))
                        <a href="{{ route('emergency.index') }}" class="btn btn-outline-danger" title="إعادة تعيين">
                            <i class="fas fa-times"></i>
                        </a>
                    @endif
                </form>
            </div>
        </div>
    </div>

    @if(isset($pendingClinicReferrals) && $pendingClinicReferrals->count() > 0)
        <!-- إحالات الطوارئ والتمريض الواردة من العيادات الاستشارية (المرضى العامين / كاش) -->
        <div class="row mb-3 animate__animated animate__fadeIn">
            <div class="col-12">
                <div class="card border-0 shadow-sm border-start border-4 border-danger rounded-3 bg-white">
                    <div class="card-header bg-danger bg-opacity-10 py-2 px-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-ambulance text-danger fs-6"></i>
                            <h6 class="mb-0 fw-bold text-dark small">📥 إحالات الطوارئ والتمريض الواردة من العيادات الاستشارية (المرضى العامين / كاش)</h6>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-2 py-1 small">{{ $pendingClinicReferrals->count() }} إحالة جديدة</span>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            @foreach($pendingClinicReferrals as $pRef)
                                <div class="col-lg-6 col-12">
                                    <div class="p-2 border rounded-2 bg-light shadow-2xs d-flex justify-content-between align-items-center gap-2">
                                        <div class="text-truncate">
                                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                                <strong class="text-dark small">{{ optional(optional($pRef->visit)->patient)->name ?? optional(optional(optional($pRef->visit)->patient)->user)->name ?? 'مريض' }}</strong>
                                                <span class="badge bg-warning text-dark px-1 py-0 rounded-pill" style="font-size: 0.65rem;">{{ $pRef->priority ?? 'عاجل' }}</span>
                                                <span class="badge bg-secondary text-white px-1 py-0 rounded-pill" style="font-size: 0.60rem;">
                                                    <i class="fas fa-clock me-1"></i>{{ $pRef->created_at ? $pRef->created_at->format('H:i') : '' }}
                                                </span>
                                            </div>
                                            <small class="text-muted d-block text-truncate" style="font-size: 0.72rem;">
                                                <i class="fas fa-user-md text-secondary me-1"></i>د. {{ optional(optional(optional($pRef->visit)->doctor)->user)->name ?? 'الاستشاري' }}
                                                — <span class="text-danger fw-bold">{{ $pRef->description }}</span>
                                            </small>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="{{ route('emergency.create', ['patient_id' => optional($pRef->visit)->patient_id, 'from_referral_id' => $pRef->id, 'return_to' => 'emergency']) }}" class="btn btn-xs btn-danger text-white fw-bold shadow-xs py-1 px-2" style="font-size: 0.75rem;" title="فتح تذكرة وقبول الإحالة فوراً">
                                                <i class="fas fa-bolt me-1"></i> فتح تذكرة وقبول
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @php
        $actualUnpaidCount = $emergencies->filter(fn($e) => $e->hasUnpaidDues())->count();
    @endphp

    @if($actualUnpaidCount > 0)
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-danger border-0 shadow-sm" style="border-radius: 12px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    يوجد {{ $actualUnpaidCount }} حالة طوارئ عليها خدمات مستحقة لم تُسدد بعد في الكاشير.
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive" style="overflow: visible">
                        <table class="table table-hover text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th class="text-start">المريض</th>
                                    <th>الحالة</th>
                                    <th>الطبيب المسؤول</th>
                                    <th>وقت الدخول</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($emergencies as $emergency)
                                <tr class="{{ $emergency->status === 'discharged' ? ($emergency->discharge_type === 'against_medical_advice' ? 'table-secondary opacity-75' : ($emergency->discharge_type === 'transferred_to_consultation' ? 'table-info table-opacity-25' : 'table-light')) : ($emergency->payment_status == 'paid' ? 'table-success' : ($emergency->payment_status == 'pending' ? 'table-danger' : ($emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'high' ? 'table-warning' : '')))) }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-start">
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
                                                @if($emergency->patient?->healthInsuranceCategory || ($emergency->patient?->insurance_type && $emergency->patient?->insurance_type !== 'none'))
                                                    <br>
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-1 py-0 mt-1" style="font-size: 0.72rem;">
                                                        <i class="fas fa-shield-alt me-1"></i>{{ $emergency->patient?->healthInsuranceCategory?->name ?? 'ضمان صحي' }}
                                                    </span>
                                                @endif
                                                <br>
                                                <small class="text-muted">
                                                    رقم الطوارئ: #{{ $emergency->id }}
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @php
                                            $surgeryBooking = $emergency->latestSurgery;
                                            $bedBooking = $emergency->latestBedReservation;
                                        @endphp

                                        @if($emergency->requires_surgery && $surgeryBooking)
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-danger text-white px-2 py-1 shadow-sm" title="تم حجز العملية بنجاح">
                                                    <i class="fas fa-procedures me-1"></i> محول لعمليات #{{ $surgeryBooking->id }}
                                                </span>
                                                @if($surgeryBooking->room)
                                                    <small class="text-muted mt-1" style="font-size: 0.75rem;">
                                                        غ {{ $surgeryBooking->room->room_number }}
                                                    </small>
                                                @endif
                                            </div>
                                        @elseif($emergency->requires_admission && $bedBooking)
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-primary text-white px-2 py-1 shadow-sm fw-bold" title="تم حجز الرقود والسرير بنجاح">
                                                    <i class="fas fa-bed me-1"></i> محول لرقود
                                                </span>
                                                @if($bedBooking->room)
                                                    <small class="text-muted mt-1" style="font-size: 0.75rem;">
                                                        غ {{ $bedBooking->room->room_number }}
                                                    </small>
                                                @endif
                                            </div>
                                        @elseif($emergency->status === 'discharged')
                                            <div class="d-flex flex-column align-items-center">
                                                @if($emergency->discharge_type === 'recovered')
                                                    <span class="badge bg-success text-white px-2 py-1 shadow-sm" title="خرج متعافي">
                                                        <i class="fas fa-check-circle me-1"></i> خرج متعافي
                                                    </span>
                                                @elseif($emergency->discharge_type === 'against_medical_advice')
                                                    <span class="badge bg-warning text-dark px-2 py-1 shadow-sm fw-bold" title="خرج على مسؤوليته">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> خرج على مسؤوليته
                                                    </span>
                                                @elseif($emergency->discharge_type === 'transferred_to_consultation')
                                                    <span class="badge bg-info text-dark px-2 py-1 shadow-sm fw-bold" title="تحويل للاستشارية">
                                                        <i class="fas fa-clinic-medical me-1"></i> تحويل للاستشارية
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary text-white px-2 py-1 shadow-sm">
                                                        <i class="fas fa-sign-out-alt me-1"></i> تم الخروج
                                                    </span>
                                                @endif
                                                <small class="text-muted mt-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-clock text-secondary me-1"></i>
                                                    خروج: {{ $emergency->discharge_time ? $emergency->discharge_time->format('H:i') : '' }}
                                                </small>
                                            </div>
                                        @elseif($emergency->status === 'cancelled')
                                            <span class="badge bg-danger text-white px-2 py-1 shadow-sm" title="حالة ملغاة">
                                                <i class="fas fa-ban me-1"></i> ملغاة
                                            </span>
                                        @else
                                            <span class="badge bg-info text-dark px-2 py-1 shadow-sm">
                                                <i class="fas fa-spinner fa-spin me-1"></i> قيد المتابعة
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($emergency->doctor)
                                            <span class="fw-bold text-dark"><i class="fas fa-user-md text-primary me-1"></i>{{ $emergency->doctor->user->name ?? $emergency->doctor->name ?? 'غير محدد' }}</span>
                                        @else
                                            <span class="text-muted">غير محدد</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">{{ $emergency->created_at->format('H:i') }}</div>
                                        <small class="text-muted">{{ $emergency->created_at->format('Y-m-d') }}</small>
                                    </td>
                                    <td>
                                        @php
                                            $isRowUnpaid = ($emergency->payment_status !== 'paid' && $emergency->payment_status !== 'waived') && $emergency->hasUnpaidDues();
                                            $patientRowName = $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'مريض طوارئ';
                                        @endphp
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            @if($emergency->status === 'cancelled')
                                                <button type="button" class="btn btn-sm btn-secondary disabled opacity-75 shadow-none d-inline-flex align-items-center" disabled title="هذه الحالة ملغاة ومغلقة نهائياً ولا يمكن فتحها">
                                                    <i class="fas fa-lock me-1 text-danger"></i>
                                                    <span>ملغاة ومغلقة 🔒</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary disabled shadow-none" disabled title="لا يوجد تقرير لحالة ملغاة">
                                                    <i class="fas fa-print text-muted"></i>
                                                </button>
                                            @elseif($emergency->status === 'discharged')
                                                <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm d-inline-flex align-items-center" title="عرض ملف الحالة وسجل الفحص">
                                                    <i class="fas fa-file-medical me-1"></i>
                                                    <span>فتح ملف الكشف</span>
                                                </a>
                                                @if($isRowUnpaid)
                                                    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm disabled" disabled title="معلق: بانتظار تسديد الرسوم في الكاشير">
                                                        <i class="fas fa-lock text-danger"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ route('emergency.medical-report', $emergency) }}" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="التقرير الطبي للحالة (طباعة / PDF / QR)">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            @else
                                                <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-success fw-bold shadow-sm d-inline-flex align-items-center" title="فتح محطة كشف الطوارئ">
                                                    <i class="fas fa-stethoscope me-1"></i>
                                                    <span>فتح ملف الكشف</span>
                                                </a>
                                                @if($isRowUnpaid)
                                                    <button type="button" class="btn btn-sm btn-outline-secondary shadow-sm disabled" disabled title="معلق: بانتظار تسديد الرسوم في الكاشير">
                                                        <i class="fas fa-lock text-danger"></i>
                                                    </button>
                                                @else
                                                    <a href="{{ route('emergency.medical-report', $emergency) }}" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm" title="التقرير الطبي للحالة (طباعة / PDF / QR)">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                @endif
                                            @endif

                                            @if($emergency->status !== 'discharged' && $emergency->status !== 'cancelled')
                                                <button type="button" class="btn btn-sm btn-outline-danger shadow-sm" title="إلغاء حالة الطوارئ"
                                                        onclick="openCancelEmergencyModal({{ $emergency->id }}, '{{ addslashes($patientRowName) }}', {{ $emergency->payment_status === 'paid' ? 'true' : 'false' }})">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4">
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
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        طلبات الخدمات التمريضية {{ request('filter') === 'discharged' ? '(المكتملة)' : (request('filter') === 'all' ? '(كافة الحالات)' : '(الجارية والمعلقة)') }}
                        <span class="badge bg-light text-success ms-2">{{ $nursingRequests->count() }}</span>
                    </h5>
                    @if(request('filter') !== 'discharged')
                        <small class="text-white-50"><i class="fas fa-check-circle me-1"></i>تختفي الحالات تلقائياً فور إنهاء الخدمة وتنتقل إلى تبويب المغادرين</small>
                    @endif
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

<!-- Modal إلغاء حالة الطوارئ من القائمة -->
<div class="modal fade" id="emergencyCancelIndexModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-danger text-white p-3">
                <h5 class="modal-title fw-bold mb-0">
                    <i class="fas fa-ban me-2"></i>تأكيد إلغاء حالة الطوارئ
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="emergencyCancelIndexForm" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light text-end">
                    <div class="card border-0 shadow-xs p-3 bg-white mb-3 rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">المريض:</span>
                            <strong id="cancelModalPatientName" class="text-dark">---</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">حالة السداد:</span>
                            <span id="cancelModalPaymentBadge" class="badge bg-secondary">غير مسدد</span>
                        </div>
                    </div>

                    <div id="cancelModalPaidNotice" class="alert alert-warning py-2 px-3 small border-warning d-none">
                        <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                        <strong>ملاحظة مالية:</strong> هذه الحالة مسددة مسبقاً في الكاشير. عند الإلغاء سيتم تحويل المريض مباشرة إلى <strong>مستحقات الاسترجاع بالكاشير</strong> لاسترداد المبلغ المسدد.
                    </div>

                    <div class="mb-3">
                        <label for="cancel_index_reason" class="form-label fw-bold text-dark small">
                            سبب إلغاء الحالة <span class="text-danger">*</span>:
                        </label>
                        <textarea class="form-control" id="cancel_index_reason" name="cancel_reason" rows="3" required
                                  placeholder="يرجى كتابة سبب الإلغاء بدقة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-white justify-content-between p-3">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger fw-bold px-4 shadow-sm">
                        <i class="fas fa-ban me-1"></i> تأكيد إلغاء الحالة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCancelEmergencyModal(emergencyId, patientName, isPaid) {
        var form = document.getElementById('emergencyCancelIndexForm');
        form.action = '/emergency/' + emergencyId + '/cancel';
        document.getElementById('cancelModalPatientName').textContent = patientName;
        
        var badge = document.getElementById('cancelModalPaymentBadge');
        var notice = document.getElementById('cancelModalPaidNotice');
        if (isPaid) {
            badge.className = 'badge bg-success';
            badge.textContent = 'مسدد مسبقاً';
            notice.classList.remove('d-none');
        } else {
            badge.className = 'badge bg-secondary';
            badge.textContent = 'غير مسدد';
            notice.classList.add('d-none');
        }
        
        var modal = new bootstrap.Modal(document.getElementById('emergencyCancelIndexModal'));
        modal.show();
    }
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

@endsection
