<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>شاشة الانتظار المركزية - العيادات الاستشارية</title>
    <!-- Google Fonts: Cairo -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-main: #0b132b;
            --bg-card: #1c2541;
            --primary: #3a86ff;
            --success: #10b981;
            --danger: #ef4444;
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
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .header {
            background: rgba(28, 37, 65, 0.95);
            border-bottom: 2px solid rgba(58, 134, 255, 0.4);
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .header .title h1 {
            font-size: 1.3rem;
            font-weight: 900;
            line-height: 1.2;
        }

        .header .title p {
            font-size: 0.8rem;
            color: var(--accent);
            font-weight: 700;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .size-btn {
            background: rgba(58, 134, 255, 0.15);
            border: 1px solid rgba(58, 134, 255, 0.4);
            color: #93c5fd;
            padding: 5px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .size-btn:hover, .size-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .live-time {
            font-size: 1.4rem;
            font-weight: 900;
            direction: ltr;
            color: #ffffff;
            background: rgba(11, 19, 43, 0.8);
            padding: 4px 12px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .clinics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 14px;
            padding: 16px 20px;
            flex: 1;
            align-content: start;
        }

        /* الحجم الافتراضي المصغر */
        .clinic-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 14px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.25s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
        }

        .clinic-card.calling {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.45);
            animation: pulseCard 1.5s infinite;
        }

        @keyframes pulseCard {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
        }

        .clinic-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 6px;
            margin-bottom: 8px;
            gap: 8px;
        }

        .doc-name {
            font-size: 1.05rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.25;
        }

        .doc-spec {
            font-size: 0.78rem;
            color: #93c5fd;
            font-weight: 700;
        }

        .dept-tag {
            font-size: 0.72rem;
            background: rgba(58, 134, 255, 0.2);
            padding: 2px 7px;
            border-radius: 8px;
            color: #93c5fd;
            white-space: nowrap;
        }

        .serving-info-box {
            background: rgba(11, 19, 43, 0.75);
            border-radius: 10px;
            padding: 8px 10px;
            text-align: center;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .serving-label {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 700;
        }

        .current-num {
            font-size: 2.1rem;
            font-weight: 900;
            color: var(--accent);
            line-height: 1.1;
        }

        .current-name {
            font-size: 1.02rem;
            font-weight: 900;
            margin-top: 2px;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .clinic-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
            color: #94a3b8;
            font-weight: 700;
            padding-top: 4px;
        }

        /* وضع فائق الصغر - Extra Compact للشاشات المزدحمة */
        body.size-compact .clinics-grid {
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            padding: 12px 14px;
        }
        body.size-compact .clinic-card {
            padding: 9px 10px;
            border-radius: 10px;
        }
        body.size-compact .doc-name {
            font-size: 0.95rem;
        }
        body.size-compact .doc-spec {
            font-size: 0.72rem;
        }
        body.size-compact .serving-info-box {
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        body.size-compact .current-num {
            font-size: 1.7rem;
        }
        body.size-compact .current-name {
            font-size: 0.9rem;
        }
        body.size-compact .clinic-footer {
            font-size: 0.72rem;
        }

        /* وضع الحجم الأكبر إذا أراد المستخدم التكبير */
        body.size-large .clinics-grid {
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 18px;
            padding: 22px 26px;
        }
        body.size-large .clinic-card {
            padding: 16px;
            border-radius: 16px;
        }
        body.size-large .doc-name {
            font-size: 1.2rem;
        }
        body.size-large .current-num {
            font-size: 2.7rem;
        }
        body.size-large .current-name {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1>مستشفى الكفاءات الأهلي</h1>
            <p>لوحة الانتظار المركزية - صالة العيادات الاستشارية</p>
        </div>

        <div class="header-actions">
            <button type="button" class="size-btn" id="btn-size-compact" onclick="setCardSize('compact')" title="حجم مصغر جداً">
                <i class="fas fa-compress-alt"></i> مصغر جداً
            </button>
            <button type="button" class="size-btn active" id="btn-size-default" onclick="setCardSize('default')" title="حجم مدمج متوازن">
                <i class="fas fa-th"></i> مدمج
            </button>
            <button type="button" class="size-btn" id="btn-size-large" onclick="setCardSize('large')" title="حجم كبير">
                <i class="fas fa-expand-alt"></i> كبير
            </button>
            <button type="button" class="size-btn" onclick="toggleFullScreen()" title="ملء الشاشة">
                <i class="fas fa-expand"></i>
            </button>
            <div class="live-time" id="clock-time">--:--:--</div>
        </div>
    </div>

    <div class="clinics-grid" id="clinics-grid">
        <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px;">
            جاري تحميل حالة العيادات...
        </div>
    </div>

    <script>
        function setCardSize(size) {
            document.body.classList.remove('size-compact', 'size-large');
            document.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));

            if (size === 'compact') {
                document.body.classList.add('size-compact');
                document.getElementById('btn-size-compact')?.classList.add('active');
            } else if (size === 'large') {
                document.body.classList.add('size-large');
                document.getElementById('btn-size-large')?.classList.add('active');
            } else {
                document.getElementById('btn-size-default')?.classList.add('active');
            }
            try {
                localStorage.setItem('all_clinics_card_size', size);
            } catch(e) {}
        }

        function toggleFullScreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {});
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }

        // Restore saved preference
        try {
            const savedSize = localStorage.getItem('all_clinics_card_size') || 'default';
            setCardSize(savedSize);
        } catch(e) {}

        function updateClock() {
            const now = new Date();
            document.getElementById('clock-time').textContent = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        const apiUrl = "{{ route('queue.all.data') }}";

        async function fetchAllData() {
            try {
                const res = await fetch(apiUrl + (apiUrl.includes('?') ? '&' : '?') + '_t=' + new Date().getTime(), {
                    cache: 'no-store',
                    headers: {
                        'Cache-Control': 'no-cache, no-store, must-revalidate',
                        'Pragma': 'no-cache',
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}`);
                }

                const data = await res.json();
                if (data.success) {
                    renderClinics(data.clinics);
                } else {
                    showStatusMessage(data.message || 'تعذر تحميل بيانات العيادات');
                }
            } catch(e) {
                console.warn('Queue fetch warning:', e);
                const container = document.getElementById('clinics-grid');
                // Only show offline notice if the grid hasn't loaded any cards yet
                if (container && container.querySelectorAll('.clinic-card').length === 0) {
                    showStatusMessage('جاري الاتصال بالخادم وتحديث حالة العيادات...');
                }
            }
        }

        function showStatusMessage(msg) {
            const container = document.getElementById('clinics-grid');
            if (container && container.querySelectorAll('.clinic-card').length === 0) {
                container.innerHTML = `<div style="grid-column: 1/-1; text-align:center; color:#94a3b8; padding:40px; font-weight:700;"><i class="fas fa-circle-notch fa-spin me-2"></i> ${msg}</div>`;
            }
        }

        function renderClinics(clinics) {
            const container = document.getElementById('clinics-grid');
            if (!clinics || clinics.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; color:#94a3b8; padding:40px; font-size: 1.1rem;"><i class="fas fa-info-circle me-2"></i> لا توجد عيادات مسجلة أو متاحة للعمل في الوقت الحالي</div>';
                return;
            }

            container.innerHTML = clinics.map(c => {
                const isCalling = c.current_patient && c.current_patient.status === 'calling';
                const cur = c.current_patient;

                return `
                    <div class="clinic-card ${isCalling ? 'calling' : ''}">
                        <div class="clinic-header">
                            <div>
                                <div class="doc-name">د. ${c.doctor_name}</div>
                                <div class="doc-spec">${c.specialization || c.department_name}</div>
                            </div>
                            <span class="dept-tag">
                                ${c.department_name}
                            </span>
                        </div>

                        <div class="serving-info-box">
                            <div class="serving-label">المريض بالداخل / المستدعى</div>
                            <div class="current-num">${cur ? cur.queue_number : '-'}</div>
                            <div class="current-name">${cur ? cur.name : 'العيادة جاهزة'}</div>
                        </div>

                        <div class="clinic-footer">
                            <span><i class="fas fa-users me-1"></i> الانتظار: ${c.waiting_count}</span>
                            <span>${isCalling ? '<span style="color:#34d399;"><i class="fas fa-bullhorn me-1"></i> استدعاء</span>' : '🟢 تعمل'}</span>
                        </div>
                    </div>
                `;
            }).join('');
        }

        setInterval(fetchAllData, 3000);
        fetchAllData();
    </script>
</body>
</html>
