<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج التحاليل - {{ $request->visit->patient->user->name }}</title>
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
            padding: 20mm;
        }

        /* علامة مائية في الخلفية باستخدام صورة 1.jpg */
        .page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/1.jpg') }}');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 55% auto;
            opacity: 0.08; /* خفيف وواضح */
            pointer-events: none;
            z-index: 0;
        }

        /* إبقاء بقية المحتوى فوق العلامة المائية */
        .page > * { position: relative; z-index: 1; }

        /* الإطار الزخرفي */
        .decorative-border {
            position: absolute;
            top: 10mm;
            right: 10mm;
            bottom: 10mm;
            left: 10mm;
            border: 2px solid #1e7e8f;
            pointer-events: none;
        }

        /* الزخرفة البنية على يمين الصفحة */
        .right-pattern {
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            width: 58mm;
            background-size: cover;
            background-repeat: no-repeat;
            background-position: right top;
            opacity: 0.28;
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
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L100,0 L100,100 Z" fill="%231e7e8f"/></svg>');
        }

        .corner-pattern.bottom-left {
            bottom: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L0,100 L100,100 Z" fill="%231e7e8f"/></svg>');
        }

        /* الترويسة */
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1e7e8f;
        }

        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 10px;
            position: relative;
            width: 100%;
            min-height: 120px;
        }

        .logo {
            width: 200px;
            height: 120px;
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

        /* صف الشعار وشارة المختبر */
        .logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 10px;
            position: relative;
            width: 100%;
        }

        .lab-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #e8f4f4;
            color: #1e7e8f;
            border: 1px solid #1e7e8f;
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: bold;
            white-space: nowrap;
            position: absolute;
            right: 0;
            top: 8px;
        }

        .lab-badge img {
            width: 22px;
            height: 22px;
        }

        .hospital-name-ar {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            letter-spacing: 1px;
        }

        .hospital-name-en {
            font-size: 18px;
            color: #888;
            font-style: normal;
            margin-bottom: 15px;
        }

        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-top: 15px;
        }

        /* معلومات الوثيقة */
        .document-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            padding: 10px 0;
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
            color: #1e7e8f;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e7e8f;
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

        /* جدول النتائج */
        .results-section {
            margin-bottom: 25px;
        }

        .results-section h3 {
            color: #1e7e8f;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1e7e8f;
        }

        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .results-table thead {
            background: #1e7e8f;
            color: white;
        }

        .results-table th {
            padding: 12px;
            text-align: center;
            font-weight: bold;
            border: 1px solid #dee2e6;
        }

        .results-table td {
            padding: 10px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .results-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .results-table tbody tr:hover {
            background: #e9ecef;
        }

        .status-normal {
            color: #28a745;
            font-weight: bold;
        }

        .status-high {
            color: #dc3545;
            font-weight: bold;
        }

        .status-low {
            color: #ffc107;
            font-weight: bold;
        }

        /* الملاحظات */
        .notes-section {
            background: #fff9e6;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 25px;
        }

        .notes-section h4 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .notes-content {
            color: #333;
            line-height: 1.6;
        }

        /* التذييل */
        .footer {
            position: absolute;
            bottom: 20mm;
            left: 20mm;
            right: 20mm;
            border-top: 2px solid #1e7e8f;
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
            color: #1e7e8f;
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
            background: #1e7e8f;
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
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .page {
                margin: 0;
                padding: 15mm;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4;
                margin: 0;
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
@php
                $isBloodBankRequest = $isBloodBankRequest ?? ($request->type === 'blood_bank' || data_get($request->details, 'blood_bank', false));
                $documentTitle = $isBloodBankRequest ? 'نموذج مصرف الدم' : 'نتائج التحاليل المخبرية';
                $badgeText = $isBloodBankRequest ? 'مصرف الدم' : 'المختبر';
                $badgeIcon = $isBloodBankRequest ? 'blood-bank-icon.svg' : 'lab-icon.svg';
            @endphp

            <div class="header">
            <div class="logo-row">
                <div class="logo">
                    <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo" style="width: 200px; height: 120px;">
                </div>
                <div class="lab-badge">
                    <img src="{{ asset('images/' . $badgeIcon) }}" alt="Lab Icon">
                    <span>{{ $badgeText }}</span>
                </div>
            </div>
            <div class="hospital-name-ar">مستشفى الكفاءات الاهلي</div>
            <div class="hospital-name-en">Al-Kafaat Private Hospital</div>
            <div class="document-title">{{ $documentTitle }}</div>
        </div>

        <!-- معلومات الوثيقة -->
        <div class="document-info">
            <div class="info-item">
                <span class="info-label">التاريخ:</span>
                <span class="info-value">{{ now()->format('Y-m-d') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">العدد:</span>
                <span class="info-value">{{ $request->id }}</span>
            </div>
        </div>

        <!-- معلومات المريض -->
        <div class="patient-info">
            <h3>معلومات المريض</h3>
            <div class="patient-details">
                <div class="detail-item">
                    <span class="detail-label">الاسم:</span>
                    <span class="detail-value">{{ $request->visit->patient->user->name }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">العمر:</span>
                    <span class="detail-value">{{ $request->visit->patient->age }} سنة</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الجنس:</span>
                    <span class="detail-value">{{ $request->visit->patient->gender == 'male' ? 'ذكر' : 'أنثى' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">رقم الهاتف:</span>
                    <span class="detail-value">{{ $request->visit->patient->phone }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">الطبيب المعالج:</span>
                    <span class="detail-value">د. {{ $request->visit->doctor?->user?->name ?? 'غير محدد' }}</span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">تاريخ الزيارة:</span>
                    <span class="detail-value">{{ $request->visit->visit_date->format('Y-m-d') }}</span>
                </div>
            </div>
        </div>

        @if($isBloodBankRequest)
            <div class="results-section">
                <h3>بيانات طلب مصرف الدم</h3>
                @if($bloodBankRequest)
                    <table class="results-table">
                        <tbody>
                            <tr><td><strong>رقم الطلب</strong></td><td>{{ $request->id }}</td></tr>
                            <tr><td><strong>حالة الطلب</strong></td><td>{{ $bloodBankRequest->status }}</td></tr>
                            <tr><td><strong>الغرفة</strong></td><td>{{ $bloodBankRequest->room_no ?? '-' }}</td></tr>
                            <tr><td><strong>فصيلة المتبرع</strong></td><td>{{ $bloodBankRequest->donor_group ?? '-' }}</td></tr>
                            <tr><td><strong>فصيلة المريض</strong></td><td>{{ $bloodBankRequest->patient_group ?? '-' }}</td></tr>
                            <tr><td><strong>وزن المتبرع</strong></td><td>{{ $bloodBankRequest->donor_weight ?? '-' }}</td></tr>
                            <tr><td><strong>وزن المريض</strong></td><td>{{ $bloodBankRequest->recipient_weight ?? '-' }}</td></tr>
                            <tr><td><strong>درجة الحرارة</strong></td><td>{{ $bloodBankRequest->at_room_temp ?? '-' }}</td></tr>
                            <tr><td><strong>بند</strong></td><td>{{ $bloodBankRequest->bovine_albumin ?? '-' }}</td></tr>
                            <tr><td><strong>مضاد الغلوبولين البشري</strong></td><td>{{ $bloodBankRequest->anti_human_globulin ?? '-' }}</td></tr>
                            <tr><td><strong>توافقية</strong></td><td>{{ $bloodBankRequest->compatibility ?? '-' }}</td></tr>
                            <tr><td><strong>رقم القارورة</strong></td><td>{{ $bloodBankRequest->bottle_no ?? '-' }}</td></tr>
                            <tr><td><strong>تاريخ العملية</strong></td><td>{{ $bloodBankRequest->operative_date?->format('Y-m-d') ?? '-' }}</td></tr>
                            <tr><td><strong>تاريخ الانتهاء</strong></td><td>{{ $bloodBankRequest->exp_date?->format('Y-m-d') ?? '-' }}</td></tr>
                            <tr><td><strong>الطبيب المسؤول</strong></td><td>{{ $bloodBankRequest->doctor_in_charge ?? '-' }}</td></tr>
                            <tr><td><strong>المبلغ الإجمالي</strong></td><td>{{ $bloodBankRequest->total_amount ?? 0 }}</td></tr>
                        </tbody>
                    </table>
                    @if($bloodBankRequest->notes)
                        <div class="notes-section">
                            <h4>📝 ملاحظات مصرف الدم:</h4>
                            <div class="notes-content">{{ $bloodBankRequest->notes }}</div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-warning">لم يتم العثور على بيانات مصرف الدم المتخصصة لهذا الطلب.</div>
                @endif
            </div>
        @else
            <!-- نتائج التحاليل -->
            @php
                $labResults = \App\Models\LabResult::where('request_id', $request->id)->get();
            @endphp

            @if($labResults->count() > 0)
            <div class="results-section">
                <h3>نتائج الفحوصات المختبرية</h3>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>اسم الفحص</th>
                            <th>النتيجة</th>
                            <th>الوحدة</th>
                            <th>المدى الطبيعي</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($labResults as $index => $result)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $result->test_name }}</strong></td>
                            <td>{{ $result->value }}</td>
                            <td>{{ $result->unit }}</td>
                            <td>{{ $result->reference_range }}</td>
                            <td>
                                <span class="status-{{ $result->status }}">
                                    {{ $result->status == 'normal' ? '✓ طبيعي' : ($result->status == 'high' ? '↑ مرتفع' : '↓ منخفض') }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        @endif

        <!-- الملاحظات -->
        @php
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            $notes = is_array($resultData) ? ($resultData['notes'] ?? '') : '';
        @endphp

        @if($notes)
        <div class="notes-section">
            <h4>📝 ملاحظات إضافية:</h4>
            <div class="notes-content">{{ $notes }}</div>
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
                    {{ $request->id }}-7178
                </div>
            </div>
        </div>
    </div>

    <script>
        function generatePDF() {
            // حفظ كـ PDF باستخدام وظيفة الطباعة في المتصفح
            window.print();
        }
    </script>
</body>
</html>
