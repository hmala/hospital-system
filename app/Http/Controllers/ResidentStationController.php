<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\ResidentStation;
use App\Models\Doctor;
use Illuminate\Http\Request;

class ResidentStationController extends Controller
{
    public function index()
    {
        // جلب العمليات في مرحلة المقيم (pre_op أو post_op)
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'residentStations', 'preOpResidentStation', 'postOpResidentStation'])
            ->where(function($query) {
                $query->where(function($q) {
                    // حالة 1: العمليات المحجوزة التي تحتاج pre_op
                    $q->where('status', 'scheduled')
                      ->whereDoesntHave('residentStations', function($sq) {
                          $sq->where('phase', 'pre_op');
                      });
                })
                ->orWhere(function($q) {
                    // حالة 2: pre_op موجود لكن غير مكتمل
                    $q->whereHas('residentStations', function($sq) {
                        $sq->where('phase', 'pre_op')
                          ->where('status', '!=', 'completed');
                    });
                })
                ->orWhere(function($q) {
                    // حالة 3: بعد التخدير تحتاج post_op
                    $q->whereHas('anesthesiaStation', function($sq) {
                        $sq->where('status', 'completed');
                    })
                    ->whereDoesntHave('residentStations', function($sq) {
                        $sq->where('phase', 'post_op');
                    });
                })
                ->orWhere(function($q) {
                    // حالة 4: post_op موجود لكن غير مكتمل
                    $q->whereHas('residentStations', function($sq) {
                        $sq->where('phase', 'post_op')
                          ->where('status', '!=', 'completed');
                    });
                });
            })
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.resident.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // تحديد المرحلة الحالية (pre_op أو post_op)
        $preOpStation = $surgery->preOpResidentStation;
        $postOpStation = $surgery->postOpResidentStation;

        // تحديد المرحلة النشطة
        if (!$preOpStation || $preOpStation->status !== 'completed') {
            $currentPhase = 'pre_op';
            $station = $preOpStation;
        } elseif ($surgery->anesthesiaStation && $surgery->anesthesiaStation->status === 'completed') {
            if (!$postOpStation || $postOpStation->status !== 'completed') {
                $currentPhase = 'post_op';
                $station = $postOpStation;
            } else {
                return redirect()->route('resident-station.index')
                    ->with('error', 'تم إتمام جميع مراحل المقيم');
            }
        } else {
            return redirect()->route('operation-theater-station.show', $surgery)
                ->with('error', 'يجب إتمام مرحلة صالة العمليات أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$station) {
            $station = $surgery->residentStations()->create([
                'phase' => $currentPhase,
                'status' => 'pending',
            ]);
        }

        $surgery->load([
            'patient.user', 
            'doctor.user', 
            'residentStations', 
            'preOpResidentStation', 
            'postOpResidentStation',
            'labTests.labTest',
            'radiologyTests.radiologyType'
        ]);
        $residents = Doctor::where('type', 'resident')->get();

        return view('surgery-stations.resident.show', compact('surgery', 'station', 'currentPhase', 'residents'));
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
            'review_of_other_systems' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:2000',
            'post_op_notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
        ]);

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
}
