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
        // جلب العمليات التي في محطة المقيم
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'residentStation.resident.user'])
            ->whereHas('anesthesiaStation', function($q) {
                $q->where('status', 'completed');
            })
            ->whereHas('residentStation', function($q) {
                $q->where('status', '!=', 'completed');
            })
            ->orWhere(function($query) {
                $query->whereHas('anesthesiaStation', function($q) {
                    $q->where('status', 'completed');
                })->whereDoesntHave('residentStation');
            })
            ->whereIn('status', ['waiting', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.resident.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // التحقق من أن محطة التخدير مكتملة
        if (!$surgery->anesthesiaStation || $surgery->anesthesiaStation->status !== 'completed') {
            return redirect()->route('anesthesia-station.show', $surgery)
                ->with('error', 'يجب إتمام محطة التخدير أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->residentStation) {
            $surgery->residentStation()->create([
                'resident_id' => $surgery->surgeonStation?->resident_assigned_id,
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'residentStation.resident.user']);

        return view('surgery-stations.resident.show', compact('surgery'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'post_op_notes' => 'nullable|string|max:2000',
            'treatment_plan' => 'nullable|string|max:2000',
            'follow_up_date' => 'nullable|date',
        ]);

        $station = $surgery->residentStation;
        if (!$station) {
            $station = $surgery->residentStation()->create([
                'resident_id' => $surgery->surgeonStation?->resident_assigned_id,
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update($validated);

        return redirect()->route('resident-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات محطة المقيم بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $station = $surgery->residentStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة التمريض التالية
        if (!$surgery->nursingStation) {
            $surgery->nursingStation()->create([
                'status' => 'pending',
            ]);
        }

        return redirect()->route('resident-station.index')
            ->with('success', 'تم إتمام محطة المقيم والانتقال لمحطة التمريض');
    }
}
