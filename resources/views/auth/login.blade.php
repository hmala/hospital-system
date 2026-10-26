<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | MEDIX ERP - إدارة المستشفيات والمراكز الطبية</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0284c7;
            --primary-dark: #0369a1;
            --primary-glow: rgba(2, 132, 199, 0.35);
            --accent-blue: #2563eb;
            --accent-emerald: #059669;
            --bg-page: linear-gradient(135deg, #f0f7ff 0%, #e8f4fd 50%, #f1fbf7 100%);
            --text-title: #0f172a;
            --text-body: #334155;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --border-card: rgba(255, 255, 255, 0.9);
            --border-input: #cbd5e1;
            --border-focus: #0284c7;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-page);
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
            padding: 1.5rem;
        }

        /* Subtle Light Tech Grid */
        .bg-grid {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(to right, rgba(15, 23, 42, 0.025) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(15, 23, 42, 0.025) 1px, transparent 1px);
            background-size: 36px 36px;
            pointer-events: none;
            z-index: 0;
        }

        /* Ambient Pastel Orbs */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(100px);
            pointer-events: none;
            z-index: 0;
            opacity: 0.65;
        }

        .orb-1 {
            width: 480px;
            height: 480px;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.28) 0%, rgba(56, 189, 248, 0) 70%);
            top: -10%;
            right: -5%;
            animation: orbFloat 14s ease-in-out infinite alternate;
        }

        .orb-2 {
            width: 520px;
            height: 520px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.22) 0%, rgba(16, 185, 129, 0) 70%);
            bottom: -15%;
            left: -5%;
            animation: orbFloat 18s ease-in-out infinite alternate-reverse;
        }

        .orb-3 {
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(129, 140, 248, 0.2) 0%, rgba(129, 140, 248, 0) 70%);
            top: 35%;
            left: 30%;
            animation: orbFloat 12s ease-in-out infinite alternate;
        }

        @keyframes orbFloat {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(35px, -25px) scale(1.06); }
            100% { transform: translate(-25px, 35px) scale(0.96); }
        }

        /* Main Glassmorphic White Container */
        .login-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1100px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid var(--border-card);
            border-radius: 28px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 
                0 25px 60px -15px rgba(30, 58, 138, 0.09),
                0 0 0 1px rgba(226, 232, 240, 0.7);
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        /* Right Side in RTL: Brand Showcase Panel */
        .brand-panel {
            padding: 3.5rem 3rem;
            background: linear-gradient(150deg, #f8fafc 0%, #edf5fd 55%, #f0fdf4 100%);
            border-left: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .brand-panel::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, #0284c7, #10b981, #2563eb);
        }

        .brand-top {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .brand-logo-holder {
            display: inline-flex;
            align-items: center;
            justify-content: flex-start;
        }

        .brand-logo-holder img {
            max-height: 82px;
            width: auto;
            filter: drop-shadow(0 4px 12px rgba(2, 132, 199, 0.15));
        }

        .brand-tagline {
            font-size: 1.65rem;
            font-weight: 800;
            line-height: 1.35;
            color: var(--text-title);
            letter-spacing: -0.5px;
        }

        .brand-tagline span {
            background: linear-gradient(135deg, #0284c7 0%, #059669 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.65;
        }

        /* Feature Pills in Brand Panel */
        .feature-list {
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            margin: 2rem 0;
        }

        .feature-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.1rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            transition: all 0.25s ease;
        }

        .feature-card:hover {
            border-color: #bae6fd;
            box-shadow: 0 6px 16px rgba(2, 132, 199, 0.08);
            transform: translateX(-4px);
        }

        .feature-icon-box {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .icon-blue {
            background: #eff6ff;
            color: #0284c7;
            border: 1px solid #dbeafe;
        }

        .icon-emerald {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #d1fae5;
        }

        .icon-purple {
            background: #faf5ff;
            color: #7c3aed;
            border: 1px solid #ede9fe;
        }

        .feature-text h4 {
            font-size: 0.92rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .feature-text p {
            font-size: 0.78rem;
            color: var(--text-muted);
            margin: 0;
        }

        .brand-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .hospital-badge {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            color: #334155;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: pulseDot 2s infinite;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(0.85); }
        }

        .system-version {
            font-size: 0.75rem;
            font-family: monospace;
            font-weight: 700;
            color: #475569;
            background: #e2e8f0;
            padding: 3px 8px;
            border-radius: 6px;
        }

        /* Left Side in RTL: Login Portal Form */
        .login-panel {
            padding: 3.5rem 3rem;
            background: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            margin-bottom: 2rem;
        }

        .login-title {
            font-size: 1.85rem;
            font-weight: 800;
            color: var(--text-title);
            margin-bottom: 0.4rem;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            font-size: 0.92rem;
            color: var(--text-muted);
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.35rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 0.86rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.45rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            right: 1.1rem;
            color: var(--text-light);
            font-size: 0.95rem;
            transition: color 0.2s ease;
            pointer-events: none;
        }

        .form-input {
            width: 100%;
            padding: 0.85rem 2.85rem 0.85rem 1rem;
            background: #f8fafc;
            border: 1.5px solid var(--border-input);
            border-radius: 14px;
            color: #0f172a;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Cairo', sans-serif;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }

        .form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .form-input:focus {
            background: #ffffff;
            border-color: var(--border-focus);
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.12);
        }

        .form-input:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: var(--primary);
        }

        .password-toggle-btn {
            position: absolute;
            left: 1rem;
            background: none;
            border: none;
            color: var(--text-light);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
            transition: color 0.2s ease;
        }

        .password-toggle-btn:hover {
            color: #0f172a;
        }

        /* Error States */
        .form-input.is-invalid {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .field-error {
            color: #dc2626;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        /* Checkbox & Forgot */
        .form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.2rem 0 1.8rem 0;
            font-size: 0.86rem;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 0.55rem;
            cursor: pointer;
            user-select: none;
            color: var(--text-muted);
            font-weight: 600;
        }

        .custom-checkbox input {
            display: none;
        }

        .checkbox-box {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .checkbox-box i {
            color: #ffffff;
            font-size: 0.65rem;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s ease;
        }

        .custom-checkbox input:checked + .checkbox-box {
            background: var(--primary);
            border-color: var(--primary);
            box-shadow: 0 0 8px rgba(2, 132, 199, 0.4);
        }

        .custom-checkbox input:checked + .checkbox-box i {
            opacity: 1;
            transform: scale(1);
        }

        .forgot-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s ease;
        }

        .forgot-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        /* Submit Button */
        .btn-submit {
            width: 100%;
            padding: 0.95rem 1.5rem;
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);
            border: none;
            border-radius: 14px;
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
            box-shadow: 0 10px 22px -4px rgba(2, 132, 199, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .btn-submit::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(255, 255, 255, 0.2), transparent);
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px -4px rgba(2, 132, 199, 0.55);
        }

        .btn-submit:hover::after {
            opacity: 1;
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        /* Footer Security Notice */
        .portal-footer {
            margin-top: 2rem;
            padding-top: 1.25rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            color: var(--text-light);
        }

        .portal-footer i {
            color: #059669;
        }

        /* Responsive Breakpoints */
        @media (max-width: 960px) {
            .login-wrapper {
                grid-template-columns: 1fr;
                max-width: 520px;
                border-radius: 22px;
            }

            .brand-panel {
                padding: 2.5rem 2rem 2rem 2rem;
                border-left: none;
                border-bottom: 1px solid #e2e8f0;
            }

            .brand-tagline {
                font-size: 1.35rem;
            }

            .feature-list {
                display: none;
            }

            .login-panel {
                padding: 2.5rem 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Decor -->
    <div class="bg-grid"></div>
    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="ambient-orb orb-3"></div>

    @php
        $loginLogoFile = public_path('images/logo.png');
        if (!file_exists($loginLogoFile)) {
            $loginLogoFile = public_path('images/logo.jpeg');
        }
        $loginLogoSrc = file_exists($loginLogoFile) ? 'data:image/png;base64,' . base64_encode(file_get_contents($loginLogoFile)) : asset('images/logo.png');
    @endphp

    <!-- Main White Glass Container -->
    <div class="login-wrapper">

        <!-- Right Side in RTL: Brand & Showcase -->
        <div class="brand-panel">
            <div class="brand-top">
                <div class="brand-logo-holder">
                    <img src="{{ $loginLogoSrc }}" alt="MEDIX ERP" class="img-fluid">
                </div>

                <div class="brand-tagline">
                    المنظومة السحابية الذكية<br>
                    <span>لإدارة المستشفيات والمراكز</span>
                </div>

                <p class="brand-desc">
                    حلول رقمية متكاملة تجمع بين الملف الطبي الموحد، التحاسب المالي والصناديق، والربط اللحظي بين العيادات والمحطات التشخيصية.
                </p>

                <!-- Dynamic Features Pill List -->
                <div class="feature-list">
                    <div class="feature-card">
                        <div class="feature-icon-box icon-blue">
                            <i class="fas fa-heart-pulse"></i>
                        </div>
                        <div class="feature-text">
                            <h4>إدارة سريرية وطوارئ فورية</h4>
                            <p>طابور ذكي، متابعة مرضى الطوارئ، وتوثيق لحظي</p>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box icon-emerald">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div class="feature-text">
                            <h4>نظام الصناديق والضمان الصحي</h4>
                            <p>أتمتة نسب التحمل، مطالبات الضمان، ووصولات حرارية</p>
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon-box icon-purple">
                            <i class="fas fa-microscope"></i>
                        </div>
                        <div class="feature-text">
                            <h4>محطات تشخيصية متطورة</h4>
                            <p>مختبرات متكاملة، واستدعاء صوتي للأشعة والسونار</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="brand-bottom">
                <div class="hospital-badge">
                    <div class="status-dot"></div>
                    <span>مستشفى الكفاءات التخصصي</span>
                </div>
                <div class="system-version">v2.4 LTS</div>
            </div>
        </div>

        <!-- Left Side in RTL: Login Portal Form -->
        <div class="login-panel">
            <div class="login-header">
                <h1 class="login-title">تسجيل الدخول</h1>
                <p class="login-subtitle">مرحباً بك مجدداً، أدخل بياناتك للنفاذ إلى لوحة العمل</p>
            </div>

            <form class="login-form" method="POST" action="{{ route('login') }}" autocomplete="on">
                @csrf

                <!-- Email Input -->
                <div class="form-group">
                    <label for="email" class="form-label">البريد الإلكتروني أو اسم المستخدم</label>
                    <div class="input-wrapper">
                        <input id="email" 
                               type="email" 
                               class="form-input @error('email') is-invalid @enderror"
                               name="email" 
                               value="{{ old('email') }}" 
                               required 
                               autocomplete="email" 
                               autofocus 
                               placeholder="example@medixerp.com">
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    @error('email')
                        <div class="field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="form-group">
                    <label for="password" class="form-label">كلمة المرور</label>
                    <div class="input-wrapper">
                        <input id="password" 
                               type="password" 
                               class="form-input @error('password') is-invalid @enderror"
                               name="password" 
                               required 
                               autocomplete="current-password" 
                               placeholder="••••••••••••">
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword()" title="إظهار/إخفاء كلمة المرور">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="field-error">
                            <i class="fas fa-circle-exclamation"></i>
                            <span>{{ $message }}</span>
                        </div>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-options">
                    <label class="custom-checkbox">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkbox-box">
                            <i class="fas fa-check"></i>
                        </span>
                        <span>تذكر بياناتي</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="forgot-link" href="{{ route('password.request') }}">
                            نسيت كلمة المرور؟
                        </a>
                    @endif
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-submit" id="btn-submit">
                    <i class="fas fa-arrow-left-to-bracket"></i>
                    <span>دخول المنظومة</span>
                </button>
            </form>

            <!-- Security Notice Footer -->
            <div class="portal-footer">
                <i class="fas fa-shield-halved"></i>
                <span>اتصال آمن ومشفّر بتقنية 256-Bit SSL | معتمد للاستخدام الطبي</span>
            </div>
        </div>

    </div>

    {{-- Global Page Loader Ultra --}}
    <div id="global-page-loader">
        <div class="loader-top-bar" id="loader-top-bar"></div>
        <div class="loader-logo-wrapper">
            <div class="loader-glow-aura"></div>
            <div class="loader-spinner-ring-outer"></div>
            <div class="loader-spinner-ring"></div>
            <img src="{{ $loginLogoSrc }}" alt="MEDIX ERP" class="loader-logo-img">
        </div>
        <div class="loader-text-wrapper">
            <div class="loader-text">
                جاري التحقق والاتصال بالمنظومة<span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <style>
        #global-page-loader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            z-index: 999999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            visibility: hidden;
            transition: opacity 0.35s ease-in-out, visibility 0.35s ease-in-out;
        }

        #global-page-loader.active {
            opacity: 1;
            pointer-events: auto;
            visibility: visible;
        }

        .loader-top-bar {
            position: absolute;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #0284c7, #10b981, #2563eb);
            box-shadow: 0 0 14px rgba(2, 132, 199, 0.6);
            transition: width 0.35s ease;
        }

        .loader-logo-wrapper {
            position: relative;
            width: 140px;
            height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .loader-glow-aura {
            position: absolute;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(2, 132, 199, 0.3) 0%, rgba(2, 132, 199, 0) 70%);
            animation: auraPulse 1.8s ease-in-out infinite alternate;
        }

        .loader-spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            border-top-color: #0284c7;
            border-right-color: #10b981;
            animation: loaderSpin 0.9s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
        }

        .loader-spinner-ring-outer {
            position: absolute;
            width: 125%;
            height: 125%;
            border-radius: 50%;
            border: 2px dashed rgba(2, 132, 199, 0.3);
            animation: loaderSpinReverse 3.5s linear infinite;
        }

        .loader-logo-img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 50%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            padding: 12px;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.2);
            z-index: 2;
            animation: logoPulse 1.4s ease-in-out infinite alternate;
        }

        .loader-text-wrapper {
            margin-top: 26px;
            text-align: center;
        }

        .loader-text {
            color: #0f172a;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }

        .loader-dots span {
            animation: dotBlink 1.4s infinite fill-mode: both;
            opacity: 0;
            font-weight: bold;
        }
        .loader-dots span:nth-child(1) { animation-delay: 0.2s; }
        .loader-dots span:nth-child(2) { animation-delay: 0.4s; }
        .loader-dots span:nth-child(3) { animation-delay: 0.6s; }

        @keyframes loaderSpin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes loaderSpinReverse {
            0% { transform: rotate(360deg); }
            100% { transform: rotate(0deg); }
        }

        @keyframes logoPulse {
            0% { transform: scale(0.92); box-shadow: 0 0 15px rgba(2, 132, 199, 0.3); }
            100% { transform: scale(1.06); box-shadow: 0 0 30px rgba(2, 132, 199, 0.5); }
        }

        @keyframes auraPulse {
            0% { transform: scale(0.8); opacity: 0.3; }
            100% { transform: scale(1.6); opacity: 0.8; }
        }

        @keyframes dotBlink {
            0%, 100% { opacity: 0; }
            50% { opacity: 1; }
        }
    </style>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const loader = document.getElementById('global-page-loader');
            const topBar = document.getElementById('loader-top-bar');
            const loginForm = document.querySelector('.login-form');

            if (loginForm) {
                loginForm.addEventListener('submit', function() {
                    if (loader) {
                        loader.classList.add('active');
                        if (topBar) {
                            topBar.style.width = '0%';
                            let w = 0;
                            const interval = setInterval(function() {
                                if (w < 90) { 
                                    w += 6; 
                                    topBar.style.width = w + '%'; 
                                } else {
                                    clearInterval(interval);
                                }
                            }, 80);
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
