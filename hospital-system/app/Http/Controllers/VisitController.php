<?php
// app/Http/Controllers/VisitController.php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Appointment;
use Illuminate\Http\Request;

class VisitController extends Controller
{
    public function index()
    {
        $visits = Visit::with(['patient', 'doctor.user', 'department', 'appointment'])
            ->latest('visit_date')
            ->orderBy('visit_time', 'desc')
            ->paginate(20);

        return view('visits.index', compact('visits'));
    }

    public function create($patientId = null, $appointmentId = null)
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        $selectedPatient = $patientId ? Patient::find($patientId) : null;
        $appointment = $appointmentId ? Appointment::with(['patient.user', 'doctor.user', 'department'])->find($appointmentId) : null;

        // تحديد القيم الافتراضية
        if ($appointment) {
            // إذا جاء من موعد مسبق
            $defaultDate = $appointment->appointment_date->format('Y-m-d');
            $defaultTime = now()->format('H:i'); // وقت افتراضي بدلاً من وقت الموعد
            $defaultDoctor = $appointment->doctor_id;
            $defaultDepartment = $appointment->department_id;
            $defaultComplaint = $appointment->reason;
        } else {
            // إذا كانت زيارة جديدة
            $defaultDate = now()->format('Y-m-d');
            $defaultTime = now()->format('H:i');
            $defaultDoctor = null;
            $defaultDepartment = null;
            $defaultComplaint = null;
        }

        return view('visits.create', compact(
            'patients', 'doctors', 'departments', 
            'selectedPatient', 'appointment',
            'defaultDate', 'defaultTime', 'defaultDoctor', 'defaultDepartment', 'defaultComplaint'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'appointment_id' => 'nullable|exists:appointments,id',
            'visit_date' => 'required|date',
            'visit_time' => 'required|date_format:H:i',
            'visit_type' => 'required|in:checkup,followup,emergency,surgery,lab,radiology',
            'chief_complaint' => 'required|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment' => 'nullable|string|max:1000',
            'prescription' => 'nullable|string|max:1000',
        ]);

        $visitData = [
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $request->department_id,
            'appointment_id' => $request->appointment_id,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'visit_type' => $request->visit_type,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'prescription' => $request->prescription,
            'notes' => $request->notes,
            'vital_signs' => $request->vital_signs ? (is_string($request->vital_signs) ? json_decode($request->vital_signs) : $request->vital_signs) : null,
            'next_visit_date' => $request->next_visit_date,
            'is_completed' => true
        ];

        // إذا كانت الزيارة مرتبطة بموعد، نقوم بتحديث حالة الموعد
        if ($request->appointment_id) {
            $appointment = Appointment::find($request->appointment_id);
            if ($appointment) {
                $appointment->update(['status' => 'completed']);
            }
        }

        Visit::create($visitData);

        return redirect()->route('visits.index')
            ->with('success', 'تم تسجيل الزيارة بنجاح');
    }

    public function show(Visit $visit)
    {
        $visit->load(['patient', 'doctor.user', 'department', 'appointment']);
        return view('visits.show', compact('visit'));
    }

    public function edit(Visit $visit)
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('visits.edit', compact('visit', 'patients', 'doctors', 'departments'));
    }

    public function update(Request $request, Visit $visit)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'visit_date' => 'required|date',
            'visit_time' => 'required|date_format:H:i',
            'visit_type' => 'required|in:checkup,followup,emergency,surgery,lab,radiology',
            'chief_complaint' => 'required|string|max:1000',
            'diagnosis' => 'nullable|string|max:1000',
            'treatment' => 'nullable|string|max:1000',
            'prescription' => 'nullable|string|max:1000',
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
        ]);

        $visit->update([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $request->department_id,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'visit_type' => $request->visit_type,
            'chief_complaint' => $request->chief_complaint,
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'prescription' => $request->prescription,
            'notes' => $request->notes,
            'vital_signs' => $request->vital_signs ? (is_string($request->vital_signs) ? json_decode($request->vital_signs) : $request->vital_signs) : null,
            'next_visit_date' => $request->next_visit_date,
            'status' => $request->status,
        ]);

        // إذا تم إنهاء الزيارة، حدث حالة الموعد المرتبط بها
        if ($request->status === 'completed' && $visit->appointment) {
            $visit->appointment->complete();
        }

        // إذا تم إلغاء الزيارة، ألغِ الموعد المرتبط بها
        if ($request->status === 'cancelled' && $visit->appointment) {
            $visit->appointment->cancel('تم إلغاء الزيارة');
        }

        return redirect()->route('visits.show', $visit)
            ->with('success', 'تم تحديث الزيارة بنجاح');
    }

    public function destroy(Visit $visit)
    {
        $visit->delete();

        return redirect()->route('visits.index')
            ->with('success', 'تم حذف الزيارة بنجاح');
    }
}