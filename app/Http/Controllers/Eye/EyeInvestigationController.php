<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Eye\EyeAppointment;
use App\Models\Eye\EyeInvestigation;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EyeInvestigationController extends Controller
{
    /**
     * قائمة فحوصات الأجهزة التشخيصية
     */
    public function index(Request $request)
    {
        $query = EyeInvestigation::with(['patient', 'requestedDoctor', 'performedStaff']);

        if ($request->filled('type')) {
            $query->where('investigation_type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('patient', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('medical_record_number', 'like', "%{$search}%");
            });
        }

        $investigations = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('eye.investigations.eye_investigation_index', compact('investigations'));
    }

    /**
     * إنشاء طلب فحص جهاز جديد
     */
    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        return view('eye.investigations.eye_investigation_create', compact('patient'));
    }

    /**
     * حفظ طلب الفحص
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'         => 'required|exists:patients,id',
            'investigation_type' => 'required|string',
            'eye_target'         => 'required|in:OD,OS,OU',
            'findings'           => 'nullable|string',
            'conclusion'         => 'nullable|string',
            'attachment'         => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('eye_investigations', 'public');
        }

        $investigation = EyeInvestigation::create([
            'patient_id'         => $request->patient_id,
            'eye_appointment_id' => $request->eye_appointment_id,
            'investigation_type' => $request->investigation_type,
            'eye_target'         => $request->eye_target,
            'status'             => $request->hasFile('attachment') || !empty($request->findings) ? 'completed' : 'requested',
            'findings'           => $request->findings,
            'conclusion'         => $request->conclusion,
            'measurement_data'   => $request->measurement_data ? json_decode($request->measurement_data, true) : null,
            'attachment_path'    => $attachmentPath,
            'requested_by'       => Auth::id(),
            'performed_by'       => $request->hasFile('attachment') ? Auth::id() : null,
            'performed_at'       => $request->hasFile('attachment') ? now() : null,
        ]);

        return redirect()->route('eye.investigations.show', $investigation)
            ->with('success', 'تم حفظ وتوثيق فحص الجهاز بنجاح.');
    }

    /**
     * استعراض فحص جهاز
     */
    public function show(EyeInvestigation $investigation)
    {
        $investigation->load(['patient', 'requestedDoctor', 'performedStaff']);
        return view('eye.investigations.eye_investigation_show', compact('investigation'));
    }

    /**
     * إدخال أو تعديل نتائج الفحص ورفع التقرير
     */
    public function updateResults(Request $request, EyeInvestigation $investigation)
    {
        $request->validate([
            'findings'   => 'required|string',
            'conclusion' => 'nullable|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $data = [
            'findings'     => $request->findings,
            'conclusion'   => $request->conclusion,
            'status'       => 'completed',
            'performed_by' => Auth::id(),
            'performed_at' => now(),
        ];

        if ($request->hasFile('attachment')) {
            if ($investigation->attachment_path && Storage::disk('public')->exists($investigation->attachment_path)) {
                Storage::disk('public')->delete($investigation->attachment_path);
            }
            $data['attachment_path'] = $request->file('attachment')->store('eye_investigations', 'public');
        }

        $investigation->update($data);

        return back()->with('success', 'تم اعتماد وإرفاق نتيجة فحص الجهاز بنجاح.');
    }
}
