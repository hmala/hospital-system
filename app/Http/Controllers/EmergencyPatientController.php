<?php

namespace App\Http\Controllers;

use App\Models\EmergencyPatient;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EmergencyPatientController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403);
        }

        $query = EmergencyPatient::query();

        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $patients = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('emergency_patients.index', compact('patients'));
    }

    public function show(EmergencyPatient $patient)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403);
        }

        return view('emergency_patients.show', compact('patient'));
    }

    public function migrate(EmergencyPatient $patient)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'doctor', 'nurse', 'receptionist', 'emergency_staff'])) {
            abort(403);
        }

        if ($patient->migrated) {
            return redirect()->back()->with('error', 'تم ترحيل هذا المريض سابقاً');
        }

        // create user and patient record
        $userAccount = User::create([
            'name' => $patient->name,
            'email' => 'emergency_' . time() . '_' . rand(1000,9999) . '@example.com',
            'role' => 'patient',
            'password' => bcrypt(Str::random(12)),
        ]);
        $mainPatient = Patient::create([
            'user_id' => $userAccount->id,
            'date_of_birth' => $patient->date_of_birth,
            'gender' => $patient->gender,
            'phone' => $patient->phone,
        ]);

        $patient->update(['migrated' => true, 'is_active' => false]);

        if ($patient->emergency) {
            $patient->emergency->update(['patient_id' => $mainPatient->id, 'patient_migrated' => true]);
        }

        return redirect()->back()->with('success', 'تم ترحيل المريض إلى جدول المرضى العام');
    }
}
