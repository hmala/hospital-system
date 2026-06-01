<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "فحص تفاصيل طلبات الإيكو:" . PHP_EOL . PHP_EOL;

$echoRequests = \App\Models\Request::where('type', 'radiology')
    ->where('subtype', 'echo')
    ->get();

if ($echoRequests->count() === 0) {
    echo "❌ لا توجد طلبات إيكو!" . PHP_EOL;
} else {
    echo "عدد طلبات الإيكو: " . $echoRequests->count() . PHP_EOL . PHP_EOL;
    
    foreach ($echoRequests as $req) {
        echo "طلب #{$req->id}:" . PHP_EOL;
        echo "  subtype: {$req->subtype}" . PHP_EOL;
        echo "  status: {$req->status}" . PHP_EOL;
        
        $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
        
        if (is_array($details)) {
            echo "  details (array):" . PHP_EOL;
            if (isset($details['echo_staff_id'])) {
                echo "    echo_staff_id: {$details['echo_staff_id']}" . PHP_EOL;
            } else {
                echo "    ❌ echo_staff_id غير موجود!" . PHP_EOL;
            }
        } else {
            echo "  details (raw): " . $req->details . PHP_EOL;
        }
        
        // اختبار JSON_EXTRACT
        $result = \DB::select("SELECT JSON_EXTRACT(details, '$.echo_staff_id') as echo_staff FROM requests WHERE id = ?", [$req->id]);
        if ($result) {
            echo "  JSON_EXTRACT result: " . ($result[0]->echo_staff ?? 'NULL') . PHP_EOL;
        }
        
        echo PHP_EOL;
    }
}
