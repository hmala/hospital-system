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
        }

        .report-header-container {
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

        /* Fixed Layout Table Architecture */
        .table-print {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
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
        }
        .table-print th.col-header {
            background-color: #f1f5f9 !important;
            color: #1e293b;
            font-weight: 800;
            border: 1px solid #cbd5e1;
            padding: 7px 4px;
            text-align: center;
            font-size: 11px;
            white-space: nowrap;
        }
        .table-print td {
            border: 1px solid #e2e8f0;
            padding: 6px 4px;
            text-align: center;
            font-size: 10.5px;
            vertical-align: middle;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4 landscape;
                margin: 8mm 10mm;
            }
            .table-print th.col-header {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .document-title-banner {
                background: #005a9c !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-card {
                background-color: #f8fafc !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body onload="window.print()">

<div class="container-fluid py-2">
    <!-- أزرار التحكم العلوية (تختفي عند الطباعة) -->
    <div class="no-print d-flex justify-content-between align-items-center mb-3 p-3 bg-light rounded border">
        <div>
            <h5 class="mb-0 fw-bold"><i class="fas fa-print me-2 text-primary"></i>معاينة طباعة كشف مقبوضات الكاشير</h5>
            <small class="text-muted">الهدر المعتمد يظهر في ترويسة التقرير، وعناوين الأعمدة تتكرر بانتظام وثبات تام عبر كافة الصفحات</small>
        </div>
        <div class="d-flex gap-2">
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
                <span>كشف مقبوضات ووصولات الكاشير المعتمد (Cashier Revenue & Receipts Statement)</span>
            </div>
            <div class="banner-meta">
                <span class="report-badge">
                    <i class="fas fa-calendar-alt me-1"></i>
                    @if($fromDate && $toDate)
                        الفترة: {{ $fromDate }} إلى {{ $toDate }}
                    @elseif($fromDate)
                        من تاريخ: {{ $fromDate }}
                    @elseif($toDate)
                        لغاية تاريخ: {{ $toDate }}
                    @else
                        كافة الفترات المسجلة
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
                    <span class="text-muted small d-block" style="font-size: 11px;">المقبوضات المحصلة (المريض)</span>
                    <strong class="text-success fs-6">{{ number_format($totalAmount, 0) }} د.ع</strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted small d-block" style="font-size: 11px;">إجمالي مطالبات الضمان</span>
                    <strong class="text-primary fs-6">{{ number_format($totalInsurance, 0) }} د.ع</strong>
                </div>
                <div class="col-3 border-end">
                    <span class="text-muted small d-block" style="font-size: 11px;">عدد الوصولات المصدرة</span>
                    <strong class="text-dark fs-6">{{ number_format($totalCount) }} وصل</strong>
                </div>
                <div class="col-3">
                    <span class="text-muted small d-block" style="font-size: 11px;">القسم / الكاشير المستعلم</span>
                    <strong class="text-secondary small">{{ $paymentType ? (\App\Models\Payment::PAYMENT_TYPES[$paymentType] ?? $paymentType) : 'كافة الأقسام' }}</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول الوصولات الرئيسي المنظم بدقة عبر كافة الصفحات -->
    <table class="table-print mb-4">
        <thead>
            <!-- عناوين أعمدة الجدول المتكررة بثبات هندسي في أعلى كل صفحة -->
            <tr>
                <th class="col-header" style="width: 3.5%;">#</th>
                <th class="col-header" style="width: 12%;">رقم الوصل</th>
                <th class="col-header" style="width: 11.5%;">التاريخ والوقت</th>
                <th class="col-header" style="text-align: right; width: 18%;">اسم المريض</th>
                <th class="col-header" style="width: 10%;">الكاشير</th>
                <th class="col-header" style="width: 13%;">القسم / الخدمة</th>
                <th class="col-header" style="width: 13%;">نوع التغطية</th>
                <th class="col-header" style="width: 11%;">المحصل (المريض)</th>
                <th class="col-header" style="width: 8%;">طريقة الدفع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            @php
                $patientName = $payment->patient_name ?? optional($payment->patient)->user->name ?? optional($payment->emergency)->emergencyPatient?->name ?? optional($payment->emergency)->patient?->user?->name ?? 'غير محدد';
                $patientPhone = optional($payment->patient)->user->phone ?? optional($payment->emergency)->emergencyPatient?->phone ?? optional($payment->emergency)->patient?->user?->phone;
                $isInsurance = ($payment->insurance_type && $payment->insurance_type !== 'none') || ($payment->insurance_share > 0);
            @endphp
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="fw-bold font-monospace">{{ $payment->receipt_number ?: ('#REC-' . $payment->id) }}</td>
                <td>
                    <div class="fw-bold">{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : $payment->created_at->format('Y-m-d') }}</div>
                    <small class="text-muted font-monospace">{{ $payment->paid_at ? $payment->paid_at->format('h:i A') : $payment->created_at->format('h:i A') }}</small>
                </td>
                <td style="text-align: right;">
                    <strong class="text-dark">{{ $patientName }}</strong>
                    @if($patientPhone)
                        <br><small class="text-muted font-monospace">{{ $patientPhone }}</small>
                    @endif
                </td>
                <td>{{ optional($payment->cashier)->name ?? 'النظام' }}</td>
                <td>
                    <span class="fw-bold text-dark">{{ \App\Models\Payment::PAYMENT_TYPES[$payment->payment_type] ?? $payment->payment_type }}</span>
                    @if($payment->description)
                        <br><small class="text-muted">{{ Str::limit($payment->description, 35) }}</small>
                    @endif
                </td>
                {{-- نوع التغطية (كاش أم ضمان) --}}
                <td>
                    @if($isInsurance)
                        <div class="text-primary fw-bold" style="font-size: 11px;">
                            <i class="fas fa-shield-alt me-1"></i>
                            {{ $payment->insurance_type_name }}
                        </div>
                        @if($payment->insurance_card_no)
                            <small class="text-muted font-monospace d-block">#{{ $payment->insurance_card_no }}</small>
                        @endif
                        @if($payment->insurance_share > 0)
                            <small class="text-primary fw-semibold d-block" style="font-size: 10px;">
                                حصة الضمان: {{ number_format($payment->insurance_share, 0) }} د.ع
                            </small>
                        @endif
                    @else
                        <span class="badge bg-light text-secondary border px-2 py-1">
                            <i class="fas fa-money-bill-wave me-1 text-success"></i> نقدي (كاش)
                        </span>
                    @endif
                </td>
                <td class="fw-bold text-dark font-monospace fs-6">
                    {{ number_format($payment->amount, 0) }} د.ع
                </td>
                <td>{{ $payment->payment_method_name }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="9" class="text-center py-4 text-muted">لا توجد بيانات مسجلة في هذا التقرير</td>
            </tr>
            @endforelse
        </tbody>
        @if($payments->count() > 0)
        <tfoot>
            <tr style="background-color: #f1f5f9; font-weight: bold; border-top: 2px solid #005a9c;">
                <td colspan="6" style="text-align: right; padding: 10px 14px; font-size: 12px;">
                    <i class="fas fa-calculator me-1 text-primary"></i> إجمالي المقبوضات المحصلة ومطالبات الضمان للتقرير:
                </td>
                <td style="text-align: center; font-size: 11px; color: #005a9c; padding: 10px 4px;">
                    @if($totalInsurance > 0)
                        <span>حصة الضمان: <strong class="font-monospace">{{ number_format($totalInsurance, 0) }}</strong> د.ع</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td style="font-size: 13px; color: #198754; text-align: center; padding: 10px 4px;">
                    <strong class="font-monospace">{{ number_format($totalAmount, 0) }}</strong> د.ع
                </td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- قسم التواقيع والاعتماد في نهاية التقرير -->
    <div class="row mt-4 pt-3" style="page-break-inside: avoid; break-inside: avoid;">
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">أمين الصندوق (الكاشير)</p>
            <p class="text-muted">........................................</p>
        </div>
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">المحاسب المالي</p>
            <p class="text-muted">........................................</p>
        </div>
        <div class="col-4 text-center">
            <p class="fw-bold mb-4">مدير الحسابات / التدقيق</p>
            <p class="text-muted">........................................</p>
        </div>
    </div>
</div>

</body>
</html>
