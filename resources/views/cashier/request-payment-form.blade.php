@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-gradient-primary text-white" style="background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-file-invoice-dollar me-2"></i>
                            تسوية ودفع رسوم الطلب الطبي
                        </h5>
                        <a href="{{ auth()->user()->hasRole('consultation_receptionist') && !auth()->user()->hasRole('admin') ? route('consultant-availability.index') : route('cashier.index') }}" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-arrow-right me-1"></i>العودة
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    @php
                        $patient = optional($request->visit)->patient;
                        $appointment = optional($request->visit)->appointment;
                        $defaultInsurance = $request->insurance_type ?? optional($appointment)->insurance_type ?? optional($patient)->insurance_type ?? 'none';
                        $hiCategory = $patient ? $patient->healthInsuranceCategory : null;
                        if ($defaultInsurance === 'hi' && $patient) {
                            $defaultCopay = $patient->getCopayPercentageFor($request->type);
                        } else {
                            $defaultCopay = (float)($patient->copay_percentage ?? 15.0);
                        }
                        $defaultCardNo = $patient->insurance_card_no ?? $patient->insurance_booklet_number ?? '';

                        $details = is_string($request->details) ? json_decode($request->details, true) : $request->details;
                        $items = [];

                        if ($request->type === 'lab') {
                            $testIds = $details['lab_test_ids'] ?? [];
                            if (empty($testIds) && !empty($details['package_id'])) {
                                $pkg = \App\Models\Package::find($details['package_id']);
                                if ($pkg) {
                                    $testIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                                }
                            }
                            if (!empty($testIds)) {
                                foreach ($testIds as $tId) {
                                    $test = \App\Models\LabTest::find($tId);
                                    if ($test) {
                                        $items[] = [
                                            'id' => $test->id,
                                            'name' => $test->name,
                                            'code' => $test->code,
                                            'type' => 'lab',
                                            'base_price' => (float)$test->getRegularPrice(),
                                            'moi_price' => (float)($test->moi_price > 0 ? $test->moi_price : 0),
                                            'hi_price' => (float)($test->hi_price > 0 ? $test->hi_price : 0),
                                            'is_moi_active' => (bool)($test->is_moi_active ?? true),
                                            'is_hi_active' => (bool)($test->is_hi_active ?? true),
                                        ];
                                    }
                                }
                            } elseif (!empty($details['tests'])) {
                                foreach ($details['tests'] as $tName) {
                                    $test = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                                    if ($test) {
                                        $items[] = [
                                            'id' => $test->id,
                                            'name' => $test->name,
                                            'code' => $test->code,
                                            'type' => 'lab',
                                            'base_price' => (float)$test->getRegularPrice(),
                                            'moi_price' => (float)($test->moi_price > 0 ? $test->moi_price : 0),
                                            'hi_price' => (float)($test->hi_price > 0 ? $test->hi_price : 0),
                                            'is_moi_active' => (bool)($test->is_moi_active ?? true),
                                            'is_hi_active' => (bool)($test->is_hi_active ?? true),
                                        ];
                                    }
                                }
                            }
                        } elseif ($request->type === 'radiology') {
                            $typeIds = $details['radiology_type_ids'] ?? $details['radiology_types'] ?? $details['radiology_type_id'] ?? $details['ultrasound_type_id'] ?? [];
                            if (!is_array($typeIds)) {
                                $typeIds = [$typeIds];
                            }
                            if (!empty($typeIds)) {
                                foreach ($typeIds as $rId) {
                                    $rad = \App\Models\RadiologyType::find($rId);
                                    if ($rad) {
                                        $items[] = [
                                            'id' => $rad->id,
                                            'name' => $rad->name,
                                            'code' => $rad->code,
                                            'type' => 'radiology',
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
                                    <strong>#{{ $request->id }}</strong>
                                </div>
                                <div class="mb-2 d-flex justify-content-between">
                                    <span class="text-muted">النوع:</span>
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
                                        <option value="none" {{ old('insurance_type', $defaultInsurance) == 'none' ? 'selected' : '' }}>بدون ضمان (دفع نقدي كامل 100%)</option>
                                        <option value="moi" {{ old('insurance_type', $defaultInsurance) == 'moi' ? 'selected' : '' }}>ضمان قوى الأمن الداخلي (وزارة الداخلية)</option>
                                        <option value="hi" {{ old('insurance_type', $defaultInsurance) == 'hi' ? 'selected' : '' }}>هيئة الضمان الصحي الوطني</option>
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
                                                    <th width="40" class="text-center">#</th>
                                                    <th>اسم الخدمة / الفحص</th>
                                                    <th width="100">الرمز</th>
                                                    <th width="130" class="text-end">السعر المعتمد</th>
                                                    <th width="130" class="text-end text-success">تحمل المريض</th>
                                                    <th width="130" class="text-end text-primary">حصة الضمان</th>
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
                                                        data-base-price="{{ $item['base_price'] }}"
                                                        data-moi-price="{{ $item['moi_price'] }}"
                                                        data-hi-price="{{ $item['hi_price'] }}"
                                                        data-is-moi-active="{{ $item['is_moi_active'] ? '1' : '0' }}"
                                                        data-is-hi-active="{{ $item['is_hi_active'] ? '1' : '0' }}">
                                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                        <td>
                                                            @if($item['type'] === 'lab')
                                                                <i class="fas fa-vial text-primary me-2"></i>
                                                            @else
                                                                <i class="fas fa-x-ray text-info me-2"></i>
                                                            @endif
                                                            <span class="fw-semibold">{{ $item['name'] }}</span>
                                                        </td>
                                                        <td><code>{{ $item['code'] }}</code></td>
                                                        <td class="text-end fw-bold row-approved-price">{{ number_format($rowApp) }} د.ع</td>
                                                        <td class="text-end text-success fw-bold row-patient-share">{{ number_format($rowPat) }} د.ع</td>
                                                        <td class="text-end text-primary fw-bold row-insurance-share">{{ number_format($rowIns) }} د.ع</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                            <tfoot class="table-success">
                                                <tr>
                                                    <th colspan="3" class="text-end fw-bold">المجموع الإجمالي:</th>
                                                    <th class="text-end fw-bold fs-6 text-dark" id="table_total_approved">{{ number_format($initialApproved) }} د.ع</th>
                                                    <th class="text-end fw-bold fs-6 text-success" id="table_total_patient">{{ number_format($initialPatient) }} د.ع</th>
                                                    <th class="text-end fw-bold fs-6 text-primary" id="table_total_insurance">{{ number_format($initialInsurance) }} د.ع</th>
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
                                <label class="form-label fw-bold">
                                    <i class="fas fa-hand-holding-usd me-1 text-success"></i>
                                    المبلغ المحصل نقداً من المريض (IQD) *
                                </label>
                                <div class="input-group input-group-lg">
                                    <input type="number" 
                                           class="form-control fw-bold text-success @error('amount') is-invalid @enderror" 
                                           id="amount" 
                                           name="amount" 
                                           step="0.01" 
                                           min="0" 
                                           value="{{ old('amount', $initialPatient > 0 ? $initialPatient : ($request->total_amount ?? 0)) }}" 
                                           required>
                                    <span class="input-group-text bg-success text-white fw-bold">IQD</span>
                                </div>
                                <small class="text-muted">المبلغ الفعلي المستلم في الصندوق من المريض.</small>
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
                            <a href="{{ auth()->user()->hasRole('consultation_receptionist') && !auth()->user()->hasRole('admin') ? route('consultant-availability.index') : route('cashier.index') }}" class="btn btn-secondary">
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

        rows.forEach(row => {
            const basePrice = parseFloat(row.getAttribute('data-base-price')) || 0;
            const moiPrice = parseFloat(row.getAttribute('data-moi-price')) || 0;
            const hiPrice = parseFloat(row.getAttribute('data-hi-price')) || 0;
            const isMoiActive = row.getAttribute('data-is-moi-active') === '1';
            const isHiActive = row.getAttribute('data-is-hi-active') === '1';

            let rowApproved = basePrice;
            let rowCovered = false;

            if (insType === 'moi') {
                if (!isMoiActive || moiPrice <= 0) {
                    hasError = true;
                } else {
                    rowApproved = moiPrice;
                    rowCovered = true;
                }
            } else if (insType === 'hi') {
                if (!isHiActive || hiPrice <= 0) {
                    hasError = true;
                } else {
                    rowApproved = hiPrice;
                    rowCovered = true;
                }
            }

            let rowPatient = rowApproved;
            let rowInsurance = 0;

            if (rowCovered) {
                rowPatient = Math.round(rowApproved * (copayPct / 100));
                rowInsurance = Math.max(0, rowApproved - rowPatient);
            }

            totalApproved += rowApproved;
            totalPatient += rowPatient;
            totalInsurance += rowInsurance;

            const tdApproved = row.querySelector('.row-approved-price');
            const tdPatient = row.querySelector('.row-patient-share');
            const tdInsurance = row.querySelector('.row-insurance-share');

            if (tdApproved) tdApproved.textContent = formatNumber(rowApproved);
            if (tdPatient) tdPatient.textContent = formatNumber(rowPatient);
            if (tdInsurance) tdInsurance.textContent = formatNumber(rowInsurance);
        });

        const alertBox = document.getElementById('insuranceErrorAlert');
        const submitBtn = document.getElementById('submitPaymentBtn');

        if (hasError) {
            if (alertBox) alertBox.classList.remove('d-none');
            if (submitBtn) submitBtn.disabled = true;
            totalApproved = 0;
            totalPatient = 0;
            totalInsurance = 0;
        } else {
            if (alertBox) alertBox.classList.add('d-none');
            if (submitBtn) submitBtn.disabled = false;
        }

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

        if (amountInput) {
            amountInput.value = totalPatient;
        }
    }

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