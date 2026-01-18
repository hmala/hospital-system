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

                    <!-- معلومات المريض -->
                    <div class="bg-light p-3 rounded mb-4">
                        <h6 class="mb-3">
                            <i class="fas fa-user me-2 text-primary"></i>
                            معلومات المريض
                        </h6>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الاسم:</small>
                                <div class="fw-bold">{{ $payment->patient->user->name }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">الرقم الوطني:</small>
                                <div class="fw-bold">{{ $payment->patient->national_id ?? 'غير محدد' }}</div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <small class="text-muted">رقم الهاتف:</small>
                                <div class="fw-bold">{{ $payment->patient->user->phone ?? 'غير محدد' }}</div>
                            </div>
                        </div>
                    </div>

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
