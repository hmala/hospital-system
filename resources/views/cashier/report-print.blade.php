<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير مقبوضات الكاشير - طباعة</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #fff;
            color: #212529;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .table-print {
            width: 100%;
            border-collapse: collapse;
        }
        .table-print thead {
            display: table-header-group; /* يضمن تكرار الهدر في كل صفحة مطبوعة */
        }
        .table-print tfoot {
            display: table-footer-group;
        }
        .table-print tr {
            page-break-inside: avoid;
        }
        .table-print th.col-header {
            background-color: #f1f5f9 !important;
            color: #1e293b;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 8px 6px;
            text-align: center;
            font-size: 11.5px;
        }
        .table-print td {
            border: 1px solid #e2e8f0;
            padding: 6px;
            text-align: center;
            font-size: 11.5px;
        }
        .header-box {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .summary-card {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
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
            <h5 class="mb-0 fw-bold"><i class="fas fa-print me-2 text-primary"></i>معاينة طباعة تقرير الكاشير</h5>
            <small class="text-muted">الهدر سيتكرر تلقائياً في أعلى كل صفحة مطبوعة</small>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
                <i class="fas fa-print me-1"></i> طباعة الآن
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fas fa-times me-1"></i> إغلاق
            </button>
        </div>
    </div>

    <!-- جدول الوصولات الرئيسي مع تكرار الهدر في كل صفحة -->
    <table class="table-print mb-4">
        <thead>
            <!-- الهدر المتكرر في أعلى كل ورقة -->
            <tr>
                <th colspan="8" class="p-0 border-0 bg-white text-start">
                    <div class="header-box">
                        <div class="row align-items-center">
                            <div class="col-4">
                                <h5 class="fw-bold mb-1 text-primary">مستشفى النظام الطبي الحديث</h5>
                                <p class="text-muted small mb-0">قسم الحسابات المالية والتدقيق</p>
                            </div>
                            <div class="col-4 text-center">
                                <h5 class="fw-bold mb-1 border-bottom pb-1 d-inline-block">كشف مقبوضات ووصولات الكاشير</h5>
                                <div class="small text-muted mt-1">
                                    @if($fromDate && $toDate)
                                        الفترة: <strong>{{ $fromDate }}</strong> إلى <strong>{{ $toDate }}</strong>
                                    @else
                                        كافة السجلات المسجلة
                                    @endif
                                </div>
                            </div>
                            <div class="col-4 text-start">
                                <div class="small"><strong>تاريخ الطباعة:</strong> {{ now()->format('Y-m-d H:i') }}</div>
                                <div class="small"><strong>الكاشير المسجل:</strong> {{ auth()->user()->name }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- ملخص مالي سريع متكرر -->
                    <div class="summary-card">
                        <div class="row text-center gy-1">
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block" style="font-size: 11px;">إجمالي المقبوضات المحصلة</span>
                                <strong class="text-success fs-6">{{ number_format($totalAmount, 0) }} د.ع</strong>
                            </div>
                            <div class="col-4 border-end">
                                <span class="text-muted small d-block" style="font-size: 11px;">عدد الوصولات المصدرة</span>
                                <strong class="text-primary fs-6">{{ number_format($totalCount) }} وصل</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-muted small d-block" style="font-size: 11px;">القسم المختار</span>
                                <strong class="text-dark fs-6">{{ $paymentType ? (\App\Models\Payment::PAYMENT_TYPES[$paymentType] ?? $paymentType) : 'كافة الأقسام' }}</strong>
                            </div>
                        </div>
                    </div>
                </th>
            </tr>

            <!-- عناوين أعمدة الجدول المتكررة -->
            <tr>
                <th class="col-header" style="width: 35px;">#</th>
                <th class="col-header" style="width: 140px;">رقم الوصل</th>
                <th class="col-header" style="width: 130px;">التاريخ والوقت</th>
                <th class="col-header" style="text-align: right; width: 22%;">اسم المريض</th>
                <th class="col-header" style="width: 130px;">المستخدم (الكاشير)</th>
                <th class="col-header">القسم / نوع الخدمة</th>
                <th class="col-header" style="width: 120px;">المبلغ المدفوع</th>
                <th class="col-header" style="width: 90px;">طريقة الدفع</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $payment)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td class="fw-bold">{{ $payment->receipt_number ?: ('#REC-' . $payment->id) }}</td>
                <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : $payment->created_at->format('Y-m-d H:i') }}</td>
                <td style="text-align: right;">
                    <strong>{{ optional($payment->patient)->user->name ?? optional($payment->emergency)->emergencyPatient?->name ?? optional($payment->emergency)->patient?->user?->name ?? 'غير محدد' }}</strong>
                    @if(optional($payment->patient)->user->phone ?? optional($payment->emergency)->emergencyPatient?->phone ?? optional($payment->emergency)->patient?->user?->phone)
                        <br><small class="text-muted">{{ optional($payment->patient)->user->phone ?? optional($payment->emergency)->emergencyPatient?->phone ?? optional($payment->emergency)->patient?->user?->phone }}</small>
                    @endif
                </td>
                <td>{{ optional($payment->cashier)->name ?? 'النظام' }}</td>
                <td>
                    <span class="fw-semibold">{{ \App\Models\Payment::PAYMENT_TYPES[$payment->payment_type] ?? $payment->payment_type }}</span>
                    @if($payment->description)
                        <br><small class="text-muted">{{ $payment->description }}</small>
                    @endif
                </td>
                <td class="fw-bold text-dark">{{ number_format($payment->amount, 0) }} د.ع</td>
                <td>{{ $payment->payment_method_name }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">لا توجد بيانات مسجلة في هذا التقرير</td>
            </tr>
            @endforelse
        </tbody>
        @if($payments->count() > 0)
        <tfoot>
            <tr style="background-color: #f8fafc; font-weight: bold;">
                <td colspan="6" style="text-align: right; padding: 8px 12px;">إجمالي المبالغ المحصلة:</td>
                <td style="font-size: 13px; color: #198754;">{{ number_format($totalAmount, 0) }} د.ع</td>
                <td></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <!-- قسم التواقيع والاعتماد في نهاية التقرير -->
    <div class="row mt-4 pt-3" style="page-break-inside: avoid;">
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
