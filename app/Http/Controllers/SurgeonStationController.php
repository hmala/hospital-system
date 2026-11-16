<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Surgery;
use App\Models\SurgeryTreatment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SurgeonStationController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSharedAccount = (
            in_array($user->name, ['حساب الجراحين', 'حساب الجراحين العام', 'حساب أطباء الجراحة', 'حساب الجراحة'])
            || $user->email === 'gh@hospital.com'
            || $user->hasRole(['admin', 'receptionist', 'surgery_staff'])
            || ($user->hasRole(['الجراح', 'جراح', 'surgeon']) && ! $user->doctor)
        );

        // جلب العمليات لمحطة الجراح (كافة العمليات النشطة في صالة العمليات مثل محطة التخدير)
        $query = Surgery::with([
            'patient.user',
            'doctor.user',
            'surgeonStation.surgeon.user',
            'department',
        ])->where('status', '!=', 'cancelled');

        // فلترة الطبيب الجراح (إذا تم اختياره من القائمة المنسدلة)
        if ($request->filled('doctor_id')) {
            if ($request->doctor_id === 'unassigned') {
                $query->whereNull('doctor_id')
                    ->where(function ($q) {
                        $q->whereDoesntHave('surgeonStation')
                            ->orWhereHas('surgeonStation', fn ($sq) => $sq->whereNull('surgeon_id'));
                    });
            } else {
                $selectedDocId = $request->doctor_id;
                $query->where(function ($q) use ($selectedDocId) {
                    $q->where('doctor_id', $selectedDocId)
                        ->orWhereHas('surgeonStation', function ($sq) use ($selectedDocId) {
                            $sq->where('surgeon_id', $selectedDocId);
                        });
                });
            }
        } elseif (! $isSharedAccount && $user->doctor && ! $user->can('view surgeon station')) {
            // إذا كان طبيباً عادياً لا يحمل صلاحية محطة الجراح العامة
            $surgeonDoctorIds = $this->getSurgeonDoctorIds($user);
            $query->where(function ($q) use ($surgeonDoctorIds) {
                $q->whereIn('doctor_id', $surgeonDoctorIds)
                    ->orWhereHas('surgeonStation', function ($sq) use ($surgeonDoctorIds) {
                        $sq->whereIn('surgeon_id', $surgeonDoctorIds);
                    });
            });
        }

        // فلترة التاريخ
        if ($request->filled('date')) {
            if ($request->date === 'today') {
                $query->whereDate('scheduled_date', today());
            } elseif ($request->date !== 'all') {
                $query->whereDate('scheduled_date', $request->date);
            }
        }

        // فلترة حالة محطة الجراح
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'pending') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('surgeonStation')
                        ->orWhereHas('surgeonStation', fn ($sq) => $sq->where('status', 'pending'));
                });
            } else {
                $query->whereHas('surgeonStation', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }
        }

        // فلترة البحث النصي بالاسم، رقم الملف، اسم العملية، واسم الطبيب الجراح
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('surgery_name', 'like', "%{$search}%")
                    ->orWhere('surgery_type', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('file_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('doctor.user', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $surgeries = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get();

        // قائمة الجراحين للاختيار منها في الفلتر
        $surgeons = Doctor::where('type', 'surgeon')
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->sortBy(fn ($d) => $d->user?->name ?? '');

        return view('surgery-stations.surgeon.index', compact('surgeries', 'isSharedAccount', 'surgeons'));
    }

    public function show(Surgery $surgery)
    {
        $this->authorizeSurgeon($surgery);

        // إنشاء محطة إذا لم تكن موجودة ومزامنة الجراح
        if (! $surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        } elseif (! $surgery->surgeonStation->surgeon_id && $surgery->doctor_id) {
            $surgery->surgeonStation()->update([
                'surgeon_id' => $surgery->doctor_id,
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

        $combinedReadings = collect();
        if ($surgery->preOpResidentStation) {
            $combinedReadings = $combinedReadings->concat($surgery->preOpResidentStation->readings->map(function ($reading) {
                $reading->phase_label = 'قبل دخول الصالة';

                return $reading;
            }));
        }
        if ($surgery->postOpResidentStation) {
            $combinedReadings = $combinedReadings->concat($surgery->postOpResidentStation->readings->map(function ($reading) {
                $reading->phase_label = 'بعد العملية';

                return $reading;
            }));
        }
        $combinedReadings = $combinedReadings->sortByDesc('created_at');

        return view('surgery-stations.surgeon.follow-ups', compact('surgery', 'combinedReadings'));
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
        if (! $station) {
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
            $surgery->surgeryTreatments()
                ->where('item_type', SurgeryTreatment::TYPE_MEDICATION)
                ->whereNull('batch_id')
                ->delete();
            $treatments = $prescribedMedications['surgery_treatments'][$surgery->id];
            foreach ($treatments as $index => $treatment) {
                if (! empty($treatment['description'])) {
                    SurgeryTreatment::create([
                        'surgery_id' => $surgery->id,
                        'item_type' => SurgeryTreatment::TYPE_MEDICATION,
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
        if (! $station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة التخدير التالية
        if (! $surgery->anesthesiaStation) {
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
        if (! $user) {
            abort(401);
        }

        // الحساب الموحد لصالة العمليات أو الإدارة لديهم صلاحية كاملة على كل العمليات
        $isShared = (
            in_array($user->name, ['حساب الجراحين', 'حساب الجراحين العام', 'حساب أطباء الجراحة', 'حساب الجراحة'])
            || $user->email === 'gh@hospital.com'
            || $user->hasRole(['admin', 'receptionist', 'surgery_staff'])
            || ($user->hasRole(['الجراح', 'جراح', 'surgeon']) && ! $user->doctor)
        );

        if ($isShared) {
            return;
        }

        if ($user->doctor) {
            $surgeonDoctorIds = $this->getSurgeonDoctorIds($user);
            $surgeryDoctorIds = array_filter([$surgery->doctor_id, $surgery->surgeonStation?->surgeon_id]);
            if (empty(array_intersect($surgeryDoctorIds, $surgeonDoctorIds))) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه العملية الجراحية المسندة لجراح آخر');
            }
        }
    }

    /**
     * جلب كافة المعرّفات المرتبطة بالطبيب الجراح لمعالجة الازدواجية التاريخية
     */
    protected function getSurgeonDoctorIds($user): array
    {
        $ids = [];
        if ($user->doctor) {
            $ids[] = $user->doctor->id;
        }

        $doctorByUser = Doctor::where('user_id', $user->id)->pluck('id')->toArray();
        $ids = array_merge($ids, $doctorByUser);

        // مطابقة أي حساب طبيب مسجل بنفس اسم المستخدم (مع إزالة البادئات د. أو دكتور)
        $cleanName = trim(preg_replace('/^(د\.|دكتور|دكتورة)\s*/u', '', $user->name));
        if (! empty($cleanName)) {
            $matchedByName = Doctor::whereHas('user', function ($q) use ($cleanName) {
                $q->where('name', 'like', "%{$cleanName}%");
            })->pluck('id')->toArray();

            $ids = array_merge($ids, $matchedByName);
        }

        return array_values(array_unique(array_filter($ids)));
    }
}
