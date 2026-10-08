@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-9 col-md-11">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <!-- رأس الصفحة -->
                <div class="card-header bg-gradient bg-primary text-white p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0 fw-bold">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        فاتورة وتسديد خدمات الطوارئ
                    </h5>
                    <span class="badge bg-white text-primary font-monospace fs-7 px-3 py-1 rounded-pill">
                        حالة طوارئ #{{ $payment->emergency->id }}
                    </span>
                </div>

                <div class="card-body p-4">
                    @php
                        $em = $payment->emergency;
                        $patient = $em->patient;
                        
                        // فحص حالة الضمان
                        $isInsurance = false;
                        if (isset($em->is_insured)) {
                            $isInsurance = (bool) $em->is_insured;
                        } elseif (isset($em->insurance_type) && $em->insurance_type !== 'none') {
                            $isInsurance = true;
                        } elseif ($patient && ($patient->insurance_type === 'hi' || $patient->healthInsuranceCategory)) {
                            $isInsurance = true;
                        }
                        if ($payment->insurance_type === 'none') {
                            $isInsurance = false;
                        } elseif ($payment->payment_method === 'insurance' || ($payment->insurance_share > 0)) {
                            $isInsurance = true;
                        }

                        $insuranceType = $isInsurance ? 'hi' : 'none';
                        $emergencyCopay = $isInsurance && $patient ? $patient->getCopayPercentageFor('emergency') : 100.0;
                        $labCopay = $isInsurance && $patient ? $patient->getCopayPercentageFor('lab') : 100.0;
                        $radCopay = $isInsurance && $patient ? $patient->getCopayPercentageFor('radiology') : 100.0;

                        // بيانات المريض
                        if ($patient) {
                            $pname = $patient->user->name ?? 'غير محدد';
                            $pphone = $patient->user->phone ?? ($patient->phone ?? '---');
                            $pid = '#' . $patient->id;
                            $insCategory = $patient->healthInsuranceCategory?->name ?? 'ضمان صحي';
                        } elseif ($em->emergencyPatient) {
                            $pname = $em->emergencyPatient->name;
                            $pphone = $em->emergencyPatient->phone ?? '---';
                            $pid = '(طوارئ)';
                            $insCategory = null;
                        } else {
                            $pname = 'غير محدد';
                            $pphone = '---';
                            $pid = '-';
                            $insCategory = null;
                        }
                    @endphp

                    <!-- بطاقة المريض الموحدة -->
                    <div class="card bg-light border-0 rounded-3 p-3 mb-4 shadow-xs">
                        <div class="row align-items-center g-3">
                            <div class="col-md-6 d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                    <i class="fas fa-user-injured"></i>
                                </div>
                                <div>
                                    <h6 class="mb-1 fw-bold text-dark">{{ $pname }}</h6>
                                    <div class="d-flex align-items-center gap-2 flex-wrap text-muted small">
                                        <span><i class="fas fa-id-card me-1"></i>{{ $pid }}</span>
                                        <span>•</span>
                                        <span><i class="fas fa-phone me-1"></i>{{ $pphone }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 text-md-end">
                                <div class="d-flex align-items-center justify-content-md-end gap-2 flex-wrap">
                                    <span class="badge bg-{{ $em->priority_color ?? 'warning' }} px-3 py-1 rounded-pill">
                                        الأولوية: {{ $em->priority_text ?? $em->priority }}
                                    </span>
                                    @if($isInsurance)
                                        <span class="badge bg-success text-white px-3 py-1 rounded-pill">
                                            <i class="fas fa-shield-alt me-1"></i> {{ $insCategory ?: 'مشمول بالضمان الصحي' }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary text-white px-3 py-1 rounded-pill">
                                            <i class="fas fa-money-bill me-1"></i> كاش عادي
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @php
                        $invoiceItems = [];

                        // 1. خدمات الطوارئ غير المدفوعة
                        $unpaidServiceIds = \DB::table('emergency_emergency_service')
                            ->where('emergency_id', $payment->emergency_id)
                            ->whereNull('payment_id')
                            ->pluck('emergency_service_id');

                        foreach($em->services->whereIn('id', $unpaidServiceIds) as $service) {
                            $pricing = $service->calculateInsurancePricing($insuranceType, $emergencyCopay);
                            $invoiceItems[] = [
                                'category' => 'خدمة طوارئ',
                                'badge_class' => 'bg-info text-dark',
                                'icon' => 'fa-medkit',
                                'name' => $service->name,
                                'qty' => 1,
                                'is_covered' => $isInsurance && $pricing['is_covered'],
                                'price' => (float) $pricing['approved_price'],
                                'total' => (float) $pricing['approved_price'],
                                'patient_share' => (float) $pricing['patient_share'],
                                'insurance_share' => (float) $pricing['insurance_share'],
                            ];
                        }

                        // 2. التحاليل المخبرية غير المدفوعة
                        foreach($em->labRequests->whereNull('payment_id') as $labReq) {
                            foreach($labReq->labTests as $test) {
                                $pricing = $test->calculateInsurancePricing($insuranceType, $labCopay);
                                $invoiceItems[] = [
                                    'category' => 'تحليل مختبر',
                                    'badge_class' => 'bg-primary text-white',
                                    'icon' => 'fa-vial',
                                    'name' => $test->name,
                                    'qty' => 1,
                                    'is_covered' => $isInsurance && $pricing['is_covered'],
                                    'price' => (float) $pricing['approved_price'],
                                    'total' => (float) $pricing['approved_price'],
                                    'patient_share' => (float) $pricing['patient_share'],
                                    'insurance_share' => (float) $pricing['insurance_share'],
                                ];
                            }
                        }

                        // 3. الأشعة والتصوير غير المدفوعة
                        foreach($em->radiologyRequests->whereNull('payment_id') as $radReq) {
                            foreach($radReq->radiologyTypes as $type) {
                                $pricing = $type->calculateInsurancePricing($insuranceType, $radCopay);
                                $invoiceItems[] = [
                                    'category' => 'فحص أشعة',
                                    'badge_class' => 'bg-info text-white',
                                    'icon' => 'fa-x-ray',
                                    'name' => $type->name,
                                    'qty' => 1,
                                    'is_covered' => $isInsurance && $pricing['is_covered'],
                                    'price' => (float) $pricing['approved_price'],
                                    'total' => (float) $pricing['approved_price'],
                                    'patient_share' => (float) $pricing['patient_share'],
                                    'insurance_share' => (float) $pricing['insurance_share'],
                                ];
                            }
                        }

                        // 4. استشارة الطبيب
                        foreach($em->appointments as $ap) {
                            if($ap->payment_status === 'pending' && $ap->status !== 'cancelled') {
                                $apFee = (float) ($ap->consultation_fee ?? 0);
                                $invoiceItems[] = [
                                    'category' => 'استشارة',
                                    'badge_class' => 'bg-secondary text-white',
                                    'icon' => 'fa-user-md',
                                    'name' => $ap->reason ?: 'استشارة استشاري',
                                    'qty' => 1,
                                    'is_covered' => false,
                                    'price' => $apFee,
                                    'total' => $apFee,
                                    'patient_share' => $apFee,
                                    'insurance_share' => 0.0,
                                ];
                            }
                        }

                        // 5. رسوم متابعة الطبيب
                        if($em->doctor_follow_up_fee > 0 && !$em->follow_up_payment_id) {
                            $fFee = (float) $em->doctor_follow_up_fee;
                            $fPatient = $isInsurance ? round($fFee * ($emergencyCopay / 100.0), 2) : $fFee;
                            $fIns = round($fFee - $fPatient, 2);
                            $invoiceItems[] = [
                                'category' => 'متابعة طبيب',
                                'badge_class' => 'bg-warning text-dark',
                                'icon' => 'fa-stethoscope',
                                'name' => 'رسوم متابعة وإشراف طبيب الطوارئ',
                                'qty' => 1,
                                'is_covered' => $isInsurance,
                                'price' => $fFee,
                                'total' => $fFee,
                                'patient_share' => $fPatient,
                                'insurance_share' => $fIns,
                            ];
                        }

                        $subtotal = array_sum(array_column($invoiceItems, 'total'));
                        $insuranceCoveredTotal = array_sum(array_column($invoiceItems, 'insurance_share'));
                        if ($insuranceCoveredTotal <= 0 && $payment->insurance_share > 0) {
                            $insuranceCoveredTotal = (float) $payment->insurance_share;
                        }
                        
                        $patientRequiredAmount = (float) $payment->amount;
                        $paidAmount = $payment->paid_at ? (float) $payment->amount : 0.0;
                        $dueAmount = max(0.0, $patientRequiredAmount - $paidAmount);
                        $totalInvoiceAmount = $subtotal > 0 ? $subtotal : (float) ($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
                    @endphp

                    <!-- جدول الفاتورة الموحد والوحيد -->
                    <div class="card border rounded-3 overflow-hidden mb-4 shadow-xs">
                        <div class="card-header bg-light py-2 px-3">
                            <h6 class="mb-0 fw-bold text-dark">
                                <i class="fas fa-list-check me-1 text-primary"></i>
                                تفاصيل البنود والخدمات المطلوبة
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light text-secondary small text-nowrap">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">#</th>
                                        <th style="width: 120px;">النوع</th>
                                        <th>اسم البند / الخدمة</th>
                                        <th style="width: 60px;" class="text-center">الكمية</th>
                                        <th style="width: 140px;" class="text-end">السعر المعتمد</th>
                                        <th style="width: 140px;" class="text-end">الإجمالي</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($invoiceItems as $idx => $item)
                                        <tr>
                                            <td class="text-center font-monospace text-muted small">{{ $idx + 1 }}</td>
                                            <td>
                                                <span class="badge {{ $item['badge_class'] }} font-monospace" style="font-size: 0.72rem;">
                                                    <i class="fas {{ $item['icon'] }} me-1"></i> {{ $item['category'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="fw-semibold text-dark">{{ $item['name'] }}</span>
                                                @if($item['is_covered'])
                                                    <span class="badge bg-success-subtle text-success border border-success border-opacity-25 ms-1" style="font-size: 0.7rem;">
                                                        <i class="fas fa-shield-alt me-1"></i> ضمان صحي
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center font-monospace">{{ $item['qty'] }}</td>
                                            <td class="text-end font-monospace">{{ number_format($item['price'], 0) }} د.ع</td>
                                            <td class="text-end font-monospace fw-bold text-dark">{{ number_format($item['total'], 0) }} د.ع</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">
                                                لا توجد بنود غير مسددة لهذه الحالة
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end text-muted">المجموع الإجمالي للخدمات:</th>
                                        <th colspan="2" class="text-end font-monospace fw-bold">{{ number_format($totalInvoiceAmount, 0) }} د.ع</th>
                                    </tr>
                                    @if($isInsurance && $insuranceCoveredTotal > 0)
                                        <tr class="table-success bg-opacity-25">
                                            <th colspan="4" class="text-end text-success">
                                                <i class="fas fa-shield-alt me-1"></i> تغطية ومطالبة هيئة الضمان الصحي:
                                            </th>
                                            <th colspan="2" class="text-end font-monospace fw-bold text-success">- {{ number_format($insuranceCoveredTotal, 0) }} د.ع</th>
                                        </tr>
                                    @endif
                                    <tr class="table-warning bg-opacity-50">
                                        <th colspan="4" class="text-end fs-6 fw-bold text-dark">
                                            💵 الصافي المطلوب قبضه من المريض نقدياً:
                                        </th>
                                        <th colspan="2" class="text-end font-monospace fs-5 fw-bold text-primary">{{ number_format($dueAmount, 0) }} د.ع</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- نموذج الدفع والقبض الفوري -->
                    <div class="card border-primary bg-primary-subtle bg-opacity-10 rounded-3 p-3 shadow-sm">
                        <form action="{{ route('cashier.emergency.payment.process', $payment) }}" method="POST">
                            @csrf
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="fas fa-cash-register me-1"></i>
                                تسجيل العملية والقبض
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label fw-bold">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror" required>
                                        @if($dueAmount <= 0 && $isInsurance)
                                            <option value="insurance" selected>تأمين / ضمان صحي (مغطى 100%)</option>
                                            <option value="cash">نقدي (كاش)</option>
                                            <option value="card">بطاقة إلكترونية (POS)</option>
                                        @else
                                            <option value="cash" {{ old('payment_method', 'cash') == 'cash' ? 'selected' : '' }}>نقدي (كاش)</option>
                                            <option value="card" {{ old('payment_method') == 'card' ? 'selected' : '' }}>بطاقة إلكترونية (POS)</option>
                                            <option value="insurance" {{ old('payment_method') == 'insurance' ? 'selected' : '' }}>تأمين / ضمان صحي</option>
                                        @endif
                                    </select>
                                    @error('payment_method')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="amount" class="form-label fw-bold">المبلغ المقبوض نقدياً <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" 
                                               name="amount" 
                                               id="amount" 
                                               class="form-control font-monospace fw-bold fs-6 @error('amount') is-invalid @enderror"
                                               value="{{ old('amount', $dueAmount) }}" 
                                               step="500" 
                                               min="0" 
                                               required>
                                        <span class="input-group-text bg-light text-muted">د.ع</span>
                                    </div>
                                    @error('amount')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label for="notes" class="form-label text-muted small">ملاحظات إضافية (اختياري)</label>
                                    <input type="text" 
                                           name="notes" 
                                           id="notes" 
                                           class="form-control form-control-sm @error('notes') is-invalid @enderror" 
                                           placeholder="أي ملاحظات حول السند أو الدفع..."
                                           value="{{ old('notes') }}">
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                                <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary px-3">
                                    <i class="fas fa-arrow-right me-1"></i> العودة لقائمة الكاشير
                                </a>
                                <button type="submit" class="btn btn-success btn-lg px-4 fw-bold shadow-sm">
                                    <i class="fas fa-check-circle me-1"></i>
                                    {{ $dueAmount <= 0 && $isInsurance ? 'اعتماد وتسديد بالضمان (0 د.ع)' : 'تأكيد القبض وإصدار الوصل' }}
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection