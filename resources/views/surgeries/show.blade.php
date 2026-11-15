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
        .sg-pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: sgPulse 1.8s infinite;
        }
        @keyframes sgPulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
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

        /* ===== نمط الصفحة الواحدة الموحدة (Single Page View Mode) ===== */
        .tab-content .tab-pane {
            display: block !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin-bottom: 24px !important;
            scroll-margin-top: 95px;
        }
        .tab-pane.col-xl-6, .tab-pane.col-lg-6, .tab-pane.col-xl-5, .tab-pane.col-xl-7, .tab-pane.col-md-5, .tab-pane.col-md-7, .tab-content .row .tab-pane {
            margin-bottom: 0 !important;
        }
        .clin-tabs-wrapper {
            position: sticky;
            top: 10px;
            z-index: 1030;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 16px;
            padding: 8px 10px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.85);
            margin-bottom: 24px;
            transition: all 0.2s ease;
        }
        .clin-nav-tab {
            cursor: pointer;
        }
        .clin-tab-card {
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.05);
            border: 1px solid #e2e8f0;
            transition: box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .clin-tab-card:hover {
            box-shadow: 0 8px 25px rgba(15, 23, 42, 0.08);
            border-color: #cbd5e1;
        }
        #btnBackToTopSurgery {
            position: fixed;
            bottom: 24px;
            left: 24px;
            z-index: 1050;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
            transition: all 0.2s ease;
        }
        #btnBackToTopSurgery:hover {
            transform: translateY(-2px);
        }
        .collapse-arrow {
            transition: transform 0.25s ease;
        }
        [data-bs-toggle="collapse"][aria-expanded="true"] .collapse-arrow {
            transform: rotate(180deg);
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

    {{-- كارت معلومات وبيانات المريض (Patient Information Card) --}}
    <div class="card border-0 shadow-sm rounded-4 mb-3 p-3 p-xl-4 bg-white" style="border: 1px solid #e2e8f0 !important; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #3b82f6 0%, #06b6d4 100%);"></div>

        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar-modern-box flex-shrink-0" style="width: 52px; height: 52px; font-size: 1.4rem;">
                    <i class="fas fa-user-injured text-primary"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 class="clin-patient-name mb-0" title="{{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير محدد' }}">
                            {{ $surgery->patient?->user?->name ?? $surgery->patient?->name ?? 'غير محدد' }}
                        </h4>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fw-bold" style="font-size: 0.76rem;">
                            #{{ $surgery->patient?->patient_id ?? $surgery->patient_id ?? 'N/A' }}
                        </span>
                        @if($surgery->patient?->file_number)
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                                ملف: {{ $surgery->patient->file_number }}
                            </span>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-2 text-muted small mt-1.5 flex-wrap">
                        <span class="badge-modern-sub">
                            <i class="fas fa-birthday-cake me-1 text-primary"></i>{{ $surgery->patient?->age ? $surgery->patient->age . ' سنة' : 'العمر غير مسجل' }}
                        </span>
                        <span class="badge-modern-sub">
                            <i class="fas fa-venus-mars me-1 text-info"></i>{{ $surgery->patient?->gender === 'male' ? 'ذكر' : ($surgery->patient?->gender === 'female' ? 'أنثى' : 'غير محدد') }}
                        </span>
                        @if($surgery->patient?->blood_group)
                            <span class="badge bg-danger-subtle text-danger border" style="font-size: 0.74rem;">
                                <i class="fas fa-tint me-1"></i>{{ $surgery->patient->blood_group }}
                            </span>
                        @endif
                        @if($surgery->patient?->user?->phone)
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.74rem;">
                                <i class="fas fa-phone-alt me-1 text-muted"></i>{{ $surgery->patient->user->phone }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted fw-bold">الحساب:</span>
                @if($surgery->insurance_type && $surgery->insurance_type !== 'none')
                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5" style="font-size: 0.8rem;">
                        <i class="fas fa-shield-alt me-1"></i>{{ $surgery->insurance_type === 'hi' ? 'ضمان صحي' : 'ضمان قوى الأمن' }}
                    </span>
                    @if($surgery->patient?->insurance_card_no)
                        <span class="text-muted small font-monospace">بطاقة: {{ $surgery->patient->insurance_card_no }}</span>
                    @endif
                @else
                    <span class="badge bg-secondary-subtle text-secondary border px-2.5 py-1.5" style="font-size: 0.8rem;">
                        <i class="fas fa-money-bill-wave me-1"></i>نقدي (كاش)
                    </span>
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
        {{-- عناصر الصلاحيات البرمجية (شريط التبويبات ملغي كلياً والصفحة متصلة مباشرة) --}}
        <div class="d-none" id="surgeryDetailTabs">
            @foreach($visibleTabs as $tKey => $tMeta)
                <span id="tab-btn-{{ $tKey }}"></span>
            @endforeach
        </div>

        {{-- Unified Tab Content Panes --}}
        <div class="tab-content" id="surgeryDetailTabContent">

            {{-- TAB 1 & 2: الفحوصات والتحاليل ونوع العملية والإضافات --}}
            @php
                $hasTestsTab = isset($visibleTabs['detail_tests']);
                $hasTypeTab = isset($visibleTabs['detail_type']);
            @endphp

            @if($hasTestsTab || $hasTypeTab)
                @php
                    $surgeryLabCount = $surgery->labTests->count();
                    $surgeryRadCount = $surgery->radiologyTests->count();
                    $canWriteType = $hasTypeTab ? $visibleTabs['detail_type']['can_write'] : false;
                    $protocol = $surgery->surgeonStation?->monitoring_protocol ?? null;
                    $typeChanges = $hasTypeTab ? $surgery->surgeryTypeChanges()->latest()->get() : collect();
                    $additionalOpsCount = $surgery->additionalOperations->count();
                @endphp

                <div class="tab-pane fade show active" id="pane-detail_tests" role="tabpanel" aria-labelledby="tab-btn-detail_tests">
                    <div class="card clin-tab-card shadow-sm border-0 mb-4" style="background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0 !important;">
                        <div class="card-body p-3 p-md-4">
                            {{-- Interactive Action Buttons for Tests & Operations --}}
                            <div class="d-flex flex-wrap align-items-center justify-content-start gap-2.5">
                                @if($hasTestsTab)
                                    {{-- Button 1: Lab Tests --}}
                                    <button class="btn btn-outline-primary fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-xs"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseLabTestsSection"
                                            aria-expanded="false"
                                            aria-controls="collapseLabTestsSection"
                                            id="btnToggleLabTests"
                                            style="font-size: 0.92rem; transition: all 0.2s ease;">
                                        <i class="fas fa-flask text-primary"></i>
                                        <span>التحاليل والفحوصات المخبرية</span>
                                        <span class="badge rounded-pill bg-primary text-white px-2 py-1">{{ $surgeryLabCount }}</span>
                                        <i class="fas fa-chevron-down ms-1 text-muted small collapse-arrow"></i>
                                    </button>

                                    {{-- Button 2: Radiology Tests --}}
                                    <button class="btn btn-outline-success fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-xs"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseRadiologyTestsSection"
                                            aria-expanded="false"
                                            aria-controls="collapseRadiologyTestsSection"
                                            id="btnToggleRadiologyTests"
                                            style="font-size: 0.92rem; transition: all 0.2s ease;">
                                        <i class="fas fa-x-ray text-success"></i>
                                        <span>الأشعة والتصوير الطبي</span>
                                        <span class="badge rounded-pill bg-success text-white px-2 py-1">{{ $surgeryRadCount }}</span>
                                        <i class="fas fa-chevron-down ms-1 text-muted small collapse-arrow"></i>
                                    </button>
                                @endif

                                @if($hasTypeTab)
                                    {{-- Button 3: Surgery Type & Additional Operations --}}
                                    <button class="btn btn-outline-info text-dark fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-xs"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#collapseSurgeryTypeSection"
                                            aria-expanded="{{ $activeTab === 'detail_type' ? 'true' : 'false' }}"
                                            aria-controls="collapseSurgeryTypeSection"
                                            id="btnToggleSurgeryType"
                                            style="font-size: 0.92rem; transition: all 0.2s ease; border-color: #38bdf8;">
                                        <i class="fas fa-procedures text-info"></i>
                                        <span>نوع العملية والعمليات الإضافية</span>
                                        @if($additionalOpsCount > 0)
                                            <span class="badge rounded-pill bg-info text-white px-2 py-1">+{{ $additionalOpsCount }} إضافية</span>
                                        @endif
                                        <i class="fas fa-chevron-down ms-1 text-muted small collapse-arrow"></i>
                                    </button>
                                @endif

                                {{-- Button 4: حالة المريض في الصالة (قائمة منسدلة موحدة) --}}
                                @php
                                    $otStation = $surgery->operationTheaterStation;
                                    $isOtCompleted = ($surgery->status === 'completed') || ($otStation && $otStation->status === 'completed');
                                    $isOtInProgress = ($surgery->status === 'in_progress') || ($otStation && $otStation->status === 'in_progress');
                                    $canControlSurgery = $user && ($user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor', 'resident', 'surgery_coordinator', 'admin-hsop', 'hospital_admin']) || $user->can('control surgeries'));
                                @endphp

                                <div class="dropdown d-inline-block">
                                    @if($canControlSurgery && $surgery->status !== 'cancelled' && $surgery->status !== 'completed')
                                        <button class="btn btn-sm rounded-pill px-3 py-2 fw-bold shadow-xs dropdown-toggle d-inline-flex align-items-center gap-2"
                                                type="button"
                                                id="patientOrStatusDropdownBtn"
                                                data-bs-toggle="dropdown"
                                                aria-expanded="false"
                                                style="font-size: 0.92rem; border: 1px solid {{ $isOtInProgress ? '#bae6fd' : '#fde68a' }}; background: {{ $isOtInProgress ? '#e0f2fe' : '#fef3c7' }}; color: {{ $isOtInProgress ? '#0369a1' : '#92400e' }};">
                                            @if($isOtInProgress)
                                                <span class="sg-pulse-dot me-1" style="width: 8px; height: 8px;"></span>
                                                <span>حالة المريض: Intra-Op (داخل الصالة)</span>
                                            @else
                                                <i class="fas fa-clock me-1 text-warning"></i>
                                                <span>حالة المريض: Pre-Op (بالانتظار)</span>
                                            @endif
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 py-2 border-0" aria-labelledby="patientOrStatusDropdownBtn" style="min-width: 240px; font-size: 0.88rem;">
                                            <li class="dropdown-header small text-muted fw-bold pb-1 border-bottom mb-1">
                                                تحديث حالة المريض بالصالة
                                            </li>

                                            @if(in_array($surgery->status, ['scheduled', 'waiting', 'checked_in']))
                                                <li>
                                                    <form action="{{ route('surgeries.start', $surgery) }}" method="POST" class="surgery-live-action-form mb-0" id="formDropdownStartSurgery" onsubmit="broadcastSurgeryLiveUpdate()">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-success fw-bold" onclick="return confirm('تأكيد بدء العملية ودخول المريض لصالة العمليات؟')">
                                                            <i class="fas fa-play text-success" style="width: 16px;"></i>
                                                            <span>بدء العملية (دخول الصالة)</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning" data-bs-toggle="modal" data-bs-target="#postponeSurgeryModal">
                                                        <i class="fas fa-calendar-alt text-warning" style="width: 16px;"></i>
                                                        <span class="text-dark fw-semibold">تأجيل العملية للموعد القادم</span>
                                                    </button>
                                                </li>
                                            @endif

                                            @if($surgery->status === 'in_progress')
                                                <li>
                                                    <form action="{{ route('surgeries.complete', $surgery) }}" method="POST" class="surgery-live-action-form mb-0" id="formDropdownCompleteSurgery" onsubmit="broadcastSurgeryLiveUpdate()">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger fw-bold" onclick="return confirm('تأكيد إنهاء العملية الجراحية واكتمالها؟')">
                                                            <i class="fas fa-check-double text-danger" style="width: 16px;"></i>
                                                            <span>إنهاء العملية (خروج من الصالة)</span>
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form action="{{ route('surgeries.return-to-waiting', $surgery) }}" method="POST" class="surgery-live-action-form mb-0" id="formDropdownReturnSurgery" onsubmit="broadcastSurgeryLiveUpdate()">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item py-2 d-flex align-items-center gap-2 text-warning fw-semibold" onclick="return confirm('تأكيد إعادة إخراج المريض من صالة العمليات وإرجاعه لقائمة الانتظار؟')">
                                                            <i class="fas fa-door-open text-warning" style="width: 16px;"></i>
                                                            <span class="text-dark">إعادة إخراجه من الصالة</span>
                                                        </button>
                                                    </form>
                                                </li>
                                            @endif

                                            @if($surgery->status !== 'cancelled' && $surgery->status !== 'completed')
                                                <li><hr class="dropdown-divider my-1"></li>
                                                <li>
                                                    <button type="button" class="dropdown-item py-2 d-flex align-items-center gap-2 text-danger" data-bs-toggle="modal" data-bs-target="#cancelSurgeryModal">
                                                        <i class="fas fa-times-circle text-danger" style="width: 16px;"></i>
                                                        <span>إلغاء العملية</span>
                                                    </button>
                                                </li>
                                            @endif
                                        </ul>
                                    @else
                                        {{-- شارة القراءة فقط --}}
                                        @if($isOtCompleted)
                                            <span class="badge py-2 px-3 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 0.92rem;">
                                                <i class="fas fa-check-double me-1 text-success"></i>Post-Op (خرج من الصالة)
                                            </span>
                                        @elseif($surgery->status === 'cancelled')
                                            <span class="badge py-2 px-3 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 0.92rem;">
                                                <i class="fas fa-ban me-1 text-danger"></i>Cancelled (ملغاة)
                                            </span>
                                        @elseif($isOtInProgress)
                                            <span class="badge py-2 px-3 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 0.92rem;">
                                                <span class="sg-pulse-dot me-1"></span>Intra-Op (داخل الصالة)
                                            </span>
                                        @else
                                            <span class="badge py-2 px-3 rounded-pill shadow-xs fw-bold d-inline-flex align-items-center" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 0.92rem;">
                                                <i class="fas fa-clock me-1 text-warning"></i>Pre-Op (بالانتظار)
                                            </span>
                                        @endif
                                    @endif
                                </div>

                                {{-- Button 5: صالة العمليات / الردهة (منقولة قبل مدة العملية) --}}
                                @php
                                    $canWriteLocation = isset($visibleTabs['detail_team_timing']) && $visibleTabs['detail_team_timing']['can_write'];
                                    $currentLocationName = $surgery->location?->name;
                                @endphp
                                <div class="dropdown d-inline-block" id="locationDropdownWrapper">
                                    <button class="btn btn-outline-danger text-dark fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-xs dropdown-toggle"
                                            type="button"
                                            id="btnToggleLocationDropdown"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="outside"
                                            aria-expanded="false"
                                            style="font-size: 0.92rem; transition: all 0.2s ease; border-color: #fca5a5; background: #ffffff; color: #991b1b;">
                                        <i class="fas fa-door-open text-danger"></i>
                                        <span>صالة العمليات:</span>
                                        <span class="badge rounded-pill {{ $currentLocationName ? 'bg-danger text-white' : 'bg-light text-muted border' }} px-2 py-1" id="topLocationBadge">
                                            {{ $currentLocationName ?: 'غير محددة' }}
                                        </span>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 p-3 border-0" aria-labelledby="btnToggleLocationDropdown" style="min-width: 290px;">
                                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="fw-bold small text-dark"><i class="fas fa-door-open text-danger me-1"></i>تحديد صالة العمليات</span>
                                            @if($currentLocationName)
                                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle" id="topLocationPreviewBadge">{{ $currentLocationName }}</span>
                                            @endif
                                        </div>

                                        @if($canWriteLocation)
                                            <div class="mb-2">
                                                <label for="top_location_id" class="form-label text-dark fw-bold small mb-1">اختر صالة العمليات:</label>
                                                <select id="top_location_id" class="form-select form-select-sm border-danger" onchange="syncLocationSelection(this)">
                                                    <option value="">-- اختر صالة العمليات --</option>
                                                    @foreach($locations as $loc)
                                                        <option value="{{ $loc->id }}" {{ old('location_id', $surgery->location_id) == $loc->id ? 'selected' : '' }}>
                                                            {{ $loc->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                                <small class="text-muted">يُحفظ مع زر الحفظ</small>
                                                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 fw-bold" onclick="submitUnifiedSurgeryForm()">
                                                    <i class="fas fa-check me-1"></i>حفظ الصالة
                                                </button>
                                            </div>
                                        @else
                                            <div class="py-2 text-center text-muted small">
                                                <div class="fs-6 fw-bold text-dark mb-1">{{ $currentLocationName ?: 'لم تحدد الصالة بعد' }}</div>
                                                <div class="text-secondary small">تعديل الصالة متاح للكادر المخول بصلاحية صالة العمليات.</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Button 6: مدة العملية وتوقيتها (رقم بالدقائق مع شرائح سريعة) --}}
                                @php
                                    $canWriteTiming = isset($visibleTabs['detail_team_timing']) && $visibleTabs['detail_team_timing']['can_write'];
                                    $durationMinutes = $surgery->estimated_duration;
                                    $durationHoursText = $durationMinutes ? sprintf('%02d:%02d', floor($durationMinutes / 60), $durationMinutes % 60) : null;
                                @endphp
                                <div class="dropdown d-inline-block" id="durationDropdownWrapper">
                                    <button class="btn btn-outline-secondary fw-bold d-inline-flex align-items-center gap-2 px-3 py-2 rounded-pill shadow-xs dropdown-toggle"
                                            type="button"
                                            id="btnToggleDurationDropdown"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="outside"
                                            aria-expanded="false"
                                            style="font-size: 0.92rem; transition: all 0.2s ease; border-color: #cbd5e1; background: #ffffff; color: #334155;">
                                        <i class="fas fa-stopwatch text-warning-emphasis"></i>
                                        <span>مدة العملية:</span>
                                        @if($durationMinutes)
                                            <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1">
                                                {{ $durationMinutes }} دقيقة
                                                @if($durationMinutes >= 60)
                                                    <small class="text-muted fw-normal">({{ $durationHoursText }} س)</small>
                                                @endif
                                            </span>
                                        @else
                                            <span class="badge rounded-pill bg-light text-muted border px-2 py-1">غير مسجلة</span>
                                        @endif
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg rounded-3 p-3 border-0" aria-labelledby="btnToggleDurationDropdown" style="min-width: 300px;">
                                        <div class="d-flex align-items-center justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="fw-bold small text-dark"><i class="fas fa-clock text-primary me-1"></i>مدة العملية الجراحية (رقم)</span>
                                            @if($durationMinutes)
                                                <span class="badge bg-primary text-white">{{ $durationMinutes }} دقيقة</span>
                                            @endif
                                        </div>

                                        @if($canWriteTiming)
                                            <form action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST" id="formQuickSurgeryDuration" class="mb-0">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="tab" value="detail_team_timing">

                                                {{-- أزرار سريعة بنقرة واحدة --}}
                                                <div class="mb-2">
                                                    <label class="form-label text-muted small mb-1">خيارات سريعة بنقرة واحدة:</label>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        @foreach([30, 45, 60, 90, 120, 150, 180] as $chipMin)
                                                            <button type="button" class="btn btn-sm btn-light border py-1 px-2 fw-semibold" style="font-size: 0.78rem;" onclick="setQuickDurationVal({{ $chipMin }})">
                                                                {{ $chipMin }}د
                                                            </button>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                {{-- حقل إدخال الرقم --}}
                                                <div class="mb-2">
                                                    <label for="quickDurationInput" class="form-label text-dark fw-bold small mb-1">المدة بالدقائق (رقم):</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-primary"><i class="fas fa-stopwatch"></i></span>
                                                        <input type="number" min="1" step="5" class="form-control fw-bold text-center border-primary" id="quickDurationInput" name="estimated_duration_minutes" value="{{ $durationMinutes ?? '' }}" placeholder="أدخل المدة (مثلاً: 60)" oninput="updateQuickDurationHelper(this.value)">
                                                        <span class="input-group-text bg-light text-muted">دقيقة</span>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                                    <small class="text-muted fw-semibold" id="quickDurationHelperText">{{ $durationMinutes ? 'المسجلة: ' . $durationMinutes . ' دقيقة (' . $durationHoursText . ' س)' : 'أدخل رقماً بالدقائق' }}</small>
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                                                        <i class="fas fa-check me-1"></i>حفظ المدة
                                                    </button>
                                                </div>
                                            </form>
                                        @else
                                            <div class="py-2 text-center text-muted small">
                                                @if($durationMinutes)
                                                    <div class="fs-5 fw-bold text-primary mb-1">{{ $durationMinutes }} دقيقة</div>
                                                    <div>يعادل: {{ $durationHoursText }} ساعة</div>
                                                @else
                                                    لم يتم تسجيل مدة للعملية بعد.
                                                @endif
                                                <div class="text-secondary small mt-1">تعديل المدة متاح للكادر المخول بصلاحية التوقيت.</div>
                                            </div>
                                        @endif

                                        @if($surgeryStartFormatted || $surgeryEndFormatted)
                                            <div class="mt-2 pt-2 border-top text-muted small" style="font-size: 0.76rem;">
                                                <i class="fas fa-calendar-check me-1 text-success"></i>
                                                البدء: <strong>{{ $surgeryStartFormatted ?: '—' }}</strong> | الانتهاء: <strong>{{ $surgeryEndFormatted ?: '—' }}</strong>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Button 6: زر الحفظ الموحد بجانب مدة العملية --}}
                                @php
                                    $canWriteDiagnosis = isset($visibleTabs['detail_diagnosis']) && $visibleTabs['detail_diagnosis']['can_write'];
                                    $canWriteTeamTiming = isset($visibleTabs['detail_team_timing']) && $visibleTabs['detail_team_timing']['can_write'];
                                    $canWriteAnesthesia = isset($visibleTabs['detail_anesthesia']) && $visibleTabs['detail_anesthesia']['can_write'];
                                    $canSaveAny = $canWriteDiagnosis || $canWriteTeamTiming || $canWriteAnesthesia || $canWriteTiming;
                                    $writableCount = ($canWriteDiagnosis ? 1 : 0) + ($canWriteTeamTiming ? 1 : 0) + ($canWriteAnesthesia ? 1 : 0);
                                @endphp
                                @if($canSaveAny)
                                    <button type="button"
                                            class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2 px-3.5 py-2 rounded-pill shadow-sm"
                                            id="btnUnifiedSaveSurgery"
                                            onclick="submitUnifiedSurgeryForm()"
                                            title="حفظ كافة البيانات والتعديلات المدخلة"
                                            style="font-size: 0.92rem; background: linear-gradient(135deg, #2563eb, #1d4ed8); border: none;">
                                        <i class="fas fa-save text-white"></i>
                                        <span>
                                            @if($writableCount > 1)
                                                حفظ بيانات العملية
                                            @elseif($canWriteDiagnosis)
                                                حفظ التشخيص والعلاجات
                                            @elseif($canWriteAnesthesia)
                                                حفظ تفاصيل التخدير
                                            @elseif($canWriteTeamTiming)
                                                حفظ الفريق الطبي والتوقيت
                                            @else
                                                حفظ التعديلات
                                            @endif
                                        </span>
                                    </button>
                                @endif
                            </div>

                            @if($hasTestsTab)
                                {{-- Collapsible Container 1: Lab Tests (Surgery Lab Tests Only) --}}
                                <div class="collapse mt-3" id="collapseLabTestsSection">
                                    <div class="table-responsive rounded-3 border bg-white" style="border-color:#bfdbfe !important;">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                            <thead style="background:linear-gradient(135deg,#eff6ff,#dbeafe);">
                                                <tr>
                                                    <th class="px-3 py-2 text-primary fw-bold border-0"><i class="fas fa-vial me-1"></i>التحليل المخبري للعملية</th>
                                                    <th class="py-2 text-muted fw-semibold border-0">النتيجة</th>
                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                    <th class="py-2 border-0 text-end pe-3">المرفق</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($surgery->labTests as $labTest)
                                                    <tr>
                                                        <td class="px-3 fw-semibold text-dark">{{ optional($labTest->labTest)->name ?? 'تحليل طبي' }}</td>
                                                        <td>@if($labTest->result)<strong class="text-dark">{{ $labTest->result }}</strong>@else<span class="text-muted small">—</span>@endif</td>
                                                        <td class="text-center"><span class="badge bg-{{ $labTest->status_color }}">{{ $labTest->status_text }}</span></td>
                                                        <td class="text-end pe-3">
                                                            @if($labTest->result_file)
                                                                <a href="{{ asset('storage/' . $labTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2"><i class="fas fa-download me-1"></i>تحميل</a>
                                                            @else
                                                                <span class="text-muted small">—</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="4" class="text-center text-muted py-3">لا توجد تحاليل مخبرية مخصصة للعملية</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {{-- Collapsible Container 2: Radiology Tests (Surgery Radiology Tests Only) --}}
                                <div class="collapse mt-3" id="collapseRadiologyTestsSection">
                                    <div class="table-responsive rounded-3 border bg-white" style="border-color:#bbf7d0 !important;">
                                        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                                            <thead style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                                                <tr>
                                                    <th class="px-3 py-2 text-success fw-bold border-0"><i class="fas fa-x-ray me-1"></i>الأشعة والتصوير للعملية</th>
                                                    <th class="py-2 text-muted fw-semibold border-0">التقرير / النتيجة</th>
                                                    <th class="py-2 text-muted fw-semibold border-0 text-center">الحالة</th>
                                                    <th class="py-2 border-0 text-end pe-3">المرفق</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($surgery->radiologyTests as $radTest)
                                                    <tr>
                                                        <td class="px-3 fw-semibold text-dark">{{ optional($radTest->radiologyType)->name ?? 'تصوير طبي' }}</td>
                                                        <td>@if($radTest->result)<span class="text-dark small">{{ $radTest->result }}</span>@else<span class="text-muted small">—</span>@endif</td>
                                                        <td class="text-center"><span class="badge bg-{{ $radTest->status_color }}">{{ $radTest->status_text }}</span></td>
                                                        <td class="text-end pe-3">
                                                            @if($radTest->result_file)
                                                                <a href="{{ asset('storage/' . $radTest->result_file) }}" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2"><i class="fas fa-download me-1"></i>تحميل</a>
                                                            @else
                                                                <span class="text-muted small">—</span>
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
                            @endif

                            @if($hasTypeTab)
                                {{-- Collapsible Container 3: Surgery Type, Additional Operations & Notes --}}
                                <div id="pane-detail_type">
                                    <div class="collapse {{ $activeTab === 'detail_type' ? 'show' : '' }} mt-3" id="collapseSurgeryTypeSection">
                                        <div class="p-3 rounded-3" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                                            <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-3">
                                                <h6 class="fw-bold text-primary mb-0">
                                                    <i class="fas fa-procedures me-2"></i>نوع العملية والعمليات الإضافية والملاحظات
                                                </h6>
                                                @if($canWriteType)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-pen me-1"></i>متاح للتعديل</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border"><i class="fas fa-eye me-1"></i>قراءة فقط</span>
                                                @endif
                                            </div>

                                            {{-- Section 1: Main Procedure Summary & Change Type Option --}}
                                            <div class="clin-section-card mb-3">
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

                                            {{-- Section 2: Direct Procedure Notes (surgical_notes) --}}
                                            <div class="clin-section-card clin-surface-white mb-3">
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

                                            {{-- Section 3: Side-by-Side Additional Operations & Type Change Log --}}
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
                        </div>
                    </div>
                </div>
            @endif

            {{-- ROW: التشخيص الطبي للجراح وجدول العلاجات (يمين) + دمج الفريق الطبي والتخدير معاً (يسار) --}}
            @if(isset($visibleTabs['detail_diagnosis']) || isset($visibleTabs['detail_team_timing']) || isset($visibleTabs['detail_anesthesia']))
                @php
                    $hasLeftColumn = isset($visibleTabs['detail_team_timing']) || isset($visibleTabs['detail_anesthesia']);
                    $hasRightColumn = isset($visibleTabs['detail_diagnosis']);
                    $rightColClass = $hasLeftColumn ? 'col-xl-5 col-lg-12' : 'col-12';
                    $leftColClass = $hasRightColumn ? 'col-xl-7 col-lg-12' : 'col-12';
                @endphp
                <div class="row g-3 mb-4">
                    {{-- TAB 3: التشخيص وخطة العلاج (يمين) --}}
                    @if(isset($visibleTabs['detail_diagnosis']))
                        @php $canWriteDiagnosis = $visibleTabs['detail_diagnosis']['can_write']; @endphp
                        <div class="{{ $rightColClass }} tab-pane fade {{ $activeTab === 'detail_diagnosis' ? 'show active' : '' }}" id="pane-detail_diagnosis" role="tabpanel" aria-labelledby="tab-btn-detail_diagnosis">
                            <div class="card clin-tab-card h-100 d-flex flex-column">
                                @if($canWriteDiagnosis)
                                    <form id="detailDiagnosisForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST" class="d-flex flex-column h-100 mb-0">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="tab" value="detail_diagnosis">
                                @endif
                                <div class="card-body p-3 p-md-4 d-flex flex-column gap-3 flex-grow-1">
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

                                    {{-- Section 1: Compact Clinical Summary Grid --}}
                                    <div class="clin-section-card">
                                        <div class="clin-section-title text-primary mb-2">
                                            <i class="fas fa-notes-medical"></i> التشخيص والخطة السريرية للجراح
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-12">
                                                <div class="clin-field-card">
                                                    <label for="diagnosis" class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                        <i class="fas fa-stethoscope text-primary"></i>
                                                        <span>التشخيص الطبي للعملية</span>
                                                    </label>
                                                    <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="diagnosis" @if($canWriteDiagnosis) name="diagnosis" @else readonly disabled @endif rows="2" placeholder="أدخل التشخيص الطبي المفصل للمريض...">{{ old('diagnosis', $surgery->diagnosis) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="clin-field-card">
                                                    <label for="treatment_plan" class="form-label fw-bold text-dark small mb-1 d-flex align-items-center gap-1">
                                                        <i class="fas fa-file-medical text-info"></i>
                                                        <span>خطة العلاج والتوصيات الجراحية</span>
                                                    </label>
                                                    <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="treatment_plan" @if($canWriteDiagnosis) name="treatment_plan" @else readonly disabled @endif rows="2" placeholder="خطة العلاج وتوصيات ما بعد العملية...">{{ old('treatment_plan', $surgery->treatment_plan) }}</textarea>
                                                </div>
                                            </div>
                                            <div class="col-12">
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
                                                    <textarea class="form-control clin-compact-textarea {{ $canWriteDiagnosis ? '' : 'bg-light' }}" id="post_op_notes" @if($canWriteDiagnosis) name="post_op_notes" @else readonly disabled @endif rows="2" placeholder="ملاحظات الجراح بعد التداخل...">{{ old('post_op_notes', $surgery->post_op_notes) }}</textarea>
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
                            </form>
                        @endif
                    </div>
                </div>
            @endif

                    {{-- العمود الأيسر: بطاقة موحدة تجمع الفريق الطبي (أعلى بـ 5 قوائم منسدلة) وتفاصيل وخطة التخدير (أسفل) --}}
                    @if($hasLeftColumn)
                        <div class="{{ $leftColClass }}">
                            <div class="card clin-tab-card h-100 shadow-sm border d-flex flex-column">
                                <div class="card-header bg-white border-bottom py-2.5 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                                        <i class="fas fa-user-md text-primary"></i>
                                        <span>الفريق الطبي المشارك وخطة التخدير</span>
                                    </h6>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($user && $user->can('view anesthesia station') && $surgery->anesthesiaStation && $surgery->anesthesiaStation->status !== 'completed')
                                            <button type="button" class="btn btn-outline-success btn-sm px-3 rounded-pill fw-bold" onclick="completeAnesthesia({{ $surgery->id }})">
                                                <i class="fas fa-check-double me-1"></i>اعتماد وإتمام محطة التخدير
                                            </button>
                                        @endif
                                        <span class="badge {{ $surgery->anesthesiaStation?->status === 'completed' ? 'bg-success' : ($surgery->anesthesiaStation?->status === 'in_progress' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                                            {{ $surgery->anesthesiaStation?->status === 'completed' ? 'محطة التخدير: مكتملة' : ($surgery->anesthesiaStation?->status === 'in_progress' ? 'محطة التخدير: قيد العمل' : 'محطة التخدير: معلقة') }}
                                        </span>
                                    </div>
                                </div>

                                <div class="card-body p-3 flex-grow-1 d-flex flex-column gap-3">
                                    {{-- 1. الفريق الطبي المشارك (أعلى الكارت في صف واحد من 5 قوائم منسدلة) --}}
                                    @if(isset($visibleTabs['detail_team_timing']))
                                        @php
                                            $canWriteTeamTiming = $visibleTabs['detail_team_timing']['can_write'];
                                        @endphp
                                        <div class="tab-pane fade {{ $activeTab === 'detail_team_timing' ? 'show active' : '' }}" id="pane-detail_team_timing" role="tabpanel" aria-labelledby="tab-btn-detail_team_timing">
                                            @if($canWriteTeamTiming)
                                                <form id="detailTeamTimingForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST" class="mb-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="tab" value="detail_team_timing">
                                            @endif

                                            <input type="hidden" id="team_location_id" name="location_id" value="{{ old('location_id', $surgery->location_id) }}">

                                            <div class="fw-bold text-primary small pb-1.5 mb-2 border-bottom d-flex align-items-center justify-content-between">
                                                <span><i class="fas fa-users-cog me-1"></i>أعضاء الفريق الطبي المشارك في العملية (5 قوائم)</span>
                                                @if($canWriteTeamTiming)
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-2">متاح للتعديل</span>
                                                @else
                                                    <span class="badge bg-secondary-subtle text-secondary border py-0.5 px-2">للقراءة فقط</span>
                                                @endif
                                            </div>

                                            {{-- صف أفقي واحد يضم 5 قوائم/حقول متناسقة تماماً وموحدة المظهر والارتفاع --}}
                                            <div class="row g-2 align-items-end">
                                                {{-- 1. طبيب الجراح (محدد مسبقاً ومقفل من الاستعلامات) --}}
                                                <div class="col-lg col-md-4 col-sm-6">
                                                    <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 22px;">
                                                        <label class="form-label text-dark small fw-semibold mb-0">
                                                            <i class="fas fa-user-md text-primary me-1"></i>طبيب الجراح:
                                                        </label>
                                                        <span class="badge bg-light text-muted border small py-0 px-1.5" style="font-size: 0.68rem;" title="محدد مسبقاً ومثبت من الاستعلامات">
                                                            <i class="fas fa-lock me-1 text-secondary"></i>الاستعلامات
                                                        </span>
                                                    </div>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text bg-light text-primary"><i class="fas fa-user-md"></i></span>
                                                        <input type="text" class="form-control form-control-sm bg-light fw-bold text-dark text-truncate border-secondary-subtle"
                                                               value="د. {{ $surgery->doctor?->user?->full_name ?? $surgery->doctor?->user?->name ?? 'غير محدد' }}"
                                                               readonly disabled
                                                               title="د. {{ $surgery->doctor?->user?->full_name ?? $surgery->doctor?->user?->name ?? 'غير محدد' }} (محدد من الاستعلامات)">
                                                    </div>
                                                </div>

                                                {{-- 2. مساعد جراح --}}
                                                <div class="col-lg col-md-4 col-sm-6">
                                                    <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 22px;">
                                                        <label for="surgical_assistant_name" class="form-label text-dark small fw-semibold mb-0">
                                                            <i class="fas fa-hands-helping text-primary me-1"></i>مساعد جراح:
                                                        </label>
                                                        @canany(['view surgeries', 'edit surgeries'])
                                                            <a href="{{ route('surgical-assistants.index', ['type' => 'surgical']) }}" target="_blank" class="small text-decoration-none text-primary fw-semibold" style="font-size: 0.72rem;" title="فتح دليل وإدارة مساعدي الجراحين في نافذة جديدة">
                                                                <i class="fas fa-external-link-alt me-1"></i>دليل المساعدين
                                                            </a>
                                                        @endcanany
                                                    </div>
                                                    @php
                                                        $savedAssistant = old('surgical_assistant_name', $surgery->anesthesiaStation?->surgical_assistant_name ?? $surgery->surgical_assistant_name ?? '');
                                                    @endphp
                                                    @if($canWriteTeamTiming)
                                                        <div class="position-relative" id="assistantAutocompleteWrapper">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light text-primary"><i class="fas fa-hands-helping"></i></span>
                                                                <input type="text" id="surgical_assistant_name" name="surgical_assistant_name"
                                                                       class="form-control form-control-sm border-primary"
                                                                       autocomplete="off"
                                                                       placeholder="اختيار أو بحث..."
                                                                       value="{{ $savedAssistant }}">
                                                                <button type="button" class="btn btn-outline-secondary {{ $savedAssistant ? '' : 'd-none' }}" id="clearAssistantBtn" title="مسح">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-primary" id="toggleAssistantMenuBtn" title="عرض القائمة">
                                                                    <i class="fas fa-chevron-down"></i>
                                                                </button>
                                                            </div>
                                                            <div id="surgicalAssistantsMenu" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 220px; overflow-y: auto; display: none;"></div>
                                                        </div>
                                                    @else
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-primary"><i class="fas fa-hands-helping"></i></span>
                                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $savedAssistant ?: 'غير محدد' }}" readonly disabled>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- 3. طبيب التخدير 1 --}}
                                                <div class="col-lg col-md-4 col-sm-6">
                                                    <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 22px;">
                                                        <label for="team_anesthesiologist_name" class="form-label text-dark small fw-semibold mb-0">
                                                            <i class="fas fa-syringe text-success me-1"></i>طبيب التخدير 1:
                                                        </label>
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-1.5" style="font-size: 0.68rem;">طبيب</span>
                                                    </div>
                                                    @php
                                                        $selectedAnesth1Doc = $anesthesiaDoctors->firstWhere('id', old('anesthesiologist_id', $primaryAnesthId));
                                                        $savedAnesth1Name = $selectedAnesth1Doc
                                                            ? 'د. ' . ($selectedAnesth1Doc->user?->full_name ?? $selectedAnesth1Doc->user?->name ?? 'طبيب تخدير')
                                                            : ($primaryAnesthId && $primaryAnesthName !== 'غير محدد' ? (str_starts_with($primaryAnesthName, 'د. ') ? $primaryAnesthName : 'د. ' . $primaryAnesthName) : '');
                                                    @endphp
                                                    <input type="hidden" id="team_anesthesiologist_id" name="anesthesiologist_id" value="{{ old('anesthesiologist_id', $primaryAnesthId) }}">
                                                    @if($canWriteTeamTiming)
                                                        <div class="position-relative" id="anesth1AutocompleteWrapper">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light text-success"><i class="fas fa-syringe"></i></span>
                                                                <input type="text" id="team_anesthesiologist_name"
                                                                       class="form-control form-control-sm border-success-subtle"
                                                                       autocomplete="off"
                                                                       placeholder="اختيار أو بحث طبيب..."
                                                                       value="{{ $savedAnesth1Name }}">
                                                                <button type="button" class="btn btn-outline-secondary {{ $savedAnesth1Name ? '' : 'd-none' }}" id="clearAnesth1Btn" title="مسح">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-success" id="toggleAnesth1MenuBtn" title="عرض القائمة">
                                                                    <i class="fas fa-chevron-down"></i>
                                                                </button>
                                                            </div>
                                                            <div id="anesthesiaDoctors1Menu" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 220px; overflow-y: auto; display: none;"></div>
                                                        </div>
                                                    @else
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-success"><i class="fas fa-syringe"></i></span>
                                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $savedAnesth1Name ?: 'غير محدد' }}" readonly disabled>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- 4. طبيب التخدير 2 --}}
                                                <div class="col-lg col-md-6 col-sm-6">
                                                    <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 22px;">
                                                        <label for="team_anesthesiologist_2_name" class="form-label text-dark small fw-semibold mb-0">
                                                            <i class="fas fa-user-md text-info me-1"></i>طبيب التخدير 2:
                                                        </label>
                                                        <span class="badge bg-info-subtle text-info border border-info-subtle py-0 px-1.5" style="font-size: 0.68rem;">طبيب</span>
                                                    </div>
                                                    @php
                                                        $selectedAnesth2Doc = $anesthesiaDoctors->firstWhere('id', old('anesthesiologist_2_id', $secondaryAnesthId));
                                                        $savedAnesth2Name = $selectedAnesth2Doc
                                                            ? 'د. ' . ($selectedAnesth2Doc->user?->full_name ?? $selectedAnesth2Doc->user?->name ?? 'طبيب تخدير')
                                                            : ($secondaryAnesthId && $secondaryAnesthName !== 'غير محدد' ? (str_starts_with($secondaryAnesthName, 'د. ') ? $secondaryAnesthName : 'د. ' . $secondaryAnesthName) : '');
                                                    @endphp
                                                    <input type="hidden" id="team_anesthesiologist_2_id" name="anesthesiologist_2_id" value="{{ old('anesthesiologist_2_id', $secondaryAnesthId) }}">
                                                    @if($canWriteTeamTiming)
                                                        <div class="position-relative" id="anesth2AutocompleteWrapper">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light text-info"><i class="fas fa-user-md"></i></span>
                                                                <input type="text" id="team_anesthesiologist_2_name"
                                                                       class="form-control form-control-sm border-info-subtle"
                                                                       autocomplete="off"
                                                                       placeholder="اختيار أو بحث طبيب..."
                                                                       value="{{ $savedAnesth2Name }}">
                                                                <button type="button" class="btn btn-outline-secondary {{ $savedAnesth2Name ? '' : 'd-none' }}" id="clearAnesth2Btn" title="مسح">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-info" id="toggleAnesth2MenuBtn" title="عرض القائمة">
                                                                    <i class="fas fa-chevron-down"></i>
                                                                </button>
                                                            </div>
                                                            <div id="anesthesiaDoctors2Menu" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 220px; overflow-y: auto; display: none;"></div>
                                                        </div>
                                                    @else
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-info"><i class="fas fa-user-md"></i></span>
                                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $savedAnesth2Name ?: 'غير محدد' }}" readonly disabled>
                                                        </div>
                                                    @endif
                                                </div>

                                                {{-- 5. مساعد التخدير --}}
                                                <div class="col-lg col-md-6 col-sm-12">
                                                    <div class="d-flex align-items-center justify-content-between mb-1" style="min-height: 22px;">
                                                        <label for="team_anesthesia_assistant_name" class="form-label text-dark small fw-semibold mb-0">
                                                            <i class="fas fa-user-nurse text-secondary me-1"></i>مساعد التخدير:
                                                        </label>
                                                        @canany(['view surgeries', 'edit surgeries'])
                                                            <a href="{{ route('surgical-assistants.index', ['type' => 'anesthesia']) }}" target="_blank" class="small text-decoration-none text-info fw-semibold" style="font-size: 0.72rem;" title="فتح دليل مساعدي التخدير في نافذة جديدة">
                                                                <i class="fas fa-external-link-alt me-1"></i>دليل المساعدين
                                                            </a>
                                                        @endcanany
                                                    </div>
                                                    @php
                                                        $savedAnesthAssistant = old('anesthesia_assistant_name', $surgery->anesthesiaStation?->anesthesia_assistant_name ?? $surgery->anesthesia_assistant_name ?? '');
                                                    @endphp
                                                    @if($canWriteTeamTiming)
                                                        <div class="position-relative" id="anesthAssistantAutocompleteWrapper">
                                                            <div class="input-group input-group-sm">
                                                                <span class="input-group-text bg-light text-secondary"><i class="fas fa-user-nurse"></i></span>
                                                                <input type="text" id="team_anesthesia_assistant_name" name="anesthesia_assistant_name"
                                                                       class="form-control form-control-sm border-secondary-subtle"
                                                                       autocomplete="off"
                                                                       placeholder="اختيار أو كتابة..."
                                                                       value="{{ $savedAnesthAssistant }}">
                                                                <button type="button" class="btn btn-outline-secondary {{ $savedAnesthAssistant ? '' : 'd-none' }}" id="clearAnesthAssistantBtn" title="مسح">
                                                                    <i class="fas fa-times"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-outline-secondary" id="toggleAnesthAssistantMenuBtn" title="عرض القائمة">
                                                                    <i class="fas fa-chevron-down"></i>
                                                                </button>
                                                            </div>
                                                            <div id="anesthesiaAssistantsMenu" class="list-group position-absolute w-100 shadow-lg mt-1" style="z-index: 1065; max-height: 220px; overflow-y: auto; display: none;"></div>
                                                        </div>
                                                    @else
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text bg-light text-secondary"><i class="fas fa-user-nurse"></i></span>
                                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $savedAnesthAssistant ?: 'غير محدد' }}" readonly disabled>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            @if($canWriteTeamTiming)
                                                <input type="hidden" id="start_time" name="start_time" value="{{ old('start_time', $surgeryStartFormatted) }}">
                                                <input type="hidden" id="end_time" name="end_time" value="{{ old('end_time', $surgeryEndFormatted) }}">
                                                <input type="hidden" id="estimated_duration_minutes" name="estimated_duration_minutes" value="{{ $surgery->estimated_duration ?? '' }}">
                                                </form>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- فاصل أفقي أنيق بين الفريق الطبي وتفاصيل التخدير --}}
                                    @if(isset($visibleTabs['detail_team_timing']) && isset($visibleTabs['detail_anesthesia']))
                                        <hr class="my-1 border-secondary-subtle">
                                    @endif

                                    {{-- 2. تفاصيل وخطة التخدير السريرية (أسفل الفريق الطبي في صف واحد) --}}
                                    @if(isset($visibleTabs['detail_anesthesia']))
                                        @php
                                            $canWriteAnesthesia = $visibleTabs['detail_anesthesia']['can_write'];
                                        @endphp
                                        <div class="tab-pane fade {{ $activeTab === 'detail_anesthesia' ? 'show active' : '' }}" id="pane-detail_anesthesia" role="tabpanel" aria-labelledby="tab-btn-detail_anesthesia">
                                            @if($canWriteAnesthesia)
                                                <form id="detailAnesthesiaForm" action="{{ route('surgeries.updateDetails', $surgery) }}" method="POST" class="d-flex flex-column h-100 mb-0">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="tab" value="detail_anesthesia">
                                            @endif

                                            <div class="d-flex flex-column gap-2.5 h-100">
                                                <div class="fw-bold text-success small pb-1 border-bottom d-flex align-items-center justify-content-between">
                                                    <span><i class="fas fa-syringe me-1"></i>خطة وتفاصيل التخدير</span>
                                                    @if($canWriteAnesthesia)
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle py-0.5 px-2">تعديل</span>
                                                    @else
                                                        <span class="badge bg-secondary-subtle text-secondary border py-0.5 px-2">قراءة</span>
                                                    @endif
                                                </div>

                                                @if(!$canWriteAnesthesia)
                                                    <div class="alert alert-secondary border-secondary bg-white py-1.5 px-2 mb-0 small">
                                                        <i class="fas fa-eye me-1"></i>
                                                        قسم التخدير للعرض فقط وفق صلاحياتك؛ التوثيق لطبيب التخدير.
                                                    </div>
                                                @endif

                                                <div class="row g-3">
                                                    {{-- نوع التخدير --}}
                                                    <div class="col-md-5">
                                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-1 mb-1">
                                                            <label for="anesthesia_type_input" class="form-label text-dark small fw-semibold mb-0">
                                                                <i class="fas fa-syringe text-success me-1"></i>نوع التخدير:
                                                            </label>
                                                            @if($canWriteAnesthesia)
                                                                <div class="d-flex flex-wrap gap-1">
                                                                    <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير عام (General Anesthesia)')">عام</button>
                                                                    <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير نصفي (Spinal Anesthesia)')">نصفي</button>
                                                                    <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير مناطقي (Regional Anesthesia)')">مناطقي</button>
                                                                    <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('تخدير موضعي (Local Anesthesia)')">موضعي</button>
                                                                    <button type="button" class="clin-quick-chip" onclick="setAnesthesiaQuickType('سديشن (Sedation)')">سديشن</button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        @if($canWriteAnesthesia)
                                                            <input type="text" id="anesthesia_type_input" name="anesthesia_type" list="anesthesiaTypesList" class="form-control form-control-sm border-success-subtle" value="{{ old('anesthesia_type', $currentAnesthesiaType) }}" placeholder="مثال: تخدير عام (General Anesthesia)">
                                                            <datalist id="anesthesiaTypesList">
                                                                <option value="تخدير عام (General Anesthesia)">
                                                                <option value="تخدير نصفي (Spinal Anesthesia)">
                                                                <option value="تخدير مناطقي (Regional Anesthesia)">
                                                                <option value="تخدير موضعي (Local Anesthesia)">
                                                                <option value="سديشن (Sedation)">
                                                            </datalist>
                                                        @else
                                                            <input type="text" class="form-control form-control-sm bg-light" value="{{ $currentAnesthesiaType ?: 'غير محدد' }}" readonly disabled>
                                                        @endif
                                                    </div>

                                                    {{-- ملاحظات التخدير --}}
                                                    <div class="col-md-7 d-flex flex-column">
                                                        <div class="d-flex align-items-center justify-content-between gap-1 mb-1">
                                                            <label for="anesthesia_notes" class="form-label text-dark small fw-semibold mb-0">
                                                                <i class="fas fa-notes-medical text-success me-1"></i>ملاحظات وتفاصيل طبيب التخدير:
                                                            </label>
                                                            @if($canWriteAnesthesia)
                                                                <div class="d-flex align-items-center gap-1">
                                                                    <button type="button" class="clin-quick-chip" onclick="applyAnesthesiaTemplate(document.getElementById('anesthesia_type_input')?.value, true)" title="إدراج كليشة نوع التخدير المختار تلقائياً">
                                                                        <i class="fas fa-magic me-1"></i>كليشة التخدير
                                                                    </button>
                                                                    <button type="button" class="clin-quick-chip" onclick="fillDetailQuickText('anesthesia_notes', 'مستقر خلال التداخل، مجرى الهواء سليم، إفاقة سلسة دون مضاعفات')">إفاقة مستقرة</button>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        <textarea id="anesthesia_notes" @if($canWriteAnesthesia) name="anesthesia_notes" @else readonly disabled @endif class="form-control clin-compact-textarea flex-grow-1 {{ $canWriteAnesthesia ? 'border-success-subtle' : 'bg-light' }}" rows="5" placeholder="سجل العلامات التخديرية، الأدوية المستخدمة، تقييم مجرى الهواء، وملاحظات الإفاقة...">{{ old('anesthesia_notes', $currentAnesthesiaNotes) }}</textarea>
                                                    </div>
                                                </div>
                                            </div>

                                            @if($canWriteAnesthesia)
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
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

    {{-- زر العودة للأعلى بوضع الصفحة الواحدة --}}
    <button type="button" id="btnBackToTopSurgery" class="btn btn-primary shadow-lg" title="العودة للأعلى">
        <i class="fas fa-chevron-up"></i>
    </button>
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
            <form action="{{ route('surgeries.cancel', $surgery) }}" method="POST" class="surgery-live-action-form" onsubmit="broadcastSurgeryLiveUpdate()">
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

