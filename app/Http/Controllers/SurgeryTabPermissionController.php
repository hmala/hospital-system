<?php

namespace App\Http\Controllers;

use App\Models\SurgeryTabPermission;
use App\Services\UserActivityLogger;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class SurgeryTabPermissionController extends Controller
{
    /**
     * Display the Tab Permissions Matrix.
     */
    public function index()
    {
        // Load target clinical roles
        $targetRoleNames = ['resident', 'nurse', 'doctor', 'surgery_coordinator', 'surgery_staff', 'admin'];
        $roles = Role::whereIn('name', $targetRoleNames)->get();

        // If some roles missing, provide standard objects
        $roleLabels = [
            'resident' => 'الطبيب المقيم (Resident)',
            'nurse' => 'كادر التمريض (Nurse)',
            'doctor' => 'الطبيب الجراح / الأخصائي (Surgeon/Doctor)',
            'surgery_coordinator' => 'منسق العمليات (Surgery Coordinator)',
            'surgery_staff' => 'كادر العمليات (Surgery Staff)',
            'admin' => 'مدير النظام (Administrator)',
        ];

        $tabs = SurgeryTabPermission::TABS;

        // Fetch current stored permissions grouped by role_name and tab_key
        $currentPermissions = SurgeryTabPermission::all()->groupBy('role_name')->map(function ($items) {
            return $items->pluck('access_level', 'tab_key')->toArray();
        })->toArray();

        // Defaults fallback
        $defaultMatrix = SurgeryTabPermission::defaultMatrix();

        return view('surgeries.settings.tabs', compact('roles', 'roleLabels', 'tabs', 'currentPermissions', 'defaultMatrix'));
    }

    /**
     * Update the Tab Permissions Matrix.
     */
    public function update(Request $request)
    {
        $permissionsData = $request->input('permissions', []);

        if (! is_array($permissionsData)) {
            return redirect()->back()->with('error', 'بيانات غير صالحة.');
        }

        $validLevels = [
            SurgeryTabPermission::ACCESS_HIDDEN,
            SurgeryTabPermission::ACCESS_READ,
            SurgeryTabPermission::ACCESS_WRITE,
        ];

        $tabKeys = array_keys(SurgeryTabPermission::TABS);
        $updatedCount = 0;

        foreach ($permissionsData as $roleName => $tabs) {
            if (! is_array($tabs)) {
                continue;
            }

            foreach ($tabs as $tabKey => $level) {
                if (in_array($tabKey, $tabKeys) && in_array($level, $validLevels)) {
                    SurgeryTabPermission::updateOrCreate(
                        ['role_name' => $roleName, 'tab_key' => $tabKey],
                        ['access_level' => $level]
                    );
                    $updatedCount++;
                }
            }
        }

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'update_surgery_tab_permissions',
                "تم تحديث مصفوفة صلاحيات تبويبات العمليات الجراحية ({$updatedCount} خاصية)",
                'surgery_tab_permissions',
                null
            );
        }

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('success', 'تم حفظ مصفوفة صلاحيات التبويبات بنجاح وانعكست على الواجهات السريرية.');
    }

    /**
     * Reset permissions to clinical standard defaults.
     */
    public function resetDefaults()
    {
        SurgeryTabPermission::truncate();
        SurgeryTabPermission::seedDefaults();

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'reset_surgery_tab_permissions',
                'تمت استعادة الضبط الافتراضي لمصفوفة صلاحيات تبويبات العمليات',
                'surgery_tab_permissions',
                null
            );
        }

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('success', 'تمت استعادة الضبط الطبي الافتراضي لمصفوفة التبويبات بنجاح.');
    }
}
