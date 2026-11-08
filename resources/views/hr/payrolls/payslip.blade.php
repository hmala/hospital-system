<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قسيمة راتب الموظف - {{ $slip->employee->full_name }} ({{ $slip->payrollCycle->cycle_month ?? 'شيت الرواتب' }})</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            margin: 0;
            padding: 25px;
        }

        .payslip-container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* شريط التحكم بالطباعة */
        .print-controls {
            max-width: 820px;
            margin: 0 auto 15px;
        }

        /* الترويسة الرسمية */
        .payslip-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .hospital-brand h4 {
            font-weight: 800;
            color: #1e3a8a;
            margin-bottom: 2px;
        }

        .payslip-badge {
            background: #1e3a8a;
            color: #ffffff;
            padding: 6px 16px;
            border-radius: 6px;
            font-size: 13pt;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        /* بطاقة بيانات الموظف */
        .employee-info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 18px;
            margin-bottom: 20px;
        }

        .info-cell {
            padding: 4px 8px;
        }

        .info-label {
            font-size: 8.5pt;
            color: #64748b;
            font-weight: 600;
            display: block;
        }

        .info-val {
            font-size: 10pt;
            font-weight: 700;
            color: #0f172a;
        }

        /* جدول التفاصيل المالية المزدوج */
        .salary-table {
            border: 1px solid #cbd5e1;
            margin-bottom: 20px;
        }

        .salary-table th {
            font-size: 9.5pt;
            font-weight: 800;
            padding: 8px 12px;
            text-align: center;
        }

        .salary-table td {
            font-size: 9pt;
            padding: 8px 12px;
            vertical-align: middle;
        }

        .earnings-header {
            background-color: #f0fdf4 !important;
            color: #166534 !important;
            border-bottom: 2px solid #22c55e !important;
        }

        .deductions-header {
            background-color: #fef2f2 !important;
            color: #991b1b !important;
            border-bottom: 2px solid #ef4444 !important;
        }

        .earnings-row {
            background-color: #ffffff;
        }

        .deductions-row {
            background-color: #ffffff;
        }

        /* بطاقة صافي الراتب البارزة */
        .net-salary-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: #ffffff;
            border-radius: 8px;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .net-salary-title {
            font-size: 11pt;
            font-weight: 700;
            opacity: 0.9;
            margin: 0;
        }

        .net-salary-amount {
            font-size: 18pt;
            font-weight: 900;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .net-salary-currency {
            font-size: 11pt;
            opacity: 0.85;
            font-weight: 600;
        }

        /* التواقيع الرسمية */
        .signatures-area {
            border-top: 1px dashed #cbd5e1;
            padding-top: 20px;
            margin-top: 20px;
        }

        .sig-box {
            text-align: center;
            padding: 8px;
        }

        .sig-title {
            font-size: 8.5pt;
            font-weight: 700;
            color: #475569;
            margin-bottom: 35px;
        }

        .sig-line {
            border-bottom: 1px solid #94a3b8;
            width: 75%;
            margin: 0 auto;
        }

        /* تذييل الورقة */
        .payslip-footer {
            text-align: center;
            font-size: 7.5pt;
            color: #94a3b8;
            margin-top: 25px;
            border-top: 1px solid #f1f5f9;
            padding-top: 10px;
        }

        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }

            .print-controls {
                display: none !important;
            }

            .payslip-container {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                border-radius: 0 !important;
                padding: 15mm !important;
                max-width: 100% !important;
                page-break-inside: avoid !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- شريط التحكم والأزرار -->
    <div class="print-controls d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold text-dark mb-0">
                <i class="fas fa-file-invoice-dollar text-success me-2"></i>معاينة قسيمة الراتب الرسمية
            </h5>
            <small class="text-muted">دورة رواتب شهر: {{ $slip->payrollCycle->cycle_month ?? '—' }}</small>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-success fw-bold px-4 shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة القسيمة (Print)
            </button>
            <a href="{{ route('hr.payrolls.show', $slip->hr_payroll_cycle_id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة لشيت الرواتب
            </a>
        </div>
    </div>

    <!-- بطاقة قسيمة الراتب القياسية -->
    <div class="payslip-container">

        <!-- ترويسة القسيمة -->
        <div class="payslip-header d-flex justify-content-between align-items-center">
            <div class="hospital-brand">
                <h4>{{ $hospital->name ?? 'مستشفى الفيحاء الأهلي' }}</h4>
                <div class="text-muted small">
                    <i class="fas fa-map-marker-alt me-1"></i> {{ $hospital->address ?? 'العراق - البصرة' }} 
                    | <i class="fas fa-phone me-1"></i> {{ $hospital->phone ?? '07700000000' }}
                </div>
                <div class="small fw-semibold text-secondary mt-1">قسم الموارد البشرية والرواتب (HR Department)</div>
            </div>

            <div class="text-start">
                <div class="payslip-badge">قسيمة راتب الموظف</div>
                <div class="text-muted small mt-1 font-monospace">
                    الشهر: <strong>{{ $slip->payrollCycle->cycle_month ?? now()->format('Y-m') }}</strong>
                </div>
                <div class="text-muted small">
                    تاريخ الإصدار: {{ now()->format('Y/m/d') }}
                </div>
            </div>
        </div>

        <!-- معلومات الموظف -->
        <div class="employee-info-card">
            <div class="row g-2">
                <div class="col-sm-4 info-cell">
                    <span class="info-label">اسم الموظف الكامل:</span>
                    <span class="info-val">{{ $slip->employee->full_name }}</span>
                </div>
                <div class="col-sm-4 info-cell">
                    <span class="info-label">الرقم الوظيفي (ID):</span>
                    <span class="info-val font-monospace">{{ $slip->employee->employee_code }}</span>
                </div>
                <div class="col-sm-4 info-cell">
                    <span class="info-label">القسم التابع له:</span>
                    <span class="info-val">{{ $slip->employee->department?->name ?? 'قسم عام' }}</span>
                </div>

                <div class="col-sm-4 info-cell">
                    <span class="info-label">المسمى الوظيفي:</span>
                    <span class="info-val">{{ $slip->employee->job_title }}</span>
                </div>
                <div class="col-sm-4 info-cell">
                    <span class="info-label">طريقة الصرف:</span>
                    <span class="info-val">
                        @if($slip->payment_method === 'bank')
                            <span class="badge bg-primary"><i class="fas fa-university me-1"></i>تحويل بنكي</span>
                            <small class="text-muted d-block font-monospace">{{ $slip->employee->bank_name }} - {{ $slip->employee->bank_account_number }}</small>
                        @else
                            <span class="badge bg-success"><i class="fas fa-money-bill-wave me-1"></i>نقداً (كاش)</span>
                        @endif
                    </span>
                </div>
                <div class="col-sm-4 info-cell">
                    <span class="info-label">حالة القسيمة:</span>
                    <span class="info-val">
                        @if($slip->payrollCycle?->status === 'approved')
                            <span class="badge bg-success-subtle text-success border border-success"><i class="fas fa-check-double me-1"></i>معتمد ومصروف</span>
                        @else
                            <span class="badge bg-warning-subtle text-dark border border-warning"><i class="fas fa-clock me-1"></i>مسودة قيد التدقيق</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- جدول تفكيك الراتب (الاستحقاقات مقابل الاستقطاعات) -->
        <div class="table-responsive">
            <table class="table table-bordered salary-table mb-0">
                <thead>
                    <tr>
                        <th colspan="2" class="earnings-header w-50">
                            <i class="fas fa-plus-circle me-1"></i> الاستحقاقات والمكافآت (Earnings)
                        </th>
                        <th colspan="2" class="deductions-header w-50">
                            <i class="fas fa-minus-circle me-1"></i> الاستقطاعات والخصومات (Deductions)
                        </th>
                    </tr>
                    <tr class="table-light small">
                        <th>البند</th>
                        <th class="text-end" style="width: 120px;">المبلغ (د.ع)</th>
                        <th>البند</th>
                        <th class="text-end" style="width: 120px;">المبلغ (د.ع)</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- سطر 1: الأساسي مقابل الغياب -->
                    <tr>
                        <td class="fw-semibold">الراتب الأساسي الشهري</td>
                        <td class="text-end fw-bold font-monospace">{{ number_format($slip->basic_salary, 0) }}</td>
                        <td class="fw-semibold">استقطاع الغياب والتأخير</td>
                        <td class="text-end font-monospace {{ $slip->absence_deduction > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $slip->absence_deduction > 0 ? number_format($slip->absence_deduction, 0) : '0' }}
                        </td>
                    </tr>

                    <!-- سطر 2: المخصصات مقابل العقوبات -->
                    <tr>
                        <td class="fw-semibold">المخصصات والعلاوات الثابتة</td>
                        <td class="text-end font-monospace {{ $slip->allowances > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                            {{ number_format($slip->allowances, 0) }}
                        </td>
                        <td class="fw-semibold">العقوبات الإدارية والإنذارات</td>
                        <td class="text-end font-monospace {{ $slip->penalties_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $slip->penalties_amount > 0 ? number_format($slip->penalties_amount, 0) : '0' }}
                        </td>
                    </tr>

                    <!-- سطر 3: الإضافي مقابل السلف -->
                    <tr>
                        <td class="fw-semibold">أجر العمل الإضافي (Overtime)</td>
                        <td class="text-end font-monospace {{ $slip->overtime_amount > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                            {{ $slip->overtime_amount > 0 ? number_format($slip->overtime_amount, 0) : '0' }}
                        </td>
                        <td class="fw-semibold">قسط السلفة الشهرية</td>
                        <td class="text-end font-monospace {{ $slip->loan_deduction > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $slip->loan_deduction > 0 ? number_format($slip->loan_deduction, 0) : '0' }}
                        </td>
                    </tr>

                    <!-- سطر 4: المكافآت مقابل الضمان -->
                    <tr>
                        <td class="fw-semibold">المكافآت والحوافز التشجيعية</td>
                        <td class="text-end font-monospace {{ $slip->bonuses_amount > 0 ? 'text-success fw-bold' : 'text-muted' }}">
                            {{ $slip->bonuses_amount > 0 ? number_format($slip->bonuses_amount, 0) : '0' }}
                        </td>
                        <td class="fw-semibold">استقطاع الضمان الاجتماعي</td>
                        <td class="text-end font-monospace {{ $slip->social_security_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $slip->social_security_amount > 0 ? number_format($slip->social_security_amount, 0) : '0' }}
                        </td>
                    </tr>

                    <!-- سطر 5: فارغ مقابل الضريبة -->
                    <tr>
                        <td class="text-muted">—</td>
                        <td class="text-end text-muted font-monospace">—</td>
                        <td class="fw-semibold">استقطاع ضريبة الدخل</td>
                        <td class="text-end font-monospace {{ $slip->tax_amount > 0 ? 'text-danger fw-bold' : 'text-muted' }}">
                            {{ $slip->tax_amount > 0 ? number_format($slip->tax_amount, 0) : '0' }}
                        </td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td class="text-success">إجمالي الاستحقاقات (Gross):</td>
                        <td class="text-end text-success fs-6 font-monospace">
                            {{ number_format($slip->total_earnings, 0) }} د.ع
                        </td>
                        <td class="text-danger">إجمالي الاستقطاعات:</td>
                        <td class="text-end text-danger fs-6 font-monospace">
                            {{ number_format($slip->total_deductions, 0) }} د.ع
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- بطاقة صافي الراتب النهائي للدفع -->
        <div class="net-salary-card mt-3">
            <div>
                <div class="net-salary-title">
                    <i class="fas fa-hand-holding-usd me-2"></i>صافي الراتب المستحق للصرف (Net Pay)
                </div>
                <div class="small opacity-75 mt-1">
                    طريقة التسليم: {{ $slip->payment_method === 'bank' ? 'حساب بنكي' : 'استلام نقدي من الصندوق' }}
                </div>
            </div>
            <div class="text-end">
                <div class="net-salary-amount">
                    {{ number_format($slip->net_salary, 0) }}
                    <span class="net-salary-currency">دينار عراقي</span>
                </div>
            </div>
        </div>

        @if($slip->notes)
        <div class="alert alert-light border small text-muted py-2 mb-3">
            <strong>ملاحظات:</strong> {{ $slip->notes }}
        </div>
        @endif

        <!-- مساحة التواقيع والاعتماد الرسمي -->
        <div class="signatures-area">
            <div class="row text-center">
                <div class="col-4 sig-box">
                    <div class="sig-title">إعداد: مسؤول الرواتب</div>
                    <div class="sig-line"></div>
                </div>
                <div class="col-4 sig-box">
                    <div class="sig-title">تدقيق: المحاسب المالي</div>
                    <div class="sig-line"></div>
                </div>
                <div class="col-4 sig-box">
                    <div class="sig-title">اعتماد: مدير الموارد البشرية</div>
                    <div class="sig-line"></div>
                </div>
            </div>
        </div>

        <!-- تذييل الصفحة -->
        <div class="payslip-footer">
            وثيقة راتب رسمية صادرة عن النظام الإلكتروني لإدارة المستشفى | {{ config('app.name', 'Hospital System') }} | كود التحقق: {{ md5($slip->id . $slip->created_at) }}
        </div>

    </div>

</body>
</html>
