<?php
require __DIR__.'/vendor/autoload.php';
$app=require __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$perms=\Spatie\Permission\Models\Permission::where('name','like','%lab%')->pluck('name');
echo "lab permissions: ". implode(', ',$perms->toArray());
