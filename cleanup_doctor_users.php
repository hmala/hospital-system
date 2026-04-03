<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "🔍 البحث عن مستخدمين بدور طبيب بدون سجل طبيب...\n\n";

$doctorUsers = User::where('role', 'doctor')->get();

foreach ($doctorUsers as $user) {
    echo "🗑️  حذف: {$user->name} ({$user->email})\n";
    $user->delete();
}

echo "\n✅ تم التنظيف الكامل!\n";
echo "📊 المستخدمين بدور طبيب: " . User::where('role', 'doctor')->count() . "\n";
