@extends('layouts.app')

@section('content')
<div class="container py-3">
    <!-- شريط التحكم العلوي -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1 text-dark">
                @if((float)$payment->amount < 0)
                    <i class="fas fa-undo text-danger me-2"></i>
                    {{ __('سند استرجاع مالي رسمي (Refund Voucher)') }}
                @else
                    <i class="fas fa-receipt text-success me-2"></i>
                    {{ __('إيصال قبض رسمي') }}
                @endif
            </h4>
            <span class="text-muted small">{{ __("رقم الإيصال:") }} <strong class="text-dark">{{ $payment->receipt_number }}</strong></span>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            @if($payment->isVoided())
                <span class="badge bg-danger fs-6 d-flex align-items-center px-3 py-2 rounded-pill shadow-sm">
                    <i class="fas fa-ban me-1"></i> {{ __('وصل ملغى ومبطل') }} ({{ $payment->void_type === 'rebill' ? __('أُعيد فتحه للتصحيح') : __('استرداد مالي') }})
                </span>
            @elseif($payment->paid_at)
                @can('void payment receipt')
                    <a href="{{ route('cashier.payments.void.form', $payment->id) }}" class="btn btn-outline-danger px-3 rounded-pill shadow-sm">
                        <i class="fas fa-undo-alt me-1"></i> {{ __("إبطال الوصل / إعادة فتح للتصحيح") }}
                    </a>
                @endcan
            @endif
            <a href="{{ route('cashier.receipt.print', $payment->id) }}" class="btn btn-primary px-4 rounded-pill shadow-sm" target="_blank">
                <i class="fas fa-print me-1"></i> {{ __("طباعة الوصل") }}
            </a>
            <a href="{{ route('cashier.index') }}" class="btn btn-outline-secondary px-3 rounded-pill">
                <i class="fas fa-arrow-right me-1"></i> {{ __("العودة للكاشير") }}
            </a>
        </div>
    </div>

    @if($payment->isVoided())
        <div class="alert alert-danger border-2 border-danger rounded-4 shadow-sm p-3 mb-4 d-flex align-items-center gap-3">
            <div class="rounded-circle bg-danger text-white p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="fas fa-exclamation-triangle fs-4"></i>
            </div>
            <div>
                <h6 class="fw-bold mb-1 text-danger">{{ __("تنبيه رقابي: هذا الوصل تم إبطاله رسمياً وغير معتمد مالياً") }}</h6>
                <div class="small text-dark">
                    <strong>{{ __("تاريخ الإبطال:") }}</strong> {{ $payment->voided_at?->format('Y-m-d H:i:s') }} |
                    <strong>{{ __("بواسطة:") }}</strong> {{ $payment->voidedBy?->name ?? __('المشرف الإداري') }} |
                    <strong>{{ __("نوع الإجراء:") }}</strong> {{ $payment->void_type === 'rebill' ? __('إعادة فتح في الكاشير للتسديد الصحيح') : __('إلغاء واسترداد نقدي') }} <br>
                    <strong>{{ __("سبب الإلغاء:") }}</strong> <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">{{ $payment->void_reason }}</span>
                </div>
            </div>
        </div>
    @endif

    @php
        // استخراج بيانات المريض
        $p = $payment->patient;
        if ($payment->incubatorReservation) {
            $res = $payment->incubatorReservation;
            $pname = $res->baby_name ?: (__('طفل') . ' ' . ($res->patient?->name ?? __('غير محدد')));
            $pphone = $res->patient?->phone ?? ($res->patient?->user?->phone ?? '—');
            $pid = __('حاضنة') . ' #' . ($res->incubator?->id ?? $res->id);
        } elseif(!$p && $payment->emergency) {
            $ep = $payment->emergency->emergencyPatient;
            $pname = $ep ? $ep->name : __('غير محدد');
            $pphone = $ep ? ($ep->phone ?? __('غير محدد')) : __('غير محدد');
            $pid = '(' . __('طوارئ') . ')';
        } else {
            $pname = $p ? ($p->user?->name ?? __('غير محدد')) : __('غير محدد');
            $pphone = $p ? ($p->user?->phone ?? __('غير محدد')) : __('غير محدد');
            $pid = $p ? ($p->national_id ?? '#'.$p->id) : '-';
        }

        // استخراج تفاصيل الخدمات المسددة
        $lineItems = [];
        $isInsured = $payment->insurance_type && $payment->insurance_type !== 'none';
        $copayPct = (float)($payment->copay_percentage ?? 0);

        // 1. كشف استشارية أو سونار
        if ($payment->appointment) {
            $serviceTitle = __(__('رسوم كشف العيادة الاستشارية'));
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
                $serviceTitle = __('فحص سونار:') . ' ' . $scanType->name;
            } elseif (!empty($payment->appointment->reason) && $payment->appointment->reason !== 'كشف طبي عام') {
                $serviceTitle = $payment->appointment->reason;
            }

            $approvedPrice = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
            $patientShare = (float)($payment->patient_share > 0 ? $payment->patient_share : $payment->amount);
            $insuranceShare = (float)($payment->insurance_share ?? max(0, $approvedPrice - $patientShare));

            $lineItems[] = [
                'name' => $serviceTitle,
                'category' => $scanType ? __('سونار') : __('استشارية'),
                'doctor' => $payment->appointment->doctor?->user?->name ? (__('د.') . ' ' . $payment->appointment->doctor->user->name) : __('عام'),
                'approved' => $approvedPrice,
                'patient' => $patientShare,
                'insurance' => $insuranceShare,
            ];
        }

        // 2. طلبات الفحوصات الطبية الموحدة
        $linkedRequests = \App\Models\Request::where('payment_id', $payment->id)->get();
        if ($linkedRequests->isEmpty() && $payment->request) {
            $linkedRequests = collect([$payment->request]);
        }

        foreach ($linkedRequests as $reqItem) {
            $details = is_string($reqItem->details) ? json_decode($reqItem->details, true) : $reqItem->details;
            $doctorName = $reqItem->visit?->doctor?->user?->name ? (__('د.') . ' ' . $reqItem->visit->doctor->user->name) : __('المختبر / الأشعة');

            if ($reqItem->type === 'lab') {
                $packageId = $details['package_id'] ?? null;
                $pkg = $packageId ? \App\Models\Package::find($packageId) : null;

                if ($pkg) {
                    $pkgTestIds = $pkg->labTests()->pluck('lab_tests.id')->toArray();
                    $base = (float)$pkg->price;
                    $pat = $isInsured ? round($base * ($copayPct / 100)) : $base;
                    $lineItems[] = [
                        'name' => '📦 ' . __('باقة:') . ' ' . $pkg->name . ' (' . count($pkgTestIds) . ' ' . __('تحاليل مشمولة') . ')',
                        'category' => __('باقة مختبرية متكاملة'),
                        'doctor' => $doctorName,
                        'approved' => $base,
                        'patient' => $pat,
                        'insurance' => max(0, $base - $pat),
                    ];

                    $allTestIds = $details['lab_test_ids'] ?? [];
                    $extraTestIds = array_diff($allTestIds, $pkgTestIds);
                    foreach ($extraTestIds as $testId) {
                        $test = \App\Models\LabTest::find($testId);
                        if ($test) {
                            $pricing = $isInsured && method_exists($test, 'calculateInsurancePricing')
                                ? $test->calculateInsurancePricing($payment->insurance_type, $copayPct)
                                : null;
                            $base = $pricing ? (float)$pricing['approved_price'] : (float)$test->price;
                            $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                            $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                            $lineItems[] = [
                                'name' => '🔬 ' . __('تحليل إضافي:') . ' ' . $test->name . ($test->code ? ' (' . $test->code . ')' : ''),
                                'category' => __('تحاليل مختبرية إضافية'),
                                'doctor' => $doctorName,
                                'approved' => $base,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                    }
                } elseif (!empty($details['lab_test_ids'])) {
                    foreach ($details['lab_test_ids'] as $testId) {
                        $test = \App\Models\LabTest::find($testId);
                        if ($test) {
                            $pricing = $isInsured && method_exists($test, 'calculateInsurancePricing')
                                ? $test->calculateInsurancePricing($payment->insurance_type, $copayPct)
                                : null;
                            $base = $pricing ? (float)$pricing['approved_price'] : (float)$test->price;
                            $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                            $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                            $lineItems[] = [
                                'name' => __('تحليل:') . ' ' . $test->name . ($test->code ? ' (' . $test->code . ')' : ''),
                                'category' => __('تحاليل مختبرية'),
                                'doctor' => $doctorName,
                                'approved' => $base,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                    }
                } elseif (!empty($details['tests'])) {
                    foreach ($details['tests'] as $tName) {
                        $test = \App\Models\LabTest::where('name', $tName)->orWhere('code', $tName)->first();
                        if ($test) {
                            $pricing = $isInsured && method_exists($test, 'calculateInsurancePricing')
                                ? $test->calculateInsurancePricing($payment->insurance_type, $copayPct)
                                : null;
                            $base = $pricing ? (float)$pricing['approved_price'] : (float)$test->price;
                            $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                            $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                            $lineItems[] = [
                                'name' => __('تحليل:') . ' ' . $test->name . ($test->code ? ' (' . $test->code . ')' : ''),
                                'category' => __('تحاليل مختبرية'),
                                'doctor' => $doctorName,
                                'approved' => $base,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                    }
                }
            } elseif ($reqItem->type === 'radiology') {
                $typeIds = (array)($details['radiology_type_ids'] ?? $details['radiology_types'] ?? $details['radiology_type_id'] ?? []);
                $radTargets = $details['radiology_targets'] ?? [];
                foreach ($typeIds as $typeId) {
                    $type = \App\Models\RadiologyType::find($typeId);
                    if ($type) {
                        $pricing = $isInsured && method_exists($type, 'calculateInsurancePricing')
                            ? $type->calculateInsurancePricing($payment->insurance_type, $copayPct)
                            : null;
                        $base = $pricing ? (float)$pricing['approved_price'] : (float)($type->base_price ?? $type->price ?? 0);
                        $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                        $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                        $targetPart = isset($radTargets[$typeId]) && !empty($radTargets[$typeId]) ? trim($radTargets[$typeId]) : null;
                        $lineItems[] = [
                            'name' => __('أشعة:') . ' ' . $type->name . ($targetPart ? " [العضو: {$targetPart}]" : ""),
                            'category' => __('فحص إشعاعي'),
                            'doctor' => $doctorName,
                            'approved' => $base,
                            'patient' => $pat,
                            'insurance' => $ins,
                        ];
                    }
                }
            } elseif ($reqItem->type === 'nursing') {
                $nursingServiceIds = (array) ($details['nursing_services'] ?? []);
                if (!empty($nursingServiceIds)) {
                    foreach ($nursingServiceIds as $nsId) {
                        $nSvc = \App\Models\EmergencyService::find($nsId);
                        if ($nSvc) {
                            $pricing = $isInsured && method_exists($nSvc, 'calculateInsurancePricing')
                                ? $nSvc->calculateInsurancePricing($payment->insurance_type, $copayPct)
                                : null;
                            $base = $pricing ? (float)$pricing['total_amount'] : (float)$nSvc->price;
                            $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                            $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                            $lineItems[] = [
                                'name' => __('تمريض:') . ' ' . $nSvc->name,
                                'category' => __('خدمات تمريضية وتخطيط قلب'),
                                'doctor' => $doctorName,
                                'approved' => $base,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                    }
                } else {
                    $desc = trim($details['description'] ?? $reqItem->description ?? 'تخطيط قلب');
                    $descLower = mb_strtolower($desc);
                    $matchedSvc = null;
                    if (str_contains($desc, 'تخطيط') || str_contains($desc, 'تحطيط') || str_contains($descLower, 'ecg') || str_contains($descLower, 'echo') || str_contains($desc, 'ايكو') || str_contains($descLower, 'hear echo')) {
                        $matchedSvc = \App\Models\EmergencyService::where('name', 'تخطيط قلب')->first()
                            ?? \App\Models\EmergencyService::find(93)
                            ?? \App\Models\EmergencyService::where('name', 'LIKE', '%تخطيط%')->orderBy('id', 'desc')->first();
                    }
                    if ($matchedSvc) {
                        $pricing = $isInsured && method_exists($matchedSvc, 'calculateInsurancePricing')
                            ? $matchedSvc->calculateInsurancePricing($payment->insurance_type, $copayPct)
                            : null;
                        $base = $pricing ? (float)$pricing['total_amount'] : (float)$matchedSvc->price;
                        $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                        $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                    } else {
                        $base = 15000.00;
                        $pat = $isInsured ? round($base * ($copayPct / 100)) : $base;
                        $ins = max(0, $base - $pat);
                    }
                    $cleanTitle = (str_contains($reqItem->description ?? '', 'تخطيط') || str_contains($reqItem->description ?? '', 'تحطيط')) ? __('فحص تخطيط قلب (ECG)') : ($reqItem->description ?: __('خدمة تمريضية'));
                    $lineItems[] = [
                        'name' => $cleanTitle,
                        'category' => __('خدمات تمريضية وتخطيط قلب'),
                        'doctor' => $doctorName,
                        'approved' => $base,
                        'patient' => $pat,
                        'insurance' => $ins,
                    ];
                }
            }
        }

        // 2.1 خدمات إضافية وفندقية ملحقة بالطلب والزيارة
        $paidVisitExtraServices = \App\Models\VisitExtraService::where('payment_id', $payment->id)->get();
        if ($paidVisitExtraServices->isEmpty() && $payment->request_id) {
            $paidVisitExtraServices = \App\Models\VisitExtraService::where(function($q) use ($payment) {
                $q->where('request_id', $payment->request_id);
                if ($payment->request?->visit_id) {
                    $q->orWhere('visit_id', $payment->request->visit_id);
                }
            })->where('payment_status', 'paid')->get();
        }
        foreach ($paidVisitExtraServices as $extraSvc) {
            $eTotal = (float)$extraSvc->total_amount;
            $lineItems[] = [
                'name' => __('خدمة إضافية:') . ' ' . $extraSvc->name . ($extraSvc->quantity > 1 ? " (×{$extraSvc->quantity})" : '') . ($extraSvc->notes ? ' - ' . $extraSvc->notes : ''),
                'category' => __('خدمات إضافية وفندقية'),
                'doctor' => __('الكاشير'),
                'approved' => $eTotal,
                'patient' => $eTotal,
                'insurance' => 0,
            ];
        }

        // 3. خدمات واستشارات الطوارئ
        if ($payment->emergency) {
            // استشارات الطوارئ للأطباء الاستشاريين
            $emergencyAppointments = $payment->emergency->appointments()
                ->where('status', '!=', 'cancelled')
                ->with('doctor.user')
                ->get();

            $paidServices = \DB::table('emergency_emergency_service')
                ->join('emergency_services', 'emergency_emergency_service.emergency_service_id', '=', 'emergency_services.id')
                ->where('emergency_emergency_service.payment_id', $payment->id)
                ->select('emergency_services.name', 'emergency_services.price', 'emergency_emergency_service.quantity')
                ->get();
            foreach ($paidServices as $svc) {
                if ($emergencyAppointments->isNotEmpty() && (str_contains($svc->name, 'استشارة') || $svc->name === 'استشارة اخصائي')) {
                    continue;
                }
                $qty = max(1, (int) ($svc->quantity ?? 1));
                $unitBase = (float) ($svc->price ?? 0);
                $base = $unitBase * $qty;
                $pat = $isInsured ? round($base * ($copayPct / 100)) : $base;
                $nameStr = __('خدمة طوارئ:') . ' ' . $svc->name . ($qty > 1 ? " (×{$qty})" : '');
                $lineItems[] = [
                    'name' => $nameStr,
                    'category' => __('طوارئ'),
                    'doctor' => $payment->emergency->doctor?->user?->name ? (__('د.') . ' ' . $payment->emergency->doctor->user->name) : __('كادر الطوارئ'),
                    'approved' => $base,
                    'patient' => $pat,
                    'insurance' => max(0, $base - $pat),
                ];
            }

            foreach ($emergencyAppointments as $apt) {
                $docName = $apt->doctor?->user?->name ? (__('د.') . ' ' . preg_replace('/^د[\.\s]+/u', '', $apt->doctor->user->name)) : __('الاستشاري');
                $fee = (float)($apt->consultation_fee ?? 0);
                if ($fee <= 0) continue;
                $lineItems[] = [
                    'name' => __('استشارة طبية طوارئ:') . ' ' . $docName,
                    'category' => __('استشارة طوارئ'),
                    'doctor' => $docName,
                    'approved' => $fee,
                    'patient' => $fee,
                    'insurance' => 0.0,
                ];
            }

            // طلبات تحاليل المختبر المسددة بهذا الإيصال
            $paidLabs = $payment->emergency->labRequests()->where('payment_id', $payment->id)->with('labTests')->get();
            foreach ($paidLabs as $labReq) {
                foreach ($labReq->labTests as $lt) {
                    $base = (float)($lt->price ?? 0);
                    $pricing = $isInsured && method_exists($lt, 'calculateInsurancePricing')
                        ? $lt->calculateInsurancePricing($payment->insurance_type, $copayPct)
                        : null;
                    $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                    $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                    $lineItems[] = [
                        'name' => __('تحليل طوارئ:') . ' ' . $lt->name . ($lt->code ? ' (' . $lt->code . ')' : ''),
                        'category' => __('مختبر طوارئ'),
                        'doctor' => $payment->emergency->doctor?->user?->name ? (__('د.') . ' ' . $payment->emergency->doctor->user->name) : __('المختبر'),
                        'approved' => $base,
                        'patient' => $pat,
                        'insurance' => $ins,
                    ];
                }
            }

            // طلبات الأشعة والتصوير الطبي المسددة بهذا الإيصال
            $paidRads = $payment->emergency->radiologyRequests()->where('payment_id', $payment->id)->with('radiologyTypes')->get();
            foreach ($paidRads as $radReq) {
                foreach ($radReq->radiologyTypes as $rt) {
                    $base = (float)($rt->base_price ?? $rt->price ?? 0);
                    $pricing = $isInsured && method_exists($rt, 'calculateInsurancePricing')
                        ? $rt->calculateInsurancePricing($payment->insurance_type, $copayPct)
                        : null;
                    $pat = $pricing ? (float)$pricing['patient_share'] : ($isInsured ? round($base * ($copayPct / 100)) : $base);
                    $ins = $pricing ? (float)$pricing['insurance_share'] : max(0, $base - $pat);
                    $lineItems[] = [
                        'name' => __('أشعة طوارئ:') . ' ' . $rt->name,
                        'category' => __('أشعة وتصوير'),
                        'doctor' => $payment->emergency->doctor?->user?->name ? (__('د.') . ' ' . $payment->emergency->doctor->user->name) : __('قسم الأشعة'),
                        'approved' => $base,
                        'patient' => $pat,
                        'insurance' => $ins,
                    ];
                }
            }
        }

        // 4. العمليات الجراحية والخدمات الملحقة والإضافية
        if ($payment->surgery || $payment->payment_type === 'surgery') {
            $surgery = $payment->surgery;
            if (!$surgery && preg_match('/ID: #(\d+)/', $payment->description, $matches)) {
                $surgery = \App\Models\Surgery::with(['doctor.user', 'room', 'extraServices'])->find($matches[1]);
            }
            if ($surgery) {
                $surgDoc = $surgery->doctor?->user?->name ? (__('د.') . ' ' . $surgery->doctor->user->name) : ($surgery->surgeon_name ?: __('جراحة'));
                
                $pkgItems = is_array($payment->included_package_items) ? $payment->included_package_items : (json_decode($payment->included_package_items, true) ?: []);

                if (preg_match('/العناصر المدفوعة:\n(.+)/s', $payment->description ?? '', $descMatches)) {
                    $itemLines = explode("\n", trim($descMatches[1]));
                    foreach ($itemLines as $line) {
                        $line = trim(str_replace('- ', '', $line));
                        if (empty($line) || str_starts_with($line, 'تغطية الضمان:') || str_starts_with($line, 'حصة المريض:') || str_starts_with($line, 'نوع التسديد:') || str_starts_with($line, 'طريقة السداد:')) {
                            continue;
                        }

                        // 1. رسوم العملية الجراحية
                        if (str_contains($line, 'رسوم العملية الجراحية')) {
                            $paid = 0.0;
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $pm)) {
                                $paid = (float)str_replace(',', '', $pm[1]);
                            } else {
                                $paid = (float)($surgery->surgery_fee ?? $payment->amount);
                            }
                            
                            $appr = $paid;
                            if ($isInsured && $copayPct > 0) {
                                $pat = round($appr * ($copayPct / 100));
                                $ins = max(0, $appr - $pat);
                            } else {
                                $pat = $paid;
                                $ins = 0;
                            }

                            $lineItems[] = [
                                'name' => __('رسوم العملية الجراحية:') . ' ' . $surgery->surgery_type,
                                'category' => __('عمليات'),
                                'doctor' => $surgDoc,
                                'approved' => $appr,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                        // 2. أجور الغرفة والإقامة
                        elseif (str_contains($line, 'أجور الغرفة') || str_contains($line, 'أجور الإقامة')) {
                            $isPkg = (str_contains($line, 'مشمولة') || str_contains($line, '0 د.ع')) && ! preg_match('/مدفوع:\s*[1-9]/u', $line);
                            $paid = 0.0;
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $pm)) {
                                $paid = (float)str_replace(',', '', $pm[1]);
                            }
                            $roomInfo = $surgery->room ? (' (' . $surgery->room->room_type_name . ' - ' . __('رقم') . ' ' . $surgery->room->room_number . ')') : '';

                            $lineItems[] = [
                                'name' => __('أجور الإقامة والغرفة') . $roomInfo . ($isPkg ? ' (' . __('مشمولة بالباقة') . ')' : ''),
                                'category' => __('إقامة وفندقة'),
                                'doctor' => $surgDoc,
                                'approved' => $paid,
                                'patient' => $paid,
                                'insurance' => 0,
                            ];
                        }
                        // 3. خدمة إضافية
                        elseif (str_contains($line, 'خدمة إضافية:')) {
                            $rawClean = preg_replace('/^خدمة إضافية:\s*/u', '', $line);
                            $isPkg = (str_contains($line, 'مشمولة') || str_contains($line, '0 د.ع')) && ! preg_match('/مدفوع:\s*[1-9]/u', $line);
                            
                            $paid = 0.0;
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $em)) {
                                $paid = (float)str_replace(',', '', $em[1]);
                            }

                            $svcTitle = preg_replace('/\s*-\s*مدفوع.*$/u', '', $rawClean);
                            $svcTitle = preg_replace('/\s*\(مشمولة.*$/u', '', $svcTitle);
                            $svcTitle = trim($svcTitle);
                            
                            $appr = $paid;
                            $ins = 0.0;
                            $pat = $paid;

                            if ($isPkg) {
                                foreach ($pkgItems as $pi) {
                                    if (($pi['type'] ?? '') === 'extra' && (str_contains($pi['name'] ?? '', $svcTitle) || str_contains($svcTitle, $pi['name'] ?? ''))) {
                                        $pkgPrice = (float)($pi['price'] ?? 0);
                                        if (($pi['bearer'] ?? '') === 'insurance') {
                                            $appr = $pkgPrice;
                                            $ins = $pkgPrice;
                                            $pat = 0.0;
                                        }
                                        break;
                                    }
                                }
                            }

                            $lineItems[] = [
                                'name' => __('خدمة إضافية:') . ' ' . $svcTitle . ($isPkg ? ' (' . __('مشمولة بالباقة') . ')' : ''),
                                'category' => __('خدمات إضافية وفندقية'),
                                'doctor' => $surgDoc,
                                'approved' => $appr,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                        // 4. تحاليل
                        elseif (str_contains($line, 'تحليل:')) {
                            $rawClean = preg_replace('/^تحليل:\s*/u', '', $line);
                            $isPkg = (str_contains($line, 'مشمول') || str_contains($line, '0 د.ع')) && ! preg_match('/مدفوع:\s*[1-9]/u', $line);
                            $paid = 0.0;
                            if (preg_match('/([\d,]+)\s*د\.ع/', $line, $lm)) {
                                $paid = (float)str_replace(',', '', $lm[1]);
                            }
                            $svcTitle = preg_replace('/\s*\(.*$/u', '', $rawClean);
                            $svcTitle = trim($svcTitle);

                            $appr = $paid;
                            $ins = 0.0;
                            $pat = $paid;

                            if ($isPkg) {
                                foreach ($pkgItems as $pi) {
                                    if (($pi['type'] ?? '') === 'lab' && (str_contains($pi['name'] ?? '', $svcTitle) || str_contains($svcTitle, $pi['name'] ?? ''))) {
                                        $pkgPrice = (float)($pi['price'] ?? 0);
                                        if (($pi['bearer'] ?? '') === 'insurance') {
                                            $appr = $pkgPrice;
                                            $ins = $pkgPrice;
                                            $pat = 0.0;
                                        }
                                        break;
                                    }
                                }
                            }

                            $lineItems[] = [
                                'name' => __('تحليل:') . ' ' . $svcTitle . ($isPkg ? ' (' . __('مشمول بالباقة') . ')' : ''),
                                'category' => __('تحاليل مختبرية'),
                                'doctor' => $surgDoc,
                                'approved' => $appr,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                        // 5. أشعة
                        elseif (str_contains($line, 'أشعة:')) {
                            $rawClean = preg_replace('/^أشعة:\s*/u', '', $line);
                            $isPkg = (str_contains($line, 'مشمولة') || str_contains($line, '0 د.ع')) && ! preg_match('/مدفوع:\s*[1-9]/u', $line);
                            $paid = 0.0;
                            if (preg_match('/([\d,]+)\s*د\.ع/', $line, $rm)) {
                                $paid = (float)str_replace(',', '', $rm[1]);
                            }
                            $svcTitle = preg_replace('/\s*\(.*$/u', '', $rawClean);
                            $svcTitle = trim($svcTitle);

                            $appr = $paid;
                            $ins = 0.0;
                            $pat = $paid;

                            if ($isPkg) {
                                foreach ($pkgItems as $pi) {
                                    if (($pi['type'] ?? '') === 'radiology' && (str_contains($pi['name'] ?? '', $svcTitle) || str_contains($svcTitle, $pi['name'] ?? ''))) {
                                        $pkgPrice = (float)($pi['price'] ?? 0);
                                        if (($pi['bearer'] ?? '') === 'insurance') {
                                            $appr = $pkgPrice;
                                            $ins = $pkgPrice;
                                            $pat = 0.0;
                                        }
                                        break;
                                    }
                                }
                            }

                            $lineItems[] = [
                                'name' => __('أشعة:') . ' ' . $svcTitle . ($isPkg ? ' (' . __('مشمولة بالباقة') . ')' : ''),
                                'category' => __('أشعة وتصوير'),
                                'doctor' => $surgDoc,
                                'approved' => $appr,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                        // 6. استشارة
                        elseif (str_contains($line, 'استشارة سريرية:')) {
                            $rawClean = preg_replace('/^استشارة سريرية:\s*/u', '', $line);
                            $isPkg = (str_contains($line, 'مشمولة') || str_contains($line, '0 د.ع')) && ! preg_match('/مدفوع:\s*[1-9]/u', $line);
                            $paid = 0.0;
                            if (preg_match('/مدفوع:\s*([\d,]+)\s*د\.ع/', $line, $cm)) {
                                $paid = (float)str_replace(',', '', $cm[1]);
                            }
                            $svcTitle = preg_replace('/\s*-\s*مدفوع.*$/u', '', $rawClean);
                            $svcTitle = preg_replace('/\s*\(.*$/u', '', $svcTitle);
                            $svcTitle = trim($svcTitle);

                            $appr = $paid;
                            $ins = 0.0;
                            $pat = $paid;

                            if ($isPkg) {
                                foreach ($pkgItems as $pi) {
                                    if (($pi['type'] ?? '') === 'consultation' && (str_contains($pi['name'] ?? '', $svcTitle) || str_contains($svcTitle, $pi['name'] ?? ''))) {
                                        $pkgPrice = (float)($pi['price'] ?? 0);
                                        if (($pi['bearer'] ?? '') === 'insurance') {
                                            $appr = $pkgPrice;
                                            $ins = $pkgPrice;
                                            $pat = 0.0;
                                        }
                                        break;
                                    }
                                }
                            }

                            $lineItems[] = [
                                'name' => __('استشارة سريرية:') . ' ' . $svcTitle . ($isPkg ? ' (' . __('مشمولة بالباقة') . ')' : ''),
                                'category' => __('استشارة طبية'),
                                'doctor' => $surgDoc,
                                'approved' => $appr,
                                'patient' => $pat,
                                'insurance' => $ins,
                            ];
                        }
                    }
                }
            }
        }

        // 5. دفعات وأمانات حاضنة الخدج
        if ($payment->incubatorReservation || $payment->payment_type === 'incubator') {
            $res = $payment->incubatorReservation;
            $babyTitle = $res ? ($res->baby_name ?: (__('طفل') . ' ' . ($res->patient?->name ?? ''))) : __('الخديج');
            $docName = $res?->doctor?->user?->name ? (__('د.') . ' ' . $res->doctor->user->name) : __('طبيب الخدج');
            $lineItems[] = [
                'name' => $payment->description ?: (__('دفعة / أمانات حاضنة الخدج') . ' - ' . ($res?->incubator?->name ?? '') . " ({$babyTitle})"),
                'category' => __('الخدج والحاضنات (NICU)'),
                'doctor' => $docName,
                'approved' => (float)$payment->amount,
                'patient' => (float)$payment->amount,
                'insurance' => 0,
            ];
        }

        // إذا لم توجد تفاصيل متعددة نستخدم السجل المباشر
        if (empty($lineItems)) {
            $approvedPrice = (float)($payment->total_amount > 0 ? $payment->total_amount : $payment->amount);
            $patientShare = (float)($payment->patient_share > 0 ? $payment->patient_share : $payment->amount);
            $insuranceShare = (float)($payment->insurance_share ?? max(0, $approvedPrice - $patientShare));

            $docTitle = $payment->appointment?->doctor?->user?->name 
                ? (__('د.') . ' ' . $payment->appointment->doctor->user->name) 
                : ($payment->emergency?->doctor?->user?->name ? (__('د.') . ' ' . $payment->emergency->doctor->user->name) : __('الكادر الطبي'));

            $lineItems[] = [
                'name' => $payment->description ?: __('خدمة طبية عامة'),
                'category' => $payment->payment_type ?? __('عام'),
                'doctor' => $docTitle,
                'approved' => $approvedPrice,
                'patient' => $patientShare,
                'insurance' => $insuranceShare,
            ];
        }

        $isRefund = (float)$payment->amount < 0;
        if ($isRefund) {
            $lineItems = [
                [
                    'name' => $payment->description ?: __('استرجاع مالي للمريض: إلغاء طلب فحص خارجي'),
                    'category' => __('سند صرف استرجاع نقدي'),
                    'doctor' => optional($payment->cashier)->name ?? __('الكاشير'),
                    'approved' => abs((float)$payment->amount),
                    'patient' => abs((float)$payment->amount),
                    'insurance' => 0,
                ]
            ];
        }

        $totalApprovedSum = abs((float)($payment->total_amount != 0 ? $payment->total_amount : collect($lineItems)->sum('approved')));
        $totalPatientSum = abs((float)($payment->amount));
        $totalInsuranceSum = abs((float)($payment->insurance_share ?? max(0, $totalApprovedSum - $totalPatientSum)));
    @endphp

    <!-- بطاقة الإيصال المصممة بنمط السند الرقمي المتقن -->
    <div class="row justify-content-center">
        <div class="col-lg-9 col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white" id="receipt_card">
                
                <!-- ترويسة الإيصال الأنيقة -->
                <div class="p-4 border-bottom text-center" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div class="text-start">
                            <h5 class="fw-bold text-dark mb-0">{{ __("مستشفى الكفاءات الأهلي") }}</h5>
                            <small class="text-muted">{{ __("قسم الصندوق والحسابات الطبية") }}</small>
                        </div>
                        <div class="text-end">
                            @if($isRefund)
                                <span class="badge bg-danger px-3 py-2 fs-6 rounded-pill">
                                    <i class="fas fa-undo me-1"></i> {{ __("تم الاسترجاع والصرف للمريض") }}
                                </span>
                            @else
                                <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">
                                    <i class="fas fa-check-circle me-1"></i> {{ __("تم القبض بنجاح") }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="p-3 bg-white rounded-3 border d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <span class="text-muted small d-block">{{ __("رقم السند:") }}</span>
                            <strong class="text-primary fs-5">{{ $payment->receipt_number }}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">{{ __("تاريخ ووقت القبض:") }}</span>
                            <strong class="text-dark">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d h:i A') : now()->format('Y-m-d h:i A') }}</strong>
                        </div>
                        <div>
                            <span class="text-muted small d-block">{{ __("أمين الصندوق (الكاشير):") }}</span>
                            <strong class="text-dark">{{ optional($payment->cashier)->name ?? __('النظام') }}</strong>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4">
                    <!-- بيانات المريض والتغطية -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <span class="text-muted small d-block mb-1"><i class="fas fa-user text-primary me-1"></i>{{ __("بيانات المريض:") }}</span>
                                <h6 class="fw-bold mb-1 text-dark">{{ $pname }}</h6>
                                <div class="text-muted small">
                                    <span>{{ __("الهوية:") }} <strong>{{ $pid }}</strong></span> | 
                                    <span>{{ __("الهاتف:") }} <strong>{{ $pphone }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded-3 border bg-light h-100">
                                <span class="text-muted small d-block mb-1"><i class="fas fa-shield-alt text-success me-1"></i>{{ __("التغطية والضمان:") }}</span>
                                @if($isInsured)
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-success">{{ $payment->insurance_type === 'hi' ? __('الضمان الصحي الوطني') : __('ضمان قوى الأمن الداخلي') }}</span>
                                        <span class="badge bg-light text-dark border">{{ __('تحمل المريض:') }} {{ number_format($copayPct, 0) }}%</span>
                                    </div>
                                    @if($payment->insurance_card_no)
                                        <small class="text-muted">{{ __("رقم البطاقة/الدفتر:") }} <strong class="text-dark">{{ $payment->insurance_card_no }}</strong></small>
                                    @endif
                                @else
                                    <h6 class="fw-bold mb-0 text-secondary">{{ __("دفع نقدي كامل (خاص / 100%)") }}</h6>
                                    <small class="text-muted">{{ __("غير مشمول بالتغطية التأمينية") }}</small>
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
                                        <th>{{ __("الخدمة / الفحص") }}</th>
                                        <th>{{ __("الطبيب / القسم") }}</th>
                                        <th width="120" class="text-end">{{ __("السعر المعتمد") }}</th>
                                        @if($isInsured)
                                            <th width="120" class="text-end text-primary">{{ __("حصة الضمان") }}</th>
                                        @endif
                                        <th width="130" class="text-end text-success">{{ __("المقبوض من المريض") }}</th>
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
                                            <td class="text-end fw-semibold">{{ number_format($item['approved']) }} {{ __('د.ع') }}</td>
                                            @if($isInsured)
                                                <td class="text-end text-primary fw-semibold">{{ number_format($item['insurance']) }} {{ __('د.ع') }}</td>
                                            @endif
                                            <td class="text-end text-success fw-bold">{{ number_format($item['patient']) }} {{ __('د.ع') }}</td>
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
                                    <span class="text-muted small">{{ __("طريقة الدفع:") }}</span>
                                    <span class="badge bg-white text-dark border px-3 py-2 fs-7 fw-bold">
                                        @if($payment->payment_method === 'split')
                                            🔄 {{ __('دفع مركب') }} ({{ __('نقداً:') }} {{ number_format((float)($payment->cash_amount ?? 0), 0) }} {{ __('د.ع') }} | {{ __('بطاقة:') }} {{ number_format((float)($payment->card_amount ?? 0), 0) }} {{ __('د.ع') }})
                                        @elseif($payment->payment_method === 'card')
                                            {{ __('💳 بطاقة دفع إلكتروني (POS / Card)') }}
                                        @else
                                            {{ __('💵 نقدي (Cash)') }}
                                        @endif
                                    </span>
                                </div>
                                @if($payment->notes)
                                    <div class="text-muted small mt-2">
                                        <i class="fas fa-comment-alt me-1"></i>{{ __("ملاحظات:") }} {{ $payment->notes }}
                                    </div>
                                @endif
                            </div>

                            <div class="col-md-6 text-md-end">
                                @if($isInsured)
                                    <div class="text-muted small mb-1">
                                        {{ __("إجمالي التسعيرة:") }} {{ number_format($totalApprovedSum) }} {{ __('د.ع') }} | {{ __("تغطية الضمان:") }} <strong class="text-primary">{{ number_format($totalInsuranceSum) }} {{ __('د.ع') }}</strong>
                                    </div>
                                @elseif($payment->discount_amount > 0)
                                    <div class="text-muted small mb-1">
                                        {{ __("إجمالي الفاتورة:") }} {{ number_format((float)($payment->patient_share > 0 ? $payment->patient_share : ($totalApprovedSum > 0 ? $totalApprovedSum : ($payment->amount + $payment->discount_amount)))) }} {{ __('د.ع') }} |
                                        <span class="text-danger fw-bold">
                                            <i class="fas fa-tags me-1"></i> {{ __("خصم خاص") }} ({{ (float)$payment->discount_percentage }}%): -{{ number_format($payment->discount_amount) }} {{ __('د.ع') }}
                                        </span>
                                        @if($payment->discount_reason)
                                            <span class="text-muted small">({{ $payment->discount_reason }})</span>
                                        @endif
                                    </div>
                                @endif
                                <div class="d-inline-block bg-white p-2 px-4 rounded-3 border {{ $isRefund ? 'border-danger' : 'border-success' }} shadow-sm">
                                    <span class="text-muted small d-block">{{ $isRefund ? __('المبلغ المسترجع للمريض:') : __('المبلغ المقبوض نقداً من المريض:') }}</span>
                                    <span class="fs-3 fw-bold {{ $isRefund ? 'text-danger' : 'text-success' }}">{{ number_format($totalPatientSum) }} {{ __('د.ع') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- تذييل الوصل الرسمي -->
                    <div class="text-center pt-2 border-top text-muted small">
                        <p class="mb-1">{{ __("هذا الإيصال سند مالي رسمي صادر إلكترونياً ولا يحتاج إلى ختم يدوي إضافي ما لم يُطلب رسمياً.") }}</p>
                        <small class="text-muted">{{ __("مستشفى الكفاءات الأهلي — نتمنى لكم دوام الصحة والعافية") }}</small>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endsection
