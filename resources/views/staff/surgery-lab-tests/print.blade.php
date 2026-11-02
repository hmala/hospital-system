<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        $patientModel = $surgery?->patient ?? $test->surgery?->patient;
        $patientUser = $patientModel?->user;
        $patientName = $patientUser?->name ?? 'Patient';
    @endphp
    <title>Surgery Clinical Laboratory Report - {{ $patientName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&family=Tajawal:wght@500;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 10mm 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            direction: ltr;
            background: #f0f2f5;
            color: #222;
            padding: 20px;
        }

        .report-page {
            width: 210mm;
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 8mm 12mm 10mm 12mm;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1.5px solid #005a9c;
            border-radius: 4px;
        }

        .report-main-content {
            flex: 1 0 auto;
            position: relative;
            z-index: 1;
        }

        .report-bottom-block {
            margin-top: auto;
            position: relative;
            z-index: 1;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Watermark Background */
        .report-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/1.jpg') }}');
            background-repeat: no-repeat;
            background-position: center 50%;
            background-size: 45% auto;
            opacity: 0.045;
            pointer-events: none;
            z-index: 0;
        }

        .report-page > * {
            position: relative;
            z-index: 1;
        }

        :root {
            --letterhead-top: 45mm;
            --letterhead-bottom: 25mm;
        }

        /* Letterhead Mode Styles */
        body.letterhead-mode .report-header,
        body.letterhead-mode .document-title-banner,
        body.letterhead-mode .hospital-contact-line,
        body.letterhead-mode .report-footer-wrapper {
            display: none !important;
        }

        body.letterhead-mode .report-page::before {
            display: none !important;
        }

        body.letterhead-mode .report-page {
            padding-top: var(--letterhead-top, 45mm) !important;
            padding-bottom: var(--letterhead-bottom, 25mm) !important;
            border: none !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        /* Print Toolbar (Hidden during print) */
        .print-toolbar {
            position: fixed;
            top: 15px;
            right: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(15, 23, 42, 0.94);
            padding: 7px 14px;
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
            z-index: 99999;
            direction: rtl;
            font-family: 'Tajawal', 'Cairo', 'Segoe UI', sans-serif;
        }

        .toolbar-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13.5px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.25);
            transition: all 0.2s ease;
        }

        .btn-print { background: #0284c7; color: #fff; }
        .btn-print:hover { background: #0369a1; transform: translateY(-1px); }
        .btn-close-window { background: #475569; color: #fff; }
        .btn-close-window:hover { background: #334155; }

        .mode-toggle-group {
            display: flex;
            background: rgba(255, 255, 255, 0.1);
            padding: 3px;
            border-radius: 6px;
            gap: 4px;
        }

        .mode-btn {
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12.5px;
            font-weight: 700;
            cursor: pointer;
            color: #cbd5e1;
            background: transparent;
            transition: all 0.2s ease;
        }

        .mode-btn:hover { color: #fff; }

        .mode-btn.active {
            background: #0284c7;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        .margin-control-group {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #e2e8f0;
            font-size: 12px;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.08);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .margin-control-group input {
            width: 48px;
            padding: 3px 6px;
            border-radius: 4px;
            border: 1px solid rgba(255,255,255,0.3);
            background: #0f172a;
            color: #38bdf8;
            font-weight: bold;
            text-align: center;
            font-size: 13px;
        }

        /* Header Layout (Matching Official Clinical Standard) */
        .report-header {
            display: flex;
            direction: rtl;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 6px;
            margin-bottom: 8px;
            border-bottom: 2px solid #005a9c;
        }

        .header-hospital-ar {
            text-align: right;
            flex: 1;
            direction: rtl;
        }
        .header-hospital-ar h2 {
            font-size: 16px;
            font-weight: 900;
            color: #005a9c;
            margin: 0 0 1px 0;
            line-height: 1.2;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        .header-hospital-ar h4 {
            font-size: 12px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        .header-hospital-ar p {
            font-size: 9.5px;
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }

        .header-logo {
            text-align: center;
            width: 110px;
            padding: 0 8px;
        }
        .header-logo img, .hospital-logo-img {
            max-width: 90px;
            max-height: 72px;
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
            margin: 0 0 1px 0;
            line-height: 1.2;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .header-hospital-en h4 {
            font-size: 11px;
            font-weight: 800;
            color: #0284c7;
            margin: 0 0 1px 0;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }
        .header-hospital-en p {
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
            margin: 0;
        }

        /* Document Title Banner */
        .document-title-banner {
            background: linear-gradient(90deg, #005a9c 0%, #0284c7 100%);
            color: #ffffff;
            text-align: center;
            padding: 4px 12px;
            border-radius: 4px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .document-title-banner h3 {
            font-size: 12px;
            font-weight: 900;
            margin: 0;
            letter-spacing: 0.3px;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        .report-code-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.4);
            padding: 1px 8px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 0.8px;
            direction: ltr;
        }

        /* Patient & Surgery Info Card (3-Zone Grey Clinical Card) */
        .patient-info-card {
            background-color: #f8fafc;
            border: 1.2px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 1.35fr 1.35fr 85px;
            gap: 10px;
            align-items: center;
        }

        .patient-zone-main, .patient-zone-order {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .patient-zone-qr {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-left: 1px dashed #cbd5e1;
            padding-left: 8px;
        }

        .qr-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-img {
            width: 62px;
            height: 62px;
            background: #ffffff;
            padding: 2px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .qr-tag {
            font-size: 8px;
            font-weight: 800;
            color: #005a9c;
            margin-top: 2px;
            letter-spacing: 0.4px;
            direction: ltr;
        }

        .qr-caption {
            font-size: 7.5px;
            color: #64748b;
            font-weight: 700;
            margin-top: 1px;
            white-space: nowrap;
        }

        .info-row {
            display: flex;
            align-items: center;
            line-height: 1.35;
            font-size: 11.5px;
        }

        .info-pill {
            background-color: #e5e8ec;
            color: #1e293b;
            font-weight: bold;
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 3px;
            min-width: 82px;
            display: inline-block;
        }

        .info-colon {
            margin: 0 4px;
            font-weight: bold;
            color: #475569;
        }

        .val-arabic-name {
            color: #005a9c;
            font-weight: bold;
            font-size: 12px;
            font-family: 'Tahoma', Arial, sans-serif;
            direction: rtl;
        }

        .val-english-name {
            color: #334155;
            font-weight: bold;
            font-size: 11px;
        }

        .info-val-text {
            color: #0f172a;
            font-weight: bold;
            font-size: 11px;
        }

        .badge-room {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 800;
        }

        /* Test Table Column Headers */
        .tests-header-bar {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr 1.2fr 0.9fr;
            border-top: 1.5px solid #2b5899;
            border-bottom: 1.5px solid #2b5899;
            padding: 4px 6px;
            margin-bottom: 4px;
            background-color: #f8fafc;
        }

        .header-col {
            color: #1e4b88;
            font-weight: bold;
            font-size: 11.5px;
            letter-spacing: 0.2px;
        }

        .header-col.center {
            text-align: center;
        }

        .header-col.right {
            text-align: center;
        }

        /* Category Header Row */
        .category-header-row {
            background-color: #f0f4f8;
            color: #005a9c;
            font-weight: 800;
            font-size: 11px;
            padding: 3.5px 8px;
            margin-top: 6px;
            margin-bottom: 2px;
            border-left: 3.5px solid #005a9c;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            page-break-inside: avoid;
            break-inside: avoid;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Test Item Card */
        .test-card {
            padding: 4px 6px;
            border-bottom: 1px solid #e1e4e8;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .test-card:last-child {
            border-bottom: 1.5px solid #2b5899;
        }

        .test-main-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr 1.2fr 0.9fr;
            align-items: center;
        }

        .test-name {
            font-weight: bold;
            color: #0f172a;
            font-size: 12px;
        }

        .test-name-ar {
            font-size: 10px;
            color: #64748b;
            font-weight: normal;
            display: block;
            direction: rtl;
            text-align: left;
            margin-top: 1px;
        }

        .test-value-cell {
            text-align: center;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .val-normal {
            color: #1e4b88;
            font-weight: bold;
            font-size: 12.5px;
        }

        .val-high {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: bold;
            font-size: 12px;
            padding: 1px 6px;
            border-radius: 3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-low {
            display: inline-block;
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
            font-weight: bold;
            font-size: 12px;
            padding: 1px 6px;
            border-radius: 3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-abnormal {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: bold;
            font-size: 12px;
            padding: 1px 6px;
            border-radius: 3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-high {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: 800;
            font-size: 10.5px;
            padding: 1px 8px;
            border-radius: 3px;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-low {
            display: inline-block;
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
            font-weight: 800;
            font-size: 10.5px;
            padding: 1px 8px;
            border-radius: 3px;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-normal {
            display: inline-block;
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 700;
            font-size: 10.5px;
            padding: 1px 8px;
            border-radius: 3px;
            letter-spacing: 0.3px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-pending {
            display: inline-block;
            background: #f8fafc;
            color: #64748b;
            border: 1px dashed #cbd5e1;
            font-weight: 700;
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 3px;
        }

        .unit-label {
            color: #475569;
            font-size: 11px;
            text-align: center;
        }

        .ref-interval-text {
            color: #334155;
            font-size: 11px;
            text-align: center;
            font-weight: 600;
        }

        /* Clinical Notes Block */
        .notes-block {
            border: 1px solid #bfdbfe;
            background-color: #eff6ff;
            border-radius: 6px;
            padding: 6px 12px;
            margin-top: 10px;
            margin-bottom: 6px;
            font-size: 11px;
            color: #1e3a8a;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .notes-block strong {
            color: #1d4ed8;
            margin-bottom: 2px;
            display: block;
        }

        /* Official Authentication & Signatures Block */
        .auth-block {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            background-color: #f8fafc;
            margin-top: 8px;
            margin-bottom: 4px;
            display: grid;
            grid-template-columns: 2fr 1.2fr;
            gap: 15px;
            align-items: center;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .auth-cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .signature-box-empty {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .sig-empty-line {
            width: 85%;
            height: 24px;
            border-bottom: 1.5px dashed #005a9c;
            margin-bottom: 2px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            color: #94a3b8;
            font-size: 10px;
            font-style: italic;
        }
        .sig-title {
            font-size: 11px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 2px;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        .sig-subtitle {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
        }

        .stamp-circle-box {
            width: 60px;
            height: 60px;
            border: 1.5px dashed #005a9c;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #005a9c;
            background: #ffffff;
            padding: 3px;
        }

        /* Footer */
        .report-footer-wrapper {
            width: 100%;
            margin-top: 6px;
            z-index: 10;
        }

        .report-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 10px;
            color: #333;
            font-family: 'Segoe UI', 'Tajawal', Tahoma, sans-serif;
        }

        .footer-printed-by span {
            font-weight: 700;
            color: #1e3a5f;
        }

        .footer-datetime {
            font-size: 10px;
            font-weight: 600;
            color: #444;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            letter-spacing: 0.3px;
        }

        .hospital-contact-line {
            text-align: center;
            font-size: 9px;
            color: #555;
            margin-top: 4px;
            font-family: 'Segoe UI', 'Tajawal', Tahoma, sans-serif;
            direction: rtl;
        }

        /* Print Media Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 12mm 10mm 12mm;
            }

            .print-toolbar {
                display: none !important;
            }

            body {
                background: #fff !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .report-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 6mm 10mm 6mm 10mm !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: 270mm !important;
                position: relative !important;
                box-sizing: border-box !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                border: 1.5px solid #005a9c !important;
                border-radius: 4px !important;
            }

            body.letterhead-mode .report-page {
                border: none !important;
            }

            .test-card {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .patient-info-card {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .auth-block {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body>

    @php
        // Helper: Convert Arabic Name to English Transliteration
        if (!function_exists('transliterateArabicName')) {
            function transliterateArabicName($text) {
                if (empty($text)) return '';
                $known = [
                    'محمد' => 'Mohammed', 'احمد' => 'Ahmed', 'أحمد' => 'Ahmed', 'علي' => 'Ali',
                    'حسين' => 'Hussein', 'حسن' => 'Hasan', 'صادق' => 'Sadeq', 'ياسين' => 'Yaseen',
                    'عباس' => 'Abbas', 'فاضل' => 'Fadhel', 'كاظم' => 'Kadhim', 'مهدي' => 'Mahdi',
                    'عمر' => 'Omar', 'عثمان' => 'Othman', 'خالد' => 'Khalid', 'مصطفى' => 'Mustafa',
                    'ابراهيم' => 'Ibrahim', 'إبراهيم' => 'Ibrahim', 'يوسف' => 'Yousif', 'محمود' => 'Mahmoud',
                    'عبدالله' => 'Abdullah', 'عبد الله' => 'Abdullah', 'عبد الرحمن' => 'Abdulrahman',
                    'فاطمة' => 'Fatima', 'زينب' => 'Zainab', 'مريم' => 'Maryam', 'نور' => 'Noor',
                    'سارة' => 'Sara', 'ساره' => 'Sara', 'هدى' => 'Huda', 'حيدر' => 'Haider',
                    'حسام' => 'Hussam', 'عبد الستار' => 'Abdul-Sattar', 'أسامة' => 'Osama'
                ];
                $words = preg_split('/\s+/', trim($text));
                $latinWords = [];
                $charMap = [
                    'ا' => 'a', 'أ' => 'A', 'إ' => 'E', 'آ' => 'Aa', 'ب' => 'b', 'ت' => 't', 'ث' => 'th',
                    'ج' => 'j', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'dh', 'ر' => 'r', 'ز' => 'z',
                    'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'dh', 'ع' => 'a',
                    'غ' => 'gh', 'ف' => 'f', 'ق' => 'q', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n',
                    'ه' => 'h', 'ة' => 'a', 'و' => 'w', 'ي' => 'y', 'ى' => 'a', 'ء' => ''
                ];
                foreach ($words as $w) {
                    if (isset($known[$w])) {
                        $latinWords[] = $known[$w];
                    } else {
                        $chars = mb_str_split($w);
                        $res = '';
                        foreach ($chars as $c) {
                            $res .= $charMap[$c] ?? $c;
                        }
                        $latinWords[] = ucfirst($res);
                    }
                }
                return implode(' ', $latinWords);
            }
        }

        // Helper: Dynamically Evaluate Lab Test Value Against Reference Range
        if (!function_exists('calcLabStatus')) {
            function calcLabStatus($value, $refRange, $fallbackStatus = 'normal') {
                $valStr = trim((string)$value);
                if ($valStr === '') return 'pending';

                $ref = trim((string)$refRange);
                $ref = str_replace(["\xc2\xa0", '–', '—', '−', '[', ']', '(', ')'], [' ', '-', '-', '-', '', '', '', ''], $ref);
                $ref = trim($ref);

                $numVal = null;
                if (is_numeric($valStr)) {
                    $numVal = (float)$valStr;
                } else {
                    $cleanVal = preg_replace('/[^\d\.\-\+]/', '', $valStr);
                    if (is_numeric($cleanVal)) {
                        $numVal = (float)$cleanVal;
                    }
                }

                if ($numVal !== null && !empty($ref)) {
                    // Pattern 1: Range "12.3 - 20.2" or "8-16"
                    if (preg_match('/([+\-]?\d+(?:\.\d+)?)\s*(?:-|to|\.\.)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $min = (float)$m[1];
                        $max = (float)$m[2];
                        if ($min > $max) { $t = $min; $min = $max; $max = $t; }
                        if ($numVal < $min) return 'low';
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
                    // Pattern 2: Upper bound "< 10" or "<= 10"
                    if (preg_match('/(?:<|<=|≤)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $max = (float)$m[1];
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
                    // Pattern 3: Lower bound "> 0.75" or ">= 0.75"
                    if (preg_match('/(?:>|>=|≥)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $min = (float)$m[1];
                        if ($numVal < $min) return 'low';
                        return 'normal';
                    }
                }

                $lower = strtolower($valStr);
                if (str_contains($lower, 'pos') || str_contains($lower, 'react') || str_contains($lower, 'high')) return 'high';
                if (str_contains($lower, 'low')) return 'low';

                $fallback = strtolower((string)$fallbackStatus);
                if (in_array($fallback, ['high', 'low', 'abnormal', 'positive'])) {
                    return $fallback;
                }

                return 'normal';
            }
        }

        $surgery = $surgery ?? $test->surgery;
        $patientModel = $surgery?->patient;
        $patientUser = $patientModel?->user;

        $rawName = $patientUser?->name ?? ($patientModel?->name ?? 'Patient');
        $arabicName = $rawName;
        $englishName = transliterateArabicName($rawName);

        $patientAge = $patientModel?->age ?? ($patientUser?->date_of_birth ? \Carbon\Carbon::parse($patientUser->date_of_birth)->age : 30);
        $rawGender = trim(strtolower((string)($patientUser?->gender ?? $patientModel?->gender ?? '')));
        if (in_array($rawGender, ['female', 'f', 'أنثى', 'انثى'], true)) {
            $genderEn = 'Female';
            $patientGender = 'female';
        } elseif (in_array($rawGender, ['male', 'm', 'ذكر'], true)) {
            $genderEn = 'Male';
            $patientGender = 'male';
        } else {
            $genderEn = 'Male';
            $patientGender = 'male';
        }

        $rawDoctor = $surgery?->doctor?->user?->name ?? '';
        $doctorEn = $rawDoctor ? (str_starts_with($rawDoctor, 'د.') || str_starts_with($rawDoctor, 'الدكتور') ? transliterateArabicName($rawDoctor) : 'Dr. ' . transliterateArabicName($rawDoctor)) : 'Dr. ' . transliterateArabicName($rawDoctor);

        $patientPhone = $patientUser?->phone ?? ($patientModel?->phone ?? '—');
        $roomInfo = $surgery?->room ? 'غرفة ' . $surgery->room->room_number . ' (' . ($surgery->room->floor ?? '—') . ')' : 'غير محدد';

        $reportNo = 'SURG-' . ($surgery ? $surgery->id : $test->id);
        $sampleDate = $test->created_at ? $test->created_at->format('d-M-Y') : date('d-M-Y');
        $sampleTime = $test->created_at ? $test->created_at->format('H:i A') : date('H:i A');

        $hospitalLogoPath = public_path('images/1.jpg');
        $hospitalLogoSrc = asset('images/1.jpg');
        if (file_exists($hospitalLogoPath)) {
            $ext = pathinfo($hospitalLogoPath, PATHINFO_EXTENSION);
            $mime = ($ext === 'png') ? 'image/png' : 'image/jpeg';
            $hospitalLogoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($hospitalLogoPath));
        }

        $verifyUrl = route('staff.surgery-lab-tests.show', $test->id);
        $cleanPhone = preg_replace('/[^0-9]/', '', (string)$patientPhone);
        if (str_starts_with($cleanPhone, '07')) {
            $cleanPhone = '964' . substr($cleanPhone, 1);
        }
        $waShareUrl = $cleanPhone ? 'https://wa.me/' . $cleanPhone . '?text=' . urlencode("مرحباً بك {$rawName}، تم إصدار تقرير تحاليل عمليتك الجراحية المعتمد من مستشفى الفرات الأهلي:\n" . $verifyUrl) : null;
    @endphp

    <!-- Floating Print Toolbar -->
    <div class="print-toolbar">
        <button class="toolbar-btn btn-print" onclick="window.print()">
            🖨️ طباعة التقرير
        </button>

        @if($waShareUrl)
            <a href="{{ $waShareUrl }}" target="_blank" class="toolbar-btn" style="background: #25d366; color: #fff;" title="إرسال رابط التقرير الطبي المعتمد للمريض عبر واتساب">
                💬 إرسال واتساب للمريض
            </a>
        @endif

        <div class="mode-toggle-group">
            <button type="button" class="mode-btn" id="btnModeLetterhead" onclick="setPrintMode('letterhead')">
                📄 ورق مروس (Letterhead)
            </button>
            <button type="button" class="mode-btn active" id="btnModeFull" onclick="setPrintMode('full')">
                📑 ورق أبيض كامل (Full)
            </button>
        </div>

        <div class="margin-control-group" id="marginControls" style="display: none;">
            <label for="topMarginInput">المسافة العلوية:</label>
            <input type="number" id="topMarginInput" min="10" max="120" step="1" value="45" oninput="changeTopMargin(this.value)">
            <span>مم</span>
        </div>

        <a href="{{ route('staff.surgery-lab-tests.show', $test) }}" class="toolbar-btn btn-close-window">
            ✕ رجوع
        </a>
    </div>

    <!-- Main Report Page -->
    <div class="report-page">
        <div class="report-main-content">
            <!-- Full Official Header -->
            <div class="report-header" style="direction: rtl;">
                <div class="header-hospital-ar">
                    <h2>مستشفى الفرات الأهلي</h2>
                    <h4>قسم المختبر والتحاليل الطبية السريرية</h4>
                    <p>فحوصات وتحاليل العمليات الجراحية المعتمدة</p>
                </div>
                <div class="header-logo">
                    <img src="{!! $hospitalLogoSrc !!}" 
                         alt="Hospital Logo" 
                         class="hospital-logo-img" 
                         onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                         style="max-height: 75px; max-width: 95px; object-fit: contain;">
                </div>
                <div class="header-hospital-en">
                    <h2>Al-Furat Private Hospital</h2>
                    <h4>Surgery & Clinical Laboratory Dept.</h4>
                    <p>Pre-Operative & Diagnostic Pathology Services</p>
                </div>
            </div>

            <!-- Document Title Banner -->
            <div class="document-title-banner">
                <h3>تقرير الفحوصات المخبرية للعمليات الجراحية (SURGERY CLINICAL LABORATORY REPORT)</h3>
                <div class="report-code-badge">
                    {{ $reportNo }}
                </div>
            </div>

            <!-- Patient & Surgery Info Card (3-Zone Grey Clinical Card) -->
            <div class="patient-info-card">
                <!-- Zone 1: Patient Demographics -->
                <div class="patient-zone-main">
                    <div class="info-row">
                        <span class="info-pill">Patient Name</span>
                        <span class="info-colon">:</span>
                        <span class="val-arabic-name">{{ $arabicName }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Patient Name</span>
                        <span class="info-colon">:</span>
                        <span class="val-english-name">{{ $englishName }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Age / Sex</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text">{{ $patientAge ? $patientAge . ' Year(s)' : '—' }} &nbsp;•&nbsp; <strong style="color: #005a9c;">{{ $genderEn }}</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Phone No.</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text" style="direction: ltr; text-align: right;">{{ $patientPhone ?: '—' }}</span>
                    </div>
                </div>

                <!-- Zone 2: Surgery & Clinical Details -->
                <div class="patient-zone-order">
                    <div class="info-row">
                        <span class="info-pill">Surgeon</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text">{{ $doctorEn ?: '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Surgery</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text" style="color: #005a9c;">{{ $surgery?->surgery_type ?? 'عملية جراحية' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Room / Ward</span>
                        <span class="info-colon">:</span>
                        <span class="badge-room">{{ $roomInfo }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Date / Time</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text">{{ $sampleDate }} &nbsp;•&nbsp; {{ $sampleTime }}</span>
                    </div>
                </div>

                <!-- Zone 3: Direct QR Code -->
                <div class="patient-zone-qr">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" class="qr-img">
                        <span class="qr-tag">LAB-#{{ $test->id }}</span>
                        <span class="qr-caption">تحقق الفحص</span>
                    </div>
                </div>
            </div>

            <!-- Standard Tests Column Headers -->
            <div class="tests-header-bar">
                <div class="header-col">Test Description / الفحص المخبري</div>
                <div class="header-col center">Result / النتيجة</div>
                <div class="header-col center">Unit / الوحدة</div>
                <div class="header-col center">Reference Interval</div>
                <div class="header-col right">Status</div>
            </div>

            @php
                // Group tests by main category
                $grouped = $surgeryLabTests->groupBy(function($item) {
                    return $item->labTest?->category_text ?? ($item->labTest?->main_category ?? 'تحاليل العمليات الجراحية العامة (General Pre-Op Tests)');
                });
            @endphp

            <div class="standard-tests-section">
                @foreach($grouped as $categoryName => $testsGroup)
                    <div class="category-header-row">
                        {{ $categoryName }}
                    </div>

                    @foreach($testsGroup as $sLabTest)
                        @php
                            $labTest = $sLabTest->labTest;
                            $val = trim((string)($sLabTest->result ?? ''));
                            
                            // Calculate normal reference range dynamically based on patient age and gender
                            $refObj = $sLabTest->lab_test_id ? \App\Models\LabTestReference::forPatient($sLabTest->lab_test_id, $patientGender, $patientAge) : null;
                            $refDisplay = $refObj ? $refObj->range_display : ($labTest?->reference_range ?: '—');
                            $unitDisplay = $refObj?->unit ?? ($labTest?->unit ?? '—');

                            $status = calcLabStatus($val, $refDisplay, $sLabTest->status);

                            $valClass = 'val-normal';
                            if ($status === 'high' || $status === 'positive' || str_contains($status, 'high') || str_contains($status, 'pos') || $status === '+') {
                                $valClass = 'val-high';
                            } elseif ($status === 'low' || str_contains($status, 'low') || $status === '-') {
                                $valClass = 'val-low';
                            } elseif ($status === 'abnormal') {
                                $valClass = 'val-abnormal';
                            }
                        @endphp

                        <div class="test-card">
                            <div class="test-main-row">
                                <div class="test-name">
                                    {{ $labTest?->name ?? ('تحليل #' . $sLabTest->id) }}
                                    @if($labTest?->code)
                                        <small style="color: #64748b; font-weight: normal; margin-left: 4px;">({{ $labTest->code }})</small>
                                    @endif
                                </div>

                                <div class="test-value-cell">
                                    @if($val !== '')
                                        <span class="{{ $valClass }}">{{ $val }}</span>
                                    @else
                                        <span class="val-status-pending">⏳ بانتظار الفحص</span>
                                    @endif
                                </div>

                                <div class="unit-label">
                                    {{ $unitDisplay }}
                                </div>

                                <div class="ref-interval-text">
                                    {{ $refDisplay }}
                                </div>

                                <div class="test-value-cell right">
                                    @if($val === '')
                                        <span class="val-status-pending">Pending</span>
                                    @elseif($status === 'high' || $status === 'positive' || str_contains($status, 'high') || str_contains($status, 'pos') || $status === '+')
                                        <span class="val-status-high">High</span>
                                    @elseif($status === 'low' || str_contains($status, 'low') || $status === '-')
                                        <span class="val-status-low">Low</span>
                                    @elseif($status === 'abnormal')
                                        <span class="val-status-high">Abnormal</span>
                                    @else
                                        <span class="val-status-normal">Normal</span>
                                    @endif
                                </div>
                            </div>

                            @if($labTest && $labTest->subTests && $labTest->subTests->count() > 0)
                                <div style="margin-left: 15px; margin-top: 3px; border-left: 2px solid #cbd5e1; padding-left: 8px;">
                                    @foreach($labTest->subTests as $subTest)
                                        <div style="display: grid; grid-template-columns: 2.2fr 1fr 1fr 1.2fr 0.9fr; font-size: 11px; padding: 2px 0; color: #475569;">
                                            <div>• {{ $subTest->name }}</div>
                                            <div style="text-align: center;">—</div>
                                            <div style="text-align: center;">{{ $subTest->unit ?? '—' }}</div>
                                            <div style="text-align: center;">{{ $subTest->reference_range ?? '—' }}</div>
                                            <div style="text-align: center;">—</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            </div>

            <!-- Notes & Clinical Interpretation -->
            @php
                $allNotes = collect($surgeryLabTests)->pluck('notes')->filter()->unique()->implode(' • ');
                if (empty($allNotes) && !empty($test->notes)) {
                    $allNotes = $test->notes;
                }
            @endphp
            @if(!empty($allNotes))
                <div class="notes-block">
                    <strong>📋 الملاحظات والتوصيات السريرية (Clinical Notes & Recommendations):</strong>
                    <span>{{ $allNotes }}</span>
                </div>
            @endif
        </div>

        <div class="report-bottom-block">
            <!-- Official Signatures & Verification -->
            <div class="auth-block">
                <div class="auth-cell" style="align-items: flex-start; text-align: left;">
                    <div class="signature-box-empty">
                        <div class="sig-title">أخصائي / فاحص المختبر والتحاليل الطبية</div>
                        <div class="sig-subtitle">{{ auth()->user()->name ?? 'Clinical Laboratory Specialist' }}</div>
                        <div class="sig-empty-line">
                            (مكان التوقيع والاعتماد)
                        </div>
                        <div style="font-size: 9px; color: #64748b; font-weight: 700; margin-top: 1px;">
                            التاريخ: {{ now()->format('Y-m-d') }}
                        </div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="stamp-circle-box">
                        <div style="font-size: 7px; font-weight: 800; color: #005a9c;">★ ★ ★</div>
                        <div style="font-size: 8px; font-weight: 900; line-height: 1.1;">مستشفى الفرات</div>
                        <div style="font-size: 7px; font-weight: 800; color: #0284c7;">مختبر العمليات</div>
                        <div style="font-size: 7px; color: #b91c1c; font-weight: 900; margin-top: 1px;">مُعتمَــد</div>
                    </div>
                </div>
            </div>

            <!-- Pinned Footer -->
            <div class="report-footer-wrapper">
                <div class="report-footer">
                    <div class="footer-printed-by">
                        Printed by : <span>{{ auth()->user()->name ?? 'موظف المختبر' }}</span>
                    </div>
                    <div class="footer-datetime">
                        <span>{{ now()->format('d-m-Y h:i:sA') }}</span>
                    </div>
                </div>

                <div class="hospital-contact-line">
                    📍 مستشفى الفرات الأهلي - قسم المختبرات والعمليات الجراحية &nbsp;|&nbsp; 📞 للاستفسار والمراجعة: مراجعة إدارة المختبر الطبي
                </div>
            </div>
        </div>
    </div>

    <script>
        function setPrintMode(mode) {
            const body = document.body;
            const btnLetterhead = document.getElementById('btnModeLetterhead');
            const btnFull = document.getElementById('btnModeFull');
            const marginControls = document.getElementById('marginControls');

            if (mode === 'letterhead') {
                body.classList.add('letterhead-mode');
                if (btnLetterhead) btnLetterhead.classList.add('active');
                if (btnFull) btnFull.classList.remove('active');
                if (marginControls) marginControls.style.display = 'flex';
            } else {
                body.classList.remove('letterhead-mode');
                if (btnFull) btnFull.classList.add('active');
                if (btnLetterhead) btnLetterhead.classList.remove('active');
                if (marginControls) marginControls.style.display = 'none';
            }

            try {
                localStorage.setItem('surgery_lab_print_mode', mode);
            } catch (e) {}
        }

        function changeTopMargin(val) {
            val = parseInt(val) || 45;
            document.documentElement.style.setProperty('--letterhead-top', val + 'mm');
            try {
                localStorage.setItem('surgery_lab_print_top_margin', val);
            } catch (e) {}
        }

        // Initialize user preference on load
        (function() {
            let savedMode = 'full';
            let savedTop = 45;
            try {
                savedMode = localStorage.getItem('surgery_lab_print_mode') || 'full';
                savedTop = parseInt(localStorage.getItem('surgery_lab_print_top_margin')) || 45;
            } catch (e) {}

            const topInput = document.getElementById('topMarginInput');
            if (topInput) {
                topInput.value = savedTop;
            }
            changeTopMargin(savedTop);
            setPrintMode(savedMode);
        })();
    </script>
</body>
</html>
