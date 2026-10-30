@extends('layouts.app')

@section('content')
<div class="container-fluid">
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
                                <h5 class="mb-0 fw-bold">تسوية ودفع رسوم خدمات الطوارئ</h5>
                                <small class="text-white-50">رقم الإشعار المالي: #{{ $payment->id }} | حالة الطوارئ: #{{ $payment->emergency->id }}</small>
                            </div>
                        </div>
                        <a href="{{ route('cashier.index') }}" class="btn btn-sm btn-outline-light rounded-pill px-3 shadow-sm">
                            <i class="fas fa-arrow-right me-1"></i>العودة لقائمة الكاشير
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
                            $pname = $patient->user->name ?? 'غير محدد';
                            $pphone = $patient->user->phone ?? ($patient->phone ?? '---');
                            $pid = '#' . $patient->id;
                            $nationalId = $patient->national_id ?? '---';
                            $insCategoryName = $hiCategory?->name ?? 'ضمان صحي وطني';
                        } elseif ($em->emergencyPatient) {
                            $pname = $em->emergencyPatient->name;
                            $pphone = $em->emergencyPatient->phone ?? '---';
                            $pid = '(طوارئ)';
                            $nationalId = '---';
                            $insCategoryName = null;
                        } else {
                            $pname = 'غير محدد';
                            $pphone = '---';
                            $pid = '-';
                            $nationalId = '---';
                            $insCategoryName = null;
                        }

                        // جمع وحساب بنود الفاتورة
                        $invoiceItems = [];

                        // 1. خدمات الطوارئ التمريضية والطبية
                        $unpaidServiceIds = \DB::table('emergency_emergency_service')
                            ->where('emergency_id', $payment->emergency_id)
                            ->where(function ($q) use ($payment) {
                                $q->whereNull('payment_id')->orWhere('payment_id', $payment->id);
                            })
                            ->pluck('emergency_service_id');

                        foreach($em->services->whereIn('id', $unpaidServiceIds) as $service) {
                            $pricing = $service->calculateInsurancePricing($insuranceType, $emergencyCopay);
                            $qty = max(1, (int) ($service->pivot->quantity ?? 1));
                            $approvedPrice = (float) $pricing['approved_price'];
                            $patientShare = (float) $pricing['patient_share'];
                            $insuranceShare = (float) $pricing['insurance_share'];

                            $invoiceItems[] = [
                                'category' => 'خدمة طوارئ',
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
                                    'category' => 'تحليل مختبر',
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
                                    'category' => 'فحص أشعة',
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
                        foreach($em->appointments as $ap) {
                            if($ap->payment_status === 'pending' && $ap->status !== 'cancelled') {
                                $apFee = (float) ($ap->consultation_fee > 0 ? $ap->consultation_fee : 50000);
                                $docName = $ap->doctor?->user?->name ? ('د. ' . preg_replace('/^د[\.\s]+/u', '', $ap->doctor->user->name)) : 'الاستشاري';
                                $spec = $ap->doctor?->specialization ? ' (' . $ap->doctor->specialization . ')' : '';
                                $dept = $ap->department?->name ? ' - ' . $ap->department->name : '';
                                $invoiceItems[] = [
                                    'category' => 'استشارة طبية',
                                    'badge_class' => 'bg-purple text-white',
                                    'icon' => 'fa-user-md',
                                    'name' => 'استشارة طبية: ' . $docName . $spec . $dept,
                                    'qty' => 1,
                                    'is_covered' => false,
                                    'price' => $apFee,
                                    'total' => $apFee,
                                    'patient_share' => $apFee,
                                    'insurance_share' => 0.0,
                                ];
                            }
                        }

                        // 5. رسوم متابعة الطبيب
                        if($em->doctor_follow_up_fee > 0 && !$em->follow_up_payment_id) {
                            $fFee = (float) $em->doctor_follow_up_fee;
                            $fPatient = $isInsurance ? round($fFee * ($emergencyCopay / 100.0), 2) : $fFee;
                            $fIns = round($fFee - $fPatient, 2);
                            $invoiceItems[] = [
                                'category' => 'متابعة طبيب',
                                'badge_class' => 'bg-warning text-dark',
                                'icon' => 'fa-stethoscope',
                                'name' => 'رسوم متابعة وإشراف طبيب الطوارئ',
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

                    <!-- بطاقتان علويتان: معلومات الحالة ومعلومات المريض (مطابقة 100% لواجهة request-payment) -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-xs">
                                <h6 class="text-primary fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-truck-medical"></i>
                                    بيانات حالة الطوارئ
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">رقم الحالة:</span>
                                    <strong class="font-monospace">#{{ $em->id }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">درجة الأولوية:</span>
                                    <span class="badge bg-{{ $em->priority_color ?? 'warning' }} px-3 py-1 rounded-pill">
                                        {{ $em->priority_text ?? $em->priority }}
                                    </span>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">طريقة الوصول:</span>
                                    <span>{{ $em->arrival_mode ?? 'وصول مباشر' }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">تاريخ ووقت الدخول:</span>
                                    <span class="font-monospace text-muted small">{{ $em->admission_time ? \Carbon\Carbon::parse($em->admission_time)->format('Y-m-d H:i') : $em->created_at->format('Y-m-d H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100 shadow-xs">
                                <h6 class="text-success fw-bold mb-3 d-flex align-items-center gap-2">
                                    <i class="fas fa-user-injured"></i>
                                    معلومات المريض
                                </h6>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">الاسم:</span>
                                    <strong class="text-dark">{{ $pname }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">رقم الهوية / الملف:</span>
                                    <span class="font-monospace">{{ $nationalId !== '---' ? $nationalId : $pid }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">رقم الهاتف:</span>
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
                                        تغطية الضمان ونسبة التحمل (Co-payment)
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-success px-3 py-1 rounded-pill" id="copayBadge">
                                            {{ $insuranceType === 'hi' ? 'مشمول بالضمان الصحي الوطني' : 'مشمول بضمان قوى الأمن' }}
                                        </span>
                                        <span class="badge bg-secondary-subtle text-secondary border small py-1 px-2 rounded-pill">
                                            <i class="fas fa-lock me-1"></i>مثبتة آلياً ومحمية
                                        </span>
                                    </div>
                                </div>

                                <div class="row g-3">
                                    <!-- جهة الضمان (مقفل ومحمي) -->
                                    <div class="col-md-6" id="insurance_type_wrapper">
                                        <label class="form-label fw-bold small text-muted">جهة الضمان / التأمين</label>
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between">
                                            <span class="fw-bold text-dark">
                                                <i class="fas fa-check-circle text-success me-1"></i>
                                                {{ $insuranceType === 'hi' ? 'هيئة الضمان الصحي الوطني (HI)' : 'ضمان قوى الأمن الداخلي (وزارة الداخلية)' }}
                                            </span>
                                            <input type="hidden" name="insurance_type" id="insurance_type" value="{{ $insuranceType }}">
                                        </div>
                                    </div>

                                    <!-- رقم بطاقة الضمان -->
                                    <div class="col-md-6" id="insurance_card_col">
                                        <label class="form-label fw-bold small text-muted">رقم بطاقة / دفتر الضمان</label>
                                        <input type="text" class="form-control bg-light" id="insurance_card_no" name="insurance_card_no" 
                                               value="{{ old('insurance_card_no', $defaultCardNo) }}" placeholder="أدخل رقم الهوية أو الدفتر" readonly>
                                    </div>

                                    @if($hiCategory)
                                    <div class="col-12" id="patient_hi_category_badge">
                                        <div class="p-2 rounded bg-white border d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div>
                                                <span class="badge bg-info text-dark me-1"><i class="fas fa-layer-group me-1"></i>{{ $hiCategory->name }} (الفئة {{ $hiCategory->code }})</span>
                                                <small class="text-muted">نسبة استقطاع خدمات الطوارئ: <strong class="text-primary fs-6">{{ (float)$emergencyCopay }}%</strong></small>
                                            </div>
                                            @if($hiCategory->requires_thermal_stamp)
                                                <span class="badge bg-danger text-white"><i class="fas fa-stamp me-1"></i>شرط الختم الحراري (0%)</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="fas fa-check me-1"></i> فئة معتمدة رسمياً
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
                                                    نسبة التحمل المعتمدة رسمياً على المريض للطوارئ (Co-payment):
                                                </label>
                                                <span class="badge bg-primary fs-6 px-3 py-1" id="display_copay_badge">
                                                    {{ (float)$emergencyCopay }}%
                                                </span>
                                            </div>
                                            <span class="text-muted small">
                                                <i class="fas fa-shield-alt text-success me-1"></i>
                                                مقفلة ومحددة آلياً وفق فئة المريض الرسمية المعتمدة
                                            </span>
                                        </div>
                                        <input type="hidden" id="copay_percentage" name="copay_percentage" value="{{ (float)$emergencyCopay }}">
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للضمان -->
                                <div class="row g-2 mt-3 pt-3 border-top text-center" id="live_pricing_summary">
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي السعر المعتمد</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($totalInvoiceAmount) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success fw-bold d-block">تحمل المريض (نقداً)</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($dueAmount) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="bg-white p-2 rounded border border-primary">
                                            <small class="text-primary fw-bold d-block">حصة الضمان (ذمة)</small>
                                            <strong class="text-primary fs-6" id="display_insurance_share">{{ number_format($insuranceCoveredTotal) }} د.ع</strong>
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
                                        حساب الدفع النقدي والخصم الخاص (طوارئ)
                                    </h6>
                                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-1 rounded-pill">
                                        دفع نقدي كامل (خارج الضمان)
                                    </span>
                                </div>

                                <!-- صندوق الخصم المالي بكتابة ومقترحات -->
                                <div class="p-3 rounded-3 bg-white border border-danger border-opacity-25 shadow-2xs mb-3" id="discount_section">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-tags text-danger"></i>
                                            <label class="form-label fw-bold small text-dark mb-0">
                                                خصم خاص / إداري للمريض:
                                            </label>
                                            <span class="badge bg-danger fs-6 px-3 py-1" id="display_discount_badge">
                                                0%
                                            </span>
                                        </div>
                                        <span class="text-muted small">
                                            <i class="fas fa-keyboard text-secondary me-1"></i>
                                            اكتب النسبة مباشرة أو اختر من المقترحات
                                        </span>
                                    </div>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-md-3">
                                            <label class="form-label small text-muted mb-1 fw-bold">نسبة الخصم %</label>
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
                                                    <option value="0">0% (بدون خصم)</option>
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
                                                    <option value="100">100% (إعفاء كامل)</option>
                                                </datalist>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small text-muted mb-1 fw-bold">سبب الخصم أو التوجيه</label>
                                            <input type="text" class="form-control form-control-sm" id="discount_reason" name="discount_reason" 
                                                   placeholder="سبب الخصم أو الجهة المخولة (إدارة / طبيب / إنساني)..." 
                                                   list="emergency_discount_reason_suggestions">
                                            <datalist id="emergency_discount_reason_suggestions">
                                                <option value="خصم إدارة"></option>
                                                <option value="إعفاء بتوجيه الطبيب"></option>
                                                <option value="تخفيض إنساني"></option>
                                                <option value="حالة طوارئ خاصة"></option>
                                                <option value="خصم موظف / ذوي موظف"></option>
                                                <option value="موافقة المدير التنفيذي"></option>
                                            </datalist>
                                        </div>
                                        <div class="col-md-4 text-md-end pt-md-3">
                                            <span class="text-danger fw-bold small" id="display_discount_amount">
                                                قيمة الخصم: 0 د.ع
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- بطاقة معاينة الحسبة المالية المباشرة للنقدي -->
                                <div class="row g-2 pt-2 border-top text-center" id="live_pricing_summary">
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border">
                                            <small class="text-muted d-block">إجمالي خدمات الطوارئ</small>
                                            <strong class="text-dark fs-6" id="display_approved_price">{{ number_format($totalInvoiceAmount) }} د.ع</strong>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="bg-white p-2 rounded border border-success">
                                            <small class="text-success fw-bold d-block">الصافي المطلوب قبضه نقداً</small>
                                            <strong class="text-success fs-6" id="display_patient_share">{{ number_format($dueAmount) }} د.ع</strong>
                                            <div id="summary_discount_note" class="text-danger small mt-1 fw-bold d-none">
                                                <i class="fas fa-tag me-1"></i>خصم: <span id="summary_discount_val">0 د.ع</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- جدول تفاصيل الخدمات والأسعار المعتمدة -->
                        <div class="mb-4">
                            <h6 class="text-dark fw-bold mb-3 d-flex align-items-center gap-2">
                                <i class="fas fa-list-ol text-primary"></i>
                                تفاصيل الخدمات والبنود المشمولة بالفاتورة
                            </h6>
                            <div class="border rounded-3 p-3 bg-white shadow-xs">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover align-middle mb-0" id="itemsTable">
                                        <thead class="table-light text-secondary small">
                                            <tr>
                                                <th width="35" class="text-center">#</th>
                                                <th width="120">النوع</th>
                                                <th>اسم الخدمة / البند</th>
                                                <th width="65" class="text-center">الكمية</th>
                                                <th width="120" class="text-end">السعر المعتمد</th>
                                                <th width="120" class="text-end text-success">تحمل المريض</th>
                                                <th width="120" class="text-end text-primary">حصة الضمان</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($invoiceItems as $idx => $item)
                                                <tr>
                                                    <td class="text-center text-muted font-monospace small">{{ $idx + 1 }}</td>
                                                    <td>
                                                        <span class="badge {{ $item['badge_class'] }} font-monospace" style="font-size: 0.72rem;">
                                                            <i class="fas {{ $item['icon'] }} me-1"></i> {{ $item['category'] }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="fw-semibold text-dark">{{ $item['name'] }}</span>
                                                        @if($item['is_covered'])
                                                            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 ms-1" style="font-size: 0.7rem;">
                                                                <i class="fas fa-shield-alt me-1"></i> ضمان صحي
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="text-center font-monospace">{{ $item['qty'] }}</td>
                                                    <td class="text-end font-monospace">{{ number_format($item['price'], 0) }} د.ع</td>
                                                    <td class="text-end font-monospace fw-bold text-success">{{ number_format($item['patient_share'], 0) }} د.ع</td>
                                                    <td class="text-end font-monospace fw-bold text-primary">{{ number_format($item['insurance_share'], 0) }} د.ع</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center py-4 text-muted">
                                                        لا توجد بنود أو خدمات معلقة لهذه الحالة
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="table-success">
                                            <tr>
                                                <th colspan="4" class="text-end fw-bold">المجموع الإجمالي:</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-dark">{{ number_format($totalInvoiceAmount, 0) }} د.ع</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-success">{{ number_format($dueAmount, 0) }} د.ع</th>
                                                <th class="text-end fw-bold font-monospace fs-6 text-primary">{{ number_format($insuranceCoveredTotal, 0) }} د.ع</th>
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
                                    طريقة الدفع والتحصيل *
                                </label>
                                <div class="border rounded-3 p-3 bg-white h-100 d-flex flex-column justify-content-center">
                                    @if($dueAmount <= 0 && $isInsurance)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_insurance" value="insurance" checked>
                                            <label class="form-check-label fw-bold text-success" for="payment_insurance">
                                                <i class="fas fa-shield-alt me-1"></i> تأمين / ضمان صحي (مغطى 100% - صفر د.ع)
                                            </label>
                                        </div>
                                    @else
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="payment_cash">
                                                <i class="fas fa-money-bill-wave text-success me-1"></i> نقدي (Cash IQD)
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card" {{ old('payment_method') == 'card' ? 'checked' : '' }}>
                                            <label class="form-check-label fw-semibold" for="payment_card">
                                                <i class="fas fa-credit-card text-primary me-1"></i> بطاقة دفع إلكتروني (Electronic POS)
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
                                        المبلغ المطلوب قبضه نقداً من المريض (IQD) *
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
                                           value="{{ old('amount', $dueAmount) }}" 
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

                        <!-- ملاحظات إضافية -->
                        <div class="mb-4">
                            <label for="notes" class="form-label small text-muted">ملاحظات السند المالي (اختياري)</label>
                            <input type="text" class="form-control" id="notes" name="notes" 
                                   value="{{ old('notes') }}" placeholder="أي تفاصيل أو ملاحظات حول القبض أو الإعفاء...">
                        </div>

                        <!-- أزرار الإجراءات السفلية -->
                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary px-4 rounded-pill">
                                <i class="fas fa-times me-1"></i>إلغاء والعودة
                            </a>
                            <button type="submit" id="submitPaymentBtn" class="btn btn-success btn-lg px-5 fw-bold rounded-pill shadow">
                                <i class="fas fa-check-circle me-2"></i>
                                {{ $dueAmount <= 0 && $isInsurance ? 'اعتماد وتسديد بالضمان (0 د.ع)' : 'تأكيد القبض وإصدار الوصل (' . number_format($dueAmount, 0) . ' د.ع)' }}
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
        return Math.round(num).toLocaleString('en-US') + ' د.ع';
    }

    function recalculateEmergencyDiscount() {
        if (isInsurance) return;
        let discountPct = parseFloat(discountInput ? discountInput.value : 0) || 0;
        if (discountPct < 0) discountPct = 0;
        if (discountPct > 100) discountPct = 100;
        if (discountInput && discountInput.value !== '' && (parseFloat(discountInput.value) > 100 || parseFloat(discountInput.value) < 0)) {
            discountInput.value = discountPct;
        }

        let discountAmount = 0;
        let netAmount = baseDueAmount;

        if (discountPct > 0) {
            discountAmount = Math.round(baseDueAmount * (discountPct / 100));
            netAmount = Math.max(0, baseDueAmount - discountAmount);
        }

        if (displayDiscountBadge) displayDiscountBadge.textContent = discountPct + '%';
        if (displayDiscountAmount) displayDiscountAmount.textContent = 'قيمة الخصم: ' + formatNumber(discountAmount);
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
            submitBtn.innerHTML = '<i class="fas fa-check-circle me-2"></i> تأكيد القبض وإصدار الوصل (' + formatNumber(netAmount) + ')';
        }
    }

    if (discountInput) {
        discountInput.addEventListener('input', recalculateEmergencyDiscount);
        discountInput.addEventListener('change', recalculateEmergencyDiscount);
    }
});
</script>