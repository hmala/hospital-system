<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/bootstrap/app.php';
$kernel = app()->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$id = $argv[1] ?? null;
if (!$id) {
    echo "Available EmergencyRadiologyRequest IDs:\n";
    foreach (\App\Models\EmergencyRadiologyRequest::pluck('id') as $i) {
        echo " - $i\n";
    }
    echo "\nUsage: php tmp_debug_eradh.php <id>\n";
    exit(1);
}

$e = \App\Models\EmergencyRadiologyRequest::with('radiologyTypes')->find($id);
if (!$e) {
    echo "Request $id not found\n";
    exit(1);
}

$attachments = $e->radiologyTypes->map(function($type) {
    $rawPath = $type->pivot->image_path ?? null;
    if (empty($rawPath)) {
        return null;
    }
    $normalized = str_replace('\\', '/', trim($rawPath));
    $pathPart = ltrim($normalized, '/');
    $encoded = implode('/', array_map('rawurlencode', explode('/', $pathPart)));
    $url = asset('storage/' . $encoded);
    $ext = strtolower(pathinfo(parse_url($normalized, PHP_URL_PATH) ?: $normalized, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp','bmp']);
    return [
        'name' => $type->name,
        'path' => $normalized,
        'url' => $url,
        'is_image' => $isImage,
    ];
})->filter()->values();

print_r($attachments->toArray());

echo "\nFile exists?\n";
foreach ($attachments as $att) {
    $local = __DIR__.'/storage/app/public/'.ltrim($att['path'], '/');
    echo $att['path'] . ' -> ' . (file_exists($local) ? 'yes' : 'no') . " (" . $local . ")\n";
}
