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
            background: rgba(28, 37, 65, 0.9);
            border-bottom: 2px solid rgba(58, 134, 255, 0.4);
            padding: 14px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .header .title h1 {
            font-size: 1.5rem;
            font-weight: 900;
        }

        .header .title p {
            font-size: 0.9rem;
            color: var(--accent);
            font-weight: 700;
        }

        .live-time {
            font-size: 1.8rem;
            font-weight: 900;
            direction: ltr;
        }

        .clinics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 20px;
            padding: 25px 30px;
            flex: 1;
        }

        .clinic-card {
            background: var(--bg-card);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .clinic-card.calling {
            border-color: var(--success);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.5);
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
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .doc-name {
            font-size: 1.2rem;
            font-weight: 900;
            color: #ffffff;
        }

        .doc-spec {
            font-size: 0.85rem;
            color: #93c5fd;
            font-weight: 700;
        }

        .serving-info-box {
            background: rgba(11, 19, 43, 0.7);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            margin-bottom: 12px;
        }

        .current-num {
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--accent);
            line-height: 1;
        }

        .current-name {
            font-size: 1.3rem;
            font-weight: 900;
            margin-top: 6px;
            color: #ffffff;
        }

        .clinic-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #94a3b8;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            <h1>مستشفى الكفاءات الأهلي</h1>
            <p>لوحة الانتظار المركزية - صالة الاستشارية</p>
        </div>
        <div class="live-time" id="clock-time">--:--:--</div>
    </div>

    <div class="clinics-grid" id="clinics-grid">
        <div style="grid-column: 1/-1; text-align: center; color: #94a3b8; padding: 40px;">
            جاري تحميل حالة العيادات...
        </div>
    </div>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clock-time').textContent = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        async function fetchAllData() {
            try {
                const res = await fetch('/queue/all/data?_t=' + new Date().getTime(), {
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
                    renderClinics(data.clinics);
                }
            } catch(e) {}
        }

        function renderClinics(clinics) {
            const container = document.getElementById('clinics-grid');
            if (!clinics || clinics.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align:center;">لا توجد عيادات متاحة اليوم</div>';
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
                            <span style="font-size:0.8rem; background:rgba(58,134,255,0.2); padding:2px 8px; border-radius:10px; color:#93c5fd;">
                                ${c.department_name}
                            </span>
                        </div>

                        <div class="serving-info-box">
                            <div style="font-size:0.8rem; color:#94a3b8; font-weight:700;">المريض بالداخل / المستدعى</div>
                            <div class="current-num">${cur ? cur.queue_number : '-'}</div>
                            <div class="current-name">${cur ? cur.name : 'العيادة جاهزة'}</div>
                        </div>

                        <div class="clinic-footer">
                            <span><i class="fas fa-users me-1"></i> المنتظرون: ${c.waiting_count}</span>
                            <span>${isCalling ? '<span style="color:#34d399;">📢 يتم الاستدعاء</span>' : '🟢 قيد العمل'}</span>
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