<!-- Modal Postpone / Reschedule -->
<div class="modal fade" id="postponeSurgeryModal" tabindex="-1" aria-labelledby="postponeSurgeryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="postponeSurgeryModalLabel">
                    <i class="fas fa-calendar-alt me-2 text-dark"></i>تأجيل موعد العملية الجراحية
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <form action="{{ route('surgeries.quick-update-schedule', $surgery) }}" method="POST" class="surgery-live-action-form" onsubmit="broadcastSurgeryLiveUpdate()">
                @csrf
                <div class="modal-body">
                    <p class="small text-muted mb-3">يمكنك تعديل تاريخ ووقت العملية لتأجيلها وإبقائها في قائمة الانتظار للموعد الجديد.</p>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">تاريخ الموعد الجديد</label>
                        <input type="date" name="scheduled_date" class="form-control" value="{{ $surgery->scheduled_date ? $surgery->scheduled_date->format('Y-m-d') : date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">الوقت الجديد</label>
                        <input type="time" name="scheduled_time" class="form-control" value="{{ $surgery->scheduled_time ? (is_string($surgery->scheduled_time) ? substr($surgery->scheduled_time, 11, 5) : $surgery->scheduled_time->format('H:i')) : '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">صالة العمليات (اختياري)</label>
                        <select name="operation_theater_id" class="form-select">
                            <option value="">-- بدون تحديد --</option>
                            @foreach(\App\Models\OperationTheater::where('is_active', true)->orderBy('name')->get() as $ot)
                                <option value="{{ $ot->id }}" {{ $surgery->operation_theater_id == $ot->id ? 'selected' : '' }}>
                                    {{ $ot->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-warning text-dark fw-bold rounded-pill px-4">حفظ وتأجيل الموعد</button>
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

        // كليشات ونماذج التخدير المعتمدة بحسب النوع
        window.anesthesiaTypeTemplates = {
            'تخدير عام': "prmedication given\ngeneralanesthesia given\noperation passesd smoothly without complication\npatient recoverd well",
            'تخدير نصفي': "2IV canulae putted\nspinal aneshesia given\npatient recovered well",
            'تخدير مناطقي': "2IV canulea putted\nregional anesthesia given\npatient recovered well",
            'تخدير موضعي': "يتم كتب التقرير من قبل الطبيب الجراح"
        };

        window.getAnesthesiaTemplate = function(type) {
            if (!type) return '';
            const clean = type.trim().toLowerCase();
            if (clean.includes('عام') || clean.includes('general')) {
                return window.anesthesiaTypeTemplates['تخدير عام'];
            }
            if (clean.includes('نصفي') || clean.includes('spinal')) {
                return window.anesthesiaTypeTemplates['تخدير نصفي'];
            }
            if (clean.includes('مناطقي') || clean.includes('regional')) {
                return window.anesthesiaTypeTemplates['تخدير مناطقي'];
            }
            if (clean.includes('موضعي') || clean.includes('local')) {
                return window.anesthesiaTypeTemplates['تخدير موضعي'];
            }
            return '';
        };

        window.applyAnesthesiaTemplate = function(typeValue, force = false) {
            const notesEl = document.getElementById('anesthesia_notes');
            if (!notesEl || notesEl.readOnly || notesEl.disabled) return;

            const template = window.getAnesthesiaTemplate(typeValue || '');
            if (!template) return;

            const currentNotes = (notesEl.value || '').trim();
            const standardList = [
                window.anesthesiaTypeTemplates['تخدير عام'].trim(),
                window.anesthesiaTypeTemplates['تخدير نصفي'].trim(),
                window.anesthesiaTypeTemplates['تخدير مناطقي'].trim(),
                window.anesthesiaTypeTemplates['تخدير موضعي'].trim(),
                "مستقر خلال التداخل، مجرى الهواء سليم، إفاقة سلسة دون مضاعفات"
            ];

            if (force || !currentNotes || standardList.includes(currentNotes)) {
                notesEl.value = template;
            }
        };

        window.setAnesthesiaQuickType = function(value) {
            const el = document.getElementById('anesthesia_type_input');
            if (!el || el.readOnly || el.disabled) return;
            el.value = value;
            window.applyAnesthesiaTemplate(value, true);
            el.focus();
        };

        window.setQuickDurationVal = function(min) {
            const input = document.getElementById('quickDurationInput');
            if (input) {
                input.value = min;
                window.updateQuickDurationHelper(min);
            }
        };

        window.updateQuickDurationHelper = function(val) {
            const helper = document.getElementById('quickDurationHelperText');
            if (!helper) return;
            const min = parseInt(val, 10);
            if (!isNaN(min) && min > 0) {
                const hours = Math.floor(min / 60);
                const minutes = min % 60;
                let formatted = '';
                if (hours > 0 && minutes > 0) {
                    formatted = hours + ' ساعة و ' + minutes + ' دقيقة';
                } else if (hours > 0) {
                    formatted = hours + ' ساعة';
                } else {
                    formatted = minutes + ' دقيقة';
                }
                helper.textContent = 'المدة: ' + min + ' دقيقة (' + formatted + ')';
            } else {
                helper.textContent = 'أدخل رقماً بالدقائق';
            }
        };

        window.syncLocationSelection = function(selectEl) {
            if (!selectEl) return;
            const val = selectEl.value;
            const text = selectEl.options[selectEl.selectedIndex]?.text || 'غير محددة';
            const hiddenLoc = document.getElementById('team_location_id');
            if (hiddenLoc) {
                hiddenLoc.value = val;
            }
            const badge = document.getElementById('topLocationBadge');
            if (badge) {
                badge.textContent = val ? text : 'غير محددة';
                badge.className = 'badge rounded-pill ' + (val ? 'bg-danger text-white' : 'bg-light text-muted border') + ' px-2 py-1';
            }
            const previewBadge = document.getElementById('topLocationPreviewBadge');
            if (previewBadge) {
                previewBadge.textContent = text;
            }
        };

        window.submitUnifiedSurgeryForm = function() {
            const saveBtn = document.getElementById('btnUnifiedSaveSurgery');
            if (saveBtn) {
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-white"></i> <span>جاري الحفظ...</span>';
            }

            const diagForm = document.getElementById('detailDiagnosisForm');
            const teamForm = document.getElementById('detailTeamTimingForm');
            const anesthForm = document.getElementById('detailAnesthesiaForm');
            const quickDurationInput = document.getElementById('quickDurationInput');
            const topLocationSelect = document.getElementById('top_location_id');

            // Sync top location into hidden field if selected
            if (topLocationSelect && topLocationSelect.value) {
                const hiddenLoc = document.getElementById('team_location_id');
                if (hiddenLoc) hiddenLoc.value = topLocationSelect.value;
            }

            const formCount = (diagForm ? 1 : 0) + (teamForm ? 1 : 0) + (anesthForm ? 1 : 0);

            // If only one writable form is present on the page (e.g. single-role Surgeon or Anesthesiologist or Surgery Staff)
            if (formCount === 1) {
                const targetForm = diagForm || teamForm || anesthForm;
                if (targetForm) {
                    if (quickDurationInput && quickDurationInput.value) {
                        let durField = targetForm.querySelector('#estimated_duration_minutes');
                        if (!durField) {
                            durField = document.createElement('input');
                            durField.type = 'hidden';
                            durField.name = 'estimated_duration_minutes';
                            durField.id = 'estimated_duration_minutes';
                            targetForm.appendChild(durField);
                        }
                        durField.value = quickDurationInput.value;
                    }
                    if (topLocationSelect && topLocationSelect.value) {
                        let locField = targetForm.querySelector('#team_location_id');
                        if (!locField) {
                            locField = document.createElement('input');
                            locField.type = 'hidden';
                            locField.name = 'location_id';
                            locField.id = 'team_location_id';
                            targetForm.appendChild(locField);
                        }
                        locField.value = topLocationSelect.value;
                    }
                    if (typeof window.broadcastSurgeryLiveUpdate === 'function') {
                        window.broadcastSurgeryLiveUpdate();
                    }
                    targetForm.submit();
                    return;
                }
            }

            // For multi-role users or Admin: merge all writable forms and submit in one unscoped PATCH request
            const unifiedForm = document.createElement('form');
            unifiedForm.method = 'POST';
            unifiedForm.action = '{{ route("surgeries.updateDetails", $surgery) }}';
            unifiedForm.style.display = 'none';

            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = '_token';
            tokenInput.value = '{{ csrf_token() }}';
            unifiedForm.appendChild(tokenInput);

            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'PATCH';
            unifiedForm.appendChild(methodInput);

            const gatheredNames = new Set(['_token', '_method', 'tab']);

            [diagForm, teamForm, anesthForm].forEach(form => {
                if (!form) return;
                const formData = new FormData(form);
                for (let [name, value] of formData.entries()) {
                    if (gatheredNames.has(name)) continue;
                    if (name === 'estimated_duration_minutes' && quickDurationInput && quickDurationInput.value) {
                        value = quickDurationInput.value;
                    }
                    if (name === 'location_id' && topLocationSelect && topLocationSelect.value) {
                        value = topLocationSelect.value;
                    }
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = name;
                    input.value = value;
                    unifiedForm.appendChild(input);
                }
            });

            if (quickDurationInput && quickDurationInput.value && !unifiedForm.querySelector('[name="estimated_duration_minutes"]')) {
                const durInput = document.createElement('input');
                durInput.type = 'hidden';
                durInput.name = 'estimated_duration_minutes';
                durInput.value = quickDurationInput.value;
                unifiedForm.appendChild(durInput);
            }

            if (topLocationSelect && topLocationSelect.value && !unifiedForm.querySelector('[name="location_id"]')) {
                const locInput = document.createElement('input');
                locInput.type = 'hidden';
                locInput.name = 'location_id';
                locInput.value = topLocationSelect.value;
                unifiedForm.appendChild(locInput);
            }

            if (typeof window.broadcastSurgeryLiveUpdate === 'function') {
                window.broadcastSurgeryLiveUpdate();
            }

            document.body.appendChild(unifiedForm);
            unifiedForm.submit();
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

    // ================= منظومة القائمة المنسدلة الذكية لمساعد الجراح (مثل التصنيف الدولي للأمراض) =================
    window.surgicalAssistantsData = {!! json_encode($surgicalAssistants ?? [], JSON_UNESCAPED_UNICODE) !!};
    window.anesthesiaAssistantsData = {!! json_encode($anesthesiaAssistants ?? [], JSON_UNESCAPED_UNICODE) !!};
    window.anesthesiaDoctorsData = {!! json_encode(collect($anesthesiaDoctors ?? [])->map(function($doc) {
        return [
            'id' => $doc->id,
            'name' => 'د. ' . ($doc->user?->full_name ?? $doc->user?->name ?? 'طبيب تخدير'),
            'specialization' => $doc->specialization ?? 'طبيب تخدير',
        ];
    })->values(), JSON_UNESCAPED_UNICODE) !!};

    function setupSurgicalAssistantAutocomplete() {
        const input = document.getElementById('surgical_assistant_name');
        const menu = document.getElementById('surgicalAssistantsMenu');
        const clearBtn = document.getElementById('clearAssistantBtn');
        const toggleBtn = document.getElementById('toggleAssistantMenuBtn');
        const wrapper = document.getElementById('assistantAutocompleteWrapper');

        if (!input || !menu) return;

        function renderAssistantOptions(filterText = '') {
            const query = (filterText || '').trim().toLowerCase();
            const list = window.surgicalAssistantsData || [];

            let matched = list.filter(item => {
                const name = (item.name || '').toLowerCase();
                const notes = (item.notes || '').toLowerCase();
                return !query || name.includes(query) || notes.includes(query);
            });

            if (matched.length === 0) {
                if (query.length > 0) {
                    menu.innerHTML = `
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 assistant-option-item" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-plus-circle text-success me-2"></i>
                                <span class="fw-bold text-dark">${filterText}</span>
                            </div>
                            <span class="badge bg-success-subtle text-success border small">استخدام هذا الاسم</span>
                        </div>
                    `;
                    const customOpt = menu.querySelector('.assistant-option-item');
                    if (customOpt) {
                        customOpt.addEventListener('click', function() {
                            selectAssistant(filterText);
                        });
                    }
                    menu.style.display = 'block';
                } else {
                    menu.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>لا توجد أسماء مساعدين مسجلة</div>';
                    menu.style.display = 'block';
                }
                return;
            }

            let html = '';
            matched.forEach(item => {
                const isSelected = input.value.trim() === item.name.trim();
                const selectedClass = isSelected ? 'bg-primary-subtle border-primary' : '';
                const roleBadge = item.notes
                    ? `<span class="badge bg-light text-secondary border small">${item.notes}</span>`
                    : '<span class="badge bg-info-subtle text-info border small">مساعد جراح</span>';

                html += `
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 assistant-option-item ${selectedClass}" style="cursor: pointer;" data-assistant-name="${item.name.replace(/"/g, '&quot;')}">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-md text-primary me-2"></i>
                            <strong class="text-dark">${item.name}</strong>
                        </div>
                        ${roleBadge}
                    </div>
                `;
            });

            menu.innerHTML = html;
            menu.style.display = 'block';

            menu.querySelectorAll('.assistant-option-item').forEach(el => {
                el.addEventListener('click', function() {
                    const name = this.getAttribute('data-assistant-name');
                    if (name) selectAssistant(name);
                });
            });
        }

        function selectAssistant(name) {
            input.value = name;
            if (clearBtn) clearBtn.classList.remove('d-none');
            menu.style.display = 'none';
        }

        input.addEventListener('focus', function() {
            renderAssistantOptions(this.value);
        });

        input.addEventListener('input', function() {
            if (clearBtn) clearBtn.classList.toggle('d-none', !this.value.trim());
            renderAssistantOptions(this.value);
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                } else {
                    renderAssistantOptions('');
                    input.focus();
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                input.value = '';
                this.classList.add('d-none');
                renderAssistantOptions('');
                input.focus();
            });
        }

        document.addEventListener('click', function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    }

    function setupAnesthesiaAssistantAutocomplete() {
        const input = document.getElementById('team_anesthesia_assistant_name');
        const menu = document.getElementById('anesthesiaAssistantsMenu');
        const clearBtn = document.getElementById('clearAnesthAssistantBtn');
        const toggleBtn = document.getElementById('toggleAnesthAssistantMenuBtn');
        const wrapper = document.getElementById('anesthAssistantAutocompleteWrapper');

        if (!input || !menu) return;

        function renderAnesthAssistantOptions(filterText = '') {
            const query = (filterText || '').trim().toLowerCase();
            const list = window.anesthesiaAssistantsData || [];

            let matched = list.filter(item => {
                const name = (item.name || '').toLowerCase();
                const notes = (item.notes || '').toLowerCase();
                return !query || name.includes(query) || notes.includes(query);
            });

            if (matched.length === 0) {
                if (query.length > 0) {
                    menu.innerHTML = `
                        <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 anesth-assistant-option-item" style="cursor: pointer;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-plus-circle text-info me-2"></i>
                                <span class="fw-bold text-dark">${filterText}</span>
                            </div>
                            <span class="badge bg-info-subtle text-info border small">استخدام هذا الاسم</span>
                        </div>
                    `;
                    const customOpt = menu.querySelector('.anesth-assistant-option-item');
                    if (customOpt) {
                        customOpt.addEventListener('click', function() {
                            selectAnesthAssistant(filterText);
                        });
                    }
                    menu.style.display = 'block';
                } else {
                    menu.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>لا توجد أسماء مسجلة؛ يمكنك كتابة الاسم مباشرة</div>';
                    menu.style.display = 'block';
                }
                return;
            }

            let html = '';
            matched.forEach(item => {
                const isSelected = input.value.trim() === item.name.trim();
                const selectedClass = isSelected ? 'bg-info-subtle border-info' : '';
                const roleBadge = item.notes
                    ? `<span class="badge bg-light text-secondary border small">${item.notes}</span>`
                    : '<span class="badge bg-info-subtle text-info border small">مساعد تخدير</span>';

                html += `
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 anesth-assistant-option-item ${selectedClass}" style="cursor: pointer;" data-assistant-name="${item.name.replace(/"/g, '&quot;')}">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-nurse text-info me-2"></i>
                            <strong class="text-dark">${item.name}</strong>
                        </div>
                        ${roleBadge}
                    </div>
                `;
            });

            menu.innerHTML = html;
            menu.style.display = 'block';

            menu.querySelectorAll('.anesth-assistant-option-item').forEach(el => {
                el.addEventListener('click', function() {
                    const name = this.getAttribute('data-assistant-name');
                    if (name) selectAnesthAssistant(name);
                });
            });
        }

        function selectAnesthAssistant(name) {
            input.value = name;
            if (clearBtn) clearBtn.classList.remove('d-none');
            menu.style.display = 'none';
        }

        input.addEventListener('focus', function() {
            renderAnesthAssistantOptions(this.value);
        });

        input.addEventListener('input', function() {
            if (clearBtn) clearBtn.classList.toggle('d-none', !this.value.trim());
            renderAnesthAssistantOptions(this.value);
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                } else {
                    renderAnesthAssistantOptions('');
                    input.focus();
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                input.value = '';
                this.classList.add('d-none');
                renderAnesthAssistantOptions('');
                input.focus();
            });
        }

        document.addEventListener('click', function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    }

    function setupAnesthesiaDoctorAutocomplete(slotNum) {
        const hiddenInput = document.getElementById(slotNum === 1 ? 'team_anesthesiologist_id' : 'team_anesthesiologist_2_id');
        const input = document.getElementById(slotNum === 1 ? 'team_anesthesiologist_name' : 'team_anesthesiologist_2_name');
        const menu = document.getElementById(slotNum === 1 ? 'anesthesiaDoctors1Menu' : 'anesthesiaDoctors2Menu');
        const clearBtn = document.getElementById(slotNum === 1 ? 'clearAnesth1Btn' : 'clearAnesth2Btn');
        const toggleBtn = document.getElementById(slotNum === 1 ? 'toggleAnesth1MenuBtn' : 'toggleAnesth2MenuBtn');
        const wrapper = document.getElementById(slotNum === 1 ? 'anesth1AutocompleteWrapper' : 'anesth2AutocompleteWrapper');
        const badgeColor = slotNum === 1 ? 'success' : 'info';
        const docIcon = slotNum === 1 ? 'fa-syringe' : 'fa-user-md';

        if (!input || !menu || !hiddenInput) return;

        function renderDoctorOptions(filterText = '') {
            const query = (filterText || '').trim().toLowerCase();
            const list = window.anesthesiaDoctorsData || [];

            let matched = list.filter(item => {
                const name = (item.name || '').toLowerCase();
                const spec = (item.specialization || '').toLowerCase();
                return !query || name.includes(query) || spec.includes(query);
            });

            if (matched.length === 0) {
                menu.innerHTML = '<div class="p-3 text-center text-muted small"><i class="fas fa-info-circle me-1"></i>لا يوجد أطباء تخدير مطابقين للبحث</div>';
                menu.style.display = 'block';
                return;
            }

            let html = '';
            matched.forEach(item => {
                const isSelected = String(hiddenInput.value) === String(item.id);
                const selectedClass = isSelected ? `bg-${badgeColor}-subtle border-${badgeColor}` : '';
                const specBadge = `<span class="badge bg-${badgeColor}-subtle text-${badgeColor} border small">${item.specialization || 'طبيب تخدير'}</span>`;

                html += `
                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2 px-3 anesth-doctor-option-item-${slotNum} ${selectedClass}" style="cursor: pointer;" data-doctor-id="${item.id}" data-doctor-name="${item.name.replace(/"/g, '&quot;')}">
                        <div class="d-flex align-items-center">
                            <i class="fas ${docIcon} text-${badgeColor} me-2"></i>
                            <strong class="text-dark">${item.name}</strong>
                        </div>
                        ${specBadge}
                    </div>
                `;
            });

            menu.innerHTML = html;
            menu.style.display = 'block';

            menu.querySelectorAll(`.anesth-doctor-option-item-${slotNum}`).forEach(el => {
                el.addEventListener('click', function() {
                    const id = this.getAttribute('data-doctor-id');
                    const name = this.getAttribute('data-doctor-name');
                    if (id) selectDoctor(id, name);
                });
            });
        }

        function selectDoctor(id, name) {
            hiddenInput.value = id;
            input.value = name;
            if (clearBtn) clearBtn.classList.remove('d-none');
            menu.style.display = 'none';
        }

        input.addEventListener('focus', function() {
            renderDoctorOptions(this.value);
        });

        input.addEventListener('input', function() {
            if (!this.value.trim()) {
                hiddenInput.value = '';
                if (clearBtn) clearBtn.classList.add('d-none');
            } else {
                if (clearBtn) clearBtn.classList.remove('d-none');
            }
            renderDoctorOptions(this.value);
        });

        input.addEventListener('change', function() {
            const query = this.value.trim().toLowerCase();
            if (!query) {
                hiddenInput.value = '';
                if (clearBtn) clearBtn.classList.add('d-none');
                return;
            }
            const list = window.anesthesiaDoctorsData || [];
            const exact = list.find(item => item.name.toLowerCase() === query || item.name.replace(/^د\.\s*/, '').toLowerCase() === query);
            if (exact) {
                hiddenInput.value = exact.id;
                input.value = exact.name;
                if (clearBtn) clearBtn.classList.remove('d-none');
            }
        });

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (menu.style.display === 'block') {
                    menu.style.display = 'none';
                } else {
                    renderDoctorOptions('');
                    input.focus();
                }
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                hiddenInput.value = '';
                input.value = '';
                this.classList.add('d-none');
                renderDoctorOptions('');
                input.focus();
            });
        }

        document.addEventListener('click', function(e) {
            if (wrapper && !wrapper.contains(e.target)) {
                menu.style.display = 'none';
            }
        });
    }

    function setupAnesthesiaTypeTemplateSync() {
        const anesthTypeEl = document.getElementById('anesthesia_type_input');
        if (!anesthTypeEl) return;

        anesthTypeEl.addEventListener('change', function() {
            window.applyAnesthesiaTemplate(this.value, false);
        });

        anesthTypeEl.addEventListener('input', function() {
            const val = this.value.trim();
            const datalist = document.getElementById('anesthesiaTypesList');
            if (datalist) {
                const options = Array.from(datalist.options).map(o => o.value);
                if (options.includes(val)) {
                    window.applyAnesthesiaTemplate(val, false);
                }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setupSurgicalAssistantAutocomplete();
            setupAnesthesiaDoctorAutocomplete(1);
            setupAnesthesiaDoctorAutocomplete(2);
            setupAnesthesiaAssistantAutocomplete();
            setupAnesthesiaTypeTemplateSync();
            initSinglePageNavigation();
        });
    } else {
        setupSurgicalAssistantAutocomplete();
        setupAnesthesiaDoctorAutocomplete(1);
        setupAnesthesiaDoctorAutocomplete(2);
        setupAnesthesiaAssistantAutocomplete();
        setupAnesthesiaTypeTemplateSync();
        initSinglePageNavigation();
    }

    // ================= منظومة الصفحة الواحدة الموحدة =================
    function initSinglePageNavigation() {
        const backToTopBtn = document.getElementById('btnBackToTopSurgery');

        // إذا كان هناك tab في الرابط URL نقفز إليه مباشرة
        const urlParams = new URLSearchParams(window.location.search);
        const requestedTab = urlParams.get('tab');
        if (requestedTab) {
            const targetPane = document.getElementById('pane-' + requestedTab);
            if (targetPane) {
                setTimeout(() => {
                    targetPane.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 200);
            }
        }

        // إظهار وإخفاء زر الصعود للأعلى
        window.addEventListener('scroll', function() {
            if (backToTopBtn) {
                if (window.scrollY > 350) {
                    backToTopBtn.style.display = 'flex';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            }
        }, { passive: true });

        if (backToTopBtn) {
            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    }

    // ==========================================
    // Realtime Live Sync Broadcast
    // ==========================================
    function broadcastSurgeryLiveUpdate() {
        try {
            const timestamp = Date.now().toString();
            localStorage.setItem('hospital_surgery_live_trigger', timestamp);
            if (typeof BroadcastChannel !== 'undefined') {
                const bc = new BroadcastChannel('hospital_surgery_live_channel');
                bc.postMessage({
                    action: 'surgery_updated',
                    surgery_id: {{ $surgery->id }},
                    time: timestamp
                });
                bc.close();
            }
        } catch (e) {
            console.error('Error broadcasting surgery live update:', e);
        }
    }

    window.broadcastSurgeryLiveUpdate = broadcastSurgeryLiveUpdate;

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.surgery-live-action-form').forEach(form => {
            form.addEventListener('submit', function() {
                broadcastSurgeryLiveUpdate();
            });
        });

        @if(session('success'))
        broadcastSurgeryLiveUpdate();
        @endif
    });
</script>
@endsection
