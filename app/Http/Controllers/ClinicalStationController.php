<?php

namespace App\Http\Controllers;

use App\Models\NursingStation;
use App\Models\ResidentStationFollowUp;
use App\Models\ResidentStationReading;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicalStationController extends Controller
{
    /**
     * Display the Unified Clinical Surgery Station.
     */
    public function show(Surgery $surgery, Request $request)
    {
        $user = Auth::user();

        // Check if patient has a room assigned but nursing has not checked them in yet
        if ($surgery->room_id && ! $surgery->room_checkin_at && $user && $user->hasRole('nurse') && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin', 'doctor', 'surgeon', 'anesthesiologist'])) {
            return redirect()->route('nursing-station.index')
                ->with('warning', 'يرجى تسجيل دخول المريض للغرفة أولاً قبل الدخول إلى ملف المريض والتعديل السريري.');
        }

        // 1. Ensure Resident Stations exist (pre_op and post_op)
        $preOpStation = $surgery->residentStations()->firstOrCreate(
            ['phase' => 'pre_op'],
            ['status' => 'pending']
        );

        $postOpStation = $surgery->residentStations()->where('phase', 'post_op')->first();
        if (! $postOpStation && $surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed') {
            $postOpStation = $surgery->residentStations()->create([
                'phase' => 'post_op',
                'status' => 'pending',
            ]);
        }

        // 2. Ensure Nursing Station exists
        $nursingStation = $surgery->nursingStation;
        if (! $nursingStation) {
            $nursingStation = $surgery->nursingStation()->create([
                'status' => 'pending',
            ]);
        }

        // 3. Determine current phase
        $requestedPhase = $request->query('phase');
        if ($requestedPhase === 'pre_op') {
            $currentPhase = 'pre_op';
            $station = $preOpStation;
        } elseif ($requestedPhase === 'post_op') {
            $currentPhase = 'post_op';
            $station = $postOpStation ?? $preOpStation;
        } else {
            // Auto detect
            if (! $preOpStation || $preOpStation->status !== 'completed') {
                $currentPhase = 'pre_op';
                $station = $preOpStation;
            } elseif ($postOpStation) {
                $currentPhase = 'post_op';
                $station = $postOpStation;
            } else {
                $currentPhase = 'pre_op';
                $station = $preOpStation;
            }
        }

        // 4. Compute Tab Permissions for current user
        $tabPermissions = SurgeryTabPermission::getUserTabPermissions($user);
        $allTabs = SurgeryTabPermission::TABS;

        // Filter visible tabs (exclude hidden)
        $visibleTabs = [];
        foreach ($allTabs as $key => $meta) {
            $access = $tabPermissions[$key] ?? SurgeryTabPermission::ACCESS_HIDDEN;
            if ($access !== SurgeryTabPermission::ACCESS_HIDDEN) {
                $visibleTabs[$key] = array_merge($meta, [
                    'access' => $access,
                    'can_write' => ($access === SurgeryTabPermission::ACCESS_WRITE),
                    'is_readonly' => ($access === SurgeryTabPermission::ACCESS_READ),
                ]);
            }
        }

        // Determine active tab
        $activeTab = $request->query('tab');
        if (! $activeTab || ! isset($visibleTabs[$activeTab])) {
            $activeTab = array_key_first($visibleTabs) ?? 'vitals';
        }

        // 5. Load relationships
        $surgery->load([
            'patient.user',
            'doctor.user',
            'operationTheater',
            'room',
            'residentStations.readings.resident.user',
            'residentStations.followUps',
            'nursingStation.nurse',
            'surgeryTreatments.administeredBy',
            'surgeryTreatments.batch.product',
            'surgeryTreatments.stockMovement',
            'labTests.labTest',
            'radiologyTests.radiologyType',
        ]);

        // Floor sub-store location for patient's room and available batches
        $floorLocation = $surgery->getFloorSubStoreLocation();
        $floorStockBatches = $floorLocation
            ? StockBatch::with('product')
                ->where('location_id', $floorLocation->id)
                ->where('current_qty', '>', 0)
                ->orderBy('expiry_date')
                ->get()
            : collect();

        if ($station) {
            $station->load(['vitalsRecordedBy']);
        }

        // Combined readings for vitals history and charts
        $readings = $station ? $station->readings()->with(['resident.user', 'user'])->get() : collect();
        $readingsAsc = $readings->sortBy('created_at');

        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canEditVitals = $station ? $station->canUserEditVitals($user) : true;
        $canViewVitalsCharts = $isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse'));

        // All follow-up logs
        $followUps = ResidentStationFollowUp::where('surgery_id', $surgery->id)
            ->orderBy('follow_up_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Prescribed treatments
        $treatments = $surgery->surgeryTreatments()->orderBy('sort_order')->orderBy('created_at')->get();

        $nurses = User::role('nurse')->where('is_active', true)->get();
        $residents = User::role('resident')->where('is_active', true)->get();
        $canActuallyEditVitals = (($tabPermissions['vitals'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) && ($canEditVitals ?? true);

        return view('surgery-stations.clinical.show', compact(
            'surgery',
            'currentPhase',
            'station',
            'preOpStation',
            'postOpStation',
            'nursingStation',
            'tabPermissions',
            'visibleTabs',
            'activeTab',
            'readings',
            'readingsAsc',
            'followUps',
            'treatments',
            'floorLocation',
            'floorStockBatches',
            'nurses',
            'residents',
            'canEditVitals',
            'canActuallyEditVitals',
            'canViewVitalsCharts',
            'isAdmin'
        ));
    }

    /**
     * Update clinical station data.
     */
    public function update(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $tabPermissions = SurgeryTabPermission::getUserTabPermissions($user);

        $phase = $request->input('phase', 'pre_op');
        $station = $surgery->residentStations()->firstOrCreate(
            ['phase' => $phase],
            ['status' => 'in_progress', 'started_at' => now()]
        );

        $nursingStation = $surgery->nursingStation ?? $surgery->nursingStation()->create([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $activeTab = $request->input('active_tab', 'vitals');

        // 1. History Tab (if write permitted)
        if (($tabPermissions['history'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $historyData = $request->only([
                'chief_complaint',
                'history_present_illness',
                'past_medical_hx',
                'past_surgical_hx',
                'drug_hx',
                'drug_allergy',
            ]);
            $station->update($historyData);
        }

        // 2. Vitals & Physical Exam (if write permitted)
        if (($tabPermissions['vitals'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $vitalsData = $request->only([
                'bp', 'temp', 'pr', 'rr', 'spo2',
                'pain_score', 'rbs', 'gcs', 'crt',
                'clinical_examination',
            ]);

            $hasAnyVitals = ! empty($vitalsData['bp']) || ! empty($vitalsData['temp']) ||
                            ! empty($vitalsData['pr']) || ! empty($vitalsData['rr']) ||
                            ! empty($vitalsData['spo2']) || ! empty($vitalsData['clinical_examination']) ||
                            ! empty($vitalsData['pain_score']) || ! empty($vitalsData['rbs']) ||
                            ! empty($vitalsData['gcs']) || ! empty($vitalsData['crt']);

            // إذا كان الطلب تعديلاً لقراءة تاريخية محددة تم تحميلها للأعلى
            $editingReadingId = $request->input('editing_reading_id');
            if ($editingReadingId) {
                $reading = ResidentStationReading::find($editingReadingId);
                if ($reading && $reading->resident_station_id === $station->id) {
                    $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
                    $isOwner = (int) $reading->user_id === (int) $user->id || (empty($reading->user_id) && $reading->resident_id === $user->doctor?->id);
                    $isWithin30Min = $reading->created_at ? now()->lte($reading->created_at->copy()->addMinutes(30)) : true;

                    if (! $isAdmin && (! $isOwner || ! $isWithin30Min)) {
                        return back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
                    }

                    $reading->update($vitalsData);

                    // إذا كانت هذه القراءة هي الأحدث، نقوم بتحديث قيم المحطة السريرية العلوية ومحطة التمريض
                    $latestReading = $station->readings()->orderByDesc('created_at')->orderByDesc('id')->first();
                    if ($latestReading && $latestReading->id === $reading->id) {
                        $station->update(array_merge($vitalsData, [
                            'vitals_recorded_at' => $reading->created_at,
                            'vitals_recorded_by' => $reading->user_id ?? $user->id,
                        ]));

                        if ($surgery && $surgery->nursingStation) {
                            $nursingVitalsString = implode(' | ', array_filter([
                                ! empty($vitalsData['bp']) ? "ضغط الدم: {$vitalsData['bp']}" : null,
                                ! empty($vitalsData['temp']) ? "الحرارة: {$vitalsData['temp']}°C" : null,
                                ! empty($vitalsData['pr']) ? "النبض: {$vitalsData['pr']} bpm" : null,
                                ! empty($vitalsData['rr']) ? "التنفس: {$vitalsData['rr']} /min" : null,
                                ! empty($vitalsData['spo2']) ? "الأكسجين: {$vitalsData['spo2']}%" : null,
                            ]));

                            $surgery->nursingStation->update([
                                'vital_signs' => $nursingVitalsString ?: $surgery->nursingStation->vital_signs,
                            ]);
                        }
                    }

                    return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $phase, 'tab' => $activeTab])
                        ->with('success', 'تم تعديل القراءة السريرية وحفظها بنجاح.');
                }
            }

            if ($hasAnyVitals) {
                // Check if changed from current
                $isChanged = ($station->bp !== ($vitalsData['bp'] ?? null)) ||
                             ($station->temp !== ($vitalsData['temp'] ?? null)) ||
                             ($station->pr !== ($vitalsData['pr'] ?? null)) ||
                             ($station->rr !== ($vitalsData['rr'] ?? null)) ||
                             ($station->spo2 !== ($vitalsData['spo2'] ?? null)) ||
                             ($station->pain_score !== ($vitalsData['pain_score'] ?? null)) ||
                             ($station->rbs !== ($vitalsData['rbs'] ?? null)) ||
                             ($station->gcs !== ($vitalsData['gcs'] ?? null)) ||
                             ($station->crt !== ($vitalsData['crt'] ?? null)) ||
                             ($station->clinical_examination !== ($vitalsData['clinical_examination'] ?? null));

                $isNewReading = $request->boolean('is_new_reading');

                if ($isChanged || $isNewReading) {
                    if (! $isNewReading && ! $station->canUserEditVitals($user)) {
                        return back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
                    }

                    $station->readings()->create(array_merge($vitalsData, [
                        'user_id' => $user->id,
                        'resident_id' => $user->doctor?->id,
                        'notes' => $request->input('vital_notes'),
                    ]));

                    $station->update(array_merge($vitalsData, [
                        'vitals_recorded_at' => now(),
                        'vitals_recorded_by' => $user->id,
                    ]));
                }
            }

            // Synchronize with NursingStation
            $nursingVitalsString = implode(' | ', array_filter([
                ! empty($vitalsData['bp']) ? "ضغط الدم: {$vitalsData['bp']}" : null,
                ! empty($vitalsData['temp']) ? "الحرارة: {$vitalsData['temp']}°C" : null,
                ! empty($vitalsData['pr']) ? "النبض: {$vitalsData['pr']} bpm" : null,
                ! empty($vitalsData['rr']) ? "التنفس: {$vitalsData['rr']} /min" : null,
                ! empty($vitalsData['spo2']) ? "الأكسجين: {$vitalsData['spo2']}%" : null,
            ]));

            $nursingStation->update([
                'vital_signs' => $nursingVitalsString ?: $nursingStation->vital_signs,
            ]);
        }

        // 3. Medical Plan Tab (if write permitted)
        if (($tabPermissions['plan'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $planData = $request->only([
                'review_of_other_systems',
                'notes',
                'post_op_notes',
                'treatment_plan',
            ]);
            $station->update($planData);
        }

        // 4. Fluids (Intake & Output) Tab (if write permitted)
        if (($tabPermissions['fluids'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $fluidsData = $request->only([
                'intake_iv_fluids', 'intake_oral', 'intake_blood',
                'output_urine', 'output_drain', 'output_gtube_ng',
                'output_vomiting', 'output_stool',
            ]);

            $totalIntake = ($fluidsData['intake_iv_fluids'] ?? 0) + ($fluidsData['intake_oral'] ?? 0) + ($fluidsData['intake_blood'] ?? 0);
            $totalOutput = ($fluidsData['output_urine'] ?? 0) + ($fluidsData['output_drain'] ?? 0) + ($fluidsData['output_gtube_ng'] ?? 0) + ($fluidsData['output_vomiting'] ?? 0) + ($fluidsData['output_stool'] ?? 0);

            $anyFluidsGiven = isset($fluidsData['intake_iv_fluids']) || isset($fluidsData['output_urine']);
            $fluidsData['fluid_balance'] = $anyFluidsGiven ? ($totalIntake - $totalOutput) : null;

            $station->update($fluidsData);
        }

        // 5. Nursing Care & Notes Tab (if write permitted)
        if (($tabPermissions['nursing_care'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $nursingCareData = $request->only([
                'nursing_notes',
                'discharge_notes',
                'nurse_id',
            ]);

            if (empty($nursingCareData['nurse_id']) && $user->hasRole('nurse')) {
                $nursingCareData['nurse_id'] = $user->id;
            }

            $nursingStation->update($nursingCareData);
        }

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $phase, 'tab' => $activeTab])
            ->with('success', 'تم حفظ التحديثات السريرية بنجاح.');
    }

    /**
     * Store a follow-up note entry.
     */
    public function storeFollowUp(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $access = SurgeryTabPermission::getAccessForUser($user, 'follow_up');

        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بإضافة ملاحظات المتابعة.');
        }

        $validated = $request->validate([
            'follow_up_date' => 'required|date',
            'session' => 'nullable|string|in:morning,evening',
            'notes' => 'required|string|max:2000',
        ]);

        $phase = $request->input('phase', 'pre_op');
        $station = $surgery->residentStations()->firstOrCreate(
            ['phase' => $phase],
            ['status' => 'pending']
        );

        $userName = $user->full_name ?? $user->name ?? 'مستخدم سريري';

        ResidentStationFollowUp::create([
            'surgery_id' => $surgery->id,
            'resident_station_id' => $station->id,
            'resident_id' => $user->doctor?->id,
            'resident_name' => $userName,
            'follow_up_date' => $validated['follow_up_date'],
            'session' => $validated['session'] ?? 'morning',
            'notes' => $validated['notes'],
        ]);

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $phase, 'tab' => 'follow_up'])
            ->with('success', 'تم تسجيل قيد المتابعة بنجاح.');
    }

    /**
     * Store a prescribed medication / treatment.
     */
    public function storeTreatment(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');

        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بإضافة أدوية أو علاجات.');
        }

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'timing' => 'nullable|string|max:100',
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|string|in:hours,days,weeks',
        ]);

        $validated['surgery_id'] = $surgery->id;
        $validated['status'] = 'planned';

        SurgeryTreatment::create($validated);

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'treatments'])
            ->with('success', 'تمت إضافة الدواء بنجاح إلى جدول العلاجات.');
    }

    /**
     * Administer a prescribed treatment.
     */
    public function administerTreatment(Request $request, SurgeryTreatment $treatment)
    {
        $user = Auth::user();
        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');

        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بتسجيل إعطاء العلاجات.');
        }

        $validated = $request->validate([
            'status' => 'required|in:administered,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['status'] === 'administered') {
            $userName = $user->full_name ?? $user->name ?? 'الكادر التمريض';
            $treatment->logAdministration($user->id, $userName, $validated['admin_notes'] ?? null);
            $actionText = 'تسجيل إعطاء الجرعة بنجاح';
        } else {
            $treatment->update([
                'status' => 'cancelled',
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);
            $actionText = 'إلغاء العلاج';
        }

        return redirect()->back()->with('success', "تم {$actionText}.");
    }

    /**
     * Delete a prescribed treatment.
     */
    public function deleteTreatment(SurgeryTreatment $treatment)
    {
        $user = Auth::user();
        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');

        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بحذف الأدوية.');
        }

        $treatment->delete();

        return redirect()->back()->with('success', 'تم حذف الدواء من الخطة العلاجية بنجاح.');
    }

    /**
     * Dispense medication or supply directly from the floor sub-store for the patient.
     */
    public function dispenseFloorItem(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');

        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بصرف الأدوية أو المستلزمات.');
        }

        $validated = $request->validate([
            'batch_id' => 'required|exists:stock_batches,id',
            'quantity' => 'required|integer|min:1',
            'timing' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $floorLocation = $surgery->getFloorSubStoreLocation();
        if (! $floorLocation) {
            return redirect()->back()->with('error', 'لم يتم العثور على مخزن فرعي مرتبط بطابق غرفة هذا المريض.');
        }

        try {
            DB::transaction(function () use ($validated, $surgery, $floorLocation, $user) {
                $batch = StockBatch::where('id', $validated['batch_id'])
                    ->where('location_id', $floorLocation->id)
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw new \Exception('الوجبة المحددة لا تنتمي إلى مخزن عهدة طابق هذا المريض.');
                }

                if ($batch->current_qty < $validated['quantity']) {
                    throw new \Exception("الكمية المطلوبة ({$validated['quantity']}) تتجاوز الرصيد المتوفر في عهدة الطابق ({$batch->current_qty}).");
                }

                // خصم الرصيد من وجبة الطابق
                $batch->decrement('current_qty', $validated['quantity']);

                $patientName = $surgery->patient?->name ?? 'مريض';
                $roomNumber = $surgery->room?->room_number ?? 'بدون غرفة';
                $movementNotes = "صرف سريري لمريض: {$patientName} (غرفة: {$roomNumber})";
                if (! empty($validated['admin_notes'])) {
                    $movementNotes .= ' - '.$validated['admin_notes'];
                }

                // تسجيل حركة الإخراج للمريض
                $movement = StockMovement::create([
                    'batch_id' => $batch->id,
                    'from_location_id' => $floorLocation->id,
                    'to_location_id' => null,
                    'quantity' => $validated['quantity'],
                    'type' => 'issue_to_patient',
                    'user_id' => $user->id,
                    'surgery_id' => $surgery->id,
                    'notes' => $movementNotes,
                ]);

                // تسجيل المادة في جدول علاجات ومستهلكات المريض السريرية
                $product = $batch->product;
                $unitName = $product?->unit ?? 'جرعة/قطعة';
                $dosageText = "{$validated['quantity']} {$unitName}";

                SurgeryTreatment::create([
                    'surgery_id' => $surgery->id,
                    'description' => $product?->name ?? 'مستلزم / علاج',
                    'dosage' => $dosageText,
                    'timing' => $validated['timing'] ?: 'صرف مباشر من عهدة الطابق',
                    'status' => 'administered',
                    'administered_by' => $user->id,
                    'administered_at' => now(),
                    'admin_notes' => $validated['admin_notes'] ?? null,
                    'batch_id' => $batch->id,
                    'stock_movement_id' => $movement->id,
                    'quantity' => $validated['quantity'],
                    'administrations' => [
                        [
                            'administered_at' => now()->format('Y-m-d H:i'),
                            'administered_by' => $user->id,
                            'administered_by_name' => $user->full_name ?? $user->name ?? 'الكادر التمريضي',
                            'notes' => $validated['admin_notes'] ?? null,
                        ],
                    ],
                ]);
            });

            return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'treatments'])
                ->with('success', "تم صرف العلاج بنجاح من ({$floorLocation->name}) وخصمه من الرصيد وتوثيقه في طبلة المريض.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Complete phase or clinical station.
     */
    public function complete(Surgery $surgery, Request $request)
    {
        $phase = $request->input('phase', 'pre_op');
        $station = $surgery->residentStations()->where('phase', $phase)->first();

        if ($station) {
            $station->markAsCompleted();
        }

        if ($phase === 'pre_op') {
            if (! $surgery->operationTheaterStation) {
                $surgery->operationTheaterStation()->create(['status' => 'pending']);
            }
            $message = 'تم إكمال المحطة السريرية لما قبل العملية ونقل الحالة إلى صالة العمليات.';
        } else {
            if (! $surgery->nursingStation) {
                $surgery->nursingStation()->create(['status' => 'pending']);
            }
            $message = 'تم إكمال المحطة السريرية لما بعد العملية وتثبيت المتابعة.';
        }

        return redirect()->route('surgeries.waiting')->with('success', $message);
    }

    /**
     * تعديل قراءة علامات حيوية تاريخية فردية
     */
    public function updateReading(Request $request, ResidentStationReading $reading)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $station = $reading->residentStation;
        $surgery = $station?->surgery;

        // Check if user is owner within 30 min, or Admin
        $isOwner = (int) $reading->user_id === (int) $user->id || (empty($reading->user_id) && $reading->resident_id === $user->doctor?->id);
        $isWithin30Min = $reading->created_at ? now()->lte($reading->created_at->copy()->addMinutes(30)) : true;

        if (! $isAdmin && (! $isOwner || ! $isWithin30Min)) {
            return redirect()->back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
        }

        $vitalsData = $request->only([
            'bp', 'temp', 'pr', 'rr', 'spo2',
            'pain_score', 'rbs', 'gcs', 'crt',
            'clinical_examination', 'notes',
        ]);

        $reading->update($vitalsData);

        // إذا كانت هذه القراءة هي الأحدث، نقوم بتحديث قيم المحطة السريرية العلوية ومحطة التمريض
        $latestReading = $station ? $station->readings()->orderByDesc('created_at')->orderByDesc('id')->first() : null;
        if ($latestReading && $latestReading->id === $reading->id) {
            $station->update(array_merge($vitalsData, [
                'vitals_recorded_at' => $reading->created_at,
                'vitals_recorded_by' => $reading->user_id ?? $user->id,
            ]));

            if ($surgery && $surgery->nursingStation) {
                $nursingVitalsString = implode(' | ', array_filter([
                    ! empty($vitalsData['bp']) ? "ضغط الدم: {$vitalsData['bp']}" : null,
                    ! empty($vitalsData['temp']) ? "الحرارة: {$vitalsData['temp']}°C" : null,
                    ! empty($vitalsData['pr']) ? "النبض: {$vitalsData['pr']} bpm" : null,
                    ! empty($vitalsData['rr']) ? "التنفس: {$vitalsData['rr']} /min" : null,
                    ! empty($vitalsData['spo2']) ? "الأكسجين: {$vitalsData['spo2']}%" : null,
                ]));

                $surgery->nursingStation->update([
                    'vital_signs' => $nursingVitalsString ?: $surgery->nursingStation->vital_signs,
                ]);
            }
        }

        return redirect()->back()->with('success', 'تم تعديل القراءة السريرية بنجاح.');
    }

    /**
     * حذف قراءة علامات حيوية تاريخية فردية وإعادة مزامنة المحطة
     */
    public function deleteReading(ResidentStationReading $reading)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $station = $reading->residentStation;
        $surgery = $station?->surgery;

        // Check if user is owner within 30 min, or Admin
        $isOwner = (int) $reading->user_id === (int) $user->id || (empty($reading->user_id) && $reading->resident_id === $user->doctor?->id);
        $isWithin30Min = $reading->created_at ? now()->lte($reading->created_at->copy()->addMinutes(30)) : true;

        if (! $isAdmin && (! $isOwner || ! $isWithin30Min)) {
            return redirect()->back()->with('error', 'عفواً، لا يمكن حذف هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. الحذف محصور بمدير النظام حصراً.');
        }

        $wasLatest = false;
        $latestReading = $station ? $station->readings()->orderByDesc('created_at')->orderByDesc('id')->first() : null;
        if ($latestReading && $latestReading->id === $reading->id) {
            $wasLatest = true;
        }

        $reading->delete();

        // إذا تم حذف القراءة الأخيرة، نعيد مزامنة المحطة مع القراءة السابقة لها إن وجدت
        if ($station && $wasLatest) {
            $newLatest = $station->readings()->orderByDesc('created_at')->orderByDesc('id')->first();
            if ($newLatest) {
                $syncData = $newLatest->only([
                    'bp', 'temp', 'pr', 'rr', 'spo2',
                    'pain_score', 'rbs', 'gcs', 'crt',
                    'clinical_examination',
                ]);
                $station->update(array_merge($syncData, [
                    'vitals_recorded_at' => $newLatest->created_at,
                    'vitals_recorded_by' => $newLatest->user_id,
                ]));

                if ($surgery && $surgery->nursingStation) {
                    $nursingVitalsString = implode(' | ', array_filter([
                        ! empty($syncData['bp']) ? "ضغط الدم: {$syncData['bp']}" : null,
                        ! empty($syncData['temp']) ? "الحرارة: {$syncData['temp']}°C" : null,
                        ! empty($syncData['pr']) ? "النبض: {$syncData['pr']} bpm" : null,
                        ! empty($syncData['rr']) ? "التنفس: {$syncData['rr']} /min" : null,
                        ! empty($syncData['spo2']) ? "الأكسجين: {$syncData['spo2']}%" : null,
                    ]));
                    $surgery->nursingStation->update([
                        'vital_signs' => $nursingVitalsString ?: null,
                    ]);
                }
            } else {
                // لم يعد هناك أي قراءات متبقية في الأرشيف
                $station->update([
                    'bp' => null, 'temp' => null, 'pr' => null, 'rr' => null, 'spo2' => null,
                    'pain_score' => null, 'rbs' => null, 'gcs' => null, 'crt' => null,
                    'clinical_examination' => null,
                    'vitals_recorded_at' => null,
                    'vitals_recorded_by' => null,
                ]);
                if ($surgery && $surgery->nursingStation) {
                    $surgery->nursingStation->update(['vital_signs' => null]);
                }
            }
        }

        return redirect()->back()->with('success', 'تم حذف القراءة السريرية وإعادة مزامنة سجل العلامات بنجاح.');
    }
}
