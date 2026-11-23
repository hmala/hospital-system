@extends('accounting.layout')

@section('title', 'إنشاء سند محاسبي جديد')

@section('accounting_content')
<div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
    <!-- Header with Type Switcher -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h5 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                @if($type === 'receipt')
                    <span class="badge bg-success fs-6"><i class="fas fa-hand-holding-usd me-1"></i> سند قبض (Receipt Voucher)</span>
                @elseif($type === 'payment')
                    <span class="badge bg-danger fs-6"><i class="fas fa-money-bill-wave me-1"></i> سند صرف (Payment Voucher)</span>
                @else
                    <span class="badge bg-primary fs-6"><i class="fas fa-file-invoice me-1"></i> قيد يومية عام وتسويات (Journal Voucher)</span>
                @endif
            </h5>
            <p class="text-muted small mb-0">تسجيل الحركات المالية المزدوجة المتوازنة وفق معايير النظام المحاسبي الموحد العراقي</p>
        </div>

        <!-- Switcher Pills -->
        <div class="btn-group rounded-pill p-1 bg-light border shadow-sm">
            <a href="{{ route('accounting.vouchers.create', 'receipt') }}" class="btn btn-sm rounded-pill px-3 {{ $type === 'receipt' ? 'btn-success fw-bold' : 'btn-light text-muted' }}">
                سند قبض (RV)
            </a>
            <a href="{{ route('accounting.vouchers.create', 'payment') }}" class="btn btn-sm rounded-pill px-3 {{ $type === 'payment' ? 'btn-danger fw-bold' : 'btn-light text-muted' }}">
                سند صرف (PV)
            </a>
            <a href="{{ route('accounting.vouchers.create', 'journal') }}" class="btn btn-sm rounded-pill px-3 {{ $type === 'journal' ? 'btn-primary fw-bold' : 'btn-light text-muted' }}">
                قيد يومية (JV)
            </a>
        </div>
    </div>

    <!-- The Form -->
    <form action="{{ route('accounting.vouchers.store') }}" method="POST" enctype="multipart/form-data" id="voucherForm">
        @csrf
        <input type="hidden" name="voucher_type" value="{{ $type }}">

        <!-- Top Metadata Row -->
        <div class="row g-3 mb-4 bg-light p-3 rounded-4 border">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark">تاريخ السند المحاسبي: <span class="text-danger">*</span></label>
                <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" class="form-control" required>
            </div>

            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark">العملة وسعر الصرف:</label>
                <div class="input-group">
                    <select name="currency_id" id="currencySelect" class="form-select">
                        @foreach($currencies as $curr)
                            <option value="{{ $curr->id }}" data-rate="{{ $curr->exchange_rate }}" {{ $curr->is_default ? 'selected' : '' }}>
                                {{ $curr->code }} - {{ $curr->name }}
                            </option>
                        @endforeach
                    </select>
                    <input type="number" step="0.0001" name="exchange_rate" id="exchangeRateInput" value="1.0000" class="form-control font-monospace text-center" style="max-width: 100px;" title="سعر الصرف مقابل الدينار">
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label small fw-semibold text-dark">الأرشفة الضوئية (مرفق الوصل / الفاتورة الورقية):</label>
                <input type="file" name="attachment" class="form-control" accept="application/pdf,image/*">
                <div class="form-text small">يمكنك إرفاق صورة المستند بالسكانر أو الموبايل (PDF أو صورة).</div>
            </div>

            <div class="col-12">
                <label class="form-label small fw-semibold text-dark">البيان العام للسند: <span class="text-danger">*</span></label>
                <input type="text" name="description" value="{{ old('description') }}" class="form-control" placeholder="اكتب بياناً واضحاً ومفصلاً لسبب إجراء هذا السند..." required>
            </div>
        </div>

        <!-- Real-Time Tafqeet Box -->
        <div class="alert alert-info border border-info border-opacity-25 rounded-4 p-3 mb-4 d-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-20 text-info p-2 rounded-circle fs-4">
                <i class="fas fa-spell-check"></i>
            </div>
            <div>
                <span class="text-muted small d-block">التفقيط الآلي المعتمد بالدينار العراقي:</span>
                <span class="fw-bold text-dark fs-6" id="tafqeetDisplay">فقط صفر دينار عراقي لا غير</span>
            </div>
        </div>

        <!-- Dynamic Entry Lines Table -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-dark mb-0"><i class="fas fa-list-ol me-1 text-primary"></i> أطراف القيد المحاسبي المزدوج (مدين ودائن):</h6>
                <button type="button" class="btn btn-sm btn-outline-primary rounded-3" id="btnAddLine">
                    <i class="fas fa-plus-circle me-1"></i> إضافة طرف جديد للقيد
                </button>
            </div>

            <div class="table-responsive border rounded-3 overflow-visible">
                <table class="table table-bordered mb-0 align-middle small text-center" id="linesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width: 280px;" class="text-start">الحساب المالي (المستوى 5 و 6 فقط) <span class="text-danger">*</span></th>
                            <th style="min-width: 180px;">مركز التكلفة (القسم)</th>
                            <th style="width: 140px;">مدين (Debit)</th>
                            <th style="width: 140px;">دائن (Credit)</th>
                            <th style="min-width: 180px;">البيان الخاص بالسطر</th>
                            <th style="width: 50px;">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="linesContainer">
                        <!-- Initial Line 1 (Debit side if receipt/payment) -->
                        <tr class="line-row">
                            <td class="text-start">
                                <select name="lines[0][account_id]" class="form-select form-select-sm account-select" required>
                                    <option value="">-- اختر الحساب التحليلي --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}" 
                                            {{ ($type === 'receipt' && $loop->first && str_starts_with($acc->code, '134')) ? 'selected' : '' }}>
                                            {{ $acc->code }} - {{ $acc->name }} ({{ $acc->nature === 'debit' ? 'مدين' : 'دائن' }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="lines[0][cost_center_id]" class="form-select form-select-sm">
                                    <option value="">-- عام بدون مركز --</option>
                                    @foreach($costCenters as $cc)
                                        <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[0][debit]" class="form-control form-control-sm text-center debit-input font-monospace fw-bold" value="0.00" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[0][credit]" class="form-control form-control-sm text-center credit-input font-monospace fw-bold" value="0.00" required>
                            </td>
                            <td>
                                <input type="text" name="lines[0][description]" class="form-control form-control-sm" placeholder="بيان اختياري">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-line-btn" disabled>
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Initial Line 2 (Credit side if receipt/payment) -->
                        <tr class="line-row">
                            <td class="text-start">
                                <select name="lines[1][account_id]" class="form-select form-select-sm account-select" required>
                                    <option value="">-- اختر الحساب التحليلي --</option>
                                    @foreach($accounts as $acc)
                                        <option value="{{ $acc->id }}">
                                            {{ $acc->code }} - {{ $acc->name }} ({{ $acc->nature === 'debit' ? 'مدين' : 'دائن' }})
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="lines[1][cost_center_id]" class="form-select form-select-sm">
                                    <option value="">-- عام بدون مركز --</option>
                                    @foreach($costCenters as $cc)
                                        <option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[1][debit]" class="form-control form-control-sm text-center debit-input font-monospace fw-bold" value="0.00" required>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[1][credit]" class="form-control form-control-sm text-center credit-input font-monospace fw-bold" value="0.00" required>
                            </td>
                            <td>
                                <input type="text" name="lines[1][description]" class="form-control form-control-sm" placeholder="بيان اختياري">
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-line-btn">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="2" class="text-end">الإجماليات والمطابقة الرقابية:</td>
                            <td class="text-center text-success fs-6" id="totalDebitCell">0.00 د.ع</td>
                            <td class="text-center text-danger fs-6" id="totalCreditCell">0.00 د.ع</td>
                            <td colspan="2" class="text-center">
                                <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill" id="balanceStatusBadge">
                                    <i class="fas fa-times-circle me-1"></i> غير متوازن (فرق: 0.00)
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
            <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-light rounded-3 px-3">
                <i class="fas fa-times me-1"></i> إلغاء ورجوع
            </a>
            <button type="submit" class="btn btn-primary rounded-3 px-5 py-2 fw-bold shadow" id="btnSubmitVoucher" disabled>
                <i class="fas fa-check-circle me-1"></i> حفظ وترحيل القيد للأستاذ العام
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    let lineIndex = 2;

    // Accounts template options
    const accountsOptions = `@foreach($accounts as $acc)<option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }} ({{ $acc->nature === 'debit' ? 'مدين' : 'دائن' }})</option>@endforeach`;
    const costCentersOptions = `@foreach($costCenters as $cc)<option value="{{ $cc->id }}">{{ $cc->code }} - {{ $cc->name }}</option>@endforeach`;

    // Recalculate totals and check balance
    function recalculateBalance() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.line-row').forEach(row => {
            const debit = parseFloat(row.querySelector('.debit-input').value) || 0;
            const credit = parseFloat(row.querySelector('.credit-input').value) || 0;
            totalDebit += debit;
            totalCredit += credit;
        });

        document.getElementById('totalDebitCell').textContent = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(totalDebit) + ' د.ع';
        document.getElementById('totalCreditCell').textContent = new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(totalCredit) + ' د.ع';

        const diff = Math.abs(totalDebit - totalCredit);
        const badge = document.getElementById('balanceStatusBadge');
        const submitBtn = document.getElementById('btnSubmitVoucher');

        if (totalDebit > 0 && diff < 0.005) {
            badge.className = 'badge bg-success fs-6 px-3 py-2 rounded-pill';
            badge.innerHTML = '<i class="fas fa-check-circle me-1"></i> متوازن 100% وجاهز للترحيل';
            submitBtn.removeAttribute('disabled');
        } else {
            badge.className = 'badge bg-danger fs-6 px-3 py-2 rounded-pill';
            badge.innerHTML = `<i class="fas fa-exclamation-triangle me-1"></i> غير متوازن (فرق: ${new Intl.NumberFormat('en-US', {minimumFractionDigits: 2}).format(diff)})`;
            submitBtn.setAttribute('disabled', 'disabled');
        }

        // Update Tafqeet based on total debit
        if (totalDebit > 0) {
            fetchTafqeet(totalDebit);
        } else {
            document.getElementById('tafqeetDisplay').textContent = 'فقط صفر دينار عراقي لا غير';
        }
    }

    let tafqeetDebounce = null;
    function fetchTafqeet(amount) {
        clearTimeout(tafqeetDebounce);
        tafqeetDebounce = setTimeout(() => {
            const url = "{{ route('accounting.vouchers.tafqeet') }}?amount=" + amount;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('tafqeetDisplay').textContent = data.words;
                });
        }, 200);
    }

    // Add new row
    document.getElementById('btnAddLine').addEventListener('click', function() {
        const container = document.getElementById('linesContainer');
        const tr = document.createElement('tr');
        tr.className = 'line-row';
        tr.innerHTML = `
            <td class="text-start">
                <select name="lines[${lineIndex}][account_id]" class="form-select form-select-sm account-select" required>
                    <option value="">-- اختر الحساب التحليلي --</option>
                    ${accountsOptions}
                </select>
            </td>
            <td>
                <select name="lines[${lineIndex}][cost_center_id]" class="form-select form-select-sm">
                    <option value="">-- عام بدون مركز --</option>
                    ${costCentersOptions}
                </select>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="lines[${lineIndex}][debit]" class="form-control form-control-sm text-center debit-input font-monospace fw-bold" value="0.00" required>
            </td>
            <td>
                <input type="number" step="0.01" min="0" name="lines[${lineIndex}][credit]" class="form-control form-control-sm text-center credit-input font-monospace fw-bold" value="0.00" required>
            </td>
            <td>
                <input type="text" name="lines[${lineIndex}][description]" class="form-control form-control-sm" placeholder="بيان اختياري">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 delete-line-btn">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        lineIndex++;
        attachLineEvents(tr);
    });

    function attachLineEvents(row) {
        row.querySelector('.debit-input').addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                row.querySelector('.credit-input').value = '0.00';
            }
            recalculateBalance();
        });

        row.querySelector('.credit-input').addEventListener('input', function() {
            if (parseFloat(this.value) > 0) {
                row.querySelector('.debit-input').value = '0.00';
            }
            recalculateBalance();
        });

        const deleteBtn = row.querySelector('.delete-line-btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                if (document.querySelectorAll('.line-row').length > 2) {
                    row.remove();
                    recalculateBalance();
                } else {
                    alert('لا يمكن حذف هذا السطر؛ يجب أن يتضمن القيد طرفين على الأقل (مدين ودائن).');
                }
            });
        }
    }

    // Attach to initial rows
    document.querySelectorAll('.line-row').forEach(row => attachLineEvents(row));

    // Currency change
    document.getElementById('currencySelect').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const rate = selected.getAttribute('data-rate') || '1.0000';
        document.getElementById('exchangeRateInput').value = rate;
    });

    // Initial calculation
    recalculateBalance();
</script>
@endpush
@endsection
