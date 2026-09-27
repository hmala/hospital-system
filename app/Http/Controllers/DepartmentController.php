<?php
// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function publicIndex()
    {
        $departments = Department::with(['hospital', 'doctors'])
            ->where('is_active', true)
            ->withCount(['appointments as today_appointments_count' => function($query) {
                $query->whereDate('appointment_date', today());
            }])
            ->latest()
            ->paginate(10);

        return view('departments.public_index', compact('departments'));
    }

    public function index()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view departments'))) {
            abort(403, 'غير مصرح لك بعرض قائمة العيادات والأقسام');
        }

        $departments = Department::with(['hospital', 'doctors'])
            ->withCount(['appointments as today_appointments_count' => function($query) {
                $query->whereDate('appointment_date', today());
            }])
            ->latest()
            ->paginate(10);

        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('create departments'))) {
            abort(403, 'غير مصرح لك بإضافة عيادة أو قسم جديد');
        }

        $hospitals = Hospital::all();
        return view('departments.create', compact('hospitals'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('create departments'))) {
            abort(403, 'غير مصرح لك بإضافة عيادة أو قسم جديد');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'max_patients_per_day' => 'required|integer|min:1',
        ]);

        $hospital = Hospital::first();
        Department::create([
            'hospital_id' => $hospital ? $hospital->id : 1,
            'name' => $request->name,
            'type' => $request->type,
            'room_number' => $request->room_number,
            'consultation_fee' => $request->consultation_fee,
            'moi_price' => $request->moi_price,
            'is_moi_active' => $request->has('is_moi_active'),
            'hi_price' => $request->hi_price,
            'is_hi_active' => $request->has('is_hi_active'),
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
            'max_patients_per_day' => $request->max_patients_per_day,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('departments.admin')
            ->with('success', 'تم إضافة العيادة بنجاح');
    }

    public function show(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('view departments'))) {
            abort(403, 'غير مصرح لك بعرض بيانات العيادة');
        }

        $department->load(['doctors', 'appointments' => function($query) {
            $query->whereDate('appointment_date', today());
        }]);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('edit departments'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات العيادة');
        }

        $hospitals = Hospital::all();
        return view('departments.edit', compact('department', 'hospitals'));
    }

    public function update(Request $request, Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('edit departments'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات العيادة');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_moi_active' => 'nullable|boolean',
            'hi_price' => 'nullable|numeric|min:0',
            'is_hi_active' => 'nullable|boolean',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'max_patients_per_day' => 'required|integer|min:1',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');
        $data['is_moi_active'] = $request->has('is_moi_active');
        $data['is_hi_active'] = $request->has('is_hi_active');

        $department->update($data);

        return redirect()->route('departments.admin')
            ->with('success', 'تم تحديث العيادة بنجاح');
    }

    public function destroy(Department $department)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || !$user->can('delete departments'))) {
            abort(403, 'غير مصرح لك بحذف العيادة');
        }

        $department->delete();

        return redirect()->route('departments.admin')
            ->with('success', 'تم حذف العيادة بنجاح');
    }
}