<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - غير مصرح بالوصول | نظام إدارة المستشفى</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            --warning-gradient: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
        }

        body {
            font-family: 'Cairo', 'Segoe UI', Tahoma, sans-serif;
            background: var(--bg-gradient);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
            color: #f8fafc;
            overflow-x: hidden;
            position: relative;
        }

        /* الخلفية التفاعلية مع جزيئات ضوئية */
        .bg-glow {
            position: absolute;
            width: 550px;
            height: 550px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(239, 68, 68, 0.15) 0%, rgba(245, 158, 11, 0.05) 50%, transparent 70%);
            filter: blur(50px);
            z-index: 0;
            pointer-events: none;
            animation: pulseGlow 6s ease-in-out infinite alternate;
        }

        @keyframes pulseGlow {
            0% { transform: scale(0.9) translate(-20px, -20px); opacity: 0.6; }
            100% { transform: scale(1.15) translate(20px, 20px); opacity: 0.9; }
        }

        .error-card {
            background: rgba(30, 41, 59, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.75rem;
            padding: 3rem 2.5rem;
            max-width: 680px;
            width: 100%;
            position: relative;
            z-index: 1;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            text-align: center;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* أيقونة القفل المتحركة */
        .icon-wrapper {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 110px;
            height: 110px;
            border-radius: 2rem;
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.2) 0%, rgba(245, 158, 11, 0.1) 100%);
            border: 2px solid rgba(239, 68, 68, 0.3);
            margin-bottom: 1.75rem;
            box-shadow: 0 12px 30px rgba(239, 68, 68, 0.25);
        }

        .icon-wrapper i {
            font-size: 3.5rem;
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: lockShake 3s ease-in-out infinite;
        }

        @keyframes lockShake {
            0%, 100% { transform: rotate(0deg); }
            10%, 30% { transform: rotate(-6deg); }
            20%, 40% { transform: rotate(6deg); }
            50% { transform: rotate(0deg); }
        }

        .error-code {
            font-size: 1.1rem;
            font-weight: 800;
            letter-spacing: 2px;
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            display: inline-block;
            padding: 0.35rem 1.2rem;
            border-radius: 2rem;
            border: 1px solid rgba(239, 68, 68, 0.25);
            margin-bottom: 1rem;
            text-transform: uppercase;
        }

        .error-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.85rem;
            line-height: 1.3;
        }

        /* رسالة الحرمان المحددة */
        .custom-message-box {
            background: rgba(239, 68, 68, 0.12);
            border-right: 4px solid #ef4444;
            border-radius: 0.75rem;
            padding: 1.1rem 1.4rem;
            margin: 1.5rem 0;
            text-align: right;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-top: 1px solid rgba(239, 68, 68, 0.2);
            border-bottom: 1px solid rgba(239, 68, 68, 0.2);
            border-left: 1px solid rgba(239, 68, 68, 0.2);
        }

        .custom-message-box i {
            font-size: 1.6rem;
            color: #f87171;
            flex-shrink: 0;
        }

        .custom-message-box .message-text {
            font-size: 1.15rem;
            font-weight: 700;
            color: #fecaca;
            margin: 0;
        }

        .error-description {
            font-size: 0.98rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        /* بطاقة معلومات الحساب */
        .user-diagnostic-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            font-size: 0.88rem;
            color: #cbd5e1;
            text-align: right;
        }

        .user-diagnostic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.35rem 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        }

        .user-diagnostic-item:last-child {
            border-bottom: none;
        }

        .diagnostic-label {
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .diagnostic-value {
            font-weight: 600;
            color: #e2e8f0;
        }

        /* أزرار الإجراءات */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            justify-content: center;
        }

        .btn-action {
            padding: 0.75rem 1.6rem;
            border-radius: 0.85rem;
            font-weight: 700;
            font-size: 0.98rem;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            transition: all 0.25s ease;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }

        .btn-primary-action {
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35);
        }

        .btn-primary-action:hover {
            background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45);
        }

        .btn-secondary-action {
            background: rgba(51, 65, 85, 0.8);
            color: #e2e8f0;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-secondary-action:hover {
            background: rgba(71, 85, 105, 0.9);
            color: #ffffff;
            transform: translateY(-2px);
        }

        .footer-note {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            font-size: 0.85rem;
            color: #64748b;
        }

        .footer-note a {
            color: #38bdf8;
            text-decoration: none;
        }

        .footer-note a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="bg-glow"></div>

    <div class="error-card">
        <!-- أيقونة القفل الفاخرة -->
        <div class="icon-wrapper">
            <i class="fas fa-lock"></i>
        </div>

        <div class="error-code">
            <i class="fas fa-shield-alt me-1"></i> خطأ 403 • صلاحية غير كافية
        </div>

        <h1 class="error-title">غير مصرح بالوصول إلى هذه المحطة</h1>

        <!-- نص الرسالة الخاص الممرر من النظام -->
        @php
            $rawMessage = $exception ? $exception->getMessage() : null;
            $displayMessage = !empty($rawMessage) ? $rawMessage : 'غير مصرح لك بالوصول إلى هذه الصفحة أو تنفيذ هذا الإجراء.';
        @endphp
        <div class="custom-message-box">
            <i class="fas fa-exclamation-triangle"></i>
            <div class="message-text">
                {{ $displayMessage }}
            </div>
        </div>

        <p class="error-description">
            حسابك الحالي لا يمتلك الصلاحيات الإدارية أو التشغيلية الكافية لفتح هذه الصفحة. إذا كانت هذه المحطة تقع ضمن اختصاصك اليومي، يرجى التواصل مع مسؤول النظام لتفعيل الصلاحية المطلوبة لحسابك.
        </p>

        <!-- معلومات تشخيصية لمساعدة المستخدم والمسؤول -->
        @auth
        <div class="user-diagnostic-card">
            <div class="user-diagnostic-item">
                <span class="diagnostic-label"><i class="fas fa-user"></i> المستخدم الحالي:</span>
                <span class="diagnostic-value">{{ Auth::user()->name }}</span>
            </div>
            <div class="user-diagnostic-item">
                <span class="diagnostic-label"><i class="fas fa-id-badge"></i> الدور المسند (Role):</span>
                <span class="diagnostic-value">
                    @if(Auth::user()->roles && Auth::user()->roles->count() > 0)
                        <span class="badge bg-primary text-white">{{ Auth::user()->roles->pluck('name')->join(', ') }}</span>
                    @else
                        <span class="badge bg-secondary">لا يوجد دور محدد</span>
                    @endif
                </span>
            </div>
            <div class="user-diagnostic-item">
                <span class="diagnostic-label"><i class="fas fa-link"></i> المسار المطلوب:</span>
                <span class="diagnostic-value text-break" style="max-width: 320px; font-size: 0.8rem; direction: ltr;">
                    {{ request()->path() }}
                </span>
            </div>
        </div>
        @endauth

        <!-- أزرار الإجراءات السريعة -->
        <div class="action-buttons">
            <button onclick="window.history.length > 1 ? window.history.back() : window.location.href='{{ url('/') }}'" class="btn-action btn-secondary-action">
                <i class="fas fa-arrow-right"></i>
                <span>العودة للصفحة السابقة</span>
            </button>

            <a href="{{ url('/') }}" class="btn-action btn-primary-action">
                <i class="fas fa-home"></i>
                <span>لوحة التحكم الرئيسية</span>
            </a>

            @can('view cashier')
            <a href="{{ route('cashier.index') }}" class="btn-action btn-secondary-action" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.3); color: #6ee7b7;">
                <i class="fas fa-cash-register"></i>
                <span>لوحة الكاشير العامة</span>
            </a>
            @endcan
        </div>

        <div class="footer-note">
            نظام إدارة المستشفى الرقمي &copy; {{ date('Y') }} • للحصول على مساعدة إدارية راجع <a href="mailto:admin@hospital.local">إدارة النظام</a>
        </div>
    </div>
</body>
</html>
