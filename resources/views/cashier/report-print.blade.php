<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('كشف مقبوضات ووصولات الكاشير المعتمد - طباعة') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @php
        $hospitalLogoFile = public_path('images/1.jpg');
        $hospitalLogoSrc = file_exists($hospitalLogoFile) && filesize($hospitalLogoFile) > 0
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($hospitalLogoFile))
            : asset('images/1.jpg');
        
        $watermarkSrc = $hospitalLogoSrc;

        $totalInsurance = (float) ($totalInsuranceShare ?? $payments->sum('insurance_share'));
        $groupedData = $groupedPayments ?? $payments->groupBy(fn($p) => $p->payment_type_name ?: __('أخرى'));
    @endphp
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #cbd5e1;
            color: #1e293b;
            font-size: 11px;
            margin: 0;
            padding: 20px 0;
            line-height: 1.45;
        }

        /* حاوية التقرير للشاشة */
        .report-content-wrapper {
            position: relative;
            z-index: 1;
            width: 210mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
            box-sizing: border-box;
        }

        /* العلامة المائية الرسمية الثابتة في سنتر كل ورقة مطبوعة */
        .report-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 440px;
            height: 440px;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* ترويسة المستشفى الرسمية الثلاثية */
        .header-box {
            border-bottom: 2px solid #005a9c;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 16px;
            font-weight: 900;
            color: #005a9c;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .header-hospital-ar h4 {
            font-size: 12px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
        }
        .header-hospital-ar p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }
        .header-logo {
            text-align: center;
            width: 120px;
            padding: 0 10px;
        }
        .header-logo img {
            max-width: 105px;
            max-height: 75px;
            object-fit: contain;
        }
        .header-hospital-en {
            text-align: left;
            direction: ltr;
            flex: 1;
        }
        .header-hospital-en h2 {
            font-size: 14.5px;
            font-weight: 900;
            color: #005a9c;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .header-hospital-en h4 {
            font-size: 11px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
        }
        .header-hospital-en p {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }

        /* ترويسة مصغرة تتكرر أعلى كل قسم جديد في الصفحة الجديدة */
        .dept-repeat-header {
            border-bottom: 1.5px solid #005a9c;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }

        /* شريط عنوان المستند الموحد */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            padding: 6px 12px;
            border-radius: 5px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .banner-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #ffffff;
        }
        .banner-meta {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .report-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 10.5px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 15px;
        }

        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            padding: 6px 10px;
            margin-bottom: 12px;
        }

        .dept-title-banner {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: 5px solid #005a9c;
            padding: 6px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
        }

        /* الجداول التدقيقية المقروءة والشفافة لظهور العلامة المائية */
        .table-print {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            background-color: transparent !important;
        }
        .table-print thead {
            display: table-header-group;
        }
        .table-print tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .table-print th.col-header {
            background-color: #f1f5f9 !important;
            color: #0f172a;
            font-weight: 800;
            border: 1.5px solid #64748b;
            padding: 7px 5px;
            text-align: center;
            font-size: 11px;
            white-space: nowrap;
        }
        .table-print td {
            border: 1px solid #94a3b8;
            padding: 6px 5px;
            text-align: center;
            font-size: 10.5px;
            line-height: 1.4;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            background-color: transparent !important;
        }
        .table-group-subtotal td {
            background-color: #f8fafc !important;
            border-top: 2px solid #005a9c !important;
            border-bottom: 2px solid #475569 !important;
            font-weight: 800;
            padding: 7px 5px;
        }

        .summary-drawer-table td, .summary-drawer-table th {
            border: 1.5px solid #005a9c !important;
        }

        /* فواصل الصفحات لكل قسم مستقل والصفحة الختامية */
        .dept-page-break {
            page-break-before: always;
            break-before: page;
            margin-top: 20px;
        }
        .final-summary-page {
            page-break-before: always;
            break-before: page;
            margin-top: 20px;
        }

        /* إعدادات الطباعة الحقيقية المتناسقة الهندسية */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .report-content-wrapper {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                background: transparent !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm !important; /* هوامش متناسقة مريحة تضمن تباعد الجداول عن حواف الورقة علوياً وسفلياً */
            }
            .dept-page-break {
                page-break-before: always !important;
                break-before: page !important;
                margin-top: 0 !important;
            }
            .final-summary-page {
                page-break-before: always !important;
                break-before: page !important;
                margin-top: 0 !important;
            }
            .table-print th.col-header {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .table-print td {
                background-color: transparent !important;
            }
            .document-title-banner {
                background: #005a9c !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .summary-card {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .report-watermark {
                display: block !important;
                opacity: 0.12 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<!-- أزرار التحكم العلوية للشاشة وتنبيه إعدادات الطباعة (تختفي تلقائياً عند الطباعة) -->
<div class="no-print mx-auto mb-3" style="max-width: 210mm;">
    <div class="d-flex justify-content-between align-items-center p-3 bg-white rounded border shadow-sm mb-2">
        <div>
            <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-print me-2 text-primary"></i>{{ __('معاينة طباعة كشف حساب يومية الصندوق') }}</h6>
            <small class="text-muted d-block mt-0.5">
                {{ __('تم ضبط الأبعاد بهوامش متناسقة 15mm وبدء كل قسم في ورقة مستقلة مع جداول المطابقة.') }}
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
                <i class="fas fa-print me-1"></i> {{ __('طباعة الآن') }}
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-times me-1"></i> {{ __('إغلاق') }}
            </button>
        </div>
    </div>
    
    <!-- بطاقة إرشادية للمستخدم عن كيفية إخفاء رابط وتاريخ المتصفح نهائياً -->
    <div class="alert alert-info py-2 px-3 mb-0 small d-flex align-items-center gap-2 border-info shadow-sm">
        <i class="fas fa-lightbulb text-warning fa-lg flex-shrink-0"></i>
        <div>
            <strong class="text-dark">{{ __('لإخفاء رابط وعنوان وتاريخ المتصفح أعلى وأسفل الورقة:') }}</strong>
            <span class="text-dark">{{ __('في نافذة الطباعة > اضغط "المزيد من الإعدادات" (More settings) > قم بإلغاء تحديد خيار "الرؤوس والتذييلات" (Headers and footers).') }}</span>
        </div>
    </div>
</div>

<!-- العلامة المائية الرسمية للنظام تتكرر في سنتر كل ورقة مطبوعة -->
<div class="report-watermark" style="background-image: url('{!! $watermarkSrc !!}');"></div>

<div class="report-content-wrapper">

    {{-- حلقة توليد صفحات الأقسام: كل قسم يبدأ في ورقة مستقلة --}}
    @forelse($groupedData as $departmentName => $deptPayments)
        @php
            $deptTotal = $deptPayments->sum('amount');
        @endphp
        <div class="{{ $loop->first ? 'dept-first-block' : 'dept-page-break' }}">
            @if($loop->first)
                {{-- ترويسة المستشفى الرسمية الكاملة تظهر في الصفحة الأولى --}}
                <div class="header-box">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="header-hospital-ar">
                            <h2>{{ __('مستشفى الكفاءات الأهلي') }}</h2>
                            <h4>{{ __('قسم الحسابات المالية والتدقيق') }}</h4>
                            <p>{{ __('إدارة الصناديق والمقبوضات المركزية المعتمدة') }}</p>
                        </div>
                        <div class="header-logo">
                            <img src="{!! $hospitalLogoSrc !!}" alt="Hospital Logo" onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';">
                        </div>
                        <div class="header-hospital-en">
                            <h2>Al-Kafa'at Private Hospital</h2>
                            <h4>Finance & Cashier Audit Dept</h4>
                            <p>Central Revenue & Cash Management Unit</p>
                        </div>
                    </div>
                </div>

                {{-- شريط العنوان والبيانات الوصفية --}}
                <div class="document-title-banner">
                    <div class="banner-title">
                        <i class="fas fa-file-invoice-dollar me-2"></i>
                        <span>{{ __('كشف حساب يومية الصندوق ومقبوضات الكاشير') }}</span>
                    </div>
                    <div class="banner-meta">
                        <span class="report-badge">
                            <i class="fas fa-user-circle me-1"></i>
                            {{ __('المستخدم:') }} {{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}
                        </span>
                        <span class="report-badge">
                            <i class="fas fa-calendar-alt me-1"></i>
                            @if($fromDate && $toDate)
                                {{ $fromDate == $toDate ? $fromDate : ($fromDate . ' ' . __('إلى') . ' ' . $toDate) }}
                            @elseif($fromDate)
                                {{ __('من:') }} {{ $fromDate }}
                            @elseif($toDate)
                                {{ __('إلى:') }} {{ $toDate }}
                            @else
                                {{ now()->format('Y/m/d') }}
                            @endif
                        </span>
                        <span class="report-badge">
                            <i class="fas fa-clock me-1"></i>
                            {{ now()->format('h:i A') }}
                        </span>
                    </div>
                </div>

                {{-- ملخص سريع للمؤشرات --}}
                <div class="summary-card">
                    <div class="row text-center gy-1 align-items-center">
                        <div class="col-3 border-end">
                            <span class="text-muted small d-block" style="font-size: 10px;">{{ __('إجمالي المقبوضات') }}</span>
                            <strong class="text-success" style="font-size: 12.5px;">{{ number_format($totalAmount, 0) }} {{ __('د.ع') }}</strong>
                        </div>
                        <div class="col-3 border-end">
                            <span class="text-muted small d-block" style="font-size: 10px;">{{ __('مطالبات الضمان') }}</span>
                            <strong class="text-primary" style="font-size: 12.5px;">{{ number_format($totalInsurance, 0) }} {{ __('د.ع') }}</strong>
                        </div>
                        <div class="col-3 border-end">
                            <span class="text-muted small d-block" style="font-size: 10px;">{{ __('عدد الوصولات') }}</span>
                            <strong class="text-dark" style="font-size: 12.5px;">{{ number_format($totalCount) }} {{ __('وصل') }}</strong>
                        </div>
                        <div class="col-3">
                            <span class="text-muted small d-block" style="font-size: 10px;">{{ __('الأقسام المشمولة') }}</span>
                            <strong class="text-secondary small">{{ $paymentType ? (\App\Models\Payment::PAYMENT_TYPES[$paymentType] ?? $paymentType) : __('كافة الأقسام') }}</strong>
                        </div>
                    </div>
                </div>
            @else
                {{-- ترويسة مصغرة للورقات اللاحقة --}}
                <div class="dept-repeat-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{!! $hospitalLogoSrc !!}" style="height: 24px; width: auto;" alt="Logo">
                        <strong class="text-dark" style="font-size: 11px;">{{ __('مستشفى الكفاءات الأهلي — كشف حساب يومية الصندوق') }}</strong>
                    </div>
                    <div class="text-muted small" style="font-size: 10px;">
                        <span>{{ __('المستخدم:') }} <strong>{{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}</strong></span>
                        <span class="mx-2">|</span>
                        <span>{{ __('التاريخ:') }} <strong>{{ $fromDate ?? now()->format('Y/m/d') }}</strong></span>
                    </div>
                </div>
            @endif

            {{-- شريط عنوان القسم المعني مع عدد وصولاته --}}
            <div class="dept-title-banner d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark" style="font-size: 12px;">
                    <i class="fas fa-layer-group text-primary me-1.5"></i> {{ $departmentName }}
                </span>
                <span class="badge bg-secondary font-monospace" style="font-size: 10.5px; padding: 4px 8px;">
                    {{ number_format($deptPayments->count()) }} {{ __('وصل') }}
                </span>
            </div>

            {{-- جدول مقبوضات هذا القسم --}}
            <table class="table-print mb-2">
                <thead>
                    <tr>
                        <th class="col-header" style="width: 11%;">{{ __('الرقم') }}</th>
                        <th class="col-header" style="width: 12%;">{{ __('ت الوصل') }}</th>
                        <th class="col-header" style="text-align: right; width: 23%;">{{ __('اسم المريض') }}</th>
                        <th class="col-header" style="width: 11%;">{{ __('المستخدم') }}</th>
                        <th class="col-header" style="width: 13%; text-align: end;">{{ __('السعر') }}</th>
                        <th class="col-header" style="text-align: right; width: 18%;">{{ __('الخدمة الطبية') }}</th>
                        <th class="col-header" style="width: 12%;">{{ __('القسم') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($deptPayments as $payment)
                        @php
                            $patientName = $payment->patient_name;
                            $serviceName = $payment->service_details_text;
                            $cashierName = optional($payment->cashier)->name ?? __('النظام');
                            $receiptNo = $payment->receipt_number ?: ('#' . $payment->id);
                            $dateFormatted = $payment->paid_at ? $payment->paid_at->format('Y/m/d') : $payment->created_at->format('Y/m/d');
                        @endphp
                        <tr>
                            <td class="fw-bold font-monospace text-center">{{ $receiptNo }}</td>
                            <td class="font-monospace text-center">{{ $dateFormatted }}</td>
                            <td style="text-align: right; font-weight: 600;">{{ $patientName }}</td>
                            <td class="text-center">{{ $cashierName }}</td>
                            <td class="fw-bold text-end font-monospace" style="padding-left: 8px;">
                                {{ number_format($payment->amount, 0) }}
                            </td>
                            <td style="text-align: right;">{{ $serviceName }}</td>
                            <td class="text-center fw-semibold text-secondary small">{{ $departmentName }}</td>
                        </tr>
                    @endforeach
                    {{-- صف مجموع القسم مطابق للكشف الأصلي --}}
                    <tr class="table-group-subtotal">
                        <td colspan="4" class="text-center fw-bold" style="background-color: #f1f5f9; color: #005a9c; font-size: 11px;">
                            {{ __('المجموع') }}
                        </td>
                        <td class="text-end fw-bold text-dark font-monospace" style="font-size: 12px; background-color: #e2e8f0; padding-left: 8px;">
                            {{ number_format($deptTotal, 0) }}
                        </td>
                        <td colspan="2" class="text-center fw-bold text-muted small" style="background-color: #f1f5f9;">
                            {{ $departmentName }} ({{ number_format($deptPayments->count()) }} {{ __('وصل') }})
                        </td>
                    </tr>
                </tbody>
            </table>

            {{-- تذييل صفحة القسم --}}
            <div class="text-center text-muted mb-3" style="font-size: 9.5px; border-top: 1px dashed #cbd5e1; padding-top: 5px;">
                <span>{{ __('مستشفى الكفاءات الأهلي — كشف حساب يومية الصندوق') }} | {{ __('القسم:') }} <strong>{{ $departmentName }}</strong></span>
            </div>
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-2 d-block opacity-25"></i>
            <span>{{ __('لا توجد بيانات مسجلة في هذا التقرير') }}</span>
        </div>
    @endforelse

    {{-- صفحة المطابقة والتسوية الختامية المستقلة (Final Summary Page) --}}
    @if($payments->count() > 0)
    <div class="final-summary-page">
        {{-- ترويسة مصغرة للورقة الختامية --}}
        <div class="dept-repeat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <img src="{!! $hospitalLogoSrc !!}" style="height: 26px; width: auto;" alt="Logo">
                <strong class="text-dark fs-6">{{ __('مستشفى الكفاءات الأهلي — المطابقة الختامية للصندوق') }}</strong>
            </div>
            <div class="text-muted small" style="font-size: 10px;">
                <span>{{ __('المستخدم:') }} <strong>{{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}</strong></span>
                <span class="mx-2">|</span>
                <span>{{ __('التاريخ:') }} <strong>{{ $fromDate ?? now()->format('Y/m/d') }}</strong></span>
            </div>
        </div>

        <div class="row g-3">
            {{-- جدول إجماليات الأقسام المعتمدة --}}
            <div class="col-7">
                <div class="border rounded p-2 bg-white">
                    <h6 class="fw-bold text-primary mb-2" style="font-size: 12px;">
                        <i class="fas fa-layer-group me-1"></i> {{ __('إيرادات الأقسام ومطابقة المجموع') }}
                    </h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size: 11px;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>{{ __('القسم الطبي / الإيراد') }}</th>
                                <th class="text-center">{{ __('عدد الوصولات') }}</th>
                                <th class="text-end">{{ __('المجموع (د.ع)') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandSum = 0; @endphp
                            @foreach($groupedData as $dName => $dRows)
                                @php 
                                    $dSum = $dRows->sum('amount'); 
                                    $grandSum += $dSum;
                                @endphp
                                <tr>
                                    <td class="text-center font-monospace">{{ $loop->iteration }}</td>
                                    <td class="fw-bold">{{ $dName }}</td>
                                    <td class="text-center font-monospace">{{ number_format($dRows->count()) }}</td>
                                    <td class="text-end font-monospace fw-bold">{{ number_format($dSum, 0) }}</td>
                                </tr>
                            @endforeach
                            <tr class="table-secondary fw-black">
                                <td colspan="2" class="text-center">{{ __('المجموع الكلي للمقبوضات') }}</td>
                                <td class="text-center font-monospace">{{ number_format($totalCount) }}</td>
                                <td class="text-end font-monospace text-primary" style="font-size: 12px;">{{ number_format($grandSum, 0) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- جدول سندات الاسترجاع إن وجدت --}}
                @php
                    $refundItems = $refunds ?? $refundRecords ?? collect();
                @endphp
                @if($refundItems->count() > 0)
                <div class="border rounded p-2 bg-white mt-3">
                    <h6 class="fw-bold text-danger mb-2" style="font-size: 12px;">
                        <i class="fas fa-undo-alt me-1"></i> {{ __('الاسترجاعات المدفوعة من صندوق القبض بموجب النماذج') }} ({{ $refundItems->count() }})
                    </h6>
                    <table class="table table-sm table-bordered mb-0" style="font-size: 10.5px;">
                        <thead class="table-danger text-danger">
                            <tr>
                                <th>{{ __('رقم السند') }}</th>
                                <th>{{ __('المريض') }}</th>
                                <th>{{ __('السبب') }}</th>
                                <th class="text-end">{{ __('المبلغ المسترجع') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($refundItems as $rf)
                            <tr>
                                <td class="font-monospace text-center">#{{ $rf->id }}</td>
                                <td>{{ $rf->patient_name ?? ($rf->patient?->name ?? '-') }}</td>
                                <td class="text-muted small">{{ Str::limit($rf->void_reason ?? __('استرجاع نقدي'), 25) }}</td>
                                <td class="text-end font-monospace text-danger fw-bold">-{{ number_format($rf->amount, 0) }}</td>
                            </tr>
                            @endforeach
                            <tr class="table-danger fw-bold">
                                <td colspan="3" class="text-center">{{ __('إجمالي المبالغ المسترجعة') }}</td>
                                <td class="text-end font-monospace text-danger">{{ number_format($totalRefunds ?? 0, 0) }} {{ __('د.ع') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

            {{-- بطاقة تسوية وملخص الصندوق مطابقة تماماً للمستند الأصلي --}}
            <div class="col-5">
                <div class="border border-2 border-primary rounded p-3 bg-white shadow-sm">
                    <h6 class="fw-black text-center text-primary mb-3 pb-2 border-bottom" style="font-size: 13px;">
                        <i class="fas fa-cash-register me-1"></i> {{ __('ملخص الصندوق') }} ({{ __('المطابقة المحاسبية') }})
                    </h6>
                    <table class="table table-sm table-borderless summary-drawer-table mb-2" style="font-size: 11px;">
                        <tbody>
                            <tr>
                                <td class="py-1.5 text-secondary">{{ __('مجموع المبالغ المقبوضة') }}:</td>
                                <td class="py-1.5 text-end font-monospace fw-bold text-dark">{{ number_format($totalAmount, 0) }} {{ __('د.ع') }}</td>
                            </tr>
                            @if(($totalRefunds ?? 0) > 0)
                            <tr class="text-danger">
                                <td class="py-1.5">{{ __('(-) مجموع المبالغ المسترجعة') }}:</td>
                                <td class="py-1.5 text-end font-monospace fw-bold">-{{ number_format($totalRefunds, 0) }} {{ __('د.ع') }}</td>
                            </tr>
                            @endif
                            @if(($electronicAmount ?? 0) > 0)
                            <tr class="text-primary">
                                <td class="py-1.5">{{ __('(-) المبالغ المقبوضة إلكترونياً (POS / بطاقة)') }}:</td>
                                <td class="py-1.5 text-end font-monospace fw-bold">-{{ number_format($electronicAmount, 0) }} {{ __('د.ع') }}</td>
                            </tr>
                            @endif
                            @if($totalInsurance > 0)
                            <tr class="text-info text-dark">
                                <td class="py-1.5">{{ __('مطالبات الضمان التأميني') }}:</td>
                                <td class="py-1.5 text-end font-monospace fw-bold">{{ number_format($totalInsurance, 0) }} {{ __('د.ع') }}</td>
                            </tr>
                            @endif
                            <tr class="border-top border-2 border-success bg-light">
                                <td class="py-2 fw-black text-success" style="font-size: 12px;">{{ __('صافي الصندوق (الرصيد النقدي الفعلي بالدرج)') }}:</td>
                                <td class="py-2 text-end font-monospace fw-black text-success" style="font-size: 13px;">
                                    {{ number_format($netCash ?? ($totalAmount - ($totalRefunds ?? 0) - ($electronicAmount ?? 0)), 0) }} {{ __('د.ع') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="text-center bg-light p-2 rounded border">
                        <small class="text-muted d-block" style="font-size: 10px;">{{ __('المطابقة الحسابية') }}</small>
                        <strong class="text-primary font-monospace" style="font-size: 12.5px;">
                            {{ number_format($netCash ?? ($totalAmount - ($totalRefunds ?? 0) - ($electronicAmount ?? 0)), 0) }} {{ __('دينار عراقي') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

        {{-- قسم التواقيع والاعتماد --}}
        <div class="row mt-4 pt-4 border-top" style="page-break-inside: avoid; break-inside: avoid;">
            <div class="col-4 text-center">
                <p class="fw-bold mb-4" style="font-size: 11.5px;">{{ __('أمين الصندوق (الكاشير)') }}</p>
                <p class="text-muted small">........................................</p>
            </div>
            <div class="col-4 text-center">
                <p class="fw-bold mb-4" style="font-size: 11.5px;">{{ __('المحاسب المالي') }}</p>
                <p class="text-muted small">........................................</p>
            </div>
            <div class="col-4 text-center">
                <p class="fw-bold mb-4" style="font-size: 11.5px;">{{ __('مدير الحسابات / التدقيق') }}</p>
                <p class="text-muted small">........................................</p>
            </div>
        </div>

        {{-- تذييل المستند الرسمي --}}
        <div class="text-center text-muted mt-3" style="font-size: 9px; page-break-inside: avoid; break-inside: avoid;">
            <span>{{ __('منظومة مستشفى الكفاءات الأهلي الطبية المعتمدة — كشف مالي رسمي ومطابق محاسبياً صادر إلكترونياً') }}</span>
        </div>
    </div>
    @endif

</div>

</body>
</html>
