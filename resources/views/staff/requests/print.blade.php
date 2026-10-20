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
            padding: 10mm 15mm 14mm 15mm;
            position: relative;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
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

        /* Repeating Table Architecture for Multi-Page Printing */
        .report-print-container {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin: 0;
            padding: 0;
        }

        .report-print-container thead {
            display: table-header-group;
        }

        .report-print-container tfoot {
            display: table-footer-group;
        }

        .report-print-container tbody {
            display: table-row-group;
        }

        .print-table-cell-header,
        .print-table-cell-body,
        .print-table-cell-footer {
            border: none;
            padding: 0;
            margin: 0;
            vertical-align: top;
        }

        @media screen and (max-width: 768px) {
            body {
                padding: 6px;
            }
            .report-page {
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                padding: 12mm 6mm 25mm 6mm !important;
                box-shadow: none !important;
            }
            .print-toolbar {
                right: 50% !important;
                transform: translateX(50%) !important;
                width: 94% !important;
                justify-content: center !important;
                font-size: 12px !important;
            }
            .header-top-row {
                flex-direction: column !important;
                gap: 8px !important;
                text-align: center !important;
            }
            .hospital-logo-side, .hospital-title-side, .clinical-lab-center {
                min-width: 0 !important;
                text-align: center !important;
            }
            .hospital-logo-img {
                height: 55px !important;
            }
            .hospital-name-ar {
                font-size: 15px !important;
            }
            .clinical-lab-banner {
                font-size: 20px !important;
            }
            .patient-info-grid {
                grid-template-columns: 1fr !important;
                gap: 4px !important;
            }
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

        /* Decorative Border (Matching Emergency & Radiology) */
        .decorative-border {
            position: absolute;
            top: 6mm;
            right: 6mm;
            bottom: 6mm;
            left: 6mm;
            border: 1px solid #7dd3fc;
            pointer-events: none;
            z-index: 0;
            border-radius: 2px;
        }

        .corner-pattern {
            position: absolute;
            width: 45px;
            height: 45px;
            opacity: 0.15;
        }

        .corner-pattern.top-right {
            top: 0;
            right: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path d="M0,0 L100,0 L100,100 Z" fill="%23005a9c"/></svg>');
        }

        :root {
            --letterhead-top: 45mm;
            --letterhead-bottom: 25mm;
        }

        /* Letterhead Mode Styles (for pre-printed official hospital letterhead paper) */
        body.letterhead-mode .report-header,
        body.letterhead-mode .document-title-banner,
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

        /* Ensure Public View always shows the full header, borders, and footer regardless of print mode */
        body.is-public-report .report-header,
        body.is-public-report.letterhead-mode .report-header,
        body.is-public-report .document-title-banner,
        body.is-public-report.letterhead-mode .document-title-banner,
        body.is-public-report .decorative-border,
        body.is-public-report.letterhead-mode .decorative-border,
        body.is-public-report .corner-pattern,
        body.is-public-report.letterhead-mode .corner-pattern,
        body.is-public-report .hospital-contact-line,
        body.is-public-report.letterhead-mode .hospital-contact-line,
        body.is-public-report .report-footer-wrapper,
        body.is-public-report.letterhead-mode .report-footer-wrapper {
            display: block !important;
        }

        body.is-public-report.letterhead-mode .report-page {
            padding-top: 16mm !important;
            padding-bottom: 35mm !important;
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

        /* Header Layout (Matching Emergency & Radiology) */
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

        /* Document Title Banner (Matching Emergency & Radiology) */
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

        /* Patient Info Card (Upgraded 3-Zone Grey Clinical Card with QR) */
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
            height: 26px;
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
            width: 64px;
            height: 64px;
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

        /* Test Table Column Headers (Matching image double blue line) */
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

        .prev-vertical-box {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: 2px;
            padding-top: 2px;
            border-top: 1px dashed #cbd5e1;
            width: 88%;
            gap: 2px;
        }

        .prev-vertical-row {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            font-size: 10px;
            line-height: 1.2;
        }

        .prev-val-chip {
            font-size: 10.5px;
            font-weight: 800;
            color: #1e4b88;
        }

        .prev-val-chip.high {
            color: #b91c1c;
        }

        .prev-val-chip.low {
            color: #b45309;
        }

        .prev-date-text {
            font-size: 8.5px;
            color: #64748b;
            font-weight: 600;
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

        .badge-flag {
            font-size: 10.5px;
            font-weight: 800;
            margin-left: 4px;
            vertical-align: middle;
        }
        .badge-flag.high { color: #b91c1c; }
        .badge-flag.low { color: #b45309; }

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
            background-color: #f1f4f8;
            color: #1e4b88;
            font-weight: bold;
            font-size: 12px;
            padding: 4px 8px;
            margin-top: 6px;
            border-left: 3px solid #1e4b88;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            page-break-inside: avoid;
            break-inside: avoid;
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
            font-size: 10.5px;
            color: #333;
            font-family: 'Segoe UI', 'Tajawal', Tahoma, sans-serif;
        }

        .footer-printed-by {
            font-size: 10.5px;
            color: #333;
        }

        .footer-printed-by span {
            font-weight: 700;
            color: #1e3a5f;
        }

        .footer-datetime {
            font-size: 10.5px;
            font-weight: 600;
            color: #444;
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            letter-spacing: 0.3px;
        }

        .hospital-contact-line {
            text-align: center;
            font-size: 9.5px;
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
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-height: auto !important;
                position: relative !important;
                box-sizing: border-box !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                page-break-after: auto;
            }

            .decorative-border {
                display: none !important;
            }

            .report-page::before {
                position: absolute;
                inset: 0;
                opacity: 0.04;
            }

            .report-main-content {
                flex: 1 0 auto !important;
            }

            .report-bottom-block {
                margin-top: auto !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }

            .report-footer-wrapper {
                position: static !important;
                width: 100% !important;
                margin-top: 6px !important;
                z-index: 9999;
                background: transparent !important;
            }

            .test-card {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .patient-info-card, .patient-info-grid {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }

            .auth-block {
                break-inside: avoid !important;
                page-break-inside: avoid !important;
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
        }
    </style>
</head>
<body class="{{ !empty($isPublicView) ? 'is-public-report' : '' }}">

    @if(!empty($isPublicView))
        <!-- Public Patient Toolbar (Hidden during print) -->
        <div class="print-toolbar" style="background: rgba(16, 185, 129, 0.95); right: 50%; transform: translateX(50%); border-radius: 30px; padding: 8px 20px; gap: 15px;">
            <span style="color: #fff; font-weight: bold; font-size: 13.5px;">
                🏥 مستشفى الكفاءات الأهلي — التقرير الطبي المعتمد
            </span>
            <button class="toolbar-btn btn-print" onclick="window.print()" style="background: #ffffff; color: #047857; font-weight: 800; border-radius: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                📥 تحميل / حفظ كـ PDF
            </button>
        </div>
    @else
        <!-- Action Toolbar for Staff (Hidden during print) -->
        <div class="print-toolbar">
            <button class="toolbar-btn btn-print" onclick="window.print()">
                🖨️ طباعة التقرير
            </button>

            @can('send lab whatsapp')
                @php
                    $waShareUrl = method_exists($request, 'getWhatsAppShareUrl') ? $request->getWhatsAppShareUrl() : null;
                @endphp
                @if($waShareUrl)
                    <a href="{{ $waShareUrl }}" target="_blank" class="toolbar-btn" style="background: #25d366; color: #fff;" title="إرسال رابط التقرير الطبي المعتمد للمريض عبر واتساب">
                        💬 إرسال واتساب للمريض
                    </a>
                @endif
            @endcan

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
    @endif

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

        $patientModel = $request->visit?->patient ?? ($request->patient_id ? \App\Models\Patient::find($request->patient_id) : null);
        $patientUser = $patientModel?->user;

        $rawName = $patientUser?->name ?? ($patientModel?->name ?? 'Patient');
        $arabicName = $rawName;
        $englishName = transliterateArabicName($rawName);

        $patientAge = $patientModel?->age ?? ($patientUser?->date_of_birth ? \Carbon\Carbon::parse($patientUser->date_of_birth)->age : '');
        $rawGender = trim(strtolower((string)($patientUser?->gender ?? $patientModel?->gender ?? '')));
        if (in_array($rawGender, ['female', 'f', 'أنثى', 'انثى'], true)) {
            $genderEn = 'Female';
        } elseif (in_array($rawGender, ['male', 'm', 'ذكر'], true)) {
            $genderEn = 'Male';
        } else {
            $genderEn = '—';
        }

        $rawDoctor = $request->visit?->doctor?->user?->name ?? '';
        $doctorEn = $rawDoctor ? (str_starts_with($rawDoctor, 'د.') || str_starts_with($rawDoctor, 'الدكتور') ? transliterateArabicName($rawDoctor) : 'Dr. ' . transliterateArabicName($rawDoctor)) : '';

        $patientPhone = method_exists($request, 'getPatientPhone') ? $request->getPatientPhone() : ($patientUser?->phone ?? ($patientModel?->phone ?? '—'));
        $isInsured = ($patientModel?->insurance_type && $patientModel->insurance_type !== 'none') || ($request->insurance_type && $request->insurance_type !== 'none') || !empty($patientModel?->insurance_card_no) || !empty($patientModel?->insurance_number);
        $verifyUrl = method_exists($request, 'getPublicReportUrl') ? $request->getPublicReportUrl() : url('/lab/results/view/' . $request->id);

        $sampleNo = 'IQ' . ($request->created_at ? $request->created_at->format('y') : date('y')) . '/' . str_pad($request->id, 7, '0', STR_PAD_LEFT);
        $patientNo = ($request->created_at ? $request->created_at->format('y') : date('y')) . '/' . str_pad($request->visit?->patient_id ?? $request->patient_id ?? $request->id, 7, '0', STR_PAD_LEFT);

        $sampleDate = $request->created_at ? $request->created_at->format('d-M-Y') : date('d-M-Y');
        $sampleTime = $request->created_at ? $request->created_at->format('H:i A') : date('H:i A');

        $labResults = \App\Models\LabResult::where('request_id', $request->id)->orderBy('id')->get();
        $testsCount = $labResults->count();

        $prevResultsMap = method_exists($request, 'getPreviousLabResultsMap') ? $request->getPreviousLabResultsMap() : [];
        $findPrevResults = function($testName) use ($prevResultsMap) {
            $norm = mb_strtolower(trim((string)$testName));
            if (isset($prevResultsMap[$norm])) return $prevResultsMap[$norm];
            $clean = mb_strtolower(trim(preg_replace('/\s*\(.*?\)\s*/', '', (string)$testName)));
            if (!empty($clean) && isset($prevResultsMap[$clean])) return $prevResultsMap[$clean];
            foreach ($prevResultsMap as $k => $list) {
                if ($k === $norm || $k === $clean) return $list;
            }
            return [];
        };

        if ($testsCount === 0 && !empty($request->result)) {
            $parsedRes = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            $savedArr = $parsedRes['test_results'] ?? [];
            $testsCount = count($savedArr);
        }

        // إذا لم تكن هناك نتائج مدخلة بعد، نستخرج قائمة التحاليل المطلوبة (Requisition / Work Order)
        $requestedLabTestsList = [];
        $requestDetails = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($requestDetails)) $requestDetails = [];

        if (!empty($requestDetails['package_id'])) {
            $pkg = \App\Models\Package::find($requestDetails['package_id']);
            if ($pkg) {
                $requestedLabTestsList = array_merge($requestedLabTestsList, $pkg->labTests->toArray());
            }
        }

        if (!empty($requestDetails['lab_test_ids'])) {
            $rawIds = $requestDetails['lab_test_ids'];
            $ids = is_string($rawIds) ? (json_decode($rawIds, true) ?: explode(',', $rawIds)) : (is_array($rawIds) ? $rawIds : []);
            foreach ($ids as $testId) {
                $testId = trim((string)$testId);
                if ($testId === '') continue;
                $labTestModel = \App\Models\LabTest::find($testId);
                if ($labTestModel) {
                    $requestedLabTestsList[] = $labTestModel->toArray();
                }
            }
        }

        if (!empty($requestDetails['tests'])) {
            $rawTests = $requestDetails['tests'];
            $testsArr = is_string($rawTests) ? (json_decode($rawTests, true) ?: explode(',', $rawTests)) : (is_array($rawTests) ? $rawTests : []);
            foreach ($testsArr as $tItem) {
                if (is_array($tItem)) {
                    $requestedLabTestsList[] = $tItem;
                } elseif (is_string($tItem) && trim($tItem) !== '') {
                    $requestedLabTestsList[] = ['name' => trim($tItem)];
                }
            }
        }

        if ($testsCount === 0) {
            $testsCount = count($requestedLabTestsList);
        }

        $isBloodBankRequest = $isBloodBankRequest ?? ($request->type === 'blood_bank' || data_get($request->details, 'blood_bank', false));
        $badgeText = $isBloodBankRequest ? 'Blood Bank' : 'Clinical Laboratory';
        $badgeIcon = $isBloodBankRequest ? 'blood-bank-icon.svg' : 'lab-icon.svg';

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
    @endphp

    <div class="report-page">

        <!-- Decorative Border -->
        <div class="decorative-border">
            <div class="corner-pattern top-right"></div>
            <div class="corner-pattern bottom-left"></div>
        </div>

    <table class="report-print-container">
        <thead>
            <tr>
                <td class="print-table-cell-header">
                    <!-- Header Section (Matching Emergency & Radiology) -->
                    <div class="report-header" style="direction: rtl;">
                        <div class="header-hospital-ar">
                            <h2>مستشفى الكفاءات الأهلي</h2>
                            <h4>{{ $isBloodBankRequest ? 'قسم مصرف الدم ونقل الدم' : 'قسم المختبر والتحاليل الطبية' }}</h4>
                            <p>{{ $isBloodBankRequest ? 'خدمات فحص ومطابقة وتوفير الدم ومشتقاته' : 'الفحوصات السريرية والتشخيصية المعتمدة' }}</p>
                        </div>
                        <div class="header-logo">
                            <img src="{!! $hospitalLogoSrc !!}" 
                                 alt="Hospital Logo" 
                                 class="hospital-logo-img" 
                                 onerror="this.onerror=null; this.src='{{ asset('images/1.jpg') }}';" 
                                 style="max-height: 80px; max-width: 100px; object-fit: contain;">
                        </div>
                        <div class="header-hospital-en">
                            <h2>Al-Kafa'at Private Hospital</h2>
                            <h4>{{ $isBloodBankRequest ? 'Blood Bank & Transfusion Dept.' : 'Clinical Laboratory Dept.' }}</h4>
                            <p>{{ $isBloodBankRequest ? 'Blood Screening & Transfusion Services' : 'Diagnostic & Pathology Services' }}</p>
                        </div>
                    </div>

                    <!-- Document Title Banner (Matching Emergency & Radiology) -->
                    <div class="document-title-banner">
                        <h3>{{ $isBloodBankRequest ? 'استمارة فحص ومطابقة مصرف الدم (Blood Bank Report)' : 'تقرير نتائج الفحوصات المخبرية (Clinical Laboratory Report)' }}</h3>
                        <div class="report-code-badge">
                            LAB-#{{ $request->id }}
                        </div>
                    </div>

                    <!-- Patient Info Card (3-Zone Grey Clinical Card with QR) -->
                    <div class="patient-info-card">
                        <!-- Left Zone: Patient Demographics -->
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

                        <!-- Middle Zone: Order, Doctor & Coverage -->
                        <div class="patient-zone-order">
                            <div class="info-row">
                                <span class="info-pill">Consultant</span>
                                <span class="info-colon">:</span>
                                <span class="info-val-text">{{ $doctorEn ?: '—' }}</span>
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
                                <span class="badge-coverage {{ $isInsured ? 'badge-insurance' : 'badge-cash' }}">
                                    {{ $isInsured ? '🛡️ الضمان الصحي' : '💵 حساب نقدي' }}
                                </span>
                            </div>
                        </div>

                        <!-- Right Zone: Direct QR Code -->
                        <div class="patient-zone-qr">
                            <div class="qr-box">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data={{ urlencode($verifyUrl) }}" alt="QR Code" class="qr-img">
                                <span class="qr-tag">LAB-#{{ $request->id }}</span>
                                <span class="qr-caption">مسح للنتائج</span>
                            </div>
                        </div>
                    </div>

                    @if(!isset($isBloodBankRequest) || !$isBloodBankRequest)
                        <!-- Column Headers -->
                        <div class="tests-header-bar">
                            <div class="header-col">Laboratory Test Results</div>
                            <div class="header-col center">Conventional Units</div>
                            <div class="header-col right">Status</div>
                        </div>
                    @endif
                </td>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="print-table-cell-body">

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
                        $prevItems = $findPrevResults($res->test_name);

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
                                <div class="current-val-line">
                                    <span class="{{ $valClass }}">{{ $val ?: '—' }}</span>
                                    @if($unit)
                                        <span class="unit-label">{{ $unit }}</span>
                                    @endif
                                </div>
                                @if(!empty($prevItems) && count($prevItems) > 0)
                                    <div class="prev-vertical-box">
                                        @foreach($prevItems as $p)
                                            @php
                                                $pStatus = calcLabStatus($p['value'], $refRange, $p['status'] ?? 'normal');
                                                $pClass = ($pStatus === 'high' || $pStatus === 'positive' || str_contains($pStatus, 'high') || str_contains($pStatus, 'pos') || $pStatus === '+') ? 'high' : (($pStatus === 'low' || str_contains($pStatus, 'low') || $pStatus === '-') ? 'low' : '');
                                            @endphp
                                            <div class="prev-vertical-row" title="التاريخ: {{ $p['date'] }}">
                                                <span class="prev-val-chip {{ $pClass }}">{{ $p['value'] }}</span>
                                                <span class="prev-date-text">({{ $p['date_short'] }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
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

                        <!-- Reference Range Row -->
                        @if($refRange)
                        <div class="test-meta-row">
                            <div class="meta-label">Normal Range :</div>
                            <div class="meta-conv-range">{{ $refRange }}</div>
                            <div class="meta-status-blank"></div>
                        </div>
                        @endif

                        <!-- Device / Method Row -->
                        <div class="test-device-row">
                            By Cobas Integra 400 plus (Roche Diagnostics)
                        </div>
                    </div>
                @endforeach
            @elseif(!empty($request->result) && count($savedArr ?? []) > 0)
                @php
                    $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                    $testResults = is_array($resultData) ? ($resultData['test_results'] ?? []) : [];
                @endphp
                @foreach($testResults as $tName => $tVal)
                    @php
                        $valStr = is_array($tVal) ? ($tVal['value'] ?? '') : $tVal;
                        $unitStr = is_array($tVal) ? ($tVal['unit'] ?? '') : '';
                        $refStr = is_array($tVal) ? ($tVal['reference_range'] ?? '') : '';
                        $prevItems = $findPrevResults($tName);
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
                                <div class="current-val-line">
                                    <span class="{{ $valClass }}">{{ $valStr }}</span>
                                    @if($unitStr) <span class="unit-label">{{ $unitStr }}</span> @endif
                                </div>
                                @if(!empty($prevItems) && count($prevItems) > 0)
                                    <div class="prev-vertical-box">
                                        @foreach($prevItems as $p)
                                            @php
                                                $pStatus = calcLabStatus($p['value'], $refStr, $p['status'] ?? 'normal');
                                                $pClass = ($pStatus === 'high' || $pStatus === 'positive' || str_contains($pStatus, 'high') || str_contains($pStatus, 'pos')) ? 'high' : (($pStatus === 'low' || str_contains($pStatus, 'low')) ? 'low' : '');
                                            @endphp
                                            <div class="prev-vertical-row" title="التاريخ: {{ $p['date'] }}">
                                                <span class="prev-val-chip {{ $pClass }}">{{ $p['value'] }}</span>
                                                <span class="prev-date-text">({{ $p['date_short'] }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="test-value-cell right">
                                @if($statusStr === 'high' || $statusStr === 'positive' || str_contains($statusStr, 'high') || str_contains($statusStr, 'pos'))
                                    <span class="val-status-high">High</span>
                                @elseif($statusStr === 'low' || str_contains($statusStr, 'low'))
                                    <span class="val-status-low">Low</span>
                                @elseif($statusStr === 'abnormal')
                                    <span class="val-status-high">Abnormal</span>
                                @elseif($valStr !== '' && $valStr !== '—')
                                    <span class="val-status-normal">Normal</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </div>
                        @if($refStr)
                        <div class="test-meta-row">
                            <div class="meta-label">Normal Range :</div>
                            <div class="meta-conv-range">{{ $refStr }}</div>
                            <div class="meta-status-blank"></div>
                        </div>
                        @endif
                        <div class="test-device-row">By Cobas Integra 400 plus (Roche Diagnostics)</div>
                    </div>
                @endforeach
            @elseif(count($requestedLabTestsList ?? []) > 0)
                <!-- Requisition / Work Order Mode: Requested Tests before entering results -->
                <div style="background: #f8fafc; border: 1px dashed #0284c7; border-radius: 6px; padding: 6px 12px; margin-bottom: 12px; font-size: 13px; color: #0369a1; font-weight: 600; display: flex; justify-content: space-between; align-items: center;">
                    <span><i class="fas fa-clipboard-list me-1"></i> قائمة التحاليل المطلوبة (أمر عمل المختبر / Test Work Order)</span>
                    <span class="badge bg-primary px-2 py-1">بانتظار إدخال النتائج</span>
                </div>

                @foreach($requestedLabTestsList as $reqTestItem)
                    @php
                        $tName = is_array($reqTestItem) ? ($reqTestItem['name'] ?? '') : (is_object($reqTestItem) ? $reqTestItem->name : (string)$reqTestItem);
                        $tUnit = is_array($reqTestItem) ? ($reqTestItem['unit'] ?? '') : (is_object($reqTestItem) ? ($reqTestItem->unit ?? '') : '');
                        $tRef = is_array($reqTestItem) ? ($reqTestItem['reference_range'] ?? '') : (is_object($reqTestItem) ? ($reqTestItem->reference_range ?? '') : '');
                        $tCat = is_array($reqTestItem) ? ($reqTestItem['category'] ?? '') : (is_object($reqTestItem) ? ($reqTestItem->category ?? '') : '');
                        $prevItems = $findPrevResults($tName);

                        if (empty($tUnit) || empty($tRef)) {
                            $dbTest = \App\Models\LabTest::where('name', $tName)->first();
                            if ($dbTest) {
                                $tUnit = $tUnit ?: $dbTest->unit;
                                $tRef = $tRef ?: $dbTest->reference_range;
                                $tCat = $tCat ?: $dbTest->category;
                            }
                        }
                    @endphp

                    @if($tCat && $tCat !== $currentGroup)
                        @php $currentGroup = $tCat; @endphp
                        <div class="category-header-row">
                            {{ $currentGroup }}
                        </div>
                    @endif

                    <div class="test-card">
                        <!-- Main Test Row -->
                        <div class="test-main-row">
                            <div class="test-name">
                                {{ $tName }}
                            </div>
                            <div class="test-value-cell">
                                <div class="current-val-line">
                                    <span style="display: inline-block; width: 80px; border-bottom: 1.5px dotted #94a3b8; margin-right: 5px;">&nbsp;</span>
                                    @if($tUnit)
                                        <span class="unit-label">{{ $tUnit }}</span>
                                    @endif
                                </div>
                                @if(!empty($prevItems) && count($prevItems) > 0)
                                    <div class="prev-vertical-box">
                                        @foreach($prevItems as $p)
                                            <div class="prev-vertical-row" title="التاريخ: {{ $p['date'] }}">
                                                <span class="prev-val-chip">{{ $p['value'] }}</span>
                                                <span class="prev-date-text">({{ $p['date_short'] }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="test-value-cell right">
                                <span class="badge" style="background-color: #f1f5f9; color: #64748b; font-size: 11px; border: 1px solid #cbd5e1; font-weight: normal;">Pending</span>
                            </div>
                        </div>

                        <!-- Reference Range Row -->
                        @if($tRef)
                        <div class="test-meta-row">
                            <div class="meta-label">Normal Range :</div>
                            <div class="meta-conv-range">{{ $tRef }}</div>
                            <div class="meta-status-blank"></div>
                        </div>
                        @endif

                        <!-- Device / Method Row -->
                        <div class="test-device-row">
                            By Cobas Integra 400 plus (Roche Diagnostics)
                        </div>
                    </div>
                @endforeach
            @else
                <div style="text-align: center; padding: 40px; color: #888;">
                    No laboratory tests or results recorded for this request yet.
                </div>
            @endif
        @endif

            <!-- Official Authentication & Signatures Block (مكان التوقيع فارغ للاختبار) -->
            <div class="auth-block">
                <!-- 1. Specialist / Medical Lab Scientist Signature -->
                <div class="auth-cell">
                    <div class="signature-box-empty">
                        <div class="sig-title">أخصائي / فاحص المختبر والتحاليل الطبية</div>
                        <div class="sig-subtitle">{{ auth()->user()->name ?? 'Clinical Laboratory Specialist' }}</div>
                        <div class="sig-empty-line">
                            (مكان التوقيع والاعتماد)
                        </div>
                        <div style="font-size: 9.5px; color: #64748b; font-weight: 700; margin-top: 1px;">
                            التاريخ: {{ now()->format('Y-m-d') }}
                        </div>
                    </div>
                </div>

                <!-- 2. Official Hospital / Dept Stamp -->
                <div class="auth-cell">
                    <div class="stamp-circle-box">
                        <div style="font-size: 7.5px; font-weight: 800; color: #005a9c;">★ ★ ★</div>
                        <div style="font-size: 9px; font-weight: 900; line-height: 1.2;">مستشفى الكفاءات</div>
                        <div style="font-size: 8px; font-weight: 800; color: #0284c7;">المختبر والتحاليل</div>
                        <div style="font-size: 7.5px; color: #b91c1c; font-weight: 900; margin-top: 1px;">مُعتمَــد</div>
                    </div>
                </div>
            </div>
            </td>
        </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="print-table-cell-footer">
                    <!-- Footer: Always at the bottom of EVERY page on print -->
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
                </td>
            </tr>
        </tfoot>
    </table>

    </div>

    <script>
        function setPrintMode(mode) {
            @if(!empty($isPublicView))
                // Always keep full header for patient/public report
                document.body.classList.remove('letterhead-mode');
                return;
            @endif

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
                localStorage.setItem('lab_print_mode_v2', mode);
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
            @if(!empty($isPublicView))
                setPrintMode('full');
            @else
                let savedMode = 'full'; // Default is FULL: with complete hospital header & logo
                let savedTop = 45;
                try {
                    savedMode = localStorage.getItem('lab_print_mode_v2') || 'full';
                    savedTop = parseInt(localStorage.getItem('lab_print_top_margin')) || 45;
                } catch (e) {}

                const topInput = document.getElementById('topMarginInput');
                if (topInput) {
                    topInput.value = savedTop;
                }
                changeTopMargin(savedTop);
                setPrintMode(savedMode);
            @endif
        })();
    </script>
</body>
</html>
