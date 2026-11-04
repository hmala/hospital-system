@php
    // الشعار المعتمد مع التضمين الآمن Base64 ليعمل محلياً وعلى هوستنجر فورياً
    $logoFile = public_path('images/1.jpg');
    if (!file_exists($logoFile) || filesize($logoFile) === 0) {
        $logoFile = public_path('images/logo.png');
    }
    $hospitalLogoSrc = file_exists($logoFile) && filesize($logoFile) > 0
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoFile))
        : asset('images/1.jpg');
@endphp
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة كرت تقييم الرقود #{{ $surgery->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: 80mm auto;
            margin: 0;
        }
        body {
            font-family: 'Cairo', sans-serif;
            width: 78mm;
            margin: 0 auto;
            padding: 10px 6px;
            color: #000;
            background: #fff;
            text-align: center;
        }
        .header {
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 10px;
        }
        .hospital-name {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
        }
        .hospital-sub {
            font-size: 11px;
            margin: 2px 0 0;
        }
        .title {
            font-size: 13px;
            font-weight: 700;
            margin: 8px 0;
            background: #f0f0f0;
            padding: 4px;
            border-radius: 4px;
        }
        .info-box {
            text-align: right;
            font-size: 11px;
            margin-bottom: 10px;
            line-height: 1.6;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
        }
        .info-label {
            font-weight: 600;
            color: #333;
        }
        .info-value {
            font-weight: 800;
        }
        .qr-container {
            margin: 10px auto;
            padding: 8px;
            display: inline-block;
            border: 1px solid #ccc;
            border-radius: 8px;
            background: #fff;
        }
        .qr-container img {
            display: block;
            width: 170px;
            height: 170px;
        }
        .instructions {
            font-size: 11px;
            font-weight: 700;
            margin: 8px 0;
            line-height: 1.4;
        }
        .footer {
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 10px;
            font-size: 10px;
        }
        .no-print {
            margin-top: 15px;
        }
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        @if(!empty($hospitalLogoSrc))
            <div style="margin-bottom: 5px;">
                <img src="{{ $hospitalLogoSrc }}" alt="شعار المستشفى" style="max-height: 40px; max-width: 80px; object-fit: contain;">
            </div>
        @endif
        <h1 class="hospital-name">مستشفى الكفاءات الأهلي</h1>
        <p class="hospital-sub">AlKafaat Private Hospital</p>
    </div>

    <div class="title">
        استبيان تقييم غرفة الرقود
    </div>

    <div class="info-box">
        <div class="info-row">
            <span class="info-label">المريض:</span>
            <span class="info-value">{{ $surgery->patient?->name ?? 'غير محدد' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">رقم الغرفة:</span>
            <span class="info-value">{{ $surgery->room?->room_number ?? 'غير محدد' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الجناح / الطابق:</span>
            <span class="info-value">{{ $surgery->room?->floor ?? ($surgery->location?->name ?? '-') }}</span>
        </div>
        @if($surgery->doctor)
            <div class="info-row">
                <span class="info-label">الطبيب المعالج:</span>
                <span class="info-value">د. {{ $surgery->doctor->user?->name ?? '-' }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">التاريخ:</span>
            <span class="info-value">{{ date('Y-m-d') }}</span>
        </div>
    </div>

    <div class="qr-container">
        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=0&data={{ urlencode($surveyUrl) }}" alt="QR Code">
    </div>

    <div class="instructions">
        عزيزنا المريض أو مرافقه الكريم:<br>
        امسح الرمز بكاميرا هاتفك لتقييم إقامتك وخدمات الغرفة بكل خصوصية وأمانة.
    </div>

    <div class="footer">
        رأيكم محل اهتمام مباشر من إدارة المستشفى وقسم الجودة.<br>
        نتمنى لكم دوام الصحة والعافية.
    </div>

    <div class="no-print">
        <button onclick="window.print()" style="padding: 6px 16px; font-family: inherit; font-weight: 700; cursor: pointer; border-radius: 20px; border: 1px solid #000; background: #000; color: #fff;">
            طباعة الكرت
        </button>
        <button onclick="window.close()" style="padding: 6px 16px; font-family: inherit; cursor: pointer; border-radius: 20px; border: 1px solid #ccc; background: #f5f5f5;">
            إغلاق
        </button>
    </div>

    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 400);
        };
    </script>
</body>
</html>

