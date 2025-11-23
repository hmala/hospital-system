<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المستشفى - تسجيل الدخول</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3b82f6;
            --secondary-color: #60a5fa;
            --accent-color: #10b981;
            --background-color: #f8fafc;
            --surface-color: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-muted: #9ca3af;
            --border-color: #e5e7eb;
            --border-hover: #d1d5db;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --transition: all 0.2s ease-in-out;
            --border-radius: 12px;
            --border-radius-lg: 16px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, rgba(248, 250, 252, 0.8) 0%, rgba(241, 245, 249, 0.8) 100%), url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-primary);
            line-height: 1.6;
        }



        .login-container {
            width: 100%;
            max-width: 1400px;
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        /* قسم الترحيب */
        .welcome-section {
            text-align: center;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.7);
            border-radius: var(--border-radius-lg);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-lg);
        }

        .welcome-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .luxury-features {
            margin-top: 2rem;
        }

        .feature-item {
            background: var(--surface-color);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
            transition: var(--transition);
        }

        .feature-item:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow);
            transform: scale(1.05);
        }

        .feature-icon {
            font-size: 1.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .feature-desc {
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        /* بطاقة تسجيل الدخول */
        .login-card {
            background: transparent;
            border-radius: var(--border-radius-lg);
            box-shadow: none;
            border: none;
            overflow: hidden;
        }

        .login-card:hover {
            transform: perspective(1000px) rotateX(0deg);
        }

        /* رأس البطاقة */
        .login-header {
            background: transparent;
            color: var(--text-primary);
            padding: 3rem 2rem 2rem;
            text-align: center;
        }

        .login-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
        }

        .login-logo i {
            font-size: 2.5rem;
            color: white;
        }

        .login-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
        }

        /* نموذج تسجيل الدخول */
        .login-form {
            padding: 3rem 2rem;
        }

        .form-group {
            margin-bottom: 2.5rem;
            position: relative;
        }

        .form-label {
            display: block;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .form-label i {
            color: var(--primary-color);
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 1rem;
            background: var(--surface-color);
            transition: var(--transition);
            outline: none;
            font-weight: 400;
            color: var(--text-primary);
        }

        .form-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-muted);
        }

        .form-input.is-invalid {
            border-color: #ef4444;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.1rem;
            pointer-events: none;
        }

        .form-input:focus + .input-icon {
            color: var(--primary-color);
        }

        /* زر كشف/إخفاء كلمة المرور */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .password-toggle:hover {
            color: var(--primary-color);
            background: rgba(59, 130, 246, 0.1);
        }

        /* مربع تذكرني */
        .checkbox-group {
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.95rem;
            color: var(--text-secondary);
        }

        .checkbox-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }

        .checkmark {
            width: 18px;
            height: 18px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            position: relative;
            transition: var(--transition);
        }

        .checkbox-input:checked ~ .checkmark {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .checkbox-input:checked ~ .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        /* أزرار الإجراءات */
        .form-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 1.25rem 2rem;
            border-radius: var(--border-radius);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            box-shadow: var(--shadow);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .forgot-password-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            text-align: center;
            transition: var(--transition);
            padding: 0.75rem;
        }

        .forgot-password-link:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }

        /* رسائل الخطأ */
        .error-message {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }

        .error-message i {
            font-size: 14px;
        }

        /* التنسيق المتجاوب */
        @media (max-width: 1024px) {
            .login-container {
                grid-template-columns: 1fr;
                gap: 3rem;
            }

            .welcome-section {
                order: 2;
            }

            .login-card {
                order: 1;
            }
        }

        @media (max-width: 768px) {
            .login-container {
                padding: 1rem;
                gap: 2rem;
            }

            .welcome-title {
                font-size: 2rem;
            }

            .welcome-icon {
                font-size: 3rem;
            }

            .luxury-features {
                grid-template-columns: 1fr;
            }

            .login-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .login-title {
                font-size: 1.5rem;
            }

            .login-form {
                padding: 2rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .welcome-title {
                font-size: 1.5rem;
            }

            .login-header {
                padding: 1.5rem 1rem 1rem;
            }

            .login-logo {
                width: 60px;
                height: 60px;
            }

            .login-logo i {
                font-size: 1.8rem;
            }

            .login-title {
                font-size: 1.3rem;
            }

            .login-form {
                padding: 1.5rem 1rem;
            }

            .form-input {
                padding: 0.875rem 0.875rem 0.875rem 2.5rem;
            }

            .input-icon {
                left: 0.75rem;
            }

            .password-toggle {
                right: 0.75rem;
            }
        }

        /* تأثيرات الدخول */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-container {
            animation: fadeInUp 1s ease-out;
        }

        .welcome-section {
            animation: slideInRight 1s ease-out 0.3s both;
        }

        .login-card {
            animation: slideInLeft 1s ease-out 0.3s both;
        }

        .form-group {
            animation: fadeInUp 0.8s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.5s; }
        .form-group:nth-child(2) { animation-delay: 0.7s; }
        .checkbox-group { animation-delay: 0.9s; }
        .form-actions { animation-delay: 1.1s; }

        /* تخصيص شريط التمرير */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: var(--background-color);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- قسم الترحيب -->
        <div class="welcome-section">
            <div class="welcome-icon">
                <i class="fas fa-hospital"></i>
            </div>
            <h1 class="welcome-title">نظام إدارة المستشفى</h1>
            <p class="welcome-subtitle">
                منصة شاملة لإدارة المستشفيات والعيادات الطبية بكفاءة واحترافية
            </p>

            <div class="luxury-features">
                <img src="https://images.unsplash.com/photo-1551190822-a9333d879b1f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="نظام إدارة المستشفى" style="width: 100%; height: auto; border-radius: var(--border-radius); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.1); opacity: 0.7;">
            </div>
        </div>

        <!-- بطاقة تسجيل الدخول -->
        <div class="login-card">

            <form class="login-form" method="POST" action="{{ route('login') }}">
                @csrf

                <!-- حقل البريد الإلكتروني -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i>
                        البريد الإلكتروني
                    </label>
                    <input id="email" type="email" class="form-input @error('email') is-invalid @enderror"
                           name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="أدخل بريدك الإلكتروني">
                    <i class="fas fa-envelope input-icon"></i>

                    @error('email')
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- حقل كلمة المرور -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        كلمة المرور
                    </label>
                    <input id="password" type="password" class="form-input @error('password') is-invalid @enderror"
                           name="password" required autocomplete="current-password" placeholder="أدخل كلمة المرور">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>

                    @error('password')
                        <div class="error-message">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- مربع تذكرني -->
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input class="checkbox-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        تذكرني
                    </label>
                </div>

                <!-- أزرار الإجراءات -->
                <div class="form-actions">
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i>
                        تسجيل الدخول
                    </button>

                    @if (Route::has('password.request'))
                        <a class="forgot-password-link" href="{{ route('password.request') }}">
                            <i class="fas fa-key"></i>
                            نسيت كلمة المرور؟
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <script>
        // وظيفة إظهار/إخفاء كلمة المرور
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

        // إضافة تأثيرات تفاعلية بسيطة
        document.addEventListener('DOMContentLoaded', function() {
            // تأثير التركيز على الحقول
            const inputs = document.querySelectorAll('.form-input');

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.parentElement.classList.add('focused');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.parentElement.classList.remove('focused');
                });
            });
        });
    </script>
</body>
</html>
