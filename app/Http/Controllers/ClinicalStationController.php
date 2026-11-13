<?php

namespace App\Http\Controllers;

use App\Models\LabTest;
use App\Models\Medicine;
use App\Models\NursingStation;
use App\Models\Package;
use App\Models\RadiologyType;
use App\Models\ResidentStationFollowUp;
use App\Models\ResidentStationReading;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Surgery;
use App\Models\SurgeryLabTest;
use App\Models\SurgeryRadiologyTest;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\User;
use App\Services\UserActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicalStationController extends Controller
{
    private const FLUID_FIELDS = [
        'intake_iv_fluids',
        'intake_oral',
        'intake_blood',
        'output_urine',
        'output_drain',
        'output_gtube_ng',
        'output_vomiting',
        'output_stool',
    ];

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

        // Determine active tab (redirect legacy ?tab=fluids into merged vitals tab)
        $activeTab = $request->query('tab');
        if ($activeTab === 'fluids' && isset($visibleTabs['vitals'])) {
            $activeTab = 'vitals';
        }
        if (! $activeTab || ! isset($visibleTabs[$activeTab])) {
            $activeTab = array_key_first($visibleTabs) ?? 'vitals';
        }

        // 5. Load relationships
        $surgery->load([
            'patient.user',
            'patient.healthInsuranceCategory',
            'doctor.user',
            'operationTheater',
            'room',
            'residentStations.readings.resident.user',
            'residentStations.followUps',
            'nursingStation.nurse',
            'surgeonStation.surgeon.user',
            'surgeryTreatments.medicine',
            'surgeryTreatments.prescribedBy',
            'surgeryTreatments.administeredBy',
            'surgeryTreatments.batch.product',
            'surgeryTreatments.stockMovement',
            'labTests.labTest',
            'radiologyTests.radiologyType',
        ]);

        // Floor sub-store location for patient's room and available supply batches
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
        $canManageClinicalExam = $canViewVitalsCharts;

        // All follow-up logs
        $followUps = ResidentStationFollowUp::where('surgery_id', $surgery->id)
            ->orderBy('follow_up_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Prescribed medications (from Pharmacy / Clinical Orders) & floor supplies (separated cleanly)
        $treatments = $surgery->surgeryTreatments()
            ->with(['medicine', 'prescribedBy', 'administeredBy', 'batch.product', 'stockMovement'])
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        $medicationTreatments = $treatments->filter(fn (SurgeryTreatment $item) => $item->isMedication())->values();
        $supplyTreatments = $treatments->filter(fn (SurgeryTreatment $item) => $item->isSupply())->values();

        // Floor warehouse is dedicated strictly to medical supplies/consumables (never medications)
        $floorSupplyBatches = $floorStockBatches
            ->filter(fn (StockBatch $batch) => $batch->product?->isSupply() ?? false)
            ->values();

        $canPrescribeTreatments = (($tabPermissions['treatments'] ?? '') === SurgeryTabPermission::ACCESS_WRITE)
            && ($isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse')));
        $canConfirmTreatmentDoses = (($tabPermissions['treatments'] ?? '') === SurgeryTabPermission::ACCESS_WRITE)
            && ($isAdmin || ($user && $user->hasRole('nurse')));

        $nurses = User::role('nurse')->where('is_active', true)->get();
        $residents = User::role('resident')->where('is_active', true)->get();
        $canActuallyEditVitals = (($tabPermissions['vitals'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) && ($canEditVitals ?? true);
        $canEditFluids = ($isAdmin || ($user && $user->hasRole('nurse')))
            && (($tabPermissions['vitals'] ?? '') === SurgeryTabPermission::ACCESS_WRITE);
        $canEditTests = (($tabPermissions['tests'] ?? '') === SurgeryTabPermission::ACCESS_WRITE)
            && ($isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor', 'anesthesia'])));

        $availableLabTests = LabTest::where('is_active', true)->orderBy('name')->get();
        $availableRadiologyTypes = RadiologyType::where('is_active', true)->orderBy('name')->get();
        $labPackages = Package::has('labTests')->with('labTests')->get();

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
            'medicationTreatments',
            'supplyTreatments',
            'floorLocation',
            'floorStockBatches',
            'floorSupplyBatches',
            'nurses',
            'residents',
            'canEditVitals',
            'canActuallyEditVitals',
            'canEditFluids',
            'canViewVitalsCharts',
            'canManageClinicalExam',
            'canPrescribeTreatments',
            'canConfirmTreatmentDoses',
            'canEditTests',
            'availableLabTests',
            'availableRadiologyTypes',
            'labPackages',
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

        // 2. Vitals, Physical Exam & Merged Fluids (Intake & Output)
        if (($tabPermissions['vitals'] ?? '') === SurgeryTabPermission::ACCESS_WRITE) {
            $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
            $canEditFluids = $isAdmin || ($user && $user->hasRole('nurse'));
            $canManageClinicalExam = $isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse'));
            $hasFluidsInput = $canEditFluids && $request->hasAny(self::FLUID_FIELDS);

            if ($hasFluidsInput) {
                $request->validate([
                    'intake_iv_fluids' => 'nullable|numeric|min:0',
                    'intake_oral' => 'nullable|numeric|min:0',
                    'intake_blood' => 'nullable|numeric|min:0',
                    'output_urine' => 'nullable|numeric|min:0',
                    'output_drain' => 'nullable|numeric|min:0',
                    'output_gtube_ng' => 'nullable|numeric|min:0',
                    'output_vomiting' => 'nullable|numeric|min:0',
                    'output_stool' => 'nullable|numeric|min:0',
                ]);
            }

            $vitalsFields = [
                'bp', 'temp', 'pr', 'rr', 'spo2',
                'pain_score', 'rbs', 'gcs', 'crt',
            ];
            if ($canManageClinicalExam) {
                $vitalsFields[] = 'clinical_examination';
            }
            $vitalsData = $request->only($vitalsFields);

            $hasAnyGridVitals = ! empty($vitalsData['bp']) || ! empty($vitalsData['temp']) ||
                                ! empty($vitalsData['pr']) || ! empty($vitalsData['rr']) ||
                                ! empty($vitalsData['spo2']) || ! empty($vitalsData['pain_score']) ||
                                ! empty($vitalsData['rbs']) || ! empty($vitalsData['gcs']) ||
                                ! empty($vitalsData['crt']);

            $hasAnyVitals = $hasAnyGridVitals || ($canManageClinicalExam && ! empty($vitalsData['clinical_examination']));

            [$fluidsData, $anyFluidsGiven] = $hasFluidsInput
                ? $this->prepareFluidsPayload($request)
                : [[], false];
            $fluidsChanged = $hasFluidsInput && $this->isFluidsChanged($station, $fluidsData);

            // إذا كان الطلب تعديلاً لقراءة تاريخية محددة تم تحميلها للأعلى
            $editingReadingId = $request->input('editing_reading_id');
            if ($editingReadingId) {
                $reading = ResidentStationReading::find($editingReadingId);
                if ($reading && $reading->resident_station_id === $station->id) {
                    $isOwner = (int) $reading->user_id === (int) $user->id || (empty($reading->user_id) && $reading->resident_id === $user->doctor?->id);
                    $isWithin30Min = $reading->created_at ? now()->lte($reading->created_at->copy()->addMinutes(30)) : true;

                    if (! $isAdmin && (! $isOwner || ! $isWithin30Min)) {
                        return back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
                    }

                    $readingUpdatePayload = $hasFluidsInput
                        ? array_merge($vitalsData, $fluidsData)
                        : $vitalsData;
                    $reading->update($readingUpdatePayload);

                    $this->syncStationFromReadings($station, $surgery);

                    return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'phase' => $phase, 'tab' => $activeTab])
                        ->with('success', 'تم تعديل القراءة السريرية وحفظها بنجاح.');
                }
            }

            $examChanged = $canManageClinicalExam && (($vitalsData['clinical_examination'] ?? null) !== $station->clinical_examination);
            $isVitalsChanged = ($station->bp !== ($vitalsData['bp'] ?? null)) ||
                               ($station->temp !== ($vitalsData['temp'] ?? null)) ||
                               ($station->pr !== ($vitalsData['pr'] ?? null)) ||
                               ($station->rr !== ($vitalsData['rr'] ?? null)) ||
                               ($station->spo2 !== ($vitalsData['spo2'] ?? null)) ||
                               ($station->pain_score !== ($vitalsData['pain_score'] ?? null)) ||
                               ($station->rbs !== ($vitalsData['rbs'] ?? null)) ||
                               ($station->gcs !== ($vitalsData['gcs'] ?? null)) ||
                               ($station->crt !== ($vitalsData['crt'] ?? null)) ||
                               $examChanged;

            $isNewReading = $request->boolean('is_new_reading');
            $shouldSaveVitals = $isNewReading
                ? $hasAnyGridVitals
                : ($hasAnyVitals && $isVitalsChanged);

            if ($shouldSaveVitals) {
                if (! $isNewReading && ! $station->canUserEditVitals($user)) {
                    return back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
                }

                $createPayload = $vitalsData;
                $stationPayload = array_merge($vitalsData, [
                    'vitals_recorded_at' => now(),
                    'vitals_recorded_by' => $user->id,
                ]);

                $shouldIncludeFluids = $hasFluidsInput && ($isNewReading ? $anyFluidsGiven : ($anyFluidsGiven || $fluidsChanged));
                if ($shouldIncludeFluids) {
                    $createPayload = array_merge($createPayload, $fluidsData);
                    $stationPayload = array_merge($stationPayload, $fluidsData);
                }

                $station->readings()->create(array_merge($createPayload, [
                    'user_id' => $user->id,
                    'resident_id' => $user->doctor?->id,
                    'notes' => $request->input('vital_notes'),
                ]));

                $station->update($stationPayload);
            } else {
                // إذا تم تعديل ملاحظات الفحص السريري وحدها في وضع القراءة الجديدة دون إعادة إدخال مؤشرات الجدول
                if ($isNewReading && $examChanged) {
                    $station->update(['clinical_examination' => $vitalsData['clinical_examination'] ?? null]);
                }

                $shouldSaveFluidsOnly = $hasFluidsInput && ($isNewReading ? $anyFluidsGiven : ($anyFluidsGiven || $fluidsChanged));
                if ($shouldSaveFluidsOnly) {
                    $station->update($fluidsData);

                    if ($anyFluidsGiven) {
                        $station->readings()->create(array_merge($fluidsData, [
                            'user_id' => $user->id,
                            'resident_id' => $user->doctor?->id,
                            'notes' => $request->input('vital_notes') ?: 'تسجيل رصد السوائل',
                        ]));
                    }
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

        // 4. Nursing Care & Notes Tab (if write permitted)
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
     * Live search active medicines from the hospital pharmacy catalog.
     */
    public function searchMedicines(Request $request)
    {
        $query = trim((string) $request->query('q', ''));

        $medicines = Medicine::query()
            ->with(['activeBatches'])
            ->where('is_active', true)
            ->when($query !== '', function ($q) use ($query) {
                $q->where(function ($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('generic_name', 'like', "%{$query}%")
                        ->orWhere('barcode', 'like', "%{$query}%")
                        ->orWhere('national_code', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(15)
            ->get()
            ->map(function (Medicine $med) {
                $mainStock = (int) $med->activeBatches->sum('current_quantity');
                $openSub = (int) $med->activeBatches->sum('current_sub_units');
                $factor = max(1, (int) ($med->sub_units_count ?? 1));

                return [
                    'id' => $med->id,
                    'name' => $med->name,
                    'generic_name' => $med->generic_name,
                    'dosage_form' => $med->dosage_form,
                    'strength' => $med->strength,
                    'main_unit' => $med->main_unit,
                    'sub_unit' => $med->sub_unit,
                    'total_stock' => $mainStock,
                    'total_sub_units_stock' => ($mainStock * $factor) + $openSub,
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'medicines' => $medicines,
        ]);
    }

    /**
     * Store a prescribed medication / treatment (Doctor / Resident / Surgeon / Admin only).
     */
    public function storeTreatment(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');
        $canPrescribe = ($access === SurgeryTabPermission::ACCESS_WRITE)
            && ($isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse')));

        if (! $canPrescribe) {
            return redirect()->back()->with('error', 'إدخال الأوامر الدوائية وتحديد المدة والتكرار محصور بالأطباء والمقيمين والإدارة فقط؛ دور التمريض هو تأشير التنفيذ والصرف.');
        }

        $validated = $request->validate([
            'medicine_id' => 'nullable|integer|exists:medicines,id',
            'description' => 'nullable|string|max:255',
            'dosage' => 'nullable|string|max:100',
            'route' => 'nullable|string|max:50',
            'frequency_preset' => 'nullable|string|in:q24h,q12h,q8h,q6h,q4h,stat,prn,custom',
            'interval_hours' => 'nullable|integer|min:1|max:48',
            'timing' => 'nullable|string|max:100',
            'duration_value' => 'nullable|integer|min:1|max:365',
            'duration_unit' => 'nullable|string|in:hours,days,weeks,months,doses',
            'start_at' => 'nullable|date',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $medicine = ! empty($validated['medicine_id'])
            ? Medicine::find($validated['medicine_id'])
            : null;

        $description = trim((string) ($validated['description'] ?? ''));
        if ($description === '' && $medicine) {
            $description = trim(implode(' ', array_filter([
                $medicine->name,
                $medicine->strength,
                $medicine->dosage_form ? "({$medicine->dosage_form})" : null,
            ])));
        }

        if ($description === '') {
            return redirect()->back()
                ->withInput()
                ->withErrors(['description' => 'يرجى اختيار الدواء من دليل الصيدلية أو كتابة اسمه.'])
                ->with('error', 'يرجى اختيار الدواء من دليل الصيدلية أو كتابة اسم الدواء.');
        }

        $freq = SurgeryTreatment::resolveFrequencyInput(
            $validated['frequency_preset'] ?? null,
            $validated['timing'] ?? null,
            isset($validated['interval_hours']) ? (int) $validated['interval_hours'] : null
        );

        $durationValue = isset($validated['duration_value']) ? (int) $validated['duration_value'] : null;
        $durationUnit = $validated['duration_unit'] ?? ($durationValue ? 'days' : null);

        $totalDoses = SurgeryTreatment::calculateTotalDoses(
            $freq['frequency_mode'],
            $freq['interval_hours'],
            $durationValue,
            $durationUnit
        );

        $startAt = ! empty($validated['start_at'])
            ? Carbon::parse($validated['start_at'])
            : now();

        SurgeryTreatment::create([
            'surgery_id' => $surgery->id,
            'item_type' => SurgeryTreatment::TYPE_MEDICATION,
            'medicine_id' => $medicine?->id,
            'prescribed_by' => $user->id,
            'description' => $description,
            'dosage' => $validated['dosage'] ?? null,
            'route' => $validated['route'] ?? null,
            'timing' => $freq['timing'],
            'frequency_mode' => $freq['frequency_mode'],
            'interval_hours' => $freq['interval_hours'],
            'duration_value' => $durationValue,
            'duration_unit' => $durationUnit,
            'start_at' => $startAt,
            'total_doses' => $totalDoses,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'status' => 'planned',
        ]);

        return redirect()->route('clinical-station.show', array_filter([
            'surgery' => $surgery->id,
            'phase' => $request->input('phase'),
            'tab' => 'treatments',
        ]))->with('success', 'تمت إضافة الدواء بنجاح إلى جدول العلاجات وتوليد مواعيد الجرعات.');
    }

    /**
     * Toggle an individual dose checkbox slot on the MAR schedule (Nurse / Admin only).
     */
    public function toggleTreatmentDose(Request $request, SurgeryTreatment $treatment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);

        if ($treatment->isSupply()) {
            $msg = 'المستلزمات الطبية لا تملك جدول جرعات؛ تُوثّق من تبويب المستلزمات الطبية.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : redirect()->back()->with('error', $msg);
        }

        $access = SurgeryTabPermission::getAccessForUser($user, 'treatments');
        $canConfirm = ($access === SurgeryTabPermission::ACCESS_WRITE)
            && ($isAdmin || ($user && $user->hasRole('nurse')));

        if (! $canConfirm) {
            $msg = 'تأشير صرف وإعطاء الجرعات محصور بكادر التمريض والإدارة فقط.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 403)
                : redirect()->back()->with('error', $msg);
        }

        $validated = $request->validate([
            'slot_index' => 'nullable|integer|min:0|max:59',
            'checked' => 'required|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        $slotIndex = $validated['slot_index'] ?? null;
        if ($slotIndex !== null && ! $treatment->is_prn) {
            $expected = max(1, (int) ($treatment->expected_doses_count ?? 1));
            if ($slotIndex >= $expected) {
                $msg = 'رقم الجرعة المحدد خارج نطاق عدد الجرعات المقرر لهذا الدواء.';

                return $request->expectsJson()
                    ? response()->json(['ok' => false, 'message' => $msg], 422)
                    : redirect()->back()->with('error', $msg);
            }
        }

        $result = $treatment->toggleDoseSlot(
            $slotIndex,
            $user,
            (bool) $validated['checked'],
            $validated['notes'] ?? null,
            $isAdmin
        );

        if (! ($result['ok'] ?? false)) {
            return $request->expectsJson()
                ? response()->json($result, 422)
                : redirect()->back()->with('error', $result['message'] ?? 'تعذر تحديث حالة الجرعة.');
        }

        $treatment->refresh();
        $expectedCount = $treatment->expected_doses_count;
        $adminCount = $treatment->administered_count;
        $progressPercent = ($expectedCount && $expectedCount > 0)
            ? min(100, (int) round(($adminCount / $expectedCount) * 100))
            : null;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'checked' => $result['checked'] ?? (bool) $validated['checked'],
                'slot_index' => $result['slot_index'] ?? $slotIndex,
                'administered_at_label' => $result['administered_at_label'] ?? ($treatment->administered_at ? ($treatment->administered_at->format('h:i').' '.($treatment->administered_at->format('A') === 'AM' ? 'ص' : 'م')) : null),
                'administered_by_name' => $result['administered_by_name'] ?? $treatment->administeredBy?->name,
                'administered_count' => $adminCount,
                'expected_doses_count' => $expectedCount,
                'progress_percent' => $progressPercent,
                'is_prn' => $treatment->is_prn,
                'status' => $treatment->status,
                'message' => $result['message'] ?? 'تم تحديث حالة الجرعة بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', $result['message'] ?? 'تم تحديث حالة الجرعة بنجاح.');
    }

    /**
     * Administer a prescribed treatment (legacy/action route delegating to dose slots).
     */
    public function administerTreatment(Request $request, SurgeryTreatment $treatment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $tabKey = $treatment->isSupply() ? 'supplies' : 'treatments';
        $access = SurgeryTabPermission::getAccessForUser($user, $tabKey);
        $canConfirm = ($access === SurgeryTabPermission::ACCESS_WRITE)
            && ($treatment->isSupply() || $isAdmin || ($user && $user->hasRole('nurse')));

        if (! $canConfirm) {
            return redirect()->back()->with('error', 'غير مصرح لك بتسجيل إعطاء العلاجات؛ التأشير محصور بكادر التمريض والإدارة.');
        }

        $validated = $request->validate([
            'status' => 'required|in:administered,cancelled',
            'slot_index' => 'nullable|integer|min:0|max:59',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['status'] === 'administered') {
            $result = $treatment->toggleDoseSlot(
                $validated['slot_index'] ?? null,
                $user,
                true,
                $validated['admin_notes'] ?? null,
                $isAdmin
            );
            $actionText = $result['message'] ?? 'تسجيل إعطاء الجرعة بنجاح';

            return redirect()->back()->with('success', $actionText);
        }

        $treatment->update([
            'status' => 'cancelled',
            'admin_notes' => $validated['admin_notes'] ?? null,
        ]);

        return redirect()->back()->with('success', 'تم إلغاء العلاج.');
    }

    /**
     * Delete a prescribed treatment (Doctor/Resident/Surgeon/Admin) or dispensed supply (Nurse/Admin).
     */
    public function deleteTreatment(Request $request, SurgeryTreatment $treatment)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $isSupply = $treatment->isSupply();
        $tabKey = $isSupply ? 'supplies' : 'treatments';
        $access = SurgeryTabPermission::getAccessForUser($user, $tabKey);

        $authorized = $isSupply
            ? ($access === SurgeryTabPermission::ACCESS_WRITE)
            : (($access === SurgeryTabPermission::ACCESS_WRITE) && ($isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse'))));

        if (! $authorized) {
            return redirect()->back()->with('error', $isSupply ? 'غير مصرح لك بحذف المستلزمات.' : 'حذف الأوامر الدوائية محصور بالأطباء والمقيمين والإدارة فقط.');
        }

        $surgeryId = $treatment->surgery_id;
        $treatment->delete();

        return redirect()->route('clinical-station.show', array_filter([
            'surgery' => $surgeryId,
            'phase' => $request->input('phase'),
            'tab' => $tabKey,
        ]))->with('success', $isSupply ? 'تم حذف سجل المستلزم الطبي بنجاح.' : 'تم حذف الدواء من الخطة العلاجية بنجاح.');
    }

    /**
     * Dispense medical supply directly from the floor sub-store for the patient (Supplies tab ONLY).
     */
    public function dispenseFloorItem(Request $request, Surgery $surgery)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'batch_id' => 'required|exists:stock_batches,id',
            'quantity' => 'required|integer|min:1',
            'timing' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
            'expected_type' => 'nullable|in:medication,supply',
            'phase' => 'nullable|in:pre_op,post_op',
        ]);

        $access = SurgeryTabPermission::getAccessForUser($user, 'supplies');
        if ($access !== SurgeryTabPermission::ACCESS_WRITE) {
            return redirect()->back()->with('error', 'غير مصرح لك بصرف المستلزمات الطبية.');
        }

        $targetBatch = StockBatch::with('product')->find($validated['batch_id']);
        $isSupplyProduct = $targetBatch?->product?->isSupply() ?? false;

        if (($validated['expected_type'] ?? null) === SurgeryTreatment::TYPE_MEDICATION || ! $isSupplyProduct) {
            return redirect()->back()->with('error', 'مخزن الطابق مخصص للمستلزمات الطبية والمستهلكات فقط؛ تُدار الأدوية من تبويب الأدوية وجدول الإعطاء المرتبط بالصيدلية.');
        }

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
                $movementNotes = "صرف مستلزم لمريض: {$patientName} (غرفة: {$roomNumber})";
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

                // تسجيل المستلزم في جدول مستلزمات المريض السريرية
                $product = $batch->product;
                $unitName = $product?->unit ?? 'قطعة';
                $dosageText = "{$validated['quantity']} {$unitName}";

                SurgeryTreatment::create([
                    'surgery_id' => $surgery->id,
                    'item_type' => SurgeryTreatment::TYPE_SUPPLY,
                    'description' => $product?->name ?? 'مستلزم طبي',
                    'dosage' => $dosageText,
                    'timing' => $validated['timing'] ?: 'صرف مستلزم من عهدة الطابق',
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

            return redirect()->route('clinical-station.show', array_filter([
                'surgery' => $surgery->id,
                'phase' => $validated['phase'] ?? null,
                'tab' => 'supplies',
            ]))->with('success', "تم صرف المستلزم الطبي بنجاح من ({$floorLocation->name}) وخصمه من الرصيد وتوثيقه في تبويب المستلزمات.");
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
     * تعديل قراءة علامات حيوية/سوائل تاريخية فردية
     */
    public function updateReading(Request $request, ResidentStationReading $reading)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canEditFluids = $isAdmin || ($user && $user->hasRole('nurse'));
        $canManageClinicalExam = $isAdmin || ($user && $user->hasAnyRole(['surgeon', 'resident', 'doctor']) && ! $user->hasRole('nurse'));
        $station = $reading->residentStation;
        $surgery = $station?->surgery;

        // Check if user is owner within 30 min, or Admin
        $isOwner = (int) $reading->user_id === (int) $user->id || (empty($reading->user_id) && $reading->resident_id === $user->doctor?->id);
        $isWithin30Min = $reading->created_at ? now()->lte($reading->created_at->copy()->addMinutes(30)) : true;

        if (! $isAdmin && (! $isOwner || ! $isWithin30Min)) {
            return redirect()->back()->with('error', 'عفواً، لا يمكن تعديل هذه القراءة لانقضاء مهلة 30 دقيقة أو لأنها مسجلة بحساب آخر. التعديل محصور بمدير النظام حصراً.');
        }

        $vitalsFields = [
            'bp', 'temp', 'pr', 'rr', 'spo2',
            'pain_score', 'rbs', 'gcs', 'crt',
            'notes',
        ];
        if ($canManageClinicalExam) {
            $vitalsFields[] = 'clinical_examination';
        }
        $vitalsData = $request->only($vitalsFields);

        if ($canEditFluids && $request->hasAny(self::FLUID_FIELDS)) {
            $request->validate([
                'intake_iv_fluids' => 'nullable|numeric|min:0',
                'intake_oral' => 'nullable|numeric|min:0',
                'intake_blood' => 'nullable|numeric|min:0',
                'output_urine' => 'nullable|numeric|min:0',
                'output_drain' => 'nullable|numeric|min:0',
                'output_gtube_ng' => 'nullable|numeric|min:0',
                'output_vomiting' => 'nullable|numeric|min:0',
                'output_stool' => 'nullable|numeric|min:0',
            ]);
            [$fluidsData] = $this->prepareFluidsPayload($request);
            $reading->update(array_merge($vitalsData, $fluidsData));
        } else {
            $reading->update($vitalsData);
        }

        if ($station) {
            $this->syncStationFromReadings($station, $surgery);
        }

        return redirect()->back()->with('success', 'تم تعديل القراءة السريرية بنجاح.');
    }

    /**
     * حذف قراءة علامات حيوية/سوائل تاريخية فردية وإعادة مزامنة المحطة
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

        $reading->delete();

        if ($station) {
            $this->syncStationFromReadings($station, $surgery);
        }

        return redirect()->back()->with('success', 'تم حذف القراءة السريرية وإعادة مزامنة سجل العلامات بنجاح.');
    }

    /**
     * استخراج وتنسيق حقول السوائل الـ 8 وحساب صافي التوازن المائي بدقة
     *
     * @return array{0: array<string, float|null>, 1: bool}
     */
    private function prepareFluidsPayload(Request $request): array
    {
        $fluidsData = [];
        $anyFluidsGiven = false;

        foreach (self::FLUID_FIELDS as $field) {
            $raw = $request->input($field);
            if ($raw !== null && $raw !== '' && is_numeric($raw)) {
                $fluidsData[$field] = round((float) $raw, 2);
                $anyFluidsGiven = true;
            } else {
                $fluidsData[$field] = null;
            }
        }

        if ($anyFluidsGiven) {
            $totalIntake = ($fluidsData['intake_iv_fluids'] ?? 0.0)
                + ($fluidsData['intake_oral'] ?? 0.0)
                + ($fluidsData['intake_blood'] ?? 0.0);
            $totalOutput = ($fluidsData['output_urine'] ?? 0.0)
                + ($fluidsData['output_drain'] ?? 0.0)
                + ($fluidsData['output_gtube_ng'] ?? 0.0)
                + ($fluidsData['output_vomiting'] ?? 0.0)
                + ($fluidsData['output_stool'] ?? 0.0);
            $fluidsData['fluid_balance'] = round($totalIntake - $totalOutput, 2);
        } else {
            $fluidsData['fluid_balance'] = null;
        }

        return [$fluidsData, $anyFluidsGiven];
    }

    /**
     * التحقق من تغيّر أي من حقول السوائل مقارنةً بالقيم المخزنة حالياً في المحطة
     */
    private function isFluidsChanged($station, array $fluidsData): bool
    {
        foreach (self::FLUID_FIELDS as $field) {
            $oldVal = $station?->{$field};
            $newVal = $fluidsData[$field] ?? null;

            $oldFloat = ($oldVal !== null && $oldVal !== '' && is_numeric($oldVal)) ? round((float) $oldVal, 2) : null;
            $newFloat = ($newVal !== null && is_numeric($newVal)) ? round((float) $newVal, 2) : null;

            if ($oldFloat !== $newFloat) {
                return true;
            }
        }

        return false;
    }

    /**
     * إعادة مزامنة أحدث قيم العلامات الحيوية والسوائل على المحطة من أرشيف القراءات
     */
    private function syncStationFromReadings(\App\Models\ResidentStation $station, ?Surgery $surgery): void
    {
        $latestVitalsReading = $station->readings()
            ->where(function ($q) {
                $q->where(fn ($sq) => $sq->whereNotNull('bp')->where('bp', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('temp')->where('temp', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('pr')->where('pr', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('rr')->where('rr', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('spo2')->where('spo2', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('pain_score')->where('pain_score', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('rbs')->where('rbs', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('gcs')->where('gcs', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('crt')->where('crt', '!=', ''))
                    ->orWhere(fn ($sq) => $sq->whereNotNull('clinical_examination')->where('clinical_examination', '!=', ''));
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($latestVitalsReading) {
            $latestExamReading = $station->readings()
                ->whereNotNull('clinical_examination')
                ->where('clinical_examination', '!=', '')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            $vitalsSync = $latestVitalsReading->only([
                'bp', 'temp', 'pr', 'rr', 'spo2',
                'pain_score', 'rbs', 'gcs', 'crt',
            ]);
            $vitalsSync['clinical_examination'] = $latestExamReading?->clinical_examination;
            $station->update(array_merge($vitalsSync, [
                'vitals_recorded_at' => $latestVitalsReading->created_at,
                'vitals_recorded_by' => $latestVitalsReading->user_id,
            ]));

            if ($surgery && $surgery->nursingStation) {
                $nursingVitalsString = implode(' | ', array_filter([
                    ! empty($vitalsSync['bp']) ? "ضغط الدم: {$vitalsSync['bp']}" : null,
                    ! empty($vitalsSync['temp']) ? "الحرارة: {$vitalsSync['temp']}°C" : null,
                    ! empty($vitalsSync['pr']) ? "النبض: {$vitalsSync['pr']} bpm" : null,
                    ! empty($vitalsSync['rr']) ? "التنفس: {$vitalsSync['rr']} /min" : null,
                    ! empty($vitalsSync['spo2']) ? "الأكسجين: {$vitalsSync['spo2']}%" : null,
                ]));
                $surgery->nursingStation->update([
                    'vital_signs' => $nursingVitalsString ?: null,
                ]);
            }
        } else {
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

        $latestFluidsReading = $station->readings()
            ->whereNotNull('fluid_balance')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        if ($latestFluidsReading) {
            $station->update($latestFluidsReading->only(array_merge(self::FLUID_FIELDS, ['fluid_balance'])));
        } else {
            $station->update([
                'intake_iv_fluids' => null,
                'intake_oral' => null,
                'intake_blood' => null,
                'output_urine' => null,
                'output_drain' => null,
                'output_gtube_ng' => null,
                'output_vomiting' => null,
                'output_stool' => null,
                'fluid_balance' => null,
            ]);
        }
    }

    /**
     * Store new lab tests requested from the Clinical Station.
     */
    public function storeLabTest(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $userPermissions = SurgeryTabPermission::getUserTabPermissions($user);
        $canWrite = $isAdmin || (($userPermissions['tests'] ?? '') === SurgeryTabPermission::ACCESS_WRITE);

        if (! $canWrite) {
            abort(403, 'غير مصرح لك بإضافة طلبات تحاليل مخبرية.');
        }

        $validated = $request->validate([
            'lab_test_ids' => 'nullable|array',
            'lab_test_ids.*' => 'integer|exists:lab_tests,id',
            'package_id' => 'nullable|integer|exists:packages,id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|string|in:routine,urgent',
        ]);

        $testIds = array_map('intval', $validated['lab_test_ids'] ?? []);
        if (! empty($validated['package_id'])) {
            $package = Package::find($validated['package_id']);
            if ($package) {
                $pkgTestIds = $package->labTests()->pluck('lab_tests.id')->toArray();
                $testIds = array_values(array_unique(array_merge($testIds, $pkgTestIds)));
            }
        }

        if (empty($testIds)) {
            return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
                ->with('error', 'يرجى اختيار تحليل واحد على الأقل أو باقة تحاليل.');
        }

        $notes = $validated['notes'] ?? null;
        if (! empty($validated['priority']) && $validated['priority'] === 'urgent') {
            $notes = trim('[عاجل STAT] '.($notes ?? ''));
        }

        $addedCount = 0;
        foreach ($testIds as $testId) {
            $surgery->labTests()->create([
                'lab_test_id' => $testId,
                'status' => 'pending',
                'payment_status' => 'pending',
                'notes' => $notes,
            ]);
            $addedCount++;
        }

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'create',
                'surgery',
                "طلب فحص مخبري ({$addedCount} تحاليل) للعملية #{$surgery->id} للمريض {$surgery->patient?->user?->name}",
                $surgery
            );
        }

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
            ->with('success', "تم إرسال {$addedCount} طلب تحليل مخبري بنجاح إلى المختبر وقائمة انتظار الكاشير.");
    }

    /**
     * Delete an unpaid lab test requested from the Clinical Station.
     */
    public function deleteLabTest(Surgery $surgery, SurgeryLabTest $labTest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $userPermissions = SurgeryTabPermission::getUserTabPermissions($user);
        $canWrite = $isAdmin || (($userPermissions['tests'] ?? '') === SurgeryTabPermission::ACCESS_WRITE);

        if (! $canWrite) {
            abort(403, 'غير مصرح لك بحذف هذا الفحص.');
        }

        if ($labTest->surgery_id !== $surgery->id) {
            abort(404, 'الفحص غير مرتبط بهذه العملية.');
        }

        if ($labTest->payment_status === 'paid') {
            return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
                ->with('error', 'لا يمكن حذف فحص تم سداده مسبقاً في الكاشير.');
        }

        $testName = $labTest->labTest?->name ?? 'فحص مخبري';
        $labTest->delete();

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
            ->with('success', "تم حذف التحليل «{$testName}» بنجاح.");
    }

    /**
     * Store a new radiology test requested from the Clinical Station.
     */
    public function storeRadiologyTest(Request $request, Surgery $surgery)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $userPermissions = SurgeryTabPermission::getUserTabPermissions($user);
        $canWrite = $isAdmin || (($userPermissions['tests'] ?? '') === SurgeryTabPermission::ACCESS_WRITE);

        if (! $canWrite) {
            abort(403, 'غير مصرح لك بإضافة طلبات أشعة.');
        }

        $validated = $request->validate([
            'radiology_type_id' => 'required|integer|exists:radiology_types,id',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|string|in:routine,urgent',
        ]);

        $notes = $validated['notes'] ?? null;
        if (! empty($validated['priority']) && $validated['priority'] === 'urgent') {
            $notes = trim('[عاجل STAT] '.($notes ?? ''));
        }

        $surgery->radiologyTests()->create([
            'radiology_type_id' => $validated['radiology_type_id'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'notes' => $notes,
        ]);

        $radType = RadiologyType::find($validated['radiology_type_id']);
        $radName = $radType?->name ?? 'فحص إشعاعي';

        if (class_exists(UserActivityLogger::class)) {
            UserActivityLogger::log(
                'create',
                'surgery',
                "طلب فحص أشعة ({$radName}) للعملية #{$surgery->id} للمريض {$surgery->patient?->user?->name}",
                $surgery
            );
        }

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
            ->with('success', "تم إرسال طلب فحص الأشعة «{$radName}» بنجاح إلى قسم الأشعة وقائمة انتظار الكاشير.");
    }

    /**
     * Delete an unpaid radiology test requested from the Clinical Station.
     */
    public function deleteRadiologyTest(Surgery $surgery, SurgeryRadiologyTest $radiologyTest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $userPermissions = SurgeryTabPermission::getUserTabPermissions($user);
        $canWrite = $isAdmin || (($userPermissions['tests'] ?? '') === SurgeryTabPermission::ACCESS_WRITE);

        if (! $canWrite) {
            abort(403, 'غير مصرح لك بحذف هذا الفحص.');
        }

        if ($radiologyTest->surgery_id !== $surgery->id) {
            abort(404, 'الفحص غير مرتبط بهذه العملية.');
        }

        if ($radiologyTest->payment_status === 'paid') {
            return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
                ->with('error', 'لا يمكن حذف فحص أشعة تم سداده مسبقاً في الكاشير.');
        }

        $radName = $radiologyTest->radiologyType?->name ?? 'فحص أشعة';
        $radiologyTest->delete();

        return redirect()->route('clinical-station.show', ['surgery' => $surgery->id, 'tab' => 'tests'])
            ->with('success', "تم حذف فحص الأشعة «{$radName}» بنجاح.");
    }
}
