@extends('layouts.app')
@section('title', 'شيت الرواتب المركزي - HR')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="fas fa-file-excel text-success me-2"></i>شيت الرواتب المركزي: {{ $payroll->cycle_month }}</h4>
            <p class="text-muted small mb-0">الفترة من {{ $payroll->start_date->format('Y-m-d') }} إلى {{ $payroll->end_date->format('Y-m-d') }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('hr.payrolls.index') }}" class="btn btn-outline-secondary">عودة</a>
            <button class="btn btn-outline-success" onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
            @if($payroll->status === 'draft')
            <form action="{{ route('hr.payrolls.destroy', $payroll->id) }}" method="POST" class="d-inline-block" id="refreshForm">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-primary" onclick="if(confirm('هل أنت متأكد من رغبتك بتحديث (إعادة توليد) الشيت؟ سيتم حذف المسودة الحالية وإرجاعك لصفحة التوليد.')){ document.getElementById('refreshForm').submit(); }">
                    <i class="fas fa-sync-alt"></i> تحديث وإعادة توليد
                </button>
                <button type="submit" class="btn btn-outline-danger" onclick="return confirm('هل أنت متأكد من حذف هذه المسودة نهائياً وإعادة الاستحقاقات للانتظار؟')">
                    <i class="fas fa-trash"></i> حذف المسودة
                </button>
            </form>
            @endif
            @if($payroll->status === 'draft')
            <form action="{{ route('hr.payrolls.approve', $payroll->id) }}" method="POST" class="d-inline-block">
                @csrf
                <button type="submit" class="btn btn-success fw-bold shadow-sm" onclick="return confirm('تأكيد اعتماد المسير؟ لا يمكن التراجع أو التعديل بعد الاعتماد.')">
                    <i class="fas fa-check-double me-1"></i> اعتماد نهائي وترحيل للحسابات
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- ملخص مالي -->
    <div class="row g-3 mb-3 text-center">
        <div class="col-md-2"><div class="card border-0 shadow-sm bg-light"><div class="card-body p-2"><div class="text-muted small">الأساسي</div><div class="fw-bold">{{ number_format($summary['total_basic']) }}</div></div></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm bg-success bg-opacity-10"><div class="card-body p-2"><div class="text-success small">إجمالي المخصصات</div><div class="fw-bold text-success">{{ number_format($summary['total_allowances'] + $summary['total_bonuses'] + $summary['total_overtime']) }}</div></div></div></div>
        <div class="col-md-2"><div class="card border-0 shadow-sm bg-danger bg-opacity-10"><div class="card-body p-2"><div class="text-danger small">إجمالي الاستقطاعات</div><div class="fw-bold text-danger">{{ number_format($summary['total_penalties'] + $summary['total_absence'] + $summary['total_loans'] + $summary['total_social'] + $summary['total_tax']) }}</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-primary text-white"><div class="card-body p-2"><div class="small opacity-75">إجمالي الصافي للموظفين</div><div class="fw-bold fs-5">{{ number_format($summary['total_net']) }} د.ع</div></div></div></div>
        <div class="col-md-3"><div class="card border-0 shadow-sm bg-dark text-white"><div class="card-body p-2"><div class="small opacity-75">طرق الدفع (نقداً / بنك)</div><div class="fw-bold fs-6">{{ number_format($summary['total_cash_amount']) }} / {{ number_format($summary['total_bank_amount']) }}</div></div></div></div>
    </div>

    @if($payroll->status === 'draft')
        <div class="alert alert-warning py-2 mb-3 border-0 shadow-sm small">
            <i class="fas fa-info-circle me-1"></i>
            الشيت في وضع <strong>المسودة</strong>. يمكنك النقر على أرقام "المخصصات" لتعديلها يدوياً إذا دعت الحاجة. سيتم حفظ التعديل وإعادة حساب الصافي تلقائياً.
        </div>
    @endif

    <!-- جدول الرواتب المركزي -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="table-responsive" style="max-height: 70vh;">
            <table class="table table-bordered table-hover align-middle mb-0 text-center table-sm" style="white-space: nowrap;">
                <thead class="bg-light sticky-top shadow-sm" style="z-index: 10;">
                    <tr>
                        <th rowspan="2" class="align-middle bg-light">الرقم الوظيفي</th>
                        <th rowspan="2" class="align-middle bg-light text-start">اسم الموظف / القسم</th>
                        <th rowspan="2" class="align-middle bg-light">الأساسي</th>
                        <th colspan="3" class="bg-success bg-opacity-10 text-success border-success">الاستحقاقات والمكافآت (+)</th>
                        <th colspan="5" class="bg-danger bg-opacity-10 text-danger border-danger">الاستقطاعات والخصومات (-)</th>
                        <th rowspan="2" class="align-middle bg-primary bg-opacity-10 text-primary border-primary">الصافي للدفع</th>
                        <th rowspan="2" class="align-middle bg-light">طريقة الدفع</th>
                    </tr>
                    <tr>
                        <th class="bg-success bg-opacity-10 text-success border-success">المخصصات الثابتة</th>
                        <th class="bg-success bg-opacity-10 text-success border-success">العمل الإضافي</th>
                        <th class="bg-success bg-opacity-10 text-success border-success">المكافآت</th>
                        
                        <th class="bg-danger bg-opacity-10 text-danger border-danger">الغيابات</th>
                        <th class="bg-danger bg-opacity-10 text-danger border-danger">العقوبات</th>
                        <th class="bg-danger bg-opacity-10 text-danger border-danger">السلف</th>
                        <th class="bg-danger bg-opacity-10 text-danger border-danger">ضمان</th>
                        <th class="bg-danger bg-opacity-10 text-danger border-danger">ضريبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payroll->payrolls as $slip)
                    <tr id="slip-{{ $slip->id }}">
                        <td class="text-muted font-monospace">{{ $slip->employee->employee_code }}</td>
                        <td class="text-start fw-bold">
                            {{ $slip->employee->full_name }}
                            <div class="small text-muted fw-normal">{{ $slip->employee->department->name ?? '—' }}</div>
                        </td>
                        <td class="fw-bold">{{ number_format($slip->basic_salary) }}</td>
                        
                        <!-- (+) -->
                        <td class="bg-success bg-opacity-10">
                            @if($payroll->status === 'draft')
                                <input type="number" class="form-control form-control-sm text-center fw-bold border-success text-success bg-transparent ajax-slip-input" 
                                       data-id="{{ $slip->id }}" data-field="allowances" value="{{ $slip->allowances }}" style="width:100px; margin: 0 auto;">
                            @else
                                <span class="text-success fw-bold">{{ number_format($slip->allowances) }}</span>
                            @endif
                        </td>
                        <td class="text-success">{{ $slip->overtime_amount > 0 ? number_format($slip->overtime_amount) : '—' }}</td>
                        <td class="text-success">{{ $slip->bonuses_amount > 0 ? number_format($slip->bonuses_amount) : '—' }}</td>
                        
                        <!-- (-) -->
                        <td class="text-danger">{{ $slip->absence_deduction > 0 ? number_format($slip->absence_deduction) : '—' }}</td>
                        <td class="text-danger">{{ $slip->penalties_amount > 0 ? number_format($slip->penalties_amount) : '—' }}</td>
                        <td class="text-danger fw-bold">{{ $slip->loan_deduction > 0 ? number_format($slip->loan_deduction) : '—' }}</td>
                        <td class="text-danger small">{{ $slip->social_security_amount > 0 ? number_format($slip->social_security_amount) : '—' }}</td>
                        <td class="text-danger small">{{ $slip->tax_amount > 0 ? number_format($slip->tax_amount) : '—' }}</td>
                        
                        <!-- Net & Method -->
                        <td class="fw-bold text-primary fs-6 bg-primary bg-opacity-10" id="net-{{ $slip->id }}">
                            {{ number_format($slip->net_salary) }}
                        </td>
                        <td>
                            @if($slip->payment_method === 'bank') <span class="badge bg-primary">بنك</span>
                            @else <span class="badge bg-success">كاش</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.querySelectorAll('.ajax-slip-input').forEach(input => {
        input.addEventListener('change', function() {
            let slipId = this.dataset.id;
            let val = this.value;
            
            fetch(`{{ url('hr/payrolls/slip') }}/${slipId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    allowances: val
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update net salary UI
                    document.getElementById(`net-${slipId}`).innerText = new Intl.NumberFormat().format(data.net_salary);
                    // Flashing effect
                    document.getElementById(`slip-${slipId}`).style.backgroundColor = '#d1e7dd';
                    setTimeout(() => { document.getElementById(`slip-${slipId}`).style.backgroundColor = ''; }, 1000);
                } else {
                    alert(data.message);
                }
            });
        });
    });
</script>
@endpush
@endsection
