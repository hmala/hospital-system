@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- الترويسة والأزرار -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="fas fa-file-medical-alt text-primary me-2"></i>ملف فحص العيون السريري
                    </h3>
                    <div class="text-muted small">تاريخ ووقت الفحص: {{ $examination->created_at->format('Y-m-d h:i A') }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eye.examinations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>سجل الكشوفات
                    </a>
                    @if($examination->has_glasses_prescription || $examination->ref_od_sphere || $examination->ref_os_sphere)
                    <a href="{{ route('eye.examinations.printGlasses', $examination) }}" class="btn btn-primary" target="_blank">
                        <i class="fas fa-glasses me-1"></i>طباعة راشيتة النظارة
                    </a>
                    @endif
                </div>
            </div>

            <!-- بطاقة بيانات المريض -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">اسم المريض:</span>
                            <span class="fs-5 fw-bold text-primary">{{ $examination->patient->name }}</span>
                            <div class="small text-muted">{{ $examination->patient->phone ?? 'بدون هاتف' }} | {{ $examination->patient->age ?? '-' }} سنة</div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">الطبيب الفاحص:</span>
                            <span class="fs-6 fw-bold text-dark">{{ $examination->doctor->user->name ?? 'طبيب العيون' }}</span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">التشخيص المعتمد:</span>
                            <span class="badge bg-primary fs-6 px-3 py-1 mt-1">{{ $examination->diagnosis ?? 'كشف عام' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- المقارنة الثنائية OD vs OS -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-columns text-primary me-2"></i>الفحص السريري الثنائي المقارن (OD vs OS)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0 align-middle">
                            <thead class="table-light text-center">
                                <tr>
                                    <th style="width: 25%;">عنصر الفحص</th>
                                    <th style="width: 37.5%;" class="text-primary bg-primary-subtle fs-6">العين اليمنى (OD - Right Eye)</th>
                                    <th style="width: 37.5%; color: #6f42c1;" class="bg-purple-subtle fs-6">العين اليسرى (OS - Left Eye)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="fw-bold">حدة الإبصار (بدون نظارة)</td>
                                    <td class="text-center fw-bold fs-5 text-primary">{{ $examination->va_od_unaided ?? '-' }}</td>
                                    <td class="text-center fw-bold fs-5 text-purple" style="color: #6f42c1;">{{ $examination->va_os_unaided ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">حدة الإبصار (مع النظارة)</td>
                                    <td class="text-center fw-bold fs-5 text-success">{{ $examination->va_od_corrected ?? '-' }}</td>
                                    <td class="text-center fw-bold fs-5 text-success">{{ $examination->va_os_corrected ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">حدة الإبصار (Pinhole)</td>
                                    <td class="text-center">{{ $examination->va_od_pinhole ?? '-' }}</td>
                                    <td class="text-center">{{ $examination->va_os_pinhole ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">ضغط العين (IOP)</td>
                                    <td class="text-center fw-bold fs-5 text-dark">{{ $examination->iop_od ? $examination->iop_od . ' mmHg' : '-' }}</td>
                                    <td class="text-center fw-bold fs-5 text-dark">{{ $examination->iop_os ? $examination->iop_os . ' mmHg' : '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">القرنية (Cornea)</td>
                                    <td>{{ $examination->cornea_od ?? 'سليمة' }}</td>
                                    <td>{{ $examination->cornea_os ?? 'سليمة' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">العدسة (Lens)</td>
                                    <td class="fw-semibold">{{ $examination->lens_od ?? 'Clear' }}</td>
                                    <td class="fw-semibold">{{ $examination->lens_os ?? 'Clear' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">القزحية والحدقة (Iris/Pupil)</td>
                                    <td>{{ $examination->iris_pupil_od ?? 'Round & reactive' }}</td>
                                    <td>{{ $examination->iris_pupil_os ?? 'Round & reactive' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">تقعر العصب البصري (C/D Ratio)</td>
                                    <td class="text-center fw-bold">{{ $examination->cup_to_disc_ratio_od ?? '0.3' }}</td>
                                    <td class="text-center fw-bold">{{ $examination->cup_to_disc_ratio_os ?? '0.3' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">الشبكية والماكولا (Macula/Retina)</td>
                                    <td>{{ $examination->macula_od ?? 'Normal' }}</td>
                                    <td>{{ $examination->macula_os ?? 'Normal' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- جدول قياسات النظارة (Refraction) -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-glasses text-info me-2"></i>قياسات النظارة الطبية المعتمدة (Refraction)</h5>
                    @if($examination->pupillary_distance)
                    <span class="badge bg-light text-dark border">PD: {{ $examination->pupillary_distance }} mm</span>
                    @endif
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered text-center align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>العين</th>
                                <th>Sphere (Sph)</th>
                                <th>Cylinder (Cyl)</th>
                                <th>Axis (°)</th>
                                <th>Addition (Add)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-primary">اليمنى (OD)</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_od_sphere ? sprintf("%+0.2f", $examination->ref_od_sphere) : '-' }}</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_od_cylinder ? sprintf("%+0.2f", $examination->ref_od_cylinder) : '-' }}</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_od_axis ? $examination->ref_od_axis . '°' : '-' }}</td>
                                <td class="fw-bold fs-6 text-success">{{ $examination->ref_od_add ? sprintf("%+0.2f", $examination->ref_od_add) : '-' }}</td>
                            </tr>
                            <tr>
                                <td class="fw-bold text-purple" style="color: #6f42c1;">اليسرى (OS)</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_os_sphere ? sprintf("%+0.2f", $examination->ref_os_sphere) : '-' }}</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_os_cylinder ? sprintf("%+0.2f", $examination->ref_os_cylinder) : '-' }}</td>
                                <td class="fw-bold fs-6">{{ $examination->ref_os_axis ? $examination->ref_os_axis . '°' : '-' }}</td>
                                <td class="fw-bold fs-6 text-success">{{ $examination->ref_os_add ? sprintf("%+0.2f", $examination->ref_os_add) : '-' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- الملاحظات والخطة -->
            @if($examination->management_plan || $examination->clinical_notes)
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-clipboard-check text-success me-2"></i>الخطة العلاجية وملاحظات الطبيب</h5>
                </div>
                <div class="card-body p-4">
                    <p class="fs-6 mb-0">{{ $examination->management_plan ?? $examination->clinical_notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
