<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== بنية جدول notifications ===\n\n";

$result = DB::select('SHOW CREATE TABLE notifications');
echo $result[0]->{'Create Table'};
echo "\n\n";
