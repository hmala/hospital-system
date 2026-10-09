<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\ConsultantAvailabilityController;

$request = Request::create('/consultant-availability', 'GET');
$controller = new ConsultantAvailabilityController();
$view = $controller->index($request);

echo "Success! View name: " . $view->name() . "\n";
