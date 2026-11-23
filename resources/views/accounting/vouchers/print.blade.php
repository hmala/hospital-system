<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند محاسبي رسمي - {{ $entry->entry_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #1e293b;
        }
        .print-container {
            max-width: 900px;
            margin: 20px auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .table-custom thead th {
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e1;
        }
        .signature-box {
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            padding: 15px;
            height: 110px;
            background-color: #fafafa;
        }
        @media print {
            body {
                background: none;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
                max-width: 100%;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

<!-- Control Bar (Hidden on print) -->
<div class="container my-3 no-print" style="max-width: 900px;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
        <a href="{{ route('accounting.vouchers.index') }}" class="btn btn-outline-secondary btn-sm rounded-3">
            <i class="fas fa-arrow-right me-1"></i> عودة لسجل السندات
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('accounting.vouchers.create') }}" class="btn btn-outline-primary btn-sm rounded-3">
                <i class="fas fa-plus me-1"></i> سند جديد
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm rounded-3 px-4 shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة السند (A4)
            </button>
        </div>
    </div>
</div>

<!-- Official Printable Document -->
<div class="print-container">
    <!-- Header -->
    <div class="text-center mb-3">
        <h6 class="text-muted mb-1" style="font-size: 13px;">بِسْمِ اللَّـهِ الرَّحْمَـٰنِ الرَّحِيمِ</h6>
        <h4 class="fw-bold mb-1">مستشفى الشفاء التخصصي</h4>
        <p class="text-muted small mb-0">المنظومة المالية والرقابية المتكاملة (MedFinance IFRS) - الدليل المحاسبي الموحد العراقي</p>
    </div>

    <div class="d-flex justify-content-between align-items-center border-top border-bottom py-2 mb-4">
        <div>
            <span class="text-muted small">نوع السند:</span>
            <span class="fw-bold fs-5 text-primary">
                @if(str_contains($entry->description, 'عكس'))
                    قيد تسوية عكسي (Reversal Journal)
                @elseif($entry->reference_type === 'App\Models\Payment')
                    سند قبض كاشير آلي (Automated Receipt)
                @else
                    سند محاسبي عام (Journal Voucher)
                @endif
            </span>
        </div>
        <div class="text-start">
            <div class="font-monospace fw-bold fs-5 text-dark">{{ $entry->entry_number }}</div>
            <div class="small text-muted">التاريخ: {{ $entry->date ? $entry->date->format('Y-m-d') : '-' }}</div>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="bg-light p-3 rounded-3 mb-4 border">
        <div class="row g-2 small">
            <div class="col-4">
                <span class="text-muted">السنة المالية:</span>
                <span class="fw-semibold text-dark">{{ $entry->fiscalYear ? $entry->fiscalYear->name : '2026' }}</span>
            </div>
            <div class="col-4">
                <span class="text-muted">الفترة المحاسبية:</span>
                <span class="fw-semibold text-dark">{{ $entry->fiscalPeriod ? $entry->fiscalPeriod->name : '-' }}</span>
            </div>
            <div class="col-4">
                <span class="text-muted">حالة الاعتماد:</span>
                <span class="fw-semibold text-success">{{ $entry->status === 'posted' ? 'معتمد ومرحل للأستاذ' : 'معكوس' }}</span>
            </div>
            <div class="col-12 mt-2">
                <span class="text-muted">البيان العام:</span>
                <span class="fw-semibold text-dark">{{ $entry->description }}</span>
            </div>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered table-custom align-middle text-center small mb-0">
            <thead>
                <tr>
                    <th style="width: 40px;">#</th>
                    <th style="width: 110px;">رقم الحساب</th>
                    <th class="text-start">اسم الحساب التحليلي</th>
                    <th>مركز التكلفة</th>
                    <th style="width: 130px;">مدين (د.ع)</th>
                    <th style="width: 130px;">دائن (د.ع)</th>
                    <th class="text-start">البيان الخاص</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td class="font-monospace fw-bold">{{ $line->account ? $line->account->code : '-' }}</td>
                        <td class="text-start fw-semibold">{{ $line->account ? $line->account->name : '-' }}</td>
                        <td>{{ $line->costCenter ? $line->costCenter->name : '-' }}</td>
                        <td class="fw-bold text-success">{{ $line->debit > 0 ? number_format($line->debit, 2) : '-' }}</td>
                        <td class="fw-bold text-danger">{{ $line->credit > 0 ? number_format($line->credit, 2) : '-' }}</td>
                        <td class="text-start text-muted">{{ $line->description }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">المجموع المتوازن:</td>
                    <td class="text-success fs-6">{{ number_format($entry->total_debit, 2) }}</td>
                    <td class="text-danger fs-6">{{ number_format($entry->total_credit, 2) }}</td>
                    <td>د.ع</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Tafqeet Box -->
    <div class="bg-light p-3 rounded-3 mb-5 border text-center">
        <span class="text-muted small d-block">المبلغ الإجمالي كتابةً وتفقيطاً بالدينار العراقي:</span>
        <h6 class="fw-bold text-dark mb-0 mt-1">{{ $tafqeet }}</h6>
    </div>

    <!-- Triple Official Signatures -->
    <div class="row g-4 text-center mt-4">
        <div class="col-4">
            <div class="signature-box">
                <span class="small fw-bold text-muted d-block mb-3">مُنظم السند (المحاسب)</span>
                <span class="small fw-semibold text-dark">{{ $entry->postedBy ? $entry->postedBy->name : 'إدخال آلي' }}</span>
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <span class="small fw-bold text-muted d-block mb-3">التدقيق والرقابة المالية</span>
                <span class="small text-muted">التوقيع والختم</span>
            </div>
        </div>
        <div class="col-4">
            <div class="signature-box">
                <span class="small fw-bold text-muted d-block mb-3">الآمر بالصرف / المدير المالي</span>
                <span class="small text-muted">المصادقة والاعتماد</span>
            </div>
        </div>
    </div>

    <div class="text-center text-muted small mt-5 pt-3 border-top" style="font-size: 11px;">
        تم استخراج هذا السند رسمياً من النظام المحاسبي الموحد العراقي - تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}
    </div>
</div>

</body>
</html>
