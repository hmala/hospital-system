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
    public function index(Request $request)
    {
        $requestedMatrix = $request->query('matrix') ?? session('active_matrix');
        $activeMatrix = in_array($requestedMatrix, ['clinical', 'detail'], true) ? $requestedMatrix : 'clinical';

        $tabs = SurgeryTabPermission::TABS;
        $detailTabs = SurgeryTabPermission::DETAIL_TABS;
        $clinicalTabKeys = array_keys($tabs);
        $detailTabKeys = array_keys($detailTabs);

        $coreClinicalRoles = SurgeryTabPermission::CORE_CLINICAL_ROLES;
        $coreDetailRoles = SurgeryTabPermission::CORE_DETAIL_ROLES;

        // Fetch current stored permissions grouped by role_name and tab_key
        $allPermissions = SurgeryTabPermission::all();
        $currentPermissions = $allPermissions->groupBy('role_name')->map(function ($items) {
            return $items->pluck('access_level', 'tab_key')->toArray();
        })->toArray();

        $extraClinicalRoles = $allPermissions
            ->whereIn('tab_key', $clinicalTabKeys)
            ->pluck('role_name')
            ->unique()
            ->reject(fn (string $r) => in_array($r, $coreClinicalRoles, true) || in_array($r, SurgeryTabPermission::EXCLUDED_CONFIGURABLE_ROLES, true))
            ->values()
            ->all();

        $extraDetailRoles = $allPermissions
            ->whereIn('tab_key', $detailTabKeys)
            ->pluck('role_name')
            ->unique()
            ->reject(fn (string $r) => in_array($r, $coreDetailRoles, true) || in_array($r, SurgeryTabPermission::EXCLUDED_CONFIGURABLE_ROLES, true))
            ->values()
            ->all();

        // Keep 'admin' as final fixed row in both tables
        $clinicalRoles = array_values(array_unique(array_merge(
            array_values(array_diff($coreClinicalRoles, ['admin'])),
            $extraClinicalRoles,
            ['admin']
        )));

        $detailRoles = array_values(array_unique(array_merge(
            array_values(array_diff($coreDetailRoles, ['admin'])),
            $extraDetailRoles,
            ['admin']
        )));

        $roleLabels = [];
        foreach ($clinicalRoles as $roleKey) {
            $roleLabels[$roleKey] = SurgeryTabPermission::knownRoleLabel($roleKey);
        }

        $detailRoleLabels = [];
        foreach ($detailRoles as $roleKey) {
            $detailRoleLabels[$roleKey] = SurgeryTabPermission::knownRoleLabel($roleKey);
        }

        // Load all selectable Spatie roles (e.g., NICU, emergency_staff, lab_staff, radiology_staff...)
        $allSystemRoles = Role::orderBy('name')
            ->pluck('name')
            ->reject(fn (string $r) => in_array($r, SurgeryTabPermission::EXCLUDED_CONFIGURABLE_ROLES, true))
            ->values();

        $availableClinicalRoles = $allSystemRoles
            ->reject(fn (string $r) => in_array($r, $clinicalRoles, true))
            ->mapWithKeys(fn (string $r) => [$r => SurgeryTabPermission::knownRoleLabel($r)])
            ->all();

        $availableDetailRoles = $allSystemRoles
            ->reject(fn (string $r) => in_array($r, $detailRoles, true))
            ->mapWithKeys(fn (string $r) => [$r => SurgeryTabPermission::knownRoleLabel($r)])
            ->all();

        // Defaults fallback
        $defaultMatrix = SurgeryTabPermission::defaultMatrix();
        $detailDefaultMatrix = SurgeryTabPermission::detailDefaultMatrix();

        return view('surgeries.settings.tabs', compact(
            'roleLabels',
            'detailRoleLabels',
            'clinicalRoles',
            'detailRoles',
            'coreClinicalRoles',
            'coreDetailRoles',
            'availableClinicalRoles',
            'availableDetailRoles',
            'tabs',
            'detailTabs',
            'currentPermissions',
            'defaultMatrix',
            'detailDefaultMatrix',
            'activeMatrix'
        ));
    }

    /**
     * Add an extra system role/station to one of the tab permission matrices.
     */
    public function addRole(Request $request)
    {
        $validated = $request->validate([
            'matrix_type' => 'required|in:clinical,detail',
            'role_name' => 'required|string|max:50|exists:roles,name',
            'default_level' => 'nullable|in:hidden,read,write',
        ]);

        $roleName = $validated['role_name'];
        if (in_array($roleName, SurgeryTabPermission::EXCLUDED_CONFIGURABLE_ROLES, true)) {
            return redirect()->route('surgery-stations.tab-settings.index')
                ->with('active_matrix', $validated['matrix_type'])
                ->with('error', 'لا يمكن إضافة هذا الدور يدوياً لأن صلاحياته ثابتة أو معرّفة ضمنياً.');
        }

        $level = $validated['default_level'] ?? SurgeryTabPermission::ACCESS_READ;
        $tabKeys = $validated['matrix_type'] === 'detail'
            ? array_keys(SurgeryTabPermission::DETAIL_TABS)
            : array_keys(SurgeryTabPermission::TABS);

        foreach ($tabKeys as $tabKey) {
            SurgeryTabPermission::firstOrCreate(
                ['role_name' => $roleName, 'tab_key' => $tabKey],
                ['access_level' => $level]
            );
        }

        $roleLabel = SurgeryTabPermission::knownRoleLabel($roleName);

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'add_surgery_tab_role',
                "تمت إضافة الدور/المحطة ({$roleLabel}) إلى مصفوفة صلاحيات التبويبات",
                'surgery_tab_permissions',
                null
            );
        }

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('active_matrix', $validated['matrix_type'])
            ->with('success', "تمت إضافة «{$roleLabel}» بنجاح. يمكنك الآن تخصيص صلاحيات تبويباته وحفظ المصفوفة.");
    }

    /**
     * Remove a custom added role/station from one of the tab permission matrices.
     */
    public function removeRole(Request $request)
    {
        $validated = $request->validate([
            'matrix_type' => 'required|in:clinical,detail',
            'role_name' => 'required|string|max:50',
        ]);

        $roleName = $validated['role_name'];
        $coreRoles = $validated['matrix_type'] === 'detail'
            ? SurgeryTabPermission::CORE_DETAIL_ROLES
            : SurgeryTabPermission::CORE_CLINICAL_ROLES;

        if (in_array($roleName, $coreRoles, true)) {
            return redirect()->route('surgery-stations.tab-settings.index')
                ->with('active_matrix', $validated['matrix_type'])
                ->with('error', 'لا يمكن حذف الأدوار السريرية الأساسية من المصفوفة.');
        }

        $tabKeys = $validated['matrix_type'] === 'detail'
            ? array_keys(SurgeryTabPermission::DETAIL_TABS)
            : array_keys(SurgeryTabPermission::TABS);

        SurgeryTabPermission::where('role_name', $roleName)
            ->whereIn('tab_key', $tabKeys)
            ->delete();

        $roleLabel = SurgeryTabPermission::knownRoleLabel($roleName);

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'remove_surgery_tab_role',
                "تمت إزالة الدور/المحطة ({$roleLabel}) من مصفوفة صلاحيات التبويبات",
                'surgery_tab_permissions',
                null
            );
        }

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('active_matrix', $validated['matrix_type'])
            ->with('success', "تمت إزالة «{$roleLabel}» من المصفوفة بنجاح.");
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

        $tabKeys = array_merge(
            array_keys(SurgeryTabPermission::TABS),
            array_keys(SurgeryTabPermission::DETAIL_TABS)
        );
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

        $activeMatrix = in_array($request->input('active_matrix'), ['clinical', 'detail'], true)
            ? $request->input('active_matrix')
            : 'clinical';

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('active_matrix', $activeMatrix)
            ->with('success', 'تم حفظ مصفوفة صلاحيات التبويبات بنجاح وانعكست على الواجهات السريرية.');
    }

    /**
     * Reset permissions to clinical standard defaults.
     */
    public function resetDefaults(Request $request)
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

        $activeMatrix = in_array($request->input('active_matrix'), ['clinical', 'detail'], true)
            ? $request->input('active_matrix')
            : 'clinical';

        return redirect()->route('surgery-stations.tab-settings.index')
            ->with('active_matrix', $activeMatrix)
            ->with('success', 'تمت استعادة الضبط الطبي الافتراضي لمصفوفة التبويبات بنجاح.');
    }
}
