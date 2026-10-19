@extends('layouts.app')

@section('title', 'كشف الحساب التراكمي - ' . $reservation->baby_name)

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
        <div>
            <a href="{{ route('nicu.show', $reservation) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة لملف الطفل
            </a>
            <a href="{{ route('nicu.index') }}" class="btn btn-outline-primary ms-2">
                <i class="fas fa-th me-1"></i> لوحة الحاضنات
            </a>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-primary shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة كشف الحساب
            </button>
        </div>
    </div>

    <!-- بطاقة كشف الحساب القابلة للطباعة -->
    <div class="card shadow-sm border p-4 bg-white" style="border-radius: 14px;" id="statementPrintArea">
        <!-- ترويسة المستشفى -->
        <div class="row align-items-center pb-3 border-bottom mb-4">
            <div class="col-8">
                <h3 class="fw-bold mb-1 text-primary">مستشفى الرعاية التخصصي</h3>
                <h5 class="text-secondary fw-bold mb-1">قسم العناية المركزة لحديثي الولادة والخدج (NICU)</h5>
                <p class="text-muted small mb-0">كشف حساب الإقامة والرعاية السريرية التفصيلي</p>
            </div>
            <div class="col-4 text-end">
                <div class="badge bg-light text-dark border p-2 text-start">
                    <span class="small d-block"><strong>رقم الإضبارة:</strong> {{ $reservation->patient_id }}</span>
                    <span class="small d-block"><strong>رقم الحجز:</strong> #NICU-{{ $reservation->id }}</span>
                    <span class="small d-block"><strong>تاريخ التقرير:</strong> {{ now()->format('Y-m-d H:i') }}</span>
                </div>
            </div>
        </div>

        <!-- معلومات الطفل والأم -->
        <div class="row g-3 p-3 bg-light rounded-3 mb-4">
            <div class="col-md-3">
                <span class="text-muted small d-block">اسم المولود:</span>
                <strong class="fs-6 text-dark">{{ $reservation->baby_name }}</strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">اسم الأم:</span>
                <strong class="fs-6 text-dark">{{ optional($reservation->patient?->user)->name ?? 'غير معروف' }}</strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">رقم الحاضنة / الكبسولة:</span>
                <span class="badge bg-primary fs-6">حاضنة رقم {{ $reservation->incubator->incubator_number }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">الطبيب المشرف:</span>
                <strong class="text-dark">{{ optional($reservation->doctor?->user)->name ?? 'طبيب القسم' }}</strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">تاريخ وساعة الدخول:</span>
                <span class="text-dark">{{ $reservation->admission_date->format('Y-m-d') }} {{ $reservation->admission_time ? $reservation->admission_time->format('H:i') : '' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">تاريخ وساعة الخروج:</span>
                <span class="text-dark">{{ $reservation->discharge_date ? $reservation->discharge_date->format('Y-m-d') . ' ' . ($reservation->discharge_time ? $reservation->discharge_time->format('H:i') : '') : 'ما زال راقداً' }}</span>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">إجمالي الساعات المسجلة:</span>
                <strong class="text-danger">{{ $stayDetails['total_hours'] }} ساعة</strong>
            </div>
            <div class="col-md-3">
                <span class="text-muted small d-block">الأيام الفندقية المحتسبة:</span>
                <strong class="text-success">{{ $stayDetails['calculated_days'] }} يوم</strong>
            </div>
        </div>

        @if($stayDetails['is_insured'])
            <!-- شريط بيانات وثيقة الضمان الصحي -->
            <div class="alert alert-success border border-success p-3 rounded-3 mb-4">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <span class="small text-muted d-block">نظام الفوترة والتغطية:</span>
                        <strong class="text-success fs-6"><i class="fas fa-shield-alt me-1"></i> هيئة الضمان الصحي (HIA)</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="small text-muted d-block">فئة الوثيقة المشمولة:</span>
                        <strong>الفئة {{ $reservation->policy_category ?? 'E' }}</strong>
                    </div>
                    <div class="col-md-3">
                        <span class="small text-muted d-block">رقم البطاقة / المطالبة:</span>
                        <span>{{ $reservation->insurance_card_no ?: 'غير مسجل' }} / {{ $reservation->encounter_number ?: 'بدون رقم' }}</span>
                    </div>
                    <div class="col-md-3">
                        <span class="small text-muted d-block">العمر الحملي المعتمد:</span>
                        <span class="badge bg-primary fs-6">{{ $reservation->gestational_age ?: 'غير محدد' }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- ملاحظات السماحية المطبقة -->
        @if(!empty($stayDetails['grace_notes']))
            <div class="alert alert-info py-2 px-3 small mb-4">
                <i class="fas fa-info-circle me-1"></i>
                <strong>قواعد السماحية المعتمدة (6 ساعات):</strong>
                <ul class="mb-0 ps-3">
                    @foreach($stayDetails['grace_notes'] as $gnote)
                        <li>{{ $gnote }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- 1. جدول باقات وأيام الحاضنة -->
        <h5 class="fw-bold text-dark mb-2">1. أجور الحاضنة والباقات السريرية (Daily Packages)</h5>
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>الباقة والخدمة السريرية</th>
                        <th class="text-center">الأجرة اليومية</th>
                        <th class="text-center">الأيام المحتسبة</th>
                        <th class="text-center">الفترة الزمنية</th>
                        <th class="text-end">الإجمالي</th>
                        @if($stayDetails['is_insured'])
                            <th class="text-end table-success text-success">مساهمة الضمان</th>
                            <th class="text-end table-warning text-dark">تحمل المريض (كاش)</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($stayDetails['service_breakdown'] as $sb)
                        <tr>
                            <td>
                                <strong>{{ $sb['service_name'] }}</strong>
                                @if(!empty($sb['official_claim_name']))
                                    <small class="text-muted d-block"><i class="fas fa-file-contract text-primary me-1"></i>المطالبة: {{ $sb['official_claim_name'] }}</small>
                                @endif
                                @if($sb['reason'])
                                    <small class="text-muted d-block">سبب التحويل: {{ $sb['reason'] }}</small>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($sb['daily_fee']) }} د.ع</td>
                            <td class="text-center fw-bold">{{ $sb['days'] }} يوم</td>
                            <td class="text-center small">{{ $sb['start_time'] }} إلى {{ $sb['end_time'] }}</td>
                            <td class="text-end fw-bold">{{ number_format($sb['total']) }} د.ع</td>
                            @if($stayDetails['is_insured'])
                                <td class="text-end fw-bold text-success">
                                    {{ number_format($sb['insurance_share']) }} د.ع
                                    <small class="d-block text-muted">({{ $sb['hia_percent'] }}%)</small>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    {{ number_format($sb['patient_share']) }} د.ع
                                </td>
                            @endif
                        </tr>
                    @endforeach
                    <tr class="table-light">
                        <td colspan="4" class="fw-bold text-end">مجموع أجور الإقامة بالحاضنة:</td>
                        <td class="text-end fw-bold fs-6">{{ number_format($stayDetails['total_service_cost']) }} د.ع</td>
                        @if($stayDetails['is_insured'])
                            <td class="text-end fw-bold text-success fs-6">{{ number_format($stayDetails['insurance_share_total']) }} د.ع</td>
                            <td class="text-end fw-bold text-primary fs-6">{{ number_format($stayDetails['patient_share_total']) }} د.ع</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- 2. جدول الاستشارات الطبية -->
        @if($stayDetails['consultations']->isNotEmpty())
            <h5 class="fw-bold text-dark mb-2">2. استشارات الأطباء التخصصية (Consultations)</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>نوع الاستشارة</th>
                            <th>الطبيب والتخصص</th>
                            <th>موضوع الفحص</th>
                            <th class="text-end">الأجرة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stayDetails['consultations'] as $con)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $con->consultation_type == 'internal' ? 'primary' : 'warning text-dark' }}">
                                        {{ $con->consultation_type == 'internal' ? 'داخلية' : 'خارجية (زائر)' }}
                                    </span>
                                </td>
                                <td>{{ $con->doctor_display_name }} ({{ $con->specialty }})</td>
                                <td class="small">{{ $con->reason }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($con->fee) }} د.ع</td>
                            </tr>
                        @endforeach
                        <tr class="table-light">
                            <td colspan="3" class="fw-bold text-end">مجموع أجور الاستشارات:</td>
                            <td class="text-end fw-bold text-success">{{ number_format($stayDetails['total_consultations_cost']) }} د.ع</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- 3. جدول التحاليل والأشعة والأدوية المصروفة -->
        @if($stayDetails['medical_orders']->isNotEmpty())
            <h5 class="fw-bold text-dark mb-2">3. الفحوصات المخبرية والأشعة والأدوية (Labs, X-Ray & Pharmacy)</h5>
            <div class="table-responsive mb-4">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>القسم / النوع</th>
                            <th>اسم الفحص أو العلاج</th>
                            <th>الحالة والنتيجة</th>
                            <th class="text-end">السعر</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stayDetails['medical_orders'] as $ord)
                            <tr>
                                <td>
                                    <span class="badge bg-{{ $ord->order_type == 'lab' ? 'primary' : ($ord->order_type == 'radiology' ? 'info' : 'success') }}">
                                        {{ $ord->order_type_name }}
                                    </span>
                                </td>
                                <td class="fw-bold">{{ $ord->item_name }}</td>
                                <td class="small text-muted">{{ $ord->result ?? 'تم التنفيذ' }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($ord->price) }} د.ع</td>
                            </tr>
                        @endforeach
                        <tr class="table-light">
                            <td colspan="3" class="fw-bold text-end">مجموع الفحوصات والعلاجات:</td>
                            <td class="text-end fw-bold text-success">{{ number_format($stayDetails['total_orders_cost']) }} د.ع</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        <!-- الخلاصة المالية النهائية -->
        <div class="row justify-content-end mb-4">
            <div class="col-md-6">
                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">مجموع الإقامة بالحاضنة:</span>
                        <strong>{{ number_format($stayDetails['total_service_cost']) }} د.ع</strong>
                    </div>
                    @if($stayDetails['is_insured'])
                        <div class="d-flex justify-content-between mb-2 small text-success">
                            <span><i class="fas fa-shield-alt me-1"></i> مساهمة الضمان بالحاضنة:</span>
                            <strong>{{ number_format($stayDetails['insurance_share_total']) }} د.ع</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2 small text-primary">
                            <span><i class="fas fa-user me-1"></i> مساهمة المريض بالحاضنة:</span>
                            <strong>{{ number_format($stayDetails['patient_share_total']) }} د.ع</strong>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">مجموع الاستشارات الطبية:</span>
                        <strong>{{ number_format($stayDetails['total_consultations_cost']) }} د.ع</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">مجموع المختبر والأشعة والأدوية:</span>
                        <strong>{{ number_format($stayDetails['total_orders_cost']) }} د.ع</strong>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fs-6 fw-bold text-muted">إجمالي المطالبة الكاملة:</span>
                        <span class="fs-5 fw-bold text-dark">{{ number_format($stayDetails['grand_total']) }} د.ع</span>
                    </div>
                    @if($stayDetails['is_insured'])
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-success bg-opacity-10 rounded">
                            <span class="fw-bold text-success"><i class="fas fa-file-invoice me-1"></i> مطالبة هيئة الضمان الصحي:</span>
                            <span class="fs-5 fw-bold text-success">{{ number_format($stayDetails['insurance_grand_total']) }} د.ع</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center p-2 bg-primary bg-opacity-10 rounded">
                            <span class="fs-5 fw-bold text-primary"><i class="fas fa-hand-holding-usd me-1"></i> المستحق نقداً على المريض (الصندوق):</span>
                            <span class="fs-4 fw-bold text-primary">{{ number_format($stayDetails['patient_grand_total']) }} د.ع</span>
                        </div>
                    @else
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-5 fw-bold text-dark">المبلغ المستحق للدفع:</span>
                            <span class="fs-4 fw-bold text-success">{{ number_format($stayDetails['grand_total']) }} د.ع</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- التواقيع الرسمية -->
        <div class="row pt-4 mt-4 border-top text-center text-muted small">
            <div class="col-4">
                <p class="mb-4">منظم الكشف / تمريض الخدج</p>
                <p>___________________</p>
            </div>
            <div class="col-4">
                <p class="mb-4">طبيب القسم المشرف</p>
                <p>___________________</p>
            </div>
            <div class="col-4">
                <p class="mb-4">كاشير المستشفى الرئيسي / التدقيق</p>
                <p>___________________</p>
            </div>
        </div>
    </div>
</div>
@endsection
