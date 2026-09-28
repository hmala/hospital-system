@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="fas fa-file-invoice text-success me-2"></i>فاتورة مركز العيون رقم: <span class="font-monospace text-primary">{{ $invoice->invoice_number }}</span>
                    </h3>
                    <div class="text-muted small">تاريخ الإنشاء: {{ $invoice->created_at->format('Y-m-d h:i A') }}</div>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('eye.cashier.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-right me-1"></i>العودة لسجل الكاشير
                    </a>
                    @if($invoice->status == 'paid')
                    <a href="{{ route('eye.cashier.printReceipt', $invoice) }}" class="btn btn-primary" target="_blank">
                        <i class="fas fa-print me-1"></i>طباعة الوصل
                    </a>
                    @endif
                </div>
            </div>

            <!-- بطاقة بيانات المريض والموعد -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4 bg-light rounded-3">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <span class="text-muted small d-block">اسم المريض</span>
                            <span class="fs-5 fw-bold text-primary">{{ $invoice->patient->name }}</span>
                            <div class="small text-muted">{{ $invoice->patient->phone ?? 'بدون هاتف' }} | {{ $invoice->patient->age ?? '-' }} سنة</div>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">جهة التأمين والدفع</span>
                            <span class="badge bg-secondary-subtle text-secondary fs-6 px-3 py-1 mt-1">
                                {{ $invoice->insurance_type == 'cash' ? 'نقدي كامل (Cash)' : 'الضمان الصحي العراقي' }}
                            </span>
                        </div>
                        <div class="col-md-4">
                            <span class="text-muted small d-block">الطبيب / الموعد</span>
                            <span class="fw-semibold text-dark">{{ $invoice->appointment->doctor->user->name ?? 'طبيب العيون' }}</span>
                            @if($invoice->appointment)
                                <span class="badge bg-info-subtle text-info d-block mt-1 w-fit">
                                    {{ $invoice->appointment->visit_type_arabic }} (#{{ $invoice->appointment->queue_number }})
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- جدول بنود الفاتورة والخدمات -->
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-dark"><i class="fas fa-list-check me-2 text-primary"></i>بنود وخدمات الفاتورة</h5>
                    @if($invoice->status != 'paid')
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-1"></i>إضافة فحص / عدسة / خدمة إضافية
                    </button>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>نوع الخدمة</th>
                                <th>البيان والتفاصيل</th>
                                <th class="text-center" style="width: 80px;">الكمية</th>
                                <th class="text-end" style="width: 140px;">سعر الوحدة</th>
                                <th class="text-end" style="width: 140px;">المجموع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $idx => $item)
                            <tr>
                                <td class="text-center">{{ $idx + 1 }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $item->service_type }}</span>
                                </td>
                                <td class="fw-semibold text-dark">{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">{{ number_format($item->unit_price) }} د.ع</td>
                                <td class="text-end fw-bold text-primary">{{ number_format($item->subtotal) }} د.ع</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="5" class="text-end fs-6">الإجمالي الكلي للخدمات:</th>
                                <th class="text-end fs-6 fw-bold text-dark">{{ number_format($invoice->total_amount) }} د.ع</th>
                            </tr>
                            @if($invoice->insurance_share > 0)
                            <tr>
                                <th colspan="5" class="text-end text-success">مساهمة الضمان الصحي (90%):</th>
                                <th class="text-end text-success fw-bold">{{ number_format($invoice->insurance_share) }} د.ع</th>
                            </tr>
                            <tr>
                                <th colspan="5" class="text-end text-primary">المبلغ المطلوب من المريض (10%):</th>
                                <th class="text-end text-primary fw-bold fs-5">{{ number_format($invoice->patient_share) }} د.ع</th>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- نموذج الدفع والتحصيل -->
            @if($invoice->status != 'paid')
            <div class="card border-success border-2 shadow-sm rounded-3">
                <div class="card-header bg-success text-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-hand-holding-usd me-2"></i>تسجيل السداد والتحصيل النقدي</h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('eye.cashier.pay', $invoice) }}" method="POST">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">المبلغ المستحق على المريض</label>
                                <div class="input-group">
                                    <input type="text" class="form-control fs-5 fw-bold text-primary bg-light" value="{{ number_format($invoice->patient_share) }}" readonly>
                                    <span class="input-group-text">د.ع</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">المبلغ المقبوض فعلياً <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="paid_amount" class="form-control fs-5 fw-bold text-success" value="{{ $invoice->patient_share }}" min="0" step="500" required>
                                    <span class="input-group-text">د.ع</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark">طريقة الدفع <span class="text-danger">*</span></label>
                                <select name="payment_method" class="form-select fs-6" required>
                                    <option value="cash" selected>نقدي (Cash)</option>
                                    <option value="card">بطاقة دفع إلكتروني (Card / POS)</option>
                                    <option value="insurance">تأمين صحي كامل</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-success px-5 py-2 fs-6 fw-bold">
                                <i class="fas fa-check-double me-1"></i>تأكيد الدفع وإصدار وصل القبض
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @else
            <div class="alert alert-success d-flex align-items-center justify-content-between p-4 rounded-3 shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-3x text-success me-3"></i>
                    <div>
                        <h4 class="alert-heading fw-bold mb-1">تم سداد هذه الفاتورة بالكامل ✅</h4>
                        <p class="mb-0 text-muted">
                            المبلغ المقبوض: <strong class="text-success">{{ number_format($invoice->paid_amount) }} د.ع</strong> | 
                            طريقة الدفع: <strong>{{ $invoice->payment_method }}</strong> | 
                            الكاشير: <strong>{{ $invoice->cashier->name ?? 'غير محدد' }}</strong>
                        </p>
                    </div>
                </div>
                <a href="{{ route('eye.cashier.printReceipt', $invoice) }}" class="btn btn-success px-4 py-2" target="_blank">
                    <i class="fas fa-print me-1"></i>طباعة وصل القبض
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- مودال إضافة بند إضافي للفاتورة -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2"></i>إضافة خدمة أو فحص أو مستلزم</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('eye.cashier.addItem', $invoice) }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">نوع البند</label>
                        <select name="service_type" class="form-select" id="modalServiceType" required>
                            <option value="investigation">فحص أجهزة عيون (OCT / ساحة بصرية)</option>
                            <option value="lens">عدسة عيون IOL</option>
                            <option value="procedure">إجراء جراحي / ليزر / حقن</option>
                            <option value="consumable">مستلزم طبي خاص</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">البيان / الوصف</label>
                        <input type="text" name="description" class="form-control" placeholder="مثال: تصوير شبكية طبقي OCT Macula" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold">الكمية</label>
                            <input type="number" name="quantity" class="form-control" value="1" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold">السعر (د.ع)</label>
                            <input type="number" name="unit_price" class="form-control" value="25000" min="0" step="500" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary fw-bold">إضافة وتحديث الإجمالي</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
