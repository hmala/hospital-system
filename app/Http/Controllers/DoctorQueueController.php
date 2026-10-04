<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorQueueController extends Controller
{
    /**
     * Show unified live queue display screen with dynamic doctor selection
     */
    public function liveDisplay(Request $request)
    {
        $today = today();
        $doctors = Doctor::with(['user', 'department'])
            ->where('is_active', true)
            ->where('type', 'consultant')
            ->get();

        $doctorsList = $this->getDoctorsListWithCounts($doctors, $today);

        // Check if doctor is requested via parameter, or if auth user is a doctor
        $doctorId = $request->query('doctor_id') ?? $request->query('doctor');
        if (!$doctorId && auth()->check() && auth()->user()->doctor) {
            $doctorId = auth()->user()->doctor->id;
        }

        $doctor = null;
        if ($doctorId) {
            $doctor = Doctor::with(['user', 'department'])->find($doctorId);
        }

        return view('queue.doctor-display', compact('doctor', 'doctorsList'));
    }

    /**
     * Show external TV queue display screen for a specific doctor
     */
    public function display($doctorId)
    {
        $doctor = Doctor::with(['user', 'department'])->findOrFail($doctorId);
        $today = today();
        $doctors = Doctor::with(['user', 'department'])
            ->where('is_active', true)
            ->where('type', 'consultant')
            ->get();
        $doctorsList = $this->getDoctorsListWithCounts($doctors, $today);

        return view('queue.doctor-display', compact('doctor', 'doctorsList'));
    }

    /**
     * Return JSON list of available doctors with today's queue counts
     */
    public function availableDoctorsList(Request $request)
    {
        $today = today();
        $doctors = Doctor::with(['user', 'department'])
            ->where('is_active', true)
            ->where('type', 'consultant')
            ->get();

        $doctorsList = $this->getDoctorsListWithCounts($doctors, $today);

        return response()->json([
            'ok' => true,
            'doctors' => $doctorsList,
        ]);
    }

    /**
     * Helper to compute queue counts and availability for doctors list
     */
    protected function getDoctorsListWithCounts($doctors, $today)
    {
        return $doctors->map(function ($doc) use ($today) {
            $waitingCount = Appointment::where('doctor_id', $doc->id)
                ->whereDate('appointment_date', $today)
                ->where(function ($q) {
                    $q->where('payment_status', 'paid')
                      ->orWhereNotNull('emergency_id');
                })
                ->whereDoesntHave('visit')
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->count();

            $callingCount = Appointment::where('doctor_id', $doc->id)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', ['calling', 'in_consultation'])
                ->count();

            // Direct check for today's active consultant availability
            $isAvailableToday = (bool) ($doc->is_available_today && (!$doc->available_date || $doc->available_date->isToday()));

            return [
                'id' => $doc->id,
                'name' => optional($doc->user)->name ?? 'طبيب #' . $doc->id,
                'specialization' => $doc->specialization ?? 'استشاري',
                'department' => optional($doc->department)->name ?? 'العيادات الاستشارية',
                'type' => $doc->type ?? 'consultant',
                'is_available_today' => $isAvailableToday,
                'waiting_count' => $waitingCount,
                'calling_count' => $callingCount,
            ];
        })
        ->sortByDesc(function ($doc) {
            // Sort: Available Consultants first -> Then doctors with active waiting list -> Then by ID
            return ($doc['is_available_today'] ? 1000 : 0) 
                 + ($doc['waiting_count'] > 0 ? 500 : 0) 
                 + ($doc['calling_count'] > 0 ? 200 : 0);
        })
        ->values();
    }

    /**
     * Show central TV display for all active clinics in waiting hall
     */
    public function allClinicsDisplay()
    {
        return view('queue.all-clinics-display');
    }

    /**
     * Realtime JSON data for a specific doctor's queue screen
     */
    public function queueData($doctorId)
    {
        $doctor = Doctor::with(['user', 'department'])->findOrFail($doctorId);

        // Auto-assign queue numbers for today's eligible appointments if missing
        $this->ensureQueueNumbersAssigned($doctor->id);

        $today = today();

        // 1. Current Patient being called or currently in room
        $currentPatient = Appointment::with(['patient.user', 'emergency', 'visit.radiologyRequests', 'visit.requests'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->whereIn('status', ['calling', 'in_consultation'])
            ->orderByRaw("CASE WHEN status = 'calling' THEN 1 WHEN status = 'in_consultation' THEN 2 ELSE 3 END")
            ->orderBy('called_at', 'desc')
            ->first();

        // 2. Waiting Queue List (Excluded any patient already entered to doctor with a visit)
        $waitingList = Appointment::with(['patient.user', 'emergency'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhereNotNull('emergency_id');
            })
            ->whereDoesntHave('visit')
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderByRaw("CASE WHEN emergency_id IS NOT NULL THEN 0 ELSE 1 END")
            ->orderBy('queue_number', 'asc')
            ->orderBy('id', 'asc')
            ->limit(10)
            ->get();

        // 3. Patients Waiting for Lab/Radiology Test Results & Pharmacy Dispensing
        $pendingTestsVisits = Visit::with([
            'patient.user',
            'appointment',
            'radiologyRequests.radiologyType',
            'requests',
            'prescriptions.items.medicine',
            'prescriptions.items.suggestedMedicine',
        ])
        ->where('doctor_id', $doctor->id)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->where(function($q) use ($today) {
            $q->whereDate('visit_date', $today)
              ->orWhereDate('updated_at', $today)
              ->orWhereHas('requests', function($rq) use ($today) {
                  $rq->whereDate('created_at', $today);
              })
              ->orWhereHas('radiologyRequests', function($rq) use ($today) {
                  $rq->whereDate('created_at', $today);
              });
        })
        ->where(function($q) {
            $q->whereHas('radiologyRequests')
              ->orWhereHas('requests', function($rq) {
                  $rq->whereIn('type', ['lab', 'radiology']);
              })
              ->orWhereHas('prescriptions');
        })
        ->latest('updated_at')
        ->get();

        $formattedPendingTests = $pendingTestsVisits->map(function($v) {
            $pUser = $v->patient ? $v->patient->user : null;
            $pName = $pUser ? $pUser->name : 'مريض';
            $qNum = $v->appointment ? $v->appointment->queue_number : $v->id;

            $radRequests = $v->radiologyRequests;
            $medicalRadRequests = $v->requests->where('type', 'radiology');
            $labRequests = $v->requests->where('type', 'lab');
            $prescriptions = $v->prescriptions;

            $testsList = [];
            $totalTests = 0;
            $completedCount = 0;
            $hasSubstitutionAlert = false;
            $substitutionsList = [];

            foreach ($radRequests as $rr) {
                $typeName = $rr->radiologyType ? $rr->radiologyType->name : 'أشعة';
                $isReady = ($rr->status === 'completed');
                $totalTests++;
                if ($isReady) $completedCount++;

                $testsList[] = [
                    'type' => 'radiology',
                    'name' => $typeName,
                    'status' => $rr->status,
                    'is_ready' => $isReady
                ];
            }

            foreach ($medicalRadRequests as $mr) {
                $isReady = ($mr->status === 'completed');
                $totalTests++;
                if ($isReady) $completedCount++;

                $testsList[] = [
                    'type' => 'radiology',
                    'name' => $mr->description ?: 'فحص أشعة / سونار',
                    'status' => $mr->status,
                    'payment_status' => $mr->payment_status,
                    'is_ready' => $isReady
                ];
            }

            foreach ($labRequests as $lr) {
                $isReady = ($lr->status === 'completed');
                $details = is_array($lr->details) ? $lr->details : (json_decode($lr->details, true) ?: []);
                $tests = $details['tests'] ?? [];

                if (!empty($tests) && is_array($tests)) {
                    foreach ($tests as $tName) {
                        $totalTests++;
                        if ($isReady) $completedCount++;
                        $testsList[] = [
                            'type' => 'lab',
                            'name' => $tName,
                            'status' => $lr->status,
                            'is_ready' => $isReady
                        ];
                    }
                } else {
                    $totalTests++;
                    if ($isReady) $completedCount++;
                    $testsList[] = [
                        'type' => 'lab',
                        'name' => $lr->description ?: 'تحليل مختبر',
                        'status' => $lr->status,
                        'is_ready' => $isReady
                    ];
                }
            }

            foreach ($prescriptions as $rx) {
                $isDispensed = in_array($rx->status, ['dispensed', 'partially_dispensed']);
                $totalTests++;
                if ($isDispensed) $completedCount++;

                foreach ($rx->items as $item) {
                    if ($item->substitution_status === 'pending_approval') {
                        $hasSubstitutionAlert = true;
                        $substitutionsList[] = [
                            'item_id' => $item->id,
                            'original' => $item->medicine?->name ?? $item->medicine_name,
                            'suggested' => $item->suggestedMedicine?->name ?? 'بديل مقترح',
                            'reason' => $item->substitution_reason ?? '',
                        ];
                    }
                }

                $testsList[] = [
                    'type' => 'pharmacy',
                    'name' => 'صيدلية: ' . $rx->status_text,
                    'prescription_number' => $rx->prescription_number,
                    'status' => $rx->status,
                    'status_text' => $rx->status_text,
                    'is_ready' => $isDispensed,
                    'has_sub' => $hasSubstitutionAlert,
                ];
            }

            $hasUnpaidRequests = $v->requests->whereIn('type', ['lab', 'radiology'])->where('payment_status', '!=', 'paid')->count() > 0;
            $isUnpaidAppointment = ($v->appointment && $v->appointment->payment_status !== 'paid' && !$v->appointment->emergency_id);
            $hasUnpaidTests = $hasUnpaidRequests || $isUnpaidAppointment;

            $allReady = ($totalTests > 0) && ($completedCount === $totalTests);

            return [
                'visit_id' => $v->id,
                'patient_name' => $pName,
                'queue_number' => $qNum,
                'all_ready' => $allReady,
                'has_unpaid_tests' => $hasUnpaidTests,
                'has_substitution_alert' => $hasSubstitutionAlert,
                'substitutions' => $substitutionsList,
                'total_tests' => $totalTests,
                'completed_tests' => $completedCount,
                'tests' => $testsList,
                'updated_at' => $v->updated_at ? $v->updated_at->format('H:i') : ''
            ];
        });

        // 4. Stats for today
        $totalToday = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('payment_status', 'paid')->orWhereNotNull('emergency_id');
            })
            ->count();

        $completedCount = Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'completed')
            ->count();

        $waitingCount = $waitingList->count();

        $formattedCurrent = null;
        if ($currentPatient) {
            $patientUser = $currentPatient->patient ? $currentPatient->patient->user : null;
            $name = $patientUser ? $patientUser->name : ($currentPatient->emergency && $currentPatient->emergency->emergencyPatient ? $currentPatient->emergency->emergencyPatient->name : 'مريض مجهول');
            $hasVisitTests = $currentPatient->visit && ($currentPatient->visit->radiologyRequests->count() > 0 || $currentPatient->visit->requests->count() > 0);
            
            $formattedCurrent = [
                'id' => $currentPatient->id,
                'queue_number' => $currentPatient->queue_number ?: $currentPatient->id,
                'name' => $name,
                'status' => $currentPatient->status,
                'status_text' => $currentPatient->status_text,
                'is_emergency' => (bool)$currentPatient->emergency_id,
                'is_result_review' => (bool)$hasVisitTests,
                'called_at' => $currentPatient->called_at ? $currentPatient->called_at->toIso8601String() : null,
                'called_at_timestamp' => $currentPatient->called_at ? $currentPatient->called_at->timestamp : null,
                'call_key' => $currentPatient->id . '_' . ($currentPatient->called_at ? $currentPatient->called_at->format('YmdHis') : '0') . '_' . $currentPatient->status,
            ];
        }

        $formattedWaiting = $waitingList->map(function($apt) {
            $patientUser = $apt->patient ? $apt->patient->user : null;
            $name = $patientUser ? $patientUser->name : ($apt->emergency && $apt->emergency->emergencyPatient ? $apt->emergency->emergencyPatient->name : 'مريض مجهول');
            
            return [
                'id' => $apt->id,
                'queue_number' => $apt->queue_number ?: $apt->id,
                'name' => $name,
                'is_emergency' => (bool)$apt->emergency_id,
                'status' => $apt->status,
                'status_text' => $apt->status_text,
            ];
        });

        return response()->json([
            'success' => true,
            'doctor' => [
                'id' => $doctor->id,
                'name' => $doctor->user ? $doctor->user->name : 'الطبيب',
                'specialization' => $doctor->specialization ?: 'طبيب استشاري',
                'department' => $doctor->department ? $doctor->department->name : 'العيادات الاستشارية',
                'room' => $doctor->room_number ?? 'العيادة',
            ],
            'current_patient' => $formattedCurrent,
            'waiting_list' => $formattedWaiting,
            'pending_tests_list' => $formattedPendingTests,
            'stats' => [
                'total_today' => $totalToday,
                'waiting_count' => $waitingCount,
                'pending_tests_count' => $formattedPendingTests->count(),
                'completed_count' => $completedCount,
            ],
            'server_time' => now()->format('H:i:s'),
            'server_date' => now()->translatedFormat('l d F Y'),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Realtime JSON data for ALL clinics (Central waiting hall screen)
     */
    public function allClinicsData()
    {
        $today = today();
        $daysMap = [
            'Saturday' => 'السبت',
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
        ];
        $todayArabicDay = $daysMap[date('l')] ?? 'السبت';

        $doctors = Doctor::with(['user', 'department'])
            ->where('type', 'consultant')
            ->where('is_active', true)
            ->workingOnDay($todayArabicDay)
            ->where('is_available_today', true)
            ->get();

        $clinics = [];
        foreach ($doctors as $doctor) {
            // Ensure numbers
            $this->ensureQueueNumbersAssigned($doctor->id);

            $current = Appointment::with(['patient.user', 'emergency'])
                ->where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', ['calling', 'in_consultation'])
                ->orderByRaw("CASE WHEN status = 'calling' THEN 1 WHEN status = 'in_consultation' THEN 2 ELSE 3 END")
                ->orderBy('called_at', 'desc')
                ->first();

            $waitingCount = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $today)
                ->where(function($q) {
                    $q->where('payment_status', 'paid')->orWhereNotNull('emergency_id');
                })
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->count();

            $currentData = null;
            if ($current) {
                $pUser = $current->patient ? $current->patient->user : null;
                $pName = $pUser ? $pUser->name : ($current->emergency && $current->emergency->emergencyPatient ? $current->emergency->emergencyPatient->name : 'مريض');
                $currentData = [
                    'queue_number' => $current->queue_number ?: $current->id,
                    'name' => $pName,
                    'status' => $current->status,
                    'is_emergency' => (bool)$current->emergency_id,
                    'called_at_timestamp' => $current->called_at ? $current->called_at->timestamp : null,
                ];
            }

            $clinics[] = [
                'doctor_id' => $doctor->id,
                'doctor_name' => $doctor->user ? $doctor->user->name : 'الطبيب',
                'specialization' => $doctor->specialization,
                'department_name' => $doctor->department ? $doctor->department->name : '-',
                'current_patient' => $currentData,
                'waiting_count' => $waitingCount,
            ];
        }

        return response()->json([
            'success' => true,
            'clinics' => $clinics,
            'server_time' => now()->format('H:i:s'),
            'server_date' => now()->translatedFormat('l d F Y'),
        ]);
    }

    /**
     * Action: Call Next Patient (by Doctor or Receptionist)
     */
    public function callNext(Request $request, $doctorId)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage own visits') && !$user->can('manage consultant availability')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المرضى من الطابور.'], 403);
        }

        $doctor = Doctor::findOrFail($doctorId);
        $today = today();

        $this->ensureQueueNumbersAssigned($doctor->id);

        // Find next waiting patient
        $nextAppointment = Appointment::with(['patient.user', 'emergency'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('payment_status', 'paid')->orWhereNotNull('emergency_id');
            })
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->orderByRaw("CASE WHEN emergency_id IS NOT NULL THEN 0 ELSE 1 END")
            ->orderBy('queue_number', 'asc')
            ->orderBy('id', 'asc')
            ->first();

        if (!$nextAppointment) {
            return response()->json([
                'success' => false,
                'message' => 'لا يوجد مرضى في قائمة الانتظار حالياً.'
            ], 404);
        }

        // Move previous admitted/consultation appointments with visits to completed
        Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('status', 'in_consultation')
                  ->orWhereHas('visit');
            })
            ->where('id', '!=', $nextAppointment->id)
            ->update(['status' => 'completed']);

        // Move any currently 'calling' appointment (that didn't enter and has no visit) back to confirmed
        Appointment::where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'calling')
            ->whereDoesntHave('visit')
            ->where('id', '!=', $nextAppointment->id)
            ->update(['status' => 'confirmed']);

        // Set next patient to calling
        $nextAppointment->status = 'calling';
        $nextAppointment->called_at = now();
        $nextAppointment->save();

        // إرسال نداء فوري على تيليجرام للمريض المستدعى
        try {
            app(\App\Services\TelegramService::class)->sendTurnAlert($nextAppointment);

            // تنبيه المريض الذي يليه في الطابور باقتراب الدور
            $followingAppointment = Appointment::where('doctor_id', $doctor->id)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->where('queue_number', '>', $nextAppointment->queue_number ?? 0)
                ->orderBy('queue_number', 'asc')
                ->first();

            if ($followingAppointment) {
                app(\App\Services\TelegramService::class)->sendNearTurnAlert($followingAppointment);
            }
        } catch (\Throwable $e) {
            \Log::warning('Telegram notification failed: ' . $e->getMessage());
        }

        $pName = $nextAppointment->patient && $nextAppointment->patient->user ? $nextAppointment->patient->user->name : 'المريض';

        return response()->json([
            'success' => true,
            'message' => 'تم استدعاء المريض (' . $pName . ') بنجاح.',
            'appointment_id' => $nextAppointment->id,
            'queue_number' => $nextAppointment->queue_number,
            'patient_name' => $pName
        ]);
    }

    /**
     * Action: Recall Current Patient (Re-trigger audio alert)
     */
    public function recall(Request $request, $appointmentId)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage own visits') && !$user->can('manage consultant availability')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بإعادة المناداة على المريض.'], 403);
        }

        $appointment = Appointment::with(['patient.user', 'doctor.user'])->findOrFail($appointmentId);
        
        $appointment->status = 'calling';
        $appointment->called_at = now();
        $appointment->save();

        // إعادة إرسال نداء تيليجرام
        try {
            app(\App\Services\TelegramService::class)->sendTurnAlert($appointment);
        } catch (\Throwable $e) {}

        $pName = $appointment->patient && $appointment->patient->user ? $appointment->patient->user->name : 'المريض';
        $docName = $appointment->doctor && $appointment->doctor->user ? $appointment->doctor->user->name : 'الطبيب';

        return response()->json([
            'success' => true,
            'message' => 'تمت المناداة على المريض (' . $pName . ').',
            'patient_name' => $pName,
            'doctor_name' => $docName,
            'queue_number' => $appointment->queue_number,
            'called_at' => $appointment->called_at->toIso8601String()
        ]);
    }

    /**
     * Action: Call Patient for Test Results Review (الأشعة والمختبر)
     */
    public function callForResults(Request $request, $visitId)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage own visits') && !$user->can('manage consultant availability')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المراجعين للنتائج.'], 403);
        }

        $visit = Visit::with(['patient.user', 'doctor.user', 'appointment', 'radiologyRequests', 'requests'])->findOrFail($visitId);
        
        $hasPendingRad = $visit->radiologyRequests()->where('status', '!=', 'completed')->exists();
        $hasPendingLab = $visit->requests()->where('type', 'lab')->where('status', '!=', 'completed')->exists();
        if ($hasPendingRad || $hasPendingLab) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن استدعاء المراجع حالياً، بانتظار اكتمال كافة الفحوصات الطبية من المختبر / الأشعة.'
            ], 422);
        }

        // التحقق من تسديد أجور الفحوصات والكشفية بالكاشير
        $hasUnpaidLab = $visit->requests()->whereIn('type', ['lab', 'radiology'])->where('payment_status', '!=', 'paid')->exists();
        $isUnpaidAppointment = ($visit->appointment && $visit->appointment->payment_status !== 'paid' && !$visit->appointment->emergency_id);

        if ($hasUnpaidLab || $isUnpaidAppointment) {
            $pTitle = $visit->patient && $visit->patient->user ? $visit->patient->user->name : 'المراجع';
            return response()->json([
                'success' => false,
                'message' => 'تنبيه: توجد فحوصات طبية غير مسددة في الكاشير للمراجع (' . $pTitle . '). يرجى توجيهه لتسديد الرسوم في الكاشير أولاً.'
            ], 422);
        }

        $pName = $visit->patient && $visit->patient->user ? $visit->patient->user->name : 'المراجع';
        $docName = $visit->doctor && $visit->doctor->user ? $visit->doctor->user->name : 'الطبيب';

        $appointment = $visit->appointment;
        $today = today();

        // Move previous admitted/consultation appointments with other visits to completed
        Appointment::where('doctor_id', $visit->doctor_id)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('status', 'in_consultation')
                  ->orWhereHas('visit');
            })
            ->when($appointment, function($q) use ($appointment) {
                $q->where('id', '!=', $appointment->id);
            })
            ->update(['status' => 'completed']);

        // Move any other currently 'calling' appointment (that didn't enter and has no visit) back to confirmed
        Appointment::where('doctor_id', $visit->doctor_id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'calling')
            ->whereDoesntHave('visit')
            ->when($appointment, function($q) use ($appointment) {
                $q->where('id', '!=', $appointment->id);
            })
            ->update(['status' => 'confirmed']);

        if ($appointment) {
            $appointment->status = 'calling';
            $appointment->called_at = now();
            $appointment->save();

            try {
                app(\App\Services\TelegramService::class)->sendTurnAlert($appointment);
            } catch (\Throwable $e) {}
        }

        return response()->json([
            'success' => true,
            'message' => 'تم استدعاء المراجع (' . $pName . ') لمراجعة نتائج الفحوصات.',
            'patient_name' => $pName,
            'doctor_name' => $docName,
            'queue_number' => $appointment ? $appointment->queue_number : null,
            'is_result_review' => true,
            'redirect_url' => route('doctor.visits.show', $visit->id)
        ]);
    }

    /**
     * Action: Admit / Start Consultation (Converts to Visit & sets status to in_consultation)
     */
    public function startConsultation(Request $request, $appointmentId)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage own visits') && !$user->can('manage consultant availability')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك ببدء الكشف الطبي.'], 403);
        }

        $appointment = Appointment::with(['patient.user', 'doctor.user'])->findOrFail($appointmentId);

        if ($appointment->payment_status !== 'paid' && !$appointment->emergency_id) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسديد أجور الكشف في الكاشير أولاً.'
            ], 422);
        }

        // Find or create Visit
        $visit = $appointment->visit;
        if (!$visit) {
            $visit = Visit::create([
                'patient_id' => $appointment->patient_id,
                'doctor_id' => $appointment->doctor_id,
                'department_id' => $appointment->department_id,
                'appointment_id' => $appointment->id,
                'visit_date' => $appointment->appointment_date ?? today(),
                'visit_time' => now()->format('H:i'),
                'visit_type' => 'checkup',
                'chief_complaint' => $appointment->reason ?: 'زيارة استشارية',
                'status' => 'in_progress'
            ]);
        }

        $appointment->status = 'in_consultation';
        $appointment->save();

        try {
            app(\App\Services\TelegramService::class)->sendTurnAlert($appointment);
        } catch (\Throwable $e) {}

        return response()->json([
            'success' => true,
            'message' => 'تم إدخال المريض وبدء الكشف بنجاح.',
            'visit_id' => $visit->id,
            'redirect_url' => route('doctor.visits.show', $visit->id)
        ]);
    }

    /**
     * Action: Skip / Delay Patient
     */
    public function skip(Request $request, $appointmentId)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('manage own visits') && !$user->can('manage consultant availability')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتأخير دور المريض.'], 403);
        }

        $appointment = Appointment::findOrFail($appointmentId);
        
        // Put at the end of queue
        $maxQueue = Appointment::where('doctor_id', $appointment->doctor_id)
            ->whereDate('appointment_date', today())
            ->max('queue_number') ?? 0;

        $appointment->queue_number = $maxQueue + 1;
        $appointment->status = 'confirmed';
        $appointment->called_at = null;
        $appointment->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تأخير دور المريض ونقله لآخر الطابور.'
        ]);
    }

    /**
     * Helper: Ensure queue numbers are sequential per doctor per day
     */
    private function ensureQueueNumbersAssigned($doctorId)
    {
        $today = today();
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->where(function($q) {
                $q->where('payment_status', 'paid')->orWhereNotNull('emergency_id');
            })
            ->whereNull('queue_number')
            ->orderBy('id', 'asc')
            ->get();

        if ($appointments->isNotEmpty()) {
            $maxQueue = Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $today)
                ->whereNotNull('queue_number')
                ->max('queue_number') ?? 0;

            foreach ($appointments as $apt) {
                $maxQueue++;
                $apt->queue_number = $maxQueue;
                $apt->save();
            }
        }
    }

    /**
     * Natural HD Arabic Voice TTS Stream Proxy
     */
    public function tts(Request $request)
    {
        $text = trim($request->query('text', ''));
        if (empty($text)) {
            return response('', 400);
        }

        $encoded = urlencode($text);
        $url = "https://translate.google.com/translate_tts?ie=UTF-8&tl=ar&client=tw-ob&q={$encoded}";

        try {
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                    'timeout' => 5
                ]
            ]);
            $audio = @file_get_contents($url, false, $context);
            if ($audio && strlen($audio) > 500) {
                return response($audio, 200, [
                    'Content-Type' => 'audio/mpeg',
                    'Cache-Control' => 'public, max-age=86400',
                ]);
            }
        } catch (\Exception $e) {}

        return response('', 404);
    }

    /**
     * Mobile Patient Live Queue Tracker View
     */
    public function patientLiveTracker(Appointment $appointment)
    {
        $appointment->load(['patient.user', 'doctor.user', 'department']);
        return view('queue.patient-tracker', compact('appointment'));
    }
}
