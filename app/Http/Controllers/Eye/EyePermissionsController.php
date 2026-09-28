<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class EyePermissionsController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get all eye center permissions
        $eyePermissions = Permission::where('name', 'like', '%eye%')->get();
        
        $search = $request->get('search');
        
        $users = User::with(['roles', 'permissions'])
            ->when($search, function($q) use ($search) {
                $q->where('name', 'like', "%{\$search}%")
                  ->orWhere('email', 'like', "%{\$search}%");
            })
            ->where('role', '!=', 'patient')
            ->paginate(50);

        return view('eye.permissions.index', compact('users', 'eyePermissions', 'search'));
    }

    public function update(Request $request, User $user)
    {
        // Allowed eye permissions
        $eyePermissionNames = Permission::where('name', 'like', '%eye%')->pluck('name')->toArray();
        
        $permissionsToSync = [];
        
        // Keep non-eye permissions exactly as they are
        $currentNonEyePermissions = $user->permissions->filter(function($p) use ($eyePermissionNames) {
            return !in_array($p->name, $eyePermissionNames);
        })->pluck('name')->toArray();
        
        // Add requested eye permissions
        if ($request->has('permissions') && is_array($request->permissions)) {
            foreach ($request->permissions as $permName) {
                if (in_array($permName, $eyePermissionNames)) {
                    $permissionsToSync[] = $permName;
                }
            }
        }
        
        // Merge them
        $finalPermissions = array_merge($currentNonEyePermissions, $permissionsToSync);
        
        $user->syncPermissions($finalPermissions);
        
        return back()->with('success', 'تم تحديث صلاحيات العيون للمستخدم ' . $user->name . ' بنجاح');
    }
}
