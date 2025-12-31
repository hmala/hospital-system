<?php

namespace App\Http\Controllers;

use App\Models\RadiologyRequest;
use App\Models\RadiologyType;
use App\Models\Patient;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RadiologyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of radiology requests.
     */
    public function index()
    {
        $user = Auth::user();

        if ($user->hasAnyRole(['admin', 'receptionist'])) {
            // الإداريون والاستقبال يرون جميع الطلبات
            $requests = RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType'])
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('doctor')) {
            // الأطباء يرون طلباتهم فقط
            $requests = RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType'])
                ->where('doctor_id', $user->doctor->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('patient')) {
            // المرضى يرون طلباتهم فقط
            $requests = RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType'])
                ->where('patient_id', $user->patient->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);
        } elseif ($user->hasRole('radiology_staff')) {
            // موظفو الإشعة يرون جميع الطلبات
            $requests = RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType'])
                ->whereIn('status', ['pending', 'scheduled', 'in_progress', 'completed'])
                ->orderBy('priority', 'desc')
                ->orderBy('requested_date', 'desc')
                ->paginate(15);
        } else {
            $requests = collect();
        }

        return view('radiology.index', compact('requests'));
    }

    /**
     * Show the form for creating a new radiology request.
     */
    public function create(Request $request)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->can('create radiology')) {
            abort(403, 'غير مصرح لك بإنشاء طلبات إشعة');
        }

        $radiologyTypes = RadiologyType::active()->get();
        $doctors = Doctor::with('user')->get();

        // إذا كان الطلب من زيارة معينة
        $visitId = $request->get('visit_id');
        $patientId = $request->get('patient_id');

        if ($visitId) {
            $visit = \App\Models\Visit::with(['patient.user', 'doctor.user'])->findOrFail($visitId);
            $patient = $visit->patient;
            $doctor = $visit->doctor;
        } elseif ($patientId) {
            $patient = Patient::with('user')->findOrFail($patientId);
            $doctor = null;
        } else {
            $patient = null;
            $doctor = null;
        }

        return view('radiology.create', compact('radiologyTypes', 'doctors', 'patient', 'doctor', 'visitId'));
    }

    /**
     * Store a newly created radiology request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'radiology_type_id' => 'required|exists:radiology_types,id',
            'visit_id' => 'nullable|exists:visits,id',
            'priority' => 'required|in:normal,urgent,emergency',
            'clinical_indication' => 'required|string|max:1000',
            'specific_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'requested_date' => 'required|date|after:now'
        ]);

        $radiologyType = RadiologyType::findOrFail($request->radiology_type_id);

        RadiologyRequest::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'radiology_type_id' => $request->radiology_type_id,
            'visit_id' => $request->visit_id,
            'requested_date' => $request->requested_date,
            'priority' => $request->priority,
            'status' => 'pending',
            'clinical_indication' => $request->clinical_indication,
            'specific_instructions' => $request->specific_instructions,
            'notes' => $request->notes,
            'total_cost' => $radiologyType->base_price
        ]);

        return redirect()->route('radiology.index')->with('success', 'تم إنشاء طلب الإشعة بنجاح');
    }

    /**
     * Display the specified radiology request.
     */
    public function show(RadiologyRequest $radiology)
    {
        $radiology->load(['patient.user', 'doctor.user', 'radiologyType', 'visit', 'performer', 'result.radiologist']);

        return view('radiology.show', compact('radiology'));
    }

    /**
     * Show the form for editing the radiology request.
     */
    public function edit(RadiologyRequest $radiology)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->can('edit radiology')) {
            abort(403, 'غير مصرح لك بتعديل هذا الطلب');
        }

        // الأطباء يعدلون طلباتهم فقط
        if ($user->hasRole('doctor') && $radiology->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بتعديل هذا الطلب');
        }

        if ($radiology->status !== 'pending') {
            return redirect()->route('radiology.show', $radiology)->with('error', 'لا يمكن تعديل طلب تم جدولته أو تنفيذه');
        }

        $radiologyTypes = RadiologyType::active()->get();
        $doctors = Doctor::with('user')->get();

        return view('radiology.edit', compact('radiology', 'radiologyTypes', 'doctors'));
    }

    /**
     * Update the specified radiology request.
     */
    public function update(Request $request, RadiologyRequest $radiology)
    {
        $request->validate([
            'radiology_type_id' => 'required|exists:radiology_types,id',
            'doctor_id' => 'required|exists:doctors,id',
            'priority' => 'required|in:normal,urgent,emergency',
            'clinical_indication' => 'required|string|max:1000',
            'specific_instructions' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
            'requested_date' => 'required|date'
        ]);

        if ($radiology->status !== 'pending') {
            return redirect()->route('radiology.show', $radiology)->with('error', 'لا يمكن تعديل طلب تم جدولته');
        }

        $radiologyType = RadiologyType::findOrFail($request->radiology_type_id);

        $radiology->update([
            'radiology_type_id' => $request->radiology_type_id,
            'doctor_id' => $request->doctor_id,
            'priority' => $request->priority,
            'clinical_indication' => $request->clinical_indication,
            'specific_instructions' => $request->specific_instructions,
            'notes' => $request->notes,
            'total_cost' => $radiologyType->base_price
        ]);

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم تحديث طلب الإشعة بنجاح');
    }

    /**
     * Remove the specified radiology request.
     */
    public function destroy(RadiologyRequest $radiology)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->can('delete radiology')) {
            abort(403, 'غير مصرح لك بحذف هذا الطلب');
        }

        // الأطباء يحذفون طلباتهم فقط
        if ($user->hasRole('doctor') && $radiology->doctor_id !== $user->doctor->id) {
            abort(403, 'غير مصرح لك بحذف هذا الطلب');
        }

        if ($radiology->status !== 'pending') {
            return redirect()->route('radiology.show', $radiology)->with('error', 'لا يمكن حذف طلب تم جدولته');
        }

        $radiology->delete();

        return redirect()->route('radiology.index')->with('success', 'تم حذف طلب الإشعة بنجاح');
    }

    /**
     * Schedule a radiology request.
     */
    public function schedule(Request $request, RadiologyRequest $radiology)
    {
        $request->validate([
            'scheduled_date' => 'required|date|after:now'
        ]);

        $radiology->schedule($request->scheduled_date);

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم جدولة طلب الإشعة بنجاح');
    }

    /**
     * Start performing the radiology procedure.
     */
    public function startProcedure(RadiologyRequest $radiology)
    {
        $user = Auth::user();

        if (!$user->hasRole('radiology_staff')) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }

        if (!$radiology->canBePerformed()) {
            return redirect()->route('radiology.show', $radiology)->with('error', 'لا يمكن بدء الإجراء في الوقت الحالي');
        }

        $radiology->startProcedure($user->id);

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم بدء إجراء الإشعة');
    }

    /**
     * Complete the radiology request.
     */
    public function complete(RadiologyRequest $radiology)
    {
        $user = Auth::user();

        if (!$user->hasRole('radiology_staff')) {
            abort(403, 'غير مصرح لك بتنفيذ هذا الإجراء');
        }

        $radiology->complete();

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم إكمال طلب الإشعة');
    }

    /**
     * Cancel the radiology request.
     */
    public function cancel(Request $request, RadiologyRequest $radiology)
    {
        $request->validate([
            'cancellation_reason' => 'required|string|max:500'
        ]);

        $radiology->update([
            'status' => 'cancelled',
            'notes' => ($radiology->notes ? $radiology->notes . "\n" : "") . "سبب الإلغاء: " . $request->cancellation_reason
        ]);

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم إلغاء طلب الإشعة');
    }

    /**
     * Save radiology results with images.
     */
    public function saveResults(Request $request, RadiologyRequest $radiology)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->hasRole('radiology_staff')) {
            abort(403, 'غير مصرح لك بحفظ نتائج الأشعة');
        }

        $request->validate([
            'findings' => 'required|string|max:2000',
            'impression' => 'required|string|max:1000',
            'recommendations' => 'nullable|string|max:1000',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,pdf,dcm|max:10240', // 10MB max per file
            'is_preliminary' => 'nullable|boolean'
        ]);

        // رفع الصور
        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('radiology/results', 'public');
                $imagePaths[] = $path;
            }
        }

        // دمج الصور القديمة مع الجديدة
        if ($radiology->result && $radiology->result->images) {
            $imagePaths = array_merge($radiology->result->images, $imagePaths);
        }

        // حفظ أو تحديث النتائج
        if ($radiology->result) {
            $radiology->result->update([
                'findings' => $request->findings,
                'impression' => $request->impression,
                'recommendations' => $request->recommendations,
                'images' => $imagePaths,
                'is_preliminary' => $request->has('is_preliminary'),
                'reported_at' => now()
            ]);
        } else {
            \App\Models\RadiologyResult::create([
                'radiology_request_id' => $radiology->id,
                'radiologist_id' => $user->id,
                'findings' => $request->findings,
                'impression' => $request->impression,
                'recommendations' => $request->recommendations,
                'images' => $imagePaths,
                'is_preliminary' => $request->has('is_preliminary'),
                'reported_at' => now()
            ]);
        }

        // تحديث حالة الطلب إلى مكتمل إذا لم يكن كذلك
        if ($radiology->status !== 'completed') {
            $radiology->update(['status' => 'completed']);
        }

        // تحديث حالة الطلب في جدول requests أيضاً
        if ($radiology->visit_id) {
            $medicalRequest = \App\Models\Request::where('visit_id', $radiology->visit_id)
                ->where('type', 'radiology')
                ->first();
            
            if ($medicalRequest) {
                $medicalRequest->status = 'completed';
                $medicalRequest->result = [
                    'findings' => $request->findings,
                    'impression' => $request->impression,
                    'recommendations' => $request->recommendations,
                    'images' => $imagePaths,
                    'radiologist' => $user->name,
                    'reported_at' => now()->format('Y-m-d H:i:s')
                ];
                // تحديث الوصف ليعكس اكتمال النتائج
                $medicalRequest->description = 'طلب أشعة - نتائج جاهزة';
                $medicalRequest->save();
            }
        }

        return redirect()->route('radiology.show', $radiology)->with('success', 'تم حفظ نتائج الأشعة بنجاح');
    }

    /**
     * Print radiology result
     */
    public function print(RadiologyRequest $radiology)
    {
        $radiology->load(['patient.user', 'doctor.user', 'radiologyType', 'result.radiologist']);
        
        return view('radiology.print', compact('radiology'));
    }
}
