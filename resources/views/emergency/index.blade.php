<!-- resources/views/emergency/index.blade.php -->
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

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>المريض</th>
                                    <th>نوع الطوارئ</th>
                                    <th>الأولوية</th>
                                    <th>الحالة</th>
                                    <th>نتائج التحاليل</th>
                                    <th>نتائج الأشعة</th>
                                    <th>الاستشارة</th>
                                    <th>العلامات الحيوية</th>
                                    <th>الطبيب المسؤول</th>
                                    <th>وقت الدخول</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($emergencies as $emergency)
                                <tr class="{{ $emergency->priority == 'critical' ? 'table-danger' : ($emergency->priority == 'high' ? 'table-warning' : '') }}">
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
                                        <span class="badge bg-secondary">{{ $emergency->emergency_type_text }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $emergency->priority_badge_class }}">{{ $emergency->priority_text }}</span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $emergency->status_badge_class }}">{{ $emergency->status_text }}</span>
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
                                                @php
                                                $consultationAppointment = \App\Models\Appointment::where('emergency_id', $emergency->id)->first();
                                            @endphp
                                            @if($consultationAppointment)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-calendar-check me-1"></i>
                                                    مجدول
                                                </span>
                                                <br>
                                                <small class="text-muted">{{ $consultationAppointment->appointment_date->format('d/m') }}</small>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#consultationModal-{{ $emergency->id }}" title="طلب استشارة">
                                                    <i class="fas fa-plus me-1"></i>
                                                    طلب
                                                </button>
                                            @endif
                                    </td>
                                    <td>
                                        <small>
                                            @if($emergency->vitals_last_updated)
                                                ضغط: {{ $emergency->blood_pressure ?? '---' }}<br>
                                                نبض: {{ $emergency->heart_rate ?? '---' }}<br>
                                                <span class="text-muted">{{ $emergency->vitals_last_updated->diffForHumans() }}</span>
                                            @else
                                                <span class="text-muted">لم يتم تسجيل</span>
                                            @endif
                                        </small>
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
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('emergency.show', $emergency) }}" class="btn btn-sm btn-outline-primary" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#vitalsModal-{{ $emergency->id }}" title="إدخال معلومات طبية">
                                                <i class="fas fa-notes-medical"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#labModal-{{ $emergency->id }}" title="طلب تحليل">
                                                <i class="fas fa-flask"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#radiologyModal-{{ $emergency->id }}" title="طلب أشعة">
                                                <i class="fas fa-x-ray"></i>
                                            </button>
                                            @php
                                                $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
                                            @endphp
                                            @if(!$hasConsultation)
                                                <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#consultationModal-{{ $emergency->id }}" title="طلب استشارة">
                                                    <i class="fas fa-calendar-plus"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="12" class="text-center py-4">
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
</div>
@endsection

@foreach($emergencies as $emergency)
<div class="modal fade" id="vitalsModal-{{ $emergency->id }}" tabindex="-1" aria-labelledby="vitalsModalLabel-{{ $emergency->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content medical-modal">
            <div class="modal-header medical-modal__header">
                <div>
                    <h5 class="modal-title" id="vitalsModalLabel-{{ $emergency->id }}">معلومات طبية للحالة #{{ $emergency->id }}</h5>
                    <small class="text-muted">حدّث العلامات الحيوية والتشخيص والخدمة المقدمة بسرعة</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form method="POST" action="{{ route('emergency.update-medical', $emergency) }}">
                @csrf
                @method('POST')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <strong>العلامات الحيوية</strong>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">ضغط الدم</label>
                                            <input type="text" name="blood_pressure" class="form-control" value="{{ $emergency->blood_pressure }}" placeholder="120/80">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">معدل ضربات القلب</label>
                                            <input type="number" name="heart_rate" class="form-control" value="{{ $emergency->heart_rate }}" placeholder="72">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">درجة الحرارة (°C)</label>
                                            <input type="number" step="0.1" name="temperature" class="form-control" value="{{ $emergency->temperature }}" placeholder="37.0">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">تشبع الأكسجين (SpO2 %)</label>
                                            <input type="number" name="oxygen_saturation" class="form-control" value="{{ $emergency->oxygen_saturation }}" placeholder="98">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">معدل التنفس (دقيقة)</label>
                                            <input type="number" name="respiratory_rate" class="form-control" value="{{ $emergency->respiratory_rate }}" placeholder="16">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="card border-0 shadow-sm h-100">
                                <div class="card-header bg-light">
                                    <strong>التقييم والخدمة</strong>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">التشخيص</label>
                                        <input type="text" name="diagnosis" class="form-control" value="{{ $emergency->diagnosis }}" placeholder="اكتب التشخيص هنا...">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">الخدمات المقدمة</label>
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
@endforeach

@foreach($emergencies as $emergency)
    @php
        $hasConsultation = \App\Models\Appointment::where('emergency_id', $emergency->id)->exists();
    @endphp
    @if(!$hasConsultation)
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
                            <label class="form-label">الطبيب الاستشاري</label>
                            <select name="doctor_id" class="form-select" required>
                                <option value="">اختر الطبيب</option>
                                @php
                                    $consultantDoctors = \App\Models\Doctor::where('type', 'consultant')
                                        ->where('is_active', true)
                                        ->where('is_available_today', true)
                                        ->with('user', 'department')
                                        ->get();
                                @endphp
                                @foreach($consultantDoctors as $doctor)
                                    <option value="{{ $doctor->id }}">
                                        د. {{ $doctor->user->name }} - {{ $doctor->department->name ?? 'غير محدد' }}
                                    </option>
                                @endforeach
                                @if($consultantDoctors->isEmpty())
                                    <option value="" disabled>لا يوجد أطباء استشاريون متاحون اليوم</option>
                                @endif
                            </select>
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
    @endif
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
@endforeach

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