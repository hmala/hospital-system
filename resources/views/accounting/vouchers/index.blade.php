@extends('accounting.layout')

@section('title', 'سجل السندات والقيود المحاسبية اليومية')

@section('accounting_content')
<!-- Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 fs-3">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div>
                <span class="text-muted small d-block">إجمالي القيود والسندات</span>
                <span class="fs-4 fw-bold text-dark">{{ number_format($stats['total_entries']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-success bg-opacity-10 text-success p-3 rounded-4 fs-3">
                <i class="fas fa-check-circle"></i>
            </div>
            <div>
                <span class="text-muted small d-block">قيود معتمدة ومرحلة</span>
                <span class="fs-4 fw-bold text-success">{{ number_format($stats['posted']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-4 fs-3">
                <i class="fas fa-undo-alt"></i>
            </div>
            <div>
                <span class="text-muted small d-block">قيود معكوسة رقابياً</span>
                <span class="fs-4 fw-bold text-warning">{{ number_format($stats['reversed']) }}</span>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="bg-white rounded-4 p-3 shadow-sm border border-slate-200 d-flex align-items-center gap-3">
            <div class="bg-info bg-opacity-10 text-info p-3 rounded-4 fs-3">
                <i class="fas fa-money-check-alt"></i>
            </div>
            <div>
                <span class="text-muted small d-block">إجمالي المبالغ المرحلة</span>
                <span class="fs-5 fw-bold text-dark">{{ number_format($stats['total_debit'], 2) }} د.ع</span>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Vouchers Table -->
<div class="bg-white rounded-4 shadow-sm border border-slate-200 p-3 p-md-4">
    <!-- Filter Bar -->
    <form action="{{ route('accounting.vouchers.index') }}" method="GET" class="row g-2 align-items-end mb-4">
        <div class="col-md-3">
            <label class="form-label small text-muted">البحث برقم السند أو البيان:</label>
            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="مثلاً: JV-2026-...">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted">حالة القيد:</label>
            <select name="status" class="form-select form-select-sm">
                <option value="">-- كل الحالات --</option>
                <option value="posted" {{ request('status') === 'posted' ? 'selected' : '' }}>معتمد ومرحل (Posted)</option>
                <option value="reversed" {{ request('status') === 'reversed' ? 'selected' : '' }}>معكوس رقابياً (Reversed)</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>مسودة (Draft)</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted">من تاريخ:</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-2">
            <label class="form-label small text-muted">إلى تاريخ:</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 w-100">
                <i class="fas fa-filter me-1"></i> تصفية
            </button>
            <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-light btn-sm rounded-3 border px-3">
                إعادة ضبط
            </a>
        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive border rounded-3">
        <table class="table table-hover align-middle mb-0 text-center small">
            <thead class="table-light text-dark">
                <tr>
                    <th>رقم السند</th>
                    <th>التاريخ</th>
                    <th class="text-start">البيان والتفاصيل</th>
                    <th>إجمالي المبلغ</th>
                    <th>أطراف القيد</th>
                    <th>الحالة</th>
                    <th>المحاسب المُنظم</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td>
                            <span class="badge bg-light text-dark border font-monospace fw-bold fs-6 px-2 py-1">
                                {{ $entry->entry_number }}
                            </span>
                        </td>
                        <td class="text-muted">{{ $entry->date ? $entry->date->format('Y-m-d') : '-' }}</td>
                        <td class="text-start" style="max-width: 250px;">
                            <div class="fw-semibold text-dark text-truncate">{{ $entry->description }}</div>
                            @if($entry->reference_type)
                                <small class="text-muted d-block">مرجع: {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="fw-bold text-primary fs-6">
                                {{ number_format($entry->total_debit, 2) }} د.ع
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">
                                {{ $entry->lines->count() }} أطراف
                            </span>
                        </td>
                        <td>
                            @if($entry->status === 'posted')
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fas fa-check me-1"></i> معتمد ومرحل
                                </span>
                            @elseif($entry->status === 'reversed')
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" title="معكوس بموجب قيد آخر">
                                    <i class="fas fa-undo-alt me-1"></i> قيد معكوس
                                </span>
                            @else
                                <span class="badge bg-warning-subtle text-warning border px-2 py-1">مسودة</span>
                            @endif
                        </td>
                        <td class="text-muted">
                            {{ $entry->postedBy ? $entry->postedBy->name : 'النظام الآلي' }}
                        </td>
                        <td>
                            <div class="d-flex justify-content-center gap-1">
                                <a href="{{ route('accounting.vouchers.print', $entry->id) }}" class="btn btn-sm btn-outline-secondary rounded-2 px-2" title="معاينة وطباعة A4">
                                    <i class="fas fa-print"></i>
                                </a>
                                @if($entry->status === 'posted')
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-2 px-2" 
                                            onclick="openReverseModal({{ $entry->id }}, '{{ $entry->entry_number }}')" 
                                            title="عكس القيد رقابياً">
                                        <i class="fas fa-undo-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-muted py-5">
                            <i class="fas fa-file-invoice fs-1 opacity-25 d-block mb-2"></i>
                            لا توجد قيود أو سندات مطابقة لمعايير البحث حالياً.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $entries->links() }}
    </div>
</div>

<!-- Modal: Reverse Journal Entry -->
<div class="modal fade" id="reverseEntryModal" tabindex="-1" aria-labelledby="reverseEntryModalLabel" aria-hidden="true" dir="rtl">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="reverseEntryForm" action="" method="POST">
                @csrf
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger" id="reverseEntryModalLabel">
                        <i class="fas fa-exclamation-triangle me-1"></i> عكس القيد المحاسبي رقابياً
                    </h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-3">
                    <div class="alert alert-warning border-0 small mb-3">
                        وفقاً لمعايير الرقابة الصارمة، القيد المعتمد لا يُحذف، بل يتم إنشاء **قيد عكسي آلي** يعيد الأرصدة إلى ما كانت عليه ويوثق ذلك في سجل التدقيق المالي.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">رقم القيد المراد عكسه:</label>
                        <input type="text" id="reverseModalEntryNumber" class="form-control bg-light font-monospace" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-dark">سبب العكس الإلزامي (لأغراض التدقيق):</label>
                        <textarea name="reason" rows="3" class="form-control" placeholder="يرجى توضيح سبب إلغاء أو عكس هذا القيد بالتفصيل..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">تراجع</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 shadow-sm">تأكيد عكس القيد</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openReverseModal(entryId, entryNumber) {
        const form = document.getElementById('reverseEntryForm');
        form.action = "{{ route('accounting.vouchers.reverse', ':id') }}".replace(':id', entryId);
        document.getElementById('reverseModalEntryNumber').value = entryNumber;
        const modal = new bootstrap.Modal(document.getElementById('reverseEntryModal'));
        modal.show();
    }
</script>
@endpush
@endsection
