@echo off
chcp 65001 >nul
title تثبيت برنامج سكانر المستشفى - Hospital Scanner Setup
color 0A

echo ========================================================
echo       تثبيت برنامج سكانر المستشفى التلقائي
echo ========================================================
echo.
echo [1/4] جاري إنشاء مجلد البرنامج الدائم...
set "TARGET_DIR=%LOCALAPPDATA%\HospitalScanner"
if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%"

echo [2/4] جاري نسخ ملفات البرنامج...
if exist "%~dp0HospitalScanner.exe" (
    copy /y "%~dp0HospitalScanner.exe" "%TARGET_DIR%\HospitalScanner.exe" >nul
) else if exist "C:\wamp64\www\hospital-system\HospitalScanner.exe" (
    copy /y "C:\wamp64\www\hospital-system\HospitalScanner.exe" "%TARGET_DIR%\HospitalScanner.exe" >nul
)

echo [3/4] جاري التثبيت في بدء تشغيل الويندوز (Startup)...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut(\"$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Startup\HospitalScanner.lnk\"); $s.TargetPath = \"$env:LOCALAPPDATA\HospitalScanner\HospitalScanner.exe\"; $s.WorkingDirectory = \"$env:LOCALAPPDATA\HospitalScanner\"; $s.WindowStyle = 7; $s.Description = \"Hospital Scanner Bridge\"; $s.Save()"

echo [4/4] جاري ضبط صلاحيات كروم و Edge تلقائيا لموقع المستشفى...
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 1 /t REG_SZ /d "https://hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 2 /t REG_SZ /d "https://[*.]hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 3 /t REG_SZ /d "http://localhost" /f >nul 2>&1
reg add "HKCU\Software\Policies\Microsoft\Edge\InsecureContentAllowedForUrls" /v 1 /t REG_SZ /d "https://hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Microsoft\Edge\InsecureContentAllowedForUrls" /v 2 /t REG_SZ /d "https://[*.]hinpa.icu" /f >nul 2>&1

echo.
echo [5/5] جاري تشغيل برنامج السكانر الآن في الخلفية...
taskkill /f /im HospitalScanner.exe >nul 2>&1
start "" /min "%TARGET_DIR%\HospitalScanner.exe"

echo.
echo ========================================================
echo   ✓ تم التثبيت والتفعيل بنجاح تام!
echo   - البرنامج سيعمل تلقائيا مع تشغيل الحاسبة يوميا.
echo   - تم ضبط متصفح كروم و Edge للسماح بالسكانر للأبد.
echo   - البرنامج يعمل الآن في الخلفية وجاهز للمسح.
echo ========================================================
echo.
echo اضغط أي مفتاح للإغلاق...
pause >nul
