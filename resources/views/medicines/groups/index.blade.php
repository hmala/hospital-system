@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- ترويسة الصفحة -->
    <div class="row mb-3 align-items-center">
        <div class="col-lg-6">
            <h2 class="h4 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                <span class="badge bg-success-subtle text-success p-2 rounded-3">
                    <i class="fas fa-layer-group"></i>
                </span>
                مجموعات الأدوية المفضلة (باقات الوصفات السريعة)
            </h2>
            <p class="text-muted small mb-0">
                إدارة قوالب الروشتات السريعة لتسهيل كتابة الوصفة الطبية بضغطة زر واحدة داخل محطة الطبيب.
            </p>
        </div>
        <div class="col-lg-6 d-flex justify-content-lg-end gap-2 mt-3 mt-lg-0 flex-wrap">
            <button type="button" class="btn btn-outline-danger btn-sm px-3 shadow-xs" onclick="confirmResetAllUsages()" title="تصفير عدادات استخدام كافة الباقات لتصبح 0">
                <i class="fas fa-redo me-1"></i> تصفير جميع العدادات
            </button>
            <form id="resetAllUsagesForm" action="{{ route('medicine-groups.reset-usage') }}" method="POST" class="d-none">
                @csrf
            </form>
            <button type="button" class="btn btn-success btn-sm px-3 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                <i class="fas fa-plus me-1"></i> إنشاء باقة جديدة
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm py-2 px-3 small d-flex align-items-center justify-content-between mb-3" role="alert">
            <div><i class="fas fa-check-circle me-2"></i> {{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقات الإحصاءات السريعة -->
    @php
        $totalCount = $groups->count();
        $starredCount = $groups->where('is_starred', true)->count();
        $publicCount = $groups->where('is_public', true)->count();
        $totalUsages = $groups->sum('usage_count');
    @endphp
    <div class="row g-2 mb-3">
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-xs bg-white rounded-3 p-2 d-flex flex-row align-items-center gap-3">
                <div class="bg-primary-subtle text-primary p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">إجمالي الباقات</span>
                    <strong class="text-dark fs-6" id="statTotalCount">{{ $totalCount }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-xs bg-white rounded-3 p-2 d-flex flex-row align-items-center gap-3">
                <div class="bg-warning-subtle text-warning p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">المثبتة بالصدارة ⭐</span>
                    <strong class="text-dark fs-6" id="statStarredCount">{{ $starredCount }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-xs bg-white rounded-3 p-2 d-flex flex-row align-items-center gap-3">
                <div class="bg-info-subtle text-info p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="fas fa-hospital"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">باقات عامة للمستشفى</span>
                    <strong class="text-dark fs-6">{{ $publicCount }}</strong>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-0 shadow-xs bg-white rounded-3 p-2 d-flex flex-row align-items-center gap-3">
                <div class="bg-danger-subtle text-danger p-2 rounded-circle fs-5 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                    <i class="fas fa-fire"></i>
                </div>
                <div>
                    <span class="text-muted small d-block">مرات الاستخدام الكلية</span>
                    <strong class="text-dark fs-6" id="statTotalUsages">{{ $totalUsages }}</strong>
                </div>
            </div>
        </div>
    </div>

    @if($groups->isEmpty())
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <div class="bg-light rounded-circle d-inline-flex p-4 mb-3 text-muted">
                    <i class="fas fa-pills fa-3x opacity-50"></i>
                </div>
                <h5 class="text-dark fw-bold mb-1">لا توجد أي باقات أدوية حتى الآن</h5>
                <p class="text-muted small mb-3">اضغط على زر "إنشاء باقة جديدة" أو احفظ الروشتة الحالية مباشرة من داخل شاشة الكشفية.</p>
                <button type="button" class="btn btn-success btn-sm px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                    <i class="fas fa-plus me-1"></i> إنشاء أول باقة
                </button>
            </div>
        </div>
    @else
        <!-- شريط أدوات البحث، التصفية، والتبديل بين الجدول والبطاقات -->
        <div class="card border-0 shadow-xs mb-3 bg-white">
            <div class="card-body p-2 px-3">
                <div class="row g-2 align-items-center justify-content-between">
                    <!-- البحث الفوري السريع -->
                    <div class="col-md-4 col-12">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0 text-muted">
                                <i class="fas fa-search"></i>
                            </span>
                            <input type="text" id="groupFilterSearch" class="form-control border-start-0 ps-0" placeholder="بحث باسم الباقة، الدواء، الوصف..." autocomplete="off">
                            <button class="btn btn-outline-secondary d-none" type="button" id="clearSearchBtn" title="مسح البحث">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- أزرار تصفية النوع -->
                    <div class="col-md-5 col-12 d-flex gap-1 flex-wrap align-items-center">
                        <button type="button" class="btn btn-sm btn-primary active filter-pill-btn px-2" data-filter="all">
                            الكل ({{ $groups->count() }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-pill-btn px-2" data-filter="starred">
                            ⭐ المثبتة ({{ $starredCount }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-pill-btn px-2" data-filter="public">
                            🏥 العامة ({{ $publicCount }})
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary filter-pill-btn px-2" data-filter="private">
                            👤 الخاصة ({{ $groups->where('is_public', false)->count() }})
                        </button>
                    </div>

                    <!-- محول طريقة العرض (جدول / بطاقات) -->
                    <div class="col-md-3 col-12 d-flex justify-content-md-end gap-1">
                        <div class="btn-group btn-group-sm shadow-xs" role="group" aria-label="طريقة العرض">
                            <button type="button" class="btn btn-outline-secondary view-switcher-btn active" id="btnViewTable" onclick="switchViewGroup('table')" title="عرض جدول منظم">
                                <i class="fas fa-table me-1"></i> جدول
                            </button>
                            <button type="button" class="btn btn-outline-secondary view-switcher-btn" id="btnViewCards" onclick="switchViewGroup('cards')" title="عرض شبكة بطاقات">
                                <i class="fas fa-th-large me-1"></i> بطاقات
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 1. طريقة العرض الأولى: جدول متطور (Table View) -->
        <div id="groupsTableView" class="card border-0 shadow-sm mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="groupsDataTable">
                    <thead class="table-light text-secondary small text-nowrap">
                        <tr>
                            <th style="width: 45px;" class="text-center">⭐</th>
                            <th>اسم الباقة والوصف</th>
                            <th style="width: 110px;" class="text-center">النوع</th>
                            <th>الأدوية المشمولة</th>
                            <th style="width: 170px;" class="text-center">الاستخدام</th>
                            <th style="width: 120px;" class="text-center">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="groupsTableTbody">
                        @foreach($groups as $index => $group)
                            <tr class="group-row-item" 
                                data-id="{{ $group->id }}" 
                                data-is-starred="{{ $group->is_starred ? '1' : '0' }}"
                                data-is-public="{{ $group->is_public ? '1' : '0' }}"
                                data-name="{{ strtolower($group->name) }}"
                                data-desc="{{ strtolower($group->description ?? '') }}"
                                data-meds="{{ strtolower($group->medicines->pluck('name')->implode(' ')) }}">
                                
                                <!-- مفتاح النجمة -->
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-link p-0 text-decoration-none group-star-toggle-btn {{ $group->is_starred ? 'text-warning' : 'text-muted opacity-50' }}"
                                            onclick="toggleGroupStar({{ $group->id }}, this)"
                                            title="{{ $group->is_starred ? 'إلغاء التثبيت من المفضلة' : 'تثبيت في الصدارة ⭐' }}">
                                        <i class="{{ $group->is_starred ? 'fas fa-star' : 'far fa-star' }} fs-5"></i>
                                    </button>
                                </td>

                                <!-- الاسم والوصف -->
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div>
                                            <strong class="text-dark d-block fs-6 group-name-label">
                                                {{ $group->name }}
                                            </strong>
                                            @if(!empty($group->description))
                                                <small class="text-muted d-block line-clamp-1" style="max-width: 320px;">
                                                    {{ $group->description }}
                                                </small>
                                            @endif
                                            @if(Auth::user()->isAdmin() && $group->user)
                                                <span class="text-secondary small font-monospace" style="font-size: 0.72rem;">
                                                    <i class="fas fa-user-edit me-1"></i> {{ $group->user->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- النوع -->
                                <td class="text-center">
                                    @if($group->is_public)
                                        <span class="badge bg-primary text-white font-monospace px-2 py-1" title="باقة عامة لجميع أطباء المستشفى">
                                            <i class="fas fa-hospital me-1"></i>عامة
                                        </span>
                                    @else
                                        <span class="badge bg-success-subtle text-success font-monospace px-2 py-1" title="باقة خاصة بك">
                                            <i class="fas fa-user-md me-1"></i>خاصة
                                        </span>
                                    @endif
                                </td>

                                <!-- الأدوية المشمولة -->
                                <td>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <span class="badge bg-{{ $group->medicines_count > 0 ? 'success' : 'secondary' }} rounded-pill px-2 py-1 font-monospace">
                                            <i class="fas fa-pills me-1"></i> {{ $group->medicines_count }}
                                        </span>
                                        @forelse($group->medicines->take(3) as $m)
                                            <span class="badge bg-light text-dark border font-monospace py-1" style="font-size: 0.72rem;">
                                                {{ $m->name }} <span class="text-muted">({{ $m->pivot->dosage ?: $m->pivot->dosage_form }})</span>
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">لا توجد أدوية مضافة بعد</span>
                                        @endforelse
                                        @if($group->medicines->count() > 3)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.72rem;">
                                                +{{ $group->medicines->count() - 3 }} إضافية
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <!-- عداد الاستخدام + زر التصفير الفردي -->
                                <td class="text-center">
                                    <div class="d-inline-flex align-items-center gap-2 bg-light p-1 px-2 rounded-3 border">
                                        <span class="badge {{ ($group->usage_count > 0) ? 'bg-warning text-dark fw-bold' : 'bg-secondary-subtle text-muted' }} rounded-pill font-monospace usage-badge-val" style="font-size: 0.78rem;">
                                            <i class="fas fa-fire text-danger"></i> <span class="usage-count-num">{{ $group->usage_count }}</span>
                                        </span>
                                        <button type="button" 
                                                class="btn btn-xs btn-outline-secondary p-1 py-0 rounded border-0 btn-reset-single-usage"
                                                onclick="resetSingleGroupUsage({{ $group->id }}, '{{ addslashes($group->name) }}', this)"
                                                title="تصفير عداد هذه الباقة الفردية">
                                            <i class="fas fa-redo-alt text-muted" style="font-size: 0.75rem;"></i>
                                            <small style="font-size: 0.7rem;">تصفير</small>
                                        </button>
                                    </div>
                                </td>

                                <!-- الإجراءات -->
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('medicine-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="تعديل الأدوية والجرعات">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($group->user_id === Auth::id() || Auth::user()->isAdmin())
                                            <form action="{{ route('medicine-groups.destroy', $group) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الباقة؟');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="حذف الباقة">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 2. طريقة العرض الثانية: بطاقات عصرية (Grid Cards View) -->
        <div id="groupsCardsView" class="row g-3 mb-4 d-none">
            @foreach($groups as $index => $group)
                <div class="col-md-6 col-xl-4 group-card-col group-row-item" 
                     data-id="{{ $group->id }}" 
                     data-is-starred="{{ $group->is_starred ? '1' : '0' }}"
                     data-is-public="{{ $group->is_public ? '1' : '0' }}"
                     data-name="{{ strtolower($group->name) }}"
                     data-desc="{{ strtolower($group->description ?? '') }}"
                     data-meds="{{ strtolower($group->medicines->pluck('name')->implode(' ')) }}">
                     
                    <div class="card shadow-xs h-100 border-{{ $group->is_starred ? 'warning border-2' : ($group->is_public ? 'primary' : 'success') }}-subtle transition-card bg-white">
                        <div class="card-body d-flex flex-column justify-content-between p-3">
                            <div>
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="card-title fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                            <!-- زر النجمة -->
                                            <button type="button" 
                                                    class="btn btn-sm btn-link p-0 text-decoration-none group-star-toggle-btn {{ $group->is_starred ? 'text-warning' : 'text-muted opacity-50' }}"
                                                    onclick="toggleGroupStar({{ $group->id }}, this)"
                                                    title="{{ $group->is_starred ? 'إلغاء التثبيت من المفضلة' : 'تثبيت في الصدارة ⭐' }}">
                                                <i class="{{ $group->is_starred ? 'fas fa-star' : 'far fa-star' }} fs-5"></i>
                                            </button>
                                            <span class="group-name-label">{{ $group->name }}</span>
                                            @if($group->is_public)
                                                <span class="badge bg-primary text-white font-monospace fs-7 px-2 py-1" title="باقة عامة متاحة لجميع أطباء المستشفى">
                                                    <i class="fas fa-hospital me-1"></i>عامة
                                                </span>
                                            @else
                                                <span class="badge bg-success-subtle text-success font-monospace fs-7 px-2 py-1" title="باقة خاصة بك فقط">
                                                    <i class="fas fa-user-md me-1"></i>خاصة
                                                </span>
                                            @endif
                                        </h5>
                                        @if(Auth::user()->isAdmin() && $group->user)
                                            <p class="card-text text-muted small mb-1">
                                                <i class="fas fa-user me-1"></i> المنشئ: {{ $group->user->name }}
                                            </p>
                                        @endif
                                        <p class="card-text text-secondary small mb-2 line-clamp-2">
                                            {{ $group->description ?: 'لا يوجد وصف مختصر' }}
                                        </p>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="{{ route('medicine-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" title="تعديل الأدوية والجرعات">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        @if($group->user_id === Auth::id() || Auth::user()->isAdmin())
                                            <form action="{{ route('medicine-groups.destroy', $group) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه الباقة؟');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف الباقة">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                <!-- معاينة الأدوية في البطاقة -->
                                <div class="bg-light p-2 rounded-2 mb-2">
                                    <div class="small fw-bold text-muted mb-1 d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-pills text-success me-1"></i> قائمة الأدوية ({{ $group->medicines->count() }}):</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($group->medicines->take(4) as $m)
                                            <span class="badge bg-white text-dark border font-monospace" style="font-size: 0.72rem;">
                                                {{ $m->name }} <small class="text-muted">({{ $m->pivot->dosage ?: $m->pivot->dosage_form }})</small>
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">لا توجد أدوية مضافة</span>
                                        @endforelse
                                        @if($group->medicines->count() > 4)
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size: 0.7rem;">
                                                +{{ $group->medicines->count() - 4 }} أخرى
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="pt-2 border-top d-flex justify-content-between align-items-center mt-2">
                                <!-- عداد الاستخدام + زر التصفير الفردي -->
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge {{ ($group->usage_count > 0) ? 'bg-warning text-dark fw-bold' : 'bg-secondary-subtle text-muted' }} rounded-pill px-2 py-1 font-monospace usage-badge-val" style="font-size: 0.75rem;" title="عدد مرات الاستخدام في العيادة">
                                        <i class="fas fa-fire text-danger"></i> <span class="usage-count-num">{{ $group->usage_count }}</span>
                                    </span>
                                    <button type="button" 
                                            class="btn btn-xs btn-outline-secondary p-1 py-0 rounded border-0 btn-reset-single-usage"
                                            onclick="resetSingleGroupUsage({{ $group->id }}, '{{ addslashes($group->name) }}', this)"
                                            title="تصفير عداد هذه الباقة">
                                        <i class="fas fa-redo-alt text-muted" style="font-size: 0.75rem;"></i>
                                        <small style="font-size: 0.7rem;">تصفير</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- نافذة إنشاء باقة جديدة -->
<div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-labelledby="createGroupModalLabel" aria-hidden="true" aria-modal="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fs-6 fw-bold" id="createGroupModalLabel">
                    <i class="fas fa-layer-group me-2"></i> إنشاء باقة أدوية مفضلة جديدة
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('medicine-groups.store') }}" method="POST" id="createGroupForm">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="groupName" class="form-label fw-bold small text-dark">اسم الباقة <span class="text-danger">*</span></label>
                        <input type="text" id="groupName" name="name" class="form-control" placeholder="مثال: باقة نزلات البرد، باقة ما بعد العملية..." required maxlength="255" autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label fw-bold small text-dark">وصف مختصر / ملاحظات (اختياري)</label>
                        <textarea id="groupDescription" name="description" class="form-control" rows="3" placeholder="ملاحظات توضيحية حول دواعي استخدام الباقة"></textarea>
                    </div>
                    @if(Auth::user()->isAdmin() || Auth::user()->hasRole('admin'))
                    <div class="form-check form-switch p-3 bg-light rounded-3 border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="groupIsPublic" name="is_public" value="1">
                        <label class="form-check-label fw-bold text-primary small cursor-pointer" for="groupIsPublic">
                            <i class="fas fa-hospital me-1"></i> مشاركة كباقة عامة لجميع أطباء المستشفى
                        </label>
                        <small class="text-muted d-block mt-1">عند التفعيل، ستظهر هذه الباقة في قائمة الباقات السريعة لجميع الأطباء في المستشفى.</small>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                        <i class="fas fa-check me-1"></i> إنشاء ومتابعة إضافة الأدوية
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .transition-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .transition-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.08) !important;
    }
    #createGroupModal.modal {
        z-index: 20050 !important;
    }
    .modal-backdrop.show {
        z-index: 20040 !important;
    }
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .shadow-xs {
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
</style>
@endsection

@section('scripts')
<script>
// 1. التبديل بين الجدول والبطاقات (View Switcher)
window.switchViewGroup = function(mode, save = true) {
    const tableView = document.getElementById('groupsTableView');
    const cardsView = document.getElementById('groupsCardsView');
    const btnTable = document.getElementById('btnViewTable');
    const btnCards = document.getElementById('btnViewCards');

    if (mode === 'cards') {
        if (tableView) tableView.classList.add('d-none');
        if (cardsView) cardsView.classList.remove('d-none');
        if (btnTable) {
            btnTable.classList.remove('btn-primary', 'active');
            btnTable.classList.add('btn-outline-secondary');
        }
        if (btnCards) {
            btnCards.classList.remove('btn-outline-secondary');
            btnCards.classList.add('btn-primary', 'active');
        }
    } else {
        if (cardsView) cardsView.classList.add('d-none');
        if (tableView) tableView.classList.remove('d-none');
        if (btnCards) {
            btnCards.classList.remove('btn-primary', 'active');
            btnCards.classList.add('btn-outline-secondary');
        }
        if (btnTable) {
            btnTable.classList.remove('btn-outline-secondary');
            btnTable.classList.add('btn-primary', 'active');
        }
    }

    if (save) {
        try {
            localStorage.setItem('medicine_groups_view_mode', mode);
        } catch (e) {}
    }
};

// 2. تحديث إجمالي عداد الاستخدام في رأس الصفحة
window.updateTotalUsagesStat = function() {
    let sum = 0;
    document.querySelectorAll('#groupsTableTbody .group-row-item .usage-count-num').forEach(el => {
        sum += parseInt(el.textContent || '0', 10);
    });
    const statEl = document.getElementById('statTotalUsages');
    if (statEl) statEl.textContent = sum;
};

// 3. تصفير العداد الفردي لباقة معينة (Single Reset)
window.resetSingleGroupUsage = function(groupId, groupName, btn) {
    if (!groupId) return;

    const executeReset = () => {
        if (btn) btn.disabled = true;
        fetch(`{{ url('medicine-groups') }}/${groupId}/reset-usage`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success) {
                // تحديث كافة الشارات التي تعرض عداد هذه الباقة (في الجدول والبطاقات)
                const relatedRows = document.querySelectorAll(`.group-row-item[data-id="${groupId}"]`);
                relatedRows.forEach(row => {
                    const countSpan = row.querySelector('.usage-count-num');
                    if (countSpan) countSpan.textContent = '0';
                    const badgeVal = row.querySelector('.usage-badge-val');
                    if (badgeVal) {
                        badgeVal.className = 'badge bg-secondary-subtle text-muted rounded-pill font-monospace usage-badge-val';
                    }
                });

                // تحديث الإحصائية العلوية
                window.updateTotalUsagesStat();

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'تم التصفير بنجاح 🔄',
                        text: `تم تصفير عداد باقة "${groupName}" إلى 0.`,
                        timer: 2000,
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false
                    });
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            console.error('Error resetting group usage:', err);
        });
    };

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'تصفير عداد الباقة؟',
            text: `هل أنت متأكد من تصفير عداد استخدام باقة "${groupName}" ليصبح 0؟`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، تصفير العداد',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                executeReset();
            }
        });
    } else {
        if (confirm(`هل أنت متأكد من تصفير عداد استخدام باقة "${groupName}" ليصبح 0؟`)) {
            executeReset();
        }
    }
};

