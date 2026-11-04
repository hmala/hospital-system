@extends('layouts.app')

@section('content')
<div class="container-fluid px-2 px-md-3 py-2" id="clinicalStationApp" style="max-width: 1480px; margin: 0 auto;">
    {{-- CSS Styles --}}
    <style>
        :root {
            --clin-navy: #0f172a;
            --clin-teal: #1e3a5f;
            --clin-blue: #2563eb;
            --clin-card-bg: #ffffff;
        }

        .clin-header-card {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 12px;
            color: #ffffff;
            padding: 8px 16px;
            box-shadow: 0 4px 14px -2px rgba(15, 23, 42, 0.12);
            border: 1px solid #334155;
        }

        /* Modern Vertical Clinical Sidebar Navigation */
        .clin-sidebar-nav {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
            padding: 8px;
            position: sticky;
            top: 12px;
        }

        .clin-nav-group-title {
            font-size: 0.68rem;
            font-weight: 800;
            color: #94a3b8;
            letter-spacing: 0.3px;
            padding: 6px 8px 2px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .clin-v-tab {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.81rem;
            font-weight: 700;
            color: #334155;
            text-decoration: none;
            margin-bottom: 2px;
            transition: all 0.18s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid transparent;
        }
        .clin-v-tab:hover {
            background: #f8fafc;
            color: #0f172a;
            border-color: #e2e8f0;
            transform: translateX(-2px);
        }
        .clin-v-tab.active {
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            color: #ffffff !important;
            box-shadow: 0 3px 10px rgba(15, 23, 42, 0.2);
            border-color: #0f172a;
        }
        .clin-v-tab.active .tab-icon {
            color: #38bdf8 !important;
        }
        .clin-v-tab.active .badge-perm {
            background: rgba(255, 255, 255, 0.2) !important;
            color: #ffffff !important;
            border-color: transparent !important;
        }
        .clin-v-tab.tab-star.active .tab-icon {
            color: #facc15 !important;
        }

        .badge-perm {
            font-size: 0.65rem;
            padding: 2px 5px;
            border-radius: 5px;
            font-weight: 700;
        }
        .badge-perm-read {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .badge-perm-write {
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        @media (max-width: 991px) {
            .clin-header-card {
                padding: 10px 14px;
            }
            .clin-sidebar-nav {
                position: static;
                margin-bottom: 12px;
            }
        }

        .pane-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        .pane-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 16px;
        }

        /* Workspace Internal Scrollable Body to Prevent Full-Page Scroll */
        .clin-workspace-scroll {
            max-height: calc(100vh - 180px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 transparent;
        }
        .clin-workspace-scroll::-webkit-scrollbar {
            width: 5px;
        }
        .clin-workspace-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .table-survey th, .table-survey td {
            padding: 4px 6px !important;
            font-size: 0.8rem;
        }

        .vital-box {
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .vital-box:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }
        .vital-box .vital-val {
            font-size: 1.15rem;
            font-weight: 800;
            margin: 2px 0;
        }

        .vital-box-red { border-color: #fecaca; background: #fff5f5; }
        .vital-box-orange { border-color: #fed7aa; background: #fffaf0; }
        .vital-box-blue { border-color: #bfdbfe; background: #f0f9ff; }
        .vital-box-green { border-color: #bbf7d0; background: #f0fdf4; }
        .vital-box-purple { border-color: #ddd6fe; background: #faf5ff; }

        .timeline-card {
            border-inline-start: 3px solid #2563eb;
            background: #f8fafc;
            border-radius: 0 10px 10px 0;
            padding: 10px 14px;
            margin-bottom: 8px;
        }
    </style>

    {{-- Slim Breadcrumb & Quick Actions --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small" style="font-size: 0.8rem;">
                <li class="breadcrumb-item"><a href="{{ route('surgeries.index') }}" class="text-decoration-none">إدارة العمليات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('surgeries.waiting') }}" class="text-decoration-none">قائمة الانتظار اليومية</a></li>
                <li class="breadcrumb-item active" aria-current="page">المحطة السريرية الموحدة (#{{ $surgery->id }})</li>
            </ol>
        </nav>
        <div class="d-flex align-items-center gap-1.5">
            @can('manage surgery waiting list')
                <a href="{{ route('surgery-stations.tab-settings.index') }}" class="btn btn-outline-dark btn-xs rounded-pill px-2.5 py-1 small" style="font-size: 0.74rem;" title="تخصيص صلاحيات التبويبات">
                    <i class="fas fa-sliders-h me-1"></i> صلاحيات التبويبات
                </a>
            @endcan
            <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-secondary btn-xs rounded-pill px-2.5 py-1 small" style="font-size: 0.74rem;">
                <i class="fas fa-arrow-right me-1"></i> العودة للجدول
            </a>
        </div>
    </div>

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-xs py-1.5 px-3 mb-2 small d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-xs py-1.5 px-3 mb-2 small d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Sleek Compact Patient & Surgery Executive Strip --}}
    <div class="clin-header-card mb-2">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-box rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 34px; height: 34px; font-size: 1rem;">
                    <i class="fas fa-user-injured"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-bold text-white fs-6 mb-0">{{ $surgery->patient?->name ?? 'غير محدد' }}</span>
                        <span class="badge bg-primary bg-opacity-75 px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                            ملف: {{ $surgery->patient?->patient_id ?? 'N/A' }}
                        </span>
                        <span class="badge bg-secondary bg-opacity-50 px-2 py-0.5 rounded-pill" style="font-size: 0.72rem;">
                            {{ $surgery->patient?->age ? $surgery->patient->age . ' سنة' : '' }} | {{ $surgery->patient?->gender === 'male' ? 'ذكر' : 'أنثى' }}
                        </span>
                        <span class="text-white-50 small ms-1 d-none d-lg-inline">|</span>
                        <span class="text-white-50 small"><i class="fas fa-procedures text-info me-1"></i> <strong class="text-white">{{ $surgery->surgery_name }}</strong></span>
                        <span class="text-white-50 small"><i class="fas fa-user-md text-success me-1"></i> <strong class="text-white">{{ $surgery->doctor?->name ?? 'غير محدد' }}</strong></span>
                        <span class="text-white-50 small"><i class="fas fa-door-open text-warning me-1"></i> <strong class="text-white">{{ $surgery->operationTheater?->name ?? $surgery->room?->room_number ?? 'غير مخصصة' }}</strong></span>
                        <span class="text-white-50 small"><i class="fas fa-clock text-light me-1"></i> {{ $surgery->scheduled_time ? $surgery->scheduled_time->format('h:i A') : '' }}</span>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-1.5">
                {{-- Phase Selector --}}
                <div class="btn-group bg-dark bg-opacity-50 p-0.5 rounded-pill border border-secondary" role="group">
                    <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'pre_op', 'tab' => $activeTab]) }}" 
                       class="btn btn-xs rounded-pill px-2.5 py-1 {{ $currentPhase === 'pre_op' ? 'btn-primary text-white fw-bold shadow-xs' : 'text-white-50' }}" style="font-size: 0.76rem;">
                        <i class="fas fa-arrow-circle-right me-1"></i> ما قبل العملية
                    </a>
                    <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'post_op', 'tab' => $activeTab]) }}" 
                       class="btn btn-xs rounded-pill px-2.5 py-1 {{ $currentPhase === 'post_op' ? 'btn-primary text-white fw-bold shadow-xs' : 'text-white-50' }}" style="font-size: 0.76rem;">
                        <i class="fas fa-check-circle me-1"></i> ما بعد العملية
                    </a>
                </div>

                {{-- Phase Status Badge --}}
                <div>
                    @if($station && $station->status === 'completed')
                        <span class="badge bg-success px-2.5 py-1 rounded-pill small" style="font-size: 0.74rem;"><i class="fas fa-check-double me-1"></i> مكتملة</span>
                    @elseif($station && $station->status === 'in_progress')
                        <span class="badge bg-warning text-dark px-2.5 py-1 rounded-pill small" style="font-size: 0.74rem;"><i class="fas fa-spinner fa-spin me-1"></i> قيد المتابعة</span>
                    @else
                        <span class="badge bg-secondary px-2.5 py-1 rounded-pill small" style="font-size: 0.74rem;"><i class="fas fa-hourglass-start me-1"></i> بالانتظار</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Main 2-Column Clinical Layout (Balanced 1:3 Ratio) --}}
    <div class="row g-2">
        {{-- Right Column: Vertical Tabs Sidebar (25%) --}}
        <div class="col-md-4 col-lg-3">
            <div class="clin-sidebar-nav">
                <div class="d-flex align-items-center justify-content-between pb-1.5 mb-1.5 border-bottom">
                    <span class="small fw-bold text-dark" style="font-size: 0.78rem;">
                        <i class="fas fa-layer-group text-primary me-1"></i> أقسام طبلة المريض
                    </span>
                    <span class="badge bg-light text-secondary border small" style="font-size: 0.68rem;">
                        {{ count($visibleTabs) }} تبويب
                    </span>
                </div>

                @php
                    $tabGroups = [
                        'clinical' => [
                            'title' => 'التقييم والملاحظات',
                            'icon' => 'fas fa-stethoscope text-primary',
                            'keys' => ['history', 'vitals', 'plan', 'follow_up'],
                        ],
                        'nursing' => [
                            'title' => 'الرعاية والتمريض',
                            'icon' => 'fas fa-user-nurse text-success',
                            'keys' => ['treatments', 'fluids', 'nursing_care', 'inpatient_survey'],
                        ],
                        'diagnostics' => [
                            'title' => 'الفحوصات والتشخيص',
                            'icon' => 'fas fa-microscope text-info',
                            'keys' => ['tests'],
                        ],
                    ];
                @endphp

                @foreach($tabGroups as $gKey => $group)
                    @php
                        $groupTabs = array_intersect_key($visibleTabs, array_flip($group['keys']));
                    @endphp
                    @if(count($groupTabs) > 0)
                        <div class="clin-nav-group-title">
                            <i class="{{ $group['icon'] }}"></i>
                            <span>{{ $group['title'] }}</span>
                        </div>
                        @foreach($groupTabs as $tKey => $tMeta)
                            <a class="clin-v-tab {{ $activeTab === $tKey ? 'active' : '' }} {{ $tKey === 'inpatient_survey' ? 'tab-star' : '' }}" 
                               href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $currentPhase, 'tab' => $tKey]) }}">
                                <div class="d-flex align-items-center gap-2 text-truncate">
                                    <i class="{{ $tMeta['icon'] }} tab-icon {{ $activeTab === $tKey ? '' : 'text-secondary opacity-75' }}" style="width: 16px; text-align: center;"></i>
                                    <span class="text-truncate">{{ $tMeta['label'] }}</span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    @if($tKey === 'inpatient_survey')
                                        <i class="fas fa-star text-warning small" title="استبيان الرقود"></i>
                                    @endif
                                    @if($tMeta['can_write'])
                                        <span class="badge-perm badge-perm-write" title="صلاحية إدخال وتعديل"><i class="fas fa-pen"></i></span>
                                    @else
                                        <span class="badge-perm badge-perm-read" title="صلاحية قراءة فقط"><i class="fas fa-eye"></i></span>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    @endif
                @endforeach

                @php
                    $allGroupedKeys = [];
                    foreach ($tabGroups as $g) {
                        $allGroupedKeys = array_merge($allGroupedKeys, $g['keys']);
                    }
                    $otherTabs = array_diff_key($visibleTabs, array_flip($allGroupedKeys));
                @endphp
                @if(count($otherTabs) > 0)
                    <div class="clin-nav-group-title">
                        <i class="fas fa-folder-open text-secondary"></i>
                        <span>أقسام إضافية</span>
                    </div>
                    @foreach($otherTabs as $tKey => $tMeta)
                        <a class="clin-v-tab {{ $activeTab === $tKey ? 'active' : '' }}" 
                           href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $currentPhase, 'tab' => $tKey]) }}">
                            <div class="d-flex align-items-center gap-2 text-truncate">
                                <i class="{{ $tMeta['icon'] }} tab-icon {{ $activeTab === $tKey ? '' : 'text-secondary opacity-75' }}" style="width: 16px; text-align: center;"></i>
                                <span class="text-truncate">{{ $tMeta['label'] }}</span>
                            </div>
                            <div>
                                @if($tMeta['can_write'])
                                    <span class="badge-perm badge-perm-write"><i class="fas fa-pen"></i></span>
                                @else
                                    <span class="badge-perm badge-perm-read"><i class="fas fa-eye"></i></span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Left Column: Active Tab Content Workspace (75%) --}}
        <div class="col-md-8 col-lg-9">
            @php
                $currentTabMeta = $visibleTabs[$activeTab] ?? null;
                $canWriteCurrentTab = $currentTabMeta['can_write'] ?? false;

                $survey = null;
                $surveyUrl = null;
                if ($activeTab === 'inpatient_survey') {
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
                }
            @endphp

            @if(!$currentTabMeta)
                <div class="alert alert-warning text-center p-4 rounded-4 shadow-sm">
                    <i class="fas fa-lock fa-3x text-warning mb-3"></i>
                    <h5 class="fw-bold">لا تملك صلاحية الوصول لأي تبويب في هذه المحطة</h5>
                    <p class="text-muted mb-0">يرجى مراجعة إدارة المستشفى لمنحك الصلاحيات المناسبة لدورك الوظيفي.</p>
                </div>
            @else
                <div class="pane-card mb-3">
                    {{-- Tab Pane Header --}}
                    <div class="pane-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2">
                                <i class="{{ $currentTabMeta['icon'] }} fa-md"></i>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="mb-0 fw-bold text-dark">{{ $currentTabMeta['label'] }}</h6>
                                    @if($activeTab === 'inpatient_survey' && $survey)
                                        @if($survey->status === 'completed')
                                            <span class="badge bg-success text-white rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                                <i class="fas fa-check-circle me-1"></i> مكتمل ({{ $survey->overall_score }}%)
                                            </span>
                                        @else
                                            <span class="badge bg-warning text-dark rounded-pill px-2.5 py-0.5" style="font-size: 0.72rem;">
                                                <i class="fas fa-hourglass-half me-1"></i> بانتظار التقييم
                                            </span>
                                        @endif
                                    @endif
                                </div>
                                <p class="text-muted small mb-0" style="font-size: 0.74rem;">{{ $currentTabMeta['description'] }}</p>
                            </div>
                        </div>

                        {{-- Header Quick Action Buttons --}}
                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                            @if($activeTab === 'inpatient_survey' && $survey)
                                @if($survey->status === 'completed')
                                    @hasanyrole('admin|super-admin')
                                        <form method="POST" action="{{ route('inpatient-surveys.reset', $surgery) }}" class="d-inline" onsubmit="return confirm('هل أنت متأكد من إعادة فتح وتصفير الاستبيان؟ سيتم حذف الإجابات الحالية لإعادة التقييم.');">
                                            @csrf
                                            <button type="submit" class="btn btn-outline-danger btn-xs rounded-pill px-2.5 py-1 fw-bold small" style="font-size: 0.74rem;" title="إعادة فتح الاستبيان وتصفيره">
                                                <i class="fas fa-redo-alt me-1"></i> إعادة فتح
                                            </button>
                                        </form>
                                    @endhasanyrole
                                @endif
                                <a href="{{ route('inpatient-surveys.print-a4', $surgery) }}" target="_blank" class="btn btn-primary btn-xs rounded-pill px-2.5 py-1 fw-bold shadow-xs small" style="font-size: 0.74rem;">
                                    <i class="fas fa-file-invoice me-1"></i> طباعة A4
                                </a>
                                <a href="{{ route('inpatient-surveys.print-blank') }}" target="_blank" class="btn btn-outline-secondary btn-xs rounded-pill px-2.5 py-1 fw-bold shadow-xs small" style="font-size: 0.74rem;">
                                    <i class="fas fa-file-alt me-1"></i> نموذج فارغ
                                </a>
                                <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-light btn-xs border rounded-pill px-2.5 py-1 fw-bold shadow-xs small" style="font-size: 0.74rem;">
                                    <i class="fas fa-qrcode me-1"></i> كرت حراري
                                </a>
                            @else
                                @if($canWriteCurrentTab)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2.5 py-1 rounded-pill fw-bold small" style="font-size: 0.72rem;">
                                        <i class="fas fa-edit me-1"></i> إدخال وتعديل متاح
                                    </span>
                                @else
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2.5 py-1 rounded-pill fw-bold small" style="font-size: 0.72rem;">
                                        <i class="fas fa-eye me-1"></i> وضع القراءة فقط
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- Scrollable Tab Body Container (Viewport Fit) --}}
                    <div class="clin-workspace-scroll">
                        {{-- Form for tabs that submit directly to update --}}
                        <form action="{{ route('clinical-station.update', $surgery) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="phase" value="{{ $currentPhase }}">
                            <input type="hidden" name="active_tab" value="{{ $activeTab }}">

                            <div class="p-3">
                                {{-- =============================================================== --}}
                                {{-- TAB 1: HISTORY (السيرة المرضية) --}}
                                {{-- =============================================================== --}}
                                @if($activeTab === 'history')
                                    <div class="row g-2">
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-comment-medical text-primary me-1"></i> الشكوى الرئيسية (Chief Complaint):</label>
                                            <textarea name="chief_complaint" class="form-control form-control-sm rounded-2" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="الشكوى والأعراض التي يشكو منها المريض...">{{ $station?->chief_complaint }}</textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-notes-medical text-info me-1"></i> تفاصيل وتاريخ المرض الحالي (History of Present Illness):</label>
                                            <textarea name="history_present_illness" class="form-control form-control-sm rounded-2" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="التدرج الزمني وتفاصيل الحالة المرضية الحالية...">{{ $station?->history_present_illness }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-heartbeat text-danger me-1"></i> التاريخ المرضي السابق (Past Medical Hx):</label>
                                            <textarea name="past_medical_hx" class="form-control form-control-sm rounded-2" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="أمراض مزمنة، ضغط، سكري، ربو، قلب...">{{ $station?->past_medical_hx }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-cut text-warning me-1"></i> التاريخ الجراحي السابق (Past Surgical Hx):</label>
                                            <textarea name="past_surgical_hx" class="form-control form-control-sm rounded-2" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="عمليات سابقة، مضاعفات التخدير...">{{ $station?->past_surgical_hx }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-pills text-success me-1"></i> التاريخ الدوائي (Drug Hx):</label>
                                            <textarea name="drug_hx" class="form-control form-control-sm rounded-2" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="الأدوية التي يتناولها بانتظام...">{{ $station?->drug_hx }}</textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold text-danger mb-1"><i class="fas fa-exclamation-triangle me-1"></i> التحسس الدوائي والغذائي (Allergies):</label>
                                            <textarea name="drug_allergy" class="form-control form-control-sm rounded-2 border-danger-subtle bg-danger bg-opacity-10" rows="2" 
                                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                                      placeholder="حساسية البنسلين، أدوية محددة...">{{ $station?->drug_allergy }}</textarea>
                                        </div>
                                    </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 2: VITALS & PHYSICAL EXAM (العلامات الحيوية والفحص السريري) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'vitals')
                        {{-- Top Vital Sign Cards --}}
                        <div class="row g-2 row-cols-2 row-cols-md-3 row-cols-lg-5 mb-2.5">
                            <div class="col">
                                <div class="vital-box vital-box-red">
                                    <div class="small fw-bold text-danger" style="font-size: 0.76rem;"><i class="fas fa-tachometer-alt me-1"></i> ضغط الدم (BP)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="bp" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="120/80" value="{{ $station?->bp }}">
                                    @else
                                        <div class="vital-val text-danger">{{ $station?->bp ?: '--/--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.68rem;">mmHg</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-orange">
                                    <div class="small fw-bold text-warning-emphasis" style="font-size: 0.76rem;"><i class="fas fa-thermometer-half me-1"></i> الحرارة (Temp)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="temp" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="37.0" value="{{ $station?->temp }}">
                                    @else
                                        <div class="vital-val text-warning-emphasis">{{ $station?->temp ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.68rem;">°C</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-red">
                                    <div class="small fw-bold text-danger" style="font-size: 0.76rem;"><i class="fas fa-heart me-1"></i> النبض (PR)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="pr" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="75" value="{{ $station?->pr }}">
                                    @else
                                        <div class="vital-val text-danger">{{ $station?->pr ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.68rem;">bpm</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-blue">
                                    <div class="small fw-bold text-primary" style="font-size: 0.76rem;"><i class="fas fa-lungs me-1"></i> التنفس (RR)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="rr" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="16" value="{{ $station?->rr }}">
                                    @else
                                        <div class="vital-val text-primary">{{ $station?->rr ?: '--' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.68rem;">/min</div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="vital-box vital-box-green">
                                    <div class="small fw-bold text-success" style="font-size: 0.76rem;"><i class="fas fa-wind me-1"></i> الأكسجين (SPo2)</div>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="spo2" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="98" value="{{ $station?->spo2 }}">
                                    @else
                                        <div class="vital-val text-success">{{ $station?->spo2 ? $station->spo2 . '%' : '--%' }}</div>
                                    @endif
                                    <div class="text-muted" style="font-size: 0.68rem;">%</div>
                                </div>
                            </div>
                        </div>

                        {{-- Secondary Vitals Row --}}
                        <div class="row g-2 row-cols-2 row-cols-md-4 mb-2.5">
                            <div class="col">
                                <div class="card p-1.5 text-center rounded-2 border bg-light">
                                    <span class="text-muted fw-bold" style="font-size: 0.72rem;">مقياس الألم (Pain):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="pain_score" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="0 - 10" value="{{ $station?->pain_score }}">
                                    @else
                                        <strong class="text-dark small d-block">{{ $station?->pain_score ?: '0' }} / 10</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-1.5 text-center rounded-2 border bg-light">
                                    <span class="text-muted fw-bold" style="font-size: 0.72rem;">سكر الدم (RBS):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="rbs" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="110 mg/dL" value="{{ $station?->rbs }}">
                                    @else
                                        <strong class="text-dark small d-block">{{ $station?->rbs ?: '--' }} mg/dL</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-1.5 text-center rounded-2 border bg-light">
                                    <span class="text-muted fw-bold" style="font-size: 0.72rem;">مقياس الوعي (GCS):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="gcs" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="15/15" value="{{ $station?->gcs }}">
                                    @else
                                        <strong class="text-dark small d-block">{{ $station?->gcs ?: '15/15' }}</strong>
                                    @endif
                                </div>
                            </div>
                            <div class="col">
                                <div class="card p-1.5 text-center rounded-2 border bg-light">
                                    <span class="text-muted fw-bold" style="font-size: 0.72rem;">إعادة امتلاء الشعيرات (CRT):</span>
                                    @if($canWriteCurrentTab)
                                        <input type="text" name="crt" class="form-control form-control-sm text-center fw-bold mt-1" 
                                               placeholder="< 2 sec" value="{{ $station?->crt }}">
                                    @else
                                        <strong class="text-dark small d-block">{{ $station?->crt ?: '< 2 sec' }}</strong>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Clinical Examination --}}
                        <div class="mb-2.5">
                            <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-stethoscope text-primary me-1"></i> ملاحظات الفحص السريري (Clinical Examination):</label>
                            <textarea name="clinical_examination" class="form-control form-control-sm rounded-2" rows="2" 
                                      {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                      placeholder="ملاحظات الفحص العام وفحص الأجهزة الحيوية...">{{ $station?->clinical_examination }}</textarea>
                        </div>

                        {{-- Trend Charts Section --}}
                        <div class="card rounded-3 border p-2.5 mb-2.5">
                            <div class="d-flex align-items-center justify-content-between mb-2 border-bottom pb-1.5">
                                <h6 class="mb-0 fw-bold text-dark small"><i class="fas fa-chart-line text-primary me-2"></i> تطور العلامات الحيوية</h6>
                                <span class="badge bg-light text-secondary border small" style="font-size: 0.7rem;">{{ $readingsAsc->count() }} قراءة</span>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="border rounded-2 p-1.5 bg-light bg-opacity-25">
                                        <div class="text-center small fw-bold text-danger mb-1" style="font-size: 0.74rem;">منحنى ضغط الدم (BP Trend)</div>
                                        <div style="height: 150px;">
                                            <canvas id="bpChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded-2 p-1.5 bg-light bg-opacity-25">
                                        <div class="text-center small fw-bold text-danger mb-1" style="font-size: 0.74rem;">منحنى النبض (Pulse Rate Trend)</div>
                                        <div style="height: 150px;">
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
                        <div class="row g-2">
                            {{-- Lab Tests --}}
                            <div class="col-lg-6">
                                <div class="card rounded-3 border h-100">
                                    <div class="card-header bg-light py-1.5 px-3 d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-danger small"><i class="fas fa-flask me-1"></i> تحاليل المختبر (Lab Tests)</h6>
                                        <span class="badge bg-danger rounded-pill small" style="font-size: 0.7rem;">{{ $surgery->labTests?->count() ?? 0 }}</span>
                                    </div>
                                    <div class="p-2.5">
                                        @if($surgery->labTests && $surgery->labTests->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($surgery->labTests as $lt)
                                                    <div class="list-group-item px-0 py-1.5 d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <div class="fw-bold text-dark small">{{ $lt->labTest?->name ?? $lt->test?->name ?? 'فحص مخبري' }}</div>
                                                            <div class="text-muted small" style="font-size: 0.72rem;">النتيجة: <strong>{{ $lt->result ?: 'قيد الإجراء' }}</strong></div>
                                                        </div>
                                                        <div>
                                                            @if($lt->status === 'completed')
                                                                <span class="badge bg-success small">جاهز</span>
                                                            @else
                                                                <span class="badge bg-warning text-dark small">{{ $lt->status }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-3 small">لا توجد طلبات تحاليل مخبرية مرتبطة بهذه العملية.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Radiology Tests --}}
                            <div class="col-lg-6">
                                <div class="card rounded-3 border h-100">
                                    <div class="card-header bg-light py-1.5 px-3 d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-primary small"><i class="fas fa-x-ray me-1"></i> الفحوصات الإشعاعية (Radiology)</h6>
                                        <span class="badge bg-primary rounded-pill small" style="font-size: 0.7rem;">{{ $surgery->radiologyTests?->count() ?? 0 }}</span>
                                    </div>
                                    <div class="p-2.5">
                                        @if($surgery->radiologyTests && $surgery->radiologyTests->count() > 0)
                                            <div class="list-group list-group-flush">
                                                @foreach($surgery->radiologyTests as $rt)
                                                    <div class="list-group-item px-0 py-1.5 d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <div class="fw-bold text-dark small">{{ $rt->radiologyType?->name ?? $rt->radiologyService?->name ?? 'فحص إشعاعي' }}</div>
                                                            <div class="text-muted small" style="font-size: 0.72rem;">{{ $rt->result ?: ($rt->findings ?? 'قيد الفحص والتقرير') }}</div>
                                                        </div>
                                                        <div>
                                                            <span class="badge bg-info text-dark small">{{ $rt->status }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="text-center text-muted py-3 small">لا توجد صور أو فحوصات إشعاعية مسجلة للعملية.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 4: MEDICAL PLAN (الخطة الطبية وتوصيات الجراحة) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'plan')
                        <div class="row g-2">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-clipboard-check text-primary me-1"></i> الخطة الطبية وتوصيات ما قبل الجراحة (Pre-Op Plan):</label>
                                <textarea name="notes" class="form-control form-control-sm rounded-2" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="تعليمات الصيام، نقل الدم، تجهيز الصالة...">{{ $station?->notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-procedures text-success me-1"></i> توصيات وملاحظات ما بعد الجراحة (Post-Op Instructions):</label>
                                <textarea name="post_op_notes" class="form-control form-control-sm rounded-2" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="العناية بالجرح، المسكنات، وقت بدء السوائل...">{{ $station?->post_op_notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-stethoscope text-info me-1"></i> مراجعة الأجهزة الحيوية (Review of Systems):</label>
                                <textarea name="review_of_other_systems" class="form-control form-control-sm rounded-2" rows="2" 
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
                        <div class="row g-2">
                            {{-- Intake Column --}}
                            <div class="col-md-6">
                                <div class="card rounded-3 border h-100 p-2.5 bg-light bg-opacity-50">
                                    <h6 class="fw-bold text-primary border-bottom pb-1.5 mb-2 small">
                                        <i class="fas fa-arrow-alt-circle-down me-1"></i> السوائل الداخلة (Intake)
                                    </h6>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">مغذي وسوائل وريدية (IV Fluids) - ml:</label>
                                        <input type="number" name="intake_iv_fluids" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_iv_fluids }}" placeholder="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">سوائل عن طريق الفم (Oral Fluids) - ml:</label>
                                        <input type="number" name="intake_oral" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_oral }}" placeholder="0">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">نقل دم ومشتقاته (Blood Products) - ml:</label>
                                        <input type="number" name="intake_blood" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->intake_blood }}" placeholder="0">
                                    </div>
                                </div>
                            </div>

                            {{-- Output Column --}}
                            <div class="col-md-6">
                                <div class="card rounded-3 border h-100 p-2.5 bg-light bg-opacity-50">
                                    <h6 class="fw-bold text-danger border-bottom pb-1.5 mb-2 small">
                                        <i class="fas fa-arrow-alt-circle-up me-1"></i> السوائل الخارجة (Output)
                                    </h6>
                                    <div class="mb-1.5">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">إدرار البول (Urine) - ml:</label>
                                        <input type="number" name="output_urine" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_urine }}" placeholder="0">
                                    </div>
                                    <div class="mb-1.5">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">المفجر الجراحي (Drain) - ml:</label>
                                        <input type="number" name="output_drain" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_drain }}" placeholder="0">
                                    </div>
                                    <div class="mb-1.5">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">أنبوب المعدة (NG Tube) - ml:</label>
                                        <input type="number" name="output_gtube_ng" class="form-control form-control-sm rounded-2" 
                                               {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                               value="{{ $station?->output_gtube_ng }}" placeholder="0">
                                    </div>
                                    <div class="mb-1.5">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">القيء (Vomiting) - ml:</label>
                                        <input type="number" name="output_vomiting" class="form-control form-control-sm rounded-2" 
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
                                <div class="card rounded-3 border p-2 text-center {{ $net >= 0 ? 'bg-success bg-opacity-10 border-success-subtle' : 'bg-danger bg-opacity-10 border-danger-subtle' }}">
                                    <span class="small fw-bold text-secondary" style="font-size: 0.74rem;">صافي ميزان السوائل المسجل (Net Fluid Balance):</span>
                                    <h4 class="fw-bold mb-0 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $net !== null ? ($net > 0 ? '+' . $net : $net) . ' ml' : 'لم يُحتسب بعد' }}
                                    </h4>
                                </div>
                            </div>
                        </div>

                    {{-- =============================================================== --}}
                    {{-- TAB 8: NURSING CARE & NOTES (الرعاية التمريضية والتخريج) --}}
                    {{-- =============================================================== --}}
                    @elseif($activeTab === 'nursing_care')
                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-user-nurse text-primary me-1"></i> الممرض المسؤول عن الحالة:</label>
                                @if($canWriteCurrentTab)
                                    <select name="nurse_id" class="form-select form-select-sm rounded-2">
                                        <option value="">-- اختر الممرض المسؤول --</option>
                                        @foreach($nurses as $nurse)
                                            <option value="{{ $nurse->id }}" {{ ($nursingStation?->nurse_id == $nurse->id) ? 'selected' : '' }}>
                                                {{ $nurse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" class="form-control form-control-sm" readonly value="{{ $nursingStation?->nurse?->name ?? 'غير محدد' }}">
                                @endif
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-notes-medical text-primary me-1"></i> ملاحظات الرعاية التمريضية (Nursing Care Notes):</label>
                                <textarea name="nursing_notes" class="form-control form-control-sm rounded-2" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="ملاحظات الممرض حول حالة المريض، تغيير الضماد، الاستجابة للعلاج...">{{ $nursingStation?->nursing_notes }}</textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark mb-1"><i class="fas fa-walking text-success me-1"></i> تعليمات وملاحظات التخريج (Discharge Notes):</label>
                                <textarea name="discharge_notes" class="form-control form-control-sm rounded-2" rows="2" 
                                          {{ !$canWriteCurrentTab ? 'readonly' : '' }}
                                          placeholder="تعليمات الخروج للمنزل، مواعيد المراجعة، النصائح الغذائية...">{{ $nursingStation?->discharge_notes }}</textarea>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Action Footer for direct update forms --}}
                @if(!in_array($activeTab, ['treatments', 'follow_up', 'inpatient_survey']))
                    <div class="py-2 px-3 bg-light border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small" style="font-size: 0.74rem;">
                            <i class="fas fa-info-circle text-info me-1"></i> المحطة السريرية تحفظ البيانات فورياً وتزامنها مع ملف المريض وقسم التمريض.
                        </span>
                        @if($canWriteCurrentTab)
                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-xs" style="font-size: 0.78rem;">
                                <i class="fas fa-save me-1"></i> حفظ التحديثات السريرية
                            </button>
                        @endif
                    </div>
                @endif
            </form>

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR INPATIENT SURVEY TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'inpatient_survey')
                <div class="p-2.5">
                    <div class="row g-2">
                        {{-- 1. بطاقة الكيو آر لمسح المريض (QR Code Card) --}}
                        <div class="col-lg-4">
                            <div class="card border rounded-3 h-100 bg-light bg-opacity-50">
                                <div class="card-header bg-white py-1.5 px-2.5 border-bottom text-center">
                                    <h6 class="mb-0 fw-bold text-dark small" style="font-size: 0.8rem;">
                                        <i class="fas fa-qrcode text-primary me-1"></i> مسح هاتف المريض / المرافق
                                    </h6>
                                </div>
                                <div class="card-body p-2 text-center d-flex flex-column align-items-center justify-content-between">
                                    <div>
                                        <div class="p-1.5 bg-white rounded-3 border d-inline-block shadow-xs mb-1.5 position-relative">
                                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&margin=0&data={{ urlencode($surveyUrl) }}"
                                                 alt="QR Code" class="rounded-2" style="width: 110px; height: 110px; display: block;">
                                        </div>
                                        <div class="text-muted small mb-2" style="font-size: 0.72rem;">
                                            امسح الرمز بكاميرا الهاتف لتعبئة الاستبيان بخصوصية
                                        </div>
                                    </div>

                                    <div class="w-100">
                                        <div class="d-flex align-items-center justify-content-center gap-1 flex-wrap mb-1.5">
                                            <button type="button" class="btn btn-outline-primary btn-xs rounded-pill px-2 py-0.5 fw-semibold small" style="font-size: 0.72rem;" data-bs-toggle="modal" data-bs-target="#qrFullScreenModal">
                                                <i class="fas fa-expand me-1"></i> تكبير
                                            </button>
                                            <a href="{{ route('inpatient-surveys.print-qr', $surgery) }}" target="_blank" class="btn btn-outline-dark btn-xs rounded-pill px-2 py-0.5 fw-semibold small" style="font-size: 0.72rem;">
                                                <i class="fas fa-receipt me-1"></i> طباعة كرت
                                            </a>
                                        </div>

                                        <div class="p-1.5 bg-white rounded-2 text-muted small border text-start">
                                            <div class="d-flex justify-content-between mb-0.5" style="font-size: 0.68rem;">
                                                <span>رابط مباشر:</span>
                                                <a href="{{ $surveyUrl }}" target="_blank" class="text-primary fw-bold text-decoration-none">
                                                    فتح <i class="fas fa-external-link-alt" style="font-size: 0.62rem;"></i>
                                                </a>
                                            </div>
                                            <div class="text-truncate font-monospace text-muted" style="font-size: 0.66rem;">
                                                {{ $surveyUrl }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 2. إما عرض ملخص التقييم إذا اكتمل أو نموذج الإدخال اليدوي للممرض --}}
                        <div class="col-lg-8">
                            @if($survey->status === 'completed')
                                {{-- عرض ملخص التقييم المكتمل --}}
                                <div class="card border rounded-3 h-100 bg-white">
                                    <div class="card-header bg-white py-1.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-success small">
                                            <i class="fas fa-clipboard-check me-1"></i> نتائج وتقييم المريض المسجل
                                        </h6>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-0.5" style="font-size: 0.7rem;">
                                            {{ $survey->submitted_via === 'patient_qr' ? 'هاتف المريض (QR)' : 'إدخال يدوي للكادر' }}
                                        </span>
                                    </div>
                                    <div class="card-body p-2.5">
                                        <div class="row g-1.5 mb-2">
                                            @php
                                                $metricLabels = [
                                                    'cleanliness_room' => 'النظافة العامة للغرفة',
                                                    'cleanliness_bathroom' => 'نظافة الحمام والمرافق',
                                                    'temperature_ac' => 'التكييف والحرارة',
                                                    'lighting' => 'إضاءة الغرفة',
                                                    'quietness_privacy' => 'الهدوء والخصوصية',
                                                    'bed_comfort' => 'راحة السرير والفراش',
                                                    'control_panel' => 'لوحة التحكم والتمريض',
                                                    'storage_space' => 'مساحات التخزين',
                                                    'furniture_safety' => 'جودة وأمان الأثاث',
                                                    'entertainment' => 'الترفيه (تلفاز وإنترنت)',
                                                    'doctor_communication' => 'تواصل وزيارة الطبيب',
                                                ];
                                            @endphp
                                            @foreach($metricLabels as $field => $label)
                                                @php $val = $survey->{$field}; @endphp
                                                <div class="col-sm-6">
                                                    <div class="d-flex justify-content-between align-items-center p-1.5 rounded-2 bg-light border" style="font-size: 0.78rem;">
                                                        <span class="fw-semibold text-dark">{{ $label }}:</span>
                                                        @if($val == 5)
                                                            <span class="badge bg-success rounded-pill px-1.5 py-0.5">ممتاز (5/5)</span>
                                                        @elseif($val == 4)
                                                            <span class="badge bg-info text-white rounded-pill px-1.5 py-0.5">جيد جداً (4/5)</span>
                                                        @elseif($val == 3)
                                                            <span class="badge bg-warning text-dark rounded-pill px-1.5 py-0.5">جيد (3/5)</span>
                                                        @elseif($val == 2)
                                                            <span class="badge bg-orange text-white rounded-pill px-1.5 py-0.5" style="background:#f97316;">مقبول (2/5)</span>
                                                        @elseif($val == 1)
                                                            <span class="badge bg-danger rounded-pill px-1.5 py-0.5">ضعيف (1/5)</span>
                                                        @else
                                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill px-1.5 py-0.5">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($survey->recommend_score_nps)
                                            <div class="alert alert-primary bg-opacity-10 border border-primary border-opacity-25 rounded-2 py-1.5 px-2.5 mb-2 d-flex justify-content-between align-items-center">
                                                <span class="fw-bold small text-primary" style="font-size: 0.76rem;"><i class="fas fa-thumbs-up me-1"></i> احتمالية التوصية بالمستشفى (NPS):</span>
                                                <span class="badge bg-primary text-white rounded-pill px-2.5 py-0.5 small">{{ $survey->recommend_score_nps }} من 10</span>
                                            </div>
                                        @endif

                                        @if($survey->room_notes || $survey->patient_suggestions || $survey->doctor_notes)
                                            <div class="p-2 bg-light rounded-2 border small" style="font-size: 0.76rem;">
                                                @if($survey->room_notes)
                                                    <div class="mb-1">
                                                        <strong class="text-dark">ملاحظات الغرفة:</strong>
                                                        <span class="text-muted">{{ $survey->room_notes }}</span>
                                                    </div>
                                                @endif
                                                @if($survey->patient_suggestions)
                                                    <div class="mb-1">
                                                        <strong class="text-dark">مقترحات المريض:</strong>
                                                        <span class="text-muted">{{ $survey->patient_suggestions }}</span>
                                                    </div>
                                                @endif
                                                @if($survey->doctor_notes)
                                                    <div>
                                                        <strong class="text-dark">ملاحظات حول الطبيب:</strong>
                                                        <span class="text-muted">{{ $survey->doctor_notes }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                {{-- نموذج الإدخال اليدوي المباشر للممرض --}}
                                <div class="card border rounded-3 h-100 bg-white">
                                    <div class="card-header bg-white py-1.5 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <h6 class="mb-0 fw-bold text-dark small">
                                            <i class="fas fa-edit text-warning me-1"></i> إدخال يدوي للتقييم بواسطة الكادر
                                        </h6>
                                        <small class="text-muted" style="font-size: 0.7rem;">في حال طلب المريض التعبئة شفهياً</small>
                                    </div>
                                    <div class="card-body p-2.5">
                                        <form method="POST" action="{{ route('inpatient-surveys.manual.store', $surgery) }}">
                                            @csrf
                                            
                                            <div class="table-responsive mb-2">
                                                <table class="table table-sm table-hover align-middle mb-0 table-survey">
                                                    <thead class="table-light small">
                                                        <tr>
                                                            <th>البند</th>
                                                            <th class="text-center" style="width: 55px;">ممتاز (5)</th>
                                                            <th class="text-center" style="width: 55px;">جيد جداً (4)</th>
                                                            <th class="text-center" style="width: 55px;">جيد (3)</th>
                                                            <th class="text-center" style="width: 55px;">مقبول (2)</th>
                                                            <th class="text-center" style="width: 55px;">ضعيف (1)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $manualFields = [
                                                                'cleanliness_room' => 'النظافة العامة للغرفة',
                                                                'cleanliness_bathroom' => 'نظافة الحمام والمرافق',
                                                                'temperature_ac' => 'التكييف والحرارة',
                                                                'lighting' => 'إضاءة الغرفة',
                                                                'quietness_privacy' => 'الهدوء والخصوصية',
                                                                'bed_comfort' => 'راحة السرير والفراش',
                                                                'control_panel' => 'لوحة التحكم والتمريض',
                                                                'storage_space' => 'مساحات التخزين',
                                                                'furniture_safety' => 'جودة وأمان الأثاث',
                                                                'entertainment' => 'الترفيه (تلفاز وإنترنت)',
                                                                'doctor_communication' => 'تواصل وزيارة الطبيب',
                                                            ];
                                                        @endphp
                                                        @foreach($manualFields as $f => $l)
                                                            <tr>
                                                                <td class="small fw-semibold py-1">{{ $l }}</td>
                                                                @for($score = 5; $score >= 1; $score--)
                                                                    <td class="text-center py-1">
                                                                        <input type="radio" name="{{ $f }}" value="{{ $score }}" class="form-check-input" {{ $score === 5 ? 'checked' : '' }} style="width: 14px; height: 14px;">
                                                                    </td>
                                                                @endfor
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="row g-2 mb-2">
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark fw-bold mb-0.5" style="font-size: 0.74rem;">ملاحظات ومقترحات المريض:</label>
                                                    <textarea name="patient_suggestions" class="form-control form-control-sm rounded-2" rows="1" placeholder="اكتب ملاحظات المريض..."></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark fw-bold mb-0.5" style="font-size: 0.74rem;">ملاحظات إضافية حول زيارة الطبيب:</label>
                                                    <textarea name="doctor_notes" class="form-control form-control-sm rounded-2" rows="1" placeholder="ملاحظات زيارة وتواصل الطبيب..."></textarea>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end">
                                                <button type="submit" class="btn btn-warning btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs text-dark" style="font-size: 0.78rem;">
                                                    <i class="fas fa-save me-1"></i> حفظ وتوثيق التقييم
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Full-screen QR Modal --}}
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
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=0&data={{ urlencode($surveyUrl) }}"
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
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR TREATMENTS TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'treatments')
                <div class="p-3">
                    {{-- Prescribed Medications Table --}}
                    <div class="card rounded-3 border mb-2.5">
                        <div class="card-header bg-light py-1.5 px-3 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark small"><i class="fas fa-pills text-warning me-2"></i> جدول الأدوية والعلاجات الموصوفة</h6>
                            <span class="badge bg-primary rounded-pill small" style="font-size: 0.7rem;">{{ $treatments->count() }} دواء</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0 align-middle text-center small">
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
                                            <td class="text-start fw-bold text-dark">{{ $t->description }}</td>
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
                                            <td class="text-muted" style="font-size: 0.72rem;">
                                                @if($t->administered_at)
                                                    {{ $t->administered_at->format('h:i A') }} ({{ $t->administeredBy?->name ?? 'تمريض' }})
                                                @else
                                                    --
                                                @endif
                                            </td>
                                            @if($canWriteCurrentTab)
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-center gap-1">
                                                        <form action="{{ route('clinical-station.treatments.administer', $t) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="status" value="administered">
                                                            <button type="submit" class="btn btn-success btn-xs rounded-pill px-2.5 py-0.5 fw-bold small" style="font-size: 0.72rem;" title="تسجيل إعطاء الجرعة">
                                                                <i class="fas fa-check me-1"></i> إعطاء
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('clinical-station.treatments.delete', $t) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذا الدواء؟');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline-danger btn-xs rounded-circle p-0" style="width: 22px; height: 22px;" title="حذف الدواء">
                                                                <i class="fas fa-trash-alt" style="font-size: 0.65rem;"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            @endif
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="{{ $canWriteCurrentTab ? '8' : '7' }}" class="text-muted py-3 small">
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
                        <div class="card rounded-3 border bg-light bg-opacity-50 p-2.5">
                            <h6 class="fw-bold text-dark mb-2 small"><i class="fas fa-plus-circle text-success me-1"></i> إضافة دواء أو أمر علاجي جديد</h6>
                            <form action="{{ route('clinical-station.treatments.store', $surgery) }}" method="POST">
                                @csrf
                                <div class="row g-2 align-items-end">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">اسم الدواء والشكل:</label>
                                        <input type="text" name="description" class="form-control form-control-sm rounded-2" required placeholder="مثال: Ceftriaxone 1g IV">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">الجرعة:</label>
                                        <input type="text" name="dosage" class="form-control form-control-sm rounded-2" placeholder="مثال: 1 vial">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-0.5" style="font-size: 0.74rem;">التوقيت والتكرار:</label>
                                        <input type="text" name="timing" class="form-control form-control-sm rounded-2" placeholder="مثال: كل 12 ساعة">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold shadow-xs" style="font-size: 0.78rem;">
                                            <i class="fas fa-plus me-1"></i> حفظ وإضافة للعلاجات
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            @endif

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR FOLLOW-UP TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'follow_up')
                <div class="p-3">
                    {{-- Form to add follow up entry --}}
                    @if($canWriteCurrentTab)
                        <div class="card rounded-3 border p-2.5 mb-3 bg-light bg-opacity-50">
                            <h6 class="fw-bold text-dark mb-2 small"><i class="fas fa-plus-circle text-primary me-1"></i> إضافة قيد متابعة جديد (Daily Follow-Up)</h6>
                            <form action="{{ route('clinical-station.follow-ups.store', $surgery) }}" method="POST">
                                @csrf
                                <input type="hidden" name="phase" value="{{ $currentPhase }}">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1" style="font-size: 0.74rem;">تاريخ المرور:</label>
                                        <input type="date" name="follow_up_date" class="form-control form-control-sm rounded-2" value="{{ date('Y-m-d') }}" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-semibold mb-1" style="font-size: 0.74rem;">الجولة:</label>
                                        <select name="session" class="form-select form-select-sm rounded-2">
                                            <option value="morning">صباحية (Morning)</option>
                                            <option value="evening">مسائية (Evening)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-semibold mb-1" style="font-size: 0.74rem;">ملاحظات الطبيب / كادر المتابعة:</label>
                                        <textarea name="notes" class="form-control form-control-sm rounded-2" rows="2" required placeholder="سجل تطور حالة المريض، تقييم الجرح، فحص البطن، خطة اليوم..."></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs" style="font-size: 0.78rem;">
                                            <i class="fas fa-save me-1"></i> حفظ قيد المتابعة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    @endif

                    {{-- Follow-Up Timeline --}}
                    <h6 class="fw-bold text-dark mb-2 small"><i class="fas fa-stream text-primary me-2"></i> سجل المتابعات الدورية</h6>
                    @forelse($followUps as $f)
                        <div class="timeline-card">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div>
                                    <strong class="text-dark small">{{ $f->session === 'morning' ? 'جولة صباحية (Morning)' : ($f->session === 'evening' ? 'جولة مسائية (Evening)' : $f->session) }}</strong>
                                    <span class="text-muted ms-2 small" style="font-size: 0.72rem;">({{ $f->follow_up_date?->format('Y-m-d') }})</span>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border small" style="font-size: 0.7rem;">
                                    <i class="fas fa-user-edit me-1"></i> {{ $f->resident_name ?: 'كادر الرعاية' }}
                                </span>
                            </div>
                            <div class="text-dark mb-1 small" style="font-size: 0.85rem; line-height: 1.5;">
                                {{ $f->notes }}
                            </div>
                            <div class="text-muted" style="font-size: 0.7rem;">
                                وُثق: {{ $f->created_at->format('Y-m-d h:i A') }}
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-light text-center border p-3 rounded-3 text-muted small">
                            <i class="fas fa-clipboard-list fa-lg mb-1 d-block text-secondary"></i>
                            لا توجد قيود متابعة مسجلة للمريض حتى الآن.
                        </div>
                    @endforelse
                </div>
            @endif
                    </div>{{-- End clin-workspace-scroll --}}

            {{-- Complete Station Phase Button (Pinned at bottom of pane card) --}}
            <div class="py-2 px-3 bg-white border-top d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small" style="font-size: 0.78rem;">المرحلة الحالية: <strong>{{ $currentPhase === 'pre_op' ? 'ما قبل العملية' : 'ما بعد العملية' }}</strong></span>
                </div>
                <div>
                    <form action="{{ route('clinical-station.complete', $surgery) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من إنهاء هذه المرحلة السريرية؟');">
                        @csrf
                        <input type="hidden" name="phase" value="{{ $currentPhase }}">
                        @if($currentPhase === 'pre_op')
                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs" style="font-size: 0.78rem;">
                                <i class="fas fa-check-double me-1"></i> إنهاء محطة ما قبل العملية ونقل للصالة
                            </button>
                        @else
                            <button type="submit" class="btn btn-purple btn-sm rounded-pill px-3 py-1 fw-bold shadow-xs text-white" style="background: #7c3aed; font-size: 0.78rem;">
                                <i class="fas fa-flag-checkered me-1"></i> إنهاء متابعة ما بعد العملية
                            </button>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    @endif
        </div>{{-- End col-md-8 col-lg-9 --}}
    </div>{{-- End row g-3 --}}
</div>{{-- End clinicalStationApp --}}

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
