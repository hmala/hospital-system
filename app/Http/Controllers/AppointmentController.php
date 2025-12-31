<?php
// app/Http/Controllers/AppointmentController.php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // إذا كان المستخدم مريضاً، يرى مواعيده فقط
        if ($user->hasRole('patient')) {
            // التحقق من وجود سجل مريض وإنشاءه إذا لم يكن موجوداً
            if (!$user->patient) {
                Patient::create([
                    'user_id' => $user->id,
                    'medical_record_number' => 'P' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                    'blood_type' => null,
                ]);
                $user->load('patient');
            }
            
            $activeAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
                ->where('patient_id', $user->patient->id)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->whereDate('appointment_date', '>=', today())
                ->latest('appointment_date')
                ->paginate(20);

            $completedAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
                ->where('patient_id', $user->patient->id)
                ->whereIn('status', ['completed', 'cancelled'])
                ->latest('appointment_date')
                ->limit(10)
                ->get();

            $todayAppointments = Appointment::with(['patient.user', 'doctor.user'])
                ->where('patient_id', $user->patient->id)
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->today()
                ->orderBy('appointment_date')
                ->get();
        } else {
            // المواعيد النشطة (المجدولة والمؤكدة) - فقط القادمة أو اليوم
            $activeAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->whereDate('appointment_date', '>=', today())
                ->latest('appointment_date')
                ->paginate(20);

            // المواعيد المكتملة والملغاة (آخر 10 فقط)
            $completedAppointments = Appointment::with(['patient.user', 'doctor.user', 'department'])
                ->whereIn('status', ['completed', 'cancelled'])
                ->latest('appointment_date')
                ->limit(10)
                ->get();

            $todayAppointments = Appointment::with(['patient.user', 'doctor.user'])
                ->whereIn('status', ['scheduled', 'confirmed'])
                ->today()
                ->orderBy('appointment_date')
                ->get();
        }

        return view('appointments.index', compact('activeAppointments', 'completedAppointments', 'todayAppointments'));
    }

    public function create()
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('appointments.create', compact('patients', 'doctors', 'departments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'required|in:' . implode(',', array_keys(\App\Models\Appointment::VISIT_REASONS)),
            'consultation_fee' => 'required|numeric|min:0',
        ]);

        // تحديد التاريخ والوقت الكامل
        $appointmentDateTime = $request->appointment_date . ' ' . $request->appointment_time . ':00';

        // التحقق من توفر التاريخ والوقت
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $appointmentDateTime)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        if ($existingAppointment) {
            return back()->withErrors(['appointment_time' => 'يوجد موعد محجوز مسبقاً لهذا الطبيب في هذا التاريخ والوقت'])->withInput();
        }

        // الحصول على أجر الكشف من الطبيب إذا لم يتم تحديده
        $consultationFee = $request->consultation_fee;
        if (!$consultationFee) {
            $doctor = Doctor::find($request->doctor_id);
            $consultationFee = $doctor->consultation_fee;
        }

        Appointment::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'department_id' => $request->department_id,
            'appointment_date' => $request->appointment_date . ' ' . $request->appointment_time . ':00',
            'reason' => $request->reason,
            'notes' => $request->notes,
            'consultation_fee' => $consultationFee,
            'duration' => $request->duration ?? 30,
            'status' => 'scheduled'
        ]);

        return redirect()->route('appointments.index')
            ->with('success', 'تم حجز الموعد بنجاح');
    }

    public function show(Appointment $appointment)
    {
        $appointment->load(['patient.user', 'doctor.user', 'department']);
        return view('appointments.show', compact('appointment'));
    }

    public function edit(Appointment $appointment)
    {
        $patients = Patient::with('user')->get();
        $doctors = Doctor::with(['user', 'department'])->where('is_active', true)->get();
        $departments = Department::where('is_active', true)->get();

        return view('appointments.edit', compact('appointment', 'patients', 'doctors', 'departments'));
    }

    public function update(Request $request, Appointment $appointment)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'department_id' => 'required|exists:departments,id',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required|date_format:H:i',
            'reason' => 'required|in:' . implode(',', array_keys(\App\Models\Appointment::VISIT_REASONS)),
            'consultation_fee' => 'required|numeric|min:0',
            'status' => 'required|in:scheduled,confirmed,completed,cancelled,no_show',
        ]);

        // تحديد التاريخ والوقت الكامل
        $appointmentDateTime = $request->appointment_date . ' ' . $request->appointment_time . ':00';

        // التحقق من توفر التاريخ والوقت (استثناء الموعد الحالي)
        $existingAppointment = Appointment::where('doctor_id', $request->doctor_id)
            ->where('appointment_date', $appointmentDateTime)
            ->where('id', '!=', $appointment->id)
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->exists();

        if ($existingAppointment) {
            return back()->withErrors(['appointment_time' => 'يوجد موعد محجوز مسبقاً لهذا الطبيب في هذا التاريخ والوقت'])->withInput();
        }

        try {
            $appointment->update([
                'patient_id' => $request->patient_id,
                'doctor_id' => $request->doctor_id,
                'department_id' => $request->department_id,
                'appointment_date' => $appointmentDateTime,
                'reason' => $request->reason,
                'notes' => $request->notes,
                'consultation_fee' => $request->consultation_fee,
                'duration' => $request->duration ?? 30,
                'status' => $request->status
            ]);

            return redirect()->route('appointments.index')
                ->with('success', 'تم تحديث الموعد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ الموعد: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(Appointment $appointment)
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'تم حذف الموعد بنجاح');
    }

    // تغيير حالة الموعد
    public function confirm(Appointment $appointment)
    {
        $appointment->confirm();

        return redirect()->back()
            ->with('success', 'تم تأكيد الموعد بنجاح');
    }

    public function complete(Appointment $appointment)
    {
        $appointment->complete();

        return redirect()->back()
            ->with('success', 'تم إكمال الموعد بنجاح');
    }

    public function cancel(Request $request, Appointment $appointment)
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:500'
        ]);

        // التحقق من أن الموعد لم يتم تحويله إلى زيارة
        if ($appointment->visit) {
            return redirect()->back()
                ->with('error', 'لا يمكن إلغاء موعد تم تحويله إلى زيارة بالفعل');
        }

        $appointment->cancel($request->cancellation_reason ?: 'تم الإلغاء من قبل المستخدم');

        return redirect()->back()
            ->with('success', 'تم إلغاء الموعد بنجاح');
    }

    // الحصول على المواعيد المتاحة للطبيب (لم تعد مستخدمة)
    // public function getAvailableSlots(Request $request)
    // {
    //     // تم إزالة هذه الدالة لأننا لم نعد نستخدم أوقات محددة
    // }
}