<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeryTreatment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\LabTest;
use App\Models\RadiologyType;
use App\Events\SurgeryUpdated;
use Illuminate\Http\Request;

class SurgeryController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'receptionist', 'doctor', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // العمليات النشطة (المجدولة والمنتظرة والجارية)
        $activeSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'surgeryTreatments', 'radiologyTests', 'labTests'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress']);

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً
        if ($user->hasRole('doctor') && $user->doctor) {
            $activeSurgeriesQuery->where('doctor_id', $user->doctor->id);
        }

        // تطبيق البحث والفلترة
        if (request('search')) {
            $search = request('search');
            $activeSurgeriesQuery->where(function($query) use ($search) {
                $query->whereHas('patient.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('doctor.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        if (request('status') && request('status') !== '') {
            $activeSurgeriesQuery->where('status', request('status'));
        }

        if (request('date')) {
            $activeSurgeriesQuery->whereDate('scheduled_date', request('date'));
        }

        $activeSurgeries = $activeSurgeriesQuery->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(20)
            ->appends(request()->query());

        // العمليات المكتملة والملغاة
        $completedSurgeriesQuery = Surgery::with(['patient.user', 'doctor.user', 'department', 'surgeryTreatments', 'radiologyTests', 'labTests'])
            ->whereIn('status', ['completed', 'cancelled']);

        // فلترة حسب الطبيب إذا كان المستخدم طبيباً
        if ($user->hasRole('doctor') && $user->doctor) {
            $completedSurgeriesQuery->where('doctor_id', $user->doctor->id);
        }

        if (request('search')) {
            $search = request('search');
            $completedSurgeriesQuery->where(function($query) use ($search) {
                $query->whereHas('patient.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('doctor.user', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%');
                })
                ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        if (request('status') && in_array(request('status'), ['completed', 'cancelled'])) {
            $completedSurgeriesQuery->where('status', request('status'));
        }

        if (request('date')) {
            $completedSurgeriesQuery->whereDate('scheduled_date', request('date'));
        }

        $completedSurgeries = $completedSurgeriesQuery->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->paginate(20)
            ->appends(request()->query());

        return view('surgeries.index', compact('activeSurgeries', 'completedSurgeries'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإنشاء عمليات جراحية');
        }

        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->where('specialization', 'جراحة')->get();
        $departments = Department::where('is_active', true)->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        return view('surgeries.create', compact('patients', 'doctors', 'departments', 'labTests', 'radiologyTypes'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإنشاء عمليات جراحية');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'surgery_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'referral_source' => 'required|in:internal,external',
            'external_doctor_name' => 'nullable|string|max:255',
            'external_hospital_name' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'referring_physician' => 'nullable|string|max:255',
            'anesthesia_type' => 'nullable|string|max:255',
            'surgery_classification' => 'nullable|string|max:255',
            'supplies' => 'nullable|string',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $surgeryData = $request->except(['lab_tests', 'radiology_tests']);
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time);

        $surgery = Surgery::create($surgeryData);

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

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.index')->with('success', 'تم حجز العملية بنجاح');
    }

    public function show(Surgery $surgery)
    {
        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user', 'visit.labResults', 'visit.radiologyRequests.radiologyType', 'visit.radiologyRequests.result', 'surgeryTreatments']);
        return view('surgeries.show', compact('surgery'));
    }

    public function edit(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor'])) {
            abort(403, 'غير مصرح لك بتعديل العمليات الجراحية');
        }

        $surgery->load(['patient.user', 'doctor.user', 'department', 'visit', 'labTests.labTest', 'radiologyTests.radiologyType', 'anesthesiologist.user', 'anesthesiologist2.user']);
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();
        $labTests = LabTest::active()->orderBy('name')->get();
        $radiologyTypes = RadiologyType::active()->orderBy('name')->get();
        return view('surgeries.edit', compact('surgery', 'patients', 'doctors', 'departments', 'labTests', 'radiologyTypes'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist', 'doctor'])) {
            abort(403, 'غير مصرح لك بتعديل العمليات الجراحية');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'surgery_type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'status' => 'required|in:scheduled,waiting,in_progress,completed,cancelled',
            'referral_source' => 'required|in:internal,external',
            'external_doctor_name' => 'nullable|string|max:255',
            'external_hospital_name' => 'nullable|string|max:255',
            'referral_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'post_op_notes' => 'nullable|string',
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'referring_physician' => 'nullable|string|max:255',
            'anesthesia_type' => 'nullable|string|max:255',
            'surgery_classification' => 'nullable|string|max:255',
            'supplies' => 'nullable|string',
            'surgery_category' => 'nullable|string|in:elective,emergency,urgent,semi_urgent',
            'surgery_type_detail' => 'nullable|string|in:diagnostic,therapeutic,preventive,cosmetic,reconstructive,palliative',
            'anesthesia_position' => 'nullable|string|in:supine,prone,lateral,lithotomy,fowler,trendelenburg,sitting,other',
            'asa_classification' => 'nullable|string|in:asa1,asa2,asa3,asa4,asa5,asa6',
            'surgical_complexity' => 'nullable|string|in:minor,intermediate,major,complex',
            'surgical_notes' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date|after:today',
            'lab_tests' => 'nullable|array',
            'lab_tests.*' => 'exists:lab_tests,id',
            'radiology_tests' => 'nullable|array',
            'radiology_tests.*' => 'exists:radiology_types,id',
        ]);

        $surgeryData = $request->except(['lab_tests', 'radiology_tests']);
        $surgeryData['scheduled_time'] = \Carbon\Carbon::createFromFormat('Y-m-d H:i', $request->scheduled_date . ' ' . $request->scheduled_time);

        $surgery->update($surgeryData);

        // تحديث التحاليل المخبرية
        $oldLabTestIds = $surgery->labTests->pluck('lab_test_id')->toArray();
        $newLabTestIds = $request->has('lab_tests') ? $request->lab_tests : [];

        // حذف التحاليل التي أزيلت
        foreach ($surgery->labTests as $labTest) {
            if (!in_array($labTest->lab_test_id, $newLabTestIds)) {
                $labTest->delete();
            }
        }
        // إضافة التحاليل الجديدة فقط
        foreach ($newLabTestIds as $labTestId) {
            if (!in_array($labTestId, $oldLabTestIds)) {
                $surgery->labTests()->create([
                    'lab_test_id' => $labTestId,
                    'status' => 'pending'
                ]);
            }
        }

        // تحديث الأشعة
        $oldRadiologyTypeIds = $surgery->radiologyTests->pluck('radiology_type_id')->toArray();
        $newRadiologyTypeIds = $request->has('radiology_tests') ? $request->radiology_tests : [];

        // حذف الأشعة التي أزيلت
        foreach ($surgery->radiologyTests as $radiologyTest) {
            if (!in_array($radiologyTest->radiology_type_id, $newRadiologyTypeIds)) {
                $radiologyTest->delete();
            }
        }
        // إضافة الأشعة الجديدة فقط
        foreach ($newRadiologyTypeIds as $radiologyTypeId) {
            if (!in_array($radiologyTypeId, $oldRadiologyTypeIds)) {
                $surgery->radiologyTests()->create([
                    'radiology_type_id' => $radiologyTypeId,
                    'status' => 'pending'
                ]);
            }
        }

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->route('surgeries.show', $surgery)->with('success', 'تم تحديث العملية بنجاح');
    }

    public function destroy(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بحذف العمليات الجراحية');
        }

        $surgery->delete();
        return redirect()->route('surgeries.index')->with('success', 'تم حذف العملية');
    }

    public function waitingList()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى قائمة الانتظار');
        }

        $waitingSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->get();

        $inProgressSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->where('status', 'in_progress')
            ->get();
            
        return view('surgeries.waiting', compact('waitingSurgeries', 'inProgressSurgeries'));
    }

    public function controlPanel()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بالوصول إلى لوحة التحكم');
        }

        $waitingSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('scheduled_date', 'asc')
            ->orderBy('scheduled_time', 'asc')
            ->get();

        $inProgressSurgeries = Surgery::with(['patient.user', 'doctor.user', 'department'])
            ->where('status', 'in_progress')
            ->get();
            
        return view('surgeries.control', compact('waitingSurgeries', 'inProgressSurgeries'));
    }

    public function checkIn(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بتسجيل دخول المريض');
        }

        $surgery->status = 'waiting';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم تسجيل دخول المريض لقائمة الانتظار');
    }

    public function start(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك ببدء العملية');
        }

        $surgery->status = 'in_progress';
        $surgery->started_at = now();
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم بدء العملية في ' . now()->format('H:i'));
    }

    public function complete(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإكمال العملية');
        }

        $surgery->status = 'completed';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إكمال العملية');
    }

    public function cancel(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإلغاء العملية');
        }

        $surgery->status = 'cancelled';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إلغاء العملية');
    }

    public function returnToWaiting(Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'surgery_staff', 'receptionist'])) {
            abort(403, 'غير مصرح لك بإعادة العملية إلى الانتظار');
        }

        $surgery->status = 'waiting';
        $surgery->save();

        broadcast(new SurgeryUpdated($surgery));

        return redirect()->back()->with('success', 'تم إعادة العملية إلى قائمة الانتظار');
    }

    public function updateDetails(Request $request, Surgery $surgery)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'doctor', 'surgery_staff'])) {
            abort(403, 'غير مصرح لك بتحديث تفاصيل العملية');
        }

        \Log::info('=== Update Surgery Details Started ===');
        \Log::info('Surgery ID: ' . $surgery->id);
        \Log::info('Request data:', $request->all());
        \Log::info('Request method: ' . $request->method());
        \Log::info('User: ' . $user->name . ' (' . $user->id . ')');

        // Log specific fields
        \Log::info('Diagnosis from request: ' . $request->input('diagnosis'));
        \Log::info('Anesthesia type from request: ' . $request->input('anesthesia_type'));
        \Log::info('Start time from request: ' . $request->input('start_time'));
        \Log::info('End time from request: ' . $request->input('end_time'));

        $validated = $request->validate([
            'diagnosis' => 'nullable|string|max:1000',
            'anesthesia_type' => 'nullable|string|in:local,regional,general,sedation',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'supplies' => 'nullable|string|max:1000',
            'start_time' => 'nullable|string',
            'end_time' => 'nullable|string',
            'estimated_duration' => 'nullable',
            'estimated_duration_minutes' => 'nullable|integer|min:1',
            'post_op_notes' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
            'prescribed_medications' => 'nullable|array',
            'prescribed_medications.surgery_treatments' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*.description' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.dosage' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.timing' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.duration_value' => 'nullable|integer|min:1',
            'prescribed_medications.surgery_treatments.*.*.duration_unit' => 'nullable|string|in:days,weeks,months,hours,doses',
            'surgery_category' => 'nullable|string|in:elective,emergency,urgent,semi_urgent',
            'surgery_type_detail' => 'nullable|string|in:diagnostic,therapeutic,preventive,cosmetic,reconstructive,palliative',
            'anesthesia_position' => 'nullable|string|in:supine,prone,lateral,lithotomy,fowler,trendelenburg,sitting,other',
            'asa_classification' => 'nullable|string|in:asa1,asa2,asa3,asa4,asa5,asa6',
            'surgical_complexity' => 'nullable|string|in:minor,intermediate,major,complex',
            'surgical_notes' => 'nullable|string|max:1000',
        ]);

        \Log::info('Validation passed, validated data: ', $validated);

        // Validate time formats manually
        if (!empty($validated['start_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['start_time'])) {
            return redirect()->back()->withErrors(['start_time' => 'وقت البدء غير صحيح']);
        }
        if (!empty($validated['end_time']) && !preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $validated['end_time'])) {
            return redirect()->back()->withErrors(['end_time' => 'وقت الانتهاء غير صحيح']);
        }

        // Convert time fields to full datetime using surgery's scheduled date
        if (isset($validated['start_time']) && !empty($validated['start_time'])) {
            $validated['start_time'] = $surgery->scheduled_date->format('Y-m-d') . ' ' . $validated['start_time'] . ':00';
        } else {
            $validated['start_time'] = null;
        }
        if (isset($validated['end_time']) && !empty($validated['end_time'])) {
            $validated['end_time'] = $surgery->scheduled_date->format('Y-m-d') . ' ' . $validated['end_time'] . ':00';
        } else {
            $validated['end_time'] = null;
        }

        // Convert follow_up_date to Carbon instance
        if (isset($validated['follow_up_date']) && !empty($validated['follow_up_date'])) {
            $validated['follow_up_date'] = \Carbon\Carbon::parse($validated['follow_up_date']);
        } else {
            $validated['follow_up_date'] = null;
        }

        // Handle estimated duration - use minutes if available, otherwise convert from HH:MM format
        if (isset($validated['estimated_duration_minutes'])) {
            $validated['estimated_duration'] = $validated['estimated_duration_minutes'];
            unset($validated['estimated_duration_minutes']);
        } elseif (isset($validated['estimated_duration']) && !empty($validated['estimated_duration'])) {
            // Check if it's already in minutes (number) or in HH:MM format
            if (is_numeric($validated['estimated_duration'])) {
                // Already in minutes, keep it as is
                $validated['estimated_duration'] = (int)$validated['estimated_duration'];
            } elseif (strpos($validated['estimated_duration'], ':') !== false) {
                // Convert HH:MM to minutes
                list($hours, $minutes) = explode(':', $validated['estimated_duration']);
                $validated['estimated_duration'] = ($hours * 60) + $minutes;
            }
        } else {
            unset($validated['estimated_duration']);
        }

        // Handle prescribed_medications - save surgery treatments to separate table
        \Log::info('=== Surgery Update Details Debug ===');
        \Log::info('Raw request data:', $request->all());
        \Log::info('Request method: ' . $request->method());
        \Log::info('Prescribed medications from request: ' . json_encode($request->input('prescribed_medications')));
        \Log::info('===================================');

        // Check if we have surgery treatments to save
        $prescribedMedications = $request->input('prescribed_medications');
        
        if ($prescribedMedications && isset($prescribedMedications['surgery_treatments'][$surgery->id])) {
            \Log::info('Surgery treatments for surgery ' . $surgery->id . ': ' . json_encode($prescribedMedications['surgery_treatments'][$surgery->id]));

            // Delete existing treatments
            $surgery->surgeryTreatments()->delete();

            // Save new treatments
            $treatments = $prescribedMedications['surgery_treatments'][$surgery->id];
            \Log::info('Treatments array: ' . json_encode($treatments));
            foreach ($treatments as $index => $treatment) {
                \Log::info('Processing treatment ' . $index . ': ' . json_encode($treatment));
                if (!empty($treatment['description'])) {
                    \Log::info('Creating treatment for index ' . $index);
                    SurgeryTreatment::create([
                        'surgery_id' => $surgery->id,
                        'description' => $treatment['description'],
                        'dosage' => $treatment['dosage'] ?? null,
                        'timing' => $treatment['timing'] ?? null,
                        'duration_value' => $treatment['duration_value'] ?? null,
                        'duration_unit' => $treatment['duration_unit'] ?? null,
                        'sort_order' => $index,
                    ]);
                    \Log::info('Treatment created successfully');
                } else {
                    \Log::info('Skipping treatment ' . $index . ' - empty description');
                }
            }
        } else {
            \Log::info('No surgery treatments found in request');
        }

        // Remove prescribed_medications from validated array as we handled it separately
        unset($validated['prescribed_medications']);

        \Log::info('Final validated data to update: ', $validated);

        try {
            $surgery->update($validated);
            \Log::info('Surgery updated successfully, new data: ', $surgery->toArray());
        } catch (\Exception $e) {
            \Log::error('Error updating surgery: ' . $e->getMessage());
            return redirect()->back()->with('error', 'حدث خطأ في حفظ البيانات: ' . $e->getMessage());
        }

        return redirect()->route('surgeries.index')->with('success', 'تم حفظ تفاصيل العملية بنجاح');
    }
}
