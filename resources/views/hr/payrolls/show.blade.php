@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-0 text-primary">
                <i class="fas fa-file-invoice-dollar text-success me-2"></i> مسير رواتب شهر {{ $cycle->cycle_month }}
            </h3>
            <div class="text-muted mt-1">
                الفترة من {{ $cycle->start_date->format('Y-m-d') }} إلى {{ $cycle->end_date->format('Y-m-d') }}
            </div>
        </div>
        <div>
            <a href="{{ route('hr.payrolls.index') }}" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-right"></i> عودة
            </a>
            @if($cycle->status == 'draft')
            <form action="{{ route('hr.payrolls.approve', $cycle->id) }}" method="POST" class="d-inline-block">
                @csrf
                <button type="submit" class="btn btn-success shadow-sm" onclick="return confirm('هل أنت متأكد من اعتماد الرواتب؟ لا يمكن التعديل بعد الاعتماد.')">
                    <i class="fas fa-check-double"></i> اعتماد المسير نهائياً
                </button>
            </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0 text-center">
                    <thead class="table-dark">
                        <tr>
                            <th rowspan="2" class="align-middle">القسم</th>
                            <th rowspan="2" class="align-middle">اسم الموظف</th>
                            <th rowspan="2" class="align-middle">الراتب الأساسي</th>
                            <th colspan="3">الاستحقاقات والاستقطاعات الإضافية</th>
                            <th rowspan="2" class="align-middle bg-success text-white">الراتب الصافي النهائي</th>
                            @if($cycle->status == 'draft')
                            <th rowspan="2" class="align-middle">تعديل المخصصات</th>
                            @endif
                        </tr>
                        <tr>
                            <th class="bg-secondary text-white">المخصصات والعلاوات (يدوي)</th>
                            <th class="bg-success text-white">المكافآت (تلقائي)</th>
                            <th class="bg-danger text-white">العقوبات والخصومات (تلقائي)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalBasic = 0;
                            $totalAllowances = 0;
                            $totalBonuses = 0;
                            $totalPenalties = 0;
                            $totalNet = 0;
                        @endphp
                        @foreach($cycle->payrolls as $slip)
                        @php
                            $totalBasic += $slip->basic_salary;
                            $totalAllowances += $slip->allowances;
                            $totalBonuses += $slip->bonuses_amount;
                            $totalPenalties += $slip->penalties_amount;
                            $totalNet += $slip->net_salary;
                        @endphp
                        <tr id="row-{{ $slip->id }}">
                            <td>{{ $slip->employee->department->name ?? 'غير محدد' }}</td>
                            <td class="text-start fw-bold text-primary">{{ $slip->employee->full_name }}</td>
                            <td>{{ number_format($slip->basic_salary, 0) }} د.ع</td>
                            
                            <td class="allowances-cell bg-light">
                                <span class="allowances-text fw-bold">{{ number_format($slip->allowances, 0) }}</span> د.ع
                            </td>
                            
                            <td class="text-success fw-bold">{{ number_format($slip->bonuses_amount, 0) }} د.ع</td>
                            <td class="text-danger fw-bold">{{ number_format($slip->penalties_amount, 0) }} د.ع</td>
                            
                            <td class="bg-success-subtle fw-bold fs-5 net-salary-cell">{{ number_format($slip->net_salary, 0) }} د.ع</td>
                            
                            @if($cycle->status == 'draft')
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-allowances" 
                                    data-id="{{ $slip->id }}" 
                                    data-val="{{ rtrim(rtrim($slip->allowances, '0'), '.') ?: '0' }}">
                                    <i class="fas fa-edit"></i> مخصصات
                                </button>
                            </td>
                            @endif
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light fw-bold fs-5">
                        <tr>
                            <td colspan="2" class="text-end">الإجمالي الكلي للشهر:</td>
                            <td class="text-primary">{{ number_format($totalBasic, 0) }} د.ع</td>
                            <td id="total-allowances">{{ number_format($totalAllowances, 0) }} د.ع</td>
                            <td class="text-success">{{ number_format($totalBonuses, 0) }} د.ع</td>
                            <td class="text-danger">{{ number_format($totalPenalties, 0) }} د.ع</td>
                            <td class="bg-success text-white" id="total-net">{{ number_format($totalNet, 0) }} د.ع</td>
                            @if($cycle->status == 'draft')
                            <td></td>
                            @endif
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Editing Allowances -->
<div class="modal fade" id="editAllowancesModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">تعديل المخصصات/العلاوات</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="slip_id">
                <label class="form-label">إجمالي مبلغ المخصصات (د.ع)</label>
                <input type="number" id="allowances_input" class="form-control" step="1000" min="0">
                <small class="text-muted mt-2 d-block">سجل مجموع المخصصات كـ (الزوجية، الأطفال، النقل، الخطورة، إلخ).</small>
            </div>
            <div class="modal-footer bg-light p-2">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-success btn-sm" id="btn-save-allowances"><i class="fas fa-save"></i> حفظ</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let editModal = new bootstrap.Modal(document.getElementById('editAllowancesModal'));
    
    document.querySelectorAll('.btn-edit-allowances').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('slip_id').value = this.dataset.id;
            document.getElementById('allowances_input').value = this.dataset.val;
            editModal.show();
        });
    });

    document.getElementById('btn-save-allowances').addEventListener('click', function() {
        let slipId = document.getElementById('slip_id').value;
        let allowances = document.getElementById('allowances_input').value || 0;
        
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
        this.disabled = true;

        fetch(`/hr/payrolls/slip/${slipId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ allowances: allowances })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Update UI instantly
                let row = document.getElementById(`row-${slipId}`);
                row.querySelector('.allowances-text').innerText = new Intl.NumberFormat().format(allowances);
                row.querySelector('.net-salary-cell').innerText = new Intl.NumberFormat().format(data.net_salary) + ' د.ع';
                
                // Update dataset for next edit
                row.querySelector('.btn-edit-allowances').dataset.val = allowances;
                
                editModal.hide();
                // Optionally reload to update totals, or update totals via JS
                location.reload();
            } else {
                alert('حدث خطأ أثناء الحفظ');
            }
        })
        .finally(() => {
            this.innerHTML = '<i class="fas fa-save"></i> حفظ';
            this.disabled = false;
        });
    });
});
</script>
@endsection
