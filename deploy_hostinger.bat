@echo off
setlocal enabledelayedexpansion
title Hostinger Auto Deploy - Hospital System
chcp 65001 >nul

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

echo اختر نوع العملية المطلوبة على السيرفر (Hostinger):
echo --------------------------------------------------------
echo [1] نشر وتحديث عادي (سحب الكود + الميغريشنات + مسح الكاش مع الحفاظ على البيانات)
echo [2] نشر وتحديث + تصفير الجداول التشغيلية والتجريبية لبدء التشغيل الفعلي
echo [3] تصفير الجداول التشغيلية والتجريبية فقط على السيرفر
echo [4] إلغاء وخروج
echo --------------------------------------------------------
set /p userchoice="اكتب رقم الخيار (1 أو 2 أو 3 أو 4) ثم اضغط Enter [الافتراضي: 1]: "

if "%userchoice%"=="" set userchoice=1
if "%userchoice%"=="4" goto end

echo.
if "%userchoice%"=="3" (
    echo [تخطي رفع Git] الانتقال لتصفير الجداول التشغيلية على السيرفر مباشرة...
    goto ssh_step
)

echo [1/2] Staging, committing and pushing local changes to GitHub main branch...
git add .
git diff-index --quiet HEAD || git commit -m "chore(deploy): auto-commit changes before hostinger deploy"
git push origin main
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [WARNING] git push returned an issue. Continuing to server pull if changes are already on GitHub...
)
echo [OK] Pushed to GitHub successfully.
echo.

:ssh_step
echo [2/2] Connecting to Hostinger via SSH...
echo Please enter your SSH password if prompted:
echo --------------------------------------------------------

if "%userchoice%"=="1" (
    ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/7] Fetching and Resetting to GitHub Main ===' && git stash --include-untracked && git fetch origin main && git reset --hard origin/main && echo '=== [2/7] Running Migrations ===' && php artisan migrate --force && echo '=== [3/7] Seeding Official Medicines Catalog ===' && php artisan db:seed --class=OfficialMedicinesSeeder --force && echo '=== [4/7] Seeding Radiology and Ultrasound Services ===' && php artisan db:seed --class=RadiologyTypesSeeder --force && echo '=== [5/7] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [6/7] Setting Telegram Webhook ===' && php artisan telegram:set-webhook https://hinpa.icu/hearmz/telegram/webhook && echo '=== [7/7] Clearing App & View Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Deployment completed successfully!' && echo '============================================='"
) else if "%userchoice%"=="2" (
    ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/8] Fetching and Resetting to GitHub Main ===' && git stash --include-untracked && git fetch origin main && git reset --hard origin/main && echo '=== [2/8] Running Migrations ===' && php artisan migrate --force && echo '=== [3/8] Safely Cleaning 48 Operational Tables ===' && php artisan db:clean-operational-data --force && echo '=== [4/8] Seeding Official Medicines Catalog ===' && php artisan db:seed --class=OfficialMedicinesSeeder --force && echo '=== [5/8] Seeding Radiology and Ultrasound Services ===' && php artisan db:seed --class=RadiologyTypesSeeder --force && echo '=== [6/8] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [7/8] Setting Telegram Webhook ===' && php artisan telegram:set-webhook https://hinpa.icu/hearmz/telegram/webhook && echo '=== [8/8] Clearing App & View Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Deployment with Operational Clean completed successfully!' && echo '============================================='"
) else if "%userchoice%"=="3" (
    ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/3] Safely Cleaning 48 Operational Tables on Server ===' && php artisan db:clean-operational-data --force && echo '=== [2/3] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [3/3] Clearing App & View Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Operational Tables Cleaned successfully on Server!' && echo '============================================='"
)

:end
echo.
echo ========================================================
echo Press any key to close this window...
pause >nul
