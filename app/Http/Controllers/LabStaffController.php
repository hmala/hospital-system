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

        $query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where(function ($q) {
                $q->whereIn('type', ['lab', 'blood_bank'])
                  ->orWhere(function ($inner) {
                      $inner->where('type', 'lab')
                            ->whereJsonContains('details->blood_bank', true);
                  });
            })
            ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed']);

        if (request()->filled('search')) {
            $s = trim(request('search'));
            $query->where(function($q) use ($s) {
                $q->where('id', $s)
                  ->orWhereHas('visit.patient.user', function($pq) use ($s) {
                      $pq->where('name', 'like', "%{$s}%")
                         ->orWhere('phone', 'like', "%{$s}%");
                  })
                  ->orWhereHas('visit.doctor.user', function($dq) use ($s) {
                      $dq->where('name', 'like', "%{$s}%");
                  });
            });
        }

        $sourceFilter = request('source', 'all');
        if ($sourceFilter === 'consultation') {
            $query->whereHas('visit', function($vq) {
                $vq->whereNotNull('doctor_id');
            });
        } elseif ($sourceFilter === 'direct') {
            $query->where(function($q) {
                $q->whereDoesntHave('visit')
                  ->orWhereHas('visit', function($vq) {
                      $vq->whereNull('doctor_id');
                  });
            });
        }

        $statusFilter = request('status', 'all');
        if ($statusFilter === 'pending') {
            $query->whereIn('status', ['pending', 'pending_service_selection', 'in_progress']);
        } elseif ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        }

        $dateFilter = request('date', 'all');
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        }

        $baseCountQuery = MedicalRequest::where(function ($q) {
            $q->whereIn('type', ['lab', 'blood_bank'])
              ->orWhere(function ($inner) {
                  $inner->where('type', 'lab')
                        ->whereJsonContains('details->blood_bank', true);
              });
        })->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed']);

        $activeSourceQuery = clone $baseCountQuery;
        if ($sourceFilter === 'consultation') {
            $activeSourceQuery->whereHas('visit', fn($vq) => $vq->whereNotNull('doctor_id'));
        } elseif ($sourceFilter === 'direct') {
            $activeSourceQuery->where(fn($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn($vq) => $vq->whereNull('doctor_id')));
        }

        // Emergency queries & counts
        $emergencyBaseQuery = \App\Models\EmergencyLabRequest::whereIn('status', ['pending', 'in_progress', 'completed']);
        $emergencyCount = (clone $emergencyBaseQuery)->count();
        $emergencyPendingCount = (clone $emergencyBaseQuery)->whereIn('status', ['pending', 'in_progress'])->count();
        $emergencyCompletedCount = (clone $emergencyBaseQuery)->where('status', 'completed')->count();

        if ($sourceFilter === 'emergency') {
            $emergencyQuery = \App\Models\EmergencyLabRequest::with(['emergency', 'patient.user', 'labTests.references'])
                ->whereIn('status', ['pending', 'in_progress', 'completed']);

            if ($statusFilter === 'pending') {
                $emergencyQuery->whereIn('status', ['pending', 'in_progress']);
            } elseif ($statusFilter === 'completed') {
                $emergencyQuery->where('status', 'completed');
            }

            if ($dateFilter === 'today') {
                $emergencyQuery->whereDate('requested_at', now()->toDateString());
            }

            if (!empty($s)) {
                $emergencyQuery->where(function ($eq) use ($s) {
                    $eq->where('emergency_id', 'like', "%{$s}%")
                       ->orWhereHas('patient.user', function ($puq) use ($s) {
                           $puq->where('name', 'like', "%{$s}%");
                       });
                });
            }

            $emergencyLabRequests = $emergencyQuery->orderByRaw("FIELD(priority, 'critical', 'urgent')")
                ->orderBy('requested_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            $requests = collect();

            $counts = [
                'all' => (clone $baseCountQuery)->count(),
                'consultation' => (clone $baseCountQuery)->whereHas('visit', fn($vq) => $vq->whereNotNull('doctor_id'))->count(),
                'direct' => (clone $baseCountQuery)->where(fn($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn($vq) => $vq->whereNull('doctor_id')))->count(),
                'emergency' => $emergencyCount,
                'pending' => $emergencyPendingCount,
                'completed' => $emergencyCompletedCount,
                'tab_total' => $emergencyCount,
            ];
        } else {
            $counts = [
                'all' => (clone $baseCountQuery)->count(),
                'consultation' => (clone $baseCountQuery)->whereHas('visit', fn($vq) => $vq->whereNotNull('doctor_id'))->count(),
                'direct' => (clone $baseCountQuery)->where(fn($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn($vq) => $vq->whereNull('doctor_id')))->count(),
                'emergency' => $emergencyCount,
                'pending' => (clone $activeSourceQuery)->whereIn('status', ['pending', 'pending_service_selection', 'in_progress'])->count(),
                'completed' => (clone $activeSourceQuery)->where('status', 'completed')->count(),
                'tab_total' => (clone $activeSourceQuery)->count(),
            ];

            $requests = $query->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            $emergencyLabRequests = collect();
        }

        return view('lab.index', compact('requests', 'emergencyLabRequests', 'counts', 'statusFilter', 'dateFilter', 'sourceFilter'));
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
        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (is_array($resultData)) {
                $savedTestResults = $resultData['test_results'] ?? [];
                $savedNotes = $resultData['notes'] ?? '';
            }
        }

        return view('lab.show', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
    }

    public function attachment(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();

        // السماح لكافة الكوادر المعنية (المختبر، الأطباء، الطوارئ، الاستقبال، الإدارة) أو من يملك صلاحية عرض الفحوصات
        if (!$user->hasAnyRole(['admin', 'doctor', 'lab_staff', 'emergency_staff', 'receptionist', 'nurse']) && !$user->can('view lab tests')) {
            abort(403, 'غير مصرح لك بعرض المرفقات');
        }

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        $testName = $httpRequest->query('test');

        $attachmentPath = null;
        if ($testName && !empty($details['test_attachments'][$testName]['path'])) {
            $attachmentPath = $details['test_attachments'][$testName]['path'];
        } elseif (!empty($details['attachment'])) {
            $attachmentPath = $details['attachment'];
        }

        if (!$attachmentPath) {
            abort(404, 'لا يوجد تقرير مرفق لهذا الطلب');
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachmentPath)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->response($attachmentPath);
        }

        $fullPath = storage_path('app/public/' . $attachmentPath);
        if (file_exists($fullPath)) {
            return response()->file($fullPath);
        }

        abort(404, 'الملف المرفق غير موجود في وحدة التخزين');
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

        // 3. معالجة المرفقات (تقرير عام أو تقارير مخصصة لكل فحص) وحفظ نتائج التحاليل
        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($details)) $details = [];

        $hasNewAttachment = false;

        // أ. معالجة المرفق العام (attachment)
        if ($httpRequest->hasFile('attachment')) {
            $file = $httpRequest->file('attachment');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('lab_attachments', $filename, 'public');
            
            $details['attachment'] = $path;
            $details['attachment_name'] = $file->getClientOriginalName();
            $details['attachment_mime'] = $file->getClientMimeType();
            $details['attachment_title'] = $httpRequest->attachment_title ?: 'تقرير جهاز التحاليل العام';
            $details['attached_at'] = now()->toDateTimeString();
            $hasNewAttachment = true;
        } elseif ($httpRequest->has('remove_attachment') && $httpRequest->remove_attachment == '1') {
            if (!empty($details['attachment'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($details['attachment']);
            }
            unset($details['attachment'], $details['attachment_name'], $details['attachment_mime'], $details['attachment_title'], $details['attached_at']);
        }

        // ب. معالجة المرفقات المخصصة لكل فحص (test_attachments)
        if (!isset($details['test_attachments']) || !is_array($details['test_attachments'])) {
            $details['test_attachments'] = [];
        }

        if ($httpRequest->hasFile('test_attachments')) {
            $testFiles = $httpRequest->file('test_attachments');
            if (is_array($testFiles)) {
                foreach ($testFiles as $testName => $file) {
                    if ($file && $file->isValid()) {
                        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                        $path = $file->storeAs('lab_attachments', $filename, 'public');
                        
                        $details['test_attachments'][$testName] = [
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                            'title' => 'تقرير فحص: ' . $testName,
                            'attached_at' => now()->toDateTimeString(),
                        ];
                        $hasNewAttachment = true;
                    }
                }
            }
        }

        // ج. حذف مرفق فحص محدد إن طلب المستخدم
        if ($httpRequest->has('remove_test_attachment')) {
            $removes = (array) $httpRequest->remove_test_attachment;
            foreach ($removes as $tName => $val) {
                if ($val && !empty($details['test_attachments'][$tName]['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($details['test_attachments'][$tName]['path']);
                    unset($details['test_attachments'][$tName]);
                }
            }
        }

        $request->details = $details;

        $savedResults = 0;
        if ($httpRequest->has('test_results') && is_array($httpRequest->test_results)) {
            LabResult::where('request_id', $request->id)->delete();

            $sourceType = !empty($details['package_id']) ? 'package' : 'general';
            $packageId = $details['package_id'] ?? null;

            foreach ($httpRequest->test_results as $key => $data) {
                if (!empty($data['value'])) {
                    $testName = $data['test_name'] ?? $key;
                    $parentTestName = $data['parent_test_name'] ?? null;
                    $subTestId = $data['sub_test_id'] ?? null;
                    $labTestId = $data['lab_test_id'] ?? null;
                    $unit = $data['unit'] ?? '';
                    $refRange = $data['reference_range'] ?? ((new LabResult)->getReferenceRange($testName));
                    $status = (new LabResult)->determineStatus($data['value'], $testName, $refRange);
                    if (!empty($data['status']) && in_array($data['status'], ['high', 'low', 'abnormal', 'positive'])) {
                        $status = $data['status'];
                    }

                    if (!$labTestId && $parentTestName) {
                        $parentTest = \App\Models\LabTest::where('name', $parentTestName)->first();
                        $labTestId = $parentTest?->id;
                    } elseif (!$labTestId) {
                        $directTest = \App\Models\LabTest::where('name', $testName)->first();
                        $labTestId = $directTest?->id;
                    }

                    LabResult::create([
                        'visit_id' => $request->visit_id,
                        'request_id' => $request->id,
                        'test_name' => $testName,
                        'parent_test_name' => $parentTestName,
                        'value' => $data['value'],
                        'unit' => $unit,
                        'status' => $status,
                        'reference_range' => $refRange,
                        'notes' => $data['notes'] ?? null,
                        'source_type' => $sourceType,
                        'package_id' => $packageId,
                        'lab_test_id' => $labTestId,
                        'sub_test_id' => $subTestId,
                    ]);
                    $savedResults++;
                }
            }
        }

        $targetStatus = $httpRequest->status ?? ($savedResults > 0 || $hasNewAttachment || !empty($details['attachment']) || !empty($details['test_attachments']) ? 'completed' : $request->status);
        $request->status = $targetStatus;

        $existingRes = is_string($request->result) ? (json_decode($request->result, true) ?: []) : ($request->result ?: []);
        if (!is_array($existingRes)) $existingRes = [];

        if ($savedResults > 0) {
            $existingRes['test_results'] = $httpRequest->test_results;
        }
        if (!empty($details['attachment'])) {
            $existingRes['attachment'] = $details['attachment'];
            $existingRes['attachment_name'] = $details['attachment_name'] ?? '';
            $existingRes['attachment_title'] = $details['attachment_title'] ?? '';
        } elseif (isset($existingRes['attachment']) && empty($details['attachment'])) {
            unset($existingRes['attachment'], $existingRes['attachment_name'], $existingRes['attachment_title']);
        }
        if (!empty($details['test_attachments'])) {
            $existingRes['test_attachments'] = $details['test_attachments'];
        } elseif (isset($existingRes['test_attachments']) && empty($details['test_attachments'])) {
            unset($existingRes['test_attachments']);
        }

        if ($httpRequest->filled('result_notes') || $httpRequest->filled('result')) {
            $rawNote = trim((string)($httpRequest->result_notes ?? $httpRequest->result));
            if (!str_starts_with($rawNote, '{') && !str_starts_with($rawNote, '[')) {
                $existingRes['notes'] = $rawNote;
            }
        } elseif ($httpRequest->has('result') && empty($httpRequest->result)) {
            unset($existingRes['notes']);
        }

        $request->result = json_encode($existingRes);
        $request->save();

        if ($targetStatus === 'completed') {
            try {
                app(\App\Services\TelegramService::class)->sendResultsReady($request);
            } catch (\Throwable $te) {
                Log::warning('Telegram lab notification error: ' . $te->getMessage());
            }

            $isDoctorVisit = $request->visit && (!empty($request->visit->doctor_id) || !empty($request->visit->appointment_id) || $request->visit->visit_type === 'checkup');
            if ($request->visit && !$isDoctorVisit) {
                $pending = $request->visit->requests()->where('id', '!=', $request->id)->where('status', '!=', 'completed')->count();
                if ($pending === 0) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }
        }

        $msg = $savedResults > 0
            ? "تم حفظ {$savedResults} نتيجة تحليل" . ($hasNewAttachment ? " مع تقارير الأجهزة المرفقة" : "") . " بنجاح"
            : ($hasNewAttachment ? "تم حفظ وإرفاق تقارير الأجهزة بنجاح" : "تم حفظ بيانات وتفاصيل الفحص بنجاح");

        // إذا تم الاعتماد الكامل ننتقل للقائمة، وإذا كان حفظ مسودة نبقى في نفس الصفحة
        if ($targetStatus === 'completed' && $httpRequest->action === 'complete') {
            return redirect()->route('lab.index')->with('success', $msg . ' وتم اعتماد وإنهاء الفحص.');
        }

        return redirect()->route('lab.show', $request)->with('success', $msg);
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
