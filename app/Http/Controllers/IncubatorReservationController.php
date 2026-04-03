<?php

namespace App\Http\Controllers;

use App\Models\IncubatorReservation;
use App\Models\Incubator;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;
use Carbon\Carbon;

class IncubatorReservationController extends Controller
{
    /**
     * عرض قائمة حجوزات الحاضنات
     */
    public function index()
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $reservations = IncubatorReservation::with([
                            'patient.user',
                            'incubator',
                            'doctor.user',
                            'department'
                        ])
                        ->latest()
                        ->paginate(20);

        return view('incubator_reservations.index', compact('reservations'));
    }

    /**
     * عرض نموذج إنشاء حجز جديد
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        // الحصول على المريض إذا تم تمريره
        $patient = null;
        $existingReservation = null;

        if ($request->has('patient_id')) {
            $patient = Patient::with('user')->findOrFail($request->patient_id);

            // التحقق من عمر المريض - الحاضنات للخدج فقط (أقل من سنة)
            if ($patient->age >= 1) {
                return redirect()->back()
                               ->with('error', 'عذراً، حاضنات الخدج مخصصة للأطفال حديثي الولادة فقط (أقل من سنة)');
            }

            // تحقق سريع: هل للطفل حجز نشط بالفعل؟ (حتى نظهر تحذير فور دخول الصفحة)
            $existingReservation = IncubatorReservation::where('patient_id', $patient->id)
                ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
                ->with('incubator')
                ->first();
        }

        // فلترة المرضى - إظهار الأطفال فقط (أقل من سنة)
        $patients = Patient::with('user')
                          ->get()
                          ->filter(function ($p) {
                              return $p->age !== null && $p->age < 1;
                          });

        // اختيار نوع الحاضنة كمحدد للفرز
        $selectedType = $request->input('incubator_type');
        $incubatorTypes = [
            Incubator::TYPE_NORMAL => 'حاضنة عادية',
            Incubator::TYPE_OXYGEN => 'حاضنة + أكسجين',
            Incubator::TYPE_PHOTOTHERAPY => 'حاضنة + علاج ضوئي',
        ];

        // إذا تم تمرير حاضنة مسبقاً، نستخدم نوعها كتحديد افتراضي
        if (!$selectedType && $request->has('incubator_id')) {
            $selectedIncubator = Incubator::find($request->incubator_id);
            if ($selectedIncubator) {
                $selectedType = $selectedIncubator->incubator_type;
            }
        }

        $incubatorsQuery = Incubator::available();
        if ($selectedType) {
            $incubatorsQuery->ofType($selectedType);
        }

        $incubators = $incubatorsQuery->orderBy('incubator_number')->get();
        $doctors = Doctor::with('user')->get();
        $departments = Department::where('is_active', true)->get();

        return view('incubator_reservations.create', compact(
            'patient',
            'patients',
            'incubators',
            'doctors',
            'departments',
            'selectedType',
            'incubatorTypes',
            'existingReservation'
        ));
    }

    /**
     * حفظ حجز جديد
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'incubator_id' => 'required|exists:incubators,id',
            'incubator_type' => 'nullable|in:normal,oxygen,phototherapy',
            'admission_date' => 'required|date',
            'admission_time' => 'required|date_format:H:i',
        ]);

        // سحب اسم الطفل تلقائياً من بيانات المريض
        $patientUser = Patient::with('user')->findOrFail($validated['patient_id']);
        $validated['baby_name'] = optional($patientUser->user)->name ?? 'غير معروف';

        // إضافة القيم الافتراضية للحقول الاختيارية
        $validated['doctor_id'] = null;
        $validated['department_id'] = null;
        $validated['birth_weight'] = null;
        $validated['gestational_age'] = null;
        $validated['medical_notes'] = null;
        $validated['admission_notes'] = null;

        // التحقق من عمر المريض
        if ($patientUser->age >= 1) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'عذراً، حاضنات الخدج مخصصة للأطفال حديثي الولادة فقط (أقل من سنة)');
        }

        // التحقق من أن الطفل ليس لديه حجز نشط حالياً
        $existingPatientReservation = IncubatorReservation::where('patient_id', $validated['patient_id'])
            ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
            ->first();

        if ($existingPatientReservation) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'هذا الطفل لديه حجز نشط بالفعل في الحاضنة رقم ' . $existingPatientReservation->incubator->incubator_number . '. يجب إنهاء الحجز الحالي أولاً.');
        }

        // التحقق من توفر الحاضنة
        $incubator = Incubator::findOrFail($validated['incubator_id']);
        if (!$incubator->isAvailable()) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'الحاضنة المحددة غير متاحة حالياً');
        }

        // التحقق من عدم وجود حجوزات نشطة للحاضنة
        $existingIncubatorReservation = IncubatorReservation::where('incubator_id', $validated['incubator_id'])
            ->whereIn('status', [IncubatorReservation::STATUS_PENDING, IncubatorReservation::STATUS_ADMITTED])
            ->first();

        if ($existingIncubatorReservation) {
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'الحاضنة رقم ' . $incubator->incubator_number . ' محجوزة بالفعل للطفل ' . $existingIncubatorReservation->baby_name . '. يرجى اختيار حاضنة أخرى.');
        }

        // إنشاء الحجز
        $reservation = IncubatorReservation::create($validated);

        // حساب التكلفة المتوقعة
        $reservation->update([
            'total_cost' => $reservation->calculateExpectedCost()
        ]);

        return redirect()->route('incubator-reservations.index')
                        ->with('success', 'تم حجز الحاضنة بنجاح');
    }

    /**
     * عرض تفاصيل حجز معين
     */
    public function show(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $incubatorReservation->load([
            'patient.user',
            'incubator.room',
            'doctor.user',
            'department'
        ]);

        return view('incubator_reservations.show', compact('incubatorReservation'));
    }

    /**
     * تأكيد دخول الطفل للحاضنة
     */
    public function admit(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'nicu_staff'])) {
            abort(403);
        }

        if ($incubatorReservation->status !== IncubatorReservation::STATUS_PENDING) {
            return redirect()->back()
                           ->with('error', 'الحجز ليس في حالة الانتظار');
        }

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_ADMITTED,
            'admission_date' => now()->toDateString(),
            'admission_time' => now()->format('H:i'),
        ]);

        return redirect()->back()
                        ->with('success', 'تم تسجيل دخول الطفل للحاضنة بنجاح');
    }

    /**
     * تسجيل خروج الطفل من الحاضنة
     */
    public function discharge(Request $request, IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        if ($incubatorReservation->status !== IncubatorReservation::STATUS_ADMITTED) {
            return redirect()->back()
                           ->with('error', 'الطفل ليس داخل الحاضنة حالياً');
        }

        $validated = $request->validate([
            'discharge_notes' => 'nullable|string',
        ]);

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_DISCHARGED,
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_notes' => $validated['discharge_notes'] ?? null,
            'total_cost' => $incubatorReservation->calculateActualCost(),
        ]);

        return redirect()->back()
                        ->with('success', 'تم تسجيل خروج الطفل من الحاضنة بنجاح');
    }

    /**
     * إلغاء حجز
     */
    public function cancel(IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'nicu_staff'])) {
            abort(403);
        }

        if (in_array($incubatorReservation->status, [
            IncubatorReservation::STATUS_DISCHARGED,
            IncubatorReservation::STATUS_CANCELLED
        ])) {
            return redirect()->back()
                           ->with('error', 'لا يمكن إلغاء هذا الحجز');
        }

        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_CANCELLED
        ]);

        return redirect()->back()
                        ->with('success', 'تم إلغاء الحجز بنجاح');
    }

    /**
     * نقل الطفل إلى حاضنة أخرى
     */
    public function transfer(Request $request, IncubatorReservation $incubatorReservation)
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $validated = $request->validate([
            'new_incubator_id' => 'required|exists:incubators,id',
            'transfer_reason' => 'nullable|string',
        ]);

        $newIncubator = Incubator::findOrFail($validated['new_incubator_id']);
        
        if (!$newIncubator->isAvailable()) {
            return redirect()->back()
                           ->with('error', 'الحاضنة الجديدة غير متاحة');
        }

        // تحديث الحجز القديم
        $incubatorReservation->update([
            'status' => IncubatorReservation::STATUS_TRANSFERRED,
            'discharge_date' => now()->toDateString(),
            'discharge_time' => now()->format('H:i'),
            'discharge_notes' => 'تم النقل إلى حاضنة رقم ' . $newIncubator->incubator_number . 
                               ': ' . ($validated['transfer_reason'] ?? ''),
        ]);

        // إنشاء حجز جديد
        $newReservation = IncubatorReservation::create([
            'patient_id' => $incubatorReservation->patient_id,
            'baby_name' => $incubatorReservation->baby_name,
            'incubator_id' => $validated['new_incubator_id'],
            'doctor_id' => $incubatorReservation->doctor_id,
            'department_id' => $incubatorReservation->department_id,
            'admission_date' => now()->toDateString(),
            'admission_time' => now()->format('H:i'),
            'expected_duration' => $incubatorReservation->expected_duration,
            'birth_weight' => $incubatorReservation->birth_weight,
            'gestational_age' => $incubatorReservation->gestational_age,
            'medical_notes' => $incubatorReservation->medical_notes,
            'admission_notes' => 'منقول من حاضنة رقم ' . $incubatorReservation->incubator->incubator_number,
            'status' => IncubatorReservation::STATUS_ADMITTED,
        ]);

        return redirect()->route('incubator-reservations.show', $newReservation)
                        ->with('success', 'تم نقل الطفل إلى الحاضنة الجديدة بنجاح');
    }

    /**
     * عرض الحاضنات المشغولة حالياً
     */
    public function occupied()
    {
        $user = auth()->user();
        
        if (!$user->hasAnyRole(['admin', 'receptionist', 'doctor', 'nicu_staff'])) {
            abort(403);
        }

        $activeReservations = IncubatorReservation::with([
                                'patient.user',
                                'incubator',
                                'doctor.user'
                            ])
                            ->active()
                            ->latest()
                            ->get();

        return view('incubator_reservations.occupied', compact('activeReservations'));
    }
}
