<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Department;
use App\Models\Room;
use App\Models\BedReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BedReservationController extends Controller
{
    public function index()
    {
        // list only bed reservations
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            abort(403);
        }

        $query = BedReservation::with(['patient.user', 'doctor.user', 'department', 'room']);

        if (request('search')) {
            $search = request('search');
            $query->whereHas('patient.user', function($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }

        $reservations = $query->orderBy('scheduled_date', 'desc')->paginate(20);
        return view('bed_reservations.index', compact('reservations'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            abort(403);
        }
        $patients = Patient::with('user')->get()->sortBy(fn($p) => $p->user->name);
        $doctors = Doctor::with('user')
                        ->where('is_active', true)
                        ->get()
                        ->sortBy(fn($d) => $d->user->name);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $rooms = Room::where('is_active', true)->orderBy('room_type')->orderBy('room_number')->get();

        $selectedPatient = null;
        if (request('patient_id')) {
            $selectedPatient = Patient::find(request('patient_id'));
        }

        return view('bed_reservations.create', compact('patients','doctors','departments','rooms', 'selectedPatient'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            abort(403);
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
        if (!empty($data['room_id'])) {
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
        if (!$user->hasRole(['admin', 'receptionist', 'surgery_staff'])) {
            abort(403);
        }

        $reservation->status = 'confirmed';
        $reservation->save();

        return redirect()->route('bed-reservations.index')->with('success', 'تم تسجيل دخول المريض للغرفة');
    }
}
