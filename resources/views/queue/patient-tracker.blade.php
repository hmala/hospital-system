<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع الدور الحي - مستشفى الكفاءات الأهلي</title>
    <!-- Google Fonts Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            color: #f8fafc;
            font-family: 'Cairo', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 16px 12px;
        }

        .tracker-card {
            background: rgba(30, 41, 59, 0.85);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .pulse-calling {
            border: 2px solid #10b981 !important;
            box-shadow: 0 0 35px rgba(16, 185, 129, 0.6) !important;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
            50% { transform: scale(1.02); box-shadow: 0 0 40px rgba(16, 185, 129, 0.8); }
            100% { transform: scale(1); box-shadow: 0 0 20px rgba(16, 185, 129, 0.4); }
        }

        .ticket-box {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.2) 0%, rgba(16, 185, 129, 0.2) 100%);
            border: 2px solid rgba(58, 134, 255, 0.4);
            border-radius: 20px;
            padding: 20px;
            text-align: center;
        }

        .ticket-number {
            font-size: 4rem;
            font-weight: 900;
            line-height: 1;
            color: #ffffff;
            text-shadow: 0 0 20px rgba(0, 240, 255, 0.6);
        }

        .telegram-btn {
            background: linear-gradient(135deg, #0088cc 0%, #00a2ed 100%);
            color: white;
            border-radius: 50px;
            font-weight: 800;
            transition: transform 0.2s;
        }
        .telegram-btn:hover {
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container py-2" style="max-width: 480px;">
        
        <!-- Header -->
        <div class="text-center mb-3">
            <h5 class="fw-bold mb-0 text-white"><i class="fas fa-hospital-alt text-success me-2"></i>مستشفى الكفاءات الأهلي</h5>
            <small class="text-white-50">نظام المتابعة الحية لطابور الانتظار</small>
        </div>

        @php
            $patient = $appointment->patient;
            $doctor = $appointment->doctor;
            $queueNum = $appointment->queue_number ?? $appointment->id;
            $telegramService = app(\App\Services\TelegramService::class);
            $deepLink = $telegramService->getAppointmentDeepLink($appointment);
        @endphp

        <!-- Main Card -->
        <div class="tracker-card p-4 mb-3" id="mainTrackerCard">
            <!-- Patient Info -->
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary border-opacity-25">
                <div>
                    <span class="text-white-50 small d-block">المراجع:</span>
                    <strong class="fs-6 text-white">{{ optional(optional($patient)->user)->name ?? 'عزيزي المراجع' }}</strong>
                </div>
                <div class="text-end">
                    <span class="text-white-50 small d-block">العيادة:</span>
                    <strong class="text-info">د. {{ optional(optional($doctor)->user)->name ?? 'الاستشاري' }}</strong>
                </div>
            </div>

            <!-- Ticket Box -->
            <div class="ticket-box mb-3">
                <span class="text-white-50 small d-block mb-1">رقم تذكرتك في الطابور</span>
                <div class="ticket-number" id="ticketNumber">#{{ $queueNum }}</div>
                <div class="mt-2" id="statusBadgeContainer">
                    <span class="badge bg-primary px-3 py-2 rounded-pill fs-7" id="statusBadge">بانتظار الدور</span>
                </div>
            </div>

            <!-- Live Stats Indicator -->
            <div class="p-3 bg-dark bg-opacity-50 rounded-3 border border-secondary border-opacity-25 text-center mb-3">
                <div class="row g-2">
                    <div class="col-6 border-end border-secondary border-opacity-25">
                        <small class="text-white-50 d-block">الرقم الحالي بالداخل</small>
                        <h4 class="fw-bold mb-0 text-warning" id="currentServingNum">-</h4>
                    </div>
                    <div class="col-6">
                        <small class="text-white-50 d-block">المرضى قبلك</small>
                        <h4 class="fw-bold mb-0 text-info" id="patientsAheadNum">-</h4>
                    </div>
                </div>
            </div>

            <!-- Realtime Advice / Calling Alert -->
            <div class="alert alert-secondary bg-opacity-10 border-0 rounded-3 text-center mb-0 p-3" id="alertBox">
                <i class="fas fa-sync-alt fa-spin me-2" id="alertIcon"></i>
                <span id="alertText">جاري تحديث حالة الطابور مباشرة...</span>
            </div>
        </div>

        <!-- Telegram Notifications Card -->
        <div class="tracker-card p-3 text-center">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <i class="fab fa-telegram text-info fs-4"></i>
                <strong class="text-white">تفعيل نداء التيليجرام</strong>
            </div>
            <p class="text-white-50 small mb-3">
                احصل على إشعار صوتي فوري عند مناداة دورك مع استلام نتائج التحاليل مباشرة.
            </p>
            <a href="{{ $deepLink }}" target="_blank" class="btn telegram-btn w-100 py-2">
                <i class="fab fa-telegram-plane me-1"></i> فتح المحادثة والبدء
            </a>
        </div>

    </div>

    <!-- Footer -->
    <div class="text-center text-white-50 small py-2">
        <i class="fas fa-clock me-1"></i> يتم التحديث تلقائياً كل 8 ثوانٍ
    </div>

    <!-- Audio Chime -->
    <audio id="callingChime" preload="auto">
        <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
    </audio>

    <script>
        const appointmentId = {{ $appointment->id }};
        const doctorId = {{ $appointment->doctor_id }};
        const myQueueNum = {{ (int)$queueNum }};
        let isCallingSoundPlayed = false;

        function updateQueueStatus() {
            fetch(`/queue/doctor/${doctorId}/data`, { cache: 'no-store' })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;

                    const current = data.current_patient;
                    const waiting = data.waiting_list || [];

                    // 1. Current serving number
                    const currentNum = current ? current.queue_number : '-';
                    document.getElementById('currentServingNum').textContent = '#' + currentNum;

                    // 2. Count ahead
                    let ahead = 0;
                    waiting.forEach(item => {
                        if (item.queue_number < myQueueNum) ahead++;
                    });
                    document.getElementById('patientsAheadNum').textContent = ahead;

                    // 3. Check if I am being called
                    const mainCard = document.getElementById('mainTrackerCard');
                    const statusBadge = document.getElementById('statusBadge');
                    const alertBox = document.getElementById('alertBox');
                    const alertText = document.getElementById('alertText');
                    const alertIcon = document.getElementById('alertIcon');

                    if (current && current.id === appointmentId && current.status === 'calling') {
                        // I AM BEING CALLED!
                        mainCard.classList.add('pulse-calling');
                        statusBadge.className = 'badge bg-success px-4 py-2 rounded-pill fs-6 animate__animated animate__heartBeat';
                        statusBadge.textContent = '🔔 حان دورك الآن! ادخل للعيادة';
                        
                        alertBox.className = 'alert alert-success border-0 rounded-3 text-center mb-0 p-3 shadow-sm';
                        alertIcon.className = 'fas fa-door-open fs-5 me-2';
                        alertText.innerHTML = '<strong>تفضل بالدخول لعيادة الطبيب فوراً!</strong>';

                        if (!isCallingSoundPlayed) {
                            try {
                                document.getElementById('callingChime').play();
                                if (navigator.vibrate) navigator.vibrate([300, 100, 300, 100, 500]);
                            } catch (e) {}
                            isCallingSoundPlayed = true;
                        }
                    } else if (ahead === 0 && (!current || current.id !== appointmentId)) {
                        // Next in line
                        mainCard.classList.remove('pulse-calling');
                        statusBadge.className = 'badge bg-warning text-dark px-3 py-2 rounded-pill fs-7';
                        statusBadge.textContent = 'أنت التالي في الطابور';
                        alertBox.className = 'alert alert-warning border-0 rounded-3 text-center mb-0 p-2 small';
                        alertIcon.className = 'fas fa-walking me-1';
                        alertText.textContent = 'يرجى التواجد أمام باب العيادة للاستعداد.';
                    } else {
                        mainCard.classList.remove('pulse-calling');
                        statusBadge.className = 'badge bg-primary px-3 py-2 rounded-pill fs-7';
                        statusBadge.textContent = 'بانتظار الدور';
                        alertBox.className = 'alert alert-secondary bg-opacity-10 border-0 rounded-3 text-center mb-0 p-2 small text-white-50';
                        alertIcon.className = 'fas fa-info-circle me-1';
                        alertText.textContent = `أمامك ${ahead} مراجعين في الانتظار (الوقت المقدر: ${ahead * 5} دقيقة).`;
                    }
                })
                .catch(err => console.error('Tracker sync error:', err));
        }

        // Initial call + recurring poll
        updateQueueStatus();
        setInterval(updateQueueStatus, 8000);
    </script>
</body>
</html>
