{{-- قسم المستندات والمرفقات المتعددة للعمليات الجراحية (سكانر USB + رفع ملفات + تحكم بالقائمة المنسدلة) --}}
@php
    $isEditMode = isset($surgery) && $surgery->id;
    $hasDocsTable = \Illuminate\Support\Facades\Schema::hasTable('surgery_documents');
    $existingDocs = ($isEditMode && $hasDocsTable) ? ($surgery->documents ?? collect()) : collect();
    $hasTypesTable = \Illuminate\Support\Facades\Schema::hasTable('surgery_document_types');
    $docTypesList = $documentTypes ?? ($hasTypesTable ? \App\Models\SurgeryDocumentType::active()->get() : collect());
@endphp

<div class="card border-0 shadow-2xs rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0 !important;">
    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between rounded-top-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-circle p-1 bg-primary text-white" style="width:24px;height:24px;display:flex;align-items:center;justify-content:center;">
                <i class="fas fa-file-medical" style="font-size:11px;"></i>
            </span>
            <span class="fw-bold text-dark small" style="font-size: 0.88rem;">{{ __('مستندات ومرفقات العملية الجراحية') }}</span>
            @if($isEditMode && $existingDocs->count() > 0)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-0" id="existingDocsBadge" style="font-size: 0.72rem;">
                    {{ $existingDocs->count() }} {{ __('مستند') }}
                </span>
            @endif
        </div>
        <div class="d-flex align-items-center gap-1">
            <!-- زر إدارة القائمة المنسدلة -->
            <button type="button" class="btn btn-xs btn-outline-secondary px-2 rounded-2" onclick="openDocumentTypesModal()" title="{{ __('إدارة وتعديل خيارات القائمة المنسدلة لأنواع المستندات') }}" style="font-size: 0.73rem; height: 26px;">
                <i class="fas fa-cog me-1 text-primary"></i> {{ __('إدارة القائمة') }}
            </button>
            <!-- زر إضافة مستند جديد -->
            <button type="button" class="btn btn-xs btn-primary px-2 rounded-2 fw-bold shadow-xs" onclick="addSurgeryDocumentRow()" style="font-size: 0.73rem; height: 26px;">
                <i class="fas fa-plus me-1"></i> {{ __('إضافة مستند') }}
            </button>
        </div>
    </div>

    <div class="card-body p-3">
        {{-- 1. في وضع التعديل: عرض المستندات المرفوعة مسبقاً --}}
        @if($isEditMode)
            <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <span class="text-muted small fw-bold" style="font-size: 0.76rem;">{{ __('المستندات المحفوظة مسبقاً:') }}</span>
                </div>
                <div class="d-flex flex-column gap-2" id="existingDocumentsList">
                    @forelse($existingDocs as $doc)
                        <div class="border rounded-2 p-2 bg-white d-flex align-items-center justify-content-between shadow-2xs" id="surgery-doc-item-{{ $doc->id }}" style="border-color: #e2e8f0 !important;">
                            <div class="d-flex align-items-center gap-2 overflow-hidden me-2">
                                @if($doc->is_pdf)
                                    <span class="badge bg-danger-subtle text-danger p-2 rounded-2 flex-shrink-0">
                                        <i class="fas fa-file-pdf fa-lg"></i>
                                    </span>
                                @elseif($doc->is_image)
                                    <span class="badge bg-primary-subtle text-primary p-2 rounded-2 flex-shrink-0">
                                        <i class="fas fa-file-image fa-lg"></i>
                                    </span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary p-2 rounded-2 flex-shrink-0">
                                        <i class="fas fa-file-alt fa-lg"></i>
                                    </span>
                                @endif
                                <div class="text-truncate">
                                    <div class="fw-bold text-dark text-truncate small" title="{{ $doc->title }}" style="font-size: 0.82rem;">
                                        {{ $doc->title }}
                                    </div>
                                    <div class="text-muted d-flex align-items-center gap-1 flex-wrap" style="font-size: 0.7rem;">
                                        <span class="badge bg-light text-secondary border px-1 py-0">{{ $doc->category_name }}</span>
                                        <span>• {{ $doc->formatted_size }}</span>
                                        <span>• {{ $doc->created_at ? $doc->created_at->format('Y/m/d') : '-' }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-1 flex-shrink-0">
                                <a href="{{ route('surgeries.documents.view', [$surgery->id, $doc->id]) }}" target="_blank" class="btn btn-xs btn-outline-primary px-2" title="{{ __('معاينة المستند') }}" style="height: 26px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-xs btn-outline-danger px-2" onclick="deleteSurgeryDocument({{ $surgery->id }}, {{ $doc->id }})" title="{{ __('حذف المستند') }}" style="height: 26px;">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-2 px-3 border border-dashed rounded-2 bg-white text-muted small" id="noExistingDocsMsg" style="font-size: 0.78rem;">
                            <i class="fas fa-info-circle me-1 text-primary"></i> {{ __('لا توجد مستندات مرفوعة مسبقاً لهذه العملية') }}
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- 2. قسم إضافة مستندات جديدة (ديناميكي) --}}
        <div>
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="text-dark small fw-bold" style="font-size: 0.78rem;">
                    <i class="fas fa-paperclip text-success me-1"></i> {{ __('إضافة مستندات ومرفقات جديدة:') }}
                </span>
                <span id="newDocsCountBadge" class="badge bg-secondary-subtle text-secondary py-0 px-2" style="font-size: 0.7rem;">
                    0 مستند جديد
                </span>
            </div>

            <!-- حاوية الصفوف الديناميكية -->
            <div id="surgeryDocumentsContainer" class="d-flex flex-column gap-2 mb-2">
                <!-- الصفوف تضاف هنا برمجياً عبر الجافاسكربت -->
            </div>

            <!-- زر إضافة مستند مباشر في حال كانت القائمة فارغة -->
            <div id="emptyNewDocsNotice" class="text-center py-3 px-2 border border-dashed rounded-3 bg-white mb-2" style="border-color: #cbd5e1 !important;">
                <i class="fas fa-cloud-upload-alt text-muted fa-2x mb-2 d-block"></i>
                <div class="small text-muted mb-2">{{ __('يمكنك إضافة عدة مستندات وموافقات وتقارير للعملية وتسميتها واختيار نوعها') }}</div>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-2xs" onclick="addSurgeryDocumentRow()">
                    <i class="fas fa-plus me-1"></i> {{ __('انقر هنا لإضافة مستند جديد') }}
                </button>
            </div>

            <!-- شريط حالة السكانر والكشف المباشر -->
            <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-white border small text-muted" style="font-size: 0.72rem; border-color: #e2e8f0 !important;">
                <div class="d-flex align-items-center gap-1">
                    <span id="scannerStatusIndicator" class="badge bg-light text-secondary border py-0 px-2">
                        <i class="fas fa-circle text-secondary me-1" style="font-size: 7px;"></i>{{ __('حالة السكانر: غير مفحوص') }}
                    </span>
                </div>
                <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none fw-bold" id="detect_scanner_btn" onclick="detectScannerDevice(true)">
                    <i class="fas fa-sync-alt me-1" id="detect_spinner"></i>{{ __('فحص اتصال السكانر') }}
                </button>
            </div>
        </div>
    </div>
