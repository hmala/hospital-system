@extends('layouts.app')

@section('content')
@php
    $user = auth()->user();
    $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
    $isSurgeryStaff = $user && $user->hasRole('surgery_staff');

    $savedMedications = ($surgery->surgeryTreatments ?? collect())
        ->where('item_type', \App\Models\SurgeryTreatment::TYPE_MEDICATION)
        ->values();
    $editableTreatments = $savedMedications->filter(fn ($t) => empty($t->batch_id))->values();
    $marBatchTreatments = $savedMedications->filter(fn ($t) => ! empty($t->batch_id))->values();

    $surgeryStartFormatted = '';
    if ($surgery->start_time) {
        $surgeryStartFormatted = is_string($surgery->start_time)
            ? \Carbon\Carbon::parse($surgery->start_time)->format('H:i')
            : $surgery->start_time->format('H:i');
    } elseif ($surgery->started_at) {
        $surgeryStartFormatted = $surgery->started_at->format('H:i');
    }

    $surgeryEndFormatted = '';
    if ($surgery->end_time) {
        $surgeryEndFormatted = is_string($surgery->end_time)
            ? \Carbon\Carbon::parse($surgery->end_time)->format('H:i')
            : $surgery->end_time->format('H:i');
    }

    $primaryAnesthId = $surgery->anesthesiaStation?->anesthesiologist_id ?? $surgery->anesthesiologist_id;
    $secondaryAnesthId = $surgery->anesthesiaStation?->anesthesiologist_2_id ?? $surgery->anesthesiologist_2_id;
    $primaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist?->user?->full_name
        ?? $surgery->anesthesiaStation?->anesthesiologist?->user?->name
        ?? $surgery->anesthesiologist?->user?->full_name
        ?? $surgery->anesthesiologist?->user?->name
        ?? 'غير محدد';
    $secondaryAnesthName = $surgery->anesthesiaStation?->anesthesiologist2?->user?->full_name
        ?? $surgery->anesthesiaStation?->anesthesiologist2?->user?->name
        ?? $surgery->anesthesiologist2?->user?->full_name
        ?? $surgery->anesthesiologist2?->user?->name
        ?? 'غير محدد';
    $currentAnesthesiaType = $surgery->anesthesiaStation?->anesthesia_type ?? $surgery->anesthesia_type ?? '';
    $currentAnesthesiaNotes = $surgery->anesthesiaStation?->notes ?? '';

    $shortTabLabels = [
        'detail_tests' => 'الفحوصات والتحاليل',
        'detail_type' => 'نوع العملية',
        'detail_diagnosis' => 'التشخيص والعلاج',
        'detail_anesthesia' => 'تفاصيل التخدير',
        'detail_team_timing' => 'الفريق والتوقيت',
        'detail_supplies' => 'المستلزمات والأجهزة',
        'detail_followups' => 'المتابعات',
    ];
@endphp

