<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'en' ? 'ltr' : 'rtl' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('خارج جدول الدوام الرسمي') }} | {{ __('قسم التمريض') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(145deg, #f0f7ff 0%, #e2e8f0 50%, #cbd5e1 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #0f172a;
        }

        .offduty-card {
            background: #ffffff;
            width: 100%;
            max-width: 540px;
            border-radius: 24px;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.12), 0 0 0 1px rgba(226, 232, 240, 0.8);
            padding: 2.75rem 2.25rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .offduty-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #f59e0b, #ef4444, #f59e0b);
        }

        .avatar-wrap {
            width: 88px;
            height: 88px;
            margin: 0 auto 1.25rem;
            background: linear-gradient(135deg, #fef3c7 0%, #fee2e2 100%);
            border: 3px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px -4px rgba(239, 68, 68, 0.2);
            position: relative;
        }

        .avatar-wrap i {
            font-size: 2.5rem;
            color: #d97706;
        }

        .badge-pulse {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 22px;
            height: 22px;
            background: #ef4444;
            border: 3px solid #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .badge-pulse::after {
            content: '';
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: #ef4444;
            animation: pulse-ring 2s infinite;
            position: absolute;
        }

        @keyframes pulse-ring {
            0% { transform: scale(0.95); opacity: 0.8; }
            70% { transform: scale(1.8); opacity: 0; }
            100% { transform: scale(0.95); opacity: 0; }
        }

        .nurse-name {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }

        .nurse-meta {
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            padding: 6px 16px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }

        .notice-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.75rem;
            text-align: right;
            font-size: 0.92rem;
            line-height: 1.7;
            color: #334155;
        }

        .notice-box strong {
            color: #0f172a;
        }

        .btn-check {
            width: 100%;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #ffffff;
            border: none;
            padding: 0.95rem 1.5rem;
            border-radius: 14px;
            font-size: 1.05rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            margin-bottom: 0.75rem;
        }

        .btn-check:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        .btn-logout {
            width: 100%;
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            border-radius: 14px;
            font-size: 0.92rem;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-logout:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .alert-box {
            padding: 0.85rem 1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .alert-warning {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }
        .alert-success {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }
    </style>
</head>
<body>

    <div class="offduty-card">
        
        {{-- Flash Alerts --}}
        @if(session('warning'))
            <div class="alert-box alert-warning">
                <i class="fas fa-exclamation-circle fa-lg"></i>
                <span>{{ session('warning') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="alert-box alert-success">
                <i class="fas fa-check-circle fa-lg"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Avatar & Badge --}}
        <div class="avatar-wrap">
            <i class="fas fa-user-nurse"></i>
            <div class="badge-pulse" title="{{ __('خارج الدوام') }}"></div>
        </div>

        {{-- Status Pill --}}
        <div>
            <span class="status-pill">
                <i class="fas fa-ban"></i>
                <span>{{ __('خارج جدول الدوام الرسمي لليوم') }}</span>
            </span>
        </div>

        {{-- Nurse Name & Info --}}
        <h2 class="nurse-name">{{ $nurse->name }}</h2>
        <p class="nurse-meta">
            <span>{{ $nurse->email }}</span>
            @if($nurse->phone) • <span>{{ $nurse->phone }}</span> @endif
        </p>

        {{-- Guidance Textbox --}}
        <div class="notice-box">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <i class="far fa-calendar-alt text-primary" style="color: #2563eb;"></i>
                <strong>{{ __('تاريخ اليوم:') }}</strong> {{ \Carbon\Carbon::parse($todayDate)->translatedFormat('l، d F Y') }}
            </div>
            <p style="margin-bottom: 0;">
                {{ __('أهلاً بك زميلنا العزيز، حسابك غير مدرج حالياً ضمن جدول دوام الكادر التمريضي المعتمد لهذا اليوم.') }}
            </p>
            <p style="margin-top: 6px; font-size: 0.85rem; color: #64748b; margin-bottom: 0;">
                <i class="fas fa-info-circle me-1 text-secondary"></i> {{ __('إذا كان لديك شفت فعلي أو تكليف بتبديل دوام، يرجى مراجعة مسؤول قسم التمريض لإضافتك لجدول التوفر وتحديد طابقك لفتح النظام فوراً.') }}
            </p>
        </div>

        {{-- Primary Action: Re-check & Enter --}}
        <a href="{{ route('nurse.check-access') }}" class="btn-check">
            <i class="fas fa-sync-alt fa-spin-hover"></i>
            <span>{{ __('فحص التفعيل والدخول للنظام') }}</span>
        </a>
        <p style="font-size: 0.78rem; color: #94a3b8; margin-bottom: 1.25rem;">
            {{ __('إذا قام المشرف بتفعيلك الآن، انقر للتحقق والدخول مباشرة دون الحاجة لكتابة كلمة المرور مجدداً.') }}
        </p>

        {{-- Secondary Action: Logout & Language Switch --}}
        <div style="display: flex; gap: 8px; margin-bottom: 0.5rem;">
            <a href="{{ route('lang.switch', app()->getLocale() === 'en' ? 'ar' : 'en') }}" class="btn-logout" style="width: auto; padding: 0.75rem 1.1rem;">
                <i class="fas fa-globe"></i>
                <span>{{ app()->getLocale() === 'en' ? 'عربي' : 'EN' }}</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" id="logoutForm" style="flex: 1;">
                @csrf
                <button type="submit" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>{{ __('تسجيل الخروج') }}</span>
                </button>
            </form>
        </div>

    </div>

</body>
</html>
