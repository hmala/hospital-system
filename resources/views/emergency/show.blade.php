<!-- resources/views/emergency/show.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-2">
        <div class="col-12">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fas fa-ambulance text-danger me-2"></i>
                    <span>محطة كشف الطوارئ</span>
                    <span class="text-muted ms-2 fs-6">#{{ $emergency->id }}</span>
                </h4>
                <div class="no-print d-flex flex-wrap gap-1 align-items-center">
                    @if($emergency->status !== 'transferred' && $emergency->status !== 'discharged')
                        <form action="{{ route('emergency.transfer-to-surgery', $emergency) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى صالة العمليات؟ سيتم تسجيله وترحيل بياناته تلقائياً.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                <i class="fas fa-procedures me-1"></i>تحويل للعمليات
                            </button>
                        </form>
                        <form action="{{ route('emergency.transfer-to-admission', $emergency) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى الرقود؟ سيتم إرسال طلب الحجز للاستعلامات.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm">
                                <i class="fas fa-bed me-1"></i>تحويل رقود
                            </button>
                        </form>
                        <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تسجيل خروج المريض (خرج متعافي)؟')">
                            @csrf
                            <input type="hidden" name="discharge_type" value="recovered">
                            <button type="submit" class="btn btn-sm btn-success fw-bold shadow-sm">
                                <i class="fas fa-user-check me-1"></i>خرج متعافي
                            </button>
                        </form>
                        <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تسجيل خروج المريض (خرج على مسؤوليته)؟')">
                            @csrf
                            <input type="hidden" name="discharge_type" value="against_medical_advice">
                            <button type="submit" class="btn btn-sm btn-outline-warning text-dark fw-bold">
                                <i class="fas fa-user-shield me-1"></i>على مسؤوليته
                            </button>
                        </form>
                    @elseif($emergency->status === 'discharged')
                        @if($emergency->discharge_type === 'recovered')
                            <span class="badge bg-success fs-6 py-1 px-2">
                                <i class="fas fa-check-circle me-1"></i> خرج متعافي ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @elseif($emergency->discharge_type === 'against_medical_advice')
                            <span class="badge bg-warning text-dark fs-6 py-1 px-2">
                                <i class="fas fa-exclamation-triangle me-1"></i> خرج على مسؤوليته ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @endif
                    @endif
                    @if($emergency->payment && $emergency->payment->paid_at)
                        <button onclick="window.print()" class="btn btn-sm btn-primary shadow-sm">
                            <i class="fas fa-print me-1"></i>طباعة
                        </button>
                    @endif
                    <a href="{{ route('emergency.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>الرجوع للقائمة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @php
        $patientName = $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد';
        $patientPhone = $emergency->patient?->user?->phone ?? $emergency->emergencyPatient?->phone ?? null;
        $patientAge = $emergency->patient?->age ?? ($emergency->emergencyPatient?->date_of_birth ? $emergency->emergencyPatient->date_of_birth->age : null);
        $patientBlood = $emergency->patient?->blood_type ?? null;
        $patientGender = $emergency->patient?->gender ?? $emergency->emergencyPatient?->gender ?? null;
        $doctorName = $emergency->doctor?->user?->name ? 'د. ' . $emergency->doctor->user->name : null;
        $nurseName = $emergency->nurse?->user?->name ?? null;
        $complaint = $emergency->symptoms ?? $emergency->chief_complaint ?? $emergency->description ?? null;
    @endphp

    <!-- شريط بيانات المريض وحالة الطوارئ المدمج (Compact Patient & Triage Banner) -->
    <div class="card border-0 shadow-sm mb-3" style="border-radius: 12px; background: #ffffff;">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <!-- 1. هوية المريض والبيانات الشخصية -->
                <div class="col-lg-4 col-md-6 border-start-lg">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm flex-shrink-0"
                             style="width: 46px; height: 46px; background: linear-gradient(135deg, #0d6efd, #0b5ed7); font-size: 1.15rem;">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h5 class="fw-bold mb-1 text-truncate" title="{{ $patientName }}">
                                {{ $patientName }}
                                @if($emergency->emergencyPatient && !$emergency->patient)
                                    <span class="badge bg-secondary-subtle text-secondary border px-1" style="font-size: 0.65rem;">سجل مؤقت</span>
                                @endif
                            </h5>
                            <div class="d-flex flex-wrap gap-2 align-items-center text-muted small">
                                @if($patientPhone)
                                    <span><i class="fas fa-phone-alt me-1 text-secondary"></i><span dir="ltr">{{ $patientPhone }}</span></span>
                                @endif
                                @if($patientAge)
                                    <span class="badge bg-light text-dark border"><i class="fas fa-birthday-cake text-muted me-1"></i>{{ $patientAge }} سنة</span>
                                @endif
                                @if($patientBlood)
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold"><i class="fas fa-tint me-1"></i>{{ $patientBlood }}</span>
                                @endif
                                @if($patientGender)
                                    <span class="badge bg-light text-secondary border">{{ $patientGender === 'female' || $patientGender === 'أنثى' ? 'أنثى' : 'ذكر' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. بيانات الفرز والوقت والطبيب والشكوى -->
                <div class="col-lg-5 col-md-6 border-start-lg">
                    <div class="d-flex flex-wrap gap-3 align-items-center">
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fas fa-clock text-secondary me-1"></i>وقت الدخول</small>
                            <span class="fw-bold text-dark small">{{ $emergency->created_at->format('Y/m/d H:i') }}</span>
                        </div>
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fas fa-stethoscope text-primary me-1"></i>نوع الطوارئ</small>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">{{ $emergency->emergency_type_text }}</span>
                        </div>
                        @if($doctorName)
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fas fa-user-md text-info me-1"></i>الطبيب المسؤول</small>
                            <span class="fw-bold text-dark small">{{ $doctorName }}</span>
                        </div>
                        @endif
                        @if($nurseName)
                        <div>
                            <small class="text-muted d-block" style="font-size: 0.75rem;"><i class="fas fa-user-nurse text-success me-1"></i>الممرض</small>
                            <span class="fw-bold text-dark small">{{ $nurseName }}</span>
                        </div>
                        @endif
                    </div>
                    @if($complaint)
                        <div class="mt-2 text-truncate small" style="max-width: 480px;" title="{{ $complaint }}">
                            <span class="text-muted fw-bold">الشكوى / الوصف:</span>
                            <span class="text-dark">{{ Str::limit($complaint, 80) }}</span>
                        </div>
                    @endif
                </div>

                <!-- 3. الأولوية والحالة والإجراءات الحية -->
                <div class="col-lg-3 col-md-12 d-flex flex-row flex-lg-column align-items-start align-items-lg-end justify-content-between justify-content-lg-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $emergency->priority_badge_class }} px-2 py-1 shadow-sm" title="درجة أولوية الفرز">
                            <i class="fas fa-exclamation-circle me-1"></i>{{ $emergency->priority_text }}
                        </span>
                        <span class="badge {{ $emergency->status_badge_class }} px-2 py-1 shadow-sm" title="حالة الحالة في الطوارئ">
                            {{ $emergency->status_text }}
                        </span>
                    </div>
                    @if($emergency->services && $emergency->services->count() > 0)
                        <small class="text-muted" style="font-size: 0.75rem;">
                            <i class="fas fa-concierge-bell text-success me-1"></i>{{ $emergency->services->count() }} خدمات محتسبة
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- المحتوى الطبي الرئيسي - نظام التبويبات المتكامل (مطابق لمحطة الاستشارية) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 14px; overflow: hidden;">
                <div class="card-header bg-white border-bottom p-0">
                    <ul class="nav nav-tabs nav-fill border-bottom-0" id="emergencyWorkstationTabs" role="tablist" style="gap: 2px;">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-3 text-danger d-flex align-items-center justify-content-center gap-2" id="vitals-tab" data-bs-toggle="tab" data-bs-target="#tab-vitals" type="button" role="tab">
                                <i class="fas fa-heartbeat fa-lg"></i>
                                <span>العلامات الحيوية</span>
                                @if($emergency->blood_pressure || $emergency->heart_rate)
                                    <span class="badge bg-danger rounded-pill"><i class="fas fa-check"></i></span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 text-info d-flex align-items-center justify-content-center gap-2" id="diagnosis-tab" data-bs-toggle="tab" data-bs-target="#tab-diagnosis" type="button" role="tab">
                                <i class="fas fa-stethoscope fa-lg"></i>
                                <span>التشخيص والخدمات</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 text-primary d-flex align-items-center justify-content-center gap-2" id="requests-tab" data-bs-toggle="tab" data-bs-target="#tab-requests" type="button" role="tab">
                                <i class="fas fa-flask fa-lg"></i>
                                <span>المختبر والأشعة والنتائج</span>
                                @php
                                    $requestsTotal = ($emergency->labRequests ? $emergency->labRequests->count() : 0) + ($emergency->radiologyRequests ? $emergency->radiologyRequests->count() : 0);
                                @endphp
                                @if($requestsTotal > 0)
                                    <span class="badge bg-primary rounded-pill">{{ $requestsTotal }}</span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-3 text-success d-flex align-items-center justify-content-center gap-2" id="treatment-tab" data-bs-toggle="tab" data-bs-target="#tab-treatment" type="button" role="tab">
                                <i class="fas fa-pills fa-lg"></i>
                                <span>الأدوية وعلاج الطوارئ</span>
                                @if($emergency->treatments && $emergency->treatments->count() > 0)
                                    <span class="badge bg-success rounded-pill">{{ $emergency->treatments->count() }}</span>
                                @endif
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4 bg-light bg-opacity-25">
                    <div class="tab-content" id="emergencyWorkstationTabsContent">
                        
                        <!-- 1. تبويب العلامات الحيوية المباشر -->
                        <div class="tab-pane fade show active" id="tab-vitals" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-5">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-danger text-white py-3">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-plus-circle me-2"></i>تسجيل قراءة علامات حيوية جديدة</h5>
                                        </div>
                                        <div class="card-body p-4">
                                            <form action="{{ route('emergency.update-vitals', $emergency) }}" method="POST">
                                                @csrf
                                                <div class="row g-3">
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-tint text-primary me-1"></i>ضغط الدم</label>
                                                        <input type="text" class="form-control" name="blood_pressure" placeholder="120/80" value="{{ old('blood_pressure', $emergency->blood_pressure) }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-heart text-danger me-1"></i>النبض (bpm)</label>
                                                        <input type="number" class="form-control" name="heart_rate" placeholder="72" min="1" max="300" value="{{ old('heart_rate', $emergency->heart_rate) }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-thermometer-half text-warning me-1"></i>الحرارة (°C)</label>
                                                        <input type="number" step="0.1" class="form-control" name="temperature" placeholder="37.0" min="30" max="45" value="{{ old('temperature', $emergency->temperature) }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-wind text-success me-1"></i>الأكسجين (SpO2 %)</label>
                                                        <input type="number" class="form-control" name="oxygen_saturation" placeholder="98" min="1" max="100" value="{{ old('oxygen_saturation', $emergency->oxygen_saturation) }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-lungs text-info me-1"></i>التنفس (/دقيقة)</label>
                                                        <input type="number" class="form-control" name="respiratory_rate" placeholder="18" min="1" max="100" value="{{ old('respiratory_rate', $emergency->respiratory_rate) }}">
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <label class="form-label fw-bold"><i class="fas fa-vial text-secondary me-1"></i>سكر الدم (mg/dL)</label>
                                                        <input type="number" class="form-control" name="blood_glucose" placeholder="110" min="1" max="1000">
                                                    </div>
                                                    <div class="col-12 mt-4">
                                                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2 shadow-sm">
                                                            <i class="fas fa-save me-1"></i> حفظ وتحديث العلامات الحيوية
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-history text-secondary me-2"></i>سجل القراءات ومخطط الحالة</h5>
                                            <small class="text-muted">آخر تحديث: {{ $emergency->vitals_last_updated ? $emergency->vitals_last_updated->diffForHumans() : 'لم يسجل' }}</small>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row g-2 text-center mb-3">
                                                <div class="col-4">
                                                    <div class="p-2 border rounded bg-white">
                                                        <small class="text-muted d-block">ضغط الدم</small>
                                                        <h5 class="mb-0 text-primary fw-bold">{{ $emergency->blood_pressure ?? '---' }}</h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 border rounded bg-white">
                                                        <small class="text-muted d-block">النبض</small>
                                                        <h5 class="mb-0 text-danger fw-bold">{{ $emergency->heart_rate ?? '---' }} <small class="fs-6">bpm</small></h5>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="p-2 border rounded bg-white">
                                                        <small class="text-muted d-block">الأكسجين</small>
                                                        <h5 class="mb-0 text-success fw-bold">{{ $emergency->oxygen_saturation ?? '---' }} <small class="fs-6">%</small></h5>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($emergency->vitalSignReadings && $emergency->vitalSignReadings->count() > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-bordered table-hover align-middle mb-0 text-center">
                                                        <thead class="table-light">
                                                            <tr>
                                                                <th>الوقت</th>
                                                                <th>الضغط</th>
                                                                <th>النبض</th>
                                                                <th>الحرارة</th>
                                                                <th>SpO2</th>
                                                                <th>سكر</th>
                                                                <th>المسجل</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($emergency->vitalSignReadings as $reading)
                                                            <tr>
                                                                <td><small>{{ $reading->created_at->format('d/m H:i') }}</small></td>
                                                                <td><strong>{{ $reading->blood_pressure ?? '---' }}</strong></td>
                                                                <td>{{ $reading->heart_rate ?? '---' }}</td>
                                                                <td>{{ $reading->temperature ? $reading->temperature . '°' : '---' }}</td>
                                                                <td>{{ $reading->oxygen_saturation ? $reading->oxygen_saturation . '%' : '---' }}</td>
                                                                <td>{{ $reading->blood_glucose ?? '---' }}</td>
                                                                <td><small>{{ optional($reading->recordedBy)->name ?? 'الطاقم' }}</small></td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="alert alert-light text-center py-4 mb-0">
                                                    <i class="fas fa-heartbeat fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">لا توجد قراءات سابقة مسجلة لهذه الحالة.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. تبويب التشخيص والخدمات التمريضية والطبية -->
                        <div class="tab-pane fade" id="tab-diagnosis" role="tabpanel">
                            <form action="{{ route('emergency.update-medical', $emergency) }}" method="POST">
                                @csrf
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-info text-white py-3">
                                                <h5 class="mb-0 fw-bold"><i class="fas fa-stethoscope me-2"></i>التشخيص الطبي وتوصيات الطبيب</h5>
                                            </div>
                                            <div class="card-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">التشخيص الطبي والسريري (Diagnosis)</label>
                                                    <textarea class="form-control" name="diagnosis" rows="5" placeholder="اكتب التشخيص الطبي للحالة وتوصيات الرعاية...">{{ old('diagnosis', $emergency->diagnosis) }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">الخطة العلاجية المقترحة (Treatment Plan)</label>
                                                    <textarea class="form-control" name="treatment_plan" rows="3" placeholder="ملاحظات متابعة الخطة العلاجية...">{{ old('treatment_plan', $emergency->treatment_plan) }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="card border-0 shadow-sm h-100">
                                            <div class="card-header bg-success text-white py-3">
                                                <h5 class="mb-0 fw-bold"><i class="fas fa-concierge-bell me-2"></i>الخدمات الطبية والتمريضية المقدمة</h5>
                                            </div>
                                            <div class="card-body p-4">
                                                <label class="form-label fw-bold">اختر الخدمات المقدمة للحالة في الطوارئ:</label>
                                                <div class="row g-2 border rounded p-3 bg-white" style="max-height: 280px; overflow-y: auto;">
                                                    @php
                                                        $selectedServiceIds = $emergency->services->pluck('id')->toArray();
                                                    @endphp
                                                    @foreach($emergencyServices as $service)
                                                    <div class="col-12">
                                                        <div class="form-check d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <input class="form-check-input" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="srv_{{ $service->id }}" {{ in_array($service->id, $selectedServiceIds) ? 'checked' : '' }}>
                                                                <label class="form-check-label fw-bold" for="srv_{{ $service->id }}">
                                                                    {{ $service->name }}
                                                                </label>
                                                            </div>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle">{{ number_format($service->price) }} IQD</span>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                </div>
                                                <small class="text-muted d-block mt-2">يتم احتساب أجور الخدمات تلقائياً في حساب الكاشير الخاص بالحالة.</small>

                                                <div class="mt-4 pt-3 border-top text-end">
                                                    <button type="submit" class="btn btn-info text-white fw-bold px-4 shadow-sm">
                                                        <i class="fas fa-save me-1"></i> حفظ التشخيص والخدمات
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- 3. تبويب طلبات الفحوصات الطبية (المختبر والأشعة ونتائجها) -->
                        <div class="tab-pane fade" id="tab-requests" role="tabpanel">
                            <div class="row g-4">
                                <!-- طلبات واستعراض تحاليل المختبر -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-flask me-2"></i>الفحوصات المختبرية</h5>
                                            <button type="button" class="btn btn-light btn-sm fw-bold text-primary" data-bs-toggle="collapse" data-bs-target="#newLabRequestCollapse">
                                                <i class="fas fa-plus me-1"></i> طلب تحليل جديد
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- نموذج إضافة طلب مختبر جديد -->
                                            <div class="collapse mb-3" id="newLabRequestCollapse">
                                                <div class="card card-body border-primary p-3 bg-light">
                                                    <form action="{{ route('emergency.request-lab', $emergency) }}" method="POST">
                                                        @csrf
                                                        <h6 class="fw-bold text-primary mb-2">طلب تحاليل عاجلة للمختبر:</h6>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-bold">الأولوية</label>
                                                            <select name="priority" class="form-select form-select-sm" required>
                                                                <option value="urgent">عاجل (Urgent)</option>
                                                                <option value="critical">حرج وطارئ جداً (Critical)</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-bold">التحاليل المطلوبة</label>
                                                            <div class="border rounded p-2 bg-white" style="max-height: 180px; overflow-y: auto;">
                                                                @foreach($labTests as $test)
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="lab_chk_{{ $test->id }}">
                                                                    <label class="form-check-label small" for="lab_chk_{{ $test->id }}">
                                                                        {{ $test->name }} <span class="text-muted">({{ number_format($test->price) }} IQD)</span>
                                                                    </label>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات سريرية للمختبر...">
                                                        </div>
                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-primary btn-sm fw-bold px-3">
                                                                <i class="fas fa-paper-plane me-1"></i> إرسال الطلب للمختبر
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- عرض الطلبات السابقة ونتائجها -->
                                            @php
                                                $labRequests = $emergency->labRequests ? $emergency->labRequests->sortByDesc('requested_at') : collect();
                                            @endphp
                                            @if($labRequests->count() > 0)
                                                @foreach($labRequests as $labReq)
                                                    <div class="border rounded-3 p-3 mb-2 bg-white shadow-sm">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <span class="fw-bold text-dark">طلب مختبر #{{ $labReq->id }}</span>
                                                                <small class="text-muted ms-2">{{ optional($labReq->requested_at)->format('d/m H:i') }}</small>
                                                            </div>
                                                            <span class="badge {{ $labReq->status == 'completed' ? 'bg-success' : ($labReq->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}">
                                                                {{ $labReq->status_text }}
                                                            </span>
                                                        </div>
                                                        @if($labReq->status == 'completed')
                                                            <div class="p-2 rounded bg-light border">
                                                                <strong class="text-success small d-block mb-1"><i class="fas fa-check-circle me-1"></i>نتائج الفحوصات:</strong>
                                                                @foreach($labReq->labTests as $test)
                                                                    <div class="d-flex justify-content-between border-bottom py-1 small">
                                                                        <span>{{ $test->name }}</span>
                                                                        <span class="fw-bold {{ !empty($test->pivot->result) ? 'text-primary' : 'text-muted' }}">{{ $test->pivot->result ?: 'قيد الإدخال' }}</span>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <small class="text-muted d-block">الفحوصات قيد الفحص في المختبر...</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="alert alert-light text-center py-3 mb-0">
                                                    <p class="text-muted mb-0">لا توجد طلبات تحاليل مسجلة حتى الآن.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- طلبات واستعراض الأشعة والسونار -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm mb-4">
                                        <div class="card-header bg-info text-white py-3 d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 fw-bold"><i class="fas fa-x-ray me-2"></i>الأشعة والسونار (Radiology)</h5>
                                            <button type="button" class="btn btn-light btn-sm fw-bold text-info" data-bs-toggle="collapse" data-bs-target="#newRadRequestCollapse">
                                                <i class="fas fa-plus me-1"></i> طلب أشعة جديد
                                            </button>
                                        </div>
                                        <div class="card-body p-3">
                                            <!-- نموذج إضافة طلب أشعة جديد -->
                                            <div class="collapse mb-3" id="newRadRequestCollapse">
                                                <div class="card card-body border-info p-3 bg-light">
                                                    <form action="{{ route('emergency.request-radiology', $emergency) }}" method="POST">
                                                        @csrf
                                                        <h6 class="fw-bold text-info mb-2">طلب فحص أشعة / سونار عاجل:</h6>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-bold">الأولوية</label>
                                                            <select name="priority" class="form-select form-select-sm" required>
                                                                <option value="urgent">عاجل (Urgent)</option>
                                                                <option value="critical">حرج وطارئ جداً (Critical)</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label small fw-bold">أنواع الفحوصات المطلوبة</label>
                                                            <div class="border rounded p-2 bg-white" style="max-height: 180px; overflow-y: auto;">
                                                                @foreach($radiologyTypes as $radType)
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="radiology_type_ids[]" value="{{ $radType->id }}" id="rad_chk_{{ $radType->id }}">
                                                                    <label class="form-check-label small" for="rad_chk_{{ $radType->id }}">
                                                                        {{ $radType->name }} <span class="text-muted">({{ number_format($radType->price) }} IQD)</span>
                                                                    </label>
                                                                </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                        <div class="mb-2">
                                                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات سريرية لقسم الأشعة...">
                                                        </div>
                                                        <div class="text-end">
                                                            <button type="submit" class="btn btn-info text-white btn-sm fw-bold px-3">
                                                                <i class="fas fa-paper-plane me-1"></i> إرسال الطلب للأشعة
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>

                                            <!-- عرض طلبات الأشعة السابقة ونتائجها -->
                                            @php
                                                $radRequests = $emergency->radiologyRequests ? $emergency->radiologyRequests->sortByDesc('requested_at') : collect();
                                            @endphp
                                            @if($radRequests->count() > 0)
                                                @foreach($radRequests as $radReq)
                                                    <div class="border rounded-3 p-3 mb-2 bg-white shadow-sm">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div>
                                                                <span class="fw-bold text-dark">طلب أشعة #{{ $radReq->id }}</span>
                                                                <small class="text-muted ms-2">{{ optional($radReq->requested_at)->format('d/m H:i') }}</small>
                                                            </div>
                                                            <span class="badge {{ $radReq->status == 'completed' ? 'bg-success' : ($radReq->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}">
                                                                {{ $radReq->status_text }}
                                                            </span>
                                                        </div>
                                                        @if($radReq->status == 'completed')
                                                            <div class="p-2 rounded bg-light border">
                                                                <strong class="text-success small d-block mb-1"><i class="fas fa-check-circle me-1"></i>تقرير ونتائج الأشعة:</strong>
                                                                @foreach($radReq->radiologyTypes as $type)
                                                                    <div class="border-bottom py-1 small">
                                                                        <div class="fw-bold text-info">{{ $type->name }}</div>
                                                                        <p class="mb-0 text-muted">{{ $type->pivot->result ?: 'تم إنجاز التصوير بنجاح.' }}</p>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <small class="text-muted d-block">بانتظار تصوير المريض وإعداد التقرير الشعاعي...</small>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="alert alert-light text-center py-3 mb-0">
                                                    <p class="text-muted mb-0">لا توجد طلبات أشعة مسجلة حتى الآن.</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. تبويب الأدوية وعلاج الطوارئ والوصفة الطبية الإلكترونية -->
                        <div class="tab-pane fade" id="tab-treatment" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="fw-bold text-success mb-1">
                                        <i class="fas fa-prescription-bottle-alt me-2"></i>خطة العلاج والوصفة الطبية الإلكترونية
                                    </h5>
                                    <small class="text-muted">إعطاء وصرف الأدوية والمحاليل الوريدية والإبر وفق دليل التأمين والضمان الصحي الرسمي</small>
                                </div>
                                <button type="button" class="btn btn-success fw-bold shadow-sm" onclick="addMedication()">
                                    <i class="fas fa-plus me-1"></i> إضافة دواء / محلول
                                </button>
                            </div>

                            {{-- ───── لوحة تنبيهات البديل الدوائي ───── --}}
                            <div id="substitutionAlertPanel" class="d-none mb-4">
                                <div class="alert alert-warning border-warning shadow-sm p-0 overflow-hidden">
                                    <div class="d-flex align-items-center px-3 py-2 bg-warning bg-opacity-25 border-bottom border-warning">
                                        <i class="fas fa-exchange-alt text-warning fs-5 me-2"></i>
                                        <strong class="text-warning-emphasis">🔔 الصيدلية تقترح بدائل دوائية — بانتظار موافقتك</strong>
                                        <span id="subBadge" class="badge bg-warning text-dark ms-auto rounded-pill fs-6"></span>
                                    </div>
                                    <div id="substitutionItems" class="p-3"></div>
                                </div>
                            </div>

                            @if($emergency->treatments && $emergency->treatments->count() > 0)
                                <div class="card mb-4 border-0 shadow-sm">
                                    <div class="card-header bg-white py-2 border-bottom">
                                        <span class="fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i>العلاجات والمحاليل المعطاة للمريض ({{ $emergency->treatments->count() }}):</span>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle mb-0 text-center">
                                            <thead class="table-light">
                                                <tr>
                                                    <th style="width: 120px;">النوع</th>
                                                    <th class="text-start">اسم الدواء / العلاج</th>
                                                    <th>المرات يومياً</th>
                                                    <th class="text-start">ملاحظات وتعليمات</th>
                                                    <th>المسؤول</th>
                                                    <th>الوقت</th>
                                                    <th>الحالة</th>
                                                    <th style="width:60px;"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $typeBadges = [
                                                        'medication' => ['label' => 'دوائي', 'class' => 'bg-primary'],
                                                        'injection' => ['label' => 'إبرة', 'class' => 'bg-danger'],
                                                        'drip' => ['label' => 'محلول وريدي', 'class' => 'bg-info text-dark'],
                                                        'oxygen' => ['label' => 'أكسجين', 'class' => 'bg-success'],
                                                        'other' => ['label' => 'أخرى', 'class' => 'bg-secondary'],
                                                    ];
                                                @endphp
                                                @foreach($emergency->treatments as $treatment)
                                                <tr>
                                                    <td>
                                                        <span class="badge {{ $typeBadges[$treatment->treatment_type]['class'] ?? 'bg-secondary' }} px-2 py-1">
                                                            {{ $typeBadges[$treatment->treatment_type]['label'] ?? $treatment->treatment_type }}
                                                        </span>
                                                    </td>
                                                    <td class="text-start"><strong>{{ $treatment->description }}</strong></td>
                                                    <td>{{ $treatment->frequency_per_day ? $treatment->frequency_per_day . 'x' : '-' }}</td>
                                                    <td class="text-start"><small class="text-muted">{{ $treatment->notes ?: '-' }}</small></td>
                                                    <td><small>{{ $treatment->creator?->name ?? 'طاقم الطوارئ' }}</small></td>
                                                    <td><small class="text-muted">{{ $treatment->created_at->format('d/m H:i') }}</small></td>
                                                    <td><span class="badge bg-success">مكتمل</span></td>
                                                    <td>
                                                        <form method="POST" action="{{ route('emergency.treatments.destroy', [$emergency, $treatment]) }}"
                                                              onsubmit="return confirm('هل تريد حذف هذا العلاج وبنده من الوصفة؟')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="حذف">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <div class="card border border-success shadow-sm">
                                <div class="card-header bg-success bg-opacity-10 text-success fw-bold py-2">
                                    <i class="fas fa-edit me-1"></i> تسجيل وإعطاء أدوية جديدة:
                                </div>
                                <div class="card-body p-4">
                                    <form action="{{ route('emergency.treatments.store', $emergency) }}" method="POST" id="treatmentForm">
                                        @csrf
                                        <div id="medicationsContainer">
                                            <!-- سيتم توليد سطور الأدوية هنا -->
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                            <button type="button" class="btn btn-outline-success fw-bold" onclick="addMedication()">
                                                <i class="fas fa-plus me-1"></i> دواء إضافي
                                            </button>
                                            <button type="submit" class="btn btn-success btn-lg px-4 shadow-sm fw-bold">
                                                <i class="fas fa-save me-1"></i> حفظ وتوثيق العلاج
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal للعلامات الحيوية -->
<div class="modal fade" id="vitalSignsModal" tabindex="-1" aria-labelledby="vitalSignsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('emergency.update-vitals', $emergency) }}" method="POST">
                @csrf
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="vitalSignsModalLabel">
                        <i class="fas fa-heartbeat me-2"></i>
                        قياس العلامات الحيوية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        جميع القراءات اختيارية - أدخل فقط القراءات المتوفرة
                    </div>

                    <div class="row">
                        <!-- ضغط الدم -->
                        <div class="col-md-6 mb-3">
                            <label for="blood_pressure" class="form-label">
                                <i class="fas fa-tint text-primary me-2"></i>
                                ضغط الدم
                            </label>
                            <input type="text" 
                                   class="form-control form-control-lg" 
                                   id="blood_pressure" 
                                   name="blood_pressure" 
                                   placeholder="120/80"
                                   value="{{ old('blood_pressure', $emergency->blood_pressure) }}">
                            <small class="text-muted">مثال: 120/80</small>
                        </div>

                        <!-- معدل ضربات القلب -->
                        <div class="col-md-6 mb-3">
                            <label for="heart_rate" class="form-label">
                                <i class="fas fa-heart text-danger me-2"></i>
                                معدل ضربات القلب (bpm)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="heart_rate" 
                                   name="heart_rate" 
                                   placeholder="72"
                                   min="1"
                                   max="300"
                                   value="{{ old('heart_rate', $emergency->heart_rate) }}">
                            <small class="text-muted">المعدل الطبيعي: 60-100 نبضة/دقيقة</small>
                        </div>

                        <!-- درجة الحرارة -->
                        <div class="col-md-4 mb-3">
                            <label for="temperature" class="form-label">
                                <i class="fas fa-thermometer-half text-warning me-2"></i>
                                درجة الحرارة (°C)
                            </label>
                            <input type="number" 
                                   step="0.1" 
                                   class="form-control form-control-lg" 
                                   id="temperature" 
                                   name="temperature" 
                                   placeholder="37.0"
                                   min="30"
                                   max="45"
                                   value="{{ old('temperature', $emergency->temperature) }}">
                            <small class="text-muted">الطبيعي: 36.5-37.5°C</small>
                        </div>

                        <!-- معدل التنفس -->
                        <div class="col-md-4 mb-3">
                            <label for="respiratory_rate" class="form-label">
                                <i class="fas fa-lungs text-info me-2"></i>
                                معدل التنفس (/دقيقة)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="respiratory_rate" 
                                   name="respiratory_rate" 
                                   placeholder="16"
                                   min="1"
                                   max="100"
                                   value="{{ old('respiratory_rate', $emergency->respiratory_rate) }}">
                            <small class="text-muted">الطبيعي: 12-20 نفس/دقيقة</small>
                        </div>

                        <!-- نسبة الأكسجين -->
                        <div class="col-md-4 mb-3">
                            <label for="oxygen_saturation" class="form-label">
                                <i class="fas fa-wind text-success me-2"></i>
                                تشبع الأكسجين (%)
                            </label>
                            <input type="number" 
                                   class="form-control form-control-lg" 
                                   id="oxygen_saturation" 
                                   name="oxygen_saturation" 
                                   placeholder="98"
                                   min="1"
                                   max="100"
                                   value="{{ old('oxygen_saturation', $emergency->oxygen_saturation) }}">
                            <small class="text-muted">الطبيعي: 95-100%</small>
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

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 5px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
}

.timeline-title {
    margin-bottom: 5px;
    font-weight: bold;
}

.timeline-text {
    margin-bottom: 5px;
    color: #6c757d;
}
</style>

<style media="print">
    /* إخفاء جميع العناصر الأصلية */
    * {
        display: none !important;
    }

    /* إظهار الجسم فقط مع المحتوى المطبوع */
    body {
        display: block !important;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.6;
        color: #000;
        background: #fff !important;
        margin: 20px;
        direction: rtl;
    }

    /* إنشاء تخطيط منظم للطباعة */
    body::before {
        content: "تقرير حالة الطوارئ";
        display: block;
        font-size: 18pt;
        font-weight: bold;
        text-align: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #000;
        padding-bottom: 10px;
    }

    /* إنشاء جدول للمعلومات */
    body::after {
        content: "";
        display: table;
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    /* إنشاء صفوف الجدول باستخدام pseudo-elements */
    body::after {
        content:
            "معلومات عامة" "\A"
            "رقم الحالة: <?php echo $emergency->id; ?>" "\A"
            "وقت الدخول: <?php echo $emergency->created_at->format('d/m/Y H:i'); ?>" "\A"
            "الحالة: <?php echo $emergency->status_text; ?>" "\A"
            "الأولوية: <?php echo $emergency->priority_text; ?>" "\A"
            "نوع الطوارئ: <?php echo $emergency->emergency_type_text; ?>" "\A"
            "" "\A"
            "معلومات المريض" "\A"
            "الاسم: <?php echo $emergency->patient ? $emergency->patient->user->name : ($emergency->emergencyPatient ? $emergency->emergencyPatient->name : 'غير محدد'); ?>" "\A"
            "الهاتف: <?php echo $emergency->patient ? $emergency->patient->user->phone : ($emergency->emergencyPatient ? $emergency->emergencyPatient->phone : 'غير محدد'); ?>" "\A"
            "العمر: <?php echo $emergency->patient ? $emergency->patient->age : 'غير محدد'; ?> سنة" "\A"
            "فصيلة الدم: <?php echo $emergency->patient ? $emergency->patient->blood_type : 'غير محدد'; ?>" "\A"
            "" "\A"
            "الفريق الطبي" "\A"
            "الطبيب المسؤول: <?php echo $emergency->doctor ? 'د. ' . $emergency->doctor->user->name : 'غير محدد'; ?>" "\A"
            "الممرض المسؤول: <?php echo $emergency->nurse ? $emergency->nurse->user->name : 'غير محدد'; ?>" "\A"
            "" "\A"
            "العلامات الحيوية" "\A"
            "ضغط الدم: <?php echo $emergency->blood_pressure ?? '---'; ?>" "\A"
            "معدل ضربات القلب: <?php echo $emergency->heart_rate ?? '---'; ?> bpm" "\A"
            "درجة الحرارة: <?php echo $emergency->temperature ?? '---'; ?> °C" "\A"
            "تشبع الأكسجين: <?php echo $emergency->oxygen_saturation ?? '---'; ?> %" "\A"
            "معدل التنفس: <?php echo $emergency->respiratory_rate ?? '---'; ?> /min" "\A"
            "آخر تحديث: <?php echo $emergency->vitals_last_updated ? $emergency->vitals_last_updated->format('d/m/Y H:i') : 'لم يتم التحديث'; ?>" "\A"
            "" "\A"
            "التفاصيل الطبية" "\A"
            "وصف الحالة: <?php echo $emergency->description; ?>" "\A"
            "<?php if($emergency->required_actions): ?>" "\A"
            "الإجراءات المطلوبة: <?php echo $emergency->required_actions; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->diagnosis): ?>" "\A"
            "التشخيص: <?php echo $emergency->diagnosis; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_given): ?>" "\A"
            "العلاج المقدم: <?php echo $emergency->treatment_given; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_plan): ?>" "\A"
            "الخطة العلاجية: <?php echo $emergency->treatment_plan; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->discharged_at): ?>" "\A"
            "وقت المغادرة: <?php echo $emergency->discharged_at->format('d/m/Y H:i'); ?>" "\A"
            "<?php endif; ?>";
        white-space: pre-line;
        font-family: Arial, sans-serif;
        font-size: 12pt;
        line-height: 1.8;
        display: block;
    }

    /* تنسيق العناوين في المحتوى */
    body::after {
        content: "" "\A" "═══════════════════════════════════════════════" "\A" "معلومات عامة" "\A" "═══════════════════════════════════════════════" "\A"
            "رقم الحالة: <?php echo $emergency->id; ?>" "\A"
            "وقت الدخول: <?php echo $emergency->created_at->format('d/m/Y H:i'); ?>" "\A"
            "الحالة: <?php echo $emergency->status_text; ?>" "\A"
            "الأولوية: <?php echo $emergency->priority_text; ?>" "\A"
            "نوع الطوارئ: <?php echo $emergency->emergency_type_text; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "معلومات المريض" "\A" "═══════════════════════════════════════════════" "\A"
            "الاسم: <?php echo $emergency->patient ? $emergency->patient->user->name : ($emergency->emergencyPatient ? $emergency->emergencyPatient->name : 'غير محدد'); ?>" "\A"
            "الهاتف: <?php echo $emergency->patient ? $emergency->patient->user->phone : ($emergency->emergencyPatient ? $emergency->emergencyPatient->phone : 'غير محدد'); ?>" "\A"
            "العمر: <?php echo $emergency->patient ? $emergency->patient->age : 'غير محدد'; ?> سنة" "\A"
            "فصيلة الدم: <?php echo $emergency->patient ? $emergency->patient->blood_type : 'غير محدد'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "الفريق الطبي" "\A" "═══════════════════════════════════════════════" "\A"
            "الطبيب المسؤول: <?php echo $emergency->doctor ? 'د. ' . $emergency->doctor->user->name : 'غير محدد'; ?>" "\A"
            "الممرض المسؤول: <?php echo $emergency->nurse ? $emergency->nurse->user->name : 'غير محدد'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "العلامات الحيوية" "\A" "═══════════════════════════════════════════════" "\A"
            "ضغط الدم: <?php echo $emergency->blood_pressure ?? '---'; ?>" "\A"
            "معدل ضربات القلب: <?php echo $emergency->heart_rate ?? '---'; ?> bpm" "\A"
            "درجة الحرارة: <?php echo $emergency->temperature ?? '---'; ?> °C" "\A"
            "تشبع الأكسجين: <?php echo $emergency->oxygen_saturation ?? '---'; ?> %" "\A"
            "معدل التنفس: <?php echo $emergency->respiratory_rate ?? '---'; ?> /min" "\A"
            "آخر تحديث: <?php echo $emergency->vitals_last_updated ? $emergency->vitals_last_updated->format('d/m/Y H:i') : 'لم يتم التحديث'; ?>" "\A"
            "" "\A"
            "═══════════════════════════════════════════════" "\A" "التفاصيل الطبية" "\A" "═══════════════════════════════════════════════" "\A"
            "وصف الحالة: <?php echo $emergency->description; ?>" "\A"
            "<?php if($emergency->required_actions): ?>" "\A"
            "الإجراءات المطلوبة: <?php echo $emergency->required_actions; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->diagnosis): ?>" "\A"
            "التشخيص: <?php echo $emergency->diagnosis; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_given): ?>" "\A"
            "العلاج المقدم: <?php echo $emergency->treatment_given; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->treatment_plan): ?>" "\A"
            "الخطة العلاجية: <?php echo $emergency->treatment_plan; ?>" "\A"
            "<?php endif; ?>"
            "<?php if($emergency->discharged_at): ?>" "\A"
            "وقت المغادرة: <?php echo $emergency->discharged_at->format('d/m/Y H:i'); ?>" "\A"
            "<?php endif; ?>";
    }

    /* إعدادات الصفحة */
    @page {
        margin: 1cm;
        size: A4;
    }
</style>
@endsection

@push('styles')
<style>
.frequency-selector input[type="radio"]:checked + .frequency-btn {
    background: linear-gradient(135deg, #198754, #157347) !important;
    color: #fff !important;
    border-color: #146c43 !important;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(25, 135, 84, 0.35);
}
.select2-container--default .select2-selection--single {
    height: 38px !important;
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
    display: flex !important;
    align-items: center !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px !important;
    padding-left: 8px !important;
    padding-right: 8px !important;
    color: #212529 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}
.select2-dropdown {
    border-color: #198754 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    z-index: 1060 !important;
}
</style>
@endpush

@push('scripts')
<script>
window.availableMedicinesData = @json($availableMedicines ?? []);

window.handleMedicineSelect = function(selectElem) {
    const row = selectElem.closest('.medication-item');
    if (!row) return;

    const idInput = row.querySelector('.med-id-input');
    const nameInput = row.querySelector('.med-name-input');
    const dosageInput = row.querySelector('.med-dosage-input');
    const typeSelect = row.querySelector('.med-type-select');

    const val = selectElem.value;
    if (!val || val === '') {
        if (idInput) idInput.value = '';
        return;
    }

    if (val === 'custom') {
        if (idInput) idInput.value = '';
        if (nameInput) {
            nameInput.value = '';
            nameInput.focus();
        }
        return;
    }

    const medId = parseInt(val, 10);
    const med = (window.availableMedicinesData || []).find(m => m.id === medId);

    if (med) {
        if (idInput) idInput.value = med.id;
        if (nameInput) nameInput.value = med.name;
        if (dosageInput && med.strength) dosageInput.value = med.strength;

        if (typeSelect && med.dosage_form) {
            const formStr = (med.dosage_form || '').toLowerCase();
            if (formStr.includes('tab') || formStr.includes('cap') || formStr.includes('حبوب') || formStr.includes('كبسول')) {
                typeSelect.value = 'tablet';
            } else if (formStr.includes('inj') || formStr.includes('amp') || formStr.includes('vial') || formStr.includes('إبر') || formStr.includes('حقن')) {
                typeSelect.value = 'injection';
            } else if (formStr.includes('drip') || formStr.includes('infusion') || formStr.includes('محلول') || formStr.includes('مغذي')) {
                typeSelect.value = 'drip';
            } else if (formStr.includes('syr') || formStr.includes('susp') || formStr.includes('شراب') || formStr.includes('معلق')) {
                typeSelect.value = 'syrup';
            } else if (formStr.includes('cream') || formStr.includes('oint') || formStr.includes('gel') || formStr.includes('مرهم') || formStr.includes('كريم')) {
                typeSelect.value = 'cream';
            } else if (formStr.includes('drop') || formStr.includes('قطر')) {
                typeSelect.value = 'drops';
            } else {
                typeSelect.value = 'other';
            }
        }
    }
};

window.removeMedication = function(elem) {
    try {
        if (!elem) return;
        const item = elem.closest('.medication-item');
        if (item) {
            if (typeof $ !== 'undefined' && $.fn.select2) {
                const $s = $(item).find('.medicine-select2');
                if ($s.length && $s.hasClass('select2-hidden-accessible')) {
                    $s.select2('destroy');
                }
            }
            item.remove();
        }
    } catch(e) {
        console.error('Error removing medication:', e);
    }
};

    // إعادة ضبط عرض وحجم حقول الأدوية عند فتح تبويب العلاج
    const treatmentTabBtn = document.getElementById('treatment-tab');
    if (treatmentTabBtn) {
        treatmentTabBtn.addEventListener('shown.bs.tab', function () {
            if (typeof window.initMedicineSelect2 === 'function') {
                window.initMedicineSelect2('#medicationsContainer');
            }
        });
    }

window.initMedicineSelect2 = function(context) {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        const $targets = context ? $(context).find('.medicine-select2') : $('.medicine-select2');
        $targets.each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    placeholder: '-- ابحث بالاسم التجاري أو العلمي --',
                    width: '100%',
                    dir: 'rtl',
                    allowClear: true
                }).on('select2:select', function () {
                    window.handleMedicineSelect(this);
                }).on('select2:clear', function() {
                    window.handleMedicineSelect(this);
                });
            }
        });
    }
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-remove-medication');
    if (btn) {
        e.preventDefault();
        e.stopPropagation();
        window.removeMedication(btn);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    window.medicationIndex = 0;

    window.addMedication = function() {
        try {
            const container = document.getElementById('medicationsContainer');
            if (!container) return;

            let optionsHtml = '<option value="">-- ابحث بالاسم التجاري أو العلمي --</option>';
            if (window.availableMedicinesData && window.availableMedicinesData.length > 0) {
                window.availableMedicinesData.forEach(med => {
                    optionsHtml += `<option value="${med.id}">${med.name} ${med.strength || ''} (${med.generic_name || ''} - ${med.dosage_form || ''})</option>`;
                });
            }
            optionsHtml += '<option value="custom">✏️ كتابة اسم دواء يدوي غير مدرج</option>';

            const idx = window.medicationIndex;
            const medicationHtml = `
                <div class="medication-item card mb-3 border-success shadow-sm">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-pills text-success me-1"></i>
                                    اسم الدواء (دليل الضمان الصحي)
                                </label>
                                <select class="form-select medicine-select2" data-index="${idx}" onchange="handleMedicineSelect(this)">
                                    ${optionsHtml}
                                </select>
                                <input type="hidden" name="prescribed_medications[${idx}][medicine_id]" class="med-id-input" value="">
                                <input type="text" class="form-control med-name-input mt-2" name="prescribed_medications[${idx}][name]"
                                       placeholder="اسم الدواء الموصوف" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">الشكل الدوائي</label>
                                <select class="form-select med-type-select" name="prescribed_medications[${idx}][type]" required>
                                    <option value="tablet">حبوب / أقراص</option>
                                    <option value="injection">إبرة / حقن</option>
                                    <option value="drip">محلول وريدي / مغذي</option>
                                    <option value="syrup">شراب</option>
                                    <option value="cream">كريم / مرهم</option>
                                    <option value="drops">قطرات</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold">الجرعة / القوة</label>
                                <input type="text" class="form-control med-dosage-input" name="prescribed_medications[${idx}][dosage]"
                                       placeholder="مثال: 500mg" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold d-block mb-2">التكرار يومياً</label>
                                <div class="frequency-selector" style="display: flex; gap: 4px; flex-wrap: wrap;">
                                    <input type="radio" id="new_freq_${idx}_1" name="prescribed_medications[${idx}][frequency]" value="1" checked style="display: none;">
                                    <label for="new_freq_${idx}_1" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">1x</label>

                                    <input type="radio" id="new_freq_${idx}_2" name="prescribed_medications[${idx}][frequency]" value="2" style="display: none;">
                                    <label for="new_freq_${idx}_2" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">2x</label>

                                    <input type="radio" id="new_freq_${idx}_3" name="prescribed_medications[${idx}][frequency]" value="3" style="display: none;">
                                    <label for="new_freq_${idx}_3" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">3x</label>

                                    <input type="radio" id="new_freq_${idx}_4" name="prescribed_medications[${idx}][frequency]" value="4" style="display: none;">
                                    <label for="new_freq_${idx}_4" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">4x</label>

                                    <input type="radio" id="new_freq_${idx}_needed" name="prescribed_medications[${idx}][frequency]" value="as_needed" style="display: none;">
                                    <label for="new_freq_${idx}_needed" class="frequency-btn" style="padding: 4px 8px; border: 1px solid #ced4da; border-radius: 4px; cursor: pointer; font-size: 0.8rem; background: white;">حاجة</label>
                                </div>
                            </div>
                            <div class="col-md-1 d-flex align-items-end justify-content-center">
                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-medication" onclick="window.removeMedication(this)" title="حذف الدواء">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-2">
                                <label class="form-label text-muted small fw-bold">المدة</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][duration]"
                                       placeholder="مثال: 7 أيام">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-muted small fw-bold">التوقيت</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][times]"
                                       placeholder="صباحاً، بعد الأكل...">
                            </div>
                            <div class="col-md-7">
                                <label class="form-label text-muted small fw-bold">تعليمات وتوصيات خاصة للعلاج والصرف</label>
                                <input type="text" class="form-control form-control-sm" name="prescribed_medications[${idx}][instructions]"
                                       placeholder="مثال: يؤخذ بعد الطعام مباشرة مع كوب ماء وفير">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = medicationHtml.trim();
            const newRow = tempDiv.firstChild;
            container.appendChild(newRow);

            window.initMedicineSelect2(newRow);
            window.medicationIndex++;
        } catch (error) {
            console.error('Error in addMedication:', error);
        }
    };

    // إضافة سطر دواء أولي تلقائياً إذا كان الوعاء فارغاً
    if (document.getElementById('medicationsContainer') && document.querySelectorAll('.medication-item').length === 0) {
        window.addMedication();
    }
});

// ══════════════ بديل الدواء — طبيب الطوارئ ══════════════
(function () {
    const EMERGENCY_ID = {{ $emergency->id }};
    const SUB_URL      = '{{ route("emergency.substitution-requests", $emergency) }}';
    const RESPOND_URL  = '{{ route("emergency.respond-substitution", $emergency) }}';
    const CSRF         = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function renderSubstitutions(requests) {
        const panel = document.getElementById('substitutionAlertPanel');
        const container = document.getElementById('substitutionItems');
        const badge = document.getElementById('subBadge');

        if (!requests || requests.length === 0) {
            panel.classList.add('d-none');
            return;
        }

        badge.textContent = requests.length;
        panel.classList.remove('d-none');

        container.innerHTML = requests.map(req => `
            <div class="d-flex flex-wrap align-items-center gap-2 mb-3 p-2 bg-white rounded border" id="sub-row-${req.id}">
                <div class="flex-grow-1">
                    <div class="fw-bold text-danger small mb-1">
                        ❌ غير متوفر: <span class="text-dark">${req.original_medicine_name}</span>
                    </div>
                    <div class="text-success small">
                        ✅ البديل المقترح: <strong>${req.suggested_medicine_name}</strong>
                        ${req.suggested_strength ? `<span class="badge bg-light text-dark border ms-1">${req.suggested_strength}</span>` : ''}
                        ${req.suggested_form ? `<span class="badge bg-light text-dark border ms-1">${req.suggested_form}</span>` : ''}
                    </div>
                    <div class="text-muted small mt-1">السبب: ${req.substitution_reason}</div>
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button onclick="respondSub(${req.id}, 'approve')"
                            class="btn btn-sm btn-success px-3">
                        <i class="fas fa-check me-1"></i> موافقة
                    </button>
                    <button onclick="respondSub(${req.id}, 'reject')"
                            class="btn btn-sm btn-outline-danger px-3">
                        <i class="fas fa-times me-1"></i> رفض
                    </button>
                </div>
            </div>
        `).join('');
    }

    window.respondSub = function (itemId, action) {
        const label = action === 'approve' ? 'الموافقة على البديل' : 'رفض البديل';
        const notes = prompt(`ملاحظة اختيارية لـ"${label}" (أو اضغط إلغاء للتخطي):`);
        if (notes === null && action === 'approve') return; // user cancelled approve

        fetch(RESPOND_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ item_id: itemId, action, response_notes: notes ?? '' }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                // أزل الصف المُجاب عليه
                const row = document.getElementById(`sub-row-${itemId}`);
                if (row) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity 0.4s';
                    setTimeout(() => row.remove(), 400);
                }
                // تنبيه مختصر
                const toast = document.createElement('div');
                toast.className = 'alert alert-success alert-dismissible position-fixed bottom-0 end-0 m-3 shadow';
                toast.style.zIndex = 9999;
                toast.innerHTML = `${data.message} <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>`;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);

                // إعادة الفحص
                setTimeout(checkSubstitutions, 500);
            } else {
                alert(data.message ?? 'حدث خطأ');
            }
        })
        .catch(() => alert('تعذر الاتصال بالخادم'));
    };

    function checkSubstitutions() {
        fetch(SUB_URL, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) renderSubstitutions(data.requests);
            })
            .catch(() => {});
    }

    // فحص فوري عند تحميل الصفحة ثم كل 30 ثانية
    checkSubstitutions();
    setInterval(checkSubstitutions, 30000);
})();
</script>
@endpush