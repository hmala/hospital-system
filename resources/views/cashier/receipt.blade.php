@extends('layouts.app')

@section('content')
<div class="container py-3">
    <!-- شريط التحكم العلوي -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                <i class="fas fa-receipt text-success me-2"></i>
                إيصال قبض رسمي
            </h4>
            <span class="text-muted small">رقم الإيصال: <strong class="text-dark">{{ $payment->receipt_number }}</strong></span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('cashier.receipt.print', $payment->id) }}" class="btn btn-primary px-4 rounded-pill shadow-sm" target="_blank">
                <i class="fas fa-print me-1"></i> طباعة الوصل
            </a>
            <a href="{{ auth()->user()->hasRole('consultation_receptionist') && !auth()->user()->hasRole('admin') ? route('consultant-availability.index') : route('cashier.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
                <i class="fas fa-arrow-right me-1"></i> العودة للكاشير
            </a>
        </div>
    </div>

    @php
        // استخراج بيانات المريض
        $p = $payment->patient;
        if(!$p && $payment->emergency) {
            $ep = $payment->emergency->emergencyPatient;
            $pname = $ep ? $ep->name : 'غير محدد';
            $pphone = $ep ? ($ep->phone ?? 'غير محدد') : 'غير محدد';
            $pid = '(طوارئ)';
        } else {
            $pname = $p ? ($p->user?->name ?? 'غير محدد') : 'غير محدد';
            $pphone = $p ? ($p->user?->phone ?? 'غير محدد') : 'غير محدد';
            $pid = $p ? ($p->national_id ?? '#'.$p->id) : '-';
        }

        // استخراج تفاصيل الخدمات المسددة
        $lineItems = [];
        $isInsured = $payment->insurance_type && $payment->insurance_type !== 'none';
        $copayPct = (float)($payment->copay_percentage ?? 0);

        // 1. كشف استشارية أو سونار
        if ($payment->appointment) {
            $serviceTitle = 'رسوم كشف العيادة الاستشارية';
            $scanType = null;
            if ($payment->appointment->visit) {
                $medReq = \App\Models\Request::where('visit_id', $payment->appointment->visit->id)->where('type', 'radiology')->first();
                if ($medReq) {
                    $details = is_string($medReq->details) ? json_decode($medReq->details, true) : $medReq->details;
                    $radTypeId = $details['ultrasound_type_id'] ?? ($details['radiology_type_ids'][0] ?? null);
                    if ($radTypeId) {
                        $scanType = \App\Models\RadiologyType::find($radTypeId);
                    }
                }
            }
            if ($scanType) {
                $serviceTitle = 'فحص سونار: ' . $scanType->name;
            } elseif (!empty($payment->appointment->reason) && $payment->appointment->reason !== 'كشف طبي عام') {
                $serviceTitle = $payment->appointment->reason;
            }

            $approvedPrice = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
            $patientShare = (float)($payment->patient_share > 0 ? $payment->patient_share : $payment->amount);
            $insuranceShare = (float)($payment->insurance_share ?? max(0, $approvedPrice - $patientShare));

            $lineItems[] = [
                'name' => $serviceTitle,
                'category' => $scanType ? 'سونار' : 'استشارية',
                'doctor' => $payment->appointment->doctor?->user?->name ? ('د. ' . $payment->appointment->doctor->user->name) : 'عام',
                'approved' => $approvedPrice,
                'patient' => $patientShare,
                'insurance' => $insuranceShare,
            ];
        }

        // 2. طلبات الفحوصات الطبية
        if ($payment->request) {
            $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;
            $doctorName = $payment->request->visit?->doctor?->user?->name ? ('د. ' . $payment->request->visit->doctor->user->name) : 'المختبر / الأشعة';

            if ($payment->request->type === 'lab' && isset($details['lab_test_ids'])) {
                foreach ($details['lab_test_ids'] as $testId) {
                    $test = \App\Models\LabTest::find($testId);
                    if ($test) {
                        $base = (float)$test->price;
                        $pat = $isInsured ? round($base * ($copayPct / 100)) : $base;
                        $lineItems[] = [
                            'name' => 'تحليل: ' . $test->name . ($test->code ? ' (' . $test->code . ')' : ''),
                            'category' => 'تحاليل مختبرية',
                            'doctor' => $doctorName,
                            'approved' => $base,
                            'patient' => $pat,
                            'insurance' => max(0, $base - $pat),
                        ];
                    }
                }
            } elseif ($payment->request->type === 'radiology' && isset($details['radiology_type_ids'])) {
                foreach ($details['radiology_type_ids'] as $typeId) {
                    $type = \App\Models\RadiologyType::find($typeId);
                    if ($type) {
                        $base = (float)$type->base_price;
                        $pat = $isInsured ? round($base * ($copayPct / 100)) : $base;
                        $lineItems[] = [
                            'name' => 'أشعة: ' . $type->name,
                            'category' => 'فحص إشعاعي',
                            'doctor' => $doctorName,
                            'approved' => $base,
                            'patient' => $pat,
                            'insurance' => max(0, $base - $pat),
                        ];
                    }
                }
            }
        }

        // إذا لم توجد تفاصيل متعددة نستخدم السجل المباشر
        if (empty($lineItems)) {
            $approvedPrice = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
            $patientShare = (float)($payment->patient_share > 0 ? $payment->patient_share : $payment->amount);
            $insuranceShare = (float)($payment->insurance_share ?? max(0, $approvedPrice - $patientShare));

            $docTitle = $payment->appointment?->doctor?->user?->name 
                ? ('د. ' . $payment->appointment->doctor->user->name) 
                : ($payment->emergency?->doctor?->user?->name ? ('د. ' . $payment->emergency->doctor->user->name) : 'الكادر الطبي');

            $lineItems[] = [
                'name' => $payment->description ?: 'خدمة طبية عامة',
                'category' => $payment->payment_type ?? 'عام',
                'doctor' => $docTitle,
                'approved' => $approvedPrice,
                'patient' => $patientShare,
                'insurance' => $insuranceShare,
            ];
        }

        $totalApprovedSum = (float)($payment->total_amount > 0 ? $payment->total_amount : collect($lineItems)->sum('approved'));
        $totalPatientSum = (float)($payment->amount);
        $totalInsuranceSum = (float)($payment->insurance_share ?? max(0, $totalApprovedSum - $totalPatientSum));
    @endphp

    <!-- بطاقة الإيصال المصممة بنمط السند الرقمي المتقن -->
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white" id="receipt_card">
                
                <!-- ترويسة الإيصال الأنيقة -->
                <div class="p-4 border-bottom text-center" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="text-start">
                            <h5 class="fw-bold text-dark mb-0">مستشفى الكفاءات الأهلي</h5>
                            <small class="text-muted">قسم الصندوق والحسابات الطبية</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">
                                <i class="fas fa-check-circle me-1"></i> تم القبض بنجاح
                            </span>
                        </div>
                    </div>

                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <span class="text-muted small d-block">رقم السند:</span>
                            <strong class="text-primary fs-5">{{ $payment->receipt_number }}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">تاريخ ووقت القبض:</span>
                            <strong class="text-dark">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d h:i A') : now()->format('Y-m-d h:i A') }}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">أمين الصندوق (الكاشير):</span>
                            <strong class="text-dark">{{ optional($payment->cashier)->name ?? 'النظام' }}</strong>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- بيانات المريض والتغطية -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <span class="text-muted small d-block mb-1"><i class="fas fa-user text-primary me-1"></i>بيانات المريض:</span>
                                <h6 class="fw-bold mb-1 text-dark">{{ $pname }}</h6>
                                <div class="text-muted small">
                                    <span>الهوية: <strong>{{ $pid }}</strong></span> | 
                                    <span>الهاتف: <strong>{{ $pphone }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <span class="text-muted small d-block mb-1"><i class="fas fa-shield-alt text-success me-1"></i>التغطية والضمان:</span>
                                @if($isInsured)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-success">{{ $payment->insurance_type === 'hi' ? 'الضمان الصحي الوطني' : 'ضمان قوى الأمن الداخلي' }}</span>
                                        <span class="badge bg-light text-dark border">تحمل المريض: {{ number_format($copayPct, 0) }}%</span>
                                    </div>
                                    @if($payment->insurance_card_no)
                                        <small class="text-muted">رقم البطاقة/الدفتر: <strong class="text-dark">{{ $payment->insurance_card_no }}</strong></small>
                                    @endif
                                @else
                                    <h6 class="fw-bold mb-0 text-secondary">دفع نقدي كامل (خاص / 100%)</h6>
                                    <small class="text-muted">غير مشمول بالتغطية التأمينية</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- جدول الخدمات المسددة -->
                    <div class="mb-4">
                        <div class="table-responsive border rounded-3 overflow-hidden">
                            <table class="table table-bordered table-hover mb-0 align-middle">
                                <thead class="table-light text-muted small">
                                    <tr>
                                        <th width="40" class="text-center">#</th>
                                        <th>الخدمة / الفحص</th>
                                        <th>الطبيب / القسم</th>
                                        <th width="120" class="text-end">السعر المعتمد</th>
                                        @if($isInsured)
                                            <th width="120" class="text-end text-primary">حصة الضمان</th>
                                        @endif
                                        <th width="130" class="text-end text-success">المقبوض من المريض</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lineItems as $idx => $item)
                                        <tr>
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <strong class="text-dark">{{ $item['name'] }}</strong>
                                                <span class="badge bg-light text-dark border ms-1">{{ $item['category'] }}</span>
                                            </td>
                                            <td class="text-muted small">{{ $item['doctor'] }}</td>
                                            <td class="text-end fw-semibold">{{ number_format($item['approved']) }} د.ع</td>
                                            @if($isInsured)
                                                <td class="text-end text-primary fw-semibold">{{ number_format($item['insurance']) }} د.ع</td>
                                            @endif
                                            <td class="text-end text-success fw-bold">{{ number_format($item['patient']) }} د.ع</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- بطاقة المجموع والتسوية المالية -->
                    <div class="p-3 rounded-4 border mb-4" style="background: linear-gradient(135deg, #f0fdf4 0%, #f8fafc 100%);">
                        <div class="row align-items-center g-3 text-center text-md-start">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center gap-2 justify-content-center justify-content-md-start">
                                    <span class="text-muted small">طريقة الدفع:</span>
                                    <span class="badge bg-white text-dark border px-3 py-2 fs-7 fw-bold">
                                        @if($payment->payment_method === 'card')
                                            💳 بطاقة دفع إلكتروني (POS / Card)
                                        @else
                                            💵 نقدي (Cash)
                                        @endif
                                    </span>
                                </div>
                                @if($payment->notes)
                                    <div class="text-muted small mt-2">
                                        <i class="fas fa-comment-alt me-1"></i>ملاحظات: {{ $payment->notes }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6 text-md-end">
                                @if($isInsured)
                                    <div class="text-muted small mb-1">
                                        إجمالي التسعيرة: {{ number_format($totalApprovedSum) }} د.ع | تغطية الضمان: <strong class="text-primary">{{ number_format($totalInsuranceSum) }} د.ع</strong>
                                    </div>
                                @endif
                                <div class="d-inline-block bg-white p-2 px-4 rounded-3 border border-success shadow-sm">
                                    <span class="text-muted small d-block">المبلغ المقبوض نقداً من المريض:</span>
                                    <span class="fs-3 fw-bold text-success">{{ number_format($totalPatientSum) }} د.ع</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تذييل الوصل الرسمي -->
                    <div class="text-center pt-2 border-top text-muted small">
                        <p class="mb-1">هذا الإيصال سند مالي رسمي صادر إلكترونياً ولا يحتاج إلى ختم يدوي إضافي ما لم يُطلب رسمياً.</p>
                        <small class="text-muted">مستشفى الكفاءات الأهلي — نتمنى لكم دوام الصحة والعافية</small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
