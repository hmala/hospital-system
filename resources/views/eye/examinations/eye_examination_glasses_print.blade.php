<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>راشيتة نظارة طبية - {{ $examination->patient->name }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #222;
            margin: 0;
            padding: 30px;
        }
        .prescription-card {
            max-width: 650px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #0d6efd;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .hospital-title {
            font-size: 20px;
            font-weight: bold;
            color: #0d6efd;
        }
        .center-subtitle {
            font-size: 14px;
            color: #555;
            margin-top: 4px;
        }
        .rx-badge {
            font-size: 32px;
            font-weight: 900;
            color: #0d6efd;
            font-family: 'Times New Roman', Times, serif;
        }
        .patient-info {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            background: #f1f6fc;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .info-label {
            color: #666;
            margin-bottom: 2px;
        }
        .info-value {
            font-weight: bold;
            color: #111;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: center;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #c2d7f7;
            padding: 10px 8px;
            font-size: 14px;
        }
        th {
            background-color: #e2edfc;
            color: #0d6efd;
            font-weight: bold;
        }
        .eye-name {
            font-weight: bold;
            background-color: #f8fafd;
            text-align: right;
            padding-right: 12px;
        }
        .pd-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fdfdfe;
            border: 1px dashed #0d6efd;
            padding: 10px 16px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
        }
        .signature-area {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
        }
        .doctor-signature {
            text-align: center;
            width: 200px;
        }
        .signature-line {
            border-top: 1px dashed #777;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 13px;
            color: #555;
        }
        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 120px;
            font-weight: 900;
            color: rgba(13, 110, 253, 0.03);
            pointer-events: none;
            user-select: none;
        }
        .btn-print {
            display: block;
            width: 100%;
            max-width: 250px;
            margin: 20px auto 0;
            padding: 12px;
            background: #0d6efd;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: bold;
            border-radius: 8px;
            border: none;
            cursor: pointer;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .prescription-card {
                box-shadow: none;
                border: 2px solid #333;
                max-width: 100%;
                padding: 20px;
            }
            .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="prescription-card">
    <div class="watermark">👁️</div>

    <div class="header">
        <div>
            <div class="hospital-title">مستشفى النور التخصصي</div>
            <div class="center-subtitle">مركز طب وجراحة العيون والبصريات | قسم فحص الانكسار</div>
        </div>
        <div class="rx-badge">℞</div>
    </div>

    <div class="patient-info">
        <div>
            <div class="info-label">اسم المريض:</div>
            <div class="info-value">{{ $examination->patient->name }}</div>
        </div>
        <div>
            <div class="info-label">العمر:</div>
            <div class="info-value">{{ $examination->patient->age ?? '-' }} سنة</div>
        </div>
        <div>
            <div class="info-label">التاريخ:</div>
            <div class="info-value">{{ $examination->created_at->format('Y-m-d') }}</div>
        </div>
    </div>

    <h4 style="color: #0d6efd; margin-bottom: 10px; font-size: 16px;">👓 وصفة النظارات الطبية (Optical Prescription)</h4>
    <table>
        <thead>
            <tr>
                <th style="width: 22%;">العين (Eye)</th>
                <th style="width: 20%;">Sphere (Sph)</th>
                <th style="width: 20%;">Cylinder (Cyl)</th>
                <th style="width: 18%;">Axis (°)</th>
                <th style="width: 20%;">Addition (Add)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="eye-name">اليمنى (OD) - Right</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_od_sphere ? sprintf("%+0.2f", $examination->ref_od_sphere) : '0.00' }}</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_od_cylinder ? sprintf("%+0.2f", $examination->ref_od_cylinder) : '0.00' }}</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_od_axis ? $examination->ref_od_axis . '°' : '-' }}</td>
                <td style="font-weight: bold; font-size: 15px; color: #198754;">{{ $examination->ref_od_add ? sprintf("%+0.2f", $examination->ref_od_add) : '-' }}</td>
            </tr>
            <tr>
                <td class="eye-name">اليسرى (OS) - Left</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_os_sphere ? sprintf("%+0.2f", $examination->ref_os_sphere) : '0.00' }}</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_os_cylinder ? sprintf("%+0.2f", $examination->ref_os_cylinder) : '0.00' }}</td>
                <td style="font-weight: bold; font-size: 15px;">{{ $examination->ref_os_axis ? $examination->ref_os_axis . '°' : '-' }}</td>
                <td style="font-weight: bold; font-size: 15px; color: #198754;">{{ $examination->ref_os_add ? sprintf("%+0.2f", $examination->ref_os_add) : '-' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="pd-section">
        <div>المسافة بين الحدقتين (Pupillary Distance): <strong>{{ $examination->pupillary_distance ? $examination->pupillary_distance . ' mm' : 'قياس قياسي' }}</strong></div>
        <div>نوع العدسات المقترح: <strong>طبية عاكسة / طبقات حماية (Anti-Reflective)</strong></div>
    </div>

    <div class="signature-area">
        <div style="font-size: 12px; color: #777;">
            صلاحية هذه الراشيتة 6 أشهر من تاريخ الفحص.<br>
            رقم الفحص المعتمد: #{{ $examination->id }}
        </div>
        <div class="doctor-signature">
            <div style="font-weight: bold; font-size: 14px;">{{ $examination->doctor->user->name ?? 'طبيب / أخصائي البصريات' }}</div>
            <div class="signature-line">التوقيع والختم الطبي</div>
        </div>
    </div>
</div>

<button onclick="window.print()" class="btn-print">🖨️ طباعة راشيتة النظارة</button>

<script>
    window.onload = function() {
        setTimeout(() => { window.print(); }, 500);
    };
</script>

</body>
</html>