// 4. تأكيد تصفير جميع العدادات
window.confirmResetAllUsages = function() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'تصفير جميع عدادات الباقات؟',
            text: 'سيتم إعادة جميع عدادات استخدام الباقات إلى 0.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'نعم، تصفير الكل',
            cancelButtonText: 'إلغاء'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('resetAllUsagesForm');
                if (form) form.submit();
            }
        });
    } else {
        if (confirm('هل أنت متأكد من تصفير عدادات استخدام كافة الباقات لتصبح 0؟')) {
            const form = document.getElementById('resetAllUsagesForm');
            if (form) form.submit();
        }
    }
};

// 5. التثبيت في المفضلة ⭐
window.toggleGroupStar = function(groupId, btn) {
    if (!groupId) return;
    if (btn) btn.disabled = true;

    fetch(`{{ url('medicine-groups') }}/${groupId}/toggle-star`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (btn) btn.disabled = false;
        if (data.success) {
            const isStarred = data.is_starred;
            const relatedItems = document.querySelectorAll(`.group-row-item[data-id="${groupId}"]`);

            relatedItems.forEach(item => {
                item.setAttribute('data-is-starred', isStarred ? '1' : '0');
                const starBtn = item.querySelector('.group-star-toggle-btn');
                if (starBtn) {
                    if (isStarred) {
                        starBtn.className = 'btn btn-sm btn-link p-0 text-decoration-none group-star-toggle-btn text-warning';
                        starBtn.innerHTML = '<i class="fas fa-star fs-5"></i>';
                        starBtn.title = 'إلغاء التثبيت من المفضلة';
                    } else {
                        starBtn.className = 'btn btn-sm btn-link p-0 text-decoration-none group-star-toggle-btn text-muted opacity-50';
                        starBtn.innerHTML = '<i class="far fa-star fs-5"></i>';
                        starBtn.title = 'تثبيت في الصدارة ⭐';
                    }
                }
                const card = item.querySelector('.card');
                if (card) {
                    if (isStarred) {
                        card.classList.add('border-warning', 'border-2');
                    } else {
                        card.classList.remove('border-warning', 'border-2');
                    }
                }
            });

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: isStarred ? 'success' : 'info',
                    title: data.message,
                    timer: 1500,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false
                });
            }
        }
    })
    .catch(err => {
        if (btn) btn.disabled = false;
        console.error('Error toggling group star:', err);
    });
};

