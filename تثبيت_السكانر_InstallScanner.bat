@echo off
setlocal
title Hospital Scanner Setup

echo ========================================================
echo       Hospital Scanner - Auto Setup (One-Time)
echo ========================================================
echo.

set "TARGET_DIR=%LOCALAPPDATA%\HospitalScanner"
if not exist "%TARGET_DIR%" mkdir "%TARGET_DIR%"

echo [1/4] Copying Hospital Scanner files...
if exist "%~dp0HospitalScanner.exe" (
    copy /y "%~dp0HospitalScanner.exe" "%TARGET_DIR%\HospitalScanner.exe" >nul
) else if exist "C:\wamp64\www\hospital-system\HospitalScanner.exe" (
    copy /y "C:\wamp64\www\hospital-system\HospitalScanner.exe" "%TARGET_DIR%\HospitalScanner.exe" >nul
)

echo [2/4] Setting up auto-start with Windows (Startup)...
powershell -NoProfile -WindowStyle Hidden -Command "$ws = New-Object -ComObject WScript.Shell; $s = $ws.CreateShortcut([IO.Path]::Combine($env:APPDATA, 'Microsoft\Windows\Start Menu\Programs\Startup\HospitalScanner.lnk')); $s.TargetPath = [IO.Path]::Combine($env:LOCALAPPDATA, 'HospitalScanner\HospitalScanner.exe'); $s.WorkingDirectory = [IO.Path]::Combine($env:LOCALAPPDATA, 'HospitalScanner'); $s.Save()"

echo [3/4] Enabling Chrome and Edge browser policies for Hospital...
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 1 /t REG_SZ /d "https://hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 2 /t REG_SZ /d "https://[*.]hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Google\Chrome\InsecureContentAllowedForUrls" /v 3 /t REG_SZ /d "http://localhost" /f >nul 2>&1
reg add "HKCU\Software\Policies\Microsoft\Edge\InsecureContentAllowedForUrls" /v 1 /t REG_SZ /d "https://hinpa.icu" /f >nul 2>&1
reg add "HKCU\Software\Policies\Microsoft\Edge\InsecureContentAllowedForUrls" /v 2 /t REG_SZ /d "https://[*.]hinpa.icu" /f >nul 2>&1

echo [4/4] Starting Hospital Scanner in background...
taskkill /f /im HospitalScanner.exe >nul 2>&1
start "" /min "%TARGET_DIR%\HospitalScanner.exe"

echo.
echo ========================================================
echo   SUCCESS! Hospital Scanner is now installed and active!
echo   - It will start automatically with Windows every day.
echo   - Browser permissions are set permanently.
echo   - Scanner service is now running in the background.
echo ========================================================
echo.
pause
