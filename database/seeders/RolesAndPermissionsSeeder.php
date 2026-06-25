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
            // صلاحيات نظام الحسابات
            'view accounting',
            'view accounting reports',
            'create expenses',
            'edit expenses',
            'delete expenses',
            'approve expenses',

            // صلاحيات الكاشير - منفصلة لكل قسم
            'view cashier',
            'view cashier appointments',      // عرض مواعيد الاستشارية
            'process consultation payments',   // معالجة دفع الاستشارية
            'view cashier medical requests',  // عرض الطلبات الطبية
            'process medical requests payments', // معالجة دفع الطلبات الطبية
            'view cashier emergency',         // عرض طوارئ الكاشير
            'process emergency payments',     // معالجة دفع الطوارئ
            'view cashier surgeries',         // عرض عمليات الكاشير
            'process surgery payments',       // معالجة دفع العمليات
            'view cashier reports',           // عرض تقارير المدفوعات
            
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
            'manage rooms',
            'view resident station',
            'view operation theater station',
            'view surgeon station',
            'view anesthesia station',
            'view nursing station',
            
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
            // صلاحيات مجموعات المفضلات للتحاليل
            'view lab test groups',
            'create lab test groups',
            'edit lab test groups',
            'delete lab test groups',
            // صلاحيات الباقات (Packages)
            'view packages',
            'create packages',
            'edit packages',
            'delete packages',

            // صلاحيات المخزن والمشتريات
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            'view inventory',
            'manage inventory',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view stock_batches',
            'view stock_movements',
            'view stock transfers',
            'view stock transfer requests',

            // صلاحيات الصيدلية
            'view pharmacy',
            'process pharmacy requests',

            // صلاحيات المخزن والمشتريات
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            'manage inventory',
            
            // صلاحيات الاستعلامات
            'view inquiries',
            'create inquiries',
            'manage inquiries',
            'inquiry.create.lab',
            'inquiry.create.radiology',            'inquiry.create.radiology.general',
            'inquiry.create.radiology.ultrasound',
            'inquiry.create.radiology.mri',
            'inquiry.create.radiology.echo',            'inquiry.create.pharmacy',
            'inquiry.create.checkup',
            'inquiry.create.blood_bank',
            
            // صلاحيات حجز الأشعة - حسب النوع (للتحكم المرن)
            'inquiry.create.radiology.general',     // حجز أشعة عامة
            'inquiry.create.radiology.ultrasound',  // حجز سونار
            'inquiry.create.radiology.mri',         // حجز رنين مغناطيسي
            'inquiry.create.radiology.echo',        // حجز إيكو
            
            // صلاحيات توفر الأطباء الاستشاريين
            'manage consultant availability',
            
            // صلاحيات إدارة النظام
            'manage users',
            'manage roles',
            'manage permissions',
            
            // صلاحيات الطوارئ
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'delete emergencies',
            'manage emergency vitals',

            // صلاحيات عرض المرضى المقيمين
            'view occupancy',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // إنشاء الأدوار وتعيين الصلاحيات

        // دور المدير (Admin)
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // دور الطبيب (Doctor)
        $doctorRole = Role::firstOrCreate(['name' => 'doctor']);
        $doctorRole->givePermissionTo([
            'view patients',
            'manage own visits',
            'view visits',
            'create visits',
            'edit visits',
            'view surgeries',
            'create surgeries',
            'edit surgeries',
            'view resident station',
            'view surgeon station',
            'view anesthesia station',
            'view radiology',
            'create radiology',
            'view lab tests',
            'create lab tests',
            'manage surgery lab tests',
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'manage emergency vitals',
            'view lab test groups',
            'create lab test groups',
            'edit lab test groups',
            'delete lab test groups',
        ]);

        // دور المريض (Patient)
        $patientRole = Role::firstOrCreate(['name' => 'patient']);
        $patientRole->givePermissionTo([
            'view own visits',
            'view appointments',
            'create appointments',
            'cancel appointments',
            'view departments',
            'view doctors',
        ]);

        // دور موظف الاستقبال (Receptionist)
        $receptionistRole = Role::firstOrCreate(['name' => 'receptionist']);
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
            'manage rooms',
            'view resident station',
            'view operation theater station',
            'view surgeon station',
            'view anesthesia station',
            'view nursing station',
            'view radiology',
            'create radiology',
            'view lab tests',
            'create lab tests',
            'view inquiries',
            'create inquiries',
            'manage inquiries',
            'inquiry.create.lab',
            'inquiry.create.radiology',
            'inquiry.create.pharmacy',
            'inquiry.create.checkup',
            'inquiry.create.blood_bank',
            'view cashier',
            'view occupancy',
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'manage emergency vitals',
            'view lab test groups',
            'create lab test groups',
            'edit lab test groups',
            'delete lab test groups',
        ]);

        // دور الكاشير (Cashier) - صلاحيات كاملة للكاشير
        $cashierRole = Role::firstOrCreate(['name' => 'cashier']);
        $cashierRole->givePermissionTo([
            'view cashier',
            'view cashier appointments',
            'process consultation payments',
            'view cashier medical requests',
            'process medical requests payments',
            'view cashier emergency',
            'process emergency payments',
            'view cashier surgeries',
            'process surgery payments',
            'view cashier reports',
            'view patients',
            // صلاحيات نظام الحسابات
            'view accounting',
            'view accounting reports',
            'create expenses',
            'edit expenses',
            'approve expenses',
        ]);

        // دور موظف استعلامات الاستشارية (Consultation Receptionist)
        $consultationReceptionistRole = Role::firstOrCreate(['name' => 'consultation_receptionist']);
        $consultationReceptionistRole->givePermissionTo([
            'view patients',
            'create patients',
            'view inquiries',
            'create inquiries',
            'manage consultant availability',
            'view doctors',
            'view occupancy',
            'view cashier',
            'view cashier appointments',
            'process consultation payments',
            'inquiry.create.checkup',
            // يمكن إضافة صلاحيات الأشعة حسب الحاجة:
            // 'inquiry.create.radiology.ultrasound',
            // 'inquiry.create.radiology.general',
            // إلخ...
        ]);

        // دور موظف المختبر (Lab Staff)
        $labStaffRole = Role::firstOrCreate(['name' => 'lab_staff']);
        $labStaffRole->givePermissionTo([
            'view patients',
            'view lab tests',
            'process lab requests',
            'manage surgery lab tests',
            // أضفنا صلاحيات إدارة الباقات لموظفي المختبر
            'view packages',
            'create packages',
            'edit packages',
            'delete packages',
            'view lab test groups',
            'create lab test groups',
            'edit lab test groups',
            'delete lab test groups',
        ]);

        // دور موظف الإشعة (Radiology Staff)
        $radiologyStaffRole = Role::firstOrCreate(['name' => 'radiology_staff']);
        $radiologyStaffRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف أشعة عامة (General Radiology)
        $radiologyGeneralRole = Role::firstOrCreate(['name' => 'radiology_general']);
        $radiologyGeneralRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف سونار (Ultrasound Radiology)
        $radiologyUltrasoundRole = Role::firstOrCreate(['name' => 'radiology_ultrasound']);
        $radiologyUltrasoundRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف الرنين المغناطيسي (MRI Radiology)
        $radiologyMriRole = Role::firstOrCreate(['name' => 'radiology_mri']);
        $radiologyMriRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف إيكو (Echo Radiology) - جاهز للمستقبل
        $radiologyEchoRole = Role::firstOrCreate(['name' => 'radiology_echo']);
        $radiologyEchoRole->givePermissionTo([
            'view patients',
            'view radiology',
            'process radiology requests',
        ]);

        // دور موظف الصيدلية (Pharmacy Staff)
        $pharmacyStaffRole = Role::firstOrCreate(['name' => 'pharmacy_staff']);
        $pharmacyStaffRole->givePermissionTo([
            'view patients',
            'view pharmacy',
            'process pharmacy requests',
        ]);

        // دور موظف المخزن (Inventory Manager)
        $inventoryManagerRole = Role::firstOrCreate(['name' => 'inventory_manager']);
        $inventoryManagerRole->givePermissionTo([
            'view suppliers',
            'create suppliers',
            'edit suppliers',
            'delete suppliers',
            'view purchases',
            'create purchases',
            'edit purchases',
            'delete purchases',
            'manage inventory',
            'view products',
            'create products',
            'edit products',
            'delete products',
            'view stock_batches',
            'view stock_movements',
            'view stock transfers',
            'view stock transfer requests',
        ]);

        // دور الممرض (Nurse)
        $nurseRole = Role::firstOrCreate(['name' => 'nurse']);
        $nurseRole->givePermissionTo([
            'view patients',
            'view visits',
            'create visits',
            'edit visits',
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'manage emergency vitals',
            'view lab tests',
            'create lab tests',
            'view radiology',
            'create radiology',
            // السماح للممرضين برؤية الباقات
            'view packages',
            'view nursing station',
        ]);

        // دور موظف الطوارئ (Emergency Staff)
        $emergencyStaffRole = Role::firstOrCreate(['name' => 'emergency_staff']);
        $emergencyStaffRole->givePermissionTo([
            'view patients',
            'view emergencies',
            'create emergencies',
            'edit emergencies',
            'manage emergency vitals',
            'view lab tests',
            'create lab tests',
            'view radiology',
            'create radiology',
            // السماح بعرض الباقات لطواقم الطوارئ
            'view packages',
        ]);

        // دور موظف العمليات (Surgery Staff)
        $surgeryStaffRole = Role::firstOrCreate(['name' => 'surgery_staff']);
        $surgeryStaffRole->givePermissionTo([
            'view patients',
            'view surgeries',
            'edit surgeries',
            'manage surgery waiting list',
            'control surgeries',
            'manage rooms',
            'view resident station',
            'view operation theater station',
            'view surgeon station',
            'view anesthesia station',
            'view nursing station',
            'view cashier',
            'view cashier surgeries',
            'process surgery payments',
        ]);

        // دور موظف الاستعلامات (Inquiry Staff)
        $inquiryStaffRole = Role::firstOrCreate(['name' => 'inquiry_staff']);
        $inquiryStaffRole->givePermissionTo([
            'view patients',
            'view inquiries',
            'create inquiries',
            'manage inquiries',
            'view occupancy',
            'view visits',
            'view appointments',
            'view departments',
            'view doctors',
        ]);

        // دور موظف عام (Staff)
        $staffRole = Role::firstOrCreate(['name' => 'staff']);
        $staffRole->givePermissionTo([
            'view patients',
            'view inquiries',
            'create inquiries',
            'manage inquiries',
            'view visits',
            'view appointments',
            'view departments',
            'view doctors',
            'view occupancy',
            'view surgeries',
            'view radiology',
            'view lab tests',
            // السماح للطاقم بعرض الباقات
            'view packages',
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
