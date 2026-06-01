<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص تفاصيل طلب إيكو:\n";
echo "==========================================\n\n";

$request = DB::table('requests')->where('id', 11)->first();

if ($request) {
    echo "طلب #{$request->id}:\n";
    echo "  type: {$request->type}\n";
    echo "  subtype: {$request->subtype}\n";
    echo "  status: {$request->status}\n";
    echo "  details (raw): {$request->details}\n\n";
    
    $details = json_decode($request->details, true);
    echo "  details (parsed):\n";
    print_r($details);
    
    if ($request->visit_id) {
        echo "\n\nمعلومات الزيارة:\n";
        $visit = DB::table('visits')->where('id', $request->visit_id)->first();
        if ($visit) {
            echo "  visit_id: {$visit->id}\n";
            echo "  doctor_id: {$visit->doctor_id}\n";
            
            if ($visit->doctor_id) {
                $doctor = DB::table('users')->where('id', $visit->doctor_id)->first();
                if ($doctor) {
                    echo "  الطبيب المخصص: {$doctor->name}\n";
                }
            }
        }
    }
} else {
    echo "الطلب غير موجود\n";
}
