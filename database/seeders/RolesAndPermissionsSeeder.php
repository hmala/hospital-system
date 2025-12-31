<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // إعادة تعيين الصلاحيات المخزنة مؤقتاً
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // إنشاء الصلاحيات
        $permissions = [
            // صلاحيات المرضى
            'view patients',
            'create patients',
            'edit patients',
            'delete patients',
            
            // صلاحيات الأطباء
            'view doctors',
            'create doctors',
            'edit doctors',
            'delete doctors',
            'manage own visits',
            
            // صلاحيات العيادات
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',
            
            // صلاحيات المواعيد
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'cancel appointments',
            
            // صلاحيات الزيارات
            'view visits',
            'create visits',
            'edit visits',
            'delete visits',
            'view own visits',
            
            // صلاحيات العمليات
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'delete surgeries',
            'manage surgery waiting list',
            'control surgeries',
            
            // صلاحيات الإشعة
            'view radiology',
            'create radiology',
            'edit radiology',
            'delete radiology',
            'manage radiology types',
            'process radiology requests',
            
            // صلاحيات المختبر
            'view lab tests',
            'create lab tests',
            'edit lab tests',
            'delete lab tests',
            'process lab requests',
            'manage surgery lab tests',
            
            // صلاحيات الصيدلية
            'view pharmacy',
            'process pharmacy requests',
            
            // صلاحيات الاستعلامات
            'view inquiries',
            'create inquiries',
            'manage inquiries',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // إنشاء الأدوار وتعيين الصلاحيات

        // دور المدير (Admin)
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // دور الطبيب (Doctor)
        $doctorRole = Role::create(['name' => 'doctor']);
        $doctorRole->givePermissionTo([
            'view patients',
            'manage own visits',
            'view visits',
            'create visits',
            'edit visits',
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'view radiology',
            'create radiology',
            'view lab tests',
            'create lab tests',
            'manage surgery lab tests',
        ]);

        // دور المريض (Patient)
        $patientRole = Role::create(['name' => 'patient']);
        $patientRole->givePermissionTo([
            'view own visits',
            'view appointments',
            'create appointments',
            'cancel appointments',
            'view departments',
            'view doctors',
        ]);

        // دور موظف الاستقبال (Receptionist)
        $receptionistRole = Role::create(['name' => 'receptionist']);
        $receptionistRole->givePermissionTo([
            'view patients',
            'create patients',
            'edit patients',
            'view doctors',
            'view departments',
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'view visits',
            'create visits',
            'edit visits',
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'manage surgery waiting list',
            'control surgeries',
            'view radiology',
            'create radiology',
            'view lab tests',
            'create lab tests',
            'view inquiries',
            'create inquiries',
            'manage inquiries',
        ]);

        // دور موظف المختبر (Lab Staff)
        $labStaffRole = Role::create(['name' => 'lab_staff']);
        $labStaffRole->givePermissionTo([
            'view patients',
            'view lab tests',
            'process lab requests',
            'manage surgery lab tests',
        ]);

        // دور موظف الإشعة (Radiology Staff)
        $radiologyStaffRole = Role::create(['name' => 'radiology_staff']);
        $radiologyStaffRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف الصيدلية (Pharmacy Staff)
        $pharmacyStaffRole = Role::create(['name' => 'pharmacy_staff']);
        $pharmacyStaffRole->givePermissionTo([
            'view patients',
            'view pharmacy',
            'process pharmacy requests',
        ]);

        // دور موظف العمليات (Surgery Staff)
        $surgeryStaffRole = Role::create(['name' => 'surgery_staff']);
        $surgeryStaffRole->givePermissionTo([
            'view patients',
            'view surgeries',
            'edit surgeries',
            'manage surgery waiting list',
            'control surgeries',
        ]);

        // تعيين الأدوار للمستخدمين الحاليين بناءً على حقل role
        $users = User::all();
        foreach ($users as $user) {
            if ($user->role) {
                try {
                    $user->assignRole($user->role);
                    echo "✓ تم تعيين دور '{$user->role}' للمستخدم: {$user->name}\n";
                } catch (\Exception $e) {
                    echo "✗ فشل تعيين الدور للمستخدم: {$user->name} - {$e->getMessage()}\n";
                }
            }
        }

        echo "\n✓ تم إنشاء جميع الأدوار والصلاحيات بنجاح!\n";
    }
}
