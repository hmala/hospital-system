<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "فحص details بالتفصيل:\n";
echo "==========================================\n\n";

$request = DB::table('requests')->where('id', 11)->first();

echo "details (كامل):\n";
var_dump($request->details);
echo "\n\n";

echo "طول النص: " . strlen($request->details) . "\n";
echo "أول 10 أحرف (hex): " . bin2hex(substr($request->details, 0, 10)) . "\n\n";

$decoded = json_decode($request->details);
echo "json_decode (بدون true):\n";
var_dump($decoded);
echo "\n\n";

$decoded_assoc = json_decode($request->details, true);
echo "json_decode (مع true):\n";
var_dump($decoded_assoc);
echo "\n\n";

echo "json_last_error: " . json_last_error() . "\n";
echo "json_last_error_msg: " . json_last_error_msg() . "\n";
