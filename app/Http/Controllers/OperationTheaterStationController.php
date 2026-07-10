<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Surgery;
use App\Models\OperationTheaterStation;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;

class OperationTheaterStationController extends Controller
{
    public function index()
    {
        // جلب العمليات التي في محطة صالة العمليات
        $surgeries = Surgery::with(['patient.user', 'doctor.user', 'operationTheaterStation'])
            ->where(function($query) {
                // العمليات التي أنهت مرحلة المقيم (pre_op)
                $query->whereHas('residentStation', function($q) {
                    $q->where('phase', 'pre_op')
                      ->where('status', 'completed');
                });
            })
            ->where(function($query) {
                // ولم تكتمل مرحلة صالة العمليات بعد
                $query->whereDoesntHave('operationTheaterStation')
                    ->orWhereHas('operationTheaterStation', function($q) {
                        $q->where('status', '!=', 'completed');
                    });
            })
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_time')
            ->get();

        return view('surgery-stations.operation-theater.index', compact('surgeries'));
    }

    public function show(Surgery $surgery)
    {
        // التحقق من أن محطة المقيم (pre_op) مكتملة
        $preOpResident = $surgery->residentStation()->where('phase', 'pre_op')->first();
        if (!$preOpResident || $preOpResident->status !== 'completed') {
            return redirect()->route('resident-station.show', $surgery)
                ->with('error', 'يجب إتمام مرحلة تحضير المقيم أولاً');
        }

        // إنشاء محطة إذا لم تكن موجودة
        if (!$surgery->operationTheaterStation) {
            $surgery->operationTheaterStation()->create([
                'status' => 'pending',
            ]);
        }

        $surgery->load(['patient.user', 'doctor.user', 'operationTheaterStation.orNurse', 'operationTheaterStation.anesthesiologist.user', 'medicalDevices']);
        
        // جلب قائمة الممرضين وأطباء التخدير
        $nurses = User::whereJsonContains('role', 'surgery_nurse')->get();
        $anesthesiologists = Doctor::anesthesia()->get();
        $devices = \App\Models\MedicalDevice::where('status', 'active')->orderBy('name')->get();

        return view('surgery-stations.operation-theater.show', compact('surgery', 'nurses', 'anesthesiologists', 'devices'));
    }

    public function update(Request $request, Surgery $surgery)
    {
        $validated = $request->validate([
            'or_nurse_id' => 'nullable|exists:users,id',
            'anesthesiologist_id' => 'nullable|exists:doctors,id',
            'notes' => 'nullable|string|max:2000',
            'procedure_notes' => 'nullable|string|max:2000',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'devices' => 'nullable|array',
            'devices.*' => 'exists:medical_devices,id',
        ]);

        $station = $surgery->operationTheaterStation;
        if (!$station) {
            $station = $surgery->operationTheaterStation()->create([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        if ($station->status === 'pending') {
            $station->markAsStarted();
        }

        // تحديث محطة صالة العمليات
        $station->update([
            'or_nurse_id' => $validated['or_nurse_id'] ?? null,
            'anesthesiologist_id' => $validated['anesthesiologist_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'procedure_notes' => $validated['procedure_notes'] ?? null,
            'start_time' => $validated['start_time'] ?? null,
            'end_time' => $validated['end_time'] ?? null,
        ]);

        // مزامنة الأجهزة الطبية المستخدمة
        $syncData = [];
        if ($request->has('devices')) {
            foreach ($request->devices as $deviceId) {
                $syncData[$deviceId] = ['assigned_by' => auth()->id()];
            }
        }
        $surgery->medicalDevices()->sync($syncData);

        return redirect()->route('operation-theater-station.show', $surgery)
            ->with('success', 'تم حفظ بيانات صالة العمليات بنجاح');
    }

    public function complete(Surgery $surgery)
    {
        $station = $surgery->operationTheaterStation;
        if (!$station) {
            return redirect()->back()->with('error', 'المحطة غير موجودة');
        }

        $station->markAsCompleted();

        // إنشاء محطة الجراح التالية
        if (!$surgery->surgeonStation) {
            $surgery->surgeonStation()->create([
                'surgeon_id' => $surgery->doctor_id,
                'status' => 'pending',
            ]);
        }

        return redirect()->route('operation-theater-station.index')
            ->with('success', 'تم إتمام صالة العمليات والانتقال لمحطة الجراح');
    }
}

