@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header text-white py-3" style="background-color: #6f42c1;">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0 fw-bold"><i class="fas fa-microscope me-2"></i>طلب / توثيق فحص أجهزة عيون تشخيصية</h4>
                        <a href="{{ route('eye.investigations.index') }}" class="btn btn-sm btn-light fw-semibold">
                            <i class="fas fa-arrow-right me-1"></i>العودة لسجل الفحوصات
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('eye.investigations.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- اختيار المريض -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">المريض <span class="text-danger">*</span></label>
                            @if($patient)
                                <input type="hidden" name="patient_id" value="{{ $patient->id }}">
                                <div class="p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold text-primary fs-5">{{ $patient->name }}</div>
                                        <div class="small text-muted">{{ $patient->phone ?? 'بدون هاتف' }} | {{ $patient->age ?? '-' }} سنة</div>
                                    </div>
                                    <span class="badge bg-success">مريض محدد</span>
                                </div>
                            @else
                                <select name="patient_id" class="form-select" required>
                                    <option value="">-- اختر المريض من السجل --</option>
                                    @foreach(\App\Models\Patient::latest()->limit(50)->get() as $p)
                                        <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->phone ?? 'بدون هاتف' }})</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <!-- نوع الفحص والعين المستهدفة -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-7">
                                <label class="form-label fw-bold text-dark">نوع فحص الجهاز المطلوب <span class="text-danger">*</span></label>
                                <select name="investigation_type" class="form-select" required>
                                    <option value="oct_macula" selected>تصوير طبقي للشبكية (OCT Macula)</option>
                                    <option value="oct_optic_disc">تصوير العصب البصري (OCT Optic Disc)</option>
                                    <option value="visual_field">فحص مجال وساحة البصر (Visual Field Humphrey)</option>
                                    <option value="pentacam_topography">تضاريس القرنية الملونة (Pentacam / Topography)</option>
                                    <option value="biometry_iol">حساب قوة وعدسة العين (Biometry / A-Scan IOL)</option>
                                    <option value="fundus_photography">تصوير قاع العين الملون (Fundus Photography)</option>
                                    <option value="b_scan">سونار العين الحركي (B-Scan Ultrasound)</option>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label fw-bold text-dark">العين المستهدفة <span class="text-danger">*</span></label>
                                <select name="eye_target" class="form-select" required>
                                    <option value="OD">العين اليمنى فقط (OD)</option>
                                    <option value="OS">العين اليسرى فقط (OS)</option>
                                    <option value="OU" selected>كلا العينين (OU - Both Eyes)</option>
                                </select>
                            </div>
                        </div>

                        <!-- إرفاق ملف التقرير الطبي للجهاز -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">إرفاق تقرير الجهاز الصادر (PDF أو صورة Scan)</label>
                            <input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                            <div class="small text-muted mt-1">يدعم ملفات PDF أو صور عالية الدقة من أجهزة Zeiss أو Topcon أو Heidelberg</div>
                        </div>

                        <!-- الملاحظات والنتيجة السريرية -->
                        <div class="mb-3">
                            <label class="form-label fw-bold text-dark">المشاهدات السريرية للفحص (Findings)</label>
                            <textarea name="findings" class="form-control" rows="3" placeholder="مثال: Central Macular Thickness = 320 µm, Cystoid Macular Edema with subretinal fluid..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">الخلاصة والقرار الطبي (Conclusion / Impression)</label>
                            <input type="text" name="conclusion" class="form-control" placeholder="مثال: Diabetic Macular Edema OD - Candidate for Anti-VEGF Injection">
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('eye.investigations.index') }}" class="btn btn-light px-4">إلغاء</a>
                            <button type="submit" class="btn text-white px-5 fw-bold" style="background-color: #6f42c1;">
                                <i class="fas fa-save me-1"></i>حفظ الفحص واعتماده
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
