@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- الترويسة والأزرار -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="fas fa-microscope text-purple me-2" style="color: #6f42c1;"></i>تقرير فحص: {{ $investigation->investigation_type_arabic }}
                    </h3>
                    <div class="text-muted small">تاريخ التوثيق: {{ $investigation->created_at->format('Y-m-d h:i A') }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eye.investigations.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>سجل الفحوصات
                    </a>
                </div>
            </div>

            <!-- بطاقة المريض والفحص -->
            <div class="card border-0 shadow-sm rounded-3 mb-4 bg-light">
                <div class="card-body p-4">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <span class="text-muted small d-block">المريض:</span>
                            <span class="fs-5 fw-bold text-primary">{{ $investigation->patient->name }}</span>
                            <div class="small text-muted">{{ $investigation->patient->phone ?? 'بدون هاتف' }}</div>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">العين المفحوصة:</span>
                            <span class="badge {{ $investigation->eye_target == 'OD' ? 'bg-primary' : ($investigation->eye_target == 'OS' ? 'bg-purple' : 'bg-success') }} fs-6 px-3 py-1 mt-1" style="{{ $investigation->eye_target == 'OS' ? 'background-color: #6f42c1;' : '' }}">
                                {{ $investigation->eye_target == 'OD' ? 'اليمنى (OD)' : ($investigation->eye_target == 'OS' ? 'اليسرى (OS)' : 'كلا العينين (OU)') }}
                            </span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">الطبيب الطالب:</span>
                            <span class="fw-bold text-dark">{{ $investigation->requestedDoctor->name ?? 'طبيب العيون' }}</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">حالة الفحص:</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-6 px-3 py-1 mt-1">
                                {{ $investigation->status_arabic }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- عارض ملف التقرير الطبي للجهاز (PDF / Image) -->
            @if($investigation->attachment_path)
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-file-medical text-primary me-2"></i>مرفق تقرير الجهاز الأصلي</h5>
                    <a href="{{ asset('storage/' . $investigation->attachment_path) }}" class="btn btn-sm btn-outline-primary" target="_blank" download>
                        <i class="fas fa-download me-1"></i>تحميل التقرير
                    </a>
                </div>
                <div class="card-body p-3 text-center bg-dark rounded-bottom">
                    @php
                        $ext = pathinfo($investigation->attachment_path, PATHINFO_EXTENSION);
                    @endphp

                    @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                        <img src="{{ asset('storage/' . $investigation->attachment_path) }}" class="img-fluid rounded shadow" style="max-height: 600px;" alt="Eye Investigation Report">
                    @elseif(strtolower($ext) == 'pdf')
                        <iframe src="{{ asset('storage/' . $investigation->attachment_path) }}" width="100%" height="600px" style="border: none; border-radius: 8px;"></iframe>
                    @else
                        <a href="{{ asset('storage/' . $investigation->attachment_path) }}" class="btn btn-light" target="_blank">
                            <i class="fas fa-external-link-alt me-1"></i>فتح الملف المرفق
                        </a>
                    @endif
                </div>
            </div>
            @endif

            <!-- المشاهدات والتقرير السريري -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-notes-medical text-success me-2"></i>المشاهدات والخلاصة السريرية</h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="fw-bold text-muted small d-block">المشاهدات الطبية (Findings):</label>
                        <p class="fs-6 bg-light p-3 rounded-2">{{ $investigation->findings ?? 'لم يتم تدوين مشاهدات تفصيلية' }}</p>
                    </div>

                    @if($investigation->conclusion)
                    <div>
                        <label class="fw-bold text-muted small d-block">الخلاصة والقرار (Conclusion / Impression):</label>
                        <div class="alert alert-primary mb-0 fw-bold fs-6">
                            {{ $investigation->conclusion }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- تعديل أو اعتماد التقرير -->
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-edit text-info me-2"></i>تعديل المشاهدات أو إرفاق تقرير جديد</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('eye.investigations.updateResults', $investigation) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">المشاهدات (Findings)</label>
                            <textarea name="findings" class="form-control" rows="3" required>{{ $investigation->findings }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">الخلاصة والقرار (Conclusion)</label>
                            <input type="text" name="conclusion" class="form-control" value="{{ $investigation->conclusion }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">تحديث الملف المرفق (PDF/صورة)</label>
                            <input type="file" name="attachment" class="form-control">
                        </div>
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary fw-bold px-4">
                                <i class="fas fa-check me-1"></i>تحديث واعتماد التقرير
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