// 6. تهيئة الصفحة والبحث والفلترة عند اكتمال تحميل DOM
document.addEventListener('DOMContentLoaded', function() {
    // تفعيل نمط العرض المحفوظ
    try {
        const savedView = localStorage.getItem('medicine_groups_view_mode') || 'table';
        window.switchViewGroup(savedView, false);
    } catch (e) {
        window.switchViewGroup('table', false);
    }

    // البحث والفلترة الفورية
    const searchInput = document.getElementById('groupFilterSearch');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const filterPills = document.querySelectorAll('.filter-pill-btn');
    let activeFilter = 'all';

    function applyFilters() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        if (clearSearchBtn) {
            clearSearchBtn.classList.toggle('d-none', !query);
        }

        const items = document.querySelectorAll('.group-row-item');
        items.forEach(item => {
            const isStarred = item.getAttribute('data-is-starred') === '1';
            const isPublic = item.getAttribute('data-is-public') === '1';
            const name = item.getAttribute('data-name') || '';
            const desc = item.getAttribute('data-desc') || '';
            const meds = item.getAttribute('data-meds') || '';

            let matchesCategory = true;
            if (activeFilter === 'starred') matchesCategory = isStarred;
            else if (activeFilter === 'public') matchesCategory = isPublic;
            else if (activeFilter === 'private') matchesCategory = !isPublic;

            let matchesSearch = true;
            if (query) {
                matchesSearch = name.includes(query) || desc.includes(query) || meds.includes(query);
            }

            item.style.display = (matchesCategory && matchesSearch) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
    }
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            applyFilters();
            searchInput.focus();
        });
    }

    filterPills.forEach(pill => {
        pill.addEventListener('click', function() {
            filterPills.forEach(p => {
                p.classList.remove('btn-primary', 'active');
                p.classList.add('btn-outline-secondary');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary', 'active');
            activeFilter = this.getAttribute('data-filter');
            applyFilters();
        });
    });
});
</script>
@endsection
