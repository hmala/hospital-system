@echo off
setlocal
title Hospital System - Hostinger Deploy

echo ========================================================
echo        Hospital System - Hostinger Auto Deploy
echo ========================================================
echo.

cd /d "c:\wamp64\www\hospital-system"
echo Working directory: %CD%
echo.

echo Select Deployment Mode:
echo --------------------------------------------------------
echo  [1] Normal Deploy (Pull Code + Migrations + Cache Clear)
echo  [2] Deploy + CLEAN 48 Operational Tables on Server
echo  [3] Clean Operational Tables ONLY on Server
echo  [4] Exit
echo --------------------------------------------------------
echo.

set /p userchoice="Enter choice [1, 2, 3, 4] (Default is 1): "

if "%userchoice%"=="" set userchoice=1
if "%userchoice%"=="4" goto end
if "%userchoice%"=="3" goto clean_only
if "%userchoice%"=="2" goto deploy_with_clean

:normal_deploy
echo.
echo [1/2] Staging and pushing local changes to GitHub main...
git add .
git diff-index --quiet HEAD || git commit -m "chore(deploy): auto-commit before hostinger deploy"
git push origin main
echo.
echo [2/2] Connecting to Hostinger via SSH (Normal Deploy)...
echo Please enter your SSH password if prompted:
echo --------------------------------------------------------
ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/7] Resetting to GitHub Main ===' && git stash --include-untracked && git fetch origin main && git reset --hard origin/main && echo '=== [2/7] Running Migrations ===' && php artisan migrate --force && echo '=== [3/7] Seeding Official Medicines ===' && php artisan db:seed --class=OfficialMedicinesSeeder --force && echo '=== [4/7] Seeding Radiology Types ===' && php artisan db:seed --class=RadiologyTypesSeeder --force && echo '=== [5/7] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [6/7] Setting Telegram Webhook ===' && php artisan telegram:set-webhook https://hinpa.icu/hearmz/telegram/webhook && echo '=== [7/7] Clearing App Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Deployment completed successfully!' && echo '============================================='"
goto end

:deploy_with_clean
echo.
echo [1/2] Staging and pushing local changes to GitHub main...
git add .
git diff-index --quiet HEAD || git commit -m "chore(deploy): auto-commit before hostinger deploy with clean"
git push origin main
echo.
echo [2/2] Connecting to Hostinger via SSH (Deploy + Clean Operational Tables)...
echo Please enter your SSH password if prompted:
echo --------------------------------------------------------
ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/8] Resetting to GitHub Main ===' && git stash --include-untracked && git fetch origin main && git reset --hard origin/main && echo '=== [2/8] Running Migrations ===' && php artisan migrate --force && echo '=== [3/8] Safely Cleaning 48 Operational Tables ===' && php artisan db:clean-operational-data --force && echo '=== [4/8] Seeding Official Medicines ===' && php artisan db:seed --class=OfficialMedicinesSeeder --force && echo '=== [5/8] Seeding Radiology Types ===' && php artisan db:seed --class=RadiologyTypesSeeder --force && echo '=== [6/8] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [7/8] Setting Telegram Webhook ===' && php artisan telegram:set-webhook https://hinpa.icu/hearmz/telegram/webhook && echo '=== [8/8] Clearing App Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Deployment with Operational Clean completed successfully!' && echo '============================================='"
goto end

:clean_only
echo.
echo Connecting to Hostinger via SSH (Clean Operational Tables Only)...
echo Please enter your SSH password if prompted:
echo --------------------------------------------------------
ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/3] Safely Cleaning 48 Operational Tables ===' && php artisan db:clean-operational-data --force && echo '=== [2/3] Resetting Permissions Cache ===' && php artisan permission:cache-reset && echo '=== [3/3] Clearing App Cache ===' && php artisan optimize:clear && echo '=============================================' && echo 'Operational Clean completed successfully!' && echo '============================================='"
goto end

:end
echo.
echo ========================================================
echo Process finished.
echo ========================================================
pause
