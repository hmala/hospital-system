<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RadiologyStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $requests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('type', 'radiology')
            ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $emergencyRadiologyRequests = \App\Models\EmergencyRadiologyRequest::with(['emergency', 'patient.user', 'radiologyTypes'])
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->orderByRaw("FIELD(priority, 'critical', 'urgent')")
            ->orderBy('requested_at', 'asc')
            ->get();

        return view('radiology-staff.index', compact('requests', 'emergencyRadiologyRequests'));
    }

    public function show(MedicalRequest $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        if ($request->type !== 'radiology') {
            abort(403, 'هذا الطلب ليس من نوع الأشعة');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $savedTestResults = [];
        $savedNotes = '';
        $bloodBankRequest = null;

        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (is_array($resultData)) {
                $savedTestResults = $resultData['test_results'] ?? [];
                $savedNotes = $resultData['notes'] ?? '';
            }
        }

        return view('radiology-staff.show', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
    }

    public function update(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $request->load(['visit']);

        // 1. اختيار أنواع الأشعة (pending_service_selection)
        if ($request->status === 'pending_service_selection' && $httpRequest->has('radiology_type_ids')) {
            $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
            if (!is_array($details)) $details = [];

            $details['radiology_type_ids'] = $httpRequest->radiology_type_ids;
            $details['services_selected'] = true;
            $details['services_selected_at'] = now()->toDateTimeString();
            $details['services_selected_by'] = auth()->id();

            $request->details = $details;
            $request->status = 'pending';
            $request->payment_status = 'pending';
            $request->save();

            $request->visit->status = 'pending_payment';
            $request->visit->save();

            return redirect()->route('radiology-staff.index')->with('success', 'تم تحديد أنواع الأشعة. الطلب بانتظار الدفع.');
        }

        // 2. حفظ نتائج الأشعة (result_text/result_notes)
        if ($httpRequest->has('result_text') || $httpRequest->has('result_notes')) {
            $request->result = json_encode([
                'result_text' => $httpRequest->result_text ?? '',
                'notes' => $httpRequest->result_notes ?? '',
            ]);
            $request->status = 'completed';
            $request->save();

            if ($request->visit) {
                $pending = $request->visit->requests()->where('id', '!=', $request->id)->where('status', '!=', 'completed')->count();
                if ($pending === 0) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }

            return redirect()->route('radiology-staff.show', $request)->with('success', 'تم حفظ نتائج الأشعة بنجاح');
        }

        // 3. تحديث الحالة فقط
        $request->update(['status' => $httpRequest->status ?? 'completed']);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }
}
