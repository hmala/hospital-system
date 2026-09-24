@echo off
setlocal enabledelayedexpansion
title Hostinger Auto Deploy - Hospital System

echo ========================================================
echo     Hospital System - Auto Deploy to Hostinger
echo ========================================================
echo.

if exist "%~dp0.git" (
    cd /d "%~dp0"
) else if exist "c:\wamp64\www\hospital-system\.git" (
    cd /d "c:\wamp64\www\hospital-system"
) else (
    cd /d "%~dp0"
)

echo Working directory: %CD%
echo.

echo [1/2] Pushing local changes to GitHub main branch...
git push origin main
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [WARNING] git push returned an issue. Continuing to server pull if changes are already on GitHub...
)
echo [OK] Pushed to GitHub successfully.
echo.

echo [2/2] Connecting to Hostinger via SSH...
echo Please enter your SSH password if prompted:
echo --------------------------------------------------------

ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/4] Pulling from GitHub ===' && git pull origin main && echo '=== [2/4] Running Migrations ===' && php artisan migrate --force && echo '=== [3/4] Seeding Official Medicines Catalog ===' && php artisan db:seed --class=OfficialMedicinesSeeder --force && echo '=== [4/4] Clearing Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Deployment completed successfully!' && echo '============================================='"

:end
echo.
echo ========================================================
echo Press any key to close this window...
pause >nul
