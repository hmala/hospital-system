<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Obstetric Case Sheet - {{ $observation->mother_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #111827;
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            font-size: 12px;
            line-height: 1.35;
        }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 12px auto;
            background: #ffffff;
            padding: 12mm 13mm;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .print-toolbar {
            width: 210mm;
            margin: 12px auto 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }
        .btn-tool {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #cbd5e1;
            background: #1e293b;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-tool.secondary {
            background: #fff;
            color: #1e293b;
        }
        .header-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #475569;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .logo-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-circle {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid #334155;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 20px;
            color: #0f172a;
        }
        .hospital-titles {
            text-align: center;
            flex: 1;
        }
        .hospital-titles h2 {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
        }
        .hospital-titles h3 {
            margin: 2px 0 0;
            font-size: 15px;
            font-weight: 700;
        }
        .header-date {
            font-weight: 700;
            font-size: 12px;
        }
        .meta-grid {
            display: grid;
            grid-template-columns: 1.25fr 1fr;
            gap: 24px;
            margin-bottom: 10px;
            font-size: 11.5px;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .meta-label {
            font-weight: 600;
            width: 118px;
            color: #334155;
        }
        .sheet-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 8px 0 6px;
        }
        .sheet-title-row h1 {
            margin: 0 auto;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
        }
        .stay-dates {
            font-size: 11.5px;
            text-align: left;
            min-width: 210px;
        }
        table.case-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        table.case-table th,
        table.case-table td {
            border: 1px solid #94a3b8;
            padding: 4px 7px;
            vertical-align: middle;
        }
        table.case-table th {
            background: #f1f5f9;
            font-weight: 700;
            text-align: left;
            color: #1e293b;
        }
        .field-label {
            width: 155px;
            font-weight: 700;
            background: #f8fafc;
        }
        .inline-cell-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inline-cell-table td {
            border: none;
            border-right: 1px solid #94a3b8;
            padding: 0 6px;
        }
        .inline-cell-table td:last-child {
            border-right: none;
        }
        .section-heading {
            font-weight: 700;
            font-size: 12.5px;
            text-decoration: underline;
            margin: 6px 0 3px;
        }
        .handover-footer-grid {
            display: grid;
            grid-template-columns: 190px 1fr;
            gap: 16px;
            margin-top: 18px;
            flex: 1;
        }
        .footprint-and-doc {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
        }
        .footprint-box {
            width: 140px;
            height: 155px;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 10.5px;
            text-align: center;
        }
        .nicu-doctor-sig {
            font-weight: 700;
            font-size: 12.5px;
            text-align: center;
            direction: rtl;
        }
        .arabic-handover {
            direction: rtl;
            text-align: right;
            font-size: 13px;
            line-height: 2.1;
            padding-top: 4px;
        }
        .arabic-handover .line-fill {
            display: inline-block;
            border-bottom: 1px dotted #475569;
            min-width: 110px;
            text-align: center;
            padding: 0 6px;
            font-weight: 700;
        }
        .arabic-handover .sig-row {
            margin-top: 26px;
            text-align: center;
            font-weight: 700;
        }
        .page-footer {
            margin-top: auto;
            border-top: 1.5px solid #475569;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
            color: #1e293b;
        }
        @media print {
            body {
                background: #fff;
            }
            .print-toolbar {
                display: none !important;
            }
            .sheet {
                margin: 0;
                box-shadow: none;
                width: 100%;
                min-height: 274mm;
                padding: 4mm 6mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <a href="{{ route('nicu.observations.show', $observation) }}" class="btn-tool secondary">
            العودة لملف المعاينة
        </a>
        <button type="button" class="btn-tool" onclick="window.print()">
            طباعة الاستمارة (A4)
        </button>
    </div>

    <div class="sheet">
        {{-- Header --}}
        <div class="header-banner">
            <div class="logo-box">
                <div class="logo-circle">K</div>
            </div>
            <div class="hospital-titles">
                <h2>{{ $config['hospital_name_ar'] }}</h2>
                <h3>{{ $config['hospital_name_en'] }}</h3>
            </div>
            <div class="header-date">
                {{ ($observation->reference_date ?? $observation->delivery_date ?? now())->format('d/m/Y') }}
            </div>
        </div>

        {{-- Top Metadata --}}
        <div class="meta-grid">
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Ref Date</td>
                    <td>{{ ($observation->reference_date ?? $observation->delivery_date ?? now())->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Patient Name</td>
                    <td><strong>{{ $observation->mother_name }}</strong></td>
                </tr>
                <tr>
                    <td class="meta-label">Departement</td>
                    <td>{{ $observation->department_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Requesting Dr.</td>
                    <td>{{ $observation->requesting_doctor_name ?? '-' }}</td>
                </tr>
            </table>

            <table class="meta-table">
                <tr>
                    <td class="meta-label">Printed user</td>
                    <td>{{ auth()->user()->name ?? 'NICU' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Printed @</td>
                    <td>{{ now()->format('d/m/Y h:i A') }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Birth of Date</td>
                    <td>{{ $observation->mother_birth_date?->format('d/m/Y') ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Age-Gender</td>
                    <td>{{ $observation->mother_age_gender ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Report Of</td>
                    <td>{{ $observation->report_type }}</td>
                </tr>
                <tr>
                    <td class="meta-label">Type</td>
                    <td>{{ $observation->stay_type }}</td>
                </tr>
            </table>
        </div>

        {{-- Title + Admission/Discharge Dates --}}
        <div class="sheet-title-row">
            <div style="width: 210px;"></div>
            <h1>Obstetric Case Sheet</h1>
            <div class="stay-dates">
                <div><strong>Date of Admission:</strong> {{ $observation->admission_date?->format('d/m/Y') ?? '-' }}</div>
                <div><strong>Date of Discharge:</strong> {{ $observation->discharge_date?->format('d/m/Y') ?? '-' }}</div>
            </div>
        </div>

        {{-- Operative & Delivery Table --}}
        <table class="case-table">
            <tr>
                <td class="field-label">Operative Description</td>
                <td>{{ $observation->operative_description ?? '-' }}</td>
            </tr>
            <tr>
                <td class="field-label">Date Of Delivery</td>
                <td style="padding: 0;">
                    <table class="inline-cell-table">
                        <tr>
                            <td style="width: 110px; font-weight: 700;">{{ $observation->delivery_date?->format('d/m/Y') ?? '-' }}</td>
                            <td style="background: #f8fafc; font-weight: 700; width: 130px;">Case Of Delivery</td>
                            <td style="text-align: center; font-weight: 700;">{{ $observation->case_of_delivery }}</td>
                            <td style="background: #f8fafc; font-weight: 700; width: 105px;">No. Of Borns</td>
                            <td style="text-align: center; width: 55px; font-weight: 700;">{{ $observation->no_of_borns }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="field-label">Pre Delivery Diagnosis</td>
                <td>{{ $observation->pre_delivery_diagnosis }}</td>
            </tr>
            <tr>
                <td class="field-label">Post Delivery Diagnosis</td>
                <td>{{ $observation->post_delivery_diagnosis }}</td>
            </tr>
            <tr>
                <td class="field-label">Procedure Performed</td>
                <td>{{ $observation->procedure_performed }}</td>
            </tr>
            <tr>
                <td class="field-label">Surgeon</td>
                <td>{{ $observation->surgeon_name }}</td>
            </tr>
            <tr>
                <td class="field-label">Anesthesia</td>
                <td>{{ $observation->anesthesia_type }}</td>
            </tr>
            <tr>
                <td class="field-label">Assessment</td>
                <td>{{ $observation->assessment }}</td>
            </tr>
            <tr>
                <td class="field-label">History Of Illness</td>
                <td>{{ $observation->history_of_illness }}</td>
            </tr>
            <tr>
                <td class="field-label">Delivery Note</td>
                <td><strong>{{ $observation->delivery_note ?? $observation->delivery_time }}</strong></td>
            </tr>
        </table>

        {{-- Born Name Table --}}
        <table class="case-table">
            <thead>
                <tr>
                    <th>Born Name</th>
                    <th style="width: 120px; text-align: center;">Live Status</th>
                    <th style="width: 95px; text-align: center;">Gender</th>
                    <th style="width: 95px; text-align: center;">Weight Kg</th>
                </tr>
            </thead>
            <tbody>
                @forelse($observation->babies as $baby)
                    <tr>
                        <td style="text-align: center; font-weight: 700;">{{ $baby->born_name }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $baby->live_status }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $baby->gender ?? '-' }}</td>
                        <td style="text-align: center; font-weight: 700;">{{ $baby->weight_kg !== null ? rtrim(rtrim(number_format((float) $baby->weight_kg, 3, '.', ''), '0'), '.') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="text-align: center;">طفل / {{ $observation->mother_name }}</td>
                        <td style="text-align: center;">Live Birth</td>
                        <td style="text-align: center;">-</td>
                        <td style="text-align: center;">-</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Treatment Table --}}
        <div class="section-heading">Treatment</div>
        <table class="case-table">
            <thead>
                <tr>
                    <th style="width: 38%;">Generic Name</th>
                    <th style="width: 34%;">Brand Name</th>
                    <th style="width: 28%;">Frequency</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $printTreatments = $observation->treatments ?? [];
                @endphp
                @forelse($printTreatments as $tr)
                    <tr>
                        <td>{{ $tr['generic_name'] ?? '' }}</td>
                        <td>{{ $tr['brand_name'] ?? '' }}</td>
                        <td>{{ $tr['frequency'] ?? '' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td style="height: 22px;"></td>
                        <td></td>
                        <td></td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Bottom Section: Footprint + Doctor Name (Left) & Arabic Handover Receipt (Right) --}}
        <div class="handover-footer-grid">
            <div class="footprint-and-doc">
                <div class="footprint-box">
                    بصمة قدم الطفل<br>Baby Footprint
                </div>
                <div class="nicu-doctor-sig">
                    {{ $observation->nicu_doctor_name ?? 'طبيب وحدة الخدج' }}
                </div>
            </div>

            <div class="arabic-handover">
                @php
                    $firstBaby = $observation->babies->first();
                    $genderText = match ($firstBaby?->gender) {
                        'Male', 'ذكر' => 'ذكر',
                        'Female', 'أنثى' => 'أنثى',
                        default => '..........',
                    };
                    $statusText = $firstBaby?->live_status === 'Live Birth' ? 'جيدة' : ($firstBaby?->live_status ?? '..........');
                @endphp
                <div>
                    تم استلام الطفل من قبل
                    <span class="line-fill">{{ $observation->recipient_name ?: '........................' }}</span>
                    الجنس
                    <span class="line-fill" style="min-width: 65px;">{{ $genderText }}</span>
                    وبحالة
                    <span class="line-fill" style="min-width: 70px;">{{ $statusText }}</span>
                    التاريخ
                    <span class="line-fill" style="min-width: 90px;">{{ $observation->handed_over_at?->format('d/m/Y') ?? ($observation->delivery_date?->format('d/m/Y') ?? '............') }}</span>
                </div>
                <div>
                    <strong>{{ $observation->recipient_relation ?: 'الأب' }} :-</strong>
                    <span class="line-fill" style="min-width: 260px;">{{ $observation->recipient_name }}</span>
                </div>
                <div>
                    <strong>الهوية :-</strong>
                    <span class="line-fill" style="min-width: 260px;">{{ $observation->recipient_id_number }}</span>
                </div>
                <div>
                    <strong>الهاتف :-</strong>
                    <span class="line-fill" style="min-width: 260px;">{{ $observation->recipient_phone }}</span>
                </div>
                <div>
                    <strong>السكن :-</strong>
                    <span class="line-fill" style="min-width: 260px;">{{ $observation->recipient_address }}</span>
                </div>
                <div class="sig-row">
                    توقيع وبصمة المستلم : ........................................
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="page-footer">
            <div>
                <div><strong>Email:</strong> {{ $config['hospital_email'] }}</div>
                <div><strong>mobile 1:</strong> {{ $config['hospital_phone'] }}</div>
            </div>
            <div style="direction: rtl; font-weight: 600;">
                {{ $config['hospital_address'] }}
            </div>
        </div>
    </div>
</body>
</html>
