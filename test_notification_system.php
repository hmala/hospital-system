<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== اختبار نظام الإشعارات ===\n\n";

// الحصول على مستخدم اختباري
$user = \App\Models\User::first();
if (!$user) {
    echo "لا يوجد مستخدمين في النظام!\n";
    exit;
}

echo "1. اختبار createForUser\n";
echo "المستخدم: {$user->name}\n";

\App\Models\Notification::createForUser(
    $user->id,
    'test_notification',
    '🔔 إشعار اختباري',
    'هذا إشعار تجريبي لاختبار النظام',
    ['test_data' => 'value', 'timestamp' => now()]
);

echo "✓ تم إنشاء الإشعار بنجاح\n\n";

// اختبار createForRole
echo "2. اختبار createForRole\n";
\App\Models\Notification::createForRole(
    ['receptionist'],
    'role_notification',
    '👥 إشعار للموظفين',
    'هذا إشعار لجميع موظفي الاستقبال',
    ['role_test' => true]
);

echo "✓ تم إنشاء إشعارات للدور بنجاح\n\n";

// عد الإشعارات غير المقروءة
echo "3. عدد الإشعارات غير المقروءة للمستخدم\n";
$count = \App\Models\Notification::unreadCountForUser($user->id);
echo "العدد: {$count}\n\n";

// جلب الإشعارات غير المقروءة
echo "4. جلب الإشعارات غير المقروءة\n";
$unread = \App\Models\Notification::unreadForUser($user->id);
echo "عدد الإشعارات: {$unread->count()}\n";

if ($unread->count() > 0) {
    echo "\nالإشعارات:\n";
    foreach ($unread as $notification) {
        $data = is_string($notification->data) ? json_decode($notification->data, true) : $notification->data;
        echo "- {$data['title']}: {$data['message']}\n";
    }
}

echo "\n=== انتهى الاختبار ===\n";
