<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$requests = \App\Models\Request::where('type', 'radiology')->whereNull('subtype')->get();
echo "Requests without subtype: " . $requests->count() . PHP_EOL;

foreach ($requests as $req) {
    $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
    if (isset($details['radiology_type_ids']) && is_array($details['radiology_type_ids'])) {
        $types = \App\Models\RadiologyType::whereIn('id', $details['radiology_type_ids'])->get();
        $subcats = $types->pluck('subcategory')->unique();
        
        if ($subcats->count() === 1) {
            $subcat = $subcats->first();
            if ($subcat === 'سونار') {
                $req->subtype = 'ultrasound';
            } elseif ($subcat === 'الرنين') {
                $req->subtype = 'echo';
            } else {
                $req->subtype = 'general';
            }
        } else {
            $req->subtype = 'general';
        }
        
        $req->save();
        echo "Updated request #{$req->id} to subtype: {$req->subtype}" . PHP_EOL;
    } else {
        // لا يوجد أنواع محددة، نجعلها general
        $req->subtype = 'general';
        $req->save();
        echo "Updated request #{$req->id} to general (no types selected)" . PHP_EOL;
    }
}

echo PHP_EOL . "Done!" . PHP_EOL;
