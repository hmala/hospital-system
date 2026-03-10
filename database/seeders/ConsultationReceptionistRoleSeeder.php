<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ConsultationReceptionistRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // إعادة تعيين الصلاحيات المخزنة مؤقتاً
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الصلاحية الجديدة
        $permission = Permission::firstOrCreate(['name' => 'manage consultant availability']);

        // إنشاء الدور الجديد
        $role = Role::firstOrCreate(['name' => 'consultation_receptionist']);

        // تعيين الصلاحية للدور
        $role->givePermissionTo($permission);

        $this->command->info('تم إنشاء الدور والصلاحية:');
        $this->command->info('الدور: consultation_receptionist');
        $this->command->info('الصلاحية: manage consultant availability');
    }
}
