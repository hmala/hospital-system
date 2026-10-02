<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Laboratory Report - {{ $request->visit?->patient?->user?->name ?? 'Patient' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800&family=Tajawal:wght@500;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
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
            margin: 0 auto;
            background: #ffffff;
            padding: 16mm 20mm 35mm 20mm;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
        }

        /* Watermark Background */
        .report-page::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: url('{{ asset('images/1.jpg') }}');
            background-repeat: no-repeat;
            background-position: center 52%;
            background-size: 52% auto;
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }

        .report-page > * {
            position: relative;
            z-index: 1;
        }

        /* Decorative Border */
        .decorative-border {
            position: absolute;
            top: 8mm;
            right: 8mm;
            bottom: 8mm;
            left: 8mm;
            border: 1.5px solid #1e7e8f;
            pointer-events: none;
            z-index: 0;
        }

        .corner-pattern {
            position: absolute;
            width: 50px;
            height: 50px;
            opacity: 0.18;
        }

        .corner-pattern.top-right {
            top: 0;
            right: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L100,0 L100,100 Z" fill="%231e7e8f"/></svg>');
        }

        :root {
            --letterhead-top: 45mm;
            --letterhead-bottom: 25mm;
        }

        /* Letterhead Mode Styles (for pre-printed official hospital letterhead paper) */
        body.letterhead-mode .report-header,
        body.letterhead-mode .decorative-border,
        body.letterhead-mode .corner-pattern,
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

        .mode-btn:hover {
            color: #fff;
        }

        .mode-btn.active {
            background: #1e7e8f;
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

        /* Header Section */
        .report-header {
            text-align: center;
            margin-bottom: 22px;
            padding-bottom: 12px;
            border-bottom: 2px solid #1e7e8f;
        }

        .header-top-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
            position: relative;
        }

        .hospital-logo-side {
            text-align: left;
            min-width: 180px;
        }

        .hospital-logo-img {
            height: 85px;
            max-width: 180px;
            object-fit: contain;
        }

        .clinical-lab-center {
            text-align: center;
            flex-grow: 1;
        }

        .hospital-title-side {
            text-align: right;
            min-width: 190px;
        }

        .hospital-name-ar {
            font-size: 12px;
            font-weight: 700;
            color: #1e3a5f;
            font-family: 'Tajawal', 'Cairo', 'Tahoma', sans-serif;
            margin-bottom: 2px;
            direction: rtl;
            line-height: 1.25;
        }

        .hospital-name-en {
            font-size: 9.5px;
            color: #555555;
            font-weight: 600;
            letter-spacing: 0.3px;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            line-height: 1.2;
        }

        .clinical-lab-banner {
            font-family: 'Times New Roman', Times, serif;
            font-size: 30px;
            font-weight: bold;
            font-style: italic;
            color: #1e4b88;
            letter-spacing: 0.5px;
        }

        /* Patient Info Grid (Matching image exactly) */
        .patient-info-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 6px 30px;
            margin-bottom: 18px;
            font-size: 13px;
        }

        .info-row {
            display: flex;
            align-items: center;
            line-height: 1.5;
            margin-bottom: 4px;
        }

        .info-pill {
            background-color: #e5e8ec;
            color: #1a1a1a;
            font-weight: bold;
            font-size: 12.5px;
            padding: 2px 8px;
            border-radius: 4px;
            min-width: 120px;
            display: inline-block;
        }

        .info-colon {
            margin: 0 8px;
            font-weight: bold;
            color: #333;
        }

        .val-arabic-name {
            color: #1e4b88;
            font-weight: bold;
            font-size: 14.5px;
            font-family: 'Tahoma', Arial, sans-serif;
            direction: rtl;
        }

        .val-english-name {
            color: #8b0000;
            font-weight: bold;
            font-size: 14px;
        }

        .info-val-text {
            color: #111;
            font-weight: bold;
            font-size: 13px;
        }

        /* Test Table Column Headers (Matching image double blue line) */
        .tests-header-bar {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            border-top: 1.5px solid #2b5899;
            border-bottom: 1.5px solid #2b5899;
            padding: 6px 4px;
            margin-bottom: 8px;
        }

        .header-col {
            color: #1e4b88;
            font-weight: bold;
            font-size: 13.5px;
        }

        .header-col.center {
            text-align: center;
        }

        .header-col.right {
            text-align: right;
            padding-right: 15px;
        }

        /* Test Item Card */
        .test-card {
            padding: 6px 4px 8px 4px;
            border-bottom: 1px solid #e1e4e8;
        }

        .test-card:last-child {
            border-bottom: 1.5px solid #2b5899;
        }

        .test-main-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            align-items: baseline;
            margin-bottom: 3px;
        }

        .test-name {
            font-weight: bold;
            color: #000;
            font-size: 13.5px;
        }

        .test-value-cell {
            text-align: center;
            font-size: 13px;
        }

        .test-value-cell.right {
            text-align: right;
            padding-right: 15px;
        }

        .val-normal {
            color: #1e4b88;
            font-weight: bold;
            font-size: 14px;
        }

        .val-high {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: bold;
            font-size: 13.5px;
            padding: 1px 7px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-low {
            display: inline-block;
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
            font-weight: bold;
            font-size: 13.5px;
            padding: 1px 7px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-abnormal {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: bold;
            font-size: 13.5px;
            padding: 1px 7px;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .badge-flag {
            font-size: 11px;
            font-weight: 800;
            margin-left: 5px;
            vertical-align: middle;
        }
        .badge-flag.high { color: #b91c1c; }
        .badge-flag.low { color: #b45309; }

        .unit-label {
            color: #333;
            font-size: 12.5px;
            margin-left: 4px;
        }

        .test-meta-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            align-items: baseline;
            font-size: 12.5px;
            color: #444;
            margin-bottom: 2px;
        }

        .meta-label {
            font-weight: 600;
            color: #333;
        }

        .meta-conv-range {
            text-align: center;
            color: #444;
            font-size: 12.5px;
        }

        .meta-si-range {
            text-align: right;
            padding-right: 15px;
            color: #444;
            font-size: 12.5px;
        }

        .test-device-row {
            font-size: 12px;
            color: #1e4b88;
            margin-top: 2px;
        }

        /* Category Header */
        .category-header-row {
            background-color: #f1f4f8;
            color: #1e4b88;
            font-weight: bold;
            font-size: 13px;
            padding: 5px 8px;
            margin-top: 8px;
            border-left: 3px solid #1e4b88;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Footer */
        .report-footer-wrapper {
            position: absolute !important;
            bottom: 14mm;
            left: 20mm;
            right: 20mm;
            width: auto;
            z-index: 10;
        }

        .report-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            color: #333;
            font-family: 'Segoe UI', 'Tajawal', Tahoma, sans-serif;
        }

        .footer-printed-by {
            font-size: 11.5px;
            color: #333;
        }

        .footer-printed-by span {
            font-weight: 700;
            color: #1e3a5f;
        }

        .footer-datetime {
            font-size: 11.5px;
            font-weight: 600;
            color: #444;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            letter-spacing: 0.3px;
        }

        .hospital-contact-line {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-top: 6px;
            font-family: 'Segoe UI', 'Tajawal', Tahoma, sans-serif;
            direction: rtl;
        }

        /* Print Media Styles */
        @media print {
            .print-toolbar {
                display: none !important;
            }

            body {
                background: #fff;
                padding: 0;
                margin: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-page {
                box-shadow: none !important;
                margin: 0 auto !important;
                padding: 16mm 20mm 35mm 20mm !important;
                width: 210mm !important;
                max-width: 100% !important;
                min-height: 297mm !important;
                position: relative !important;
                box-sizing: border-box !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                page-break-after: auto;
            }

            .decorative-border {
                display: block !important;
                position: absolute;
                top: 8mm;
                right: 8mm;
                bottom: 8mm;
                left: 8mm;
                border: 1.5px solid #1e7e8f;
            }

            .report-page::before {
                position: absolute;
                inset: 0;
                opacity: 0.04;
            }

            .report-footer-wrapper {
                position: absolute !important;
                bottom: 14mm !important;
                left: 20mm !important;
                right: 20mm !important;
                width: auto !important;
                z-index: 9999;
                background: transparent !important;
            }

            .test-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .patient-info-grid {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            body.letterhead-mode .report-header,
            body.letterhead-mode .decorative-border,
            body.letterhead-mode .corner-pattern,
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
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Action Toolbar (Hidden during print) -->
    <div class="print-toolbar">
        <button class="toolbar-btn btn-print" onclick="window.print()">
            🖨️ طباعة التقرير
        </button>

        <div class="mode-toggle-group">
            <button type="button" class="mode-btn" id="btnModeLetterhead" onclick="setPrintMode('letterhead')">
                📄 ورق مروس (Letterhead)
            </button>
            <button type="button" class="mode-btn" id="btnModeFull" onclick="setPrintMode('full')">
                📑 ورق أبيض كامل (Full)
            </button>
        </div>

        <div class="margin-control-group" id="marginControls">
            <label for="topMarginInput">المسافة العلوية:</label>
            <input type="number" id="topMarginInput" min="10" max="120" step="1" value="45" oninput="changeTopMargin(this.value)">
            <span>مم</span>
        </div>

        <button class="toolbar-btn btn-close-window" onclick="window.history.back()">
            ✕ رجوع
        </button>
    </div>

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
                    'سارة' => 'Sara', 'ساره' => 'Sara', 'هدى' => 'Huda', 'حيدر' => 'Haider'
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
                if ($valStr === '') return 'normal';

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
                    // Pattern 1: Range "12.3 - 20.2" or "-2 to +2" or "8-16"
                    if (preg_match('/([+\-]?\d+(?:\.\d+)?)\s*(?:-|to|\.\.)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $min = (float)$m[1];
                        $max = (float)$m[2];
                        if ($min > $max) { $t = $min; $min = $max; $max = $t; }
                        if ($numVal < $min) return 'low';
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
                    // Pattern 2: Upper bound "< 10" or "<= 10" or "≤ 10"
                    if (preg_match('/(?:<|<=|≤)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $max = (float)$m[1];
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
                    // Pattern 3: Lower bound "> 0.75" or ">= 0.75" or "≥ 0.75"
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

        $rawName = $request->visit?->patient?->user?->name ?? 'Patient';
        $arabicName = $rawName;
        $englishName = transliterateArabicName($rawName);

        $patientAge = $request->visit?->patient?->age ?? '';
        $rawGender = strtolower($request->visit?->patient?->gender ?? '');
        $genderEn = ($rawGender === 'female' || $rawGender === 'أنثى') ? 'Female' : 'Male';

        $rawDoctor = $request->visit?->doctor?->user?->name ?? '';
        $doctorEn = $rawDoctor ? (str_starts_with($rawDoctor, 'د.') || str_starts_with($rawDoctor, 'الدكتور') ? transliterateArabicName($rawDoctor) : 'Dr. ' . transliterateArabicName($rawDoctor)) : '';

        $sampleNo = 'IQ' . ($request->created_at ? $request->created_at->format('y') : date('y')) . '/' . str_pad($request->id, 7, '0', STR_PAD_LEFT);
        $patientNo = ($request->created_at ? $request->created_at->format('y') : date('y')) . '/' . str_pad($request->visit?->patient_id ?? $request->patient_id ?? $request->id, 7, '0', STR_PAD_LEFT);

        $sampleDate = $request->created_at ? $request->created_at->format('d-M-Y') : date('d-M-Y');
        $sampleTime = $request->created_at ? $request->created_at->format('H:i A') : date('H:i A');

        $labResults = \App\Models\LabResult::where('request_id', $request->id)->orderBy('id')->get();
        $testsCount = $labResults->count();

        if ($testsCount === 0 && !empty($request->result)) {
            $parsedRes = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            $savedArr = $parsedRes['test_results'] ?? [];
            $testsCount = count($savedArr);
        }

        $isBloodBankRequest = $isBloodBankRequest ?? ($request->type === 'blood_bank' || data_get($request->details, 'blood_bank', false));
        $badgeText = $isBloodBankRequest ? 'Blood Bank' : 'Clinical Laboratory';
        $badgeIcon = $isBloodBankRequest ? 'blood-bank-icon.svg' : 'lab-icon.svg';
    @endphp

    <div class="report-page">

        <!-- Decorative Border -->
        <div class="decorative-border">
            <div class="corner-pattern top-right"></div>
            <div class="corner-pattern bottom-left"></div>
        </div>

        <!-- Header Section -->
        <div class="report-header">
            <div class="header-top-row">
                <!-- Left: Hospital Logo (in place of Lab Icon badge) -->
                <div class="hospital-logo-side">
                    <img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo" class="hospital-logo-img">
                </div>

                <!-- Center: Clinical Laboratory Title -->
                <div class="clinical-lab-center">
                    <div class="clinical-lab-banner">Clinical Laboratory</div>
                </div>

                <!-- Right: Hospital Name in Arabic & English -->
                <div class="hospital-title-side">
                    <div class="hospital-name-ar">مستشفى الكفاءات الاهلي</div>
                    <div class="hospital-name-en">Al-Kafaat Private Hospital</div>
                </div>
            </div>
        </div>

        <!-- Patient Info Box -->
        <div class="patient-info-grid">
            <!-- Left Column -->
            <div>
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
                    <span class="info-pill">Age</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $patientAge ? $patientAge . ' Year(s)' : '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Sex</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $genderEn }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Consultant</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $doctorEn ?: '—' }}</span>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <div class="info-row">
                    <span class="info-pill">Sample No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $sampleNo }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Patient No.</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $patientNo }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Sample Date</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $sampleDate }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Sample Time</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $sampleTime }}</span>
                </div>
                <div class="info-row">
                    <span class="info-pill">Number Of Tests</span>
                    <span class="info-colon">:</span>
                    <span class="info-val-text">{{ $testsCount }}</span>
                </div>
            </div>
        </div>

        @if(isset($isBloodBankRequest) && $isBloodBankRequest)
            <!-- Blood Bank Specialized Table -->
            <div style="margin-top: 20px;">
                <div class="category-header-row">Blood Bank Request Details</div>
                @if(isset($bloodBankRequest) && $bloodBankRequest)
                    <table style="width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px;">
                        <tbody>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; width: 40%; padding: 6px;">Request ID</td><td>{{ $request->id }}</td></tr>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; padding: 6px;">Status</td><td>{{ $bloodBankRequest->status }}</td></tr>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; padding: 6px;">Donor Group</td><td>{{ $bloodBankRequest->donor_group ?? '-' }}</td></tr>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; padding: 6px;">Patient Group</td><td>{{ $bloodBankRequest->patient_group ?? '-' }}</td></tr>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; padding: 6px;">Compatibility</td><td>{{ $bloodBankRequest->compatibility ?? '-' }}</td></tr>
                            <tr style="border-bottom: 1px solid #ddd; padding: 6px 0;"><td style="font-weight: bold; padding: 6px;">Bottle No</td><td>{{ $bloodBankRequest->bottle_no ?? '-' }}</td></tr>
                        </tbody>
                    </table>
                @endif
            </div>
        @else
            <!-- Column Headers -->
            <div class="tests-header-bar">
                <div class="header-col">Laboratory Test Results</div>
                <div class="header-col center">Conventional Units</div>
                <div class="header-col right">SI Units</div>
            </div>

            <!-- Test Items List -->
            @php
                $currentGroup = null;
            @endphp

            @if($labResults->count() > 0)
                @foreach($labResults as $res)
                    @php
                        $parentName = $res->parent_test_name;
                        $val = trim($res->value ?? '');
                        $unit = trim($res->unit ?? '');
                        $refRange = trim($res->reference_range ?? '');

                        // تقييم الحالة ديناميكياً بدقة
                        $status = calcLabStatus($val, $refRange, $res->status ?? 'normal');

                        $valClass = 'val-normal';
                        $flagBadge = '';
                        if ($status === 'high' || $status === 'positive' || str_contains($status, 'high') || str_contains($status, 'pos') || $status === '+') {
                            $valClass = 'val-high';
                            $flagBadge = '<span class="badge-flag high">↑ High</span>';
                        } elseif ($status === 'low' || str_contains($status, 'low') || $status === '-') {
                            $valClass = 'val-low';
                            $flagBadge = '<span class="badge-flag low">↓ Low</span>';
                        } elseif ($status === 'abnormal') {
                            $valClass = 'val-abnormal';
                            $flagBadge = '<span class="badge-flag high">Abnormal</span>';
                        }

                        // Secondary SI Unit computation or alternate display
                        $siVal = '';
                        $siUnit = '';
                        $siRange = '';

                        if (is_numeric($val)) {
                            $num = (float)$val;
                            if (strtolower($unit) === 'g/l') {
                                $siVal = number_format($num * 100, 1);
                                $siUnit = 'mg/dl';
                                if (preg_match('/([+\-]?\d+(?:\.\d+)?)\s*(?:-|to)\s*([+\-]?\d+(?:\.\d+)?)/i', $refRange, $m)) {
                                    $siRange = number_format((float)$m[1] * 100, 1) . ' - ' . number_format((float)$m[2] * 100, 1);
                                }
                            } elseif (strtolower($unit) === 'mg/dl' && $num > 10) {
                                $siVal = number_format($num / 100, 2);
                                $siUnit = 'g/l';
                                if (preg_match('/([+\-]?\d+(?:\.\d+)?)\s*(?:-|to)\s*([+\-]?\d+(?:\.\d+)?)/i', $refRange, $m)) {
                                    $siRange = number_format((float)$m[1] / 100, 2) . ' - ' . number_format((float)$m[2] / 100, 2);
                                }
                            }
                        }
                    @endphp

                    @if($parentName && $parentName !== $currentGroup)
                        @php $currentGroup = $parentName; @endphp
                        <div class="category-header-row">
                            {{ $currentGroup }}
                        </div>
                    @endif

                    <div class="test-card">
                        <!-- Main Test Row -->
                        <div class="test-main-row">
                            <div class="test-name">
                                {{ $res->test_name }}
                            </div>
                            <div class="test-value-cell">
                                <span class="{{ $valClass }}">{{ $val ?: '—' }}</span>
                                {!! $flagBadge !!}
                                @if($unit)
                                    <span class="unit-label">{{ $unit }}</span>
                                @endif
                            </div>
                            <div class="test-value-cell right">
                                @if($siVal)
                                    <span class="{{ $valClass }}">{{ $siVal }}</span>
                                    {!! $flagBadge !!}
                                    <span class="unit-label">{{ $siUnit }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Reference Range Row -->
                        @if($refRange || $siRange)
                        <div class="test-meta-row">
                            <div class="meta-label">Normal Range :</div>
                            <div class="meta-conv-range">{{ $refRange ?: '—' }}</div>
                            <div class="meta-si-range">{{ $siRange ?: '' }}</div>
                        </div>
                        @endif

                        <!-- Device / Method Row -->
                        <div class="test-device-row">
                            By Cobas Integra 400 plus (Roche Diagnostics)
                        </div>
                    </div>
                @endforeach
            @elseif(!empty($request->result))
                @php
                    $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                    $testResults = is_array($resultData) ? ($resultData['test_results'] ?? []) : [];
                @endphp
                @foreach($testResults as $tName => $tVal)
                    @php
                        $valStr = is_array($tVal) ? ($tVal['value'] ?? '') : $tVal;
                        $unitStr = is_array($tVal) ? ($tVal['unit'] ?? '') : '';
                        $refStr = is_array($tVal) ? ($tVal['reference_range'] ?? '') : '';
                        $statusStr = calcLabStatus($valStr, $refStr, is_array($tVal) ? ($tVal['status'] ?? 'normal') : 'normal');

                        $valClass = 'val-normal';
                        $flagBadge = '';
                        if ($statusStr === 'high' || $statusStr === 'positive' || str_contains($statusStr, 'high') || str_contains($statusStr, 'pos')) {
                            $valClass = 'val-high';
                            $flagBadge = '<span class="badge-flag high">↑ High</span>';
                        } elseif ($statusStr === 'low' || str_contains($statusStr, 'low')) {
                            $valClass = 'val-low';
                            $flagBadge = '<span class="badge-flag low">↓ Low</span>';
                        } elseif ($statusStr === 'abnormal') {
                            $valClass = 'val-abnormal';
                            $flagBadge = '<span class="badge-flag high">Abnormal</span>';
                        }
                    @endphp
                    <div class="test-card">
                        <div class="test-main-row">
                            <div class="test-name">{{ is_numeric($tName) ? 'Test #' . ($tName + 1) : $tName }}</div>
                            <div class="test-value-cell">
                                <span class="{{ $valClass }}">{{ $valStr }}</span>
                                {!! $flagBadge !!}
                                @if($unitStr) <span class="unit-label">{{ $unitStr }}</span> @endif
                            </div>
                            <div class="test-value-cell right"></div>
                        </div>
                        @if($refStr)
                        <div class="test-meta-row">
                            <div class="meta-label">Normal Range :</div>
                            <div class="meta-conv-range">{{ $refStr }}</div>
                            <div class="meta-si-range"></div>
                        </div>
                        @endif
                        <div class="test-device-row">By Cobas Integra 400 plus (Roche Diagnostics)</div>
                    </div>
                @endforeach
            @else
                <div style="text-align: center; padding: 40px; color: #888;">
                    No laboratory test results recorded for this request yet.
                </div>
            @endif
        @endif

        <!-- Footer: Always at the bottom of every page on print and bottom on screen -->
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
                📍 بغداد - الحارثية - شارع الكندي &nbsp;|&nbsp; 📞 +964 (0) 778 050 7060 &nbsp;|&nbsp; 📧 info@alkafaathospital.com
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
                localStorage.setItem('lab_print_mode', mode);
            } catch (e) {}
        }

        function changeTopMargin(val) {
            val = parseInt(val) || 45;
            document.documentElement.style.setProperty('--letterhead-top', val + 'mm');
            try {
                localStorage.setItem('lab_print_top_margin', val);
            } catch (e) {}
        }

        // Initialize user preference on load
        (function() {
            let savedMode = 'letterhead';
            let savedTop = 45;
            try {
                savedMode = localStorage.getItem('lab_print_mode') || 'letterhead';
                savedTop = parseInt(localStorage.getItem('lab_print_top_margin')) || 45;
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
