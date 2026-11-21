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
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <!-- رأس الصفحة بتدرج أزرق ملكي متطابق مع واجهات الكاشير -->
                <div class="card-header bg-gradient-primary text-white p-3" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px;">
                                <i class="fas fa-file-invoice-dollar fs-5"></i>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">{{ __('تسوية ودفع رسوم خدمات الطوارئ') }}</h5>
                                <small class="text-white-50">{{ __('رقم الإشعار المالي:') }} #{{ $payment->id }} | حالة الطوارئ: #{{ $payment->emergency->id }}</small>
                            </div>
                        </div>
                        <a href="{{ route('cashier.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 shadow-sm">
                            <i class="fas fa-arrow-right me-1"></i>{{ __('العودة لقائمة الكاشير') }}
                        </a>
                    </div>
                </div>

                <div class="card-body p-4">
                    @php
                        $em = $payment->emergency;
                        $patient = $em->patient;
                        
                        // فحص حالة الضمان بدقة
                        $isInsurance = false;
                        if (isset($em->is_insured)) {
                            $isInsurance = (bool) $em->is_insured;
                        } elseif (isset($em->insurance_type) && $em->insurance_type !== 'none') {
                            $isInsurance = true;
                        } elseif ($patient && $patient->insurance_type !== 'none' && !empty($patient->insurance_type)) {
                            $isInsurance = true;
                        }

                        if ($payment->insurance_type === 'none' || !$isInsurance) {
                            $isInsurance = false;
                        } elseif ($payment->payment_method === 'insurance' || ($payment->insurance_share > 0)) {
                            $isInsurance = true;
                        }

                        $insuranceType = $isInsurance ? ($payment->insurance_type ?: ($em->insurance_type ?: 'hi')) : 'none';
                        $hiCategory = $patient?->healthInsuranceCategory;
                        $emergencyCopay = $isInsurance && $patient ? (float)$patient->getCopayPercentageFor('emergency') : 100.0;
                        $labCopay = $isInsurance && $patient ? (float)$patient->getCopayPercentageFor('lab') : 100.0;
                        $radCopay = $isInsurance && $patient ? (float)$patient->getCopayPercentageFor('radiology') : 100.0;
                        $defaultCardNo = ($insuranceType !== 'none' && $patient) ? ($patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '') : '';

                        // بيانات المريض
                        if ($patient) {
                            $pname = $patient->user->name ?? __('غير محدد');
                            $pphone = $patient->user->phone ?? ($patient->phone ?? '---');
                            $pid = '#' . $patient->id;
                            $nationalId = $patient->national_id ?? '---';
                            $insCategoryName = $hiCategory?->name ?? __('ضمان صحي وطني');
                        } elseif ($em->emergencyPatient) {
                            $pname = $em->emergencyPatient->name;
                            $pphone = $em->emergencyPatient->phone ?? '---';
                            $pid = '(' . __('طوارئ') . ')';
                            $nationalId = '---';
                            $insCategoryName = null;
                        } else {
                            $pname = __('غير محدد');
                            $pphone = '---';
                            $pid = '-';
                            $nationalId = '---';
                            $insCategoryName = null;
                        }

                        // جمع وحساب بنود الفاتورة
                        $invoiceItems = [];

                        // 1. خدمات الطوارئ التمريضية والطبية
                        $hasEmergencyAppointments = $em->appointments->where('status', '!=', 'cancelled')->isNotEmpty();
                        $unpaidServiceIds = \DB::table('emergency_emergency_service')
                            ->where('emergency_id', $payment->emergency_id)
                            ->where(function ($q) use ($payment) {
                                $q->whereNull('payment_id')->orWhere('payment_id', $payment->id);
                            })
                            ->pluck('emergency_service_id');

                        foreach($em->services->whereIn('id', $unpaidServiceIds) as $service) {
                            // استبعاد خدمة الاستشارة العامة إذا كان هناك موعد استشارة استشاري مرتبط بالحالة منعاً للازدواجية
                            if ($hasEmergencyAppointments && ($service->id == 115 || str_contains($service->name, 'استشارة'))) {
                                continue;
                            }
                            $pricing = $service->calculateInsurancePricing($insuranceType, $emergencyCopay);
                            $qty = max(1, (int) ($service->pivot->quantity ?? 1));
                            $approvedPrice = (float) $pricing['approved_price'];
                            $patientShare = (float) $pricing['patient_share'];
                            $insuranceShare = (float) $pricing['insurance_share'];

                            $invoiceItems[] = [
                                'key' => 'service_' . $service->id,
                                'category' => __('خدمة طوارئ'),
                                'badge_class' => 'bg-info text-dark',
                                'icon' => 'fa-medkit',
                                'name' => $service->name . ($qty > 1 ? " (×{$qty})" : ''),
                                'qty' => $qty,
                                'is_covered' => $isInsurance && $pricing['is_covered'],
                                'price' => $approvedPrice,
                                'total' => round($approvedPrice * $qty, 2),
                                'patient_share' => round($patientShare * $qty, 2),
                                'insurance_share' => round($insuranceShare * $qty, 2),
                            ];
                        }

                        // 2. التحاليل المخبرية
                        $applicableLabRequests = $em->labRequests->filter(function ($labReq) use ($payment) {
                            return is_null($labReq->payment_id) || $labReq->payment_id == $payment->id;
                        });
                        foreach($applicableLabRequests as $labReq) {
                            foreach($labReq->labTests as $test) {
                                $pricing = $test->calculateInsurancePricing($insuranceType, $labCopay);
                                $invoiceItems[] = [
                                    'key' => 'lab_' . $test->id,
                                    'category' => __('تحليل مختبر'),
                                    'badge_class' => 'bg-primary text-white',
                                    'icon' => 'fa-vial',
                                    'name' => $test->name . ($test->code ? ' (' . $test->code . ')' : ''),
                                    'qty' => 1,
                                    'is_covered' => $isInsurance && $pricing['is_covered'],
                                    'price' => (float) $pricing['approved_price'],
                                    'total' => (float) $pricing['approved_price'],
                                    'patient_share' => (float) $pricing['patient_share'],
                                    'insurance_share' => (float) $pricing['insurance_share'],
                                ];
                            }
                        }

                        // 3. الأشعة والتصوير
                        $applicableRadRequests = $em->radiologyRequests->filter(function ($radReq) use ($payment) {
                            return is_null($radReq->payment_id) || $radReq->payment_id == $payment->id;
                        });
                        foreach($applicableRadRequests as $radReq) {
                            foreach($radReq->radiologyTypes as $type) {
                                $pricing = $type->calculateInsurancePricing($insuranceType, $radCopay);
                                $invoiceItems[] = [
                                    'key' => 'radiology_' . $type->id,
                                    'category' => __('فحص أشعة'),
                                    'badge_class' => 'bg-info text-white',
                                    'icon' => 'fa-x-ray',
                                    'name' => $type->name,
                                    'qty' => 1,
                                    'is_covered' => $isInsurance && $pricing['is_covered'],
                                    'price' => (float) $pricing['approved_price'],
                                    'total' => (float) $pricing['approved_price'],
                                    'patient_share' => (float) $pricing['patient_share'],
                                    'insurance_share' => (float) $pricing['insurance_share'],
                                ];
                            }
                        }

                        // 4. استشارة الطبيب الاستشاري
                        $hasUnpricedConsultation = false;
                        $unpricedDoctorNames = [];

                        foreach($em->appointments as $ap) {
                            if($ap->payment_status === 'pending' && $ap->status !== 'cancelled') {
                                $apFee = (float) ($ap->consultation_fee ?? 0);
                                if ($apFee <= 0 && $ap->doctor && (float) $ap->doctor->direct_consultation_fee > 0) {
                                    $apFee = (float) $ap->doctor->direct_consultation_fee;
                                }
                                $isUnpriced = ($apFee <= 0);
                                if ($isUnpriced) {
                                    $hasUnpricedConsultation = true;
                                    $unpricedDoctorNames[] = $ap->doctor?->user?->name ?? __('طبيب غير محدد');
                                }
                                $docName = $ap->doctor?->user?->name ? (__('د.') . ' ' . preg_replace('/^د[\.\s]+/u', '', $ap->doctor->user->name)) : __('الاستشاري');
                                $spec = $ap->doctor?->specialization ? ' (' . $ap->doctor->specialization . ')' : '';
                                $dept = $ap->department?->name ? ' - ' . $ap->department->name : '';
                                $invoiceItems[] = [
                                    'key' => 'appointment_' . $ap->id,
                                    'category' => __('استشارة طبية'),
                                    'badge_class' => 'bg-purple text-white',
                                    'icon' => 'fa-user-md',
                                    'name' => __('استشارة طبية:') . ' ' . $docName . $spec . $dept . ($isUnpriced ? ' [' . __('غير مسعرة') . ']' : ''),
                                    'qty' => 1,
                                    'is_covered' => false,
                                    'price' => $apFee,
                                    'total' => $apFee,
                                    'patient_share' => $apFee,
                                    'insurance_share' => 0.0,
                                    'is_unpriced' => $isUnpriced,
                                ];
                            }
                        }

                        // 5. رسوم متابعة الطبيب
                        if($em->doctor_follow_up_fee > 0 && !$em->follow_up_payment_id) {
                            $fFee = (float) $em->doctor_follow_up_fee;
                            $fPatient = $isInsurance ? round($fFee * ($emergencyCopay / 100.0), 2) : $fFee;
                            $fIns = round($fFee - $fPatient, 2);
                            $invoiceItems[] = [
                                'key' => 'follow_up_' . $em->id,
                                'category' => __('متابعة طبيب'),
                                'badge_class' => 'bg-warning text-dark',
                                'icon' => 'fa-stethoscope',
                                'name' => __('رسوم متابعة وإشراف طبيب الطوارئ'),
                                'qty' => 1,
                                'is_covered' => $isInsurance,
                                'price' => $fFee,
                                'total' => $fFee,
                                'patient_share' => $fPatient,
                                'insurance_share' => $fIns,
                            ];
                        }

                        // الحسابات المالية الإجمالية الدقيقة
                        $subtotal = array_sum(array_column($invoiceItems, 'total'));
                        $insuranceCoveredTotal = array_sum(array_column($invoiceItems, 'insurance_share'));
                        $calculatedPatientShare = array_sum(array_column($invoiceItems, 'patient_share'));
                        
                        if ($insuranceCoveredTotal <= 0 && $payment->insurance_share > 0) {
                            $insuranceCoveredTotal = (float) $payment->insurance_share;
                        }
                        
                        $patientRequiredAmount = !empty($invoiceItems) ? $calculatedPatientShare : (float) $payment->amount;
                        $paidAmount = $payment->paid_at ? (float) $payment->amount : 0.0;
                        $dueAmount = max(0.0, $patientRequiredAmount - $paidAmount);
                        $totalInvoiceAmount = $subtotal > 0 ? $subtotal : (float) ($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
                    @endphp

                    <!-- بطاقتان علويتان: معلومات الحالة و{{ __('معلومات المريض') }} (مطابقة 100% لواجهة request-payment) -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-xs">
                                <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-truck-medical"></i>
                                    {{ __('بيانات حالة الطوارئ') }}
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">{{ __('رقم الحالة:') }}</span>
                                    <strong class="font-monospace">#{{ $em->id }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">{{ __('درجة الأولوية:') }}</span>
                                    <span class="badge bg-{{ $em->priority_color ?? 'warning' }} px-3 py-1 rounded-pill">
                                        {{ $em->priority_text ?? $em->priority }}
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">{{ __('طريقة الوصول:') }}</span>
                                    <span>{{ $em->arrival_mode ?? __('وصول مباشر') }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('تاريخ ووقت الدخول:') }}</span>
                                    <span class="font-monospace text-muted small">{{ $em->admission_time ? \Carbon\Carbon::parse($em->admission_time)->format('Y-m-d H:i') : $em->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-xs">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-user-injured"></i>
                                    {{ __('معلومات المريض') }}
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">{{ __('الاسم:') }}</span>
                                    <strong class="text-dark">{{ $pname }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">{{ __('رقم الهوية / الملف:') }}</span>
                                    <span class="font-monospace">{{ $nationalId !== '---' ? $nationalId : $pid }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">{{ __('رقم الهاتف:') }}</span>
                                    <span class="font-monospace">{{ $pphone }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('cashier.emergency.payment.process', $payment) }}" method="POST" id="emergencyPaymentForm">
                        @csrf
                        @method('POST')

                        @if($isInsurance)
                            <!-- قسم الضمان الصحي ونسبة التحمل التلقائية المحمية (خاص بالمرضى المؤمنين) -->
                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-shield-alt"></i>
                                        {{ __('تغطية الضمان ونسبة التحمل (Co-payment)') }}
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success px-3 py-1 rounded-pill" id="copayBadge">
                                            {{ $insuranceType === 'hi' ? __('مشمول بالضمان الصحي الوطني') : __('مشمول بضمان قوى الأمن') }}
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary border small py-1 px-2 rounded-pill">
                                            <i class="fas fa-lock me-1"></i>{{ __('مثبتة آلياً ومحمية') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- جهة الضمان (مقفل ومحمي) -->
                                    <div class="col-md-6" id="insurance_type_wrapper">
                                        <label class="form-label fw-bold small text-muted">{{ __('جهة الضمان / التأمين') }}</label>
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                {{ $insuranceType === 'hi' ? __('هيئة الضمان الصحي الوطني (HI)') : __('ضمان قوى الأمن الداخلي (وزارة الداخلية)') }}
                                            </span>
                                            <input type="hidden" name="insurance_type" id="insurance_type" value="{{ $insuranceType }}">
                                        </div>
                                    </div>

                                    <!-- رقم بطاقة الضمان -->
                                    <div class="col-md-6" id="insurance_card_col">
                                        <label class="form-label fw-bold small text-muted">{{ __('رقم بطاقة / دفتر الضمان') }}</label>
                                        <input type="text" class="form-control bg-light" id="insurance_card_no" name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="{{ __('أدخل رقم الهوية أو الدفتر') }}" readonly>
                                    </div>

                                    @if($hiCategory)
                                    <div class="col-12" id="patient_hi_category_badge">
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} ({{ __('الفئة') }} {{ $hiCategory->code }})</span>
                                                <small class="text-muted">{{ __('نسبة استقطاع خدمات الطوارئ:') }} <strong class="text-primary fs-6">{{ (float)$emergencyCopay }}%</strong></small>
                                            </div>
                                            @if($hiCategory->requires_thermal_stamp)
                                                <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>{{ __('شرط الختم الحراري (0%)') }}</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="fas fa-check me-1"></i> {{ __('فئة معتمدة رسمياً') }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    @endif

                                    <!-- نسبة التحمل (تلقائية ومقفلة ومحمية تماماً) -->
                                    <div class="col-12" id="copay_section">
                                        <div class="p-2 rounded-3 bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-percentage text-primary"></i>
                                                <label class="form-label fw-bold small text-muted mb-0">
                                                    {{ __('نسبة التحمل المعتمدة رسمياً على المريض للطوارئ (Co-payment):') }}
                                                </label>
                                                <span class="badge bg-primary fs-6 px-3 py-1" id="display_copay_badge">
                                                    {{ (float)$emergencyCopay }}%
                                                </span>
                                            </div>
                                            <span class="text-muted small">
                                                <i class="fas fa-shield-alt text-success me-1"></i>
                                                {{ __('مقفلة ومحددة آلياً وفق فئة المريض الرسمية المعتمدة') }}
                                            </span>
                                        </div>
                                        <input type="hidden" id="copay_percentage" name="copay_percentage" value="{{ (float)$emergencyCopay }}">
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للضمان -->
                                <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">{{ __('إجمالي السعر المعتمد') }}</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($totalInvoiceAmount) }} {{ __('د.ع') }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success fw-bold d-block">{{ __('تحمل المريض (نقداً)') }}</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($dueAmount) }} {{ __('د.ع') }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-primary">
                                            <small class="text-primary fw-bold d-block">{{ __('حصة الضمان (ذمة)') }}</small>
                                            <strong class="text-primary fs-6" id="display_insurance_share">{{ number_format($insuranceCoveredTotal) }} {{ __('د.ع') }}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- قسم الدفع النقدي والخصم الخاص (خارج الضمان 100%) -->
                            <input type="hidden" name="insurance_type" id="insurance_type" value="none">
                            <input type="hidden" id="copay_percentage" name="copay_percentage" value="100">

                            <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc;">
                                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                    <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-wallet text-success"></i>
                                        {{ __('حساب الدفع النقدي والخصم الخاص (طوارئ)') }}
                                    </h6>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                                        {{ __('دفع نقدي كامل (خارج الضمان)') }}
                                    </span>
                                </div>

                                <!-- صندوق الخصم المالي بكتابة ومقترحات ونطاق تطبيقه -->
                                <div class="p-3 rounded-3 bg-white border border-danger border-opacity-25 shadow-2xs mb-3" id="discount_section">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-tags text-danger"></i>
                                            <label class="form-label fw-bold small text-dark mb-0">
                                                {{ __('خصم خاص / إداري للمريض:') }}
                                            </label>
                                            <span class="badge bg-danger fs-6 px-3 py-1" id="display_discount_badge">
                                                0%
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="small fw-bold text-muted">{{ __('نوع الخصم:') }}</span>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <input type="radio" class="btn-check" name="discount_scope" id="discount_scope_overall" value="overall" checked autocomplete="off" onchange="toggleDiscountScope('overall')">
                                                <label class="btn btn-outline-danger py-0 px-2 fw-bold" for="discount_scope_overall" title="{{ __('تطبيق نسبة خصم موحدة على كامل الفاتورة') }}">
                                                    <i class="fas fa-receipt me-1"></i>{{ __('خصم إجمالي ع الوصل') }}
                                                </label>

                                                <input type="radio" class="btn-check" name="discount_scope" id="discount_scope_per_item" value="per_item" autocomplete="off" onchange="toggleDiscountScope('per_item')">
                                                <label class="btn btn-outline-danger py-0 px-2 fw-bold" for="discount_scope_per_item" title="{{ __('تحديد نسبة خصم مخصصة لكل خدمة أو فحص بشكل منفصل') }}">
                                                    <i class="fas fa-list-check me-1"></i>{{ __('خصم تفصيلي لكل خدمة') }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3" id="discount_overall_input_col">
                                            <label class="form-label small text-muted mb-1 fw-bold" id="discount_percentage_label">{{ __('نسبة الخصم %') }}</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light text-danger fw-bold">%</span>
                                                <input type="number" 
                                                       class="form-control form-control-sm fw-bold text-danger" 
                                                       id="discount_percentage" 
                                                       name="discount_percentage" 
                                                       min="0" 
                                                       max="100" 
                                                       step="5" 
                                                       value="0" 
                                                       placeholder="0" 
                                                       list="emergency_discount_percentage_suggestions">
                                                <datalist id="emergency_discount_percentage_suggestions">
                                                    <option value="0">0% ({{ __('بدون خصم') }})</option>
                                                    <option value="5">5%</option>
                                                    <option value="10">10%</option>
                                                    <option value="15">15%</option>
                                                    <option value="20">20%</option>
                                                    <option value="25">25%</option>
                                                    <option value="30">30%</option>
                                                    <option value="35">35%</option>
                                                    <option value="40">40%</option>
                                                    <option value="45">45%</option>
                                                    <option value="50">50%</option>
                                                    <option value="55">55%</option>
                                                    <option value="60">60%</option>
                                                    <option value="70">70%</option>
                                                    <option value="75">75%</option>
                                                    <option value="80">80%</option>
                                                    <option value="90">90%</option>
                                                    <option value="100">100% ({{ __('إعفاء كامل') }})</option>
                                                </datalist>
                                            </div>
                                            <small class="text-muted d-block mt-0.5" id="discount_scope_subhint" style="font-size: 0.68rem;">{{ __('اكتب النسبة أو اختر من المقترحات') }}</small>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1 fw-bold">{{ __('سبب الخصم أو التوجيه') }}</label>
                                            <input type="text" class="form-control form-control-sm" id="discount_reason" name="discount_reason" 
                                                   placeholder="{{ __('سبب الخصم أو الجهة المخولة (إدارة / طبيب / إنساني)...') }}" 
                                                   list="emergency_discount_reason_suggestions">
                                            <datalist id="emergency_discount_reason_suggestions">
                                                <option value="{{ __('خصم إدارة') }}"></option>
                                                <option value="{{ __('إعفاء بتوجيه الطبيب') }}"></option>
                                                <option value="{{ __('تخفيض إنساني') }}"></option>
                                                <option value="{{ __('حالة طوارئ خاصة') }}"></option>
                                                <option value="{{ __('خصم موظف / ذوي موظف') }}"></option>
                                                <option value="{{ __('موافقة المدير التنفيذي') }}"></option>
                                            </datalist>
                                        </div>
                                        <div class="col-md-4 text-md-end pt-md-3">
                                            <span class="text-danger fw-bold small" id="display_discount_amount">
                                                {{ __('قيمة الخصم:') }} 0 {{ __('د.ع') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للنقدي -->
                                <div class="row g-2 pt-2 border-top text-center" id="live_pricing_summary">
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">{{ __('إجمالي خدمات الطوارئ') }}</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($totalInvoiceAmount) }} {{ __('د.ع') }}</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success fw-bold d-block">{{ __('الصافي المطلوب قبضه نقداً') }}</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($dueAmount) }} {{ __('د.ع') }}</strong>
                                            <div id="summary_discount_note" class="text-danger small mt-1 fw-bold d-none">
                                                <i class="fas fa-tag me-1"></i>{{ __('خصم:') }} <span id="summary_discount_val">0 {{ __('د.ع') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- جدول تفاصيل الخدمات والأسعار المعتمدة -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                <h6 class="text-dark fw-bold mb-0 d-flex align-items-center gap-2">
                                    <i class="fas fa-list-ol text-primary"></i>
                                    {{ __('تفاصيل الخدمات والبنود المشمولة بالفاتورة') }}
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success px-2 py-1 shadow-2xs" id="summary_badge_pay"><i class="fas fa-check me-1"></i>{{ __('تسديد') }}: <span id="summary_count_pay">{{ count($invoiceItems) }}</span></span>
                                    <span class="badge bg-warning text-dark px-2 py-1 shadow-2xs" id="summary_badge_postpone"><i class="fas fa-clock me-1"></i>{{ __('تأجيل') }}: <span id="summary_count_postpone">0</span></span>
                                    <span class="badge bg-danger px-2 py-1 shadow-2xs" id="summary_badge_cancel"><i class="fas fa-times me-1"></i>{{ __('إلغاء') }}: <span id="summary_count_cancel">0</span></span>
                                </div>
                            </div>
                            <div class="border rounded-3 p-3 bg-white shadow-xs">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th width="35" class="text-center">#</th>
                                                <th width="110">{{ __('النوع') }}</th>
                                                <th>{{ __('اسم الخدمة / البند') }}</th>
                                                <th width="55" class="text-center">{{ __('الكمية') }}</th>
                                                <th width="135" class="text-center text-primary"><i class="fas fa-edit me-1"></i>{{ __('السعر المعتمد') }}</th>
                                                <th width="110" class="text-end text-success">{{ __('تحمل المريض') }}</th>
                                                <th width="115" class="text-center col-item-discount text-danger" style="display: none;"><i class="fas fa-tag me-1"></i>{{ __('خصم الخدمة %') }}</th>
                                                <th width="105" class="text-end text-primary">{{ __('حصة الضمان') }}</th>
                                                <th width="185" class="text-center"><i class="fas fa-tasks me-1 text-secondary"></i>{{ __('الإجراء') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($invoiceItems as $idx => $item)
                                                <tr class="emergency-item-row" 
                                                    data-key="{{ $item['key'] ?? ('item_'.$idx) }}"
                                                    data-base-price="{{ (float)$item['price'] }}"
                                                    data-qty="{{ (int)$item['qty'] }}"
                                                    data-base-patient="{{ (float)$item['patient_share'] }}"
                                                    data-base-insurance="{{ (float)$item['insurance_share'] }}"
                                                    data-is-covered="{{ $item['is_covered'] ? '1' : '0' }}"
                                                    data-copay="{{ $item['is_covered'] && $isInsurance ? $emergencyCopay : 100 }}"
                                                    data-is-unpriced="{{ !empty($item['is_unpriced']) ? '1' : '0' }}">
                                                    <td class="text-center text-muted font-monospace small">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <span class="badge {{ $item['badge_class'] }} font-monospace" style="font-size: 0.72rem;">
                                                            <i class="fas {{ $item['icon'] }} me-1"></i> {{ $item['category'] }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark item-name-label">{{ $item['name'] }}</span>
                                                        @if($item['is_covered'])
                                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 ms-1" style="font-size: 0.7rem;">
                                                                <i class="fas fa-shield-alt me-1"></i> {{ __('ضمان صحي') }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center font-monospace">{{ $item['qty'] }}</td>
                                                    <td class="text-center">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm text-center fw-bold text-dark item-custom-price-input" 
                                                                   name="item_custom_prices[{{ $item['key'] ?? ('item_'.$idx) }}]" 
                                                                   value="{{ (float)$item['price'] }}" 
                                                                   min="0" step="any"
                                                                   placeholder="0"
                                                                   oninput="handleItemPriceChange(this)"
                                                                   onchange="handleItemPriceChange(this)">
                                                            <span class="input-group-text bg-light text-muted px-1" style="font-size:0.7rem;">{{ __('د.ع') }}</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-success row-patient-share">{{ number_format($item['patient_share'], 0) }} {{ __('د.ع') }}</td>
                                                    <td class="text-center col-item-discount" style="display: none;">
                                                        <div class="input-group input-group-sm justify-content-center">
                                                            <input type="number" 
                                                                   class="form-control form-control-sm text-center fw-bold text-danger item-discount-input" 
                                                                   name="item_discounts[{{ $item['key'] ?? ('item_'.$idx) }}]" 
                                                                   min="0" 
                                                                   max="100" 
                                                                   step="5" 
                                                                   value="0" 
                                                                   placeholder="0" 
                                                                   style="max-width: 65px;"
                                                                   oninput="handleItemDiscountInput(this)" 
                                                                   onchange="handleItemDiscountInput(this)">
                                                            <span class="input-group-text bg-light text-danger fw-bold px-1" style="font-size:0.75rem;">%</span>
                                                        </div>
                                                        <div class="item-discount-val-badge text-danger fw-bold mt-1 small" style="font-size: 0.7rem; display: none;"></div>
                                                    </td>
                                                    <td class="text-end font-monospace fw-bold text-primary row-insurance-share">{{ number_format($item['insurance_share'], 0) }} {{ __('د.ع') }}</td>
                                                    <td class="text-center p-1" style="min-width: 180px;">
                                                        <div class="segmented-control-pills item-action-group">
                                                            <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                   name="item_actions[{{ $item['key'] ?? ('item_'.$idx) }}]" 
                                                                   id="act_pay_{{ $item['key'] ?? ('item_'.$idx) }}" 
                                                                   value="pay" checked autocomplete="off"
                                                                   onchange="handleItemActionChange(this)">
                                                            <label class="segmented-pill-label pill-pay" for="act_pay_{{ $item['key'] ?? ('item_'.$idx) }}" title="{{ __('تسديد ودفع الرسوم الآن') }}">
                                                                <i class="fas fa-check"></i>
                                                                <span>{{ __('تسديد') }}</span>
                                                            </label>

                                                            <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                   name="item_actions[{{ $item['key'] ?? ('item_'.$idx) }}]" 
                                                                   id="act_postpone_{{ $item['key'] ?? ('item_'.$idx) }}" 
                                                                   value="postpone" autocomplete="off"
                                                                   onchange="handleItemActionChange(this)">
                                                            <label class="segmented-pill-label pill-postpone" for="act_postpone_{{ $item['key'] ?? ('item_'.$idx) }}" title="{{ __('تأجيل الفحص / الخدمة لوقت لاحق') }}">
                                                                <i class="fas fa-clock"></i>
                                                                <span>{{ __('تأجيل') }}</span>
                                                            </label>

                                                            <input type="radio" class="segmented-pill-input item-action-radio" 
                                                                   name="item_actions[{{ $item['key'] ?? ('item_'.$idx) }}]" 
                                                                   id="act_cancel_{{ $item['key'] ?? ('item_'.$idx) }}" 
                                                                   value="cancel" autocomplete="off"
                                                                   onchange="handleItemActionChange(this)">
                                                            <label class="segmented-pill-label pill-cancel" for="act_cancel_{{ $item['key'] ?? ('item_'.$idx) }}" title="{{ __('إلغاء نهائي بطلب المريض') }}">
                                                                <i class="fas fa-times"></i>
                                                                <span>{{ __('إلغاء') }}</span>
                                                            </label>
                                                        </div>

                                                        <div class="item-reason-container mt-1.5 d-none" id="reason_box_{{ $item['key'] ?? ('item_'.$idx) }}">
                                                            <select class="form-select form-select-sm py-0.5 px-2 border-danger-subtle text-danger small mb-1 item-reason-select rounded-pill" 
                                                                    name="item_reasons[{{ $item['key'] ?? ('item_'.$idx) }}]"
                                                                    style="font-size: 0.72rem; height: 28px;">
                                                                <option value="patient_refused">⛔ {{ __('عدم رغبة المريض بالخدمة') }}</option>
                                                                <option value="lack_of_funds">💸 {{ __('قلة السيولة / عدم توفر المبلغ') }}</option>
                                                                <option value="time_constraint">⏳ {{ __('ضيق الوقت / تأجيل للمتابعة') }}</option>
                                                                <option value="doctor_cancelled">👨‍⚕️ {{ __('إلغاء بتوجيه الطبيب') }}</option>
                                                            </select>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center py-4 text-muted">
                                                        {{ __('لا توجد بنود أو خدمات معلقة لهذه الحالة') }}
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-success">
                                            <tr>
                                                <th colspan="4" class="text-end fw-bold">{{ __('المجموع الإجمالي (المسدد):') }}</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-dark" id="table_total_approved">{{ number_format($totalInvoiceAmount, 0) }} {{ __('د.ع') }}</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-success" id="table_total_patient">{{ number_format($dueAmount, 0) }} {{ __('د.ع') }}</th>
                                                <th class="text-center col-item-discount text-danger fw-bold fs-6" id="table_total_discount_items" style="display: none;">-0 {{ __('د.ع') }}</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-primary" id="table_total_insurance">{{ number_format($insuranceCoveredTotal, 0) }} {{ __('د.ع') }}</th>
                                                <th class="text-center p-1" id="table_actions_summary">
                                                    <span class="badge bg-success" id="table_actions_badge">{{ __('كل الخدمات مسددة') }}</span>
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- قسم طريقة الدفع وقفل المبلغ الصارم (مطابقة تامة 100% لواجهة request-payment) -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-credit-card me-1 text-primary"></i>
                                    {{ __('طريقة الدفع والتحصيل *') }}
                                </label>
                                <div class="border rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-center">
                                    @if($dueAmount <= 0 && $isInsurance)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_insurance" value="insurance" checked>
                                            <label class="form-check-label fw-bold text-success" for="payment_insurance">
                                                <i class="fas fa-shield-alt me-1"></i> {{ __('تأمين / ضمان صحي (مغطى 100% - صفر د.ع)') }}
                                            </label>
                                        </div>
                                    @else
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="payment_cash">
                                                <i class="fas fa-money-bill-wave text-success me-1"></i> {{ __('نقدي (Cash IQD)') }}
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="payment_card">
                                                <i class="fas fa-credit-card text-primary me-1"></i> {{ __('بطاقة دفع إلكتروني (Electronic POS)') }}
                                            </label>
                                        </div>
                                    @endif
                                </div>
                                @error('payment_method')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1">
                                    <span>
                                        <i class="fas fa-hand-holding-usd me-1 text-success"></i>
                                        {{ __('المبلغ المطلوب قبضه نقداً من المريض (IQD) *') }}
                                    </span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle small fw-bold" style="font-size: 0.72rem;">
                                        <i class="fas fa-lock me-1"></i>{{ __('محسوب آلياً ومطابق للوصل') }}
                                    </span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" 
                                           class="form-control fw-bold text-success bg-light @error('amount') is-invalid @enderror" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0" 
                                           value="{{ old('amount', $dueAmount) }}" 
                                           readonly
                                           required>
                                    <span class="input-group-text bg-success text-white fw-bold">IQD</span>
                                </div>
                                <small class="text-muted">{{ __('المبلغ الفعلي المستلم في الصندوق من المريض (محسوب بدقة من جدول الخدمات لضمان تطابق الوصل).') }}</small>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- ملاحظات إضافية -->
                        <div class="mb-4">
                            <label for="notes" class="form-label small text-muted">{{ __('ملاحظات السند المالي (اختياري)') }}</label>
                            <input type="text" class="form-control" id="notes" name="notes" 
                                   value="{{ old('notes') }}" placeholder="{{ __('أي تفاصيل أو ملاحظات حول القبض أو الإعفاء...') }}">
                        </div>

                        @if($hasUnpricedConsultation)
                            <div class="alert alert-danger d-flex align-items-center gap-3 p-3 rounded-3 mb-4 shadow-sm border border-danger">
                                <i class="fas fa-ban fa-2x text-danger flex-shrink-0"></i>
                                <div>
                                    <h6 class="fw-bold mb-1 text-danger">{{ __('⚠️ تنبيه: لا يمكن تسديد الفاتورة لوجود استشارة غير مسعرة!') }}</h6>
                                    <p class="mb-0 small text-dark">
                                        {{ __('الطبيب') }} (<strong>{{ implode('، ', $unpricedDoctorNames) }}</strong>) {{ __('لا يمتلك «سعر كشفية مباشرة» محدد في النظام.') }} 
                                        {{ __('يرجى تحديد سعر الكشفية المباشرة في ملف الطبيب أولاً قبل إتمام القبض.') }}
                                    </p>
                                </div>
                            </div>
                        @endif

                        <!-- أزرار الإجراءات السفلية -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="fas fa-times me-1"></i>{{ __('إلغاء والعودة') }}
                            </a>
                            <button type="submit" id="submitPaymentBtn" class="btn btn-success btn-lg px-5 fw-bold rounded-pill shadow"
                                    @if($hasUnpricedConsultation) disabled title="{{ __('لا يمكن التسديد لوجود استشارة غير مسعرة') }}" @endif>
                                <i class="fas fa-check-circle me-2"></i>
                                {{ $dueAmount <= 0 && $isInsurance ? __('اعتماد وتسديد بالضمان (0 د.ع)') : __('تأكيد القبض وإصدار الوصل') . ' (' . number_format($dueAmount, 0) . ' ' . __('د.ع') . ')' }}
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
window.toggleDiscountScope = function(scope) {
    const isPerItem = (scope === 'per_item');
    const cols = document.querySelectorAll('.col-item-discount');
    cols.forEach(col => {
        col.style.display = isPerItem ? '' : 'none';
    });

    const overallInput = document.getElementById('discount_percentage');
    const label = document.getElementById('discount_percentage_label');
    const hint = document.getElementById('discount_scope_subhint');

    if (isPerItem) {
        if (overallInput) {
            overallInput.readOnly = true;
            overallInput.classList.add('bg-light');
        }
        if (label) label.textContent = '{{ __('إجمالي الخصم %') }}';
        if (hint) hint.textContent = '{{ __('محسوب تلقائياً من مجموع خصومات الخدمات في الجدول') }}';
    } else {
        if (overallInput) {
            overallInput.readOnly = false;
            overallInput.classList.remove('bg-light');
        }
        if (label) label.textContent = '{{ __('نسبة الخصم %') }}';
        if (hint) hint.textContent = '{{ __('اكتب النسبة أو اختر من المقترحات') }}';
        document.querySelectorAll('.item-discount-input').forEach(inp => {
            inp.value = 0;
        });
        document.querySelectorAll('.item-discount-val-badge').forEach(b => {
            b.style.display = 'none';
            b.textContent = '';
        });
    }

    if (typeof window.recalculateEmergencyDiscount === 'function') {
        window.recalculateEmergencyDiscount();
    }
};

window.handleItemDiscountInput = function(inputElem) {
    let val = parseFloat(inputElem.value);
    if (isNaN(val) || val < 0) val = 0;
    if (val > 100) val = 100;
    inputElem.value = val;

    if (typeof window.recalculateEmergencyDiscount === 'function') {
        window.recalculateEmergencyDiscount();
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const isInsurance = @json($isInsurance);
    const baseDueAmount = @json((float)$dueAmount);
    const amountInput = document.getElementById('amount');
    const discountInput = document.getElementById('discount_percentage');
    const displayDiscountBadge = document.getElementById('display_discount_badge');
    const displayDiscountAmount = document.getElementById('display_discount_amount');
    const displayPatientShare = document.getElementById('display_patient_share');
    const summaryDiscountNote = document.getElementById('summary_discount_note');
    const summaryDiscountVal = document.getElementById('summary_discount_val');
    const submitBtn = document.getElementById('submitPaymentBtn');

    function formatNumber(num) {
        return Math.round(num).toLocaleString('en-US') + ' ' + '{{ __('د.ع') }}';
    }

    window.recalculateEmergencyDiscount = function() {
        if (isInsurance) return;

        const isPerItemScope = document.getElementById('discount_scope_per_item')?.checked;
        let discountPct = 0;
        let discountAmount = 0;

        if (isPerItemScope) {
            let totalItemsDiscount = 0;
            const rows = document.querySelectorAll('.emergency-item-row');
            rows.forEach(row => {
                const discInput = row.querySelector('.item-discount-input');
                const badge = row.querySelector('.item-discount-val-badge');
                const rowPatCell = row.querySelector('.row-patient-share');
                let basePat = parseFloat(row.dataset.basePatient) || 0;
                let itemDiscPct = parseFloat(discInput ? discInput.value : 0) || 0;

                if (itemDiscPct > 0) {
                    let itemDiscVal = Math.round(basePat * (itemDiscPct / 100));
                    let discountedPat = Math.max(0, basePat - itemDiscVal);
                    totalItemsDiscount += itemDiscVal;

                    if (badge) {
                        badge.textContent = '-' + formatNumber(itemDiscVal);
                        badge.style.display = 'block';
                    }
                    if (rowPatCell) {
                        rowPatCell.innerHTML = '<span class="text-muted text-decoration-line-through small me-1">' + formatNumber(basePat) + '</span><span class="text-danger fw-bold">' + formatNumber(discountedPat) + '</span>';
                    }
                } else {
                    if (badge) {
                        badge.textContent = '';
                        badge.style.display = 'none';
                    }
                    if (rowPatCell) {
                        rowPatCell.innerHTML = formatNumber(basePat);
                    }
                }
            });

            discountAmount = totalItemsDiscount;
            discountPct = baseDueAmount > 0 ? Math.round((discountAmount / baseDueAmount) * 1000) / 10 : 0;

            if (discountInput) {
                discountInput.value = discountPct;
            }

            const tableTotalDiscountItems = document.getElementById('table_total_discount_items');
            if (tableTotalDiscountItems) {
                tableTotalDiscountItems.textContent = '-' + formatNumber(discountAmount);
            }
        } else {
            discountPct = parseFloat(discountInput ? discountInput.value : 0) || 0;
            if (discountPct < 0) discountPct = 0;
            if (discountPct > 100) discountPct = 100;
            if (discountInput && discountInput.value !== '' && (parseFloat(discountInput.value) > 100 || parseFloat(discountInput.value) < 0)) {
                discountInput.value = discountPct;
            }

            if (discountPct > 0) {
                discountAmount = Math.round(baseDueAmount * (discountPct / 100));
            }

            document.querySelectorAll('.emergency-item-row').forEach(row => {
                const rowPatCell = row.querySelector('.row-patient-share');
                let basePat = parseFloat(row.dataset.basePatient) || 0;
                if (rowPatCell) {
                    rowPatCell.innerHTML = formatNumber(basePat);
                }
            });
        }

        let netAmount = Math.max(0, baseDueAmount - discountAmount);

        if (displayDiscountBadge) displayDiscountBadge.textContent = discountPct + '%';
        if (displayDiscountAmount) displayDiscountAmount.textContent = '{{ __('قيمة الخصم:') }} ' + formatNumber(discountAmount);
        if (amountInput) amountInput.value = netAmount;
        if (displayPatientShare) displayPatientShare.textContent = formatNumber(netAmount);

        if (summaryDiscountNote) {
            if (discountAmount > 0) {
                summaryDiscountNote.classList.remove('d-none');
                if (summaryDiscountVal) summaryDiscountVal.textContent = '-' + formatNumber(discountAmount);
            } else {
                summaryDiscountNote.classList.add('d-none');
            }
        }

        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> ' + '{{ __('تأكيد القبض وإصدار الوصل') }}' + ' (' + formatNumber(netAmount) + ')';
        }
    };

    if (discountInput) {
        discountInput.addEventListener('input', window.recalculateEmergencyDiscount);
        discountInput.addEventListener('change', window.recalculateEmergencyDiscount);
    }
});
</script>