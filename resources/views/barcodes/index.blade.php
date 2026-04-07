@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="mb-1">طباعة الباركودات</h4>
                    <p class="text-muted mb-0">اختر الدفعات المراد طباعة باركوداتها</p>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>المادة</th>
                            <th>الباركود الداخلي</th>
                            <th>الموقع</th>
                            <th>الكمية</th>
                            <th>تاريخ الاستلام</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($batches as $batch)
                            <tr>
                                <td>
                                    <input type="checkbox" class="batch-checkbox" value="{{ $batch->id }}">
                                </td>
                                <td>{{ $batch->product->name }}</td>
                                <td><code>{{ $batch->internal_barcode }}</code></td>
                                <td>{{ $batch->location->name }}</td>
                                <td>{{ $batch->current_qty }} {{ $batch->product->unit }}</td>
                                <td>{{ $batch->received_at->format('Y-m-d') }}</td>
                                <td>
                                    <a href="{{ route('barcodes.show', $batch) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-qr-code"></i> طباعة
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">لا توجد دفعات حالياً</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex justify-content-between align-items-center">
                <button type="button" id="printSelectedBtn" class="btn btn-primary" disabled>
                    <i class="bi bi-printer"></i> طباعة المحدد (<span id="selectedCount">0</span>)
                </button>
                <div>
                    {{ $batches->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const selectAllCheckbox = document.getElementById('selectAll');
    const batchCheckboxes = document.querySelectorAll('.batch-checkbox');
    const printSelectedBtn = document.getElementById('printSelectedBtn');
    const selectedCountSpan = document.getElementById('selectedCount');

    // Select/Deselect All
    selectAllCheckbox.addEventListener('change', function() {
        batchCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    // Update count on individual checkbox change
    batchCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
    });

    function updateSelectedCount() {
        const selectedCount = document.querySelectorAll('.batch-checkbox:checked').length;
        selectedCountSpan.textContent = selectedCount;
        printSelectedBtn.disabled = selectedCount === 0;
    }

    // Print selected batches
    printSelectedBtn.addEventListener('click', function() {
        const selectedIds = Array.from(document.querySelectorAll('.batch-checkbox:checked'))
            .map(cb => cb.value);
        
        if (selectedIds.length > 0) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('barcodes.print_multiple') }}";
            form.target = '_blank';

            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            form.appendChild(csrfInput);

            selectedIds.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'batch_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }
    });
</script>
@endsection
