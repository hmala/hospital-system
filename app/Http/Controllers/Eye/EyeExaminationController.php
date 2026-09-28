<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use App\Models\Eye\EyeExamination;
use App\Models\Eye\EyeInvestigation;
use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EyeExaminationController extends Controller
{
    /**
     * قائمة فحوصات وكشوفات العيون
     */
    public function index(Request $request)
    {
        $query = EyeExamination::with(['patient', 'doctor.user', 'appointment']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('medical_record_number', 'like', "%{$search}%");
            })->orWhere('diagnosis', 'like', "%{$search}%");
        }

        $examinations = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('eye.examinations.eye_examination_index', compact('examinations'));
    }

    /**
     * محطة كشف وفحص العيون السريرية للمريض
     */
    public function create(Request $request)
    {
        $patientId = $request->get('patient_id');
        $appointmentId = $request->get('appointment_id');

        if (!$patientId && !$appointmentId) {
            return redirect()->route('eye.reception.index')
                ->with('error', 'يجب اختيار مريض أو موعد لفتح محطة الفحص السريري.');
        }

        $appointment = $appointmentId ? EyeAppointment::with('patient', 'doctor.user')->find($appointmentId) : null;
        $patient = $appointment ? $appointment->patient : Patient::findOrFail($patientId);

        // سجل الفحوصات والزيارات السابقة للعين للمقارنة التطورية
        $previousExams = EyeExamination::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // فحوصات الأجهزة السابقة (OCT, المجال البصري، السونار)
        $previousInvestigations = EyeInvestigation::where('patient_id', $patient->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // قائمة تحاليل المختبر العام للطلب السريع
        $labTests = LabTest::where('is_active', true)->orderBy('name')->get(['id', 'name', 'price']);

        // أدوية وقطرات العيون الشائعة من الصيدلية
        $eyeMedicines = Medicine::where('is_active', true)
            ->where(function ($q) {
                $q->where('dosage_form', 'like', '%قطر%')
                  ->orWhere('dosage_form', 'like', '%مرهم%')
                  ->orWhere('name', 'like', '%eye%')
                  ->orWhere('name', 'like', '%oph%')
                  ->orWhere('name', 'like', '%moxi%')
                  ->orWhere('name', 'like', '%tobra%');
            })
            ->limit(40)
            ->get();

        return view('eye.examinations.eye_examination_workstation', compact(
            'patient',
            'appointment',
            'previousExams',
            'previousInvestigations',
            'labTests',
            'eyeMedicines'
        ));
    }

    /**
     * حفظ استمارة فحص العيون السريري
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'eye_appointment_id' => 'nullable|exists:eye_appointments,id',
            'diagnosis' => 'nullable|string',
            'management_plan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $examData = $request->except(['_token', 'send_lab_order', 'send_prescription', 'lab_test_ids', 'prescription_items']);

            // ربط الطبيب المعالج
            $doctor = Doctor::where('user_id', Auth::id())->first();
            $examData['doctor_id'] = $doctor ? $doctor->id : null;

            $examination = EyeExamination::create($examData);

            // تحديث حالة الموعد إلى مكتمل
            if ($request->filled('eye_appointment_id')) {
                EyeAppointment::where('id', $request->eye_appointment_id)->update(['status' => 'completed']);
            }

            // إرسال طلبات المختبر إلى مختبر المستشفى العام إن حُددت
            if ($request->boolean('send_lab_order') && $request->filled('lab_test_ids')) {
                foreach ($request->lab_test_ids as $testId) {
                    MedicalRequest::create([
                        'patient_id'     => $examination->patient_id,
                        'doctor_id'      => $examination->doctor_id,
                        'lab_test_id'    => $testId,
                        'request_type'   => 'lab',
                        'status'         => 'pending',
                        'clinical_notes' => 'طلب صادر من عيادة العيون: ' . $examination->diagnosis,
                        'requested_at'   => now(),
                    ]);
                }
            }

            // إرسال وصفة قطرات العيون للصيدلية المركزية إن وُجدت
            if ($request->boolean('send_prescription') && $request->filled('prescription_items')) {
                $rxNumber = 'RX-EYE-' . date('Ymd') . '-' . rand(1000, 9999);
                $prescription = Prescription::create([
                    'prescription_number' => $rxNumber,
                    'patient_id'          => $examination->patient_id,
                    'doctor_id'           => $examination->doctor_id,
                    'status'              => 'pending',
                    'notes'               => 'وصفة علاجية من قسم العيون - التشخيص: ' . $examination->diagnosis,
                    'prescribed_at'       => now(),
                ]);

                foreach ($request->prescription_items as $item) {
                    if (!empty($item['medicine_id'])) {
                        PrescriptionItem::create([
                            'prescription_id'     => $prescription->id,
                            'medicine_id'         => $item['medicine_id'],
                            'dosage_instructions' => $item['dosage_instructions'] ?? 'قطرة كل 4 ساعات',
                            'quantity'            => $item['quantity'] ?? 1,
                            'status'              => 'pending',
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('eye.examinations.show', $examination)
                ->with('success', 'تم حفظ فحص العيون السريري بنجاح وتحديث السجل الطبي.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء حفظ الفحص: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * استعراض فحص عيون بتصميم مقارن
     */
    public function show(EyeExamination $examination)
    {
        $examination->load(['patient', 'doctor.user', 'appointment']);
        return view('eye.examinations.eye_examination_show', compact('examination'));
    }

    /**
     * طباعة راشيتة النظارة الطبية الرسمية (Glasses RX)
     */
    public function printGlasses(EyeExamination $examination)
    {
        $examination->load(['patient', 'doctor.user']);
        return view('eye.examinations.eye_examination_glasses_print', compact('examination'));
    }
}
