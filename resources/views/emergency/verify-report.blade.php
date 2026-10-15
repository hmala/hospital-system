<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق الرقمي من التقرير الطبي - مستشفى الكفاءات الأهلي</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Cairo', Tahoma, Arial, sans-serif;
        }

        body {
            direction: rtl;
            text-align: right;
            background: linear-gradient(135deg, #f0fdf4 0%, #e0f2fe 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 14px;
        }

        .verify-card {
            max-width: 580px;
            width: 100%;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 35px rgba(15, 118, 110, 0.12);
            overflow: hidden;
            border: 1px solid #ccfbf1;
        }

        .verify-header {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: #ffffff;
            padding: 24px 20px;
            text-align: center;
            position: relative;
        }

        .hospital-logo {
            width: 70px;
            height: 70px;
            background: #ffffff;
            border-radius: 50%;
            padding: 6px;
            margin: 0 auto 12px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        }
        .hospital-logo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }

        .verify-header h1 {
            font-size: 20px;
            font-weight: 900;
            margin-bottom: 2px;
        }
        .verify-header h2 {
            font-size: 13px;
            font-weight: 600;
            color: #ccfbf1;
        }

        /* Success Status Banner */
        .status-banner {
            background: #f0fdf4;
            border-bottom: 1px solid #bbf7d0;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .status-icon {
            width: 46px;
            height: 46px;
            background: #22c55e;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(34, 197, 94, 0.3);
        }
        .status-text h3 {
            font-size: 15px;
            font-weight: 800;
            color: #15803d;
            margin-bottom: 2px;
        }
        .status-text p {
            font-size: 12px;
            color: #166534;
            font-weight: 600;
        }

        /* Card Content */
        .verify-body {
            padding: 22px;
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
            font-size: 13px;
        }
        .data-table tr:not(:last-child) td {
            border-bottom: 1px solid #f1f5f9;
        }
        .data-table td {
            padding: 10px 14px;
        }
        .data-table .label-col {
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            width: 38%;
            border-left: 1px solid #e2e8f0;
        }
        .data-table .val-col {
            color: #0f172a;
            font-weight: 700;
        }

        /* Signature block inside verification */
        .doctor-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            margin-bottom: 16px;
        }
        .doctor-box h4 {
            font-size: 14px;
            font-weight: 800;
            color: #0f766e;
            margin-bottom: 2px;
        }
        .doctor-box p {
            font-size: 12px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .sig-img {
            max-height: 60px;
            max-width: 180px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
            mix-blend-mode: multiply;
        }

        .verify-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 14px 20px;
            text-align: center;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            line-height: 1.5;
        }
    </style>
</head>
<body>

@php
    $patientName = $emergency->patient?->user?->name ?? $emergency->emergencyPatient?->name ?? 'غير محدد';
    $emergencyId = 'EMR-' . str_pad($emergency->id, 6, '0', STR_PAD_LEFT);
    $doctorName = $doctor?->user?->name ?? $emergency->doctor?->user?->name ?? 'د. مهند إسماعيل';
    $doctorSpec = $doctor?->specialization ?? $emergency->doctor?->specialization ?? 'طب الطوارئ والحالات الحرجة';
    $signaturePath = $doctor?->signature ?? $emergency->doctor?->signature ?? 'images/signatures/doctor_244.png';
@endphp

