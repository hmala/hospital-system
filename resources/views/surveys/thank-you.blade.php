<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شكراً لك | مستشفى الكفاءات الأهلي</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #1e293b;
        }
        .thank-you-card {
            background: #ffffff;
            border-radius: 28px;
            padding: 40px 24px;
            text-align: center;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.5s ease-out;
        }
        .icon-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #dcfce7;
            color: #16a34a;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 42px;
            margin-bottom: 24px;
            box-shadow: 0 10px 20px rgba(22, 163, 74, 0.15);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="thank-you-card">
        <div class="icon-circle">
            <i class="fas fa-check"></i>
        </div>

        <h4 class="fw-bold mb-2 text-dark">
            {{ !empty($alreadyCompleted) ? 'تم استلام تقييمك مسبقاً' : 'شكراً جزيلاً لك!' }}
        </h4>
        
        <p class="text-muted mb-4 fs-6">
            {{ !empty($alreadyCompleted) 
                ? 'لقد تم تسجيل وإرسال استبيان هذا الرقود مسبقاً بنجاح وحفظه في ملفك السريري.' 
                : 'نشكرك من القلب على وقتك وملاحظاتك الثمينة. رأيك أمانة نعتز بها ويساعدنا دائماً على تقديم أرقى مستويات الرعاية الصحية.' }}
        </p>

        <div class="p-3 bg-light rounded-4 mb-4 text-start">
            <div class="d-flex justify-content-between mb-1.5 small">
                <span class="text-muted">المستشفى:</span>
                <span class="fw-bold text-dark">مستشفى الكفاءات الأهلي</span>
            </div>
            @if($survey->room)
                <div class="d-flex justify-content-between mb-1.5 small">
                    <span class="text-muted">الغرفة:</span>
                    <span class="fw-bold text-dark">غرفة {{ $survey->room->room_number }} ({{ $survey->location?->name ?? 'الرقود' }})</span>
                </div>
            @endif
            <div class="d-flex justify-content-between small">
                <span class="text-muted">حالة الاستبيان:</span>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-0.5">
                    <i class="fas fa-check-circle me-1"></i> مكتمل ومسجل
                </span>
            </div>
        </div>

        <div class="text-muted small">
            <i class="fas fa-heart text-danger me-1"></i> نتمنى لك دوام الصحة والعافية والسلامة دائماً.
        </div>
    </div>

</body>
</html>

