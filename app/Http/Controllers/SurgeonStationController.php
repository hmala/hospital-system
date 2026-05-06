<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use App\Models\SurgeonStation;
use App\Models\Doctor;
use Illuminate\Http\Request;

class SurgeonStationController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // جلب العمليات التي في محطة الجراح
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'surgeonStation'])
            ->whereHas('surgeonStation', function($q) {
                $q->where('status', '!=', 'completed');
            })
            ->orWhereDoesntHave('surgeonStation')
            ->whereIn('status', ['scheduled', 'waiting'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.surgeon.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'surgeonStation.residentAssigned.user']);
        
        $residents = Doctor::with('user')
            ->where('is_active', true)
            ->where('type', 'resident')
            ->orderBy('id')
            ->get();

        return view('surgery-stations.surgeon.show', compact('surgery', 'residents'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'resident_assigned_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
        ]);

        $station = $surgery->surgeonStation;
        if (!$station) {
            $station = $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update($validated);

        return redirect()->route('surgeon-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة الجراح بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $station = $surgery->surgeonStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة التخدير التالية
        if (!$surgery->anesthesiaStation) {
            $surgery->anesthesiaStation()->create([
                'status' => 'pending',
            ]);
        }

        return redirect()->route('surgeon-station.index')
            ->with('success', 'تم إتمام محطة الجراح والانتقال لمحطة التخدير');
    }
}
