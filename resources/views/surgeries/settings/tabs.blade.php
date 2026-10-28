@extends('layouts.app')

@section('content')
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
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 22px 28px;
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
            min-width: 200px;
            background: #ffffff;
            font-weight: 700;
            border-inline-end: 2px solid #e2e8f0;
            vertical-align: middle;
            padding: 14px 18px;
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
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
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

    {{-- Alert Messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle fa-lg me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle fa-lg me-2"></i>
            <div>{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Main Container Card --}}
    <div class="matrix-card">
        {{-- Card Header --}}
        <div class="matrix-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h4 class="mb-1 fw-bold text-white">
                    <i class="fas fa-sliders-h text-warning me-2"></i> مصفوفة صلاحيات تبويبات المحطة السريرية الموحدة
                </h4>
                <p class="mb-0 text-white-50 small">
                    التحكم المركزي في ظهور وطبيعة كل تبويب سريري لكل دور وظيفي (مخفي، قراءة فقط، أو قراءة وإدخال).
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form action="{{ route('surgery-stations.tab-settings.reset') }}" method="POST" onsubmit="return confirm('هل أنت متأكد من استعادة الضبط الافتراضي للمصفوفة الطبية؟');">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm rounded-pill px-3">
                        <i class="fas fa-undo me-1"></i> استعادة الضبط الطبي الافتراضي
                    </button>
                </form>
            </div>
        </div>

        {{-- Legend Bar --}}
        <div class="bg-light px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-3">
                <span class="small fw-bold text-secondary">دليل مستويات الصلاحية:</span>
                <span class="level-pill-badge bg-secondary bg-opacity-10 text-secondary border">
                    <i class="fas fa-eye-slash text-muted"></i> 🚫 <strong>مخفي (Hidden):</strong> لا يظهر التبويب أصلاً للمستخدم.
                </span>
                <span class="level-pill-badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">
                    <i class="fas fa-eye text-primary"></i> 👁️ <strong>قراءة فقط (Read):</strong> تصفح واطلاع فقط دون إمكانية إدخال أو تعديل.
                </span>
                <span class="level-pill-badge bg-success bg-opacity-10 text-success border border-success-subtle">
                    <i class="fas fa-pen text-success"></i> ✍️ <strong>قراءة وإدخال (Write):</strong> صلاحية كاملة للإدخال والتعديل والحفظ.
                </span>
            </div>
            <div>
                <span class="badge bg-dark-subtle text-dark border px-3 py-2 rounded-pill small">
                    <i class="fas fa-shield-alt text-primary me-1"></i> مدير النظام يملك صلاحية كاملة تلقائياً
                </span>
            </div>
        </div>

        {{-- Form & Interactive Matrix --}}
        <form action="{{ route('surgery-stations.tab-settings.update') }}" method="POST">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover table-bordered mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="tab-col-header text-start px-3" style="min-width: 220px;">
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
                        @php
                            $targetRoleKeys = ['resident', 'nurse', 'doctor', 'surgery_coordinator', 'surgery_staff', 'admin'];
                        @endphp
                        @foreach($targetRoleKeys as $roleKey)
                            @php
                                $roleLabel = $roleLabels[$roleKey] ?? $roleKey;
                                $isRoleAdmin = ($roleKey === 'admin');
                            @endphp
                            <tr>
                                <td class="role-row-header">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-circle rounded-circle bg-light border p-2 text-center" style="width: 36px; height: 36px;">
                                            @if($roleKey === 'resident')
                                                <i class="fas fa-user-graduate text-primary"></i>
                                            @elseif($roleKey === 'nurse')
                                                <i class="fas fa-user-nurse text-danger"></i>
                                            @elseif($roleKey === 'doctor')
                                                <i class="fas fa-user-md text-success"></i>
                                            @elseif($roleKey === 'admin')
                                                <i class="fas fa-user-shield text-dark"></i>
                                            @else
                                                <i class="fas fa-hospital-user text-info"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark">{{ $roleLabel }}</div>
                                            <div class="text-muted small" style="font-size: 0.75rem;">الكود: <code>{{ $roleKey }}</code></div>
                                        </div>
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

            {{-- Save Footer --}}
            <div class="p-3 bg-white border-top d-flex align-items-center justify-content-between">
                <span class="text-muted small">
                    <i class="fas fa-info-circle text-info me-1"></i> يتم تطبيق التعديلات فور الحفظ على كافة شاشات الأطباء والممرضين.
                </span>
                <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">
                    <i class="fas fa-save me-1"></i> حفظ مصفوفة الصلاحيات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateSelectColor(select) {
    select.setAttribute('data-level', select.value);
}
</script>
@endsection