</div>

<!-- مودال إدارة قائمة أنواع المستندات (Manage Document Types Modal) -->
<div class="modal fade" id="manageDocumentTypesModal" tabindex="-1" aria-labelledby="manageDocumentTypesModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header py-2 px-3 border-bottom bg-light">
                <h6 class="modal-title fw-bold text-dark" id="manageDocumentTypesModalLabel">
                    <i class="fas fa-cog text-primary me-1"></i> {{ __('إدارة القائمة المنسدلة لأنواع مستندات العمليات') }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <!-- نموذج إضافة نوع جديد -->
                <div class="mb-3 p-2 bg-light rounded-3 border">
                    <label class="form-label small fw-bold text-dark mb-1">{{ __('إضافة نوع مستند جديد للقائمة:') }}</label>
                    <div class="input-group input-group-sm">
                        <input type="text" id="newDocTypeNameInput" class="form-control" placeholder="{{ __('اكتب نوع المستند (مثال: فحص وظائف الكلى، تقرير خروج...)') }}" onkeypress="if(event.key === 'Enter') { event.preventDefault(); submitNewDocType(); }">
                        <button type="button" class="btn btn-primary fw-bold px-3" id="btnAddDocType" onclick="submitNewDocType()">
                            <i class="fas fa-plus me-1"></i> {{ __('إضافة') }}
                        </button>
                    </div>
                    <div id="addDocTypeError" class="text-danger small mt-1 d-none"></div>
                </div>

                <!-- قائمة الأنواع الحالية -->
                <label class="form-label small fw-bold text-muted mb-1">{{ __('الأنواع المتاحة حالياً في القائمة المنسدلة:') }}</label>
                <div id="docTypesListContainer" class="d-flex flex-column gap-1 border rounded-2 p-2 bg-white" style="max-height: 260px; overflow-y: auto;">
                    <div class="text-center py-2 text-muted small">
                        <i class="fas fa-spinner fa-spin me-1"></i> {{ __('جاري تحميل القائمة...') }}
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-white">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- مودال معاينة الوثيقة الممسوحة أو المرفوعة (Document Full Preview Modal) -->
<div class="modal fade" id="documentPreviewModal" tabindex="-1" aria-labelledby="documentPreviewModalLabel" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0 rounded-4">
            <div class="modal-header py-2 px-3 border-bottom">
                <h6 class="modal-title fw-bold text-dark" id="documentPreviewModalLabel">
                    <i class="fas fa-eye text-primary me-1"></i> {{ __('معاينة المستند المرفق') }}
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2 text-center bg-light" style="min-height: 300px; max-height: 75vh; overflow-y: auto;">
                <img id="documentPreviewImg" src="" alt="{{ __('معاينة المستند') }}" class="img-fluid rounded shadow-sm border" style="max-height: 70vh; display: none;">
                <div id="documentPdfPreview" class="alert alert-info my-4" style="display: none;">
                    <i class="fas fa-file-pdf fa-3x mb-2 d-block text-danger"></i>
                    <strong class="d-block mb-1">{{ __('ملف PDF مرفوع:') }}</strong>
                    <span id="documentPdfName" class="badge bg-white text-dark border px-2 py-1">-</span>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-white">
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">{{ __('إغلاق') }}</button>
            </div>
        </div>
    </div>
</div>

{{-- سكريبت إدارة المستندات المتعددة والسكانر والأنواع --}}
<script>
(function() {
    // قائمة أنواع المستندات محملة مسبقاً من الباكند
    window.surgeryDocTypes = @json($docTypesList->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));
    let docRowCounter = 0;

    // 1. فتح نافذة إدارة أنواع المستندات
    window.openDocumentTypesModal = function() {
        const modalEl = document.getElementById('manageDocumentTypesModal');
        if (!modalEl) return;
        renderDocTypesInModal();
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    };

    // 2. رسم قائمة الأنواع داخل المودال
    window.renderDocTypesInModal = function() {
        const container = document.getElementById('docTypesListContainer');
        if (!container) return;

        if (!window.surgeryDocTypes || window.surgeryDocTypes.length === 0) {
            container.innerHTML = '<div class="text-center py-2 text-muted small">لا توجد أنواع مستندات حالياً. يمكنك إضافة نوع جديد أعلاه.</div>';
            return;
        }

        let html = '';
        window.surgeryDocTypes.forEach(type => {
            html += `
                <div class="d-flex align-items-center justify-content-between p-1 px-2 border rounded bg-light small" id="docTypeItem_${type.id}">
                    <div class="d-flex align-items-center gap-2 text-truncate">
                        <i class="fas fa-tag text-primary" style="font-size: 11px;"></i>
                        <span class="fw-bold text-dark text-truncate">${type.name}</span>
                    </div>
                    <button type="button" class="btn btn-link btn-xs text-danger p-0 text-decoration-none" onclick="deleteDocType(${type.id})" title="حذف هذا النوع من القائمة">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </div>
            `;
        });
        container.innerHTML = html;
    };

    // 3. إضافة نوع مستند جديد عبر AJAX
    window.submitNewDocType = function() {
        const input = document.getElementById('newDocTypeNameInput');
        const errDiv = document.getElementById('addDocTypeError');
        const btn = document.getElementById('btnAddDocType');
        if (!input) return;

        const name = input.value.trim();
        if (!name) {
            if (errDiv) {
                errDiv.textContent = 'يرجى كتابة اسم نوع المستند أولاً.';
                errDiv.classList.remove('d-none');
            }
            return;
        }
        if (errDiv) errDiv.classList.add('d-none');
        if (btn) btn.disabled = true;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          document.querySelector('input[name="_token"]')?.value;

        fetch('{{ route("surgery-document-types.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ name: name })
        })
        .then(res => res.json())
        .then(data => {
            if (btn) btn.disabled = false;
            if (data.success && data.type) {
                input.value = '';
                // إضافة النوع للمصفوفة المحلية إذا لم يكن موجوداً
                const exists = window.surgeryDocTypes.some(t => t.id === data.type.id);
                if (!exists) {
                    window.surgeryDocTypes.push({ id: data.type.id, name: data.type.name });
                }
                renderDocTypesInModal();
                updateAllDocTypeSelects();
                if (window.toastr) {
                    toastr.success('تمت إضافة نوع المستند بنجاح: ' + data.type.name);
                }
            } else {
                if (errDiv) {
                    errDiv.textContent = data.message || 'حدث خطأ أثناء حفظ نوع المستند.';
                    errDiv.classList.remove('d-none');
                }
            }
        })
        .catch(err => {
            if (btn) btn.disabled = false;
            console.error('Error saving doc type:', err);
            if (errDiv) {
                errDiv.textContent = 'تعذر الاتصال بالخادم لحفظ نوع المستند.';
                errDiv.classList.remove('d-none');
            }
        });
    };

    // 4. حذف نوع مستند عبر AJAX
    window.deleteDocType = function(typeId) {
        if (!confirm('هل أنت متأكد من حذف/تعطيل هذا النوع من القائمة المنسدلة؟')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          document.querySelector('input[name="_token"]')?.value;

        fetch('{{ url("surgery-document-types") }}/' + typeId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.surgeryDocTypes = window.surgeryDocTypes.filter(t => t.id !== typeId);
                renderDocTypesInModal();
                updateAllDocTypeSelects();
                if (window.toastr) {
                    toastr.info('تم حذف نوع المستند من القائمة');
                }
            }
        })
        .catch(err => {
            console.error('Error deleting doc type:', err);
        });
    };

    // 5. تحديث كافة القوائم المنسدلة للأنواع في الصفحة
    window.updateAllDocTypeSelects = function() {
        document.querySelectorAll('.doc-type-select').forEach(select => {
            const currentVal = select.value;
            let optionsHtml = '<option value="">-- اختر نوع المستند من القائمة --</option>';
            window.surgeryDocTypes.forEach(t => {
                const selected = (String(t.id) === String(currentVal)) ? 'selected' : '';
                optionsHtml += `<option value="${t.id}" data-name="${t.name}" ${selected}>${t.name}</option>`;
            });
            optionsHtml += '<option value="custom" ' + (currentVal === 'custom' ? 'selected' : '') + '>-- نوع آخر (مخصص) --</option>';
            select.innerHTML = optionsHtml;
        });
    };

    // 6. إضافة سطر مستند ديناميكي جديد
    window.addSurgeryDocumentRow = function() {
        docRowCounter++;
        const rowIndex = docRowCounter;
        const container = document.getElementById('surgeryDocumentsContainer');
        const emptyNotice = document.getElementById('emptyNewDocsNotice');
        if (emptyNotice) emptyNotice.style.display = 'none';

        let typeOptions = '<option value="">-- اختر نوع المستند من القائمة --</option>';
        window.surgeryDocTypes.forEach(t => {
            typeOptions += `<option value="${t.id}" data-name="${t.name}">${t.name}</option>`;
        });
        typeOptions += '<option value="custom">-- نوع آخر (مخصص) --</option>';

        const rowHtml = `
            <div class="border rounded-3 p-2 bg-white shadow-2xs position-relative surgery-doc-row" id="docRow_${rowIndex}" style="border: 1px solid #cbd5e1 !important;">
                <div class="d-flex align-items-center justify-content-between pb-1 mb-1 border-bottom">
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-0" style="font-size: 0.72rem;">
                        <i class="fas fa-file-medical text-primary me-1"></i> مستند جديد #<span class="doc-row-number">${document.querySelectorAll('.surgery-doc-row').length + 1}</span>
                    </span>
                    <button type="button" class="btn btn-link btn-xs text-danger p-0 text-decoration-none fw-bold" onclick="removeSurgeryDocumentRow(${rowIndex})" title="إلغاء هذا المستند" style="font-size: 0.75rem;">
                        <i class="fas fa-times me-1"></i> إلغاء
                    </button>
                </div>
                <div class="row g-2 align-items-center">
                    <!-- نوع المستند المنسدل -->
                    <div class="col-12 col-md-5">
                        <label class="form-label text-muted mb-0 small fw-bold" style="font-size: 0.72rem;">نوع المستند (قائمة منسدلة):</label>
                        <select name="surgery_documents[${rowIndex}][document_type_id]" id="docTypeSelect_${rowIndex}" class="form-select form-select-sm doc-type-select" onchange="onDocTypeChanged(${rowIndex}, this)" style="font-size: 0.8rem; height: 32px;">
                            ${typeOptions}
                        </select>
                    </div>
                    <!-- اسم / وصف المستند -->
                    <div class="col-12 col-md-7">
                        <label class="form-label text-muted mb-0 small fw-bold" style="font-size: 0.72rem;">اسم / وصف المستند المرفوع:</label>
                        <input type="text" name="surgery_documents[${rowIndex}][title]" id="docTitleInput_${rowIndex}" class="form-control form-control-sm doc-title-input" placeholder="اسم المستند (مثال: تقرير إيكو سابق)" style="font-size: 0.8rem; height: 32px;">
                    </div>
                    <!-- أدوات الإرفاق (رفع وسكانر ومعاينة) -->
                    <div class="col-12 pt-1 border-top mt-2">
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <input type="file" name="surgery_documents[${rowIndex}][file]" id="docFileInput_${rowIndex}" class="d-none doc-file-input" accept="image/*,application/pdf" onchange="onDocFileChosen(${rowIndex}, this)">
                            <textarea name="surgery_documents[${rowIndex}][scanned_data]" id="docScanInput_${rowIndex}" class="d-none"></textarea>

                            <button type="button" class="btn btn-xs btn-outline-secondary px-2" onclick="document.getElementById('docFileInput_${rowIndex}').click()" style="height: 28px; font-size: 0.75rem;" title="رفع ملف من الجهاز (صورة أو PDF)">
                                <i class="fas fa-upload me-1 text-primary"></i> رفع ملف
                            </button>
                            <button type="button" class="btn btn-xs btn-outline-primary px-2" id="docScanBtn_${rowIndex}" onclick="scanForDocumentRow(${rowIndex})" style="height: 28px; font-size: 0.75rem;" title="مسح وسحب من السكانر المتصل بالحاسبة">
                                <i class="fas fa-print me-1"></i> مسح بالسكانر
                            </button>

                            <span id="docStatusBadge_${rowIndex}" class="badge bg-warning-subtle text-warning border border-warning-subtle py-1 px-2 text-truncate" style="font-size: 0.72rem; max-width: 220px;">
                                <i class="fas fa-exclamation-triangle me-1"></i> لم يتم اختيار ملف بعد
                            </span>

                            <button type="button" class="btn btn-link btn-xs text-primary p-0 text-decoration-none fw-bold d-none ms-1" id="docPreviewBtn_${rowIndex}" onclick="previewDocumentRow(${rowIndex})" style="font-size: 0.75rem;">
                                <i class="fas fa-eye me-1"></i> معاينة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);
        updateNewDocsCount();
    };

    // 7. إزالة سطر مستند
    window.removeSurgeryDocumentRow = function(rowIndex) {
        const row = document.getElementById(`docRow_${rowIndex}`);
        if (row) {
            row.remove();
        }
        updateNewDocsCount();
        const container = document.getElementById('surgeryDocumentsContainer');
        const emptyNotice = document.getElementById('emptyNewDocsNotice');
        if (container && container.children.length === 0 && emptyNotice) {
            emptyNotice.style.display = 'block';
        }
    };

    function updateNewDocsCount() {
        const count = document.querySelectorAll('.surgery-doc-row').length;
        const badge = document.getElementById('newDocsCountBadge');
        if (badge) {
            badge.innerText = `${count} مستند جديد`;
        }
        // تحديث أرقام الصفوف
        document.querySelectorAll('.surgery-doc-row').forEach((row, idx) => {
            const numSpan = row.querySelector('.doc-row-number');
            if (numSpan) numSpan.innerText = idx + 1;
        });
    }

    // 8. عند تغيير نوع المستند من القائمة المنسدلة
    window.onDocTypeChanged = function(rowIndex, select) {
        const titleInput = document.getElementById(`docTitleInput_${rowIndex}`);
        if (!titleInput) return;

        const selectedOption = select.options[select.selectedIndex];
        const typeName = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text) : '';

        if (select.value === 'custom') {
            titleInput.placeholder = 'اكتب اسم المستند المخصص هنا...';
            titleInput.focus();
        } else if (typeName && select.value !== '') {
            // إذا كان حقل الاسم فارغاً أو مساوياً لاسم افتراضي سابق، نحدثه تلقائياً
            if (!titleInput.value.trim() || select.previousTypeName === titleInput.value.trim()) {
                titleInput.value = typeName;
            }
            select.previousTypeName = typeName;
        }
    };

    // 9. عند اختيار ملف من الجهاز
    window.onDocFileChosen = function(rowIndex, input) {
        const badge = document.getElementById(`docStatusBadge_${rowIndex}`);
        const previewBtn = document.getElementById(`docPreviewBtn_${rowIndex}`);
        const scanInput = document.getElementById(`docScanInput_${rowIndex}`);
        if (scanInput) scanInput.value = ''; // تفريغ السكانر إذا اختار ملف عادي

        if (input.files && input.files[0]) {
            const file = input.files[0];
            const sizeStr = (file.size < 1048576) ? (Math.round(file.size / 1024) + ' KB') : ((file.size / 1048576).toFixed(1) + ' MB');
            if (badge) {
                badge.className = 'badge bg-success-subtle text-success border border-success-subtle py-1 px-2 text-truncate';
                badge.innerHTML = `<i class="fas fa-check-circle me-1"></i> ${file.name} (${sizeStr})`;
            }
            if (previewBtn) previewBtn.classList.remove('d-none');

            // إذا كان اسم المستند فارغاً، نضع اسم الملف كافتراضي
            const titleInput = document.getElementById(`docTitleInput_${rowIndex}`);
            if (titleInput && !titleInput.value.trim()) {
                const cleanName = file.name.replace(/\.[^/.]+$/, "");
                titleInput.value = cleanName;
            }
        }
    };

    // 10. سحب من السكانر للسطر المحدد
    window.scanForDocumentRow = async function(rowIndex) {
        const btn = document.getElementById(`docScanBtn_${rowIndex}`);
        const badge = document.getElementById(`docStatusBadge_${rowIndex}`);
        const scanInput = document.getElementById(`docScanInput_${rowIndex}`);
        const fileInput = document.getElementById(`docFileInput_${rowIndex}`);
        const previewBtn = document.getElementById(`docPreviewBtn_${rowIndex}`);

        if (fileInput) fileInput.value = ''; // تفريغ الملف العادي

        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> مسح...';

        const endpoints = [
            'http://127.0.0.1:5000/scan',
            'http://127.0.0.1:37426/scan',
            'http://localhost:5000/scan',
            'http://localhost:37426/scan'
        ];
        let response = null;
        let lastError = null;

        for (const url of endpoints) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 12000);
                const res = await fetch(url, { method: 'POST', mode: 'cors', signal: controller.signal });
                clearTimeout(timeoutId);
                if (res.ok) {
                    response = res;
                    break;
                }
            } catch (e) {
                lastError = e;
            }
        }

        try {
            if (!response) {
                throw lastError || new Error('تعذر الاتصال ببرنامج السكانر المحلي');
            }
            const blob = await response.blob();
            const reader = new FileReader();
            reader.onloadend = function() {
                const base64data = reader.result;
                if (scanInput) scanInput.value = base64data;
                if (badge) {
                    badge.className = 'badge bg-success text-white py-1 px-2';
                    badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> تم السحب بالسكانر بنجاح';
                }
                if (previewBtn) previewBtn.classList.remove('d-none');

                const titleInput = document.getElementById(`docTitleInput_${rowIndex}`);
                if (titleInput && !titleInput.value.trim()) {
                    titleInput.value = 'مستند ممسوح بالسكانر';
                }
            };
            reader.readAsDataURL(blob);
        } catch (err) {
            console.warn('Scanner error:', err);
            if (window.toastr) {
                toastr.error('تعذر إجراء المسح من السكانر. تأكد من تشغيل البرنامج ووضع الورقة داخل السكانر.');
            } else {
                alert('تعذر إجراء المسح من السكانر. تأكد من تشغيل البرنامج ووضع الورقة داخل السكانر.');
            }
        } finally {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    };

    // 11. معاينة مستند السطر المحدد
    window.previewDocumentRow = function(rowIndex) {
        const modalEl = document.getElementById('documentPreviewModal');
        const img = document.getElementById('documentPreviewImg');
        const pdfDiv = document.getElementById('documentPdfPreview');
        const pdfName = document.getElementById('documentPdfName');
        const scanInput = document.getElementById(`docScanInput_${rowIndex}`);
        const fileInput = document.getElementById(`docFileInput_${rowIndex}`);

        if (scanInput && scanInput.value) {
            img.src = scanInput.value;
            img.style.display = 'inline-block';
            if (pdfDiv) pdfDiv.style.display = 'none';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        if (fileInput && fileInput.files && fileInput.files[0]) {
            const file = fileInput.files[0];
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.style.display = 'inline-block';
                    if (pdfDiv) pdfDiv.style.display = 'none';
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                };
                reader.readAsDataURL(file);
            } else {
                img.style.display = 'none';
                if (pdfDiv) {
                    pdfDiv.style.display = 'block';
                    if (pdfName) pdfName.innerText = file.name;
                }
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
            return;
        }
    };

    // 12. حذف مستند محفوظ مسبقاً من العملية عبر AJAX
    window.deleteSurgeryDocument = function(surgeryId, docId) {
        if (!confirm('هل أنت متأكد من حذف هذا المستند نهائياً من العملية؟')) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                          document.querySelector('input[name="_token"]')?.value;

        fetch('{{ url("surgeries") }}/' + surgeryId + '/documents/' + docId, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const item = document.getElementById(`surgery-doc-item-${docId}`);
                if (item) {
                    item.remove();
                }
                const badge = document.getElementById('existingDocsBadge');
                if (badge) {
                    const remaining = document.querySelectorAll('#existingDocumentsList .shadow-2xs').length;
                    badge.innerText = `${remaining} مستند`;
                }
                if (window.toastr) {
                    toastr.success('تم حذف المستند بنجاح');
                }
            } else {
                alert(data.message || 'تعذر حذف المستند');
            }
        })
        .catch(err => {
            console.error('Error deleting surgery doc:', err);
            alert('حدث خطأ أثناء محاولة حذف المستند');
        });
    };

    // 13. كشف السكانر المتصل بالحاسبة
    window.detectScannerDevice = async function(showFeedback = true) {
        const detectBtn = document.getElementById('detect_scanner_btn');
        const spinner = document.getElementById('detect_spinner');
        const indicator = document.getElementById('scannerStatusIndicator');

        if (detectBtn) detectBtn.disabled = true;
        if (spinner) spinner.className = 'fas fa-spinner fa-spin me-1';
        if (indicator) {
            indicator.className = 'badge bg-warning-subtle text-warning border border-warning-subtle py-0 px-2';
            indicator.innerHTML = '<span class="spinner-grow spinner-grow-sm me-1" style="width:7px;height:7px;"></span>جاري قراءة السكانر...';
        }

        const endpoints = [
            'http://127.0.0.1:5000/status',
            'http://127.0.0.1:37426/status',
            'http://localhost:5000/status',
            'http://localhost:37426/status'
        ];

        let foundScanner = null;
        let bridgeRunning = false;

        for (const url of endpoints) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 2500);
                const res = await fetch(url, { method: 'GET', mode: 'cors', signal: controller.signal });
                clearTimeout(timeoutId);

                if (res.ok) {
                    const data = await res.json();
                    bridgeRunning = true;
                    if (data.scanners && data.scanners.length > 0) {
                        foundScanner = data.scanners[0].name || data.scanners[0].id || 'سكانر متصل';
                    } else if (data.status === 'ready' || data.status === 'ok') {
                        foundScanner = data.message || 'سكانر جاهز';
                    }
                    break;
                }
            } catch (e) {
                // Ignore and try next
            }
        }

        if (detectBtn) detectBtn.disabled = false;
        if (spinner) spinner.className = 'fas fa-sync-alt me-1';

        if (foundScanner) {
            if (indicator) {
                indicator.className = 'badge bg-success text-white py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-check-circle me-1" style="font-size: 8px;"></i>متصل: ' + foundScanner;
            }
            if (window.toastr && showFeedback) {
                toastr.success('تم اكتشاف وقراءة السكانر بنجاح: ' + foundScanner);
            }
        } else if (bridgeRunning) {
            if (indicator) {
                indicator.className = 'badge bg-warning text-dark py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-exclamation-circle me-1" style="font-size: 8px;"></i>البرنامج يعمل ولكن لم يتم اكتشاف جهاز';
            }
            if (showFeedback) {
                if (window.toastr) {
                    toastr.warning('برنامج السكانر يعمل ولكن لم يتم العثور على جهاز سكانر متصل بالحاسبة.');
                } else {
                    alert('برنامج السكانر يعمل ولكن لم يتم العثور على جهاز سكانر متصل بالحاسبة.');
                }
            }
        } else {
            if (indicator) {
                indicator.className = 'badge bg-secondary text-white py-0 px-2';
                indicator.innerHTML = '<i class="fas fa-times-circle me-1" style="font-size: 8px;"></i>السكانر غير متصل';
            }
            if (showFeedback) {
                if (window.toastr) {
                    toastr.error('تعذر الاتصال ببرنامج السكانر المحلي (127.0.0.1:5000).');
                } else {
                    alert('تعذر الاتصال ببرنامج السكانر المحلي (127.0.0.1:5000).');
                }
            }
        }
    };

    // فحص هادئ أولي بعد تحميل الصفحة
    setTimeout(() => {
        detectScannerDevice(false);
    }, 1500);

})();
</script>
