@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <!-- Header Section -->
    <div class="row mb-4 align-items-center">
        <div class="col-md-7">
            <div class="d-flex align-items-center gap-3">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-4 border border-info border-opacity-25 shadow-sm">
                    <i class="fas fa-hands-helping fa-2x"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-1 text-dark">دليل وإدارة مساعدي الجراحين</h2>
                    <p class="text-muted mb-0 small">إدارة وتحديث قائمة مساعدي الجراحين وكوادر العمليات المعتمدة للربط المباشر مع سجل العمليات</p>
                </div>
            </div>
        </div>
        <div class="col-md-5 text-md-end mt-3 mt-md-0">
            @if($canManage)
                <button type="button" class="btn btn-primary px-3 py-2 shadow-sm rounded-3 fw-bold" data-bs-toggle="modal" data-bs-target="#createAssistantModal">
                    <i class="fas fa-user-plus me-1"></i>
                    إضافة مساعد جراح جديد
                </button>
            @endif
        </div>
    </div>

    <!-- Alert Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-3 d-flex align-items-center" role="alert">
            <i class="fas fa-check-circle fs-5 me-2"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">إجمالي المساعدين</span>
                        <h4 class="mb-0 fw-bold text-dark font-monospace">{{ $totalCount }}</h4>
                    </div>
                    <div class="rounded-circle p-3 bg-light text-primary">
                        <i class="fas fa-users fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">المساعدون النشطون</span>
                        <h4 class="mb-0 fw-bold text-success font-monospace">{{ $activeCount }}</h4>
                    </div>
                    <div class="rounded-circle p-3 bg-success-subtle text-success">
                        <i class="fas fa-user-check fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-md-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3 d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small fw-semibold d-block mb-1">المعطلون / المؤرشفون</span>
                        <h4 class="mb-0 fw-bold text-secondary font-monospace">{{ $inactiveCount }}</h4>
                    </div>
                    <div class="rounded-circle p-3 bg-secondary-subtle text-secondary">
                        <i class="fas fa-user-slash fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('surgical-assistants.index') }}">
                <div class="row g-2 align-items-end">
                    <div class="col-md-6 col-lg-5">
                        <label class="form-label small fw-bold text-muted mb-1">البحث بالاسم أو الهاتف أو الملاحظات</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control border-start-0" placeholder="اكتب اسم المساعد للبحث..." value="{{ request('search') }}">
                        </div>
                    </div>
                    <div class="col-md-3 col-lg-3">
                        <label class="form-label small fw-bold text-muted mb-1">الحالة</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">جميع الحالات</option>
                            <option value="active" @selected(request('status') === 'active')>نشط فقط</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>معطل فقط</option>
                        </select>
                    </div>
                    <div class="col-md-3 col-lg-4 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary px-3 rounded-2 fw-semibold">
                            <i class="fas fa-filter me-1"></i> تصفية
                        </button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('surgical-assistants.index') }}" class="btn btn-sm btn-outline-secondary px-3 rounded-2">
                                <i class="fas fa-undo me-1"></i> إعادة تعيين
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Assistants Table -->
    <div class="card border-0 shadow-sm rounded-3 bg-white overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light table-light">
                    <tr>
                        <th style="width: 60px;" class="text-center">#</th>
                        <th>اسم مساعد الجراح</th>
                        <th>رقم الهاتف</th>
                        <th>الصفة / الملاحظات</th>
                        <th class="text-center">العمليات المسجلة</th>
                        <th class="text-center" style="width: 120px;">الحالة</th>
                        <th class="text-center" style="width: 140px;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assistants as $assistant)
                        @php
                            $opsCount = $surgeriesCounts[$assistant->name] ?? 0;
                        @endphp
                        <tr class="{{ $assistant->is_active ? '' : 'table-light opacity-75' }}">
                            <td class="text-center fw-bold text-muted font-monospace small">
                                {{ $loop->iteration + ($assistants->currentPage() - 1) * $assistants->perPage() }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-circle-sm {{ $assistant->is_active ? 'bg-primary-subtle text-primary' : 'bg-secondary-subtle text-secondary' }} rounded-circle d-flex align-items-center justify-content-center" style="width: 34px; height: 34px;">
                                        <i class="fas fa-user-md small"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $assistant->name }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($assistant->phone)
                                    <span class="font-monospace text-dark small" dir="ltr">
                                        <i class="fas fa-phone-alt text-muted me-1 small"></i>{{ $assistant->phone }}
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td>
                                @if($assistant->notes)
                                    <span class="small text-muted">{{ $assistant->notes }}</span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($opsCount > 0)
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill font-monospace">
                                        <i class="fas fa-procedures me-1"></i>{{ $opsCount }} عملية
                                    </span>
                                @else
                                    <span class="badge bg-light text-muted border px-2 py-1 rounded-pill font-monospace">
                                        0
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($canManage)
                                    <form action="{{ route('surgical-assistants.toggle-status', $assistant) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-sm py-0 px-2 rounded-pill border-0 {{ $assistant->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}" style="font-size: 0.78rem;" title="انقر لتبديل الحالة">
                                            <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i>
                                            {{ $assistant->is_active ? 'نشط' : 'معطل' }}
                                        </button>
                                    </form>
                                @else
                                    <span class="badge {{ $assistant->is_active ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">
                                        {{ $assistant->is_active ? 'نشط' : 'معطل' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-1">
                                    @if($canManage)
                                        <button type="button" class="btn btn-sm btn-outline-primary px-2 rounded-2" title="تعديل البيانات"
                                            onclick="openEditAssistantModal({{ $assistant->id }}, '{{ addslashes($assistant->name) }}', '{{ addslashes($assistant->phone ?? '') }}', '{{ addslashes($assistant->notes ?? '') }}', {{ $assistant->is_active ? 'true' : 'false' }})">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <form action="{{ route('surgical-assistants.destroy', $assistant) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف مساعد الجراح ({{ addslashes($assistant->name) }}) نهائياً؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger px-2 rounded-2" title="حذف">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fas fa-hands-helping fa-3x mb-3 text-secondary opacity-50"></i>
                                <div class="fw-bold fs-5">لا يوجد مساعدو جراحين مطابقون للبحث</div>
                                <p class="small text-muted mb-0">يمكنك إضافة مساعد جديد باستخدام زر الإضافة أعلاه.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($assistants->hasPages())
            <div class="card-footer bg-white border-0 py-3 d-flex justify-content-center">
                {{ $assistants->links() }}
            </div>
        @endif
    </div>
</div>

{{-- Modal Create Assistant --}}
@if($canManage)
<div class="modal fade" id="createAssistantModal" tabindex="-1" aria-labelledby="createAssistantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('surgical-assistants.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-primary text-white py-3">
                    <h5 class="modal-title fw-bold" id="createAssistantModalLabel">
                        <i class="fas fa-user-plus me-2"></i>إضافة مساعد جراح جديد
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="create_name" class="form-label fw-bold text-dark small">الاسم الكامل لمساعد الجراح <span class="text-danger">*</span></label>
                        <input type="text" id="create_name" name="name" class="form-control" required placeholder="مثال: د. علي عبدالله أو كادر تمريض عمليات...">
                    </div>
                    <div class="mb-3">
                        <label for="create_phone" class="form-label fw-bold text-dark small">رقم الهاتف (اختياري)</label>
                        <input type="text" id="create_phone" name="phone" class="form-control" placeholder="07xxxxxxxx">
                    </div>
                    <div class="mb-3">
                        <label for="create_notes" class="form-label fw-bold text-dark small">الصفة أو الملاحظات (اختياري)</label>
                        <textarea id="create_notes" name="notes" rows="2" class="form-control" placeholder="مثال: كادر صالة العمليات، مساعد خارجي..."></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="create_is_active" name="is_active" value="1" checked>
                        <label class="form-check-label fw-bold small text-dark" for="create_is_active">تفعيل المساعد فوراً في القوائم المنسدلة للعمليات</label>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ وإضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Assistant --}}
