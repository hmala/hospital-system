<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعهد خطي بموافقة ولي أمر المريض - {{ $reservation->baby_title }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 16mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            color: #111827;
            background: #f1f5f9;
            margin: 0;
            padding: 0;
            font-size: 14px;
            line-height: 1.85;
        }
        .print-toolbar {
            width: 210mm;
            margin: 14px auto 0;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        }
        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .toolbar-input {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 13px;
            min-width: 210px;
            font-family: inherit;
        }
        .btn-tool {
            border: 1px solid #0d6efd;
            background: #0d6efd;
            color: #ffffff;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
        }
        .btn-tool.secondary {
            background: #ffffff;
            color: #334155;
            border-color: #cbd5e1;
        }
        .sheet {
            width: 210mm;
            min-height: 285mm;
            margin: 12px auto 20px;
            background: #ffffff;
            padding: 16mm 18mm 14mm;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .header-banner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2.5px solid #334155;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .logo-wrap {
            width: 90px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }
        .logo-wrap img {
            max-width: 78px;
            max-height: 68px;
            object-fit: contain;
        }
        .hospital-titles {
            text-align: center;
            flex: 1;
        }
        .hospital-titles h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }
        .hospital-titles h3 {
            margin: 2px 0 0;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: 0.3px;
        }
        .header-date {
            width: 95px;
            text-align: left;
            font-weight: 700;
            font-size: 13.5px;
            direction: ltr;
            color: #1e293b;
        }
        .form-title {
            text-align: center;
            font-size: 17px;
            font-weight: 800;
            text-decoration: underline;
            text-underline-offset: 6px;
            margin: 6px 0 14px;
            color: #000000;
        }
        .patient-ref-bar {
            display: flex;
            justify-content: center;
            gap: 18px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #475569;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 5px 12px;
            margin-bottom: 16px;
        }
        .party-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 15px;
        }
        .party-table td {
            padding: 5px 4px;
            vertical-align: middle;
        }
        .party-label {
            width: 195px;
            font-weight: 700;
            color: #0f172a;
        }
        .party-value {
            font-weight: 700;
            color: #000000;
            padding-right: 10px;
        }
        .consent-body {
            font-size: 14.5px;
            line-height: 2.05;
            color: #111827;
            text-align: justify;
        }
        .consent-body p {
            margin: 0 0 10px;
        }
        .consent-note {
            margin-top: 14px !important;
            font-weight: 700;
            color: #0f172a;
        }
        .date-declaration {
            margin-top: 26px;
            font-size: 15px;
            font-weight: 700;
            text-align: center;
        }
        .signatures-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-top: 34px;
            margin-bottom: 24px;
            padding: 0 12px;
        }
        .sig-col {
            width: 44%;
            text-align: center;
        }
        .sig-title {
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 10px;
            color: #000000;
        }
        .sig-name {
            font-weight: 700;
            font-size: 14.5px;
            color: #1e293b;
            margin-bottom: 14px;
        }
        .sig-space {
            height: 62px;
            border-bottom: 1px dashed #94a3b8;
            width: 82%;
            margin: 0 auto;
        }
        .footer-bar {
            border-top: 3px solid #334155;
            padding-top: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.65;
        }
        .footer-mobiles {
            direction: ltr;
            display: inline-block;
        }
        @media print {
            body {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .sheet {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                min-height: 270mm !important;
            }
            .patient-ref-bar {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body>
@php
    $logoFile = public_path('images/1.jpg');
    if (! file_exists($logoFile) || filesize($logoFile) === 0) {
        $logoFile = public_path('images/logo.png');
    }

    if (file_exists($logoFile) && filesize($logoFile) > 0) {
        $ext = strtolower(pathinfo($logoFile, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            default => 'image/jpeg',
        };
        $hospitalLogoSrc = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($logoFile));
    } else {
        $hospitalLogoSrc = asset('images/1.jpg');
    }

    $hospitalNameAr = $config['hospital_name_ar'] ?? ($hospital?->name ?? 'مستشفى الكفاءات الأهلي');
    $hospitalNameEn = $config['hospital_name_en'] ?? 'Al-Kafaat Hospital';
    $hospitalAddress = $config['hospital_address'] ?? ($hospital?->address ?? 'بغداد - الحارثية شارع الكندي');
    $hospitalEmail = $config['hospital_email'] ?? ($hospital?->email ?? 'alkafaathospital@gmail.com');
    $hospitalPhone1 = $config['hospital_phone'] ?? ($hospital?->phone ?? '07760507060');
    $hospitalPhone2 = $config['hospital_phone_2'] ?? '07860507060';

    $consentDate = ($reservation->admission_date ?? now())->format('d/m/Y');
    $doctorName = optional($reservation->doctor?->user)->name;
    $displayDoctor = $doctorName ? (str_starts_with($doctorName, 'د.') || str_starts_with($doctorName, 'الدكتور') ? $doctorName : 'الدكتور '.$doctorName) : '..................................................';
    $displayGuardian = $guardianName !== '' ? $guardianName : '..................................................';
@endphp

<div class="print-toolbar no-print">
    <div class="toolbar-group">
        <a href="{{ route('nicu.show', $reservation) }}" class="btn-tool secondary">
            العودة لملف الطفل
        </a>
        <label for="liveGuardianInput" style="font-size: 12.5px; font-weight: 700; color: #334155;">تعديل اسم ولي الأمر قبل الطباعة:</label>
        <input type="text" id="liveGuardianInput" class="toolbar-input" value="{{ $guardianName }}" placeholder="اكتب اسم ولي الأمر (الأب / الأم)...">
    </div>
    <div class="toolbar-group">
        <button type="button" class="btn-tool" onclick="window.print()">
            🖨️ طباعة استمارة التعهد (A4)
        </button>
    </div>
</div>

<div class="sheet">
    <div>
        <div class="header-banner">
            <div class="logo-wrap">
                <img src="{!! $hospitalLogoSrc !!}" alt="{{ $hospitalNameAr }}">
            </div>
            <div class="hospital-titles">
                <h2>{{ $hospitalNameAr }}</h2>
                <h3>{{ $hospitalNameEn }}</h3>
            </div>
            <div class="header-date">{{ $consentDate }}</div>
        </div>

        <h1 class="form-title">تعهد خطي بموافقة ولي أمر المريض على التدخلات العلاجية لمرضى دخول الخدج</h1>

        <div class="patient-ref-bar">
            <span><strong>اسم الطفل:</strong> {{ $reservation->baby_title }}</span>
            <span>|</span>
            <span><strong>الحاضنة:</strong> رقم {{ $reservation->incubator?->incubator_number ?? '—' }}</span>
            <span>|</span>
            <span><strong>رقم الإضبارة:</strong> #{{ $reservation->id }}</span>
        </div>

        <table class="party-table">
            <tr>
                <td class="party-label">إني ولي أمر المريض</td>
                <td class="party-value" id="printGuardianTop">{{ $displayGuardian }}</td>
            </tr>
            <tr>
                <td class="party-label">أطلعني الطبيب المعالج</td>
                <td class="party-value">{{ $displayDoctor }}</td>
            </tr>
        </table>

        <div class="consent-body">
            <p>على طبيعة مرض المريض وعلى التدخلات العلاجية والجراحية اللازمة لعلاجه من المرض، وعليه أوافق على دخول المريض إلى ردهة الخدج من قبل الطبيب أعلاه.</p>
            <p>كما أوافق على أن يتم وضع المريض على أجهزة (CPAP, Chest Tube, Ventilator) عند الحاجة، وكما أخول الطبيب طلب المساعدة أو استشارة أي طبيب آخر عند الضرورة.</p>
            <p>ويعد هذا إقراراً شاملاً أوقعه بإرادتي وبكامل حريتي وأتحمل المسؤولية عن ذلك، ولا أطالب الطبيب بأي تعويض مادي أو معنوي أو عشائري لأي مضاعفات تحصل أثناء وبعد دخول المريض لردهة الخدج وبضمنها حالات الوفاة لا سامح الله.</p>

            <p style="margin-top: 22px;">أتعهد بالالتزام بالنظام الداخلي للمستشفى والملحق المادي والمحافظة على كافة الموجودات وأثاث الغرفة ودفع التعويض المالي لأي إتلاف ناتج عن استخدامها من قبلي.</p>

            <p class="consent-note">ملاحظة: يتم تجديد حساب الحاضنة يومياً بعد الساعة الثانية عشر ظهراً.</p>
        </div>

        <div class="date-declaration">
            ولأجله وقعت في أدناه بتاريخ &nbsp; <span dir="ltr">{{ $consentDate }}</span>
        </div>

        <div class="signatures-row">
            <div class="sig-col">
                <div class="sig-title">توقيع وبصمة ولي أمر المريض</div>
                <div class="sig-name" id="printGuardianBottom">{{ $displayGuardian }}</div>
                <div class="sig-space"></div>
            </div>
            <div class="sig-col">
                <div class="sig-title">الممرضة المسؤولة</div>
                <div class="sig-name">&nbsp;</div>
                <div class="sig-space"></div>
            </div>
        </div>
    </div>

    <div class="footer-bar">
        <div>
            {{ $hospitalAddress }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            <span dir="ltr">Email: {{ $hospitalEmail }}</span>
        </div>
        <div class="footer-mobiles">
            mobile 1: {{ $hospitalPhone1 }}
            &nbsp;&nbsp;|&nbsp;&nbsp;
            mobile 2: {{ $hospitalPhone2 }}
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('liveGuardianInput');
    const topEl = document.getElementById('printGuardianTop');
    const bottomEl = document.getElementById('printGuardianBottom');
    const fallbackText = '..................................................';

    if (input) {
        input.addEventListener('input', function () {
            const val = this.value.trim() || fallbackText;
            if (topEl) topEl.textContent = val;
            if (bottomEl) bottomEl.textContent = val;
        });
    }
});
</script>
</body>
</html>
