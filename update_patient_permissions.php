<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

// حذف الصلاحيات القديمة للمريض
$patientRoleId = DB::table('roles')->where('name', 'patient')->value('id');
DB::table('role_has_permissions')->where('role_id', $patientRoleId)->delete();

// إضافة الصلاحيات الجديدة
$role = Role::where('name', 'patient')->first();
$role->givePermissionTo([
    'view own visits',
    'view appointments',
    'create appointments',
    'cancel appointments',
    'view departments',
    'view doctors'
]);

echo "✓ تم تحديث صلاحيات المريض بنجاح!\n";
