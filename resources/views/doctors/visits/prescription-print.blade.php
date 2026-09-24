<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>وصفة طبية إلكترونية - {{ $prescription->prescription_number ?? 'RX-' . $visit->id }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #1a1a1a;
            font-size: 13px;
        }

        .prescription-container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            position: relative;
            border-top: 6px solid #0d6efd;
        }

        .rx-watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 140px;
            font-weight: 900;
            color: rgba(13, 110, 253, 0.04);
            pointer-events: none;
            user-select: none;
            font-family: 'Times New Roman', serif;
        }

        .header-logo {
            font-size: 24px;
            font-weight: 800;
            color: #0d6efd;
        }

        .info-box {
            background-color: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 20px;
        }

        .rx-symbol {
            font-size: 32px;
            font-weight: 900;
            font-family: 'Times New Roman', serif;
            color: #0d6efd;
            line-height: 1;
        }

        .table-meds {
            margin-bottom: 20px;
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
        }

        .table-meds th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 700;
            border-bottom: 2px solid #cbd5e1;
            padding: 10px 12px;
        }

        .table-meds td {
            padding: 10px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .med-number {
            display: inline-block;
            width: 24px;
            height: 24px;
            line-height: 24px;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 50%;
            text-align: center;
            font-weight: bold;
            font-size: 11px;
        }

        .footer-sign {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e1;
        }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .prescription-container {
                box-shadow: none;
                margin: 0;
                padding: 15px;
                max-width: 100%;
                border-top: 4px solid #0d6efd;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="container py-3 no-print">
    <div class="d-flex justify-content-between align-items-center max-w-800 mx-auto" style="max-width: 800px;">
        <a href="{{ route('doctor.visits.show', $visit) }}" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-right me-1"></i> العودة للكشف
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm px-4">
                <i class="fas fa-print me-1"></i> طباعة الوصفة (Ctrl+P)
            </button>
        </div>
    </div>
</div>

<div class="prescription-container">
    <div class="rx-watermark">℞</div>

    <!-- ترويسة الوصفة -->
    <div class="row align-items-center mb-3 pb-3 border-bottom">
        <div class="col-7">
            <div class="header-logo"><i class="fas fa-hospital me-2"></i>نظام المستشفى المتكامل</div>
            <div class="text-muted small">العيادات الاستشارية والتخصصية | E-Prescription</div>
        </div>
        <div class="col-5 text-start">
            <div class="fw-bold text-primary fs-6">{{ $prescription->prescription_number ?? ('RX-' . date('Ymd') . '-' . str_pad($visit->id, 4, '0', STR_PAD_LEFT)) }}</div>
            <div class="text-muted small">التاريخ: {{ $visit->visit_date ? $visit->visit_date->format('Y/m/d') : date('Y/m/d') }}</div>
        </div>
    </div>

    <!-- معلومات المريض والطبيب -->
    <div class="info-box">
        <div class="row g-2">
            <div class="col-6 col-md-3">
                <span class="text-muted d-block small">اسم المريض:</span>
                <strong>{{ optional($visit->patient)->user->name ?? 'مريض غير مسجل' }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block small">العمر / الجنس:</span>
                <span>{{ optional($visit->patient)->age ? optional($visit->patient)->age . ' سنة' : '-' }} / {{ optional($visit->patient)->gender === 'female' ? 'أنثى' : 'ذكر' }}</span>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block small">الطبيب المعالج:</span>
                <strong>د. {{ optional($visit->doctor)->user->name ?? 'الطبيب الاستشاري' }}</strong>
            </div>
            <div class="col-6 col-md-3">
                <span class="text-muted d-block small">التخصص / العيادة:</span>
                <span>{{ optional($visit->doctor)->specialization ?? 'استشارية' }}</span>
            </div>
        </div>
    </div>

    <!-- التشخيص والعلامات إن وجدت -->
    @if($visit->diagnosis)
        <div class="mb-3 p-2 bg-light rounded border-start border-3 border-primary">
            <span class="fw-bold text-primary"><i class="fas fa-stethoscope me-1"></i>التشخيص الطبي:</span>
            <span class="ms-1">{{ is_array($visit->diagnosis) ? ($visit->diagnosis['description'] ?? json_encode($visit->diagnosis, JSON_UNESCAPED_UNICODE)) : $visit->diagnosis }}</span>
        </div>
    @endif

    <!-- جدول الأدوية الموصوفة -->
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="rx-symbol">℞</span>
        <h6 class="fw-bold mb-0 text-dark">الأدوية الموصوفة (Medications)</h6>
    </div>

    <table class="table-meds">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 40%;">اسم الدواء والجرعة</th>
                <th style="width: 15%;">التكرار</th>
                <th style="width: 15%;">المدة</th>
                <th style="width: 25%;">تعليمات الاستعمال</th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = $prescription && $prescription->items->count() > 0 
                    ? $prescription->items 
                    : $visit->prescribedMedications->where('item_type', 'medication');
            @endphp

            @forelse($items as $idx => $item)
                <tr>
                    <td><span class="med-number">{{ $idx + 1 }}</span></td>
                    <td>
                        <strong class="text-primary fs-6">{{ $item->medicine ? $item->medicine->name : ($item->name ?? '-') }}</strong>
                        @if($item->medicine && $item->medicine->generic_name)
                            <div class="text-muted small">({{ $item->medicine->generic_name }})</div>
                        @endif
                        @if($item->dosage)
                            <div class="text-secondary small">الجرعة: {{ $item->dosage }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            {{ $item->dosage_frequency ?? ($item->frequency ? ($item->frequency . ' مرات يومياً') : '-') }}
                        </span>
                    </td>
                    <td>{{ $item->duration_days ? ($item->duration_days . ' أيام') : ($item->duration ?? '-') }}</td>
                    <td>
                        <div class="small fw-semibold text-dark">{{ $item->instructions ?: 'حسب إرشادات الطبيب' }}</div>
                        @if(!empty($item->times))
                            <div class="text-muted small">{{ $item->times }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">لا توجد أدوية موصوفة في هذه الزيارة</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- خطة وتوصيات إضافية -->
    @if($visit->treatment_plan)
        <div class="mb-3 p-2 bg-light rounded">
            <span class="fw-bold text-secondary"><i class="fas fa-clipboard-list me-1"></i>توصيات وخطة المتابعة:</span>
            <p class="mb-0 mt-1 small">{{ $visit->treatment_plan }}</p>
        </div>
    @endif

    <!-- تذييل التوقيع وختم الصيدلية -->
    <div class="row footer-sign align-items-end">
        <div class="col-6">
            <div class="text-muted small mb-1">ختم وصرف الصيدلية:</div>
            <div style="height: 50px; border: 1px dashed #cbd5e1; border-radius: 4px; display: flex; align-items: center; justify-content: center; color: #94a3b8; font-size: 11px;">
                مساحة ختم الصيدلية وتاريخ الصرف
            </div>
        </div>
        <div class="col-6 text-start">
            <div class="text-muted small">توقيع الطبيب المعالج:</div>
            <div class="fw-bold mt-2">د. {{ optional($visit->doctor)->user->name ?? '...........................' }}</div>
            <div class="text-muted small">الرمز: {{ optional($visit->doctor)->license_number ?? 'MD-' . $visit->doctor_id }}</div>
        </div>
    </div>
</div>

</body>
</html>
