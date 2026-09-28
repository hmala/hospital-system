@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- ترويسة المريض ومحطة الفحص -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-primary text-white">
        <div class="card-body p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 52px; height: 52px;">
                        <i class="fas fa-eye fa-2x"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">{{ $patient->name }}</h4>
                        <div class="small opacity-75">
                            <span>الهاتف: {{ $patient->phone ?? 'غير مسجل' }}</span> | 
                            <span>العمر: {{ $patient->age ?? '-' }} سنة</span> | 
                            <span>الجنس: {{ $patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</span> | 
                            <span class="badge bg-light text-primary">{{ $patient->insurance_type == 'cash' ? 'نقدي' : 'تأمين صحي' }}</span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    @if($appointment)
                    <div class="text-end">
                        <span class="badge bg-warning text-dark fs-6 px-3 py-1">طابور #{{ $appointment->queue_number }}</span>
                        <div class="small text-white-50 mt-1">{{ $appointment->visit_type_arabic }}</div>
                    </div>
                    @endif
                    <a href="{{ route('eye.reception.index') }}" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-arrow-right me-1"></i>العودة للطابور
                    </a>
                </div>
            </div>
            @if($appointment && $appointment->chief_complaint)
            <div class="alert alert-warning text-dark py-2 px-3 mt-3 mb-0 small rounded-2">
                <strong><i class="fas fa-comment-medical me-1"></i>الشكوى الرئيسية للمريض:</strong> {{ $appointment->chief_complaint }}
            </div>
            @endif
        </div>
    </div>

    <form action="{{ route('eye.examinations.store') }}" method="POST" id="eyeExamForm">
        @csrf
        <input type="hidden" name="patient_id" value="{{ $patient->id }}">
        @if($appointment)
        <input type="hidden" name="eye_appointment_id" value="{{ $appointment->id }}">
        @endif

        <div class="row g-4">
            <!-- العمود الأيمن: استمارة الفحص السريري الثنائي OD / OS -->
            <div class="col-lg-8">

                <!-- 1. حدة الإبصار (Visual Acuity) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i>1. فحص حدة الإبصار (Visual Acuity - VA)</h5>
                        <span class="badge bg-light text-muted border">Snellen / Decimal</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <!-- العين اليمنى OD -->
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-primary mb-3"><i class="fas fa-eye me-1"></i>العين اليمنى (OD - Right Eye)</h6>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">بدون نظارة (Unaided)</label>
                                    <input type="text" name="va_od_unaided" class="form-control form-control-sm" placeholder="مثال: 6/60 أو CF 1m" list="vaOptions">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">مع النظارة (Corrected / With Glasses)</label>
                                    <input type="text" name="va_od_corrected" class="form-control form-control-sm" placeholder="مثال: 6/6" list="vaOptions">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">مع الثقب الصغير (Pinhole PH)</label>
                                    <input type="text" name="va_od_pinhole" class="form-control form-control-sm" placeholder="مثال: 6/9" list="vaOptions">
                                </div>
                            </div>

                            <!-- العين اليسرى OS -->
                            <div class="col-md-6">
                                <h6 class="fw-bold text-purple mb-3" style="color: #6f42c1;"><i class="fas fa-eye me-1"></i>العين اليسرى (OS - Left Eye)</h6>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">بدون نظارة (Unaided)</label>
                                    <input type="text" name="va_os_unaided" class="form-control form-control-sm" placeholder="مثال: 6/36 أو CF 2m" list="vaOptions">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">مع النظارة (Corrected / With Glasses)</label>
                                    <input type="text" name="va_os_corrected" class="form-control form-control-sm" placeholder="مثال: 6/6" list="vaOptions">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">مع الثقب الصغير (Pinhole PH)</label>
                                    <input type="text" name="va_os_pinhole" class="form-control form-control-sm" placeholder="مثال: 6/9" list="vaOptions">
                                </div>
                            </div>
                        </div>

                        <datalist id="vaOptions">
                            <option value="6/6">
                            <option value="6/9">
                            <option value="6/12">
                            <option value="6/18">
                            <option value="6/24">
                            <option value="6/36">
                            <option value="6/60">
                            <option value="CF 1m (عد أصابع)">
                            <option value="CF 2m">
                            <option value="HM (حركة يد)">
                            <option value="PL (إدراك ضوء)">
                            <option value="NPL (لا يدرك الضوء)">
                        </datalist>
                    </div>
                </div>

                <!-- 2. قياس الانكسار والنظارة الطبية (Refraction & Glasses Prescription) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-glasses text-info me-2"></i>2. قياس الانكسار والنظارة الطبية (Refraction)</h5>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyODtoOS">
                            <i class="fas fa-copy me-1"></i>نسخ قياسات اليمين لليسار (OD ➡️ OS)
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle mb-3">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 140px;">العين</th>
                                        <th>Sphere (Sph)</th>
                                        <th>Cylinder (Cyl)</th>
                                        <th>Axis (°)</th>
                                        <th>Addition (Add)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-primary bg-light">العين اليمنى (OD)</td>
                                        <td>
                                            <input type="number" name="ref_od_sphere" id="ref_od_sphere" class="form-control form-control-sm text-center" step="0.25" placeholder="-2.50">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_od_cylinder" id="ref_od_cylinder" class="form-control form-control-sm text-center" step="0.25" placeholder="-0.75">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_od_axis" id="ref_od_axis" class="form-control form-control-sm text-center" min="1" max="180" placeholder="90">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_od_add" id="ref_od_add" class="form-control form-control-sm text-center" step="0.25" placeholder="+2.00">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-bold text-purple bg-light" style="color: #6f42c1;">العين اليسرى (OS)</td>
                                        <td>
                                            <input type="number" name="ref_os_sphere" id="ref_os_sphere" class="form-control form-control-sm text-center" step="0.25" placeholder="-2.50">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_os_cylinder" id="ref_os_cylinder" class="form-control form-control-sm text-center" step="0.25" placeholder="-0.75">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_os_axis" id="ref_os_axis" class="form-control form-control-sm text-center" min="1" max="180" placeholder="90">
                                        </td>
                                        <td>
                                            <input type="number" name="ref_os_add" id="ref_os_add" class="form-control form-control-sm text-center" step="0.25" placeholder="+2.00">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">المسافة بين الحدقتين (PD):</span>
                                    <input type="number" name="pupillary_distance" class="form-control text-center" step="0.5" placeholder="62.0">
                                    <span class="input-group-text">mm</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch fs-6">
                                    <input class="form-check-input" type="checkbox" name="has_glasses_prescription" id="hasGlassesCheck" value="1" checked>
                                    <label class="form-check-label fw-bold text-dark" for="hasGlassesCheck">
                                        إصدار وطباعة راشيتة نظارة رسمية للمريض
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. ضغط العين (Intraocular Pressure - IOP) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-tachometer-alt text-warning me-2"></i>3. قياس ضغط العين (IOP mmHg)</h5>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" onclick="setQuickIOP(14, 14)">14</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setQuickIOP(16, 16)">16</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setQuickIOP(18, 18)">18</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="setQuickIOP(21, 21)">21</button>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-primary">العين اليمنى (OD)</label>
                                <div class="input-group">
                                    <input type="number" name="iop_od" id="iop_od" class="form-control text-center fs-5 fw-bold" step="0.5" placeholder="16.0">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-purple" style="color: #6f42c1;">العين اليسرى (OS)</label>
                                <div class="input-group">
                                    <input type="number" name="iop_os" id="iop_os" class="form-control text-center fs-5 fw-bold" step="0.5" placeholder="16.0">
                                    <span class="input-group-text">mmHg</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">طريقة القياس والجهاز</label>
                                <select name="iop_method" class="form-select">
                                    <option value="goldmann" selected>Goldmann Applanation (GAT)</option>
                                    <option value="air_puff">Non-Contact Tonometry (Air-Puff)</option>
                                    <option value="icare">iCare Rebound Tonometer</option>
                                    <option value="tonopen">Tono-Pen</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. فحص المصباح الشقي وقاع العين (Slit Lamp & Fundus) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-microscope text-success me-2"></i>4. فحص المصباح الشقي وقاع العين (Slit Lamp & Fundus)</h5>
                        <button type="button" class="btn btn-sm btn-success" id="btnAllWNL">
                            <i class="fas fa-check-double me-1"></i>الكل سليم وطبيعي (WNL لكلا العينين)
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <!-- فحص القرنية والعدسة والمقدمة -->
                        <h6 class="fw-bold text-muted border-bottom pb-2 mb-3">الجزء الأمامي (Anterior Segment):</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6 border-end">
                                <span class="badge bg-primary mb-2">اليمنى OD</span>
                                <div class="mb-2">
                                    <label class="form-label small">القرنية (Cornea):</label>
                                    <input type="text" name="cornea_od" id="cornea_od" class="form-control form-control-sm" placeholder="Clear, smooth...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">العدسة (Lens):</label>
                                    <input type="text" name="lens_od" id="lens_od" class="form-control form-control-sm" placeholder="Clear / Nuclear Sclerosis Grade...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">القزحية والحدقة (Iris & Pupil):</label>
                                    <input type="text" name="iris_pupil_od" id="iris_pupil_od" class="form-control form-control-sm" placeholder="Round, reactive, normal...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <span class="badge bg-purple mb-2" style="background-color: #6f42c1;">اليسرى OS</span>
                                <div class="mb-2">
                                    <label class="form-label small">القرنية (Cornea):</label>
                                    <input type="text" name="cornea_os" id="cornea_os" class="form-control form-control-sm" placeholder="Clear, smooth...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">العدسة (Lens):</label>
                                    <input type="text" name="lens_os" id="lens_os" class="form-control form-control-sm" placeholder="Clear / Nuclear Sclerosis Grade...">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">القزحية والحدقة (Iris & Pupil):</label>
                                    <input type="text" name="iris_pupil_os" id="iris_pupil_os" class="form-control form-control-sm" placeholder="Round, reactive, normal...">
                                </div>
                            </div>
                        </div>

                        <!-- فحص الشبكية والعصب وقاع العين -->
                        <h6 class="fw-bold text-muted border-bottom pb-2 mb-3 mt-4">قاع العين والشبكية (Posterior Segment / Fundus):</h6>
                        <div class="row g-3">
                            <div class="col-md-6 border-end">
                                <div class="mb-2">
                                    <label class="form-label small">نسبة تقعر العصب (C/D Ratio OD):</label>
                                    <input type="text" name="cup_to_disc_ratio_od" id="cd_od" class="form-control form-control-sm" placeholder="0.3">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">الماكولا والشبكية (Macula & Retina OD):</label>
                                    <input type="text" name="macula_od" id="macula_od" class="form-control form-control-sm" placeholder="Normal foveal reflex, no exudates...">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2">
                                    <label class="form-label small">نسبة تقعر العصب (C/D Ratio OS):</label>
                                    <input type="text" name="cup_to_disc_ratio_os" id="cd_os" class="form-control form-control-sm" placeholder="0.3">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small">الماكولا والشبكية (Macula & Retina OS):</label>
                                    <input type="text" name="macula_os" id="macula_os" class="form-control form-control-sm" placeholder="Normal foveal reflex, no exudates...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 5. التشخيص والقرار العلاجي -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-medical text-primary me-2"></i>5. التشخيص الطبي والخطة العلاجية</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">التشخيص السريري المعتمد (Diagnosis) <span class="text-danger">*</span></label>
                            <input type="text" name="diagnosis" class="form-control fs-6" placeholder="مثال: Senile Cataract OD / Refractive Error (Myopia + Astigmatism) / Open Angle Glaucoma" list="commonEyeDiagnoses" required>
                            <datalist id="commonEyeDiagnoses">
                                <option value="Senile Nuclear Cataract (ماء أبيض)">
                                <option value="Refractive Error (خطأ انكسار ونظارة)">
                                <option value="Primary Open Angle Glaucoma (ماء أزرق / ضغط عين)">
                                <option value="Diabetic Macular Edema (وذمة ماكولا سكرية)">
                                <option value="Non-Proliferative Diabetic Retinopathy (اعتلال شبكية سكري)">
                                <option value="Dry Eye Syndrome (متلازمة جفاف العين)">
                                <option value="Allergic Conjunctivitis (التهاب ملتحمة تحسسي)">
                                <option value="Corneal Ulcer (قرحة قرنية)">
                                <option value="Pterygium (ظفرة العين)">
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">الخطة العلاجية والقرار (Management Plan)</label>
                            <textarea name="management_plan" class="form-control" rows="3" placeholder="مثال: جدولة عملية ماء أبيض Phaco + IOL بعد تجهيز السكر، صرف قطرات ترطيب ومضاد للالتهاب..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 6. التكامل مع المختبر والصيدلية المركزية -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-network-wired text-info me-2"></i>6. إرسال طلبات المختبر والصيدلية المركزية</h5>
                    </div>
                    <div class="card-body p-4">
                        <!-- طلب مختبر -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="send_lab_order" id="sendLabCheck" value="1">
                            <label class="form-check-label fw-bold text-dark" for="sendLabCheck">
                                إرسال طلب تحاليل دم/سكر إلى مختبر المستشفى العام
                            </label>
                        </div>
                        <div id="labTestsSelectSection" class="p-3 bg-light rounded-3 mb-4 d-none">
                            <label class="form-label small fw-bold">اختر التحاليل المطلوبة:</label>
                            <div class="row g-2">
                                @foreach($labTests->take(8) as $lt)
                                <div class="col-md-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="lab_test_ids[]" value="{{ $lt->id }}" id="lt_{{ $lt->id }}">
                                        <label class="form-check-label small" for="lt_{{ $lt->id }}">{{ $lt->name }}</label>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- وصفة صيدلية العيون -->
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="send_prescription" id="sendRxCheck" value="1">
                            <label class="form-check-label fw-bold text-dark" for="sendRxCheck">
                                إرسال وصفة قطرات / مراهم عيون إلى صيدلية المستشفى بنظام كانبان الفوري
                            </label>
                        </div>
                        <div id="rxSelectSection" class="p-3 bg-light rounded-3 d-none">
                            <label class="form-label small fw-bold">اختر قطرات ومراهم العيون:</label>
                            <div id="rxItemsContainer">
                                <div class="row g-2 mb-2 rx-item-row">
                                    <div class="col-md-6">
                                        <select name="prescription_items[0][medicine_id]" class="form-select form-select-sm">
                                            <option value="">-- اختر الدواء من دليل الصيدلية --</option>
                                            @foreach($eyeMedicines as $med)
                                                <option value="{{ $med->id }}">{{ $med->name }} ({{ $med->dosage_form }})</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="text" name="prescription_items[0][dosage_instructions]" class="form-control form-control-sm" placeholder="الجرعة: قطرة 4 مرات يومياً">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="prescription_items[0][quantity]" class="form-control form-control-sm text-center" value="1" min="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mb-5">
                    <a href="{{ route('eye.reception.index') }}" class="btn btn-light px-4">إلغاء</a>
                    <button type="submit" class="btn btn-primary px-5 py-2 fs-6 fw-bold">
                        <i class="fas fa-save me-1"></i>حفظ الفحص السريري واعتماد الزيارة
                    </button>
                </div>

            </div>

            <!-- العمود الأيسر: السجل التاريخي للمريض وفحوصات الأجهزة السابقة -->
            <div class="col-lg-4">
                <!-- كارت الوصول السريع للإجراءات -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-bolt text-warning me-2"></i>إجراءات وتحويلات سريعة</h6>
                    </div>
                    <div class="card-body p-3 d-grid gap-2">
                        <a href="{{ route('eye.investigations.create', ['patient_id' => $patient->id]) }}" class="btn btn-outline-info text-start">
                            <i class="fas fa-microscope me-2"></i>تحويل لغرفة الأجهزة (OCT / ساحة)
                        </a>
                        <a href="{{ route('eye.surgeries.create', ['patient_id' => $patient->id]) }}" class="btn btn-outline-danger text-start">
                            <i class="fas fa-procedures me-2"></i>جدولة عملية ماء أبيض أو حقن
                        </a>
                    </div>
                </div>

                <!-- سجل ضغط العين والفحوصات السابقة -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-history text-secondary me-2"></i>الفحوصات والزيارات السابقة</h6>
                    </div>
                    <div class="card-body p-3">
                        @forelse($previousExams as $pExam)
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold small">{{ $pExam->created_at->format('Y-m-d') }}</span>
                                <span class="badge bg-light text-dark border">IOP: {{ $pExam->iop_od ?? '-' }} / {{ $pExam->iop_os ?? '-' }}</span>
                            </div>
                            <div class="small text-muted mt-1">{{ $pExam->diagnosis ?? 'كشف سابق' }}</div>
                        </div>
                        @empty
                        <div class="text-center py-3 text-muted small">هذه هي الزيارة الأولى للمريض بمركز العيون</div>
                        @endforelse
                    </div>
                </div>

                <!-- فحوصات الأجهزة المتخصصة السابقة -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3">
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-file-image text-purple me-2" style="color: #6f42c1;"></i>تقارير الأجهزة السابقة</h6>
                    </div>
                    <div class="card-body p-3">
                        @forelse($previousInvestigations as $inv)
                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                            <div>
                                <div class="fw-bold small">{{ $inv->investigation_type_arabic }}</div>
                                <div class="text-muted small">{{ $inv->created_at->format('Y-m-d') }}</div>
                            </div>
                            <a href="{{ route('eye.investigations.show', $inv) }}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                        @empty
                        <div class="text-center py-3 text-muted small">لا توجد فحوصات أجهزة سابقة</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // زر نسخ قياسات النظارة من اليمنى إلى اليسرى
    const btnCopy = document.getElementById('btnCopyODtoOS');
    if (btnCopy) {
        btnCopy.addEventListener('click', function () {
            document.getElementById('ref_os_sphere').value = document.getElementById('ref_od_sphere').value;
            document.getElementById('ref_os_cylinder').value = document.getElementById('ref_od_cylinder').value;
            document.getElementById('ref_os_axis').value = document.getElementById('ref_od_axis').value;
            document.getElementById('ref_os_add').value = document.getElementById('ref_od_add').value;
        });
    }

    // زر WNL: الكل طبيعي وسليم لكلا العينين
    const btnWNL = document.getElementById('btnAllWNL');
    if (btnWNL) {
        btnWNL.addEventListener('click', function () {
            document.getElementById('cornea_od').value = 'Clear & smooth, no infiltrates';
            document.getElementById('cornea_os').value = 'Clear & smooth, no infiltrates';
            document.getElementById('lens_od').value = 'Clear, no cataract';
            document.getElementById('lens_os').value = 'Clear, no cataract';
            document.getElementById('iris_pupil_od').value = 'Round, regular & reactive to light';
            document.getElementById('iris_pupil_os').value = 'Round, regular & reactive to light';
            document.getElementById('cd_od').value = '0.3';
            document.getElementById('cd_os').value = '0.3';
            document.getElementById('macula_od').value = 'Normal foveal reflex';
            document.getElementById('macula_os').value = 'Normal foveal reflex';
        });
    }

    // إظهار/إخفاء أقسام المختبر والصيدلية
    const sendLabCheck = document.getElementById('sendLabCheck');
    const labSection = document.getElementById('labTestsSelectSection');
    if (sendLabCheck && labSection) {
        sendLabCheck.addEventListener('change', function () {
            labSection.classList.toggle('d-none', !this.checked);
        });
    }

    const sendRxCheck = document.getElementById('sendRxCheck');
    const rxSection = document.getElementById('rxSelectSection');
    if (sendRxCheck && rxSection) {
        sendRxCheck.addEventListener('change', function () {
            rxSection.classList.toggle('d-none', !this.checked);
        });
    }
});

function setQuickIOP(od, os) {
    document.getElementById('iop_od').value = od;
    document.getElementById('iop_os').value = os;
}
</script>
@endpush
@endsection