<div class="modal fade" id="editAssistantModal" tabindex="-1" aria-labelledby="editAssistantModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form id="editAssistantForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-dark text-white py-3">
                    <h5 class="modal-title fw-bold" id="editAssistantModalLabel">
                        <i class="fas fa-edit me-2"></i>تعديل بيانات مساعد الجراح
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label fw-bold text-dark small">الاسم الكامل لمساعد الجراح <span class="text-danger">*</span></label>
                        <input type="text" id="edit_name" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_phone" class="form-label fw-bold text-dark small">رقم الهاتف (اختياري)</label>
                        <input type="text" id="edit_phone" name="phone" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label for="edit_notes" class="form-label fw-bold text-dark small">الصفة أو الملاحظات (اختياري)</label>
                        <textarea id="edit_notes" name="notes" rows="2" class="form-control"></textarea>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" value="1">
                        <label class="form-check-label fw-bold small text-dark" for="edit_is_active">نشط في القوائم المنسدلة للعمليات</label>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4 fw-bold">حفظ التعديلات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openEditAssistantModal(id, name, phone, notes, isActive) {
        const form = document.getElementById('editAssistantForm');
        form.action = "{{ url('surgical-assistants') }}/" + id;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_phone').value = phone || '';
        document.getElementById('edit_notes').value = notes || '';
        document.getElementById('edit_is_active').checked = Boolean(isActive);

        const modal = new bootstrap.Modal(document.getElementById('editAssistantModal'));
        modal.show();
    }
</script>
@endif
@endsection

