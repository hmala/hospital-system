<!-- resources/views/emergency/show.blade.php -->
@extends('layouts.app')

@section('content')
@php
    $unpaidAmount = $unpaidAmount ?? $emergency->getUnpaidAmount();
    $isPaid = $isPaid ?? ($unpaidAmount <= 0 || in_array($emergency->payment_status, ['paid', 'waived']));
@endphp
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
                    @if($emergency->status !== 'transferred' && $emergency->status !== 'discharged' && $emergency->status !== 'cancelled')
                        <button type="button" class="btn btn-sm btn-info text-white fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#consultationModal">
                            <i class="fas fa-calendar-plus me-1"></i>طلب استشارة
                        </button>

                        @if($isPaid)
                            <div class="dropdown d-inline">
                                <button class="btn btn-sm btn-dark dropdown-toggle shadow-sm fw-bold d-inline-flex align-items-center" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-sliders-h me-1 text-warning"></i>
                                    <span>إجراءات وتحويل وتخريج الحالة</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 text-start" style="min-width: 230px;">
                                    <li class="px-3 py-1 bg-light border-bottom text-muted small fw-bold">
                                        <i class="fas fa-exchange-alt me-1 text-primary"></i> تحويل الحالة
                                    </li>
                                    <li>
                                        <form action="{{ route('emergency.transfer-to-surgery', $emergency) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى صالة العمليات؟ سيتم تسجيله وترحيل بياناته تلقائياً.')">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-danger py-2">
                                                <i class="fas fa-procedures me-2 text-danger"></i>
                                                <span>تحويل للعمليات</span>
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('emergency.transfer-to-admission', $emergency) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من تحويل هذا المريض إلى الرقود؟ سيتم إرسال طلب الحجز للاستعلامات.')">
                                            @csrf
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-primary py-2">
                                                <i class="fas fa-bed me-2 text-primary"></i>
                                                <span>تحويل رقود</span>
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" onsubmit="return confirm('تأكيد تحويل المريض إلى العيادات الاستشارية وتوثيق خروجه من الطوارئ؟')">
                                            @csrf
                                            <input type="hidden" name="discharge_type" value="transferred_to_consultation">
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-info py-2">
                                                <i class="fas fa-clinic-medical me-2 text-info"></i>
                                                <span class="fw-bold">تحويل للاستشارية</span>
                                            </button>
                                        </form>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li class="px-3 py-1 bg-light border-bottom text-muted small fw-bold">
                                        <i class="fas fa-sign-out-alt me-1 text-success"></i> تسجيل الخروج
                                    </li>
                                    <li>
                                        <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" onsubmit="return confirm('تأكيد تسجيل خروج المريض (خرج متعافي)؟')">
                                            @csrf
                                            <input type="hidden" name="discharge_type" value="recovered">
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-success py-2">
                                                <i class="fas fa-user-check me-2 text-success"></i>
                                                <span>خرج متعافي</span>
                                            </button>
                                        </form>
                                    </li>
                                    <li>
                                        <form action="{{ route('emergency.discharge', $emergency) }}" method="POST" onsubmit="return confirm('تأكيد تسجيل خروج المريض (خرج على مسؤوليته)؟')">
                                            @csrf
                                            <input type="hidden" name="discharge_type" value="against_medical_advice">
                                            <button type="submit" class="dropdown-item d-flex align-items-center text-secondary py-2">
                                                <i class="fas fa-user-shield me-2 text-warning"></i>
                                                <span>خرج على مسؤوليته</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <button class="btn btn-sm btn-secondary shadow-sm fw-bold d-inline-flex align-items-center disabled" type="button" disabled title="معلق: بانتظار تسديد رسوم الطوارئ بالكاشير (المتبقي: {{ number_format($unpaidAmount) }} د.ع)">
                                <i class="fas fa-lock me-1 text-warning"></i>
                                <span>إجراءات وتحويل وتخريج (بانتظار التسديد)</span>
                            </button>
                        @endif

                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#cancelEmergencyModal" title="إلغاء حالة الطوارئ">
                            <i class="fas fa-ban me-1"></i>إلغاء الحالة
                        </button>
                    @elseif($emergency->status === 'cancelled')
                        <span class="badge bg-danger fs-6 py-1 px-2 shadow-sm">
                            <i class="fas fa-ban me-1"></i> ملغاة
                        </span>
                    @elseif($emergency->status === 'discharged')
                        @if($emergency->discharge_type === 'recovered')
                            <span class="badge bg-success fs-6 py-1 px-2 shadow-sm">
                                <i class="fas fa-check-circle me-1"></i> خرج متعافي ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @elseif($emergency->discharge_type === 'against_medical_advice')
                            <span class="badge bg-warning text-dark fs-6 py-1 px-2 shadow-sm">
                                <i class="fas fa-exclamation-triangle me-1"></i> خرج على مسؤوليته ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @elseif($emergency->discharge_type === 'transferred_to_consultation')
                            <span class="badge bg-info text-dark fs-6 py-1 px-2 shadow-sm fw-bold">
                                <i class="fas fa-clinic-medical me-1"></i> تحويل للاستشارية ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @else
                            <span class="badge bg-secondary fs-6 py-1 px-2 shadow-sm">
                                <i class="fas fa-sign-out-alt me-1"></i> تم الخروج ({{ $emergency->discharge_time ? $emergency->discharge_time->format('Y-m-d H:i') : '' }})
                            </span>
                        @endif
                    @endif
                    @if($emergency->appointments && $emergency->appointments->count() > 0)
                        <span class="badge bg-info text-dark fw-bold py-1 px-2 border" title="تم إنشاء طلب استشارة للمريض">
                            <i class="fas fa-calendar-check text-primary me-1"></i> تم طلب استشارة ({{ $emergency->appointments->count() }})
                        </span>
                    @endif

                    @if($isPaid)
                        <a href="{{ route('emergency.medical-report', $emergency) }}" target="_blank" class="btn btn-sm btn-outline-danger fw-bold shadow-sm" title="فتح وطباعة التقرير الطبي الرسمي للحالة بتوقيع الطبيب ورمز QR">
                            <i class="fas fa-file-medical-alt me-1"></i>التقرير الطبي للطوارئ
                        </a>
                    @else
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-bold shadow-sm disabled" disabled title="لا يمكن فتح التقرير الطبي إلا بعد تسديد الرسوم في الكاشير (المتبقي: {{ number_format($unpaidAmount) }} د.ع)">
                            <i class="fas fa-lock me-1 text-danger"></i>التقرير الطبي (معلق بالكاشير)
                        </button>
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

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show no-print" role="alert">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(!$isPaid && $emergency->status !== 'cancelled')
        <div class="alert alert-warning border-2 border-warning shadow-sm mb-3 p-3 no-print" style="border-radius: 12px; background: #fffbeb;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-lock text-warning fs-4 me-1"></i>
                    <div>
                        <strong class="text-dark d-block">⚠️ بانتظار تسديد رسوم الطوارئ بالكاشير (المبلغ المستحق: {{ number_format($unpaidAmount) }} د.ع)</strong>
                        <small class="text-muted">التقرير الطبي الرسمي وإجراءات التحويل (للعمليات أو الرقود) وتخريج الحالة معلقة لحين إبراء الذمة المالية وتسديد الرسوم في الصندوق.</small>
                    </div>
                </div>
                @if(auth()->user()->can('process emergency payments') || auth()->user()->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'cashier']))
                    <a href="{{ route('cashier.index') }}" class="btn btn-warning btn-sm fw-bold px-3 shadow-sm text-dark">
                        <i class="fas fa-cash-register me-1"></i> التوجه للكاشير للتسديد
                    </a>
                @endif
            </div>
        </div>
    @endif

    @if($emergency->status === 'cancelled')
        <div class="alert alert-danger border-2 border-danger shadow-sm mb-3 p-3 no-print" style="border-radius: 12px; background: #fef2f2;">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-ban text-danger fs-4 me-1"></i>
                <div>
                    <strong class="text-danger d-block">تم إلغاء حالة الطوارئ هذه</strong>
                    <small class="text-dark">{{ $emergency->notes }}</small>
                </div>
            </div>
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
                                @if($emergency->patient?->healthInsuranceCategory || ($emergency->patient?->insurance_type && $emergency->patient?->insurance_type !== 'none'))
                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1 ms-1" style="font-size: 0.75rem;">
                                        <i class="fas fa-shield-alt me-1"></i>{{ $emergency->patient?->healthInsuranceCategory?->name ?? 'مشمول بالضمان الصحي' }}
                                        @if($emergency->patient?->insurance_card_no)
                                            <span class="text-dark ms-1">({{ $emergency->patient->insurance_card_no }})</span>
                                        @endif
                                    </span>
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

    @if($emergency->consultant_notes)
        <div class="alert alert-info border-2 border-info shadow-sm mb-4 p-3" style="border-radius: 12px; background: #f0fdfa;">
            <div class="d-flex align-items-start gap-3">
                <div class="bg-info text-white p-2 rounded-circle fs-4 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <span><i class="fas fa-clipboard-check text-info me-1"></i>رأي وتوصيات الطبيب الاستشاري</span>
                            @if($emergency->consultantDoctor)
                                <span class="badge bg-primary text-white">د. {{ $emergency->consultantDoctor->user?->name }} ({{ $emergency->consultantDoctor->specialization }})</span>
                            @endif
                        </h6>
                        @if($emergency->consultation_responded_at)
                            <small class="text-muted"><i class="fas fa-clock me-1"></i>{{ $emergency->consultation_responded_at->format('Y-m-d H:i') }}</small>
                        @endif
                    </div>
                    <div class="p-3 bg-white rounded border text-dark fw-semibold" style="white-space: pre-line; line-height: 1.7; font-size: 0.95rem;">
                        {{ $emergency->consultant_notes }}
                    </div>
                    @if($emergency->consultant_recommendation)
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <span class="text-muted small fw-bold">التوصية المقترحة:</span>
                            @if($emergency->consultant_recommendation === 'requires_surgery')
                                <span class="badge bg-danger fs-6 py-1 px-2"><i class="fas fa-procedures me-1"></i>تحويل عاجل لصالة العمليات</span>
                            @elseif($emergency->consultant_recommendation === 'requires_admission')
                                <span class="badge bg-warning text-dark fs-6 py-1 px-2"><i class="fas fa-bed me-1"></i>دخول ورقود بالمستشفى</span>
                            @elseif($emergency->consultant_recommendation === 'discharge_safe')
                                <span class="badge bg-success fs-6 py-1 px-2"><i class="fas fa-check-circle me-1"></i>مستقر ويمكن تخريجه</span>
                            @else
                                <span class="badge bg-info text-dark fs-6 py-1 px-2"><i class="fas fa-clinic-medical me-1"></i>متابعة تحفظية بالطوارئ</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- المحتوى الطبي الرئيسي - مقسم إلى شاشة عمل الطوارئ (يمين) والسجل الطبي الدائم (يسار) مطابق لمحطة الاستشارية -->
    <div class="row g-3 mb-4">
        <!-- 1. شاشة عمل الطوارئ الرئيسية (التبويبات الـ 4 المستقلة) -->
        <div class="col-12 col-xl-7 col-lg-7">
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
                                <span>طلب مختبر / أشعة</span>
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
                                                    <div class="col-sm-12">
                                                        <label class="form-label fw-bold"><i class="fas fa-brain text-info me-1"></i>مستوى الوعي (GCS - Glasgow Coma Scale)</label>
                                                        <div class="input-group">
                                                            <span class="input-group-text bg-white"><i class="fas fa-brain text-info"></i></span>
                                                            <input type="text" class="form-control fw-bold" name="gcs" placeholder="15/15" value="{{ old('gcs', $emergency->gcs ?? '15/15') }}">
                                                            <button class="btn btn-outline-secondary btn-sm" type="button" onclick="this.previousElementSibling.value='15/15'">طبيعي (15/15)</button>
                                                        </div>
                                                        <small class="text-muted">مستوى الوعي في استمارة الطوارئ الرسمية (القيمة الطبيعية: 15/15)</small>
                                                    </div>
                                                    <div class="col-12 mt-3">
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
                                                    <label class="form-label fw-bold"><i class="fas fa-file-medical text-primary me-1"></i>الشكوى الرئيسية (Chief Complaint)</label>
                                                    <input type="text" class="form-control" name="symptoms" placeholder="مثال: ألم حاد في البطن، ضيق تنفس، قيء..." value="{{ old('symptoms', $emergency->symptoms) }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold"><i class="fas fa-notes-medical text-info me-1"></i>تاريخ المريض الحالي History of Present Illness (HPI)</label>
                                                    <textarea class="form-control" name="hpi" rows="2" placeholder="اكتب التاريخ المرضي الحالي وتطور الأعراض بالتفصيل...">{{ old('hpi', $emergency->hpi) }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold"><i class="fas fa-stethoscope text-danger me-1"></i>التشخيص الطبي والسريري (Diagnosis)</label>
                                                    <textarea class="form-control" name="diagnosis" rows="2" placeholder="اكتب التشخيص الطبي للحالة وتوصيات الرعاية...">{{ old('diagnosis', $emergency->diagnosis) }}</textarea>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold text-dark"><i class="fas fa-balance-scale text-warning me-1"></i>الملاحظات الطبية - العدلية (Medico-Legal Notes) ⚖️</label>
                                                    <textarea class="form-control" name="medico_legal_notes" rows="2" placeholder="توثيق الإصابة والشبهة الجنائية (مثل: حادث سير، مشاجرة، أو: لا توجد شبهة جنائية / حادث عرضي)...">{{ old('medico_legal_notes', $emergency->medico_legal_notes) }}</textarea>
                                                    <small class="text-muted">تظهر في الوجه الثاني من التقرير الطبي الرسمي</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold"><i class="fas fa-clipboard-list text-secondary me-1"></i>ملاحظات إضافية (Additional Notes)</label>
                                                    <textarea class="form-control" name="notes" rows="2" placeholder="أي ملاحظات أو إرشادات سريرية إضافية...">{{ old('notes', $emergency->notes) }}</textarea>
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
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <label class="form-label fw-bold mb-0">اختر الخدمات المقدمة للحالة في الطوارئ:</label>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span id="serviceSelectedCount" class="badge bg-success-subtle text-success border border-success-subtle small d-none">0 محدد</span>
                                                        @if(!empty($paidServiceMap) && count($paidServiceMap) > 0)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle small">
                                                                <i class="fas fa-lock me-1"></i> {{ count($paidServiceMap) }} خدمات مسددة في الكاشير
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <!-- أزرار الفلترة السريعة للخدمات (الكل / ضمان / كاش) -->
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-2">
                                                    <div class="btn-group btn-group-sm" role="group" id="serviceFilterGroup">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 active service-filter-btn" data-filter="all" style="font-size: 0.75rem;">الكل</button>
                                                        <button type="button" class="btn btn-outline-success btn-sm py-0 px-2 service-filter-btn" data-filter="insurance" style="font-size: 0.75rem;">
                                                            <i class="fas fa-shield-alt me-1"></i>مشمول بالضمان
                                                        </button>
                                                        <button type="button" class="btn btn-outline-warning btn-sm py-0 px-2 service-filter-btn text-dark" data-filter="cash" style="font-size: 0.75rem;">
                                                            <i class="fas fa-money-bill me-1"></i>كاش فقط
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="input-group input-group-sm mb-2">
                                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                                    <input type="text" id="serviceSearchInput" class="form-control" placeholder="ابحث باسم الخدمة الطبية أو التمريضية...">
                                                    <button class="btn btn-outline-secondary d-none" type="button" id="clearServiceSearch"><i class="fas fa-times"></i></button>
                                                </div>
                                                <div class="row g-2 border rounded p-3 bg-white" id="servicesContainer" style="max-height: 320px; overflow-y: auto;">
                                                    @php
                                                        $selectedServiceIds = $emergency->services->pluck('id')->toArray();
                                                        $serviceQuantities = $emergency->services->pluck('pivot.quantity', 'id')->toArray();
                                                    @endphp
                                                    @foreach($emergencyServices as $service)
                                                        @php
                                                            $isPaid = isset($paidServiceMap[$service->id]);
                                                            $isSelected = in_array($service->id, $selectedServiceIds);
                                                            $qty = (int) ($serviceQuantities[$service->id] ?? 1);
                                                            if ($qty < 1) $qty = 1;
                                                            $unitPrice = (float) $service->price;
                                                            $isSrvInsurance = ($service->is_hi_active ?? true) && ($service->hi_price > 0);
                                                        @endphp
                                                        <div class="col-12 service-item" data-is-insurance="{{ $isSrvInsurance ? '1' : '0' }}">
                                                            <div class="form-check d-flex justify-content-between align-items-center p-2 rounded {{ $isPaid ? 'bg-success-subtle bg-opacity-25 border border-success-subtle' : ($isSelected ? 'bg-light border' : '') }}">
                                                                <div class="d-flex align-items-center flex-grow-1 me-2">
                                                                    @if($isPaid)
                                                                        <input type="hidden" name="service_ids[]" value="{{ $service->id }}">
                                                                        <input class="form-check-input service-checkbox me-2" type="checkbox" checked disabled id="srv_{{ $service->id }}">
                                                                    @else
                                                                        <input class="form-check-input service-checkbox me-2" type="checkbox" name="service_ids[]" value="{{ $service->id }}" id="srv_{{ $service->id }}" {{ $isSelected ? 'checked' : '' }}>
                                                                    @endif
                                                                    <label class="form-check-label fw-bold service-name mb-0 {{ $isPaid ? 'text-success' : '' }}" for="srv_{{ $service->id }}">
                                                                        {{ $service->name }}
                                                                        @if($isSrvInsurance)
                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.68rem;">
                                                                                <i class="fas fa-shield-alt"></i> ضمان {{ number_format($service->hi_price) }} د.ع
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.68rem;">
                                                                                كاش فقط
                                                                            </span>
                                                                        @endif
                                                                        @if($isPaid)
                                                                            <span class="badge bg-success text-white ms-2" style="font-size: 0.72rem;">
                                                                                <i class="fas fa-check-circle me-1"></i> مسددة (إيصال #{{ $paidServiceMap[$service->id] }})
                                                                            </span>
                                                                        @endif
                                                                    </label>
                                                                </div>

                                                                <!-- عداد الكمية التفاعلي ومضاعف السعر -->
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <div class="input-group input-group-sm service-qty-group {{ $isSelected || $isPaid ? '' : 'd-none' }}" style="width: 105px;">
                                                                        <button type="button" class="btn btn-outline-secondary px-2 btn-service-minus" title="إنقاص" {{ $isPaid ? 'disabled' : '' }}>
                                                                            <i class="fas fa-minus" style="font-size: 0.65rem;"></i>
                                                                        </button>
                                                                        <input type="number" 
                                                                               name="service_quantities[{{ $service->id }}]" 
                                                                               class="form-control text-center p-0 fw-bold service-qty-input" 
                                                                               value="{{ $qty }}" 
                                                                               min="1" 
                                                                               max="99" 
                                                                               data-unit-price="{{ $unitPrice }}"
                                                                               data-service-id="{{ $service->id }}"
                                                                               style="font-size: 0.85rem;"
                                                                               {{ $isPaid ? 'readonly' : '' }}>
                                                                        <button type="button" class="btn btn-outline-secondary px-2 btn-service-plus" title="زيادة" {{ $isPaid ? 'disabled' : '' }}>
                                                                            <i class="fas fa-plus" style="font-size: 0.65rem;"></i>
                                                                        </button>
                                                                    </div>

                                                                    <span class="badge {{ $isPaid ? 'bg-success text-white' : 'bg-success-subtle text-success border border-success-subtle' }} service-price-badge px-2 py-1 text-center" style="min-width: 85px; font-size: 0.8rem;">
                                                                        <span class="price-val">{{ number_format($unitPrice * $qty) }}</span> IQD
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                    <div id="noServicesFound" class="col-12 text-muted small text-center py-3 d-none">
                                                        <i class="fas fa-info-circle me-1"></i> لا توجد خدمات مطابقة للبحث
                                                    </div>
                                                </div>
                                                <div class="alert alert-light border small text-muted mt-2 py-2 mb-0">
                                                    <i class="fas fa-shield-alt text-success me-1"></i> <strong>الضوابط المالية:</strong> الخدمات المسددة تظهر مقفلة لمنع حذفها المباشر من واجهة الطوارئ لحماية توازن قاصة الكاشير وصحة وصولات المريض. إذا رغب المريض في إلغاء خدمة مسددة واسترجاع قيمتها، يتم ذلك عبر إجراء الاسترجاع المالي (Refund) في الكاشير. أما الخدمات غير المسددة فيمكن إضافتها أو حذفها بحرية تامة.
                                                </div>

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

                        <!-- 3. تبويب طلبات الفحوصات الطبية (المختبر والأشعة) -->
                        <div class="tab-pane fade" id="tab-requests" role="tabpanel">
                            <div class="row g-4">
                                <!-- طلب تحاليل المختبر -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-header bg-primary text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-flask fs-5"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-white">طلب تحاليل المختبر</h6>
                                                    <small class="text-white-50">اختر التحاليل المطلوبة وأرسلها للمختبر</small>
                                                </div>
                                            </div>
                                            <span id="labSelectedCount" class="badge bg-white text-primary fw-bold px-2.5 py-1.5 d-none">0 محدد</span>
                                        </div>
                                        <div class="card-body p-3 bg-white">
                                            <form action="{{ route('emergency.request-lab', $emergency) }}" method="POST" id="emergencyLabRequestForm">
                                                @csrf
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-5">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>درجة الأولوية
                                                        </label>
                                                        <select name="priority" class="form-select form-select-sm" required>
                                                            <option value="urgent">عاجل (Urgent)</option>
                                                            <option value="critical">حرج وطارئ جداً (Critical)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            <i class="fas fa-filter text-secondary me-1"></i>تصفية التسعير
                                                        </label>
                                                        <div class="btn-group btn-group-sm w-100" role="group" id="labFilterGroup">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 active lab-filter-btn" data-filter="all">الكل</button>
                                                            <button type="button" class="btn btn-outline-success btn-sm py-1 px-2 lab-filter-btn" data-filter="insurance">
                                                                <i class="fas fa-shield-alt me-1"></i>مشمول بالضمان
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning btn-sm py-1 px-2 lab-filter-btn text-dark" data-filter="cash">
                                                                كاش فقط
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                                                        <input type="text" id="labSearchInput" class="form-control" placeholder="ابحث باسم التحليل أو الفحص المخبري...">
                                                        <button class="btn btn-outline-secondary d-none" type="button" id="clearLabSearch"><i class="fas fa-times"></i></button>
                                                    </div>
                                                </div>

                                                <div class="border rounded-3 p-2 bg-light bg-opacity-50 mb-2" id="labTestsContainer" style="max-height: 280px; overflow-y: auto;">
                                                    @foreach($labTests as $test)
                                                        @php
                                                            $isLabInsurance = ($test->is_hi_active ?? true) && ($test->hi_price > 0);
                                                        @endphp
                                                        <div class="form-check lab-test-item py-1.5 px-2 border-bottom border-light-subtle rounded-1" data-is-insurance="{{ $isLabInsurance ? '1' : '0' }}">
                                                            <input class="form-check-input lab-checkbox ms-0 me-2" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="lab_chk_{{ $test->id }}">
                                                            <label class="form-check-label small d-flex justify-content-between align-items-center cursor-pointer w-100 mb-0" for="lab_chk_{{ $test->id }}">
                                                                <div>
                                                                    <span class="lab-name fw-medium text-dark">{{ $test->name }}</span>
                                                                    @if($isLabInsurance)
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">
                                                                            <i class="fas fa-shield-alt"></i> ضمان {{ number_format($test->hi_price) }} د.ع
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.65rem;">
                                                                            كاش فقط
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <span class="text-muted font-monospace small">({{ number_format($test->price) }} IQD)</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                    <div id="noLabTestsFound" class="text-muted small text-center py-3 d-none">
                                                        <i class="fas fa-info-circle me-1"></i> لا توجد تحاليل مطابقة للبحث
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات سريرية أو تشخيص موجه للمختبر (اختياري)...">
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle text-primary me-1"></i>تظهر النتائج بالسجل الطبي يساراً
                                                    </small>
                                                    <button type="submit" class="btn btn-primary btn-sm fw-bold px-3 shadow-sm">
                                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب للمختبر
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- طلب فحوصات الأشعة والسونار -->
                                <div class="col-lg-6">
                                    <div class="card border-0 shadow-sm mb-3">
                                        <div class="card-header bg-info text-white py-2.5 px-3 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-x-ray fs-5"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-white">طلب الأشعة والتصوير (Radiology)</h6>
                                                    <small class="text-white-50">اختر الفحوصات الشعاعية وأرسلها لقسم الأشعة</small>
                                                </div>
                                            </div>
                                            <span id="radSelectedCount" class="badge bg-white text-info fw-bold px-2.5 py-1.5 d-none">0 محدد</span>
                                        </div>
                                        <div class="card-body p-3 bg-white">
                                            <form action="{{ route('emergency.request-radiology', $emergency) }}" method="POST" id="emergencyRadRequestForm">
                                                @csrf
                                                <div class="row g-2 mb-2">
                                                    <div class="col-md-5">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            <i class="fas fa-exclamation-triangle text-warning me-1"></i>درجة الأولوية
                                                        </label>
                                                        <select name="priority" class="form-select form-select-sm" required>
                                                            <option value="urgent">عاجل (Urgent)</option>
                                                            <option value="critical">حرج وطارئ جداً (Critical)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <label class="form-label small fw-bold text-dark mb-1">
                                                            <i class="fas fa-filter text-secondary me-1"></i>تصفية التسعير
                                                        </label>
                                                        <div class="btn-group btn-group-sm w-100" role="group" id="radFilterGroup">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm py-1 px-2 active rad-filter-btn" data-filter="all">الكل</button>
                                                            <button type="button" class="btn btn-outline-info btn-sm py-1 px-2 rad-filter-btn" data-filter="insurance">
                                                                <i class="fas fa-shield-alt me-1"></i>مشمول بالضمان
                                                            </button>
                                                            <button type="button" class="btn btn-outline-warning btn-sm py-1 px-2 rad-filter-btn text-dark" data-filter="cash">
                                                                كاش فقط
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                                                        <input type="text" id="radSearchInput" class="form-control" placeholder="ابحث بنوع الأشعة، السونار، أو المفراس...">
                                                        <button class="btn btn-outline-secondary d-none" type="button" id="clearRadSearch"><i class="fas fa-times"></i></button>
                                                    </div>
                                                </div>

                                                <div class="border rounded-3 p-2 bg-light bg-opacity-50 mb-2" id="radTypesContainer" style="max-height: 280px; overflow-y: auto;">
                                                    @foreach($radiologyTypes as $radType)
                                                        @php
                                                            $isRadInsurance = ($radType->is_hi_active ?? true) && ($radType->hi_price > 0);
                                                        @endphp
                                                        <div class="form-check rad-type-item py-1.5 px-2 border-bottom border-light-subtle rounded-1" data-is-insurance="{{ $isRadInsurance ? '1' : '0' }}">
                                                            <input class="form-check-input rad-checkbox ms-0 me-2" type="checkbox" name="radiology_type_ids[]" value="{{ $radType->id }}" id="rad_chk_{{ $radType->id }}">
                                                            <label class="form-check-label small d-flex justify-content-between align-items-center cursor-pointer w-100 mb-0" for="rad_chk_{{ $radType->id }}">
                                                                <div>
                                                                    <span class="rad-name fw-medium text-dark">{{ $radType->name }}</span>
                                                                    @if($isRadInsurance)
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.65rem;">
                                                                            <i class="fas fa-shield-alt"></i> ضمان {{ number_format($radType->hi_price) }} د.ع
                                                                        </span>
                                                                    @else
                                                                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle ms-1" style="font-size: 0.65rem;">
                                                                            كاش فقط
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <span class="text-muted font-monospace small">({{ number_format($radType->price) }} IQD)</span>
                                                            </label>
                                                        </div>
                                                    @endforeach
                                                    <div id="noRadTypesFound" class="text-muted small text-center py-3 d-none">
                                                        <i class="fas fa-info-circle me-1"></i> لا توجد فحوصات مطابقة للبحث
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <input type="text" name="notes" class="form-control form-control-sm" placeholder="ملاحظات سريرية أو العضو المستهدف لقسم الأشعة (اختياري)...">
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                                    <small class="text-muted">
                                                        <i class="fas fa-info-circle text-info me-1"></i>تظهر التقارير والصور بالسجل الطبي يساراً
                                                    </small>
                                                    <button type="submit" class="btn btn-info text-white btn-sm fw-bold px-3 shadow-sm">
                                                        <i class="fas fa-paper-plane me-1"></i> إرسال الطلب للأشعة
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- شريط توجيهي يوضح أن نتائج الفحوصات وسير العمل تتابع من السجل الطبي يساراً مطابقاً للاستشارية -->
                                <div class="col-12">
                                    <div class="alert alert-light border border-secondary-subtle d-flex flex-wrap align-items-center justify-content-between py-2 px-3 mb-0 rounded-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                <i class="fas fa-notes-medical"></i>
                                            </div>
                                            <div class="small">
                                                <strong class="text-dark">متابعة الفحوصات والنتائج:</strong>
                                                <span class="text-muted">كافة طلبات المختبر والأشعة الحالية، مراحل سدادها لدى الكاشير، ونتائجها وتقاريرها الطبية الرسمية معروضة ومحدثة لحظياً في قسم <strong>"طلبات الحالة"</strong> داخل لوحة <strong>السجل الطبي والفحوصات</strong> (على يسار الشاشة).</span>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="document.getElementById('side-cur-tab')?.click(); document.getElementById('side-cur-tab')?.scrollIntoView({behavior: 'smooth', block: 'nearest'});">
                                            <i class="fas fa-arrow-left me-1"></i> عرض طلبات ونتائج الحالة
                                        </button>
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
                        </div><!-- /#tab-treatment -->
                    </div><!-- /#emergencyWorkstationTabsContent -->
                </div><!-- /.card-body -->
            </div><!-- /.card -->
        </div><!-- /.col-12.col-xl-7.col-lg-7 -->

        <!-- 2. السجل الطبي الدائم ونتائج الفحوصات (العمود الأيسر الدائم - مطابق لمحطة الاستشارية) -->
        <div class="col-12 col-xl-5 col-lg-5">
            <div class="card shadow-sm border-0 sticky-top" style="top: 80px; z-index: 10; border-radius: 12px; overflow: hidden;">
                <!-- رأس البطاقة -->
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-notes-medical text-info"></i>
                        <h6 class="mb-0 fw-bold">السجل الطبي ونتائج الفحوصات</h6>
                    </div>
                    @if($emergency->patient)
                        <a href="{{ route('doctor.patient.history', $emergency->patient) }}" target="_blank" class="btn btn-xs btn-outline-info text-white py-1 px-2" style="font-size: 0.78rem;">
                            <i class="fas fa-external-link-alt me-1"></i> السجل الزمني الكامل ↗
                        </a>
                    @endif
                </div>

                <!-- أشرطة التبويبات الداخلية للسجل الطبي -->
                <div class="bg-light p-2 border-bottom">
                    <ul class="nav nav-pills nav-fill gap-1" id="emergencyHistorySubTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active py-1 px-2 small fw-semibold" id="side-cur-tab" data-bs-toggle="pill" data-bs-target="#side-cur-pane" type="button" role="tab">
                                <i class="fas fa-bolt text-warning me-1"></i> طلبات الحالة
                                @php
                                    $curRequestsCount = ($emergency->labRequests ? $emergency->labRequests->count() : 0) + ($emergency->radiologyRequests ? $emergency->radiologyRequests->count() : 0) + ($emergency->treatments ? $emergency->treatments->count() : 0);
                                @endphp
                                @if($curRequestsCount > 0)
                                    <span class="badge bg-warning text-dark ms-1">{{ $curRequestsCount }}</span>
                                @endif
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-past-lab-tab" data-bs-toggle="pill" data-bs-target="#side-past-lab-pane" type="button" role="tab">
                                <i class="fas fa-microscope text-primary me-1"></i> تحاليل سابقة
                                <span class="badge bg-primary ms-1">{{ $pastLabRequests ? $pastLabRequests->count() : 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-past-rad-tab" data-bs-toggle="pill" data-bs-target="#side-past-rad-pane" type="button" role="tab">
                                <i class="fas fa-x-ray text-info me-1"></i> أشعة سابقة
                                <span class="badge bg-info text-dark ms-1">{{ $pastRadiologyRequests ? $pastRadiologyRequests->count() : 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-past-visits-tab" data-bs-toggle="pill" data-bs-target="#side-past-visits-pane" type="button" role="tab">
                                <i class="fas fa-history text-success me-1"></i> زيارات
                                <span class="badge bg-secondary ms-1">{{ $pastVisits ? $pastVisits->count() : 0 }}</span>
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link py-1 px-2 small fw-semibold" id="side-past-surgeries-tab" data-bs-toggle="pill" data-bs-target="#side-past-surgeries-pane" type="button" role="tab">
                                <i class="fas fa-procedures text-danger me-1"></i> عمليات/طوارئ
                                <span class="badge bg-danger ms-1">{{ ($pastSurgeries ? $pastSurgeries->count() : 0) + ($pastEmergencies ? $pastEmergencies->count() : 0) }}</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- جسم السجل الطبي الدائم مع شريط تمرير أنيق -->
                <div class="card-body p-3 overflow-y-auto" style="max-height: calc(100vh - 180px); min-height: 520px;">
                    <div class="tab-content" id="emergencyHistorySubTabsContent">
                        
                        <!-- 1. طلبات ونتائج الحالة الحالية للطوارئ (السجل الطبي والفحوصات) -->
                        <div class="tab-pane fade show active" id="side-cur-pane" role="tabpanel">
                            @php
                                $allLabRequests = $emergency->labRequests ? $emergency->labRequests->sortByDesc('id') : collect();
                                $curLabRequests = $allLabRequests->where('status', '!=', 'cancelled');
                                $cancelledLabRequests = $allLabRequests->where('status', 'cancelled');

                                $allRadRequests = $emergency->radiologyRequests ? $emergency->radiologyRequests->sortByDesc('id') : collect();
                                $curRadRequests = $allRadRequests->where('status', '!=', 'cancelled');
                                $cancelledRadRequests = $allRadRequests->where('status', 'cancelled');

                                $curTreatments = $emergency->treatments ? $emergency->treatments->sortByDesc('id') : collect();
                                $curTotal = $curLabRequests->count() + $curRadRequests->count() + $curTreatments->count();
                            @endphp

                            @if($curTotal > 0 || $cancelledLabRequests->count() > 0 || $cancelledRadRequests->count() > 0)
                                <!-- شريط تصفية طلبات الحالة الحالية (Sub-Filter Pills مطابق لمحطة الاستشارية) -->
                                <div class="today-filter-tray d-flex flex-wrap gap-1 mb-3 p-1 bg-light rounded-3 border align-items-center">
                                    <button type="button" class="btn btn-xs btn-dark rounded-pill px-2.5 py-1 fw-bold emg-cur-filter-pill active" data-filter="all" onclick="filterCurEmergencyRequests('all', this)">
                                        الكل <span class="badge bg-secondary rounded-pill ms-1">{{ $curTotal }}</span>
                                    </button>
                                    @if($curLabRequests->count() > 0)
                                        <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2.5 py-1 fw-bold emg-cur-filter-pill" data-filter="lab" onclick="filterCurEmergencyRequests('lab', this)">
                                            <i class="fas fa-microscope me-1"></i> المختبر <span class="badge bg-primary rounded-pill ms-1">{{ $curLabRequests->count() }}</span>
                                        </button>
                                    @endif
                                    @if($curRadRequests->count() > 0)
                                        <button type="button" class="btn btn-xs btn-outline-info rounded-pill px-2.5 py-1 fw-bold emg-cur-filter-pill text-dark" data-filter="radiology" onclick="filterCurEmergencyRequests('radiology', this)">
                                            <i class="fas fa-x-ray me-1 text-info"></i> الأشعة <span class="badge bg-info rounded-pill ms-1 text-dark">{{ $curRadRequests->count() }}</span>
                                        </button>
                                    @endif
                                    @if($curTreatments->count() > 0)
                                        <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2.5 py-1 fw-bold emg-cur-filter-pill" data-filter="treatment" onclick="filterCurEmergencyRequests('treatment', this)">
                                            <i class="fas fa-pills me-1"></i> العلاجات <span class="badge bg-success rounded-pill ms-1">{{ $curTreatments->count() }}</span>
                                        </button>
                                    @endif
                                </div>

                                <div class="d-flex flex-column gap-3">
                                    {{-- 1. بطاقات طلبات المختبر الحالية --}}
                                    @foreach($curLabRequests as $labReq)
                                        @php
                                            $isPaid = !empty($labReq->payment_id) || $emergency->payment_status === 'paid' || $emergency->payment_status === 'waived';
                                            $isCompleted = ($labReq->status === 'completed');
                                        @endphp
                                        <div class="emg-cur-card-item card border {{ $isCompleted ? 'border-success' : ($isPaid ? 'border-primary' : 'border-warning') }} shadow-sm rounded-3 overflow-hidden" id="emergency-lab-card-{{ $labReq->id }}" data-emg-type="lab">
                                            <div class="card-header py-2 px-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-microscope me-1"></i> طلب مختبر #{{ $labReq->id }}
                                                    </span>
                                                    <small class="text-muted font-monospace">{{ optional($labReq->requested_at ?: $labReq->created_at)->format('H:i') }}</small>
                                                    <span class="badge {{ $labReq->priority === 'critical' ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.68rem;">
                                                        {{ $labReq->priority_text }}
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    @if($isPaid)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-check-circle me-1"></i> مسدد
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.72rem;">
                                                            <i class="fas fa-hourglass-half me-1"></i> عند الكاشير
                                                        </span>
                                                    @endif
                                                    <span class="badge {{ $labReq->status == 'completed' ? 'bg-success' : ($labReq->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}" style="font-size: 0.72rem;">
                                                        {{ $labReq->status_text }}
                                                    </span>
                                                    @if($isCompleted)
                                                        <a href="{{ route('staff.emergency-lab.print', $labReq) }}" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="طباعة النتيجة الرسمية">
                                                            <i class="fas fa-print me-1"></i> طباعة
                                                        </a>
                                                    @elseif($labReq->status !== 'cancelled')
                                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="إلغاء هذا الطلب وتحويله للاسترجاع بالكاشير" onclick="cancelEmergencyRequestAction({{ $emergency->id }}, 'lab', {{ $labReq->id }}, 'emergency-lab-card-{{ $labReq->id }}')">
                                                            <i class="fas fa-ban me-1"></i> إلغاء الطلب
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper) -->
                                            <div class="px-2 py-2 bg-light-subtle border-bottom">
                                                <div class="d-flex align-items-center justify-content-between position-relative" style="font-size: 0.72rem;">
                                                    <div class="position-absolute start-0 end-0 top-50 translate-middle-y border-top border-2" style="border-color: #dee2e6 !important; z-index: 1;"></div>
                                                    
                                                    <!-- المرحلة 1: طلب الطبيب -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                            <i class="fas fa-check"></i>
                                                        </span>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.7rem;">طلب الطبيب</span>
                                                    </div>

                                                    <!-- المرحلة 2: الكاشير -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isPaid)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-check"></i>
                                                            </span>
                                                            <span class="fw-semibold text-success" style="font-size: 0.7rem;">سُدّد</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-warning text-dark mb-1 shadow-xs border border-warning" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-coins"></i>
                                                            </span>
                                                            <span class="fw-bold text-danger" style="font-size: 0.7rem;">عند الكاشير</span>
                                                        @endif
                                                    </div>

                                                    <!-- المرحلة 3: المختبر -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isCompleted)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-check"></i>
                                                            </span>
                                                            <span class="fw-semibold text-success" style="font-size: 0.7rem;">أُنجز</span>
                                                        @elseif($isPaid)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-primary text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                            <span class="fw-bold text-primary" style="font-size: 0.7rem;">قيد الفحص</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-hourglass-start"></i>
                                                            </span>
                                                            <span class="text-muted" style="font-size: 0.7rem;">بالانتظار</span>
                                                        @endif
                                                    </div>

                                                    <!-- المرحلة 4: النتيجة -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isCompleted)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-file-medical"></i>
                                                            </span>
                                                            <span class="fw-bold text-success" style="font-size: 0.7rem;">النتيجة جاهزة</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-file"></i>
                                                            </span>
                                                            <span class="text-muted" style="font-size: 0.7rem;">التقرير</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body p-2.5">
                                                @if($labReq->notes)
                                                    <div class="small text-muted mb-2"><i class="fas fa-sticky-note me-1"></i><strong>ملاحظات:</strong> {{ $labReq->notes }}</div>
                                                @endif

                                                <!-- الجدول السريري المصغر (Clinical Mini-Table) -->
                                                <div class="table-responsive rounded border bg-white mb-0">
                                                    <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.8rem;">
                                                        <thead class="table-light text-muted" style="font-size: 0.72rem;">
                                                            <tr>
                                                                <th class="py-1 px-2 text-center" style="width: 28px;">#</th>
                                                                <th class="py-1 px-2 text-start">اسم التحليل المخبري</th>
                                                                @if($isCompleted)
                                                                    <th>النتيجة</th>
                                                                    <th>الوحدة</th>
                                                                    <th>المرجع</th>
                                                                    <th>الحالة</th>
                                                                @else
                                                                    <th class="py-1 px-2 text-center" style="width: 100px;">الحالة</th>
                                                                    @if(!$isPaid)
                                                                        <th class="py-1 px-2 text-center" style="width: 35px;">حذف</th>
                                                                    @endif
                                                                @endif
                                                            </tr>
                                                        </thead>
                                                        <tbody id="emergency-lab-tests-tbody-{{ $labReq->id }}">
                                                            @foreach($labReq->labTests as $itemIdx => $test)
                                                                @php
                                                                    $rawResult = $test->pivot->result;
                                                                    $parsed = is_string($rawResult) ? json_decode($rawResult, true) : $rawResult;
                                                                    $isObj = is_array($parsed);
                                                                    $val = $isObj ? ($parsed['value'] ?? '') : $rawResult;
                                                                    $unit = $isObj ? ($parsed['unit'] ?? $test->unit) : $test->unit;
                                                                    $range = $isObj ? ($parsed['reference_range'] ?? '') : '';
                                                                    $status = $isObj ? ($parsed['status'] ?? '') : '';
                                                                    $notes = $isObj ? ($parsed['notes'] ?? '') : '';
                                                                    $isAbnormal = in_array($status, ['high', 'low', 'مرتفع', 'منخفض']);
                                                                @endphp
                                                                <tr id="emergency-req-item-lab-{{ $labReq->id }}-{{ $test->id }}" class="{{ $isAbnormal ? 'table-warning' : '' }}">
                                                                    <td class="text-muted font-monospace text-center py-1 px-2">{{ $itemIdx + 1 }}</td>
                                                                    <td class="text-start fw-semibold text-dark py-1 px-2">
                                                                        <i class="fas fa-vial text-primary me-1 opacity-75"></i> {{ $test->name }}
                                                                        @if($notes)
                                                                            <div class="text-muted extra-small" style="font-size: 0.72rem;">{{ $notes }}</div>
                                                                        @endif
                                                                    </td>
                                                                    @if($isCompleted)
                                                                        <td>
                                                                            @if(!empty($val))
                                                                                <span class="fw-bold fs-6 {{ $isAbnormal ? 'text-danger' : 'text-primary' }}">{{ $val }}</span>
                                                                            @else
                                                                                <span class="text-muted">-</span>
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-muted">{{ $unit ?: '-' }}</td>
                                                                        <td class="text-muted"><small>{{ $range ?: '-' }}</small></td>
                                                                        <td>
                                                                            @if($status === 'normal' || $status === 'طبيعي')
                                                                                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">طبيعي</span>
                                                                            @elseif($status === 'high' || $status === 'مرتفع')
                                                                                <span class="badge bg-danger text-white" style="font-size: 0.68rem;">↑ مرتفع</span>
                                                                            @elseif($status === 'low' || $status === 'منخفض')
                                                                                <span class="badge bg-warning text-dark" style="font-size: 0.68rem;">↓ منخفض</span>
                                                                            @elseif(!empty($val))
                                                                                <span class="badge bg-primary-subtle text-primary" style="font-size: 0.68rem;">تم الفحص</span>
                                                                            @else
                                                                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.68rem;">-</span>
                                                                            @endif
                                                                        </td>
                                                                    @else
                                                                        <td class="text-center py-1 px-2">
                                                                            @if($isPaid)
                                                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">قيد التحليل</span>
                                                                            @else
                                                                                <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.68rem;">بانتظار السداد</span>
                                                                            @endif
                                                                        </td>
                                                                        @if(!$isPaid)
                                                                            <td class="text-center py-1 px-2">
                                                                                <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1.5 rounded-circle shadow-none lh-1" title="حذف هذا التحليل من الطلب" onclick="emergencyRemoveRequestItem({{ $emergency->id }}, 'lab', {{ $labReq->id }}, {{ $test->id }}, 'emergency-req-item-lab-{{ $labReq->id }}-{{ $test->id }}')">
                                                                                    <i class="fas fa-times" style="font-size: 0.68rem;"></i>
                                                                                </button>
                                                                            </td>
                                                                        @endif
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                @if(!$isCompleted)
                                                    <div class="alert alert-light border small text-muted mb-0 py-1.5 mt-2 d-flex align-items-center gap-2">
                                                        <i class="fas fa-spinner fa-spin text-warning"></i>
                                                        <span>{{ $isPaid ? 'الفحوصات قيد المعالجة في المختبر...' : 'الطلب بانتظار سداد المريض لدى الكاشير لبدء الفحص في المختبر.' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 2. بطاقات طلبات الأشعة الحالية --}}
                                    @foreach($curRadRequests as $radReq)
                                        @php
                                            $isPaid = !empty($radReq->payment_id) || $emergency->payment_status === 'paid' || $emergency->payment_status === 'waived';
                                            $isCompleted = ($radReq->status === 'completed');
                                        @endphp
                                        <div class="emg-cur-card-item card border {{ $isCompleted ? 'border-success' : ($isPaid ? 'border-info' : 'border-warning') }} shadow-sm rounded-3 overflow-hidden" id="emergency-rad-card-{{ $radReq->id }}" data-emg-type="radiology">
                                            <div class="card-header py-2 px-3 bg-white d-flex justify-content-between align-items-center border-bottom">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-info text-dark">
                                                        <i class="fas fa-x-ray me-1"></i> طلب أشعة #{{ $radReq->id }}
                                                    </span>
                                                    <small class="text-muted font-monospace">{{ optional($radReq->requested_at ?: $radReq->created_at)->format('H:i') }}</small>
                                                    <span class="badge {{ $radReq->priority === 'critical' ? 'bg-danger' : 'bg-warning text-dark' }}" style="font-size: 0.68rem;">
                                                        {{ $radReq->priority_text }}
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    @if($isPaid)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-check-circle me-1"></i> مسدد
                                                        </span>
                                                    @else
                                                        <span class="badge bg-warning text-dark border border-warning" style="font-size: 0.72rem;">
                                                            <i class="fas fa-hourglass-half me-1"></i> عند الكاشير
                                                        </span>
                                                    @endif
                                                    <span class="badge {{ $radReq->status == 'completed' ? 'bg-success' : ($radReq->status == 'in_progress' ? 'bg-info' : 'bg-warning text-dark') }}" style="font-size: 0.72rem;">
                                                        {{ $radReq->status_text }}
                                                    </span>
                                                    @if($isCompleted)
                                                        <a href="{{ route('staff.emergency-radiology.print', $radReq) }}" target="_blank" class="btn btn-xs btn-outline-info py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="طباعة التقرير الشعاعي الرسمي">
                                                            <i class="fas fa-print me-1"></i> طباعة
                                                        </a>
                                                    @elseif($radReq->status !== 'cancelled')
                                                        <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2 fw-bold" style="font-size: 0.72rem;" title="إلغاء هذا الفحص وتحويله للاسترجاع بالكاشير" onclick="cancelEmergencyRequestAction({{ $emergency->id }}, 'radiology', {{ $radReq->id }}, 'emergency-rad-card-{{ $radReq->id }}')">
                                                            <i class="fas fa-ban me-1"></i> إلغاء الطلب
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>

                                            <!-- شريط مسار ومراحل الفحص التفاعلي (Status Progress Stepper) -->
                                            <div class="px-2 py-2 bg-light-subtle border-bottom">
                                                <div class="d-flex align-items-center justify-content-between position-relative" style="font-size: 0.72rem;">
                                                    <div class="position-absolute start-0 end-0 top-50 translate-middle-y border-top border-2" style="border-color: #dee2e6 !important; z-index: 1;"></div>
                                                    
                                                    <!-- المرحلة 1: طلب الطبيب -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                            <i class="fas fa-check"></i>
                                                        </span>
                                                        <span class="fw-semibold text-dark" style="font-size: 0.7rem;">طلب الطبيب</span>
                                                    </div>

                                                    <!-- المرحلة 2: الكاشير -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isPaid)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-check"></i>
                                                            </span>
                                                            <span class="fw-semibold text-success" style="font-size: 0.7rem;">سُدّد</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-warning text-dark mb-1 shadow-xs border border-warning" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-coins"></i>
                                                            </span>
                                                            <span class="fw-bold text-danger" style="font-size: 0.7rem;">عند الكاشير</span>
                                                        @endif
                                                    </div>

                                                    <!-- المرحلة 3: قسم الأشعة -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isCompleted)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-check"></i>
                                                            </span>
                                                            <span class="fw-semibold text-success" style="font-size: 0.7rem;">أُنجز</span>
                                                        @elseif($isPaid)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-info text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-spinner fa-spin"></i>
                                                            </span>
                                                            <span class="fw-bold text-info" style="font-size: 0.7rem;">قيد التصوير</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-hourglass-start"></i>
                                                            </span>
                                                            <span class="text-muted" style="font-size: 0.7rem;">بالانتظار</span>
                                                        @endif
                                                    </div>

                                                    <!-- المرحلة 4: النتيجة والتقرير -->
                                                    <div class="position-relative text-center d-flex flex-column align-items-center bg-light-subtle px-1" style="z-index: 2;">
                                                        @if($isCompleted)
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-success text-white mb-1 shadow-xs" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-file-medical"></i>
                                                            </span>
                                                            <span class="fw-bold text-success" style="font-size: 0.7rem;">التقرير جاهز</span>
                                                        @else
                                                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center bg-white text-muted mb-1 border" style="width: 20px; height: 20px; font-size: 0.65rem;">
                                                                <i class="fas fa-file"></i>
                                                            </span>
                                                            <span class="text-muted" style="font-size: 0.7rem;">التقرير</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-body p-2.5">
                                                @if($radReq->notes)
                                                    <div class="small text-muted mb-2"><i class="fas fa-sticky-note me-1"></i><strong>ملاحظات:</strong> {{ $radReq->notes }}</div>
                                                @endif

                                                <!-- الجدول السريري المصغر (Clinical Mini-Table) -->
                                                <div class="table-responsive rounded border bg-white mb-0">
                                                    <table class="table table-sm table-hover align-middle mb-0 text-center" style="font-size: 0.8rem;">
                                                        <thead class="table-light text-muted" style="font-size: 0.72rem;">
                                                            <tr>
                                                                <th class="py-1 px-2 text-center" style="width: 28px;">#</th>
                                                                <th class="py-1 px-2 text-start">نوع الفحص الشعاعي</th>
                                                                <th class="py-1 px-2 text-center" style="width: 100px;">الحالة</th>
                                                                @if(!$isPaid && !$isCompleted)
                                                                    <th class="py-1 px-2 text-center" style="width: 35px;">حذف</th>
                                                                @endif
                                                            </tr>
                                                        </thead>
                                                        <tbody id="emergency-rad-tests-tbody-{{ $radReq->id }}">
                                                            @foreach($radReq->radiologyTypes as $itemIdx => $type)
                                                                <tr id="emergency-req-item-rad-{{ $radReq->id }}-{{ $type->id }}">
                                                                    <td class="text-muted font-monospace text-center py-1 px-2">{{ $itemIdx + 1 }}</td>
                                                                    <td class="text-start fw-semibold text-dark py-1 px-2">
                                                                        <i class="fas fa-x-ray text-info me-1 opacity-75"></i> {{ $type->name }}
                                                                    </td>
                                                                    <td class="text-center py-1 px-2">
                                                                        @if($isCompleted)
                                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">مكتمل</span>
                                                                        @elseif($isPaid)
                                                                            <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.68rem;">قيد التصوير</span>
                                                                        @else
                                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle" style="font-size: 0.68rem;">بانتظار السداد</span>
                                                                        @endif
                                                                    </td>
                                                                    @if(!$isPaid && !$isCompleted)
                                                                        <td class="text-center py-1 px-2">
                                                                            <button type="button" class="btn btn-xs btn-outline-danger py-0 px-1.5 rounded-circle shadow-none lh-1" title="حذف هذا الفحص من الطلب" onclick="emergencyRemoveRequestItem({{ $emergency->id }}, 'radiology', {{ $radReq->id }}, {{ $type->id }}, 'emergency-req-item-rad-{{ $radReq->id }}-{{ $type->id }}')">
                                                                                <i class="fas fa-times" style="font-size: 0.68rem;"></i>
                                                                            </button>
                                                                        </td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>

                                                @if($isCompleted)
                                                    <div class="mt-2.5 p-2.5 rounded bg-light border">
                                                        @foreach($radReq->radiologyTypes as $type)
                                                            <div class="border-bottom pb-2 mb-2 last-border-0">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="fw-bold text-dark"><i class="fas fa-camera-retro text-info me-1"></i>{{ $type->name }}</span>
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle small">مكتمل</span>
                                                                </div>
                                                                <div class="p-2 bg-white rounded border small text-dark mb-2" style="white-space: pre-wrap; line-height: 1.6;">
                                                                    {!! nl2br(e($type->pivot->result ?: 'تم إنجاز التصوير والفحص الشعاعي بنجاح.')) !!}
                                                                </div>
                                                                @if(!empty($type->pivot->image_path))
                                                                    <div class="d-flex align-items-center gap-2 pt-1">
                                                                        <a href="{{ route('staff.emergency-radiology.attachment', ['emergencyRadiology' => $radReq, 'typeId' => $type->id]) }}" target="_blank" class="btn btn-sm btn-outline-primary py-1 px-2 fw-bold">
                                                                            <i class="fas fa-image me-1"></i>معاينة صورة الأشعة المرفقة
                                                                        </a>
                                                                        <a href="{{ route('staff.emergency-radiology.attachment', ['emergencyRadiology' => $radReq, 'typeId' => $type->id]) }}" download class="btn btn-sm btn-outline-secondary py-1 px-2">
                                                                            <i class="fas fa-download me-1"></i>تحميل
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach

                                                        @if($radReq->result)
                                                            @if($radReq->result->findings)
                                                                <div class="p-2 bg-white rounded border mb-1.5 small" style="white-space: pre-line;">
                                                                    <strong class="text-dark d-block">المشاهدات:</strong>
                                                                    {{ $radReq->result->findings }}
                                                                </div>
                                                            @endif
                                                            @if($radReq->result->impression)
                                                                <div class="p-2 bg-primary-subtle text-primary-emphasis rounded border border-primary-subtle small fw-semibold">
                                                                    <strong>الانطباع التشخيصي:</strong> {{ $radReq->result->impression }}
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="alert alert-light border small text-muted mb-0 py-1.5 mt-2 d-flex align-items-center gap-2">
                                                        <i class="fas fa-spinner fa-spin text-info"></i>
                                                        <span>{{ $isPaid ? 'بانتظار تصوير المريض وإعداد التقرير الشعاعي...' : 'الطلب بانتظار سداد المريض لدى الكاشير لبدء الفحص بالأشعة.' }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach

                                    {{-- 3. العلاجات المعطاة حالياً --}}
                                    @if($curTreatments->count() > 0)
                                        <div class="emg-cur-card-item card border border-success-subtle shadow-sm rounded-3 overflow-hidden" data-emg-type="treatment">
                                            <div class="card-header py-2 px-3 bg-success-subtle border-success-subtle d-flex justify-content-between align-items-center">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-pills me-1"></i> العلاجات المعطاة ({{ $curTreatments->count() }})
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="card-body p-2.5">
                                                <div class="d-flex flex-column gap-1.5">
                                                    @foreach($curTreatments as $tr)
                                                        <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center small">
                                                            <div>
                                                                <strong class="text-dark">{{ $tr->treatment_name }}</strong>
                                                                @if($tr->instructions)
                                                                    <div class="text-muted extra-small">{{ $tr->instructions }}</div>
                                                                @endif
                                                            </div>
                                                            <div class="text-end">
                                                                <span class="badge bg-secondary-subtle text-dark border">{{ $tr->type }}</span>
                                                                <small class="text-muted d-block font-monospace">{{ $tr->given_at ? \Carbon\Carbon::parse($tr->given_at)->format('H:i') : '' }}</small>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- 4. أرشيف طلبات المختبر والأشعة الملغاة --}}
                                    @if($cancelledLabRequests->count() > 0)
                                        <div class="accordion mb-2 emg-cur-card-item" data-emg-type="lab" id="cancelledLabAccordion">
                                            <div class="accordion-item border-0 bg-transparent">
                                                <h2 class="accordion-header" id="headingCancelledLab">
                                                    <button class="accordion-button collapsed py-2 px-3 bg-light text-muted border rounded-3 fw-bold small shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCancelledLab">
                                                        <i class="fas fa-archive me-2 text-secondary"></i> أرشيف طلبات المختبر الملغاة ({{ $cancelledLabRequests->count() }})
                                                    </button>
                                                </h2>
                                                <div id="collapseCancelledLab" class="accordion-collapse collapse pt-2" data-bs-parent="#cancelledLabAccordion">
                                                    @foreach($cancelledLabRequests as $cReq)
                                                        <div class="card border border-danger-subtle bg-light-subtle rounded-3 p-2.5 mb-2 opacity-75">
                                                            <div class="d-flex justify-content-between align-items-center small">
                                                                <span class="text-muted"><i class="fas fa-ban text-danger me-1"></i> طلب مختبر ملغي #{{ $cReq->id }}</span>
                                                                <span class="badge bg-danger-subtle text-danger">ملغي</span>
                                                            </div>
                                                            <small class="text-muted mt-1">{{ $cReq->notes ?: 'تم إلغاء الطلب من قبل الطبيب' }}</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if($cancelledRadRequests->count() > 0)
                                        <div class="accordion mb-2 emg-cur-card-item" data-emg-type="radiology" id="cancelledRadAccordion">
                                            <div class="accordion-item border-0 bg-transparent">
                                                <h2 class="accordion-header" id="headingCancelledRad">
                                                    <button class="accordion-button collapsed py-2 px-3 bg-light text-muted border rounded-3 fw-bold small shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCancelledRad">
                                                        <i class="fas fa-archive me-2 text-secondary"></i> أرشيف طلبات الأشعة الملغاة ({{ $cancelledRadRequests->count() }})
                                                    </button>
                                                </h2>
                                                <div id="collapseCancelledRad" class="accordion-collapse collapse pt-2" data-bs-parent="#cancelledRadAccordion">
                                                    @foreach($cancelledRadRequests as $cReq)
                                                        <div class="card border border-danger-subtle bg-light-subtle rounded-3 p-2.5 mb-2 opacity-75">
                                                            <div class="d-flex justify-content-between align-items-center small">
                                                                <span class="text-muted"><i class="fas fa-ban text-danger me-1"></i> طلب أشعة ملغي #{{ $cReq->id }}</span>
                                                                <span class="badge bg-danger-subtle text-danger">ملغي</span>
                                                            </div>
                                                            <small class="text-muted mt-1">{{ $cReq->notes ?: 'تم إلغاء الطلب من قبل الطبيب' }}</small>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-clipboard-check fa-3x opacity-25 mb-2"></i>
                                    <h6 class="fw-bold mb-1">لا توجد طلبات مسجلة لهذه الحالة بعد</h6>
                                    <p class="small mb-0">يمكنك طلب تحاليل أو أشعة أو تسجيل أدوية من شاشة العمل على اليمين.</p>
                                </div>
                            @endif
                        </div>

                        <!-- 2. نتائج التحاليل السابقة -->
                        <div class="tab-pane fade" id="side-past-lab-pane" role="tabpanel">
                                    @if(isset($pastLabRequests) && $pastLabRequests->count() > 0)
                                        <div class="d-flex flex-column gap-3">
                                            @foreach($pastLabRequests as $medRequest)
                                                @php
                                                    $reqDetails = is_string($medRequest->details) ? json_decode($medRequest->details, true) : ($medRequest->details ?? []);
                                                    $resultData = is_string($medRequest->result) ? json_decode($medRequest->result, true) : ($medRequest->result ?? []);
                                                    $testAttachments = $reqDetails['test_attachments'] ?? ($resultData['test_attachments'] ?? []);
                                                    if (!is_array($testAttachments)) $testAttachments = [];
                                                    $hasAttachment = !empty($reqDetails['attachment']);
                                                    $isImageAttachment = $hasAttachment && str_starts_with($reqDetails['attachment_mime'] ?? '', 'image/');
                                                @endphp
                                                <div class="card border rounded-3 shadow-sm overflow-hidden">
                                                    <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-primary"><i class="fas fa-flask me-1"></i> تحليل مخبري</span>
                                                            <strong class="text-dark small">{{ $medRequest->created_at->format('Y-m-d H:i') }}</strong>
                                                            <small class="text-muted">({{ $medRequest->created_at->diffForHumans() }})</small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1">
                                                            <span class="badge bg-light text-dark border">د. {{ $medRequest->visit?->doctor?->user?->name ?? 'الطبيب المعالج' }}</span>
                                                            <span class="badge bg-{{ $medRequest->status === 'completed' ? 'success' : 'warning text-dark' }}">
                                                                {{ $medRequest->status === 'completed' ? 'مكتمل' : 'قيد الفحص' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        @if(!empty($reqDetails['tests']) && is_array($reqDetails['tests']))
                                                            <div class="d-flex flex-wrap gap-1 mb-2">
                                                                @foreach($reqDetails['tests'] as $tItem)
                                                                    <span class="badge bg-light text-dark border font-monospace">{{ $tItem }}</span>
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        <!-- المرفقات والتقارير إن وجدت -->
                                                        @if(!empty($testAttachments) && count($testAttachments) > 0)
                                                            <div class="d-flex flex-column gap-2 mb-2">
                                                                <div class="small fw-bold text-success d-flex align-items-center gap-1">
                                                                    <i class="fas fa-paperclip"></i> تقارير أجهزة الفحص المرفقة:
                                                                </div>
                                                                @foreach($testAttachments as $tName => $tAtt)
                                                                    @php
                                                                        $tPath = $tAtt['path'] ?? '';
                                                                        $tUrl = $tPath ? route('lab.attachment', ['request' => $medRequest, 'test' => $tName]) : '';
                                                                        $tMime = $tAtt['mime'] ?? '';
                                                                        $tIsImage = str_starts_with($tMime, 'image/');
                                                                    @endphp
                                                                    @if($tUrl)
                                                                        <div class="p-2 rounded border bg-success-subtle border-success-subtle d-flex justify-content-between align-items-center">
                                                                            <div class="d-flex align-items-center gap-2">
                                                                                <i class="fas {{ $tIsImage ? 'fa-file-image text-primary' : 'fa-file-pdf text-danger' }}"></i>
                                                                                <span class="badge bg-success text-white font-monospace">{{ $tName }}</span>
                                                                                <small class="text-dark fw-semibold">{{ $tAtt['name'] ?? 'تقرير جهاز الفحص' }}</small>
                                                                            </div>
                                                                            <a href="{{ $tUrl }}" target="_blank" class="btn btn-xs btn-success fw-bold px-2 py-1" style="font-size: 0.75rem;">
                                                                                <i class="fas fa-eye me-1"></i> فتح المرفق
                                                                            </a>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endif

                                                        @if(isset($resultData['test_results']) && is_array($resultData['test_results']) && count($resultData['test_results']) > 0)
                                                            <div class="table-responsive mt-2">
                                                                <table class="table table-sm table-bordered mb-0 align-middle text-center" style="font-size: 0.8rem;">
                                                                    <thead class="table-light">
                                                                        <tr>
                                                                            <th class="text-start">الفحص</th>
                                                                            <th>النتيجة</th>
                                                                            <th>الوحدة</th>
                                                                            <th>المعدل الطبيعي</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($resultData['test_results'] as $testName => $testData)
                                                                            @php
                                                                                $value = is_array($testData) ? ($testData['value'] ?? '-') : $testData;
                                                                                $unit = is_array($testData) ? ($testData['unit'] ?? '-') : '-';
                                                                                $reference = is_array($testData) ? ($testData['reference'] ?? ($testData['reference_range'] ?? '-')) : '-';
                                                                                $isAbnormal = is_array($testData) && isset($testData['abnormal']) && $testData['abnormal'];
                                                                            @endphp
                                                                            <tr class="{{ $isAbnormal ? 'table-warning' : '' }}">
                                                                                <td class="text-start fw-bold">{{ $testName }}</td>
                                                                                <td>
                                                                                    <span class="badge bg-{{ $isAbnormal ? 'warning' : 'success' }} text-dark fs-6">{{ $value }}</span>
                                                                                </td>
                                                                                <td class="text-muted">{{ $unit }}</td>
                                                                                <td><small class="text-muted">{{ $reference }}</small></td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-flask fa-2x opacity-25 mb-2"></i>
                                            <p class="mb-0">لا توجد تحاليل مخبرية سابقة مسجلة لهذا المريض.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- 3. تقارير الأشعة والتصوير السابقة -->
                                <div class="tab-pane fade" id="side-past-rad-pane" role="tabpanel">
                                    @if(isset($pastRadiologyRequests) && $pastRadiologyRequests->count() > 0)
                                        <div class="d-flex flex-column gap-3">
                                            @foreach($pastRadiologyRequests as $medRequest)
                                                @php
                                                    $reqDetails = is_string($medRequest->details) ? json_decode($medRequest->details, true) : ($medRequest->details ?? []);
                                                    $radResult = is_string($medRequest->result) ? json_decode($medRequest->result, true) : ($medRequest->result ?? []);
                                                    if (!is_array($radResult) && !empty($medRequest->result)) {
                                                        $radResult = ['findings' => (string)$medRequest->result];
                                                    }
                                                    $radFindings = $radResult['findings'] ?? $radResult['result_text'] ?? null;
                                                    $radImpression = $radResult['impression'] ?? null;
                                                    $radRecommendations = $radResult['recommendations'] ?? null;
                                                    $radImages = $radResult['images'] ?? [];
                                                    $radiologist = $radResult['radiologist'] ?? null;
                                                @endphp
                                                <div class="card border rounded-3 shadow-sm overflow-hidden">
                                                    <div class="card-header py-2 px-3 bg-light d-flex justify-content-between align-items-center">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-info text-dark"><i class="fas fa-x-ray me-1"></i> فحص إشعاعي</span>
                                                            <strong class="text-dark small">{{ $medRequest->created_at->format('Y-m-d H:i') }}</strong>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-1">
                                                            @if($radiologist)
                                                                <span class="badge bg-light text-muted border">طبيب الأشعة: {{ $radiologist }}</span>
                                                            @endif
                                                            <span class="badge bg-{{ $medRequest->status === 'completed' ? 'success' : 'warning text-dark' }}">
                                                                {{ $medRequest->status === 'completed' ? 'مكتمل' : 'قيد الفحص' }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body p-3">
                                                        @if(!empty($radFindings))
                                                            <div class="mb-2">
                                                                <strong class="d-block text-dark small mb-1"><i class="fas fa-microscope text-info me-1"></i> النتائج والمشاهدات (Findings):</strong>
                                                                <div class="p-2 bg-light rounded border small" style="white-space: pre-line;">{{ $radFindings }}</div>
                                                            </div>
                                                        @endif
                                                        @if(!empty($radImpression))
                                                            <div class="mb-2">
                                                                <strong class="d-block text-primary small mb-1"><i class="fas fa-clipboard-check me-1"></i> الانطباع التشخيصي (Impression):</strong>
                                                                <div class="p-2 bg-primary-subtle text-primary rounded border border-primary-subtle small fw-bold" style="white-space: pre-line;">{{ $radImpression }}</div>
                                                            </div>
                                                        @endif
                                                        @if(!empty($radRecommendations))
                                                            <div class="mb-2">
                                                                <strong class="d-block text-secondary small mb-1"><i class="fas fa-comment-medical me-1"></i> التوصيات:</strong>
                                                                <div class="p-2 bg-light rounded border text-muted small" style="white-space: pre-line;">{{ $radRecommendations }}</div>
                                                            </div>
                                                        @endif
                                                        @if(is_array($radImages) && count($radImages) > 0)
                                                            <div class="mt-2 pt-2 border-top">
                                                                <strong class="d-block text-dark small mb-1.5"><i class="fas fa-images text-info me-1"></i> الصور والمرفقات الإشعاعية ({{ count($radImages) }}):</strong>
                                                                <div class="d-flex flex-wrap gap-2">
                                                                    @foreach($radImages as $imgIndex => $imgPath)
                                                                        @php
                                                                            $imgUrl = asset('storage/' . $imgPath);
                                                                            $isPdf = str_ends_with(strtolower($imgPath), '.pdf');
                                                                        @endphp
                                                                        @if($isPdf)
                                                                            <a href="{{ $imgUrl }}" target="_blank" class="btn btn-xs btn-outline-info rounded-pill py-1 px-2.5 d-inline-flex align-items-center gap-1">
                                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                                <span>ملف PDF #{{ $imgIndex + 1 }} ↗</span>
                                                                            </a>
                                                                        @else
                                                                            <a href="{{ $imgUrl }}" target="_blank" class="d-inline-block border rounded overflow-hidden shadow-sm" style="width: 70px; height: 70px;">
                                                                                <img src="{{ $imgUrl }}" alt="Radiology Image" style="width: 100%; height: 100%; object-fit: cover;">
                                                                            </a>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-x-ray fa-2x opacity-25 mb-2"></i>
                                            <p class="mb-0">لا توجد تقارير أشعة سابقة مسجلة لهذا المريض.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- 4. الزيارات الاستشارية السابقة -->
                                <div class="tab-pane fade" id="side-past-visits-pane" role="tabpanel">
                                    @if(isset($pastVisits) && $pastVisits->count() > 0)
                                        <div class="accordion d-flex flex-column gap-2" id="pastVisitsEmergencyAccordion">
                                            @foreach($pastVisits as $pv)
                                                @php
                                                    $pvMeds = $pv->prescribedMedications ?? collect();
                                                    $pvDiag = $pv->diagnosis ?? [];
                                                @endphp
                                                <div class="card border rounded-3 overflow-hidden shadow-sm">
                                                    <div class="card-header bg-light py-2 px-3 cursor-pointer d-flex justify-content-between align-items-center"
                                                         data-bs-toggle="collapse" 
                                                         data-bs-target="#pvCollapse{{ $pv->id }}"
                                                         style="cursor: pointer;">
                                                        <div>
                                                            <strong class="text-dark small d-block">{{ $pv->visit_date ?: $pv->created_at->format('Y-m-d') }}</strong>
                                                            <small class="text-muted">{{ $pv->created_at->diffForHumans() }}</small>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                                د. {{ $pv->doctor?->user?->name ?? 'طبيب استشاري' }}
                                                            </span>
                                                            <span class="badge bg-secondary-subtle text-secondary border">
                                                                {{ $pv->department?->name ?? 'عيادة استشارية' }}
                                                            </span>
                                                            <i class="fas fa-chevron-down text-muted"></i>
                                                        </div>
                                                    </div>
                                                    <div id="pvCollapse{{ $pv->id }}" class="collapse" data-bs-parent="#pastVisitsEmergencyAccordion">
                                                        <div class="card-body p-3 bg-white">
                                                            @if(!empty($pv->chief_complaint))
                                                                <div class="mb-2">
                                                                    <strong class="text-dark small d-block">الشكوى الرئيسية:</strong>
                                                                    <p class="mb-1 text-muted small">{{ $pv->chief_complaint }}</p>
                                                                </div>
                                                            @endif
                                                            @if(!empty($pvDiag))
                                                                <div class="mb-2">
                                                                    <strong class="text-primary small d-block">التشخيص الطبي:</strong>
                                                                    <p class="mb-1 fw-bold text-dark small">
                                                                        {{ is_array($pvDiag) ? ($pvDiag['description'] ?? ($pvDiag['name'] ?? ($pvDiag['code'] ?? '-'))) : $pvDiag }}
                                                                    </p>
                                                                </div>
                                                            @endif
                                                            @if($pvMeds->count() > 0)
                                                                <div class="mt-2 pt-2 border-top">
                                                                    <strong class="text-success small d-block mb-1"><i class="fas fa-pills me-1"></i> الأدوية الموصوفة ({{ $pvMeds->count() }}):</strong>
                                                                    <div class="d-flex flex-wrap gap-1">
                                                                        @foreach($pvMeds as $med)
                                                                            <span class="badge bg-light text-dark border small">
                                                                                {{ $med->name ?? ($med->medicine?->name ?? 'دواء') }} ({{ $med->dosage_frequency ?? ($med->dosage ?? '') }})
                                                                            </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted">
                                            <i class="fas fa-user-md fa-2x opacity-25 mb-2"></i>
                                            <p class="mb-0">لا توجد زيارات استشارية سابقة مسجلة لهذا المريض.</p>
                                        </div>
                                    @endif
                                </div>

                                <!-- 5. العمليات الجراحية وحالات الطوارئ السابقة -->
                                <div class="tab-pane fade" id="side-past-surgeries-pane" role="tabpanel">
                                    {{-- العمليات السابقة --}}
                                    <div class="mb-3">
                                        <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                                            <i class="fas fa-procedures text-danger me-1"></i> العمليات الجراحية السابقة ({{ isset($pastSurgeries) ? $pastSurgeries->count() : 0 }})
                                        </h6>
                                        @if(isset($pastSurgeries) && $pastSurgeries->count() > 0)
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($pastSurgeries as $surgery)
                                                    <div class="card border rounded-3 p-2.5 shadow-sm small">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <strong class="text-danger"><i class="fas fa-scalpel me-1"></i> {{ $surgery->procedure_name ?: 'عملية جراحية' }}</strong>
                                                            <span class="badge bg-{{ $surgery->status === 'completed' ? 'success' : 'primary' }}">
                                                                {{ $surgery->status_text ?? $surgery->status }}
                                                            </span>
                                                        </div>
                                                        <div class="text-muted extra-small">
                                                            <span>التاريخ: {{ $surgery->scheduled_date ? \Carbon\Carbon::parse($surgery->scheduled_date)->format('Y-m-d') : '-' }}</span>
                                                            <span class="ms-3">الجراح: {{ $surgery->surgeon_name ?: ($surgery->doctor?->user?->name ?? '-') }}</span>
                                                        </div>
                                                        @if($surgery->notes)
                                                            <div class="text-muted extra-small bg-light p-1.5 rounded mt-1">{{ $surgery->notes }}</div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted small mb-0">لا توجد عمليات جراحية سابقة مسجلة لهذا المريض.</p>
                                        @endif
                                    </div>

                                    {{-- حالات الطوارئ السابقة --}}
                                    <div>
                                        <h6 class="fw-bold text-dark border-bottom pb-1 mb-2">
                                            <i class="fas fa-ambulance text-warning me-1"></i> حالات الطوارئ السابقة ({{ isset($pastEmergencies) ? $pastEmergencies->count() : 0 }})
                                        </h6>
                                        @if(isset($pastEmergencies) && $pastEmergencies->count() > 0)
                                            <div class="d-flex flex-column gap-2">
                                                @foreach($pastEmergencies as $pe)
                                                    <div class="card border rounded-3 p-2.5 shadow-sm small">
                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                            <strong class="text-dark"><i class="fas fa-ambulance text-warning me-1"></i> حالة طوارئ #{{ $pe->id }}</strong>
                                                            <span class="badge bg-{{ $pe->status === 'discharged' ? 'secondary' : 'warning text-dark' }}">
                                                                {{ $pe->status_text }}
                                                            </span>
                                                        </div>
                                                        <div class="text-muted extra-small mb-1">
                                                            <span>وقت الدخول: {{ $pe->admission_time ? \Carbon\Carbon::parse($pe->admission_time)->format('Y-m-d H:i') : $pe->created_at->format('Y-m-d H:i') }}</span>
                                                            <span class="ms-2">الطبيب: {{ $pe->doctor?->user?->name ? 'د. ' . $pe->doctor->user->name : '-' }}</span>
                                                        </div>
                                                        @if($pe->symptoms)
                                                            <div class="extra-small mb-0.5"><strong class="text-dark">الأعراض:</strong> <span class="text-muted">{{ $pe->symptoms }}</span></div>
                                                        @endif
                                                        @if($pe->diagnosis)
                                                            <div class="extra-small mb-0.5"><strong class="text-primary">التشخيص:</strong> <span class="text-dark fw-semibold">{{ $pe->diagnosis }}</span></div>
                                                        @endif
                                                        @if($pe->treatment_given)
                                                            <div class="extra-small"><strong class="text-success">العلاج:</strong> <span class="text-muted">{{ $pe->treatment_given }}</span></div>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-muted small mb-0">لا توجد حالات طوارئ سابقة أخرى لهذا المريض.</p>
                                        @endif
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

<!-- Modal طلب استشارة طبية -->
<div class="modal fade" id="consultationModal" tabindex="-1" aria-labelledby="consultationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('emergency.create-consultation', $emergency) }}" method="POST">
                @csrf
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="consultationModalLabel">
                        <i class="fas fa-user-md me-2"></i>طلب استشارة طبية لحالة الطوارئ
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2">
                        <i class="fas fa-info-circle me-1"></i>
                        يمكنك اختيار طبيب استشاري أو جراح أو أكثر لتحويل المريض للاستشارة مع تسجيل الرسوم تلقائياً في حساب الطوارئ.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="consult_appointment_date" class="form-label fw-bold">
                                <i class="fas fa-calendar me-1 text-primary"></i>تاريخ الاستشارة <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="consult_appointment_date" name="appointment_date" 
                                   value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="consult_appointment_time" class="form-label fw-bold">
                                <i class="fas fa-clock me-1 text-primary"></i>وقت الاستشارة <span class="text-danger">*</span>
                            </label>
                            <input type="time" class="form-control" id="consult_appointment_time" name="appointment_time" 
                                   value="{{ date('H:i', strtotime('+1 hour')) }}" required>
                        </div>

                        <div class="col-12">
                            <label for="consult_doctor_ids" class="form-label fw-bold">
                                <i class="fas fa-stethoscope me-1 text-success"></i>اختر الأطباء الاستشاريين المتاحين اليوم <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="consult_doctor_ids" name="doctor_ids[]" multiple size="6" required>
                                @forelse($consultantDoctors ?? [] as $doc)
                                    <option value="{{ $doc->id }}">
                                        د. {{ $doc->user->name ?? 'طبيب' }} - {{ $doc->department->name ?? 'قسم عام' }} ({{ $doc->specialization ?? 'استشاري' }})
                                    </option>
                                @empty
                                    <option disabled>لا يوجد أطباء استشاريين متوفرين اليوم في المستشفى</option>
                                @endforelse
                            </select>
                            <small class="text-muted">اضغط زر Ctrl (أو Cmd) لتحديد أكثر من طبيب استشاري من المتواجدين اليوم.</small>
                        </div>

                        <div class="col-12">
                            <label for="consult_notes" class="form-label fw-bold">
                                <i class="fas fa-notes-medical me-1 text-primary"></i>ملاحظات وتفاصيل الاستشارة المطلوبة
                            </label>
                            <textarea class="form-control" id="consult_notes" name="notes" rows="4" 
                                      placeholder="اكتب أسباب التحويل، الأعراض المقلقة، التوصيات أو أي أسئلة موجهة للاستشاري...">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-info text-white fw-bold">
                        <i class="fas fa-paper-plane me-1"></i>إرسال طلب الاستشارة
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal إلغاء حالة الطوارئ -->
<div class="modal fade" id="cancelEmergencyModal" tabindex="-1" aria-labelledby="cancelEmergencyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-danger text-white p-3">
                <h5 class="modal-title fw-bold mb-0" id="cancelEmergencyModalLabel">
                    <i class="fas fa-ban me-2"></i>تأكيد إلغاء حالة الطوارئ #{{ $emergency->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('emergency.cancel', $emergency) }}" method="POST">
                @csrf
                <div class="modal-body p-4 bg-light text-end">
                    <div class="card border-0 shadow-xs p-3 bg-white mb-3 rounded-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">المريض:</span>
                            <strong class="text-dark">{{ $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">الطبيب المسؤول:</span>
                            <span class="text-dark">{{ $emergency->doctor?->user?->name ?? 'غير محدد' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">حالة السداد الحالية:</span>
                            @if($emergency->payment_status === 'paid')
                                <span class="badge bg-success">مسدد مسبقاً (سيتم إحالته لاسترجاع الكاشير)</span>
                            @else
                                <span class="badge bg-secondary">غير مسدد</span>
                            @endif
                        </div>
                    </div>

                    @if($emergency->payment_status === 'paid')
                        <div class="alert alert-warning py-2 px-3 small border-warning">
                            <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                            <strong>ملاحظة مالية:</strong> هذه الحالة مسددة مسبقاً في الكاشير. عند الإلغاء سيتم تحويل المريض مباشرة إلى <strong>مستحقات الاسترجاع بالكاشير</strong> لصرف واسترداد المبلغ المسدد للمريض.
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="cancel_reason" class="form-label fw-bold text-dark small">
                            سبب إلغاء الحالة <span class="text-danger">*</span>:
                        </label>
                        <textarea class="form-control" id="cancel_reason" name="cancel_reason" rows="3" required
                                  placeholder="يرجى كتابة سبب الإلغاء بدقة (مثال: عدم الحاجة للعلاج، تحويل خارجي، طلب المريض، إلخ)..."></textarea>
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

// البحث التفاعلي المباشر لتحاليل المختبر والأشعة
(function() {
    function initLiveFilter(inputId, clearBtnId, itemSelector, nameSelector, emptyId, countId, chkSelector, filterBtnSelector) {
        const input = document.getElementById(inputId);
        const clearBtn = document.getElementById(clearBtnId);
        const items = document.querySelectorAll(itemSelector);
        const emptyMsg = document.getElementById(emptyId);
        const countBadge = document.getElementById(countId);
        const checkboxes = document.querySelectorAll(chkSelector);
        const filterBtns = filterBtnSelector ? document.querySelectorAll(filterBtnSelector) : [];
        let activeFilter = 'all';

        if (!input && !filterBtns.length) return;

        function normalizeArabic(text) {
            return (text || '')
                .toLowerCase()
                .replace(/[أإآء]/g, 'ا')
                .replace(/ة/g, 'ه')
                .replace(/ى/g, 'ي')
                .replace(/[\u064B-\u065F]/g, '')
                .trim();
        }

        function applyFilter() {
            const query = input ? normalizeArabic(input.value) : '';
            let visibleCount = 0;

            if (clearBtn && input) {
                clearBtn.classList.toggle('d-none', !input.value);
            }

            items.forEach(item => {
                const nameEl = item.querySelector(nameSelector);
                const name = normalizeArabic(nameEl ? nameEl.textContent : '');
                const chk = item.querySelector('input[type="checkbox"]');
                const isInsurance = item.getAttribute('data-is-insurance') === '1';

                const matchesQuery = !query || name.includes(query) || (chk && chk.checked);
                let matchesCategory = true;
                if (activeFilter === 'insurance') {
                    matchesCategory = isInsurance || (chk && chk.checked);
                } else if (activeFilter === 'cash') {
                    matchesCategory = !isInsurance || (chk && chk.checked);
                }

                if (matchesQuery && matchesCategory) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            });

            if (emptyMsg) {
                emptyMsg.classList.toggle('d-none', visibleCount > 0);
            }
        }

        if (input) {
            input.addEventListener('input', applyFilter);
        }

        if (clearBtn && input) {
            clearBtn.addEventListener('click', function() {
                input.value = '';
                applyFilter();
                input.focus();
            });
        }

        if (filterBtns.length) {
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeFilter = this.getAttribute('data-filter') || 'all';
                    applyFilter();
                });
            });
        }

        function updateSelected() {
            if (!countBadge || !checkboxes.length) return;
            const checkedCount = Array.from(checkboxes).filter(c => c.checked).length;
            if (checkedCount > 0) {
                countBadge.textContent = `${checkedCount} محدد`;
                countBadge.classList.remove('d-none');
            } else {
                countBadge.classList.add('d-none');
            }
        }

        checkboxes.forEach(chk => chk.addEventListener('change', updateSelected));
        updateSelected();
    }

    initLiveFilter('serviceSearchInput', 'clearServiceSearch', '.service-item', '.service-name', 'noServicesFound', 'serviceSelectedCount', '.service-checkbox', '.service-filter-btn');
    initLiveFilter('labSearchInput', 'clearLabSearch', '.lab-test-item', '.lab-name', 'noLabTestsFound', 'labSelectedCount', '.lab-checkbox', '.lab-filter-btn');
    initLiveFilter('radSearchInput', 'clearRadSearch', '.rad-type-item', '.rad-name', 'noRadTypesFound', 'radSelectedCount', '.rad-checkbox', '.rad-filter-btn');

    // إدارة عداد كميات الخدمات التمريضية والطبية (زيادة / إنقاص / تحديث السعر الفوري)
    document.querySelectorAll('.service-item').forEach(function(row) {
        const checkbox = row.querySelector('.service-checkbox');
        const qtyGroup = row.querySelector('.service-qty-group');
        const qtyInput = row.querySelector('.service-qty-input');
        const minusBtn = row.querySelector('.btn-service-minus');
        const plusBtn = row.querySelector('.btn-service-plus');
        const priceVal = row.querySelector('.price-val');
        const formCheck = row.querySelector('.form-check');

        if (!qtyInput) return;
        const unitPrice = parseFloat(qtyInput.getAttribute('data-unit-price')) || 0;

        function updatePrice() {
            let q = parseInt(qtyInput.value) || 1;
            if (q < 1) {
                q = 1;
                qtyInput.value = 1;
            }
            if (priceVal) {
                priceVal.textContent = Math.round(unitPrice * q).toLocaleString('en-US');
            }
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let current = parseInt(qtyInput.value) || 1;
                qtyInput.value = current + 1;
                updatePrice();
            });
        }

        if (minusBtn) {
            minusBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                let current = parseInt(qtyInput.value) || 1;
                if (current > 1) {
                    qtyInput.value = current - 1;
                    updatePrice();
                }
            });
        }

        if (qtyInput) {
            qtyInput.addEventListener('input', updatePrice);
            qtyInput.addEventListener('change', updatePrice);
        }

        if (checkbox && !checkbox.disabled) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    if (qtyGroup) qtyGroup.classList.remove('d-none');
                    if (formCheck) formCheck.classList.add('bg-light', 'border');
                    if (!qtyInput.value || parseInt(qtyInput.value) < 1) {
                        qtyInput.value = 1;
                    }
                    updatePrice();
                } else {
                    if (qtyGroup) qtyGroup.classList.add('d-none');
                    if (formCheck) formCheck.classList.remove('bg-light', 'border');
                    qtyInput.value = 1;
                    updatePrice();
                }
            });
        }
    });

    /**
     * تمكين طبيب الطوارئ من حذف فحص/تحليل محدد من الطلب المعلق مباشرة دون تحديث كامل الصفحة
     */
    window.emergencyRemoveRequestItem = function(emergencyId, type, requestId, itemId, elementId) {
        var itemTypeName = (type === 'lab') ? 'التحليل المخبري' : 'فحص الأشعة';
        if (!confirm('هل أنت متأكد من حذف هذا ' + itemTypeName + ' من الطلب؟')) {
            return;
        }

        var el = document.getElementById(elementId);
        if (el) {
            el.style.opacity = '0.5';
            el.style.pointerEvents = 'none';
        }

        var url = '{{ url("/emergency") }}/' + emergencyId + '/remove-request-item';
        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
            || document.querySelector('input[name="_token"]')?.value;

        fetch(url, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: type,
                request_id: requestId,
                item_id: itemId
            })
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, status: res.status, data: data };
            });
        })
        .then(function(resObj) {
            if (!resObj.ok || !resObj.data.success) {
                alert(resObj.data.message || 'تعذر حذف العنصر من الطلب');
                if (el) {
                    el.style.opacity = '1';
                    el.style.pointerEvents = 'auto';
                }
                return;
            }

            // تم الحذف بنجاح
            if (el) {
                el.remove();
            }

            // إذا كان الفحص الأخير وألغي الطلب، يتم إخفاء كارت الطلب بسلاسة
            if (resObj.data.is_cancelled || resObj.data.remaining_count === 0) {
                var cardPrefix = (type === 'lab') ? 'emergency-lab-card-' : 'emergency-rad-card-';
                var card = document.getElementById(cardPrefix + requestId);
                if (card) {
                    card.style.transition = 'all 0.4s ease';
                    card.style.opacity = '0';
                    card.style.transform = 'scale(0.95)';
                    setTimeout(function() {
                        card.remove();
                    }, 400);
                }
            }
        })
        .catch(function(err) {
            console.error(err);
            alert('حدث خطأ في الاتصال بالخادم');
            if (el) {
                el.style.opacity = '1';
                el.style.pointerEvents = 'auto';
            }
        });
    };

    /**
     * إلغاء طلب فحص طوارئ معلق أو مسدد وإرساله للاسترجاع في الكاشير
     */
    window.cancelEmergencyRequestAction = function(emergencyId, type, requestId, cardId) {
        var typeTitle = (type === 'lab') ? 'التحليل المخبري' : 'فحص الأشعة';
        var reason = prompt('يرجى تأكيد سبب إلغاء هذا الطلب (مثال: طلب بالخطأ، تراجع المريض، فحص خارجي):', 'طلب بالخطأ');
        if (reason === null) return;

        var card = document.getElementById(cardId);
        if (card) {
            card.style.opacity = '0.5';
            card.style.pointerEvents = 'none';
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
            || document.querySelector('input[name="_token"]')?.value;

        fetch('{{ url("/emergency") }}/' + emergencyId + '/cancel-request', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                type: type,
                request_id: requestId,
                reason: reason
            })
        })
        .then(function(res) {
            return res.json().then(function(data) {
                return { ok: res.ok, data: data };
            });
        })
        .then(function(resObj) {
            if (!resObj.ok || !resObj.data.success) {
                alert(resObj.data.message || 'تعذر إلغاء الطلب');
                if (card) {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                }
                return;
            }

            alert(resObj.data.message);
            window.location.reload();
        })
        .catch(function(err) {
            console.error(err);
            alert('حدث خطأ في الاتصال أثناء معالجة الإلغاء');
            if (card) {
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
            }
        });
    };

    /**
     * تصفية طلبات الحالة الحالية بالسجل الطبي (الكل / مختبر / أشعة / علاجات) مطابق لمحطة الاستشارية
     */
    window.filterCurEmergencyRequests = function(filterType, btn) {
        document.querySelectorAll('.emg-cur-filter-pill').forEach(function(pill) {
            pill.classList.remove('active', 'btn-dark', 'btn-primary', 'btn-info', 'btn-success');
            var f = pill.getAttribute('data-filter');
            if (f === 'all') pill.classList.add('btn-outline-secondary');
            else if (f === 'lab') pill.classList.add('btn-outline-primary');
            else if (f === 'radiology') pill.classList.add('btn-outline-info');
            else if (f === 'treatment') pill.classList.add('btn-outline-success');
        });

        btn.classList.add('active');
        btn.classList.remove('btn-outline-secondary', 'btn-outline-primary', 'btn-outline-info', 'btn-outline-success');
        if (filterType === 'all') btn.classList.add('btn-dark');
        else if (filterType === 'lab') btn.classList.add('btn-primary');
        else if (filterType === 'radiology') btn.classList.add('btn-info');
        else if (filterType === 'treatment') btn.classList.add('btn-success');

        document.querySelectorAll('.emg-cur-card-item').forEach(function(card) {
            if (filterType === 'all' || card.getAttribute('data-emg-type') === filterType) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });
    };
})();
</script>
@endpush