<?php

namespace App\Http\Controllers;

use App\Models\CcuService;
use App\Models\CcuServiceLog;
use App\Models\Doctor;
use App\Models\IcuAdmission;
use App\Models\IcuBed;
use App\Models\IcuVitalSign;
use App\Models\Patient;
use App\Services\CcuBillingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IcuAdmissionController extends Controller
{
    public function create(Request $request)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('admit icu patients')) {
            abort(403, 'غير مصرح لك بتسجيل دخول مرضى العناية المركزة');
        }

        app(CcuBillingService::class)->ensureDefaultsExist();

        $beds = IcuBed::where('is_active', true)->orderByNumber()->get();
        $doctors = Doctor::with('user')->where('is_active', true)->get()->sortBy(fn ($d) => optional($d->user)->name);
        $ccuServices = CcuService::active()
            ->where('service_type', CcuService::TYPE_PACKAGE)
            ->orderBy('id')
            ->get();

        $selectedBedId = $request->bed_id ?? null;
        $selectedPatientId = $request->patient_id ?? null;
        $selectedPatient = null;
        if ($selectedPatientId) {
            $selectedPatient = Patient::with(['user', 'healthInsuranceCategory'])->find($selectedPatientId);
        }

        return view('icu.admit', compact('beds', 'doctors', 'ccuServices', 'selectedBedId', 'selectedPatientId', 'selectedPatient'));
    }

    public function searchPatients(Request $request)
    {
        $query = trim($request->get('query') ?? $request->get('term') ?? '');

        if (empty($query)) {
            return response()->json([]);
        }

        $patients = Patient::with(['user', 'healthInsuranceCategory'])
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($uq) use ($query) {
                    $uq->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('phone', 'LIKE', "%{$query}%");
                })
                    ->orWhere('id', $query)
                    ->orWhere('national_id', 'LIKE', "%{$query}%")
                    ->orWhere('insurance_number', 'LIKE', "%{$query}%");
            })
            ->limit(20)
            ->get()
            ->map(function ($patient) {
                $user = $patient->user;
                $insuranceLabel = match ($patient->insurance_type) {
                    'moi' => 'ضمان قوى الأمن الداخلي',
                    'hi' => 'هيئة الضمان الصحي الوطني',
                    default => 'بدون ضمان'
                };

                return [
                    'id' => $patient->id,
                    'name' => $user?->name ?? $patient->name ?? 'مريض غير محدد',
                    'phone' => $user?->phone ?? $patient->phone ?? 'لا يوجد هاتف',
                    'national_id' => $patient->national_id ?? '',
                    'age' => $patient->age,
                    'gender' => $patient->gender ?? $user?->gender ?? '',
                    'insurance_type' => $patient->insurance_type ?? 'none',
                    'insurance_label' => $insuranceLabel,
                    'insurance_card_no' => $patient->insurance_card_no ?: ($patient->insurance_number ?: ''),
                ];
            });

        return response()->json($patients);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('admit icu patients')) {
            abort(403, 'غير مصرح لك بتسجيل دخول مرضى العناية المركزة');
        }

        $validated = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'icu_bed_id' => 'required|exists:icu_beds,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'ccu_service_id' => 'nullable|exists:ccu_services,id',
            'admission_source' => 'required|string|in:emergency,operation_theater,ward,external_referral',
            'admission_date' => 'required|date',
            'primary_diagnosis' => 'nullable|string|max:500',
            'icd10_code' => 'nullable|string|max:50',
            'initial_gcs' => 'nullable|integer|min:3|max:15',
            'is_ventilated' => 'nullable|boolean',
            'is_isolated' => 'nullable|boolean',
            'is_insured' => 'nullable|boolean',
            'insurance_type' => 'nullable|string|in:none,hi,health_insurance,moi',
            'insurance_category' => 'nullable|string|max:100',
            'policy_category' => 'nullable|string|max:50',
            'pricing_mode' => 'nullable|string|in:package,bed_plus_services',
        ]);

        $bed = IcuBed::findOrFail($validated['icu_bed_id']);
        if ($bed->status !== IcuBed::STATUS_AVAILABLE) {
            return back()->withInput()->with('error', 'عذراً، هذا السرير غير متاح حالياً للحجز.');
        }

        return DB::transaction(function () use ($validated, $bed) {
            $billingService = app(CcuBillingService::class);
            $billingService->ensureDefaultsExist();

            $patient = Patient::with('healthInsuranceCategory')->find($validated['patient_id']);
            $isInsured = ! empty($validated['is_insured']);
            $insuranceType = $isInsured
                ? ((! empty($validated['insurance_type']) && $validated['insurance_type'] !== 'none') ? $validated['insurance_type'] : 'hi')
                : 'none';

            $rawCat = $validated['policy_category']
                ?? $validated['insurance_category']
                ?? optional($patient?->healthInsuranceCategory)->name
                ?? '';
            $cleanCategory = strtoupper(trim(str_replace('الفئة', '', (string) $rawCat)));
            $policyCategory = $isInsured ? ($cleanCategory ?: 'E') : null;

            $isVentilated = ! empty($validated['is_ventilated']);

            $service = ! empty($validated['ccu_service_id'])
                ? CcuService::find($validated['ccu_service_id'])
                : null;

            if (! $service) {
                if ($isVentilated) {
                    $service = CcuService::where('code', 'ventilator_support')->first();
                } elseif ($bed->bed_type === IcuBed::TYPE_CCU) {
                    $service = CcuService::where('code', 'ccu_cardiac')->first();
                } elseif ($isInsured) {
                    $service = CcuService::where('code', 'oxygen_monitoring')->first();
                } else {
                    $service = CcuService::where('code', 'cash_bed_only_separate')->first()
                        ?? CcuService::where('code', 'oxygen_monitoring')->first();
                }
            }

            if ($service && ($service->code === 'ventilator_support' || str_contains(strtolower($service->name), 'ventilator'))) {
                $isVentilated = true;
            }

            $pricingMode = $validated['pricing_mode']
                ?? ($service && ! $service->include_bed_fee ? 'bed_plus_services' : 'package');

            $validated['admission_date'] = Carbon::parse($validated['admission_date']);
            $validated['is_ventilated'] = $isVentilated;
            $validated['is_isolated'] = ! empty($validated['is_isolated']) || $bed->bed_type === IcuBed::TYPE_ISOLATION;
            $validated['is_insured'] = $isInsured;
            $validated['insurance_type'] = $insuranceType;
            $validated['policy_category'] = $policyCategory;
            $validated['ccu_service_id'] = $service?->id;
            $validated['pricing_mode'] = $pricingMode;
            $validated['status'] = IcuAdmission::STATUS_ACTIVE;

            $admission = IcuAdmission::create($validated);

            // تحديث حالة السرير إلى مشغول
            $bed->update(['status' => IcuBed::STATUS_OCCUPIED]);

            // تدوين سجل الباقة / نظام التسعير الأولي للرقود
            if ($service) {
                $serviceOnlyFee = $service->getServiceOnlyFee($insuranceType);
                $appliedBedFee = $service->include_bed_fee ? 0.0 : (float) $bed->daily_price;

                CcuServiceLog::create([
                    'icu_admission_id' => $admission->id,
                    'ccu_service_id' => $service->id,
                    'log_category' => CcuServiceLog::CATEGORY_STAY_PACKAGE,
                    'service_name_snapshot' => $service->name,
                    'daily_fee' => $serviceOnlyFee,
                    'bed_daily_fee' => $appliedBedFee,
                    'start_time' => $validated['admission_date'],
                    'end_time' => null,
                    'days_count' => 1,
                    'total_cost' => $serviceOnlyFee + $appliedBedFee,
                    'reason_for_switch' => 'تسكين أولي عند الاستعلامات',
                    'switched_by' => Auth::id(),
                ]);
            }

            $billingService->calculateStayDetails($admission);

            // تدوين القراءة الحيوية الأولية ومقياس الوعي
            if (! empty($validated['initial_gcs'])) {
                IcuVitalSign::create([
                    'icu_admission_id' => $admission->id,
                    'recorded_at' => $validated['admission_date'],
                    'recorded_by' => Auth::id(),
                    'gcs' => $validated['initial_gcs'],
                    'notes' => 'التقييم الأولي لمستوى الوعي عند دخول العناية',
                ]);
            }

            return redirect()->route('icu.show', $admission->id)
                ->with('success', "تم تسجيل دخول المريض بنجاح وتسكينه على السرير ({$bed->bed_number}).");
        });
    }

    public function discharge(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('discharge icu patients')) {
            abort(403, 'غير مصرح لك بتخريج مريض العناية المركزة');
        }

        $validated = $request->validate([
            'discharge_destination' => 'required|string|max:255',
            'discharge_summary' => 'required|string|max:2000',
        ]);

        DB::transaction(function () use ($admission, $validated) {
            $admission->update([
                'status' => IcuAdmission::STATUS_DISCHARGED,
                'discharge_date' => Carbon::now(),
                'discharge_destination' => $validated['discharge_destination'],
                'discharge_summary' => $validated['discharge_summary'],
                'discharged_by' => Auth::id(),
            ]);

            // تحويل السرير فوراً إلى قيد التعقيم
            if ($admission->bed) {
                $admission->bed->update(['status' => IcuBed::STATUS_STERILIZING]);
            }
        });

        return redirect()->route('icu.index')
            ->with('success', "تم تخريج المريض بنجاح ونقل السرير رقم ({$admission->bed->bed_number}) إلى مرحلة التعقيم.");
    }

    public function transferBed(Request $request, IcuAdmission $admission)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu beds')) {
            abort(403, 'غير مصرح لك بنقل مريض بين الأسرة');
        }

        $validated = $request->validate([
            'new_bed_id' => 'required|exists:icu_beds,id',
        ]);

        $newBed = IcuBed::findOrFail($validated['new_bed_id']);
        if ($newBed->status !== IcuBed::STATUS_AVAILABLE) {
            return back()->with('error', 'السرير المستهدف غير متاح للنقل.');
        }

        $oldBed = $admission->bed;

        DB::transaction(function () use ($admission, $oldBed, $newBed) {
            $oldBed->update(['status' => IcuBed::STATUS_STERILIZING]);
            $newBed->update(['status' => IcuBed::STATUS_OCCUPIED]);

            $admission->update([
                'icu_bed_id' => $newBed->id,
                'is_isolated' => $newBed->bed_type === IcuBed::TYPE_ISOLATION,
            ]);
        });

        return redirect()->route('icu.show', $admission->id)
            ->with('success', "تم نقل المريض من السرير ({$oldBed->bed_number}) إلى السرير ({$newBed->bed_number}) بنجاح.");
    }
}
