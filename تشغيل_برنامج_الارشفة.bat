@echo off
chcp 65001 >nul
title 🖨️ برنامج الأرشفة - Hospital Scanner Bridge
color 0A

echo.
echo ════════════════════════════════════════════════════════
echo    🖨️  جاري تشغيل برنامج الأرشفة التلقائي
echo ════════════════════════════════════════════════════════
echo.

:: التحقق من Python
echo [1/4] التحقق من Python...
where python >nul 2>nul
if %errorlevel% neq 0 (
    color 0C
    echo.
    echo ❌ خطأ: Python غير مثبت!
    echo.
    echo يرجى تثبيت Python من:
    echo https://www.python.org/downloads/
    echo.
    echo ⚠️  تأكد من اختيار "Add Python to PATH" عند التثبيت
    echo.
    pause
    exit /b 1
)
echo ✓ Python موجود
echo.

:: التحقق من NAPS2
echo [2/4] التحقق من NAPS2...
if not exist "C:\Program Files\NAPS2\naps2.console.exe" (
    color 0E
    echo.
    echo ⚠️  تحذير: NAPS2 غير مثبت!
    echo.
    echo يرجى تثبيت NAPS2 من:
    echo https://github.com/cyanfish/naps2/releases/latest
    echo.
    echo 💡 يمكنك المتابعة، لكن المسح لن يعمل بدون NAPS2
    echo.
    timeout /t 5
)
echo ✓ NAPS2 موجود
echo.

:: تثبيت المكتبات المطلوبة
echo [3/4] تثبيت المكتبات المطلوبة...
python -m pip install --upgrade pip --quiet 2>nul
python -m pip install flask flask-cors --quiet 2>nul

if %errorlevel% neq 0 (
    color 0C
    echo.
    echo ❌ فشل تثبيت المكتبات!
    echo.
    echo حاول تشغيل الأمر يدوياً:
    echo python -m pip install flask flask-cors
    echo.
    pause
    exit /b 1
)
echo ✓ تم تثبيت المكتبات بنجاح
echo.

:: تشغيل البرنامج
echo [4/4] تشغيل خادم الأرشفة...
echo.
echo ════════════════════════════════════════════════════════
echo.
python scanner_bridge.py

:: في حالة توقف البرنامج
color 0C
echo.
echo ════════════════════════════════════════════════════════
echo   ⚠️  البرنامج توقف!
echo ════════════════════════════════════════════════════════
echo.
pause
