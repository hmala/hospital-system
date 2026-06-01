<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\AnesthesiaStation;
use App\Models\Doctor;
use Illuminate\Http\Request;

class AnesthesiaStationController extends Controller
{
    public function index()
    {
        // جلب العمليات التي في محطة التخدير (بعد الجراح)
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'anesthesiaStation'])
            ->whereHas('surgeonStation', function($q) {
                $q->where('status', 'completed');
            })
            ->where(function($query) {
                $query->whereDoesntHave('anesthesiaStation')
                    ->orWhereHas('anesthesiaStation', function($q) {
                        $q->where('status', '!=', 'completed');
                    });
            })
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.anesthesia.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // التحقق من أن محطة الجراح مكتملة
        if (!$surgery->surgeonStation || $surgery->surgeonStation->status !== 'completed') {
            return redirect()->route('surgeon-station.show', $surgery)
                ->with('error', 'يجب إتمام محطة الجراح أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->anesthesiaStation) {
            $surgery->anesthesiaStation()->create([
                'anesthesiologist_id' => $surgery->operationTheaterStation?->anesthesiologist_id,
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'anesthesiaStation.anesthesiologist.user', 'anesthesiaStation.anesthesiologist2.user']);
        
        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        return view('surgery-stations.anesthesia.show', compact('surgery', 'doctors'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'anesthesiologist_2_id' => 'nullable|exists:doctors,id',
            'anesthesia_type' => 'nullable|string|in:local,regional,general,sedation',
            'surgical_assistant_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $station = $surgery->anesthesiaStation;
        if (!$station) {
            $station = $surgery->anesthesiaStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update($validated);

        return redirect()->route('anesthesia-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة التخدير بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $station = $surgery->anesthesiaStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة المقيم (post_op) التالية
        $postOpStation = $surgery->postOpResidentStation;
        if (!$postOpStation) {
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
}
