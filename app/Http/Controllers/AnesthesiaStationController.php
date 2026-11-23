<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Surgery;
use Illuminate\Http\Request;

class AnesthesiaStationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isSharedAccount = (
            in_array($user->name, ['حساب أطباء التخدير', 'حساب اطباء التخدير', 'جساب اطباء التخدير', 'حساب التخدير'])
            || $user->email === 'anth@hospital.com'
            || $user->hasRole(['admin', 'receptionist', 'surgery_staff'])
            || ($user->hasRole('التخدير') && ! $user->doctor)
        );

        // جلب العمليات لمحطة التخدير (كافة العمليات النشطة في صالة العمليات)
        $query = Surgery::with([
            'patient.user',
            'doctor.user',
            'department',
            'anesthesiaStation.anesthesiologist.user',
            'anesthesiaStation.anesthesiologist2.user',
            'anesthesiologist.user',
            'anesthesiologist2.user',
        ])->where('status', '!=', 'cancelled');

        // فلترة طبيب التخدير (إذا تم اختياره من القائمة المنسدلة)
        if ($request->filled('anesthesiologist_id')) {
            if ($request->anesthesiologist_id === 'unassigned') {
                $query->whereNull('anesthesiologist_id')
                    ->where(function ($q) {
                        $q->whereDoesntHave('anesthesiaStation')
                            ->orWhereHas('anesthesiaStation', fn ($sq) => $sq->whereNull('anesthesiologist_id'));
                    });
            } else {
                $selectedDocId = $request->anesthesiologist_id;
                $query->where(function ($q) use ($selectedDocId) {
                    $q->where('anesthesiologist_id', $selectedDocId)
                        ->orWhere('anesthesiologist_2_id', $selectedDocId)
                        ->orWhereHas('anesthesiaStation', function ($sq) use ($selectedDocId) {
                            $sq->where('anesthesiologist_id', $selectedDocId)
                                ->orWhere('anesthesiologist_2_id', $selectedDocId);
                        });
                });
            }
        } elseif (! $isSharedAccount && $user->doctor && ! $user->can('view anesthesia station')) {
            // إذا كان طبيباً عادياً لا يحمل صلاحية محطة التخدير العامة
            $doctorIds = $this->getAnesthesiologistDoctorIds($user);
            $query->where(function ($q) use ($doctorIds) {
                $q->whereIn('anesthesiologist_id', $doctorIds)
                    ->orWhereIn('anesthesiologist_2_id', $doctorIds)
                    ->orWhereHas('anesthesiaStation', function ($sq) use ($doctorIds) {
                        $sq->whereIn('anesthesiologist_id', $doctorIds)
                            ->orWhereIn('anesthesiologist_2_id', $doctorIds);
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

        // فلترة حالة محطة التخدير
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'pending') {
                $query->where(function ($q) {
                    $q->whereDoesntHave('anesthesiaStation')
                        ->orWhereHas('anesthesiaStation', fn ($sq) => $sq->where('status', 'pending'));
                });
            } else {
                $query->whereHas('anesthesiaStation', function ($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }
        }

        // فلترة البحث النصي (اسم المريض، رقم الملف، اسم العملية، نوع التخدير، اسم الطبيب)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('surgery_type', 'like', "%{$search}%")
                    ->orWhere('anesthesia_type', 'like', "%{$search}%")
                    ->orWhereHas('patient.user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('patient', function ($pq) use ($search) {
                        $pq->where('file_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('anesthesiaStation.anesthesiologist.user', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('anesthesiologist.user', function ($dq) use ($search) {
                        $dq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $surgeries = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get();

        // قائمة أطباء التخدير للاختيار منها في الحساب الموحد
        $anesthesiologists = Doctor::anesthesia()
            ->where('is_active', true)
            ->with('user')
            ->get()
            ->sortBy(fn ($d) => $d->user?->name ?? '');

        return view('surgery-stations.anesthesia.index', compact('surgeries', 'isSharedAccount', 'anesthesiologists'));
    }

    public function show(Surgery $surgery)
    {
        $this->authorizeAnesthesiologist($surgery);

        // إنشاء محطة إذا لم تكن موجودة، أو مزامنة بيانات التخدير من جدول العمليات إذا توفر
        if (! $surgery->anesthesiaStation) {
            $surgery->anesthesiaStation()->create([
                'anesthesiologist_id' => $surgery->anesthesiologist_id ?? $surgery->operationTheaterStation?->anesthesiologist_id,
                'anesthesiologist_2_id' => $surgery->anesthesiologist_2_id,
                'surgical_assistant_name' => $surgery->surgical_assistant_name,
                'anesthesia_type' => $surgery->anesthesia_type,
                'status' => 'pending',
            ]);
        } else {
            $syncData = [];
            if (! $surgery->anesthesiaStation->anesthesiologist_id && $surgery->anesthesiologist_id) {
                $syncData['anesthesiologist_id'] = $surgery->anesthesiologist_id;
            }
            if (! $surgery->anesthesiaStation->anesthesiologist_2_id && $surgery->anesthesiologist_2_id) {
                $syncData['anesthesiologist_2_id'] = $surgery->anesthesiologist_2_id;
            }
            if (! $surgery->anesthesiaStation->surgical_assistant_name && $surgery->surgical_assistant_name) {
                $syncData['surgical_assistant_name'] = $surgery->surgical_assistant_name;
            }
            if (! $surgery->anesthesiaStation->anesthesia_type && $surgery->anesthesia_type) {
                $syncData['anesthesia_type'] = $surgery->anesthesia_type;
            }
            if (! empty($syncData)) {
                $surgery->anesthesiaStation->update($syncData);
            }
        }

        $surgery->load(['patient.user', 'doctor.user', 'anesthesiaStation.anesthesiologist.user', 'anesthesiaStation.anesthesiologist2.user']);

        $doctors = Doctor::with('user')
            ->anesthesia()
            ->orderBy('id')
            ->get();

        return view('surgery-stations.anesthesia.show', compact('surgery', 'doctors'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $this->authorizeAnesthesiologist($surgery);

        $validated = $request->validate([
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'anesthesia_type' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:pending,in_progress,completed',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $station = $surgery->anesthesiaStation;
        if (! $station) {
            $station = $surgery->anesthesiaStation()->create([
                'status' => $request->status ?? 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update($validated);

        // تحديث نوع وطبيب التخدير في جدول العمليات الرئيسي أيضاً للمزامنة
        $syncSurgery = [];
        if ($request->filled('anesthesia_type')) {
            $syncSurgery['anesthesia_type'] = $request->anesthesia_type;
        }
        if ($request->has('anesthesiologist_id')) {
            $syncSurgery['anesthesiologist_id'] = $request->anesthesiologist_id;
        }
        if ($request->has('anesthesiologist_2_id')) {
            $syncSurgery['anesthesiologist_2_id'] = $request->anesthesiologist_2_id;
        }
        if ($request->filled('surgical_assistant_name')) {
            $syncSurgery['surgical_assistant_name'] = $request->surgical_assistant_name;
        }
        if (! empty($syncSurgery)) {
            $surgery->update($syncSurgery);
        }

        return redirect()->route('anesthesia-station.show', $surgery)
            ->with('success', 'تم حفظ وتحديث بيانات محطة التخدير بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $this->authorizeAnesthesiologist($surgery);

        $station = $surgery->anesthesiaStation;
        if (! $station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة المقيم (post_op) التالية
        $postOpStation = $surgery->postOpResidentStation;
        if (! $postOpStation) {
            $preOpStation = $surgery->preOpResidentStation;
            $surgery->residentStations()->create([
                'phase' => 'post_op',
                'resident_id' => $preOpStation?->resident_id,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('anesthesia-station.index')
            ->with('success', 'تم إتمام محطة التخدير والانتقال لمحطة المقيم (متابعة)');
    }

    private function authorizeAnesthesiologist(Surgery $surgery)
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        $isSharedAccount = (
            in_array($user->name, ['حساب أطباء التخدير', 'حساب اطباء التخدير', 'جساب اطباء التخدير', 'حساب التخدير'])
            || $user->email === 'anth@hospital.com'
            || $user->hasRole(['admin', 'receptionist', 'surgery_staff'])
            || ($user->hasRole('التخدير') && ! $user->doctor)
        );

        // الحساب الموحد لصالة العمليات أو حاملو صلاحية محطة التخدير أو دور التخدير
        if ($isSharedAccount || $user->can('view anesthesia station') || $user->hasRole(['التخدير', 'anesthesiologist'])) {
            return;
        }

        if ($user->doctor) {
            $doctorIds = $this->getAnesthesiologistDoctorIds($user);
            $assignedToDoctor = false;

            if ($surgery->anesthesiaStation) {
                $assignedToDoctor = in_array($surgery->anesthesiaStation->anesthesiologist_id, $doctorIds)
                    || in_array($surgery->anesthesiaStation->anesthesiologist_2_id, $doctorIds);
            }

            if (! $assignedToDoctor) {
                $assignedToDoctor = in_array($surgery->anesthesiologist_id, $doctorIds)
                    || in_array($surgery->anesthesiologist_2_id, $doctorIds);
            }

            // إذا لم يتم تعيين طبيب تخدير لها بعد، يُسمح للطبيب بفتحها لتعيين الطبيب وإدخال التفاصيل
            if (! $surgery->anesthesiologist_id && (! $surgery->anesthesiaStation || ! $surgery->anesthesiaStation->anesthesiologist_id)) {
                $assignedToDoctor = true;
            }

            if (! $assignedToDoctor) {
                abort(403, 'غير مصرح لك بالوصول إلى هذه العملية الجراحية المسندة لطبيب تخدير آخر');
            }
        }
    }

    /**
     * جلب كافة المعرّفات المرتبطة بطبيب التخدير لمعالجة الازدواجية والربط
     */
    protected function getAnesthesiologistDoctorIds($user): array
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
