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
        
        // التحقق من وجود سجل مريض، وإنشاءه تلقائياً إذا لم يكن موجوداً
        if (!$user->patient) {
            // إنشاء سجل مريض تلقائياً
            $patient = \App\Models\Patient::create([
                'user_id' => $user->id,
                'medical_record_number' => 'P' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'blood_type' => null,
            ]);
        } else {
            $patient = $user->patient;
        }

        // زيارات المريض
        $visits = \App\Models\Visit::where('patient_id', $patient->id)
            ->with(['doctor.user', 'appointment', 'requests'])
            ->orderBy('visit_date', 'desc')
            ->paginate(10);

        return view('patients.visits.index', compact('visits'));
    }

    public function show(Visit $visit)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        
        // التحقق من وجود سجل مريض
        if (!$user->patient) {
            // إنشاء سجل مريض تلقائياً
            \App\Models\Patient::create([
                'user_id' => $user->id,
                'medical_record_number' => 'P' . str_pad($user->id, 6, '0', STR_PAD_LEFT),
                'blood_type' => null,
            ]);
            
            // إعادة تحميل العلاقة
            $user->load('patient');
        }
        
        if ($visit->patient_id !== $user->patient->id) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الزيارة');
        }

        $visit->load(['doctor.user', 'appointment', 'requests']);

        return view('patients.visits.show', compact('visit'));
    }
}
