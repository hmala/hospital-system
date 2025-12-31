@extends('layouts.app')

@section('content')
@php
    $canManageSurgery = auth()->user()->hasRole(['surgery_staff', 'admin']) || 
                       (auth()->user()->isDoctor() && auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id);
@endphp

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-procedures me-2"></i>
                    تفاصيل العملية الجراحية
                </h2>
                <div>
                    @if($canManageSurgery)
                    <a href="{{ route('surgeries.edit', $surgery) }}" class="btn btn-warning">
                        <i class="fas fa-edit me-2"></i>تعديل
                    </a>
                    @if($surgery->status == 'completed')
                    <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#detailsModal">
                        <i class="fas fa-clipboard-check me-2"></i>تفاصيل العملية
                    </button>
                    @endif
                    @endif
                    <a href="{{ route('surgeries.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة
                    </a>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">معلومات العملية</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">نوع العملية:</th>
                            <td><strong>{{ $surgery->surgery_type }}</strong></td>
                        </tr>
                        <tr>
                            <th>الحالة:</th>
                            <td>
                                @if($surgery->status == 'scheduled')
                                    <span class="badge bg-secondary">مجدولة</span>
                                @elseif($surgery->status == 'in_progress')
                                    <span class="badge bg-warning">جارية</span>
                                @elseif($surgery->status == 'completed')
                                    <span class="badge bg-success">مكتملة</span>
                                @else
                                    <span class="badge bg-danger">ملغاة</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>التاريخ:</th>
                            <td>{{ $surgery->scheduled_date->format('Y-m-d') }}</td>
                        </tr>
                        <tr>
                            <th>الوقت:</th>
                            <td>{{ $surgery->scheduled_time }}</td>
                        </tr>
                        <tr>
                            <th>المريض:</th>
                            <td>
                                <a href="{{ route('patients.show', $surgery->patient) }}" class="text-decoration-none">
                                    {{ $surgery->patient->user->name }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <th>الطبيب الجراح:</th>
                            <td>د. {{ $surgery->doctor->user->name }}</td>
                        </tr>
                        @if($surgery->anesthesiologist)
                        <tr>
                            <th>الطبيب المخدر:</th>
                            <td>د. {{ $surgery->anesthesiologist->user->name }}</td>
                        </tr>
                        @endif
                        @if($surgery->anesthesiologist2)
                        <tr>
                            <th>الطبيب المخدر الثاني:</th>
                            <td>د. {{ $surgery->anesthesiologist2->user->name }}</td>
                        </tr>
                        @endif
                        @if($surgery->surgical_assistant_name)
                        <tr>
                            <th>اسم مساعد الجراح:</th>
                            <td>{{ $surgery->surgical_assistant_name }}</td>
                        </tr>
                        @endif
                        @if($surgery->start_time)
                        <tr>
                            <th>وقت بدء العملية:</th>
                            <td>{{ $surgery->start_time }}</td>
                        </tr>
                        @endif
                        @if($surgery->end_time)
                        <tr>
                            <th>وقت انتهاء العملية:</th>
                            <td>{{ $surgery->end_time }}</td>
                        </tr>
                        @endif
                        @if($surgery->start_time && $surgery->end_time)
                        <tr>
                            <th>مدة العملية:</th>
                            <td>{{ \Carbon\Carbon::parse($surgery->start_time)->diff(\Carbon\Carbon::parse($surgery->end_time))->format('%H:%I') }}</td>
                        </tr>
                        @endif
                        @if($surgery->referring_physician)
                        <tr>
                            <th>الطبيب المرسل:</th>
                            <td>{{ $surgery->referring_physician }}</td>
                        </tr>
                        @endif
                        @if($surgery->anesthesia_type)
                        <tr>
                            <th>نوع التخدير:</th>
                            <td>{{ $surgery->anesthesia_type }}</td>
                        </tr>
                        @endif
                        @if($surgery->surgery_classification)
                        <tr>
                            <th>تصنيف العملية:</th>
                            <td>{{ $surgery->surgery_classification }}</td>
                        </tr>
                        @endif
                        @if($surgery->supplies)
                        <tr>
                            <th>المستلزمات:</th>
                            <td>{{ $surgery->supplies }}</td>
                        </tr>
                        @endif
                        @if($surgery->visit)
                        <tr>
                            <th>الزيارة المرتبطة:</th>
                            <td>
                                <a href="{{ route('visits.show', $surgery->visit) }}" class="text-decoration-none">
                                    زيارة رقم {{ $surgery->visit->id }}
                                </a>
                            </td>
                        </tr>
                        @endif
                    </table>

                    @if($surgery->description)
                    <hr>
                    <h6>وصف العملية:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->description }}
                    </div>
                    @endif

                    @if($surgery->notes)
                    <hr>
                    <h6>ملاحظات:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->notes }}
                    </div>
                    @endif

                    @if($surgery->post_op_notes)
                    <hr>
                    <h6>ملاحظات ما بعد العملية:</h6>
                    <div class="bg-light p-3 rounded">
                        {{ $surgery->post_op_notes }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- التحاليل والأشعة المطلوبة -->
            @if($surgery->labTests->count() > 0 || $surgery->radiologyTests->count() > 0)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-flask me-2"></i>الفحوصات المطلوبة قبل العملية</h5>
                </div>
                <div class="card-body">
                    @if($surgery->labTests->count() > 0)
                    <h6 class="text-primary mb-3"><i class="fas fa-vial me-2"></i>التحاليل المخبرية</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>اسم التحليل</th>
                                    <th>الفئة</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإكمال</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->labTests as $labTest)
                                <tr>
                                    <td>{{ $labTest->labTest->name }}</td>
                                    <td>{{ $labTest->labTest->category ?? 'غير محدد' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $labTest->status_color }}">
                                            {{ $labTest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $labTest->completed_at ? $labTest->completed_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        @if($labTest->result)
                                            <span class="text-success">{{ $labTest->result }}</span>
                                        @elseif($labTest->result_file)
                                            <a href="{{ asset('storage/' . $labTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-download me-1"></i>عرض الملف
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    @if($surgery->radiologyTests->count() > 0)
                    <h6 class="text-success mb-3"><i class="fas fa-x-ray me-2"></i>الأشعة والتصوير</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نوع التصوير</th>
                                    <th>الكود</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الإكمال</th>
                                    <th>النتيجة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->radiologyTests as $radiologyTest)
                                <tr>
                                    <td>{{ $radiologyTest->radiologyType->name }}</td>
                                    <td>{{ $radiologyTest->radiologyType->code }}</td>
                                    <td>
                                        <span class="badge bg-{{ $radiologyTest->status_color }}">
                                            {{ $radiologyTest->status_text }}
                                        </span>
                                    </td>
                                    <td>{{ $radiologyTest->completed_at ? $radiologyTest->completed_at->format('Y-m-d H:i') : '-' }}</td>
                                    <td>
                                        @if($radiologyTest->result)
                                            <span class="text-success">{{ $radiologyTest->result }}</span>
                                        @elseif($radiologyTest->result_file)
                                            <a href="{{ asset('storage/' . $radiologyTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-file-download me-1"></i>عرض الملف
                                            </a>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- نتائج التحاليل والأشعة من الزيارة -->
            @if($surgery->visit)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>نتائج الفحوصات السابقة</h5>
                </div>
                <div class="card-body">
                    @if($surgery->visit->labResults->count() > 0)
                    <h6 class="text-primary mb-3"><i class="fas fa-vial me-2"></i>نتائج التحاليل المخبرية</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>اسم التحليل</th>
                                    <th>النتيجة</th>
                                    <th>الوحدة</th>
                                    <th>النطاق الطبيعي</th>
                                    <th>تاريخ الفحص</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->visit->getLatestLabResults() as $labResult)
                                <tr>
                                    <td>{{ $labResult->test_name }}</td>
                                    <td>{{ $labResult->result }}</td>
                                    <td>{{ $labResult->unit ?? '-' }}</td>
                                    <td>{{ $labResult->normal_range ?? '-' }}</td>
                                    <td>{{ $labResult->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        @if($labResult->is_abnormal)
                                            <span class="badge bg-danger">غير طبيعي</span>
                                        @else
                                            <span class="badge bg-success">طبيعي</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد نتائج تحاليل مخبرية سابقة لهذه الزيارة
                    </div>
                    @endif

                    @if($surgery->visit->radiologyRequests->count() > 0)
                    <h6 class="text-success mb-3"><i class="fas fa-x-ray me-2"></i>نتائج الأشعة والتصوير</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>نوع التصوير</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الحالة</th>
                                    <th>النتائج</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($surgery->visit->radiologyRequests as $radiologyRequest)
                                <tr>
                                    <td>{{ $radiologyRequest->radiologyType->name }}</td>
                                    <td>{{ $radiologyRequest->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $radiologyRequest->status_color }}">
                                            {{ $radiologyRequest->status_text }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($radiologyRequest->result)
                                            <div>
                                                <strong>الملاحظات:</strong> {{ $radiologyRequest->result->findings }}<br>
                                                @if($radiologyRequest->result->impression)
                                                    <strong>الانطباع:</strong> {{ $radiologyRequest->result->impression }}<br>
                                                @endif
                                                @if($radiologyRequest->result->recommendations)
                                                    <strong>التوصيات:</strong> {{ $radiologyRequest->result->recommendations }}
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">لا توجد نتائج</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>لا توجد طلبات أشعة سابقة لهذه الزيارة
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>إجراءات سريعة</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @if($canManageSurgery)
                            @if($surgery->status == 'scheduled')
                            <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning w-100" onclick="return confirm('هل تريد بدء العملية؟')">
                                    <i class="fas fa-play me-2"></i>بدء العملية
                                </button>
                            </form>
                            @endif

                            @if($surgery->status == 'in_progress')
                            <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success w-100" onclick="return confirm('هل تم إكمال العملية؟')">
                                    <i class="fas fa-check me-2"></i>إكمال العملية
                                </button>
                            </form>
                            @endif

                            @if($surgery->status != 'cancelled' && $surgery->status != 'completed')
                            <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-danger w-100" onclick="return confirm('هل تريد إلغاء العملية؟')">
                                    <i class="fas fa-times me-2"></i>إلغاء العملية
                                </button>
                            </form>
                            @endif
                        @endif

                        <a href="{{ route('patients.show', $surgery->patient) }}" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>عرض ملف المريض
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Surgery Details Modal -->
@if($surgery->status == 'completed')
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="detailsModalLabel">
                    <i class="fas fa-clipboard-check me-2"></i>
                    تفاصيل العملية الجراحية
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-body p-0">
                    <!-- Patient Info Header -->
                    <div class="bg-light p-3 border-bottom">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-1">
                                    <i class="fas fa-user-injured text-primary me-2"></i>
                                    المريض: <strong>{{ $surgery->patient->user->name }}</strong>
                                </h6>
                                <small class="text-muted">
                                    <i class="fas fa-user-md me-1"></i>
                                    الطبيب: د. {{ $surgery->doctor->user->name }} |
                                    <i class="fas fa-procedures me-1"></i>
                                    العملية: {{ $surgery->surgery_type }} |
                                    <i class="fas fa-calendar me-1"></i>
                                    التاريخ: {{ $surgery->scheduled_date->format('Y-m-d') }}
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="fas fa-check-circle me-1"></i>
                                    العملية مكتملة
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-4">
                        <!-- Surgery Details Accordion -->
                        <div class="accordion" id="surgeryDetailsAccordion">
                            <!-- Diagnosis Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="diagnosisHeading">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#diagnosisCollapse" aria-expanded="true"
                                            aria-controls="diagnosisCollapse">
                                        <i class="fas fa-stethoscope me-2 text-primary"></i>
                                        التشخيص والتخدير
                                    </button>
                                </h2>
                                <div id="diagnosisCollapse" class="accordion-collapse collapse show"
                                     aria-labelledby="diagnosisHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="diagnosis" class="form-label fw-bold">
                                                        <i class="fas fa-diagnoses text-primary me-1"></i>
                                                        التشخيص
                                                    </label>
                                                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3"
                                                              placeholder="أدخل التشخيص الطبي...">{{ $surgery->diagnosis }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="anesthesia_type" class="form-label fw-bold">
                                                        <i class="fas fa-syringe text-success me-1"></i>
                                                        نوع التخدير
                                                    </label>
                                                    <select class="form-select" id="anesthesia_type" name="anesthesia_type">
                                                        <option value="">اختر نوع التخدير</option>
                                                        <option value="local" {{ $surgery->anesthesia_type == 'local' ? 'selected' : '' }}>تخدير موضعي</option>
                                                        <option value="regional" {{ $surgery->anesthesia_type == 'regional' ? 'selected' : '' }}>تخدير إقليمي</option>
                                                        <option value="general" {{ $surgery->anesthesia_type == 'general' ? 'selected' : '' }}>تخدير عام</option>
                                                        <option value="sedation" {{ $surgery->anesthesia_type == 'sedation' ? 'selected' : '' }}>تخدير إيحائي</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Medical Team Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="teamHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#teamCollapse" aria-expanded="false"
                                            aria-controls="teamCollapse">
                                        <i class="fas fa-users me-2 text-info"></i>
                                        الفريق الطبي
                                    </button>
                                </h2>
                                <div id="teamCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="teamHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="anesthesiologist_id" class="form-label fw-bold">
                                                        <i class="fas fa-user-nurse text-info me-1"></i>
                                                        طبيب التخدير
                                                    </label>
                                                    <select class="form-select" id="anesthesiologist_id" name="anesthesiologist_id">
                                                        <option value="">اختر طبيب التخدير</option>
                                                        @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_id == $doctor->id ? 'selected' : '' }}>
                                                            د. {{ $doctor->user->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="anesthesiologist_2_id" class="form-label fw-bold">
                                                        <i class="fas fa-user-nurse text-info me-1"></i>
                                                        طبيب تخدير مساعد
                                                    </label>
                                                    <select class="form-select" id="anesthesiologist_2_id" name="anesthesiologist_2_id">
                                                        <option value="">اختر طبيب التخدير المساعد</option>
                                                        @foreach(\App\Models\Doctor::where('specialization', 'like', '%تخدير%')->orWhere('specialization', 'like', '%anesthesia%')->get() as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ $surgery->anesthesiologist_2_id == $doctor->id ? 'selected' : '' }}>
                                                            د. {{ $doctor->user->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="surgical_assistant_name" class="form-label fw-bold">
                                                        <i class="fas fa-user-friends text-warning me-1"></i>
                                                        مساعد جراح
                                                    </label>
                                                    <input type="text" class="form-control" id="surgical_assistant_name" name="surgical_assistant_name"
                                                           value="{{ $surgery->surgical_assistant_name }}" placeholder="اسم المساعد الجراح">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="supplies" class="form-label fw-bold">
                                                        <i class="fas fa-tools text-secondary me-1"></i>
                                                        المستلزمات المستخدمة
                                                    </label>
                                                    <textarea class="form-control" id="supplies" name="supplies" rows="2"
                                                              placeholder="المستلزمات والأدوات المستخدمة...">{{ $surgery->supplies }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Surgery Details Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="surgeryHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#surgeryCollapse" aria-expanded="false"
                                            aria-controls="surgeryCollapse">
                                        <i class="fas fa-procedures me-2 text-danger"></i>
                                        تفاصيل العملية
                                    </button>
                                </h2>
                                <div id="surgeryCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="surgeryHeading">
                                    <div class="accordion-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="start_time" class="form-label fw-bold">
                                                        <i class="fas fa-clock text-success me-1"></i>
                                                        وقت البدء
                                                    </label>
                                                    <input type="time" class="form-control" id="start_time" name="start_time"
                                                           value="{{ $surgery->start_time ? $surgery->start_time->format('H:i') : '' }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="end_time" class="form-label fw-bold">
                                                        <i class="fas fa-clock text-danger me-1"></i>
                                                        وقت الانتهاء
                                                    </label>
                                                    <input type="time" class="form-control" id="end_time" name="end_time"
                                                           value="{{ $surgery->end_time ? $surgery->end_time->format('H:i') : '' }}">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="estimated_duration" class="form-label fw-bold">
                                                        <i class="fas fa-hourglass-half text-warning me-1"></i>
                                                        المدة المقدرة
                                                    </label>
                                                    <input type="text" class="form-control" id="estimated_duration" name="estimated_duration"
                                                           value="{{ $surgery->estimated_duration }}" placeholder="مثال: 2:30 (ساعات:دقائق)">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="surgery_category" class="form-label fw-bold">
                                                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                                                        تصنيف العملية
                                                    </label>
                                                    <select class="form-select" id="surgery_category" name="surgery_category">
                                                        <option value="">اختر التصنيف</option>
                                                        <option value="elective" {{ $surgery->surgery_category == 'elective' ? 'selected' : '' }}>اختيارية</option>
                                                        <option value="emergency" {{ $surgery->surgery_category == 'emergency' ? 'selected' : '' }}>طارئة</option>
                                                        <option value="urgent" {{ $surgery->surgery_category == 'urgent' ? 'selected' : '' }}>عاجلة</option>
                                                        <option value="semi_urgent" {{ $surgery->surgery_category == 'semi_urgent' ? 'selected' : '' }}>شبه عاجلة</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="surgery_type_detail" class="form-label fw-bold">
                                                        <i class="fas fa-info-circle text-info me-1"></i>
                                                        نوع العملية التفصيلي
                                                    </label>
                                                    <select class="form-select" id="surgery_type_detail" name="surgery_type_detail">
                                                        <option value="">اختر النوع</option>
                                                        <option value="diagnostic" {{ $surgery->surgery_type_detail == 'diagnostic' ? 'selected' : '' }}>تشخيصية</option>
                                                        <option value="therapeutic" {{ $surgery->surgery_type_detail == 'therapeutic' ? 'selected' : '' }}>علاجية</option>
                                                        <option value="preventive" {{ $surgery->surgery_type_detail == 'preventive' ? 'selected' : '' }}>وقائية</option>
                                                        <option value="cosmetic" {{ $surgery->surgery_type_detail == 'cosmetic' ? 'selected' : '' }}>تجميلية</option>
                                                        <option value="reconstructive" {{ $surgery->surgery_type_detail == 'reconstructive' ? 'selected' : '' }}>ترميمية</option>
                                                        <option value="palliative" {{ $surgery->surgery_type_detail == 'palliative' ? 'selected' : '' }}>تلطيفية</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="anesthesia_position" class="form-label fw-bold">
                                                        <i class="fas fa-bed text-secondary me-1"></i>
                                                        وضعية التخدير
                                                    </label>
                                                    <select class="form-select" id="anesthesia_position" name="anesthesia_position">
                                                        <option value="">اختر الوضعية</option>
                                                        <option value="supine" {{ $surgery->anesthesia_position == 'supine' ? 'selected' : '' }}>استلقاء على الظهر</option>
                                                        <option value="prone" {{ $surgery->anesthesia_position == 'prone' ? 'selected' : '' }}>استلقاء على البطن</option>
                                                        <option value="lateral" {{ $surgery->anesthesia_position == 'lateral' ? 'selected' : '' }}>جانبية</option>
                                                        <option value="lithotomy" {{ $surgery->anesthesia_position == 'lithotomy' ? 'selected' : '' }}>ليثوتومي</option>
                                                        <option value="fowler" {{ $surgery->anesthesia_position == 'fowler' ? 'selected' : '' }}>فاولر</option>
                                                        <option value="trendelenburg" {{ $surgery->anesthesia_position == 'trendelenburg' ? 'selected' : '' }}>تريندلنبرغ</option>
                                                        <option value="sitting" {{ $surgery->anesthesia_position == 'sitting' ? 'selected' : '' }}>جلوس</option>
                                                        <option value="other" {{ $surgery->anesthesia_position == 'other' ? 'selected' : '' }}>أخرى</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="asa_classification" class="form-label fw-bold">
                                                        <i class="fas fa-heartbeat text-danger me-1"></i>
                                                        تصنيف ASA
                                                    </label>
                                                    <select class="form-select" id="asa_classification" name="asa_classification">
                                                        <option value="">اختر التصنيف</option>
                                                        <option value="asa1" {{ $surgery->asa_classification == 'asa1' ? 'selected' : '' }}>ASA 1 - مريض سليم</option>
                                                        <option value="asa2" {{ $surgery->asa_classification == 'asa2' ? 'selected' : '' }}>ASA 2 - مرض خفيف</option>
                                                        <option value="asa3" {{ $surgery->asa_classification == 'asa3' ? 'selected' : '' }}>ASA 3 - مرض شديد</option>
                                                        <option value="asa4" {{ $surgery->asa_classification == 'asa4' ? 'selected' : '' }}>ASA 4 - مرض شديد يهدد الحياة</option>
                                                        <option value="asa5" {{ $surgery->asa_classification == 'asa5' ? 'selected' : '' }}>ASA 5 - مريض ميت لا يُتوقع البقاء</option>
                                                        <option value="asa6" {{ $surgery->asa_classification == 'asa6' ? 'selected' : '' }}>ASA 6 - مريض تم إيقاف قلبه</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="surgical_complexity" class="form-label fw-bold">
                                                        <i class="fas fa-cogs text-primary me-1"></i>
                                                        تعقيد العملية
                                                    </label>
                                                    <select class="form-select" id="surgical_complexity" name="surgical_complexity">
                                                        <option value="">اختر التعقيد</option>
                                                        <option value="minor" {{ $surgery->surgical_complexity == 'minor' ? 'selected' : '' }}>بسيطة</option>
                                                        <option value="intermediate" {{ $surgery->surgical_complexity == 'intermediate' ? 'selected' : '' }}>متوسطة</option>
                                                        <option value="major" {{ $surgery->surgical_complexity == 'major' ? 'selected' : '' }}>كبرى</option>
                                                        <option value="complex" {{ $surgery->surgical_complexity == 'complex' ? 'selected' : '' }}>معقدة</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="surgical_notes" class="form-label fw-bold">
                                                <i class="fas fa-notes-medical text-primary me-1"></i>
                                                ملاحظات جراحية
                                            </label>
                                            <textarea class="form-control" id="surgical_notes" name="surgical_notes" rows="3"
                                                      placeholder="ملاحظات حول العملية الجراحية...">{{ $surgery->surgical_notes }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Post-Op Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="postOpHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#postOpCollapse" aria-expanded="false"
                                            aria-controls="postOpCollapse">
                                        <i class="fas fa-heart me-2 text-danger"></i>
                                        ما بعد العملية
                                    </button>
                                </h2>
                                <div id="postOpCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="postOpHeading">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label for="post_op_notes" class="form-label fw-bold">
                                                <i class="fas fa-file-medical text-danger me-1"></i>
                                                ملاحظات ما بعد العملية
                                            </label>
                                            <textarea class="form-control" id="post_op_notes" name="post_op_notes" rows="4"
                                                      placeholder="ملاحظات حول فترة ما بعد العملية...">{{ $surgery->post_op_notes }}</textarea>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="follow_up_date" class="form-label fw-bold">
                                                        <i class="fas fa-calendar-check text-success me-1"></i>
                                                        موعد المتابعة
                                                    </label>
                                                    <input type="date" class="form-control" id="follow_up_date" name="follow_up_date"
                                                           value="{{ $surgery->follow_up_date }}" min="{{ date('Y-m-d') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="notes" class="form-label fw-bold">
                                                        <i class="fas fa-sticky-note text-warning me-1"></i>
                                                        ملاحظات إضافية
                                                    </label>
                                                    <textarea class="form-control" id="notes" name="notes" rows="2"
                                                              placeholder="ملاحظات إضافية...">{{ $surgery->notes }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Treatment Plan Section -->
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="treatmentHeading">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#treatmentCollapse" aria-expanded="false"
                                            aria-controls="treatmentCollapse">
                                        <i class="fas fa-pills me-2 text-success"></i>
                                        خطة العلاج والأدوية
                                    </button>
                                </h2>
                                <div id="treatmentCollapse" class="accordion-collapse collapse"
                                     aria-labelledby="treatmentHeading">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label for="treatment_plan" class="form-label fw-bold">
                                                <i class="fas fa-clipboard-list text-success me-1"></i>
                                                خطة العلاج
                                            </label>
                                            <textarea class="form-control" id="treatment_plan" name="treatment_plan" rows="4"
                                                      placeholder="خطة العلاج والرعاية المطلوبة...">{{ $surgery->treatment_plan }}</textarea>
                                        </div>

                                        <!-- Medications Section -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-pills text-primary me-1"></i>
                                                الأدوية الموصوفة
                                            </label>
                                            <div id="medicationsContainer">
                                                @if($surgery->prescribed_medications && is_array($surgery->prescribed_medications) && isset($surgery->prescribed_medications['medications']))
                                                    @foreach($surgery->prescribed_medications['medications'] as $index => $medication)
                                                    <div class="medication-item card mb-3 border-success">
                                                        <div class="card-body">
                                                            <div class="row">
                                                                <div class="col-md-3">
                                                                    <label class="form-label">اسم الدواء</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][name]"
                                                                           value="{{ $medication['name'] ?? '' }}" placeholder="اسم الدواء أو اختر من القائمة" list="commonMedications">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">الجرعة</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][dosage]"
                                                                           value="{{ $medication['dosage'] ?? '' }}" placeholder="مثال: 500mg">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">التوقيت</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][timing]"
                                                                           value="{{ $medication['timing'] ?? '' }}" placeholder="مثال: مرتين يومياً">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">المدة</label>
                                                                    <input type="number" class="form-control" name="prescribed_medications[medications][{{ $index }}][duration]"
                                                                           value="{{ $medication['duration'] ?? '' }}" placeholder="عدد الأيام" min="1">
                                                                </div>
                                                                <div class="col-md-2">
                                                                    <label class="form-label">الملاحظات</label>
                                                                    <input type="text" class="form-control" name="prescribed_medications[medications][{{ $index }}][notes]"
                                                                           value="{{ $medication['notes'] ?? '' }}" placeholder="ملاحظات إضافية">
                                                                </div>
                                                                <div class="col-md-1 d-flex align-items-end">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeMedication(this)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <button type="button" class="btn btn-outline-success btn-sm" onclick="addMedication()">
                                                <i class="fas fa-plus me-1"></i>إضافة دواء
                                            </button>
                                        </div>

                                        <!-- Surgery Treatments Section -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">
                                                <i class="fas fa-procedures text-warning me-1"></i>
                                                علاجات العملية
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table table-bordered" id="surgeryTreatmentsTable">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>اسم العلاج</th>
                                                            <th>الجرعة</th>
                                                            <th>التوقيت</th>
                                                            <th>المدة</th>
                                                            <th>الوحدة</th>
                                                            <th>الإجراءات</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="surgeryTreatmentsContainer">
                                                        @if($surgery->surgeryTreatments && $surgery->surgeryTreatments->count() > 0)
                                                            @foreach($surgery->surgeryTreatments->sortBy('sort_order') as $index => $treatment)
                                                            <tr class="treatment-item">
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]"
                                                                           value="{{ $treatment->description }}" placeholder="وصف العلاج">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]"
                                                                           value="{{ $treatment->dosage }}" placeholder="الجرعة">
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]"
                                                                           value="{{ $treatment->timing }}" placeholder="التوقيت">
                                                                </td>
                                                                <td>
                                                                    <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]"
                                                                           value="{{ $treatment->duration_value }}" placeholder="القيمة" min="1">
                                                                </td>
                                                                <td>
                                                                    <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_unit]">
                                                                        <option value="days" {{ $treatment->duration_unit == 'days' ? 'selected' : '' }}>يوم</option>
                                                                        <option value="weeks" {{ $treatment->duration_unit == 'weeks' ? 'selected' : '' }}>أسبوع</option>
                                                                        <option value="months" {{ $treatment->duration_unit == 'months' ? 'selected' : '' }}>شهر</option>
                                                                        <option value="hours" {{ $treatment->duration_unit == 'hours' ? 'selected' : '' }}>ساعة</option>
                                                                        <option value="doses" {{ $treatment->duration_unit == 'doses' ? 'selected' : '' }}>جرعة</option>
                                                                    </select>
                                                                </td>
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                            @endforeach
                                                        @else
                                                            <tr id="emptySurgeryTreatmentsRow">
                                                                <td colspan="6" class="text-center py-4 text-muted">
                                                                    <i class="fas fa-table fa-2x mb-2"></i>
                                                                    <p>لا توجد علاجات محددة للعملية</p>
                                                                    <small>اضغط على "إضافة علاج" لبدء إضافة علاجات العملية</small>
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                            <button type="button" class="btn btn-outline-warning btn-sm" onclick="addSurgeryTreatment()">
                                                <i class="fas fa-plus me-1"></i>إضافة علاج
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>إلغاء
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>حفظ التفاصيل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Common Medications DataList -->
<datalist id="commonMedications">
    <option value="أموكسيسيلين (Amoxicillin)">
    <option value="أزيثروميسين (Azithromycin)">
    <option value="أموكسيكلاف (Amoxicillin-Clavulanate)">
    <option value="سيفالكسين (Cephalexin)">
    <option value="سيفازولين (Cefazolin)">
    <option value="ميترونيدازول (Metronidazole)">
    <option value="سيبروفلوكساسين (Ciprofloxacin)">
    <option value="تريميثوبريم-سلفاميثوكسازول (Trimethoprim-Sulfamethoxazole)">
    <option value="إيبوبروفين (Ibuprofen)">
    <option value="باراسيتامول (Paracetamol)">
    <option value="ديكلوفيناك (Diclofenac)">
    <option value="ترامادول (Tramadol)">
    <option value="مورفين (Morphine)">
    <option value="أسبرين (Aspirin)">
    <option value="وارفارين (Warfarin)">
    <option value="إنسولين (Insulin)">
    <option value="ميتفورمين (Metformin)">
    <option value="أتورفاستاتين (Atorvastatin)">
    <option value="لوسارتان (Losartan)">
    <option value="أملوديبين (Amlodipine)">
    <option value="فوروسيميد (Furosemide)">
    <option value="ديجوكسين (Digoxin)">
    <option value="بريدنيزون (Prednisone)">
    <option value="أوميبرازول (Omeprazole)">
    <option value="رانيتيدين (Ranitidine)">
    <option value="ألبرازولام (Alprazolam)">
    <option value="ديازيبام (Diazepam)">
    <option value="فلوكسيتين (Fluoxetine)">
    <option value="سيرترالين (Sertraline)">
    <option value="أميتريبتيلين (Amitriptyline)">
    <option value="كلونازيبام (Clonazepam)">
    <option value="فينيتوين (Phenytoin)">
    <option value="كاربامازيبين (Carbamazepine)">
    <option value="فالبروات (Valproate)">
    <option value="ليفوثيروكسين (Levothyroxine)">
    <option value="بروبيل ثيوراسيل (Propylthiouracil)">
    <option value="ميثيمازول (Methimazole)">
    <option value="هيبارين (Heparin)">
    <option value="إينوكسابارين (Enoxaparin)">
    <option value="كلوبيدوغريل (Clopidogrel)">
    <option value="تيكاغريلور (Ticagrelor)">
    <option value="ريفامبيسين (Rifampicin)">
    <option value="إيزونيازيد (Isoniazid)">
    <option value="إيثامبوتول (Ethambutol)">
    <option value="بيرازيناميد (Pyrazinamide)">
    <option value="فيتامين D">
    <option value="كالسيوم">
    <option value="حديد">
    <option value="فيتامين B12">
    <option value="فولات">
    <option value="زنك">
    <option value="مغنيسيوم">
    <option value="بوتاسيوم">
    <option value="صوديوم">
    <option value="كلوريد">
    <option value="بيكربونات">
</datalist>

<script>
window.addMedication = function() {
    const container = document.getElementById('medicationsContainer');
    const medicationIndex = container.querySelectorAll('.medication-item').length;
    const medicationHtml = `
        <div class="medication-item card mb-3 border-success">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">اسم الدواء</label>
                        <input type="text" class="form-control" name="prescribed_medications[medications][${medicationIndex}][name]"
                               placeholder="اسم الدواء أو اختر من القائمة" list="commonMedications">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الجرعة</label>
                        <input type="text" class="form-control" name="prescribed_medications[medications][${medicationIndex}][dosage]"
                               placeholder="مثال: 500mg">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">التوقيت</label>
                        <input type="text" class="form-control" name="prescribed_medications[medications][${medicationIndex}][timing]"
                               placeholder="مثال: مرتين يومياً">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">المدة</label>
                        <input type="number" class="form-control" name="prescribed_medications[medications][${medicationIndex}][duration]"
                               placeholder="عدد الأيام" min="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">الملاحظات</label>
                        <input type="text" class="form-control" name="prescribed_medications[medications][${medicationIndex}][notes]"
                               placeholder="ملاحظات إضافية">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeMedication(this)">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', medicationHtml);
};

window.addSurgeryTreatment = function() {
    const container = document.getElementById('surgeryTreatmentsContainer');
    const emptyRow = document.getElementById('emptySurgeryTreatmentsRow');
    if (!container) return;

    // Remove empty row if it exists
    if (emptyRow) {
        emptyRow.remove();
    }

    const treatmentIndex = container.querySelectorAll('.treatment-item').length;
    const treatmentHtml = `
        <tr class="treatment-item">
            <td>
                <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][description]"
                       placeholder="وصف العلاج">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][dosage]"
                       placeholder="الجرعة">
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][timing]"
                       placeholder="التوقيت">
            </td>
            <td>
                <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_value]"
                       placeholder="القيمة" min="1">
            </td>
            <td>
                <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_unit]">
                    <option value="days">يوم</option>
                    <option value="weeks">أسبوع</option>
                    <option value="months">شهر</option>
                    <option value="hours">ساعة</option>
                    <option value="doses">جرعة</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `;
    container.insertAdjacentHTML('beforeend', treatmentHtml);
};

window.removeMedication = function(button) {
    button.closest('.medication-item').remove();
};

window.removeSurgeryTreatment = function(button) {
    const row = button.closest('.treatment-item');
    const container = row.parentElement;
    row.remove();

    // Re-number remaining rows - though we don't have row numbers in show.blade.php
    // Add empty row if no treatments left
    const remainingRows = container.querySelectorAll('.treatment-item');
    if (remainingRows.length === 0) {
        const emptyRowHtml = `
            <tr id="emptySurgeryTreatmentsRow">
                <td colspan="6" class="text-center py-4 text-muted">
                    <i class="fas fa-table fa-2x mb-2"></i>
                    <p>لا توجد علاجات محددة للعملية</p>
                    <small>اضغط على "إضافة علاج" لبدء إضافة علاجات العملية</small>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', emptyRowHtml);
    }
};

