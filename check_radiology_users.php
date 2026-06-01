<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "فحص مستخدمي الأشعة وأدوارهم:" . PHP_EOL . PHP_EOL;

$radiologyUsers = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'like', '%radiology%');
})->with('roles')->get();

if ($radiologyUsers->count() === 0) {
    echo "❌ لا يوجد مستخدمين بأدوار أشعة!" . PHP_EOL;
} else {
    echo "عدد المستخدمين: " . $radiologyUsers->count() . PHP_EOL . PHP_EOL;
    
    foreach ($radiologyUsers as $user) {
        echo "👤 {$user->name} (ID: {$user->id})" . PHP_EOL;
        echo "   الأدوار: " . $user->roles->pluck('name')->implode(', ') . PHP_EOL;
        echo PHP_EOL;
    }
}

echo PHP_EOL . "الطلبات حسب subtype:" . PHP_EOL;
$requests = \App\Models\Request::where('type', 'radiology')->get();
$grouped = $requests->groupBy('subtype');

foreach ($grouped as $subtype => $reqs) {
    $label = $subtype ?? 'NULL';
    echo "  {$label}: {$reqs->count()} طلب" . PHP_EOL;
    
    foreach ($reqs as $req) {
        $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
        $echoStaff = isset($details['echo_staff_id']) ? "echo_staff={$details['echo_staff_id']}" : '';
        echo "    - طلب #{$req->id} | status: {$req->status} | {$echoStaff}" . PHP_EOL;
    }
}
