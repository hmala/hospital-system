@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="clinicalStationApp">
    {{-- CSS Styles --}}
    <style>
        :root {
            --clin-navy: #476abdff;
            --clin-teal: #1c6dd8ff;
            --clin-blue: #2563eb;
            --clin-card-bg: #6866ceff;
        }

        /* Ultra-Modern Clinical Executive Header */
        .clin-header-modern {
            background: #ffffff !important;
            border-radius: 16px;
            padding: 12px 20px;
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
            font-size: 1.22rem;
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

        /* Segmented Control iOS / iPadOS Style */
        .clin-segmented-control {
            display: inline-flex;
            align-items: center;
            background: #f1f5f9;
            padding: 3px;
            border-radius: 999px;
            border: 1px solid #e2e8f0;
        }

        .clin-segment-item {
            padding: 5px 14px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #64748b;
            text-decoration: none;
            border-radius: 999px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
        }
        .clin-segment-item:hover {
            color: #0f172a;
        }
        .clin-segment-item.active {
            background: #ffffff;
            color: #2563eb !important;
            font-weight: 800;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }

        /* Modern Status Badge with Colored Pulse Indicator */
        .badge-status-modern {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 0.75rem;
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

        /* Responsive Tablet-Ready Horizontal Tabs Bar (Zero Scroll / Auto-Fit) */
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
            min-width: 72px;
        }

        @media (max-width: 991px) {
            .clin-tab-item {
                flex: 1 1 calc(20% - 4px);
                min-width: 80px;
            }
            .clin-header-card {
                padding: 10px 14px;
            }
        }

        @media (max-width: 576px) {
            .clin-tab-item {
                flex: 1 1 calc(33.33% - 4px);
            }
        }

        .clin-nav-tab {
            font-size: 0.81rem;
            font-weight: 700;
            padding: 8px 4px;
            min-height: 42px;
            border-radius: 8px;
            color: #475569;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            text-decoration: none;
            text-align: center;
            width: 100%;
            white-space: nowrap;
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
            background: rgba(255, 255, 255, 0.3) !important;
            color: #ffffff !important;
        }

        .badge-perm-dot {
            font-size: 0.6rem;
            padding: 1px 4px;
            border-radius: 4px;
            line-height: 1;
        }
        .badge-perm-read {
            background: #eff6ff;
            color: #1d4ed8;
        }
        .badge-perm-write {
            background: #f0fdf4;
            color: #15803d;
        }

        .breadcrumb-item + .breadcrumb-item::before {
            padding-left: 0.4rem !important;
            padding-right: 0.4rem !important;
        }

        .pane-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.03);
            overflow: hidden;
        }
        .pane-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 22px;
        }

        .vital-box {
            border-radius: 14px;
            padding: 14px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .vital-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .vital-box .vital-val {
            font-size: 1.4rem;
            font-weight: 800;
            margin: 4px 0;
        }

        .vital-box-red { border-color: #fecaca; background: #fff5f5; }
        .vital-box-orange { border-color: #fed7aa; background: #fffaf0; }
        .vital-box-blue { border-color: #bfdbfe; background: #f0f9ff; }
        .vital-box-green { border-color: #bbf7d0; background: #f0fdf4; }
        .vital-box-purple { border-color: #ddd6fe; background: #faf5ff; }

        .timeline-card {
            border-inline-start: 3px solid #2563eb;
            background: #f8fafc;
            border-radius: 0 12px 12px 0;
            padding: 14px 18px;
            margin-bottom: 12px;
        }
    </style>

    {{-- Breadcrumb & Quick Actions --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('surgeries.index') }}" class="text-decoration-none">إدارة العمليات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('surgeries.waiting') }}" class="text-decoration-none">قائمة الانتظار اليومية</a></li>
                <li class="breadcrumb-item active" aria-current="page">المحطة السريرية الموحدة (#{{ $surgery->id }})</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-2">
            @can('manage surgery tab permissions')
                <a href="{{ route('surgery-stations.tab-settings.index') }}" class="btn btn-outline-dark btn-sm rounded-pill px-3" title="تخصيص صلاحيات التبويبات">
                    <i class="fas fa-sliders-h me-1"></i> ضبط صلاحيات التبويبات
                </a>
            @endcan
            <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-right me-1"></i> العودة للجدول
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
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

    {{-- Ultra-Modern Clinical Executive Header --}}
    <div class="clin-header-modern mb-3">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            
            {{-- Left Side: 3 Visual Pillars --}}
            <div class="d-flex flex-wrap align-items-center gap-3 gap-xl-4">
                
                {{-- Pillar 1: Patient Identity --}}
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-modern-box flex-shrink-0">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="clin-patient-name mb-0">{{ $surgery->patient?->name ?? 'غير محدد' }}</h3>
                            <span class="badge-modern-code">
                                #{{ $surgery->patient?->patient_id ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2 text-muted small mt-1">
                            <span class="badge-modern-sub">
                                <i class="fas fa-birthday-cake me-1 text-primary"></i> {{ $surgery->patient?->age ? $surgery->patient->age . ' سنة' : 'العمر غير مسجل' }}
                            </span>
                            <span class="badge-modern-sub">
                                <i class="fas fa-venus-mars me-1 text-info"></i> {{ $surgery->patient?->gender === 'male' ? 'ذكر' : 'أنثى' }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Vertical Divider 1 --}}
                <div class="clin-v-divider d-none d-md-block"></div>

                {{-- Pillar 2: Procedure & Doctor --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-procedures text-primary me-1"></i> العملية الجراحية
                    </div>
                    <div class="fw-bold text-dark fs-6 text-truncate" style="max-width: 260px;" title="{{ $surgery->surgery_name }}">
                        {{ $surgery->surgery_name }}
                    </div>
                    <div class="text-secondary small mt-0.5 d-flex align-items-center gap-1.5">
                        <span class="badge-doctor-chip">
                            <i class="fas fa-user-md text-success me-1"></i> د. {{ $surgery->doctor?->name ?? 'غير محدد' }}
                        </span>
                    </div>
                </div>

                {{-- Vertical Divider 2 --}}
                <div class="clin-v-divider d-none d-lg-block"></div>

                {{-- Pillar 3: Location & Time --}}
                <div>
                    <div class="clin-meta-label">
                        <i class="fas fa-map-marker-alt text-warning me-1"></i> الموقع والجدولة
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-0.5">
                        <span class="badge-room-chip">
                            <i class="fas fa-door-open me-1 text-amber"></i> {{ $surgery->operationTheater?->name ?? $surgery->room?->room_number ?? 'غير مخصصة' }}
                        </span>
                        <span class="badge-time-chip">
                            <i class="far fa-clock me-1 text-secondary"></i> {{ $surgery->scheduled_time ? $surgery->scheduled_time->format('h:i A') : '--:--' }}
                        </span>
                    </div>
                </div>

            </div>

            {{-- Right Side: Phase Segmented Switcher & Clinical Status --}}
            <div class="d-flex flex-wrap align-items-center gap-2.5 ms-auto ms-lg-0">
                {{-- Apple / iPadOS Style Segmented Control --}}
                <div class="clin-segmented-control" role="group">
                    <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'pre_op', 'tab' => $activeTab]) }}" 
                       class="clin-segment-item {{ $currentPhase === 'pre_op' ? 'active' : '' }}">
                        <i class="fas fa-arrow-circle-right me-1"></i> ما قبل العملية
                    </a>
                    <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'post_op', 'tab' => $activeTab]) }}" 
                       class="clin-segment-item {{ $currentPhase === 'post_op' ? 'active' : '' }}">
                        <i class="fas fa-check-circle me-1"></i> ما بعد العملية
                    </a>
                </div>

                {{-- Phase Status Modern Pill with Glowing Pulse Indicator --}}
                <div class="d-flex align-items-center gap-1.5">
                    @if($station && $station->status === 'completed')
                        <span class="badge-status-modern badge-status-completed">
                            <span class="status-pulse-dot bg-success"></span>
                            مكتملة
                        </span>
                    @elseif($station && $station->status === 'in_progress')
                        <span class="badge-status-modern badge-status-progress">
                            <span class="status-pulse-dot bg-warning"></span>
                            قيد المتابعة
                        </span>
                    @else
                        <span class="badge-status-modern badge-status-waiting">
                            <span class="status-pulse-dot bg-secondary"></span>
                            بالانتظار
                        </span>
                    @endif

                    @if($surgery->room_id && $surgery->room_checkin_at && !$surgery->discharged_at)
                        <form action="{{ route('nursing-station.room-checkout', $surgery) }}" method="POST" class="d-inline" onsubmit="return confirm('تأكيد تخريج المريض من الغرفة وإخلاء السرير؟ سيتم إيقاف احتساب الإقامة وتحرير الغرفة.')">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1 shadow-xs fw-semibold" style="font-size: 0.78rem;" title="تخريج المريض وإخلاء السرير">
                                <i class="fas fa-sign-out-alt me-1"></i> تخريج من الغرفة
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    </div>

    {{-- Tabs Navigation Bar (Responsive Tablet Grid - Zero Scroll Auto-Fit) --}}
    @php
        $shortTabLabels = [
            'history' => 'السيرة',
            'vitals' => 'العلامات',
            'plan' => 'الخطة',
            'follow_up' => 'المتابعة',
            'treatments' => 'الأدوية',
            'fluids' => 'السوائل',
            'nursing_care' => 'التمريض',
            'inpatient_survey' => 'الاستبيان ⭐',
            'tests' => 'الفحوصات',
        ];
    @endphp

    <div class="clin-tabs-wrapper mb-3">
        <ul class="clin-tabs-grid" id="clinicalStationTabs" role="tablist">
            @foreach($visibleTabs as $tKey => $tMeta)
                <li class="clin-tab-item" role="presentation">
                    <a class="clin-nav-tab {{ $activeTab === $tKey ? 'active' : '' }}" 
                       href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $currentPhase, 'tab' => $tKey]) }}"
                       title="{{ $tMeta['label'] }}">
                        <i class="{{ $tMeta['icon'] }} small"></i>
                        <span>{{ $shortTabLabels[$tKey] ?? $tMeta['label'] }}</span>
                        @if($tMeta['can_write'])
                            <span class="badge-perm-dot badge-perm-write" title="صلاحية إدخال وتعديل"><i class="fas fa-pen"></i></span>
                        @else
                            <span class="badge-perm-dot badge-perm-read" title="صلاحية قراءة فقط"><i class="fas fa-eye"></i></span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Active Tab Content Card --}}
    @php
        $currentTabMeta = $visibleTabs[$activeTab] ?? null;
        $canWriteCurrentTab = $currentTabMeta['can_write'] ?? false;
    @endphp

    @if(!$currentTabMeta)
        <div class="alert alert-warning text-center p-5 rounded-4 shadow-sm">
            <i class="fas fa-lock fa-3x text-warning mb-3"></i>
            <h5 class="fw-bold">لا تملك صلاحية الوصول لأي تبويب في هذه المحطة</h5>
            <p class="text-muted mb-0">يرجى مراجعة إدارة المستشفى لمنحك الصلاحيات المناسبة لدورك الوظيفي.</p>
        </div>
    @else
        <div class="pane-card mb-4">
            {{-- Tab Pane Header --}}
            <div class="pane-header d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2.5">
                        <i class="{{ $currentTabMeta['icon'] }} fa-lg"></i>
                    </div>
                    <div>
                        <h5 class="mb-0 fw-bold text-dark">{{ $currentTabMeta['label'] }}</h5>
                        <p class="text-muted small mb-0">{{ $currentTabMeta['description'] }}</p>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    @if($canWriteCurrentTab)
                        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill fw-bold small">
                            <i class="fas fa-edit me-1"></i> إدخال وتعديل متاح بحسابك
                        </span>
                    @else
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2 rounded-pill fw-bold small">
                            <i class="fas fa-eye me-1"></i> وضع القراءة والاطلاع فقط
                        </span>
                    @endif
                </div>
            </div>

            {{-- Form for tabs that submit directly to update --}}
            <form action="{{ route('clinical-station.update', $surgery) }}" method="POST">
                @csrf
                @method('PATCH')
                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                <div class="p-4">
                    {{-- =============================================================== --}}
                    {{-- TAB 1: HISTORY (السيرة المرضية) --}}
                    {{-- =============================================================== --}}
                    @if($activeTab === 'history')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-comment-medical text-primary me-1"></i> الشكوى الرئيسية (Chief Complaint):</label>
                                <textarea name="chief_complaint" class="form-control rounded-3" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="الشكوى والأعراض التي يشكو منها المريض...">{{ $station?->chief_complaint }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-notes-medical text-info me-1"></i> تفاصيل وتاريخ المرض الحالي (History of Present Illness):</label>
                                <textarea name="history_present_illness" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="التدرج الزمني وتفاصيل الحالة المرضية الحالية...">{{ $station?->history_present_illness }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-heartbeat text-danger me-1"></i> التاريخ المرضي السابق (Past Medical Hx):</label>
                                <textarea name="past_medical_hx" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="أمراض مزمنة، ضغط، سكري، ربو، قلب...">{{ $station?->past_medical_hx }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-cut text-warning me-1"></i> التاريخ الجراحي السابق (Past Surgical Hx):</label>
                                <textarea name="past_surgical_hx" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="عمليات سابقة، مضاعفات التخدير...">{{ $station?->past_surgical_hx }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-pills text-success me-1"></i> التاريخ الدوائي (Drug Hx):</label>
                                <textarea name="drug_hx" class="form-control rounded-3" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="الأدوية التي يتناولها بانتظام...">{{ $station?->drug_hx }}</textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-danger"><i class="fas fa-exclamation-triangle me-1"></i> التحسس الدوائي والغذائي (Allergies):</label>
                                <textarea name="drug_allergy" class="form-control rounded-3 border-danger-subtle bg-danger bg-opacity-10" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="حساسية البنسلين، أدوية محددة...">{{ $station?->drug_allergy }}</textarea>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 2: VITALS & PHYSICAL EXAM (العلامات الحيوية والفحص السريري) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'vitals')
                        {{-- Top Vital Sign Cards --}}
                        <div class="row g-3 row-cols-2 row-cols-md-3 row-cols-lg-5 mb-4">
                            <div class="col">
                                <div class="vital-box vital-box-red">
                                    <div class="small fw-bold text-danger"><i class="fas fa-tachometer-alt me-1"></i> ضغط الدم (BP)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="bp" class="form-control form-control-sm text-center fw-bold mt-2" 
                                               placeholder="120/80" value="{{ $station?->bp }}">
                                    @else
                                        <div class="vital-val text-danger">{{ $station?->bp ?: '--/--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">mmHg</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-orange">
                                    <div class="small fw-bold text-warning-emphasis"><i class="fas fa-thermometer-half me-1"></i> الحرارة (Temp)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="temp" class="form-control form-control-sm text-center fw-bold mt-2" 
                                               placeholder="37.0" value="{{ $station?->temp }}">
                                    @else
                                        <div class="vital-val text-warning-emphasis">{{ $station?->temp ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">°C</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-red">
                                    <div class="small fw-bold text-danger"><i class="fas fa-heart me-1"></i> النبض (PR)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="pr" class="form-control form-control-sm text-center fw-bold mt-2" 
                                               placeholder="75" value="{{ $station?->pr }}">
                                    @else
                                        <div class="vital-val text-danger">{{ $station?->pr ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">bpm</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-blue">
                                    <div class="small fw-bold text-primary"><i class="fas fa-lungs me-1"></i> التنفس (RR)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="rr" class="form-control form-control-sm text-center fw-bold mt-2" 
                                               placeholder="16" value="{{ $station?->rr }}">
                                    @else
                                        <div class="vital-val text-primary">{{ $station?->rr ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">/min</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-green">
                                    <div class="small fw-bold text-success"><i class="fas fa-wind me-1"></i> الأكسجين (SPo2)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="spo2" class="form-control form-control-sm text-center fw-bold mt-2" 
                                               placeholder="98" value="{{ $station?->spo2 }}">
                                    @else
                                        <div class="vital-val text-success">{{ $station?->spo2 ? $station->spo2 . '%' : '--%' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.72rem;">%</div>
                                </div>
                            </div>
                        </div>

                        {{-- Secondary Vitals Row --}}
                        <div class="row g-3 row-cols-2 row-cols-md-4 mb-4">
                            <div class="col">
                                <div class="card p-2 text-center rounded-3 border bg-light">
                                    <span class="small text-muted fw-bold">مقياس الألم (Pain Score):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="pain_score" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="0 - 10" value="{{ $station?->pain_score }}">
                                    @else
                                        <strong class="text-dark fs-6">{{ $station?->pain_score ?: '0' }} / 10</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-2 text-center rounded-3 border bg-light">
                                    <span class="small text-muted fw-bold">سكر الدم (RBS):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="rbs" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="110 mg/dL" value="{{ $station?->rbs }}">
                                    @else
                                        <strong class="text-dark fs-6">{{ $station?->rbs ?: '--' }} mg/dL</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-2 text-center rounded-3 border bg-light">
                                    <span class="small text-muted fw-bold">مقياس الوعي (GCS):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="gcs" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="15/15" value="{{ $station?->gcs }}">
                                    @else
                                        <strong class="text-dark fs-6">{{ $station?->gcs ?: '15/15' }}</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-2 text-center rounded-3 border bg-light">
                                    <span class="small text-muted fw-bold">إعادة امتلاء الشعيرات (CRT):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="crt" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="< 2 sec" value="{{ $station?->crt }}">
                                    @else
                                        <strong class="text-dark fs-6">{{ $station?->crt ?: '< 2 sec' }}</strong>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Clinical Examination --}}
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark"><i class="fas fa-stethoscope text-primary me-1"></i> ملاحظات الفحص السريري (Clinical Examination):</label>
                            <textarea name="clinical_examination" class="form-control rounded-3" rows="3" 
                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                      placeholder="ملاحظات الفحص العام وفحص الأجهزة الحيوية...">{{ $station?->clinical_examination }}</textarea>
                        </div>

                        {{-- Trend Charts Section --}}
                        <div class="card rounded-4 border p-3 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-chart-line text-primary me-2"></i> المنحنيات البيانية لتطور العلامات الحيوية</h6>
                                <span class="badge bg-light text-secondary border small">{{ $readingsAsc->count() }} قراءة مسجلة</span>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-2 bg-light bg-opacity-25">
                                        <div class="text-center small fw-bold text-danger mb-2">منحنى ضغط الدم (BP Trend)</div>
                                        <div style="height: 180px;">
                                            <canvas id="bpChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-3 p-2 bg-light bg-opacity-25">
                                        <div class="text-center small fw-bold text-danger mb-2">منحنى النبض (Pulse Rate Trend)</div>
                                        <div style="height: 180px;">
                                            <canvas id="prChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Readings Log Table --}}
                        <div class="card rounded-4 border overflow-hidden">
                            <div class="card-header bg-light py-2 px-3 fw-bold small text-dark d-flex align-items-center justify-content-between">
                                <span><i class="fas fa-history text-secondary me-1"></i> سجل القراءات التاريخية للمريض</span>
                                <span class="text-muted" style="font-size: 0.75rem;">مرتبة من الأحدث للأقدم</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover mb-0 align-middle text-center small">
                                    <thead class="table-light">
                                        <tr>
                                            <th>الوقت والتاريخ</th>
                                            <th>الضغط</th>
                                            <th>الحرارة</th>
                                            <th>النبض</th>
                                            <th>التنفس</th>
                                            <th>الأكسجين</th>
                                            <th>السكر</th>
                                            <th>المسجل</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($readings as $r)
                                            <tr>
                                                <td class="text-muted">{{ $r->created_at->format('Y-m-d h:i A') }}</td>
                                                <td><span class="badge bg-danger bg-opacity-10 text-danger border">{{ $r->bp ?: '--' }}</span></td>
                                                <td>{{ $r->temp ? $r->temp . '°C' : '--' }}</td>
                                                <td>{{ $r->pr ? $r->pr . ' bpm' : '--' }}</td>
                                                <td>{{ $r->rr ?: '--' }}</td>
                                                <td><span class="badge bg-success bg-opacity-10 text-success border">{{ $r->spo2 ? $r->spo2 . '%' : '--' }}</span></td>
                                                <td>{{ $r->rbs ?: '--' }}</td>
                                                <td class="text-secondary">{{ $r->resident?->user?->name ?? 'طاقم الرعاية' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="8" class="text-muted py-3">لم يتم تسجيل قراءات أرشيفية سابقة حتى الآن.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 3: LAB & RADIOLOGY TESTS (الفحوصات والمختبر والأشعة) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'tests')
                        <div class="row g-4">
                            {{-- Lab Tests --}}
                            <div class="col-lg-6">
                                <div class="card rounded-3 border h-100">
                                    <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-danger"><i class="fas fa-flask me-1"></i> تحاليل المختبر (Lab Tests)</h6>
                                        <span class="badge bg-danger rounded-pill">{{ $surgery->labTests?->count() ?? 0 }}</span>
                                    </div>
                                    <div class="p-3">
                                        @if($surgery->labTests && $surgery->labTests->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($surgery->labTests as $lt)
                                                    <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ $lt->labTest?->name ?? $lt->test?->name ?? 'فحص مخبري' }}</div>
                                                            <div class="text-muted small">النتيجة: <strong>{{ $lt->result ?: 'قيد الإجراء' }}</strong></div>
                                                        </div>
                                                        <div>
                                                            @if($lt->status === 'completed')
                                                                <span class="badge bg-success">جاهز</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark">{{ $lt->status }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-4 small">لا توجد طلبات تحاليل مخبرية مرتبطة بهذه العملية.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Radiology Tests --}}
                            <div class="col-lg-6">
                                <div class="card rounded-3 border h-100">
                                    <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-primary"><i class="fas fa-x-ray me-1"></i> الفحوصات الإشعاعية (Radiology)</h6>
                                        <span class="badge bg-primary rounded-pill">{{ $surgery->radiologyTests?->count() ?? 0 }}</span>
                                    </div>
                                    <div class="p-3">
                                        @if($surgery->radiologyTests && $surgery->radiologyTests->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($surgery->radiologyTests as $rt)
                                                    <div class="list-group-item px-0 py-2 d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <div class="fw-bold text-dark">{{ $rt->radiologyType?->name ?? $rt->radiologyService?->name ?? 'فحص إشعاعي' }}</div>
                                                            <div class="text-muted small">{{ $rt->result ?: ($rt->findings ?? 'قيد الفحص والتقرير') }}</div>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-info text-dark">{{ $rt->status }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-4 small">لا توجد صور أو فحوصات إشعاعية مسجلة للعملية.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 4: MEDICAL PLAN (الخطة الطبية وتوصيات الجراحة) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'plan')
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-clipboard-check text-primary me-1"></i> الخطة الطبية وتوصيات ما قبل الجراحة (Pre-Op Plan):</label>
                                <textarea name="notes" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="تعليمات الصيام، نقل الدم، تجهيز الصالة...">{{ $station?->notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-procedures text-success me-1"></i> توصيات وملاحظات ما بعد الجراحة (Post-Op Instructions):</label>
                                <textarea name="post_op_notes" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="العناية بالجرح، المسكنات، وقت بدء السوائل...">{{ $station?->post_op_notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-stethoscope text-info me-1"></i> مراجعة الأجهزة الحيوية (Review of Systems):</label>
                                <textarea name="review_of_other_systems" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="الصدر، البطن، الجهاز العصبي، الجهاز البولي...">{{ $station?->review_of_other_systems }}</textarea>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 5: TREATMENTS (الأدوية وجدول الإعطاء) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'treatments')
                        {{-- Handled below outside standard form to support administer actions --}}
                    
                    {{-- =============================================================== --}}
                    {{-- TAB 6: FOLLOW UP SHEET (سجل المتابعة اليومي) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'follow_up')
                        {{-- Handled below outside standard form to support multi-entries --}}

                    {{-- =============================================================== --}}
                    {{-- TAB 7: FLUIDS (رصد السوائل Intake & Output) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'fluids')
                        <div class="row g-4">
                            {{-- Intake Column --}}
                            <div class="col-md-6">
                                <div class="card rounded-3 border h-100 p-3 bg-light bg-opacity-50">
                                    <h6 class="fw-bold text-primary border-bottom pb-2 mb-3">
                                        <i class="fas fa-arrow-alt-circle-down me-1"></i> السوائل الداخلة (Intake)
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">مغذي وسوائل وريدية (IV Fluids) - ml:</label>
                                        <input type="number" name="intake_iv_fluids" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_iv_fluids }}" placeholder="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">سوائل عن طريق الفم (Oral Fluids) - ml:</label>
                                        <input type="number" name="intake_oral" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_oral }}" placeholder="0">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">نقل دم ومشتقاته (Blood Products) - ml:</label>
                                        <input type="number" name="intake_blood" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_blood }}" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            {{-- Output Column --}}
                            <div class="col-md-6">
                                <div class="card rounded-3 border h-100 p-3 bg-light bg-opacity-50">
                                    <h6 class="fw-bold text-danger border-bottom pb-2 mb-3">
                                        <i class="fas fa-arrow-alt-circle-up me-1"></i> السوائل الخارجة (Output)
                                    </h6>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">إدرار البول (Urine) - ml:</label>
                                        <input type="number" name="output_urine" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_urine }}" placeholder="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">المفجر الجراحي (Drain) - ml:</label>
                                        <input type="number" name="output_drain" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_drain }}" placeholder="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">أنبوب المعدة (NG Tube) - ml:</label>
                                        <input type="number" name="output_gtube_ng" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_gtube_ng }}" placeholder="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">القيء (Vomiting) - ml:</label>
                                        <input type="number" name="output_vomiting" class="form-control form-control-sm" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_vomiting }}" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            {{-- Net Fluid Balance Banner --}}
                            <div class="col-12">
                                @php
                                    $net = $station?->fluid_balance;
                                @endphp
                                <div class="card rounded-3 border p-3 text-center {{ $net >= 0 ? 'bg-success bg-opacity-10 border-success-subtle' : 'bg-danger bg-opacity-10 border-danger-subtle' }}">
                                    <span class="small fw-bold text-secondary">صافي ميزان السوائل المسجل (Net Fluid Balance):</span>
                                    <h3 class="fw-bold mb-0 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $net !== null ? ($net > 0 ? '+' . $net : $net) . ' ml' : 'لم يُحتسب بعد' }}
                                    </h3>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 8: NURSING CARE & NOTES (الرعاية التمريضية والتخريج) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'nursing_care')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-user-nurse text-primary me-1"></i> الممرض المسؤول عن الحالة:</label>
                                @if($canWriteCurrentTab)
                                    <select name="nurse_id" class="form-select rounded-3">
                                        <option value="">-- اختر الممرض المسؤول --</option>
                                        @foreach($nurses as $nurse)
                                            <option value="{{ $nurse->id }}" {{ ($nursingStation?->nurse_id == $nurse->id) ? 'selected' : '' }}>
                                                {{ $nurse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control" readonly value="{{ $nursingStation?->nurse?->name ?? 'غير محدد' }}">
                                @endif
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-notes-medical text-primary me-1"></i> ملاحظات الرعاية التمريضية (Nursing Care Notes):</label>
                                <textarea name="nursing_notes" class="form-control rounded-3" rows="4" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="ملاحظات الممرض حول حالة المريض، تغيير الضماد، الاستجابة للعلاج...">{{ $nursingStation?->nursing_notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-bold text-dark"><i class="fas fa-walking text-success me-1"></i> تعليمات وملاحظات التخريج (Discharge Notes):</label>
                                <textarea name="discharge_notes" class="form-control rounded-3" rows="3" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="تعليمات الخروج للمنزل، مواعيد المراجعة، النصائح الغذائية...">{{ $nursingStation?->discharge_notes }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Footer for direct update forms --}}
                @if(!in_array($activeTab, ['treatments', 'follow_up', 'inpatient_survey', 'tests']) && $canWriteCurrentTab)
                    <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small">
                            <i class="fas fa-info-circle text-info me-1"></i> المحطة السريرية تحفظ البيانات فورياً وتزامنها مع ملف المريض وقسم التمريض.
                        </span>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ التحديثات السريرية
                        </button>
                    </div>
                @endif
            </form>

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR INPATIENT SURVEY TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'inpatient_survey')
                @php
                    $survey = $surgery->inpatientSurvey ?? \App\Models\InpatientRoomSurvey::firstOrCreate(
                        ['surgery_id' => $surgery->id],
                        [
                            'patient_id' => $surgery->patient_id,
                            'doctor_id' => $surgery->doctor_id,
                            'room_id' => $surgery->room_id,
                            'location_id' => $surgery->location_id ?? $surgery->room?->location_id,
                            'room_type' => $surgery->room?->type ?? 'regular',
                            'status' => 'pending',
                        ]
                    );
                    $surveyUrl = route('inpatient-surveys.public.show', ['uuid' => $survey->uuid]);
                    $canResetSurvey = auth()->check() && (
                        auth()->user()->hasAnyRole(['admin', 'super_admin', 'super-admin', 'hospital_admin', 'مدير النظام']) ||
                        auth()->user()->role === 'admin' ||
                        auth()->user()->can('manage settings') ||
                        auth()->user()->can('manage surgery waiting list')
                    );
                @endphp

                <div class="p-4">
                    {{-- STATE A: PENDING (NOT YET COMPLETED) -> GRAND CENTERED QR CODE ACROSS THE PAGE --}}
                    @if($survey->status !== 'completed')
                        {{-- Top Action & Info Bar --}}
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4 p-3 bg-white rounded-4 border shadow-xs">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                    <i class="fas fa-qrcode fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">استبيان تقييم غرفة الرقود وجودة الرعاية</h6>
                                    <small class="text-muted">امسح الرمز أو اطبعه لتمكين المريض أو المرافق من تعبئة التقييم السريري مباشرة من الهاتف</small>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-2 fw-semibold">
                                    <i class="fas fa-hourglass-half me-1"></i> بانتظار مسح وإرسال التقييم
                                </span>
                                <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-outline-dark rounded-pill px-3 py-1.5 fw-semibold small shadow-xs">
                                    <i class="fas fa-receipt me-1"></i> طباعة كرت حراري (80mm)
                                </a>
                                <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" target="_blank" class="btn btn-outline-primary rounded-pill px-3 py-1.5 fw-semibold small shadow-xs">
                                    <i class="fas fa-file-pdf me-1"></i> طباعة تقرير A4
                                </a>
                                <a href="{{ route('inpatient-surveys.print-blank') }}" target="_blank" class="btn btn-light border rounded-pill px-3 py-1.5 fw-semibold small">
                                    <i class="fas fa-print me-1"></i> استبيان فارغ A4
                                </a>
                                <button type="button" class="btn btn-light border rounded-pill px-3 py-1.5 fw-semibold small" onclick="window.location.reload();" title="تحديث للتحقق من وصول التقييم">
                                    <i class="fas fa-sync-alt me-1"></i> تحديث
                                </button>
                            </div>
                        </div>

                        {{-- Grand Centered QR Presentation Card --}}
                        <div class="card border-0 shadow-sm rounded-4 bg-white text-center p-4 p-md-5 my-2">
                            <div class="mx-auto" style="max-width: 680px;">
                                <div class="mb-3">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1.5 fw-bold fs-6">
                                        <i class="fas fa-door-open me-1"></i> غرفة {{ $surgery->room?->room_number ?? '-' }} — {{ $surgery->room?->type === 'vip' ? 'جناح خاص VIP' : 'غرفة مشتركة' }}
                                    </span>
                                </div>

                                <h3 class="fw-bold text-dark mb-2">
                                    امسح رمز التقييم عبر كاميرا الهاتف
                                </h3>
                                <p class="text-muted mb-4 fs-6">
                                    وجّه كاميرا الهاتف المحمول أو تطبيق الباركود نحو الرمز التالي ليقوم المريض 
                                    <strong class="text-dark">({{ $surgery->patient?->name }})</strong> أو مرافقه بالإجابة بخصوصية تامة.
                                </p>

                                {{-- The Big QR Code Display Box --}}
                                <div class="p-3 p-sm-4 bg-light rounded-4 border d-inline-block shadow-sm my-2 position-relative">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=340x340&margin=0&data={{ urlencode($surveyUrl) }}"
                                         alt="Inpatient Room Survey QR Code"
                                         class="rounded-3 bg-white p-2 shadow-xs"
                                         style="width: 270px; height: 270px; max-width: 100%; display: block; aspect-ratio: 1/1;">
                                </div>

                                <div class="mt-3 mb-4">
                                    <span class="badge bg-light text-muted border rounded-pill px-3 py-2 small fw-normal">
                                        <i class="fas fa-shield-alt text-success me-1"></i> رابط آمن ومباشر لا يتطلب تسجيل الدخول
                                    </span>
                                </div>

                                {{-- Fast Actions / Link Tools --}}
                                <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap mb-4">
                                    <a href="{{ $surveyUrl }}" target="_blank" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                                        <i class="fas fa-external-link-alt me-1"></i> فتح صفحة الاستبيان
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" id="btnCopySurveyLink" onclick="navigator.clipboard.writeText('{{ $surveyUrl }}'); this.innerHTML='<i class=\'fas fa-check text-success me-1\'></i> تم النسخ بنجاح!'; setTimeout(() => { this.innerHTML='<i class=\'fas fa-copy me-1\'></i> نسخ الرابط'; }, 2500);">
                                        <i class="fas fa-copy me-1"></i> نسخ الرابط
                                    </button>
                                    <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-dark rounded-pill px-4 py-2 fw-semibold">
                                        <i class="fas fa-receipt me-1"></i> كرت مريض حراري
                                    </a>
                                    <button type="button" class="btn btn-outline-primary rounded-pill px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#qrFullScreenModal">
                                        <i class="fas fa-expand me-1"></i> تكبير بملء الشاشة
                                    </button>
                                </div>

                                {{-- Auto-Transition Note --}}
                                <div class="p-3 rounded-3 bg-info bg-opacity-10 border border-info border-opacity-25 text-start d-flex align-items-start gap-2">
                                    <i class="fas fa-info-circle text-info mt-1"></i>
                                    <div class="small text-dark">
                                        <strong>ملاحظة للمحطة السريرية:</strong> بمجرد قيام المريض أو مرافقه بإرسال التقييم عبر هاتفه، سيختفي رمز الباركود من هذه الصفحة وستظهر مباشرة نتائج جميع مفردات الاستبيان (النظافة، التكييف، الأثاث، راحة السرير، وزيارة الطبيب).
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Full-screen QR Modal (Only available in pending state) --}}
                        <div class="modal fade" id="qrFullScreenModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-4 border-0 shadow-lg text-center p-4">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold text-dark w-100">
                                            مسح استبيان غرفة الرقود (غرفة {{ $surgery->room?->room_number ?? '-' }})
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-4">
                                        <div class="p-3 bg-white border rounded-4 d-inline-block shadow-sm mb-3">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=340x340&margin=0&data={{ urlencode($surveyUrl) }}"
                                                 alt="QR Code" style="width: 280px; height: 280px; display: block;">
                                        </div>
                                        <p class="text-muted fw-semibold mb-0">
                                            وجّه كاميرا الهاتف نحو الرمز لفتح الاستبيان مباشرة بكل أريحية.
                                        </p>
                                    </div>
                                    <div class="modal-footer border-0 pt-0 justify-content-center">
                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
                                        <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-primary rounded-pill px-4 fw-bold">
                                            <i class="fas fa-print me-1"></i> طباعة كرت
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- STATE B: COMPLETED -> QR CODE DISAPPEARS AND FULL-WIDTH SCORECARD REPLACES IT --}}
                    @else
                        {{-- Executive Result Hero Banner --}}
                        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                            <div class="card-body p-4 text-white">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                    {{-- Left: Score & Verdict --}}
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-4 d-flex flex-column align-items-center justify-content-center text-center shadow-sm px-3 py-2"
                                             style="min-width: 95px; height: 95px; background: {{ $survey->overall_score >= 85 ? 'linear-gradient(135deg, #10b981, #059669)' : ($survey->overall_score >= 70 ? 'linear-gradient(135deg, #3b82f6, #1d4ed8)' : ($survey->overall_score >= 50 ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #ef4444, #b91c1c)')) }};">
                                            <span class="fs-2 fw-black text-white lh-1">{{ round($survey->overall_score) }}%</span>
                                            <span class="small text-white-50 mt-1" style="font-size: 0.72rem;">التقييم الإجمالي</span>
                                        </div>
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <h5 class="mb-0 fw-bold text-white">نتائج استبيان تقييم غرفة الرقود</h5>
                                                <span class="badge bg-success text-white rounded-pill px-2.5 py-1 small">
                                                    <i class="fas fa-check-circle me-1"></i> تقييم مكتمل
                                                </span>
                                            </div>
                                            <p class="mb-1 text-light opacity-75 small">
                                                تم إرسال هذا التقييم بواسطة: 
                                                <strong class="text-white">{{ $survey->submitted_via === 'patient_qr' ? '📱 هاتف المريض عبر رمز الباركود (QR)' : '👨‍⚕️ إدخال الكادر السريري' }}</strong>
                                                @if($survey->completed_at)
                                                    بتاريخ {{ \Carbon\Carbon::parse($survey->completed_at)->format('Y-m-d h:i A') }}
                                                @endif
                                            </p>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                @if($survey->recommend_score_nps)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 small">
                                                        <i class="fas fa-thumbs-up text-warning me-1"></i> مؤشر التوصية (NPS): <strong class="text-warning">{{ $survey->recommend_score_nps }}</strong> من 10
                                                    </span>
                                                @endif
                                                @if($survey->room_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        🛏️ الغرفة: {{ round($survey->room_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->nursing_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        🩺 التمريض: {{ round($survey->nursing_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->lab_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        🧪 المختبر: {{ round($survey->lab_score) }}%
                                                    </span>
                                                @endif
                                                @if($survey->pharmacy_score)
                                                    <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-2.5 py-1 small">
                                                        💊 الصيدلية: {{ round($survey->pharmacy_score) }}%
                                                    </span>
                                                @endif
                                                <span class="badge bg-white bg-opacity-15 text-white border border-white border-opacity-25 rounded-pill px-3 py-1 small">
                                                    <i class="fas fa-door-closed me-1"></i> غرفة {{ $surgery->room?->room_number ?? '-' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right: Actions & Admin Reset --}}
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        @if($canResetSurvey)
                                            <form method="POST" action="{{ route('inpatient-surveys.reset', $surgery) }}" class="d-inline" onsubmit="return confirm('تنبيه هام: هل أنت متأكد من تصفير وإعادة فتح الاستبيان؟\n\nسيتم حذف الإجابات الحالية وإعادة إظهار باركود التقييم للمريض مجدداً.');">
                                                @csrf
                                                <button type="submit" class="btn btn-warning text-dark rounded-pill px-3 py-2 fw-bold shadow-sm" title="إعادة فتح وتصفير الاستبيان وإعادة إظهار الباركود">
                                                    <i class="fas fa-undo-alt me-1"></i> إعادة فتح الاستبيان (أدمن)
                                                </button>
                                            </form>
                                        @endif

                                        <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" target="_blank" class="btn btn-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-file-pdf me-1"></i> طباعة تقرير A4
                                        </a>
                                        <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-light rounded-pill px-3 py-2 fw-bold shadow-sm">
                                            <i class="fas fa-receipt me-1"></i> إيصال حراري
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Compact Results Dashboard: 2-Column Balanced View (27 Clinical & Hospitality Metrics) --}}
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-3 px-1 flex-wrap gap-2">
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">
                                        <i class="fas fa-poll-h text-primary me-2"></i> تفاصيل تقييم جميع خدمات ومفردات الاستبيان (27 معياراً سريرياً وفندقياً)
                                    </h6>
                                    <small class="text-muted">درجات التقييم من 1 (ضعيف) إلى 5 (ممتاز) مدخلة ومقسمة حسب الأقسام السريرية والخدمية</small>
                                </div>
                                <span class="badge bg-white text-dark border shadow-xs rounded-pill px-3 py-1.5 fw-semibold small">
                                    إجمالي البنود المقيمة: 27 / 27
                                </span>
                            </div>

                            @php
                                $getRatingInfo = function($val) {
                                    return match((int)$val) {
                                        5 => ['text' => 'ممتاز (5/5)', 'badge' => 'bg-success text-white', 'style' => ''],
                                        4 => ['text' => 'جيد جداً (4/5)', 'badge' => 'bg-info text-white', 'style' => ''],
                                        3 => ['text' => 'جيد (3/5)', 'badge' => 'bg-warning text-dark', 'style' => ''],
                                        2 => ['text' => 'مقبول (2/5)', 'badge' => 'text-white', 'style' => 'background:#f97316;'],
                                        1 => ['text' => 'ضعيف (1/5)', 'badge' => 'bg-danger text-white', 'style' => ''],
                                        default => ['text' => 'غير مقيم', 'badge' => 'bg-secondary-subtle text-secondary border', 'style' => ''],
                                    };
                                };

                                $roomMetrics = [
                                    'cleanliness_room' => ['label' => 'النظافة العامة لغرفة الرقود', 'icon' => 'fas fa-broom', 'color' => '#10b981'],
                                    'cleanliness_bathroom' => ['label' => 'نظافة الحمام والمرافق الصحية', 'icon' => 'fas fa-sink', 'color' => '#06b6d4'],
                                    'temperature_ac' => ['label' => 'التكييف والحرارة والتهوية', 'icon' => 'fas fa-wind', 'color' => '#3b82f6'],
                                    'lighting' => ['label' => 'إضاءة الغرفة ومستوى الراحة البصرية', 'icon' => 'fas fa-lightbulb', 'color' => '#f59e0b'],
                                    'quietness_privacy' => ['label' => 'الهدوء والسكينة والخصوصية بالقسم', 'icon' => 'fas fa-volume-mute', 'color' => '#8b5cf6'],
                                    'bed_comfort' => ['label' => 'راحة السرير والمفارش ونظافتها', 'icon' => 'fas fa-bed', 'color' => '#ec4899'],
                                    'control_panel' => ['label' => 'لوحة استدعاء التمريض وسرعة الاستجابة', 'icon' => 'fas fa-bell', 'color' => '#ef4444'],
                                    'storage_space' => ['label' => 'مساحات التخزين والدواليب', 'icon' => 'fas fa-door-closed', 'color' => '#64748b'],
                                    'furniture_safety' => ['label' => 'جودة وأمان الأثاث ومرافق الغرفة', 'icon' => 'fas fa-chair', 'color' => '#14b8a6'],
                                    'entertainment' => ['label' => 'الترفيه (شاشة التلفاز والواي فاي)', 'icon' => 'fas fa-tv', 'color' => '#6366f1'],
                                ];

                                $doctorMetrics = [
                                    'doctor_communication' => ['label' => 'تواصل وزيارة الطبيب المعالج واهتمامه', 'icon' => 'fas fa-user-md', 'color' => '#059669'],
                                ];

                                $nursingMetrics = [
                                    'nursing_greeting' => ['label' => 'الترحيب والمهنية في التعامل', 'icon' => 'fas fa-smile', 'color' => '#8b5cf6'],
                                    'nursing_skill' => ['label' => 'المهارة في تقديم الرعاية (حقن وتضميد)', 'icon' => 'fas fa-syringe', 'color' => '#7c3aed'],
                                    'nursing_care_attention' => ['label' => 'الاهتمام والرعاية المستمرة', 'icon' => 'fas fa-heartbeat', 'color' => '#ec4899'],
                                    'nursing_instructions' => ['label' => 'وضوح التعليمات والتواصل معك', 'icon' => 'fas fa-comment-medical', 'color' => '#6366f1'],
                                    'nursing_response_speed' => ['label' => 'سرعة الاستجابة عند طلب المساعدة', 'icon' => 'fas fa-stopwatch', 'color' => '#f59e0b'],
                                    'nursing_privacy_respect' => ['label' => 'المحافظة على الخصوصية والكرامة', 'icon' => 'fas fa-user-shield', 'color' => '#10b981'],
                                ];

                                $labMetrics = [
                                    'lab_prep_instructions' => ['label' => 'وضوح تعليمات التحضير للتحليل', 'icon' => 'fas fa-clipboard-list', 'color' => '#06b6d4'],
                                    'lab_sampling_skill' => ['label' => 'الاحتراف والمهارة في سحب العينة', 'icon' => 'fas fa-hand-holding-medical', 'color' => '#0284c7'],
                                    'lab_hygiene_sterilization' => ['label' => 'النظافة والتعقيم في سحب العينات', 'icon' => 'fas fa-pump-soap', 'color' => '#14b8a6'],
                                    'lab_results_speed' => ['label' => 'سرعة استخراج النتائج وتسليمها', 'icon' => 'fas fa-clock', 'color' => '#3b82f6'],
                                    'lab_staff_behavior' => ['label' => 'التعامل اللطيف والمطمئن أثناء العملية', 'icon' => 'fas fa-user-check', 'color' => '#10b981'],
                                ];

                                $pharmacyMetrics = [
                                    'pharmacy_dispense_speed' => ['label' => 'سرعة وصحة صرف الأدوية المطلوبة', 'icon' => 'fas fa-shipping-fast', 'color' => '#f97316'],
                                    'pharmacy_instruction_clarity' => ['label' => 'وضوح شرح طريقة استخدام الدواء', 'icon' => 'fas fa-info-circle', 'color' => '#eab308'],
                                    'pharmacy_comprehension_check' => ['label' => 'التأكد من فهمك الكامل لتعليمات الدواء', 'icon' => 'fas fa-tasks', 'color' => '#84cc16'],
                                    'pharmacy_respect_politeness' => ['label' => 'اللباقة والاحترام في التعامل من الصيدلي', 'icon' => 'fas fa-smile-beam', 'color' => '#10b981'],
                                    'pharmacy_med_availability' => ['label' => 'التوفر الكامل للأدوية الموصوفة', 'icon' => 'fas fa-boxes', 'color' => '#3b82f6'],
                                ];
                            @endphp

                            <div class="row g-3">
                                {{-- Column 1: Room & Doctor (11 Metrics) --}}
                                <div class="col-lg-6">
                                    {{-- Room Facilities Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-bed" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">١. مرافق غرفة الرقود (الورقة الأولى)</strong>
                                            </div>
                                            @if($survey->room_score)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    نسبة الرضا: {{ round($survey->room_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($roomMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Doctor Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-user-md" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">٢. الفريق الطبي المعالج</strong>
                                            </div>
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($doctorMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                {{-- Column 2: Nursing, Lab & Pharmacy (16 Metrics) --}}
                                <div class="col-lg-6">
                                    {{-- Nursing Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(147, 51, 234, 0.1); color: #7c3aed;">
                                                    <i class="fas fa-user-nurse" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">٣. طاقم التمريض (الورقة الثانية)</strong>
                                            </div>
                                            @if($survey->nursing_score)
                                                <span class="badge rounded-pill px-2.5 py-1 small fw-bold" style="background: rgba(147, 51, 234, 0.1); color: #7c3aed; border: 1px solid rgba(147, 51, 234, 0.2);">
                                                    نسبة الرضا: {{ round($survey->nursing_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($nursingMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Lab Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-vial" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">٤. قسم المختبر والتحاليل (الورقة الثانية)</strong>
                                            </div>
                                            @if($survey->lab_score)
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    نسبة الرضا: {{ round($survey->lab_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($labMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Pharmacy Card --}}
                                    <div class="card border-0 shadow-sm rounded-4 bg-white mb-3 overflow-hidden">
                                        <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                    <i class="fas fa-pills" style="font-size: 0.8rem;"></i>
                                                </div>
                                                <strong class="text-dark small">٥. قسم الصيدلية والتعامل مع الأدوية (الورقة الثانية)</strong>
                                            </div>
                                            @if($survey->pharmacy_score)
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2.5 py-1 small fw-bold">
                                                    نسبة الرضا: {{ round($survey->pharmacy_score) }}%
                                                </span>
                                            @endif
                                        </div>
                                        <div class="card-body p-0">
                                            @foreach($pharmacyMetrics as $field => $info)
                                                @php
                                                    $val = $survey->{$field};
                                                    $rInfo = $getRatingInfo($val);
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-between py-2 px-3 border-bottom {{ $loop->last ? 'border-bottom-0' : '' }}" style="font-size: 0.82rem;">
                                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                                        <i class="{{ $info['icon'] }}" style="color: {{ $info['color'] }}; width: 16px; text-align: center; font-size: 0.82rem;"></i>
                                                        <span class="text-dark fw-semibold text-truncate">{{ $info['label'] }}</span>
                                                    </div>
                                                    <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                        <div class="d-inline-flex align-items-center" style="gap: 2px;">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                <i class="{{ $i <= $val ? 'fas fa-star text-warning' : 'far fa-star text-muted opacity-25' }}" style="font-size: 0.72rem;"></i>
                                                            @endfor
                                                        </div>
                                                        <span class="badge {{ $rInfo['badge'] }} rounded-pill px-2 py-0.5 fw-bold" style="font-size: 0.72rem; min-width: 62px; text-align: center; {{ $rInfo['style'] ?? '' }}">
                                                            {{ $rInfo['text'] }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Patient Feedback, Priorities & Departmental Remarks --}}
                        @php
                            $hasPriorities = !empty($survey->top_three_improvements) && is_array($survey->top_three_improvements) && count($survey->top_three_improvements) > 0;
                            $hasComments = !empty($survey->room_notes) || !empty($survey->patient_suggestions) || !empty($survey->doctor_notes) || !empty($survey->nursing_notes) || !empty($survey->lab_notes) || !empty($survey->pharmacy_notes) || !empty($survey->respondent_name);
                        @endphp

                        @if($hasPriorities || $hasComments)
                            <div class="row g-4">
                                {{-- Top 3 Priorities --}}
                                @if($hasPriorities)
                                    <div class="{{ $hasComments ? 'col-lg-5' : 'col-12' }}">
                                        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                                            <div class="card-header bg-white py-3 px-4 border-bottom">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-bullseye text-warning me-2"></i> أهم أولويات التحسين المحددة من المريض
                                                </h6>
                                            </div>
                                            <div class="card-body p-4">
                                                <p class="small text-muted mb-3">حدد المريض المجالات التالية كأولويات تتطلب التطوير مستقبلاً:</p>
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach($survey->top_three_improvements as $idx => $priority)
                                                        <div class="d-flex align-items-center gap-2 p-2.5 rounded-3 bg-light border">
                                                            <span class="badge bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.8rem;">
                                                                {{ $idx + 1 }}
                                                            </span>
                                                            <span class="small fw-semibold text-dark">{{ $priority }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                {{-- Qualitative Notes & Departmental Remarks --}}
                                @if($hasComments)
                                    <div class="{{ $hasPriorities ? 'col-lg-7' : 'col-12' }}">
                                        <div class="card border-0 shadow-sm rounded-4 bg-white h-100">
                                            <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center justify-content-between">
                                                <h6 class="mb-0 fw-bold text-dark">
                                                    <i class="fas fa-comment-dots text-primary me-2"></i> ملاحظات ومقترحات المريض التفصيلية
                                                </h6>
                                                @if($survey->respondent_name || $survey->respondent_phone || $survey->respondent_email)
                                                    <span class="badge bg-light text-muted border rounded-pill px-2.5 py-1 small">
                                                        <i class="fas fa-user-edit me-1"></i> {{ $survey->respondent_name ?? 'المريض/المرافق' }}
                                                        {{ $survey->respondent_phone ? ' | '.$survey->respondent_phone : '' }}
                                                        {{ $survey->respondent_email ? ' | '.$survey->respondent_email : '' }}
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="card-body p-4 d-flex flex-column gap-3">
                                                @if(!empty($survey->patient_suggestions))
                                                    <div class="p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-25">
                                                        <strong class="d-block small text-primary mb-1"><i class="fas fa-lightbulb me-1"></i> مقترحات المريض الإضافية:</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->patient_suggestions }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->nursing_notes))
                                                    <div class="p-3 rounded-3 bg-purple bg-opacity-10 border border-purple border-opacity-25" style="background: rgba(147, 51, 234, 0.05); border-color: rgba(147, 51, 234, 0.2);">
                                                        <strong class="d-block small text-purple mb-1" style="color: #7c3aed;"><i class="fas fa-user-nurse me-1"></i> ملاحظات كادر التمريض:</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->nursing_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->lab_notes))
                                                    <div class="p-3 rounded-3 bg-info bg-opacity-10 border border-info border-opacity-25">
                                                        <strong class="d-block small text-info mb-1"><i class="fas fa-vial me-1"></i> ملاحظات قسم المختبر:</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->lab_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->pharmacy_notes))
                                                    <div class="p-3 rounded-3 bg-warning bg-opacity-10 border border-warning border-opacity-25">
                                                        <strong class="d-block small text-warning text-dark mb-1"><i class="fas fa-pills me-1"></i> ملاحظات الصيدلية وصرف الأدوية:</strong>
                                                        <p class="small text-dark mb-0">{{ $survey->pharmacy_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->room_notes))
                                                    <div class="p-3 rounded-3 bg-light border">
                                                        <strong class="d-block small text-dark mb-1"><i class="fas fa-bed me-1"></i> ملاحظات مرافق الغرفة:</strong>
                                                        <p class="small text-muted mb-0">{{ $survey->room_notes }}</p>
                                                    </div>
                                                @endif

                                                @if(!empty($survey->doctor_notes))
                                                    <div class="p-3 rounded-3 bg-light border">
                                                        <strong class="d-block small text-dark mb-1"><i class="fas fa-user-md me-1"></i> ملاحظات زيارة الطبيب:</strong>
                                                        <p class="small text-muted mb-0">{{ $survey->doctor_notes }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endif
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR TREATMENTS TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'treatments')
                <div class="p-4">
                    {{-- Prescribed Medications Table --}}
                    <div class="card rounded-3 border mb-4">
                        <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-warning me-2"></i> جدول الأدوية والعلاجات الموصوفة</h6>
                                <span class="badge bg-primary rounded-pill">{{ $treatments->count() }} دواء</span>
                                @if(isset($floorLocation) && $floorLocation)
                                    <span class="badge bg-warning bg-opacity-20 text-dark border border-warning-subtle" title="المخزن المعتمد لغرفة المريض">
                                        <i class="fas fa-warehouse text-warning me-1"></i> عهدة: {{ $floorLocation->name }} ({{ $surgery->room?->room_number ? 'غرفة ' . $surgery->room->room_number : 'طابق ' . ($surgery->room?->floor ?? '--') }})
                                    </span>
                                @endif
                            </div>
                            @if($canWriteCurrentTab && isset($floorLocation) && $floorLocation)
                                <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 py-1 fw-bold text-dark shadow-xs" data-bs-toggle="modal" data-bs-target="#dispenseFloorStockModal">
                                    <i class="fas fa-box-open me-1"></i> صرف علاج / مستلزم من عهدة الطابق
                                </button>
                            @endif
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle text-center small">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th class="text-start">اسم الدواء والعلاج</th>
                                        <th>الجرعة</th>
                                        <th>التوقيت والتكرار</th>
                                        <th>المدة</th>
                                        <th>الحالة</th>
                                        <th>آخر إعطاء</th>
                                        @if($canWriteCurrentTab)
                                            <th>الإجراء التمريضي</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($treatments as $idx => $t)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td class="text-start fw-bold text-dark">
                                                {{ $t->description }}
                                                @if($t->batch)
                                                    <div class="mt-1">
                                                        <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle" style="font-size: 0.72rem;">
                                                            <i class="fas fa-boxes me-1"></i> عهدة: {{ $t->batch->location?->name ?? 'مخزن الطابق' }}
                                                            @if($t->batch->manufacturer_lot_number) | Lot: {{ $t->batch->manufacturer_lot_number }} @endif
                                                            @if($t->batch->expiry_date) | نفاد: {{ $t->batch->expiry_date->format('Y-m') }} @endif
                                                        </span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td><span class="badge bg-light text-dark border">{{ $t->dosage ?: 'حسب الإرشاد' }}</span></td>
                                            <td>{{ $t->timing ?: '--' }}</td>
                                            <td>{{ $t->duration_value ? $t->duration_value . ' ' . $t->duration_unit : '--' }}</td>
                                            <td>
                                                @if($t->status === 'administered')
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle">تم الإعطاء</span>
                                                @else
                                                    <span class="badge bg-warning bg-opacity-25 text-warning-emphasis border border-warning-subtle">قيد الانتظار</span>
                                                @endif
                                            </td>
                                            <td class="text-muted" style="font-size: 0.75rem;">
                                                @if($t->administered_at)
                                                    {{ $t->administered_at->format('h:i A') }} ({{ $t->administeredBy?->name ?? 'تمريض' }})
                                                @else
                                                    --
                                                @endif
                                            </td>
                                            @if($canWriteCurrentTab)
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        @if($t->status !== 'administered')
                                                            <form action="{{ route('clinical-station.treatments.administer', $t) }}" method="POST">
                                                                @csrf
                                                                <input type="hidden" name="status" value="administered">
                                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold" title="تسجيل إعطاء الجرعة">
                                                                    <i class="fas fa-check me-1"></i> تم الإعطاء
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form action="{{ route('clinical-station.treatments.delete', $t) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الدواء؟');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-circle p-1" style="width: 28px; height: 28px;" title="حذف الدواء">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canWriteCurrentTab ? '8' : '7' }}" class="text-muted py-4">
                                                لا توجد علاجات أو أدوية موصوفة مسبقاً لهذه العملية.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Form to prescribe new medication (if write allowed) --}}
                    @if($canWriteCurrentTab)
                        <div class="card rounded-3 border bg-light bg-opacity-50 p-3">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-plus-circle text-success me-1"></i> إضافة دواء أو أمر علاجي جديد</h6>
                            <form action="{{ route('clinical-station.treatments.store', $surgery) }}" method="POST">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold">اسم الدواء والشكل الصيدلاني:</label>
                                        <input type="text" name="description" class="form-control form-control-sm rounded-2" required placeholder="مثال: Ceftriaxone 1g IV">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold">الجرعة:</label>
                                        <input type="text" name="dosage" class="form-control form-control-sm rounded-2" placeholder="مثال: 1 vial">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">التوقيت والتكرار:</label>
                                        <input type="text" name="timing" class="form-control form-control-sm rounded-2" placeholder="مثال: كل 12 ساعة">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">
                                            <i class="fas fa-plus me-1"></i> حفظ وإضافة للعلاجات
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Modal: Dispense from Floor Sub-Store --}}
                    @if($canWriteCurrentTab && isset($floorLocation) && $floorLocation)
                        <div class="modal fade" id="dispenseFloorStockModal" tabindex="-1" aria-labelledby="dispenseFloorStockModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content rounded-3 shadow border-0">
                                    <div class="modal-header bg-warning bg-opacity-10 border-bottom py-2.5 px-3">
                                        <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="dispenseFloorStockModalLabel">
                                            <i class="fas fa-box-open text-warning"></i> صرف علاج / مستلزم من عهدة الطابق
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form action="{{ route('clinical-station.treatments.dispense-floor-stock', $surgery) }}" method="POST">
                                        @csrf
                                        <div class="modal-body p-3">
                                            <div class="alert alert-warning p-2 mb-3 d-flex align-items-center gap-2 small border border-warning-subtle" role="alert">
                                                <i class="fas fa-info-circle text-warning fs-5"></i>
                                                <div>
                                                    يتم الصرف والخصم التلقائي من <strong>{{ $floorLocation->name }}</strong> لغرفة المريض ({{ $surgery->room?->room_number ?? '--' }} - {{ $surgery->room?->floor ?? '--' }}).
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label small fw-bold text-dark">اختر المادة / الوجبة من عهدة الطابق: <span class="text-danger">*</span></label>
                                                @if(isset($floorStockBatches) && $floorStockBatches->isNotEmpty())
                                                    <select name="batch_id" id="dispense_batch_id" class="form-select form-select-sm rounded-2" required onchange="updateDispenseMaxQty(this)">
                                                        <option value="" disabled selected>-- اختر الدواء أو المستلزم المتوفر في الطابق --</option>
                                                        @foreach($floorStockBatches as $b)
                                                            <option value="{{ $b->id }}" data-max="{{ $b->current_qty }}" data-unit="{{ $b->product?->unit ?? 'جرعة/قطعة' }}">
                                                                {{ $b->product?->name }} [المتوفر: {{ $b->current_qty }} {{ $b->product?->unit ?? 'قطعة' }}] (Lot: {{ $b->manufacturer_lot_number ?: $b->internal_barcode }} - نفاد: {{ $b->expiry_date?->format('Y-m') }})
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <div class="alert alert-secondary py-2 px-3 small mb-0">
                                                        <i class="fas fa-exclamation-triangle text-danger me-1"></i> لا توجد وجبات متوفرة برصيد موجب حالياً في مخزن هذا الطابق ({{ $floorLocation->name }}).
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label small fw-bold text-dark">الكمية المصروفة: <span class="text-danger">*</span></label>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="quantity" id="dispense_qty" class="form-control rounded-start-2" min="1" value="1" required>
                                                        <span class="input-group-text rounded-end-2 small" id="dispense_unit_label">قطعة</span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold text-dark">طريقة الإعطاء / التوقيت:</label>
                                                    <input type="text" name="timing" class="form-control form-control-sm rounded-2" placeholder="مثال: فوري IV / تضميد">
                                                </div>
                                            </div>

                                            <div class="mb-2">
                                                <label class="form-label small fw-semibold text-dark">ملاحظات التمريض:</label>
                                                <input type="text" name="admin_notes" class="form-control form-control-sm rounded-2" placeholder="أي تعليمات أو ملاحظات إضافية...">
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light py-2 px-3">
                                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">إلغاء</button>
                                            @if(isset($floorStockBatches) && $floorStockBatches->isNotEmpty())
                                                <button type="submit" class="btn btn-sm btn-warning rounded-pill px-4 fw-bold text-dark shadow-xs">
                                                    <i class="fas fa-check-circle me-1"></i> تأكيد الصرف والخصم من الطابق
                                                </button>
                                            @endif
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <script>
                            function updateDispenseMaxQty(select) {
                                const option = select.options[select.selectedIndex];
                                const max = option.getAttribute('data-max');
                                const unit = option.getAttribute('data-unit') || 'قطعة';
                                const qtyInput = document.getElementById('dispense_qty');
                                const unitLabel = document.getElementById('dispense_unit_label');
                                if (max && qtyInput) {
                                    qtyInput.max = max;
                                }
                                if (unitLabel) {
                                    unitLabel.textContent = unit;
                                }
                            }
                        </script>
                    @endif
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR FOLLOW-UP TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'follow_up')
                <div class="p-4">
                    {{-- Form to add follow up entry --}}
                    @if($canWriteCurrentTab)
                        <div class="card rounded-3 border p-3 mb-4 bg-light bg-opacity-50">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-plus-circle text-primary me-1"></i> إضافة قيد متابعة جديد (Daily Follow-Up Entry)</h6>
                            <form action="{{ route('clinical-station.follow-ups.store', $surgery) }}" method="POST">
                                @csrf
                                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">تاريخ المرور والمتابعة:</label>
                                        <input type="date" name="follow_up_date" class="form-control form-control-sm rounded-2" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold">الجولة / الدورية:</label>
                                        <select name="session" class="form-select form-select-sm rounded-2">
                                            <option value="morning">جولة صباحية (Morning)</option>
                                            <option value="evening">جولة مسائية (Evening)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold">ملاحظات الطبيب / كادر المتابعة:</label>
                                        <textarea name="notes" class="form-control rounded-2" rows="2" required placeholder="سجل تطور حالة المريض، تقييم الجرح، فحص البطن، خطة اليوم..."></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-4 fw-bold">
                                            <i class="fas fa-save me-1"></i> حفظ قيد المتابعة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Follow-Up Timeline --}}
                    <h6 class="fw-bold text-dark mb-3"><i class="fas fa-stream text-primary me-2"></i> سجل المتابعات الدورية السابقة</h6>
                    @forelse($followUps as $f)
                        <div class="timeline-card">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div>
                                    <strong class="text-dark">{{ $f->session === 'morning' ? 'جولة صباحية (Morning)' : ($f->session === 'evening' ? 'جولة مسائية (Evening)' : $f->session) }}</strong>
                                    <span class="text-muted ms-2 small">({{ $f->follow_up_date?->format('Y-m-d') }})</span>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border small">
                                    <i class="fas fa-user-edit me-1"></i> {{ $f->resident_name ?: 'كادر الرعاية' }}
                                </span>
                            </div>
                            <div class="text-dark mb-1" style="font-size: 0.92rem; line-height: 1.6;">
                                {{ $f->notes }}
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem;">
                                وُثق بتاريخ: {{ $f->created_at->format('Y-m-d h:i A') }}
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light text-center border p-4 rounded-3 text-muted">
                            <i class="fas fa-clipboard-list fa-2x mb-2 d-block text-secondary"></i>
                            لا توجد قيود متابعة مسجلة للمريض حتى الآن.
                        </div>
                    @endforelse
                </div>
            @endif

            {{-- Complete Station Phase Button --}}
            <div class="p-3 bg-white border-top d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small">المرحلة الحالية: <strong>{{ $currentPhase === 'pre_op' ? 'ما قبل العملية' : 'ما بعد العملية' }}</strong></span>
                </div>
                <div>
                    <form action="{{ route('clinical-station.complete', $surgery) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إنهاء هذه المرحلة السريرية؟');">
                        @csrf
                        <input type="hidden" name="phase" value="{{ $currentPhase }}">
                        @if($currentPhase === 'pre_op')
                            <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fas fa-check-double me-1"></i> إنهاء محطة ما قبل العملية ونقل للصالة
                            </button>
                        @else
                            <button type="submit" class="btn btn-purple rounded-pill px-4 fw-bold shadow-sm" style="background: #7c3aed; color: #fff;">
                                <i class="fas fa-flag-checkered me-1"></i> إنهاء متابعة ما بعد العملية
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

{{-- Scripts for Chart.js --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    @if($activeTab === 'vitals' && $readingsAsc->count() > 0)
        const labels = @json($readingsAsc->map(fn($r) => $r->created_at->format('h:i A')));
        
        // BP Data
        const systolicData = @json($readingsAsc->map(function($r) {
            $parts = explode('/', (string)$r->bp);
            return isset($parts[0]) && is_numeric($parts[0]) ? (int)$parts[0] : null;
        }));
        const diastolicData = @json($readingsAsc->map(function($r) {
            $parts = explode('/', (string)$r->bp);
            return isset($parts[1]) && is_numeric($parts[1]) ? (int)$parts[1] : null;
        }));

        const bpCtx = document.getElementById('bpChart');
        if (bpCtx) {
            new Chart(bpCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        { label: 'انقباضي (SYS)', data: systolicData, borderColor: '#ef4444', backgroundColor: 'rgba(239, 68, 68, 0.1)', tension: 0.3, fill: true },
                        { label: 'انبساطي (DIA)', data: diastolicData, borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.3, fill: true }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', rtl: true } }
                }
            });
        }

        // Pulse Data
        const pulseData = @json($readingsAsc->map(fn($r) => is_numeric($r->pr) ? (int)$r->pr : null));
        const prCtx = document.getElementById('prChart');
        if (prCtx) {
            new Chart(prCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'النبض (bpm)',
                        data: pulseData,
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.15)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top', rtl: true } }
                }
            });
        }
    @endif
});
</script>
@endsection
