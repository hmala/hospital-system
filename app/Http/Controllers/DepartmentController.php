<?php
// app/Http/Controllers/DepartmentController.php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Hospital;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
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
        $hospitals = Hospital::all();
        return view('departments.create', compact('hospitals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'max_patients_per_day' => 'required|integer|min:1',
        ]);

        Department::create([
            'hospital_id' => Hospital::first()->id, // نستخدم المستشفى الأول في النظام
            'name' => $request->name,
            'type' => $request->type,
            'room_number' => $request->room_number,
            'consultation_fee' => $request->consultation_fee,
            'working_hours_start' => $request->working_hours_start,
            'working_hours_end' => $request->working_hours_end,
            'max_patients_per_day' => $request->max_patients_per_day,
            'is_active' => $request->has('is_active')
        ]);

        return redirect()->route('departments.index')
            ->with('success', 'تم إضافة العيادة بنجاح');
    }

    public function show(Department $department)
    {
        $department->load(['doctors', 'appointments' => function($query) {
            $query->whereDate('appointment_date', today());
        }]);

        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $hospitals = Hospital::all();
        return view('departments.edit', compact('department', 'hospitals'));
    }

    public function update(Request $request, Department $department)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:internal,surgery,pediatrics,obstetrics,orthopedics,cardiology,dentistry,dermatology,emergency,other',
            'room_number' => 'required|string|max:50',
            'consultation_fee' => 'required|numeric|min:0',
            'working_hours_start' => 'required|date_format:H:i',
            'working_hours_end' => 'required|date_format:H:i|after:working_hours_start',
            'max_patients_per_day' => 'required|integer|min:1',
        ]);

        $department->update($request->all());

        return redirect()->route('departments.index')
            ->with('success', 'تم تحديث العيادة بنجاح');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return redirect()->route('departments.index')
            ->with('success', 'تم حذف العيادة بنجاح');
    }
}