<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>باجة هوية موظف - {{ $employee->full_name }} ({{ $employee->employee_code }})</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <style>
        :root {
            @if($employee->staff_type === 'medical')
                --badge-theme: #1d4ed8;
                --badge-gradient: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
                --badge-accent: #60a5fa;
            @elseif($employee->staff_type === 'nursing')
                --badge-theme: #0f766e;
                --badge-gradient: linear-gradient(135deg, #134e4a 0%, #0d9488 100%);
                --badge-accent: #2dd4bf;
            @elseif($employee->staff_type === 'technical')
                --badge-theme: #6b21a8;
                --badge-gradient: linear-gradient(135deg, #581c87 0%, #9333ea 100%);
                --badge-accent: #c084fc;
            @elseif($employee->staff_type === 'administrative')
                --badge-theme: #334155;
                --badge-gradient: linear-gradient(135deg, #1e293b 0%, #475569 100%);
                --badge-accent: #94a3b8;
            @else
                --badge-theme: #b45309;
                --badge-gradient: linear-gradient(135deg, #78350f 0%, #d97706 100%);
                --badge-accent: #fbbf24;
            @endif
        }

        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 20px;
        }

        /* حاوية بطاقة الهوية القياسية (Standard CR80: 54mm × 85.6mm) */
        .badge-card {
            width: 54mm;
            height: 85.6mm;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.12);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
            box-sizing: border-box;
            margin: 0 auto;
        }

        /* الشريط العلوي للباجة */
        .badge-header {
            background: var(--badge-gradient);
            color: #ffffff;
            padding: 7px 8px 6px;
            text-align: center;
            position: relative;
        }

        .badge-header::after {
            content: '';
            position: absolute;
            bottom: -5px;
            right: 0;
            left: 0;
            height: 5px;
            background: var(--badge-accent);
        }

        .badge-hospital-name {
            font-size: 8.5pt;
            font-weight: 800;
            line-height: 1.2;
            letter-spacing: -0.2px;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        .badge-hospital-sub {
            font-size: 6pt;
            opacity: 0.85;
            letter-spacing: 0.4px;
            margin: 0;
            font-weight: 600;
        }

        /* ثقب تعليق الباجة (Lanyard Slot) */
        .lanyard-slot {
            width: 14mm;
            height: 3mm;
            background: #e2e8f0;
            border-radius: 2mm;
            margin: 3px auto 0;
            border: 1px dashed #cbd5e1;
        }

        /* صورة الموظف */
        .badge-photo-wrapper {
            margin-top: 5px;
            text-align: center;
            position: relative;
        }

        .badge-photo {
            width: 24mm;
            height: 27mm;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid var(--badge-theme);
            box-shadow: 0 3px 8px rgba(0,0,0,0.15);
            background: #f8fafc;
        }

        .badge-photo-placeholder {
            width: 24mm;
            height: 27mm;
            border-radius: 8px;
            border: 2px solid var(--badge-theme);
            background: #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24pt;
            color: var(--badge-theme);
            box-shadow: 0 3px 8px rgba(0,0,0,0.1);
        }

        /* جسم البطاقة والبيانات */
        .badge-body {
            text-align: center;
            padding: 3px 6px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .badge-name {
            font-size: 9.5pt;
            font-weight: 900;
            color: #0f172a;
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .badge-title {
            font-size: 7.5pt;
            font-weight: 700;
            color: var(--badge-theme);
            line-height: 1.2;
            margin-bottom: 2px;
        }

        .badge-dept {
            font-size: 6.5pt;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 3px;
        }

        .badge-category-pill {
            display: inline-block;
            background: var(--badge-theme);
            color: #ffffff;
            font-size: 6pt;
            font-weight: 700;
            padding: 1.5px 8px;
            border-radius: 10px;
            letter-spacing: 0.2px;
            margin: 0 auto 3px;
        }

        /* كود الموظف والباركود */
        .badge-code-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 2px 4px;
            margin: 0 4px;
        }

        .badge-code {
            font-family: monospace;
            font-size: 7.5pt;
            font-weight: 800;
            color: #1e293b;
            letter-spacing: 1px;
            margin-bottom: 1px;
        }

        /* محاكاة باركود مرئي دقيق بدون اعتماد خارجي */
        .visual-barcode {
            display: flex;
            justify-content: center;
            align-items: flex-end;
            gap: 1.2px;
            height: 5.5mm;
            overflow: hidden;
            margin-top: 1px;
        }

        .visual-barcode span {
            background: #0f172a;
            height: 100%;
            display: inline-block;
        }

        /* تذييل البطاقة */
        .badge-footer {
            background: var(--badge-gradient);
            color: #ffffff;
            text-align: center;
            padding: 3px 6px;
            font-size: 5pt;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        /* الوجه الخلفي للبطاقة (Back of the badge) */
        .badge-back {
            padding: 10px 8px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            text-align: right;
            font-size: 6.5pt;
        }

        .badge-back-title {
            font-size: 8pt;
            font-weight: 800;
            color: var(--badge-theme);
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
            margin-bottom: 6px;
            text-align: center;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            border-bottom: 1px dashed #f1f5f9;
        }

        .info-row span:first-child {
            color: #64748b;
            font-weight: 600;
        }

        .info-row span:last-child {
            color: #0f172a;
            font-weight: 700;
        }

        .badge-terms {
            font-size: 5pt;
            color: #64748b;
            line-height: 1.3;
            text-align: justify;
            background: #f8fafc;
            padding: 4px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            margin-top: 4px;
        }

        .stamp-box {
            width: 18mm;
            height: 12mm;
            border: 1px dashed #94a3b8;
            border-radius: 4px;
            margin: 4px auto 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            font-size: 5.5pt;
            font-weight: 700;
        }

        /* أزرار التحكم بالطباعة */
        .print-controls {
            max-width: 700px;
            margin: 0 auto 20px;
            background: #ffffff;
            border-radius: 12px;
            padding: 15px 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
        }

        /* إعدادات الطباعة الدقيقة للباجات */
        @media print {
            body {
                background: none !important;
                padding: 0 !important;
            }

            .print-controls {
                display: none !important;
            }

            .badges-container {
                display: flex !important;
                flex-direction: row !important;
                justify-content: center !important;
                gap: 15mm !important;
                padding-top: 10mm !important;
            }

            .badge-card {
                box-shadow: none !important;
                border: 0.5pt solid #cbd5e1 !important;
                page-break-inside: avoid !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- أزرار وشريط التحكم العائم للطباعة -->
    <div class="print-controls d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="fw-bold mb-1 text-primary">
                <i class="fas fa-id-badge me-2"></i>معاينة وطباعة باجة الموظف الرسمية
            </h5>
            <p class="text-muted small mb-0">المقاس القياسي: بطاقة بلاستيكية ممغنطة / CR80 (54mm × 85.6mm)</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-success fw-bold px-4 shadow-sm">
                <i class="fas fa-print me-1"></i> طباعة فورية للباجة
            </button>
            <a href="{{ route('hr.employees.show', $employee->id) }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right me-1"></i> العودة للإضبارة
            </a>
        </div>
    </div>

    <!-- حاوية البطاقات (الوجه الأمامي والوجه الخلفي معاً) -->
    <div class="badges-container d-flex flex-wrap justify-content-center align-items-center gap-4">

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- الوجه الأمامي للباجة (FRONT SIDE) -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="badge-card">
            <!-- الترويسة والشعار -->
            <div class="badge-header">
                <h6 class="badge-hospital-name">{{ $hospital->name ?? 'مستشفى الفيحاء الأهلي' }}</h6>
                <div class="badge-hospital-sub">HOSPITAL MANAGEMENT STAFF ID</div>
            </div>

            <!-- ثقب التعليق القياسي (اختياري للطباعة) -->
            <div class="lanyard-slot"></div>

            <!-- صورة الموظف -->
            <div class="badge-photo-wrapper">
                @if($employee->profile_photo && \Illuminate\Support\Facades\Storage::disk('public')->exists($employee->profile_photo))
                    <img src="{{ asset('storage/' . $employee->profile_photo) }}" alt="{{ $employee->full_name }}" class="badge-photo">
                @else
                    <div class="badge-photo-placeholder">
                        <i class="fas {{ $employee->gender === 'female' ? 'fa-user-nurse' : 'fa-user-md' }}"></i>
                    </div>
                @endif
            </div>

            <!-- تفاصيل الموظف والوظيفة -->
            <div class="badge-body">
                <div class="badge-category-pill">
                    <i class="fas {{ $employee->staff_type === 'medical' ? 'fa-stethoscope' : ($employee->staff_type === 'nursing' ? 'fa-user-nurse' : ($employee->staff_type === 'technical' ? 'fa-microscope' : 'fa-id-badge')) }} me-1"></i>
                    {{ $employee->staff_type_name }}
                </div>
                
                <div class="badge-name">{{ $employee->full_name }}</div>
                <div class="badge-title">{{ $employee->job_title }}</div>
                <div class="badge-dept">{{ $employee->department?->name ?? 'قسم عام' }}</div>

                <!-- صندوق الكود والباركود -->
                <div class="badge-code-box">
                    <div class="badge-code">{{ $employee->employee_code }}</div>
                    <!-- رسم خطوط الباركود بطريقة هندسية خالصة -->
                    <div class="visual-barcode" title="{{ $employee->employee_code }}">
                        @php
                            $codeLengths = [2, 1, 3, 1, 2, 4, 1, 3, 2, 1, 4, 2, 1, 3, 2, 1, 3, 4, 1, 2, 3, 1, 4, 2];
                        @endphp
                        @foreach($codeLengths as $w)
                            <span style="width: {{ $w * 0.7 }}px;"></span>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- شريط التذييل -->
            <div class="badge-footer">
                OFFICIAL AUTHORIZED IDENTITY CARD
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════ -->
        <!-- الوجه الخلفي للباجة (BACK SIDE) -->
        <!-- ═══════════════════════════════════════════════════ -->
        <div class="badge-card">
            <!-- الشريط العلوي للوجه الخلفي -->
            <div class="badge-header" style="padding: 5px 8px;">
                <div class="badge-hospital-name" style="font-size: 7.5pt;">معلومات وتنبيهات أمنية</div>
            </div>

            <div class="badge-back">
                <div>
                    <div class="badge-back-title">
                        <i class="fas fa-info-circle me-1"></i>بيانات الهوية والاتصال
                    </div>

                    <div class="info-row">
                        <span>فصيلة الدم:</span>
                        <span class="text-danger fw-bold">{{ $employee->blood_group ?? '—' }}</span>
                    </div>

                    <div class="info-row">
                        <span>هاتف الطوارئ:</span>
                        <span class="font-monospace">{{ $employee->emergency_phone ?? $employee->phone }}</span>
                    </div>

                    <div class="info-row">
                        <span>الرقم الوطني:</span>
                        <span class="font-monospace">{{ $employee->national_id ? Str::mask($employee->national_id, '*', 4, 4) : '—' }}</span>
                    </div>

                    @if($employee->isMedicalStaff() && $employee->medical_license_number)
                    <div class="info-row">
                        <span>رقم الترخيص:</span>
                        <span class="font-monospace">{{ $employee->medical_license_number }}</span>
                    </div>
                    @endif

                    <div class="info-row">
                        <span>تاريخ الإصدار:</span>
                        <span>{{ now()->format('Y/m/d') }}</span>
                    </div>

                    <!-- التنبيهات والشروط -->
                    <div class="badge-terms">
                        <strong>تعليمات المستشفى:</strong><br>
                        • هذه البطاقة ملك للمستشفى ويجب إبرازها أثناء الدوام الرسمي.<br>
                        • في حال العثور على هذه البطاقة يرجى تسليمها لإدارة الموارد البشرية بالمستشفى فوراً.<br>
                        • هاتف الاستعلامات: {{ $hospital->phone ?? '07700000000' }}
                    </div>
                </div>

                <!-- مربع ختم الإدارة والتصديق -->
                <div class="text-center">
                    <div class="stamp-box">
                        ختم الإدارة<br>STAMP
                    </div>
                </div>
            </div>

            <!-- شريط التذييل للوجه الخلفي -->
            <div class="badge-footer">
                PROPERTY OF {{ strtoupper($hospital->name ?? 'HOSPITAL') }}
            </div>
        </div>

    </div>

</body>
</html>
