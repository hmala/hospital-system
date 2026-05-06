@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-layer-group me-2"></i>مجموعات المفضلات</h2>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createGroupModal">
                <i class="fas fa-plus me-1"></i> إنشاء مجموعة جديدة
            </button>
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
                <div class="alert alert-info">ليس لديك أي مجموعة حتى الآن. اضغط على زر "إنشاء مجموعة جديدة" لإضافة واحدة.</div>
            @else
                <div class="alert alert-secondary">اضغط على زر التحرير بجانب أي مجموعة لاختيار التحاليل الخاصة بها.</div>
                <div class="row g-3">
                    @foreach($groups as $group)
                        <div class="col-md-6 col-xl-4">
                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="card-title mb-1">{{ $group->name }}</h5>
                                            @if(Auth::user()->hasRole('admin') && $group->user)
                                                <p class="card-text text-muted mb-1">صاحب المجموعة: {{ $group->user->name }}</p>
                                            @endif
                                            <p class="card-text text-muted mb-1">{{ $group->description ?? 'بدون وصف' }}</p>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('lab-tests.groups.edit', $group) }}" class="btn btn-sm btn-outline-primary" title="تحرير المجموعة"><i class="fas fa-edit"></i></a>
                                            <form action="{{ route('lab-tests.groups.destroy', $group) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذه المجموعة؟');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="حذف المجموعة"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge bg-primary">{{ $group->lab_tests_count }} تحليل</span>
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
                        <label for="groupName" class="form-label">اسم المجموعة</label>
                        <input type="text" id="groupName" name="name" class="form-control" required maxlength="255" autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="groupDescription" class="form-label">وصف (اختياري)</label>
                        <textarea id="groupDescription" name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إنشاء</button>
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