// Auto-calculate estimated duration
document.addEventListener('input', function(e) {
    if (e.target.name === 'start_time' || e.target.name === 'end_time') {
        const startTimeInput = document.querySelector('input[name="start_time"]');
        const endTimeInput = document.querySelector('input[name="end_time"]');
        const durationField = document.querySelector('input[name="estimated_duration"]');

        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        if (startTime && endTime && durationField) {
            // Parse times
            const start = new Date('1970-01-01T' + startTime + ':00');
            const end = new Date('1970-01-01T' + endTime + ':00');

            // Handle cases where end time is next day
            if (end < start) {
                end.setDate(end.getDate() + 1);
            }

            // Calculate difference in minutes
            const diffMs = end - start;
            const diffMins = Math.round(diffMs / 60000);

            // Convert to hours and minutes format
            const hours = Math.floor(diffMins / 60);
            const minutes = diffMins % 60;

            // Format as HH:MM
            const formattedDuration = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');

            // Set the duration
            durationField.value = formattedDuration;

            // Also store the total minutes in a hidden field for backend processing
            let hiddenMinutesField = document.querySelector('input[name="estimated_duration_minutes"]');
            if (!hiddenMinutesField) {
                hiddenMinutesField = document.createElement('input');
                hiddenMinutesField.type = 'hidden';
                hiddenMinutesField.name = 'estimated_duration_minutes';
                document.querySelector('form').appendChild(hiddenMinutesField);
            }
            hiddenMinutesField.value = diffMins;
        }
    }
});
</script>

@endsection
