@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-edit me-2"></i>تحرير المجموعة: {{ $group->name }}</h2>
                <p class="text-muted mb-0">{{ $group->description ?? 'بدون وصف' }}</p>
            </div>
            <a href="{{ route('lab-tests.groups.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> العودة للمجموعات
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('lab-tests.groups.update', $group) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                <input type="text" class="form-control" id="groupEditSearch" placeholder="ابحث عن تحليل بالاسم أو الكود...">
                <button type="button" class="btn btn-outline-secondary" id="groupEditClearSearch"><i class="fas fa-times"></i></button>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-outline-success" id="groupEditSelectAll">تحديد الكل</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="groupEditClearAll">إلغاء التحديد</button>
                <span id="groupEditSelectedCount" class="text-muted small ms-auto">تم تحديد {{ count($selectedTestIds) }} تحليل</span>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    @foreach($labTests as $category => $tests)
                        <div class="col-12 group-edit-category">
                            <div class="card border rounded mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">{{ $category }}</h5>
                                    <span class="badge bg-secondary">{{ $tests->count() }} تحليل</span>
                                </div>
                                <div class="card-body row g-2">
                                    @foreach($tests as $test)
                                        <div class="col-md-6 col-lg-4 group-edit-test-item">
                                            <div class="form-check p-2 border rounded h-100">
                                                <input class="form-check-input group-edit-test-checkbox" type="checkbox" name="lab_test_ids[]" value="{{ $test->id }}" id="group_test_{{ $test->id }}" {{ in_array($test->id, $selectedTestIds) ? 'checked' : '' }}>
                                                <label class="form-check-label d-block" for="group_test_{{ $test->id }}">
                                                    <strong>{{ $test->name }}</strong>
                                                    <div class="text-muted small">{{ $test->code }}</div>
                                                </label>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i> حفظ تحاليل المجموعة
            </button>
            <span id="groupEditSavedCount" class="text-muted">تم تحديد {{ count($selectedTestIds) }} تحليل في المجموعة</span>
        </div>
    </form>
</div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('groupEditSearch');
            const clearSearch = document.getElementById('groupEditClearSearch');
            const selectAll = document.getElementById('groupEditSelectAll');
            const clearAll = document.getElementById('groupEditClearAll');
            const selectedCount = document.getElementById('groupEditSelectedCount');
            const savedCount = document.getElementById('groupEditSavedCount');
            const testItems = document.querySelectorAll('.group-edit-test-item');
            const checkboxes = document.querySelectorAll('.group-edit-test-checkbox');
            const categories = document.querySelectorAll('.group-edit-category');

            function updateSelectedCount() {
                const count = document.querySelectorAll('.group-edit-test-checkbox:checked').length;
                selectedCount.textContent = `تم تحديد ${count} تحليل`;
                savedCount.textContent = `تم تحديد ${count} تحليل في المجموعة`;
            }

            function filterTests() {
                const term = searchInput.value.trim().toLowerCase();
                categories.forEach(function(category) {
                    let visibleInCategory = false;
                    category.querySelectorAll('.group-edit-test-item').forEach(function(item) {
                        const text = item.textContent.toLowerCase();
                        const visible = term === '' || text.includes(term);
                        item.style.display = visible ? '' : 'none';
                        if (visible) visibleInCategory = true;
                    });
                    category.style.display = visibleInCategory ? '' : 'none';
                });
            }

            searchInput.addEventListener('input', filterTests);
            clearSearch.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.focus();
                filterTests();
            });

            selectAll.addEventListener('click', function() {
                testItems.forEach(function(item) {
                    if (item.style.display !== 'none') {
                        const checkbox = item.querySelector('.group-edit-test-checkbox');
                        if (checkbox) checkbox.checked = true;
                    }
                });
                updateSelectedCount();
            });

            clearAll.addEventListener('click', function() {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
                updateSelectedCount();
            });

            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', updateSelectedCount);
            });

            updateSelectedCount();
        });
    </script>
@endsection
