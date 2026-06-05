<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use App\Models\LabResult;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LabStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $requests = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where(function ($q) {
                $q->whereIn('type', ['lab', 'blood_bank'])
                  ->orWhere(function ($inner) {
                      $inner->where('type', 'lab')
                            ->whereJsonContains('details->blood_bank', true);
                  });
            })
            ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $emergencyLabRequests = \App\Models\EmergencyLabRequest::with(['emergency', 'patient.user', 'labTests'])
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->orderByRaw("FIELD(priority, 'critical', 'urgent')")
            ->orderBy('requested_at', 'asc')
            ->get();

        return view('lab.index', compact('requests', 'emergencyLabRequests'));
    }

    public function show(MedicalRequest $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        if (!in_array($request->type, ['lab', 'blood_bank'])) {
            abort(403, 'هذا الطلب ليس من نوع المختبر');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $bloodBankRequest = null;
        $isBloodBankRequest = $request->type === 'blood_bank';
        if ($isBloodBankRequest) {
            $bloodBankRequest = \App\Models\BloodBankRequest::where('request_id', $request->id)->first();
        }

        $savedTestResults = [];
        $savedNotes = '';
        
        // جلب النتائج من جدول lab_results مع النتائج الفرعية
        $labResults = LabResult::where('request_id', $request->id)->with('subResults')->get();
        foreach ($labResults as $labResult) {
            $savedTestResults[$labResult->test_name] = [
                'value' => $labResult->value,
                'unit' => $labResult->unit,
                'status' => $labResult->status,
            ];
            
            // إضافة النتائج الفرعية إن وجدت
            if ($labResult->subResults->count() > 0) {
                $savedTestResults[$labResult->test_name]['sub_results'] = [];
                foreach ($labResult->subResults as $subResult) {
                    // استخدام value_text إذا كان موجوداً، وإلا استخدام value
                    $savedTestResults[$labResult->test_name]['sub_results'][$subResult->sub_test_name] = 
                        $subResult->value_text ?? $subResult->value;
                }
            }
        }

        // الاحتفاظ بالملاحظات من JSON المحفوظ
        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (is_array($resultData)) {
                $savedNotes = $resultData['notes'] ?? '';
            }
        }

        return view('lab.show', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
    }

    public function update(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $request->load(['visit']);

        // 1. اختيار تحاليل أو باقة (pending_service_selection)
        if ($request->status === 'pending_service_selection' && ($httpRequest->has('lab_test_ids') || $httpRequest->has('package_id'))) {
            $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
            if (!is_array($details)) $details = [];

            if ($httpRequest->has('package_id') && $httpRequest->package_id) {
                $packageId = $httpRequest->package_id;
                $details['package_id'] = $packageId;
                try {
                    $packageTests = \App\Models\Package::find($packageId)->labTests()->pluck('lab_tests.id')->toArray();
                } catch (\Exception $e) {
                    $packageTests = [];
                }
                $details['lab_test_ids'] = $packageTests;
                $details['package_expanded_at'] = now()->toDateTimeString();
            } else {
                $details['lab_test_ids'] = $httpRequest->lab_test_ids;
            }
            $details['services_selected'] = true;
            $details['services_selected_at'] = now()->toDateTimeString();
            $details['services_selected_by'] = auth()->id();
            $details['service_selection_type'] = $httpRequest->service_selection_type ?? ($httpRequest->package_id ? 'package' : 'general');

            $request->details = $details;
            $request->status = 'pending';
            $request->payment_status = 'pending';
            $request->save();

            $request->visit->status = 'pending_payment';
            $request->visit->save();

            return redirect()->route('lab.index')->with('success', 'تم تحديد التحاليل المطلوبة. الطلب بانتظار الدفع.');
        }

        // 2. حفظ بيانات مصرف الدم
        $isBloodBankRequest = $request->type === 'blood_bank';
        if (!$isBloodBankRequest) {
            $details = is_string($request->details) ? (json_decode($request->details, true) ?: []) : ($request->details ?? []);
            $isBloodBankRequest = data_get($details, 'blood_bank', false) === true;
        }

        if ($isBloodBankRequest) {
            $httpRequest->validate([
                'room_no' => 'nullable|string|max:100',
                'donor_group' => 'nullable|string|max:50',
                'patient_group' => 'nullable|string|max:50',
                'donor_weight' => 'nullable|numeric|min:0',
                'recipient_weight' => 'nullable|numeric|min:0',
                'at_room_temp' => 'nullable|string|max:50',
                'bovine_albumin' => 'nullable|string|max:50',
                'anti_human_globulin' => 'nullable|string|max:50',
                'compatibility' => 'nullable|string|max:50',
                'bottle_no' => 'nullable|string|max:50',
                'operative_date' => 'nullable|date',
                'exp_date' => 'nullable|date',
                'doctor_in_charge' => 'nullable|string|max:100',
                'total_amount' => 'nullable|numeric|min:0',
            ]);

            $visit = $request->visit;
            \App\Models\BloodBankRequest::updateOrCreate(
                ['request_id' => $request->id],
                [
                    'visit_id' => $request->visit_id ?? $visit?->id,
                    'patient_id' => $request->patient_id ?? $visit?->patient_id,
                    'department_id' => $request->department_id ?? $visit?->department_id,
                    'doctor_id' => $request->doctor_id ?? $visit?->doctor_id,
                    'status' => $httpRequest->status ?? ($request->status === 'pending' ? 'in_progress' : $request->status),
                    'room_no' => $httpRequest->room_no,
                    'donor_group' => $httpRequest->donor_group,
                    'patient_group' => $httpRequest->patient_group,
                    'donor_weight' => $httpRequest->donor_weight,
                    'recipient_weight' => $httpRequest->recipient_weight,
                    'at_room_temp' => $httpRequest->at_room_temp,
                    'bovine_albumin' => $httpRequest->bovine_albumin,
                    'anti_human_globulin' => $httpRequest->anti_human_globulin,
                    'compatibility' => $httpRequest->compatibility,
                    'bottle_no' => $httpRequest->bottle_no,
                    'operative_date' => $httpRequest->operative_date,
                    'exp_date' => $httpRequest->exp_date,
                    'doctor_in_charge' => $httpRequest->doctor_in_charge,
                    'total_amount' => $httpRequest->total_amount ?? 0,
                    'notes' => $httpRequest->notes ?? null,
                ]
            );

            $request->status = in_array($request->status, ['pending', 'pending_service_selection']) ? 'in_progress' : ($httpRequest->status ?? $request->status);
            $request->payment_status = 'pending';
            $request->save();

            return redirect()->route('lab.show', $request)->with('success', 'تم حفظ بيانات مصرف الدم بنجاح');
        }

        // 3. حفظ نتائج التحاليل
        if ($httpRequest->has('test_results') && is_array($httpRequest->test_results)) {
            LabResult::where('request_id', $request->id)->delete();

            $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
            if (!is_array($details)) $details = [];

            $sourceType = !empty($details['package_id']) ? 'package' : 'general';
            $packageId = $details['package_id'] ?? null;
            $savedResults = 0;

            foreach ($httpRequest->test_results as $testName => $data) {
                if (!empty($data['value'])) {
                    $labResult = LabResult::create([
                        'visit_id' => $request->visit_id,
                        'request_id' => $request->id,
                        'test_name' => $testName,
                        'value' => $data['value'],
                        'unit' => $data['unit'] ?? '',
                        'status' => (new LabResult)->determineStatus($data['value'], $testName),
                        'reference_range' => (new LabResult)->getReferenceRange($testName),
                        'notes' => $data['notes'] ?? null,
                        'source_type' => $sourceType,
                        'package_id' => $packageId,
                    ]);
                    $savedResults++;

                    // حفظ النتائج الفرعية إن وجدت
                    if (!empty($data['sub_results']) && is_array($data['sub_results'])) {
                        // جلب معلومات التحليل لمعرفة أنواع الفحوصات الفرعية
                        $labTest = \App\Models\LabTest::where('name', $testName)->with('subTests')->first();
                        
                        foreach ($data['sub_results'] as $subTestName => $subValue) {
                            if ($subValue !== null && $subValue !== '') {
                                // التحقق من نوع الفحص الفرعي
                                $subTestInfo = $labTest?->subTests->firstWhere('name', $subTestName);
                                $isNumeric = $subTestInfo && $subTestInfo->result_type === 'numeric';
                                
                                \App\Models\LabResultSubResult::create([
                                    'lab_result_id' => $labResult->id,
                                    'sub_test_name' => $subTestName,
                                    'value' => $isNumeric ? $subValue : null,
                                    'value_text' => !$isNumeric ? $subValue : null,
                                ]);
                            }
                        }
                    }
                }
            }

            $request->status = 'completed';
            $request->result = json_encode([
                'test_results' => $httpRequest->test_results,
                'notes' => $httpRequest->result_notes ?? '',
            ]);
            $request->save();

            if ($request->visit) {
                $pending = $request->visit->requests()->where('id', '!=', $request->id)->where('status', '!=', 'completed')->count();
                if ($pending === 0) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }

            return redirect()->route('lab.show', $request)->with('success', "تم حفظ {$savedResults} نتيجة تحليل بنجاح");
        }

        // 4. تحديث حالة الطلب فقط
        $request->update(['status' => $httpRequest->status ?? 'completed']);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function print(MedicalRequest $request)
    {
        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $requestDetails = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($requestDetails)) $requestDetails = [];

        $isBloodBankRequest = $request->type === 'blood_bank' || data_get($requestDetails, 'blood_bank', false) === true;
        $bloodBankRequest = $isBloodBankRequest
            ? \App\Models\BloodBankRequest::where('request_id', $request->id)->first()
            : null;

        return view('staff.requests.print', compact('request', 'isBloodBankRequest', 'bloodBankRequest'));
    }
}
