<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج تحليل طوارئ - {{ $emergencyLab->patient->user->name }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Arial','Tahoma',sans-serif;direction:rtl;background:#fff;color:#333;padding:20px;}
        .page{max-width:210mm;min-height:297mm;margin:0 auto;background:white;position:relative;padding:20mm 20mm 35mm 20mm;}
        .page::before{content:'';position:absolute;inset:0;background-image:url('{{ asset('images/1.jpg') }}');background-repeat:no-repeat;background-position:center center;background-size:55% auto;opacity:0.08;pointer-events:none;z-index:0;}
        .page>*{position:relative;z-index:1;}
        .decorative-border{position:absolute;top:10mm;right:10mm;bottom:10mm;left:10mm;border:2px solid #1e7e8f;pointer-events:none;}
        .header{text-align:center;margin-bottom:15px;padding-bottom:10px;border-bottom:2px solid #1e7e8f;}
        .logo-row{display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:6px;position:relative;width:100%;min-height:80px;}
        .logo{width:140px;height:80px;margin:0;position:absolute;left:50%;transform:translateX(-50%);top:0;}
        .logo img{width:100%;height:100%;object-fit:contain;}
        .lab-badge{display:flex;align-items:center;gap:8px;background:#e8f4f4;color:#1e7e8f;border:1px solid #1e7e8f;padding:8px 12px;border-radius:20px;font-weight:bold;white-space:nowrap;position:absolute;right:0;top:8px;}
        .hospital-name-ar{font-size:22px;font-weight:bold;color:#333;margin-bottom:4px;letter-spacing:0.5px;}
        .hospital-name-en{font-size:14px;color:#888;font-style:normal;margin-bottom:8px;}
        .document-title{font-size:16px;font-weight:bold;color:#333;margin-top:8px;}
        .document-info{display:flex;justify-content:space-between;margin-bottom:15px;padding:6px 0;}
        .info-item{display:flex;align-items:center;gap:10px;}
        .info-label{font-weight:bold;color:#666;}
        .info-value{color:#333;}
        .patient-info{background:rgba(248,249,250,0.55);border:1px solid #dee2e6;border-radius:8px;padding:20px;margin-bottom:25px;}
        .patient-info h3{color:#1e7e8f;font-size:18px;margin-bottom:15px;padding-bottom:10px;border-bottom:2px solid #1e7e8f;}
        .patient-details{display:grid;grid-template-columns:repeat(2,1fr);gap:15px;}
        .detail-item{display:flex;gap:10px;}
        .detail-label{font-weight:bold;color:#555;min-width:100px;}
        .detail-value{color:#333;}
        .results-section{margin-bottom:25px;}
        .results-table{width:100%;border-collapse:collapse;border:2px solid #1e7e8f;margin-bottom:20px;background:white;}
        .results-table tr{border-bottom:1px solid #dee2e6;}
        .results-table tr:last-child{border-bottom:none;}
        .results-table td{padding:12px 15px;vertical-align:top;}
        .results-table td:first-child{background:#e8f4f4;color:#1e7e8f;font-weight:bold;width:200px;border-left:3px solid #1e7e8f;}
        .results-table td:last-child{color:#333;line-height:1.6;white-space:pre-wrap;}
        .footer{position:absolute;bottom:20mm;left:20mm;right:20mm;border-top:2px solid #1e7e8f;padding-top:15px;}
        .footer-content{display:flex;justify-content:space-between;align-items:center;font-size:12px;color:#666;}
        .contact-info{display:flex;gap:20px;}
        .contact-item{display:flex;align-items:center;gap:5px;}
        .barcode{text-align:center;font-size:18px;font-weight:bold;color:#1e7e8f;}
        .print-buttons{position:fixed;top:20px;left:20px;display:flex;gap:10px;z-index:1000;}
        .btn{padding:10px 20px;border:none;border-radius:5px;cursor:pointer;font-size:14px;font-weight:bold;display:flex;align-items:center;gap:8px;}
        .btn-print{background:#1e7e8f;color:white;}
        .btn-pdf{background:#dc3545;color:white;}
        .btn-back{background:#6c757d;color:white;}
        .btn:hover{opacity:0.9;}
        @media print{.print-buttons{display:none!important;}body{padding:0;margin:0;-webkit-print-color-adjust:exact;print-color-adjust:exact;}.page{margin:0;padding:20mm 20mm 40mm 20mm;max-width:100%;min-height:100vh;-webkit-print-color-adjust:exact;print-color-adjust:exact;page-break-after:always;}.page:last-child{page-break-after:auto;}@page{size:A4 portrait;margin:0;}.decorative-border{top:10mm;right:10mm;bottom:10mm;left:10mm;}.footer{position:absolute;bottom:15mm;left:20mm;right:20mm;}}</style>
</head>
<body>
@php
    // Build a labResults-like collection from emergency pivot data
    $labResults = $emergencyLab->labTests->map(function($t){
        $raw = $t->pivot->result;
        $data = is_string($raw) ? json_decode($raw,true) : $raw;
        return (object)[
            'test_name' => $t->name,
            'value' => $data['value'] ?? '',
            'unit' => $data['unit'] ?? $t->unit,
            'reference_range' => $data['reference_range'] ?? '',
            'status' => $data['status'] ?? '',
            'notes' => $data['notes'] ?? '',
            'image_path' => $t->pivot->image_path ?? null,
        ];
    });
@endphp

<div class="print-buttons">
    <button class="btn btn-print" onclick="window.print()">🖨️ طباعة</button>
    <button class="btn btn-pdf" onclick="generatePDF()">📄 حفظ PDF</button>
    <button class="btn btn-back" onclick="window.history.back()">← رجوع</button>
</div>

<div class="page">
    <div class="decorative-border"><div class="corner-pattern top-right"></div><div class="corner-pattern bottom-left"></div></div>
    <div class="header">
        <div class="logo-row"><div class="logo"><img src="{{ asset('images/1.jpg') }}" alt="Hospital Logo"></div><div class="lab-badge">🧪 المختبر</div></div>
        <div class="hospital-name-ar">مستشفى الكفاءات الاهلي</div>
        <div class="hospital-name-en">Al-Kafaat Private Hospital</div>
        <div class="document-title">نتائج تحاليل الطوارئ</div>
    </div>
    <div class="document-info">
        <div class="info-item"><span class="info-label">التاريخ:</span><span class="info-value">{{ optional($emergencyLab->completed_at ?? $emergencyLab->requested_at)->format('Y-m-d') }}</span></div>
        <div class="info-item"><span class="info-label">رقم الطلب:</span><span class="info-value">ELAB-{{ $emergencyLab->id }}</span></div>
    </div>
    <div class="patient-info">
        <h3>معلومات المريض</h3>
        <div class="patient-details">
            <div class="detail-item"><span class="detail-label">الاسم:</span><span class="detail-value">{{ $emergencyLab->patient->user->name }}</span></div>
            <div class="detail-item"><span class="detail-label">العمر:</span><span class="detail-value">{{ $emergencyLab->patient->age??'-' }} سنة</span></div>
            <div class="detail-item"><span class="detail-label">الجنس:</span><span class="detail-value">@if(($emergencyLab->patient->gender ?? null)==='male') ذكر @elseif(($emergencyLab->patient->gender ?? null)==='female') أنثى @else - @endif</span></div>
            <div class="detail-item"><span class="detail-label">رقم الهاتف:</span><span class="detail-value">{{ $emergencyLab->patient->phone??'-' }}</span></div>
            <div class="detail-item"><span class="detail-label">الطبيب المعالج:</span><span class="detail-value">قسم الطوارئ</span></div>
            <div class="detail-item"><span class="detail-label">تاريخ الطلب:</span><span class="detail-value">{{ optional($emergencyLab->requested_at)->format('Y-m-d') }}</span></div>
        </div>
    </div>
    <div class="results-section">
        <h3>نتائج الفحوصات المختبرية</h3>
        <table class="results-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>اسم الفحص</th>
                    <th>النتيجة</th>
                    <th>الوحدة</th>
                    <th>المدى الطبيعي</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                @foreach($labResults as $index => $res)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $res->test_name }}</strong></td>
                    <td>{{ $res->value }}</td>
                    <td>{{ $res->unit }}</td>
                    <td>{{ $res->reference_range }}</td>
                    <td>
                        @if($res->status)
                            <span class="status-{{ $res->status }}">
                                {{ $res->status == 'normal' ? '✓ طبيعي' : ($res->status == 'high' ? '↑ مرتفع' : '↓ منخفض') }}
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($labResults->contains('image_path'))
    <div class="page">
        <div class="decorative-border"><div class="corner-pattern top-right"></div><div class="corner-pattern bottom-left"></div></div>
        <div class="header"><div class="hospital-name-ar">صور التحاليل</div><div class="hospital-name-en">Test Images</div></div>
        <div class="images-section"><div class="images-grid">
            @foreach($labResults as $r)
                @if($r->image_path)
                    <div class="image-item">
                        <?php $ext = strtolower(pathinfo($r->image_path, PATHINFO_EXTENSION)); ?>
                        @if(!in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']))
                            <div style="padding:50px;text-align:center;background:#f8f9fa;">
                                📄 {{ basename($r->image_path) }}
                            </div>
                        @else
                            <img src="{{ asset('storage/' . implode('/', array_map('rawurlencode', explode('/', ltrim($r->image_path,'/'))))) }}" alt="{{ $r->test_name }}">
                        @endif
                    </div>
                @endif
            @endforeach
        </div></div>
    </div>
    @endif
    <div class="footer"><div class="footer-content"><div class="contact-info"><div class="contact-item">📞 +964 (0) 778 050 7060</div><div class="contact-item">📧 info@alkafaathospital.com</div><div class="contact-item">📍 بغداد - الحارثية - شارع الكندي</div></div><div class="barcode">ELAB-{{ $emergencyLab->id }}</div></div></div>
</div>
<script>function generatePDF(){window.print();}</script>
</body>
</html>