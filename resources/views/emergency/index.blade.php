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
                                <tr class="{{ $emergency->status === 'discharged' ? ($emergency->discharge_type === 'against_medical_advice' ? 'table-secondary opacity-75' : 'table-light') : ($emergency->payment_status == 'paid' ? 'table-success' : ($emergency->payment_status == 'pending' ? 'table-danger' : ($emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'high' ? 'table-warning' : '')))) }}">
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
                                            $surgeryBooking = $emergency->latestSurgery;
                                            $bedBooking = $emergency->latestBedReservation;
                                        @endphp

                                        @if($emergency->requires_surgery && $surgeryBooking)
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-danger text-white px-2 py-1 shadow-sm" title="تم حجز العملية بنجاح">
                                                    <i class="fas fa-procedures me-1"></i> تم حجز العملية #{{ $surgeryBooking->id }}
                                                </span>
                                                <div class="d-flex gap-1 align-items-center mt-1">
                                                    @if($surgeryBooking->room)
                                                        <span class="badge bg-primary px-1" style="font-size: 0.72rem;">
                                                            <i class="fas fa-bed me-1"></i> غ {{ $surgeryBooking->room->room_number }}
                                                        </span>
                                                    @endif
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="fas fa-calendar-check text-success me-1"></i>
                                                        {{ $surgeryBooking->scheduled_date ? $surgeryBooking->scheduled_date->format('Y-m-d') : '' }}
                                                    </small>
                                                </div>
                                            </div>
                                        @elseif($emergency->requires_admission && $bedBooking)
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="badge bg-warning text-dark px-2 py-1 shadow-sm fw-bold" title="تم حجز الرقود والسرير بنجاح">
                                                    <i class="fas fa-bed me-1"></i> تم حجز الرقود
                                                </span>
                                                <div class="d-flex gap-1 align-items-center mt-1">
                                                    @if($bedBooking->room)
                                                        <span class="badge bg-primary px-1" style="font-size: 0.72rem;">
                                                            <i class="fas fa-door-open me-1"></i> غ {{ $bedBooking->room->room_number }}
                                                        </span>
                                                    @endif
                                                    <small class="text-muted" style="font-size: 0.75rem;">
                                                        <i class="fas fa-calendar-check text-success me-1"></i>
                                                        {{ $bedBooking->scheduled_date ? \Carbon\Carbon::parse($bedBooking->scheduled_date)->format('Y-m-d') : '' }}
                                                    </small>
                                                </div>
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
                                                @else
                                                    <span class="badge bg-secondary text-white px-2 py-1 shadow-sm">
                                                        <i class="fas fa-sign-out-alt me-1"></i> تم الخروج
                                                    </span>
                                                @endif
                                                <small class="text-muted mt-1" style="font-size: 0.75rem;">
                                                    <i class="fas fa-clock text-secondary me-1"></i>
                                                    {{ $emergency->discharge_time ? $emergency->discharge_time->format('H:i') : '' }}
                                                </small>
                                                <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-outline-secondary py-0 px-2 mt-1 text-nowrap" style="font-size: 0.72rem;" title="عرض إضبارة وسجل الحالة">
                                                    <i class="fas fa-file-medical me-1"></i> عرض الملف
                                                </a>
                                            </div>
                                        @else
                                            <div class="d-flex align-items-center justify-content-center">
                                                @php
                                                    $pendingSubCount = $emergency->prescriptions
                                                        ->flatMap(fn($p) => $p->items)
                                                        ->where('substitution_status', 'pending_approval')
                                                        ->count();
                                                @endphp
                                                <div class="dropdown">
                                                    <button class="btn btn-sm {{ $pendingSubCount > 0 ? 'btn-danger' : ($emergency->status === 'transferred' ? 'btn-info text-dark' : 'btn-outline-secondary') }} dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ $pendingSubCount > 0 ? 'يوجد طلب بديل دوائي بانتظار الموافقة' : 'إجراءات أخرى' }}">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                        @if($emergency->status === 'transferred')
                                                            <span class="small fw-bold ms-1">{{ $emergency->requires_surgery ? 'محول لعمليات' : 'محول لرقود' }}</span>
                                                        @endif
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" style="min-width: 200px;">
                                                        <li>
                                                            <a class="dropdown-item d-flex align-items-center text-start text-success fw-bold" href="{{ route('emergency.show', $emergency) }}">
                                                                <i class="fas fa-file-medical text-success me-2"></i>
                                                                <span>فتح ملف الكشف</span>
                                                            </a>
                                                        </li>
                                                        @if($emergency->status === 'transferred')
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li class="px-3 py-1 text-center bg-light border-bottom mb-1">
                                                                <small class="text-primary fw-bold">
                                                                    <i class="fas fa-clock me-1"></i> بانتظار حجز الاستعلامات...
                                                                </small>
                                                            </li>
                                                        @endif
                                                        @if($emergency->status !== 'transferred' && $emergency->status !== 'discharged')
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('emergency.transfer-to-surgery', $emergency) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى صالة العمليات؟ سيتم تسجيله وترحيل بياناته تلقائياً.')">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item d-flex align-items-center text-start text-danger">
                                                                    <i class="fas fa-procedures me-2 text-danger"></i>
                                                                    <span>تحويل إلى العمليات</span>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('emergency.transfer-to-admission', $emergency) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى الرقود؟ سيتم إرسال طلب الحجز للاستعلامات.')">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item d-flex align-items-center text-start text-primary">
                                                                    <i class="fas fa-bed me-2 text-primary"></i>
                                                                    <span>تحويل رقود</span>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" onsubmit="return confirm('تأكيد تسجيل خروج المريض بحالة (خرج متعافي)؟')">
                                                                @csrf
                                                                <input type="hidden" name="discharge_type" value="recovered">
                                                                <button type="submit" class="dropdown-item d-flex align-items-center text-start text-success">
                                                                    <i class="fas fa-user-check me-2 text-success"></i>
                                                                    <span>خرج متعافي</span>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" onsubmit="return confirm('تأكيد تسجيل خروج المريض (خرج على مسؤوليته)؟')">
                                                                @csrf
                                                                <input type="hidden" name="discharge_type" value="against_medical_advice">
                                                                <button type="submit" class="dropdown-item d-flex align-items-center text-start text-warning">
                                                                    <i class="fas fa-user-shield me-2 text-warning"></i>
                                                                    <span>خرج على مسؤوليته</span>
                                                                </button>
                                                            </form>
                                                        </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        @endif
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
