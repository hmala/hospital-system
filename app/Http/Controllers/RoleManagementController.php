<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
        $roles = Role::withCount('users', 'permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function rolesCreate()
    {
        $moduleOrder = self::getModuleOrder();
        $permissionDefinitions = self::getPermissionDefinitions();

        $permissions = Permission::all()->groupBy(function($permission) {
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
                if (!empty($permName)) {
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
        if ($role->name === 'admin' && !auth()->user()->hasRole('admin')) {
            return redirect()->route('roles.index')->with('error', 'دور مدير النظام الرئيسي (Admin) محمي بالكامل ولا يمكن التعديل عليه من قبل المشرفين.');
        }

        Permission::firstOrCreate(['name' => 'manage emergency services', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view patient history', 'guard_name' => 'web']);
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $moduleOrder = self::getModuleOrder();
        $permissionDefinitions = self::getPermissionDefinitions();

        $permissions = Permission::all()->groupBy(function($permission) {
            return $this->permissionGroup($permission->name);
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions', 'moduleOrder', 'permissionDefinitions'));
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        if ($role->name === 'admin' && !auth()->user()->hasRole('admin')) {
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
                if (!empty($permName)) {
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
        if ($role->name === 'admin' && !auth()->user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'دور مدير النظام الرئيسي محمي ولا يمكن التعديل عليه.'
            ], 403);
        }

        $status = filter_var($request->input('status'), FILTER_VALIDATE_BOOLEAN);

        // إذا تم إرسال مصفوفة صلاحيات (تحديد كامل القسم أو تحديد سريع)
        if ($request->has('permissions') && is_array($request->permissions)) {
            $perms = $request->permissions;
            foreach ($perms as $permName) {
                if (!empty($permName)) {
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
                'description' => 'حجز الاستشارية والسونار والفحوصات، فتح ملفات المرضى، واستعلامات الأرشيف'
            ],
            'consultant_doctors' => [
                'name' => 'العيادات والاستشارية ومحطة الأطباء',
                'icon' => 'fa-stethoscope',
                'color' => 'primary',
                'description' => 'توفر الاستشاريين، محطة كشف الطبيب، إدارة العيادات والمواعيد والزيارات'
            ],
            'cashier_finance' => [
                'name' => 'الصندوق والمالية',
                'icon' => 'fa-cash-register',
                'color' => 'success',
                'description' => 'الكاشير العام، كشفية الاستشارية، كاشير السونار والأشعة، كاشير الطوارئ والعمليات، والتقارير المالية'
            ],
            'radiology' => [
                'name' => 'الأشعة والسونار والإيكو',
                'icon' => 'fa-x-ray',
                'color' => 'teal',
                'description' => 'محطة فحص الأشعة والسونار، كتابة التقارير، تسعير وإدارة الفحوصات الإشعاعية'
            ],
            'lab' => [
                'name' => 'المختبر والتحاليل الطبية',
                'icon' => 'fa-vial',
                'color' => 'purple',
                'description' => 'محطة التحاليل وسحب العينات، إدخال النتائج، تسعير الفحوصات ومجموعات المفضلات'
            ],
            'pharmacy' => [
                'name' => 'الصيدلية والمخزن الطبي',
                'icon' => 'fa-pills',
                'color' => 'success',
                'description' => 'نقطة بيع وصرف الصيدلية (POS Kanban)، صرف الوصفات، المخزن، والمشتريات'
            ],
            'emergency' => [
                'name' => 'قسم الطوارئ',
                'icon' => 'fa-ambulance',
                'color' => 'danger',
                'description' => 'استقبال وترياج الطوارئ، محطة كشف وعلاج الحالات الحرجة، والخدمات التمريضية'
            ],
            'surgeries' => [
                'name' => 'العمليات الجراحية والرقود',
                'icon' => 'fa-procedures',
                'color' => 'warning',
                'description' => 'محطات الجراح والتخدير والتمريض والمقيم، صالات العمليات، وإدارة الأسرّة والغرف'
            ],
            'system_admin' => [
                'name' => 'إدارة النظام والإعدادات',
                'icon' => 'fa-cogs',
                'color' => 'dark',
                'description' => 'المستخدمين، الأدوار، الصلاحيات، وإعدادات المستشفى العامة'
            ],
        ];
    }

    public static function getPermissionDefinitions(): array
    {
        return [
            // 🏢 1. الاستعلامات والحجوزات
            'view inquiries' => ['label' => 'عرض شاشة واستقبال الاستعلامات', 'action' => 'عرض', 'badge' => 'info'],
            'create inquiries' => ['label' => 'إضافة استفسار أو قيد استعلامات', 'action' => 'إضافة', 'badge' => 'success'],
            'manage inquiries' => ['label' => 'إدارة ومتابعة طلبات الاستعلامات', 'action' => 'إدارة', 'badge' => 'primary'],
            'view patients' => ['label' => 'عرض قائمة المرضى المسجلين', 'action' => 'عرض', 'badge' => 'info'],
            'create patients' => ['label' => 'فتح ملف مريض جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit patients' => ['label' => 'تعديل بيانات ملف المريض', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete patients' => ['label' => 'حذف ملف مريض', 'action' => 'حذف', 'badge' => 'danger'],
            'view patient history' => ['label' => 'عرض وبحث في أرشيف وسجل المرضى الشامل', 'action' => 'عرض', 'badge' => 'info'],
            'view occupancy' => ['label' => 'عرض إشغال الأسرّة والمرضى المقيمين', 'action' => 'عرض', 'badge' => 'info'],
            'inquiry.create.checkup' => ['label' => 'حجز موعد كشفية عيادة استشارية', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.ultrasound' => ['label' => 'حجز فحص سونار (Ultrasound)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.general' => ['label' => 'حجز فحص أشعة عامة (X-Ray)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.mri' => ['label' => 'حجز فحص رنين مغناطيسي (MRI)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.radiology.echo' => ['label' => 'حجز فحص إيكو للقلب (Echocardiogram)', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.lab' => ['label' => 'حجز تحاليل مختبرية من الاستعلامات', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.pharmacy' => ['label' => 'طلب صيدلية من الاستعلامات', 'action' => 'حجز', 'badge' => 'primary'],
            'inquiry.create.blood_bank' => ['label' => 'حجز طلب مصرف الدم', 'action' => 'حجز', 'badge' => 'primary'],

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
            'view appointments' => ['label' => 'عرض جدول وقائمة المواعيد', 'action' => 'عرض', 'badge' => 'info'],
            'create appointments' => ['label' => 'حجز موعد استشاري جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit appointments' => ['label' => 'تعديل بيانات الموعد', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete appointments' => ['label' => 'حذف موعد استشاري', 'action' => 'حذف', 'badge' => 'danger'],
            'cancel appointments' => ['label' => 'إلغاء المواعيد المحجوزة', 'action' => 'إلغاء', 'badge' => 'danger'],
            'view visits' => ['label' => 'عرض سجل الزيارات الطبية العامة', 'action' => 'عرض', 'badge' => 'info'],
            'create visits' => ['label' => 'إنشاء زيارة طبية جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit visits' => ['label' => 'تعديل بيانات الزيارة الطبية', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete visits' => ['label' => 'حذف زيارة طبية', 'action' => 'حذف', 'badge' => 'danger'],
            'view own visits' => ['label' => 'دخول محطة الطبيب وععرض زياراتي', 'action' => 'عرض', 'badge' => 'info'],
            'manage own visits' => ['label' => 'إدارة الكشف الطبي والفحوصات في محطة الطبيب', 'action' => 'إدارة', 'badge' => 'primary'],
            'view referrals' => ['label' => 'عرض سجل التحويلات الطبية بين العيادات', 'action' => 'عرض', 'badge' => 'info'],
            'create referrals' => ['label' => 'إنشاء تحويل طبي لمريض', 'action' => 'إضافة', 'badge' => 'success'],
            'manage referrals' => ['label' => 'إدارة وقبول التحويلات الطبية', 'action' => 'إدارة', 'badge' => 'primary'],

            // 💳 3. الصندوق والمالية
            'view cashier' => ['label' => 'عرض لوحة الكاشير المركزية العامة', 'action' => 'عرض', 'badge' => 'info'],
            'process payments' => ['label' => 'معالجة سندات القبض والدفع العامة', 'action' => 'معالجة', 'badge' => 'success'],
            'view cashier appointments' => ['label' => 'عرض قائمة مواعيد الاستشارية في الكاشير', 'action' => 'عرض', 'badge' => 'info'],
            'process consultation payments' => ['label' => 'قبض كشفية الاستشارية (سند كشفية)', 'action' => 'معالجة', 'badge' => 'success'],
            'view cashier medical requests' => ['label' => 'عرض طلبات الفحوصات الطبية والسونار في الكاشير', 'action' => 'عرض', 'badge' => 'info'],
            'process medical requests payments' => ['label' => 'قبض رسوم فحوصات السونار والأشعة والمختبر', 'action' => 'معالجة', 'badge' => 'success'],
            'view cashier emergency' => ['label' => 'عرض كاشير قسم الطوارئ', 'action' => 'عرض', 'badge' => 'info'],
            'process emergency payments' => ['label' => 'قبض فواتير وخدمات الطوارئ', 'action' => 'معالجة', 'badge' => 'success'],
            'view cashier surgeries' => ['label' => 'عرض كاشير العمليات الجراحية', 'action' => 'عرض', 'badge' => 'info'],
            'process surgery payments' => ['label' => 'قبض وتثبيت دفعات العمليات الجراحية', 'action' => 'معالجة', 'badge' => 'success'],
            'review surgery prices' => ['label' => 'مراجعة وتأكيد أسعار وتكاليف العمليات (محاسب)', 'action' => 'مراجعة', 'badge' => 'warning'],
            'view cashier reports' => ['label' => 'عرض كشوفات الحسابات والتقارير المالية اليومية', 'action' => 'عرض', 'badge' => 'info'],
            'view payments' => ['label' => 'عرض سجل سندات الدفع والقبض', 'action' => 'عرض', 'badge' => 'info'],
            'create payments' => ['label' => 'إنشاء سند مالي يدوي جديد', 'action' => 'إضافة', 'badge' => 'success'],
            'edit payments' => ['label' => 'تعديل بيانات السند المالي', 'action' => 'تعديل', 'badge' => 'warning'],

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
            'view lab test groups' => ['label' => 'عرض مجموعات التحاليل المفضلة', 'action' => 'عرض', 'badge' => 'info'],
            'create lab test groups' => ['label' => 'إنشاء مجموعة تحاليل مفضلة جديدة', 'action' => 'إضافة', 'badge' => 'success'],
            'edit lab test groups' => ['label' => 'تعديل مجموعة تحاليل مفضلة', 'action' => 'تعديل', 'badge' => 'warning'],
            'delete lab test groups' => ['label' => 'حذف مجموعة تحاليل مفضلة', 'action' => 'حذف', 'badge' => 'danger'],
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
        ];
    }

    private function permissionGroup(string $permissionName): string
    {
        // 1. Inquiry & Bookings
        if (str_starts_with($permissionName, 'inquiry.') || in_array($permissionName, [
            'view inquiries', 'create inquiries', 'manage inquiries', 
            'view patient history', 'view occupancy', 
            'view patients', 'create patients', 'edit patients', 'delete patients'
        ])) {
            return 'inquiry';
        }
        
        // 2. Cashier & Finance
        if (str_contains($permissionName, 'cashier') || str_contains($permissionName, 'payment') || $permissionName === 'review surgery prices') {
            return 'cashier_finance';
        }

        // 3. Radiology & Ultrasound & Echo
        if (str_contains($permissionName, 'radiology')) {
            return 'radiology';
        }

        // 4. Laboratory
        if (str_contains($permissionName, 'lab ') || str_contains($permissionName, 'lab_') || str_contains($permissionName, 'package') || $permissionName === 'manage surgery lab tests') {
            return 'lab';
        }

        // 5. Pharmacy & Inventory
        if (str_contains($permissionName, 'pharmacy') || str_contains($permissionName, 'product') || str_contains($permissionName, 'supplier') || 
            str_contains($permissionName, 'purchase') || str_contains($permissionName, 'inventory') || 
            str_starts_with($permissionName, 'view stock') || str_starts_with($permissionName, 'manage location')) {
            return 'pharmacy';
        }

        // 6. Emergency
        if (str_contains($permissionName, 'emergenc')) {
            return 'emergency';
        }

        // 7. Surgeries & Inpatient & Rooms
        if (str_contains($permissionName, 'surg') || str_contains($permissionName, 'station') || $permissionName === 'manage rooms' || str_contains($permissionName, 'device')) {
            return 'surgeries';
        }

        // 8. Outpatient & Consultant & Doctors (Clinics, Appointments, Visits)
        if (str_contains($permissionName, 'consultant') || str_contains($permissionName, 'own visits') || 
            str_contains($permissionName, 'doctor') || str_contains($permissionName, 'department') || 
            str_contains($permissionName, 'appointment') || str_contains($permissionName, 'visit') || 
            str_contains($permissionName, 'referral')) {
            return 'consultant_doctors';
        }

        // 9. System Admin & Settings
        if (str_contains($permissionName, 'user') || str_contains($permissionName, 'role') || str_contains($permissionName, 'permission')) {
            return 'system_admin';
        }

        return 'system_admin';
    }

    // ============ إدارة الصلاحيات ============
    
    public function permissionsIndex()
    {
        $permissions = Permission::withCount('roles')->get()->groupBy(function($permission) {
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
