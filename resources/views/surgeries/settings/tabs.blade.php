@extends('layouts.app')

@section('content')
@php
    $activeMatrix = in_array($activeMatrix ?? 'clinical', ['clinical', 'detail'], true)
        ? ($activeMatrix ?? 'clinical')
        : 'clinical';
    $hasAnyAvailableRoles = !empty($availableClinicalRoles) || !empty($availableDetailRoles);
@endphp

<div class="container-fluid px-3 px-md-4 py-4">
    <style>
        .matrix-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .matrix-header {
            background: #ffffff;
            color: #0f172a;
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            position: relative;
        }
        .matrix-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #0284c7 0%, #06b6d4 50%, #2563eb 100%);
        }
        .matrix-header-icon {
            width: 46px;
            height: 46px;
            border-radius: 13px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            flex-shrink: 0;
        }
        .matrix-tabs-bar {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 24px;
        }
        .matrix-nav-pills {
            background: #e2e8f0;
            padding: 4px;
            border-radius: 12px;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .matrix-nav-pills .nav-link {
            border-radius: 9px;
            padding: 9px 18px;
            font-weight: 700;
            font-size: 0.9rem;
            color: #475569;
            border: 0;
            background: transparent;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.18s ease;
        }
        .matrix-nav-pills .nav-link:hover {
            color: #0f172a;
        }
        .matrix-nav-pills .nav-link.active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.1);
        }
        .matrix-nav-pills .nav-link.active .matrix-tab-count {
            background: #0d6efd !important;
            color: #ffffff !important;
        }
        .matrix-nav-pills .nav-link[data-matrix="detail"].active .matrix-tab-count {
            background: #0f766e !important;
            color: #ffffff !important;
        }
        .tab-col-header {
            font-size: 0.83rem;
            min-width: 140px;
            text-align: center;
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px 8px;
        }
        .role-row-header {
            min-width: 220px;
            background: #ffffff;
            font-weight: 700;
            border-inline-end: 2px solid #e2e8f0;
            vertical-align: middle;
            padding: 12px 16px;
        }
        .perm-select {
            font-size: 0.82rem;
            font-weight: 600;
            border-radius: 8px;
            padding: 6px 10px;
            transition: all 0.2s ease;
            cursor: pointer;
        }
        .perm-select[data-level="hidden"] {
            background-color: #f1f5f9;
            color: #64748b;
            border: 1px solid #cbd5e1;
        }
        .perm-select[data-level="read"] {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }
        .perm-select[data-level="write"] {
            background-color: #f0fdf4;
            color: #15803d;
            border: 1px solid #86efac;
        }
        .level-pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }
        .choice-card-label {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 14px;
            cursor: pointer;
            transition: all 0.16s ease;
            background: #ffffff;
        }
        .choice-card-input:checked + .choice-card-label {
            border-color: #0d6efd;
            background: #eff6ff;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.12);
        }
        .choice-card-input:disabled + .choice-card-label {
            opacity: 0.55;
            cursor: not-allowed;
            background: #f8fafc;
        }
    </style>

    {{-- Breadcrumb & Back --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('surgeries.index') }}" class="text-decoration-none">إدارة العمليات</a></li>
                <li class="breadcrumb-item"><a href="{{ route('surgeries.waiting') }}" class="text-decoration-none">قائمة الانتظار</a></li>
                <li class="breadcrumb-item active" aria-current="page">مصفوفة صلاحيات التبويبات</li>
            </ol>
        </nav>
        <a href="{{ route('surgeries.waiting') }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="fas fa-arrow-right me-1"></i> العودة لقائمة العمليات
        </a>
    </div>

    {{-- Centered Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center justify-content-center text-center px-5 position-relative mb-3" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div class="fw-semibold">{{ session('success') }}</div>
            <button type="button" class="btn-close position-absolute top-50 translate-middle-y" style="left: 1rem; right: auto;" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center justify-content-center text-center px-5 position-relative mb-3" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
            <div class="fw-semibold">{{ session('error') }}</div>
            <button type="button" class="btn-close position-absolute top-50 translate-middle-y" style="left: 1rem; right: auto;" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 text-center px-5 position-relative mb-3" role="alert">
            <div class="fw-bold mb-1">تعذر تنفيذ الطلب:</div>
            @foreach($errors->all() as $err)
                <div class="small">{{ $err }}</div>
            @endforeach
            <button type="button" class="btn-close position-absolute top-50 translate-middle-y" style="left: 1rem; right: auto;" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Unified Matrix Card --}}
    <div class="matrix-card">
        {{-- Card Header --}}
        <div class="matrix-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="matrix-header-icon">
                    <i class="fas fa-sliders-h"></i>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold" style="color: #0f172a;">
                        مصفوفة صلاحيات تبويبات المحطة السريرية الموحدة
                    </h4>
                    <p class="mb-0 text-muted small">
                        إدارة مركزية لظهور وصلاحيات التبويبات لكل دور وظيفي أو محطة إضافية في المحطة السريرية وملف العملية.
                    </p>
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <button type="button"
                        id="openAddRoleModalBtn"
                        class="btn btn-primary btn-sm rounded-pill px-3 fw-bold shadow-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addMatrixRoleModal"
                        {{ !$hasAnyAvailableRoles ? 'disabled' : '' }}>
                    <i class="fas fa-user-plus me-1"></i> إضافة دور / محطة
                </button>
                <form action="{{ route('surgery-stations.tab-settings.reset') }}" method="POST" class="mb-0" onsubmit="return confirm('هل أنت متأكد من استعادة الضبط الافتراضي للمصفوفة الطبية؟');">
                    @csrf
                    <input type="hidden" name="active_matrix" id="resetActiveMatrixInput" value="{{ $activeMatrix }}">
                    <button type="submit" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-semibold">
                        <i class="fas fa-undo me-1"></i> استعادة الضبط الطبي الافتراضي
                    </button>
                </form>
            </div>
        </div>

        {{-- Legend Bar --}}
        <div class="bg-light px-4 py-2 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-bold text-secondary me-1">مستويات الصلاحية:</span>
                <span class="level-pill-badge bg-secondary bg-opacity-10 text-secondary border">
                    🚫 <strong>مخفي (Hidden):</strong> لا يظهر التبويب
                </span>
                <span class="level-pill-badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                    👁️ <strong>قراءة فقط (Read):</strong> اطلاع دون تعديل
                </span>
                <span class="level-pill-badge bg-success bg-opacity-10 text-success border border-success-subtle">
                    ✍️ <strong>قراءة وإدخال (Write):</strong> إدخال وحفظ كامل
                </span>
            </div>
            <span class="badge bg-dark-subtle text-dark border px-3 py-2 rounded-pill small">
                <i class="fas fa-shield-alt text-primary me-1"></i> مدير النظام يملك صلاحية كاملة تلقائياً
            </span>
        </div>

        {{-- Segmented Matrix Tabs Bar --}}
        <div class="matrix-tabs-bar d-flex flex-wrap align-items-center justify-content-between gap-3">
            <ul class="nav nav-pills matrix-nav-pills" id="matrixTabsList" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button"
                            id="btnTabClinical"
                            class="nav-link {{ $activeMatrix === 'clinical' ? 'active' : '' }}"
                            data-matrix="clinical"
                            role="tab"
                            aria-controls="paneClinicalMatrix"
                            aria-selected="{{ $activeMatrix === 'clinical' ? 'true' : 'false' }}"
                            onclick="activateMatrixTab('clinical', true)">
                        <i class="fas fa-stethoscope text-primary"></i>
                        <span>المحطة السريرية الموحدة</span>
                        <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark matrix-tab-count">{{ count($clinicalRoles) }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button"
                            id="btnTabDetail"
                            class="nav-link {{ $activeMatrix === 'detail' ? 'active' : '' }}"
                            data-matrix="detail"
                            role="tab"
                            aria-controls="paneDetailMatrix"
                            aria-selected="{{ $activeMatrix === 'detail' ? 'true' : 'false' }}"
                            onclick="activateMatrixTab('detail', true)">
                        <i class="fas fa-procedures text-success"></i>
                        <span>ملف العملية وصالة العمليات</span>
                        <code class="small bg-light px-1 rounded text-muted">/surgeries/{id}</code>
                        <span class="badge rounded-pill bg-secondary bg-opacity-25 text-dark matrix-tab-count">{{ count($detailRoles) }}</span>
                    </button>
                </li>
            </ul>

            <div class="small text-muted d-flex align-items-center gap-2">
                <i class="fas fa-layer-group text-secondary"></i>
                <span>يتم حفظ كل تبويب بشكل مستقل دون الحاجة للتمرير الطويل.</span>
            </div>
        </div>

        {{-- Tab Panes --}}
        <div class="tab-content" id="matrixTabsContent">
            {{-- Pane 1: Clinical Station Matrix --}}
            <div id="paneClinicalMatrix"
                 class="tab-pane fade {{ $activeMatrix === 'clinical' ? 'show active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="btnTabClinical">
                <div class="px-4 py-2 bg-white border-bottom small text-secondary">
                    <i class="fas fa-info-circle text-primary me-1"></i>
                    تتحكم هذه المصفوفة بتبويبات شاشة التمريض والمقيم والطبيب في المحطة السريرية الموحدة (<code>/clinical-station/{id}</code>).
                </div>

                <form action="{{ route('surgery-stations.tab-settings.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="active_matrix" value="clinical">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="tab-col-header text-start px-3" style="min-width: 240px;">
                                        <i class="fas fa-user-tag text-primary me-1"></i> الدور السريري / الوظيفي
                                    </th>
                                    @foreach($tabs as $tabKey => $tabMeta)
                                        <th class="tab-col-header" title="{{ $tabMeta['description'] }}">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <i class="{{ $tabMeta['icon'] }} text-{{ $tabMeta['color'] }} fa-lg"></i>
                                                <span class="fw-bold text-dark">{{ $tabMeta['label'] }}</span>
                                                <span class="text-muted" style="font-size: 0.72rem; font-weight: normal;">({{ $tabKey }})</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($clinicalRoles as $roleKey)
                                    @php
                                        $roleLabel = $roleLabels[$roleKey] ?? $roleKey;
                                        $isRoleAdmin = ($roleKey === 'admin');
                                        $isCustomRole = !in_array($roleKey, $coreClinicalRoles, true);
                                    @endphp
                                    <tr>
                                        <td class="role-row-header">
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle rounded-circle bg-light border p-2 text-center" style="width: 36px; height: 36px;">
                                                        @if($roleKey === 'resident')
                                                            <i class="fas fa-user-graduate text-primary"></i>
                                                        @elseif($roleKey === 'nurse')
                                                            <i class="fas fa-user-nurse text-danger"></i>
                                                        @elseif($roleKey === 'doctor')
                                                            <i class="fas fa-user-md text-success"></i>
                                                        @elseif($roleKey === 'anesthesia')
                                                            <i class="fas fa-syringe text-success"></i>
                                                        @elseif($roleKey === 'admin')
                                                            <i class="fas fa-user-shield text-dark"></i>
                                                        @else
                                                            <i class="fas fa-hospital-user text-info"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            {{ $roleLabel }}
                                                            @if($isCustomRole)
                                                                <span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size: 0.68rem;">مضاف</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-muted small" style="font-size: 0.75rem;">الكود: <code>{{ $roleKey }}</code></div>
                                                    </div>
                                                </div>
                                                @if($isCustomRole)
                                                    <button type="submit"
                                                            form="remove-clinical-{{ md5($roleKey) }}"
                                                            class="btn btn-sm btn-outline-danger rounded-circle"
                                                            title="إزالة هذا الدور من المصفوفة"
                                                            onclick="return confirm('إزالة صلاحيات الدور {{ $roleKey }} من مصفوفة المحطة السريرية؟');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>

                                        @foreach($tabs as $tabKey => $tabMeta)
                                            @php
                                                $currentLevel = $currentPermissions[$roleKey][$tabKey]
                                                    ?? $defaultMatrix[$roleKey][$tabKey]
                                                    ?? 'read';
                                            @endphp
                                            <td class="text-center p-2">
                                                @if($isRoleAdmin)
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle px-3 py-2 rounded-3 fw-bold small">
                                                        <i class="fas fa-check-double me-1"></i> إدخال كامل (ثابت)
                                                    </span>
                                                    <input type="hidden" name="permissions[{{ $roleKey }}][{{ $tabKey }}]" value="write">
                                                @else
                                                    <select name="permissions[{{ $roleKey }}][{{ $tabKey }}]"
                                                            class="form-select form-select-sm perm-select mx-auto"
                                                            data-level="{{ $currentLevel }}"
                                                            onchange="updateSelectColor(this)"
                                                            style="max-width: 140px;">
                                                        <option value="hidden" {{ $currentLevel === 'hidden' ? 'selected' : '' }}>🚫 مخفي</option>
                                                        <option value="read" {{ $currentLevel === 'read' ? 'selected' : '' }}>👁️ قراءة فقط</option>
                                                        <option value="write" {{ $currentLevel === 'write' ? 'selected' : '' }}>✍️ قراءة وإدخال</option>
                                                    </select>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="text-muted small">
                            <i class="fas fa-info-circle text-info me-1"></i> يتم تطبيق التعديلات فور الحفظ على كافة شاشات المحطة السريرية.
                        </span>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ صلاحيات المحطة السريرية
                        </button>
                    </div>
                </form>
            </div>

            {{-- Pane 2: Surgery Detail & Theater Matrix --}}
            <div id="paneDetailMatrix"
                 class="tab-pane fade {{ $activeMatrix === 'detail' ? 'show active' : '' }}"
                 role="tabpanel"
                 aria-labelledby="btnTabDetail">
                <div class="px-4 py-2 bg-white border-bottom small text-secondary">
                    <i class="fas fa-shield-alt text-success me-1"></i>
                    <strong>مصفوفة صلاحيات ملف العملية وصالة العمليات:</strong>
                    أي دور أو محطة تضاف هنا وتُمنح تبويباً واحداً غير مخفي (قراءة أو إدخال) يُسمح لها بالدخول إلى <code>/surgeries/{id}</code> تلقائياً.
                </div>

                <form action="{{ route('surgery-stations.tab-settings.update') }}" method="POST">
                    @csrf
                    <input type="hidden" name="active_matrix" value="detail">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th class="tab-col-header text-start px-3" style="min-width: 240px;">
                                        <i class="fas fa-user-md text-success me-1"></i> الدور / الكادر في العملية
                                    </th>
                                    @foreach($detailTabs as $tabKey => $tabMeta)
                                        <th class="tab-col-header" title="{{ $tabMeta['description'] }}">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <i class="{{ $tabMeta['icon'] }} text-{{ $tabMeta['color'] }} fa-lg"></i>
                                                <span class="fw-bold text-dark">{{ $tabMeta['label'] }}</span>
                                                <span class="text-muted" style="font-size: 0.72rem; font-weight: normal;">({{ $tabKey }})</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($detailRoles as $roleKey)
                                    @php
                                        $roleLabel = $detailRoleLabels[$roleKey] ?? $roleKey;
                                        $isRoleAdmin = ($roleKey === 'admin');
                                        $isCustomRole = !in_array($roleKey, $coreDetailRoles, true);
                                    @endphp
                                    <tr>
                                        <td class="role-row-header">
                                            <div class="d-flex align-items-center justify-content-between gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="avatar-circle rounded-circle bg-light border p-2 text-center" style="width: 36px; height: 36px;">
                                                        @if($roleKey === 'anesthesia')
                                                            <i class="fas fa-syringe text-success"></i>
                                                        @elseif($roleKey === 'doctor')
                                                            <i class="fas fa-user-md text-primary"></i>
                                                        @elseif($roleKey === 'surgery_staff')
                                                            <i class="fas fa-user-cog text-warning"></i>
                                                        @elseif($roleKey === 'admin')
                                                            <i class="fas fa-user-shield text-dark"></i>
                                                        @else
                                                            <i class="fas fa-hospital-user text-info"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            {{ $roleLabel }}
                                                            @if($isCustomRole)
                                                                <span class="badge bg-success-subtle text-success border border-success-subtle ms-1" style="font-size: 0.68rem;">مضاف</span>
                                                            @endif
                                                        </div>
                                                        <div class="text-muted small" style="font-size: 0.75rem;">الكود: <code>{{ $roleKey }}</code></div>
                                                    </div>
                                                </div>
                                                @if($isCustomRole)
                                                    <button type="submit"
                                                            form="remove-detail-{{ md5($roleKey) }}"
                                                            class="btn btn-sm btn-outline-danger rounded-circle"
                                                            title="إزالة هذا الدور من مصفوفة ملف العملية"
                                                            onclick="return confirm('إزالة صلاحيات الدور {{ $roleKey }} من مصفوفة ملف العملية؟');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>

                                        @foreach($detailTabs as $tabKey => $tabMeta)
                                            @php
                                                $currentLevel = $currentPermissions[$roleKey][$tabKey]
                                                    ?? $detailDefaultMatrix[$roleKey][$tabKey]
                                                    ?? 'read';
                                            @endphp
                                            <td class="text-center p-2">
                                                @if($isRoleAdmin)
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success-subtle px-3 py-2 rounded-3 fw-bold small">
                                                        <i class="fas fa-check-double me-1"></i> إدخال كامل (ثابت)
                                                    </span>
                                                    <input type="hidden" name="permissions[{{ $roleKey }}][{{ $tabKey }}]" value="write">
                                                @else
                                                    <select name="permissions[{{ $roleKey }}][{{ $tabKey }}]"
                                                            class="form-select form-select-sm perm-select mx-auto"
                                                            data-level="{{ $currentLevel }}"
                                                            onchange="updateSelectColor(this)"
                                                            style="max-width: 145px;">
                                                        <option value="hidden" {{ $currentLevel === 'hidden' ? 'selected' : '' }}>🚫 مخفي</option>
                                                        <option value="read" {{ $currentLevel === 'read' ? 'selected' : '' }}>👁️ قراءة فقط</option>
                                                        <option value="write" {{ $currentLevel === 'write' ? 'selected' : '' }}>✍️ قراءة وإدخال</option>
                                                    </select>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 bg-white border-top d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span class="text-muted small">
                            <i class="fas fa-shield-alt text-success me-1"></i> يدعم حساب التخدير تلقائياً سواء أُنشئ بدور <code>التخدير</code> أو كطبيب تخدير <code>anesthesiologist</code>، ويدعم <code>doctor</code> دور <code>الجراح</code> تلقائياً.
                        </span>
                        <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-save me-1"></i> حفظ صلاحيات ملف العملية
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- External Delete Forms (kept outside matrix <form> elements to avoid nested forms) --}}
        @foreach($clinicalRoles as $roleKey)
            @if(!in_array($roleKey, $coreClinicalRoles, true))
                <form id="remove-clinical-{{ md5($roleKey) }}" action="{{ route('surgery-stations.tab-settings.roles.remove') }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="matrix_type" value="clinical">
                    <input type="hidden" name="role_name" value="{{ $roleKey }}">
                </form>
            @endif
        @endforeach

        @foreach($detailRoles as $roleKey)
            @if(!in_array($roleKey, $coreDetailRoles, true))
                <form id="remove-detail-{{ md5($roleKey) }}" action="{{ route('surgery-stations.tab-settings.roles.remove') }}" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="matrix_type" value="detail">
                    <input type="hidden" name="role_name" value="{{ $roleKey }}">
                </form>
            @endif
        @endforeach
    </div>
