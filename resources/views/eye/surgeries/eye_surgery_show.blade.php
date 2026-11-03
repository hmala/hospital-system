@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- Header -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h2 class="h3 fw-bold text-primary mb-0">
                    <i class="fas fa-file-medical-alt me-2"></i>ملف العملية الجراحية #{{ $surgery->id }}
                </h2>
                @if($surgery->status === 'scheduled')
                    <span class="badge bg-warning text-dark fs-6"><i class="fas fa-clock me-1"></i>مجدولة (Scheduled)</span>
                @elseif($surgery->status === 'in_progress')
                    <span class="badge bg-primary text-white fs-6"><i class="fas fa-spinner fa-spin me-1"></i>جارية الآن (In Progress)</span>
                @elseif($surgery->status === 'completed')
                    <span class="badge bg-success text-white fs-6"><i class="fas fa-check-circle me-1"></i>مكتملة وناجحة (Completed)</span>
                @else
                    <span class="badge bg-danger text-white fs-6"><i class="fas fa-times-circle me-1"></i>ملغاة (Cancelled)</span>
                @endif
            </div>
            <p class="text-muted mb-0 small">
                {{ $surgery->procedure_name }} • تاريخ الإجراء: {{ $surgery->surgery_date->format('Y-m-d h:i A') }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('eye.surgeries.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i>رجوع للجدول
            </a>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateSurgeryModal">
                <i class="fas fa-edit me-1"></i>تحديث التقرير والحالة
            </button>
            <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                <i class="fas fa-print me-1"></i>طباعة التقرير
            </button>
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Patient Info Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-user-injured text-primary me-2"></i>المريض</h5>
                </div>
                <div class="card-body">
                    <div class="text-center py-2 mb-3 border-bottom">
                        <div class="avatar-lg bg-primary-subtle text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-2" style="width: 70px; height: 70px;">
                            <i class="fas fa-user fa-2x"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">{{ $surgery->patient->name }}</h5>
                        <div class="text-muted small">MRN: {{ $surgery->patient->medical_record_number ?? 'N/A' }}</div>
                    </div>
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">العمر / الجنس:</span>
                            <span class="fw-bold">{{ $surgery->patient->age ?? '-' }} سنة / {{ $surgery->patient->gender === 'male' ? 'ذكر' : 'أنثى' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">الهاتف:</span>
                            <span class="fw-bold">{{ $surgery->patient->phone ?? '-' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between px-0">
                            <span class="text-muted">رقم الهوية:</span>
                            <span class="fw-bold">{{ $surgery->patient->national_id ?? '-' }}</span>
                        </li>
                    </ul>
                    <div class="d-grid mt-3">
                        <a href="{{ route('eye.examinations.create', ['patient_id' => $surgery->patient_id]) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-stethoscope me-1"></i>فتح فحص سريري للمريض
                        </a>
                    </div>
                </div>
            </div>

            <!-- Target Eye Laterality Display -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 text-center">
                <div class="card-body py-4">
                    <span class="text-muted small fw-bold d-block mb-2">العين المستهدفة للجراحة</span>
                    @if($surgery->target_eye === 'OD')
                        <div class="display-6 fw-bold text-success mb-2">
                            <i class="fas fa-eye me-2"></i>OD
                        </div>
                        <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 border border-success">
                            العين اليمنى (Oculus Dexter)
                        </span>
                    @elseif($surgery->target_eye === 'OS')
                        <div class="display-6 fw-bold text-info mb-2">
                            <i class="fas fa-eye me-2"></i>OS
                        </div>
                        <span class="badge bg-info-subtle text-info fs-6 px-3 py-2 border border-info">
                            العين اليسرى (Oculus Sinister)
                        </span>
                    @else
                        <div class="display-6 fw-bold text-secondary mb-2">
                            <i class="fas fa-glasses me-2"></i>OU
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-2 border">
                            كلتا العينين (Oculus Uterque)
                        </span>
                    @endif
                </div>
            </div>

            <!-- Surgical Team & Facility -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-user-md text-primary me-2"></i>الفريق الجراحي والتخدير</h6>
                </div>
                <div class="card-body small">
                    <div class="mb-2">
                        <span class="text-muted d-block">الجراح المسؤول:</span>
                        <span class="fw-bold text-dark fs-6">{{ $surgery->doctor->user->name ?? 'طبيب العيون' }}</span>
                    </div>
                    <div class="mb-2">
                        <span class="text-muted d-block">نوع التخدير:</span>
                        <span class="badge bg-light text-dark border px-2 py-1">{{ $surgery->anesthesia_type }}</span>
                    </div>
                    <div>
                        <span class="text-muted d-block">تاريخ ووقت الجراحة:</span>
                        <span class="fw-bold text-dark">{{ $surgery->surgery_date->format('Y-m-d h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Surgery Specifications & Operative Notes -->
        <div class="col-lg-8">
            <!-- Implant / Drug Specification -->
            @if($surgery->iolItem || $surgery->iol_power || $surgery->injection_drug)
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark">
                        <i class="fas fa-microchip text-teal me-2" style="color: #0f766e;"></i>مواصفات العدسة المزروعة / العقار المحقون
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @if($surgery->iolItem || $surgery->iol_power)
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold text-teal mb-2" style="color: #0f766e;"><i class="fas fa-circle-notch me-1"></i>العدسة المطوية داخل العين (IOL)</h6>
                                <div class="small">
                                    <div class="mb-1">النوع: <strong>{{ $surgery->iolItem->item_name ?? 'عدسة مطوية' }}</strong></div>
                                    <div class="mb-1">القوة البصرية: <span class="badge bg-primary fs-6">+{{ $surgery->iol_power }} D</span></div>
                                    <div class="mb-1">الرقم التسلسلي: <code>{{ $surgery->iol_serial_number ?? 'غير مسجل' }}</code></div>
                                    <div class="text-success small mt-2"><i class="fas fa-check-circle me-1"></i>تم خصمها من رصيد مخزن العيون تلقائياً</div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($surgery->injection_drug)
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <h6 class="fw-bold text-indigo mb-2" style="color: #4338ca;"><i class="fas fa-syringe me-1"></i>الحقن الزجاجي (Intravitreal)</h6>
                                <div class="small">
                                    <div class="mb-1">العقار: <strong>{{ $surgery->injection_drug }}</strong></div>
                                    <div class="mb-1">الجرعة: <span class="badge bg-info text-dark fs-6">{{ $surgery->injection_dose ?? '0.05 ml' }}</span></div>
                                    <div class="text-muted small mt-2">المسافة: 3.5 - 4.0 mm من الحافة الصلبية (Limbus)</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Operative Report Card -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-medical text-primary me-2"></i>تقرير ومجريات العملية (Operative Report)</h5>
                </div>
                <div class="card-body">
                    @if($surgery->operative_notes)
                        <div class="p-3 bg-light rounded-3 border" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.8;">{{ $surgery->operative_notes }}</div>
                    @else
                        <div class="text-muted text-center py-4">
                            <i class="fas fa-pen-fancy fa-2x mb-2 text-secondary opacity-50"></i>
                            <div>لم يتم تدوين مجريات وخطوات العملية بعد</div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#updateSurgeryModal">
                                <i class="fas fa-edit me-1"></i>تدوين التقرير الآن
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Complications & Postop Plan -->
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-exclamation-triangle me-2"></i>المضاعفات أثناء العملية</h6>
                        </div>
                        <div class="card-body">
                            @if($surgery->complications)
                                <div class="alert alert-danger mb-0 small" style="white-space: pre-wrap;">{{ $surgery->complications }}</div>
                            @else
                                <div class="text-success small fw-semibold">
                                    <i class="fas fa-check-circle me-1"></i>العملية سارت بدون أي مضاعفات مسجلة (Uneventful)
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-white py-3">
                            <h6 class="mb-0 fw-bold text-success"><i class="fas fa-prescription-bottle-alt me-2"></i>تعليمات وخطة ما بعد الجراحة</h6>
                        </div>
                        <div class="card-body">
                            @if($surgery->postop_plan)
                                <div class="p-2 bg-light rounded border small" style="white-space: pre-wrap;">{{ $surgery->postop_plan }}</div>
                            @else
                                <div class="text-muted small">لم يتم تسجيل خطة ما بعد الجراحة بعد.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Update Surgery Report and Status -->
<div class="modal fade" id="updateSurgeryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-notes-medical me-2"></i>تحديث تقرير وحالة العملية الجراحية</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('eye.surgeries.status', $surgery) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">حالة العملية <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-lg" required>
                            <option value="scheduled" {{ $surgery->status === 'scheduled' ? 'selected' : '' }}>⏳ مجدولة (Scheduled)</option>
                            <option value="in_progress" {{ $surgery->status === 'in_progress' ? 'selected' : '' }}>⚡ جارية الآن في صالة العمليات (In Progress)</option>
                            <option value="completed" {{ $surgery->status === 'completed' ? 'selected' : '' }}>✅ مكتملة بنجاح (Completed)</option>
                            <option value="cancelled" {{ $surgery->status === 'cancelled' ? 'selected' : '' }}>❌ ملغاة (Cancelled)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">تقرير وخطوات الجراحة (Operative Notes)</label>
                        <textarea name="operative_notes" rows="5" class="form-control" placeholder="تفاصيل فتح الجرح، التقطيع، الفاكو، سحب القشرة، زرع العدسة، إغلاق الجرح، وتأكيد ضغط العين...">{{ $surgery->operative_notes }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-danger">المضاعفات الجراحية إن وُجدت (Complications)</label>
                        <textarea name="complications" rows="2" class="form-control" placeholder="مثال: تمزق المحفظة الخلفية، نزول زجاجي، نزيف أمامي...">{{ $surgery->complications }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-success">خطة ما بعد العملية والعلاج المنزلي (Postoperative Plan)</label>
                        <textarea name="postop_plan" rows="3" class="form-control" placeholder="العلاج: قطرات المضاد والستيرويد، واقي العين، موعد المراجعة القادمة...">{{ $surgery->postop_plan }}</textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">
                        <i class="fas fa-save me-1"></i>حفظ التعديلات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
