@extends('layouts.app')

@section('content')
<div class="container-fluid px-4 py-3">
    <!-- رأس الصفحة -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <div>
            <h3 class="fw-bold mb-1 text-primary">
                <i class="fas fa-tags me-2"></i> {{ __("إعدادات أسعار وتصنيفات الفحوصات المختبرية") }}
            </h3>
            <p class="text-muted small mb-0">{{ __("تحكم سريع وشامل في أسعار الفحوصات (النقدي، الضمان، الداخلية) وتصنيفاتها بنقرة واحدة") }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('lab-tests.export', request()->query()) }}" class="btn btn-outline-success">
                <i class="fas fa-file-excel me-1"></i>{{ __("تصدير إكسل") }}</a>
            <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#importLabTestsModal">
                <i class="fas fa-file-import me-1"></i> {{ __("استيراد وتحديث إكسل") }}
            </button>
            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#quickAddCategoryModal">
                <i class="fas fa-plus me-1"></i> {{ __("إضافة تصنيف جديد") }}
            </button>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#renameCategoryModal">
                <i class="fas fa-pen-to-square me-1"></i> {{ __("تعديل اسم تصنيف") }}
            </button>
            <a href="{{ route('lab-tests.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-list me-1"></i> {{ __("دليل الفحوصات") }}
            </a>
            <button type="submit" form="labPricingForm" id="saveAllTopBtn" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-save me-1"></i> {{ __("حفظ جميع التعديلات") }}
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('warning'))
        <div class="alert alert-warning alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-exclamation-triangle me-1"></i> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
            <i class="fas fa-times-circle me-1"></i> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- بطاقة الفلاتر والبحث -->
    <div class="card shadow-sm border-0 mb-3 bg-light">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('lab-tests.pricing-settings.index') }}" class="row g-2 align-items-center">
                <div class="col-md-3">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ __("ابحث باسم التحليل أو الكود...") }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select form-select-sm">
                        <option value="">-- {{ __("جميع التصنيفات") }} ({{ $categories->count() }}) --</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="filter_type" class="form-select form-select-sm">
                        <option value="">{{ __("-- كل الحالات والأسعار --") }}</option>
                        <option value="missing_hi" {{ request('filter_type') === 'missing_hi' ? 'selected' : '' }}>{{ __("بدون سعر ضمان") }}</option>
                        <option value="missing_moi" {{ request('filter_type') === 'missing_moi' ? 'selected' : '' }}>{{ __("بدون سعر داخلية") }}</option>
                        <option value="active" {{ request('filter_type') === 'active' ? 'selected' : '' }}>{{ __("التحاليل النشطة فقط") }}</option>
                        <option value="inactive" {{ request('filter_type') === 'inactive' ? 'selected' : '' }}>{{ __("المعطلة فقط") }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="per_page" class="form-select form-select-sm">
                        <option value="25" {{ request('per_page') == '25' ? 'selected' : '' }}>{{ __("عرض 25 في الصفحة") }}</option>
                        <option value="50" {{ request('per_page', 50) == '50' ? 'selected' : '' }}>{{ __("عرض 50 في الصفحة") }}</option>
                        <option value="100" {{ request('per_page') == '100' ? 'selected' : '' }}>{{ __("عرض 100 في الصفحة") }}</option>
                        <option value="200" {{ request('per_page') == '200' ? 'selected' : '' }}>{{ __("عرض 200 في الصفحة") }}</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill">
                        <i class="fas fa-filter me-1"></i> {{ __("تصفية") }}
                    </button>
                    @if(request()->hasAny(['q', 'category', 'filter_type', 'per_page']))
                        <a href="{{ route('lab-tests.pricing-settings.index') }}" class="btn btn-outline-secondary btn-sm" title="{{ __("إعادة تعيين") }}">
                            <i class="fas fa-rotate-left"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- جدول الأسعار السريع -->
    <form id="labPricingForm" method="POST" action="{{ route('lab-tests.pricing-settings.save') }}">
        @csrf
        <input type="hidden" name="save_mode" id="saveModeInput" value="all">
        <input type="hidden" name="target_id" id="targetIdInput" value="">

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold small text-muted">
                    {{ __("إجمالي النتائج:") }} <span class="badge bg-primary rounded-pill">{{ $labTests->total() }}</span> {{ __("تحليل") }}
                </span>
                <span class="small text-muted">
                    <i class="fas fa-info-circle text-info me-1"></i> {{ __("يمكنك التعديل المباشر في الحقول ثم الضغط على \"حفظ\" للسطر أو \"حفظ جميع التعديلات\"") }}
                </span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle mb-0" id="pricingTable" style="font-size: 0.88rem;">
                    <thead class="table-light text-center">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th style="min-width: 220px;" class="text-start">{{ __("اسم التحليل والكود") }}</th>
                            <th style="min-width: 170px;">{{ __("التصنيف") }}</th>
                            <th style="min-width: 130px;">{{ __("السعر النقدي (د.ع)") }}</th>
                            <th style="min-width: 130px;">{{ __("سعر الضمان (د.ع)") }}</th>
                            <th style="min-width: 130px;">{{ __("سعر الداخلية (د.ع)") }}</th>
                            <th style="width: 90px;">{{ __("الحالة") }}</th>
                            <th style="width: 100px;">{{ __("الإجراء") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($labTests as $index => $test)
                            <tr id="test_row_{{ $test->id }}" class="test-row" data-index="{{ $index }}" data-id="{{ $test->id }}">
                                <td class="text-center text-muted small">
                                    {{ $test->id }}
                                    <input type="hidden" name="test_id[{{ $index }}]" value="{{ $test->id }}">
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $test->name }}</div>
                                    @if($test->code)
                                        <code class="small text-primary" style="font-size: 0.76rem;">{{ $test->code }}</code>
                                    @endif
                                </td>
                                <td>
                                    <select name="subcategory[{{ $index }}]" class="form-select form-select-sm category-select">
                                        <option value="">{{ __("-- بدون تصنيف --") }}</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat }}" {{ $test->subcategory === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                        @if($test->subcategory && !$categories->contains($test->subcategory))
                                            <option value="{{ $test->subcategory }}" selected>{{ $test->subcategory }}</option>
                                        @endif
                                        <option value="__ADD_NEW_CAT__" class="text-primary fw-bold">{{ __("➕ إضافة تصنيف جديد...") }}</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" 
                                           name="price[{{ $index }}]" 
                                           value="{{ $test->price !== null ? number_format($test->price, 0, '', ',') : '' }}" 
                                           class="form-control form-control-sm text-end fw-bold text-success price-input" 
                                           placeholder="0" inputmode="numeric">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="hi_price[{{ $index }}]" 
                                           value="{{ $test->hi_price !== null ? number_format($test->hi_price, 0, '', ',') : '' }}" 
                                           class="form-control form-control-sm text-end fw-bold text-primary price-input" 
                                           placeholder="0" inputmode="numeric">
                                </td>
                                <td>
                                    <input type="text" 
                                           name="moi_price[{{ $index }}]" 
                                           value="{{ $test->moi_price !== null ? number_format($test->moi_price, 0, '', ',') : '' }}" 
                                           class="form-control form-control-sm text-end fw-bold text-danger price-input" 
                                           placeholder="0" inputmode="numeric">
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" 
                                               type="checkbox" 
                                               name="is_active[{{ $index }}]" 
                                               value="1" 
                                               {{ $test->is_active ? 'checked' : '' }}
                                               title="{{ $test->is_active ? __("نشط") : __("معطل") }}">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button type="button" 
                                            class="btn btn-sm btn-primary py-1 px-3 save-row-btn" 
                                            data-id="{{ $test->id }}" 
                                            data-index="{{ $index }}"
                                            title="{{ __("حفظ تعديلات هذا السطر فقط") }}">
                                        <i class="fas fa-save me-1"></i> {{ __("حفظ") }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-search fa-2x mb-2 text-secondary opacity-50"></i>
                                    <div>{{ __("لا توجد تحاليل تطابق معايير البحث") }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="small text-muted">
                    {{ __("عرض") }} {{ $labTests->firstItem() ?? 0 }} {{ __("إلى") }} {{ $labTests->lastItem() ?? 0 }} {{ __("من أصل") }} {{ $labTests->total() }} {{ __("تحليل") }}
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="submit" form="labPricingForm" id="saveAllBottomBtn" class="btn btn-success btn-sm fw-bold shadow-sm px-3">
                        <i class="fas fa-save me-1"></i> {{ __("حفظ جميع التعديلات") }}
                    </button>
                    @if($labTests->hasPages())
                        <div>
                            {{ $labTests->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

<!-- نافذة تعديل اسم تصنيف كامل -->
<div class="modal fade" id="renameCategoryModal" tabindex="-1" aria-labelledby="renameCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('lab-tests.pricing-settings.rename-category') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fs-6" id="renameCategoryModalLabel">
                        <i class="fas fa-pen-to-square me-1"></i> {{ __("تعديل اسم تصنيف لجميع التحاليل") }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">
                        {{ __("سيقوم النظام بتغيير اسم هذا التصنيف فورياً في كافة التحاليل المسندة إليه دون التأثير على أسعارها أو نتائجها.") }}
                    </p>
                    <div class="mb-3">
                        <label for="old_category" class="form-label small fw-bold">{{ __("اختر التصنيف الحالي:") }}</label>
                        <select name="old_category" id="old_category" class="form-select" required>
                            <option value="">{{ __("-- اختر التصنيف المراد تعديله --") }}</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="new_category" class="form-label small fw-bold">{{ __("الاسم الجديد للتصنيف:") }}</label>
                        <input type="text" name="new_category" id="new_category" class="form-control" placeholder="{{ __("أدخل التسمية الجديدة المعتمدة...") }}" required>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __("إلغاء") }}</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-check me-1"></i> {{ __("تحديث اسم التصنيف") }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- نافذة إضافة تصنيف جديد سريع -->
<div class="modal fade" id="quickAddCategoryModal" tabindex="-1" aria-labelledby="quickAddCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title fs-6" id="quickAddCategoryModalLabel">
                    <i class="fas fa-plus-circle me-1"></i> {{ __("إضافة تصنيف جديد") }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="mb-2">
                    <label for="quickCategoryNameInput" class="form-label small fw-bold">{{ __("اسم التصنيف الجديد:") }}</label>
                    <input type="text" id="quickCategoryNameInput" class="form-control form-control-sm" placeholder="{{ __("مثال: الهرمونات، كيمياء الدم...") }}" required>
                </div>
                <div class="small text-muted" style="font-size: 0.78rem;">
                    {{ __("سيُضاف هذا التصنيف فورياً إلى القائمة المنسدلة لجميع السطور.") }}
                </div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{ __("إلغاء") }}</button>
                <button type="button" id="saveQuickCategoryBtn" class="btn btn-success btn-sm fw-bold">
                    <i class="fas fa-plus me-1"></i> {{ __("إضافة للتصنيفات") }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- نافذة استيراد وتحديث الأسعار من ملف إكسل -->
<div class="modal fade" id="importLabTestsModal" tabindex="-1" aria-labelledby="importLabTestsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('lab-tests.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-content shadow-lg border-0 rounded-4">
                <div class="modal-header bg-info text-white py-3 rounded-top-4">
                    <h5 class="modal-title fs-6 fw-bold" id="importLabTestsModalLabel">
                        <i class="fas fa-file-excel me-2"></i> {{ __("استيراد وتحديث أسعار التحاليل من ملف إكسل") }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-light border border-info-subtle small mb-3 rounded-3">
                        <div class="fw-bold mb-1 text-primary"><i class="fas fa-info-circle me-1"></i>{{ __("آلية المطابقة والتحديث الذكي:") }}</div>
                        <ul class="mb-0 ps-3 text-muted">
                            <li>{{ __("يمكنك تصدير الجدول الحالي أولاً عبر زر") }}<strong>{{ __("تصدير إكسل") }}</strong>{{ __("وتعديل الأسعار فيه ثم إعادة رفعه هنا.") }}</li>
                            <li>{{ __("يقوم النظام بمطابقة التحاليل تلقائياً برقم") }}<strong>{{ __("المعرف (# ID)") }}</strong>{{ __("أو") }}<strong>{{ __("الرمز الطبي (Code)") }}</strong>{{ __("أو الاسم.") }}</li>
                            <li>{{ __("يتم تحديث: سعر الكاش، سعر الضمان، حالة شمول الضمان، وحالة التفعيل (نشط/معطل).") }}</li>
                            <li>{{ __("الصيغ المدعومة:") }}<code>.xlsx</code>, <code>.xls</code>, <code>.csv</code>{{ __("(الحد الأقصى: 10 ميغابايت).") }}</li>
                        </ul>
                    </div>

                    <div class="mb-3">
                        <label for="excel_file_lab" class="form-label small fw-bold text-dark">
                            <i class="fas fa-cloud-arrow-up me-1 text-primary"></i> {{ __("اختر ملف الإكسل المحدث:") }}
                        </label>
                        <input type="file" name="excel_file" id="excel_file_lab" class="form-control rounded-3" accept=".xlsx,.xls,.csv" required>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2 rounded-bottom-4">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">{{ __("إلغاء") }}</button>
                    <button type="submit" class="btn btn-info text-white btn-sm fw-bold rounded-pill px-4 shadow-sm">
                        <i class="fas fa-upload me-1"></i> {{ __("بدء الاستيراد والتحديث") }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تنسيق فواصل الآلاف
    function formatWithCommas(val) {
        if (!val && val !== 0) return '';
        let str = val.toString().trim();
        if (str.includes('.')) str = str.split('.')[0];
        const digits = str.replace(/[^0-9]/g, '');
        if (!digits) return '';
        return parseInt(digits, 10).toLocaleString('en-US');
    }

    $(document).on('input', '.price-input', function() {
        const pos = this.selectionStart;
        const len = this.value.length;
        this.value = formatWithCommas(this.value);
        const diff = this.value.length - len;
        this.setSelectionRange(pos + diff, pos + diff);
    });

    // إدارة إضافة تصنيف جديد ديناميكياً
    let activeCategorySelectForNew = null;
    const quickAddCategoryModalEl = document.getElementById('quickAddCategoryModal');
    let quickAddModalInstance = null;
    if (quickAddCategoryModalEl) {
        quickAddModalInstance = new bootstrap.Modal(quickAddCategoryModalEl);
    }

    $(document).on('change', '.category-select', function() {
        if ($(this).val() === '__ADD_NEW_CAT__') {
            activeCategorySelectForNew = $(this);
            $('#quickCategoryNameInput').val('');
            if (quickAddModalInstance) {
                quickAddModalInstance.show();
            }
        }
    });

    $('#quickAddCategoryModal').on('hidden.bs.modal', function() {
        if (activeCategorySelectForNew && activeCategorySelectForNew.val() === '__ADD_NEW_CAT__') {
            activeCategorySelectForNew.val('');
        }
        activeCategorySelectForNew = null;
    });

    $('#saveQuickCategoryBtn').on('click', function() {
        const newCatName = $('#quickCategoryNameInput').val().trim();
        if (!newCatName) {
            alert('{{ __('يرجى كتابة اسم التصنيف الجديد') }}');
            return;
        }

        // إضافة التصنيف الجديد لكافة القوائم المنسدلة في الصفحة
        $('.category-select').each(function() {
            let exists = false;
            $(this).find('option').each(function() {
                if ($(this).val() === newCatName) exists = true;
            });
            if (!exists) {
                $(this).find('option[value="__ADD_NEW_CAT__"]').before(
                    $('<option>', { value: newCatName, text: newCatName })
                );
            }
        });

        // وإضافته أيضاً لقائمة الفلتر بالأعلى إذا لم يكن موجوداً
        const $filterCat = $('select[name="category"]');
        if ($filterCat.find(`option[value="${newCatName}"]`).length === 0) {
            $filterCat.append($('<option>', { value: newCatName, text: newCatName }));
        }

        // تحديد الخيار الجديد للسطر الذي طلبه
        if (activeCategorySelectForNew) {
            activeCategorySelectForNew.val(newCatName);
        }

        if (quickAddModalInstance) {
            quickAddModalInstance.hide();
        }
        activeCategorySelectForNew = null;
    });

    // حفظ سطر فردي عبر AJAX
    $(document).on('click', '.save-row-btn', function(e) {
        e.preventDefault();
        const $btn = $(this);
        const testId = $btn.data('id');
        const index = $btn.data('index');
        const $row = $(`#test_row_${testId}`);

        const originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        const formData = {
            _token: '{{ csrf_token() }}',
            save_mode: 'row',
            target_id: testId,
            test_id: { [index]: testId },
            price: { [index]: $row.find(`input[name="price[${index}]"]`).val() },
            hi_price: { [index]: $row.find(`input[name="hi_price[${index}]"]`).val() },
            moi_price: { [index]: $row.find(`input[name="moi_price[${index}]"]`).val() },
            subcategory: { [index]: $row.find(`select[name="subcategory[${index}]"]`).val() },
        };

        if ($row.find(`input[name="is_active[${index}]"]`).is(':checked')) {
            formData.is_active = { [index]: 1 };
        }

        $.ajax({
            url: '{{ route("lab-tests.pricing-settings.save") }}',
            method: 'POST',
            data: formData,
            success: function(res) {
                $btn.removeClass('btn-primary').addClass('btn-success').html('<i class="fas fa-check"></i> {{ __("تم") }}');
                $row.addClass('table-success');
                setTimeout(function() {
                    $row.removeClass('table-success');
                    $btn.removeClass('btn-success').addClass('btn-primary').html(originalHtml).prop('disabled', false);
                }, 1500);
            },
            error: function(xhr) {
                $btn.removeClass('btn-primary').addClass('btn-danger').html('<i class="fas fa-times"></i> {{ __("فشل") }}');
                alert(xhr.responseJSON?.message || '{{ __('تعذر حفظ السطر، يرجى المحاولة ثانية') }}');
                setTimeout(function() {
                    $btn.removeClass('btn-danger').addClass('btn-primary').html(originalHtml).prop('disabled', false);
                }, 2000);
            }
        });
    });

    // حفظ الكل
    $('#saveAllTopBtn, #saveAllBottomBtn').on('click', function(e) {
        if (!confirm('{{ __('هل تريد بالتأكيد حفظ جميع تعديلات الأسعار والتصنيفات في هذه الصفحة؟') }}')) {
            e.preventDefault();
            return false;
        }
        $('#saveModeInput').val('all');
        $('#targetIdInput').val('');
    });
});
</script>
@endpush
@endsection
