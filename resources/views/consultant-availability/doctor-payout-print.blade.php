<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سند صرف نقدي - {{ $transaction->voucher_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    @php
        $hospitalLogoFile = public_path('images/1.jpg');
        $hospitalLogoSrc = file_exists($hospitalLogoFile) && filesize($hospitalLogoFile) > 0
            ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($hospitalLogoFile))
            : asset('images/1.jpg');
        
        $isRefund = ($transaction->category === 'doctor_payout_reversal' || $transaction->voucher_type === 'inflow');
        $docUser = $doctor->user;
    @endphp
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f8f9fa;
            color: #212529;
            margin: 0;
            padding: 20px;
            font-size: 13px;
            line-height: 1.5;
        }

        .screen-actions {
            max-width: 850px;
            margin: 0 auto 15px auto;
        }

        .voucher-sheet {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            position: relative;
            border: 1px solid #e5e7eb;
        }

        /* Center Watermark */
        .voucher-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 380px;
            height: 380px;
            background-image: url('{{ $hospitalLogoSrc }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .voucher-content {
            position: relative;
            z-index: 1;
        }

        .voucher-header {
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .voucher-title-pill {
            display: inline-block;
            background: {{ $isRefund ? '#dc3545' : '#198754' }};
            color: #ffffff;
            font-weight: 800;
            font-size: 15px;
            padding: 6px 24px;
            border-radius: 50px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            letter-spacing: 0.5px;
        }

        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .amount-display-box {
            background: linear-gradient(135deg, {{ $isRefund ? '#fff5f5, #ffe3e3' : '#f0fdf4, #dcfce7' }});
            border: 2px dashed {{ $isRefund ? '#dc3545' : '#198754' }};
            border-radius: 10px;
            padding: 16px 20px;
            margin: 20px 0;
            text-align: center;
        }

        .amount-number {
            font-size: 26px;
            font-weight: 900;
            font-family: monospace, sans-serif;
            color: {{ $isRefund ? '#b02a37' : '#146c43' }};
        }

        .amount-words {
            font-size: 14px;
            font-weight: 700;
            color: #334155;
            margin-top: 5px;
        }

        .signature-section {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
        }

        .signature-box {
            text-align: center;
            padding: 10px;
        }

        .signature-line {
            height: 50px;
            border-bottom: 1px solid #64748b;
            margin-bottom: 8px;
        }

        /* Thermal receipt view mode */
        body.thermal-mode {
            padding: 0;
            background-color: #fff;
        }
        body.thermal-mode .screen-actions {
            display: none;
        }
        body.thermal-mode .voucher-sheet {
            max-width: 78mm;
            padding: 10px 8px;
            box-shadow: none;
            border: none;
            border-radius: 0;
            font-size: 11px;
        }
        body.thermal-mode .voucher-watermark {
            display: none;
        }
        body.thermal-mode .amount-number {
            font-size: 18px;
        }
        body.thermal-mode .voucher-title-pill {
            font-size: 12px;
            padding: 4px 12px;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                font-size: 12px;
            }
            .screen-actions {
                display: none !important;
            }
            .voucher-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 20px 25px !important;
                max-width: 100% !important;
            }
            @page {
                size: A4;
                margin: 10mm 12mm;
            }
        }
    </style>
