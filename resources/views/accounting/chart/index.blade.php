@extends('accounting.layout')

@section('title', 'دليل الحسابات وشجرة النظام المحاسبي الموحد')

@section('accounting_content')
<!-- Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 fs-3">
                <i class="fas fa-sitemap"></i>
            </div>
            <div>
                <span class="text-muted small d-block">إجمالي حسابات الدليل</span>
                <span class="fs-4 fw-bold text-dark">{{ number_format($stats['total']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-4 fs-3">
                <i class="fas fa-check-double"></i>
            </div>
            <div>
                <span class="text-muted small d-block">تقبل القيود (المستوى 5 و 6)</span>
                <span class="fs-4 fw-bold text-success">{{ number_format($stats['postable']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-secondary bg-opacity-10 text-secondary p-3 rounded-4 fs-3">
                <i class="fas fa-folder-tree"></i>
            </div>
            <div>
                <span class="text-muted small d-block">حسابات تجميعية (مستوى 1-4)</span>
                <span class="fs-4 fw-bold text-secondary">{{ number_format($stats['headers']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-10 text-info p-3 rounded-4 fs-3">
                <i class="fas fa-coins"></i>
            </div>
            <div>
                <span class="text-muted small d-block">العملة الأساسية والسعر</span>
                <span class="fs-5 fw-bold text-dark">دينار عراقي (1$) = 1320</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Split-View Layout -->
<div class="row g-4">
    <!-- Right Panel: Interactive Tree View -->
    <div class="col-lg-7">
        <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
            <!-- Search & Filter Controls -->
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
                <div class="input-group" style="max-width: 380px;">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-search"></i></span>
                    <input type="text" id="treeSearchInput" class="form-control bg-light border-start-0" placeholder="ابحث برقم الدليل أو اسم الحساب...">
                </div>

                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" id="btnExpandAll" title="توسيع الكل">
                        <i class="fas fa-expand-arrows-alt me-1"></i> توسيع
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-3" id="btnCollapseAll" title="طي الكل">
                        <i class="fas fa-compress-arrows-alt me-1"></i> طي
                    </button>
                </div>
            </div>

            <!-- Legend Bar -->
            <div class="d-flex flex-wrap gap-3 align-items-center bg-slate-50 p-2 rounded-3 mb-3 border text-muted small">
                <span class="d-flex align-items-center gap-1">
                    <span class="badge bg-secondary rounded-pill">1..4</span>
                    <span>حسابات تجميعية رقابية (لا تقبل القيود)</span>
                </span>
                <span class="d-flex align-items-center gap-1">
                    <span class="badge bg-success rounded-pill">5..6</span>
                    <span>حسابات تحليلية (تقبل القيود المباشرة)</span>
                </span>
            </div>

            <!-- The Collapsible Tree Container -->
            <div class="chart-tree-wrapper overflow-auto pe-2" style="max-height: 650px;" id="treeContainer">
                @foreach($rootAccounts as $root)
                    <div class="tree-root-item mb-2" data-code="{{ $root->code }}" data-name="{{ $root->name }}">
                        <!-- Root Node Header -->
                        <div class="d-flex align-items-center justify-content-between p-2 rounded-3 bg-light border hover-bg-light cursor-pointer tree-node" 
                             onclick="toggleNode('node-{{ $root->id }}', this)" 
                             data-account-id="{{ $root->id }}">
                            <div class="d-flex align-items-center gap-2">
                                <span class="tree-toggle-icon text-muted">
                                    <i class="fas fa-chevron-left" id="icon-node-{{ $root->id }}"></i>
                                </span>
                                <span class="badge bg-primary px-2 py-1">{{ $root->code }}</span>
                                <span class="fw-bold text-dark">{{ $root->name }}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary-subtle text-secondary small">مستوى {{ $root->level }}</span>
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill" onclick="event.stopPropagation(); selectAccount({{ $root->id }})">
                                    تفاصيل
                                </button>
                            </div>
                        </div>

                        <!-- Children Container -->
                        <div class="tree-children ms-3 mt-1 d-none border-start border-2 border-primary border-opacity-25 ps-2" id="node-{{ $root->id }}">
                            @include('accounting.chart._tree_nodes', ['accounts' => $root->children])
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Left Panel: Account Details & Fast Actions -->
    <div class="col-lg-5">
        <div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4 sticky-top" style="top: 20px;">
            <div id="detailsPlaceholder" class="text-center py-5 text-muted">
                <i class="fas fa-mouse-pointer fs-1 text-muted opacity-50 mb-3 d-block"></i>
                <h5 class="fw-semibold">اختر حساباً من الشجرة</h5>
                <p class="small text-muted mb-0">انقر على أي حساب من شجرة الدليل لعرض بياناته التفصيلية، رصيده اللحظي، وأحدث القيود المحاسبية المسجلة عليه.</p>
            </div>

            <div id="detailsCard" class="d-none">
                <!-- Account Title & Badges -->
                <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary fs-6 px-3 py-1 rounded-pill" id="detailCode">111</span>
                            <span class="badge" id="detailPostableBadge">يقبل القيود</span>
                        </div>
                        <h5 class="fw-bold text-dark mb-0" id="detailName">اسم الحساب</h5>
                    </div>
                    <button type="button" class="btn btn-sm btn-success rounded-3 d-flex align-items-center gap-1 shadow-sm" id="btnAddSubAccount">
                        <i class="fas fa-plus-circle"></i>
                        <span>+ إضافة ابن</span>
                    </button>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3 border">
                    <div class="row g-2 small">
                        <div class="col-6">
                            <span class="text-muted d-block">رتبة المستوى:</span>
                            <span class="fw-semibold text-dark" id="detailLevel">المستوى 5</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">طبيعة الحساب:</span>
                            <span class="fw-semibold text-dark" id="detailNature">مدين</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">النوع / التبويب:</span>
                            <span class="fw-semibold text-dark" id="detailType">أصول</span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">الحساب الأب:</span>
                            <span class="fw-semibold text-dark text-truncate d-block" id="detailParent">-</span>
                        </div>
                    </div>
                </div>

                <!-- Instant Balance Box -->
                <div class="bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 p-3 mb-3 text-center">
                    <span class="text-muted small d-block">الرصيد الفعلي اللحظي للحساب</span>
                    <h3 class="fw-bold text-primary mb-0" id="detailBalance">0.00 د.ع</h3>
                </div>

                <!-- Recent Transactions -->
                <h6 class="fw-bold text-dark mb-2 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history me-1 text-muted"></i> آخر الحركات المحاسبية المسجلة:</span>
                </h6>
                <div class="table-responsive border rounded-3 overflow-hidden" style="max-height: 250px;">
                    <table class="table table-sm table-hover mb-0 small text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>رقم القيد</th>
                                <th>التاريخ</th>
                                <th>البيان</th>
                                <th>مدين</th>
                                <th>دائن</th>
                            </tr>
                        </thead>
                        <tbody id="detailTransactionsTable">
                            <tr>
                                <td colspan="5" class="text-muted py-3">لا توجد حركات مسجلة على هذا الحساب حالياً</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Sub-Account -->
<div class="modal fade" id="subAccountModal" tabindex="-1" aria-labelledby="subAccountModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('accounting.chart.store') }}" method="POST">
                @csrf
                <input type="hidden" name="parent_id" id="modalParentId">

                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="subAccountModalLabel">
                        <i class="fas fa-plus-circle text-success me-1"></i> إضافة حساب فرعي جديد
                    </h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-3">
                    <!-- Parent Details Notice -->
                    <div class="alert alert-light border rounded-3 p-2 mb-3 small d-flex align-items-center gap-2">
                        <i class="fas fa-folder-open text-primary fs-5"></i>
                        <div>
                            <span class="text-muted">الحساب الأب:</span>
                            <span class="fw-bold text-dark" id="modalParentLabel">-</span>
                        </div>
                    </div>

                    <!-- Suggested Code Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">كود / رقم الحساب الجديد:</label>
                        <div class="input-group">
                            <input type="text" name="code" id="modalAccountCode" class="form-control text-start font-monospace fw-bold" required>
                            <button type="button" class="btn btn-outline-secondary" id="btnAutoSuggest" title="اقتراح تلقائي للكود التالي">
                                <i class="fas fa-magic me-1"></i> اقتراح كود
                            </button>
                        </div>
                        <div class="form-text small" id="modalCodeNotice">سيتم فتح الحساب وفق تدرج الدليل الموحد.</div>
                    </div>

                    <!-- Account Name Input -->
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">اسم الحساب الجديد (عربي):</label>
                        <input type="text" name="name" id="modalAccountName" class="form-control" placeholder="مثلاً: صندوق كاشير الطوارئ المسائي" required>
                    </div>

                    <div class="alert alert-info py-2 px-3 rounded-3 small border-0 mb-0 d-flex align-items-center gap-2">
                        <i class="fas fa-info-circle"></i>
                        <span>ملاحظة: إذا كان طول الكود 5 أو 6 أرقام، سيُصنف الحساب تلقائياً كـ <strong>حساب تحليلي يقبل القيود</strong>.</span>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4 shadow-sm">حفظ الحساب في الدليل</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let currentSelectedAccountId = null;

    function toggleNode(nodeId, element) {
        const children = document.getElementById(nodeId);
        const icon = document.getElementById('icon-' + nodeId);
        if (!children) return;

        if (children.classList.contains('d-none')) {
            children.classList.remove('d-none');
            if (icon) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-down');
            }
        } else {
            children.classList.add('d-none');
            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-left');
            }
        }
    }

    function selectAccount(accountId) {
        currentSelectedAccountId = accountId;

        // Visual selection on tree
        document.querySelectorAll('.tree-node').forEach(el => el.classList.remove('bg-primary-subtle', 'border-primary'));
        const activeNode = document.querySelector(`[data-account-id="${accountId}"]`);
        if (activeNode) {
            activeNode.classList.add('bg-primary-subtle', 'border-primary');
        }

        // Show details card, hide placeholder
        const placeholder = document.getElementById('detailsPlaceholder');
        const card = document.getElementById('detailsCard');
        
        const detailsUrl = "{{ route('accounting.chart.details', ':id') }}".replace(':id', accountId);

        fetch(detailsUrl)
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const acc = data.account;

                document.getElementById('detailCode').textContent = acc.code;
                document.getElementById('detailName').textContent = acc.name;
                document.getElementById('detailLevel').textContent = 'المستوى ' + acc.level;
                document.getElementById('detailNature').textContent = acc.nature === 'debit' ? 'مدين (Debit)' : 'دائن (Credit)';
                
                const typeMap = {
                    'asset': 'أصول (Assets)',
                    'liability': 'التزامات (Liabilities)',
                    'equity': 'حقوق ملكية (Equity)',
                    'expense': 'استخدامات / مصروفات (Expenses)',
                    'revenue': 'موارد / إيرادات (Revenues)'
                };
                document.getElementById('detailType').textContent = typeMap[acc.account_type] || acc.account_type;
                document.getElementById('detailParent').textContent = acc.parent ? `${acc.parent.code} - ${acc.parent.name}` : 'لا يوجد (حساب رئيسي)';

                const postableBadge = document.getElementById('detailPostableBadge');
                if (acc.is_postable) {
                    postableBadge.className = 'badge bg-success';
                    postableBadge.textContent = '🟢 يقبل تسجيل القيود';
                } else {
                    postableBadge.className = 'badge bg-secondary';
                    postableBadge.textContent = '📁 حساب تجميعي رقابي';
                }

                document.getElementById('detailBalance').textContent = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(acc.balance) + ' د.ع';

                // Fill transactions table
                const tbody = document.getElementById('detailTransactionsTable');
                if (acc.recent_transactions && acc.recent_transactions.length > 0) {
                    tbody.innerHTML = '';
                    acc.recent_transactions.forEach(t => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td><span class="badge bg-light text-dark border font-monospace">${t.entry_number}</span></td>
                            <td class="text-muted small">${t.date}</td>
                            <td class="text-start small text-truncate" style="max-width: 140px;">${t.description}</td>
                            <td class="text-success fw-semibold">${t.debit > 0 ? new Intl.NumberFormat('en-US').format(t.debit) : '-'}</td>
                            <td class="text-danger fw-semibold">${t.credit > 0 ? new Intl.NumberFormat('en-US').format(t.credit) : '-'}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-muted py-3">لا توجد حركات مسجلة على هذا الحساب</td></tr>';
                }

                placeholder.classList.add('d-none');
                card.classList.remove('d-none');
            });
    }

    // Modal Trigger
    document.getElementById('btnAddSubAccount').addEventListener('click', function() {
        if (!currentSelectedAccountId) return;

        const parentName = document.getElementById('detailName').textContent;
        const parentCode = document.getElementById('detailCode').textContent;

        document.getElementById('modalParentId').value = currentSelectedAccountId;
        document.getElementById('modalParentLabel').textContent = `${parentCode} - ${parentName}`;
        document.getElementById('modalAccountName').value = '';

        // Auto fetch suggestion
        const suggestUrl = "{{ route('accounting.chart.suggest-code') }}?parent_id=" + currentSelectedAccountId;
        fetch(suggestUrl)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalAccountCode').value = data.suggested_code;
                    document.getElementById('modalCodeNotice').textContent = `الكود المقترح يتبع المستوى ${data.level} (${data.is_postable ? 'يقبل القيود' : 'تجميعي'}).`;
                }
            });

        const modal = new bootstrap.Modal(document.getElementById('subAccountModal'));
        modal.show();
    });

    // Auto suggest button click
    document.getElementById('btnAutoSuggest').addEventListener('click', function() {
        if (!currentSelectedAccountId) return;
        const suggestUrl = "{{ route('accounting.chart.suggest-code') }}?parent_id=" + currentSelectedAccountId;
        fetch(suggestUrl)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalAccountCode').value = data.suggested_code;
                }
            });
    });

    // Expand / Collapse All
    document.getElementById('btnExpandAll').addEventListener('click', function() {
        document.querySelectorAll('.tree-children').forEach(el => el.classList.remove('d-none'));
        document.querySelectorAll('.tree-toggle-icon i').forEach(el => {
            el.classList.remove('fa-chevron-left');
            el.classList.add('fa-chevron-down');
        });
    });

    document.getElementById('btnCollapseAll').addEventListener('click', function() {
        document.querySelectorAll('.tree-children').forEach(el => el.classList.add('d-none'));
        document.querySelectorAll('.tree-toggle-icon i').forEach(el => {
            el.classList.remove('fa-chevron-down');
            el.classList.add('fa-chevron-left');
        });
    });

    // Tree Instant Search
    document.getElementById('treeSearchInput').addEventListener('input', function(e) {
        const term = e.target.value.trim().toLowerCase();
        if (!term) {
            document.querySelectorAll('.tree-node-item, .tree-root-item').forEach(el => el.classList.remove('d-none'));
            return;
        }

        document.querySelectorAll('.tree-node-item, .tree-root-item').forEach(el => {
            const code = (el.getAttribute('data-code') || '').toLowerCase();
            const name = (el.getAttribute('data-name') || '').toLowerCase();
            if (code.includes(term) || name.includes(term)) {
                el.classList.remove('d-none');
                // Open parent trees if hidden
                let parent = el.closest('.tree-children');
                while (parent) {
                    parent.classList.remove('d-none');
                    parent = parent.parentElement.closest('.tree-children');
                }
            } else {
                el.classList.add('d-none');
            }
        });
    });
</script>
@endpush
@endsection
