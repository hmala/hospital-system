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

        $surgery->load(['patient.user', 'doctor.user', 'nursingStation.nurse']);
        
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
        ]);

        $station = $surgery->nursingStation;
        if (!$station) {
            $station = $surgery->nursingStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        $station->update($validated);

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
