@extends('layouts.app')

@section('content')
<style>
    .inquiry-stat-card {
        background: rgba(148,163,184,0.18);
        color: #0f172a;
        border: 1px solid rgba(148,163,184,0.22);
        border-radius: 18px;
    }
    .inquiry-stat-card .card-body {
        padding: 1rem 1rem !important;
    }
    .inquiry-stat-card .icon-circle {
        background: rgba(148,163,184,0.16);
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        color: #475569;
    }
    .inquiry-stat-card h6 {
        color: #475569;
        font-size: 0.78rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 0.35rem;
    }
    .inquiry-stat-card h1 {
        font-size: 2rem;
        margin: 0;
    }
    .inquiry-table {
        background: transparent;
    }
    .inquiry-table thead th {
        background: rgba(191,219,254,0.5);
        color: #1d4ed8;
        border-color: rgba(147,197,253,0.3);
        font-weight: 600;
    }
    .inquiry-table tbody tr {
        background: rgba(238,246,255,0.92);
    }
    .inquiry-table tbody tr:nth-of-type(odd) {
        background: rgba(219,234,254,0.92);
    }
    .inquiry-table tbody tr:hover {
        background: rgba(191,219,254,0.5);
    }
    .inquiry-table td,
    .inquiry-table th {
        border-color: rgba(147,197,253,0.28);
        vertical-align: middle;
        padding: 0.85rem 0.95rem;
        color: #1e3a8a;
    }
    .inquiry-table tbody td small,
    .inquiry-table tbody td .text-muted {
        color: #475569 !important;
    }
    .inquiry-table .badge {
        background: rgba(59,130,246,0.18);
        color: #fff;
        border: 1px solid rgba(59,130,246,0.2);
        text-shadow: none;
    }
    .inquiry-table .badge.bg-secondary {
        background: rgba(107,114,128,0.9);
        color: #fff;
    }
    .inquiry-table .badge.bg-info,
    .inquiry-table .badge.bg-success,
    .inquiry-table .badge.bg-warning {
        color: #fff;
        text-shadow: none;
    }
    .inquiry-actions .btn-info {
        background: #475569;
        border-color: #475569;
        color: #fff;
    }
    .inquiry-actions .btn-info:hover {
        background: #334155;
    }
    .inquiry-quick .card {
        background: rgba(203,213,225,0.16);
        border: 1px solid rgba(148,163,184,0.24);
        border-radius: 18px;
    }
    .inquiry-quick .card-header {
        background: transparent !important;
        color: #475569 !important;
        border-bottom: none !important;
    }
    .btn-outline-info {
        color: #475569;
        border-color: #475569;
    }
    .btn-outline-info:hover {
        background: rgba(71,85,105,0.08);
    }
    .text-muted { color: #6b7280 !important; }
</style>
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>
                        <i class="fas fa-hospital me-2"></i>
                        الاستعلامات والاستقبال
                    </h2>
                    <p class="text-muted">إدارة استقبال المرضى وإنشاء الطلبات الطبية</p>
                </div>
               
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(isset($isConsultationReceptionistOnly) && $isConsultationReceptionistOnly)
        <!-- واجهة موظف استعلامات الاستشارية: زر طلب جديد فقط -->
        <div class="row justify-content-center my-5">
            <div class="col-lg-6 col-md-8 text-center py-5">
                <div class="card border-0 shadow-sm rounded-4 p-5" style="background: linear-gradient(135deg, #f8fafc 0%, #eef2f6 100%); border: 1px solid rgba(203, 213, 225, 0.6) !important;">
                    <div class="mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle shadow-sm" style="width: 80px; height: 80px;">
                            <i class="fas fa-stethoscope fa-3x"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-2">استقبال العيادات الاستشارية</h3>
                    <p class="text-muted mb-4">بدء حجز كشفية جديدة لمريض بالعيادات</p>
                    <div>
                        <a href="{{ route('inquiry.search') }}" class="btn btn-primary btn-lg px-5 py-3 fw-bold shadow rounded-pill fs-5">
                            <i class="fas fa-plus-circle me-2"></i> طلب جديد
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @else
        @php
            $hasAnyTransfers = (isset($consultantSurgeryTransfers) && count($consultantSurgeryTransfers) > 0)
                || (isset($pendingInsuranceEmergencyReferrals) && count($pendingInsuranceEmergencyReferrals) > 0)
                || (isset($pendingTransfers) && count($pendingTransfers) > 0)
                || (isset($pendingAdmissionTransfers) && count($pendingAdmissionTransfers) > 0);
        @endphp

        <!-- شريط الإجراءات السريع -->
        <div class="row mb-4">
            <div class="col-12 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-dark mb-0">
                    <i class="fas fa-clipboard-list me-2 text-primary"></i> التحويلات والإحالات المعلقة
                </h5>
                <div class="d-flex gap-2">
                    @can('create inquiries')
                    <a href="{{ route('inquiry.search') }}" class="btn btn-primary fw-bold shadow-sm">
                        <i class="fas fa-plus-circle me-1"></i> طلب جديد
                    </a>
                    @endcan
                    <button class="btn btn-outline-secondary" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt me-1"></i> تحديث
                    </button>
                </div>
            </div>
        </div>

        @if(isset($consultantSurgeryTransfers) && count($consultantSurgeryTransfers) > 0)
            <!-- مرضى العيادات الاستشارية المحولين للعمليات -->
            <div class="row mb-4 animate__animated animate__fadeIn">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-warning">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-procedures me-2 animate__animated animate__pulse animate__infinite"></i>
                                مرضى العيادات الاستشارية المحولين للعمليات الجراحية
                            </h5>
                            <span class="fw-bold fs-6">({{ count($consultantSurgeryTransfers) }} مرضى بانتظار حجز العملية)</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>وقت التحويل</th>
                                            <th>اسم المريض</th>
                                            <th>رقم المريض</th>
                                            <th>الطبيب الاستشاري</th>
                                            <th>العيادة</th>
                                            <th>ملاحظات العملية</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($consultantSurgeryTransfers as $cTransfer)
                                            <tr>
                                                <td>
                                                    <small class="text-dark fw-bold">
                                                        <i class="fas fa-clock me-1 text-warning"></i>
                                                        {{ $cTransfer->updated_at ? $cTransfer->updated_at->format('Y-m-d H:i') : $cTransfer->visit_date->format('Y-m-d') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong>{{ optional($cTransfer->patient)->user->name ?? 'مريض غير محدد' }}</strong>
                                                    <br><small class="text-muted">{{ optional($cTransfer->patient)->user->phone ?? '-' }}</small>
                                                </td>
                                                <td><code>#{{ $cTransfer->patient_id }}</code></td>
                                                <td>د. {{ optional($cTransfer->doctor)->user->name ?? 'طبيب استشاري' }}</td>
                                                <td><span class="badge bg-info text-white">{{ optional($cTransfer->department)->name ?? 'الاستشارية' }}</span></td>
                                                <td class="text-start">
                                                    <small class="text-dark fw-semibold">{{ Str::limit($cTransfer->surgery_notes, 80) }}</small>
                                                </td>
                                                <td>
                                                    <a href="{{ route('surgeries.create', [
                                                        'patient_id' => $cTransfer->patient_id,
                                                        'visit_id' => $cTransfer->id,
                                                        'doctor_id' => $cTransfer->doctor_id,
                                                        'department_id' => $cTransfer->department_id,
                                                        'referring_doctor_name' => optional($cTransfer->doctor)->user->name ?? 'طبيب استشاري'
                                                    ]) }}" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm">
                                                        <i class="fas fa-procedures me-1"></i> حجز عملية جراحية
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($pendingInsuranceEmergencyReferrals) && count($pendingInsuranceEmergencyReferrals) > 0)
            <!-- إحالات الطوارئ المشمولة بالضمان الصحي الواردة من العيادات الاستشارية -->
            <div class="row mb-4 animate__animated animate__fadeIn">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-success">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-shield-alt me-2 animate__animated animate__pulse animate__infinite"></i>
                                إحالات الطوارئ المشمولة بالضمان الصحي (الواردة من عيادات الاستشارية)
                            </h5>
                            <span class="badge bg-white text-success fw-bold fs-6">{{ count($pendingInsuranceEmergencyReferrals) }} إحالة بانتظار التأكيد</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>وقت الإحالة</th>
                                            <th>اسم المريض</th>
                                            <th>فئة الضمان</th>
                                            <th>الطبيب الاستشاري</th>
                                            <th>الخدمات والتوجيهات</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingInsuranceEmergencyReferrals as $insRef)
                                            <tr>
                                                <td>
                                                    <small class="text-success fw-bold">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $insRef->created_at ? $insRef->created_at->format('H:i') : '—' }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong>{{ optional(optional($insRef->visit)->patient)->name ?? optional(optional(optional($insRef->visit)->patient)->user)->name ?? 'مريض غير محدد' }}</strong>
                                                    <br><small class="text-muted">{{ optional(optional(optional($insRef->visit)->patient)->user)->phone ?? '-' }}</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-2 py-1">
                                                        {{ optional(optional(optional($insRef->visit)->patient)->healthInsuranceCategory)->name ?? 'ضمان صحي' }}
                                                    </span>
                                                </td>
                                                <td>د. {{ optional(optional(optional($insRef->visit)->doctor)->user)->name ?? 'الاستشاري' }}</td>
                                                <td><span class="text-dark fw-bold">{{ Str::limit($insRef->description, 60) }}</span></td>
                                                <td>
                                                    <a href="{{ route('emergency.create', ['patient_id' => optional($insRef->visit)->patient_id, 'from_referral_id' => $insRef->id, 'return_to' => 'inquiry']) }}" class="btn btn-sm btn-success fw-bold shadow-xs">
                                                        <i class="fas fa-ambulance me-1"></i> حجز طوارئ الضمان
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($pendingTransfers) && count($pendingTransfers) > 0)
            <!-- مرضى محولون للعمليات من الطوارئ -->
            <div class="row mb-4 animate__animated animate__fadeIn">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-danger">
                        <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-procedures me-2 animate__animated animate__pulse animate__infinite"></i>
                                مرضى الطوارئ المحولين للعمليات الجراحية
                            </h5>
                            <span class="badge bg-white text-danger fw-bold fs-6">{{ count($pendingTransfers) }} مرضى بانتظار الحجز</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>وقت التحويل</th>
                                            <th>اسم المريض</th>
                                            <th>رقم المريض</th>
                                            <th>الطبيب المحيل</th>
                                            <th>الشكوى/الحالة</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingTransfers as $transfer)
                                            <tr>
                                                <td>
                                                    <small class="text-danger fw-bold">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $transfer->updated_at->format('Y-m-d H:i') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong>{{ $transfer->patient?->user?->name ?? ($transfer->emergencyPatient?->name ?? 'مريض غير محدد') }}</strong>
                                                    <br><small class="text-muted">{{ $transfer->patient?->user?->phone ?? ($transfer->emergencyPatient?->phone ?? '-') }}</small>
                                                </td>
                                                <td><code>#{{ $transfer->patient_id }}</code></td>
                                                <td>د. {{ $transfer->doctor?->user?->name ?? 'طبيب الطوارئ' }}</td>
                                                <td><span class="text-muted">{{ Str::limit($transfer->description ?: $transfer->symptoms, 50) }}</span></td>
                                                <td>
                                                    <a href="{{ route('surgeries.create', ['patient_id' => $transfer->patient_id, 'referring_doctor_name' => $transfer->doctor?->user?->name ?? 'طبيب الطوارئ']) }}" class="btn btn-sm btn-danger fw-bold">
                                                        <i class="fas fa-plus me-1"></i> حجز عملية جراحية
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(isset($pendingAdmissionTransfers) && count($pendingAdmissionTransfers) > 0)
            <!-- مرضى محولون للرقود من الطوارئ -->
            <div class="row mb-4 animate__animated animate__fadeIn">
                <div class="col-12">
                    <div class="card border-0 shadow-sm border-start border-4 border-warning">
                        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center py-3">
                            <h5 class="mb-0 fw-bold">
                                <i class="fas fa-bed me-2 animate__animated animate__pulse animate__infinite"></i>
                                مرضى الطوارئ المحولين للرقود (التنويم)
                            </h5>
                            <span class="fw-bold fs-6">({{ count($pendingAdmissionTransfers) }} مرضى بانتظار حجز سرير)</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0 text-center">
                                    <thead class="table-light">
                                        <tr>
                                            <th>وقت التحويل</th>
                                            <th>اسم المريض</th>
                                            <th>رقم المريض</th>
                                            <th>الطبيب المحيل</th>
                                            <th>الشكوى/الحالة</th>
                                            <th>الإجراء</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingAdmissionTransfers as $admTransfer)
                                            <tr>
                                                <td>
                                                    <small class="text-warning fw-bold text-dark">
                                                        <i class="fas fa-clock me-1"></i>
                                                        {{ $admTransfer->updated_at->format('Y-m-d H:i') }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong>{{ $admTransfer->patient?->user?->name ?? ($admTransfer->emergencyPatient?->name ?? 'مريض غير محدد') }}</strong>
                                                    <br><small class="text-muted">{{ $admTransfer->patient?->user?->phone ?? ($admTransfer->emergencyPatient?->phone ?? '-') }}</small>
                                                </td>
                                                <td><code>#{{ $admTransfer->patient_id }}</code></td>
                                                <td>د. {{ $admTransfer->doctor?->user?->name ?? 'طبيب الطوارئ' }}</td>
                                                <td><span class="text-muted">{{ Str::limit($admTransfer->description ?: $admTransfer->symptoms, 50) }}</span></td>
                                                <td>
                                                    <a href="{{ route('bed-reservations.create', ['patient_id' => $admTransfer->patient_id, 'doctor_id' => $admTransfer->doctor_id]) }}" class="btn btn-sm btn-warning text-dark fw-bold">
                                                        <i class="fas fa-bed me-1"></i> حجز رقود وسرير
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(!$hasAnyTransfers)
            <div class="row justify-content-center my-4">
                <div class="col-lg-6 text-center py-5">
                    <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4 class="fw-bold text-dark">لا توجد تحويلات أو إحالات معلقة</h4>
                        <p class="text-muted mb-4">كافة تحويلات العمليات والرقود وإحالات الطوارئ مكتملة ومحجوزة</p>
                        <div>
                            <a href="{{ route('inquiry.search') }}" class="btn btn-primary px-4 py-2 fw-bold shadow-sm rounded-pill">
                                <i class="fas fa-plus-circle me-1"></i> إنشاء طلب جديد
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

</div>
@endsection
