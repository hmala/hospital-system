@echo off
chcp 65001 >nul
title 🚀 Hostinger Auto Deploy - Hospital System

echo ========================================================
echo     نظام المستشفى الأهلي - التحديث التلقائي إلى Hostinger
echo ========================================================
echo.

cd /d "%~dp0"

echo [1/2] جاري رفع التعديلات المحلية إلى GitHub (main)...
git push origin main
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ❌ حدث خطأ أثناء الرفع إلى GitHub. يرجى التحقق من الاتصال.
    goto end
)
echo ✅ تم الرفع إلى GitHub بنجاح!
echo.

echo [2/2] جاري الاتصال بسيرفر هوستنجر وسحب التحديثات...
echo (يرجى إدخال كلمة سر SSH إذا طُلبت منك)
echo --------------------------------------------------------

ssh -p 65002 -t u175868711@92.113.18.46 "cd domains/hinpa.icu/public_html/hearmz && echo '=== [1/3] سحب التحديثات من GitHub ===' && git pull origin main && echo '=== [2/3] تحديث قاعدة البيانات (Migrations) ===' && php artisan migrate --force && echo '=== [3/3] تفريغ وتحديث الكاش وتحسين الأداء ===' && php artisan optimize:clear && echo '=============================================' && echo '✅ تم التحديث والنشر على هوستنجر بنجاح!' && echo '============================================='"

:end
echo.
echo ========================================================
echo اضغط أي مفتاح لإغلاق هذه النافذة...
pause >nul
