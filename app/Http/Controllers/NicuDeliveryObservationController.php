<?php

namespace App\Http\Controllers;

use App\Models\NicuDeliveryObservation;
use App\Models\NicuDeliveryObservationBaby;
use App\Models\NicuSetting;
use App\Models\Patient;
use App\Models\Surgery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NicuDeliveryObservationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    protected function authorizeObservationAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'غير مصرح بالدخول');
        }

        $isAdmin = $user->hasAnyRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        if (! $isAdmin && ! $user->can('manage nicu delivery observations')) {
            abort(403, 'عذراً، لا تملك صلاحية إدارة معاينات المواليد (ساعتين) لقسم الخدج.');
        }
    }

    protected function authorizeSettingsAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'غير مصرح بالدخول');
        }

        $isAdmin = $user->hasAnyRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        if (! $isAdmin && ! $user->can('manage nicu delivery radar settings') && ! $user->hasRole('nicu_doctor')) {
            abort(403, 'عذراً، لا تملك صلاحية تعديل إعدادات معاينة المواليد بالخدج.');
        }
    }

    /**
     * شاشة معاينات الوليد الحية (ساعتان) وأرشيف التسليم والتحويل
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasAnyRole(['admin', 'admin-hsop', 'hospital_admin', 'super_admin']);
        $canView = $isAdmin || ($user && ($user->can('manage nicu delivery observations') || $user->can('view nicu delivery radar')));

        if (! $canView) {
            abort(403, 'عذراً، لا تملك صلاحية عرض معاينات الوليد الحية لقسم الخدج.');
        }

        $observationSettings = NicuSetting::observationConfig();

        $activeObservations = NicuDeliveryObservation::with([
            'babies.incubatorReservation.incubator',
            'surgery',
            'patient.user',
        ])
            ->whereIn('status', [
                NicuDeliveryObservation::STATUS_UNDER_OBSERVATION,
                NicuDeliveryObservation::STATUS_READY_FOR_HANDOVER,
                NicuDeliveryObservation::STATUS_PARTIAL,
            ])
            ->orderBy('started_at', 'asc')
            ->get();

        $archiveSearch = trim((string) $request->input('archive_search', ''));

        $archiveObservations = NicuDeliveryObservation::with([
            'babies.incubatorReservation.incubator',
            'surgery',
            'patient.user',
        ])
            ->when($archiveSearch !== '', function ($q) use ($archiveSearch) {
                $q->where(function ($sub) use ($archiveSearch) {
                    $sub->where('mother_name', 'like', "%{$archiveSearch}%")
                        ->orWhere('recipient_name', 'like', "%{$archiveSearch}%")
                        ->orWhere('recipient_id_number', 'like', "%{$archiveSearch}%")
                        ->orWhere('recipient_phone', 'like', "%{$archiveSearch}%")
                        ->orWhereHas('babies', fn ($b) => $b->where('born_name', 'like', "%{$archiveSearch}%"));
                });
            })
            ->orderByDesc('created_at')
            ->limit(60)
            ->get();

        $readyCount = $activeObservations->filter(fn ($obs) => $obs->isDurationCompleted())->count();

        $stats = [
            'under_observation' => max(0, $activeObservations->count() - $readyCount),
            'ready_for_handover' => $readyCount,
            'admitted_today' => NicuDeliveryObservationBaby::where('disposition', NicuDeliveryObservationBaby::DISPOSITION_ADMITTED_TO_NICU)
                ->whereDate('updated_at', today())
                ->count(),
            'handed_over_today' => NicuDeliveryObservation::where('status', NicuDeliveryObservation::STATUS_HANDED_OVER)
                ->whereDate('handed_over_at', today())
                ->count(),
        ];

        $canManageSettings = $isAdmin || ($user && ($user->can('manage nicu delivery radar settings') || $user->hasRole('nicu_doctor')));
        $canManageObservations = $isAdmin || ($user && $user->can('manage nicu delivery observations'));

        return view('nicu.observations.index', compact(
            'activeObservations',
            'archiveObservations',
            'archiveSearch',
            'observationSettings',
            'stats',
            'canManageSettings',
            'canManageObservations'
        ));
    }

    /**
     * بدء معاينة (ساعتين) مباشرة من عملية قيصرية / ولادة في رادار العمليات
     */
    public function startFromSurgery(Request $request, Surgery $surgery): RedirectResponse
    {
        $this->authorizeObservationAccess();

        $existing = NicuDeliveryObservation::where('surgery_id', $surgery->id)->first();
        if ($existing) {
            return redirect()->route('nicu.observations.show', $existing);
        }

        $surgery->load([
            'patient.user',
            'doctor.user',
            'department',
            'surgicalOperation',
            'additionalOperations.surgicalOperation',
        ]);

        $config = NicuSetting::observationConfig();
        $motherName = $surgery->patient?->user?->name ?? 'مريضة عمليات';
        $surgeonName = $surgery->doctor?->user?->name ?? $surgery->surgeon_name;
        $opName = $surgery->surgicalOperation?->name ?? $surgery->surgery_type ?? 'عملية قيصرية / ولادة';

        if ($surgery->additionalOperations->isNotEmpty()) {
            $addNames = $surgery->additionalOperations
                ->map(fn ($item) => $item->surgicalOperation?->name)
                ->filter()
                ->implode(' + ');
            if ($addNames !== '') {
                $opName .= ' + '.$addNames;
            }
        }

        $deliveryTime = null;
        if ($surgery->start_time) {
            $deliveryTime = $surgery->start_time->format('h:i a');
        } elseif ($surgery->scheduled_time) {
            $deliveryTime = Carbon::parse($surgery->scheduled_time)->format('h:i a');
        } else {
            $deliveryTime = now()->format('h:i a');
        }

        $user = auth()->user();
        $observation = DB::transaction(function () use ($surgery, $config, $motherName, $surgeonName, $opName, $deliveryTime, $user) {
            $ageGender = null;
            if ($surgery->patient && $surgery->patient->age !== null) {
                $ageGender = $surgery->patient->age.'F';
            }

            $obs = NicuDeliveryObservation::create([
                'surgery_id' => $surgery->id,
                'patient_id' => $surgery->patient_id,
                'mother_name' => $motherName,
                'mother_age_gender' => $ageGender,
                'mother_birth_date' => $surgery->patient?->date_of_birth,
                'reference_date' => $surgery->scheduled_date ?? now()->toDateString(),
                'department_name' => $surgery->department?->name ?? 'العمليات الجراحية',
                'requesting_doctor_name' => $surgeonName,
                'report_type' => 'Obstetrics Case Sheet',
                'stay_type' => 'In Patient',
                'admission_date' => $surgery->room_checkin_at?->toDateString() ?? $surgery->scheduled_date?->toDateString() ?? now()->toDateString(),
                'discharge_date' => $surgery->discharged_at?->toDateString(),
                'operative_description' => $opName,
                'delivery_date' => $surgery->scheduled_date?->toDateString() ?? now()->toDateString(),
                'delivery_time' => $deliveryTime,
                'case_of_delivery' => 'SINGLE',
                'no_of_borns' => 1,
                'pre_delivery_diagnosis' => $surgery->diagnosis,
                'post_delivery_diagnosis' => $surgery->post_op_notes,
                'procedure_performed' => $opName,
                'surgeon_name' => $surgeonName,
                'anesthesia_type' => $surgery->anesthesia_type,
                'history_of_illness' => $surgery->notes,
                'delivery_note' => $deliveryTime,
                'started_at' => now(),
                'target_duration_minutes' => $config['duration_minutes'],
                'status' => NicuDeliveryObservation::STATUS_UNDER_OBSERVATION,
                'is_billable' => $config['is_billable'],
                'observation_fee' => $config['is_billable'] ? $config['fee'] : 0,
                'payment_status' => ($config['is_billable'] && $config['fee'] > 0) ? 'pending' : 'free',
                'nicu_doctor_id' => $user?->hasRole('nicu_doctor') ? $user->id : null,
                'nicu_doctor_name' => $user?->hasRole('nicu_doctor') ? $user->name : null,
                'created_by' => $user?->id,
            ]);

            NicuDeliveryObservationBaby::create([
                'observation_id' => $obs->id,
                'born_name' => 'طفل / '.$motherName,
                'live_status' => 'Live Birth',
                'disposition' => NicuDeliveryObservationBaby::DISPOSITION_OBSERVING,
            ]);

            return $obs;
        });

        return redirect()
            ->route('nicu.observations.show', $observation)
            ->with('success', 'تم استلام المولود في قسم الخدج وبدء مؤقت المعاينة ('.$observation->target_duration_minutes.' دقيقة).');
    }

    /**
     * فتح معاينة يدوية لولادة طبيعية أو حالة خارج رادار العمليات
     */
    public function storeManual(Request $request): RedirectResponse
    {
        $this->authorizeObservationAccess();

        $validated = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'mother_name' => 'required|string|max:255',
            'operative_description' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'delivery_time' => 'nullable|string|max:50',
            'case_of_delivery' => 'nullable|string|max:50',
            'no_of_borns' => 'nullable|integer|min:1|max:6',
            'baby_gender' => 'nullable|string|max:30',
            'baby_weight_kg' => 'nullable|numeric|min:0.2|max:10',
            'requesting_doctor_name' => 'nullable|string|max:255',
            'department_name' => 'nullable|string|max:255',
            'delivery_note' => 'nullable|string',
        ]);

        $patient = ! empty($validated['patient_id']) ? Patient::with('user')->find($validated['patient_id']) : null;
        $motherName = $patient?->user?->name ?? $validated['mother_name'];
        $count = max(1, (int) ($validated['no_of_borns'] ?? 1));
        $caseOfDelivery = ($validated['case_of_delivery'] ?? null) ?: match ($count) {
            1 => 'SINGLE',
            2 => 'TWIN',
            3 => 'TRIPLET',
            default => 'MULTIPLE',
        };

        $config = NicuSetting::observationConfig();
        $user = auth()->user();

        $observation = DB::transaction(function () use ($validated, $patient, $motherName, $count, $caseOfDelivery, $config, $user) {
            $ageGender = null;
            if ($patient && $patient->age !== null) {
                $ageGender = $patient->age.'F';
            }

            $deliveryDate = $validated['delivery_date'] ?? now()->toDateString();
            $deliveryTime = $validated['delivery_time'] ?? now()->format('h:i a');

            $obs = NicuDeliveryObservation::create([
                'patient_id' => $patient?->id,
                'mother_name' => $motherName,
                'mother_age_gender' => $ageGender,
                'mother_birth_date' => $patient?->date_of_birth,
                'reference_date' => $deliveryDate,
                'department_name' => $validated['department_name'] ?? 'صالة الولادة الطبيعية',
                'requesting_doctor_name' => $validated['requesting_doctor_name'] ?? null,
                'report_type' => 'Obstetrics Case Sheet',
                'stay_type' => 'In Patient',
                'admission_date' => $deliveryDate,
                'operative_description' => $validated['operative_description'] ?? 'ولادة طبيعية',
                'delivery_date' => $deliveryDate,
                'delivery_time' => $deliveryTime,
                'case_of_delivery' => $caseOfDelivery,
                'no_of_borns' => $count,
                'surgeon_name' => $validated['requesting_doctor_name'] ?? null,
                'delivery_note' => $validated['delivery_note'] ?? $deliveryTime,
                'started_at' => now(),
                'target_duration_minutes' => $config['duration_minutes'],
                'status' => NicuDeliveryObservation::STATUS_UNDER_OBSERVATION,
                'is_billable' => $config['is_billable'],
                'observation_fee' => $config['is_billable'] ? $config['fee'] : 0,
                'payment_status' => ($config['is_billable'] && $config['fee'] > 0) ? 'pending' : 'free',
                'nicu_doctor_id' => $user?->hasRole('nicu_doctor') ? $user->id : null,
                'nicu_doctor_name' => $user?->hasRole('nicu_doctor') ? $user->name : null,
                'created_by' => $user?->id,
            ]);

            for ($i = 1; $i <= $count; $i++) {
                $babyTitle = $count === 1
                    ? 'طفل / '.$motherName
                    : 'طفل '.$i.' / '.$motherName;

                NicuDeliveryObservationBaby::create([
                    'observation_id' => $obs->id,
                    'born_name' => $babyTitle,
                    'live_status' => 'Live Birth',
                    'gender' => $i === 1 ? ($validated['baby_gender'] ?? null) : null,
                    'weight_kg' => $i === 1 ? ($validated['baby_weight_kg'] ?? null) : null,
                    'disposition' => NicuDeliveryObservationBaby::DISPOSITION_OBSERVING,
                ]);
            }

            return $obs;
        });

        return redirect()
            ->route('nicu.observations.show', $observation)
            ->with('success', 'تم تسجيل معاينة المولود بنجاح وبدء مؤقت الساعتين.');
    }

    /**
     * شاشة ملف المعاينة (ساعتان) للوليد واستمارة Obstetric Case Sheet
     */
    public function show(NicuDeliveryObservation $observation): View
    {
        $this->authorizeObservationAccess();

        $observation->load([
            'babies.incubatorReservation.incubator',
            'surgery.patient.user',
            'patient.user',
            'nicuDoctor',
            'handedOverBy',
            'signedFormUploadedBy',
        ]);

        $config = NicuSetting::observationConfig();

        $nicuDoctors = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['nicu_doctor', 'doctor', 'admin']);
        })->orderBy('name')->get();

        $quickTreatments = [
            ['generic_name' => 'Phytomenadione (Vitamin K1)', 'brand_name' => 'Konakion 1 mg IM', 'frequency' => 'Stat once after birth'],
            ['generic_name' => 'Tobramycin / Erythromycin Eye Drops', 'brand_name' => 'Tobrex Ophthalmic', 'frequency' => 'Single prophylactic dose'],
            ['generic_name' => 'Hepatitis B Vaccine', 'brand_name' => 'Engerix-B Pediatric', 'frequency' => '0.5 ml IM within 12h'],
        ];

        return view('nicu.observations.show', compact(
            'observation',
            'config',
            'nicuDoctors',
            'quickTreatments'
        ));
    }

    /**
     * حفظ وتحديث بيانات الاستمارة السريرية والمواليد والأدوية الاختيارية
     */
    public function update(Request $request, NicuDeliveryObservation $observation): RedirectResponse
    {
        $this->authorizeObservationAccess();

        $validated = $request->validate([
            'mother_name' => 'required|string|max:255',
            'mother_age_gender' => 'nullable|string|max:50',
            'mother_birth_date' => 'nullable|date',
            'reference_date' => 'nullable|date',
            'department_name' => 'nullable|string|max:255',
            'requesting_doctor_name' => 'nullable|string|max:255',
            'stay_type' => 'nullable|string|max:100',
            'admission_date' => 'nullable|date',
            'discharge_date' => 'nullable|date',
            'operative_description' => 'nullable|string|max:255',
            'delivery_date' => 'nullable|date',
            'delivery_time' => 'nullable|string|max:50',
            'case_of_delivery' => 'nullable|string|max:50',
            'pre_delivery_diagnosis' => 'nullable|string',
            'post_delivery_diagnosis' => 'nullable|string',
            'procedure_performed' => 'nullable|string',
            'surgeon_name' => 'nullable|string|max:255',
            'anesthesia_type' => 'nullable|string|max:255',
            'assessment' => 'nullable|string',
            'history_of_illness' => 'nullable|string',
            'delivery_note' => 'nullable|string',
            'nicu_doctor_id' => 'nullable|exists:users,id',
            'nicu_doctor_name' => 'nullable|string|max:255',
            'treatments' => 'nullable|array',
            'treatments.*.generic_name' => 'nullable|string|max:255',
            'treatments.*.brand_name' => 'nullable|string|max:255',
            'treatments.*.frequency' => 'nullable|string|max:255',
            'babies' => 'required|array|min:1',
            'babies.*.id' => 'nullable|integer|exists:nicu_delivery_observation_babies,id',
            'babies.*.born_name' => 'required|string|max:255',
            'babies.*.live_status' => 'nullable|string|max:100',
            'babies.*.gender' => 'nullable|string|max:50',
            'babies.*.weight_kg' => 'nullable|numeric|min:0.1|max:15',
            'babies.*.notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($observation, $validated) {
            $treatments = collect($validated['treatments'] ?? [])
                ->map(fn ($row) => [
                    'generic_name' => trim((string) ($row['generic_name'] ?? '')),
                    'brand_name' => trim((string) ($row['brand_name'] ?? '')),
                    'frequency' => trim((string) ($row['frequency'] ?? '')),
                ])
                ->filter(fn ($row) => $row['generic_name'] !== '' || $row['brand_name'] !== '' || $row['frequency'] !== '')
                ->values()
                ->all();

            $doctorName = trim((string) ($validated['nicu_doctor_name'] ?? ''));
            if ($doctorName === '' && ! empty($validated['nicu_doctor_id'])) {
                $doctorName = User::find($validated['nicu_doctor_id'])?->name ?? '';
            }

            $submittedBabies = collect($validated['babies']);
            $submittedIds = $submittedBabies->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

            // حماية المواليد المحولين للحاضنة أو المسجلين مسبقاً من الحذف العرضي
            $observation->babies()
                ->whereNotIn('id', $submittedIds)
                ->where('disposition', NicuDeliveryObservationBaby::DISPOSITION_OBSERVING)
                ->delete();

            foreach ($submittedBabies as $babyData) {
                $babyId = ! empty($babyData['id']) ? (int) $babyData['id'] : null;
                $payload = [
                    'born_name' => trim($babyData['born_name']),
                    'live_status' => trim((string) ($babyData['live_status'] ?? 'Live Birth')) ?: 'Live Birth',
                    'gender' => $babyData['gender'] ?? null,
                    'weight_kg' => (($babyData['weight_kg'] ?? null) !== '' && ($babyData['weight_kg'] ?? null) !== null) ? $babyData['weight_kg'] : null,
                    'notes' => $babyData['notes'] ?? null,
                ];

                if ($babyId) {
                    $existingBaby = $observation->babies()->where('id', $babyId)->first();
                    if ($existingBaby) {
                        $existingBaby->update($payload);

                        continue;
                    }
                }

                $payload['observation_id'] = $observation->id;
                $payload['disposition'] = NicuDeliveryObservationBaby::DISPOSITION_OBSERVING;
                NicuDeliveryObservationBaby::create($payload);
            }

            $babyCount = $observation->babies()->count();
            $caseOfDelivery = $validated['case_of_delivery'] ?? $observation->case_of_delivery;
            if ($babyCount === 1 && in_array($caseOfDelivery, ['TWIN', 'TRIPLET'], true)) {
                $caseOfDelivery = 'SINGLE';
            } elseif ($babyCount === 2 && $caseOfDelivery === 'SINGLE') {
                $caseOfDelivery = 'TWIN';
            } elseif ($babyCount >= 3 && in_array($caseOfDelivery, ['SINGLE', 'TWIN'], true)) {
                $caseOfDelivery = 'TRIPLET';
            }

            $observation->update([
                'mother_name' => $validated['mother_name'],
                'mother_age_gender' => $validated['mother_age_gender'] ?? null,
                'mother_birth_date' => $validated['mother_birth_date'] ?? null,
                'reference_date' => $validated['reference_date'] ?? null,
                'department_name' => $validated['department_name'] ?? null,
                'requesting_doctor_name' => $validated['requesting_doctor_name'] ?? null,
                'stay_type' => $validated['stay_type'] ?? 'In Patient',
                'admission_date' => $validated['admission_date'] ?? null,
                'discharge_date' => $validated['discharge_date'] ?? null,
                'operative_description' => $validated['operative_description'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
                'delivery_time' => $validated['delivery_time'] ?? null,
                'case_of_delivery' => $caseOfDelivery,
                'no_of_borns' => $babyCount,
                'pre_delivery_diagnosis' => $validated['pre_delivery_diagnosis'] ?? null,
                'post_delivery_diagnosis' => $validated['post_delivery_diagnosis'] ?? null,
                'procedure_performed' => $validated['procedure_performed'] ?? null,
                'surgeon_name' => $validated['surgeon_name'] ?? null,
                'anesthesia_type' => $validated['anesthesia_type'] ?? null,
                'assessment' => $validated['assessment'] ?? null,
                'history_of_illness' => $validated['history_of_illness'] ?? null,
                'delivery_note' => $validated['delivery_note'] ?? null,
                'nicu_doctor_id' => $validated['nicu_doctor_id'] ?? null,
                'nicu_doctor_name' => $doctorName !== '' ? $doctorName : null,
                'treatments' => $treatments,
            ]);

            $observation->recalculateStatus();
        });

        return redirect()
            ->route('nicu.observations.show', $observation)
            ->with('success', 'تم حفظ بيانات المعاينة والمواليد بنجاح.');
    }

    /**
     * توثيق التسليم القانوني للأهل
     */
    public function handover(Request $request, NicuDeliveryObservation $observation): RedirectResponse
    {
        $this->authorizeObservationAccess();

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'recipient_relation' => 'nullable|string|max:100',
            'recipient_id_number' => 'required|string|max:100',
            'recipient_phone' => 'required|string|max:100',
            'recipient_address' => 'nullable|string|max:255',
            'handover_text' => 'nullable|string',
            'baby_ids' => 'nullable|array',
            'baby_ids.*' => 'integer|exists:nicu_delivery_observation_babies,id',
        ]);

        DB::transaction(function () use ($observation, $validated) {
            $query = $observation->babies()
                ->where('disposition', NicuDeliveryObservationBaby::DISPOSITION_OBSERVING);

            if (! empty($validated['baby_ids'])) {
                $query->whereIn('id', $validated['baby_ids']);
            }

            $query->update([
                'disposition' => NicuDeliveryObservationBaby::DISPOSITION_HANDED_OVER,
            ]);

            $observation->update([
                'recipient_name' => $validated['recipient_name'],
                'recipient_relation' => $validated['recipient_relation'] ?? 'الأب',
                'recipient_id_number' => $validated['recipient_id_number'],
                'recipient_phone' => $validated['recipient_phone'],
                'recipient_address' => $validated['recipient_address'] ?? null,
                'handover_text' => $validated['handover_text'] ?? null,
                'handed_over_at' => now(),
                'handed_over_by' => auth()->id(),
            ]);

            $observation->recalculateStatus();
        });

        return redirect()
            ->route('nicu.observations.show', $observation)
            ->with('success', 'تم توثيق تسليم المولود للأهل وحفظ بيانات المستلم بنجاح.');
    }

    /**
     * رفع وأرشفة نسخة الاستمارة الموقعة والمبصمة
     */
    public function uploadSignedForm(Request $request, NicuDeliveryObservation $observation): RedirectResponse
    {
        $this->authorizeObservationAccess();

        $request->validate([
            'signed_form' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ]);

        if ($observation->signed_form_path && Storage::disk('public')->exists($observation->signed_form_path)) {
            Storage::disk('public')->delete($observation->signed_form_path);
        }

        $path = $request->file('signed_form')->store('nicu_observations/signed_forms', 'public');

        $observation->update([
            'signed_form_path' => $path,
            'signed_form_uploaded_at' => now(),
            'signed_form_uploaded_by' => auth()->id(),
        ]);

        return redirect()
            ->route('nicu.observations.show', $observation)
            ->with('success', 'تم أرشفة الاستمارة الموقعة والمبصمة بنجاح.');
    }

    /**
     * تحميل أو عرض نسخة الاستمارة المؤرشفة
     */
    public function downloadSignedForm(NicuDeliveryObservation $observation)
    {
        $this->authorizeObservationAccess();

        if (! $observation->signed_form_path || ! Storage::disk('public')->exists($observation->signed_form_path)) {
            abort(404, 'لا توجد استمارة مؤرشفة لهذه الحالة.');
        }

        return response()->file(Storage::disk('public')->path($observation->signed_form_path));
    }

    /**
     * طباعة الاستمارة الرسمية A4 (Obstetric Case Sheet)
     */
    public function print(NicuDeliveryObservation $observation): View
    {
        $this->authorizeObservationAccess();

        $observation->load([
            'babies',
            'patient.user',
            'surgery',
            'nicuDoctor',
        ]);

        $config = NicuSetting::observationConfig();

        return view('nicu.observations.print', compact('observation', 'config'));
    }

    /**
     * تحديث الإعدادات الديناميكية لمدة المعاينة والأجور وترويسة الطباعة
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $this->authorizeSettingsAccess();

        $validated = $request->validate([
            'observation_duration_minutes' => 'required|integer|min:15|max:720',
            'observation_is_billable' => 'nullable|boolean',
            'observation_fee' => 'nullable|numeric|min:0|max:10000000',
            'hospital_print_name_ar' => 'nullable|string|max:255',
            'hospital_print_name_en' => 'nullable|string|max:255',
            'hospital_print_email' => 'nullable|string|max:255',
            'hospital_print_phone' => 'nullable|string|max:100',
            'hospital_print_address' => 'nullable|string|max:255',
        ]);

        $isBillable = $request->boolean('observation_is_billable');

        NicuSetting::setValue('observation_duration_minutes', (int) $validated['observation_duration_minutes']);
        NicuSetting::setValue('observation_is_billable', $isBillable ? '1' : '0');
        NicuSetting::setValue('observation_fee', (float) ($validated['observation_fee'] ?? 0));
        NicuSetting::setValue('hospital_print_name_ar', trim((string) ($validated['hospital_print_name_ar'] ?? 'مستشفى الكفاءات الأهلي')));
        NicuSetting::setValue('hospital_print_name_en', trim((string) ($validated['hospital_print_name_en'] ?? 'Al-Kafaat Hospital')));
        NicuSetting::setValue('hospital_print_email', trim((string) ($validated['hospital_print_email'] ?? '')));
        NicuSetting::setValue('hospital_print_phone', trim((string) ($validated['hospital_print_phone'] ?? '')));
        NicuSetting::setValue('hospital_print_address', trim((string) ($validated['hospital_print_address'] ?? '')));

        return redirect()
            ->route('nicu.delivery-radar.settings')
            ->with('success', 'تم حفظ إعدادات معاينة المواليد وترويسة الطباعة بنجاح.');
    }
}
