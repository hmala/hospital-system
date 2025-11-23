<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;

class PatientVisitController extends Controller
{
    public function index()
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->patient) {
            abort(403, 'يجب أن تكون مريضاً للوصول إلى هذه الصفحة');
        }

        $patient = $user->patient;

        // زيارات المريض
        $visits = Visit::where('patient_id', $patient->id)
            ->with(['doctor.user', 'appointment', 'requests'])
            ->orderBy('visit_date', 'desc')
            ->paginate(10);

        return view('patients.visits.index', compact('visits'));
    }

    public function show(Visit $visit)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user->patient || $visit->patient_id !== $user->patient->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الزيارة');
        }

        $visit->load(['doctor.user', 'appointment', 'requests']);

        return view('patients.visits.show', compact('visit'));
    }
}
