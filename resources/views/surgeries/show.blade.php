@extends('layouts.app')

@section('styles')
<style>
    /* Styling for Premium Dashboard */
    .surgery-dashboard-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.06);
        background-color: #ffffff;
    }
    .surgery-card-header {
        background: linear-gradient(135deg, #1e3a8a, #3b82f6);
        color: #ffffff !important;
        border-radius: 12px 12px 0 0 !important;
        padding: 1.25rem 1.5rem;
    }
    .surgery-card-header h5 {
        color: #ffffff !important;
    }
    .surgery-sidebar-card {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
        background-color: #ffffff;
    }
    .sidebar-header-custom {
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 1rem 1.25rem;
        border-radius: 12px 12px 0 0;
    }
    .nav-pills .nav-link {
        color: #4b5563;
        font-weight: 600;
        padding: 0.8rem 1.25rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        margin-bottom: 0.5rem;
        border: 1px solid transparent;
    }
    .nav-pills .nav-link.active {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        color: white;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
    }
    .nav-pills .nav-link:hover:not(.active) {
        background-color: #f1f5f9;
        color: #1e293b;
        border-color: #e2e8f0;
    }
    .patient-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background-color: #eff6ff;
        color: #2563eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1rem;
        border: 3px solid #dbeafe;
        box-shadow: 0 4px 10px rgba(37, 99, 235, 0.08);
    }
    .duration-input-group {
        display: flex;
        gap: 4px;
        align-items: center;
    }
    .timing-textarea {
        min-height: 60px;
        resize: vertical;
    }
    .form-label {
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 0.45rem;
        font-size: 0.875rem;
    }
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #cbd5e1;
        padding: 0.6rem 0.75rem;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }
    /* Info items in sidebar */
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    .info-item:last-child {
        border-bottom: none;
    }
</style>
@endsection

@section('content')
@php
    $canManageSurgery = auth()->user()->hasRole(['surgery_staff', 'admin']) || 
                       (auth()->user()->isDoctor() && auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id);
    $isSurgeryStaff = auth()->user()->hasRole('surgery_staff');
    $teamMissing = $isSurgeryStaff && empty($surgery->anesthesiologist_id) && empty($surgery->anesthesiaStation?->anesthesiologist_id);
    $timingMissing = $isSurgeryStaff && empty($surgery->start_time);
    $suppliesMissing = $isSurgeryStaff && empty($surgery->supplies);
    $anesthesiaMissing = $isSurgeryStaff && empty($surgery->anesthesiaStation?->anesthesia_type) && empty($surgery->anesthesia_type);
@endphp

