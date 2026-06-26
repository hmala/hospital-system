<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\ResidentStation;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SurgeryTreatment;

class ResidentStationController extends Controller
{
    public function index()
    {
        // 1. جلب العمليات التي تحتاج مرحلة pre_op
        $preOpSurgeries = Surgery::with(['patient.user', 'doctor.user', 'residentStations', 'preOpResidentStation', 'postOpResidentStation'])
            ->where(function($query) {
                $query->where(function($q) {
                    // حالة 1: العمليات المحجوزة التي تحتاج pre_op
                    $q->where('status', 'scheduled')
                      ->whereDoesntHave('residentStations', function($sq) {
                          $sq->where('phase', 'pre_op');
                      });
                })
                ->orWhereHas('residentStations', function($sq) {
                    // حالة 2: pre_op موجود لكن غير مكتمل
                    $sq->where('phase', 'pre_op')
                      ->where('status', '!=', 'completed');
                });
            })
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // 2. جلب العمليات التي تحتاج مرحلة post_op بعد اكتمال صالة العمليات
        $postOpSurgeries = Surgery::with(['patient.user', 'doctor.user', 'residentStations', 'preOpResidentStation', 'postOpResidentStation'])
            ->whereHas('anesthesiaStation', function($sq) {
                $sq->where('status', 'completed');
            })
            ->where(function($query) {
                $query->whereDoesntHave('residentStations', function($sq) {
                    $sq->where('phase', 'post_op');
                })
                ->orWhereHas('residentStations', function($sq) {
                    $sq->where('phase', 'post_op')
                      ->where('status', '!=', 'completed');
                });
            })
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // 3. جلب العمليات التي أتمت كل مراحل المقيم (pre_op و post_op)
        $completedSurgeries = Surgery::with(['patient.user', 'doctor.user', 'residentStations', 'preOpResidentStation', 'postOpResidentStation'])
            ->whereHas('residentStations', function($q) {
                $q->where('phase', 'pre_op')->where('status', 'completed');
            })
            ->whereHas('residentStations', function($q) {
                $q->where('phase', 'post_op')->where('status', 'completed');
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('surgery-stations.resident.index', compact('preOpSurgeries', 'postOpSurgeries', 'completedSurgeries'));
    }

    public function show(Surgery $surgery, Request $request)
    {
        $requestedPhase = $request->query('phase');
        $preOpStation = $surgery->preOpResidentStation;
        $postOpStation = $surgery->postOpResidentStation;

        // تحديد المرحلة المعروضة
        if ($requestedPhase === 'pre_op') {
            $currentPhase = 'pre_op';
            $station = $preOpStation;
        } elseif ($requestedPhase === 'post_op') {
            $currentPhase = 'post_op';
            $station = $postOpStation;
        } else {
            // التحديد التلقائي للمرحلة
            if (!$preOpStation || $preOpStation->status !== 'completed') {
                $currentPhase = 'pre_op';
                $station = $preOpStation;
            } elseif ($surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed') {
                if (!$postOpStation || $postOpStation->status !== 'completed') {
                    $currentPhase = 'post_op';
                    $station = $postOpStation;
                } else {
                    // كلا المرحلتين مكتملتين، نعرض post_op بشكل افتراضي
                    $currentPhase = 'post_op';
                    $station = $postOpStation;
                }
            } else {
                // مرحلة صالة العمليات لم تكتمل بعد، ولكن مرحلة pre_op مكتملة
                // نسمح بعرض مرحلة pre_op المكتملة بدلاً من الحظر
                $currentPhase = 'pre_op';
                $station = $preOpStation;
            }
        }

        // إنشاء محطة إذا لم تكن موجودة وكانت المرحلة نشطة وغير مكتملة
        if (!$station) {
            // تعيين المقيم تلقائياً من المستخدم الحالي
            $defaultResidentId = null;
            $user = Auth::user();
            if ($user) {
                $defaultResidentId = $user->doctor?->id;
                if (!$defaultResidentId) {
                    $doctor = Doctor::where('user_id', $user->id)->first();
                    $defaultResidentId = $doctor?->id;
                }
            }

            $station = $surgery->residentStations()->create([
                'phase' => $currentPhase,
                'status' => 'pending',
                'resident_id' => $defaultResidentId,
            ]);
        }

        $surgery->load([
            'patient.user', 
            'doctor.user', 
            'residentStations.readings.resident.user', 
            'residentStations.followUps.resident.user', 
            'preOpResidentStation.readings.resident.user', 
            'postOpResidentStation.readings.resident.user',
            'labTests.labTest',
            'radiologyTests.radiologyType',
            'surgeryTreatments.administeredBy'
        ]);
        $station->load(['readings.resident.user', 'followUps.resident.user']);
        $residents = Doctor::where('type', 'resident')->get();

        return view('surgery-stations.resident.show', compact('surgery', 'station', 'currentPhase', 'residents'));
    }

    public function storeFollowUp(Request $request, Surgery $surgery)
    {
        $station = $surgery->postOpResidentStation;
        if (!$station) {
            return redirect()->back()->with('error', 'لا توجد محطة متابعة ما بعد العملية لتسجيل المتابعة.');
        }

        $validated = $request->validate([
            'follow_up_date' => 'required|date',
            'session' => 'required|in:morning,evening',
            'notes' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $residentId = null;
        $residentName = null;

        if ($user) {
            $residentId = $user->doctor?->id;
            $residentName = $user->full_name ?? $user->name;

            if (!$residentId) {
                $resident = Doctor::where('user_id', $user->id)->first();
                $residentId = $resident?->id;
            }
        }

        if (!$residentId && $station->resident_id) {
            $residentId = $station->resident_id;
        }

        if (!$residentName && $station->resident?->user?->full_name) {
            $residentName = $station->resident->user->full_name;
        }

        $station->followUps()->create([
            'surgery_id' => $surgery->id,
            'resident_station_id' => $station->id,
            'resident_id' => $residentId,
            'resident_name' => $residentName,
            'follow_up_date' => $validated['follow_up_date'],
            'session' => $validated['session'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->back()
            ->with('success', 'تم تسجيل متابعة جديدة بنجاح.');
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'resident_id' => 'nullable|exists:doctors,id',
            'phase' => 'required|in:pre_op,post_op',
            'chief_complaint' => 'nullable|string|max:2000',
            'history_present_illness' => 'nullable|string|max:2000',
            'past_medical_hx' => 'nullable|string|max:2000',
            'past_surgical_hx' => 'nullable|string|max:2000',
            'drug_hx' => 'nullable|string|max:2000',
            'drug_allergy' => 'nullable|string|max:2000',
            'clinical_examination' => 'nullable|string|max:2000',
            'bp' => 'nullable|string|max:100',
            'temp' => 'nullable|string|max:100',
            'pr' => 'nullable|string|max:100',
            'rr' => 'nullable|string|max:100',
            'spo2' => 'nullable|string|max:100',
            'pain_score' => 'nullable|string|max:100',
            'rbs' => 'nullable|string|max:100',
            'gcs' => 'nullable|string|max:100',
            'crt' => 'nullable|string|max:100',
            'intake_iv_fluids' => 'nullable|numeric|min:0',
            'intake_oral' => 'nullable|numeric|min:0',
            'intake_blood' => 'nullable|numeric|min:0',
            'output_urine' => 'nullable|numeric|min:0',
            'output_drain' => 'nullable|numeric|min:0',
            'output_gtube_ng' => 'nullable|numeric|min:0',
            'output_vomiting' => 'nullable|numeric|min:0',
            'output_stool' => 'nullable|numeric|min:0',
            'review_of_other_systems' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'post_op_notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
        ]);

        $intake = ($validated['intake_iv_fluids'] ?? 0) + ($validated['intake_oral'] ?? 0) + ($validated['intake_blood'] ?? 0);
        $output = ($validated['output_urine'] ?? 0) + ($validated['output_drain'] ?? 0) + ($validated['output_gtube_ng'] ?? 0) + ($validated['output_vomiting'] ?? 0) + ($validated['output_stool'] ?? 0);
        
        $anyFluids = isset($validated['intake_iv_fluids']) || isset($validated['intake_oral']) || isset($validated['intake_blood']) ||
                     isset($validated['output_urine']) || isset($validated['output_drain']) || isset($validated['output_gtube_ng']) ||
                     isset($validated['output_vomiting']) || isset($validated['output_stool']);
                     
        $validated['fluid_balance'] = $anyFluids ? ($intake - $output) : null;

        $user = Auth::user();
        $residentId = null;
        if ($user) {
            $residentId = $user->doctor?->id;
            if (!$residentId) {
                $resident = \App\Models\Doctor::where('user_id', $user->id)->first();
                $residentId = $resident?->id;
            }
        }

        if ($residentId) {
            $validated['resident_id'] = $residentId;
        }

        $station = $surgery->residentStations()->where('phase', $validated['phase'])->first();
        
        if (!$station) {
            $station = $surgery->residentStations()->create([
                'phase' => $validated['phase'],
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        if ($station->status === 'pending') {
            $station->markAsStarted();
        }

        // التحقق من وجود قراءة جديدة للعلامات الحيوية أو الفحص السريري لحفظها في السجل الدوري
        $hasVitalsOrExam = !empty($validated['bp']) || 
                           !empty($validated['temp']) || 
                           !empty($validated['pr']) || 
                           !empty($validated['rr']) || 
                           !empty($validated['spo2']) || 
                           !empty($validated['clinical_examination']);

        if ($hasVitalsOrExam) {
            $isDifferent = $station->bp !== ($validated['bp'] ?? null) ||
                           $station->temp !== ($validated['temp'] ?? null) ||
                           $station->pr !== ($validated['pr'] ?? null) ||
                           $station->rr !== ($validated['rr'] ?? null) ||
                           $station->spo2 !== ($validated['spo2'] ?? null) ||
                           $station->clinical_examination !== ($validated['clinical_examination'] ?? null);

            if ($isDifferent) {
                $station->readings()->create([
                    'resident_id' => $validated['resident_id'] ?? $station->resident_id,
                    'bp' => $validated['bp'] ?? null,
                    'temp' => $validated['temp'] ?? null,
                    'pr' => $validated['pr'] ?? null,
                    'rr' => $validated['rr'] ?? null,
                    'spo2' => $validated['spo2'] ?? null,
                    'clinical_examination' => $validated['clinical_examination'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        }

        $station->update($validated);

        return redirect()->route('resident-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة المقيم بنجاح');
    }

    public function complete(Surgery $surgery, Request $request)
    {
        $phase = $request->input('phase', 'pre_op');
        $station = $surgery->residentStations()->where('phase', $phase)->first();
        
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء المحطة التالية
        if ($phase === 'pre_op') {
            // بعد pre_op ننتقل لصالة العمليات
            if (!$surgery->operationTheaterStation) {
                $surgery->operationTheaterStation()->create([
                    'status' => 'pending',
                ]);
            }
            $message = 'تم إتمام مرحلة التحضير والانتقال لصالة العمليات';
        } else {
            // بعد post_op ننتقل للتمريض
            if (!$surgery->nursingStation) {
                $surgery->nursingStation()->create([
                    'status' => 'pending',
                ]);
            }
            $message = 'تم إتمام مرحلة المتابعة والانتقال لمحطة التمريض';
        }

        return redirect()->route('resident-station.index')
            ->with('success', $message);
    }

    public function administerTreatment(Request $request, SurgeryTreatment $treatment)
    {
        $validated = $request->validate([
            'status' => 'required|in:administered,cancelled',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        if ($validated['status'] === 'administered') {
            $user = Auth::user();
            $userName = $user?->full_name ?? $user?->name ?? 'ممرض';
            $treatment->logAdministration(Auth::id(), $userName, $validated['admin_notes'] ?? null);
            $actionText = 'إعطاء جرعة من';
        } else {
            $treatment->update([
                'status' => 'cancelled',
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);
            $actionText = 'إيقاف/إلغاء';
        }

        return redirect()->back()->with('success', "تم {$actionText} العلاج بنجاح.");
    }
}
