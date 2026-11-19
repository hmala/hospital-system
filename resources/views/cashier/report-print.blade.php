<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف مقبوضات ووصولات الكاشير المعتمد - طباعة</title>
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
    @endphp
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #fff;
            color: #212529;
            font-size: 11px;
            margin: 0;
            padding: 0;
            line-height: 1.4;
            position: relative;
        }

        /* Distinct & Clear Center Watermark Repeating on All Pages */
        .report-watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 480px;
            height: 480px;
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            opacity: 0.15; /* وضوح بارز ومميز في منتصف كل صفحة */
            pointer-events: none;
            z-index: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .report-content-wrapper {
            position: relative;
            z-index: 1;
            width: 95%;
            max-width: 280mm;
            margin: 0 auto;
            padding-bottom: 8mm;
        }

        .report-header-container {
            padding-top: 10mm;
            margin-bottom: 12px;
        }

        /* Standard 3-Zone Hospital Header */
        .header-box {
            border-bottom: 2px solid #005a9c;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        .header-hospital-ar {
            text-align: right;
            flex: 1;
        }
        .header-hospital-ar h2 {
            font-size: 17px;
            font-weight: 900;
            color: #005a9c;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .header-hospital-ar h4 {
            font-size: 12.5px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
        }
        .header-hospital-ar p {
            font-size: 10px;
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
            max-width: 110px;
            max-height: 80px;
            object-fit: contain;
        }
        .header-hospital-en {
            text-align: left;
            direction: ltr;
            flex: 1;
        }
        .header-hospital-en h2 {
            font-size: 15px;
            font-weight: 900;
            color: #005a9c;
            margin: 0 0 2px 0;
            line-height: 1.2;
        }
        .header-hospital-en h4 {
            font-size: 11.5px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
        }
        .header-hospital-en p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            padding: 7px 14px;
            border-radius: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .banner-title {
            font-size: 13px;
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
            font-size: 11px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
        }

        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
        }

        /* Fixed Layout Table Architecture with Transparent Cells for Watermark */
        .table-print {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            background-color: transparent !important;
        }
        .table-print thead {
            display: table-header-group; /* يضمن تكرار عناوين الأعمدة فقط في كل صفحة */
        }
        .table-print tfoot {
            display: table-row-group; /* يمنع تكرار المجموع في كل صفحة ويحصره في نهاية التقرير */
        }
        .table-print tr {
            page-break-inside: avoid;
            break-inside: avoid;
            background-color: transparent !important;
        }
        .table-print th.col-header {
            background-color: #f1f5f9 !important;
            color: #1e293b;
            font-weight: 800;
            border: 1.5px solid #64748b;
            padding: 8px 6px;
            text-align: center;
            font-size: 11px;
            white-space: nowrap;
        }
        .table-print td {
            border: 1px solid #94a3b8;
            padding: 7px 6px;
            text-align: center;
            font-size: 11px;
            line-height: 1.45;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
            background-color: transparent !important; /* شفاف حتى تظهر العلامة المائية بوضوح */
        }

        .table-group-subtotal td {
            background-color: #f8fafc !important;
            border-top: 2px solid #005a9c !important;
            border-bottom: 2px solid #475569 !important;
            font-weight: 800;
            padding: 8px 6px;
        }

        .dept-title-banner {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-right: 5px solid #005a9c;
            padding: 6px 12px;
            border-radius: 4px;
        }

        .dept-repeat-header {
            border-bottom: 1.5px solid #005a9c;
            padding-bottom: 5px;
            margin-bottom: 12px;
        }

        .dept-page-break {
            page-break-before: always !important;
            break-before: page !important;
            padding-top: 5mm;
        }

        .final-summary-page {
            page-break-before: always !important;
            break-before: page !important;
            padding-top: 5mm;
        }

        .summary-drawer-table td, .summary-drawer-table th {
            border: 1.5px solid #005a9c !important;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 15mm 12mm 15mm 12mm !important; /* مسافات علوية وسفلية وجانبية متناسقة ومريحة تمنع الالتصاق */
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
                background-color: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .table-print th.col-header {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .table-print td {
                background-color: transparent !important;
            }
            .dept-page-break {
                page-break-before: always !important;
                break-before: page !important;
            }
            .final-summary-page {
                page-break-before: always !important;
                break-before: page !important;
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
                opacity: 0.15 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

<!-- العلامة المائية للنظام واضحة في سنتر كل صفحة مطبوعة -->
<div class="report-watermark" style="background-image: url('{!! $watermarkSrc !!}');"></div>

<div class="report-content-wrapper">
    <!-- أزرار التحكم العلوية (تختفي عند الطباعة) -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border">
        <div>
            <h5 class="mb-0 fw-bold"><i class="fas fa-print me-2 text-primary"></i>معاينة طباعة كشف مقبوضات الكاشير</h5>
            <small class="text-muted d-block mt-1">
                تم حجب رابط المتصفح التلقائي نهائياً، وضبط تباعد الجدول العلوي، وتوضيح العلامة المائية في منتصف الصفحات.
            </small>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success border-opacity-25 px-3 py-2 rounded-pill small">
                <i class="fas fa-check-circle me-1"></i> العلامة المائية مفعلة (15%)
            </span>
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4 fw-bold">
                <i class="fas fa-print me-1"></i> طباعة الآن
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>

    <!-- الترويسة الرسمية المعتمدة (تظهر في رأس التقرير) -->
    <div class="report-header-container">
        <div class="header-box">
            <div class="d-flex justify-content-between align-items-center">
                <!-- الجانب الأيمن (عربي) -->
                <div class="header-hospital-ar">
                    <h2>مستشفى الكفاءات الأهلي</h2>
                    <h4>قسم الحسابات المالية والتدقيق</h4>
                    <p>إدارة الصناديق والمقبوضات المركزية المعتمدة</p>
                </div>

                <!-- المنتصف (الشعار الرسمي) -->
                <div class="header-logo">
                    <img src="{!! $hospitalLogoSrc !!}" 
                         alt="Hospital Logo" 
                         class="hospital-logo-img" 
                         onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                         style="max-height: 80px; max-width: 120px; object-fit: contain;">
                </div>

                <!-- الجانب الأيسر (إنجليزي) -->
                <div class="header-hospital-en">
                    <h2>Al-Kafa'at Private Hospital</h2>
                    <h4>Finance & Cashier Audit Dept</h4>
                    <p>Central Revenue & Cash Management Unit</p>
                </div>
            </div>
        </div>

        <!-- شريط عنوان المستند الموحد (Document Title Banner) -->
        <div class="document-title-banner">
            <div class="banner-title">
                <i class="fas fa-file-invoice-dollar me-2"></i>
                <span>{{ __('كشف حساب يومية الصندوق ومقبوضات الكاشير') }} (Cashier Daily Drawer Statement)</span>
            </div>
            <div class="banner-meta">
                <span class="report-badge">
                    <i class="fas fa-user-circle me-1"></i>
                    {{ __('المستخدم:') }} {{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}
                </span>
                <span class="report-badge">
                    <i class="fas fa-calendar-alt me-1"></i>
                    @if($fromDate && $toDate)
                        {{ $fromDate == $toDate ? $fromDate : ($fromDate . ' إلى ' . $toDate) }}
                    @elseif($fromDate)
                        {{ __('من تاريخ:') }} {{ $fromDate }}
                    @elseif($toDate)
                        {{ __('إلى تاريخ:') }} {{ $toDate }}
                    @else
                        {{ now()->format('Y/m/d') }}
                    @endif
                </span>
                <span class="report-badge">
                    <i class="fas fa-clock me-1"></i>
                    {{ now()->format('Y-m-d H:i') }}
                </span>
            </div>
        </div>

        <!-- ملخص مالي سريع متكرر -->
        <div class="summary-card">
            <div class="row text-center gy-1 align-items-center">
                <div class="col-3 border-end">
                    <span class="text-muted small d-block" style="font-size: 11px;">{{ __('المقبوضات المحصلة (المريض)') }}</span>
                    <strong class="text-success fs-6">{{ number_format($totalAmount, 0) }} {{ __('د.ع') }}</strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted small d-block" style="font-size: 11px;">{{ __('إجمالي مطالبات الضمان') }}</span>
                    <strong class="text-primary fs-6">{{ number_format($totalInsurance, 0) }} {{ __('د.ع') }}</strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted small d-block" style="font-size: 11px;">{{ __('عدد الوصولات المصدرة') }}</span>
                    <strong class="text-dark fs-6">{{ number_format($totalCount) }} {{ __('وصل') }}</strong>
                </div>
                <div class="col-3">
                    <span class="text-muted small d-block" style="font-size: 11px;">{{ __('القسم / الصندوق') }}</span>
                    <strong class="text-secondary small">{{ $paymentType ? (\App\Models\Payment::PAYMENT_TYPES[$paymentType] ?? $paymentType) : __('كافة الأقسام') }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- أقسام المقبوضات: كل قسم في جدول مستقل وبداية صفحة جديدة نظيفة -->
    @php $groupedData = $groupedPayments ?? $payments->groupBy(fn($p) => $p->payment_type_name ?: __('أخرى')); @endphp
    @forelse($groupedData as $departmentName => $deptPayments)
        @php
            $deptTotal = $deptPayments->sum('amount');
        @endphp
        <div class="dept-report-section @if(!$loop->first) dept-page-break @endif mb-4">
            @if(!$loop->first)
                {{-- ترويسة مصغرة للورقة الجديدة تضمن توثيق اسم المستشفى وتاريخ الكشف واسم الكاشير --}}
                <div class="dept-repeat-header d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <img src="{!! $hospitalLogoSrc !!}" style="height: 26px; width: auto;" alt="Logo">
                        <strong class="text-dark" style="font-size: 11.5px;">{{ __('مستشفى الكفاءات الأهلي — كشف حساب يومية الصندوق') }}</strong>
                    </div>
                    <div class="text-muted small" style="font-size: 10.5px;">
                        <span>{{ __('المستخدم:') }} <strong>{{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}</strong></span>
                        <span class="mx-2">|</span>
                        <span>{{ __('التاريخ:') }} <strong>{{ $fromDate ?? now()->format('Y/m/d') }}</strong></span>
                    </div>
                </div>
            @endif

            {{-- شريط عنوان القسم المعني مع شارة عدد وصولاته --}}
            <div class="dept-title-banner mb-2 d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark" style="font-size: 12px;">
                    <i class="fas fa-layer-group text-primary me-2"></i> {{ $departmentName }}
                </span>
                <span class="badge bg-secondary font-monospace" style="font-size: 10.5px; padding: 4px 8px;">
                    {{ number_format($deptPayments->count()) }} {{ __('وصل') }}
                </span>
            </div>

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
                    {{-- صف مجموع القسم --}}
                    <tr class="table-group-subtotal">
                        <td colspan="4" class="text-center fw-bold" style="background-color: #f1f5f9; color: #005a9c; font-size: 11.5px;">
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
        </div>
    @empty
        <div class="text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-2 d-block opacity-25"></i>
            <span>{{ __('لا توجد بيانات مسجلة في هذا التقرير') }}</span>
        </div>
    @endforelse

    {{-- صفحة المطابقة والتسوية الختامية (تبدأ في صفحة جديدة نظيفة ومستقلة) --}}
    @if($payments->count() > 0)
    <div class="final-summary-page">
        {{-- ترويسة مصغرة للورقة الختامية --}}
        <div class="dept-repeat-header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <img src="{!! $hospitalLogoSrc !!}" style="height: 28px; width: auto;" alt="Logo">
                <strong class="text-dark fs-6">{{ __('مستشفى الكفاءات الأهلي — المطابقة الختامية للصندوق') }}</strong>
            </div>
            <div class="text-muted small" style="font-size: 10.5px;">
                <span>{{ __('المستخدم:') }} <strong>{{ $selectedCashier ? $selectedCashier->name : ($cashierId ? (\App\Models\User::find($cashierId)?->name ?? __('كافة الصناديق')) : __('كافة الصناديق')) }}</strong></span>
                <span class="mx-2">|</span>
                <span>{{ __('التاريخ:') }} <strong>{{ $fromDate ?? now()->format('Y/m/d') }}</strong></span>
            </div>
        </div>

        {{-- جدول ملخص إيرادات الأقسام المعتمدة --}}
        <div class="document-title-banner mb-2" style="background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%); padding: 6px 14px;">
            <div class="banner-title" style="font-size: 12px;">
                <i class="fas fa-list-check me-2"></i>
                <span>{{ __('ملخص إيرادات الأقسام والمجموع الكلي للمقبوضات') }}</span>
            </div>
            <div class="banner-meta">
                <span class="report-badge" style="font-size: 10.5px;">{{ number_format($totalCount) }} {{ __('إجمالي الوصولات') }}</span>
            </div>
        </div>

        <table class="table-print mb-4">
            <thead>
                <tr>
                    <th class="col-header" style="width: 8%;">#</th>
                    <th class="col-header" style="text-align: right; width: 44%;">{{ __('القسم / التبويب') }}</th>
                    <th class="col-header" style="width: 20%;">{{ __('عدد الوصولات') }}</th>
                    <th class="col-header" style="width: 28%; text-align: end;">{{ __('إجمالي الإيراد المحصل') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($groupedData as $dName => $dItems)
                    <tr>
                        <td class="text-center font-monospace">{{ $loop->iteration }}</td>
                        <td style="text-align: right; font-weight: 700;">{{ $dName }}</td>
                        <td class="text-center font-monospace">{{ number_format($dItems->count()) }} {{ __('وصل') }}</td>
                        <td class="text-end fw-bold font-monospace" style="padding-left: 12px;">
                            {{ number_format($dItems->sum('amount'), 0) }} {{ __('د.ع') }}
                        </td>
                    </tr>
                @endforeach
                <tr style="background-color: #e2e8f0; font-weight: 900; border-top: 2.5px solid #005a9c; font-size: 13px;">
                    <td colspan="2" class="text-center" style="color: #005a9c; padding: 10px;">
                        {{ __('المجموع الكلي للمقبوضات') }}
                    </td>
                    <td class="text-center font-monospace" style="color: #005a9c;">
                        {{ number_format($totalCount) }} {{ __('وصل') }}
                    </td>
                    <td class="text-end font-monospace text-success fw-bold" style="font-size: 13.5px; padding-left: 12px;">
                        {{ number_format($totalAmount, 0) }} {{ __('د.ع') }}
                    </td>
                </tr>
            </tbody>
        </table>

        {{-- جدول الاسترجاعات المدفوعة من صندوق القبض بموجب النماذج (مطابق للصفحة 4 من الكشف الأصلي) --}}
        @if(isset($refunds) && $refunds->isNotEmpty())
            <div class="mb-4" style="page-break-inside: avoid; break-inside: avoid;">
                <div class="document-title-banner mb-2" style="background: linear-gradient(90deg, #9f1239 0%, #e11d48 100%); padding: 6px 12px;">
                    <div class="banner-title" style="font-size: 12px;">
                        <i class="fas fa-undo-alt me-2"></i>
                        <span>{{ __('الاسترجاعات المدفوعة من صندوق القبض بموجب النماذج') }}</span>
                    </div>
                    <div class="banner-meta">
                        <span class="report-badge" style="font-size: 10.5px;">{{ $refunds->count() }} {{ __('سند استرجاع') }}</span>
                    </div>
                </div>
                <table class="table-print mb-2">
                    <thead>
                        <tr>
                            <th class="col-header" style="width: 10%;">{{ __('الرقم (ر.وصل)') }}</th>
                            <th class="col-header" style="width: 12%;">{{ __('تاريخ الاسترجاع') }}</th>
                            <th class="col-header" style="width: 23%; text-align: right;">{{ __('اسم المريض') }}</th>
                            <th class="col-header" style="width: 11%;">{{ __('ص. القبض') }}</th>
                            <th class="col-header" style="width: 13%; text-align: end;">{{ __('المبلغ المسترجع') }}</th>
                            <th class="col-header" style="width: 18%; text-align: right;">{{ __('الخدمة الطبية') }}</th>
                            <th class="col-header" style="width: 13%;">{{ __('القسم') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($refunds as $ref)
                            @php
                                $refPatient = $ref->patient_name;
                                $refService = $ref->service_details_text;
                                $refCashier = optional($ref->voidedBy)->name ?? optional($ref->cashier)->name ?? __('النظام');
                                $refReceipt = $ref->original_receipt_number ?: ($ref->receipt_number ?: ('#' . $ref->id));
                                $refDate = $ref->voided_at ? $ref->voided_at->format('Y/m/d') : $ref->created_at->format('Y/m/d');
                            @endphp
                            <tr>
                                <td class="font-monospace text-center fw-bold">{{ $refReceipt }}</td>
                                <td class="font-monospace text-center">{{ $refDate }}</td>
                                <td style="text-align: right; font-weight: 600;">{{ $refPatient }}</td>
                                <td class="text-center">{{ $refCashier }}</td>
                                <td class="text-end fw-bold text-danger font-monospace" style="padding-left: 8px;">
                                    {{ number_format($ref->amount, 0) }}
                                </td>
                                <td style="text-align: right;">{{ $refService }}</td>
                                <td class="text-center text-secondary small">{{ $ref->payment_type_name }}</td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #fee2e2; font-weight: 800; border-top: 1.5px solid #be123c;">
                            <td colspan="4" class="text-center text-danger" style="font-size: 12px; padding: 7px;">
                                {{ __('مجموع الاسترجاعات') }}
                            </td>
                            <td class="text-end font-monospace text-danger fw-bold" style="font-size: 12.5px; padding-left: 8px;">
                                {{ number_format($totalRefunds ?? 0, 0) }}
                            </td>
                            <td colspan="2" class="text-start pe-3 text-muted small">
                                ({{ $refunds->count() }} {{ __('سند استرجاع') }})
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif

        {{-- جدول ملخص الصندوق (مطابق تماماً لما في الصفحة 4 من الكشف المرفق) --}}
        <div class="row justify-content-center mt-3 mb-4" style="page-break-inside: avoid; break-inside: avoid;">
            <div class="col-8 col-md-6">
                <table class="table-print summary-drawer-table" style="border: 2px solid #005a9c; margin: 0 auto; width: 100%;">
                    <thead>
                        <tr style="background-color: #005a9c; color: #fff;">
                            <th colspan="2" class="text-center py-2 fw-bold" style="font-size: 13px; letter-spacing: 0.5px;">
                                <i class="fas fa-cash-register me-1"></i> {{ __('ملخص الصندوق') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="fw-bold py-2 px-3 text-start" style="width: 55%; font-size: 11.5px; background-color: #f8fafc;">
                                {{ __('مجموع المبالغ المقبوضة') }}
                            </td>
                            <td class="fw-bold text-end font-monospace px-3 py-2 text-dark" style="width: 45%; font-size: 12.5px;">
                                {{ number_format($totalAmount, 0) }} {{ __('د.ع') }}
                            </td>
                        </tr>
                        @if(($totalRefunds ?? 0) > 0)
                        <tr>
                            <td class="fw-bold py-2 px-3 text-start text-danger" style="font-size: 11.5px; background-color: #fff1f2;">
                                {{ __('(-) مجموع المبالغ المسترجعة') }}
                            </td>
                            <td class="fw-bold text-end font-monospace px-3 py-2 text-danger" style="font-size: 12.5px;">
                                {{ number_format($totalRefunds, 0) }} {{ __('د.ع') }}
                            </td>
                        </tr>
                        @endif
                        @if(($electronicAmount ?? 0) > 0)
                        <tr>
                            <td class="fw-bold py-2 px-3 text-start text-primary" style="font-size: 11.5px; background-color: #f0f9ff;">
                                {{ __('(-) المبالغ المقبوضة إلكترونياً (POS / بطاقة)') }}
                            </td>
                            <td class="fw-bold text-end font-monospace px-3 py-2 text-primary" style="font-size: 12.5px;">
                                {{ number_format($electronicAmount, 0) }} {{ __('د.ع') }}
                            </td>
                        </tr>
                        @endif
                        <tr style="background-color: #ecfdf5; border-top: 2px solid #059669;">
                            <td class="fw-black py-2.5 px-3 text-start text-success" style="font-size: 12.5px;">
                                {{ __('صافي الصندوق (الرصيد النقدي الفعلي بالدرج)') }}
                            </td>
                            <td class="fw-black text-end font-monospace px-3 py-2.5 text-success" style="font-size: 13.5px;">
                                {{ number_format($netCash ?? ($totalAmount - ($totalRefunds ?? 0) - ($electronicAmount ?? 0)), 0) }} {{ __('د.ع') }}
                            </td>
                        </tr>
                        <tr style="background-color: #f1f5f9; border-top: 1.5px solid #cbd5e1;">
                            <td class="fw-bold py-2 px-3 text-start text-dark" style="font-size: 12px;">
                                {{ __('المجموع النهائي المعتمد') }}
                            </td>
                            <td class="fw-bold text-end font-monospace px-3 py-2 text-dark" style="font-size: 13px;">
                                {{ number_format($netCash ?? ($totalAmount - ($totalRefunds ?? 0) - ($electronicAmount ?? 0)), 0) }} {{ __('د.ع') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- قسم التواقيع والاعتماد في نهاية التقرير -->
        <div class="row mt-4 pt-3" style="page-break-inside: avoid; break-inside: avoid;">
            <div class="col-4 text-center">
                <p class="fw-bold mb-4">{{ __('أمين الصندوق (الكاشير)') }}</p>
                <p class="text-muted">........................................</p>
            </div>
            <div class="col-4 text-center">
                <p class="fw-bold mb-4">{{ __('المحاسب المالي') }}</p>
                <p class="text-muted">........................................</p>
            </div>
            <div class="col-4 text-center">
                <p class="fw-bold mb-4">{{ __('مدير الحسابات / التدقيق') }}</p>
                <p class="text-muted">........................................</p>
            </div>
        </div>

        <!-- تذييل المستند الرسمي المعتمد للنظام -->
        <div class="text-center text-muted small mt-3" style="font-size: 9.5px; page-break-inside: avoid; break-inside: avoid;">
            <span>{{ __('منظومة مستشفى الكفاءات الأهلي الطبية المعتمدة — كشف مالي رسمي ومطابق محاسبياً صادر إلكترونياً') }}</span>
        </div>
    </div>
    @endif

</body>
</html>