<div class="verify-card">
    <!-- Header -->
    <div class="verify-header">
        <div class="hospital-logo">
            <img src="{{ asset('images/1.jpg') }}" alt="شعار المستشفى">
        </div>
        <h1>مستشفى الكفاءات الأهلي</h1>
        <h2>بوابة التحقق الرقمي المعتمد من التقارير الطبية</h2>
    </div>

    <!-- Status Banner -->
    <div class="status-banner">
        <div class="status-icon">
            <i class="fas fa-check"></i>
        </div>
        <div class="status-text">
            <h3>وثيقة رسمية معتمدة وموثقة</h3>
            <p>التقرير الطبي مسجل وصادر رسمياً من قسم الطوارئ بالنظام الإلكتروني للمستشفى.</p>
        </div>
    </div>

    <!-- Body -->
    <div class="verify-body">
        <table class="data-table">
            <tr>
                <td class="label-col"><i class="fas fa-hashtag text-primary me-1"></i> رقم التقرير</td>
                <td class="val-col"><strong style="color: #0f766e;">{{ $emergencyId }}</strong></td>
            </tr>
            <tr>
                <td class="label-col"><i class="fas fa-user text-secondary me-1"></i> اسم المريض</td>
                <td class="val-col" style="font-size: 14px; color: #0f172a;">{{ $patientName }}</td>
            </tr>
            <tr>
                <td class="label-col"><i class="fas fa-calendar-alt text-info me-1"></i> تاريخ وتوقيت الدخول</td>
                <td class="val-col">{{ $emergency->created_at->format('Y-m-d H:i') }}</td>
            </tr>
            <tr>
                <td class="label-col"><i class="fas fa-heartbeat text-danger me-1"></i> الفرز ودرجة الخطورة</td>
                <td class="val-col">
                    @if($emergency->priority === 'critical')
                        <span style="color: #b91c1c; font-weight: 800;">حالة حرجة (Critical)</span>
                    @elseif($emergency->priority === 'urgent')
                        <span style="color: #b45309; font-weight: 800;">حالة عاجلة (Urgent)</span>
                    @elseif($emergency->priority === 'semi_urgent')
                        <span style="color: #0369a1; font-weight: 800;">شبه عاجلة (Semi-urgent)</span>
                    @else
                        <span style="color: #15803d; font-weight: 800;">غير عاجلة (Non-urgent)</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label-col"><i class="fas fa-stethoscope text-primary me-1"></i> التشخيص السريري</td>
                <td class="val-col">{{ $emergency->diagnosis ?: ($emergency->initial_assessment ?: 'تمت المعاينة والتقييم السريري') }}</td>
            </tr>
            <tr>
                <td class="label-col"><i class="fas fa-door-open text-success me-1"></i> حالة الخروج</td>
                <td class="val-col">
                    @if($emergency->discharge_type === 'recovered')
                        <span style="color: #15803d; font-weight: 800;">خروج بعد الاستقرار والتعافي</span>
                    @elseif($emergency->discharge_type === 'against_medical_advice')
                        <span style="color: #b45309; font-weight: 800;">خروج على مسؤولية المريض</span>
                    @elseif($emergency->discharge_type === 'transferred_to_consultation')
                        <span style="color: #0369a1; font-weight: 800;">تحويل للعيادات الاستشارية</span>
                    @elseif($emergency->requires_surgery || $emergency->status === 'transferred_to_surgery')
                        <span style="color: #b91c1c; font-weight: 800;">تحويل لصالة العمليات</span>
                    @elseif($emergency->requires_admission || $emergency->status === 'transferred_to_admission')
                        <span style="color: #0369a1; font-weight: 800;">تحويل إلى الرقود</span>
                    @else
                        <span>مستمر بالرعاية الطارئة</span>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Attending Doctor Box -->
        <div class="doctor-box">
            <h4>الطبيب المعالج المعتمد: {{ $doctorName }}</h4>
            <p>{{ $doctorSpec }}</p>
            @if($signaturePath && file_exists(public_path($signaturePath)))
                <img src="{{ asset($signaturePath) }}" class="sig-img" alt="توقيع الطبيب">
            @elseif(file_exists(public_path('images/signatures/doctor_244.png')))
                <img src="{{ asset('images/signatures/doctor_244.png') }}" class="sig-img" alt="توقيع الطبيب">
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="verify-footer">
        <div>مستشفى الكفاءات الأهلي — نظام الأرشفة والتوثيق الطبي الإلكتروني الموحد</div>
        <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">
            تاريخ التحقق الفوري: {{ now()->format('Y-m-d H:i:s') }} | رقم التحقق الرقمي: {{ md5($emergency->id . '-' . $emergency->created_at) }}
        </div>
    </div>
</div>

</body>
</html>
