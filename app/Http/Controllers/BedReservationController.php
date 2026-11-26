<?php

namespace App\Http\Controllers;

use App\Models\BedReservation;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Room;
use Illuminate\Http\Request;

class BedReservationController extends Controller
{
    public function index()
    {
        // list only bed reservations
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('view bed reservations') && ! $user->can('view occupancy') && ! $user->can('manage rooms')))) {
            abort(403, 'غير مصرح لك بالوصول لحجوزات الأسرّة');
        }

        $query = BedReservation::with(['patient.user', 'doctor.user', 'department', 'room']);

        if (request('search')) {
            $search = request('search');
            $query->whereHas('patient.user', function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }

        $reservations = $query->orderBy('scheduled_date', 'desc')->paginate(20);

        return view('bed_reservations.index', compact('reservations'));
    }

    public function create()
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('create bed reservations') && ! $user->can('manage rooms')))) {
            abort(403, 'غير مصرح لك بإنشاء حجز سرير');
        }

        $selectedPatient = null;
        $selectedPatientId = old('patient_id', request('patient_id'));
        if ($selectedPatientId) {
            $selectedPatient = Patient::with('user')->find($selectedPatientId);
        }

        if ($selectedPatient) {
            $patients = collect([$selectedPatient]);
        } else {
            $patients = Patient::with('user')->latest('id')->take(25)->get();
        }

        $doctors = Doctor::with('user')
            ->where('is_active', true)
            ->get()
            ->sortBy(fn ($d) => optional($d->user)->name);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('is_active', true)
            ->where('room_purpose', 'beds')
            ->orderBy('room_type')
            ->orderBy('room_number')
            ->get();

        return view('bed_reservations.create', compact('patients', 'doctors', 'departments', 'rooms', 'selectedPatient'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('create bed reservations') && ! $user->can('manage rooms')))) {
            abort(403, 'غير مصرح لك بحفظ حجز سرير');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'scheduled_date' => 'required|date',
            'scheduled_time' => 'required|date_format:H:i',
            'department_id' => 'nullable|exists:departments,id',
            'room_id' => 'nullable|exists:rooms,id',
        ]);

        $data = $request->only(['patient_id', 'doctor_id', 'scheduled_date', 'scheduled_time', 'department_id', 'room_id']);

        $reservation = BedReservation::create($data);

        // mark room occupied if provided
        if (! empty($data['room_id'])) {
            $room = Room::find($data['room_id']);
            if ($room) {
                $room->update(['status' => 'occupied']);
            }
        }

        return redirect()->route('bed-reservations.index')->with('success', 'تم حجز الرقود بنجاح');
    }

    public function confirm(Request $request, BedReservation $reservation)
    {
        $user = auth()->user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (! $isAdmin && (! $user || (! $user->can('create bed reservations') && ! $user->can('manage rooms')))) {
            abort(403, 'غير مصرح لك بتأكيد حجز سرير');
        }

        $reservation->status = 'confirmed';
        $reservation->save();

        return redirect()->route('bed-reservations.index')->with('success', 'تم تسجيل دخول المريض للغرفة');
    }
}
