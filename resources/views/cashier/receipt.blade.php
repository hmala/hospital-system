@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2>
                    <i class="fas fa-receipt me-2 text-success"></i>
                    إيصال الدفع
                </h2>
                <div>
                    <a href="{{ route('cashier.receipt.print', $payment->id) }}" class="btn btn-primary me-2" target="_blank">
                        <i class="fas fa-print me-2"></i>طباعة
                    </a>
                    <a href="{{ route('cashier.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-right me-2"></i>العودة
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-lg" id="receipt">
                <!-- Header -->
                <div class="card-header bg-gradient-success text-white text-center py-4" 
                     style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <h3 class="mb-1">
                        <i class="fas fa-hospital-alt me-2"></i>
                        مستشفى النظام الطبي
                    </h3>
                    <p class="mb-0">إيصال دفع رسوم الخدمات الطبية</p>
                </div>

                <div class="card-body p-4">
                    <!-- معلومات الإيصال -->
                    <div class="row mb-4">
                        <div class="col-6">
                            <div class="border-bottom pb-2 mb-2">
                                <small class="text-muted">رقم الإيصال:</small>
                                <h5 class="mb-0 text-success">{{ $payment->receipt_number }}</h5>
                            </div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="border-bottom pb-2 mb-2">
                                <small class="text-muted">تاريخ ووقت الدفع:</small>
                                <h6 class="mb-0">{{ $payment->paid_at->format('Y-m-d H:i') }}</h6>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <small class="text-muted">طريقة الدفع:</small>
                            <div class="fw-bold">{{ ucfirst($payment->payment_method) }}</div>
                        </div>
                        @if($payment->notes)
                        <div class="col-md-6 text-end">
                            <small class="text-muted">ملاحظات:</small>
                            <div class="fw-bold">{{ $payment->notes }}</div>
                        </div>
                        @endif
                    </div>

                    <!-- معلومات المريض -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-user me-2 text-primary"></i>
                            معلومات المريض
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الاسم:</small>
                                @php
                            $p = $payment->patient;
                            if(!$p && $payment->emergency) {
                                $ep = $payment->emergency->emergencyPatient;
                                if($ep) {
                                    $pname = $ep->name;
                                    $pphone = $ep->phone ?? 'غير محدد';
                                    $pid = '(طوارئ)';
                                } else {
                                    $pname = 'غير محدد';
                                    $pphone = 'غير محدد';
                                    $pid = '-';
                                }
                            } else {
                                $pname = $p ? ($p->user->name ?? 'غير محدد') : 'غير محدد';
                                $pphone = $p ? ($p->user->phone ?? 'غير محدد') : 'غير محدد';
                                $pid = $p ? '#'.$p->id : '-';
                            }
                        @endphp
                        <div class="fw-bold">{{ $pname }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الرقم الوطني:</small>
                                <div class="fw-bold">{{ $pid }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">رقم الهاتف:</small>
                                <div class="fw-bold">{{ $pphone }}</div>
                            </div>
                        </div>
                    </div>

                    @php
                        // بناء جدول خدمات/عناصر للتفصيل
                        $lineItems = [];

                        // حالة الدفع المرتبط مباشرةً بطوارئ (دون طلب med)
                        if ($payment->emergency) {
                            foreach ($payment->emergency->services as $svc) {
                                $lineItems[] = ['الخدمة' => 'خدمة طوارئ: ' . $svc->name, 'السعر' => $svc->price ?? 0];
                            }
                        }

                        // 1. تفاصيل الموعد
                        if ($payment->appointment) {
                            $doctorFee = $payment->appointment->doctor->fee_by_specialization ?? 0;
                            $consultFee = $payment->appointment->consultation_fee ?? 0;
                            $hospitalProfit = $consultFee - $doctorFee;
                            $lineItems[] = ['الخدمة' => 'أجر الطبيب', 'السعر' => $doctorFee];
                            $lineItems[] = ['الخدمة' => 'مبلغ الكشف', 'السعر' => $consultFee];
                            $lineItems[] = ['الخدمة' => 'ربح المستشفى', 'السعر' => $hospitalProfit];
                        }

                        // 2. طلبات طبية (تحاليل، أشعة، صيدلية، طوارئ)
                        if ($payment->request) {
                            $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;

                            if ($payment->request->type === 'lab' && isset($details['lab_test_ids'])) {
                                foreach ($details['lab_test_ids'] as $testId) {
                                    $test = \App\Models\LabTest::find($testId);
                                    if ($test) {
                                        $lineItems[] = ['الخدمة' => 'تحاليل: ' . $test->name, 'السعر' => $test->price ?? 0];
                                    }
                                }
                            } elseif ($payment->request->type === 'radiology' && isset($details['radiology_type_ids'])) {
                                foreach ($details['radiology_type_ids'] as $typeId) {
                                    $type = \App\Models\RadiologyType::find($typeId);
                                    if ($type) {
                                        $lineItems[] = ['الخدمة' => 'أشعة: ' . $type->name, 'السعر' => $type->base_price ?? 0];
                                    }
                                }
                            } elseif ($payment->request->type === 'pharmacy') {
                                // إذا كانت هناك قائمة بأسماء أدوية في التفاصيل
                                if (isset($details['tests']) && is_array($details['tests'])) {
                                    foreach ($details['tests'] as $drugName) {
                                        $lineItems[] = ['الخدمة' => 'صيدلية: ' . $drugName, 'السعر' => 0];
                                    }
                                }
                            } elseif ($payment->request->type === 'emergency') {
                                if ($payment->request->visit && $payment->request->visit->emergency) {
                                    foreach($payment->request->visit->emergency->services as $svc) {
                                        $lineItems[] = ['الخدمة' => 'خدمة طوارئ: ' . $svc->name, 'السعر' => $svc->price ?? 0];
                                    }
                                }
                            }
                        }

                        // 3. عمليات جراحية
                        if ($payment->payment_type === 'surgery' && isset($surgery) && $surgery) {
                            $paidItemsFromDesc = [];
                            if (preg_match('/العناصر المدفوعة:\n(.+)/s', $payment->description, $descMatches)) {
                                $itemLines = explode("\n", trim($descMatches[1]));
                                foreach ($itemLines as $line) {
                                    $line = trim(str_replace('- ', '', $line));
                                    if (!empty($line)) {
                                        // حاول تقدير السعر كما كان في الكود السابق
                                        $price = 0;
                                        if (str_contains($line, 'رسوم العملية')) {
                                            $price = $surgery->surgery_fee ?? 0;
                                        } elseif (str_contains($line, 'تحليل:')) {
                                            $name = trim(str_replace('تحليل:', '', $line));
                                            foreach ($surgery->labTests as $labTest) {
                                                if ($labTest->labTest && $labTest->labTest->name === $name) {
                                                    $price = $labTest->labTest->price ?? 0;
                                                    break;
                                                }
                                            }
                                        } elseif (str_contains($line, 'أشعة:')) {
                                            $name = trim(str_replace('أشعة:', '', $line));
                                            foreach ($surgery->radiologyTests as $rad) {
                                                if ($rad->radiologyType && $rad->radiologyType->name === $name) {
                                                    $price = $rad->radiologyType->base_price ?? 0;
                                                    break;
                                                }
                                            }
                                        }
                                        $lineItems[] = ['الخدمة' => $line, 'السعر' => $price];
                                    }
                                }
                            }
                        }
                    @endphp

                    @if(count($lineItems) > 0)
                        <div class="bg-light p-3 rounded mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-list-check me-2 text-success"></i>
                                تفاصيل كل خدمة واجرها
                            </h6>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الخدمة</th>
                                            <th class="text-end">السعر (IQD)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($lineItems as $idx => $ln)
                                        <tr>
                                            <td>{{ $idx + 1 }}</td>
                                            <td>{{ $ln['الخدمة'] }}</td>
                                            <td class="text-end">{{ number_format($ln['السعر'],2) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="bg-light p-3 rounded mb-4">
                            <h6 class="mb-3">
                                <i class="fas fa-list-check me-2 text-success"></i>
                                تفاصيل الخدمة المدفوعة
                            </h6>
                            <p class="mb-0">{{ number_format($payment->amount,2) }} IQD</p>
                        </div>
                    @endif

                    @if($payment->appointment)
                    <!-- معلومات الموعد -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-calendar-check me-2 text-info"></i>
                            تفاصيل الموعد
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">رقم الموعد:</small>
                                <div class="fw-bold">#{{ $payment->appointment->id }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">التاريخ:</small>
                                <div class="fw-bold">{{ $payment->appointment->appointment_date->format('Y-m-d H:i') }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الطبيب:</small>
                                <div class="fw-bold">د. {{ $payment->appointment->doctor->user->name }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">القسم:</small>
                                <div class="fw-bold">{{ $payment->appointment->department->name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- تفصيل الرسوم للموعد -->
                    @php
                        $doctorFee = $payment->appointment->doctor->fee_by_specialization ?? 0;
                        $consultFee = $payment->appointment->consultation_fee ?? 0;
                        $hospitalProfit = $consultFee - $doctorFee;
                    @endphp
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-list-check me-2 text-success"></i>
                            تفصيل الرسوم
                        </h6>
                        <table class="table table-sm mb-0">
                            <tr>
                                <td>أجر الطبيب</td>
                                <td class="text-end">{{ number_format($doctorFee,2) }} IQD</td>
                            </tr>
                            <tr>
                                <td>مبلغ الكشف</td>
                                <td class="text-end">{{ number_format($consultFee,2) }} IQD</td>
                            </tr>
                            <tr>
                                <td>ربح المستشفى</td>
                                <td class="text-end">{{ number_format($hospitalProfit,2) }} IQD</td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    @if($payment->request)
                    <!-- معلومات الطلب -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-file-medical me-2 text-warning"></i>
                            تفاصيل الطلب الطبي
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">رقم الطلب:</small>
                                <div class="fw-bold">#{{ $payment->request->id }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">النوع:</small>
                                <div class="fw-bold">
                                    @if($payment->request->type === 'lab')
                                        <span class="badge bg-primary">تحاليل</span>
                                    @elseif($payment->request->type === 'radiology')
                                        <span class="badge bg-info">أشعة</span>
                                    @elseif($payment->request->type === 'pharmacy')
                                        <span class="badge bg-success">صيدلية</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $payment->request->type }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">التاريخ:</small>
                                <div class="fw-bold">{{ $payment->request->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الطبيب:</small>
                                <div class="fw-bold">{{ $payment->request->visit->doctor ? 'د. ' . $payment->request->visit->doctor->user->name : 'غير محدد' }}</div>
                            </div>
                        </div>
                    </div>

                    @php
                        $details = is_string($payment->request->details) ? json_decode($payment->request->details, true) : $payment->request->details;
                    @endphp

                    @if($payment->request->type === 'lab' && isset($details['lab_test_ids']))
                    <!-- Lab Tests Details -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-flask me-2 text-primary"></i>
                            تفاصيل التحاليل المطلوبة
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-primary">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>اسم التحليل</th>
                                        <th>الرمز</th>
                                        <th style="width: 150px;" class="text-end">السعر (IQD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalAmount = 0; @endphp
                                    @foreach($details['lab_test_ids'] as $index => $testId)
                                        @php
                                            $test = \App\Models\LabTest::find($testId);
                                            if($test) {
                                                $price = $test->price ?? 0;
                                                $totalAmount += $price;
                                            }
                                        @endphp
                                        @if($test)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <i class="fas fa-vial text-primary me-1"></i>
                                                {{ $test->name }}
                                            </td>
                                            <td>{{ $test->code }}</td>
                                            <td class="text-end fw-bold">{{ number_format($price, 2) }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">الإجمالي:</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($totalAmount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @elseif($payment->request->type === 'radiology' && isset($details['radiology_type_ids']))
                    <!-- Radiology Types Details -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-x-ray me-2 text-info"></i>
                            تفاصيل الفحوصات الإشعاعية المطلوبة
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-info">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>نوع الأشعة</th>
                                        <th>الوصف</th>
                                        <th style="width: 150px;" class="text-end">السعر (IQD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $totalAmount = 0; @endphp
                                    @foreach($details['radiology_type_ids'] as $index => $typeId)
                                        @php
                                            $radiologyType = \App\Models\RadiologyType::find($typeId);
                                            if($radiologyType) {
                                                $price = $radiologyType->base_price ?? 0;
                                                $totalAmount += $price;
                                            }
                                        @endphp
                                        @if($radiologyType)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                <i class="fas fa-camera text-info me-1"></i>
                                                {{ $radiologyType->name }}
                                            </td>
                                            <td>{{ $radiologyType->description ?? '-' }}</td>
                                            <td class="text-end fw-bold">{{ number_format($price, 2) }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">الإجمالي:</td>
                                        <td class="text-end fw-bold text-success">{{ number_format($totalAmount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @endif
                    @endif

                    @php
                        // محاولة الحصول على العملية الجراحية من الوصف
                        $surgery = null;
                        if ($payment->payment_type === 'surgery' && preg_match('/ID: #(\d+)/', $payment->description, $matches)) {
                            $surgery = \App\Models\Surgery::with(['patient.user', 'doctor.user', 'department', 'labTests.labTest', 'radiologyTests.radiologyType'])->find($matches[1]);
                        }
                    @endphp

                    @if($payment->payment_type === 'surgery' && $surgery)
                    <!-- معلومات العملية الجراحية -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-procedures me-2 text-danger"></i>
                            تفاصيل العملية الجراحية
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">رقم العملية:</small>
                                <div class="fw-bold">#{{ $surgery->id }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">نوع العملية:</small>
                                <div class="fw-bold">{{ $surgery->surgery_type }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">تاريخ العملية:</small>
                                <div class="fw-bold">{{ $surgery->scheduled_date->format('Y-m-d') }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">وقت العملية:</small>
                                <div class="fw-bold">{{ $surgery->scheduled_time->format('H:i') }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الجراح:</small>
                                <div class="fw-bold">
                                    @if($surgery->doctor && $surgery->doctor->user)
                                        د. {{ $surgery->doctor->user->name }}
                                    @elseif($surgery->surgeon_name)
                                        {{ $surgery->surgeon_name }} <span class="badge bg-secondary">خارجي</span>
                                    @else
                                        غير محدد
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">القسم:</small>
                                <div class="fw-bold">{{ $surgery->department->name ?? 'غير محدد' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- تفاصيل العناصر المدفوعة -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-list-check me-2 text-success"></i>
                            تفصيل العناصر المدفوعة في هذا الإيصال
                        </h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-success">
                                    <tr>
                                        <th style="width: 50px;">#</th>
                                        <th>البند</th>
                                        <th>التفاصيل</th>
                                        <th class="text-end" style="width: 150px;">التكلفة (IQD)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $itemIndex = 0;
                                        $calculatedTotal = 0;
                                        // تحليل الوصف لمعرفة العناصر المدفوعة
                                        $paidItemsFromDesc = [];
                                        if (preg_match('/العناصر المدفوعة:\n(.+)/s', $payment->description, $descMatches)) {
                                            $itemLines = explode("\n", trim($descMatches[1]));
                                            foreach ($itemLines as $line) {
                                                $line = trim(str_replace('- ', '', $line));
                                                if (!empty($line)) {
                                                    $paidItemsFromDesc[] = $line;
                                                }
                                            }
                                        }
                                    @endphp

                                    @foreach($paidItemsFromDesc as $item)
                                        @php
                                            $itemIndex++;
                                            $itemPrice = 0;
                                            $itemType = 'other';
                                            $itemIcon = 'fas fa-circle';
                                            $itemClass = 'text-secondary';
                                            
                                            if (str_contains($item, 'رسوم العملية')) {
                                                $itemPrice = $surgery->surgery_fee ?? 0;
                                                $itemType = 'surgery';
                                                $itemIcon = 'fas fa-procedures';
                                                $itemClass = 'text-danger';
                                            } elseif (str_contains($item, 'تحليل:')) {
                                                $itemType = 'lab';
                                                $itemIcon = 'fas fa-vial';
                                                $itemClass = 'text-primary';
                                                // البحث عن التحليل بالاسم
                                                $labName = trim(str_replace('تحليل:', '', $item));
                                                foreach ($surgery->labTests as $labTest) {
                                                    if ($labTest->labTest && $labTest->labTest->name === $labName) {
                                                        $itemPrice = $labTest->labTest->price ?? 0;
                                                        break;
                                                    }
                                                }
                                            } elseif (str_contains($item, 'أشعة:')) {
                                                $itemType = 'radiology';
                                                $itemIcon = 'fas fa-x-ray';
                                                $itemClass = 'text-info';
                                                // البحث عن الأشعة بالاسم
                                                $radName = trim(str_replace('أشعة:', '', $item));
                                                foreach ($surgery->radiologyTests as $radTest) {
                                                    if ($radTest->radiologyType && $radTest->radiologyType->name === $radName) {
                                                        $itemPrice = $radTest->radiologyType->base_price ?? 0;
                                                        break;
                                                    }
                                                }
                                            }
                                            $calculatedTotal += $itemPrice;
                                        @endphp
                                        <tr>
                                            <td>{{ $itemIndex }}</td>
                                            <td>
                                                <i class="{{ $itemIcon }} {{ $itemClass }} me-2"></i>
                                                @if($itemType === 'surgery')
                                                    رسوم العملية الجراحية
                                                @elseif($itemType === 'lab')
                                                    تحليل مخبري
                                                @elseif($itemType === 'radiology')
                                                    فحص إشعاعي
                                                @else
                                                    {{ $item }}
                                                @endif
                                            </td>
                                            <td>
                                                @if($itemType === 'surgery')
                                                    {{ $surgery->surgery_type }}
                                                @else
                                                    {{ str_replace(['تحليل:', 'أشعة:'], '', $item) }}
                                                @endif
                                            </td>
                                            <td class="text-end fw-bold">{{ number_format($itemPrice, 0) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="table-success">
                                    <tr>
                                        <td colspan="3" class="text-end fw-bold">إجمالي هذا الإيصال:</td>
                                        <td class="text-end fw-bold text-success" style="font-size: 1.1rem;">{{ number_format($payment->amount, 0) }} IQD</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- ملخص حالة دفع العملية -->
                    @php
                        $surgeryFee = $surgery->surgery_fee ?? 0;
                        $surgeryFeePaid = $surgery->surgery_fee_paid === 'paid';
                        
                        $totalLabFee = $surgery->labTests->sum(function($test) {
                            return $test->labTest->price ?? 0;
                        });
                        $paidLabFee = $surgery->labTests->where('payment_status', 'paid')->sum(function($test) {
                            return $test->labTest->price ?? 0;
                        });
                        
                        $totalRadFee = $surgery->radiologyTests->sum(function($test) {
                            return $test->radiologyType->base_price ?? 0;
                        });
                        $paidRadFee = $surgery->radiologyTests->where('payment_status', 'paid')->sum(function($test) {
                            return $test->radiologyType->base_price ?? 0;
                        });
                        
                        $totalSurgeryAmount = $surgeryFee + $totalLabFee + $totalRadFee;
                        $totalPaidAmount = ($surgeryFeePaid ? $surgeryFee : 0) + $paidLabFee + $paidRadFee;
                        $remainingAmount = $totalSurgeryAmount - $totalPaidAmount;
                    @endphp

                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>
                            ملخص حالة دفع العملية الكاملة
                        </h6>
                        <div class="row text-center">
                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-white">
                                    <small class="text-muted d-block">إجمالي العملية</small>
                                    <h5 class="mb-0 text-primary">{{ number_format($totalSurgeryAmount, 0) }} IQD</h5>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-white">
                                    <small class="text-muted d-block">المدفوع حتى الآن</small>
                                    <h5 class="mb-0 text-success">{{ number_format($totalPaidAmount, 0) }} IQD</h5>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="border rounded p-2 bg-white">
                                    <small class="text-muted d-block">المتبقي</small>
                                    <h5 class="mb-0 {{ $remainingAmount > 0 ? 'text-warning' : 'text-success' }}">
                                        {{ number_format($remainingAmount, 0) }} IQD
                                        @if($remainingAmount <= 0)
                                            <i class="fas fa-check-circle ms-1"></i>
                                        @endif
                                    </h5>
                                </div>
                            </div>
                        </div>
                        
                        @if($remainingAmount > 0)
                        <div class="alert alert-warning mt-3 mb-0 py-2">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>ملاحظة:</strong> يوجد مبلغ متبقي {{ number_format($remainingAmount, 0) }} IQD سيُدفع لاحقاً.
                        </div>
                        @else
                        <div class="alert alert-success mt-3 mb-0 py-2">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>تم سداد جميع رسوم العملية بالكامل!</strong>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- ملخص الدفع -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-money-bill-wave me-2 text-success"></i>
                            ملخص الدفع
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">طريقة الدفع:</small>
                                <div class="fw-bold">
                                    <span class="badge bg-primary">{{ $payment->payment_method_name }}</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">المبلغ المدفوع:</small>
                                <div class="fw-bold text-success" style="font-size: 1.5rem;">{{ number_format($payment->amount, 2) }} IQD</div>
                            </div>
                        </div>
                    </div>

                    @if($payment->notes)
                    <!-- ملاحظات -->
                    <div class="alert alert-info mb-4">
                        <strong>ملاحظات:</strong> {{ $payment->notes }}
                    </div>
                    @endif

                    <!-- معلومات الكاشير -->
                    <div class="border-top pt-3">
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">تم الاستلام بواسطة:</small>
                                <div class="fw-bold">{{ $payment->cashier->name }}</div>
                            </div>
                            <div class="col-md-6 text-end">
                                <small class="text-muted">التوقيع:</small>
                                <div style="height: 40px; border-bottom: 1px solid #ddd; width: 200px; display: inline-block;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center mt-4 pt-3 border-top">
                        <p class="text-muted mb-0">
                            <small>هذا إيصال رسمي صادر من نظام إدارة المستشفى</small>
                        </p>
                        <p class="text-muted mb-0">
                            <small>للاستفسارات يرجى الاتصال على: 0790-XXX-XXXX</small>
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    تم تسديد المبلغ بنجاح. يمكنك الآن التوجه إلى القسم المعني.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