<div class="container-fluid py-4">
    <!-- Header Page Banner -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-6 col-lg-7">
            <h2 class="mb-1 text-dark fw-bold d-flex align-items-center flex-wrap gap-2">
                <i class="fas fa-procedures text-primary me-2"></i>
                تفاصيل العملية: {{ $surgery->surgery_type }}
                @if($surgery->status == 'scheduled')
                    <span class="badge bg-secondary fs-6 px-3 py-2"><i class="fas fa-calendar-alt me-1"></i>مجدولة</span>
                @elseif($surgery->status == 'waiting')
                    <span class="badge bg-info text-dark fs-6 px-3 py-2"><i class="fas fa-clock me-1"></i>في الانتظار</span>
                @elseif($surgery->status == 'in_progress')
                    <span class="badge bg-warning fs-6 px-3 py-2"><i class="fas fa-spinner fa-spin me-1"></i>جرية الآن</span>
                @elseif($surgery->status == 'completed')
                    <span class="badge bg-success fs-6 px-3 py-2"><i class="fas fa-check-circle me-1"></i>مكتملة</span>
                @else
                    <span class="badge bg-danger fs-6 px-3 py-2"><i class="fas fa-times-circle me-1"></i>ملغاة</span>
                @endif
            </h2>
            <p class="text-muted mb-0">
                المريض: <strong>{{ optional(optional($surgery->patient)->user)->name ?? 'غير محدد' }}</strong> | 
                تاريخ الجدولة: {{ $surgery->scheduled_date->format('Y-m-d') }}
            </p>
        </div>
        <div class="col-md-6 col-lg-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end align-items-center gap-2 flex-wrap">
            @if(auth()->user()->hasRole(['admin', 'surgery_staff', 'receptionist']))
                <!-- Start Surgery Button -->
                @if($surgery->status == 'scheduled')
                <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline mb-0">
                    @csrf
                    <button type="submit" class="btn btn-warning text-white" onclick="return confirm('هل تريد بدء العمل الجراحي الفعلي الآن؟')">
                        <i class="fas fa-play me-1"></i>بدء العملية
                    </button>
                </form>
                @endif

                <!-- Complete Surgery Button -->
                @if($surgery->status == 'in_progress')
                <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline mb-0">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('هل تود نقل حالة العملية الجراحية إلى مكتملة؟')">
                        <i class="fas fa-check-double me-1"></i>إكمال العملية
                    </button>
                </form>
                @endif

                <!-- Return to waiting list -->
                @if($surgery->status == 'in_progress')
                <form action="{{ route('surgeries.return-to-waiting', $surgery) }}" method="POST" class="d-inline mb-0">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('هل تود إعادة المريض لقائمة الانتظار؟')">
                        <i class="fas fa-history me-1"></i>إعادة للانتظار
                    </button>
                </form>
                @endif
            @endif

            <a href="{{ url()->previous() ?? route('surgeries.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-right me-1"></i>العودة
            </a>
        </div>
    </div>

    <!-- Surgery Stations Flow Notification Banners -->
    @php
        $user = auth()->user();
    @endphp

    @if($surgery->status === 'in_progress')
        <!-- 1. Operation Theater Station -->
        @if(!$surgery->operationTheaterStation || $surgery->operationTheaterStation->status !== 'completed')
            @if($user->can('view operation theater station'))
                <div class="alert alert-info border-info shadow-sm d-flex align-items-center justify-content-between mb-4" role="alert">
                    <div>
                        <i class="fas fa-clinic-medical fa-lg me-2 text-info"></i>
                        <strong>محطة صالة العمليات:</strong> المريض داخل صالة العمليات حالياً وبانتظار إنهاء هذه المرحلة.
                    </div>
                    <a href="{{ route('operation-theater-station.show', $surgery) }}" class="btn btn-info text-dark btn-sm fw-bold">
                        <i class="fas fa-edit me-1"></i>الانتقال لمحطة صالة العمليات
                    </a>
                </div>
            @endif
        <!-- 3. Surgeon Station -->
        @elseif(!$surgery->surgeonStation || $surgery->surgeonStation->status !== 'completed')
            @if($user->can('view surgeon station'))
                <div class="alert alert-primary border-primary shadow-sm d-flex align-items-center mb-4" role="alert">
                    <div>
                        <i class="fas fa-user-md fa-lg me-2 text-primary"></i>
                        <strong>محطة الطبيب الجراح:</strong> يرجى إدخال تفاصيل العملية (التشخيص والعلاجات) وحفظ البيانات في الأسفل لإتمام محطة الجراح ونقلها لمحطة التخدير تلقائياً.
                    </div>
                </div>
            @endif
        <!-- 4. Anesthesia Station -->
        @elseif(!$surgery->anesthesiaStation || $surgery->anesthesiaStation->status !== 'completed')
            @if($user->can('view anesthesia station'))
                <div class="alert alert-success border-success shadow-sm d-flex align-items-center mb-4" role="alert">
                    <div>
                        <i class="fas fa-syringe fa-lg me-2 text-success"></i>
                        <strong>محطة التخدير:</strong> العملية بانتظار توثيق طبيب التخدير ونوع التخدير المستخدم.
                    </div>
                </div>
            @endif
        <!-- 5. Nursing Station -->
        @elseif(!$surgery->nursingStation || $surgery->nursingStation->status !== 'completed')
            @if($user->can('view nursing station'))
                <div class="alert alert-info border-info shadow-sm d-flex align-items-center justify-content-between mb-4" role="alert">
                    <div>
                        <i class="fas fa-user-nurse fa-lg me-2 text-info"></i>
                        <strong>محطة التمريض:</strong> المريض في مرحلة الملاحظة التمريضية النهائية قبل الخروج.
                    </div>
                    <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-info text-dark btn-sm fw-bold">
                        <i class="fas fa-edit me-1"></i>الانتقال لمحطة التمريض وإخراج المريض
                    </a>
                </div>
            @endif
        @endif
    @endif

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- Main Form Column (12/12 grid) -->
        <div class="col-lg-12 mb-4">
            <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST" id="surgeryDetailsForm">
                @csrf
                @method('PATCH')

                <div class="card surgery-dashboard-card mb-4">
                    <div class="card-header surgery-card-header py-3">
                        <div class="row align-items-center">
                            <div class="col-sm-8">
                                <h5 class="mb-0 fw-bold">
                                    <i class="fas fa-edit me-2"></i>تقرير العملية الجراحية والتفاصيل الطبية
                                </h5>
                            </div>
                            <div class="col-sm-4 text-sm-end mt-2 mt-sm-0">
                                <span class="badge bg-light text-dark font-monospace">ID: #{{ $surgery->id }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row">
                            <!-- Left Sidebar Navigation inside Card (Tab Pills) -->
                            <div class="col-md-3 mb-4 mb-md-0 border-end">
                                <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                    <button class="nav-link active text-end" id="v-pills-visit-tests-tab" data-bs-toggle="pill" data-bs-target="#v-pills-visit-tests" type="button" role="tab">
                                        التحاليل والفحوصات الطبية<i class="fas fa-flask ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end" id="v-pills-type-tab" data-bs-toggle="pill" data-bs-target="#v-pills-type" type="button" role="tab">
                                        نوع العملية<i class="fas fa-scalpel ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end @if(auth()->user()->hasRole('surgery_staff') && empty($surgery->diagnosis)) border-danger @endif" id="v-pills-diagnosis-tab" data-bs-toggle="pill" data-bs-target="#v-pills-diagnosis" type="button" role="tab">
                                        التشخيص الطبي للجراح
                                        @if(auth()->user()->hasRole('surgery_staff') && empty($surgery->diagnosis))
                                            <span class="badge bg-danger text-white ms-1"><i class="fas fa-exclamation-triangle"></i></span>
                                        @endif
                                        <i class="fas fa-stethoscope ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end @if($anesthesiaMissing) border-danger @endif" id="v-pills-anesthesia-tab" data-bs-toggle="pill" data-bs-target="#v-pills-anesthesia" type="button" role="tab">
                                        تفاصيل التخدير
                                        @if($anesthesiaMissing) <span class="badge bg-danger text-white ms-1"><i class="fas fa-exclamation-triangle"></i></span> @endif
                                        <i class="fas fa-syringe ms-2"></i>
                                    </button>
                                    @if(auth()->user()->hasRole(['admin', 'surgery_staff']))
                                    <button class="nav-link text-end @if($teamMissing) border-warning @endif" id="v-pills-team-tab" data-bs-toggle="pill" data-bs-target="#v-pills-team" type="button" role="tab">
                                        الفريق الطبي
                                        @if($teamMissing) <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i></span> @endif
                                        <i class="fas fa-users ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end @if($timingMissing) border-warning @endif" id="v-pills-timing-tab" data-bs-toggle="pill" data-bs-target="#v-pills-timing" type="button" role="tab">
                                        التوقيت والمدة
                                        @if($timingMissing) <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i></span> @endif
                                        <i class="fas fa-clock ms-2"></i>
                                    </button>
                                    @endif
                                    <button class="nav-link text-end" id="v-pills-treatments-tab" data-bs-toggle="pill" data-bs-target="#v-pills-treatments" type="button" role="tab">
                                        جدول العلاجات<i class="fas fa-pills ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end" id="v-pills-fluids-tab" data-bs-toggle="pill" data-bs-target="#v-pills-fluids" type="button" role="tab">
                                        مكونات السوائل المطلوبة<i class="fas fa-tint ms-2"></i>
                                    </button>
                                    <button class="nav-link text-end" id="v-pills-followups-tab" data-bs-toggle="pill" data-bs-target="#v-pills-followups" type="button" role="tab">
                                        المتابعات<i class="fas fa-clipboard-list ms-2"></i>
                                    </button>
                                    @if(auth()->user()->hasRole(['admin', 'surgery_staff']))
                                    <button class="nav-link text-end @if($suppliesMissing) border-warning @endif" id="v-pills-notes-tab" data-bs-toggle="pill" data-bs-target="#v-pills-notes" type="button" role="tab">
                                        المستلزمات
                                        @if($suppliesMissing) <span class="badge bg-warning text-dark ms-1"><i class="fas fa-exclamation-triangle"></i></span> @endif
                                        <i class="fas fa-box-open ms-2"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Right Tab Contents -->
                            <div class="col-md-9 px-md-4">
                                <div class="tab-content" id="v-pills-tabContent">
                                    
                                    <!-- 0. Tests and Scans Tab -->
                                    <div class="tab-pane fade show active" id="v-pills-visit-tests" role="tabpanel">

                                        <!-- Stats Summary Row -->
                                        <div class="row g-3 mb-4">
                                            <div class="col-6 col-md-3">
                                                <div class="rounded-3 p-3 text-center h-100" style="background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;">
                                                    <div class="fw-bold fs-4 text-primary">{{ $surgery->labTests->count() }}</div>
                                                    <div class="small text-muted mt-1"><i class="fas fa-vial me-1"></i>تحليل مخبري</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="rounded-3 p-3 text-center h-100" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;">
                                                    <div class="fw-bold fs-4 text-success">{{ $surgery->radiologyTests->count() }}</div>
                                                    <div class="small text-muted mt-1"><i class="fas fa-x-ray me-1"></i>طلب أشعة</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="rounded-3 p-3 text-center h-100" style="background:linear-gradient(135deg,#fffbeb,#fef3c7);border:1px solid #fde68a;">
                                                    <div class="fw-bold fs-4 text-warning">{{ $surgery->labTests->where('status','pending')->count() + $surgery->radiologyTests->where('status','pending')->count() }}</div>
                                                    <div class="small text-muted mt-1"><i class="fas fa-hourglass-half me-1"></i>بانتظار النتيجة</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="rounded-3 p-3 text-center h-100" style="background:linear-gradient(135deg,#fdf4ff,#f3e8ff);border:1px solid #e9d5ff;">
                                                    <div class="fw-bold fs-4" style="color:#7c3aed;">{{ $surgery->labTests->where('status','completed')->count() + $surgery->radiologyTests->where('status','completed')->count() }}</div>
                                                    <div class="small text-muted mt-1"><i class="fas fa-check-circle me-1"></i>مكتمل</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tables Grid -->
                                        <div class="row g-4">

                                            <!-- LEFT: Required Surgery Tests -->
                                            <div class="col-lg-6">
                                                <p class="fw-bold text-primary mb-2 pb-1 border-bottom">
                                                    <i class="fas fa-flask me-2"></i>التحاليل والفحوصات المطلوبة للعملية
                                                </p>

                                                <!-- Lab Tests Table -->
                                                <div class="mb-4">
                                                    <div class="table-responsive rounded-3 border" style="border-color:#bfdbfe !important;">
                                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                                            <thead style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-primary fw-bold border-0"><i class="fas fa-vial me-1"></i>التحاليل المخبرية المطلوبة</th>
                                                                    <th class="py-2 text-muted fw-semibold border-0">النتيجة</th>
                                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                                    <th class="py-2 border-0"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($surgery->labTests as $labTest)
                                                                <tr>
                                                                    <td class="px-3 fw-semibold text-dark">{{ optional($labTest->labTest)->name ?? 'تحليل طبي' }}</td>
                                                                    <td>
                                                                        @if($labTest->result)
                                                                            <strong class="text-dark">{{ $labTest->result }}</strong>
                                                                        @else
                                                                            <span class="text-muted small">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-{{ $labTest->status_color }}">{{ $labTest->status_text }}</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($labTest->result_file)
                                                                            <a href="{{ asset('storage/' . $labTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;">
                                                                                <i class="fas fa-download"></i>
                                                                            </a>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                @empty
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted py-3">
                                                                        <i class="fas fa-vial me-1 opacity-50"></i>لا توجد تحاليل مخبرية مخصصة للعملية
                                                                    </td>
                                                                </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>

                                                <!-- Radiology Table -->
                                                <div>
                                                    <div class="table-responsive rounded-3 border" style="border-color:#bbf7d0 !important;">
                                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                                            <thead style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-success fw-bold border-0"><i class="fas fa-x-ray me-1"></i>الأشعة والتصوير المطلوب</th>
                                                                    <th class="py-2 text-muted fw-semibold border-0">التقرير</th>
                                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                                    <th class="py-2 border-0"></th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @forelse($surgery->radiologyTests as $radTest)
                                                                <tr>
                                                                    <td class="px-3 fw-semibold text-dark">{{ optional($radTest->radiologyType)->name ?? 'تصوير طبي' }}</td>
                                                                    <td>
                                                                        @if($radTest->result)
                                                                            <span class="text-dark small">{{ $radTest->result }}</span>
                                                                        @else
                                                                            <span class="text-muted small">—</span>
                                                                        @endif
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-{{ $radTest->status_color }}">{{ $radTest->status_text }}</span>
                                                                    </td>
                                                                    <td>
                                                                        @if($radTest->result_file)
                                                                            <a href="{{ asset('storage/' . $radTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                                                                <i class="fas fa-download"></i>
                                                                            </a>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                                @empty
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted py-3">
                                                                        <i class="fas fa-x-ray me-1 opacity-50"></i>لا توجد طلبات أشعة وتصوير للعملية
                                                                    </td>
                                                                </tr>
                                                                @endforelse
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- RIGHT: Previous Visit Tests & Scans -->
                                            <div class="col-lg-6">
                                                <p class="fw-bold text-success mb-2 pb-1 border-bottom">
                                                    <i class="fas fa-history me-2"></i>الفحوصات والتحاليل السابقة بالزيارة
                                                </p>

                                                @if($surgery->visit)
                                                    <!-- Visit Lab Results Table -->
                                                    <div class="mb-4">
                                                        <div class="table-responsive rounded-3 border" style="border-color:#e9d5ff !important;">
                                                            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                                                <thead style="background:linear-gradient(135deg,#fdf4ff,#f3e8ff);">
                                                                    <tr>
                                                                        <th class="px-3 py-2 fw-bold border-0" style="color:#7c3aed;"><i class="fas fa-vials me-1"></i>نتائج المختبر بالزيارة</th>
                                                                        <th class="py-2 text-muted fw-semibold border-0">النتيجة</th>
                                                                        <th class="py-2 text-muted fw-semibold border-0">الوحدة</th>
                                                                        <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @forelse($surgery->visit->getLatestLabResults() as $labRes)
                                                                    <tr class="{{ $labRes->is_abnormal ? 'table-danger' : '' }}">
                                                                        <td class="px-3 fw-semibold text-dark">{{ $labRes->test_name }}</td>
                                                                        <td><strong>{{ $labRes->result }}</strong></td>
                                                                        <td class="text-muted small">{{ $labRes->unit }}</td>
                                                                        <td class="text-center">
                                                                            @if($labRes->is_abnormal)
                                                                                <span class="badge bg-danger">غير طبيعي</span>
                                                                            @else
                                                                                <span class="badge bg-success">طبيعي</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                    @empty
                                                                    <tr>
                                                                        <td colspan="4" class="text-center text-muted py-3">
                                                                            <i class="fas fa-vials me-1 opacity-50"></i>لا توجد نتائج مختبر للزيارة
                                                                        </td>
                                                                    </tr>
                                                                    @endforelse
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>

                                                    <!-- Visit Radiology Table -->
                                                    <div>
                                                        <div class="table-responsive rounded-3 border" style="border-color:#fde68a !important;">
                                                            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                                                <thead style="background:linear-gradient(135deg,#fffbeb,#fef3c7);">
                                                                    <tr>
                                                                        <th class="px-3 py-2 text-warning fw-bold border-0"><i class="fas fa-image me-1"></i>تقارير الأشعة بالزيارة</th>
                                                                        <th class="py-2 text-muted fw-semibold border-0">التقرير / الملاحظات</th>
                                                                        <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @forelse($surgery->visit->radiologyRequests as $radReq)
                                                                    <tr>
                                                                        <td class="px-3 fw-semibold text-dark">{{ optional($radReq->radiologyType)->name ?? '—' }}</td>
                                                                        <td class="text-muted small" style="max-width:200px;">
                                                                            @if($radReq->result)
                                                                                {{ $radReq->result->findings }}
                                                                            @else
                                                                                <i class="fas fa-hourglass-half me-1"></i>بانتظار التقرير...
                                                                            @endif
                                                                        </td>
                                                                        <td class="text-center">
                                                                            <span class="badge bg-{{ $radReq->status_color }}">{{ $radReq->status_text }}</span>
                                                                        </td>
                                                                    </tr>
                                                                    @empty
                                                                    <tr>
                                                                        <td colspan="3" class="text-center text-muted py-3">
                                                                            <i class="fas fa-image me-1 opacity-50"></i>لا توجد طلبات أشعة للزيارة
                                                                        </td>
                                                                    </tr>
                                                                    @endforelse
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="alert alert-info text-center py-4 mb-0 rounded-3">
                                                        <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                                        لا توجد زيارة سابقة مرتبطة بهذه العملية.
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 1. Surgery Type Tab -->
                                    <div class="tab-pane fade" id="v-pills-type" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-scalpel me-2"></i>نوع العملية
                                        </h6>

                                        @if(auth()->user()->hasRole(['admin', 'surgery_staff', 'inquiry_staff']))
                                        <form action="{{ route('surgeries.updateSurgeryType', $surgery) }}" method="POST" class="mb-4">
                                            @csrf
                                            @method('PATCH')
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold text-primary">نوع العملية الحالي</label>
                                                    <div class="input-group">
                                                        <input type="text" name="surgery_type" class="form-control form-control-lg border-primary" value="{{ $surgery->surgery_type }}" required>
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fas fa-save me-1"></i>حفظ
                                                        </button>
                                                    </div>
                                                </div>
                                                @if($surgery->previous_surgery_type)
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted">
                                                        <i class="fas fa-history me-1"></i>النوع السابق
                                                    </label>
                                                    <div class="form-control bg-light text-muted" readonly>
                                                        <s>{{ $surgery->previous_surgery_type }}</s>
                                                    </div>
                                                </div>
                                                @endif
                                            </div>
                                        </form>
                                        @else
                                        <div class="row g-4 mb-4">
                                            <div class="col-md-6">
                                                <div class="card border border-primary-subtle bg-primary bg-opacity-10 rounded-3 p-3 h-100">
                                                    <small class="text-muted mb-1">نوع العملية</small>
                                                    <div class="fw-bold text-dark fs-5">{{ $surgery->surgery_type ?? 'غير محدد' }}</div>
                                                </div>
                                            </div>
                                            @if($surgery->previous_surgery_type)
                                            <div class="col-md-6">
                                                <div class="card border border-secondary-subtle bg-secondary bg-opacity-10 rounded-3 p-3 h-100">
                                                    <small class="text-muted mb-1"><i class="fas fa-history me-1"></i>النوع السابق</small>
                                                    <div class="fw-bold text-muted"><s>{{ $surgery->previous_surgery_type }}</s></div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        @endif

                                        <div class="row g-4">
                                            @if($surgery->surgery_type_detail)
                                            <div class="col-md-6">
                                                <div class="card border border-info-subtle bg-info bg-opacity-10 rounded-3 p-3 h-100">
                                                    <small class="text-muted mb-1">تفاصيل العملية</small>
                                                    <div class="fw-bold text-dark">{{ $surgery->surgery_type_detail }}</div>
                                                </div>
                                            </div>
                                            @endif
                                            @if($surgery->anesthesia_type)
                                            <div class="col-md-6">
                                                <div class="card border border-warning-subtle bg-warning bg-opacity-10 rounded-3 p-3 h-100">
                                                    <small class="text-muted mb-1">نوع التخدير</small>
                                                    <div class="fw-bold text-dark">{{ $surgery->anesthesia_type }}</div>
                                                </div>
                                            </div>
                                            @endif
                                            @php $protocol = $surgery->surgeonStation?->monitoring_protocol ?? null; @endphp
                                            @if($protocol)
                                            <div class="col-md-6">
                                                <div class="card border border-dark-subtle bg-dark bg-opacity-10 rounded-3 p-3 h-100">
                                                    <small class="text-muted mb-1"><i class="fas fa-chart-line me-1"></i>بروتوكول المراقبة</small>
                                                    <div class="fw-bold text-dark">
                                                        @switch($protocol)
                                                            @case('standard') قياسي (علامات حيوية فقط) @break
                                                            @case('fluid_monitoring') مراقبة السوائل (حيوية + سوائل) @break
                                                            @case('intensive') مكثف (حيوية + سوائل + متابعة دقيقة) @break
                                                        @endswitch
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- 2. Diagnosis & Anesthesia Tab -->
                                    <div class="tab-pane fade" id="v-pills-diagnosis" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-stethoscope me-2"></i>التشخيص الطبي للعملية
                                        </h6>
                                        @if(auth()->user()->hasRole('surgery_staff'))
                                            @if(empty($surgery->diagnosis))
                                            <div class="alert alert-warning border-warning text-dark bg-warning bg-opacity-10 mb-4" role="alert">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                <strong>لم يُدخل الجراح التشخيص بعد.</strong> يرجى التواصل مع الجراح لإدخال التشخيص الطبي للعملية.
                                            </div>
                                            @else
                                            <div class="alert alert-success border-success text-dark bg-success bg-opacity-10 mb-4" role="alert">
                                                <i class="fas fa-check-circle me-2"></i>
                                                تم إدخال التشخيص من قبل الجراح. يمكنك الاطلاع عليه أدناه.
                                            </div>
                                            @endif
                                        @elseif(auth()->user()->hasRole('admin'))
                                        <div class="alert alert-info border-info text-dark bg-info bg-opacity-10 mb-4" role="alert">
                                            <i class="fas fa-info-circle me-2"></i>
                                            يمكنك تعديل التشخيص الطبي للعملية أدناه.
                                        </div>
                                        @endif
                                        <div class="row">
                                            <div class="col-md-12 mb-3">
                                                <label for="diagnosis" class="form-label">التشخيص الطبي للعملية</label>
                                                @if(auth()->user()->hasRole('surgery_staff'))
                                                    <textarea class="form-control bg-light" id="diagnosis" rows="4" readonly placeholder="لم يُدخل الجراح التشخيص بعد...">{{ $surgery->diagnosis }}</textarea>
                                                    <small class="text-muted mt-1 d-block"><i class="fas fa-lock me-1"></i>عرض فقط — لا يمكنك تعديل التشخيص، يرجى التواصل مع الجراح.</small>
                                                @else
                                                    <textarea class="form-control" id="diagnosis" name="diagnosis" rows="4" placeholder="أدخل التشخيص الطبي المفصل للمريض...">{{ $surgery->diagnosis }}</textarea>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Anesthesia Tab -->
                                    <div class="tab-pane fade" id="v-pills-anesthesia" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-success d-flex justify-content-between align-items-center">
                                            <span><i class="fas fa-syringe me-2"></i>توثيق التخدير</span>
                                            @if(auth()->user()->isAnesthesia() || auth()->user()->isAdmin())
                                                <span class="badge bg-success small" style="font-size: 0.7rem;">وضع التعديل نشط</span>
                                            @endif
                                        </h6>

                                        @if(auth()->user()->hasRole('surgery_staff'))
                                        <div class="alert alert-secondary border-secondary bg-white mb-4">
                                            <i class="fas fa-eye me-2"></i>
                                            هذا القسم للعرض فقط لموظفي العمليات؛ لا يمكن تعديل تفاصيل التخدير هنا.
                                            لتعديل فريق التخدير استخدم تبويب "الفريق الطبي"، ولتعديل التوقيت والمدة استخدم تبويب "التوقيت والمدة".
                                        </div>
                                        @endif
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">طبيب التخدير الأول</label>
                                                <input type="text" class="form-control bg-light" value="{{ optional(optional($surgery->anesthesiaStation)->anesthesiologist?->user)->full_name ?? optional($surgery->anesthesiologist?->user)->full_name ?? 'غير محدد' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">طبيب التخدير المساعد</label>
                                                <input type="text" class="form-control bg-light" value="{{ optional(optional($surgery->anesthesiaStation)->anesthesiologist2?->user)->full_name ?? optional($surgery->anesthesiologist2?->user)->full_name ?? 'غير محدد' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">نوع التخدير</label>
                                                <input type="text" class="form-control bg-light" value="{{ $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? 'غير محدد' }}" readonly>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">حالة المرحلة</label>
                                                <input type="text" class="form-control bg-light" value="{{ $surgery->anesthesiaStation?->status == 'pending' ? 'معلقة' : ($surgery->anesthesiaStation?->status == 'in_progress' ? 'قيد العمل' : ($surgery->anesthesiaStation?->status == 'completed' ? 'مكتملة' : 'غير محدد')) }}" readonly>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label">ملاحظات طبيب التخدير</label>
                                                <div class="p-3 rounded border bg-light" style="min-height: 100px;">
                                                    {!! nl2br(e($surgery->anesthesiaStation?->notes ?? 'لا توجد ملاحظات مدونة حتى الآن.')) !!}
                                                </div>
                                            </div>
                                            @can('view anesthesia station')
                                                @if($surgery->anesthesiaStation && $surgery->anesthesiaStation->status !== 'completed')
                                                    <div class="col-12 text-end mb-2">
                                                        <button type="button" class="btn btn-success shadow-sm" onclick="completeAnesthesia({{ $surgery->id }})">
                                                            <i class="fas fa-save me-1"></i>حفظ بيانات التخدير
                                                        </button>
                                                    </div>
                                                @endif
                                            @endcan
                                        </div>
                                    </div>

                                    @if(auth()->user()->hasRole(['admin', 'surgery_staff']))
                                    <div class="tab-pane fade" id="v-pills-team" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-users me-2"></i>الفريق الطبي المشارك
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">طبيب التخدير الأول</label>
                                                <select name="anesthesiologist_id" class="form-select bg-light">
                                                    <option value="">اختر الطبيب المخدر الأول</option>
                                                    @foreach($anesthesiaDoctors as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ ((optional(optional($surgery->anesthesiaStation)->anesthesiologist)->id ?? $surgery->anesthesiologist_id) == $doctor->id) ? 'selected' : '' }}>
                                                            د. {{ $doctor->user->full_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">طبيب التخدير المساعد</label>
                                                <select name="anesthesiologist_2_id" class="form-select bg-light">
                                                    <option value="">اختر الطبيب المخدر المساعد</option>
                                                    @foreach($anesthesiaDoctors as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ ((optional(optional($surgery->anesthesiaStation)->anesthesiologist2)->id ?? $surgery->anesthesiologist_2_id) == $doctor->id) ? 'selected' : '' }}>
                                                            د. {{ $doctor->user->full_name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">اسم مساعد الجراح (خارجي أو ممرض مساعد)</label>
                                                <input type="text" name="surgical_assistant_name" class="form-control bg-light" value="{{ old('surgical_assistant_name', $surgery->anesthesiaStation?->surgical_assistant_name ?? $surgery->surgical_assistant_name ?? '') }}">
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3">
                                            <i class="fas fa-info-circle me-1"></i> لتعديل بيانات الفريق الطبي والتخدير، استخدم صفحة <a href="{{ route('anesthesia-station.show', $surgery) }}">محطة التخدير</a>.
                                        </div>
                                    </div>

                                    <!-- 3. Timing & Duration Tab -->
                                    <div class="tab-pane fade" id="v-pills-timing" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-clock me-2"></i>التوقيت الفعلي ومدة العملية
                                        </h6>
                                        <div class="row gy-3">
                                            <div class="col-md-4">
                                                <label for="start_time" class="form-label">وقت بدء العملية</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-play text-success"></i></span>
                                                    <input type="time" class="form-control border-start-0" id="start_time" name="start_time"
                                                           value="{{ $surgery->start_time ? (is_string($surgery->start_time) ? \Carbon\Carbon::parse($surgery->start_time)->format('H:i') : $surgery->start_time->format('H:i')) : ($surgery->started_at ? $surgery->started_at->format('H:i') : '') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="end_time" class="form-label">وقت انتهاء العملية</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-stop text-danger"></i></span>
                                                    <select class="form-select border-start-0" id="end_time" name="end_time">
                                                        <option value="">اختر وقت الانتهاء</option>
                                                        @for($hour = 6; $hour <= 20; $hour++)
                                                            @for($minute = 0; $minute < 60; $minute += 15)
                                                                @php $timeOption = sprintf('%02d:%02d', $hour, $minute); @endphp
                                                                <option value="{{ $timeOption }}" {{ ($surgery->end_time ? (is_string($surgery->end_time) ? \Carbon\Carbon::parse($surgery->end_time)->format('H:i') : $surgery->end_time->format('H:i')) : '') === $timeOption ? 'selected' : '' }}>{{ $timeOption }}</option>
                                                            @endfor
                                                        @endfor
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <label for="estimated_duration" class="form-label">المدة المستغرقة (س:د)</label>
                                                <div class="input-group">
                                                    <span class="input-group-text bg-light border-end-0"><i class="fas fa-hourglass-half text-primary"></i></span>
                                                    <input type="text" class="form-control bg-light fw-bold text-primary border-start-0" id="estimated_duration" name="estimated_duration"
                                                           value="{{ $surgery->estimated_duration ? sprintf('%02d:%02d', floor($surgery->estimated_duration / 60), $surgery->estimated_duration % 60) : '' }}"
                                                           readonly placeholder="سيتم حسابها تلقائياً">
                                                </div>
                                                <small class="text-muted">تُحسب تلقائياً بناءً على وقت البدء والانتهاء</small>
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    <!-- 4. Surgery Treatments Tab -->
                                    <div class="tab-pane fade" id="v-pills-treatments" role="tabpanel">
                                        @php $savedSurgeryTreatments = $surgery->surgeryTreatments ?? collect(); @endphp

                                        @if(auth()->user()->hasRole('surgery_staff'))
                                        {{-- موظف العمليات: عرض القراءة فقط --}}
                                        <div class="d-flex align-items-center border-bottom pb-2 mb-3">
                                            <h6 class="fw-bold text-primary mb-0">
                                                <i class="fas fa-pills me-2"></i>جدول علاج العمليات
                                            </h6>
                                            <span class="badge bg-secondary ms-2"><i class="fas fa-lock me-1"></i>للقراءة فقط</span>
                                        </div>
                                        <div class="alert alert-info border-info bg-info bg-opacity-10 py-2 mb-3">
                                            <i class="fas fa-info-circle me-1"></i>هذا الجدول يُعبأ من قِبَل الطبيب الجراح ولا يمكن تعديله.
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle" style="font-size:0.9rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" width="5%">#</th>
                                                        <th width="35%">وصف العلاج/الدواء</th>
                                                        <th width="20%">الجرعة</th>
                                                        <th width="25%">التوقيت/التكرار</th>
                                                        <th width="15%">المدة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse($savedSurgeryTreatments as $index => $treatment)
                                                    <tr>
                                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                                        <td class="fw-semibold text-dark">{{ $treatment->description ?? '—' }}</td>
                                                        <td class="text-muted">{{ $treatment->dosage ?? '—' }}</td>
                                                        <td class="text-muted">{{ $treatment->timing ?? '—' }}</td>
                                                        <td class="text-muted">
                                                            @if($treatment->duration_value)
                                                                {{ $treatment->duration_value }}
                                                                @php $units=['days'=>'يوم','weeks'=>'أسبوع','months'=>'شهر','hours'=>'ساعة','doses'=>'جرعة']; @endphp
                                                                {{ $units[$treatment->duration_unit] ?? '' }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-4 text-muted">
                                                            <i class="fas fa-pills fa-2x mb-2 d-block opacity-50"></i>
                                                            لم يُدخل الطبيب الجراح خطة العلاج بعد
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

                                        @else
                                        {{-- أدمن أو طبيب: عرض التحرير الكامل --}}
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                            <h6 class="fw-bold text-primary mb-0">
                                                <i class="fas fa-pills me-2"></i>جدول علاج العمليات
                                            </h6>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSurgeryTreatment()">
                                                <i class="fas fa-plus me-1"></i>إضافة علاج جديد
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle" id="surgeryTreatmentsTable">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="5%" class="text-center">#</th>
                                                        <th width="30%">وصف العلاج/الدواء</th>
                                                        <th width="20%">الجرعة</th>
                                                        <th width="20%">التوقيت/التكرار</th>
                                                        <th width="20%">المدة</th>
                                                        <th width="5%" class="text-center">حذف</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="surgeryTreatmentsContainer">
                                                    @foreach($savedSurgeryTreatments as $index => $treatment)
                                                    <tr class="treatment-item">
                                                        <td class="text-center row-number">{{ $index + 1 }}</td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]"
                                                                   value="{{ $treatment->description ?? '' }}" placeholder="اسم الدواء أو وصف العلاج" required>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]"
                                                                   value="{{ $treatment->dosage ?? '' }}" placeholder="مثال: 500mg, 2ml">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]" rows="1"
                                                                      placeholder="مثال: كل 6 ساعات">{{ $treatment->timing ?? '' }}</textarea>
                                                        </td>
                                                        <td>
                                                            <div class="duration-input-group">
                                                                <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]"
                                                                       value="{{ $treatment->duration_value ?? '' }}" placeholder="العدد" min="1">
                                                                <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_unit]">
                                                                    <option value="days" {{ ($treatment->duration_unit ?? 'days') == 'days' ? 'selected' : '' }}>يوم</option>
                                                                    <option value="weeks" {{ ($treatment->duration_unit ?? '') == 'weeks' ? 'selected' : '' }}>أسبوع</option>
                                                                    <option value="months" {{ ($treatment->duration_unit ?? '') == 'months' ? 'selected' : '' }}>شهر</option>
                                                                    <option value="hours" {{ ($treatment->duration_unit ?? '') == 'hours' ? 'selected' : '' }}>ساعة</option>
                                                                    <option value="doses" {{ ($treatment->duration_unit ?? '') == 'doses' ? 'selected' : '' }}>جرعة</option>
                                                                </select>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                    @if($savedSurgeryTreatments->isEmpty())
                                                    <tr id="emptySurgeryTreatmentsRow">
                                                        <td colspan="6" class="text-center py-4 text-muted">
                                                            <i class="fas fa-table fa-2x mb-2"></i>
                                                            <p class="mb-1">لا توجد علاجات مسجلة لهذه العملية</p>
                                                            <small>اضغط على "إضافة علاج" للبدء</small>
                                                        </td>
                                                    </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                        @endif
                                    </div>

                                    <!-- 5. Required Fluids Tab -->
                                    <div class="tab-pane fade" id="v-pills-fluids" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-tint me-2"></i>مكونات السوائل المطلوبة للمراقبة
                                        </h6>
                                        @php
                                            $requiredFluids = $surgery->surgeonStation?->required_fluids ?? [];
                                            $canEdit = auth()->user()->hasRole(['admin', 'surgery_staff', 'doctor', 'الجراح']) || 
                                                auth()->user()->hasPermissionTo('edit surgeries') ||
                                                (auth()->user()->doctor && auth()->user()->doctor->id == $surgery->doctor_id);
                                        @endphp
                                        @if($canEdit)
                                        <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div class="card border-0 shadow-sm">
                                                <div class="card-body p-4">
                                                    <p class="text-muted small mb-3"><i class="fas fa-info-circle me-1"></i>اختر مكونات السوائل المطلوبة لمراقبة المريض حسب نوع العملية:</p>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <div class="card border border-primary-subtle bg-primary bg-opacity-5 h-100">
                                                                <div class="card-header bg-transparent border-bottom border-primary-subtle py-2">
                                                                    <h6 class="fw-bold text-primary mb-0"><i class="fas fa-arrow-alt-circle-down me-1"></i>المدخلات (Intake)</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    @foreach([
                                                                        ['intake_iv_fluids', 'fa-syringe', 'primary', 'السوائل الوريدية (IV Fluids)'],
                                                                        ['intake_oral', 'fa-cup', 'success', 'الفموي (Oral)'],
                                                                        ['intake_blood', 'fa-tint', 'danger', 'الدم (Blood)'],
                                                                    ] as $f)
                                                                    <label class="d-flex align-items-center gap-2 py-2 cursor-pointer" style="cursor:pointer;">
                                                                        <input type="checkbox" name="required_fluids[]" value="{{ $f[0] }}" class="form-check-input mt-0"
                                                                            {{ in_array($f[0], $requiredFluids) ? 'checked' : '' }}>
                                                                        <i class="fas {{ $f[1] }} text-{{ $f[2] }}"></i>
                                                                        <span>{{ $f[3] }}</span>
                                                                    </label>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="card border border-warning-subtle bg-warning bg-opacity-5 h-100">
                                                                <div class="card-header bg-transparent border-bottom border-warning-subtle py-2">
                                                                    <h6 class="fw-bold text-warning-emphasis mb-0"><i class="fas fa-arrow-alt-circle-up me-1"></i>المخرجات (Output)</h6>
                                                                </div>
                                                                <div class="card-body">
                                                                    @foreach([
                                                                        ['output_urine', 'fa-toilet', 'warning', 'البول (Urine)'],
                                                                        ['output_drain', 'fa-tube', 'info', 'التصريف (Drain)'],
                                                                        ['output_gtube_ng', 'fa-stomach', 'secondary', 'أنبوب المعدة (NG Tube)'],
                                                                        ['output_vomiting', 'fa-vomit', 'danger', 'القيء (Vomiting)'],
                                                                        ['output_stool', 'fa-poo', 'secondary', 'البراز (Stool)'],
                                                                    ] as $f)
                                                                    <label class="d-flex align-items-center gap-2 py-2 cursor-pointer" style="cursor:pointer;">
                                                                        <input type="checkbox" name="required_fluids[]" value="{{ $f[0] }}" class="form-check-input mt-0"
                                                                            {{ in_array($f[0], $requiredFluids) ? 'checked' : '' }}>
                                                                        <i class="fas {{ $f[1] }} text-{{ $f[2] }}"></i>
                                                                        <span>{{ $f[3] }}</span>
                                                                    </label>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4">
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="fas fa-save me-1"></i>حفظ</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                        @else
                                        <div class="d-flex flex-wrap gap-2">
                                            @php $fluidLabels = ['intake_iv_fluids'=>'السوائل الوريدية','intake_oral'=>'الفموي','intake_blood'=>'الدم','output_urine'=>'البول','output_drain'=>'التصريف','output_gtube_ng'=>'أنبوب المعدة','output_vomiting'=>'القيء','output_stool'=>'البراز']; @endphp
                                            @forelse($requiredFluids as $f)
                                                <span class="badge bg-info bg-opacity-10 text-dark border border-info-subtle px-3 py-2">{{ $fluidLabels[$f] ?? $f }}</span>
                                            @empty
                                                <div class="alert alert-info mb-0">لم يحدد الجراح مكونات السوائل المطلوبة بعد.</div>
                                            @endforelse
                                        </div>
                                        @endif
                                    </div>

                                    <!-- 6. Follow-Ups Tab -->
                                    <div class="tab-pane fade" id="v-pills-followups" role="tabpanel">
                                        <h6 class="border-bottom pb-2 mb-3 fw-bold text-primary">
                                            <i class="fas fa-clipboard-list me-2"></i>سجل المتابعات
                                        </h6>
                                        @php $allFollowUps = $surgery->residentStationFollowUps->sortByDesc('created_at'); @endphp
                                        @if($allFollowUps->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>التاريخ</th>
                                                        <th>الوردية</th>
                                                        <th>المسجل</th>
                                                        <th>الملاحظات</th>
                                                        <th>وقت التسجيل</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($allFollowUps as $followUp)
                                                    <tr>
                                                        <td>{{ $followUp->follow_up_date->format('Y-m-d') }}</td>
                                                        <td>{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                        <td>{{ $followUp->resident?->user?->full_name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                                        <td>{!! nl2br(e($followUp->notes)) !!}</td>
                                                        <td>{{ $followUp->created_at->format('Y-m-d H:i') }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                        @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-1"></i> لا توجد متابعات مسجلة بعد.
                                        </div>
                                        @endif
                                    </div>

                                    @if(auth()->user()->hasRole(['admin', 'surgery_staff']))
                                    <!-- 6. Supplies Tab -->
                                    <div class="tab-pane fade" id="v-pills-notes" role="tabpanel">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                            <h6 class="fw-bold text-primary mb-0">
                                                <i class="fas fa-box-open me-2"></i>المستلزمات الطبية المطلوبة للعملية
                                            </h6>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addSupplyRow()">
                                                <i class="fas fa-plus me-1"></i>إضافة مستلزم
                                            </button>
                                        </div>

                                        {{-- Hidden field to store supplies as JSON --}}
                                        <input type="hidden" name="supplies" id="suppliesJsonField">

                                        <div class="table-responsive">
                                            <table class="table table-bordered align-middle" id="suppliesTable" style="font-size:0.9rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th class="text-center" width="5%">#</th>
                                                        <th width="35%">اسم المستلزم / المادة</th>
                                                        <th width="15%">الكمية</th>
                                                        <th width="20%">الوحدة</th>
                                                        <th width="20%">ملاحظات</th>
                                                        <th class="text-center" width="5%">حذف</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="suppliesContainer">
                                                    @php
                                                        $savedSupplies = [];
                                                        if ($surgery->supplies) {
                                                            $decoded = json_decode($surgery->supplies, true);
                                                            if (is_array($decoded)) {
                                                                $savedSupplies = $decoded;
                                                            } else {
                                                                // قيمة نصية قديمة - نضعها كصف واحد
                                                                $savedSupplies = [['name' => $surgery->supplies, 'qty' => '', 'unit' => '', 'notes' => '']];
                                                            }
                                                        }
                                                    @endphp
                                                    @forelse($savedSupplies as $idx => $supply)
                                                    <tr class="supply-row">
                                                        <td class="text-center text-muted supply-num">{{ $idx + 1 }}</td>
                                                        <td><input type="text" class="form-control form-control-sm supply-name" value="{{ $supply['name'] ?? '' }}" placeholder="مثال: قفازات جراحية، شاش معقم..."></td>
                                                        <td><input type="number" class="form-control form-control-sm supply-qty" value="{{ $supply['qty'] ?? '' }}" placeholder="0" min="0" step="0.5"></td>
                                                        <td>
                                                            <select class="form-select form-select-sm supply-unit">
                                                                <option value="قطعة" {{ ($supply['unit'] ?? '') == 'قطعة' ? 'selected' : '' }}>قطعة</option>
                                                                <option value="علبة" {{ ($supply['unit'] ?? '') == 'علبة' ? 'selected' : '' }}>علبة</option>
                                                                <option value="زجاجة" {{ ($supply['unit'] ?? '') == 'زجاجة' ? 'selected' : '' }}>زجاجة</option>
                                                                <option value="حقنة" {{ ($supply['unit'] ?? '') == 'حقنة' ? 'selected' : '' }}>حقنة</option>
                                                                <option value="مل" {{ ($supply['unit'] ?? '') == 'مل' ? 'selected' : '' }}>مل</option>
                                                                <option value="غرام" {{ ($supply['unit'] ?? '') == 'غرام' ? 'selected' : '' }}>غرام</option>
                                                                <option value="لتر" {{ ($supply['unit'] ?? '') == 'لتر' ? 'selected' : '' }}>لتر</option>
                                                                <option value="زوج" {{ ($supply['unit'] ?? '') == 'زوج' ? 'selected' : '' }}>زوج</option>
                                                                <option value="طقم" {{ ($supply['unit'] ?? '') == 'طقم' ? 'selected' : '' }}>طقم</option>
                                                                <option value="أخرى" {{ ($supply['unit'] ?? '') == 'أخرى' ? 'selected' : '' }}>أخرى</option>
                                                            </select>
                                                        </td>
                                                        <td><input type="text" class="form-control form-control-sm supply-notes" value="{{ $supply['notes'] ?? '' }}" placeholder="ملاحظة اختيارية"></td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSupplyRow(this)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr id="emptySuppliesRow">
                                                        <td colspan="6" class="text-center py-4 text-muted">
                                                            <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                                            لا توجد مستلزمات مسجلة — اضغط "إضافة مستلزم" للبدء
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @endif

                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Footer Actions -->
                    <div class="card-footer bg-light text-end py-3 border-top">
                        @if($canManageSurgery)
                        <button type="submit" class="btn btn-success px-4 btn-lg">
                            <i class="fas fa-save me-2"></i>حفظ كافة التفاصيل الطبية
                        </button>
                        @else
                        <button type="button" class="btn btn-secondary px-4 btn-lg" disabled>
                            <i class="fas fa-lock me-2"></i>عرض فقط (غير مصرح بالتحرير)
                        </button>
                        @endif
                    </div>
                </div>
            </form>
        </div>


    </div>
</div>

<!-- Modal Cancellation -->
<div class="modal fade" id="cancelSurgeryModal" tabindex="-1" aria-labelledby="cancelSurgeryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelSurgeryModalLabel">
                    <i class="fas fa-times-circle me-2"></i>إلغاء العملية الجراحية
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="cancellation_reason" class="form-label">سبب الإلغاء</label>
                        <textarea id="cancellation_reason" name="cancellation_reason" class="form-control" rows="4" placeholder="اكتب سبب إلغاء العملية بالتفصيل..." required></textarea>
                        <small class="text-muted">سبب الإلغاء سيتم حفظه بشكل دائم في سجل العملية الجراحية.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-danger">تأكيد إلغاء العملية</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    // Function to complete anesthesia station
    function completeAnesthesia(surgeryId) {
        if (!confirm('هل أنت متأكد من حفظ بيانات التخدير وإتمام المحطة؟')) {
            return;
        }

        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/surgery-stations/anesthesia/' + surgeryId + '/complete';

        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);

        document.body.appendChild(form);
        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        
        // Auto-calculate estimated duration from start and end time inputs
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');
        
        function calculateDuration() {
            const durationField = document.getElementById('estimated_duration');
            if (!startTimeInput || !endTimeInput || !durationField) return;

            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (startTime && endTime) {
                // Parse time inputs
                const start = new Date('1970-01-01T' + startTime + ':00');
                const end = new Date('1970-01-01T' + endTime + ':00');

                // If end time is before start time, assume it spans to the next day
                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }

                // Diff in milliseconds
                const diffMs = end - start;
                const diffMins = Math.round(diffMs / 60000);

                // Convert to hours and minutes
                const hours = Math.floor(diffMins / 60);
                const minutes = diffMins % 60;

                // Format duration string: HH:MM
                const formattedDuration = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
                durationField.value = formattedDuration;

                // Create or update a hidden input to post total minutes to backend
                let hiddenMinutesField = document.querySelector('input[name="estimated_duration_minutes"]');
                if (!hiddenMinutesField) {
                    hiddenMinutesField = document.createElement('input');
                    hiddenMinutesField.type = 'hidden';
                    hiddenMinutesField.name = 'estimated_duration_minutes';
                    document.getElementById('surgeryDetailsForm').appendChild(hiddenMinutesField);
                }
                hiddenMinutesField.value = diffMins;
            }
        }

        if (startTimeInput && endTimeInput) {
            startTimeInput.addEventListener('input', calculateDuration);
            endTimeInput.addEventListener('input', calculateDuration);
            // Trigger calculation on load if values are already filled
            calculateDuration();
        }
    });

    // Dynamic Treatments Table Logic
    window.addSurgeryTreatment = function() {
        const container = document.getElementById('surgeryTreatmentsContainer');
        const emptyRow = document.getElementById('emptySurgeryTreatmentsRow');
        if (!container) return;

        if (emptyRow) {
            emptyRow.remove();
        }

        const treatmentIndex = container.querySelectorAll('.treatment-item').length;
        const treatmentHtml = `
            <tr class="treatment-item">
                <td class="text-center row-number">${treatmentIndex + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][description]"
                           placeholder="اسم الدواء أو وصف العلاج" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][dosage]"
                           placeholder="مثال: 500mg, 2ml">
                </td>
                <td>
                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][timing]" rows="1"
                              placeholder="مثال: كل 6 ساعات"></textarea>
                </td>
                <td>
                    <div class="duration-input-group">
                        <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_value]"
                               placeholder="العدد" min="1">
                        <select class="form-select form-select-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_unit]">
                            <option value="days" selected>يوم</option>
                            <option value="weeks">أسبوع</option>
                            <option value="months">شهر</option>
                            <option value="hours">ساعة</option>
                            <option value="doses">جرعة</option>
                        </select>
                    </div>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSurgeryTreatment(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        container.insertAdjacentHTML('beforeend', treatmentHtml);
        reNumberRows();
    };

    window.removeSurgeryTreatment = function(button) {
        const row = button.closest('.treatment-item');
        if (!row) return;
        const container = row.parentElement;
        row.remove();

        reNumberRows();

        const remainingRows = container.querySelectorAll('.treatment-item');
        if (remainingRows.length === 0) {
            const emptyRowHtml = `
                <tr id="emptySurgeryTreatmentsRow">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-table fa-2x mb-2"></i>
                        <p class="mb-1">لا توجد علاجات مسجلة لهذه العملية</p>
                        <small>اضغط على "إضافة علاج" للبدء</small>
                    </td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', emptyRowHtml);
        }
    };

    function reNumberRows() {
        const rows = document.querySelectorAll('#surgeryTreatmentsContainer .treatment-item');
        rows.forEach((row, index) => {
            // Update row number display
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = index + 1;

            // Re-index names to maintain standard contiguous sequence arrays for PHP backend validation
            const inputs = row.querySelectorAll('[name]');
            inputs.forEach(input => {
                const oldName = input.name;
                const newName = oldName.replace(/(\[surgery_treatments\]\[\d+\])\[\d+\]/, `$1[${index}]`);
                input.name = newName;
            });
        });
    }

    // ===== Supplies Table Logic =====
    window.addSupplyRow = function() {
        const container = document.getElementById('suppliesContainer');
        const emptyRow  = document.getElementById('emptySuppliesRow');
        if (!container) return;
        if (emptyRow) emptyRow.remove();

        const idx = container.querySelectorAll('.supply-row').length + 1;
        const unitOptions = ['قطعة','علبة','زجاجة','حقنة','مل','غرام','لتر','زوج','طقم','أخرى']
            .map(u => `<option value="${u}">${u}</option>`).join('');

        container.insertAdjacentHTML('beforeend', `
            <tr class="supply-row">
                <td class="text-center text-muted supply-num">${idx}</td>
                <td><input type="text" class="form-control form-control-sm supply-name" placeholder="مثال: قفازات جراحية، شاش معقم..."></td>
                <td><input type="number" class="form-control form-control-sm supply-qty" placeholder="0" min="0" step="0.5"></td>
                <td><select class="form-select form-select-sm supply-unit">${unitOptions}</select></td>
                <td><input type="text" class="form-control form-control-sm supply-notes" placeholder="ملاحظة اختيارية"></td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSupplyRow(this)">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `);
        reNumberSupplyRows();
    };

    window.removeSupplyRow = function(btn) {
        const row = btn.closest('.supply-row');
        if (!row) return;
        const container = row.parentElement;
        row.remove();
        reNumberSupplyRows();
        if (!container.querySelectorAll('.supply-row').length) {
            container.insertAdjacentHTML('beforeend', `
                <tr id="emptySuppliesRow">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                        لا توجد مستلزمات مسجلة — اضغط "إضافة مستلزم" للبدء
                    </td>
                </tr>
            `);
        }
    };

    function reNumberSupplyRows() {
        document.querySelectorAll('#suppliesContainer .supply-row').forEach((row, i) => {
            const num = row.querySelector('.supply-num');
            if (num) num.textContent = i + 1;
        });
    }

    // Serialize supplies to JSON before form submit
    const surgeryForm = document.getElementById('surgeryDetailsForm');
    if (surgeryForm) {
        surgeryForm.addEventListener('submit', function() {
            const rows = document.querySelectorAll('#suppliesContainer .supply-row');
            const jsonField = document.getElementById('suppliesJsonField');
            if (!jsonField) return;
            const data = [];
            rows.forEach(row => {
                data.push({
                    name:  row.querySelector('.supply-name')?.value  || '',
                    qty:   row.querySelector('.supply-qty')?.value   || '',
                    unit:  row.querySelector('.supply-unit')?.value  || '',
                    notes: row.querySelector('.supply-notes')?.value || '',
                });
            });
            jsonField.value = JSON.stringify(data);
        });
    }
</script>
@endsection
