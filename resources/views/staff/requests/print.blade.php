<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laboratory Report - {{ $request->visit?->patient?->user?->name ?? 'Patient' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif;
            direction: ltr;
            background: #f4f6f9;
            color: #222;
            padding: 20px;
        }

        .report-page {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #ffffff;
            padding: 15mm 18mm;
            position: relative;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        }

        /* Top Action Buttons */
        .print-toolbar {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 9999;
        }

        .toolbar-btn {
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .btn-print { background: #1e4b88; color: #fff; }
        .btn-print:hover { background: #153765; }
        .btn-close-window { background: #6c757d; color: #fff; }

        /* Report Header */
        .report-header {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
            padding-top: 5px;
        }

        .header-top-right {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
        }

        .specialist-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            font-weight: bold;
            color: #8b0000;
        }

        .header-sub-qualifications {
            font-size: 10.5px;
            color: #555;
            margin-bottom: 4px;
        }

        .clinical-lab-title {
            font-family: 'Times New Roman', Times, serif;
            font-size: 34px;
            font-weight: bold;
            font-style: italic;
            color: #1e4b88;
            letter-spacing: 0.5px;
            display: inline-block;
            margin-top: 5px;
        }

        /* Patient Info Section */
        .patient-info-grid {
            display: grid;
            grid-template-columns: 1.15fr 0.85fr;
            gap: 10px 25px;
            margin-bottom: 16px;
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
            color: #111;
            font-weight: bold;
            font-size: 12.5px;
            padding: 2px 8px;
            border-radius: 3px;
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

        /* Table Column Headers */
        .tests-header-bar {
            display: grid;
            grid-template-columns: 2.2fr 1fr 1fr;
            border-top: 1.5px solid #2b5899;
            border-bottom: 1.5px solid #2b5899;
            padding: 6px 4px;
            margin-bottom: 10px;
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

        /* Test Items */
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
            margin-bottom: 4px;
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

        .val-abnormal {
            color: #c00000;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
        }

        .unit-label {
            color: #333;
            font-size: 12.5px;
            margin-left: 4px;
        }

        .test-meta-row {
            display: flex;
            align-items: center;
            font-size: 12.5px;
            color: #222;
            margin-bottom: 3px;
        }

        .meta-label {
            min-width: 140px;
            font-weight: normal;
            color: #222;
        }

        .meta-conv-range {
            width: 250px;
            color: #222;
        }

        .meta-si-range {
            color: #222;
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
        .report-footer {
            margin-top: 40px;
            padding-top: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            font-style: italic;
            color: #333;
        }

        .footer-printed-by span {
            font-weight: bold;
            font-style: normal;
        }

        .footer-datetime {
            display: flex;
            gap: 25px;
            font-style: italic;
        }

        /* Print Media Styles */
        @media print {
            .print-toolbar {
                display: none !important;
            }

            body {
                background: #fff;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .report-page {
                box-shadow: none;
                margin: 0;
                padding: 12mm 15mm;
                max-width: 100%;
                min-height: auto;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
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
            🖨️ Print Report
        </button>
        <button class="toolbar-btn btn-close-window" onclick="window.history.back()">
            ✕ Back
        </button>
    </div>

    @php
        // Helper: Convert Arabic Name to English Transliteration
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

        // Also check if result details in JSON exist
        if ($testsCount === 0 && !empty($request->result)) {
            $parsedRes = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            $savedArr = $parsedRes['test_results'] ?? [];
            $testsCount = count($savedArr);
        }
    @endphp

    <div class="report-page">

        <!-- Header -->
        <div class="report-header">
            <div class="header-top-right">
                <div class="header-sub-qualifications">Medical Microbiology (UK) - Cert. Med. Immunology (UK)</div>
                <div class="specialist-title">M.B.Ch.B., F.I.C.M.S.</div>
            </div>
            <div class="clinical-lab-title">Clinical Laboratory</div>
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
                    <span class="info-val-text">{{ $doctorEn }}</span>
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
                        // Optional Category / Parent heading
                        $parentName = $res->parent_test_name;
                        $val = trim($res->value ?? '');
                        $status = strtolower($res->status ?? 'normal');
                        $unit = trim($res->unit ?? '');
                        $refRange = trim($res->reference_range ?? '');
                        $isAbnormal = in_array($status, ['high', 'low', 'abnormal', 'positive']);

                        // Secondary SI Unit computation or alternate display
                        $siVal = '';
                        $siUnit = '';
                        $siRange = '';

                        // Auto-calculate common dual units if standard
                        if (is_numeric($val)) {
                            $num = (float)$val;
                            if (strtolower($unit) === 'g/l') {
                                $siVal = number_format($num * 100, 1);
                                $siUnit = 'mg/dl';
                                // Parse range if format 'X.XX - Y.YY'
                                if (preg_match('/([\d\.]+)\s*-\s*([\d\.]+)/', $refRange, $m)) {
                                    $siRange = number_format((float)$m[1] * 100, 1) . ' - ' . number_format((float)$m[2] * 100, 1);
                                }
                            } elseif (strtolower($unit) === 'mg/dl' && $num > 10) {
                                $siVal = number_format($num / 100, 2);
                                $siUnit = 'g/l';
                                if (preg_match('/([\d\.]+)\s*-\s*([\d\.]+)/', $refRange, $m)) {
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
                                <span class="{{ $isAbnormal ? 'val-abnormal' : 'val-normal' }}">{{ $val ?: '—' }}</span>
                                @if($unit)
                                    <span class="unit-label">{{ $unit }}</span>
                                @endif
                            </div>
                            <div class="test-value-cell right">
                                @if($siVal)
                                    <span class="{{ $isAbnormal ? 'val-abnormal' : 'val-normal' }}">{{ $siVal }}</span>
                                    <span class="unit-label">{{ $siUnit }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Reference Range Row -->
                        @if($refRange || $siRange)
                        <div class="test-meta-row">
                            <span class="meta-label">Normal Range :</span>
                            <span class="meta-conv-range">{{ $refRange ?: '—' }}</span>
                            @if($siRange)
                                <span class="meta-si-range">{{ $siRange }}</span>
                            @endif
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
                        $statusStr = is_array($tVal) ? ($tVal['status'] ?? 'normal') : 'normal';
                        $isAbn = in_array(strtolower($statusStr), ['high', 'low', 'abnormal']);
                    @endphp
                    <div class="test-card">
                        <div class="test-main-row">
                            <div class="test-name">{{ is_numeric($tName) ? 'Test #' . ($tName + 1) : $tName }}</div>
                            <div class="test-value-cell">
                                <span class="{{ $isAbn ? 'val-abnormal' : 'val-normal' }}">{{ $valStr }}</span>
                                @if($unitStr) <span class="unit-label">{{ $unitStr }}</span> @endif
                            </div>
                            <div class="test-value-cell right"></div>
                        </div>
                        @if($refStr)
                        <div class="test-meta-row">
                            <span class="meta-label">Normal Range :</span>
                            <span class="meta-conv-range">{{ $refStr }}</span>
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

        <!-- Footer -->
        <div class="report-footer">
            <div class="footer-printed-by">
                Printed by : <span>{{ auth()->user()->name ?? 'Hadeel' }}</span>
            </div>
            <div class="footer-datetime">
                <span>{{ now()->format('d-m-Y') }}</span>
                <span>{{ now()->format('g:i:sA') }}</span>
            </div>
        </div>

    </div>

</body>
</html>
