@echo off
chcp 65001 >nul
title 🖨️ Hospital Auto Scanner - نظام الأرشفة التلقائي

echo.
echo ╔═══════════════════════════════════════════════════════════════════╗
echo ║     🖨️ نظام الأرشفة التلقائي - Hospital Auto Scanner           ║
echo ║        يعمل مع أي سكانر متصل بـ Windows تلقائياً               ║
echo ╚═══════════════════════════════════════════════════════════════════╝
echo.

:: التحقق من Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Python غير مثبت!
    echo.
    echo    يرجى تثبيت Python من:
    echo    https://www.python.org/downloads/
    echo.
    echo    ⚠️ تأكد من تفعيل خيار "Add Python to PATH" أثناء التثبيت
    echo.
    pause
    exit /b 1
)

echo ✓ Python موجود
echo.

:: التحقق من المكتبات وتثبيتها إذا لزم الأمر
echo 📦 جاري التحقق من المكتبات المطلوبة...
echo.

pip show flask >nul 2>&1
if errorlevel 1 (
    echo    ⏳ جاري تثبيت Flask...
    pip install flask flask-cors --quiet
)

pip show pywin32 >nul 2>&1
if errorlevel 1 (
    echo    ⏳ جاري تثبيت PyWin32...
    pip install pywin32 --quiet
)

pip show pillow >nul 2>&1
if errorlevel 1 (
    echo    ⏳ جاري تثبيت Pillow...
    pip install pillow --quiet
)

echo ✓ جميع المكتبات جاهزة
echo.
echo ═══════════════════════════════════════════════════════════════════
echo.

:: تشغيل البرنامج
python "%~dp0scanner_bridge_auto.py"

if errorlevel 1 (
    echo.
    echo ❌ حدث خطأ أثناء التشغيل
    echo.
    pause
)