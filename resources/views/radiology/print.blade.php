<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج الأشعة - {{ $radiology->patient->user->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', 'Tahoma', sans-serif;
            direction: rtl;
            background: #fff;
            color: #333;
            padding: 20px;
        }

        .page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            position: relative;
            padding: 20mm 20mm 35mm 20mm; /* bottom padding reserves space for footer */
        }

        /* علامة مائية في الخلفية */
        .page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/1.jpg') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 55% auto;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }

        .page > * { position: relative; z-index: 1; }

        /* الإطار الزخرفي */
        .decorative-border {
            position: absolute;
            top: 10mm;
            right: 10mm;
            bottom: 10mm;
            left: 10mm;
            border: 2px solid #17a2b8;
            pointer-events: none;
        }

        .corner-pattern {
            position: absolute;
            width: 80px;
            height: 80px;
            opacity: 0.1;
        }

        .corner-pattern.top-right {
            top: 0;
            right: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L100,0 L100,100 Z" fill="%2317a2b8"/></svg>');
        }

        .corner-pattern.bottom-left {
            bottom: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L0,100 L100,100 Z" fill="%2317a2b8"/></svg>');
        }

        /* الترويسة */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #17a2b8;
        }

        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 6px;
            position: relative;
            width: 100%;
            min-height: 80px;
        }

        .logo {
            width: 140px;
            height: 80px;
            margin: 0;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: 0;
        }

        .logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .radiology-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #e8f7f9;
            color: #17a2b8;
            border: 1px solid #17a2b8;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            white-space: nowrap;
            position: absolute;
            right: 0;
            top: 8px;
        }

        .hospital-name-ar {
            font-size: 22px;
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .hospital-name-en {
            font-size: 14px;
            color: #888;
            font-style: normal;
            margin-bottom: 8px;
        }

        .document-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-top: 8px;
        }

        /* معلومات الوثيقة */
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 6px 0;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        /* معلومات المريض */
        .patient-info {
            background: rgba(248, 249, 250, 0.55);
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .patient-info h3 {
            color: #17a2b8;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #17a2b8;
        }

        .patient-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            display: flex;
            gap: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: #555;
            min-width: 100px;
        }

        .detail-value {
            color: #333;
        }

        /* معلومات الأشعة */
        .radiology-type {
            background: #e8f7f9;
            border: 2px solid #17a2b8;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: center;
        }

        .radiology-type h3 {
            color: #17a2b8;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .radiology-type p {
            color: #666;
            font-size: 14px;
        }

        /* نتائج الأشعة */
        .results-section {
            margin-bottom: 25px;
        }

        .results-section h3 {
            color: #17a2b8;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #17a2b8;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #17a2b8;
            margin-bottom: 20px;
            background: white;
        }

        .results-table tr {
            border-bottom: 1px solid #dee2e6;
        }

        .results-table tr:last-child {
            border-bottom: none;
        }

        .results-table td {
            padding: 12px 15px;
            vertical-align: top;
        }

        .results-table td:first-child {
            background: #e8f7f9;
            color: #17a2b8;
            font-weight: bold;
            width: 200px;
            border-left: 3px solid #17a2b8;
        }

        .results-table td:last-child {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .result-block {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .result-block h4 {
            color: #17a2b8;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .result-content {
            color: #333;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        /* الصور */
        .images-section {
            margin-bottom: 25px;
        }

        .images-grid {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 20px;
            margin-top: 15px;
        }

        .image-item {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .image-item img {
            width: 100%;
            height: auto;
            max-height: 500px;
            object-fit: contain;
        }

        /* التذييل */
        .footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            border-top: 2px solid #17a2b8;
            padding-top: 15px;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            color: #666;
        }

        .contact-info {
            display: flex;
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .barcode {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            color: #17a2b8;
        }

        .signature-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            margin-bottom: 15px; /* space before footer */
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .signature-box {
            text-align: center;
            min-width: 200px;
        }

        .signature-line {
            border-top: 2px solid #333;
            margin-bottom: 8px;
            padding-top: 35px;
        }

        .signature-label {
            font-weight: bold;
            color: #555;
        }

        /* أزرار الطباعة */
        .print-buttons {
            position: fixed;
            top: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
            z-index: 1000;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: #17a2b8;
            color: white;
        }

        .btn-pdf {
            background: #dc3545;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
        }

        @media print {
            .print-buttons {
                display: none !important;
            }

            body {
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                margin: 0;
                padding: 20mm 20mm 40mm 20mm; /* keep reserved footer space in print */
                max-width: 100%;
                min-height: 100vh;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-after: always;
            }

            .page:last-child {
                page-break-after: auto;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }

            .decorative-border {
                top: 10mm;
                right: 10mm;
                bottom: 10mm;
                left: 10mm;
            }

            .footer {
                position: absolute;
                bottom: 15mm; /* slightly higher to avoid collision */
                left: 20mm;
                right: 20mm;
            }

            .images-section {
                page-break-before: auto;
            }

            .image-item {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <!-- أزرار الطباعة -->
    <div class="print-buttons">
        <button class="btn btn-print" onclick="window.print()">
            🖨️ طباعة
        </button>
        <button class="btn btn-pdf" onclick="generatePDF()">
            📄 حفظ PDF
        </button>
        <button class="btn btn-back" onclick="window.history.back()">
            ← رجوع
        </button>
    </div>

    <div class="page">
        <!-- الإطار الزخرفي -->
        <div class="decorative-border">
            <div class="corner-pattern top-right"></div>
            <div class="corner-pattern bottom-left"></div>
        </div>

        <!-- الترويسة -->
        <div class="header">
            <div class="logo-row">
                <div class="logo">
                    <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo">
                </div>
                <div class="radiology-badge">
                    ☢️ قسم الأشعة
                </div>
            </div>
            <div class="hospital-name-ar">مستشفى الكفاءات الاهلي</div>
            <div class="hospital-name-en">Al-Kafaat Private Hospital</div>
            <div class="document-title">تقرير الأشعة التشخيصية</div>
        </div>

        <!-- معلومات الوثيقة -->
        <div class="document-info">
            <div class="info-item">
                <span class="info-label">التاريخ:</span>
                <span class="info-value">{{ $radiology->result ? $radiology->result->reported_at->format('Y-m-d') : now()->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">رقم الطلب:</span>
                <span class="info-value">{{ $radiology->id }}</span>
            </div>
        </div>

        <!-- معلومات المريض -->
        <div class="patient-info">
            <h3>معلومات المريض</h3>
            <div class="patient-details">
                <div class="detail-item">
                    <span class="detail-label">الاسم:</span>
                    <span class="detail-value">{{ $radiology->patient->user->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">العمر:</span>
                    <span class="detail-value">{{ $radiology->patient->age }} سنة</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الجنس:</span>
                    <span class="detail-value">{{ $radiology->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">رقم الهاتف:</span>
                    <span class="detail-value">{{ $radiology->patient->phone }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الطبيب المعالج:</span>
                    <span class="detail-value">د. {{ $radiology->doctor?->user?->name ?? 'غير محدد' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">تاريخ الطلب:</span>
                    <span class="detail-value">{{ $radiology->requested_date->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>



        <!-- نتائج الأشعة في جدول واحد -->
        <div class="results-section">
            <h3>النتائج والتقرير الطبي</h3>
            <table class="results-table">
                @if($radiology->clinical_indication)
                <tr>
                    <td>الدواعي السريرية<br>(Clinical Indication)</td>
                    <td>{{ $radiology->clinical_indication }}</td>
                </tr>
                @endif

                @if($radiology->radiologyType)
                <tr>
                    <td>طلب أشعة<br>(Radiology Type)</td>
                    <td>{{ $radiology->radiologyType->name }}</td>
                </tr>
                @endif

                @if($radiology->result)
                    @if($radiology->result->findings)
                    <tr>
                        <td>النتائج<br>(Findings)</td>
                        <td>{{ $radiology->result->findings }}</td>
                    </tr>
                    @endif

                    @if($radiology->result->impression)
                    <tr>
                        <td>الانطباع<br>(Impression)</td>
                        <td>{{ $radiology->result->impression }}</td>
                    </tr>
                    @endif

                    @if($radiology->result->recommendations)
                    <tr>
                        <td>التوصيات<br>(Recommendations)</td>
                        <td>{{ $radiology->result->recommendations }}</td>
                    </tr>
                    @endif
                @endif
            </table>
        </div>

        @if(!($radiology->result && $radiology->result->images && count($radiology->result->images) > 0))
        <!-- التوقيعات -->
        <div class="signature-section">
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        @if($radiology->result && $radiology->result->radiologist)
                        د. {{ $radiology->result->radiologist->name }}<br>
                        @endif
                        أخصائي الأشعة
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">التاريخ والوقت</div>
                </div>
            </div>
        </div>
        @endif

        <!-- التذييل -->
        <div class="footer">
            <div class="footer-content">
                <div class="contact-info">
                    <div class="contact-item">
                        📞 +964 (0) 778 050 7060
                    </div>
                    <div class="contact-item">
                        📧 info@alkafaathospital.com
                    </div>
                    <div class="contact-item">
                        📍 بغداد - الحارثية - شارع الكندي
                    </div>
                </div>
                <div class="barcode">
                    RAD-{{ $radiology->id }}
                </div>
            </div>
        </div>
    </div>

    <!-- صفحة ثانية للصور إذا وجدت -->
    @if($radiology->result && $radiology->result->images && count($radiology->result->images) > 0)
    <div class="page">
        <div class="decorative-border">
            <div class="corner-pattern top-right"></div>
            <div class="corner-pattern bottom-left"></div>
        </div>

        <div class="header">
            <div class="hospital-name-ar">صور الأشعة التشخيصية</div>
            <div class="hospital-name-en">Diagnostic Images</div>
        </div>

        <div class="images-section">
            <h3 style="color: #17a2b8; font-size: 18px; margin-bottom: 20px;">الصور المرفقة</h3>
            <div class="images-grid">
                @foreach($radiology->result->images as $index => $image)
                <div class="image-item">
                    @if(Str::endsWith($image, '.pdf'))
                        <div style="padding: 50px; text-align: center; background: #f8f9fa;">
                            <p>📄 ملف PDF</p>
                            <small>{{ basename($image) }}</small>
                        </div>
                    @else
                        <img src="{{ asset('storage/' . $image) }}" alt="صورة {{ $index + 1 }}">
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <!-- التوقيعات -->
        <div class="signature-section">
            <div class="signatures">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">
                        @if($radiology->result && $radiology->result->radiologist)
                        د. {{ $radiology->result->radiologist->name }}<br>
                        @endif
                        أخصائي الأشعة
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">التاريخ والوقت</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-content">
                <div class="contact-info">
                    <div class="contact-item">
                        📞 +964 (0) 778 050 7060
                    </div>
                    <div class="contact-item">
                        📧 info@alkafaathospital.com
                    </div>
                    <div class="contact-item">
                        📍 بغداد - الحارثية - شارع الكندي
                    </div>
                </div>
                <div class="barcode">
                    RAD-{{ $radiology->id }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <script>
        function generatePDF() {
            window.print();
        }
    </script>
</body>
</html>
