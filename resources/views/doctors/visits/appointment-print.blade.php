<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصل موعد مراجعة - {{ $visit->patient?->user?->name ?? 'المريض' }}</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
            color: #000000;
        }

        body {
            direction: rtl;
            text-align: right;
            background-color: #f3f4f6;
            padding: 20px 10px;
            font-size: 13px;
            line-height: 1.4;
        }

        .thermal-ticket {
            width: 78mm;
            max-width: 100%;
            margin: 0 auto;
            background: #ffffff;
            padding: 12px 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-radius: 4px;
        }

        /* Screen action buttons */
        .no-print {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 15px;
        }

        .btn-print {
            background-color: #10b981;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
        }

        .btn-back {
            background-color: #6b7280;
            color: white;
            padding: 8px 18px;
            border: none;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Cairo', sans-serif;
        }

        /* Header box */
        .header-box {
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 6px 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .header-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .header-titles {
            flex: 1;
            text-align: center;
        }

        .header-titles .ar-title {
            font-size: 14.5px;
            font-weight: 900;
            line-height: 1.2;
        }

        .header-titles .en-title {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .title-badge {
            text-align: center;
            font-weight: 900;
            font-size: 13px;
            padding: 3px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            margin-bottom: 8px;
        }

        /* Meta details */
        .meta-group {
            margin-bottom: 8px;
            font-size: 12.5px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
        }

        .meta-label {
            font-weight: 800;
            font-size: 12px;
            color: #333;
        }

        .meta-val {
            font-weight: 900;
            font-size: 12.5px;
        }

        /* Highlight Appointment Box */
        .appointment-highlight-box {
            border: 2px solid #000000;
            border-radius: 4px;
            padding: 8px;
            text-align: center;
            margin: 8px 0;
            background-color: #fafafa;
        }

        .apt-badge-free {
            font-size: 12px;
            font-weight: 900;
            display: inline-block;
            margin-bottom: 4px;
        }

        .apt-date-big {
            font-size: 19px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .apt-day-text {
            font-size: 13px;
            font-weight: 800;
            margin-top: 2px;
        }

        /* Notes Box */
        .notes-section {
            border: 1px solid #000000;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 8px;
            font-size: 11.5px;
        }

        .notes-title {
            font-weight: 900;
            font-size: 11.5px;
            margin-bottom: 2px;
        }

        .notes-content {
            font-weight: 700;
            font-size: 11.5px;
            line-height: 1.35;
        }

        /* Instructions */
        .footer-instructions {
            border-top: 1px dashed #000;
            padding-top: 6px;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.35;
            margin-top: 6px;
        }

        .footer-meta {
            text-align: center;
            font-size: 10px;
            margin-top: 8px;
            color: #444444;
            font-weight: 700;
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .thermal-ticket {
                width: 78mm !important;
                max-width: 78mm !important;
                margin: 0 auto !important;
                box-shadow: none !important;
                border: 1px solid #000000 !important;
                border-radius: 0 !important;
                padding: 3mm 3mm !important;
            }

            .no-print {
                display: none !important;
            }

            @page {
                size: auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ طباعة الوصل الحراري</button>
        <button class="btn-back" onclick="window.close()">⬅️ إغلاق</button>
    </div>

    @php
        $hospital = \App\Models\Hospital::first();
        $hNameAr = $hospital?->name ?? 'مستشفى الكفاءات الاهلي';
        $patient = $visit->patient;
        $patientUser = $patient?->user;
        $doctor = $visit->doctor;
        $doctorUser = $doctor?->user;
        $followUpDate = $visit->follow_up_date ?: ($followUpAppointment?->appointment_date ?? null);
        $dayName = $followUpDate ? \Carbon\Carbon::parse($followUpDate)->locale('ar')->translatedFormat('l') : 'غير محدد';
        $aptNum = str_pad($followUpAppointment?->id ?? $visit->id, 5, '0', STR_PAD_LEFT);
    @endphp

    <div class="thermal-ticket">
        <!-- Header Box with Logo & Name -->
        <div class="header-box">
            <img src="{{ asset('images/لوغو.png') }}" class="header-logo" alt="Logo" onerror="this.src='{{ asset('images/hospital-logo.svg') }}';">
            <div class="header-titles">
                <div class="ar-title">{{ $hNameAr }}</div>
                <div class="en-title">Al-Kafaat Hospital</div>
            </div>
            <div style="width: 38px;"></div>
        </div>

        <div class="title-badge">
            بطاقة موعد مراجعة واستشارة
        </div>

        <!-- Meta info -->
        <div class="meta-group">
            <div class="meta-row">
                <span class="meta-label">رقم الموعد:</span>
                <span class="meta-val font-monospace">#APT-{{ $aptNum }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">تاريخ الإصدار:</span>
                <span class="meta-val">{{ now()->format('Y/m/d h:i A') }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">اسم المريض:</span>
                <span class="meta-val">{{ $patientUser?->name ?? 'مريض غير مسجل' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">رقم الملف:</span>
                <span class="meta-val font-monospace">#{{ $patient?->national_id ?: ($patient?->id ?? $visit->patient_id) }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">الطبيب المعالج:</span>
                <span class="meta-val">د. {{ $doctorUser?->name ?? 'طبيب العيادة' }}</span>
            </div>
            <div class="meta-row">
                <span class="meta-label">العيادة / القسم:</span>
                <span class="meta-val">{{ $doctor?->specialization ?: ($visit->department?->name ?? 'الاستشارية') }}</span>
            </div>
        </div>

        <!-- الصندوق الحراري البارز لتاريخ المراجعة -->
        <div class="appointment-highlight-box">
            <div class="apt-badge-free">★ مراجعة واستشارة مجانية (Free) ★</div>
            <div class="apt-date-big">
                {{ $followUpDate ? \Carbon\Carbon::parse($followUpDate)->format('Y/m/d') : 'يرجى تحديد الموعد' }}
            </div>
            <div class="apt-day-text">
                يوم: <strong>{{ $dayName }}</strong>
                @if($followUpAppointment && $followUpAppointment->appointment_time)
                    <span> | الساعة: {{ \Carbon\Carbon::parse($followUpAppointment->appointment_time)->format('h:i A') }}</span>
                @endif
            </div>
            @if($doctor && $doctor->recheck_validity_days)
                <div style="font-size: 10px; margin-top: 3px; font-weight: 700;">
                    (الصلاحية: خلال {{ $doctor->recheck_validity_days }} أيام من تاريخ الكشف)
                </div>
            @endif
        </div>

        <!-- ملاحظات وتوجيهات الطبيب للمراجعة -->
        @if($visit->follow_up_notes)
            <div class="notes-section">
                <div class="notes-title">⚠️ توجيهات الطبيب للمراجعة:</div>
                <div class="notes-content">{{ $visit->follow_up_notes }}</div>
            </div>
        @endif

        <!-- إرشادات الحضور -->
        <div class="footer-instructions">
            • يرجى الحضور قبل الموعد بـ 15 دقيقة.<br>
            • إبراز هذا الوصل لموظف الاستقبال للدخول المباشر للطبيب.<br>
            • إحضار نتائج الفحوصات والتقارير الطبية المطلوبة.
        </div>

        <div class="footer-meta">
            نتمنى لكم دوام الصحة والعافية
        </div>
    </div>

</body>
</html>
