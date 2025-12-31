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
        $this->middleware('role:admin');
    }

    // ============ إدارة الأدوار ============
    
    public function rolesIndex()
    {
        $roles = Role::withCount('users', 'permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function rolesCreate()
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return end($parts);
        });
        return view('roles.create', compact('permissions'));
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

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('roles.index')
            ->with('success', 'تم إضافة الدور بنجاح');
    }

    public function rolesEdit(Role $role)
    {
        $permissions = Permission::all()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return end($parts);
        });
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function rolesUpdate(Request $request, Role $role)
    {
        $request->validate([
            'display_name' => 'required|string',
            'permissions' => 'array',
        ], [
            'display_name.required' => 'الاسم المعروض مطلوب',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('roles.index')
            ->with('success', 'تم تحديث الدور بنجاح');
    }

    public function rolesDestroy(Role $role)
    {
        if ($role->users()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف دور مرتبط بمستخدمين');
        }

        $role->delete();

        return redirect()->route('roles.index')
            ->with('success', 'تم حذف الدور بنجاح');
    }

    // ============ إدارة الصلاحيات ============
    
    public function permissionsIndex()
    {
        $permissions = Permission::withCount('roles')->get()->groupBy(function($permission) {
            $parts = explode(' ', $permission->name);
            return end($parts);
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
