<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeonStation;
use App\Models\Doctor;
use App\Models\SurgeryTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeonStationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // جلب العمليات التي في محطة الجراح (بعد صالة العمليات)
        $query = Surgery::with(['patient.user', 'doctor.user', 'surgeonStation'])
            ->whereHas('operationTheaterStation', function($q) {
                $q->where('status', 'completed');
            })
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time');

        // إذا كان المستخدم طبيباً جراحاً (وليس مشرفاً أو موظف استقبال/عمليات)، نعرض له عملياته فقط
        if ($user->doctor && !$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            $query->where('doctor_id', $user->doctor->id);
        }

        $surgeries = $query->get();

        return view('surgery-stations.surgeon.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        $this->authorizeSurgeon($surgery);

        // التحقق من أن صالة العمليات مكتملة
        if (!$surgery->operationTheaterStation || $surgery->operationTheaterStation->status !== 'completed') {
            return redirect()->route('operation-theater-station.show', $surgery)
                ->with('error', 'يجب إتمام مرحلة صالة العمليات أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'surgeonStation', 'residentStationFollowUps']);

        // جلب الأطباء المقيمين لتعيين أحدهم للمتابعة
        $residents = Doctor::where('type', 'resident')->where('is_active', true)->with('user')->get();

        return view('surgery-stations.surgeon.show', compact('surgery', 'residents'));
    }

    public function residentFollowUps(Surgery $surgery)
    {
        $this->authorizeSurgeon($surgery);

        $surgery->load([
            'patient.user',
            'doctor.user',
            'preOpResidentStation.readings.resident.user',
            'postOpResidentStation.readings.resident.user',
            'residentStationFollowUps',
        ]);

        return view('surgery-stations.surgeon.follow-ups', compact('surgery'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $this->authorizeSurgeon($surgery);

        $validated = $request->validate([
            'resident_assigned_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
            'monitoring_protocol' => 'nullable|string|in:standard,fluid_monitoring,intensive',
            'required_fluids' => 'nullable|array',
            'required_fluids.*' => 'string|in:intake_iv_fluids,intake_oral,intake_blood,output_urine,output_drain,output_gtube_ng,output_vomiting,output_stool',
            
            'prescribed_medications.surgery_treatments' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*' => 'nullable|array',
            'prescribed_medications.surgery_treatments.*.*.description' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.dosage' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.timing' => 'nullable|string',
            'prescribed_medications.surgery_treatments.*.*.duration_value' => 'nullable|integer|min:1',
            'prescribed_medications.surgery_treatments.*.*.duration_unit' => 'nullable|string|in:days,weeks,months,hours,doses',
        ]);

        $station = $surgery->surgeonStation;
        if (!$station) {
            $station = $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update([
            'resident_assigned_id' => $validated['resident_assigned_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'treatment_plan' => $validated['treatment_plan'] ?? null,
            'monitoring_protocol' => $validated['monitoring_protocol'] ?? 'standard',
            'required_fluids' => $validated['required_fluids'] ?? [],
        ]);

        // حفظ العلاجات المهيكلة في جدول surgery_treatments
        $prescribedMedications = $request->input('prescribed_medications');
        if ($prescribedMedications && isset($prescribedMedications['surgery_treatments'][$surgery->id])) {
            $surgery->surgeryTreatments()->delete();
            $treatments = $prescribedMedications['surgery_treatments'][$surgery->id];
            foreach ($treatments as $index => $treatment) {
                if (!empty($treatment['description'])) {
                    SurgeryTreatment::create([
                        'surgery_id' => $surgery->id,
                        'description' => $treatment['description'],
                        'dosage' => $treatment['dosage'] ?? null,
                        'timing' => $treatment['timing'] ?? null,
                        'duration_value' => $treatment['duration_value'] ?? null,
                        'duration_unit' => $treatment['duration_unit'] ?? null,
                        'sort_order' => $index,
                    ]);
                }
            }
        }

        return redirect()->route('surgeon-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة الجراح بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $this->authorizeSurgeon($surgery);

        $station = $surgery->surgeonStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة التخدير التالية
        if (!$surgery->anesthesiaStation) {
            $surgery->anesthesiaStation()->create([
                'anesthesiologist_id' => $surgery->operationTheaterStation?->anesthesiologist_id,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('surgeon-station.index')
            ->with('success', 'تم إتمام محطة الجراح والانتقال لمحطة التخدير');
    }

    private function authorizeSurgeon(Surgery $surgery)
    {
        $user = auth()->user();
        if ($user->doctor && !$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            if ($surgery->doctor_id !== $user->doctor->id) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه العملية الجراحية');
            }
        }
    }
}
