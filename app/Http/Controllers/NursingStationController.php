<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\NursingStation;
use App\Models\User;
use Illuminate\Http\Request;

class NursingStationController extends Controller
{
    public function index()
    {
        // جلب العمليات التي في محطة التمريض (بعد المقيم post_op)
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'nursingStation.nurse'])
            ->whereHas('residentStations', function($q) {
                $q->where('phase', 'post_op')
                  ->where('status', 'completed');
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

        return view('surgery-stations.nursing.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // التحقق من أن محطة المقيم (post_op) مكتملة
        $postOpStation = $surgery->postOpResidentStation;
        if (!$postOpStation || $postOpStation->status !== 'completed') {
            return redirect()->route('resident-station.show', $surgery)
                ->with('error', 'يجب إتمام مرحلة متابعة المقيم أولاً');
        }

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
            'postOpResidentStation.followUps.resident.user',
            'postOpResidentStation.readings.resident.user'
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
            'clinical_examination' => 'nullable|string|max:2000',
        ]);

        $station = $surgery->nursingStation;
        if (!$station) {
            $station = $surgery->nursingStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

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

        // تحديث العلامات الحيوية في محطة المقيم ما بعد العملية (post_op)
        $postOpStation = $surgery->postOpResidentStation;
        if ($postOpStation) {
            $isDifferent = $postOpStation->bp !== ($validated['bp'] ?? null) ||
                           $postOpStation->temp !== ($validated['temp'] ?? null) ||
                           $postOpStation->pr !== ($validated['pr'] ?? null) ||
                           $postOpStation->rr !== ($validated['rr'] ?? null) ||
                           $postOpStation->spo2 !== ($validated['spo2'] ?? null) ||
                           $postOpStation->clinical_examination !== ($validated['clinical_examination'] ?? null);

            if ($isDifferent) {
                // استخدام معرف الطبيب المقيم المرتبط بالمحطة أو null
                $postOpStation->readings()->create([
                    'resident_id' => $postOpStation->resident_id,
                    'bp' => $validated['bp'] ?? null,
                    'temp' => $validated['temp'] ?? null,
                    'pr' => $validated['pr'] ?? null,
                    'rr' => $validated['rr'] ?? null,
                    'spo2' => $validated['spo2'] ?? null,
                    'clinical_examination' => $validated['clinical_examination'] ?? null,
                    'notes' => 'تم تسجيل القراءة بواسطة التمريض',
                ]);

                $postOpStation->update([
                    'bp' => $validated['bp'] ?? null,
                    'temp' => $validated['temp'] ?? null,
                    'pr' => $validated['pr'] ?? null,
                    'rr' => $validated['rr'] ?? null,
                    'spo2' => $validated['spo2'] ?? null,
                    'clinical_examination' => $validated['clinical_examination'] ?? null,
                ]);
            }
        }

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
}
