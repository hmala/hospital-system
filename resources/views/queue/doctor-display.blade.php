<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شاشة الانتظار - عيادة {{ $doctor->user ? $doctor->user->name : 'الطبيب' }}</title>
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #0b132b;
            --bg-card: #1c2541;
            --bg-card-glow: #1e293b;
            --primary: #3a86ff;
            --primary-glow: rgba(58, 134, 255, 0.4);
            --success: #10b981;
            --success-glow: rgba(16, 185, 129, 0.4);
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #00f0ff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Cairo', sans-serif;
        }

        body {
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            user-select: none;
        }

        /* Top Header */
        .display-header {
            background: rgba(28, 37, 65, 0.92);
            backdrop-filter: blur(12px);
            border-bottom: 2px solid rgba(58, 134, 255, 0.35);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 20px rgba(0,0,0,0.4);
            gap: 15px;
            flex-wrap: wrap;
        }

        .hospital-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .hospital-logo {
            width: 44px;
            height: 44px;
            object-fit: contain;
            filter: drop-shadow(0 0 8px rgba(0, 240, 255, 0.5));
        }

        .hospital-title h1 {
            font-size: 1.3rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .hospital-title p {
            font-size: 0.8rem;
            color: var(--accent);
            font-weight: 700;
        }

        .clinic-info-badge {
            background: linear-gradient(135deg, rgba(58, 134, 255, 0.18) 0%, rgba(16, 185, 129, 0.18) 100%);
            border: 1px solid rgba(58, 134, 255, 0.4);
            padding: 6px 20px;
            border-radius: 50px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .clinic-info-badge .doc-name {
            font-size: 1.2rem;
            font-weight: 900;
            color: #ffffff;
        }

        .clinic-info-badge .doc-spec {
            font-size: 0.8rem;
            color: #6ee7b7;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .room-pill {
            background: rgba(0, 240, 255, 0.2);
            color: var(--accent);
            padding: 1px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        .header-tools {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .tool-btn {
            background: rgba(58, 134, 255, 0.15);
            border: 1px solid rgba(58, 134, 255, 0.4);
            color: #93c5fd;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .tool-btn:hover {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            transform: translateY(-1px);
        }

        .tool-btn.active {
            background: rgba(16, 185, 129, 0.25);
            border-color: var(--success);
            color: #6ee7b7;
        }

        .tool-btn.speaking {
            animation: pulseGlow 1.2s infinite;
            border-color: #38bdf8;
            color: #38bdf8;
        }

        .live-clock-box {
            text-align: left;
            direction: ltr;
            background: rgba(11, 19, 43, 0.85);
            padding: 4px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .live-time {
            font-size: 1.4rem;
            font-weight: 900;
            color: #ffffff;
            letter-spacing: 1px;
            line-height: 1.2;
            text-shadow: 0 0 10px var(--primary-glow);
        }

        .live-date {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 600;
            direction: rtl;
        }

        /* Main Screen Layout - Balanced Proportions */
        .display-content {
            flex: 1;
            display: grid;
            grid-template-columns: 1.35fr 1fr;
            gap: 20px;
            padding: 18px 24px;
            min-height: 0;
            overflow: hidden;
        }

        /* Current Patient Serving Card */
        .current-serving-card {
            background: var(--bg-card);
            border: 2px solid rgba(58, 134, 255, 0.4);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px 28px;
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.45);
            transition: all 0.3s ease;
        }

        .current-serving-card.calling-pulse {
            border-color: var(--success);
            box-shadow: 0 0 35px var(--success-glow);
            animation: pulseGlow 1.8s infinite;
        }

        @keyframes pulseGlow {
            0% { box-shadow: 0 0 20px var(--success-glow); }
            50% { box-shadow: 0 0 45px rgba(16, 185, 129, 0.75); }
            100% { box-shadow: 0 0 20px var(--success-glow); }
        }

        .card-header-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid var(--success);
            color: #6ee7b7;
            padding: 5px 16px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 800;
        }

        .status-pill.calling {
            background: rgba(58, 134, 255, 0.2);
            border-color: var(--primary);
            color: #93c5fd;
            animation: blinker 1s linear infinite;
        }

        .emergency-pill {
            background: rgba(239, 68, 68, 0.25);
            border: 1px solid var(--danger);
            color: #fca5a5;
            padding: 5px 16px;
            border-radius: 30px;
            font-size: 1rem;
            font-weight: 800;
            animation: blinker 0.8s linear infinite;
        }

        @keyframes blinker {
            50% { opacity: 0.4; }
        }

        .serving-body {
            text-align: center;
            margin: auto 0;
            padding: 10px 0;
        }

        .ticket-number-label {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-weight: 700;
            margin-bottom: 6px;
        }

        .ticket-number-badge {
            font-size: 4.8rem;
            font-weight: 900;
            line-height: 1;
            color: #ffffff;
            text-shadow: 0 0 25px var(--accent);
            display: inline-block;
            background: rgba(0, 240, 255, 0.08);
            border: 2px solid rgba(0, 240, 255, 0.3);
            border-radius: 20px;
            padding: 8px 36px;
            margin-bottom: 14px;
        }

        .serving-patient-name {
            font-size: 2.4rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 8px;
            line-height: 1.25;
            text-shadow: 0 2px 8px rgba(0,0,0,0.5);
        }

        .serving-instructions {
            font-size: 1.25rem;
            color: #a7f3d0;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .card-footer-stats {
            display: flex;
            justify-content: space-around;
            background: rgba(11, 19, 43, 0.65);
            border-radius: 12px;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.06);
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .val {
            font-size: 1.35rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.2;
        }

        .stat-item .lbl {
            font-size: 0.78rem;
            color: var(--text-muted);
            font-weight: 700;
        }

        /* Waiting Queue Column (Side) */
        .waiting-queue-panel {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            min-height: 0;
        }

        .queue-panel-header {
            background: rgba(11, 19, 43, 0.75);
            padding: 14px 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .queue-panel-header h2 {
            font-size: 1.15rem;
            font-weight: 800;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .queue-panel-header .waiting-badge-count {
            background: var(--primary);
            color: white;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 800;
        }

        .queue-list-container {
            flex: 1;
            padding: 12px 14px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        /* Custom Sleek Dark Scrollbar */
        .queue-list-container::-webkit-scrollbar {
            width: 6px;
        }
        .queue-list-container::-webkit-scrollbar-track {
            background: rgba(11, 19, 43, 0.4);
            border-radius: 4px;
        }
        .queue-list-container::-webkit-scrollbar-thumb {
            background: rgba(58, 134, 255, 0.35);
            border-radius: 4px;
        }
        .queue-list-container::-webkit-scrollbar-thumb:hover {
            background: rgba(58, 134, 255, 0.65);
        }

        .queue-row {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.2s ease;
        }

        .queue-row:first-child {
            background: rgba(58, 134, 255, 0.14);
            border-color: rgba(58, 134, 255, 0.4);
            box-shadow: 0 0 12px rgba(58, 134, 255, 0.15);
        }

        .queue-row.emergency-row {
            background: rgba(239, 68, 68, 0.16);
            border-color: rgba(239, 68, 68, 0.45);
        }

        .queue-row-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .queue-seq-num {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 1rem;
        }

        .queue-patient-name {
            font-size: 1.02rem;
            font-weight: 800;
            color: #f1f5f9;
        }

        .queue-row-status {
            font-size: 0.8rem;
            color: var(--text-muted);
            font-weight: 700;
        }

        .empty-queue-msg {
            text-align: center;
            color: var(--text-muted);
            margin: auto 0;
            font-size: 1rem;
            font-weight: 700;
            padding: 30px;
        }

        /* Bottom Guidance Ticker Bar */
        .display-ticker {
            background: rgba(15, 23, 42, 0.95);
            border-top: 1px solid rgba(58, 134, 255, 0.3);
            height: 38px;
            display: flex;
            align-items: center;
            overflow: hidden;
            padding: 0 20px;
            z-index: 50;
        }

        .ticker-label {
            background: var(--primary);
            color: #ffffff;
            font-size: 0.78rem;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 6px;
            margin-left: 14px;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 0 10px rgba(58, 134, 255, 0.35);
        }

        .ticker-track {
            flex: 1;
            overflow: hidden;
            white-space: nowrap;
        }

        .ticker-text {
            display: inline-block;
            animation: marquee 35s linear infinite;
            font-size: 0.88rem;
            color: #cbd5e1;
            font-weight: 700;
        }

        .ticker-text span {
            margin-left: 45px;
        }

        @keyframes marquee {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Sound Enable Floating Banner */
        #audio-enable-banner {
            position: fixed;
            top: 14px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #1e40af, #2563eb);
            color: #ffffff;
            padding: 8px 22px;
            font-size: 0.85rem;
            font-weight: 800;
            border-radius: 30px;
            z-index: 99999;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.25);
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        #audio-enable-banner:hover {
            transform: translateX(-50%) scale(1.03);
            background: #1d4ed8;
        }
    </style>
</head>
<body>

    <!-- Audio Enable Banner for Browser Autoplay Policy -->
    <div id="audio-enable-banner" onclick="enableAudio()">
        <i class="fas fa-volume-high"></i> انقر هنا لتفعيل النداء الصوتي والتنبيهات للشاشة
    </div>

    <!-- Top Header -->
    <header class="display-header">
        <div class="hospital-brand">
            <img src="{{ asset('images/لوغو.png') }}" class="hospital-logo" alt="Logo" onerror="this.src='{{ asset('images/hospital-logo.svg') }}';">
            <div class="hospital-title">
                <h1>مستشفى الكفاءات الأهلي</h1>
                <p>قسم العيادات الاستشارية التخصصية</p>
            </div>
        </div>

        <div class="clinic-info-badge">
            <div class="doc-name">د. {{ $doctor->user ? $doctor->user->name : 'الطبيب' }}</div>
            <div class="doc-spec">
                <span class="room-pill"><i class="fas fa-door-open me-1"></i> {{ $doctor->department ? $doctor->department->name : 'العيادة' }}</span>
                <span>{{ $doctor->specialization ?: 'استشاري' }}</span>
            </div>
        </div>

        <div class="header-tools">
            <button type="button" class="tool-btn" id="guidance-voice-btn" onclick="toggleGuidanceVoice()" title="تفعيل/إيقاف نطق الإرشادات الدورية">
                <i class="fas fa-volume-up" id="guidance-voice-icon"></i> <span id="guidance-voice-text">إرشادات صوتية: مفعّلة</span>
            </button>
            <button type="button" class="tool-btn" onclick="testAudioAndCall()" title="اختبار النداء الصوتي">
                <i class="fas fa-volume-high"></i> تجربة الصوت
            </button>
            <button type="button" class="tool-btn" onclick="toggleFullScreen()" title="ملء الشاشة">
                <i class="fas fa-expand"></i>
            </button>
            <div class="live-clock-box">
                <div class="live-time" id="clock-time">--:--:--</div>
                <div class="live-date" id="clock-date">جاري التحميل...</div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="display-content">
        <!-- Current Serving Card -->
        <div class="current-serving-card" id="serving-card">
            <div class="card-header-label">
                <span class="status-pill calling" id="serving-status-badge">
                    <i class="fas fa-bullhorn"></i> <span id="serving-status-text">في انتظار الاستدعاء</span>
                </span>
                <span id="emergency-badge" class="emergency-pill" style="display: none;">
                    <i class="fas fa-heartbeat"></i> حالة طوارئ مستعجلة
                </span>
            </div>

            <div class="serving-body">
                <div class="ticket-number-label" id="ticket-number-label">رقم المريض في الدور</div>
                <div class="ticket-number-badge" id="serving-ticket-num">-</div>
                <div class="serving-patient-name" id="serving-patient-name">لا يوجد مريض حالياً</div>
                <div class="serving-instructions" id="serving-instructions">
                    <i class="fas fa-door-open text-emerald-400"></i> تفضل بالدخول إلى غرفة الكشف
                </div>
            </div>

            <div class="card-footer-stats">
                <div class="stat-item">
                    <div class="val" id="stat-waiting">0</div>
                    <div class="lbl">في الانتظار</div>
                </div>
                <div class="stat-item">
                    <div class="val" id="stat-completed">0</div>
                    <div class="lbl">تم الكشف اليوم</div>
                </div>
                <div class="stat-item">
                    <div class="val" id="stat-total">0</div>
                    <div class="lbl">إجمالي المسجلين</div>
                </div>
            </div>
        </div>

        <!-- Waiting Queue Panel -->
        <div class="waiting-queue-panel">
            <div class="queue-panel-header">
                <h2><i class="fas fa-users-line text-blue-400"></i> قائمة الانتظار القادمة</h2>
                <span class="waiting-badge-count" id="badge-waiting-count">0 متبقي</span>
            </div>

            <div class="queue-list-container" id="queue-list">
                <div class="empty-queue-msg">لا يوجد مرضى بالانتظار</div>
            </div>
        </div>
    </main>

    <!-- Bottom Guidance Ticker Bar -->
    <footer class="display-ticker">
        <div class="ticker-label"><i class="fas fa-bullhorn"></i> إرشادات:</div>
        <div class="ticker-track">
            <div class="ticker-text">
                <span>• مرحباً بكم في مستشفى الكفاءات الأهلي</span>
                <span>• يرجى تحضير بطاقة الهوية ووصل الحجز عند الاستدعاء</span>
                <span>• يرجى الالتزام برقم الدور والمحافظة على الهدوء في صالة الانتظار</span>
                <span>• نسعى دائماً لتقديم أفضل رعاية طبية لكم - مع تمنياتنا لكم بدوام الصحة والشفاء العاجل</span>
            </div>
        </div>
    </footer>

    <script>
        const doctorId = {{ $doctor->id }};
        const baseUrl = "{{ url('/') }}";
        const apiUrl = "{{ route('queue.doctor.data', $doctor->id) }}";
        const docFullName = "{{ $doctor->user ? $doctor->user->name : 'الطبيب' }}";
        let lastCallKey = null;
        let isInitialLoad = true;
        let isAnnouncing = false;
        let globalAudioCtx = null;
        let availableVoices = [];

        // Clock Update
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const dateStr = now.toLocaleDateString('ar-IQ', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('clock-time').textContent = timeStr;
            document.getElementById('clock-date').textContent = dateStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Singleton Audio Context for Clean Ding-Dong Chime (No hardware context limits)
        function getAudioContext() {
            if (!globalAudioCtx) {
                const AudioCtxClass = window.AudioContext || window.webkitAudioContext;
                if (AudioCtxClass) {
                    globalAudioCtx = new AudioCtxClass();
                }
            }
            if (globalAudioCtx && globalAudioCtx.state === 'suspended') {
                globalAudioCtx.resume();
            }
            return globalAudioCtx;
        }

        function playChime() {
            try {
                const ctx = getAudioContext();
                if (!ctx) return;

                const now = ctx.currentTime;

                // 1st Tone (High - Note A5: 880Hz)
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(880, now);
                gain1.gain.setValueAtTime(0.4, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.75);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.75);

                // 2nd Tone (Low - Note D5: 587.33Hz)
                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(587.33, now + 0.3);
                gain2.gain.setValueAtTime(0.45, now + 0.3);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.3);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.3);
                osc2.stop(now + 1.3);
            } catch (e) {
                console.warn('Audio chime notice:', e);
            }
        }

        // Voice List Loader with Firefox / Chrome fallback
        function loadVoices() {
            if ('speechSynthesis' in window) {
                availableVoices = window.speechSynthesis.getVoices() || [];
            }
        }
        if ('speechSynthesis' in window) {
            loadVoices();
            window.speechSynthesis.onvoiceschanged = loadVoices;
        }

        // Fetch Audio Blob Once into Memory
        async function getAudioBlobUrl(text) {
            try {
                const res = await fetch(`${baseUrl}/queue/tts?text=${encodeURIComponent(text)}`);
                if (res.ok) {
                    const blob = await res.blob();
                    if (blob && blob.size > 500) {
                        return URL.createObjectURL(blob);
                    }
                }
            } catch (e) {
                console.warn('Blob fetch failed, will use fallback:', e);
            }
            return null;
        }

        // Play Audio Buffer from URL or Blob
        function playAudioStream(url) {
            return new Promise((resolve) => {
                let resolved = false;
                const finish = () => {
                    if (!resolved) {
                        resolved = true;
                        resolve();
                    }
                };

                try {
                    const audio = new Audio(url);
                    audio.onended = finish;
                    audio.onerror = finish;
                    const playProm = audio.play();
                    if (playProm !== undefined) {
                        playProm.catch(finish);
                    }
                    setTimeout(finish, 9000); // Safety timeout
                } catch (e) {
                    finish();
                }
            });
        }

        // Secondary Fallback: Browser SpeechSynthesis
        function fallbackSpeechSynthesis(text) {
            return new Promise((resolve) => {
                if (!('speechSynthesis' in window)) {
                    resolve();
                    return;
                }

                try {
                    if (window.speechSynthesis.paused) {
                        window.speechSynthesis.resume();
                    }

                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = 'ar-SA';
                    utterance.rate = 0.85;
                    utterance.pitch = 1.0;

                    if (availableVoices.length === 0) {
                        loadVoices();
                    }

                    const arVoice = availableVoices.find(v => v.lang && (v.lang.startsWith('ar') || v.lang.includes('AR') || (v.name && v.name.toLowerCase().includes('arabic'))));
                    if (arVoice) {
                        utterance.voice = arVoice;
                    }

                    let doneCalled = false;
                    const finish = () => {
                        if (!doneCalled) {
                            doneCalled = true;
                            resolve();
                        }
                    };

                    utterance.onend = finish;
                    utterance.onerror = finish;
                    setTimeout(finish, 7000);

                    window.speechSynthesis.speak(utterance);
                } catch (err) {
                    resolve();
                }
            });
        }

        // Patient Announcement: 1 Call per Button Click (جرس + نداء لمرة واحدة لكل ضغطة بالصيغة العراقية الراقية)
        async function triggerCallAnnouncement(patientName, queueNumber) {
            if (isAnnouncing) return;
            isAnnouncing = true;

            const announcementText = queueNumber 
                ? `المراجع ${patientName}، دورك رقم ${queueNumber}، تفضل لعيادة دكتور ${docFullName}.`
                : `المراجع ${patientName}، تفضل لعيادة دكتور ${docFullName}.`;

            try {
                const blobUrl = await getAudioBlobUrl(announcementText);

                // الجرس التنبيهي النقي
                playChime();
                await new Promise(r => setTimeout(r, 900)); // Chime duration

                // النداء الصوتي العربي لمرة واحدة
                if (blobUrl) {
                    await playAudioStream(blobUrl);
                    URL.revokeObjectURL(blobUrl);
                } else {
                    await fallbackSpeechSynthesis(announcementText);
                }
            } catch (e) {
                console.error('Announcement error:', e);
            } finally {
                isAnnouncing = false;
            }
        }

        // Periodic Guidance Audio Announcements (إرشادات المستشفى ناطقة دورياً بصوت خافت راقي)
        const hospitalGuidanceTips = [
            "مرحباً بكم في مستشفى الكفاءات الأهلي. يرجى تحضير بطاقة الهوية ووصل الحجز عند استدعاء رقم دورك.",
            "ضيوفنا الكرام، يرجى الالتزام برقم الدور والمحافظة على الهدوء في صالة الانتظار لراحتكم وراحة المرضى.",
            "نرجو من المراجعين الكرام الانتباه لشاشة العيادة عند نطق رقم الدور ورسائل التوجيه للدخول إلى غرفة الكشف.",
            "مستشفى الكفاءات الأهلي يتمنى لكم دوام الصحة والسلامة والشفاء العاجل."
        ];
        let currentTipIndex = 0;
        let guidanceVoiceEnabled = true;

        async function speakGuidanceTip() {
            // Do not interrupt if calling patient or audio not ready
            if (isAnnouncing || !guidanceVoiceEnabled) return;
            isAnnouncing = true;

            const btn = document.getElementById('guidance-voice-btn');
            if (btn) btn.classList.add('speaking');

            const tipText = hospitalGuidanceTips[currentTipIndex];
            currentTipIndex = (currentTipIndex + 1) % hospitalGuidanceTips.length;

            try {
                const blobUrl = await getAudioBlobUrl(tipText);
                if (blobUrl) {
                    await playAudioStream(blobUrl);
                    URL.revokeObjectURL(blobUrl);
                } else {
                    await fallbackSpeechSynthesis(tipText);
                }
            } catch (e) {
                console.warn('Guidance voice error:', e);
            } finally {
                if (btn) btn.classList.remove('speaking');
                isAnnouncing = false;
            }
        }

        function toggleGuidanceVoice() {
            guidanceVoiceEnabled = !guidanceVoiceEnabled;
            const btn = document.getElementById('guidance-voice-btn');
            const icon = document.getElementById('guidance-voice-icon');
            const text = document.getElementById('guidance-voice-text');

            if (guidanceVoiceEnabled) {
                enableAudio();
                btn.classList.add('active');
                icon.className = 'fas fa-volume-up';
                text.textContent = 'إرشادات صوتية: مفعّلة';
                // Play first tip shortly as confirmation
                setTimeout(speakGuidanceTip, 1000);
            } else {
                btn.classList.remove('active');
                icon.className = 'fas fa-volume-xmark';
                text.textContent = 'إرشادات صوتية: معطلة';
            }
        }

        // Schedule guidance voice announcement every 5 minutes (300,000 ms)
        setInterval(() => {
            if (guidanceVoiceEnabled && !isAnnouncing) {
                speakGuidanceTip();
            }
        }, 300000);

        function enableAudio() {
            getAudioContext();
            if ('speechSynthesis' in window) {
                window.speechSynthesis.resume();
                // Test subtle utterance to unlock audio pipeline in Firefox
                const dummy = new SpeechSynthesisUtterance('');
                window.speechSynthesis.speak(dummy);
            }
            playChime();
            const banner = document.getElementById('audio-enable-banner');
            if (banner) banner.style.display = 'none';
        }

        // Global click listener to unlock audio on first touch
        document.addEventListener('click', function() {
            enableAudio();
        }, { once: true });

        // Manual Test Button Function
        function testAudioAndCall() {
            enableAudio();
            const testName = document.getElementById('serving-patient-name').textContent || 'محمد علي';
            const testNum = document.getElementById('serving-ticket-num').textContent || '1';
            triggerCallAnnouncement(testName === 'لا يوجد مريض حالياً' ? 'محمد علي حسن' : testName, testNum === '-' ? '1' : testNum);
        }

        // Fetch Queue Data in Real Time with anti-cache query
        async function fetchQueueData() {
            try {
                const res = await fetch(apiUrl + '?_t=' + new Date().getTime(), {
                    cache: 'no-store',
                    headers: { 
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) return;
                const data = await res.json();

                if (data.success) {
                    renderScreen(data);
                }
            } catch (err) {
                console.error('Error fetching queue:', err);
            }
        }

        function renderScreen(data) {
            const current = data.current_patient;
            const waiting = data.waiting_list || [];
            const stats = data.stats || {};

            // Update stats
            document.getElementById('stat-waiting').textContent = stats.waiting_count || 0;
            document.getElementById('stat-completed').textContent = stats.completed_count || 0;
            document.getElementById('stat-total').textContent = stats.total_today || 0;
            const card = document.getElementById('serving-card');
            const emergencyBadge = document.getElementById('emergency-badge');
            const statusBadge = document.getElementById('serving-status-badge');
            const statusText = document.getElementById('serving-status-text');
            const instructionsEl = document.getElementById('serving-instructions');

            if (current) {
                document.getElementById('serving-ticket-num').textContent = current.queue_number;
                document.getElementById('serving-patient-name').textContent = current.name;

                if (current.status === 'calling') {
                    card.classList.add('calling-pulse');
                    statusBadge.className = 'status-pill calling';
                    statusText.innerHTML = '<i class="fas fa-bullhorn"></i> يتم الاستدعاء الآن';
                    instructionsEl.innerHTML = '<i class="fas fa-door-open text-emerald-400"></i> تفضل بالدخول إلى غرفة الكشف الآن';
                    instructionsEl.style.color = '#6ee7b7';
                } else {
                    card.classList.remove('calling-pulse');
                    statusBadge.className = 'status-pill in-consultation';
                    statusText.innerHTML = '<i class="fas fa-stethoscope"></i> المريض بالداخل (قيد الفحص)';
                    instructionsEl.innerHTML = '<i class="fas fa-user-clock text-blue-400"></i> الكشف الطبي جاري حالياً - يرجى الانتظار';
                    instructionsEl.style.color = '#93c5fd';
                }

                if (current.is_emergency) {
                    emergencyBadge.style.display = 'inline-flex';
                } else {
                    emergencyBadge.style.display = 'none';
                }

                // Call Event Trigger (Detect new call or recall)
                const newKey = current.call_key || (current.id + '_' + current.status + '_' + (current.called_at || ''));
                if (newKey !== lastCallKey) {
                    const wasInitial = isInitialLoad;
                    lastCallKey = newKey;
                    
                    if (!wasInitial && current.status === 'calling') {
                        triggerCallAnnouncement(current.name, current.queue_number);
                    }
                }
            } else {
                lastCallKey = null;
                card.classList.remove('calling-pulse');
                document.getElementById('serving-ticket-num').textContent = '-';
                document.getElementById('serving-patient-name').textContent = 'العيادة جاهزة لاستقبال المريض القادم';
                statusBadge.className = 'status-pill idle';
                statusText.innerHTML = '<i class="fas fa-check-circle"></i> في انتظار النداء';
                instructionsEl.innerHTML = '<i class="fas fa-clock text-slate-400"></i> يرجى الانتظار في الصالة لحين الاستدعاء برقم الدور';
                instructionsEl.style.color = '#94a3b8';
                emergencyBadge.style.display = 'none';
            }

            // Render Waiting List
            const queueContainer = document.getElementById('queue-list');
            if (waiting.length === 0) {
                queueContainer.innerHTML = '<div class="empty-queue-msg">لا يوجد مرضى في قائمة الانتظار</div>';
            } else {
                queueContainer.innerHTML = waiting.map((item, index) => {
                    const rowClass = item.is_emergency ? 'queue-row emergency-row' : 'queue-row';
                    const tag = item.is_emergency ? '<span style="color: #f87171; font-weight:800; font-size: 0.8rem;">(طوارئ)</span>' : (index === 0 ? '<span style="color: #60a5fa; font-weight:800; font-size: 0.8rem;">(التالي)</span>' : '');
                    
                    return `
                        <div class="${rowClass}">
                            <div class="queue-row-right">
                                <div class="queue-seq-num">${item.queue_number}</div>
                                <div>
                                    <div class="queue-patient-name">${item.name} ${tag}</div>
                                </div>
                            </div>
                            <div class="queue-row-status">
                                ${index === 0 ? '<span style="color: #34d399;">استعد للدخول</span>' : 'انتظار'}
                            </div>
                        </div>
                    `;
                }).join('');
            }

            isInitialLoad = false;
        }

        // Fullscreen Helper
        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Auto Poll every 1.5 seconds for instant response
        setInterval(fetchQueueData, 1500);
        fetchQueueData();
    </script>
</body>
</html>
