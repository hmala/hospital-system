@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <style>
        .segmented-control-pills {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            padding: 3px;
            gap: 2px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            position: relative;
            user-select: none;
            width: 100%;
            justify-content: space-between;
        }
        .segmented-pill-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }
        .segmented-pill-label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 4px 10px;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            border-radius: 50rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            margin-bottom: 0;
            flex: 1;
            text-align: center;
            white-space: nowrap;
        }
        .segmented-pill-label:hover {
            color: #1e293b;
            background: rgba(255, 255, 255, 0.7);
        }
        .segmented-pill-label i {
            font-size: 0.72rem;
        }
        /* Active Pay */
        .segmented-pill-input:checked + .segmented-pill-label.pill-pay {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }
        /* Active Postpone */
        .segmented-pill-input:checked + .segmented-pill-label.pill-postpone {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.35);
        }
        /* Active Cancel */
        .segmented-pill-input:checked + .segmented-pill-label.pill-cancel {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #ffffff !important;
            box-shadow: 0 2px 6px rgba(239, 68, 68, 0.35);
        }
        .item-reason-container {
            transition: all 0.25s ease-in-out;
        }
    </style>
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            تسوية ودفع رسوم الطلب الطبي
                        </h5>
                        <a href="{{ route('cashier.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i>العودة
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @php
                        $allRequests = collect([$request])->concat($otherPendingRequests ?? collect([]))->unique('id');
                        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                        $patient = optional($request->visit)->patient;
                        $appointment = optional($request->visit)->appointment;
                        $isDirectVisit = empty($appointment) || in_array($request->visit?->visit_type, ['lab', 'radiology', 'external'], true) || (!empty($details['direct_lab_visit']) || !empty($details['direct_radiology_visit']) || !empty($details['created_at_inquiry']));
                        // شرط التحصين: إذا كانت الزيارة مباشرة/خارجية وبدون حجز استشاري، لا يطبق الضمان الصحي وتكون نقدي 100%
                        if ($isDirectVisit && empty($appointment?->doctor_id)) {
                            $defaultInsurance = 'none';
                        } else {
                            $defaultInsurance = $request->insurance_type ?? optional($appointment)->insurance_type ?? optional($patient)->insurance_type ?? 'none';
                        }
                        $hiCategory = $patient ? $patient->healthInsuranceCategory : null;
                        if ($defaultInsurance === 'hi' && $patient) {
                            $defaultCopay = $patient->getCopayPercentageFor($request->type);
                        } else {
                            $defaultCopay = (float)($patient->copay_percentage ?? 15.0);
                        }
                        $defaultCardNo = ($defaultInsurance !== 'none') ? ($patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '') : '';
                        $items = [];
                        $seenItemKeys = [];

                        foreach ($allRequests as $reqItem) {
                            $rDetails = is_string($reqItem->details) ? json_decode($reqItem->details, true) : $reqItem->details;

                            if ($reqItem->type === 'lab') {
                                $packageId = $rDetails['package_id'] ?? null;
                                $pkg = $packageId ? \App\Models\Package::find($packageId) : null;

                                if ($pkg) {
                                    $pkgKey = 'pkg_' . $pkg->id;
                                    if (!isset($seenItemKeys[$pkgKey])) {
                                        $seenItemKeys[$pkgKey] = true;
                                        $pkgTestIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                        $items[] = [
                                            'id' => 'pkg_' . $pkg->id,
                                            'name' => '📦 باقة: ' . $pkg->name . ' (' . count($pkgTestIds) . ' تحاليل مشمولة)',
                                            'code' => $pkg->code ?? 'PKG-' . $pkg->id,
                                            'type' => 'package',
                                            'request_id' => $reqItem->id,
                                            'base_price' => (float)$pkg->price,
                                            'moi_price' => (float)$pkg->price,
                                            'hi_price' => (float)$pkg->price,
                                            'is_moi_active' => false,
                                            'is_hi_active' => false,
                                        ];

                                        // أي تحاليل إضافية خارج الباقة فقط
                                        $allTestIds = $rDetails['lab_test_ids'] ?? [];
                                        $extraTestIds = array_diff($allTestIds, $pkgTestIds);
                                        foreach ($extraTestIds as $tId) {
                                            $tKey = 'lab_' . $tId;
                                            if (isset($seenItemKeys[$tKey])) continue;
                                            $seenItemKeys[$tKey] = true;
                                            $test = \App\Models\LabTest::find($tId);
                                            if ($test) {
                                                $items[] = [
                                                    'id' => $test->id,
                                                    'name' => '🔬 ' . $test->name . ' (تحليل إضافي)',
                                                    'code' => $test->code,
                                                    'type' => 'lab',
                                                    'request_id' => $reqItem->id,
                                                    'base_price' => (float)$test->getRegularPrice(),
                                                    'moi_price' => (float)($test->moi_price > 0 ? $test->moi_price : 0),
                                                    'hi_price' => (float)($test->hi_price > 0 ? $test->hi_price : 0),
                                                    'is_moi_active' => (bool)($test->is_moi_active ?? true),
                                                    'is_hi_active' => (bool)($test->is_hi_active ?? true),
                                                ];
                                            }
                                        }
                                    }
                                } else {
                                    $testIds = $rDetails['lab_test_ids'] ?? [];
                                    if (!empty($testIds)) {
                                        foreach ($testIds as $tId) {
                                            $tKey = 'lab_' . $tId;
                                            if (isset($seenItemKeys[$tKey])) continue;
                                            $seenItemKeys[$tKey] = true;
                                            $test = \App\Models\LabTest::find($tId);
                                            if ($test) {
                                                $items[] = [
                                                    'id' => $test->id,
                                                    'name' => $test->name,
                                                    'code' => $test->code,
                                                    'type' => 'lab',
                                                    'request_id' => $reqItem->id,
                                                    'base_price' => (float)$test->getRegularPrice(),
                                                    'moi_price' => (float)($test->moi_price > 0 ? $test->moi_price : 0),
                                                    'hi_price' => (float)($test->hi_price > 0 ? $test->hi_price : 0),
                                                    'is_moi_active' => (bool)($test->is_moi_active ?? true),
                                                    'is_hi_active' => (bool)($test->is_hi_active ?? true),
                                                ];
                                            }
                                        }
                                    } elseif (!empty($rDetails['tests'])) {
                                        foreach ($rDetails['tests'] as $tName) {
                                            $test = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                            if ($test) {
                                                $tKey = 'lab_' . $test->id;
                                                if (isset($seenItemKeys[$tKey])) continue;
                                                $seenItemKeys[$tKey] = true;
                                                $items[] = [
                                                    'id' => $test->id,
                                                    'name' => $test->name,
                                                    'code' => $test->code,
                                                    'type' => 'lab',
                                                    'request_id' => $reqItem->id,
                                                    'base_price' => (float)$test->getRegularPrice(),
                                                    'moi_price' => (float)($test->moi_price > 0 ? $test->moi_price : 0),
                                                    'hi_price' => (float)($test->hi_price > 0 ? $test->hi_price : 0),
                                                    'is_moi_active' => (bool)($test->is_moi_active ?? true),
                                                    'is_hi_active' => (bool)($test->is_hi_active ?? true),
                                                ];
                                            }
                                        }
                                    }
                                }
                            } elseif ($reqItem->type === 'radiology') {
                                $typeIds = $rDetails['radiology_type_ids'] ?? $rDetails['radiology_types'] ?? $rDetails['radiology_type_id'] ?? $rDetails['ultrasound_type_id'] ?? [];
                                if (!is_array($typeIds)) {
                                    $typeIds = [$typeIds];
                                }
                                $radTargets = $rDetails['radiology_targets'] ?? [];
                                if (!empty($typeIds)) {
                                    foreach ($typeIds as $rId) {
                                        $targetPart = isset($radTargets[$rId]) && !empty($radTargets[$rId]) ? trim($radTargets[$rId]) : null;
                                        $rKey = 'rad_' . $rId . ($targetPart ? '_' . $targetPart : '');
                                        if (isset($seenItemKeys[$rKey])) continue;
                                        $seenItemKeys[$rKey] = true;
                                        $rad = \App\Models\RadiologyType::find($rId);
                                        if ($rad) {
                                            $items[] = [
                                                'id' => $rad->id,
                                                'name' => $rad->name . ($targetPart ? " [العضو: {$targetPart}]" : ""),
                                                'code' => $rad->code,
                                                'type' => 'radiology',
                                                'request_id' => $reqItem->id,
                                                'base_price' => (float)$rad->getRegularPrice(),
                                                'moi_price' => (float)($rad->moi_price > 0 ? $rad->moi_price : 0),
                                                'hi_price' => (float)($rad->hi_price > 0 ? $rad->hi_price : 0),
                                                'is_moi_active' => (bool)($rad->is_moi_active ?? true),
                                                'is_hi_active' => (bool)($rad->is_hi_active ?? true),
                                            ];
                                        }
                                    }
                                }
                            }
                        }

                        $initialApproved = 0;
                        $initialPatient = 0;
                        $initialInsurance = 0;

                        if (count($items) > 0) {
                            foreach ($items as $itm) {
                                $rowApp = $itm['base_price'];
                                $rowCov = false;
                                if ($defaultInsurance === 'moi' && $itm['is_moi_active']) {
                                    $rowApp = $itm['moi_price'];
                                    $rowCov = true;
                                } elseif ($defaultInsurance === 'hi' && $itm['is_hi_active']) {
                                    $rowApp = $itm['hi_price'];
                                    $rowCov = true;
                                }
                                $rowPat = $rowCov ? round($rowApp * ($defaultCopay / 100)) : $rowApp;
                                $rowIns = $rowCov ? max(0, $rowApp - $rowPat) : 0;

                                $initialApproved += $rowApp;
                                $initialPatient += $rowPat;
                                $initialInsurance += $rowIns;
                            }
                        } else {
                            $initialApproved = (float)($request->total_amount ?? 0);
                            $initialPatient = $initialApproved;
                            $initialInsurance = 0;
                        }
                    @endphp

                    @if(count($allRequests) > 1)
                        <div class="alert alert-info py-2 px-3 mb-3 d-flex align-items-center rounded-3 border-info shadow-sm">
                            <i class="fas fa-layer-group fa-lg me-3 text-primary"></i>
                            <div>
                                <strong class="d-block mb-1">فاتورة موحدة شاملة لخدمات الزيارة:</strong>
                                <span class="small">
                                    تشمل هذه الفاتورة طلبات الزيارة غير المسددة معاً (
                                    @foreach($allRequests as $ar)
                                        <span class="badge {{ $ar->type === 'lab' ? 'bg-primary' : 'bg-info text-dark' }} me-1">
                                            {{ $ar->type === 'lab' ? '🔬 مختبر' : '🩻 أشعة' }} #{{ $ar->id }}
                                        </span>
                                    @endforeach
                                    ) ليتم تحصيلها معاً وإصدار <strong>وصل دفع حراري واحد شامل</strong> للمريض.
                                </span>
                            </div>
                        </div>
                    @endif

                    <!-- معلومات الطلب والمريض -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <h6 class="text-primary fw-bold mb-3">
                                    <i class="fas fa-file-medical me-2"></i>
                                    تفاصيل الطلب
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">رقم الطلب:</span>
                                    <div>
                                        @if(count($allRequests) > 1)
                                            @foreach($allRequests as $ar)
                                                <span class="badge bg-dark me-1">#{{ $ar->id }}</span>
                                            @endforeach
                                        @else
                                            <strong>#{{ $request->id }}</strong>
                                        @endif
                                    </div>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">النوع:</span>
                                    <div>
                                        @if(count($allRequests) > 1)
                                            <span class="badge bg-purple text-white" style="background-color: #6f42c1;">
                                                <i class="fas fa-layer-group me-1"></i> فاتورة موحدة للزيارة
                                            </span>
                                        @else
                                            @if($request->type === 'lab')
                                                <span class="badge bg-primary">تحاليل مختبرية</span>
                                            @elseif($request->type === 'radiology')
                                                <span class="badge bg-info text-dark">فحوصات أشعة / سونار</span>
                                            @elseif($request->type === 'pharmacy')
                                                <span class="badge bg-success">صيدلية</span>
                                            @elseif($request->type === 'emergency')
                                                <span class="badge bg-danger">طوارئ</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $request->type }}</span>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">التاريخ:</span>
                                    <span>{{ $request->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <h6 class="text-success fw-bold mb-3">
                                    <i class="fas fa-user me-2"></i>
                                    معلومات المريض
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">الاسم:</span>
                                    <strong>{{ optional($request->visit->patient)->user->name ?? 'غير محدد' }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">رقم الهوية:</span>
                                    <span>{{ optional($request->visit->patient)->national_id ?? 'غير محدد' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">رقم الهاتف:</span>
                                    <span>{{ optional($request->visit->patient)->user->phone ?? 'غير محدد' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('cashier.request.payment.process', $request->id) }}" method="POST" id="requestPaymentForm">
                        @csrf
                        @method('POST')

                        @foreach($allRequests as $ar)
                            <input type="hidden" name="included_request_ids[]" value="{{ $ar->id }}">
                        @endforeach

                        <!-- قسم الضمان الصحي ونسبة التحمل التلقائية -->
                        <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    تغطية الضمان ونسبة التحمل (Co-payment)
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $defaultInsurance !== 'none' ? 'bg-success' : 'bg-secondary' }}" id="copayBadge">
                                        {{ $defaultInsurance === 'hi' ? 'مشمول بالضمان الصحي الوطني' : ($defaultInsurance === 'moi' ? 'مشمول بضمان الداخلية' : 'دفع نقدي كامل (100%)') }}
                                    </span>
                                    @if($defaultInsurance !== 'none')
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2 rounded-pill" id="toggle_insurance_edit_btn" onclick="toggleInsuranceEdit()">
                                            <i class="fas fa-lock me-1" id="lock_icon"></i><span id="lock_text">تثبيت آلي من الحجز</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- جهة الضمان (مقفل آلياً للمرضى المحجوزين على الضمان) -->
                                <div class="col-md-6" id="insurance_type_wrapper">
                                    <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين *</label>
                                    @if($defaultInsurance !== 'none')
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between" id="locked_insurance_display">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                {{ $defaultInsurance === 'hi' ? 'هيئة الضمان الصحي الوطني (HI)' : 'ضمان قوى الأمن الداخلي (وزارة الداخلية)' }}
                                            </span>
                                        </div>
                                    @endif
                                    <select class="form-select {{ $defaultInsurance !== 'none' ? 'd-none' : '' }}" id="insurance_type" name="insurance_type">
                                        <option value="none" {{ (old('insurance_type', $defaultInsurance) == 'none') ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل 100%)</option>
                                        <option value="moi" {{ (old('insurance_type', $defaultInsurance) == 'moi') ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                        <option value="hi" {{ (old('insurance_type', $defaultInsurance) == 'hi') ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
                                    </select>
                                </div>

                                <!-- رقم بطاقة الضمان -->
                                <div class="col-md-6" id="insurance_card_col" style="{{ $defaultInsurance === 'none' ? 'display: none;' : '' }}">
                                    <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                    <input type="text" class="form-control" id="insurance_card_no" name="insurance_card_no" 
                                           value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر">
                                </div>

                                @if($hiCategory)
                                <div class="col-12" id="patient_hi_category_badge">
                                    <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} (الفئة {{ $hiCategory->code }})</span>
                                            <small class="text-muted">نسبة استقطاع هذه الخدمة ({{ $request->type }}): <strong class="text-primary fs-6">{{ (float)$hiCategory->getCopayForService($request->type) }}%</strong></small>
                                        </div>
                                        @if($hiCategory->requires_thermal_stamp)
                                            <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                        @endif
                                    </div>
                                </div>
                                @endif

                                <!-- نسبة التحمل (تلقائية ومقفلة) -->
                                <div class="col-12" id="copay_section" style="{{ $defaultInsurance === 'none' ? 'display: none;' : '' }}">
                                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <div>
                                            <label class="form-label fw-bold small text-muted mb-0">
                                                نسبة التحمل المعتمدة على المريض (Co-payment):
                                            </label>
                                            <span class="badge bg-light text-primary border fs-6 ms-2" id="display_copay_badge">
                                                {{ (float)$defaultCopay }}%
                                            </span>
                                        </div>
                                        <div class="input-group" id="manual_copay_input_group" style="max-width: 180px; {{ $defaultInsurance !== 'none' ? 'display: none !important;' : '' }}">
                                            <input type="number" step="1" min="0" max="100" class="form-control text-center fw-bold" 
                                                   id="copay_percentage" name="copay_percentage" value="{{ old('copay_percentage', $defaultCopay) }}">
                                            <span class="input-group-text bg-light fw-bold">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- بطاقة معاينة الحسبة المالية المباشرة -->
                            <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border">
                                        <small class="text-muted d-block">إجمالي السعر المعتمد</small>
                                        <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($initialApproved) }} د.ع</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border border-success">
                                        <small class="text-success fw-bold d-block">تحمل المريض (نقداً)</small>
                                        <strong class="text-success fs-6" id="display_patient_share">{{ number_format($initialPatient) }} د.ع</strong>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="bg-white p-2 rounded border border-primary">
                                        <small class="text-primary fw-bold d-block">حصة الضمان (ذمة)</small>
                                        <strong class="text-primary fs-6" id="display_insurance_share">{{ number_format($initialInsurance) }} د.ع</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- جدول تفاصيل الخدمات والأسعار -->
                        <div class="mb-4">
                            <h6 class="text-dark fw-bold mb-3">
                                <i class="fas fa-list-ol me-2 text-primary"></i>
                                تفاصيل الخدمات والأسعار المعتمدة
                            </h6>
                            <div class="border rounded-3 p-3 bg-white">
                                @if(count($items) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable">
                                            <thead class="table-light">
                                                <tr>
                                                    <th width="35" class="text-center">#</th>
                                                    <th>اسم الخدمة / الفحص</th>
                                                    <th width="85">الرمز</th>
                                                    <th width="115" class="text-end">السعر المعتمد</th>
                                                    <th width="115" class="text-end text-success">تحمل المريض</th>
                                                    <th width="115" class="text-end text-primary">حصة الضمان</th>
                                                    <th width="250" class="text-center bg-light">الإجراء (قرار المريض)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $idx => $item)
                                                    @php
                                                        $rowApp = $item['base_price'];
                                                        $rowCov = false;
                                                        if ($defaultInsurance === 'moi' && $item['is_moi_active']) {
                                                            $rowApp = $item['moi_price'] > 0 ? $item['moi_price'] : $item['base_price'];
                                                            $rowCov = true;
                                                        } elseif ($defaultInsurance === 'hi' && $item['is_hi_active']) {
                                                            $rowApp = $item['hi_price'] > 0 ? $item['hi_price'] : $item['base_price'];
                                                            $rowCov = true;
                                                        }
                                                        $rowPat = $rowCov ? round($rowApp * ($defaultCopay / 100)) : $rowApp;
                                                        $rowIns = $rowCov ? max(0, $rowApp - $rowPat) : 0;
                                                    @endphp
                                                    <tr class="service-item-row" 
                                                        data-request-id="{{ $item['request_id'] }}"
                                                        data-item-type="{{ $item['type'] }}"
                                                        data-item-id="{{ $item['id'] }}"
                                                        data-base-price="{{ $item['base_price'] }}"
                                                        data-moi-price="{{ $item['moi_price'] }}"
                                                        data-hi-price="{{ $item['hi_price'] }}"
                                                        data-is-moi-active="{{ $item['is_moi_active'] ? '1' : '0' }}"
                                                        data-is-hi-active="{{ $item['is_hi_active'] ? '1' : '0' }}">
                                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                        <td>
                                                            @if($item['type'] === 'package')
                                                                <i class="fas fa-box text-warning me-2"></i>
                                                            @elseif($item['type'] === 'lab')
                                                                <i class="fas fa-vial text-primary me-2"></i>
                                                            @else
                                                                <i class="fas fa-x-ray text-info me-2"></i>
                                                            @endif
                                                            <span class="fw-semibold item-name-label">{{ $item['name'] }}</span>
                                                            @if(count($allRequests) > 1)
                                                                <span class="badge {{ $item['type'] === 'radiology' ? 'bg-info text-dark' : 'bg-primary' }} ms-1" style="font-size:0.68rem;">
                                                                    {{ $item['type'] === 'radiology' ? 'أشعة' : 'مختبر' }}
                                                                </span>
                                                            @endif
                                                            <span class="row-coverage-badge badge {{ $rowCov ? 'bg-success-subtle text-success border border-success-subtle' : ($defaultInsurance !== 'none' ? 'bg-warning-subtle text-dark border border-warning' : '') }} small ms-1" style="{{ $defaultInsurance === 'none' ? 'display: none;' : '' }}">
                                                                @if($rowCov)
                                                                    <i class="fas fa-check-circle me-1"></i>مشمول بالضمان
                                                                @elseif($defaultInsurance !== 'none')
                                                                    <i class="fas fa-coins me-1"></i>غير مشمول (كاش 100%)
                                                                @endif
                                                            </span>
                                                        </td>
                                                        <td><code>{{ $item['code'] }}</code></td>
                                                        <td class="text-end fw-bold row-approved-price">{{ number_format($rowApp) }} د.ع</td>
                                                        <td class="text-end text-success fw-bold row-patient-share">{{ number_format($rowPat) }} د.ع</td>
                                                        <td class="text-end text-primary fw-bold row-insurance-share">{{ number_format($rowIns) }} د.ع</td>
                                                        <td class="text-center p-1" style="min-width: 220px;">
                                                            <div class="segmented-control-pills item-action-group">
                                                                <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                       name="item_actions[{{ $item['request_id'] }}][{{ $item['type'] }}][{{ $item['id'] }}]" 
                                                                       id="act_pay_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" 
                                                                       value="pay" checked autocomplete="off"
                                                                       onchange="handleItemActionChange(this)">
                                                                <label class="segmented-pill-label pill-pay" for="act_pay_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" title="تسديد ودفع الرسوم الآن">
                                                                    <i class="fas fa-check"></i>
                                                                    <span>تسديد</span>
                                                                </label>

                                                                <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                       name="item_actions[{{ $item['request_id'] }}][{{ $item['type'] }}][{{ $item['id'] }}]" 
                                                                       id="act_postpone_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" 
                                                                       value="postpone" autocomplete="off"
                                                                       onchange="handleItemActionChange(this)">
                                                                <label class="segmented-pill-label pill-postpone" for="act_postpone_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" title="تأجيل الفحص لزيارة لاحقة">
                                                                    <i class="fas fa-clock"></i>
                                                                    <span>تأجيل</span>
                                                                </label>

                                                                <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                       name="item_actions[{{ $item['request_id'] }}][{{ $item['type'] }}][{{ $item['id'] }}]" 
                                                                       id="act_cancel_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" 
                                                                       value="cancel" autocomplete="off"
                                                                       onchange="handleItemActionChange(this)">
                                                                <label class="segmented-pill-label pill-cancel" for="act_cancel_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}" title="إلغاء نهائي بطلب المريض">
                                                                    <i class="fas fa-times"></i>
                                                                    <span>إلغاء</span>
                                                                </label>
                                                            </div>

                                                            <div class="item-reason-container mt-1.5 d-none" id="reason_box_{{ $item['request_id'] }}_{{ $item['type'] }}_{{ $item['id'] }}">
                                                                <select class="form-select form-select-sm py-0.5 px-2 border-danger-subtle text-danger small mb-1 item-reason-select rounded-pill" 
                                                                        name="item_reasons[{{ $item['request_id'] }}][{{ $item['type'] }}][{{ $item['id'] }}]"
                                                                        style="font-size: 0.72rem; height: 28px;"
                                                                        onchange="handleReasonSelectChange(this)">
                                                                    <option value="lack_of_funds">💸 قلة السيولة / عدم توفر المبلغ</option>
                                                                    <option value="patient_refused">⛔ عدم رغبة المريض بالفحص</option>
                                                                    <option value="time_constraint">⏳ ضيق الوقت / تأجيل لزيارة لاحقة</option>
                                                                    <option value="other">✏️ سبب آخر...</option>
                                                                </select>
                                                                <input type="text" class="form-control form-control-sm py-0.5 px-2 d-none custom-reason-input rounded-pill"
                                                                       name="item_custom_reasons[{{ $item['request_id'] }}][{{ $item['type'] }}][{{ $item['id'] }}]"
                                                                       placeholder="اكتب السبب هنا..." maxlength="190"
                                                                       style="font-size: 0.72rem; height: 28px;">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-success">
                                                <tr>
                                                    <th colspan="3" class="text-end fw-bold">المجموع الإجمالي (المسدد):</th>
                                                    <th class="text-end fw-bold fs-6 text-dark" id="table_total_approved">{{ number_format($initialApproved) }} د.ع</th>
                                                    <th class="text-end fw-bold fs-6 text-success" id="table_total_patient">{{ number_format($initialPatient) }} د.ع</th>
                                                    <th class="text-end fw-bold fs-6 text-primary" id="table_total_insurance">{{ number_format($initialInsurance) }} د.ع</th>
                                                    <th class="text-center p-1" id="table_actions_summary">
                                                        <span class="badge bg-success">كل الفحوصات مسددة</span>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-secondary mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        {{ $request->description ?: 'طلب طبي مباشر' }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- طريقة الدفع والمبلغ المحصل -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-money-bill-wave me-1 text-success"></i>
                                    طريقة الدفع (للمبلغ المقبوض من المريض) *
                                </label>
                                <div class="payment-methods-group">
                                    <div class="form-check form-check-lg mb-2">
                                        <input class="form-check-input" type="radio" name="payment_method" id="request_payment_cash" 
                                               value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }} required>
                                        <label class="form-check-label fw-semibold" for="request_payment_cash">
                                            💵 نقدي (Cash)
                                        </label>
                                    </div>
                                    <div class="form-check form-check-lg">
                                        <input class="form-check-input" type="radio" name="payment_method" id="request_payment_card" 
                                               value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                        <label class="form-check-label fw-semibold" for="request_payment_card">
                                            💳 بطاقة دفع إلكتروني / ائتمان (Electronic / Card POS)
                                        </label>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="fas fa-hand-holding-usd me-1 text-success"></i>
                                        المبلغ المحصل نقداً من المريض (IQD) *
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold" style="font-size: 0.72rem;">
                                        <i class="fas fa-lock me-1"></i>محسوب آلياً ومطابق للوصل
                                    </span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" 
                                           class="form-control fw-bold text-success bg-light @error('amount') is-invalid @enderror" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0" 
                                           value="{{ old('amount', $initialPatient > 0 ? $initialPatient : ($request->total_amount ?? 0)) }}" 
                                           readonly
                                           required>
                                    <span class="input-group-text bg-success text-white fw-bold">IQD</span>
                                </div>
                                <small class="text-muted">المبلغ الفعلي المستلم في الصندوق من المريض (محسوب بدقة من جدول الخدمات لضمان تطابق الوصل).</small>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">ملاحظات</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                      id="notes" 
                                      name="notes" 
                                      rows="3" 
                                      placeholder="أي ملاحظات إضافية...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div id="insuranceErrorAlert" class="alert alert-danger d-none fw-bold small mb-3 text-center">
                            <i class="fas fa-ban me-1"></i>
                            لا يمكن التسديد! أحد الفحوصات المختارة غير مشمول بالضمان أو أن التسعيرة الخاصة به غير مدخلة.
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                                <i class="fas fa-arrow-right me-1"></i>
                                العودة
                            </a>
                            <button type="submit" id="submitPaymentBtn" class="btn btn-success btn-lg px-4">
                                <i class="fas fa-check-circle me-2"></i>
                                تأكيد الدفع وإصدار الإيصال
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function toggleInsuranceEdit() {
    const lockedDisplay = document.getElementById('locked_insurance_display');
    const selectElem = document.getElementById('insurance_type');
    const manualGroup = document.getElementById('manual_copay_input_group');
    const lockIcon = document.getElementById('lock_icon');
    const lockText = document.getElementById('lock_text');

    if (selectElem && selectElem.classList.contains('d-none')) {
        selectElem.classList.remove('d-none');
        if (lockedDisplay) lockedDisplay.classList.add('d-none');
        if (manualGroup) manualGroup.style.display = 'flex';
        if (lockIcon) lockIcon.className = 'fas fa-unlock text-warning me-1';
        if (lockText) lockText.textContent = 'تعديل استثنائي مفتوح';
    } else if (selectElem) {
        selectElem.classList.add('d-none');
        if (lockedDisplay) lockedDisplay.classList.remove('d-none');
        if (manualGroup) manualGroup.style.display = 'none';
        if (lockIcon) lockIcon.className = 'fas fa-lock me-1';
        if (lockText) lockText.textContent = 'تثبيت آلي من الحجز';
    }
}

window.handleItemActionChange = function(radioElem) {
    const row = radioElem.closest('.service-item-row');
    if (!row) return;
    const reqId = row.getAttribute('data-request-id');
    const itemType = row.getAttribute('data-item-type');
    const itemId = row.getAttribute('data-item-id');
    const reasonBox = document.getElementById('reason_box_' + reqId + '_' + itemType + '_' + itemId);

    if (reasonBox) {
        if (radioElem.value === 'cancel' || radioElem.value === 'postpone') {
            reasonBox.classList.remove('d-none');
            const select = reasonBox.querySelector('.item-reason-select');
            if (select && radioElem.value === 'postpone') {
                select.value = 'time_constraint';
            } else if (select && radioElem.value === 'cancel') {
                select.value = 'lack_of_funds';
            }
        } else {
            reasonBox.classList.add('d-none');
        }
    }
    if (typeof window.recalculateServices === 'function') {
        window.recalculateServices();
    }
};

window.handleReasonSelectChange = function(selectElem) {
    const customInput = selectElem.parentElement.querySelector('.custom-reason-input');
    if (customInput) {
        if (selectElem.value === 'other') {
            customInput.classList.remove('d-none');
            customInput.focus();
        } else {
            customInput.classList.add('d-none');
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const insuranceTypeSelect = document.getElementById('insurance_type');
    const copayInput = document.getElementById('copay_percentage');
    const amountInput = document.getElementById('amount');
    const displayApproved = document.getElementById('display_approved_price');
    const displayPatientShare = document.getElementById('display_patient_share');
    const displayInsuranceShare = document.getElementById('display_insurance_share');
    const displayCopayBadge = document.getElementById('display_copay_badge');
    const insuranceCardCol = document.getElementById('insurance_card_col');
    const copaySection = document.getElementById('copay_section');
    const copayBadge = document.getElementById('copayBadge');
    const hiBadge = document.getElementById('patient_hi_category_badge');
    const rows = document.querySelectorAll('.service-item-row');
    const submitBtn = document.getElementById('submitPaymentBtn');
    const actionsSummary = document.getElementById('table_actions_summary');

    function formatNumber(num) {
        return Math.round(num).toLocaleString('en-US') + ' د.ع';
    }

    function recalculate() {
        const insType = insuranceTypeSelect ? insuranceTypeSelect.value : 'none';
        let copayPct = parseFloat(copayInput ? copayInput.value : 100);
        if (isNaN(copayPct) || copayPct < 0) copayPct = 0;
        if (copayPct > 100) copayPct = 100;

        if (insType === 'moi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-primary';
                copayBadge.textContent = 'ضمان قوى الأمن الداخلي';
            }
        } else if (insType === 'hi') {
            if (insuranceCardCol) insuranceCardCol.style.display = 'block';
            if (copaySection) copaySection.style.display = 'block';
            if (copayBadge) {
                copayBadge.className = 'badge bg-success';
                copayBadge.textContent = 'هيئة الضمان الصحي الوطني';
            }
        } else {
            // none
            copayPct = 100;
            if (insuranceCardCol) insuranceCardCol.style.display = 'none';
            if (copaySection) copaySection.style.display = 'none';
            if (copayBadge) {
                copayBadge.className = 'badge bg-secondary';
                copayBadge.textContent = 'دفع نقدي كامل (100%)';
            }
        }

        let totalApproved = 0;
        let totalPatient = 0;
        let totalInsurance = 0;
        let hasError = false;
        let hasUncovered = false;
        let countPay = 0;
        let countPostpone = 0;
        let countCancel = 0;

        rows.forEach(row => {
            const actRadio = row.querySelector('.item-action-radio:checked');
            const action = actRadio ? actRadio.value : 'pay';

            const basePrice = parseFloat(row.getAttribute('data-base-price')) || 0;
            const moiPrice = parseFloat(row.getAttribute('data-moi-price')) || 0;
            const hiPrice = parseFloat(row.getAttribute('data-hi-price')) || 0;
            const isMoiActive = row.getAttribute('data-is-moi-active') === '1';
            const isHiActive = row.getAttribute('data-is-hi-active') === '1';

            let rowApproved = basePrice;
            let rowCovered = false;

            if (insType === 'moi') {
                if (isMoiActive && moiPrice > 0) {
                    rowApproved = moiPrice;
                    rowCovered = true;
                } else {
                    if (action === 'pay') hasUncovered = true;
                }
            } else if (insType === 'hi') {
                if (isHiActive && hiPrice > 0) {
                    rowApproved = hiPrice;
                    rowCovered = true;
                } else {
                    if (action === 'pay') hasUncovered = true;
                }
            }

            let rowPatient = rowApproved;
            let rowInsurance = 0;

            if (rowCovered) {
                rowPatient = Math.round(rowApproved * (copayPct / 100));
                rowInsurance = Math.max(0, rowApproved - rowPatient);
            }

            const nameLabel = row.querySelector('.item-name-label');

            if (action === 'cancel') {
                countCancel++;
                row.classList.add('table-danger');
                row.classList.remove('table-warning');
                row.style.opacity = '0.7';
                if (nameLabel) nameLabel.style.textDecoration = 'line-through';
            } else if (action === 'postpone') {
                countPostpone++;
                row.classList.add('table-warning');
                row.classList.remove('table-danger');
                row.style.opacity = '0.75';
                if (nameLabel) nameLabel.style.textDecoration = 'none';
            } else {
                countPay++;
                row.classList.remove('table-danger', 'table-warning');
                row.style.opacity = '1';
                if (nameLabel) nameLabel.style.textDecoration = 'none';

                totalApproved += rowApproved;
                totalPatient += rowPatient;
                totalInsurance += rowInsurance;
            }

            const tdApproved = row.querySelector('.row-approved-price');
            const tdPatient = row.querySelector('.row-patient-share');
            const tdInsurance = row.querySelector('.row-insurance-share');
            const covBadge = row.querySelector('.row-coverage-badge');

            if (tdApproved) tdApproved.textContent = formatNumber(rowApproved);
            if (tdPatient) tdPatient.textContent = formatNumber(rowPatient);
            if (tdInsurance) tdInsurance.textContent = formatNumber(rowInsurance);

            if (covBadge) {
                if (insType === 'none') {
                    covBadge.style.display = 'none';
                } else if (rowCovered) {
                    covBadge.style.display = 'inline-block';
                    covBadge.className = 'row-coverage-badge badge bg-success-subtle text-success border border-success-subtle small ms-1';
                    covBadge.innerHTML = '<i class="fas fa-check-circle me-1"></i>مشمول بالضمان';
                } else {
                    covBadge.style.display = 'inline-block';
                    covBadge.className = 'row-coverage-badge badge bg-warning text-dark border border-warning small ms-1';
                    covBadge.innerHTML = '<i class="fas fa-coins me-1"></i>غير مشمول (كاش 100%)';
                }
            }
        });

        const alertBox = document.getElementById('insuranceErrorAlert');
        const submitBtn = document.getElementById('submitPaymentBtn');

        if (hasUncovered && insType !== 'none') {
            if (alertBox) {
                alertBox.className = 'alert alert-warning fw-semibold small mb-3 text-center';
                alertBox.innerHTML = '<i class="fas fa-info-circle me-1 text-warning"></i> تنبيه للكاشير: يحتوي الطلب على فحوصات غير مشمولة بالضمان، تم احتسابها نقداً 100% على المريض تلقائياً.';
                alertBox.classList.remove('d-none');
            }
        } else {
            if (alertBox) alertBox.classList.add('d-none');
        }

        if (submitBtn) submitBtn.disabled = false;

        if (displayApproved) displayApproved.textContent = formatNumber(totalApproved);
        if (displayPatientShare) displayPatientShare.textContent = formatNumber(totalPatient);
        if (displayInsuranceShare) displayInsuranceShare.textContent = formatNumber(totalInsurance);
        if (displayCopayBadge) displayCopayBadge.textContent = copayPct + '%';

        const tableTotalApproved = document.getElementById('table_total_approved');
        const tableTotalPatient = document.getElementById('table_total_patient');
        const tableTotalInsurance = document.getElementById('table_total_insurance');

        if (tableTotalApproved) tableTotalApproved.textContent = formatNumber(totalApproved);
        if (tableTotalPatient) tableTotalPatient.textContent = formatNumber(totalPatient);
        if (tableTotalInsurance) tableTotalInsurance.textContent = formatNumber(totalInsurance);

        if (actionsSummary) {
            if (countPay > 0 && countCancel === 0 && countPostpone === 0) {
                actionsSummary.innerHTML = '<span class="badge bg-success">تسديد الكل (' + countPay + ')</span>';
            } else {
                let sumHtml = '';
                if (countPay > 0) sumHtml += '<span class="badge bg-success me-1">' + countPay + ' تسديد</span>';
                if (countPostpone > 0) sumHtml += '<span class="badge bg-warning text-dark me-1">' + countPostpone + ' تأجيل</span>';
                if (countCancel > 0) sumHtml += '<span class="badge bg-danger">' + countCancel + ' إلغاء</span>';
                actionsSummary.innerHTML = sumHtml;
            }
        }

        if (countPay === 0) {
            if (amountInput) {
                amountInput.value = 0;
                amountInput.required = false;
            }
            if (submitBtn) {
                if (countCancel > 0 && countPostpone === 0) {
                    submitBtn.className = 'btn btn-danger btn-lg px-4';
                    submitBtn.innerHTML = '<i class="fas fa-ban me-2"></i> تأكيد إلغاء كافة الفحوصات بطلب المريض';
                } else if (countPostpone > 0 && countCancel === 0) {
                    submitBtn.className = 'btn btn-warning btn-lg px-4 text-dark';
                    submitBtn.innerHTML = '<i class="fas fa-hourglass-half me-2"></i> تأكيد تأجيل كافة الفحوصات بطلب المريض';
                } else {
                    submitBtn.className = 'btn btn-secondary btn-lg px-4';
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> تأكيد تحديث الفحوصات (إلغاء وتأجيل)';
                }
            }
        } else {
            if (amountInput) {
                amountInput.value = totalPatient;
                amountInput.required = true;
            }
            if (submitBtn) {
                submitBtn.className = 'btn btn-success btn-lg px-4';
                if (countCancel > 0 || countPostpone > 0) {
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> تأكيد دفع الفحوصات المختارة (' + formatNumber(totalPatient) + ')';
                } else {
                    submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> تأكيد الدفع وإصدار الإيصال';
                }
            }
        }
    }

    window.recalculateServices = recalculate;

    const hiCategoryCopay = @json($patient ? $patient->getCopayPercentageFor($request->type) : 15.0);
    const moiCopay = @json((float)($patient->copay_percentage ?? 15.0));

    if (insuranceTypeSelect) {
        insuranceTypeSelect.addEventListener('change', function() {
            if (this.value === 'hi') {
                if (copayInput) copayInput.value = hiCategoryCopay;
                if (hiBadge) hiBadge.style.display = 'block';
            } else if (this.value === 'moi') {
                if (copayInput) copayInput.value = moiCopay;
                if (hiBadge) hiBadge.style.display = 'none';
            } else {
                if (hiBadge) hiBadge.style.display = 'none';
            }
            recalculate();
        });
    }

    if (copayInput) {
        copayInput.addEventListener('input', recalculate);
    }

    // Initial calculation
    recalculate();
});
</script>
@endsection