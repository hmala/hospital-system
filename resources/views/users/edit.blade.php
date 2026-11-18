@extends('layouts.app')

@section('title', 'تعديل ملف المستخدم | ' . $user->name)

@push('styles')
<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .transition-all {
        transition: all 0.2s ease-in-out;
    }
    .user-avatar-circle {
        width: 80px;
        height: 80px;
        font-size: 2rem;
        background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        box-shadow: 0 8px 20px -4px rgba(37, 99, 235, 0.35);
        border: 3px solid #ffffff;
    }
    .role-select-card {
        border: 1.5px solid #e2e8f0;
        background-color: #ffffff;
        transition: all 0.2s ease;
        user-select: none;
    }
    .role-select-card:hover {
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
    }
    .role-select-card.active {
        border-color: #2563eb !important;
        background-color: #eff6ff !important;
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
    }
    .role-select-card input[type="checkbox"] {
        width: 1.25rem;
        height: 1.25rem;
        cursor: pointer;
    }
    .category-header {
        background-color: #f8fafc;
        border-radius: 12px;
        padding: 0.65rem 1rem;
        border: 1px solid #e2e8f0;
    }
    .input-icon-box {
        background-color: #f8fafc;
        border-left: none;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-3 px-lg-4">

    {{-- Breadcrumb & Action Bar --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-pill px-3 shadow-xs">
                <i class="fas fa-arrow-right me-1"></i> العودة لقائمة المستخدمين
            </a>
            <div class="vr text-muted opacity-25 d-none d-sm-block" style="height: 24px;"></div>
            <div>
                <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                    <i class="fas fa-user-gear text-primary"></i>
                    <span>تعديل ملف المستخدم</span>
                </h4>
                <small class="text-muted">تحديث البيانات الشخصية، كلمة المرور، والمخزن والصلاحيات الممنوحة</small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-light rounded-pill px-3 text-secondary border">
                <i class="fas fa-times me-1"></i> إلغاء
            </a>
            <button type="submit" form="editUserForm" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-save me-1"></i> حفظ وتحديث البيانات
            </button>
        </div>
    </div>

    {{-- Validation Error Alerts --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show rounded-4 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2 fw-bold mb-1">
                <i class="fas fa-exclamation-triangle fs-5"></i>
                <span>يرجى تصحيح الأخطاء التالية للمتابعة:</span>
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Right Column (Profile Sidebar Card) --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 sticky-top" style="top: 85px; z-index: 10;">
                {{-- Card Header Banner --}}
                <div class="bg-gradient bg-primary text-white p-4 text-center position-relative" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%) !important;">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="user-avatar-circle">
                            {{ mb_substr($user->name, 0, 1) }}
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1 text-white">{{ $user->name }}</h5>
                    <p class="text-white-50 small mb-2">{{ $user->email }}</p>
                    <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap">
                        <span class="badge bg-white bg-opacity-20 text-white rounded-pill px-3 py-1 font-monospace">
                            #ID: {{ $user->id }}
                        </span>
                        @if($user->is_active ?? true)
                            <span class="badge bg-success bg-opacity-25 text-white border border-success border-opacity-50 rounded-pill px-3 py-1">
                                <i class="fas fa-circle fa-xs me-1 text-success"></i> نشط ومفعل
                            </span>
                        @else
                            <span class="badge bg-danger bg-opacity-25 text-white border border-danger border-opacity-50 rounded-pill px-3 py-1">
                                <i class="fas fa-ban fa-xs me-1 text-danger"></i> معطل
                            </span>
                        @endif
                    </div>
                </div>

                {{-- User Metadata Body --}}
                <div class="card-body p-4 bg-white">
                    <h6 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        <span>ملخص الحساب والبيانات</span>
                    </h6>

                    <ul class="list-group list-group-flush mb-4 small">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <span class="text-muted"><i class="fas fa-id-card-clip me-1.5 text-secondary"></i> الدور الأساسي:</span>
                            <span class="fw-bold text-dark">{{ $user->role ?? 'غير محدد' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <span class="text-muted"><i class="fas fa-boxes me-1.5 text-secondary"></i> المخزن المرتبط:</span>
                            <span class="fw-bold text-primary">{{ $user->location?->name ?? 'غير مرتبط بمخزن' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <span class="text-muted"><i class="far fa-calendar-alt me-1.5 text-secondary"></i> تاريخ التسجيل:</span>
                            <span class="text-dark">{{ $user->created_at ? $user->created_at->format('Y-m-d') : 'غير متوفر' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2.5">
                            <span class="text-muted"><i class="far fa-clock me-1.5 text-secondary"></i> آخر تعديل:</span>
                            <span class="text-dark">{{ $user->updated_at ? $user->updated_at->format('Y-m-d H:i') : 'غير متوفر' }}</span>
                        </li>
                    </ul>

                    {{-- Current Active Roles Pills --}}
                    <div class="mb-4">
                        <span class="text-muted small fw-bold d-block mb-2">الأدوار الممنوحة لهذا الحساب:</span>
                        <div class="d-flex flex-wrap gap-1.5" id="currentRolesSummary">
                            @forelse($user->roles as $r)
                                @php
                                    $info = \App\Http\Controllers\RoleManagementController::getRoleLabel($r->name);
                                @endphp
                                <span class="badge bg-{{ $info['color'] }} bg-opacity-10 text-{{ $info['color'] }} border border-{{ $info['color'] }} border-opacity-25 rounded-pill px-2.5 py-1.5 font-sans">
                                    <i class="fas fa-{{ $info['icon'] }} me-1"></i> {{ $info['label'] }}
                                </span>
                            @empty
                                <span class="text-muted small">لا توجد أدوار مسندة حالياً</span>
                            @endforelse
                        </div>
                    </div>

                    {{-- Quick Status Toggle Form --}}
                    @if($user->id !== 1 && $user->email !== 'admin@hospital.com')
                        <div class="border-top pt-3 text-center">
                            <form action="{{ route('users.toggle-status', $user) }}" method="POST" onsubmit="return confirm('تأكيد تغيير حالة تفعيل هذا الحساب؟');">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm {{ ($user->is_active ?? true) ? 'btn-outline-danger' : 'btn-outline-success' }} w-100 rounded-pill py-2">
                                    <i class="fas fa-power-off me-1"></i>
                                    {{ ($user->is_active ?? true) ? 'تعطيل هذا الحساب مؤقتاً' : 'تفعيل وتنشيط الحساب' }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Left Column (Main Form Area) --}}
        <div class="col-lg-8">
            <form id="editUserForm" action="{{ route('users.update', $user) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- 1. Personal & Login Details Card --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-id-badge text-primary"></i>
                            <span>البيانات الأساسية والدخول</span>
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label fw-bold small text-dark">
                                    الاسم الكامل للموظف <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-user"></i></span>
                                    <input type="text" 
                                           class="form-control border-start-0 @error('name') is-invalid @enderror" 
                                           id="name" 
                                           name="name" 
                                           placeholder="أدخل اسم الموظف الثلاثي..."
                                           value="{{ old('name', $user->name) }}" 
                                           required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-bold small text-dark">
                                    البريد الإلكتروني الرسمي <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-envelope"></i></span>
                                    <input type="email" 
                                           class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                           id="email" 
                                           name="email" 
                                           placeholder="example@hospital.com"
                                           value="{{ old('email', $user->email) }}" 
                                           required>
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-12">
                                <label for="location_id" class="form-label fw-bold small text-dark d-flex justify-content-between align-items-center">
                                    <span>المخزن أو عهدة الطابق المرتبطة</span>
                                    <span class="text-muted fw-normal small">(اختياري لكادر المستودعات والتمريض)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-boxes"></i></span>
                                    <select name="location_id" id="location_id" class="form-select border-start-0 @error('location_id') is-invalid @enderror">
                                        <option value="">🏢 بدون ربط بمخزن محدد (عام)</option>
                                        @foreach($locations as $location)
                                            <option value="{{ $location->id }}" {{ old('location_id', $user->location_id) == $location->id ? 'selected' : '' }}>
                                                {{ $location->name }} {{ $location->code ? "({$location->code})" : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('location_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted mt-1 d-block">
                                    <i class="fas fa-info-circle me-1 text-primary"></i> عند ربط المستخدم بمخزن معين، يتم اعتماد هذا المخزن تلقائياً لحركات الصرف وطلبات التجهيز السريري الخاصة به.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. Security & Password Card --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            <i class="fas fa-shield-halved text-success"></i>
                            <span>الأمان وكلمة المرور</span>
                        </h5>
                        <span class="badge bg-light text-muted border rounded-pill px-3 py-1 small">
                            اختياري
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-light border border-info border-opacity-25 rounded-3 py-2.5 px-3 mb-3 d-flex align-items-center gap-2 text-primary small">
                            <i class="fas fa-lock text-info"></i>
                            <span>اترك حقول كلمة المرور فارغة تماماً إذا كنت ترغب بالإبقاء على كلمة المرور الحالية دون تغيير.</span>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password" class="form-label fw-bold small text-dark">كلمة المرور الجديدة</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-key"></i></span>
                                    <input type="password" 
                                           class="form-control border-start-0 border-end-0 @error('password') is-invalid @enderror" 
                                           id="password" 
                                           name="password"
                                           placeholder="••••••••">
                                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('password', this)">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">8 أحرف أو أرقام على الأقل</small>
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label fw-bold small text-dark">تأكيد كلمة المرور</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-check-double"></i></span>
                                    <input type="password" 
                                           class="form-control border-start-0 border-end-0" 
                                           id="password_confirmation" 
                                           name="password_confirmation"
                                           placeholder="••••••••">
                                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('password_confirmation', this)">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. Categorized Roles Assignment Card --}}
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom py-3 px-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h5 class="fw-bold mb-1 text-dark d-flex align-items-center gap-2">
                                    <i class="fas fa-user-shield text-warning"></i>
                                    <span>الصلاحيات والأدوار الممنوحة</span>
                                    <span class="text-danger">*</span>
                                </h5>
                                <small class="text-muted">حدد دوراً وظيفياً واحداً على الأقل لمنح المستخدم صلاحيات استخدام محطات النظام</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1.5 fw-bold" id="selectedBadgeCounter">
                                    <span id="selectedCountText">0</span> دور محدد
                                </span>
                            </div>
                        </div>

                        {{-- Role Live Search Filter --}}
                        <div class="mt-3">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" id="roleSearchInput" placeholder="بحث سريع في أسماء وتصنيفات الأدوار (مثال: تخدير، تمريض، استقبال، جراح)...">
                                <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('roleSearchInput').value=''; filterRoles();">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">
                        @error('roles')
                            <div class="alert alert-danger py-2 small mb-3">
                                <i class="fas fa-exclamation-circle me-1"></i> {{ $message }}
                            </div>
                        @enderror

                        @php
                            $userRoleNames = old('roles', $user->roles->pluck('name')->toArray());
                            
                            // Group roles by category
                            $groupedRoles = [];
                            foreach ($roles as $r) {
                                $category = \App\Http\Controllers\RoleManagementController::getRoleCategory($r->name);
                                $catKey = $category['key'];
                                if (!isset($groupedRoles[$catKey])) {
                                    $groupedRoles[$catKey] = [
                                        'info' => $category,
                                        'roles' => []
                                    ];
                                }
                                $groupedRoles[$catKey]['roles'][] = $r;
                            }
                        @endphp

                        @foreach($groupedRoles as $catKey => $catData)
                            <div class="role-category-block mb-4" data-category="{{ $catKey }}">
                                <div class="category-header d-flex justify-content-between align-items-center mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="rounded-circle bg-{{ $catData['info']['color'] }} bg-opacity-15 text-{{ $catData['info']['color'] }} p-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                            <i class="fas fa-{{ $catData['info']['icon'] }} fa-sm"></i>
                                        </div>
                                        <h6 class="fw-bold mb-0 text-dark">{{ $catData['info']['name'] }}</h6>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill small">
                                            {{ count($catData['roles']) }}
                                        </span>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted select-all-cat-btn" onclick="toggleCategoryRoles('{{ $catKey }}', true)">
                                            تحديد القسم
                                        </button>
                                        <span class="text-muted mx-1">|</span>
                                        <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" onclick="toggleCategoryRoles('{{ $catKey }}', false)">
                                            إلغاء
                                        </button>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    @foreach($catData['roles'] as $role)
                                        @php
                                            $roleInfo = \App\Http\Controllers\RoleManagementController::getRoleLabel($role->name);
                                            $isChecked = in_array($role->name, $userRoleNames);
                                        @endphp
                                        <div class="col-md-6 role-item-col" data-search-text="{{ mb_strtolower($role->name . ' ' . $roleInfo['label'] . ' ' . $catData['info']['name']) }}">
                                            <label class="role-select-card d-flex align-items-center justify-content-between p-3 rounded-3 cursor-pointer {{ $isChecked ? 'active' : '' }}" for="role_{{ $role->id }}">
                                                <div class="d-flex align-items-center gap-3">
                                                    <input class="form-check-input role-checkbox m-0" 
                                                           type="checkbox" 
                                                           name="roles[]" 
                                                           value="{{ $role->name }}" 
                                                           id="role_{{ $role->id }}"
                                                           data-category="{{ $catKey }}"
                                                           {{ $isChecked ? 'checked' : '' }}>
                                                    <div class="role-icon-box rounded-circle bg-{{ $roleInfo['color'] }} bg-opacity-10 text-{{ $roleInfo['color'] }} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                                                        <i class="fas fa-{{ $roleInfo['icon'] }}"></i>
                                                    </div>
                                                    <div>
                                                        <span class="d-block fw-bold text-dark role-label-text">{{ $roleInfo['label'] }}</span>
                                                        <small class="text-muted font-monospace">{{ $role->name }}</small>
                                                    </div>
                                                </div>
                                                <i class="fas fa-check-circle text-primary fs-5 role-check-icon {{ $isChecked ? '' : 'd-none' }}"></i>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Form Actions Bar --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-2 mb-5">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-times me-1"></i> إلغاء التعديلات
                    </a>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold shadow-sm">
                        <i class="fas fa-save me-1.5"></i> حفظ وتحديث بيانات المستخدم
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 1. Toggle Password Visibility
    function togglePasswordVisibility(fieldId, btnEl) {
        const input = document.getElementById(fieldId);
        const icon = btnEl.querySelector('i');
        if (!input) return;
        if (input.type === 'password') {
            input.type = 'text';
            icon.className = 'far fa-eye-slash text-primary';
        } else {
            input.type = 'password';
            icon.className = 'far fa-eye';
        }
    }

    // 2. Role Checkbox Toggle Styling & Counter
    function updateRoleCardsVisual() {
        let count = 0;
        document.querySelectorAll('.role-checkbox').forEach(cb => {
            const card = cb.closest('.role-select-card');
            const checkIcon = card.querySelector('.role-check-icon');
            if (cb.checked) {
                card.classList.add('active');
                if (checkIcon) checkIcon.classList.remove('d-none');
                count++;
            } else {
                card.classList.remove('active');
                if (checkIcon) checkIcon.classList.add('d-none');
            }
        });
        const counterEl = document.getElementById('selectedCountText');
        if (counterEl) counterEl.textContent = count;
    }

    document.querySelectorAll('.role-checkbox').forEach(cb => {
        cb.addEventListener('change', updateRoleCardsVisual);
    });

    // 3. Select / Deselect All Roles in Category
    function toggleCategoryRoles(catKey, state) {
        const catBlock = document.querySelector(`.role-category-block[data-category="${catKey}"]`);
        if (!catBlock) return;
        catBlock.querySelectorAll('.role-checkbox').forEach(cb => {
            cb.checked = state;
        });
        updateRoleCardsVisual();
    }

    // 4. Live Search in Roles
    function filterRoles() {
        const query = document.getElementById('roleSearchInput').value.trim().toLowerCase();
        document.querySelectorAll('.role-category-block').forEach(catBlock => {
            let catHasVisible = false;
            catBlock.querySelectorAll('.role-item-col').forEach(col => {
                const text = col.dataset.searchText || '';
                if (!query || text.includes(query)) {
                    col.style.display = '';
                    catHasVisible = true;
                } else {
                    col.style.display = 'none';
                }
            });
            catBlock.style.display = catHasVisible ? '' : 'none';
        });
    }

    document.getElementById('roleSearchInput')?.addEventListener('input', filterRoles);

    // Initial count update
    document.addEventListener('DOMContentLoaded', updateRoleCardsVisual);
</script>
@endpush
