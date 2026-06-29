<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\NursingStation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Notifications\NursingUpdateNotification;

class NursingStationController extends Controller
{
    public function index()
    {
        // 1. جلب العمليات التي تحتاج مرحلة pre_op (التهيئة قبل الصالة)
        $preOpSurgeries = Surgery::with(['patient.user', 'doctor.user', 'nursingStation.nurse', 'preOpResidentStation'])
            ->where(function($query) {
                $query->where(function($q) {
                    $q->where('status', 'scheduled')
                      ->whereDoesntHave('residentStations', function($sq) {
                          $sq->where('phase', 'pre_op');
                      });
                })
                ->orWhereHas('residentStations', function($sq) {
                    $sq->where('phase', 'pre_op')
                      ->where('status', '!=', 'completed');
                });
            })
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // 2. جلب العمليات التي تحتاج مرحلة post_op (المتابعة بعد الصالة)
        $postOpSurgeries = Surgery::with(['patient.user', 'doctor.user', 'nursingStation.nurse', 'postOpResidentStation'])
            ->whereHas('anesthesiaStation', function($sq) {
                $sq->where('status', 'completed');
            })
            ->where(function($query) {
                $query->whereDoesntHave('nursingStation')
                    ->orWhereHas('nursingStation', function($q) {
                        $q->where('status', '!=', 'completed');
                    });
            })
            ->whereIn('status', ['in_progress', 'completed', 'waiting'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        // 3. جلب العمليات التي أتمت مرحلة التمريض (الأرشيف)
        $completedSurgeries = Surgery::with(['patient.user', 'doctor.user', 'nursingStation.nurse'])
            ->whereHas('nursingStation', function($q) {
                $q->where('status', 'completed');
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return view('surgery-stations.nursing.index', compact('preOpSurgeries', 'postOpSurgeries', 'completedSurgeries'));
    }

    public function show(Surgery $surgery)
    {
        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->nursingStation) {
            $surgery->nursingStation()->create([
                'status' => 'pending',
            ]);
        }

        $surgery->load([
            'patient.user', 
            'doctor.user', 
            'nursingStation.nurse', 
            'surgeryTreatments.administeredBy',
            'preOpResidentStation.followUps.resident.user',
            'preOpResidentStation.readings.resident.user',
            'postOpResidentStation.followUps.resident.user',
            'postOpResidentStation.readings.resident.user',
            'residentStationFollowUps',
        ]);
        
        $nurses = User::role('nurse')->where('is_active', true)->get();

        return view('surgery-stations.nursing.show', compact('surgery', 'nurses'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'nurse_id' => 'nullable|exists:users,id',
            'nursing_notes' => 'nullable|string|max:2000',
            'discharge_notes' => 'nullable|string|max:2000',
            'vital_signs' => 'nullable|string|max:1000',
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
            'clinical_examination' => 'nullable|string|max:2000',
        ]);

        $intake = ($validated['intake_iv_fluids'] ?? 0) + ($validated['intake_oral'] ?? 0) + ($validated['intake_blood'] ?? 0);
        $output = ($validated['output_urine'] ?? 0) + ($validated['output_drain'] ?? 0) + ($validated['output_gtube_ng'] ?? 0) + ($validated['output_vomiting'] ?? 0) + ($validated['output_stool'] ?? 0);
        
        $anyFluids = isset($validated['intake_iv_fluids']) || isset($validated['intake_oral']) || isset($validated['intake_blood']) ||
                     isset($validated['output_urine']) || isset($validated['output_drain']) || isset($validated['output_gtube_ng']) ||
                     isset($validated['output_vomiting']) || isset($validated['output_stool']);
                     
        $validated['fluid_balance'] = $anyFluids ? ($intake - $output) : null;

        $user = Auth::user();
        if ($user) {
            $validated['nurse_id'] = $user->id;
        }

        $station = $surgery->nursingStation;
        if (!$station) {
            $station = $surgery->nursingStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        // حفظ القيم القديمة قبل التحديث
        $oldAttrs = $station->exists ? $station->getOriginal() : [];

        $station->update([
            'nurse_id' => $validated['nurse_id'] ?? null,
            'nursing_notes' => $validated['nursing_notes'] ?? null,
            'discharge_notes' => $validated['discharge_notes'] ?? null,
            'vital_signs' => $validated['vital_signs'] ?? (
                (($validated['bp'] ?? null) || ($validated['temp'] ?? null) || ($validated['pr'] ?? null) || ($validated['rr'] ?? null) || ($validated['spo2'] ?? null))
                ? implode(' | ', array_filter([
                    ($validated['bp'] ?? null) ? "ضغط الدم: " . $validated['bp'] : null,
                    ($validated['temp'] ?? null) ? "الحرارة: " . $validated['temp'] : null,
                    ($validated['pr'] ?? null) ? "النبض: " . $validated['pr'] : null,
                    ($validated['rr'] ?? null) ? "التنفس: " . $validated['rr'] : null,
                    ($validated['spo2'] ?? null) ? "الأكسجين: " . $validated['spo2'] : null,
                ]))
                : null
            ),
        ]);

        // ── بناء قائمة التغييرات ────────────────────────────────────────────
        $changes = [];

        $fieldLabels = [
            'nursing_notes' => 'ملاحظات تمريض',
            'discharge_notes' => 'ملاحظات الخروج',
            'vital_signs' => 'العلامات الحيوية',
            'bp' => 'ضغط الدم',
            'temp' => 'الحرارة',
            'pr' => 'النبض',
            'rr' => 'التنفس',
            'spo2' => 'الأكسجين',
            'pain_score' => 'مقياس الألم',
            'rbs' => 'سكر الدم',
            'gcs' => 'GCS',
            'crt' => 'CRT',
            'clinical_examination' => 'الفحص السريري',
            'intake_iv_fluids' => 'محاليل وريدية',
            'intake_oral' => 'محاليل فموية',
            'intake_blood' => 'دم',
            'output_urine' => 'بول',
            'output_drain' => 'تصريف',
            'output_gtube_ng' => 'أنبوب معدي',
            'output_vomiting' => 'تقيؤ',
            'output_stool' => 'براز',
            'fluid_balance' => 'توازن السوائل',
        ];

        foreach ($fieldLabels as $field => $label) {
            $oldVal = $oldAttrs[$field] ?? null;
            $newVal = $validated[$field] ?? null;
            if ((string) $oldVal !== (string) $newVal && ($newVal !== null && $newVal !== '')) {
                $changes[$label] = $newVal;
            }
        }
        if (isset($changes['ملاحظات تمريض'])) {
            $v = $changes['ملاحظات تمريض'];
            $changes['ملاحظات تمريض'] = mb_strlen($v) > 100 ? mb_substr($v, 0, 100) . '...' : $v;
        }
        if (isset($changes['ملاحظات الخروج'])) {
            $v = $changes['ملاحظات الخروج'];
            $changes['ملاحظات الخروج'] = mb_strlen($v) > 100 ? mb_substr($v, 0, 100) . '...' : $v;
        }
        // ──────────────────────────────────────────────────────────────────────

        // تحديث العلامات الحيوية في محطة المقيم (ما قبل أو ما بعد العملية حسب المرحلة الحالية)
        $currentStationName = $surgery->getCurrentStation();
        $activeResidentStation = ($currentStationName === 'resident_pre_op' || !$surgery->postOpResidentStation)
            ? $surgery->preOpResidentStation
            : $surgery->postOpResidentStation;

        if ($activeResidentStation) {
            $isDifferent = $activeResidentStation->bp !== ($validated['bp'] ?? null) ||
                           $activeResidentStation->temp !== ($validated['temp'] ?? null) ||
                           $activeResidentStation->pr !== ($validated['pr'] ?? null) ||
                           $activeResidentStation->rr !== ($validated['rr'] ?? null) ||
                           $activeResidentStation->spo2 !== ($validated['spo2'] ?? null) ||
                           $activeResidentStation->pain_score !== ($validated['pain_score'] ?? null) ||
                           $activeResidentStation->rbs !== ($validated['rbs'] ?? null) ||
                           $activeResidentStation->gcs !== ($validated['gcs'] ?? null) ||
                           $activeResidentStation->crt !== ($validated['crt'] ?? null) ||
                           $activeResidentStation->intake_iv_fluids !== ($validated['intake_iv_fluids'] ?? null) ||
                           $activeResidentStation->intake_oral !== ($validated['intake_oral'] ?? null) ||
                           $activeResidentStation->intake_blood !== ($validated['intake_blood'] ?? null) ||
                           $activeResidentStation->output_urine !== ($validated['output_urine'] ?? null) ||
                           $activeResidentStation->output_drain !== ($validated['output_drain'] ?? null) ||
                           $activeResidentStation->output_gtube_ng !== ($validated['output_gtube_ng'] ?? null) ||
                           $activeResidentStation->output_vomiting !== ($validated['output_vomiting'] ?? null) ||
                           $activeResidentStation->output_stool !== ($validated['output_stool'] ?? null) ||
                           $activeResidentStation->clinical_examination !== ($validated['clinical_examination'] ?? null);

            if ($isDifferent) {
                // استخدام معرف الطبيب المقيم المرتبط بالمحطة أو null
                $activeResidentStation->readings()->create([
                    'resident_id' => $activeResidentStation->resident_id,
                    'bp' => $validated['bp'] ?? null,
                    'temp' => $validated['temp'] ?? null,
                    'pr' => $validated['pr'] ?? null,
                    'rr' => $validated['rr'] ?? null,
                    'spo2' => $validated['spo2'] ?? null,
                    'pain_score' => $validated['pain_score'] ?? null,
                    'rbs' => $validated['rbs'] ?? null,
                    'gcs' => $validated['gcs'] ?? null,
                    'crt' => $validated['crt'] ?? null,
                    'intake_iv_fluids' => $validated['intake_iv_fluids'] ?? null,
                    'intake_oral' => $validated['intake_oral'] ?? null,
                    'intake_blood' => $validated['intake_blood'] ?? null,
                    'output_urine' => $validated['output_urine'] ?? null,
                    'output_drain' => $validated['output_drain'] ?? null,
                    'output_gtube_ng' => $validated['output_gtube_ng'] ?? null,
                    'output_vomiting' => $validated['output_vomiting'] ?? null,
                    'output_stool' => $validated['output_stool'] ?? null,
                    'fluid_balance' => $validated['fluid_balance'] ?? null,
                    'clinical_examination' => $validated['clinical_examination'] ?? null,
                    'notes' => 'تم تسجيل القراءة بواسطة التمريض',
                ]);

                $activeResidentStation->update([
                    'bp' => $validated['bp'] ?? null,
                    'temp' => $validated['temp'] ?? null,
                    'pr' => $validated['pr'] ?? null,
                    'rr' => $validated['rr'] ?? null,
                    'spo2' => $validated['spo2'] ?? null,
                    'pain_score' => $validated['pain_score'] ?? null,
                    'rbs' => $validated['rbs'] ?? null,
                    'gcs' => $validated['gcs'] ?? null,
                    'crt' => $validated['crt'] ?? null,
                    'intake_iv_fluids' => $validated['intake_iv_fluids'] ?? null,
                    'intake_oral' => $validated['intake_oral'] ?? null,
                    'intake_blood' => $validated['intake_blood'] ?? null,
                    'output_urine' => $validated['output_urine'] ?? null,
                    'output_drain' => $validated['output_drain'] ?? null,
                    'output_gtube_ng' => $validated['output_gtube_ng'] ?? null,
                    'output_vomiting' => $validated['output_vomiting'] ?? null,
                    'output_stool' => $validated['output_stool'] ?? null,
                    'fluid_balance' => $validated['fluid_balance'] ?? null,
                    'clinical_examination' => $validated['clinical_examination'] ?? null,
                ]);
            }
        }

        // ── إرسال إشعار للمقيم والجراح ──────────────────────────────────────
        $nurseName = $user?->full_name ?? $user?->name ?? 'ممرض';
        $recipients = collect();

        // 1) المقيم المرتبط بالمحطة الفعّالة
        if ($activeResidentStation && $activeResidentStation->resident?->user) {
            $recipients->push($activeResidentStation->resident->user);
        }

        // 2) إذا لم نجد مقيماً في المحطة النشطة، نبحث في محطات المقيم الأخرى
        if ($recipients->isEmpty() || !$recipients->first()) {
            $allResidentStations = collect([$surgery->preOpResidentStation, $surgery->postOpResidentStation])
                ->filter()
                ->unique('id');
            foreach ($allResidentStations as $rs) {
                if ($rs->resident?->user) {
                    $recipients->push($rs->resident->user);
                }
            }
        }

        // 3) إذا لم نجد مقيماً في أي محطة، نرسل لجميع المستخدمين بدور مقيم
        if ($recipients->isEmpty() || !$recipients->first()) {
            $residentUsers = \App\Models\User::role('resident')->get();
            foreach ($residentUsers as $ru) {
                $recipients->push($ru);
            }
        }

        // 3.5) إذا لم نجد، نبحث عن أطباء من نوع resident (مقيم) ونجلب حساباتهم
        if ($recipients->isEmpty() || !$recipients->first()) {
            $residentDoctors = \App\Models\Doctor::where('type', 'resident')
                ->where('is_active', true)
                ->with('user')
                ->get();
            foreach ($residentDoctors as $doctor) {
                if ($doctor->user) {
                    $recipients->push($doctor->user);
                }
            }
        }

        // 4) الجراح / الاختصاص المسؤول عن العملية
        if ($surgery->doctor?->user) {
            $recipients->push($surgery->doctor->user);
        }

        // إرسال الإشعار لكل مستلم فريد (لا تكرار إن كان نفس الشخص)
        $recipients
            ->unique('id')
            ->each(function (User $recipient) use ($surgery, $nurseName, $changes) {
                $recipient->notify(new NursingUpdateNotification($surgery, $nurseName, $changes));
            });
        // ──────────────────────────────────────────────────────────────────────

        return redirect()->route('nursing-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة التمريض بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $station = $surgery->nursingStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // تحديث حالة العملية لمكتملة
        $surgery->update(['status' => 'completed']);

        return redirect()->route('nursing-station.index')
            ->with('success', 'تم إتمام محطة التمريض والعملية مكتملة');
    }

    public function storeFollowUp(Request $request, Surgery $surgery)
    {
        $currentStationName = $surgery->getCurrentStation();
        $station = ($currentStationName === 'resident_pre_op' || !$surgery->postOpResidentStation)
            ? $surgery->preOpResidentStation
            : $surgery->postOpResidentStation;

        if (!$station) {
            return redirect()->back()->with('error', 'لا توجد محطة مقيم لتسجيل المتابعة.');
        }

        $validated = $request->validate([
            'follow_up_date' => 'required|date',
            'session' => 'required|in:morning,evening',
            'notes' => 'required|string|max:2000',
        ]);

        $user = Auth::user();
        $nurseName = $user?->full_name ?? $user?->name ?? 'ممرض';

        $station->followUps()->create([
            'surgery_id' => $surgery->id,
            'resident_station_id' => $station->id,
            'resident_id' => null,
            'resident_name' => $nurseName . ' (كادر التمريض)',
            'follow_up_date' => $validated['follow_up_date'],
            'session' => $validated['session'],
            'notes' => $validated['notes'],
        ]);

        return redirect()->back()->with('success', 'تم تسجيل متابعة جديدة بنجاح بواسطة التمريض.');
    }
}
