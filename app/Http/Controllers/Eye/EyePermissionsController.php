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
        $eyePermissionNames = $eyePermissions->pluck('name')->toArray();
        
        $search = $request->get('search');
        
        $usersQuery = User::with(['roles', 'permissions'])
            ->where('role', '!=', 'patient');

        if ($search) {
            // If searching, allow finding any hospital staff member
            $usersQuery->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        } else {
            // Default view: ONLY show users who already have eye permissions OR are Eye Doctors
            $usersQuery->where(function($q) use ($eyePermissionNames) {
                // Has eye permission
                $q->whereHas('permissions', function($pq) use ($eyePermissionNames) {
                    $pq->whereIn('name', $eyePermissionNames);
                })
                // OR is an eye doctor
                ->orWhereHas('doctor', function($dq) {
                    $dq->whereHas('department', function($ddq) {
                        $ddq->where('name', 'like', '%عيون%')->orWhere('name', 'like', '%Eye%');
                    })
                    ->orWhere('specialization', 'like', '%شبكية%')
                    ->orWhere('specialization', 'like', '%قرنية%')
                    ->orWhere('specialization', 'like', '%فاكو%')
                    ->orWhere('specialization', 'like', '%جلوكوما%');
                });
            });
        }
        
        $users = $usersQuery->paginate(50);

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
