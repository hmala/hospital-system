<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical Laboratory Report (Emergency STAT) - {{ $emergencyLab->patient?->user?->name ?? 'Patient' }}</title>
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

        /* Print Toolbar */
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

        /* Official Hospital Header */
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
            font-size: 12.5px;
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

        /* Patient Info Card (3-Zone Grey Clinical Card with QR) */
        .patient-info-card {
            background-color: #f8fafc;
            border: 1.2px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            margin-bottom: 8px;
            display: grid;
            grid-template-columns: 1.35fr 1.25fr 85px;
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
            font-size: 8.5px;
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

        .badge-coverage {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 10.5px;
            font-weight: 800;
        }
        .badge-stat {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
        }
        .badge-insurance {
            background-color: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
        .badge-cash {
            background-color: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
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
            font-size: 10.5px;
            padding: 1px 6px;
            border-radius: 3px;
            min-width: 88px;
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
            font-size: 12.5px;
            font-family: 'Tahoma', Arial, sans-serif;
            direction: rtl;
        }

        .val-english-name {
            color: #334155;
            font-weight: bold;
            font-size: 11.5px;
        }

        .info-val-text {
            color: #0f172a;
            font-weight: bold;
            font-size: 11px;
        }

        /* Test Table Column Headers */
        .tests-header-bar {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            border-top: 1.5px solid #2b5899;
            border-bottom: 1.5px solid #2b5899;
            padding: 4px 4px;
            margin-bottom: 4px;
        }

        .header-col {
            color: #1e4b88;
            font-weight: bold;
            font-size: 12.5px;
        }

        .header-col.center {
            text-align: center;
        }

        .header-col.right {
            text-align: center;
            padding-right: 0;
        }

        /* Test Item Card */
        .test-card {
            padding: 4px 4px 5px 4px;
            border-bottom: 1px solid #e1e4e8;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .test-card:last-child {
            border-bottom: 1.5px solid #2b5899;
        }

        .test-main-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            align-items: flex-start;
            margin-bottom: 2px;
        }

        .test-name {
            font-weight: bold;
            color: #000;
            font-size: 12.5px;
        }

        .test-value-cell {
            text-align: center;
            font-size: 12.5px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }

        .current-val-line {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
        }

        .test-value-cell.right {
            text-align: center;
            padding-right: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .val-normal {
            color: #1e4b88;
            font-weight: bold;
            font-size: 13px;
        }

        .val-high {
            display: inline-block;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fca5a5;
            font-weight: bold;
            font-size: 12.5px;
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
            font-size: 12.5px;
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
            font-size: 12.5px;
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
            font-size: 11px;
            padding: 1px 10px;
            border-radius: 3px;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-low {
            display: inline-block;
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fcd34d;
            font-weight: 800;
            font-size: 11px;
            padding: 1px 10px;
            border-radius: 3px;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .val-status-normal {
            display: inline-block;
            background: #f0fdf4;
            color: #15803d;
            border: 1px solid #bbf7d0;
            font-weight: 700;
            font-size: 11px;
            padding: 1px 10px;
            border-radius: 3px;
            letter-spacing: 0.4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .unit-label {
            color: #333;
            font-size: 11.5px;
            margin-left: 4px;
        }

        .test-meta-row {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            align-items: baseline;
            font-size: 11.5px;
            color: #444;
            margin-bottom: 1px;
        }

        .meta-label {
            font-weight: 600;
            color: #333;
        }

        .meta-conv-range {
            text-align: center;
            color: #444;
            font-size: 11.5px;
        }

        .meta-status-blank {
            display: block;
        }

        .test-device-row {
            font-size: 10.5px;
            color: #1e4b88;
            margin-top: 1px;
        }

        /* Category Header */
        .category-header-row {
            background-color: #f0f4f8;
            color: #005a9c;
            font-weight: 800;
            font-size: 11.5px;
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

        /* General Urine Examination Dedicated Page & Section Styles */
        .gue-page-break {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px dashed #0284c7;
        }

        .gue-banner-header {
            background-color: #005a9c;
            color: #ffffff;
            font-size: 12.5px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 4px;
            text-align: center;
            letter-spacing: 0.6px;
            margin-bottom: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .gue-table-container {
            width: 100%;
            margin-bottom: 6px;
        }

        .gue-table-header {
            display: grid;
            grid-template-columns: 2.2fr 1.3fr 1.3fr 0.9fr;
            border-top: 1.5px solid #2b5899;
            border-bottom: 1.5px solid #2b5899;
            padding: 3px 4px;
            font-weight: 800;
            color: #1e4b88;
            font-size: 11.5px;
        }

        .gue-table-row {
            display: grid;
            grid-template-columns: 2.2fr 1.3fr 1.3fr 0.9fr;
            align-items: center;
            padding: 2px 4px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
            page-break-inside: avoid;
            break-inside: avoid;
            min-height: 19px;
        }

        .gue-table-row:nth-child(even) {
            background-color: #f8fafc;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .gue-table-row:last-child {
            border-bottom: 1.5px solid #2b5899;
        }

        .gue-col-desc {
            font-weight: 700;
            color: #0f172a;
        }

        .gue-col-val {
            text-align: center;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
        }

        .gue-col-ref {
            text-align: center;
            color: #475569;
            font-size: 11px;
            font-weight: 600;
        }

        .gue-col-status {
            text-align: center;
        }

        /* Signatures Grid */
        .auth-block {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 12px;
            background-color: #f8fafc;
            margin-top: 8px;
            margin-bottom: 4px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 70px;
            gap: 10px;
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
            height: 22px;
            border-bottom: 1.5px dashed #005a9c;
            margin-bottom: 2px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            color: #94a3b8;
            font-size: 9.5px;
            font-style: italic;
        }
        .sig-title {
            font-size: 10.5px;
            font-weight: 800;
            color: #005a9c;
            margin-bottom: 1px;
            font-family: 'Cairo', 'Tajawal', sans-serif;
        }
        .sig-subtitle {
            font-size: 9.5px;
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
            padding: 2px;
        }

        /* Footer */
        .report-footer-wrapper {
            width: 100%;
            margin-top: 6px;
            z-index: 10;
        }

        .report-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
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
            font-weight: 600;
            color: #444;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            letter-spacing: 0.3px;
        }

        .hospital-contact-line {
            text-align: center;
            font-size: 9px;
            color: #555;
            margin-top: 3px;
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
                page-break-after: always !important;
                break-after: page !important;
                border: 1.5px solid #005a9c !important;
                border-radius: 4px !important;
            }

            .report-page:last-child {
                page-break-after: auto !important;
                break-after: auto !important;
            }

            body.letterhead-mode .report-page {
                border: none !important;
                padding-top: var(--letterhead-top, 45mm) !important;
                padding-bottom: var(--letterhead-bottom, 25mm) !important;
            }

            .gue-page-break {
                page-break-before: always !important;
                break-before: page !important;
                margin-top: 0 !important;
                padding-top: 0 !important;
                border-top: none !important;
            }
        }
    </style>
</head>
<body>

    @php
        $emergencyDetails = is_string($emergencyLab->details) ? (json_decode($emergencyLab->details, true) ?? []) : ($emergencyLab->details ?? []);
        if (!is_array($emergencyDetails)) $emergencyDetails = [];
        $hasGeneralAtt = !empty($emergencyDetails['attachment']);
        $testAtts = is_array($emergencyDetails['test_attachments'] ?? null) ? $emergencyDetails['test_attachments'] : [];
        $waShareUrl = method_exists($emergencyLab, 'getWhatsAppShareUrl') ? $emergencyLab->getWhatsAppShareUrl() : null;

        // Logo Base64 Generator
        $hospitalLogoPath = public_path('images/1.jpg');
        if (!file_exists($hospitalLogoPath)) {
            $hospitalLogoPath = public_path('images/hospital-logo.svg');
        }
        $hospitalLogoSrc = asset('images/1.jpg');
        if (file_exists($hospitalLogoPath)) {
            $ext = pathinfo($hospitalLogoPath, PATHINFO_EXTENSION);
            $mime = ($ext === 'svg') ? 'image/svg+xml' : (($ext === 'png') ? 'image/png' : 'image/jpeg');
            $hospitalLogoSrc = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($hospitalLogoPath));
        }

        // Helper: Arabic to English Transliteration
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

        // Helper: Evaluate Value Against Reference Range
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
                    if (preg_match('/([+\-]?\d+(?:\.\d+)?)\s*(?:-|to|\.\.)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $min = (float)$m[1];
                        $max = (float)$m[2];
                        if ($min > $max) { $t = $min; $min = $max; $max = $t; }
                        if ($numVal < $min) return 'low';
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
                    if (preg_match('/(?:<|<=|≤)\s*([+\-]?\d+(?:\.\d+)?)/i', $ref, $m)) {
                        $max = (float)$m[1];
                        if ($numVal > $max) return 'high';
                        return 'normal';
                    }
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

        // Demographics Preparation
        $patientModel = $emergencyLab->patient;
        $patientUser = $patientModel?->user;
        $emergency = $emergencyLab->emergency;
        $doctorUser = $emergency?->doctor?->user;

        $rawName = $patientUser?->name ?? ($patientModel?->name ?? 'Patient');
        $arabicName = $rawName;
        $englishName = transliterateArabicName($rawName);

        $patientAge = $patientModel?->age ?? ($patientUser?->date_of_birth ? \Carbon\Carbon::parse($patientUser->date_of_birth)->age : 30);
        $rawGender = trim(strtolower((string)($patientUser?->gender ?? $patientModel?->gender ?? 'male')));
        if (in_array($rawGender, ['female', 'f', 'أنثى', 'انثى'], true)) {
            $genderEn = 'Female';
            $patientGender = 'female';
        } elseif (in_array($rawGender, ['male', 'm', 'ذكر'], true)) {
            $genderEn = 'Male';
            $patientGender = 'male';
        } else {
            $genderEn = '—';
            $patientGender = 'male';
        }

        $rawDoctor = $doctorUser?->name ?? '';
        $doctorEn = $rawDoctor ? (str_starts_with($rawDoctor, 'د.') || str_starts_with($rawDoctor, 'الدكتور') ? transliterateArabicName($rawDoctor) : 'Dr. ' . transliterateArabicName($rawDoctor)) : 'Emergency On-Duty Physician';

        $patientPhone = $emergencyLab->getPatientPhone() ?: ($patientUser?->phone ?? '—');
        $isInsured = ($patientModel?->insurance_type && $patientModel->insurance_type !== 'none') || !empty($patientModel?->insurance_card_no);
        $verifyUrl = route('staff.emergency-lab.print', $emergencyLab);

        $sampleNo = 'EM-IQ' . ($emergencyLab->requested_at ? $emergencyLab->requested_at->format('y') : date('y')) . '/' . str_pad($emergencyLab->emergency_id ?: $emergencyLab->id, 7, '0', STR_PAD_LEFT);
        $sampleDate = $emergencyLab->requested_at ? $emergencyLab->requested_at->format('d-M-Y') : date('d-M-Y');
        $sampleTime = $emergencyLab->requested_at ? $emergencyLab->requested_at->format('H:i A') : date('H:i A');

        // Results Aggregator
        $incomingTestResults = $emergencyDetails['test_results'] ?? [];
        $allResults = collect();
        $seenKeys = [];

        $addResultItem = function($testName, $parentTestName, $value, $unit, $refRange, $status, $notes = '', $hasImage = false, $imagePath = null, $category = null, $labTestId = null) use (&$allResults, &$seenKeys) {
            $normKey = mb_strtolower(trim(($parentTestName ? $parentTestName . '::' : '') . $testName));
            if (isset($seenKeys[$normKey])) {
                $existing = $allResults->first(fn($x) => mb_strtolower(trim(($x->parent_test_name ? $x->parent_test_name . '::' : '') . $x->test_name)) === $normKey);
                if ($existing && empty($existing->value) && !empty($value)) {
                    $existing->value = $value;
                    if ($unit) $existing->unit = $unit;
                    if ($refRange) $existing->reference_range = $refRange;
                    if ($status) $existing->status = $status;
                    if ($notes) $existing->notes = $notes;
                }
                return;
            }
            $seenKeys[$normKey] = true;

            $obj = new \stdClass();
            $obj->test_name = $testName;
            $obj->parent_test_name = $parentTestName;
            $obj->value = $value;
            $obj->unit = $unit;
            $obj->reference_range = $refRange;
            $obj->status = $status;
            $obj->notes = $notes;
            $obj->has_image = $hasImage;
            $obj->image_path = $imagePath;
            $obj->category = $category ?: ($parentTestName ?: 'General Clinical Laboratory');
            $obj->lab_test_id = $labTestId;
            $allResults->push($obj);
        };

        // 1. From details['test_results']
        if (is_array($incomingTestResults) && count($incomingTestResults) > 0) {
            foreach ($incomingTestResults as $k => $d) {
                $tName = $d['test_name'] ?? $k;
                $pName = $d['parent_test_name'] ?? null;
                $val = trim((string)($d['value'] ?? ''));
                $unit = trim((string)($d['unit'] ?? ''));
                $ref = trim((string)($d['reference_range'] ?? $d['reference'] ?? ''));
                $st = $d['status'] ?? 'normal';
                $notes = $d['notes'] ?? '';
                $labTestId = $d['lab_test_id'] ?? null;
                $addResultItem($tName, $pName, $val, $unit, $ref, $st, $notes, false, null, null, $labTestId);
            }
        }

        // 2. From emergency_lab_request_tests
        foreach ($emergencyLab->requestTests as $rt) {
            $lt = $rt->labTest;
            if (!$lt) continue;

            $rawResult = $rt->result;
            $data = is_string($rawResult) ? json_decode($rawResult, true) : $rawResult;

            if (is_array($data)) {
                $hasSubKeys = false;
                foreach ($data as $subKey => $subVal) {
                    if (is_array($subVal) && (isset($subVal['value']) || isset($subVal['test_name']))) {
                        $hasSubKeys = true;
                        $sName = $subVal['test_name'] ?? $subKey;
                        $sVal = trim((string)($subVal['value'] ?? ''));
                        $sUnit = trim((string)($subVal['unit'] ?? ''));
                        $sRef = trim((string)($subVal['reference_range'] ?? $subVal['reference'] ?? ''));
                        $sStatus = $subVal['status'] ?? 'normal';
                        $sNotes = $subVal['notes'] ?? '';
                        $addResultItem($sName, $lt->name, $sVal, $sUnit, $sRef, $sStatus, $sNotes, !empty($rt->image_path), $rt->image_path, $lt->main_category, $lt->id);
                    }
                }

                if (!$hasSubKeys) {
                    $val = trim((string)($data['value'] ?? ''));
                    $unit = trim((string)($data['unit'] ?? ($lt->unit ?? '')));
                    $ref = trim((string)($data['reference'] ?? $data['reference_range'] ?? ''));
                    if (empty($ref)) {
                        $refObj = $lt->referenceForPatient($patientGender, (int)$patientAge) ?? $lt->references->first();
                        $ref = $refObj?->range_display ?? (new \App\Models\LabResult())->getReferenceRange($lt->name);
                    }
                    if (empty($unit)) {
                        $unit = $lt->unit ?: ((new \App\Models\LabResult())->getUnit($lt->name));
                    }
                    $st = $data['status'] ?? 'normal';
                    $notes = $data['notes'] ?? '';
                    $addResultItem($lt->name, null, $val, $unit, $ref, $st, $notes, !empty($rt->image_path), $rt->image_path, $lt->main_category, $lt->id);
                }
            } else {
                $val = trim((string)$rawResult);
                $refObj = $lt->referenceForPatient($patientGender, (int)$patientAge) ?? $lt->references->first();
                $ref = $refObj?->range_display ?? (new \App\Models\LabResult())->getReferenceRange($lt->name);
                $unit = $lt->unit ?: ((new \App\Models\LabResult())->getUnit($lt->name));
                $addResultItem($lt->name, null, $val, $unit, $ref, 'normal', '', !empty($rt->image_path), $rt->image_path, $lt->main_category, $lt->id);
            }
        }

        // 3. From emergencyLab->labTests
        foreach ($emergencyLab->labTests as $t) {
            if ($t->subTests && $t->subTests->count() > 0) {
                foreach ($t->subTests as $st) {
                    $addResultItem($st->name, $t->name, '', $st->unit, $st->reference_range, 'normal', '', false, null, $t->main_category, $t->id);
                }
            } else {
                $refObj = $t->referenceForPatient($patientGender, (int)$patientAge) ?? $t->references->first();
                $ref = $refObj?->range_display ?? (new \App\Models\LabResult())->getReferenceRange($t->name);
                $unit = $t->unit ?: ((new \App\Models\LabResult())->getUnit($t->name));
                $addResultItem($t->name, null, '', $unit, $ref, 'normal', '', false, null, $t->main_category, $t->id);
            }
        }

        // Partitioning: General Urine Examination (G.U.E) Separation
        $isGeneralUrine = function($item) {
            $parentName = is_object($item) ? ($item->parent_test_name ?? '') : (is_array($item) ? ($item['parent_test_name'] ?? '') : '');
            $testName = is_object($item) ? ($item->test_name ?? '') : (is_array($item) ? ($item['name'] ?? $item['test_name'] ?? '') : (string)$item);
            $check = mb_strtolower(trim($parentName . ' ' . $testName));
            return str_contains($check, 'general urine') || str_contains($check, 'g.u.e') || $check === 'gue' || str_contains($check, 'فحص الإدرار العام') || str_contains($check, 'تحليل البول العام') || str_contains($check, 'فحص البول العام');
        };

        $standardResults = $allResults->filter(fn($r) => !$isGeneralUrine($r));
        $urineResults = $allResults->filter(fn($r) => $isGeneralUrine($r));

        $hasStandardContent = ($standardResults->count() > 0);
        $hasUrineContent = ($urineResults->count() > 0);
    @endphp

    <!-- Print Action Toolbar -->
    <div class="print-toolbar">
        <button class="toolbar-btn btn-print" onclick="window.print()">
            🖨️ طباعة التقرير (Print)
        </button>

        @if($waShareUrl)
            <a href="{{ $waShareUrl }}" target="_blank" class="toolbar-btn" style="background: #25d366; color: #fff;" title="إرسال رابط التقرير الطبي المعتمد للمريض عبر واتساب">
                💬 إرسال واتساب للمريض
            </a>
        @endif

        @if($hasGeneralAtt)
            <a href="{{ asset('storage/' . $emergencyDetails['attachment']) }}" target="_blank" class="toolbar-btn" style="background: #0284c7; color: #fff; font-size: 11.5px; padding: 6px 12px;">
                📄 تقرير الجهاز المرفق
            </a>
        @endif

        @foreach($testAtts as $tName => $attPath)
            <a href="{{ asset('storage/' . $attPath) }}" target="_blank" class="toolbar-btn" style="background: #0d9488; color: #fff; font-size: 11.5px; padding: 6px 12px;">
                🔬 مرفق: {{ $tName }}
            </a>
        @endforeach

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

        <button class="toolbar-btn btn-close-window" onclick="window.close();">
            ✕ إغلاق
        </button>
    </div>

    {{-- =========================================================================
         PAGE 1: Standard Laboratory Tests (or Standalone G.U.E if it's the only test)
         ========================================================================= --}}
    @if($hasStandardContent || $hasUrineContent)
    <div class="report-page page-1">
        <div class="report-main-content">
            <!-- Full Official Hospital Header -->
            <div class="report-header" style="direction: rtl;">
                <div class="header-hospital-ar">
                    <h2>مستشفى الكفاءات الأهلي</h2>
                    <h4>قسم المختبر والتحاليل الطبية السريرية</h4>
                    <p>الفحوصات السريرية والتشخيصية المعتمدة • قسم الطوارئ (STAT)</p>
                </div>
                <div class="header-logo">
                    <img src="{!! $hospitalLogoSrc !!}" 
                         alt="Hospital Logo" 
                         class="hospital-logo-img" 
                         onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                         style="max-height: 80px; max-width: 100px; object-fit: contain;">
                </div>
                <div class="header-hospital-en">
                    <h2>AL-KAFAAT PRIVATE HOSPITAL</h2>
                    <h4>Clinical Laboratories & Diagnostic Center</h4>
                    <p>Emergency Department • 24/7 STAT Laboratory Services</p>
                </div>
            </div>

            <!-- Document Title Banner -->
            <div class="document-title-banner">
                <h3>
                    @if($hasStandardContent)
                        CLINICAL LABORATORY REPORT (EMERGENCY STAT)
                    @else
                        CLINICAL LABORATORY REPORT (EMERGENCY STAT) - GENERAL URINE EXAMINATION (G.U.E)
                    @endif
                </h3>
                <div class="report-code-badge">
                    EM-LAB #{{ $emergencyLab->emergency_id ?: $emergencyLab->id }} • {{ strtoupper($emergencyLab->priority) }}
                </div>
            </div>

            <!-- Patient Info Card (3-Zone Grey Clinical Card with QR) -->
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
                        <span class="info-val-text">
                            {{ $patientAge ? $patientAge . ' Year(s)' : '—' }} &nbsp;•&nbsp; 
                            <strong style="color: #005a9c;">{{ $genderEn }}</strong>
                            @if($patientModel?->blood_type)
                                &nbsp;•&nbsp; <span style="color: #b91c1c; font-weight: 800;">(Blood: {{ $patientModel->blood_type }})</span>
                            @endif
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Phone No.</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text" style="direction: ltr; text-align: right;">{{ $patientPhone ?: '—' }}</span>
                    </div>
                </div>

                <!-- Zone 2: Order, Doctor & STAT Priority -->
                <div class="patient-zone-order">
                    <div class="info-row">
                        <span class="info-pill">Referred By</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text">{{ $doctorEn ?: 'Emergency Physician' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Sample No.</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text" style="color: #005a9c; font-weight: 800;">{{ $sampleNo }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Date / Time</span>
                        <span class="info-colon">:</span>
                        <span class="info-val-text">{{ $sampleDate }} &nbsp;•&nbsp; {{ $sampleTime }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-pill">Coverage</span>
                        <span class="info-colon">:</span>
                        <span class="badge-coverage badge-stat me-1">🔴 طوارئ STAT</span>
                        @if($isInsured)
                            <span class="badge-coverage badge-insurance">🛡️ الضمان الصحي</span>
                        @else
                            <span class="badge-coverage badge-cash">💵 حساب نقدي</span>
                        @endif
                    </div>
                </div>

                <!-- Zone 3: Direct QR Code -->
                <div class="patient-zone-qr">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" class="qr-img">
                        <span class="qr-tag">STAT #{{ $emergencyLab->emergency_id ?: $emergencyLab->id }}</span>
                        <span class="qr-caption">مسح للتحقق</span>
                    </div>
                </div>
            </div>

            @if($hasStandardContent)
                <!-- Standard Tests Column Headers -->
                <div class="tests-header-bar">
                    <div class="header-col">INVESTIGATION / TEST NAME</div>
                    <div class="header-col center">Conventional Units / Result</div>
                    <div class="header-col right">Status / Flag</div>
                </div>

                @php $currentGroup = null; @endphp
                <div class="standard-tests-section">
                    @foreach($standardResults as $res)
                        @php
                            $parentName = $res->parent_test_name;
                            $val = trim($res->value ?? '');
                            $unit = trim($res->unit ?? '');
                            $refRange = trim($res->reference_range ?? '');

                            $displayName = !empty($parentName) ? ($parentName . ' — ' . $res->test_name) : $res->test_name;
                            $headerTitle = !empty($parentName) ? $parentName : ($res->category ?? 'General Laboratory Tests');

                            if ($headerTitle !== $currentGroup) {
                                $currentGroup = $headerTitle;
                                $showHeader = true;
                            } else {
                                $showHeader = false;
                            }

                            $status = calcLabStatus($val, $refRange, $res->status ?? 'normal');
                            $valClass = 'val-normal';
                            if ($status === 'high' || $status === 'positive' || str_contains($status, 'high') || str_contains($status, 'pos') || $status === '+') {
                                $valClass = 'val-high';
                            } elseif ($status === 'low' || str_contains($status, 'low') || $status === '-') {
                                $valClass = 'val-low';
                            } elseif ($status === 'abnormal') {
                                $valClass = 'val-abnormal';
                            }
                        @endphp

                        @if(!empty($showHeader))
                            <div class="category-header-row">
                                {{ strtoupper($currentGroup) }}
                            </div>
                        @endif

                        <div class="test-card">
                            <div class="test-main-row">
                                <div class="test-name">
                                    {{ $displayName }}
                                    @if(!empty($res->has_image))
                                        <small style="color: #0284c7; font-size: 8.5px; margin-left: 4px;">[📎 تقرير الجهاز المرفق]</small>
                                    @endif
                                    @if(!empty($res->notes))
                                        <div style="font-size: 9px; color: #475569; font-style: italic; margin-top: 1px;">
                                            • {{ $res->notes }}
                                        </div>
                                    @endif
                                </div>
                                <div class="test-value-cell">
                                    <div class="current-val-line">
                                        <span class="{{ $valClass }}">{{ $val !== '' ? $val : 'Pending' }}</span>
                                        @if($unit)
                                            <span class="unit-label">{{ $unit }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="test-value-cell right">
                                    @if($status === 'high' || $status === 'positive' || str_contains($status, 'high') || str_contains($status, 'pos') || $status === '+')
                                        <span class="val-status-high">High</span>
                                    @elseif($status === 'low' || str_contains($status, 'low') || $status === '-')
                                        <span class="val-status-low">Low</span>
                                    @elseif($status === 'abnormal')
                                        <span class="val-status-high">Abnormal</span>
                                    @elseif($val !== '' && $val !== '—')
                                        <span class="val-status-normal">Normal</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>

                            @if($refRange)
                                <div class="test-meta-row">
                                    <div class="meta-label">Reference Range :</div>
                                    <div class="meta-conv-range">{{ $refRange }}</div>
                                    <div class="meta-status-blank"></div>
                                </div>
                            @endif

                            <div class="test-device-row">
                                STAT Emergency Lab Diagnostic System • Rapid Automated Analysis
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($hasUrineContent)
                {{-- Standalone G.U.E on Page 1 --}}
                <div class="gue-banner-header" style="margin-top: 10px; margin-bottom: 6px;">
                    GENERAL URINE EXAMINATION (G.U.E) - فحص الإدرار العام
                </div>

                <div class="gue-table-container">
                    <div class="gue-table-header">
                        <div class="gue-col-desc">INVESTIGATION / TEST NAME</div>
                        <div class="gue-col-val">Result</div>
                        <div class="gue-col-ref">Reference Range</div>
                        <div class="gue-col-status">Status</div>
                    </div>

                    @foreach($urineResults as $uRes)
                        @php
                            $uVal = trim($uRes->value ?? '');
                            $uUnit = trim($uRes->unit ?? '');
                            $uRef = trim($uRes->reference_range ?? '');
                            $uStatus = calcLabStatus($uVal, $uRef, $uRes->status ?? 'normal');

                            $uClass = 'val-normal';
                            if ($uStatus === 'high' || $uStatus === 'positive' || str_contains($uStatus, 'high') || str_contains($uStatus, 'pos') || $uStatus === '+') {
                                $uClass = 'val-high';
                            } elseif ($uStatus === 'low' || str_contains($uStatus, 'low') || $uStatus === '-') {
                                $uClass = 'val-low';
                            } elseif ($uStatus === 'abnormal') {
                                $uClass = 'val-abnormal';
                            }
                        @endphp
                        <div class="gue-table-row">
                            <div class="gue-col-desc">
                                {{ $uRes->test_name }}
                                @if(!empty($uRes->notes))
                                    <small style="color: #64748b; font-size: 8.5px; font-style: italic;">({{ $uRes->notes }})</small>
                                @endif
                            </div>
                            <div class="gue-col-val">
                                <span class="{{ $uClass }}">{{ $uVal !== '' ? $uVal : '—' }}</span>
                                @if($uUnit)
                                    <span class="unit-label" style="font-size: 10px;">{{ $uUnit }}</span>
                                @endif
                            </div>
                            <div class="gue-col-ref">
                                {{ $uRef ?: '—' }}
                            </div>
                            <div class="gue-col-status">
                                @if($uStatus === 'high' || $uStatus === 'positive' || str_contains($uStatus, 'high') || str_contains($uStatus, 'pos') || $uStatus === '+')
                                    <span class="val-status-high" style="font-size: 9px; padding: 0 6px;">Abnormal</span>
                                @elseif($uStatus === 'low' || str_contains($uStatus, 'low') || $uStatus === '-')
                                    <span class="val-status-low" style="font-size: 9px; padding: 0 6px;">Low</span>
                                @elseif($uVal !== '' && $uVal !== '—')
                                    <span class="val-status-normal" style="font-size: 9px; padding: 0 6px;">Normal</span>
                                @else
                                    <span class="text-muted" style="font-size: 10px;">—</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if($emergencyLab->notes)
                <div style="background: #f8fafc; border: 1.2px solid #cbd5e1; border-radius: 6px; padding: 6px 12px; margin-top: 10px; font-size: 10.5px;">
                    <strong style="color: #005a9c;"><i class="fas fa-sticky-note me-1"></i> Clinical Notes & Observations:</strong>
                    <div style="color: #334155; margin-top: 2px;">{{ $emergencyLab->notes }}</div>
                </div>
            @endif
        </div>

        <!-- Official Signatures Block -->
        <div class="report-bottom-block">
            <div class="auth-block">
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title">Laboratory Technician</div>
                        <div class="sig-subtitle">{{ $emergencyLab->performedByUser?->name ?? 'Medical Lab Technologist' }}</div>
                        <div class="sig-empty-line">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title">Emergency Physician</div>
                        <div class="sig-subtitle">{{ $doctorUser?->name ? 'Dr. ' . $doctorUser->name : 'Attending Emergency Doctor' }}</div>
                        <div class="sig-empty-line">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title">Consultant / Lab Specialist</div>
                        <div class="sig-subtitle">{{ $emergencyLab->verifiedByUser?->name ?? 'Head of Clinical Pathology' }}</div>
                        <div class="sig-empty-line">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="stamp-circle-box">
                        <div style="font-size: 7px; font-weight: 800; color: #005a9c;">★ ★ ★</div>
                        <div style="font-size: 8px; font-weight: 900; line-height: 1.1;">مستشفى الكفاءات</div>
                        <div style="font-size: 7px; font-weight: 800; color: #0284c7;">مختبر الطوارئ</div>
                        <div style="font-size: 7px; color: #b91c1c; font-weight: 900; margin-top: 1px;">مُعتمَــد</div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="report-footer-wrapper">
                <div class="report-footer">
                    <div class="footer-printed-by">
                        Printed by: <span>{{ auth()->user()->name ?? 'موظف المختبر' }}</span>
                    </div>
                    <div>
                        Verification Code: <strong style="color: #005a9c;">EM-LAB-{{ $emergencyLab->id }}-{{ $emergencyLab->requested_at?->format('ymd') }}</strong>
                    </div>
                    <div class="footer-datetime">
                        <span>{{ now()->format('d-m-Y h:i:sA') }}</span>
                    </div>
                </div>

                <div class="hospital-contact-line">
                    📍 النجف الأشرف - حي الفرات - شارع الكوفة &nbsp;|&nbsp; 📞 +964 (0) 778 050 7060 &nbsp;|&nbsp; 📧 info@alkafaathospital.com
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- =========================================================================
         PAGE 2: Dedicated General Urine Examination (G.U.E) Page
         (Only rendered when both Standard Tests and G.U.E tests are present!)
         ========================================================================= --}}
    @if($hasUrineContent && $hasStandardContent)
    <div class="report-page page-2 gue-page-break">
        <div class="report-main-content">
            <!-- Page 2 Compact Hospital Header for G.U.E -->
            <div class="report-header page2-header" style="direction: rtl; padding-bottom: 4px; margin-bottom: 6px;">
                <div class="header-hospital-ar">
                    <h2 style="font-size: 14px; margin: 0; color: #005a9c; font-weight: 900;">مستشفى الكفاءات الأهلي</h2>
                    <h4 style="font-size: 11px; margin: 0; color: #0284c7; font-weight: 800;">قسم المختبر والتحاليل الطبية • طوارئ STAT</h4>
                </div>
                <div class="header-logo" style="width: 70px; text-align: center;">
                    <img src="{!! $hospitalLogoSrc !!}" 
                         alt="Hospital Logo" 
                         class="hospital-logo-img" 
                         onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                         style="max-height: 48px; max-width: 65px; object-fit: contain;">
                </div>
                <div class="header-hospital-en">
                    <h2 style="font-size: 13px; margin: 0; color: #005a9c; font-weight: 900;">AL-KAFAAT PRIVATE HOSPITAL</h2>
                    <h4 style="font-size: 10px; margin: 0; color: #0284c7; font-weight: 800;">Clinical Laboratory Dept. • STAT</h4>
                </div>
            </div>

            <!-- Page 2 Compact Patient Bar -->
            <div class="patient-compact-bar" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 10px; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center; font-size: 11px;">
                <div><strong>Patient:</strong> <span style="color: #005a9c; font-weight: bold;">{{ $arabicName }}</span> <span style="color: #64748b;">({{ $englishName }})</span></div>
                <div><strong>Age / Sex:</strong> {{ $patientAge ? $patientAge . ' Y' : '—' }} &nbsp;|&nbsp; <strong style="color: #005a9c;">{{ $genderEn }}</strong></div>
                <div><strong>Sample No:</strong> <span style="color: #005a9c; font-weight: bold;">{{ $sampleNo }}</span></div>
                <div><strong>Date:</strong> {{ $sampleDate }}</div>
            </div>

            <!-- GUE Banner Header -->
            <div class="gue-banner-header" style="margin-bottom: 4px; padding: 3px 8px; font-size: 11.5px;">
                GENERAL URINE EXAMINATION (G.U.E) - فحص الإدرار العام
            </div>

            <!-- Compact GUE 4-Column Table -->
            <div class="gue-table-container">
                <div class="gue-table-header" style="padding: 2.5px 4px; font-size: 11px;">
                    <div class="gue-col-desc">INVESTIGATION / TEST NAME</div>
                    <div class="gue-col-val">Result</div>
                    <div class="gue-col-ref">Reference Range</div>
                    <div class="gue-col-status">Status</div>
                </div>

                @foreach($urineResults as $uRes)
                    @php
                        $uVal = trim($uRes->value ?? '');
                        $uUnit = trim($uRes->unit ?? '');
                        $uRef = trim($uRes->reference_range ?? '');
                        $uStatus = calcLabStatus($uVal, $uRef, $uRes->status ?? 'normal');

                        $uClass = 'val-normal';
                        if ($uStatus === 'high' || $uStatus === 'positive' || str_contains($uStatus, 'high') || str_contains($uStatus, 'pos') || $uStatus === '+') {
                            $uClass = 'val-high';
                        } elseif ($uStatus === 'low' || str_contains($uStatus, 'low') || $uStatus === '-') {
                            $uClass = 'val-low';
                        } elseif ($uStatus === 'abnormal') {
                            $uClass = 'val-abnormal';
                        }
                    @endphp
                    <div class="gue-table-row" style="padding: 1.5px 4px; min-height: 18px; font-size: 10.5px;">
                        <div class="gue-col-desc">
                            {{ $uRes->test_name }}
                            @if(!empty($uRes->notes))
                                <small style="color: #64748b; font-size: 8px; font-style: italic;">({{ $uRes->notes }})</small>
                            @endif
                        </div>
                        <div class="gue-col-val">
                            <span class="{{ $uClass }}">{{ $uVal !== '' ? $uVal : '—' }}</span>
                            @if($uUnit)
                                <span class="unit-label" style="font-size: 9.5px;">{{ $uUnit }}</span>
                            @endif
                        </div>
                        <div class="gue-col-ref">
                            {{ $uRef ?: '—' }}
                        </div>
                        <div class="gue-col-status">
                            @if($uStatus === 'high' || $uStatus === 'positive' || str_contains($uStatus, 'high') || str_contains($uStatus, 'pos') || $uStatus === '+')
                                <span class="val-status-high" style="font-size: 9px; padding: 0 4px;">Abnormal</span>
                            @elseif($uStatus === 'low' || str_contains($uStatus, 'low') || $uStatus === '-')
                                <span class="val-status-low" style="font-size: 9px; padding: 0 4px;">Low</span>
                            @elseif($uVal !== '' && $uVal !== '—')
                                <span class="val-status-normal" style="font-size: 9px; padding: 0 4px;">Normal</span>
                            @else
                                <span class="text-muted" style="font-size: 10px;">—</span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Pinned Bottom Block for Page 2 -->
        <div class="report-bottom-block">
            <div class="auth-block" style="padding: 4px 10px; margin-top: 4px; margin-bottom: 2px;">
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title" style="font-size: 9.5px;">Laboratory Technician</div>
                        <div class="sig-subtitle" style="font-size: 8.5px;">{{ $emergencyLab->performedByUser?->name ?? 'Medical Lab Technologist' }}</div>
                        <div class="sig-empty-line" style="height: 18px;">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title" style="font-size: 9.5px;">Emergency Physician</div>
                        <div class="sig-subtitle" style="font-size: 8.5px;">{{ $doctorUser?->name ? 'Dr. ' . $doctorUser->name : 'Attending Doctor' }}</div>
                        <div class="sig-empty-line" style="height: 18px;">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title" style="font-size: 9.5px;">Lab Specialist</div>
                        <div class="sig-subtitle" style="font-size: 8.5px;">{{ $emergencyLab->verifiedByUser?->name ?? 'Head of Pathology' }}</div>
                        <div class="sig-empty-line" style="height: 18px;">(Signature & Stamp)</div>
                    </div>
                </div>
                <div class="auth-cell">
                    <div class="stamp-circle-box" style="width: 52px; height: 52px; padding: 1px;">
                        <div style="font-size: 6px; font-weight: 800; color: #005a9c;">★ ★ ★</div>
                        <div style="font-size: 7.5px; font-weight: 900; line-height: 1.1;">مستشفى الكفاءات</div>
                        <div style="font-size: 6.5px; font-weight: 800; color: #0284c7;">مختبر الطوارئ</div>
                        <div style="font-size: 6.5px; color: #b91c1c; font-weight: 900;">مُعتمَــد</div>
                    </div>
                </div>
            </div>

            <!-- Pinned Footer on Page 2 -->
            <div class="report-footer-wrapper">
                <div class="report-footer" style="padding-top: 3px; font-size: 9.5px;">
                    <div class="footer-printed-by">
                        Printed by: <span>{{ auth()->user()->name ?? 'موظف المختبر' }}</span>
                    </div>
                    <div>
                        Verification Code: <strong style="color: #005a9c;">EM-LAB-{{ $emergencyLab->id }}-{{ $emergencyLab->requested_at?->format('ymd') }}</strong>
                    </div>
                    <div class="footer-datetime">
                        <span>{{ now()->format('d-m-Y h:i:sA') }}</span>
                    </div>
                </div>

                <div class="hospital-contact-line" style="font-size: 8.5px; margin-top: 2px;">
                    📍 النجف الأشرف - حي الفرات - شارع الكوفة &nbsp;|&nbsp; 📞 +964 (0) 778 050 7060 &nbsp;|&nbsp; 📧 info@alkafaathospital.com
                </div>
            </div>
        </div>
    </div>
    @endif

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
                localStorage.setItem('em_lab_print_mode_v2', mode);
            } catch (e) {}
        }

        function changeTopMargin(val) {
            val = parseInt(val) || 45;
            document.documentElement.style.setProperty('--letterhead-top', val + 'mm');
            try {
                localStorage.setItem('em_lab_print_top_margin', val);
            } catch (e) {}
        }

        (function() {
            let savedMode = 'full';
            let savedTop = 45;
            try {
                savedMode = localStorage.getItem('em_lab_print_mode_v2') || 'full';
                savedTop = parseInt(localStorage.getItem('em_lab_print_top_margin')) || 45;
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
