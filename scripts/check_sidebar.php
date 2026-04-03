<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\SidebarLink;

$user = User::where('role', 'doctor')->first();
if (!$user) {
    echo "NO_DOCTOR_USER\n";
    exit(0);
}

echo "USER_ID:" . $user->id . "\n";
echo "NAME:" . $user->name . "\n";
echo "ROLE_FIELD:" . ($user->role ?? 'NULL') . "\n";
echo "HAS_SPATIE_ROLE_doctor:" . ($user->hasRole('doctor') ? 'yes' : 'no') . "\n";
echo "ROLES_LIST:" . json_encode($user->getRoleNames()->toArray()) . "\n";

$links = SidebarLink::orderBy('order')->get();
foreach ($links as $l) {
    echo "LINK_ID:" . $l->id . "|TITLE:" . $l->title . "|ROLES:" . json_encode($l->roles) . "|PERM:" . ($l->permission ?? '') . "|ENABLED:" . ($l->enabled ? '1' : '0') . "\n";
}