</head>
<body>

    {{-- أزرار التحكم بالشاشة --}}
    <div class="screen-actions d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="{{ route('consultant-availability.doctor-account', $doctor) }}" class="btn btn-outline-secondary btn-sm rounded-pill px-3 fw-bold">
            <i class="fas fa-arrow-right me-1"></i> العودة لكشف حساب الطبيب
        </a>
        <div class="d-flex gap-2">
            <button onclick="toggleThermalMode()" class="btn btn-outline-dark btn-sm rounded-pill px-3 fw-bold" id="thermalToggleBtn">
                <i class="fas fa-receipt me-1"></i> وصل حراري (78mm)
            </button>
            <button onclick="window.print()" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة السند (A4)
            </button>
        </div>
    </div>

    {{-- ورقة السند الرسمية --}}
    <div class="voucher-sheet">
        <div class="voucher-watermark"></div>
        <div class="voucher-content">

            {{-- الترويسة الرسمية الثلاثية --}}
            <div class="voucher-header">
                <div class="row align-items-center">
                    <div class="col-4 text-end">
                        <h6 class="fw-bold mb-0 text-dark">مستشفى الكفاءات الأهلي</h6>
                        <small class="text-secondary d-block fw-bold" style="font-size: 0.8rem;">قسم الحسابات المالية والتدقيق</small>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">حسابات الأطباء الاستشاريين</small>
                    </div>
                    <div class="col-4 text-center">
                        <img src="{{ $hospitalLogoSrc }}" alt="شعار المستشفى" style="height: 65px; width: auto; object-fit: contain;">
                    </div>
                    <div class="col-4 text-start">
                        <h6 class="fw-bold mb-0 text-dark font-monospace" style="direction: ltr;">Al-Kafa'at Hospital</h6>
                        <small class="text-secondary d-block font-monospace" style="font-size: 0.75rem; direction: ltr;">Finance & Accounting</small>
                        <small class="text-muted d-block font-monospace" style="font-size: 0.75rem; direction: ltr;">Consultant Accounts</small>
                    </div>
                </div>
            </div>

            {{-- شريط عنوان السند --}}
            <div class="text-center my-3">
                <div class="voucher-title-pill">
                    <i class="fas {{ $isRefund ? 'fa-undo-alt' : 'fa-hand-holding-usd' }} me-2"></i>
                    {{ $isRefund ? 'سند استرجاع مالي - حسابات الأطباء (Payout Reversal Voucher)' : 'سند صرف نقدي - مستحقات طبيب استشاري (Doctor Payout Voucher)' }}
                </div>
            </div>

            {{-- بطاقة بيانات السند والطبيب --}}
            <div class="meta-box mb-3">
                <div class="row g-2">
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">رقم السند:</span>
                        <strong class="font-monospace text-dark fs-6">{{ $transaction->voucher_number }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">تاريخ ووقت المعاملة:</span>
                        <strong class="text-dark">{{ $transaction->performed_at ? $transaction->performed_at->format('Y-m-d h:i A') : now()->format('Y-m-d h:i A') }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">المحاسب المسؤول:</span>
                        <strong class="text-dark">{{ $transaction->performer?->name ?? 'محاسب الخزينة' }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">طريقة الصرف:</span>
                        <strong class="text-dark"><i class="fas fa-money-bill-wave text-success me-1"></i> نقداً من صندوق المستشفى</strong>
                    </div>
                </div>
            </div>

            {{-- بيانات الطبيب المستفيد --}}
            <div class="meta-box mb-3 bg-white border-primary-subtle">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <span class="text-muted small d-block">اسم الطبيب المستفيد:</span>
                        <h6 class="fw-bold text-primary mb-0">
                            <i class="fas fa-user-md me-1"></i>
                            د. {{ $docUser?->name ?? '---' }}
                        </h6>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">التخصص الطبي:</span>
                        <strong class="text-dark">{{ $doctor->specialization ?? 'استشاري' }}</strong>
                    </div>
                    <div class="col-6 col-md-3">
                        <span class="text-muted small d-block">معرف الطبيب:</span>
                        <strong class="font-monospace text-secondary">#DOC-{{ $doctor->id }}</strong>
                    </div>
                </div>
            </div>

            {{-- مربع المبلغ المالي والتفقيط --}}
            <div class="amount-display-box">
                <div class="small fw-bold text-secondary mb-1">
                    {{ $isRefund ? 'إجمالي المبلغ المسترجع والمقيد لصالح الطبيب:' : 'إجمالي المبلغ المصروف نقداً للطبيب:' }}
                </div>
                <div class="amount-number">
                    {{ number_format($transaction->amount, 0) }} د.ع
                </div>
                <div class="amount-words">
                    <i class="fas fa-quote-right opacity-25 me-1"></i>
                    {{ $amountWords }}
                    <i class="fas fa-quote-left opacity-25 ms-1"></i>
                </div>
            </div>

            {{-- البيان والملاحظات --}}
            <div class="meta-box mb-4">
                <div class="mb-2">
                    <span class="text-muted small fw-bold d-block"><i class="fas fa-info-circle me-1 text-primary"></i> بيان السند:</span>
                    <div class="fw-bold text-dark ps-3">{{ $transaction->description ?: 'صرف مستحقات أتعاب كشفيات العيادة الاستشارية' }}</div>
                </div>
                @if($transaction->notes)
                    <div class="border-top pt-2 mt-2">
                        <span class="text-muted small fw-bold d-block"><i class="fas fa-sticky-note me-1 text-secondary"></i> تفاصيل وملاحظات إضافية:</span>
                        <div class="text-secondary ps-3 small">{{ $transaction->notes }}</div>
                    </div>
                @endif
            </div>

            {{-- خانات التوقيع والاعتماد الرسمي --}}
            <div class="signature-section">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="signature-box">
                            <span class="small fw-bold text-secondary d-block">توقيع المستلم (الطبيب)</span>
                            <div class="signature-line"></div>
                            <small class="text-muted">الاسم: د. {{ $docUser?->name }}</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="signature-box">
                            <span class="small fw-bold text-secondary d-block">منظم السند (المحاسب)</span>
                            <div class="signature-line"></div>
                            <small class="text-muted">{{ $transaction->performer?->name ?? 'المحاسب المسؤول' }}</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="signature-box">
                            <span class="small fw-bold text-secondary d-block">اعتماد الإدارة المالية / التدقيق</span>
                            <div class="signature-line"></div>
                            <small class="text-muted">مستشفى الكفاءات الأهلي</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- تذييل المستند --}}
            <div class="text-center text-muted small mt-4 pt-2 border-top" style="font-size: 0.72rem;">
                تم استخراج هذا السند المالي آلياً عبر نظام إدارة المستشفى المعتمد بتاريخ {{ now()->format('Y-m-d H:i') }}.
            </div>
        </div>
    </div>

    <script>
        function toggleThermalMode() {
            document.body.classList.toggle('thermal-mode');
            const btn = document.getElementById('thermalToggleBtn');
            if (document.body.classList.contains('thermal-mode')) {
                btn.innerHTML = '<i class="fas fa-desktop me-1"></i> وضع A4 الرسمي';
                btn.classList.replace('btn-outline-dark', 'btn-dark');
            } else {
                btn.innerHTML = '<i class="fas fa-receipt me-1"></i> وصل حراري (78mm)';
                btn.classList.replace('btn-dark', 'btn-outline-dark');
            }
        }
    </script>
</body>
</html>

