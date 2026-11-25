<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند تحويل وصرف مخزني رسمي #{{ $stockTransferRequest->id }} - {{ $stockTransferRequest->toLocation?->name ?? 'القسم المستلم' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #0f172a;
            font-size: 13px;
            line-height: 1.4;
        }
        .print-container {
            max-width: 960px;
            margin: 20px auto;
            background: #ffffff;
            padding: 32px 36px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .header-box {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 18px;
        }
        .doc-badge {
            background-color: #0f172a;
            color: #ffffff;
            padding: 6px 18px;
            border-radius: 6px;
            display: inline-block;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .meta-table th {
            background-color: #f8fafc;
            color: #475569;
            width: 18%;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            vertical-align: middle;
        }
        .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            vertical-align: middle;
            color: #0f172a;
        }
        .items-table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 14px;
        }
        .items-table thead th {
            background-color: #0f172a;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            border: 1px solid #0f172a;
            padding: 8px 6px;
            font-size: 12.5px;
        }
        .items-table tbody td {
            border: 1px solid #cbd5e1;
            padding: 7px 8px;
            vertical-align: middle;
        }
        .items-table tfoot td {
            background-color: #f1f5f9;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }
        .signature-box {
            border: 1px dashed #64748b;
            border-radius: 8px;
            padding: 12px;
            background-color: #f8fafc;
            min-height: 125px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .signature-title {
            font-weight: 700;
            font-size: 12.5px;
            color: #1e293b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .status-badge {
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
        }
        .barcode-font {
            font-family: 'Courier New', Courier, monospace;
            letter-spacing: 2px;
            font-weight: bold;
        }
        @media print {
            body {
                background: #ffffff !important;
                margin: 0 !important;
                padding: 0 !important;
                font-size: 12px;
                color: #000000 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                max-width: 100% !important;
            }
            .items-table thead th {
                background-color: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .meta-table th, .items-table tfoot td {
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .doc-badge {
                background-color: #000000 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .signature-box {
                background-color: #ffffff !important;
                border: 1px dashed #000000 !important;
            }
            @page {
                size: A4 portrait;
                margin: 12mm 15mm;
            }
        }
    </style>
</head>
<body>

<!-- شريط التحكم بالأعلى (مخفي أثناء الطباعة) -->
<div class="container my-3 no-print" style="max-width: 960px;">
    <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded-4 shadow-sm border">
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('stock-transfers.requests.show', $stockTransferRequest) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                <i class="fas fa-arrow-right me-1"></i> العودة لتفاصيل الشحنة
            </a>
            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                سند شحنة رقم #{{ $stockTransferRequest->id }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
                <i class="fas fa-print me-1.5"></i> طباعة المستند (A4)
            </button>
        </div>
    </div>
</div>

<!-- المستند الرسمي القابل للطباعة A4 -->
<div class="print-container">
    @php
        // الشعار المعتمد لمستشفى الكفاءات الأهلي مع التضمين الآمن Base64 لضمان الظهور الفوري في الطباعة الورقية
        $logoFile = public_path('images/1.jpg');
        if (!file_exists($logoFile) || filesize($logoFile) === 0) {
            $logoFile = public_path('images/logo.png');
        }

        if (file_exists($logoFile) && filesize($logoFile) > 0) {
            $ext = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
            $mime = match($ext) {
                'png' => 'image/png',
                'jpg', 'jpeg' => 'image/jpeg',
                'svg' => 'image/svg+xml',
                default => 'image/jpeg',
            };
            $hospitalLogoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoFile));
        } else {
            $hospitalLogoSrc = asset('images/1.jpg');
        }

        $hospitalNameAr = $hospital?->name ?? 'مستشفى الكفاءات الأهلي';
        $hospitalNameEn = 'Al-Kafa\'at Private Hospital';
    @endphp

    <!-- الترويسة الرسمية مع شعار مستشفى الكفاءات الأهلي المعتمد -->
    <div class="header-box">
        <div class="text-center mb-1">
            <span class="small text-muted fw-bold">بِسْمِ اللَّـهِ الرَّحْمَـٰنِ الرَّحِيمِ</span>
        </div>
        <div class="d-flex justify-content-between align-items-center">
            <!-- الجانب الأيمن: اسم المستشفى بالعربي والقسم -->
            <div class="text-start" style="width: 32%;">
                <h5 class="fw-bold mb-1 text-primary">{{ $hospitalNameAr }}</h5>
                <div class="small fw-semibold text-dark">قسم إدارة المخازن والإمداد الطبي المركزي</div>
                <div class="small text-muted">إدارة سلاسل التوريد والعهد المخزنية</div>
            </div>

            <!-- الوسط: شعار مستشفى الكفاءات وعنوان السند -->
            <div class="text-center flex-grow-1" style="width: 36%;">
                <div class="mb-1 text-center">
                    <img src="{!! $hospitalLogoSrc !!}" 
                         alt="شعار مستشفى الكفاءات الأهلي" 
                         style="max-height: 75px; max-width: 140px; object-fit: contain;" 
                         onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';">
                </div>
                <div class="doc-badge mb-1">
                    سند تحويل وصرف واستلام مخزني رسمي
                </div>
                <div class="small fw-bold text-secondary font-monospace" style="font-size: 11px;">OFFICIAL STOCK DISPATCH & TRANSFER VOUCHER</div>
            </div>

            <!-- الجانب الأيسر: الإنجليزية والباركود -->
            <div class="text-end" style="width: 32%;">
                <h6 class="fw-bold mb-1 text-dark font-monospace">{{ $hospitalNameEn }}</h6>
                <div class="small text-muted font-monospace">Central Medical Warehouse Dept.</div>
                <div class="small text-muted mt-1">رقم السند: <span class="fw-bold fs-6 font-monospace text-primary">#STR-{{ str_pad($stockTransferRequest->id, 6, '0', STR_PAD_LEFT) }}</span></div>
                <div class="small text-muted">تاريخ الطباعة: <span class="font-monospace text-dark">{{ now()->format('Y-m-d H:i') }}</span></div>
            </div>
        </div>
    </div>

    <!-- شبكة بيانات الشحنة والأطراف المسؤولة -->
    <table class="table meta-table mb-3">
        <tbody>
            <tr>
                <th>المخزن المصدر (المُجهّز):</th>
                <td class="fw-bold text-dark">{{ $stockTransferRequest->fromLocation?->name ?? 'المخزن الرئيسي' }}</td>
                <th>القسم / الردهة المستلمة:</th>
                <td class="fw-bold text-primary">{{ $stockTransferRequest->toLocation?->name ?? 'غير محدد' }}</td>
            </tr>
            <tr>
                <th>منظم الطلب (المسؤول):</th>
                <td>{{ $stockTransferRequest->requestedBy?->name ?? 'غير معروف' }}</td>
                <th>تاريخ وتوقيت الطلب:</th>
                <td class="font-monospace">{{ $stockTransferRequest->created_at ? $stockTransferRequest->created_at->format('Y-m-d H:i') : '—' }}</td>
            </tr>
            <tr>
                <th>أمين المخزن المصرف:</th>
                <td>{{ $stockTransferRequest->approvedBy?->name ?? 'بانتظار الصرف' }}</td>
                <th>تاريخ وتوقيت الصرف:</th>
                <td class="font-monospace">{{ $stockTransferRequest->approved_at ? $stockTransferRequest->approved_at->format('Y-m-d H:i') : '—' }}</td>
            </tr>
            <tr>
                <th>مستلم الشحنة بالقسم:</th>
                <td class="fw-bold text-success">{{ $stockTransferRequest->receivedBy?->name ?? 'بانتظار الاستلام الفعلي' }}</td>
                <th>تاريخ وتوقيت الاستلام:</th>
                <td class="font-monospace">{{ $stockTransferRequest->received_at ? $stockTransferRequest->received_at->format('Y-m-d H:i') : '—' }}</td>
            </tr>
            <tr>
                <th>حالة الشحنة الحالية:</th>
                <td colspan="3">
                    @if($stockTransferRequest->status === 'pending')
                        <span class="status-badge bg-warning text-dark border border-warning">
                            <i class="fas fa-hourglass-half me-1"></i> قيد المراجعة والاعتماد من أمين المخزن الرئيسي
                        </span>
                    @elseif($stockTransferRequest->status === 'approved')
                        <span class="status-badge bg-primary text-white">
                            <i class="fas fa-truck-loading me-1"></i> تم الصرف والترحيل — بانتظار تأكيد استلام القسم
                        </span>
                    @elseif($stockTransferRequest->status === 'completed')
                        <span class="status-badge bg-success text-white">
                            <i class="fas fa-check-double me-1"></i> مستلم ومودع بالكامل في عهدة القسم
                        </span>
                    @else
                        <span class="status-badge bg-danger text-white">
                            <i class="fas fa-times-circle me-1"></i> مرفوض / ملغي
                        </span>
                    @endif
                    @php
                        $hasModified = collect($stockTransferRequest->items)->contains(function($it) {
                            return !empty($it['modified_by_admin']) || (isset($it['original_qty']) && $it['original_qty'] != ($it['qty'] ?? 0));
                        });
                    @endphp
                    @if($hasModified)
                        <span class="status-badge bg-warning text-dark border border-warning ms-2">
                            <i class="fas fa-pen-fancy me-1"></i> تم تعديل بعض الكميات بواسطة أمين المخزن
                        </span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <!-- جدول المواد في هذه الشحنة -->
    <div class="mb-2 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark">
            <i class="fas fa-boxes-stacked me-1.5 text-primary"></i> كشف المواد والمستلزمات في الشحنة ({{ count($stockTransferRequest->items) }} صنف)
        </h6>
        <span class="small text-muted font-monospace">Items & Quantities Breakdown</span>
    </div>

    <table class="items-table mb-3">
        <thead>
            <tr>
                <th style="width: 35px;">ت</th>
                <th style="width: 100px;">كود المادة</th>
                <th>اسم المادة أو المستلزم الطبي والمواصفة</th>
                <th style="width: 85px;">الوحدة</th>
                <th style="width: 90px;">الكمية المطلوبة</th>
                <th style="width: 95px;">الكمية المصروفة</th>
                <th style="width: 95px;">الكمية المستلمة</th>
                <th style="width: 190px;">حالة الصرف وملاحظات المخزن</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalRequested = 0;
                $totalDispensed = 0;
                $totalReceived = 0;
                $isCompleted = $stockTransferRequest->status === 'completed';
                $isApproved = in_array($stockTransferRequest->status, ['approved', 'completed']);
            @endphp
            @foreach($stockTransferRequest->items as $index => $item)
                @php
                    $pid = $item['product_id'];
                    $product = $products[$pid] ?? null;
                    $reqQty = (int) ($item['original_qty'] ?? $item['qty']);
                    $dispQty = $isApproved ? (int) $item['qty'] : 0;
                    $recQty = $isCompleted ? (int) $item['qty'] : 0;

                    $totalRequested += $reqQty;
                    $totalDispensed += $dispQty;
                    $totalReceived += $recQty;

                    $isItemModified = !empty($item['modified_by_admin']) || ($reqQty != (int)$item['qty']);
                @endphp
                <tr>
                    <td class="text-center font-monospace fw-bold">{{ $index + 1 }}</td>
                    <td class="text-center font-monospace small text-muted">{{ $product?->code ?? 'PRD-'.$pid }}</td>
                    <td>
                        <div class="fw-bold text-dark">{{ $product?->name ?? 'مادة غير محددة' }}</div>
                        @if(!empty($item['reason']))
                            <div class="small text-secondary"><i class="fas fa-info-circle me-1"></i> الغرض/السبب: {{ $item['reason'] }}</div>
                        @endif
                        @if(!empty($item['notes']))
                            <div class="small text-muted"><i class="far fa-comment-dots me-1"></i> ملاحظة الطلب: {{ $item['notes'] }}</div>
                        @endif
                    </td>
                    <td class="text-center small">{{ $product?->unit ?? 'قطعة' }}</td>
                    <td class="text-center font-monospace fw-bold {{ $isItemModified && $isApproved ? 'text-decoration-line-through text-muted' : 'text-dark' }}">
                        {{ $reqQty }}
                    </td>
                    <td class="text-center font-monospace fw-bold {{ $isApproved ? 'text-primary' : 'text-muted' }}">
                        {{ $isApproved ? $dispQty : '—' }}
                    </td>
                    <td class="text-center font-monospace fw-bold {{ $isCompleted ? 'text-success' : 'text-muted' }}">
                        {{ $isCompleted ? $recQty : '—' }}
                    </td>
                    <td class="small">
                        @if($isApproved)
                            @if($isItemModified)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle d-inline-block mb-1">
                                    تعديل: عجز/توفر جزئي
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle d-inline-block mb-1">
                                    مطابق بالكامل
                                </span>
                            @endif
                        @else
                            <span class="text-muted">قيد المراجعة</span>
                        @endif
                        @if(!empty($item['admin_notes']))
                            <div class="text-dark small mt-0.5">{{ $item['admin_notes'] }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end fw-bold">المجموع الكلي: ({{ count($stockTransferRequest->items) }} صنف)</td>
                <td class="text-center font-monospace fw-bold">{{ $totalRequested }}</td>
                <td class="text-center font-monospace fw-bold text-primary">{{ $isApproved ? $totalDispensed : '—' }}</td>
                <td class="text-center font-monospace fw-bold text-success">{{ $isCompleted ? $totalReceived : '—' }}</td>
                <td class="small text-muted text-center">إجمالي الكميات والوحدات</td>
            </tr>
        </tfoot>
    </table>

    <!-- ملاحظات الاستلام الرسمية للقسم -->
    @if($stockTransferRequest->received_notes)
        <div class="p-2 mb-3 bg-light border rounded-2">
            <span class="fw-bold text-dark"><i class="fas fa-clipboard-check text-success me-1"></i> ملاحظات كادر القسم عند الاستلام:</span>
            <span class="text-muted font-monospace ms-1">"{{ $stockTransferRequest->received_notes }}"</span>
        </div>
    @endif

    <!-- التواقيع الرسمية (منظم الطلب وأمين المخزن فقط) -->
    <div class="row g-4 mt-2">
        <!-- 1. منظم الطلب -->
        <div class="col-6">
            <div class="signature-box" style="min-height: 140px; padding: 15px;">
                <div>
                    <div class="signature-title text-center fs-6">
                        <i class="fas fa-user-edit me-1.5 text-primary"></i> منظم الطلب (مسؤول عهدة القسم المستلم)
                    </div>
                    <div class="small mt-2">
                        <div class="mb-1"><strong>الاسم الرباعي:</strong> {{ $stockTransferRequest->requestedBy?->name ?? '..................................................' }}</div>
                        <div><strong>القسم / الردهة:</strong> {{ $stockTransferRequest->toLocation?->name ?? 'القسم المستلم' }}</div>
                    </div>
                </div>
                <div class="small pt-3 border-top mt-3 d-flex justify-content-between align-items-center">
                    <div><strong>التاريخ:</strong> {{ $stockTransferRequest->created_at ? $stockTransferRequest->created_at->format('Y-m-d') : '.... / .... / 202..' }}</div>
                    <div><strong>التوقيع الرسمي:</strong> .....................................</div>
                </div>
            </div>
        </div>

        <!-- 2. أمين المخزن المصرف -->
        <div class="col-6">
            <div class="signature-box" style="min-height: 140px; padding: 15px;">
                <div>
                    <div class="signature-title text-center fs-6">
                        <i class="fas fa-dolly me-1.5 text-success"></i> أمين المخزن المصرف (مسؤول التجهيز والصرف)
                    </div>
                    <div class="small mt-2">
                        <div class="mb-1"><strong>الاسم الرباعي:</strong> {{ $stockTransferRequest->approvedBy?->name ?? '..................................................' }}</div>
                        <div><strong>المخزن المجهز:</strong> {{ $stockTransferRequest->fromLocation?->name ?? 'المخزن الرئيسي' }}</div>
                    </div>
                </div>
                <div class="small pt-3 border-top mt-3 d-flex justify-content-between align-items-center">
                    <div><strong>التاريخ:</strong> {{ $stockTransferRequest->approved_at ? $stockTransferRequest->approved_at->format('Y-m-d') : '.... / .... / 202..' }}</div>
                    <div><strong>التوقيع الرسمي:</strong> .....................................</div>
                </div>
            </div>
        </div>
    </div>

    <!-- تذييل المستند الأمني -->
    <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-3 text-muted" style="font-size: 11px;">
        <div>
            <i class="fas fa-shield-alt text-primary me-1"></i> وثيقة رسمية معتمدة ومسجلة في منظومة الإمداد المخزني التابعة للمستشفى
        </div>
        <div class="font-monospace">
            ID: STR-{{ $stockTransferRequest->id }} | PRINTED_BY: {{ auth()->user()?->name ?? 'SYSTEM' }} | {{ now()->format('Y-m-d H:i:s') }}
        </div>
    </div>
</div>

</body>
</html>