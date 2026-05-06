<?php

session_start();

echo "=== فحص الـ Session الحالية ===" . PHP_EOL . PHP_EOL;

if (isset($_SESSION)) {
    echo "محتوى Session:" . PHP_EOL;
    print_r($_SESSION);
} else {
    echo "لا توجد Session نشطة" . PHP_EOL;
}

echo PHP_EOL . "الكوكيز الحالية:" . PHP_EOL;
print_r($_COOKIE);
