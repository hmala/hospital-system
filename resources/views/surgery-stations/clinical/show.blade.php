@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-3" id="clinicalStationApp">
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
            border-radius: 18px;
            color: #ffffff;
            padding: 22px 26px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.12);
            border: 1px solid #334155;
        }

        .clin-tabs-wrapper {
            background: #ffffff;
            border-radius: 16px;
            padding: 8px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid #e2e8f0;
        }

        .clin-nav-tab {
            font-size: 0.86rem;
            font-weight: 600;
            padding: 10px 18px;
            border-radius: 12px;
            color: #475569;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            text-decoration: none;
            white-space: nowrap;
        }
        .clin-nav-tab:hover {
            background: #f1f5f9;
            color: #1e293b;
        }
        .clin-nav-tab.active {
            background: #2563eb;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }
        .clin-nav-tab.active .badge-perm {
            background: rgba(255, 255, 255, 0.25) !important;
            color: #ffffff !important;
        }

        .badge-perm {
            font-size: 0.7rem;
            padding: 3px 7px;
            border-radius: 6px;
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
            @can('manage surgery waiting list')
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

    {{-- Header Patient & Surgery Banner --}}
    <div class="clin-header-card mb-4">
        <div class="row align-items-center gy-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-box rounded-circle bg-white text-primary d-flex align-items-center justify-content-center shadow-sm" style="width: 58px; height: 58px; font-size: 1.6rem;">
                        <i class="fas fa-user-injured"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="fw-bold mb-0 text-white">{{ $surgery->patient?->name ?? 'غير محدد' }}</h3>
                            <span class="badge bg-primary bg-opacity-75 px-3 py-1.5 rounded-pill small">
                                ملف: {{ $surgery->patient?->patient_id ?? 'N/A' }}
                            </span>
                            <span class="badge bg-secondary bg-opacity-50 px-2.5 py-1 rounded-pill small">
                                {{ $surgery->patient?->age ? $surgery->patient->age . ' سنة' : '' }} | {{ $surgery->patient?->gender === 'male' ? 'ذكر' : 'أنثى' }}
                            </span>
                        </div>
                        <div class="text-white-50 mt-1 d-flex flex-wrap align-items-center gap-3 small">
                            <span><i class="fas fa-procedures text-info me-1"></i> العملية: <strong class="text-white">{{ $surgery->surgery_name }}</strong></span>
                            <span><i class="fas fa-user-md text-success me-1"></i> الجراح: <strong class="text-white">{{ $surgery->doctor?->name ?? 'غير محدد' }}</strong></span>
                            <span><i class="fas fa-door-open text-warning me-1"></i> الصالة: <strong class="text-white">{{ $surgery->operationTheater?->name ?? $surgery->room?->room_number ?? 'غير مخصصة' }}</strong></span>
                            <span><i class="fas fa-clock text-light me-1"></i> الموعد: <strong class="text-white">{{ $surgery->scheduled_time ? $surgery->scheduled_time->format('h:i A') : 'غير محدد' }}</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 text-lg-end">
                <div class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    {{-- Phase Selector --}}
                    <div class="btn-group bg-dark bg-opacity-50 p-1 rounded-pill border border-secondary" role="group">
                        <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'pre_op', 'tab' => $activeTab]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $currentPhase === 'pre_op' ? 'btn-primary text-white fw-bold shadow-sm' : 'text-white-50' }}">
                            <i class="fas fa-arrow-circle-right me-1"></i> ما قبل العملية (Pre-Op)
                        </a>
                        <a href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => 'post_op', 'tab' => $activeTab]) }}" 
                           class="btn btn-sm rounded-pill px-3 {{ $currentPhase === 'post_op' ? 'btn-primary text-white fw-bold shadow-sm' : 'text-white-50' }}">
                            <i class="fas fa-check-circle me-1"></i> ما بعد العملية (Post-Op)
                        </a>
                    </div>

                    {{-- Phase Status Badge --}}
                    <div>
                        @if($station && $station->status === 'completed')
                            <span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check-double me-1"></i> مكتملة</span>
                        @elseif($station && $station->status === 'in_progress')
                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-spinner fa-spin me-1"></i> قيد المتابعة</span>
                        @else
                            <span class="badge bg-secondary px-3 py-2 rounded-pill"><i class="fas fa-hourglass-start me-1"></i> بالانتظار</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs Navigation Bar --}}
    <div class="clin-tabs-wrapper mb-4">
        <ul class="nav nav-pills d-flex flex-nowrap overflow-auto gap-2 p-1" id="clinicalStationTabs" role="tablist">
            @foreach($visibleTabs as $tKey => $tMeta)
                <li class="nav-item" role="presentation">
                    <a class="clin-nav-tab {{ $activeTab === $tKey ? 'active' : '' }}" 
                       href="{{ route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $currentPhase, 'tab' => $tKey]) }}">
                        <i class="{{ $tMeta['icon'] }}"></i>
                        <span>{{ $tMeta['label'] }}</span>
                        @if($tMeta['can_write'])
                            <span class="badge-perm badge-perm-write" title="صلاحية إدخال وتعديل"><i class="fas fa-pen"></i></span>
                        @else
                            <span class="badge-perm badge-perm-read" title="صلاحية قراءة فقط"><i class="fas fa-eye"></i></span>
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
                                                            <div class="fw-bold text-dark">{{ $lt->test?->name ?? 'فحص مخبري' }}</div>
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
                                                            <div class="fw-bold text-dark">{{ $rt->radiologyService?->name ?? 'فحص إشعاعي' }}</div>
                                                            <div class="text-muted small">{{ $rt->findings ?: 'قيد الفحص والتقرير' }}</div>
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
                @if(!in_array($activeTab, ['treatments', 'follow_up']))
                    <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between">
                        <span class="text-muted small">
                            <i class="fas fa-info-circle text-info me-1"></i> المحطة السريرية تحفظ البيانات فورياً وتزامنها مع ملف المريض وقسم التمريض.
                        </span>
                        @if($canWriteCurrentTab)
                            <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                                <i class="fas fa-save me-1"></i> حفظ التحديثات السريرية
                            </button>
                        @endif
                    </div>
                @endif
            </form>

            {{-- =============================================================== --}}
            {{-- SPECIAL SUB-VIEW FOR TREATMENTS TAB --}}
            {{-- =============================================================== --}}
            @if($activeTab === 'treatments')
                <div class="p-4">
                    {{-- Prescribed Medications Table --}}
                    <div class="card rounded-3 border mb-4">
                        <div class="card-header bg-light py-2.5 px-3 d-flex align-items-center justify-content-between">
                            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-pills text-warning me-2"></i> جدول الأدوية والعلاجات الموصوفة</h6>
                            <span class="badge bg-primary rounded-pill">{{ $treatments->count() }} دواء</span>
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
                                                        <form action="{{ route('clinical-station.treatments.administer', $t) }}" method="POST">
                                                            @csrf
                                                            <input type="hidden" name="status" value="administered">
                                                            <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 py-1 fw-bold" title="تسجيل إعطاء الجرعة">
                                                                <i class="fas fa-check me-1"></i> تم الإعطاء
                                                            </button>
                                                        </form>
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
