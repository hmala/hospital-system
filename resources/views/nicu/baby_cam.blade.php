<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بث مباشر لطفلكم الحبيب - قسم الخدج</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0b132b;
            color: #fff;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 15px;
        }
        .cam-container {
            max-width: 600px;
            width: 100%;
            background: #1c2541;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .video-wrapper {
            position: relative;
            width: 100%;
            height: 340px;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .timer-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
            padding: 6px 14px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            z-index: 10;
        }
        .rec-dot {
            width: 10px;
            height: 10px;
            background: #fff;
            border-radius: 50%;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.2; }
            100% { opacity: 1; }
        }
        .reassurance-card {
            display: none;
            padding: 40px 25px;
            text-align: center;
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .heart-icon {
            font-size: 65px;
            color: #198754;
            margin-bottom: 20px;
            animation: heartBeat 1.5s infinite;
        }
        @keyframes heartBeat {
            0% { transform: scale(1); }
            14% { transform: scale(1.15); }
            28% { transform: scale(1); }
            42% { transform: scale(1.15); }
            70% { transform: scale(1); }
        }
    </style>
</head>
<body>

<div class="cam-container">
    <!-- رأس الشاشة -->
    <div class="p-3 text-center border-bottom border-secondary border-opacity-25">
        <h5 class="mb-1 fw-bold text-info"><i class="fas fa-video me-2"></i> كاميرا الاطمئنان - قسم الخدج</h5>
        <p class="mb-0 small text-light opacity-75">
            الطفل: <strong>{{ $reservation->baby_name }}</strong> | حاضنة رقم {{ $reservation->incubator->incubator_number }}
        </p>
    </div>

    <!-- نافذة الفيديو المباشر الموقوت -->
    <div id="videoSection">
        <div class="video-wrapper">
            <div class="timer-badge">
                <span class="rec-dot"></span>
                <span>مباشر: <span id="timerDisplay">01:00</span></span>
            </div>

            @if($reservation->incubator->camera_stream_url)
                <iframe src="{{ $reservation->incubator->camera_stream_url }}" style="width:100%; height:100%; border:none;"></iframe>
            @else
                <!-- شاشة عرض الفيديو المحاكية لكاميرا الحاضنة -->
                <div class="text-center p-4">
                    <i class="fas fa-baby fa-4x text-info mb-3 opacity-75"></i>
                    <h6 class="fw-bold mb-1">بث مباشر لحاضنة الطفل</h6>
                    <p class="small text-muted mb-0">كاميرا المراقبة السريرية التخصصية نشطة</p>
                    <div class="spinner-grow spinner-grow-sm text-info mt-3" role="status"></div>
                </div>
            @endif
        </div>

        <div class="p-3 text-center bg-black bg-opacity-25">
            <small class="text-light opacity-75">
                <i class="fas fa-info-circle me-1 text-warning"></i>
                مدة المشاهدة المخصصة دقيقة واحدة حرصاً على استقرار الطفل وتقليل التعرض لشبكات الاتصال.
            </small>
        </div>
    </div>

    <!-- بطاقة الطمأنينة الدافئة (تظهر بعد انتهاء الـ 60 ثانية) -->
    <div id="reassuranceSection" class="reassurance-card">
        <div class="heart-icon">
            <i class="fas fa-hand-holding-heart"></i>
        </div>
        <h4 class="fw-bold text-success mb-3">الحمد لله على سلامته.. طفلكم في أيدٍ أمينة</h4>
        <p class="lead mb-4" style="line-height: 1.8; font-size: 16px;">
            طفلكم الحبيب <strong>({{ $reservation->baby_name }})</strong> يتلقى أفضل رعاية طبية وتمريضية متواصلة على مدار 24 ساعة تحت إشراف نخبة من أطباء واستشاريي الخدج والعناية المركزة.
        </p>
        <div class="p-3 bg-black bg-opacity-30 rounded-3 mb-4 text-start small">
            <p class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> العلامات الحيوية والتنفس تحت المراقبة اللحظية.</p>
            <p class="mb-1"><i class="fas fa-check-circle text-success me-2"></i> جدول التغذية والرضاعة منتظم بإشراف الكادر.</p>
            <p class="mb-0"><i class="fas fa-check-circle text-success me-2"></i> بيئة الحاضنة معقمة ومثالية لنموه السليم.</p>
        </div>
        <p class="small text-light opacity-75 mb-0">
            نسأل الله تعالى أن يقر أعينكم بسلامته وخروجه قريباً إلى حضنكم 💚
        </p>
    </div>
</div>

<script>
let timeLeft = 60; // 60 seconds (1 minute)
const timerDisplay = document.getElementById('timerDisplay');
const videoSection = document.getElementById('videoSection');
const reassuranceSection = document.getElementById('reassuranceSection');

const countdown = setInterval(function() {
    timeLeft--;
    let minutes = Math.floor(timeLeft / 60);
    let seconds = timeLeft % 60;
    timerDisplay.textContent = `${minutes < 10 ? '0' : ''}${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;

    if (timeLeft <= 0) {
        clearInterval(countdown);
        // قطع البث وإخفاء الفيديو فوراً
        videoSection.style.display = 'none';
        videoSection.innerHTML = ''; // تفريغ عناصر الفيديو لقطع الاتصال
        // إظهار بطاقة الطمأنينة
        reassuranceSection.style.display = 'block';
    }
}, 1000);
</script>
</body>
</html>
