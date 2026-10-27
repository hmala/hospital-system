<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول | MEDIX ERP - Hospital Management System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body {
            min-height: 100vh;
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f0f7ff;
            color: #1e3a8a;
            overflow: hidden;
        }

        /* ══ SPLIT BACKGROUND ══ */
        .page-wrap {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            min-height: 100vh;
        }

        /* LEFT — light airy hospital white-blue */
        .left-panel {
            background: linear-gradient(160deg, #f8fbff 0%, #e8f4fd 40%, #dbeafe 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 3.5rem;
            position: relative;
            overflow: hidden;
        }

        /* Soft corner glow */
        .left-panel::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 500px; height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(59,130,246,0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Diagonal divider */
        .left-panel::after {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: linear-gradient(
                to bottom,
                transparent 60%,
                rgba(30,58,138,0.06) 100%
            );
            pointer-events: none;
        }

        /* RIGHT — deep navy with dynamic multi-tier aurora and reflections */
        .right-panel {
            background: radial-gradient(circle at 85% 15%, rgba(56, 189, 248, 0.28) 0%, transparent 45%),
                        radial-gradient(circle at 15% 85%, rgba(30, 58, 138, 0.70) 0%, transparent 50%),
                        linear-gradient(155deg, #1e3a8a 0%, #1d4ed8 52%, #1e40af 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
            position: relative;
            overflow: hidden;
        }

        /* ══ Blinking White Lines Canvas ══ */
        .blinking-lines-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 1;
        }

        /* Ambient Glass Sheen Reflection */
        .panel-glass-sheen {
            position: absolute;
            top: -30%;
            left: -30%;
            width: 160%;
            height: 160%;
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.14) 0%,
                rgba(255, 255, 255, 0.03) 24%,
                transparent 48%
            );
            transform: rotate(-10deg);
            pointer-events: none;
        }

        /* Cyber Medical Telemetry Dot Grid */
        .panel-cyber-grid {
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.20) 1.2px, transparent 1.2px);
            background-size: 26px 26px;
            opacity: 0.25;
            mask-image: radial-gradient(ellipse at 50% 50%, black 35%, transparent 80%);
            -webkit-mask-image: radial-gradient(ellipse at 50% 50%, black 35%, transparent 80%);
            pointer-events: none;
        }

        /* Animated White ECG SVG Paths with Neon Blink */
        .blinking-ecg-svg {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .ecg-white-path {
            stroke: #ffffff;
            stroke-width: 1.6;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            filter: drop-shadow(0 0 6px #ffffff) drop-shadow(0 0 14px rgba(56, 189, 248, 0.9));
        }

        .ecg-blink-1 {
            stroke-dasharray: 800;
            stroke-dashoffset: 800;
            animation: ecgFlow 5s linear infinite, whiteBlink1 2.2s ease-in-out infinite alternate;
        }

        .ecg-blink-2 {
            stroke-dasharray: 800;
            stroke-dashoffset: 800;
            animation: ecgFlowReverse 6.5s linear infinite, whiteBlink2 3s ease-in-out infinite alternate 0.8s;
        }

        .ecg-blink-3 {
            stroke-dasharray: 800;
            stroke-dashoffset: 800;
            animation: ecgFlow 5.8s linear infinite, whiteBlink3 2.6s ease-in-out infinite alternate 1.4s;
        }

        @keyframes ecgFlow {
            0%   { stroke-dashoffset: 800; }
            100% { stroke-dashoffset: 0; }
        }

        @keyframes ecgFlowReverse {
            0%   { stroke-dashoffset: -800; }
            100% { stroke-dashoffset: 0; }
        }

        @keyframes whiteBlink1 {
            0%   { opacity: 0.15; filter: drop-shadow(0 0 2px #ffffff); }
            50%  { opacity: 0.85; filter: drop-shadow(0 0 8px #ffffff) drop-shadow(0 0 18px #38bdf8); }
            100% { opacity: 0.25; filter: drop-shadow(0 0 3px #ffffff); }
        }

        @keyframes whiteBlink2 {
            0%   { opacity: 0.10; filter: drop-shadow(0 0 2px #ffffff); }
            60%  { opacity: 0.90; filter: drop-shadow(0 0 10px #ffffff) drop-shadow(0 0 22px #93c5fd); }
            100% { opacity: 0.20; filter: drop-shadow(0 0 2px #ffffff); }
        }

        @keyframes whiteBlink3 {
            0%   { opacity: 0.20; filter: drop-shadow(0 0 2px #ffffff); }
            45%  { opacity: 0.95; filter: drop-shadow(0 0 9px #ffffff) drop-shadow(0 0 20px #38bdf8); }
            100% { opacity: 0.15; filter: drop-shadow(0 0 2px #ffffff); }
        }

        /* Horizontal White Laser Streaks */
        .white-line-streak {
            position: absolute;
            height: 1.5px;
            background: linear-gradient(90deg, transparent 0%, rgba(255, 255, 255, 0.2) 20%, #ffffff 50%, rgba(255, 255, 255, 0.2) 80%, transparent 100%);
            box-shadow: 0 0 8px #ffffff, 0 0 16px rgba(56, 189, 248, 0.9);
            border-radius: 50%;
            pointer-events: none;
        }

        .streak-h1 {
            top: 22%;
            left: -30%;
            width: 70%;
            animation: streakHFlow1 6s cubic-bezier(0.4, 0, 0.2, 1) infinite, streakFlash1 2.4s ease-in-out infinite;
        }

        .streak-h2 {
            top: 68%;
            right: -25%;
            width: 65%;
            animation: streakHFlow2 7.5s cubic-bezier(0.4, 0, 0.2, 1) infinite 1.8s, streakFlash2 3.1s ease-in-out infinite 0.7s;
        }

        .streak-h3 {
            bottom: 14%;
            left: 10%;
            width: 45%;
            animation: streakPulse3 3.6s ease-in-out infinite;
        }

        @keyframes streakHFlow1 {
            0%   { transform: translateX(0); opacity: 0; }
            20%  { opacity: 0.9; }
            80%  { opacity: 0.9; }
            100% { transform: translateX(180%); opacity: 0; }
        }

        @keyframes streakHFlow2 {
            0%   { transform: translateX(0); opacity: 0; }
            20%  { opacity: 0.85; }
            80%  { opacity: 0.85; }
            100% { transform: translateX(-170%); opacity: 0; }
        }

        @keyframes streakPulse3 {
            0%, 100% { opacity: 0.15; transform: scaleX(0.7); }
            50%      { opacity: 0.85; transform: scaleX(1.15); box-shadow: 0 0 12px #ffffff, 0 0 24px #38bdf8; }
        }

        @keyframes streakFlash1 {
            0%, 100% { opacity: 0.2; }
            50%      { opacity: 1; filter: drop-shadow(0 0 10px #ffffff); }
        }

        @keyframes streakFlash2 {
            0%, 100% { opacity: 0.2; }
            45%      { opacity: 0.95; filter: drop-shadow(0 0 8px #ffffff); }
        }

        /* Vertical White Laser Beams */
        .white-line-v {
            position: absolute;
            width: 1.5px;
            background: linear-gradient(180deg, transparent 0%, rgba(255, 255, 255, 0.3) 25%, #ffffff 50%, rgba(255, 255, 255, 0.3) 75%, transparent 100%);
            box-shadow: 0 0 8px #ffffff, 0 0 18px rgba(56, 189, 248, 0.7);
            border-radius: 50%;
            pointer-events: none;
        }

        .streak-v1 {
            left: 10%;
            top: 0;
            height: 55%;
            animation: vStreakBlink1 4.2s ease-in-out infinite alternate;
        }

        .streak-v2 {
            right: 12%;
            bottom: 0;
            height: 60%;
            animation: vStreakBlink2 5s ease-in-out infinite alternate 1.5s;
        }

        @keyframes vStreakBlink1 {
            0%, 100% { opacity: 0.15; transform: translateY(-10px); }
            50%      { opacity: 0.85; transform: translateY(15px); }
        }

        @keyframes vStreakBlink2 {
            0%, 100% { opacity: 0.10; transform: translateY(15px); }
            50%      { opacity: 0.80; transform: translateY(-10px); }
        }

        /* Twinkling White Telemetry Nodes */
        .white-sparkle-node {
            position: absolute;
            width: 4px;
            height: 4px;
            background: #ffffff;
            border-radius: 50%;
            box-shadow: 0 0 8px #ffffff, 0 0 16px #38bdf8;
            pointer-events: none;
        }

        .node-1 { top: 22%; left: 35%; animation: nodeSparkle 2.4s ease-in-out infinite; }
        .node-2 { top: 38%; right: 24%; animation: nodeSparkle 3.1s ease-in-out infinite 0.7s; }
        .node-3 { top: 68%; left: 22%; animation: nodeSparkle 2.7s ease-in-out infinite 1.2s; }
        .node-4 { bottom: 22%; right: 30%; animation: nodeSparkle 3.5s ease-in-out infinite 1.8s; }
        .node-5 { top: 15%; right: 42%; animation: nodeSparkle 2.9s ease-in-out infinite 0.4s; }

        @keyframes nodeSparkle {
            0%, 100% { opacity: 0.2; transform: scale(0.6); }
            50%      { opacity: 1; transform: scale(1.6); box-shadow: 0 0 12px #ffffff, 0 0 22px #93c5fd; }
        }

        /* ══ LEFT CONTENT ══ */
        .brand-showcase {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 480px;
        }

        /* Large Logo with Glowing Aura */
        .main-logo-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 210px;
            height: 210px;
            margin: 0 auto 1rem auto;
        }

        .logo-aura-glow {
            position: absolute;
            width: 245px;
            height: 245px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.32) 0%, rgba(96, 165, 250, 0.20) 50%, transparent 70%);
            animation: auraPulse 2.8s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 1;
        }

        .logo-aura-ring {
            position: absolute;
            width: 202px;
            height: 202px;
            border-radius: 50%;
            border: 2.5px solid rgba(37, 99, 235, 0.45);
            box-shadow: 0 0 32px rgba(37, 99, 235, 0.40), inset 0 0 20px rgba(37, 99, 235, 0.22);
            animation: ringPulse 3s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 1;
        }

        .logo-aura-dashed {
            position: absolute;
            width: 224px;
            height: 224px;
            border-radius: 50%;
            border: 1.5px dashed rgba(59, 130, 246, 0.35);
            animation: ringSpin 22s linear infinite;
            pointer-events: none;
            z-index: 1;
        }

        .logo-badge-container {
            position: relative;
            z-index: 2;
            width: 170px;
            height: 170px;
            border-radius: 50%;
            background: #ffffff;
            border: 2px solid rgba(219, 234, 254, 0.95);
            box-shadow: 0 14px 38px rgba(29, 78, 216, 0.20), 0 0 35px rgba(96, 165, 250, 0.42);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 18px;
            animation: logoFloat 5s ease-in-out infinite alternate;
            transition: all 0.35s ease;
        }

        .logo-badge-container:hover {
            transform: scale(1.05);
            box-shadow: 0 18px 46px rgba(29, 78, 216, 0.28), 0 0 45px rgba(37, 99, 235, 0.60);
        }

        .main-logo-img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(29, 78, 216, 0.15));
        }

        @keyframes ringPulse {
            0%   { transform: scale(0.96); opacity: 0.7; }
            100% { transform: scale(1.04); opacity: 1; }
        }

        @keyframes ringSpin {
            to { transform: rotate(360deg); }
        }

        @keyframes auraPulse {
            0%   { transform: scale(0.85); opacity: 0.35; }
            100% { transform: scale(1.15); opacity: 0.85; }
        }

        @keyframes logoFloat {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }

        .brand-name {
            font-size: 2.2rem;
            font-weight: 900;
            color: #1e3a8a;
            letter-spacing: -1px;
            line-height: 1.1;
            margin-bottom: 0.2rem;
        }

        .brand-name span {
            color: #1d4ed8;
        }

        .brand-sub {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 3px;
            color: #3b82f6;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .brand-tagline-main {
            font-size: 1rem;
            font-weight: 500;
            color: #3b5fa0;
            margin-bottom: 0.3rem;
        }

        /* INTEGRATE • MANAGE • CARE pills */
        .brand-pills {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 1rem 0 1.8rem 0;
        }

        .brand-pill {
            font-size: 0.75rem;
            font-weight: 800;
            color: #1d4ed8;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .brand-dot {
            width: 5px; height: 5px;
            background: #3b82f6;
            border-radius: 50%;
        }

        /* ECG line */
        .ecg-wrap {
            width: 100%;
            max-width: 380px;
            margin: 0 auto 1.8rem auto;
        }

        .ecg-svg { width: 100%; height: 60px; }

        .ecg-path {
            stroke: #2563eb;
            stroke-width: 2.5;
            fill: none;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-dasharray: 650;
            stroke-dashoffset: 650;
            filter: drop-shadow(0 0 5px rgba(37,99,235,0.45));
            animation: ecgDraw 3s ease-in-out infinite;
        }

        @keyframes ecgDraw {
            0%   { stroke-dashoffset: 650; opacity: 0; }
            15%  { opacity: 1; }
            65%  { stroke-dashoffset: 0; opacity: 1; }
            100% { stroke-dashoffset: -650; opacity: 0; }
        }

        /* Hospital Departments Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            width: 100%;
            max-width: 430px;
            margin: 0 auto;
        }

        .feature-chip {
            background: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(147, 197, 253, 0.50);
            border-radius: 14px;
            padding: 0.75rem 0.5rem;
            text-align: center;
            transition: all 0.28s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.05);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .feature-chip:hover {
            background: #ffffff;
            border-color: rgba(29, 78, 216, 0.40);
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(29, 78, 216, 0.14);
        }

        .feature-chip-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(219, 234, 254, 0.85) 0%, rgba(191, 219, 254, 0.55) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.35rem;
            transition: all 0.28s ease;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .feature-chip-icon i {
            font-size: 1.15rem;
            color: #1d4ed8;
            transition: all 0.28s ease;
        }

        .feature-chip:hover .feature-chip-icon {
            background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%);
            transform: scale(1.08);
            box-shadow: 0 4px 12px rgba(29, 78, 216, 0.35);
        }

        .feature-chip:hover .feature-chip-icon i {
            color: #ffffff;
        }

        .feature-chip-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #1e3a8a;
            line-height: 1.25;
            display: block;
            margin-bottom: 2px;
        }

        .feature-chip-sub {
            font-size: 0.58rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            display: block;
        }

        /* Bottom tagline */
        .bottom-tagline {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: #6b8cbb;
            font-weight: 500;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* ══ RIGHT PANEL — LOGIN CARD ══ */
        .login-box {
            width: 100%;
            max-width: 380px;
            position: relative;
            z-index: 2;
        }

        /* Card logo with Glowing Aura */
        .card-top {
            text-align: center;
            margin-bottom: 1.6rem;
        }

        /* Biometric Fingerprint (NO cross, NO ring, glowing neon biometric scanner) */
        .fingerprint-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 135px;
            height: 135px;
            margin: 0 auto 0.6rem auto;
        }

        .fingerprint-ambient-glow {
            position: absolute;
            width: 195px;
            height: 195px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.50) 0%, rgba(37, 99, 235, 0.22) 50%, transparent 70%);
            animation: auraPulse 2.6s ease-in-out infinite alternate;
            pointer-events: none;
            z-index: 1;
        }

        .fingerprint-scan-beam {
            position: absolute;
            width: 115px;
            height: 2.5px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(90deg, transparent 0%, rgba(56, 189, 248, 0.3) 15%, #ffffff 50%, rgba(56, 189, 248, 0.3) 85%, transparent 100%);
            box-shadow: 0 0 12px #ffffff, 0 0 22px #38bdf8, 0 0 35px rgba(56, 189, 248, 0.8);
            animation: scanLine 2.8s ease-in-out infinite;
            border-radius: 50%;
            pointer-events: none;
            z-index: 3;
        }

        .fingerprint-svg {
            position: relative;
            z-index: 2;
            width: 110px;
            height: 110px;
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.95))
                    drop-shadow(0 0 20px rgba(56, 189, 248, 0.90))
                    drop-shadow(0 0 40px rgba(37, 99, 235, 0.70));
            animation: fingerFloat 3.8s ease-in-out infinite alternate;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .fingerprint-svg:hover {
            transform: scale(1.08) translateY(-4px);
            filter: drop-shadow(0 0 16px rgba(255, 255, 255, 1))
                    drop-shadow(0 0 32px rgba(56, 189, 248, 0.98))
                    drop-shadow(0 0 55px rgba(37, 99, 235, 0.85));
        }

        @keyframes scanLine {
            0%   { top: 12%; opacity: 0; }
            20%  { opacity: 1; }
            80%  { opacity: 1; }
            100% { top: 86%; opacity: 0; }
        }

        @keyframes fingerFloat {
            0%   { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 900;
            color: #ffffff;
            margin-bottom: 0.2rem;
            letter-spacing: -0.5px;
        }

        .card-subtitle {
            font-size: 0.8rem;
            color: rgba(191,219,254,0.80);
            font-weight: 500;
        }

        /* Form */
        .field-group { margin-bottom: 1rem; }

        .field-wrap { position: relative; display: flex; align-items: center; }

        .field-icon {
            position: absolute;
            right: 1.1rem;
            font-size: 0.88rem;
            color: rgba(147,197,253,0.7);
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 2;
        }

        .field-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 12px;
            padding: 0.88rem 3rem 0.88rem 1rem;
            font-size: 0.92rem;
            font-weight: 500;
            color: #ffffff;
            font-family: 'Cairo', sans-serif;
            outline: none;
            transition: all 0.25s ease;
            caret-color: #93c5fd;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.15), 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .field-input::placeholder { color: rgba(191, 219, 254, 0.65); font-weight: 400; }

        .field-input:focus {
            border-color: rgba(255, 255, 255, 0.65);
            background: rgba(255, 255, 255, 0.18);
            box-shadow: inset 0 1px 2px rgba(255, 255, 255, 0.25), 0 0 0 3px rgba(56, 189, 248, 0.28), 0 8px 25px rgba(29, 78, 216, 0.35);
        }

        .field-input:focus + .field-icon { color: #93c5fd; }

        .eye-btn {
            position: absolute;
            left: 1rem;
            background: none;
            border: none;
            color: rgba(147,197,253,0.7);
            cursor: pointer;
            font-size: 0.88rem;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 2;
        }
        .eye-btn:hover { color: #ffffff; }

        .error-msg {
            margin-top: 0.35rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: #fca5a5;
            padding: 0.3rem 0.65rem;
            background: rgba(239,68,68,0.15);
            border: 1px solid rgba(252,165,165,0.25);
            border-radius: 8px;
        }

        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.1rem 0 1.5rem 0;
            font-size: 0.82rem;
        }

        .remember-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: rgba(191,219,254,0.85);
            font-weight: 600;
            cursor: pointer;
            user-select: none;
        }
        .remember-label input { display: none; }

        .chk {
            width: 16px; height: 16px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.28);
            border-radius: 4px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease; flex-shrink: 0;
        }
        .chk i { color: #1e3a8a; font-size: 0.6rem; opacity: 0; transform: scale(0.5); transition: all 0.2s ease; }
        .remember-label input:checked + .chk { background: #ffffff; border-color: #ffffff; }
        .remember-label input:checked + .chk i { opacity: 1; transform: scale(1); }

        .forgot-link {
            color: rgba(191,219,254,0.90);
            text-decoration: none;
            font-weight: 700;
            font-size: 0.82rem;
            transition: color 0.2s ease;
        }
        .forgot-link:hover { color: #ffffff; text-decoration: underline; }

        /* Submit button — white on navy */
        .btn-login {
            width: 100%;
            background: #ffffff;
            color: #1d4ed8;
            font-size: 1rem;
            font-weight: 900;
            font-family: 'Cairo', sans-serif;
            border: none;
            border-radius: 12px;
            padding: 0.92rem 1.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.6rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.25), inset 0 1px 0 rgba(255,255,255,0.9);
            transition: all 0.25s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-login::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(219,234,254,0.5) 0%, transparent 60%);
            opacity: 0;
            transition: opacity 0.25s ease;
        }
        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,0.3); color: #1e40af; }
        .btn-login:hover::before { opacity: 1; }
        .btn-login:active { transform: translateY(0); }

        .help-link {
            display: block;
            text-align: center;
            margin-top: 1.3rem;
            color: rgba(147,197,253,0.65);
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .help-link:hover { color: rgba(191,219,254,0.90); }

        /* Smarter Care tagline inside right panel */
        .right-tagline {
            margin-top: 2rem;
            text-align: center;
            color: rgba(147,197,253,0.55);
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* System live pill */
        .live-pill {
            position: absolute;
            top: 1.8rem; left: 50%; transform: translateX(-50%);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 1rem;
            background: rgba(255,255,255,0.10);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            color: rgba(191,219,254,0.90);
            backdrop-filter: blur(8px);
            white-space: nowrap;
        }
        .live-pill .dot {
            width: 6px; height: 6px;
            background: #4ade80;
            border-radius: 50%;
            box-shadow: 0 0 6px #4ade80;
            animation: livePulse 2s ease-in-out infinite;
        }
        @keyframes livePulse {
            0%,100% { opacity:1; transform:scale(1); }
            50% { opacity:0.3; transform:scale(0.7); }
        }

        @media (max-width: 900px) {
            .page-wrap { grid-template-columns: 1fr; }
            .left-panel { display: none; }
            .right-panel { min-height: 100vh; }
            html, body { overflow-y: auto; }
        }
    </style>
</head>
<body>

    @php
        $loginLogoFile = public_path('images/logo.png');
        if (!file_exists($loginLogoFile)) {
            $loginLogoFile = public_path('images/logo.jpeg');
        }
        $loginLogoSrc = file_exists($loginLogoFile)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($loginLogoFile))
            : asset('images/logo.png');

        $doctorAvatarFile = public_path('images/doctor_avatar.png');
        $doctorAvatarSrc = file_exists($doctorAvatarFile)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($doctorAvatarFile))
            : asset('images/doctor_avatar.png');
    @endphp

    <div class="page-wrap">

        <!-- ══ LEFT: Brand Showcase ══ -->
        <div class="left-panel">
            <div class="brand-showcase">

                <!-- Large logo with Glowing Aura -->
                <div class="main-logo-wrap">
                    <div class="logo-aura-glow"></div>
                    <div class="logo-aura-ring"></div>
                    <div class="logo-aura-dashed"></div>
                    <div class="logo-badge-container">
                        <img src="{{ $loginLogoSrc }}" alt="MEDIX ERP" class="main-logo-img">
                    </div>
                </div>

                <div class="brand-sub">Hospital Management System</div>
                <div class="brand-name">MEDIX <span>ERP</span></div>
                <div class="brand-tagline-main">Smarter Care · Brighter Tomorrow</div>

                <!-- INTEGRATE • MANAGE • CARE -->
                <div class="brand-pills">
                    <span class="brand-pill">Integrate</span>
                    <span class="brand-dot"></span>
                    <span class="brand-pill">Manage</span>
                    <span class="brand-dot"></span>
                    <span class="brand-pill">Care</span>
                </div>

                <!-- ECG Waveform -->
                <div class="ecg-wrap">
                    <svg class="ecg-svg" viewBox="0 0 400 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path class="ecg-path"
                            d="M10 30 H75 L95 8 L115 55 L137 18 L159 44 L177 30 H235 L253 12 L269 52 L285 30 H390" />
                    </svg>
                </div>

                <!-- Hospital System Departments -->
                <div class="features-grid">
                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <span class="feature-chip-title">العيادات الاستشارية</span>
                        <span class="feature-chip-sub">Clinics</span>
                    </div>

                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-truck-medical"></i>
                        </div>
                        <span class="feature-chip-title">طوارئ وحوادث 24/7</span>
                        <span class="feature-chip-sub">Emergency</span>
                    </div>

                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-procedures"></i>
                        </div>
                        <span class="feature-chip-title">العمليات الجراحية</span>
                        <span class="feature-chip-sub">Surgeries</span>
                    </div>

                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-flask-vial"></i>
                        </div>
                        <span class="feature-chip-title">المختبر والتحاليل</span>
                        <span class="feature-chip-sub">Laboratory</span>
                    </div>

                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-x-ray"></i>
                        </div>
                        <span class="feature-chip-title">الأشعة والسونار</span>
                        <span class="feature-chip-sub">Radiology</span>
                    </div>

                    <div class="feature-chip">
                        <div class="feature-chip-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <span class="feature-chip-title">الصندوق والمالية</span>
                        <span class="feature-chip-sub">Cashier & Treasury</span>
                    </div>
                </div>

                <div class="bottom-tagline">منظومة ذكية متكاملة لإدارة المستشفيات والمراكز التخصصية</div>
            </div>
        </div>

        <!-- ══ RIGHT: Login Panel ══ -->
        <div class="right-panel">

            <!-- Dynamic Blinking White Lines & Telemetry Canvas -->
            <div class="blinking-lines-canvas">
                <div class="panel-glass-sheen"></div>
                <div class="panel-cyber-grid"></div>

                <!-- Horizontal White Streaks -->
                <div class="white-line-streak streak-h1"></div>
                <div class="white-line-streak streak-h2"></div>
                <div class="white-line-streak streak-h3"></div>

                <!-- Vertical Laser Rays -->
                <div class="white-line-v streak-v1"></div>
                <div class="white-line-v streak-v2"></div>

                <!-- Blinking Animated White ECG Waveforms -->
                <svg class="blinking-ecg-svg" viewBox="0 0 800 600" preserveAspectRatio="none" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="ecg-white-path ecg-blink-1"
                          d="M0 120 H220 L235 90 L248 150 L262 70 L278 140 L290 120 H500 L515 95 L528 145 L540 120 H800"/>
                    <path class="ecg-white-path ecg-blink-2"
                          d="M0 320 H140 L155 295 L168 355 L182 275 L198 345 L210 320 H620 L635 290 L650 350 L665 320 H800"/>
                    <path class="ecg-white-path ecg-blink-3"
                          d="M0 510 H310 L325 480 L338 540 L352 460 L368 535 L380 510 H700 L715 485 L730 535 L742 510 H800"/>
                </svg>

                <!-- Twinkling White Telemetry Sparkle Nodes -->
                <div class="white-sparkle-node node-1"></div>
                <div class="white-sparkle-node node-2"></div>
                <div class="white-sparkle-node node-3"></div>
                <div class="white-sparkle-node node-4"></div>
                <div class="white-sparkle-node node-5"></div>
            </div>

            <!-- Live pill -->
            <div class="live-pill">
                <span class="dot"></span>
                <span>النظام يعمل · Healthcare. Simplified.</span>
            </div>

            <div class="login-box">

                <div class="card-top">
                    <div class="fingerprint-wrap">
                        <div class="fingerprint-ambient-glow"></div>
                        <div class="fingerprint-scan-beam"></div>
                        <svg class="fingerprint-svg" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="fingerprintGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#ffffff"/>
                                    <stop offset="25%" stop-color="#e0f2fe"/>
                                    <stop offset="60%" stop-color="#38bdf8"/>
                                    <stop offset="100%" stop-color="#2563eb"/>
                                </linearGradient>
                            </defs>
                            <path d="M256.12 245.96c-13.25 0-24 10.74-24 24 1.14 72.25-8.14 141.9-27.7 211.55-2.73 9.72 2.15 30.49 23.12 30.49 10.48 0 20.11-6.92 23.09-17.52 13.53-47.91 31.04-125.41 29.48-224.52.01-13.25-10.73-24-23.99-24zm-.86-81.73C194 164.16 151.25 211.3 152.1 265.32c.75 47.94-3.75 95.91-13.37 142.55-2.69 12.98 5.67 25.69 18.64 28.36 13.05 2.67 25.67-5.66 28.36-18.64 10.34-50.09 15.17-101.58 14.37-153.02-.41-25.95 19.92-52.49 54.45-52.34 31.31.47 57.15 25.34 57.62 55.47.77 48.05-2.81 96.33-10.61 143.55-2.17 13.06 6.69 25.42 19.76 27.58 19.97 3.33 26.81-15.1 27.58-19.77 8.28-50.03 12.06-101.21 11.27-152.11-.88-55.8-47.94-101.88-104.91-102.72zm-110.69-19.78c-10.3-8.34-25.37-6.8-33.76 3.48-25.62 31.5-39.39 71.28-38.75 112 .59 37.58-2.47 75.27-9.11 112.05-2.34 13.05 6.31 25.53 19.36 27.89 20.11 3.5 27.07-14.81 27.89-19.36 7.19-39.84 10.5-80.66 9.86-121.33-.47-29.88 9.2-57.88 28-80.97 8.35-10.28 6.79-25.39-3.49-33.76zm109.47-62.33c-15.41-.41-30.87 1.44-45.78 4.97-12.89 3.06-20.87 15.98-17.83 28.89 3.06 12.89 16 20.83 28.89 17.83 11.05-2.61 22.47-3.77 34-3.69 75.43 1.13 137.73 61.5 138.88 134.58.59 37.88-1.28 76.11-5.58 113.63-1.5 13.17 7.95 25.08 21.11 26.58 16.72 1.95 25.51-11.88 26.58-21.11a929.06 929.06 0 0 0 5.89-119.85c-1.56-98.75-85.07-180.33-186.16-181.83zm252.07 121.45c-2.86-12.92-15.51-21.2-28.61-18.27-12.94 2.86-21.12 15.66-18.26 28.61 4.71 21.41 4.91 37.41 4.7 61.6-.11 13.27 10.55 24.09 23.8 24.2h.2c13.17 0 23.89-10.61 24-23.8.18-22.18.4-44.11-5.83-72.34zm-40.12-90.72C417.29 43.46 337.6 1.29 252.81.02 183.02-.82 118.47 24.91 71.95 71.43c-46.77 46.77-71.93 109.11-71.93 175.3 0 11.46 4.3 22.1 12.06 30.13 8.35 8.65 21.84 9.4 31.06 1.76 9.22-7.64 10.59-21.11 3.2-30.15-2.22-2.73-3.32-6.14-3.32-9.74 0-48.45 18.88-93.94 53.15-128.2 34.27-34.28 79.76-53.16 128.21-53.16 71.05 0 133.56 46.12 151.72 113.88 2.68 10.02 12.92 16.03 22.95 13.34 10.03-2.68 16.03-12.92 13.34-22.95-23.44-87.7-103.54-149.27-196.01-149.27z"
                                  fill="url(#fingerprintGrad)"/>
                        </svg>
                    </div>
                    <h1 class="card-title">تسجيل الدخول</h1>
                    <p class="card-subtitle">بوابة النفاذ السريرية والمالية الموحدة</p>
                </div>

                <form method="POST" action="{{ route('login') }}" autocomplete="on" id="login-form">
                    @csrf

                    <!-- Email -->
                    <div class="field-group">
                        <div class="field-wrap">
                            <input id="email"
                                   type="email"
                                   class="field-input @error('email') is-invalid @enderror"
                                   name="email"
                                   value="{{ old('email') }}"
                                   required
                                   autocomplete="email"
                                   autofocus
                                   placeholder="البريد الإلكتروني أو اسم المستخدم">
                            <i class="fas fa-at field-icon"></i>
                        </div>
                        @error('email')
                            <div class="error-msg">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="field-group">
                        <div class="field-wrap">
                            <input id="password"
                                   type="password"
                                   class="field-input @error('password') is-invalid @enderror"
                                   name="password"
                                   required
                                   autocomplete="current-password"
                                   placeholder="كلمة المرور">
                            <i class="fas fa-lock field-icon"></i>
                            <button type="button" class="eye-btn" onclick="togglePwd()" title="إظهار/إخفاء">
                                <i class="fas fa-eye" id="pwd-icon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="error-msg">
                                <i class="fas fa-triangle-exclamation"></i>
                                <span>{{ $message }}</span>
                            </div>
                        @enderror
                    </div>

                    <!-- Options -->
                    <div class="options-row">
                        <label class="remember-label">
                            <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                            <span class="chk"><i class="fas fa-check"></i></span>
                            <span>تذكرني</span>
                        </label>
                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">نسيت كلمة المرور؟</a>
                        @endif
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn-login" id="btn-submit">
                        <span>دخول إلى المنظومة</span>
                        <i class="fas fa-arrow-left"></i>
                    </button>
                </form>

                <a href="mailto:support@medixerp.com" class="help-link">
                    هل تواجه صعوبة في الدخول؟ تواصل مع الدعم الفني
                </a>

                <div class="right-tagline">Technology for a Healthier Tomorrow</div>
            </div>
        </div>

    </div>

    {{-- Global Page Loader --}}
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
                جاري التحقق والدخول للمنظومة<span class="loader-dots"><span>.</span><span>.</span><span>.</span></span>
            </div>
        </div>
    </div>

    <style>
        #global-page-loader {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(240,247,255,0.95);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
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
        #global-page-loader.active { opacity: 1; pointer-events: auto; visibility: visible; }
        .loader-top-bar {
            position: absolute; top: 0; left: 0;
            height: 3px; width: 0%;
            background: linear-gradient(90deg, #1d4ed8, #3b82f6, #60a5fa);
            box-shadow: 0 0 12px rgba(29,78,216,0.6);
            transition: width 0.35s ease;
        }
        .loader-logo-wrapper {
            position: relative;
            width: 140px; height: 140px;
            display: flex; align-items: center; justify-content: center;
        }
        .loader-glow-aura {
            position: absolute; width: 110px; height: 110px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(29,78,216,0.20) 0%, transparent 70%);
            animation: auraPulse 1.8s ease-in-out infinite alternate;
        }
        .loader-spinner-ring {
            position: absolute; width: 100%; height: 100%;
            border-radius: 50%;
            border: 2.5px solid transparent;
            border-top-color: #1d4ed8;
            border-right-color: #3b82f6;
            animation: loaderSpin 0.9s cubic-bezier(0.68,-0.55,0.265,1.55) infinite;
        }
        .loader-spinner-ring-outer {
            position: absolute; width: 125%; height: 125%;
            border-radius: 50%;
            border: 1px dashed rgba(29,78,216,0.18);
            animation: loaderSpinReverse 4s linear infinite;
        }
        .loader-logo-img {
            width: 85px; height: 85px;
            object-fit: contain; border-radius: 50%;
            background: #ffffff;
            border: 1px solid rgba(147,197,253,0.3);
            padding: 10px; z-index: 2;
            animation: logoPulse 1.4s ease-in-out infinite alternate;
        }
        .loader-text-wrapper { margin-top: 28px; text-align: center; }
        .loader-text {
            color: #3b5fa0; font-size: 0.95rem; font-weight: 700;
            display: inline-flex; align-items: center; gap: 2px;
            font-family: 'Cairo', sans-serif;
        }
        .loader-dots span { animation: dotBlink 1.4s infinite; opacity: 0; font-weight: bold; }
        .loader-dots span:nth-child(1) { animation-delay: 0.2s; }
        .loader-dots span:nth-child(2) { animation-delay: 0.4s; }
        .loader-dots span:nth-child(3) { animation-delay: 0.6s; }
        @keyframes loaderSpin       { to { transform: rotate(360deg); } }
        @keyframes loaderSpinReverse{ to { transform: rotate(-360deg); } }
        @keyframes logoPulse { 0%{ transform:scale(0.92); } 100%{ transform:scale(1.06); } }
        @keyframes auraPulse { 0%{ transform:scale(0.8); opacity:0.3; } 100%{ transform:scale(1.6); opacity:0.8; } }
        @keyframes dotBlink  { 0%,100%{ opacity:0; } 50%{ opacity:1; } }
    </style>

    <script>
        function togglePwd() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('pwd-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const loader = document.getElementById('global-page-loader');
            const topBar = document.getElementById('loader-top-bar');
            const form   = document.getElementById('login-form');
            if (form) {
                form.addEventListener('submit', function () {
                    if (loader) {
                        loader.classList.add('active');
                        if (topBar) {
                            let w = 0;
                            const interval = setInterval(function () {
                                if (w < 88) { w += 6; topBar.style.width = w + '%'; }
                                else { clearInterval(interval); }
                            }, 80);
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>