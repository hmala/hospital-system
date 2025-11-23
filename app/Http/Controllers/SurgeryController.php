<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\LabTest;
use App\Models\RadiologyType;
use Illuminate\Http\Request;

class SurgeryController extends Controller
{
    public function index()
    {
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(20);
        return view('surgeries.index', compact('surgeries'));
    }

    public function create()
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        return view('surgeries.create', compact('patients', 'doctors', 'departments', 'labTests', 'radiologyTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'surgery_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'referral_source' => 'required|in:internal,external',
            'external_doctor_name' => 'nullable|string|max:255',
            'external_hospital_name' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $surgery = Surgery::create($request->except(['lab_tests', 'radiology_tests']));

        // إنشاء التحاليل المخبرية المطلوبة
        if ($request->has('lab_tests') && is_array($request->lab_tests)) {
            foreach ($request->lab_tests as $labTestId) {
                $surgery->labTests()->create([
                    'lab_test_id' => $labTestId,
                    'status' => 'pending'
                ]);
            }
        }

        // إنشاء الأشعة المطلوبة
        if ($request->has('radiology_tests') && is_array($request->radiology_tests)) {
            foreach ($request->radiology_tests as $radiologyTypeId) {
                $surgery->radiologyTests()->create([
                    'radiology_type_id' => $radiologyTypeId,
                    'status' => 'pending'
                ]);
            }
        }

        return redirect()->route('surgeries.index')->with('success', 'تم حجز العملية بنجاح');
    }

    public function show(Surgery $surgery)
    {
        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType']);
        return view('surgeries.show', compact('surgery'));
    }

    public function edit(Surgery $surgery)
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        return view('surgeries.edit', compact('surgery', 'patients', 'doctors', 'departments', 'labTests', 'radiologyTypes'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'surgery_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required',
            'status' => 'required|in:scheduled,waiting,in_progress,completed,cancelled',
            'referral_source' => 'required|in:internal,external',
            'external_doctor_name' => 'nullable|string|max:255',
            'external_hospital_name' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'post_op_notes' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $surgery->update($request->except(['lab_tests', 'radiology_tests']));

        // حذف التحاليل القديمة وإضافة الجديدة
        $surgery->labTests()->delete();
        if ($request->has('lab_tests') && is_array($request->lab_tests)) {
            foreach ($request->lab_tests as $labTestId) {
                $surgery->labTests()->create([
                    'lab_test_id' => $labTestId,
                    'status' => 'pending'
                ]);
            }
        }

        // حذف الأشعة القديمة وإضافة الجديدة
        $surgery->radiologyTests()->delete();
        if ($request->has('radiology_tests') && is_array($request->radiology_tests)) {
            foreach ($request->radiology_tests as $radiologyTypeId) {
                $surgery->radiologyTests()->create([
                    'radiology_type_id' => $radiologyTypeId,
                    'status' => 'pending'
                ]);
            }
        }

        return redirect()->route('surgeries.show', $surgery)->with('success', 'تم تحديث العملية بنجاح');
    }

    public function destroy(Surgery $surgery)
    {
        $surgery->delete();
        return redirect()->route('surgeries.index')->with('success', 'تم حذف العملية');
    }

    public function waitingList()
    {
        $waitingSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->whereDate('scheduled_date', now())
            ->whereIn('status', ['scheduled', 'waiting'])
            ->orderBy('scheduled_time', 'asc')
            ->get();

        $inProgressSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->where('status', 'in_progress')
            ->get();
            
        return view('surgeries.waiting', compact('waitingSurgeries', 'inProgressSurgeries'));
    }

    public function controlPanel()
    {
        $waitingSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->whereDate('scheduled_date', now())
            ->whereIn('status', ['scheduled', 'waiting'])
            ->orderBy('scheduled_time', 'asc')
            ->get();

        $inProgressSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->where('status', 'in_progress')
            ->get();
            
        return view('surgeries.control', compact('waitingSurgeries', 'inProgressSurgeries'));
    }

    public function checkIn(Surgery $surgery)
    {
        $surgery->status = 'waiting';
        $surgery->save();
        return redirect()->back()->with('success', 'تم تسجيل دخول المريض لقائمة الانتظار');
    }

    public function start(Surgery $surgery)
    {
        $surgery->status = 'in_progress';
        $surgery->save();
        return redirect()->back()->with('success', 'تم بدء العملية');
    }

    public function complete(Surgery $surgery)
    {
        $surgery->status = 'completed';
        $surgery->save();
        return redirect()->back()->with('success', 'تم إكمال العملية');
    }

    public function cancel(Surgery $surgery)
    {
        $surgery->status = 'cancelled';
        $surgery->save();
        return redirect()->back()->with('success', 'تم إلغاء العملية');
    }

    public function returnToWaiting(Surgery $surgery)
    {
        $surgery->status = 'waiting';
        $surgery->save();
        return redirect()->back()->with('success', 'تم إعادة العملية إلى قائمة الانتظار');
    }
}
