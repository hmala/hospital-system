<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Surgery;
use Illuminate\Http\Request;

class AnesthesiaStationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // جلب العمليات لمحطة التخدير
        $query = Surgery::with(['patient.user', 'doctor.user', 'anesthesiaStation'])
            ->where('status', '!=', 'cancelled');

        if ($user->doctor && ! $user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            $doctorId = $user->doctor->id;
            $query->where(function ($q) use ($doctorId) {
                $q->where('anesthesiologist_id', $doctorId)
                    ->orWhere('anesthesiologist_2_id', $doctorId)
                    ->orWhereHas('anesthesiaStation', function ($sq) use ($doctorId) {
                        $sq->where('anesthesiologist_id', $doctorId)
                            ->orWhere('anesthesiologist_2_id', $doctorId);
                    });
            });
        } else {
            // للمشرفين وموظفي العمليات: عرض العمليات التي تم تعيين طبيب تخدير لها أو لها محطة تخدير
            $query->where(function ($q) {
                $q->whereNotNull('anesthesiologist_id')
                    ->orWhereNotNull('anesthesiologist_2_id')
                    ->orWhereHas('anesthesiaStation');
            });
        }

        $surgeries = $query->orderBy('scheduled_date', 'desc')
            ->orderBy('scheduled_time', 'desc')
            ->get();

        return view('surgery-stations.anesthesia.index', compact('surgeries'));
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

        // تحديث نوع التخدير في جدول العمليات الرئيسي أيضاً للمزامنة
        if ($request->filled('anesthesia_type')) {
            $surgery->update(['anesthesia_type' => $request->anesthesia_type]);
        }

        $station->update($validated);

        return redirect()->route('surgeries.show', $surgery)
            ->with('success', 'تم حفظ بيانات التخدير بنجاح');
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

        if ($user->doctor && ! $user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            $doctorId = $user->doctor->id;
            $assignedToDoctor = false;

            if ($surgery->anesthesiaStation) {
                $assignedToDoctor = $surgery->anesthesiaStation->anesthesiologist_id === $doctorId
                    || $surgery->anesthesiaStation->anesthesiologist_2_id === $doctorId;
            } else {
                $assignedToDoctor = $surgery->anesthesiologist_id === $doctorId
                    || $surgery->anesthesiologist_2_id === $doctorId;
            }

            if (! $assignedToDoctor) {
                abort(403, 'غير مصرح لك بالوصول لهذه العملية');
            }
        }
    }
}
