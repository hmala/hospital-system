<?php
// one-off script to update old inquiry visits based on completed requests

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Visit;
use App\Models\Request;

$visits = Visit::where('department_id', function($q) {
    $q->select('id')->from('departments')
      ->where('name', 'LIKE', '%استعلامات%')
      ->orWhere('name', 'LIKE', '%استقبال%')
      ->limit(1);
})->get();

$updated = 0;
foreach ($visits as $visit) {
    $total = $visit->requests()->count();
    if ($total === 0) continue;
    $completed = $visit->requests()->where('status','completed')->count();
    if ($total === $completed && $visit->status !== 'completed') {
        $visit->status = 'completed';
        $visit->save();
        $updated++;
    }
}

echo "Updated {$updated} visit(s) to completed.\n";