</div>

{{-- Organized Modal rendered outside .main-content via @stack('modals') to avoid backdrop-filter fixed clipping --}}
@push('modals')
<div class="modal fade" id="addMatrixRoleModal" tabindex="-1" aria-labelledby="addMatrixRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 540px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-white border-bottom py-3 px-4">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="addMatrixRoleModalLabel" style="color: #0f172a;">
                    <span class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="fas fa-user-plus"></i>
                    </span>
                    <span>إضافة دور أو محطة للصلاحيات</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form action="{{ route('surgery-stations.tab-settings.roles.add') }}" method="POST" id="addMatrixRoleForm">
                @csrf
                <div class="modal-body p-4">
                    {{-- Step 1: Select Matrix --}}
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary mb-2">1. اختر الشاشة / المصفوفة المستهدفة:</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio"
                                       class="btn-check choice-card-input"
                                       name="matrix_type"
                                       id="modalMatrixClinical"
                                       value="clinical"
                                       {{ $activeMatrix === 'clinical' ? 'checked' : '' }}
                                       onchange="syncModalRolesForMatrix('clinical')">
                                <label class="choice-card-label w-100 h-100" for="modalMatrixClinical">
                                    <i class="fas fa-stethoscope text-primary fa-lg"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">المحطة السريرية</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">متاح: {{ count($availableClinicalRoles) }} دور</div>
                                    </div>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio"
                                       class="btn-check choice-card-input"
                                       name="matrix_type"
                                       id="modalMatrixDetail"
                                       value="detail"
                                       {{ $activeMatrix === 'detail' ? 'checked' : '' }}
                                       onchange="syncModalRolesForMatrix('detail')">
                                <label class="choice-card-label w-100 h-100" for="modalMatrixDetail">
                                    <i class="fas fa-procedures text-success fa-lg"></i>
                                    <div>
                                        <div class="fw-bold small text-dark">ملف العملية</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">متاح: {{ count($availableDetailRoles) }} دور</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Step 2: Select Role / Station --}}
                    <div class="mb-3">
                        <label for="modalRoleSelect" class="form-label fw-bold small text-secondary mb-2">2. اختر الدور الوظيفي أو المحطة:</label>
                        <select name="role_name" id="modalRoleSelect" class="form-select" required>
                            <option value="">-- اختر دوراً أو محطة --</option>
                        </select>
                        <div id="modalNoRolesHint" class="form-text text-danger d-none mt-1">
                            جميع الأدوار المتاحة مضافة مسبقاً في هذه المصفوفة.
                        </div>
                    </div>

                    {{-- Step 3: Default Starting Level --}}
                    <div>
                        <label class="form-label fw-bold small text-secondary mb-2">3. الصلاحية المبدئية لجميع التبويبات:</label>
                        <div class="row g-2">
                            <div class="col-4">
                                <input type="radio" class="btn-check choice-card-input" name="default_level" id="lvlRead" value="read" checked>
                                <label class="choice-card-label justify-content-center text-center py-2 px-1" for="lvlRead">
                                    <span class="small fw-bold text-primary">👁️ قراءة فقط</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check choice-card-input" name="default_level" id="lvlHidden" value="hidden">
                                <label class="choice-card-label justify-content-center text-center py-2 px-1" for="lvlHidden">
                                    <span class="small fw-bold text-secondary">🚫 مخفي</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <input type="radio" class="btn-check choice-card-input" name="default_level" id="lvlWrite" value="write">
                                <label class="choice-card-label justify-content-center text-center py-2 px-1" for="lvlWrite">
                                    <span class="small fw-bold text-success">✍️ إدخال كامل</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 px-4 py-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" id="modalSubmitAddRoleBtn" class="btn btn-primary rounded-pill px-4 fw-bold">
                        <i class="fas fa-plus-circle me-1"></i> إضافة وتفعيل
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
<script>
const availableRolesByMatrix = {
    clinical: @json($availableClinicalRoles),
    detail: @json($availableDetailRoles),
};

