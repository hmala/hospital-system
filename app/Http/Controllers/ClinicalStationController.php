<?php

namespace App\Http\Controllers;

use App\Models\NursingStation;
use App\Models\ResidentStationFollowUp;
use App\Models\Surgery;
use App\Models\SurgeryTabPermission;
use App\Models\SurgeryTreatment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClinicalStationController extends Controller
{
    /**
     * Display the Unified Clinical Surgery Station.
     */
    public function show(Surgery $surgery, Request $request)
    {
        $user = Auth::user();

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
            'labTests.test',
            'radiologyTests.radiologyService',
        ]);

        // Combined readings for vitals history and charts
        $readings = $station ? $station->readings()->with('resident.user')->get() : collect();
        $readingsAsc = $readings->sortBy('created_at');

        // All follow-up logs
        $followUps = ResidentStationFollowUp::where('surgery_id', $surgery->id)
            ->orderBy('follow_up_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Prescribed treatments
        $treatments = $surgery->surgeryTreatments()->orderBy('sort_order')->orderBy('created_at')->get();

        $nurses = User::role('nurse')->where('is_active', true)->get();
        $residents = User::role('resident')->where('is_active', true)->get();

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
            'nurses',
            'residents'
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
                            ! empty($vitalsData['spo2']) || ! empty($vitalsData['clinical_examination']);

            if ($hasAnyVitals) {
                // Check if changed from current
                $isChanged = ($station->bp !== ($vitalsData['bp'] ?? null)) ||
                             ($station->temp !== ($vitalsData['temp'] ?? null)) ||
                             ($station->pr !== ($vitalsData['pr'] ?? null)) ||
                             ($station->rr !== ($vitalsData['rr'] ?? null)) ||
                             ($station->spo2 !== ($vitalsData['spo2'] ?? null)) ||
                             ($station->clinical_examination !== ($vitalsData['clinical_examination'] ?? null));

                if ($isChanged) {
                    $station->readings()->create(array_merge($vitalsData, [
                        'resident_id' => $user->doctor?->id,
                        'notes' => $request->input('vital_notes'),
                    ]));
                }
            }

            $station->update($vitalsData);

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
}
