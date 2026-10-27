<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin|admin-hsop|hospital_admin');
    }

    // ============ إدارة الأدوار ============

    public function rolesIndex()
    {
        $roles = Role::withCount('users', 'permissions')
            ->with(['permissions:id,name'])
            ->get();
        $totalPermissionsCount = Permission::count();
        $totalUsersCount = User::count();

        return view('roles.index', compact('roles', 'totalPermissionsCount', 'totalUsersCount'));
    }

    public function roleUsers(Role $role)
    {
        $users = $role->users()->select('users.id', 'users.name', 'users.email', 'users.phone', 'users.created_at')->get();

        return response()->json([
            'role' => self::getRoleLabel($role->name),
            'role_name' => $role->name,
            'count' => $users->count(),
            'users' => $users,
        ]);
    }

    public static function getRoleCategory(string $roleName): array
    {
        return match ($roleName) {
            'doctor', 'resident', 'الجراح', 'التخدير' => [
                'key' => 'medical',
                'name' => 'الكوادر الطبية والجراحية',
                'icon' => 'user-md',
                'color' => 'success',
            ],
            'accountant', 'cashier', 'thaman' => [
                'key' => 'financial',
                'name' => 'المالية والمحاسبة والكاشير',
                'icon' => 'cash-register',
                'color' => 'warning',
            ],
            'lab_staff', 'radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_echo', 'radiology_mri' => [
                'key' => 'diagnostic',
                'name' => 'المحطات التشخيصية',
                'icon' => 'flask',
                'color' => 'info',
            ],
            'receptionist', 'consultation_receptionist', 'inquiry_staff', 'emergency_staff', 'nurse' => [
                'key' => 'reception_nursing',
                'name' => 'الاستقبال والتمريض والطوارئ',
                'icon' => 'ambulance',
                'color' => 'danger',
            ],
            'pharmacy_staff', 'inventory_manager', 'surgery_staff', 'gh&est', 'patient' => [
                'key' => 'pharmacy_services',
                'name' => 'الصيدلية والمستودع والخدمات',
                'icon' => 'pills',
                'color' => 'primary',
            ],
            'admin', 'admin-hsop', 'hospital_admin', 'staff' => [
                'key' => 'admin_system',
                'name' => 'الإدارة والأنظمة العامة',
                'icon' => 'crown',
                'color' => 'dark',
            ],
            default => [
                'key' => 'other',
                'name' => 'أدوار أخرى ومخصصة',
                'icon' => 'user-tag',
                'color' => 'secondary',
            ],
        };
    }

    public static function getRoleLabel(string $roleName): array
    {
        return match ($roleName) {
            'admin' => ['label' => 'مدير النظام العام', 'icon' => 'crown', 'color' => 'danger'],
            'admin-hsop', 'hospital_admin' => ['label' => 'مدير المستشفى', 'icon' => 'hospital-user', 'color' => 'danger'],
            'doctor' => ['label' => 'طبيب استشاري', 'icon' => 'user-md', 'color' => 'success'],
            'resident' => ['label' => 'طبيب مقيم', 'icon' => 'user-graduate', 'color' => 'success'],
            'الجراح' => ['label' => 'طبيب جراح', 'icon' => 'user-md', 'color' => 'info'],
            'التخدير' => ['label' => 'طبيب / مقيم تخدير', 'icon' => 'syringe', 'color' => 'warning'],
            'accountant' => ['label' => 'المحاسب المالي', 'icon' => 'calculator', 'color' => 'primary'],
            'cashier' => ['label' => 'كاشير الصندوق', 'icon' => 'cash-register', 'color' => 'success'],
            'patient' => ['label' => 'مريض', 'icon' => 'user-injured', 'color' => 'info'],
            'receptionist' => ['label' => 'موظف استقبال', 'icon' => 'user-tie', 'color' => 'warning'],
            'consultation_receptionist' => ['label' => 'موظف استعلامات استشارية', 'icon' => 'user-tie', 'color' => 'info'],
            'inquiry_staff' => ['label' => 'موظف استعلامات عامة', 'icon' => 'info-circle', 'color' => 'info'],
            'emergency_staff' => ['label' => 'كادر قسم الطوارئ', 'icon' => 'ambulance', 'color' => 'danger'],
            'nurse' => ['label' => 'كادر تمريضي', 'icon' => 'user-nurse', 'color' => 'success'],
            'lab_staff' => ['label' => 'كادر المختبر والتحاليل', 'icon' => 'flask', 'color' => 'secondary'],
            'radiology_staff' => ['label' => 'كادر الأشعة والتصوير', 'icon' => 'x-ray', 'color' => 'secondary'],
            'radiology_general' => ['label' => 'فني أشعة عامة (X-Ray)', 'icon' => 'x-ray', 'color' => 'secondary'],
            'radiology_ultrasound' => ['label' => 'فني سونار وموجات صوتية', 'icon' => 'wave-square', 'color' => 'secondary'],
            'radiology_echo' => ['label' => 'فني إيكو القلب', 'icon' => 'heartbeat', 'color' => 'secondary'],
            'radiology_mri' => ['label' => 'فني رنين مغناطيسي (MRI)', 'icon' => 'x-ray', 'color' => 'secondary'],
            'pharmacy_staff' => ['label' => 'كادر الصيدلية', 'icon' => 'pills', 'color' => 'secondary'],
            'inventory_manager' => ['label' => 'أمين المخزن والمستودع', 'icon' => 'boxes', 'color' => 'secondary'],
            'surgery_staff' => ['label' => 'كادر العمليات والرقود', 'icon' => 'procedures', 'color' => 'secondary'],
            'thaman' => ['label' => 'هيئة الضمان الصحي', 'icon' => 'shield-alt', 'color' => 'info'],
            'gh&est' => ['label' => 'كادر الجراحة والاستشارية', 'icon' => 'hospital', 'color' => 'dark'],
            'staff' => ['label' => 'كادر إداري عام', 'icon' => 'user', 'color' => 'dark'],
            default => [
                'label' => ucwords(str_replace(['_', '-'], ' ', $roleName)),
                'icon' => 'user-shield',
                'color' => 'dark',
            ],
        };
    }

    public static function getHiddenPermissions(): array
    {
        return [
            'view appointments',
            'create appointments',
            'edit appointments',
            'delete appointments',
            'create visits',
            'edit visits',
            'delete visits',
            'view referrals',
            'create referrals',
            'manage referrals',
            'view cashier appointments',
            'view cashier medical requests',
            'view cashier emergency',
            'process payments',
            'create payments',
            'edit payments',
            'view payments',
            'view doctor profits',
            'create radiology types',
            'edit radiology types',
            'delete radiology types',
            'view radiology types',
            'cancel surgeries',
            'manage surgeries',
        ];
    }

    public function rolesCreate()
    {
        Permission::firstOrCreate(['name' => 'manage emergency services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view patient history', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage health insurance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view insurance claims', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process refunds', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'send lab whatsapp', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'void payment receipt', 'guard_name' => 'web']);
        // صلاحيات قسم الخدج والحاضنات (NICU)
        Permission::firstOrCreate(['name' => 'view incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu clinical', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu nursing', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu care plans', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu doctor notes', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu attachments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'discharge incubator reservations', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $moduleOrder = self::getModuleOrder();
        $permissionDefinitions = self::getPermissionDefinitions();

        $permissions = Permission::whereNotIn('name', self::getHiddenPermissions())
            ->get()
            ->groupBy(function ($permission) {
                return $this->permissionGroup($permission->name);
            });

        return view('roles.create', compact('permissions', 'moduleOrder', 'permissionDefinitions'));
    }

    public function rolesStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'display_name' => 'required|string',
            'permissions' => 'array',
        ], [
            'name.required' => 'اسم الدور مطلوب',
            'name.unique' => 'هذا الدور موجود بالفعل',
            'display_name.required' => 'الاسم المعروض مطلوب',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        $permissionModels = [];
        if ($request->has('permissions') && is_array($request->permissions)) {
            foreach ($request->permissions as $permName) {
                if (! empty($permName)) {
                    $permissionModels[] = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $role->syncPermissions($permissionModels);

        return redirect()->route('roles.index')
            ->with('success', 'تم إضافة الدور بنجاح');
    }

    public function rolesEdit(Role $role)
    {
        if ($role->name === 'admin' && ! auth()->user()->hasRole('admin')) {
            return redirect()->route('roles.index')->with('error', 'دور مدير النظام الرئيسي (Admin) محمي بالكامل ولا يمكن التعديل عليه من قبل المشرفين.');
        }

        Permission::firstOrCreate(['name' => 'manage emergency services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view patient history', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage health insurance', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view insurance claims', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'process refunds', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'edit medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete medicine groups', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view activity logs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'delete activity logs', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'send lab whatsapp', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'void payment receipt', 'guard_name' => 'web']);
        // صلاحيات قسم الخدج والحاضنات (NICU)
        Permission::firstOrCreate(['name' => 'view incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'create incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage incubator reservations', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu clinical', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu nursing', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu care plans', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu doctor notes', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage nicu attachments', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'discharge incubator reservations', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $moduleOrder = self::getModuleOrder();
        $permissionDefinitions = self::getPermissionDefinitions();

        $permissions = Permission::whereNotIn('name', self::getHiddenPermissions())
            ->get()
            ->groupBy(function ($permission) {
                return $this->permissionGroup($permission->name);
            });
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'moduleOrder', 'permissionDefinitions'));
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        if ($role->name === 'admin' && ! auth()->user()->hasRole('admin')) {
            return redirect()->route('roles.index')->with('error', 'دور مدير النظام الرئيسي (Admin) محمي بالكامل ولا يمكن التعديل عليه.');
        }

        $request->validate([
            'display_name' => 'required|string',
            'permissions' => 'array',
        ], [
            'display_name.required' => 'الاسم المعروض مطلوب',
        ]);

        $permissionModels = [];
        if ($request->has('permissions') && is_array($request->permissions)) {
            foreach ($request->permissions as $permName) {
                if (! empty($permName)) {
                    $permissionModels[] = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $role->syncPermissions($permissionModels);

        return redirect()->route('roles.index')
            ->with('success', 'تم تحديث الدور بنجاح');
    }

    public function togglePermission(Request $request, Role $role)
    {
        if ($role->name === 'admin' && ! auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دور مدير النظام الرئيسي محمي ولا يمكن التعديل عليه.',
            ], 403);
        }

        $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);

        // إذا تم إرسال مصفوفة صلاحيات (تحديد كامل القسم أو تحديد سريع)
        if ($request->has('permissions') && is_array($request->permissions)) {
            $perms = $request->permissions;
            foreach ($perms as $permName) {
                if (! empty($permName)) {
                    $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
                    if ($status) {
                        $role->givePermissionTo($permission);
                    } else {
                        $role->revokePermissionTo($permission);
                    }
                }
            }
        }
        // أو إذا تم إرسال صلاحية فردية واحدة
        elseif ($request->filled('permission')) {
            $permName = $request->input('permission');
            $permission = Permission::firstOrCreate(['name' => $permName, 'guard_name' => 'web']);
            if ($status) {
                $role->givePermissionTo($permission);
            } else {
                $role->revokePermissionTo($permission);
            }
        }

        // تفريغ كاش الصلاحيات فورياً
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        return response()->json([
            'success' => true,
            'message' => $status ? 'تم تفعيل الصلاحية فورياً' : 'تم تعطيل الصلاحية فورياً',
            'status' => $status,
            'granted_count' => $role->permissions()->count(),
        ]);
    }

    public function rolesDestroy(Role $role)
    {
        if (in_array($role->name, ['admin', 'admin-hsop'])) {
            return back()->with('error', 'لا يمكن حذف هذا الدور لأنه من أدوار النظام الأساسية والمحمية.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف دور مرتبط بمستخدمين');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'تم حذف الدور بنجاح');
    }

    public static function getModuleOrder(): array
    {
        return [
            'inquiry' => [
                'name' => 'الاستعلامات والحجوزات',
                'icon' => 'fa-concierge-bell',
                'color' => 'info',
                'description' => 'حجز الاستشارية والسونار والفحوصات، فتح ملفات المرضى، واستعلامات الأرشيف',
            ],
            'consultant_doctors' => [
                'name' => 'العيادات والاستشارية ومحطة الأطباء',
                'icon' => 'fa-stethoscope',
                'color' => 'primary',
                'description' => 'توفر الاستشاريين، محطة كشف الطبيب، إدارة العيادات والمواعيد والزيارات',
            ],
            'cashier' => [
                'name' => 'الصندوق والكاشير',
                'icon' => 'fa-cash-register',
                'color' => 'success',
                'description' => 'الكاشير العام، كشفية الاستشارية، كاشير السونار والأشعة، كاشير الطوارئ والعمليات، وإرجاع المبالغ',
            ],
            'accounting' => [
                'name' => 'الحسابات العامة والمالية',
                'icon' => 'fa-file-invoice-dollar',
                'color' => 'secondary',
                'description' => 'مراجعة أسعار العمليات، الضمان، سجل وتقارير الفواتير، الحركات المالية، كشوفات الحسابات، وحسابات الأطباء',
            ],
            'radiology' => [
                'name' => 'الأشعة والسونار والإيكو',
                'icon' => 'fa-x-ray',
                'color' => 'teal',
                'description' => 'محطة فحص الأشعة والسونار، كتابة التقارير، تسعير وإدارة الفحوصات الإشعاعية',
            ],
            'lab' => [
                'name' => 'المختبر والتحاليل الطبية',
                'icon' => 'fa-vial',
                'color' => 'purple',
                'description' => 'محطة التحاليل وسحب العينات، إدخال النتائج، تسعير الفحوصات ومجموعات المفضلات',
            ],
            'pharmacy' => [
                'name' => 'الصيدلية والمخزن الطبي',
                'icon' => 'fa-pills',
                'color' => 'success',
                'description' => 'نقطة بيع وصرف الصيدلية (POS Kanban)، صرف الوصفات، المخزن، والمشتريات',
            ],
            'emergency' => [
                'name' => 'قسم الطوارئ',
                'icon' => 'fa-ambulance',
                'color' => 'danger',
                'description' => 'استقبال وترياج الطوارئ، محطة كشف وعلاج الحالات الحرجة، والخدمات التمريضية',
            ],
            'surgeries' => [
                'name' => 'العمليات الجراحية والرقود',
                'icon' => 'fa-procedures',
                'color' => 'warning',
                'description' => 'محطات الجراح والتخدير والتمريض والمقيم، صالات العمليات، وإدارة الأسرّة والغرف',
            ],
            'nicu' => [
                'name' => 'قسم الخدج والحاضنات (NICU)',
                'icon' => 'fa-baby',
                'color' => 'pink',
                'description' => 'مراقبة الحاضنات، الأوامر السريرية، محطة التمريض، خطة الرعاية، ملاحظات الاختصاص، والأرشيف'
            ],
            'whatsapp' => [
                'name' => 'خدمات ومراسلات الواتساب (WhatsApp)',
                'icon' => 'fab fa-whatsapp',
                'color' => 'success',
                'description' => 'إرسال ومشاركة التقارير والنتائج الطبية مع المرضى عبر واتساب للأقسام المختلفة',
            ],
            'system_admin' => [
                'name' => 'إدارة النظام والإعدادات',
                'icon' => 'fa-cogs',
                'color' => 'dark',
                'description' => 'المستخدمين، الأدوار، الصلاحيات، وإعدادات المستشفى العامة',
            ],
        ];
    }

    public static function getPermissionDefinitions(): array
    {
        return [
            // 🏢 1. الاستعلامات والحجوزات
            'view inquiries' => ['label' => 'عرض شاشة واستقبال الاستعلامات', 'action' => 'عرض', 'badge' => 'info'],
            'create inquiries' => ['label' => 'حجز وإنشاء طلب جديد في الاستعلامات', 'action' => 'إضافة', 'badge' => 'success'],
            'manage inquiries' => ['label' => 'إدارة ومتابعة طلبات الاستعلامات', 'action' => 'إدارة', 'badge' => 'primary'],
            'view patients' => ['label' => 'عرض قائمة المرضى المسجلين', 'action' => 'عرض', 'badge' => 'info'],
            'create patients' => ['label' => 'فتح ملف مريض جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit patients' => ['label' => 'تعديل بيانات ملف المريض', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete patients' => ['label' => 'حذف ملف مريض', 'action' => 'حذف', 'badge' => 'danger'],
            'view patient history' => ['label' => 'عرض وبحث في أرشيف وسجل المرضى الشامل', 'action' => 'عرض', 'badge' => 'info'],
            'view occupancy' => ['label' => 'عرض إشغال الأسرّة والمرضى المقيمين', 'action' => 'عرض', 'badge' => 'info'],
            'inquiry.create.checkup' => ['label' => 'حجز موعد كشفية عيادة استشارية', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology' => ['label' => 'حجز فحوصات الأشعة والتصوير من الاستعلامات', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.ultrasound' => ['label' => 'حجز فحص سونار (Ultrasound)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.general' => ['label' => 'حجز فحص أشعة عامة (X-Ray)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.mri' => ['label' => 'حجز فحص رنين مغناطيسي (MRI)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.echo' => ['label' => 'حجز فحص إيكو للقلب (Echocardiogram)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.lab' => ['label' => 'حجز تحاليل مختبرية من الاستعلامات', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.pharmacy' => ['label' => 'طلب صيدلية من الاستعلامات', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.blood_bank' => ['label' => 'حجز طلب مصرف الدم', 'action' => 'حجز', 'badge' => 'primary'],
            'create emergencies' => ['label' => 'حجز واستقبال حالة طوارئ جديدة / طوارئ الضمان', 'action' => 'حجز', 'badge' => 'danger'],
            'view bed reservations' => ['label' => 'عرض سجل وقائمة حجوزات الأسرّة والرقود', 'action' => 'عرض', 'badge' => 'info'],
            'create bed reservations' => ['label' => 'حجز وتسكين سرير أو رقود مبدئي لمريض', 'action' => 'حجز', 'badge' => 'success'],
            'view incubator reservations' => ['label' => 'عرض سجل وقائمة حجوزات حاضنات الخُدّج', 'action' => 'عرض', 'badge' => 'info'],
            'create incubator reservations' => ['label' => 'حجز وتسكين حاضنة خُدّج جديدة (NICU)', 'action' => 'حجز', 'badge' => 'pink'],
            'manage incubator reservations' => ['label' => 'إدارة وإجراءات الدخول والخروج للحاضنات', 'action' => 'إدارة', 'badge' => 'primary'],

            // 🩺 2. العيادات والاستشارية ومحطة الأطباء
            'manage consultant availability' => ['label' => 'دخول شاشة جدول توفر الاستشاريين والعيادات', 'action' => 'إدارة', 'badge' => 'primary'],
            'view doctors' => ['label' => 'عرض قائمة الأطباء الاستشاريين', 'action' => 'عرض', 'badge' => 'info'],
            'create doctors' => ['label' => 'إضافة طبيب استشاري جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit doctors' => ['label' => 'تعديل بيانات وسجلات الأطباء', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete doctors' => ['label' => 'حذف سجل طبيب', 'action' => 'حذف', 'badge' => 'danger'],
            'view departments' => ['label' => 'عرض قائمة العيادات والأقسام الطبية', 'action' => 'عرض', 'badge' => 'info'],
            'create departments' => ['label' => 'إضافة عيادة أو قسم طبي جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit departments' => ['label' => 'تعديل بيانات العيادات والأقسام', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete departments' => ['label' => 'حذف عيادة أو قسم', 'action' => 'حذف', 'badge' => 'danger'],
            'cancel appointments' => ['label' => 'إلغاء المواعيد وحجوزات السونار', 'action' => 'إلغاء', 'badge' => 'danger'],
            'view visits' => ['label' => 'عرض سجل الزيارات الطبية العامة', 'action' => 'عرض', 'badge' => 'info'],
            'create visits' => ['label' => 'إنشاء زيارة طبية جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit visits' => ['label' => 'تعديل بيانات الزيارة الطبية', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete visits' => ['label' => 'حذف زيارة طبية', 'action' => 'حذف', 'badge' => 'danger'],
            'view own visits' => ['label' => 'دخول محطة الطبيب وععرض زياراتي', 'action' => 'عرض', 'badge' => 'info'],
            'manage own visits' => ['label' => 'إدارة الكشف الطبي والفحوصات في محطة الطبيب', 'action' => 'إدارة', 'badge' => 'primary'],
            'view medicine groups' => ['label' => 'عرض مجموعات الأدوية المفضلة (الروشتات السريعة)', 'action' => 'عرض', 'badge' => 'info'],
            'create medicine groups' => ['label' => 'إنشاء وحفظ مجموعة أدوية مفضلة جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit medicine groups' => ['label' => 'تعديل وتخصيص باقة أدوية مفضلة', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete medicine groups' => ['label' => 'حذف باقة أدوية مفضلة', 'action' => 'حذف', 'badge' => 'danger'],
            'view lab test groups' => ['label' => 'عرض مجموعات التحاليل المفضلة', 'action' => 'عرض', 'badge' => 'info'],
            'create lab test groups' => ['label' => 'إنشاء مجموعة تحاليل مفضلة جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit lab test groups' => ['label' => 'تعديل مجموعة تحاليل مفضلة', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete lab test groups' => ['label' => 'حذف مجموعة تحاليل مفضلة', 'action' => 'حذف', 'badge' => 'danger'],
            'view referrals' => ['label' => 'عرض سجل التحويلات الطبية بين العيادات', 'action' => 'عرض', 'badge' => 'info'],
            'create referrals' => ['label' => 'إنشاء تحويل طبي لمريض', 'action' => 'إضافة', 'badge' => 'success'],
            'manage referrals' => ['label' => 'إدارة وقبول التحويلات الطبية', 'action' => 'إدارة', 'badge' => 'primary'],

            // 💳 3. الصندوق والكاشير
            'view cashier' => ['label' => 'عرض لوحة الكاشير المركزية العامة', 'action' => 'عرض', 'badge' => 'info'],
            'process consultation payments' => ['label' => 'قبض وتسديد كشفية الاستشارية وحجز السونار', 'action' => 'معالجة', 'badge' => 'success'],
            'process ultrasound payments' => ['label' => 'قبض وتسديد طلبات السونار الخارجي المباشر فقط', 'action' => 'معالجة', 'badge' => 'primary'],
            'process medical requests payments' => ['label' => 'قبض وتسديد رسوم الفحوصات (مختبر وأشعة)', 'action' => 'معالجة', 'badge' => 'success'],
            'process emergency payments' => ['label' => 'قبض وتسديد فواتير وإجراءات الطوارئ', 'action' => 'معالجة', 'badge' => 'success'],
            'view cashier surgeries' => ['label' => 'عرض كاشير قسم العمليات الجراحية', 'action' => 'عرض', 'badge' => 'info'],
            'process surgery payments' => ['label' => 'قبض وتثبيت دفعات العمليات وأجور الغرف', 'action' => 'معالجة', 'badge' => 'success'],
            'process refunds' => ['label' => 'استرجاع مبالغ العمليات الملغاة وفوارق الغرف للمرضى', 'action' => 'معالجة', 'badge' => 'warning'],
            'void payment receipt' => ['label' => 'إبطال وإلغاء وصل دفع مالي وإعادة فتحه للتصحيح والاسترداد (مع تدقيق الملغيات)', 'action' => 'إلغاء / تدقيق', 'badge' => 'danger'],

            // 📊 4. الحسابات العامة والمالية
            'view insurance claims' => ['label' => 'عرض ومتابعة كشوفات وسجلات مطالبات الضمان وتصديرها', 'action' => 'عرض', 'badge' => 'info'],
            'review surgery prices' => ['label' => 'مراجعة وتأكيد أسعار وتكاليف العمليات (محاسب)', 'action' => 'مراجعة', 'badge' => 'warning'],
            'manage health insurance' => ['label' => 'إدارة نسب وفئات استقطاع الضمان الصحي الوطني', 'action' => 'إدارة', 'badge' => 'primary'],
            'manage doctor commissions' => ['label' => 'إعدادات وتخصيص عمولات ونسب الأطباء', 'action' => 'إدارة', 'badge' => 'primary'],
            'view treasury' => ['label' => 'عرض خزينة المستشفى المركزية وحركة الصناديق', 'action' => 'عرض', 'badge' => 'info'],
            'manage treasury' => ['label' => 'تسجيل إيرادات ومصروفات وسندات الخزينة', 'action' => 'إدارة', 'badge' => 'primary'],
            'view cashier reports' => ['label' => 'عرض سجل وتقارير الفواتير والإيرادات', 'action' => 'عرض', 'badge' => 'info'],
            'view consultant financial movements' => ['label' => 'عرض الحركات والقيود المالية للاستشارية', 'action' => 'عرض', 'badge' => 'info'],
            'view account statements' => ['label' => 'عرض كشوفات الحسابات العامة وتصديرها', 'action' => 'عرض', 'badge' => 'info'],
            'view doctor accounts' => ['label' => 'عرض حسابات ومستحقات وأرباح الأطباء الاستشاريين', 'action' => 'عرض', 'badge' => 'info'],
            'view emergency analytics' => ['label' => 'عرض تحليلات وإحصاءات قسم الطوارئ', 'action' => 'عرض', 'badge' => 'info'],
            'view emergency financial movements' => ['label' => 'عرض الحركات والقيود المالية للطوارئ', 'action' => 'عرض', 'badge' => 'info'],
            'view emergency statements' => ['label' => 'عرض كشوفات حسابات الطوارئ وتفاصيلها', 'action' => 'عرض', 'badge' => 'info'],
            'view emergency doctor accounts' => ['label' => 'عرض وصرف حسابات وأجور أطباء الطوارئ', 'action' => 'عرض', 'badge' => 'info'],
            'view diagnostic analytics' => ['label' => 'عرض تحليلات وإحصاءات التشخيص (مختبر وأشعة)', 'action' => 'عرض', 'badge' => 'info'],

            // 〰️ 4. الأشعة والسونار والإيكو
            'view radiology' => ['label' => 'عرض قسم الأشعة والسونار وقائمة الفحوصات', 'action' => 'عرض', 'badge' => 'info'],
            'create radiology' => ['label' => 'إنشاء طلب فحص إشعاعي أو سونار جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit radiology' => ['label' => 'تعديل بيانات طلب الأشعة', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete radiology' => ['label' => 'حذف طلب فحص أشعة', 'action' => 'حذف', 'badge' => 'danger'],
            'process radiology requests' => ['label' => 'محطة الأشعة والسونار (كتابة واعتماد التقارير والصور)', 'action' => 'معالجة', 'badge' => 'success'],
            'manage radiology types' => ['label' => 'إدارة وتسعير خدمات وأنواع الأشعة والسونار', 'action' => 'إدارة', 'badge' => 'primary'],

            // 🧪 5. المختبر والتحاليل
            'view lab tests' => ['label' => 'عرض قائمة الفحوصات وسجل طلبات المختبر', 'action' => 'عرض', 'badge' => 'info'],
            'create lab tests' => ['label' => 'إضافة فحص مختبري جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit lab tests' => ['label' => 'تعديل بيانات وأسعار الفحوصات المختبرية', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete lab tests' => ['label' => 'حذف فحص مختبري', 'action' => 'حذف', 'badge' => 'danger'],
            'process lab requests' => ['label' => 'محطة المختبر (سحب العينات وإدخال واعتماد النتائج)', 'action' => 'معالجة', 'badge' => 'success'],
            'manage surgery lab tests' => ['label' => 'إدارة واختيار تحاليل العمليات الجراحية', 'action' => 'إدارة', 'badge' => 'primary'],
            'view packages' => ['label' => 'عرض باقات الفحص الطبي الشامل', 'action' => 'عرض', 'badge' => 'info'],
            'create packages' => ['label' => 'إضافة باقة فحوصات جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit packages' => ['label' => 'تعديل باقات الفحوصات والأسعار', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete packages' => ['label' => 'حذف باقة فحص', 'action' => 'حذف', 'badge' => 'danger'],

            // 💊 6. الصيدلية والمخزن الطبي
            'view pharmacy' => ['label' => 'عرض شاشة الصيدلية ونقطة البيع (POS Kanban)', 'action' => 'عرض', 'badge' => 'info'],
            'process pharmacy requests' => ['label' => 'صرف الوصفات الطبية واقتراح البدائل الدوائية', 'action' => 'معالجة', 'badge' => 'success'],
            'view products' => ['label' => 'عرض دليل الأدوية والمواد الطبية', 'action' => 'عرض', 'badge' => 'info'],
            'create products' => ['label' => 'إضافة دواء أو مستلزم طبي جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit products' => ['label' => 'تعديل بيانات الدواء والأسعار والباركود', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete products' => ['label' => 'حذف صنف دوائي', 'action' => 'حذف', 'badge' => 'danger'],
            'manage inventory' => ['label' => 'إدارة المستودع والمخزن الطبي العام', 'action' => 'إدارة', 'badge' => 'primary'],
            'view inventory' => ['label' => 'عرض تقرير ومستوى أرصدة المخزون', 'action' => 'عرض', 'badge' => 'info'],
            'view stock_batches' => ['label' => 'تتبع وجبات وتواريخ صلاحية الأدوية (FEFO)', 'action' => 'عرض', 'badge' => 'info'],
            'view stock_movements' => ['label' => 'عرض سجل حركات الوارد والمنصرف المخزني', 'action' => 'عرض', 'badge' => 'info'],
            'view stock transfers' => ['label' => 'عرض حركات نقل الأدوية بين الأقسام', 'action' => 'عرض', 'badge' => 'info'],
            'view stock transfer requests' => ['label' => 'عرض والموافقة على طلبات نقل المخزون', 'action' => 'عرض', 'badge' => 'info'],
            'view purchases' => ['label' => 'عرض فواتير المشتريات الدوائية', 'action' => 'عرض', 'badge' => 'info'],
            'create purchases' => ['label' => 'تسجيل فاتورة شراء واستلام وجبات جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit purchases' => ['label' => 'تعديل فاتورة مشتريات', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete purchases' => ['label' => 'حذف فاتورة مشتريات', 'action' => 'حذف', 'badge' => 'danger'],
            'view suppliers' => ['label' => 'عرض قائمة شركات الأدوية والموردين', 'action' => 'عرض', 'badge' => 'info'],
            'create suppliers' => ['label' => 'إضافة مورد أو شركة أدوية جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit suppliers' => ['label' => 'تعديل بيانات الموردين وحساباتهم', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete suppliers' => ['label' => 'حذف سجل مورد', 'action' => 'حذف', 'badge' => 'danger'],

            // 🚑 7. قسم الطوارئ
            'view emergencies' => ['label' => 'عرض حالات الطوارئ والترياج السريري', 'action' => 'عرض', 'badge' => 'info'],
            'create emergencies' => ['label' => 'تسجيل واستقبال حالة طوارئ جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit emergencies' => ['label' => 'تعديل بيانات حالة الطوارئ والملاحظات السريرية', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete emergencies' => ['label' => 'حذف حالة طوارئ', 'action' => 'حذف', 'badge' => 'danger'],
            'manage emergency services' => ['label' => 'إدارة خدمات وإجراءات الطوارئ وتسعيرها', 'action' => 'إدارة', 'badge' => 'primary'],
            'manage emergency vitals' => ['label' => 'تسجيل ومتابعة العلامات الحيوية لمريض الطوارئ', 'action' => 'إدارة', 'badge' => 'primary'],

            // 🏥 8. العمليات الجراحية والرقود
            'view surgeries' => ['label' => 'عرض جدول وسجل العمليات الجراحية', 'action' => 'عرض', 'badge' => 'info'],
            'create surgeries' => ['label' => 'حجز وإدراج عملية جراحية جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit surgeries' => ['label' => 'تعديل تفاصيل وبيانات العملية الجراحية', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete surgeries' => ['label' => 'إلغاء وحذف عملية جراحية', 'action' => 'حذف', 'badge' => 'danger'],
            'control surgeries' => ['label' => 'التحكم في مراحل العملية (بدء، إنهاء، خروج)', 'action' => 'تحكم', 'badge' => 'primary'],
            'manage surgery waiting list' => ['label' => 'إدارة قائمة انتظار العمليات وجدولتها', 'action' => 'إدارة', 'badge' => 'primary'],
            'view surgical operations' => ['label' => 'عرض دليل أنواع العمليات الجراحية المتاحة', 'action' => 'عرض', 'badge' => 'info'],
            'manage surgical operations' => ['label' => 'إدارة وتسعير أنواع العمليات الجراحية', 'action' => 'إدارة', 'badge' => 'primary'],
            'view resident station' => ['label' => 'دخول محطة الطبيب المقيم', 'action' => 'محطة', 'badge' => 'info'],
            'view operation theater station' => ['label' => 'دخول محطة صالة العمليات الكبرى', 'action' => 'محطة', 'badge' => 'danger'],
            'view surgeon station' => ['label' => 'دخول محطة الطبيب الجراح', 'action' => 'محطة', 'badge' => 'primary'],
            'view anesthesia station' => ['label' => 'دخول محطة طبيب التخدير', 'action' => 'محطة', 'badge' => 'warning'],
            'view nursing station' => ['label' => 'دخول محطة التمريض السريري', 'action' => 'محطة', 'badge' => 'success'],
            'manage rooms' => ['label' => 'إدارة غرف العمليات وأسرة الرقود', 'action' => 'إدارة', 'badge' => 'primary'],
            'view medical devices' => ['label' => 'عرض دليل الأجهزة والمستلزمات الجراحية', 'action' => 'عرض', 'badge' => 'info'],
            'manage medical devices' => ['label' => 'إدارة وصيانة الأجهزة الطبية الجراحية', 'action' => 'إدارة', 'badge' => 'primary'],

            // ⚙️ 9. إدارة النظام والإعدادات
            'manage users' => ['label' => 'إدارة حسابات المستخدمين والموظفين', 'action' => 'إدارة', 'badge' => 'danger'],
            'manage roles' => ['label' => 'إدارة الأدوار والمسميات الوظيفية', 'action' => 'إدارة', 'badge' => 'danger'],
            'manage permissions' => ['label' => 'إدارة وتخصيص صلاحيات النظام', 'action' => 'إدارة', 'badge' => 'danger'],
            'view activity logs' => ['label' => 'عرض سجل حركات ونشاط الموظفين والرقابة الإدارية', 'action' => 'عرض', 'badge' => 'info'],
            'delete activity logs' => ['label' => 'تنظيف وأرشفة سجل الحركات القديم', 'action' => 'حذف', 'badge' => 'danger'],
            'manage database sync' => ['label' => 'مزامنة وسحب ورفع قواعد البيانات السحابية', 'action' => 'إدارة', 'badge' => 'danger'],

            // 🔒 صلاحيات المواعيد والمدفوعات
            'view appointments' => ['label' => 'عرض سجل المواعيد والحجوزات', 'action' => 'عرض', 'badge' => 'info'],
            'create appointments' => ['label' => 'حجز وإنشاء موعد جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit appointments' => ['label' => 'تعديل بيانات الموعد', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete appointments' => ['label' => 'حذف موعد من السجل', 'action' => 'حذف', 'badge' => 'danger'],
            'view payments' => ['label' => 'عرض سجل المدفوعات والوصولات', 'action' => 'عرض', 'badge' => 'info'],
            'create payments' => ['label' => 'إصدار سند أو وصل دفع', 'action' => 'إضافة', 'badge' => 'success'],
            'edit payments' => ['label' => 'تعديل سند دفع', 'action' => 'تعديل', 'badge' => 'warning'],
            'view cashier appointments' => ['label' => 'عرض كاشير مواعيد الاستشارية', 'action' => 'عرض', 'badge' => 'info'],
            'view cashier medical requests' => ['label' => 'عرض كاشير الفحوصات الطبية', 'action' => 'عرض', 'badge' => 'info'],
            'view cashier emergency' => ['label' => 'عرض كاشير قسم الطوارئ', 'action' => 'عرض', 'badge' => 'info'],
            'view doctor profits' => ['label' => 'عرض أرباح وحسابات الأطباء', 'action' => 'عرض', 'badge' => 'info'],
            'view radiology types' => ['label' => 'عرض قائمة خدمات وأنواع الأشعة', 'action' => 'عرض', 'badge' => 'info'],
            'create radiology types' => ['label' => 'إضافة نوع أو خدمة أشعة جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit radiology types' => ['label' => 'تعديل نوع أو سعر خدمة أشعة', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete radiology types' => ['label' => 'حذف خدمة أشعة', 'action' => 'حذف', 'badge' => 'danger'],

            // 📲 10. خدمات ومراسلات الواتساب (WhatsApp)
            'send lab whatsapp' => ['label' => 'إرسال ومشاركة نتائج التحاليل المخبرية للمرضى عبر واتساب', 'action' => 'إرسال', 'badge' => 'success'],

            // 👶 11. قسم الخدج والحاضنات (NICU)
            'view incubator reservations' => ['label' => 'عرض لوحة وحاضنات قسم الخدج (NICU)', 'action' => 'عرض', 'badge' => 'info'],
            'create incubator reservations' => ['label' => 'تسجيل وقبول وتسكين طفل جديد بالحاضنة', 'action' => 'إضافة', 'badge' => 'success'],
            'manage incubator reservations' => ['label' => 'إدارة شاملة لملف الحاضنة وتعديل البيانات', 'action' => 'إدارة', 'badge' => 'primary'],
            'manage nicu clinical' => ['label' => 'إصدار ومتابعة الأوامر السريرية (تحاليل، أشعة، وصفات، استشارات)', 'action' => 'سريري', 'badge' => 'primary'],
            'manage nicu nursing' => ['label' => 'محطة التمريض (العلامات الحيوية، الوزن، وسجل إعطاء العلاج MAR)', 'action' => 'تمريض', 'badge' => 'success'],
            'manage nicu care plans' => ['label' => 'إدارة وتعديل خطة الرعاية وجداول مواعيد الرضاعة والعلاج', 'action' => 'توصيات', 'badge' => 'warning'],
            'manage nicu doctor notes' => ['label' => 'تدوين واعتماد وتثبيت ملاحظات وتوجيهات الطبيب الاختصاص', 'action' => 'طبيب', 'badge' => 'danger'],
            'manage nicu attachments' => ['label' => 'رفع واستعراض وأرشفة وحذف مرفقات ومستندات الطفل', 'action' => 'أرشيف', 'badge' => 'teal'],
            'discharge incubator reservations' => ['label' => 'إجراء الخروج السريري وتطهير الحاضنة', 'action' => 'خروج', 'badge' => 'danger'],
        ];
    }

    public function permissionGroup(string $permissionName): string
    {
        // 0. NICU Services
        if (str_contains($permissionName, 'incubator') || str_contains($permissionName, 'nicu')) {
            return 'nicu';
        }

        // 0. WhatsApp Services
        if (str_contains($permissionName, 'whatsapp')) {
            return 'whatsapp';
        }

        // 1. Inquiry & Bookings (All Reception & Inpatient Booking Types)
        if (str_starts_with($permissionName, 'inquiry.') || in_array($permissionName, [
            'view inquiries', 'create inquiries', 'manage inquiries',
            'view patient history', 'view occupancy',
            'view patients', 'create patients', 'edit patients', 'delete patients',
            'create emergencies',
            'view bed reservations', 'create bed reservations',
        ])) {
            return 'inquiry';
        }

        // 2. Accounting & Financial Management
        if (in_array($permissionName, [
            'review surgery prices',
            'manage health insurance',
            'manage doctor commissions',
            'view treasury',
            'manage treasury',
            'view cashier reports',
            'view consultant financial movements',
            'view account statements',
            'view doctor accounts',
            'view emergency analytics',
            'view emergency financial movements',
            'view emergency statements',
            'view emergency doctor accounts',
            'view diagnostic analytics',
            'view doctor profits',
        ]) || str_contains($permissionName, 'insurance') || str_contains($permissionName, 'treasury')) {
            return 'accounting';
        }

        // 3. Cashier & POS
        if (str_contains($permissionName, 'void') || str_contains($permissionName, 'cashier') || str_contains($permissionName, 'payment') || str_contains($permissionName, 'refund')) {
            return 'cashier';
        }

        // 3. Radiology & Ultrasound & Echo
        if (str_contains($permissionName, 'radiology')) {
            return 'radiology';
        }

        // 4. Laboratory
        if ((str_contains($permissionName, 'lab ') || str_contains($permissionName, 'lab_') || str_contains($permissionName, 'package') || $permissionName === 'manage surgery lab tests') && ! str_contains($permissionName, 'lab test group')) {
            return 'lab';
        }

        // 5. Pharmacy & Inventory
        if ((str_contains($permissionName, 'pharmacy') || str_contains($permissionName, 'product') || str_contains($permissionName, 'supplier') ||
            str_contains($permissionName, 'purchase') || str_contains($permissionName, 'inventory') ||
            str_starts_with($permissionName, 'view stock') || str_starts_with($permissionName, 'manage location')) && ! str_contains($permissionName, 'medicine group')) {
            return 'pharmacy';
        }

        // 6. Emergency
        if (str_contains($permissionName, 'emergenc')) {
            return 'emergency';
        }

        // 7. Surgeries & Operating Theaters & Stations
        if (str_contains($permissionName, 'surg') || str_contains($permissionName, 'station') || $permissionName === 'manage rooms' || str_contains($permissionName, 'device')) {
            return 'surgeries';
        }

        // 8. Outpatient & Consultant & Doctors (Clinics, Appointments, Visits, Medicine & Lab Groups)
        if (str_contains($permissionName, 'consultant') || str_contains($permissionName, 'own visits') ||
            str_contains($permissionName, 'doctor') || str_contains($permissionName, 'department') ||
            str_contains($permissionName, 'appointment') || str_contains($permissionName, 'visit') ||
            str_contains($permissionName, 'referral') || str_contains($permissionName, 'medicine group') ||
            str_contains($permissionName, 'lab test group')) {
            return 'consultant_doctors';
        }

        // 9. System Admin & Settings
        if (str_contains($permissionName, 'user') || str_contains($permissionName, 'role') || str_contains($permissionName, 'permission') || str_contains($permissionName, 'activity log')) {
            return 'system_admin';
        }

        return 'system_admin';
    }

    // ============ إدارة الصلاحيات ============

    public function permissionsIndex()
    {
        $permissions = Permission::withCount('roles')->get()->groupBy(function ($permission) {
            return $this->permissionGroup($permission->name);
        });

        return view('permissions.index', compact('permissions'));
    }

    public function permissionsCreate()
    {
        return view('permissions.create');
    }

    public function permissionsStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:permissions,name',
        ], [
            'name.required' => 'اسم الصلاحية مطلوب',
            'name.unique' => 'هذه الصلاحية موجودة بالفعل',
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return redirect()->route('permissions.index')
            ->with('success', 'تم إضافة الصلاحية بنجاح');
    }

    public function permissionsDestroy(Permission $permission)
    {
        $permission->delete();

        return redirect()->route('permissions.index')
            ->with('success', 'تم حذف الصلاحية بنجاح');
    }
}
