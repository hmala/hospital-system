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
        // جلب العمليات التي في محطة الجراح (بعد صالة العمليات)
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'surgeonStation'])
            ->whereHas('operationTheaterStation', function($q) {
                $q->where('status', 'completed');
            })
            ->where(function($query) {
                $query->whereDoesntHave('surgeonStation')
                    ->orWhereHas('surgeonStation', function($q) {
                        $q->where('status', '!=', 'completed');
                    });
            })
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.surgeon.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // التحقق من أن صالة العمليات مكتملة
        if (!$surgery->operationTheaterStation || $surgery->operationTheaterStation->status !== 'completed') {
            return redirect()->route('operation-theater-station.show', $surgery)
                ->with('error', 'يجب إتمام مرحلة صالة العمليات أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'surgeonStation']);

        return view('surgery-stations.surgeon.show', compact('surgery'));
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
                'anesthesiologist_id' => $surgery->operationTheaterStation?->anesthesiologist_id,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('surgeon-station.index')
            ->with('success', 'تم إتمام محطة الجراح والانتقال لمحطة التخدير');
    }
}