<div class="container-fluid px-3 px-md-4 py-3" id="unifiedSurgeryDetailApp">
    <style>
        .clin-header-modern {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 14px 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 20px -4px rgba(15, 23, 42, 0.05), 0 1px 3px rgba(15, 23, 42, 0.03);
            position: relative;
            overflow: hidden;
        }
        .clin-header-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #2563eb 0%, #06b6d4 50%, #3b82f6 100%);
        }
        .avatar-modern-box {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border: 1px solid #bfdbfe;
            color: #2563eb;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            box-shadow: 0 2px 6px rgba(37, 99, 235, 0.12);
        }
        .clin-patient-name {
            color: #0f172a !important;
            font-size: 1.18rem;
            font-weight: 800;
            letter-spacing: -0.2px;
        }
        .badge-modern-code {
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
            font-family: monospace, system-ui;
        }
        .badge-modern-sub {
            background: #f8fafc;
            color: #475569;
            font-size: 0.73rem;
            font-weight: 600;
            padding: 2px 7px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .clin-v-divider {
            width: 1px;
            height: 40px;
            background: #e2e8f0;
            margin: 0 4px;
        }
        .clin-meta-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px;
        }
        .badge-doctor-chip {
            background: #f0fdf4;
            color: #166534;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #bbf7d0;
        }
        .badge-room-chip {
            background: #fffbeb;
            color: #92400e;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #fde68a;
        }
        .badge-time-chip {
            background: #f8fafc;
            color: #475569;
            font-size: 0.74rem;
            font-weight: 600;
            padding: 2px 8px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .badge-status-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
        }
        .status-pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            display: inline-block;
        }
        .badge-status-completed {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }
        .badge-status-progress {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .badge-status-waiting {
            background: #f8fafc;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .badge-status-cancelled {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .clin-tabs-wrapper {
            background: #ffffff;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }
        .clin-tabs-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 0;
            margin: 0;
            list-style: none;
            width: 100%;
        }
        .clin-tab-item {
            flex: 1 1 0px;
            min-width: 110px;
        }
        @media (max-width: 991px) {
            .clin-tab-item {
                flex: 1 1 calc(33.33% - 4px);
                min-width: 100px;
            }
        }
        @media (max-width: 576px) {
            .clin-tab-item {
                flex: 1 1 calc(50% - 4px);
            }
        }
        .clin-nav-tab {
            font-size: 0.82rem;
            font-weight: 700;
            padding: 9px 8px;
            min-height: 42px;
            border-radius: 8px;
            color: #475569;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            text-decoration: none;
            text-align: center;
            width: 100%;
            white-space: nowrap;
            cursor: pointer;
        }
        .clin-nav-tab:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .clin-nav-tab.active {
            background: #2563eb !important;
            color: #ffffff !important;
            border-color: #1d4ed8 !important;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
        }
        .clin-nav-tab.active * {
            color: #ffffff !important;
        }
        .clin-nav-tab.active .badge-perm-dot {
            background: rgba(255, 255, 255, 0.28) !important;
            color: #ffffff !important;
        }
        .badge-perm-dot {
            font-size: 0.6rem;
            padding: 2px 5px;
            border-radius: 4px;
            line-height: 1;
        }
        .badge-perm-write {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-perm-read {
            background: #e2e8f0;
            color: #475569;
        }
        .clin-tab-card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            background: #ffffff;
        }
        .clin-section-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .clin-section-card.clin-surface-white {
            background: #ffffff;
        }
        .clin-field-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 9px 11px;
            height: 100%;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .clin-field-card:focus-within {
            border-color: #93c5fd;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.08);
        }
        .clin-compact-textarea {
            font-size: 0.86rem;
            line-height: 1.45;
            min-height: 62px;
            padding: 6px 9px;
            resize: vertical;
        }
        .clin-quick-chip {
            font-size: 0.69rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
            transition: all 0.12s ease;
        }
        .clin-quick-chip:hover {
            background: #eff6ff;
            border-color: #93c5fd;
            color: #1d4ed8;
        }
        .clin-section-title {
            font-size: 0.84rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .duration-input-group {
            display: flex;
            gap: 4px;
            align-items: center;
        }
        .timing-textarea {
            min-height: 36px;
            resize: vertical;
        }
    </style>

    {{-- Top Breadcrumb & Quick Actions --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('surgeries.index') }}" class="text-decoration-none">إدارة العمليات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('surgeries.waiting') }}" class="text-decoration-none">قائمة الانتظار اليومية</a></li>
                <li class="breadcrumb-item active" aria-current="page">تفاصيل العملية (#{{ $surgery->id }})</li>
            </ol>
        </nav>

        <div class="d-flex flex-wrap align-items-center gap-2">
            @can('manage surgery tab permissions')
                <a href="{{ route('surgery-stations.tab-settings.index') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                    <i class="fas fa-sliders-h me-1"></i> ضبط صلاحيات التبويبات
                </a>
            @endcan

            @if($user && ($isAdmin || $user->can('view surgeries') || $user->can('view surgeon station')) && ! ($user->hasRole('doctor') && $user->doctor?->type === 'consultant'))
                <a href="{{ route('surgeries.print', $surgery) }}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="fas fa-print me-1"></i> طباعة التقرير
                </a>
            @endif

            <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('info'))
        <div class="alert alert-info alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center mb-3" role="alert">
            <i class="fas fa-info-circle fa-lg me-2"></i>
            <div>{{ session('info') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 mb-3" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Executive Header --}}
    <div class="clin-header-modern mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3 gap-xl-4">
                {{-- Patient --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-modern-box flex-shrink-0">
                        <i class="fas fa-procedures"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="clin-patient-name mb-0">{{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير محدد' }}</h3>
                            <span class="badge-modern-code">#{{ $surgery->patient?->patient_id ?? $surgery->patient_id ?? 'N/A' }}</span>
                            <span class="badge bg-light text-dark border font-monospace" style="font-size:0.7rem;">عملية #{{ $surgery->id }}</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small mt-1 flex-wrap">
                            <span class="badge-modern-sub">
                                <i class="fas fa-birthday-cake me-1 text-primary"></i>{{ $surgery->patient?->age ? $surgery->patient->age . ' سنة' : 'العمر غير مسجل' }}
                            </span>
                            <span class="badge-modern-sub">
                                <i class="fas fa-venus-mars me-1 text-info"></i>{{ $surgery->patient?->gender === 'male' ? 'ذكر' : ($surgery->patient?->gender === 'female' ? 'أنثى' : 'غير محدد') }}
                            </span>
                            @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'حجز ضمان صحي' : 'حجز ضمان قوى الأمن' }}
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border">
                                    <i class="fas fa-money-bill-wave me-1"></i>حجز نقدي (كاش)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="clin-v-divider d-none d-md-block"></div>

                {{-- Procedure & Surgeon --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-procedures text-primary me-1"></i> تفاصيل العملية
                    </div>
                    <div class="fw-bold text-dark fs-6" title="{{ $surgery->surgery_type }}">
                        {{ $surgery->surgery_type ?? 'غير محدد' }}
                    </div>
                    <div class="text-secondary small mt-1 d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge-doctor-chip">
                            <i class="fas fa-user-md text-success me-1"></i> الجراح: د. {{ $surgery->doctor?->user?->full_name ?? $surgery->doctor?->user?->name ?? 'غير محدد' }}
                        </span>
                        <span class="badge-modern-sub">
                            <i class="fas fa-syringe text-primary me-1"></i> المخدر: {{ $primaryAnesthName }}
                        </span>
                    </div>
                </div>

                <div class="clin-v-divider d-none d-lg-block"></div>

                {{-- Location & Schedule --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-map-marker-alt text-warning me-1"></i> الموقع والجدولة
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                        <span class="badge-room-chip">
                            <i class="fas fa-door-open me-1"></i>{{ $surgery->location?->name ?? $surgery->operationTheater?->name ?? $surgery->room?->room_number ?? 'غير مخصصة' }}
                        </span>
                        <span class="badge-time-chip">
                            <i class="far fa-calendar-alt me-1"></i>{{ optional($surgery->scheduled_date)->format('Y-m-d') ?? '--' }}
                        </span>
                        <span class="badge-time-chip">
                            <i class="far fa-clock me-1"></i>{{ $surgery->scheduled_time ? (is_string($surgery->scheduled_time) ? \Carbon\Carbon::parse($surgery->scheduled_time)->format('h:i A') : $surgery->scheduled_time->format('h:i A')) : '--:--' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Status & Operational Control Buttons --}}
            <div class="d-flex flex-wrap align-items-center gap-2 ms-auto ms-lg-0">
                @if($surgery->status === 'scheduled')
                    <span class="badge-status-modern badge-status-waiting"><span class="status-pulse-dot bg-secondary"></span>مجدولة</span>
                @elseif($surgery->status === 'waiting')
                    <span class="badge-status-modern badge-status-waiting"><span class="status-pulse-dot bg-info"></span>في الانتظار</span>
                @elseif($surgery->status === 'in_progress')
                    <span class="badge-status-modern badge-status-progress"><span class="status-pulse-dot bg-warning"></span>جارية الآن</span>
                @elseif($surgery->status === 'completed')
                    <span class="badge-status-modern badge-status-completed"><span class="status-pulse-dot bg-success"></span>مكتملة</span>
                @else
                    <span class="badge-status-modern badge-status-cancelled"><span class="status-pulse-dot bg-danger"></span>ملغاة</span>
                @endif

                @if($user && $user->hasRole(['admin', 'surgery_staff', 'receptionist']))
                    @if($surgery->status === 'scheduled')
                        <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="d-inline mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning text-white rounded-pill px-3 fw-semibold" onclick="return confirm('هل تريد بدء العمل الجراحي الفعلي الآن؟')">
                                <i class="fas fa-play me-1"></i>بدء العملية
                            </button>
                        </form>
                    @endif

                    @if($surgery->status === 'in_progress')
                        <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="d-inline mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success rounded-pill px-3 fw-semibold" onclick="return confirm('هل تود نقل حالة العملية الجراحية إلى مكتملة؟')">
                                <i class="fas fa-check-double me-1"></i>إكمال العملية
                            </button>
                        </form>
                        <form action="{{ route('surgeries.return-to-waiting', $surgery) }}" method="POST" class="d-inline mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="return confirm('هل تود إعادة المريض لقائمة الانتظار؟')">
                                <i class="fas fa-history me-1"></i>إعادة للانتظار
                            </button>
                        </form>
                    @endif

                    @if($surgery->status !== 'cancelled' && $surgery->status !== 'completed')
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#cancelSurgeryModal">
                            <i class="fas fa-times-circle me-1"></i>إلغاء العملية
                        </button>
                    @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Station Flow Notification Banners --}}
    @if($surgery->status === 'in_progress' && $user)
        @if(!$surgery->operationTheaterStation || $surgery->operationTheaterStation->status !== 'completed')
            @if($user->can('view operation theater station'))
                <div class="alert alert-info border-info shadow-sm d-flex align-items-center justify-content-between mb-3 rounded-3" role="alert">
                    <div>
                        <i class="fas fa-clinic-medical fa-lg me-2 text-info"></i>
                        <strong>محطة صالة العمليات:</strong> المريض داخل صالة العمليات حالياً وبانتظار إنهاء هذه المرحلة.
                    </div>
                    <a href="{{ route('operation-theater-station.show', $surgery) }}" class="btn btn-info text-dark btn-sm fw-bold rounded-pill">
                        <i class="fas fa-edit me-1"></i>الانتقال لمحطة صالة العمليات
                    </a>
                </div>
            @endif
        @elseif(!$surgery->surgeonStation || $surgery->surgeonStation->status !== 'completed')
            @if($user->can('view surgeon station') && ! $isAnesthesiaUser)
                <div class="alert alert-primary border-primary shadow-sm d-flex align-items-center mb-3 rounded-3" role="alert">
                    <div>
                        <i class="fas fa-user-md fa-lg me-2 text-primary"></i>
                        <strong>محطة الطبيب الجراح:</strong> يرجى إدخال تفاصيل العملية (التشخيص والعلاجات) وحفظ البيانات لإتمام محطة الجراح ونقلها لمحطة التخدير تلقائياً.
                    </div>
                </div>
            @endif
        @elseif(!$surgery->anesthesiaStation || $surgery->anesthesiaStation->status !== 'completed')
            @if($user->can('view anesthesia station') || $isAnesthesiaUser)
                <div class="alert alert-success border-success shadow-sm d-flex align-items-center mb-3 rounded-3" role="alert">
                    <div>
                        <i class="fas fa-syringe fa-lg me-2 text-success"></i>
                        <strong>محطة التخدير:</strong> العملية بانتظار توثيق طبيب التخدير في تبويب «تفاصيل التخدير».
                    </div>
                </div>
            @endif
        @elseif(!$surgery->nursingStation || $surgery->nursingStation->status !== 'completed')
            @if($user->can('view nursing station'))
                <div class="alert alert-info border-info shadow-sm d-flex align-items-center justify-content-between mb-3 rounded-3" role="alert">
                    <div>
                        <i class="fas fa-user-nurse fa-lg me-2 text-info"></i>
                        <strong>محطة التمريض:</strong> المريض في مرحلة الملاحظة التمريضية النهائية قبل الخروج.
                    </div>
                    <a href="{{ route('nursing-station.show', $surgery) }}" class="btn btn-info text-dark btn-sm fw-bold rounded-pill">
                        <i class="fas fa-edit me-1"></i>الانتقال لمحطة التمريض وإخراج المريض
                    </a>
                </div>
            @endif
        @endif
    @endif

    @if(empty($visibleTabs))
        <div class="alert alert-warning text-center p-5 rounded-4 shadow-sm">
            <i class="fas fa-lock fa-3x text-warning mb-3 d-block"></i>
            <h5 class="fw-bold">لا تملك صلاحية الوصول لأي تبويب في ملف العملية</h5>
            <p class="text-muted mb-0">يرجى مراجعة إدارة المستشفى لمنحك الصلاحيات المناسبة لدورك الوظيفي.</p>
        </div>
    @else
        {{-- Unified Horizontal Auto-Fit Tabs Bar --}}
        <div class="clin-tabs-wrapper mb-3">
            <ul class="clin-tabs-grid nav" id="surgeryDetailTabs" role="tablist">
                @foreach($visibleTabs as $tKey => $tMeta)
                    <li class="clin-tab-item" role="presentation">
                        <button class="clin-nav-tab {{ $activeTab === $tKey ? 'active' : '' }}"
                                id="tab-btn-{{ $tKey }}"
                                data-bs-toggle="pill"
                                data-bs-target="#pane-{{ $tKey }}"
                                data-tab-key="{{ $tKey }}"
                                type="button"
                                role="tab"
                                aria-controls="pane-{{ $tKey }}"
                                aria-selected="{{ $activeTab === $tKey ? 'true' : 'false' }}"
                                title="{{ $tMeta['label'] }}">
                            <i class="{{ $tMeta['icon'] }} small"></i>
                            <span>{{ $shortTabLabels[$tKey] ?? $tMeta['label'] }}</span>
                            @if($tMeta['can_write'])
                                <span class="badge-perm-dot badge-perm-write" title="صلاحية إدخال وتعديل"><i class="fas fa-pen"></i></span>
                            @else
                                <span class="badge-perm-dot badge-perm-read" title="صلاحية قراءة فقط"><i class="fas fa-eye"></i></span>
                            @endif
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Unified Tab Content Panes --}}
        <div class="tab-content" id="surgeryDetailTabContent">

            {{-- TAB 1: الفحوصات والتحاليل --}}
            @if(isset($visibleTabs['detail_tests']))
                <div class="tab-pane fade {{ $activeTab === 'detail_tests' ? 'show active' : '' }}" id="pane-detail_tests" role="tabpanel" aria-labelledby="tab-btn-detail_tests">
                    <div class="card clin-tab-card">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-vials me-2"></i>التحاليل والفحوصات الطبية
                                </h6>
                                <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-eye me-1"></i>سجل النتائج</span>
                            </div>

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

                            <div class="row g-4">
                                <div class="col-lg-6">
                                    <p class="fw-bold text-primary mb-2 pb-1 border-bottom">
                                        <i class="fas fa-flask me-2"></i>التحاليل والفحوصات المطلوبة للعملية
                                    </p>
                                    <div class="mb-4 table-responsive rounded-3 border" style="border-color:#bfdbfe !important;">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                            <thead style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                                                <tr>
                                                    <th class="px-3 py-2 text-primary fw-bold border-0"><i class="fas fa-vial me-1"></i>التحاليل المخبرية</th>
                                                    <th class="py-2 text-muted fw-semibold border-0">النتيجة</th>
                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                    <th class="py-2 border-0"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($surgery->labTests as $labTest)
                                                    <tr>
                                                        <td class="px-3 fw-semibold text-dark">{{ optional($labTest->labTest)->name ?? 'تحليل طبي' }}</td>
                                                        <td>@if($labTest->result)<strong class="text-dark">{{ $labTest->result }}</strong>@else<span class="text-muted small">—</span>@endif</td>
                                                        <td class="text-center"><span class="badge bg-{{ $labTest->status_color }}">{{ $labTest->status_text }}</span></td>
                                                        <td>
                                                            @if($labTest->result_file)
                                                                <a href="{{ asset('storage/' . $labTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-download"></i></a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد تحاليل مخبرية مخصصة للعملية</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="table-responsive rounded-3 border" style="border-color:#bbf7d0 !important;">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                            <thead style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                                                <tr>
                                                    <th class="px-3 py-2 text-success fw-bold border-0"><i class="fas fa-x-ray me-1"></i>الأشعة والتصوير</th>
                                                    <th class="py-2 text-muted fw-semibold border-0">التقرير</th>
                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                    <th class="py-2 border-0"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($surgery->radiologyTests as $radTest)
                                                    <tr>
                                                        <td class="px-3 fw-semibold text-dark">{{ optional($radTest->radiologyType)->name ?? 'تصوير طبي' }}</td>
                                                        <td>@if($radTest->result)<span class="text-dark small">{{ $radTest->result }}</span>@else<span class="text-muted small">—</span>@endif</td>
                                                        <td class="text-center"><span class="badge bg-{{ $radTest->status_color }}">{{ $radTest->status_text }}</span></td>
                                                        <td>
                                                            @if($radTest->result_file)
                                                                <a href="{{ asset('storage/' . $radTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2"><i class="fas fa-download"></i></a>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد طلبات أشعة وتصوير للعملية</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <p class="fw-bold text-success mb-2 pb-1 border-bottom">
                                        <i class="fas fa-history me-2"></i>الفحوصات والتحاليل السابقة بالزيارة
                                    </p>
                                    @if($surgery->visit)
                                        <div class="mb-4 table-responsive rounded-3 border" style="border-color:#e9d5ff !important;">
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
                                                        <tr><td colspan="4" class="text-center text-muted py-3">لا توجد نتائج مختبر للزيارة</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>

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
                                                            <td class="text-center"><span class="badge bg-{{ $radReq->status_color }}">{{ $radReq->status_text }}</span></td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="3" class="text-center text-muted py-3">لا توجد طلبات أشعة للزيارة</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
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
                    </div>
                </div>
            @endif

            {{-- TAB 2: نوع العملية والإضافات --}}
            @if(isset($visibleTabs['detail_type']))
                @php
                    $canWriteType = $visibleTabs['detail_type']['can_write'];
                    $protocol = $surgery->surgeonStation?->monitoring_protocol ?? null;
                    $typeChanges = $surgery->surgeryTypeChanges()->latest()->get();
                @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_type' ? 'show active' : '' }}" id="pane-detail_type" role="tabpanel" aria-labelledby="tab-btn-detail_type">
                    <div class="card clin-tab-card">
                        <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-procedures me-2"></i>نوع العملية والعمليات الإضافية
                                </h6>
                                @if($canWriteType)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-pen me-1"></i>متاح للتعديل</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-eye me-1"></i>قراءة فقط</span>
                                @endif
                            </div>

                            {{-- Section 1: Main Procedure Summary & Classification Chips --}}
                            <div class="clin-section-card">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div>
                                        <div class="clin-section-title text-primary mb-1">
                                            <i class="fas fa-scalpel"></i> نوع العملية الرئيسي والتصنيف
                                        </div>
                                        <div class="fw-bold text-dark fs-5">{{ $surgery->surgery_type ?? 'غير محدد' }}</div>
                                    </div>

                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        @if($surgery->surgery_type_detail)
                                            <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle px-2 py-1">
                                                <i class="fas fa-tag me-1"></i>التصنيف: {{ $surgery->surgery_type_detail }}
                                            </span>
                                        @endif
                                        @if($currentAnesthesiaType)
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                <i class="fas fa-syringe me-1"></i>التخدير: {{ $currentAnesthesiaType }}
                                            </span>
                                        @endif
                                        @if($protocol)
                                            <span class="badge bg-dark-subtle text-dark border px-2 py-1">
                                                <i class="fas fa-chart-line me-1"></i>المراقبة:
                                                @switch($protocol)
                                                    @case('standard') قياسي @break
                                                    @case('fluid_monitoring') مراقبة السوائل @break
                                                    @case('intensive') مكثف @break
                                                    @default {{ $protocol }}
                                                @endswitch
                                            </span>
                                        @endif

                                        @if($canWriteType)
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="toggleSurgeryTypeArea()">
                                                <i class="fas fa-exchange-alt me-1"></i>تغيير نوع العملية
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                @if($canWriteType)
                                    <div id="surgeryTypeChangeArea" style="display: none;" class="mt-3 p-3 border rounded-3 bg-white">
                                        <input type="hidden" id="surgeryTypeCsrf" value="{{ csrf_token() }}">
                                        <div class="row g-2">
                                            <div class="col-md-8">
                                                <input type="text" id="surgeryTypeSearch" class="form-control form-control-sm mb-2" placeholder="ابحث عن نوع العملية..." oninput="filterSurgeryTypes()">
                                                <select id="surgery_type_select" class="form-select form-select-sm border-primary" size="6" onchange="toggleSurgeryTypeInput()">
                                                    <option value="">-- اختر نوع العملية --</option>
                                                    @foreach($surgicalOperations->groupBy('category') as $category => $operations)
                                                        <optgroup label="{{ $category }}">
                                                            @foreach($operations as $op)
                                                                <option value="{{ $op->name }}" data-op-id="{{ $op->id }}">{{ $op->name }}</option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                    <option value="other">أخرى (يدوي)</option>
                                                </select>
                                                <div id="custom_surgery_type_wrapper" style="display: none;" class="mt-2">
                                                    <input type="text" id="custom_surgery_type" class="form-control form-control-sm border-primary" placeholder="اكتب نوع العملية يدوياً...">
                                                </div>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end gap-2">
                                                <button type="button" class="btn btn-primary btn-sm" onclick="submitSurgeryTypeChange()">
                                                    <i class="fas fa-check me-1"></i>تأكيد
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleSurgeryTypeArea()">إلغاء</button>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Section 1.5: Direct Procedure Notes (surgical_notes) --}}
                            <div class="clin-section-card clin-surface-white">
                                @if($canWriteType)
                                    <form id="detailTypeNotesForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="tab" value="detail_type">
                                @endif
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                            <label for="surgical_notes" class="clin-section-title text-primary mb-0">
                                                <i class="fas fa-sticky-note"></i> ملاحظات نوع العملية
                                            </label>
                                            @if($canWriteType)
                                                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-semibold shadow-sm">
                                                    <i class="fas fa-save me-1"></i>حفظ الملاحظات
                                                </button>
                                            @endif
                                        </div>
                                        <textarea id="surgical_notes" @if($canWriteType) name="surgical_notes" @else readonly disabled @endif class="form-control clin-compact-textarea {{ $canWriteType ? 'border-primary-subtle' : 'bg-light' }}" rows="2" placeholder="أدخل أي ملاحظات أو تفاصيل إضافية متعلقة بنوع العملية...">{{ old('surgical_notes', $surgery->surgical_notes) }}</textarea>
                                @if($canWriteType)
                                    </form>
                                @endif
                            </div>

                            {{-- Section 2: Side-by-Side Additional Operations & Type Change Log --}}
                            <div class="row g-3 align-items-start">
                                <div class="col-lg-7">
                                    <div class="clin-section-card clin-surface-white h-100">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="clin-section-title text-success">
                                                <i class="fas fa-plus-circle"></i> العمليات الإضافية / التكميلية ({{ $surgery->additionalOperations->count() }})
                                            </span>
                                            @if($canWriteType)
                                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3" onclick="toggleAddOperationArea()">
                                                    <i class="fas fa-plus me-1"></i>إضافة عملية
                                                </button>
                                            @endif
                                        </div>

                                        @if($surgery->additionalOperations->count() > 0)
                                            <div class="table-responsive rounded-2 border">
                                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.86rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>العملية</th>
                                                            <th>السعر</th>
                                                            <th>ملاحظات</th>
                                                            <th>التاريخ</th>
                                                            @if($canWriteType)
                                                                <th class="text-center" width="50">حذف</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($surgery->additionalOperations as $addOp)
                                                            <tr>
                                                                <td class="fw-semibold">{{ $addOp->surgicalOperation?->name ?? 'غير محدد' }}</td>
                                                                <td class="fw-bold text-success">{{ number_format($addOp->fee ?? 0) }} د.ع</td>
                                                                <td class="small">{{ $addOp->notes ?? '-' }}</td>
                                                                <td class="text-muted small">{{ optional($addOp->created_at)->format('Y-m-d H:i') }}</td>
                                                                @if($canWriteType)
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="submitDeleteAction('{{ route('surgeries.removeOperation', [$surgery, $addOp]) }}', 'حذف هذه العملية الإضافية؟')">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-3 text-muted small border rounded-2 bg-light">
                                                لا توجد عمليات إضافية مسجلة.
                                            </div>
                                        @endif

                                        @if($canWriteType)
                                            <div id="addOperationArea" style="display: none;" class="mt-3 p-3 border rounded-3 bg-light">
                                                <input type="hidden" id="addOpCsrf" value="{{ csrf_token() }}">
                                                <div class="row g-2">
                                                    <div class="col-md-8">
                                                        <input type="text" id="addOpSearch" class="form-control form-control-sm mb-2" placeholder="ابحث عن العملية..." oninput="filterAddOperations()">
                                                        <select id="add_op_select" class="form-select form-select-sm border-primary" size="6" multiple>
                                                            @foreach($surgicalOperations->groupBy('category') as $category => $operations)
                                                                <optgroup label="{{ $category }}">
                                                                    @foreach($operations as $op)
                                                                        <option value="{{ $op->id }}">{{ $op->name }}</option>
                                                                    @endforeach
                                                                </optgroup>
                                                            @endforeach
                                                        </select>
                                                        <small class="text-muted d-block mt-1">اضغط Ctrl + اختر لاختيار عدة عمليات</small>
                                                    </div>
                                                    <div class="col-md-4 d-flex flex-column gap-2">
                                                        <textarea id="add_op_notes" class="form-control form-control-sm" rows="3" placeholder="ملاحظات..."></textarea>
                                                        <button type="button" class="btn btn-success btn-sm" onclick="submitAddOperation()">
                                                            <i class="fas fa-check me-1"></i>إضافة
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddOperationArea()">إلغاء</button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="clin-section-card clin-surface-white h-100">
                                        <div class="clin-section-title text-secondary mb-2">
                                            <i class="fas fa-history"></i> سجل تغييرات نوع العملية ({{ $typeChanges->count() }})
                                        </div>
                                        @if($typeChanges->count() > 0)
                                            <div class="table-responsive rounded-2 border">
                                                <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.82rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>القديم ← الجديد</th>
                                                            <th>التاريخ / بواسطة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($typeChanges as $change)
                                                            <tr>
                                                                <td>
                                                                    <div class="text-danger small"><s>{{ $change->old_type ?? '-' }}</s></div>
                                                                    <div class="text-success fw-bold">{{ $change->new_type }}</div>
                                                                </td>
                                                                <td class="text-muted small">
                                                                    <div>{{ $change->created_at->format('Y-m-d h:i A') }}</div>
                                                                    <div>{{ $change->changedBy?->name ?? 'غير معروف' }}</div>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-3 text-muted small border rounded-2 bg-light">
                                                لم يطرأ أي تغيير على نوع العملية الأساسي.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 3: التشخيص وخطة العلاج --}}
            @if(isset($visibleTabs['detail_diagnosis']))
                @php $canWriteDiagnosis = $visibleTabs['detail_diagnosis']['can_write']; @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_diagnosis' ? 'show active' : '' }}" id="pane-detail_diagnosis" role="tabpanel" aria-labelledby="tab-btn-detail_diagnosis">
                    <div class="card clin-tab-card">
                        @if($canWriteDiagnosis)
                            <form id="detailDiagnosisForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="tab" value="detail_diagnosis">
                        @endif
                        <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-stethoscope me-2"></i>التشخيص الطبي للجراح وجدول العلاجات
                                </h6>
                                @if($canWriteDiagnosis)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-pen me-1"></i>متاح للتعديل</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-lock me-1"></i>للقراءة فقط</span>
                                @endif
                            </div>

                            @if(!$canWriteDiagnosis)
                                <div class="alert alert-info border-info bg-info bg-opacity-10 py-2 mb-0 small">
                                    <i class="fas fa-eye me-1"></i> هذا القسم للعرض فقط وفق صلاحيات دورك الوظيفي؛ يتم توثيق التشخيص والعلاجات من قِبَل الطبيب الجراح.
                                </div>
                            @elseif($isSurgeryStaff && empty($surgery->diagnosis))
                                <div class="alert alert-warning border-warning bg-warning bg-opacity-10 py-2 mb-0 small">
                                    <i class="fas fa-exclamation-triangle me-1"></i> لم يتم إدخال التشخيص الطبي للعملية بعد.
                                </div>
                            @endif

                            {{-- Section 1: Compact 3-Column Clinical Summary Grid --}}
                            <div class="clin-section-card">
                                <div class="clin-section-title text-primary mb-2">
                                    <i class="fas fa-notes-medical"></i> التشخيص والخطة السريرية للجراح
                                </div>
                                <div class="row g-2">
                                    <div class="col-lg-4">
                                        <div class="clin-field-card">
                                            <label for="diagnosis" class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                <i class="fas fa-stethoscope text-primary"></i>
                                                <span>التشخيص الطبي للعملية</span>
                                            </label>
                                            <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="diagnosis" @if($canWriteDiagnosis) name="diagnosis" @else readonly disabled @endif rows="3" placeholder="أدخل التشخيص الطبي المفصل للمريض...">{{ old('diagnosis', $surgery->diagnosis) }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="clin-field-card">
                                            <label for="treatment_plan" class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                <i class="fas fa-file-medical text-info"></i>
                                                <span>خطة العلاج والتوصيات الجراحية</span>
                                            </label>
                                            <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="treatment_plan" @if($canWriteDiagnosis) name="treatment_plan" @else readonly disabled @endif rows="3" placeholder="خطة العلاج وتوصيات ما بعد العملية...">{{ old('treatment_plan', $surgery->treatment_plan) }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="clin-field-card">
                                            <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                <label for="post_op_notes" class="form-label fw-bold text-dark small mb-0 d-flex align-items-center gap-1">
                                                    <i class="fas fa-clipboard-check text-success"></i>
                                                    <span>ملاحظات ما بعد العملية</span>
                                                </label>
                                                @if($canWriteDiagnosis)
                                                    <button type="button" class="clin-quick-chip" onclick="fillDetailQuickText('post_op_notes', 'حالة مستقرة — دون مضاعفات أثناء التداخل الجراحي')">حالة مستقرة</button>
                                                @endif
                                            </div>
                                            <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="post_op_notes" @if($canWriteDiagnosis) name="post_op_notes" @else readonly disabled @endif rows="3" placeholder="ملاحظات الجراح بعد التداخل...">{{ old('post_op_notes', $surgery->post_op_notes) }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Medications & Treatment Table --}}
                            <div class="clin-section-card clin-surface-white">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="clin-section-title text-primary">
                                        <i class="fas fa-pills"></i> جدول علاج العمليات الموصوف
                                    </span>
                                    @if($canWriteDiagnosis)
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="addSurgeryTreatment()">
                                            <i class="fas fa-plus me-1"></i>إضافة علاج جديد
                                        </button>
                                    @endif
                                </div>

                                @if($canWriteDiagnosis)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0" id="surgeryTreatmentsTable" style="font-size:0.88rem;">
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
                                                @forelse($editableTreatments as $index => $treatment)
                                                    <tr class="treatment-item">
                                                        <td class="text-center row-number">{{ $index + 1 }}</td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][description]" value="{{ $treatment->description ?? '' }}" placeholder="اسم الدواء أو وصف العلاج" required>
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][dosage]" value="{{ $treatment->dosage ?? '' }}" placeholder="مثال: 500mg, 2ml">
                                                        </td>
                                                        <td>
                                                            <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][timing]" rows="1" placeholder="مثال: كل 6 ساعات">{{ $treatment->timing ?? '' }}</textarea>
                                                        </td>
                                                        <td>
                                                            <div class="duration-input-group">
                                                                <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][{{ $index }}][duration_value]" value="{{ $treatment->duration_value ?? '' }}" placeholder="العدد" min="1">
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
                                                @empty
                                                    <tr id="emptySurgeryTreatmentsRow">
                                                        <td colspan="6" class="text-center py-3 text-muted">
                                                            <i class="fas fa-pills me-1 opacity-50"></i>
                                                            لا توجد علاجات مسجلة — اضغط "إضافة علاج جديد" للبدء
                                                        </td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.88rem;">
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
                                                @forelse($savedMedications as $index => $treatment)
                                                    <tr>
                                                        <td class="text-center text-muted">{{ $index + 1 }}</td>
                                                        <td class="fw-semibold text-dark">{{ $treatment->description ?? '—' }}</td>
                                                        <td class="text-muted">{{ $treatment->dosage ?? '—' }}</td>
                                                        <td class="text-muted">{{ $treatment->timing ?? '—' }}</td>
                                                        <td class="text-muted">
                                                            @if($treatment->duration_value)
                                                                @php $units=['days'=>'يوم','weeks'=>'أسبوع','months'=>'شهر','hours'=>'ساعة','doses'=>'جرعة']; @endphp
                                                                {{ $treatment->duration_value }} {{ $units[$treatment->duration_unit] ?? '' }}
                                                            @else
                                                                —
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center py-3 text-muted">لم يُدخل الطبيب الجراح خطة العلاج بعد</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                @endif

                                @if($canWriteDiagnosis && $marBatchTreatments->isNotEmpty())
                                    <div class="mt-3 pt-2 border-top">
                                        <div class="small fw-bold text-secondary mb-1"><i class="fas fa-layer-group me-1"></i>أدوية مضافة من المحطة السريرية الموحدة (MAR)</div>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>الدواء</th>
                                                        <th>الجرعة</th>
                                                        <th>التوقيت</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($marBatchTreatments as $marMed)
                                                        <tr>
                                                            <td class="fw-semibold">{{ $marMed->description }}</td>
                                                            <td>{{ $marMed->dosage ?? '—' }}</td>
                                                            <td>{{ $marMed->timing ?? '—' }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($canWriteDiagnosis)
                            <div class="card-footer bg-light text-end py-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>حفظ التشخيص والعلاجات
                                </button>
                            </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- TAB 4: تفاصيل التخدير --}}
            @if(isset($visibleTabs['detail_anesthesia']))
                @php $canWriteAnesthesia = $visibleTabs['detail_anesthesia']['can_write']; @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_anesthesia' ? 'show active' : '' }}" id="pane-detail_anesthesia" role="tabpanel" aria-labelledby="tab-btn-detail_anesthesia">
                    <div class="card clin-tab-card">
                        @if($canWriteAnesthesia)
                            <form id="detailAnesthesiaForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="tab" value="detail_anesthesia">
                        @endif
                        <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <h6 class="fw-bold text-success mb-0">
                                    <i class="fas fa-syringe me-2"></i>توثيق تفاصيل التخدير
                                </h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $surgery->anesthesiaStation?->status === 'completed' ? 'bg-success' : ($surgery->anesthesiaStation?->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                        حالة المحطة:
                                        {{ $surgery->anesthesiaStation?->status === 'completed' ? 'مكتملة' : ($surgery->anesthesiaStation?->status === 'in_progress' ? 'قيد العمل' : 'معلقة') }}
                                    </span>
                                    @if($canWriteAnesthesia)
                                        <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-pen me-1"></i>وضع التعديل نشط</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-lock me-1"></i>للقراءة فقط</span>
                                    @endif
                                </div>
                            </div>

                            @if(!$canWriteAnesthesia)
                                <div class="alert alert-secondary border-secondary bg-white py-2 mb-0 small">
                                    <i class="fas fa-eye me-2"></i>
                                    هذا القسم للعرض فقط وفق صلاحيات دورك الوظيفي؛ يتم توثيق تفاصيل التخدير من قِبَل طبيب التخدير.
                                </div>
                            @endif

                            {{-- Section 1: Anesthesia Type --}}
                            <div class="clin-section-card">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <label for="anesthesia_type_input" class="clin-section-title text-success mb-0">
                                            <i class="fas fa-syringe"></i> نوع التخدير
                                        </label>
                                        @if($canWriteAnesthesia)
                                            <div class="d-flex flex-wrap gap-1">
                                                <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير عام (General Anesthesia)')">عام</button>
                                                <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير نصفي (Spinal Anesthesia)')">نصفي</button>
                                                <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير موضعي (Local Anesthesia)')">موضعي</button>
                                            </div>
                                        @endif
                                    </div>
                                    <span class="badge bg-light text-dark border small fw-normal">
                                        <i class="fas fa-user-md me-1 text-success"></i>طبيب التخدير: {{ $primaryAnesthName }}@if($secondaryAnesthId) — المساعد: {{ $secondaryAnesthName }}@endif
                                    </span>
                                </div>
                                @if($canWriteAnesthesia)
                                    <input type="text" id="anesthesia_type_input" name="anesthesia_type" list="anesthesiaTypesList" class="form-control form-control-sm border-success-subtle" value="{{ old('anesthesia_type', $currentAnesthesiaType) }}" placeholder="مثال: تخدير عام (General Anesthesia)">
                                    <datalist id="anesthesiaTypesList">
                                        <option value="تخدير عام (General Anesthesia)">
                                        <option value="تخدير نصفي (Spinal Anesthesia)">
                                        <option value="تخدير فوق الجافية (Epidural Anesthesia)">
                                        <option value="تخدير موضعي (Local Anesthesia)">
                                        <option value="تخدير ناحي (Regional Anesthesia)">
                                        <option value="تسكين واعي (Conscious Sedation)">
                                    </datalist>
                                @else
                                    <input type="text" class="form-control form-control-sm bg-light" value="{{ $currentAnesthesiaType ?: 'غير محدد' }}" readonly disabled>
                                @endif
                            </div>

                            {{-- Section 2: Anesthesia Clinical Notes --}}
                            <div class="clin-section-card clin-surface-white">
                                <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                    <label for="anesthesia_notes" class="clin-section-title text-success mb-0">
                                        <i class="fas fa-notes-medical"></i> ملاحظات وتفاصيل طبيب التخدير
                                    </label>
                                    @if($canWriteAnesthesia)
                                        <button type="button" class="clin-quick-chip" onclick="fillDetailQuickText('anesthesia_notes', 'مستقر خلال التداخل، مجرى الهواء سليم، إفاقة سلسة دون مضاعفات')">إفاقة مستقرة</button>
                                    @endif
                                </div>
                                <textarea id="anesthesia_notes" @if($canWriteAnesthesia) name="anesthesia_notes" @else readonly disabled @endif class="form-control clin-compact-textarea {{ $canWriteAnesthesia ? 'border-success-subtle' : 'bg-light' }}" rows="3" placeholder="سجل العلامات التخديرية، الأدوية المستخدمة، تقييم مجرى الهواء، وملاحظات الإفاقة...">{{ old('anesthesia_notes', $currentAnesthesiaNotes) }}</textarea>
                            </div>
                        </div>

                        @if($canWriteAnesthesia)
                            <div class="card-footer bg-light d-flex flex-wrap justify-content-end gap-2 py-3 border-top">
                                <button type="submit" class="btn btn-success px-4 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>حفظ تفاصيل التخدير
                                </button>

                                @if($user && $user->can('view anesthesia station') && $surgery->anesthesiaStation && $surgery->anesthesiaStation->status !== 'completed')
                                    <button type="button" class="btn btn-outline-success px-4 rounded-pill fw-bold" onclick="completeAnesthesia({{ $surgery->id }})">
                                        <i class="fas fa-check-double me-1"></i>اعتماد وإتمام محطة التخدير
                                    </button>
                                @endif
                            </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- TAB 5: الفريق الطبي والتوقيت --}}
            @if(isset($visibleTabs['detail_team_timing']))
                @php $canWriteTeamTiming = $visibleTabs['detail_team_timing']['can_write']; @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_team_timing' ? 'show active' : '' }}" id="pane-detail_team_timing" role="tabpanel" aria-labelledby="tab-btn-detail_team_timing">
                    <div class="card clin-tab-card">
                        @if($canWriteTeamTiming)
                            <form id="detailTeamTimingForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="tab" value="detail_team_timing">
                        @endif
                        <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                <h6 class="fw-bold text-warning-emphasis mb-0">
                                    <i class="fas fa-users-cog me-2 text-warning"></i>الفريق الطبي والتوقيت الفعلي للعملية
                                </h6>
                                @if($canWriteTeamTiming)
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-pen me-1"></i>متاح للتعديل</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-lock me-1"></i>للقراءة فقط</span>
                                @endif
                            </div>

                            {{-- Section 1: Operating Location & Surgical/Anesthesia Team --}}
                            <div class="clin-section-card">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                    <span class="clin-section-title text-primary">
                                        <i class="fas fa-users"></i> صالة العمليات والفريق الطبي المشارك
                                    </span>
                                    <span class="badge-doctor-chip">
                                        <i class="fas fa-user-md me-1"></i> الجراح الرئيسي: د. {{ $surgery->doctor?->user?->full_name ?? $surgery->doctor?->user?->name ?? 'غير محدد' }}
                                    </span>
                                </div>

                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="clin-field-card">
                                            <label for="team_location_id" class="form-label fw-bold text-dark small mb-1">
                                                <i class="fas fa-map-marker-alt me-1 text-danger"></i>صالة العمليات / الردهة
                                            </label>
                                            @if($canWriteTeamTiming)
                                                <select id="team_location_id" name="location_id" class="form-select form-select-sm border-primary">
                                                    <option value="">اختر صالة العمليات / الردهة</option>
                                                    @foreach($locations as $loc)
                                                        <option value="{{ $loc->id }}" {{ old('location_id', $surgery->location_id) == $loc->id ? 'selected' : '' }}>
                                                            {{ $loc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $surgery->location?->name ?? 'غير محدد' }}" readonly disabled>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="clin-field-card">
                                            <label for="surgical_assistant_name" class="form-label fw-bold text-dark small mb-1">اسم مساعد الجراح (خارجي أو ممرض مساعد)</label>
                                            <input type="text" id="surgical_assistant_name" @if($canWriteTeamTiming) name="surgical_assistant_name" @else readonly disabled @endif class="form-control form-control-sm {{ $canWriteTeamTiming ? '' : 'bg-light' }}" value="{{ old('surgical_assistant_name', $surgery->anesthesiaStation?->surgical_assistant_name ?? $surgery->surgical_assistant_name ?? '') }}" placeholder="أدخل اسم المساعد الجراحي...">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="clin-field-card">
                                            <label for="team_anesthesiologist_id" class="form-label fw-bold text-dark small mb-1">طبيب التخدير الأول</label>
                                            @if($canWriteTeamTiming)
                                                <select id="team_anesthesiologist_id" name="anesthesiologist_id" class="form-select form-select-sm">
                                                    <option value="">اختر الطبيب المخدر الأول</option>
                                                    @foreach($anesthesiaDoctors as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ old('anesthesiologist_id', $primaryAnesthId) == $doctor->id ? 'selected' : '' }}>
                                                            د. {{ $doctor->user?->full_name ?? $doctor->user?->name ?? 'طبيب تخدير' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $primaryAnesthName }}" readonly disabled>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="clin-field-card">
                                            <label for="team_anesthesiologist_2_id" class="form-label fw-bold text-dark small mb-1">طبيب التخدير المساعد</label>
                                            @if($canWriteTeamTiming)
                                                <select id="team_anesthesiologist_2_id" name="anesthesiologist_2_id" class="form-select form-select-sm">
                                                    <option value="">اختر الطبيب المخدر المساعد</option>
                                                    @foreach($anesthesiaDoctors as $doctor)
                                                        <option value="{{ $doctor->id }}" {{ old('anesthesiologist_2_id', $secondaryAnesthId) == $doctor->id ? 'selected' : '' }}>
                                                            د. {{ $doctor->user?->full_name ?? $doctor->user?->name ?? 'طبيب تخدير' }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="text" class="form-control form-control-sm bg-light" value="{{ $secondaryAnesthName }}" readonly disabled>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section 2: Actual Surgery Timing & Calculated Duration --}}
                            <div class="clin-section-card clin-surface-white">
                                <div class="clin-section-title text-primary mb-2">
                                    <i class="fas fa-clock"></i> التوقيت الفعلي ومدة العملية الجراحية
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-4">
                                        <div class="clin-field-card">
                                            <label for="start_time" class="form-label fw-bold text-dark small mb-1">وقت بدء العملية</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-play text-success"></i></span>
                                                <input type="time" class="form-control border-start-0 {{ $canWriteTeamTiming ? '' : 'bg-light' }}" id="start_time" @if($canWriteTeamTiming) name="start_time" @else readonly disabled @endif value="{{ old('start_time', $surgeryStartFormatted) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="clin-field-card">
                                            <label for="end_time" class="form-label fw-bold text-dark small mb-1">وقت انتهاء العملية</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-stop text-danger"></i></span>
                                                <input type="time" class="form-control border-start-0 {{ $canWriteTeamTiming ? '' : 'bg-light' }}" id="end_time" @if($canWriteTeamTiming) name="end_time" @else readonly disabled @endif value="{{ old('end_time', $surgeryEndFormatted) }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="clin-field-card">
                                            <label for="estimated_duration" class="form-label fw-bold text-dark small mb-1">المدة المستغرقة (س:د)</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-hourglass-half text-primary"></i></span>
                                                <input type="text" class="form-control bg-light fw-bold text-primary border-start-0 text-center" id="estimated_duration" @if($canWriteTeamTiming) name="estimated_duration" @endif value="{{ $surgery->estimated_duration ? sprintf('%02d:%02d', floor($surgery->estimated_duration / 60), $surgery->estimated_duration % 60) : '' }}" readonly placeholder="--:--">
                                            </div>
                                            @if($canWriteTeamTiming)
                                                <input type="hidden" id="estimated_duration_minutes" name="estimated_duration_minutes" value="{{ $surgery->estimated_duration ?? '' }}">
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($canWriteTeamTiming)
                            <div class="card-footer bg-light text-end py-3 border-top">
                                <button type="submit" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>حفظ الفريق الطبي والتوقيت
                                </button>
                            </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endif

            {{-- TAB 6: المستلزمات والأجهزة الطبية --}}
            @if(isset($visibleTabs['detail_supplies']))
                @php $canWriteSupplies = $visibleTabs['detail_supplies']['can_write']; @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_supplies' ? 'show active' : '' }}" id="pane-detail_supplies" role="tabpanel" aria-labelledby="tab-btn-detail_supplies">
                    <div class="row g-3 align-items-start">
                        {{-- Column 1: Direct OR Sub-Store Warehouse Dispense (Zero Manual JSON) --}}
                        <div class="col-xl-7">
                            <div class="card clin-tab-card h-100">
                                <div class="card-body p-3 p-md-4 d-flex flex-column gap-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom pb-2">
                                        <div>
                                            <h6 class="fw-bold text-primary mb-0">
                                                <i class="fas fa-boxes me-2"></i>صرف مستلزمات ومواد {{ $operationStoreLocation?->name ?? 'مخزن العمليات' }} ({{ $orDispensedItems->count() }})
                                            </h6>
                                            @if($operationStoreLocation)
                                                <small class="text-muted">الصرف المباشر يخصم تلقائياً من عهدة وجبات {{ $operationStoreLocation->name }}</small>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ route('surgeries.index', array_filter(['tab' => 'or_store', 'location_id' => $operationStoreLocation?->id])) }}"
                                               class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                                <i class="fas fa-warehouse me-1"></i>جرد وعهدة العمليات
                                            </a>
                                            @if(!$canWriteSupplies)
                                                <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-lock me-1"></i>للقراءة فقط</span>
                                            @endif
                                        </div>
                                    </div>

                                    @if($canWriteSupplies)
                                        @if(!$operationStoreLocation)
                                            <div class="alert alert-warning rounded-3 mb-0">
                                                <i class="fas fa-exclamation-triangle me-2"></i>
                                                لم يتم العثور على مخزن فرعي للعمليات (مثل <strong>مخزن عمليات</strong>). يرجى تعريفه في إدارة المخازن أولاً.
                                            </div>
                                        @elseif($operationSupplyBatches->isEmpty())
                                            <div class="clin-section-card text-center py-4 mb-0">
                                                <i class="fas fa-box-open fa-2x text-warning mb-2 d-block"></i>
                                                <div class="fw-bold text-dark mb-1">لا يوجد رصيد متوفر حالياً في {{ $operationStoreLocation->name }}</div>
                                                <p class="small text-muted mb-3">يرجى طلب تعزيز مواد من المخزن الرئيسي لإتاحة الصرف المباشر للمريض.</p>
                                                <a href="{{ route('stock-transfers.create', ['to_location_id' => $operationStoreLocation->id]) }}"
                                                   class="btn btn-warning btn-sm rounded-pill px-4 fw-bold text-dark shadow-sm">
                                                    <i class="fas fa-plus-circle me-1"></i>طلب تعزيز لمخزن العمليات
                                                </a>
                                            </div>
                                        @else
                                            <form id="detailSuppliesForm" action="{{ route('surgeries.supplies.dispense', $surgery) }}" method="POST" class="clin-section-card clin-surface-white mb-0">
                                                @csrf
                                                <div class="clin-section-title text-primary mb-2">
                                                    <i class="fas fa-cart-arrow-down"></i>صرف مادة أو مستلزم من {{ $operationStoreLocation->name }}
                                                </div>
                                                <div class="row g-2 align-items-end">
                                                    <div class="col-md-6">
                                                        <label for="or_dispense_batch_id" class="form-label small fw-bold mb-1">المادة / الوجبة المتوفرة</label>
                                                        <select id="or_dispense_batch_id" name="batch_id" class="form-select form-select-sm border-primary-subtle" required
                                                                onchange="updateOrDispenseMaxQty(this, 'or_dispense_qty', 'or_dispense_unit')">
                                                            @foreach($operationSupplyBatches as $batch)
                                                                @php
                                                                    $prod = $batch->product;
                                                                    $catLabel = $prod?->isSupply() ? 'مستلزم' : ($prod?->category ?: 'مادة مخزنية');
                                                                    $unitText = $prod?->unit ?? 'قطعة';
                                                                    $lotText = $batch->internal_barcode ?: ($batch->manufacturer_lot_number ?: '#'.$batch->id);
                                                                @endphp
                                                                <option value="{{ $batch->id }}"
                                                                        data-max="{{ $batch->current_qty }}"
                                                                        data-unit="{{ $unitText }}">
                                                                    {{ $prod?->name ?? 'مادة' }} [{{ $catLabel }}] — متوفر: {{ $batch->current_qty }} {{ $unitText }} (وجبة: {{ $lotText }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2 col-6">
                                                        <label for="or_dispense_qty" class="form-label small fw-bold mb-1">الكمية (<span id="or_dispense_unit">{{ $operationSupplyBatches->first()?->product?->unit ?? 'قطعة' }}</span>)</label>
                                                        <input type="number" id="or_dispense_qty" name="quantity" class="form-control form-control-sm text-center fw-bold"
                                                               value="1" min="1" max="{{ $operationSupplyBatches->first()?->current_qty ?? 1 }}" required>
                                                    </div>
                                                    <div class="col-md-4 col-6">
                                                        <label for="or_dispense_notes" class="form-label small fw-bold mb-1">ملاحظات الصرف</label>
                                                        <input type="text" id="or_dispense_notes" name="admin_notes" class="form-control form-control-sm" placeholder="مثال: أثناء العملية...">
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-end mt-3 pt-2 border-top">
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                                                        <i class="fas fa-check-circle me-1"></i>صرف من مخزن العمليات
                                                    </button>
                                                </div>
                                            </form>
                                        @endif
                                    @endif

                                    <div>
                                        <div class="clin-section-title text-dark mb-2">
                                            <i class="fas fa-history text-info"></i>سجل المواد والمستلزمات المصروفة لهذه العملية ({{ $orDispensedItems->count() }})
                                        </div>
                                        @if($orDispensedItems->count() > 0)
                                            <div class="table-responsive rounded-3 border">
                                                <table class="table table-sm table-hover align-middle mb-0" style="font-size:0.86rem;">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>المادة / المستلزم</th>
                                                            <th class="text-center">الكمية</th>
                                                            <th>المصروف بواسطة</th>
                                                            <th>ملاحظات</th>
                                                            @if($canWriteSupplies)
                                                                <th class="text-center" width="110">إرجاع</th>
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($orDispensedItems as $item)
                                                            @php
                                                                $itemUnit = $item->batch?->product?->unit ?? '';
                                                                $itemLot = $item->batch?->internal_barcode ?: ($item->batch?->manufacturer_lot_number ?: ($item->batch_id ? '#'.$item->batch_id : null));
                                                                $fromLocName = $item->stockMovement?->fromLocation?->name ?? 'مخزن العمليات';
                                                                $dispenserName = $item->administeredBy?->full_name ?? $item->administeredBy?->name ?? 'موظف العمليات';
                                                            @endphp
                                                            <tr>
                                                                <td>
                                                                    <div class="fw-bold text-dark">{{ $item->description }}</div>
                                                                    <div class="d-flex flex-wrap gap-1 mt-05">
                                                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle" style="font-size:0.7rem;">{{ $fromLocName }}</span>
                                                                        @if($itemLot)
                                                                            <span class="badge bg-light text-secondary border" style="font-size:0.7rem;">وجبة: {{ $itemLot }}</span>
                                                                        @endif
                                                                    </div>
                                                                </td>
                                                                <td class="text-center fw-bold text-success">
                                                                    {{ $item->quantity ?? $item->dosage ?? 1 }}
                                                                    @if($item->quantity && $itemUnit)
                                                                        <small class="text-muted fw-normal">{{ $itemUnit }}</small>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    <div class="small fw-semibold text-dark">{{ $dispenserName }}</div>
                                                                    <small class="text-muted">{{ $item->administered_at ? $item->administered_at->format('Y-m-d H:i') : ($item->created_at?->format('Y-m-d H:i') ?? '-') }}</small>
                                                                </td>
                                                                <td class="small text-muted">{{ $item->admin_notes ?: '—' }}</td>
                                                                @if($canWriteSupplies)
                                                                    <td class="text-center">
                                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2 py-0"
                                                                                onclick="submitDeleteAction('{{ route('surgeries.supplies.reverse', [$surgery, $item]) }}', 'هل تريد إلغاء هذا الصرف وإعادة الكمية إلى مخزن العمليات؟')">
                                                                            <i class="fas fa-undo-alt me-1"></i>إرجاع للمخزن
                                                                        </button>
                                                                    </td>
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-4 text-muted small border rounded-3 bg-light">
                                                <i class="fas fa-box-open fa-2x mb-2 d-block opacity-50"></i>
                                                لم يتم صرف مواد أو مستلزمات من مخزن العمليات لهذه العملية حتى الآن.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Column 2: Used Medical Devices (Independent, Zero Form Nesting) --}}
                        <div class="col-xl-5">
                            <div class="card clin-tab-card h-100">
                                <div class="card-body p-3 p-md-4">
                                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3">
                                        <h6 class="fw-bold text-primary mb-0">
                                            <i class="fas fa-stethoscope me-2"></i>الأجهزة الطبية المستخدمة ({{ $surgery->medicalDevices->count() }})
                                        </h6>
                                        @if($canWriteSupplies)
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="toggleAddDeviceArea()">
                                                <i class="fas fa-plus me-1"></i>إضافة جهاز
                                            </button>
                                        @endif
                                    </div>

                                    @if($surgery->medicalDevices->count() > 0)
                                        <div class="table-responsive">
                                            <table class="table table-bordered table-sm align-middle mb-0" style="font-size:0.87rem;">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>اسم الجهاز</th>
                                                        @if($canWriteSupplies)
                                                            <th class="text-center" width="70">إزالة</th>
                                                        @endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($surgery->medicalDevices as $device)
                                                        <tr>
                                                            <td class="fw-semibold text-dark">{{ $device->name }}</td>
                                                            @if($canWriteSupplies)
                                                                <td class="text-center">
                                                                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="submitDeleteAction('{{ route('surgeries.removeDevice', [$surgery, $device]) }}', 'إزالة هذا الجهاز من العملية؟')">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-center py-4 text-muted small border rounded-2 bg-light">
                                            لا توجد أجهزة طبية مستخدمة مسجلة.
                                        </div>
                                    @endif

                                    @if($canWriteSupplies)
                                        <div id="addDeviceArea" style="display: none;" class="mt-3 p-3 border rounded-3 bg-light">
                                            <input type="text" id="deviceSearch" class="form-control form-control-sm mb-2" placeholder="ابحث عن الجهاز..." oninput="filterDevices()">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="showAllDevices" onchange="filterDevices()">
                                                <label class="form-check-label small text-muted" for="showAllDevices">عرض أجهزة الردهات الأخرى</label>
                                            </div>
                                            <select id="device_select" class="form-select form-select-sm border-primary mb-2" size="6" multiple>
                                                @foreach($devices as $device)
                                                    <option value="{{ $device->id }}" data-location-id="{{ $device->location_id }}">{{ $device->name }}</option>
                                                @endforeach
                                            </select>
                                            <div class="d-flex justify-content-between align-items-center gap-2">
                                                <small class="text-muted">Ctrl + نقر للاختيار المتعدد</small>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-primary btn-sm" onclick="submitAddDevice()">
                                                        <i class="fas fa-check me-1"></i>تأكيد
                                                    </button>
                                                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAddDeviceArea()">إلغاء</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- TAB 7: المتابعات --}}
            @if(isset($visibleTabs['detail_followups']))
                @php $allFollowUps = ($surgery->residentStationFollowUps ?? collect())->sortByDesc('created_at'); @endphp
                <div class="tab-pane fade {{ $activeTab === 'detail_followups' ? 'show active' : '' }}" id="pane-detail_followups" role="tabpanel" aria-labelledby="tab-btn-detail_followups">
                    <div class="card clin-tab-card">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="fas fa-clipboard-list me-2"></i>سجل المتابعات الدورية ({{ $allFollowUps->count() }})
                                </h6>
                                <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-eye me-1"></i>سجل المقيم والتمريض</span>
                            </div>

                            @if($allFollowUps->count() > 0)
                                <div class="table-responsive rounded-3 border">
                                    <table class="table table-sm table-bordered align-middle mb-0" style="font-size:0.88rem;">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="12%">التاريخ</th>
                                                <th width="10%">الوردية</th>
                                                <th width="20%">المسجل</th>
                                                <th>الملاحظات</th>
                                                <th width="16%">وقت التسجيل</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($allFollowUps as $followUp)
                                                <tr>
                                                    <td>{{ optional($followUp->follow_up_date)->format('Y-m-d') ?? '—' }}</td>
                                                    <td>{{ $followUp->session === 'morning' ? 'صباحاً' : 'مساءً' }}</td>
                                                    <td>{{ $followUp->resident?->user?->full_name ?? $followUp->resident?->user?->name ?? $followUp->resident_name ?? 'غير محدد' }}</td>
                                                    <td>{!! nl2br(e($followUp->notes)) !!}</td>
                                                    <td class="text-muted small">{{ optional($followUp->created_at)->format('Y-m-d H:i') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-1"></i> لا توجد متابعات مسجلة بعد.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    @endif
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

{{-- Standalone External Forms (Never Nested) --}}
<form id="addDeviceForm" action="{{ route('surgeries.addDevice', $surgery) }}" method="POST" style="display: none;">
    @csrf
</form>

<form id="global-delete-form" action="" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('scripts')
<script>
    function completeAnesthesia(surgeryId) {
        if (!confirm('هل أنت متأكد من اعتماد وإتمام محطة التخدير؟')) {
            return;
        }

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/surgery-stations/anesthesia/' + surgeryId + '/complete';

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
        // Persist active tab key in URL without reloading
        document.querySelectorAll('#surgeryDetailTabs button[data-tab-key]').forEach(function(btn) {
            btn.addEventListener('shown.bs.tab', function(e) {
                const tabKey = e.target.getAttribute('data-tab-key');
                if (tabKey && window.history && window.history.replaceState) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('tab', tabKey);
                    window.history.replaceState({}, '', url.toString());
                }
            });
        });

        // Auto-calculate estimated duration from start and end time inputs
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');

        function calculateDuration() {
            const durationField = document.getElementById('estimated_duration');
            const hiddenMinutesField = document.getElementById('estimated_duration_minutes');
            if (!startTimeInput || !endTimeInput || !durationField) return;

            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;

            if (startTime && endTime) {
                const start = new Date('1970-01-01T' + startTime + ':00');
                const end = new Date('1970-01-01T' + endTime + ':00');

                if (end < start) {
                    end.setDate(end.getDate() + 1);
                }

                const diffMs = end - start;
                const diffMins = Math.round(diffMs / 60000);
                const hours = Math.floor(diffMins / 60);
                const minutes = diffMins % 60;

                durationField.value = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0');
                if (hiddenMinutesField) {
                    hiddenMinutesField.value = diffMins;
                }
            }
        }

        if (startTimeInput && endTimeInput) {
            startTimeInput.addEventListener('input', calculateDuration);
            endTimeInput.addEventListener('input', calculateDuration);
            calculateDuration();
        }

        // Quick helper buttons for clinical notes & anesthesia types
        window.fillDetailQuickText = function(fieldId, text) {
            const el = document.getElementById(fieldId);
            if (!el || el.readOnly || el.disabled) return;
            el.value = text;
            el.focus();
        };

        window.setAnesthesiaQuickType = function(value) {
            const el = document.getElementById('anesthesia_type_input');
            if (!el || el.readOnly || el.disabled) return;
            el.value = value;
            el.focus();
        };

        const orBatchSelect = document.getElementById('or_dispense_batch_id');
        if (orBatchSelect) {
            updateOrDispenseMaxQty(orBatchSelect, 'or_dispense_qty', 'or_dispense_unit');
        }
    });

    window.updateOrDispenseMaxQty = function(selectEl, qtyInputId, unitSpanId) {
        if (!selectEl) return;
        const selectedOption = selectEl.options[selectEl.selectedIndex];
        if (!selectedOption) return;
        const maxQty = parseInt(selectedOption.getAttribute('data-max') || '1', 10);
        const unitLabel = selectedOption.getAttribute('data-unit') || 'قطعة';
        const qtyInput = document.getElementById(qtyInputId);
        const unitSpan = document.getElementById(unitSpanId);
        if (unitSpan) {
            unitSpan.textContent = unitLabel;
        }
        if (qtyInput) {
            qtyInput.max = maxQty;
            if (parseInt(qtyInput.value || '1', 10) > maxQty) {
                qtyInput.value = maxQty;
            }
        }
    };

    // Dynamic Treatments Table Logic
    window.addSurgeryTreatment = function() {
        const container = document.getElementById('surgeryTreatmentsContainer');
        const emptyRow = document.getElementById('emptySurgeryTreatmentsRow');
        if (!container) return;
        if (emptyRow) emptyRow.remove();

        const treatmentIndex = container.querySelectorAll('.treatment-item').length;
        const treatmentHtml = `
            <tr class="treatment-item">
                <td class="text-center row-number">${treatmentIndex + 1}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][description]" placeholder="اسم الدواء أو وصف العلاج" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][dosage]" placeholder="مثال: 500mg, 2ml">
                </td>
                <td>
                    <textarea class="form-control form-control-sm timing-textarea" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][timing]" rows="1" placeholder="مثال: كل 6 ساعات"></textarea>
                </td>
                <td>
                    <div class="duration-input-group">
                        <input type="number" class="form-control form-control-sm" name="prescribed_medications[surgery_treatments][{{ $surgery->id }}][${treatmentIndex}][duration_value]" placeholder="العدد" min="1">
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

        if (!container.querySelectorAll('.treatment-item').length) {
            container.insertAdjacentHTML('beforeend', `
                <tr id="emptySurgeryTreatmentsRow">
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-table fa-2x mb-2 d-block opacity-50"></i>
                        <p class="mb-1">لا توجد علاجات مسجلة لهذه العملية</p>
                        <small>اضغط على "إضافة علاج جديد" للبدء</small>
                    </td>
                </tr>
            `);
        }
    };

    function reNumberRows() {
        document.querySelectorAll('#surgeryTreatmentsContainer .treatment-item').forEach(function(row, index) {
            const numCell = row.querySelector('.row-number');
            if (numCell) numCell.textContent = index + 1;

            row.querySelectorAll('[name]').forEach(function(input) {
                input.name = input.name.replace(/(\[surgery_treatments\]\[\d+\])\[\d+\]/, '$1[' + index + ']');
            });
        });
    }

    // Surgery type AJAX logic
    window.toggleSurgeryTypeArea = function() {
        const area = document.getElementById('surgeryTypeChangeArea');
        if (!area) return;
        area.style.display = area.style.display === 'none' ? 'block' : 'none';
        const search = document.getElementById('surgeryTypeSearch');
        if (search) search.value = '';
        filterSurgeryTypes();
    };

    window.filterSurgeryTypes = function() {
        const q = (document.getElementById('surgeryTypeSearch')?.value || '').trim().toLowerCase();
        const select = document.getElementById('surgery_type_select');
        if (!select) return;
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            if (!opt.value) continue;
            opt.style.display = q === '' || opt.text.toLowerCase().includes(q) ? '' : 'none';
        }
        select.querySelectorAll('optgroup').forEach(function(g) {
            const visible = Array.from(g.options).some(o => o.style.display !== 'none');
            g.style.display = visible ? '' : 'none';
        });
    };

    window.toggleSurgeryTypeInput = function() {
        const select = document.getElementById('surgery_type_select');
        const wrapper = document.getElementById('custom_surgery_type_wrapper');
        if (select && wrapper) {
            wrapper.style.display = select.value === 'other' ? 'block' : 'none';
        }
    };

    window.filterAddOperations = function() {
        const q = (document.getElementById('addOpSearch')?.value || '').trim().toLowerCase();
        const select = document.getElementById('add_op_select');
        if (!select) return;
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            if (!opt.value) continue;
            opt.style.display = q === '' || opt.text.toLowerCase().includes(q) ? '' : 'none';
        }
        select.querySelectorAll('optgroup').forEach(function(g) {
            const visible = Array.from(g.options).some(o => o.style.display !== 'none');
            g.style.display = visible ? '' : 'none';
        });
    };

    window.submitSurgeryTypeChange = function() {
        const select = document.getElementById('surgery_type_select');
        if (!select || !select.value) {
            alert('يرجى اختيار نوع العملية');
            return;
        }
        let surgeryType, opId;
        if (select.value === 'other') {
            const customVal = document.getElementById('custom_surgery_type').value.trim();
            if (!customVal) {
                alert('يرجى كتابة نوع العملية');
                return;
            }
            surgeryType = customVal;
            opId = null;
        } else {
            const selOpt = select.selectedOptions[0];
            surgeryType = select.value;
            opId = selOpt ? (selOpt.getAttribute('data-op-id') || null) : null;
        }

        const url = '{{ route('surgeries.updateSurgeryType', $surgery) }}';
        const token = document.getElementById('surgeryTypeCsrf').value;

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                _method: 'PATCH',
                surgery_type: surgeryType,
                surgical_operation_id: opId,
            }),
        })
        .then(r => {
            if (!r.ok) return r.json().then(e => { throw new Error(e.message || Object.values(e.errors || {}).flat().join(', ')); });
            return r.json();
        })
        .then(() => {
            const u = new URL(window.location.href);
            u.searchParams.set('tab', 'detail_type');
            window.location.href = u.toString();
        })
        .catch(err => alert('خطأ: ' + err.message));
    };

    window.toggleAddOperationArea = function() {
        const area = document.getElementById('addOperationArea');
        if (area) area.style.display = area.style.display === 'none' ? 'block' : 'none';
    };

    window.submitAddOperation = function() {
        const select = document.getElementById('add_op_select');
        const selected = select ? Array.from(select.selectedOptions).map(o => o.value) : [];
        if (!selected.length) {
            alert('يرجى اختيار عملية أو أكثر (Ctrl + click لاختيار متعدد)');
            return;
        }
        const notes = document.getElementById('add_op_notes').value.trim();
        const token = document.getElementById('addOpCsrf').value;
        const url = '{{ route('surgeries.addOperation', $surgery) }}';

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                surgical_operation_ids: selected,
                notes: notes || null,
            }),
        })
        .then(r => {
            if (!r.ok) return r.json().then(e => { throw new Error(e.message || Object.values(e.errors || {}).flat().join(', ')); });
            return r.json();
        })
        .then(() => {
            const u = new URL(window.location.href);
            u.searchParams.set('tab', 'detail_type');
            window.location.href = u.toString();
        })
        .catch(err => alert('خطأ: ' + err.message));
    };

    window.toggleAddDeviceArea = function() {
        const area = document.getElementById('addDeviceArea');
        if (!area) return;
        area.style.display = area.style.display === 'none' ? 'block' : 'none';
        if (area.style.display === 'block') {
            filterDevices();
        }
    };

    window.filterDevices = function() {
        const q = (document.getElementById('deviceSearch')?.value || '').trim().toLowerCase();
        const showAll = document.getElementById('showAllDevices')?.checked || false;
        const surgeryLocationId = "{{ $surgery->location_id }}";
        const userLocationId = "{{ auth()->user()->location_id }}";
        const activeLocationId = surgeryLocationId || userLocationId;

        const select = document.getElementById('device_select');
        if (!select) return;
        for (let i = 0; i < select.options.length; i++) {
            const opt = select.options[i];
            if (!opt.value) continue;
            const text = opt.text.toLowerCase();
            const deviceLocId = opt.getAttribute('data-location-id');
            const matchesQuery = q === '' || text.includes(q);
            const matchesLocation = showAll || !activeLocationId || deviceLocId === activeLocationId || !deviceLocId;
            opt.style.display = matchesQuery && matchesLocation ? '' : 'none';
        }
    };

    window.submitDeleteAction = function(url, message) {
        if (confirm(message || 'هل أنت متأكد من الإجراء؟')) {
            const form = document.getElementById('global-delete-form');
            form.action = url;
            form.submit();
        }
    };

    window.submitAddDevice = function() {
        const select = document.getElementById('device_select');
        const form = document.getElementById('addDeviceForm');
        if (!select || !form) return;

        const selectedIds = Array.from(select.selectedOptions).map(opt => opt.value).filter(Boolean);
        if (!selectedIds.length) {
            alert('يرجى اختيار جهاز طبي واحد على الأقل');
            return;
        }

        form.querySelectorAll('input[name="device_ids[]"]').forEach(el => el.remove());
        selectedIds.forEach(function(id) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'device_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        form.submit();
    };
</script>
@endsection
