@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h2 class="mb-1"><i class="fas fa-layer-group text-primary me-2"></i>مجموعات وباقات التحاليل المفضلة</h2>
                <p class="text-muted small mb-0">إدارة الباقات السريعة للأطباء والاستشارية وقسم الخدج والحاضنات</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                <i class="fas fa-plus me-1"></i> إنشاء باقة / مجموعة جديدة
            </button>
        </div>
    </div>

    <!-- فلترة الأقسام -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group" role="group" aria-label="Department Filter">
                <a href="{{ route('lab-tests.groups.index') }}" class="btn btn-sm {{ empty($department) ? 'btn-dark' : 'btn-outline-dark' }}">
                    <i class="fas fa-th-list me-1"></i> كافة الباقات ({{ \App\Models\UserLabTestGroup::count() }})
                </a>
                <a href="{{ route('lab-tests.groups.index', ['department' => 'nicu']) }}" class="btn btn-sm {{ $department === 'nicu' ? 'btn-info text-white' : 'btn-outline-info' }}">
                    <i class="fas fa-baby me-1"></i> باقات قسم الخدج والحاضنات (NICU) ({{ \App\Models\UserLabTestGroup::where('department', 'nicu')->count() }})
                </a>
                <a href="{{ route('lab-tests.groups.index', ['department' => 'consultant']) }}" class="btn btn-sm {{ $department === 'consultant' ? 'btn-primary' : 'btn-outline-primary' }}">
                    <i class="fas fa-user-md me-1"></i> باقات العيادات والاستشارية ({{ \App\Models\UserLabTestGroup::where(function($q){ $q->whereNull('department')->orWhere('department', 'consultant'); })->count() }})
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            @if($groups->isEmpty())
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-1"></i> لا توجد باقات في هذا القسم حالياً. اضغط على زر "إنشاء باقة / مجموعة جديدة" لإضافة واحدة.
                </div>
            @else
                <div class="alert alert-secondary py-2 small mb-3">
                    <i class="fas fa-mouse-pointer me-1"></i> اضغط على زر التحرير <i class="fas fa-edit text-primary"></i> بجانب أي باقة لاختيار وإضافة التحاليل الخاصة بها.
                </div>
                <div class="row g-3">
                    @foreach($groups as $group)
                        <div class="col-md-6 col-xl-4">
                            <div class="card shadow-sm h-100 border-{{ $group->department === 'nicu' ? 'info' : 'secondary' }}-subtle">
                                <div class="card-body d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <h5 class="card-title fw-bold text-dark mb-0">{{ $group->name }}</h5>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('lab-tests.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" title="تحرير الباقة والتحاليل"><i class="fas fa-edit"></i></a>
                                                <form action="{{ route('lab-tests.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة؟');" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف المجموعة"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            @if($group->department === 'nicu')
                                                <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle"><i class="fas fa-baby me-1"></i> قسم الخدج (NICU)</span>
                                            @else
                                                <span class="badge bg-light text-secondary border"><i class="fas fa-stethoscope me-1"></i> عيادات واستشارية</span>
                                            @endif

                                            @if($group->is_public)
                                                <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle"><i class="fas fa-globe me-1"></i> عامة للقسم</span>
                                            @endif
                                        </div>

                                        @if(Auth::user()->hasRole('admin') && $group->user)
                                            <p class="card-text text-muted small mb-1"><i class="fas fa-user-circle me-1"></i> صاحب الباقة: {{ $group->user->name }}</p>
                                        @endif
                                        <p class="card-text text-muted small mb-2">{{ $group->description ?? 'بدون وصف' }}</p>
                                    </div>
                                    <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary fs-7"><i class="fas fa-vial me-1"></i> {{ $group->lab_tests_count }} تحليل</span>
                                        <a href="{{ route('lab-tests.groups.edit', $group) }}" class="btn btn-link btn-sm p-0 text-decoration-none">تعديل الفحوصات &larr;</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>

<style>
    /* اجعل مودال إنشاء المجموعة فوق أي عنصر آخر */
    #createGroupModal.modal {
        z-index: 20050 !important;
    }
    .modal-backdrop.show {
        z-index: 20040 !important;
    }
    #createGroupModal .modal-dialog,
    #createGroupModal .modal-content,
    #createGroupModal input,
    #createGroupModal textarea {
        pointer-events: auto !important;
    }
</style>

<div class="modal fade" id="createGroupModal" tabindex="-1" role="dialog" aria-labelledby="createGroupModalLabel" aria-hidden="true" aria-modal="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="createGroupModalLabel">إنشاء مجموعة مفضلات جديدة</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('lab-tests.groups.store') }}" method="POST" id="createGroupForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="groupName" class="form-label fw-bold">اسم الباقة / المجموعة</label>
                        <input type="text" id="groupName" name="name" class="form-control" placeholder="مثال: باقة دخول روتيني للخدج، يرقان..." required maxlength="255" autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="groupDepartment" class="form-label fw-bold">القسم المخصص للباقة</label>
                        <select name="department" id="groupDepartment" class="form-select">
                            <option value="nicu" {{ request('department') === 'nicu' ? 'selected' : '' }}>👶 قسم الخدج والحاضنات (NICU)</option>
                            <option value="consultant" {{ request('department') !== 'nicu' ? 'selected' : '' }}>🩺 الاستشارية والعيادات العامة</option>
                        </select>
                        <div class="form-text">تحديد القسم يضمن ظهور الباقة في محطة عمل القسم المخصص فقط دون تداخل.</div>
                    </div>
                    @if(Auth::user()->isAdmin())
                        <div class="mb-3 form-check bg-light p-2 rounded border">
                            <div class="ms-4">
                                <input type="checkbox" class="form-check-input" id="groupIsPublic" name="is_public" value="1" {{ request('department') === 'nicu' ? 'checked' : '' }}>
                                <label class="form-check-label fw-bold" for="groupIsPublic">باقة عامة (تظهر لكافة أطباء وكوادر القسم)</label>
                                <div class="form-text small mb-0">عند تفعيلها، تصبح الباقة متاحة لجميع الأطباء في محطة العمل المحددة.</div>
                            </div>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">وصف الباقة (اختياري)</label>
                        <textarea id="groupDescription" name="description" class="form-control" rows="2" placeholder="وصف الفحوصات المستهدفة أو دواعي طلب الباقة..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> إنشاء ومتابعة اختيار التحاليل</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const createGroupModal = document.getElementById('createGroupModal');
    
    if (createGroupModal) {
        if (createGroupModal.parentElement !== document.body) {
            document.body.appendChild(createGroupModal);
        }
        
        createGroupModal.style.position = 'fixed';
        createGroupModal.style.zIndex = '20050';

        createGroupModal.addEventListener('shown.bs.modal', function () {
            const nameInput = document.getElementById('groupName');
            if (nameInput) {
                nameInput.focus();
            }
        });
        
        // إعادة تعيين النموذج عند إغلاق المودال
        createGroupModal.addEventListener('hidden.bs.modal', function () {
            const form = document.getElementById('createGroupForm');
            if (form) {
                form.reset();
            }
        });
    }
});
</script>
@endsection