let currentActiveMatrix = @json($activeMatrix);

function updateSelectColor(select) {
    select.setAttribute('data-level', select.value);
}

function syncModalRolesForMatrix(matrixType) {
    const select = document.getElementById('modalRoleSelect');
    const hint = document.getElementById('modalNoRolesHint');
    const submitBtn = document.getElementById('modalSubmitAddRoleBtn');
    const rolesMap = availableRolesByMatrix[matrixType] || {};
    const entries = Object.entries(rolesMap);

    select.innerHTML = '';
    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- اختر دوراً أو محطة --';
    select.appendChild(placeholder);

    entries.forEach(([roleKey, roleLabel]) => {
        const option = document.createElement('option');
        option.value = roleKey;
        option.textContent = `${roleLabel} (${roleKey})`;
        select.appendChild(option);
    });

    const isEmpty = entries.length === 0;
    select.disabled = isEmpty;
    if (submitBtn) submitBtn.disabled = isEmpty;
    if (hint) hint.classList.toggle('d-none', !isEmpty);
}

function activateMatrixTab(matrixType, updateUrl = true) {
    const normalized = (matrixType === 'detail') ? 'detail' : 'clinical';
    currentActiveMatrix = normalized;

    const btnClinical = document.getElementById('btnTabClinical');
    const btnDetail = document.getElementById('btnTabDetail');
    const paneClinical = document.getElementById('paneClinicalMatrix');
    const paneDetail = document.getElementById('paneDetailMatrix');
    const resetInput = document.getElementById('resetActiveMatrixInput');

    if (btnClinical && btnDetail) {
        btnClinical.classList.toggle('active', normalized === 'clinical');
        btnClinical.setAttribute('aria-selected', normalized === 'clinical' ? 'true' : 'false');
        btnDetail.classList.toggle('active', normalized === 'detail');
        btnDetail.setAttribute('aria-selected', normalized === 'detail' ? 'true' : 'false');
    }

    if (paneClinical && paneDetail) {
        paneClinical.classList.toggle('show', normalized === 'clinical');
        paneClinical.classList.toggle('active', normalized === 'clinical');
        paneDetail.classList.toggle('show', normalized === 'detail');
        paneDetail.classList.toggle('active', normalized === 'detail');
    }

    if (resetInput) {
        resetInput.value = normalized;
    }

    if (updateUrl && window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.set('matrix', normalized);
        window.history.replaceState({}, '', url.toString());
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('addMatrixRoleModal');
    if (modalEl) {
        modalEl.addEventListener('show.bs.modal', () => {
            const radio = document.getElementById(currentActiveMatrix === 'detail' ? 'modalMatrixDetail' : 'modalMatrixClinical');
            if (radio) radio.checked = true;
            syncModalRolesForMatrix(currentActiveMatrix);
        });
    }

    // Initialize modal role options for the initial active matrix
    syncModalRolesForMatrix(currentActiveMatrix);
});
</script>
@endpush
@endsection
