<?php

namespace App\Http\Controllers;

use App\Models\LabResult;
use App\Models\NicuMedicalOrder;
use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LabStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $query = MedicalRequest::with([
                'visit.patient.user', 
                'visit.doctor.user',
                'incubatorReservation.patient.user',
                'incubatorReservation.incubator',
                'incubatorReservation.doctor.user'
            ])
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
            $query->where(function ($q) use ($s) {
                $q->where('id', $s)
                    ->orWhereHas('visit.patient.user', function ($pq) use ($s) {
                        $pq->where('name', 'like', "%{$s}%")
                            ->orWhere('phone', 'like', "%{$s}%");
                    })
                    ->orWhereHas('visit.doctor.user', function ($dq) use ($s) {
                        $dq->where('name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('incubatorReservation', function ($rq) use ($s) {
                        $rq->where('baby_name', 'like', "%{$s}%")
                            ->orWhere('id', 'like', "%{$s}%")
                            ->orWhereHas('patient.user', fn ($puq) => $puq->where('name', 'like', "%{$s}%"))
                            ->orWhereHas('doctor.user', fn ($duq) => $duq->where('name', 'like', "%{$s}%"));
                    });
            });
        }

        $sourceFilter = request('source', 'all');
        if ($sourceFilter === 'consultation') {
            $query->whereHas('visit', function ($vq) {
                $vq->whereNotNull('doctor_id');
            });
        } elseif ($sourceFilter === 'direct') {
            $query->where(function ($q) {
                $q->whereDoesntHave('visit')
                    ->orWhereHas('visit', function ($vq) {
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
            $activeSourceQuery->whereHas('visit', fn ($vq) => $vq->whereNotNull('doctor_id'));
        } elseif ($sourceFilter === 'direct') {
            $activeSourceQuery->where(fn ($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn ($vq) => $vq->whereNull('doctor_id')));
        }

        // Emergency queries & counts
        $emergencyBaseQuery = \App\Models\EmergencyLabRequest::whereIn('status', ['pending', 'in_progress', 'completed']);
        $emergencyCount = (clone $emergencyBaseQuery)->count();
        $emergencyPendingCount = (clone $emergencyBaseQuery)->whereIn('status', ['pending', 'in_progress'])->count();
        $emergencyCompletedCount = (clone $emergencyBaseQuery)->where('status', 'completed')->count();

        // NICU Lab queries & counts
        $nicuRequestCount = MedicalRequest::where('type', 'lab')
            ->whereNotNull('incubator_reservation_id')
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->count();

        $useNicuMedicalRequests = $nicuRequestCount > 0;

        if ($useNicuMedicalRequests) {
            $nicuBaseQuery = MedicalRequest::where('type', 'lab')
                ->whereNotNull('incubator_reservation_id')
                ->whereIn('status', ['pending', 'in_progress', 'completed']);
        } else {
            $nicuBaseQuery = NicuMedicalOrder::where('order_type', 'lab')
                ->whereIn('status', ['pending', 'in_progress', 'completed']);
        }
        $nicuCount = (clone $nicuBaseQuery)->count();
        $nicuPendingCount = (clone $nicuBaseQuery)->whereIn('status', ['pending', 'in_progress'])->count();
        $nicuCompletedCount = (clone $nicuBaseQuery)->where('status', 'completed')->count();

        $nicuLabRequests = collect();

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

            if (! empty($s)) {
                $emergencyQuery->where(function ($eq) use ($s) {
                    $eq->where('emergency_id', 'like', "%{$s}%")
                        ->orWhereHas('patient.user', function ($puq) use ($s) {
                            $puq->where('name', 'like', "%{$s}%");
                        });
                });
            }

            $emergencyLabRequests = $emergencyQuery->orderByRaw("CASE priority WHEN 'critical' THEN 1 WHEN 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('requested_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            $requests = collect();

            $counts = [
                'all' => (clone $baseCountQuery)->count(),
                'consultation' => (clone $baseCountQuery)->whereHas('visit', fn ($vq) => $vq->whereNotNull('doctor_id'))->count(),
                'direct' => (clone $baseCountQuery)->where(fn ($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn ($vq) => $vq->whereNull('doctor_id')))->count(),
                'emergency' => $emergencyCount,
                'nicu' => $nicuCount,
                'pending' => $emergencyPendingCount,
                'completed' => $emergencyCompletedCount,
                'tab_total' => $emergencyCount,
            ];
        } elseif ($sourceFilter === 'nicu') {
            if ($useNicuMedicalRequests) {
                $nicuQuery = MedicalRequest::with(['incubatorReservation.patient.user', 'incubatorReservation.incubator', 'incubatorReservation.doctor.user', 'labResults'])
                    ->where('type', 'lab')
                    ->whereNotNull('incubator_reservation_id')
                    ->whereIn('status', ['pending', 'in_progress', 'completed']);

                if ($statusFilter === 'pending') {
                    $nicuQuery->whereIn('status', ['pending', 'in_progress']);
                } elseif ($statusFilter === 'completed') {
                    $nicuQuery->where('status', 'completed');
                }

                if ($dateFilter === 'today') {
                    $nicuQuery->whereDate('created_at', now()->toDateString());
                }

                if (!empty($s)) {
                    $nicuQuery->where(function ($nq) use ($s) {
                        $nq->where('id', $s)
                           ->orWhere('description', 'like', "%{$s}%")
                           ->orWhereHas('incubatorReservation', function($rq) use ($s) {
                               $rq->where('baby_name', 'like', "%{$s}%")
                                  ->orWhere('id', 'like', "%{$s}%")
                                  ->orWhereHas('patient.user', fn($puq) => $puq->where('name', 'like', "%{$s}%"));
                           });
                    });
                }

                $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
                $extractSql = $isSqlite ? "json_extract(details, '$.priority')" : "JSON_UNQUOTE(JSON_EXTRACT(details, '$.priority'))";
                $nicuLabRequests = $nicuQuery->orderByRaw("CASE WHEN {$extractSql} = 'stat' THEN 1 WHEN {$extractSql} = 'urgent' THEN 2 ELSE 3 END")
                    ->orderBy('created_at', 'desc')
                    ->paginate(15)
                    ->withQueryString();
            } else {
                $nicuQuery = NicuMedicalOrder::with(['reservation.patient.user', 'reservation.incubator', 'reservation.doctor.user', 'labTest.references'])
                    ->where('order_type', 'lab')
                    ->whereIn('status', ['pending', 'in_progress', 'completed']);

                if ($statusFilter === 'pending') {
                    $nicuQuery->whereIn('status', ['pending', 'in_progress']);
                } elseif ($statusFilter === 'completed') {
                    $nicuQuery->where('status', 'completed');
                }

                if ($dateFilter === 'today') {
                    $nicuQuery->whereDate('ordered_at', now()->toDateString());
                }

                if (!empty($s)) {
                    $nicuQuery->where(function ($nq) use ($s) {
                        $nq->where('item_name', 'like', "%{$s}%")
                           ->orWhere('id', 'like', "%{$s}%")
                           ->orWhereHas('reservation', function($rq) use ($s) {
                               $rq->where('baby_name', 'like', "%{$s}%")
                                  ->orWhere('id', 'like', "%{$s}%")
                                  ->orWhereHas('patient.user', fn($puq) => $puq->where('name', 'like', "%{$s}%"));
                           });
                    });
                }

                $nicuLabRequests = $nicuQuery->orderByRaw("CASE WHEN priority = 'stat' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                    ->orderBy('ordered_at', 'desc')
                    ->paginate(15)
                    ->withQueryString();
            }

            $requests = collect();
            $emergencyLabRequests = collect();

            $counts = [
                'all' => (clone $baseCountQuery)->count(),
                'consultation' => (clone $baseCountQuery)->whereHas('visit', fn($vq) => $vq->whereNotNull('doctor_id'))->count(),
                'direct' => (clone $baseCountQuery)->where(fn($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn($vq) => $vq->whereNull('doctor_id')))->count(),
                'emergency' => $emergencyCount,
                'nicu' => $nicuCount,
                'pending' => $nicuPendingCount,
                'completed' => $nicuCompletedCount,
                'tab_total' => $nicuCount,
            ];
        } else {
            $counts = [
                'all' => (clone $baseCountQuery)->count(),
                'consultation' => (clone $baseCountQuery)->whereHas('visit', fn ($vq) => $vq->whereNotNull('doctor_id'))->count(),
                'direct' => (clone $baseCountQuery)->where(fn ($q) => $q->whereDoesntHave('visit')->orWhereHas('visit', fn ($vq) => $vq->whereNull('doctor_id')))->count(),
                'emergency' => $emergencyCount,
                'nicu' => $nicuCount,
                'pending' => (clone $activeSourceQuery)->whereIn('status', ['pending', 'pending_service_selection', 'in_progress'])->count(),
                'completed' => (clone $activeSourceQuery)->where('status', 'completed')->count(),
                'tab_total' => (clone $activeSourceQuery)->count(),
            ];

            $requests = $query->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();

            $emergencyLabRequests = collect();
        }

        return view('lab.index', compact('requests', 'emergencyLabRequests', 'nicuLabRequests', 'counts', 'statusFilter', 'dateFilter', 'sourceFilter', 'useNicuMedicalRequests'));
    }

    public function show(MedicalRequest $request)
    {
        $user = Auth::user();

        if (! $user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        if (! in_array($request->type, ['lab', 'blood_bank'])) {
            abort(403, 'هذا الطلب ليس من نوع المختبر');
        }

        $request->load([
            'visit.patient.user', 
            'visit.doctor.user',
            'incubatorReservation.patient.user',
            'incubatorReservation.incubator.room',
            'incubatorReservation.doctor.user'
        ]);

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

        $packages = \App\Models\Package::where('is_active', true)->with('labTests')->orderBy('name')->get();
        $userGroups = \App\Models\UserLabTestGroup::where('user_id', $user->id)->with('labTests')->orderBy('name')->get();

        return view('lab.show', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest', 'packages', 'userGroups'));
    }

    public function attachment(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        $token = $httpRequest->query('token');
        $validToken = method_exists($request, 'getPublicReportToken') ? $request->getPublicReportToken() : null;
        $isValidToken = $token && $validToken && hash_equals($validToken, (string) $token);
        $isStaffUser = $user && ($user->hasAnyRole(['admin', 'doctor', 'lab_staff', 'emergency_staff', 'receptionist', 'nurse']) || $user->can('view lab tests'));

        // السماح لكافة الكوادر الطبية أو للمريض الحامل للرمز المشفر من الواتساب
        if (! $isStaffUser && ! $isValidToken && ! $httpRequest->hasValidSignature()) {
            abort(403, 'غير مصرح لك بعرض المرفقات');
        }

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        $testName = $httpRequest->query('test');

        $attachmentPath = null;
        if ($testName && ! empty($details['test_attachments'][$testName]['path'])) {
            $attachmentPath = $details['test_attachments'][$testName]['path'];
        } elseif (! empty($details['attachment'])) {
            $attachmentPath = $details['attachment'];
        }

        if (! $attachmentPath) {
            abort(404, 'لا يوجد تقرير مرفق لهذا الطلب');
        }

        if (\Illuminate\Support\Facades\Storage::disk('public')->exists($attachmentPath)) {
            return \Illuminate\Support\Facades\Storage::disk('public')->response($attachmentPath);
        }

        $fullPath = storage_path('app/public/'.$attachmentPath);
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
            if (! is_array($details)) {
                $details = [];
            }

            if ($httpRequest->has('package_id') && $httpRequest->package_id) {
                $packageId = $httpRequest->package_id;
                $details['package_id'] = $packageId;
                try {
                    $package = \App\Models\Package::find($packageId);
                    $packageTests = $package ? $package->labTests()->pluck('lab_tests.id')->toArray() : [];
                    if ($package) {
                        $details['package_name'] = $package->name;
                        $details['package_price'] = (float) $package->price;
                        $details['package_test_ids'] = $packageTests;
                        $request->total_amount = (float) $package->price;
                    }
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
        if (! $isBloodBankRequest) {
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
        if (! is_array($details)) {
            $details = [];
        }

        $hasNewAttachment = false;

        // أ. معالجة المرفق العام (attachment)
        if ($httpRequest->hasFile('attachment')) {
            $file = $httpRequest->file('attachment');
            $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('lab_attachments', $filename, 'public');

            $details['attachment'] = $path;
            $details['attachment_name'] = $file->getClientOriginalName();
            $details['attachment_mime'] = $file->getClientMimeType();
            $details['attachment_title'] = $httpRequest->attachment_title ?: 'تقرير جهاز التحاليل العام';
            $details['attached_at'] = now()->toDateTimeString();
            $hasNewAttachment = true;
        } elseif ($httpRequest->has('remove_attachment') && $httpRequest->remove_attachment == '1') {
            if (! empty($details['attachment'])) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($details['attachment']);
            }
            unset($details['attachment'], $details['attachment_name'], $details['attachment_mime'], $details['attachment_title'], $details['attached_at']);
        }

        // ب. معالجة المرفقات المخصصة لكل فحص (test_attachments)
        if (! isset($details['test_attachments']) || ! is_array($details['test_attachments'])) {
            $details['test_attachments'] = [];
        }

        if ($httpRequest->hasFile('test_attachments')) {
            $testFiles = $httpRequest->file('test_attachments');
            if (is_array($testFiles)) {
                foreach ($testFiles as $testName => $file) {
                    if ($file && $file->isValid()) {
                        $filename = time().'_'.uniqid().'.'.$file->getClientOriginalExtension();
                        $path = $file->storeAs('lab_attachments', $filename, 'public');

                        $details['test_attachments'][$testName] = [
                            'path' => $path,
                            'name' => $file->getClientOriginalName(),
                            'mime' => $file->getClientMimeType(),
                            'title' => 'تقرير فحص: '.$testName,
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
                if ($val && ! empty($details['test_attachments'][$tName]['path'])) {
                    \Illuminate\Support\Facades\Storage::disk('public')->delete($details['test_attachments'][$tName]['path']);
                    unset($details['test_attachments'][$tName]);
                }
            }
        }

        $request->details = $details;

        $savedResults = 0;
        if ($httpRequest->has('test_results') && is_array($httpRequest->test_results)) {
            LabResult::where('request_id', $request->id)->delete();

            $sourceType = ! empty($details['package_id']) ? 'package' : 'general';
            $packageId = $details['package_id'] ?? null;

            $testResults = $httpRequest->test_results;
            $this->autoCalculateLipidResults($testResults);

            foreach ($testResults as $key => $data) {
                if (! empty($data['value'])) {
                    $testName = $data['test_name'] ?? $key;
                    $parentTestName = $data['parent_test_name'] ?? null;
                    $subTestId = $data['sub_test_id'] ?? null;
                    $labTestId = $data['lab_test_id'] ?? null;
                    $unit = $data['unit'] ?? '';
                    $refRange = $data['reference_range'] ?? ((new LabResult)->getReferenceRange($testName));
                    $status = (new LabResult)->determineStatus($data['value'], $testName, $refRange);
                    if (! empty($data['status']) && in_array($data['status'], ['high', 'low', 'abnormal', 'positive'])) {
                        $status = $data['status'];
                    }

                    if (! $labTestId && $parentTestName) {
                        $parentTest = \App\Models\LabTest::where('name', $parentTestName)->first();
                        $labTestId = $parentTest?->id;
                    } elseif (! $labTestId) {
                        $directTest = \App\Models\LabTest::where('name', $testName)->first();
                        $labTestId = $directTest?->id;
                    }

                    LabResult::create([
                        'visit_id' => $request->visit_id,
                        'incubator_reservation_id' => $request->incubator_reservation_id,
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

        $action = $httpRequest->input('action', 'complete');
        $isDraft = ($action === 'draft') || ($httpRequest->input('status') === 'in_progress');

        if ($isDraft) {
            $targetStatus = 'in_progress';
        } else {
            $targetStatus = $httpRequest->input('status', 'completed');
            if ($targetStatus !== 'completed' && ($savedResults > 0 || $hasNewAttachment || ! empty($details['attachment']) || ! empty($details['test_attachments']))) {
                $targetStatus = 'completed';
            }
        }
        $request->status = $targetStatus;

        $existingRes = is_string($request->result) ? (json_decode($request->result, true) ?: []) : ($request->result ?: []);
        if (! is_array($existingRes)) {
            $existingRes = [];
        }

        if ($savedResults > 0) {
            $existingRes['test_results'] = $testResults;
        }
        if (! empty($details['attachment'])) {
            $existingRes['attachment'] = $details['attachment'];
            $existingRes['attachment_name'] = $details['attachment_name'] ?? '';
            $existingRes['attachment_title'] = $details['attachment_title'] ?? '';
        } elseif (isset($existingRes['attachment']) && empty($details['attachment'])) {
            unset($existingRes['attachment'], $existingRes['attachment_name'], $existingRes['attachment_title']);
        }
        if (! empty($details['test_attachments'])) {
            $existingRes['test_attachments'] = $details['test_attachments'];
        } elseif (isset($existingRes['test_attachments']) && empty($details['test_attachments'])) {
            unset($existingRes['test_attachments']);
        }

        if ($httpRequest->filled('result_notes') || $httpRequest->filled('result')) {
            $rawNote = trim((string) ($httpRequest->result_notes ?? $httpRequest->result));
            if (! str_starts_with($rawNote, '{') && ! str_starts_with($rawNote, '[')) {
                $existingRes['notes'] = $rawNote;
            }
        } elseif ($httpRequest->has('result') && empty($httpRequest->result)) {
            unset($existingRes['notes']);
        }

        $request->result = json_encode($existingRes);
        $request->save();

        $patientName = $request->incubatorReservation?->baby_title ?? ($request->visit?->patient?->name ?? 'مريض');
        $countInfo = $savedResults > 0 ? " ({$savedResults} نتيجة تحليل)" : ($hasNewAttachment ? ' (تقرير جهاز مرفق)' : '');
        $logAction = $isDraft ? 'save_lab_draft' : 'save_lab_results';
        $logDesc = $isDraft
            ? "حفظ مسودة مؤقتة لنتائج التحاليل للمريض: {$patientName}{$countInfo} - طلب #{$request->id} (قيد الفحص بالمختبر - لم تُرسل للطبيب)"
            : "حفظ واعتماد نهائي لنتائج التحاليل وإرسالها للطبيب للمريض: {$patientName}{$countInfo} - طلب #{$request->id}";

        \App\Services\UserActivityLogger::log(
            action: $logAction,
            category: 'lab',
            description: $logDesc,
            subject: $request,
            isSensitive: false,
            properties: ['saved_results' => $savedResults, 'status' => $targetStatus, 'action' => $action]
        );

        if ($targetStatus === 'completed') {
            if ($request->incubator_reservation_id) {
                NicuMedicalOrder::where('order_group_id', 'REQ-' . $request->id)
                    ->orWhere(function($q) use ($request) {
                        $q->where('reservation_id', $request->incubator_reservation_id)
                          ->where('order_type', 'lab')
                          ->where('status', '!=', 'completed');
                    })
                    ->update([
                        'status' => 'completed',
                        'completed_at' => now(),
                        'processed_by' => auth()->id(),
                    ]);
            }

            try {
                app(\App\Services\TelegramService::class)->sendResultsReady($request);
            } catch (\Throwable $te) {
                Log::warning('Telegram lab notification error: '.$te->getMessage());
            }

            $isDoctorVisit = $request->visit && (! empty($request->visit->doctor_id) || ! empty($request->visit->appointment_id) || $request->visit->visit_type === 'checkup');
            if ($request->visit && ! $isDoctorVisit) {
                $pending = $request->visit->requests()->where('id', '!=', $request->id)->where('status', '!=', 'completed')->count();
                if ($pending === 0) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }
        }

        if ($isDraft) {
            $draftMsg = $savedResults > 0
                ? "تم حفظ مسودة النتائج ({$savedResults} نتيجة فحص) بنجاح. الطلب لا يزال قيد الفحص بالمختبر ولم يتم إرساله للطبيب بعد."
                : ($hasNewAttachment ? 'تم حفظ تقارير الأجهزة كمسودة بنجاح.' : 'تم حفظ مسودة نتائج الفحص بنجاح.');

            return redirect()->route('lab.show', $request)->with('success', $draftMsg);
        }

        $msg = $savedResults > 0
            ? "تم حفظ {$savedResults} نتيجة تحليل".($hasNewAttachment ? ' مع تقارير الأجهزة المرفقة' : '').' بنجاح'
            : ($hasNewAttachment ? 'تم حفظ وإرفاق تقارير الأجهزة بنجاح' : 'تم حفظ بيانات وتفاصيل الفحص بنجاح');

        return redirect()->route('lab.index')->with('success', $msg.' وتم الاعتماد النهائي والإرسال لعيادة الطبيب.');
    }

    public function print(MedicalRequest $request)
    {
        $request->load([
            'visit.patient.user', 
            'visit.doctor.user',
            'incubatorReservation.patient.user',
            'incubatorReservation.incubator',
            'incubatorReservation.doctor.user'
        ]);

        $requestDetails = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (! is_array($requestDetails)) {
            $requestDetails = [];
        }

        $isBloodBankRequest = $request->type === 'blood_bank' || data_get($requestDetails, 'blood_bank', false) === true;
        $bloodBankRequest = $isBloodBankRequest
            ? \App\Models\BloodBankRequest::where('request_id', $request->id)->first()
            : null;

        return view('staff.requests.print', compact('request', 'isBloodBankRequest', 'bloodBankRequest'));
    }

    /**
     * إلغاء الطلب الخارجي المباشر للمختبر
     */
    public function cancel(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        if (! $user || ! $user->hasAnyRole(['lab_staff', 'admin', 'admin-hsop', 'hospital_admin'])) {
            abort(403, 'غير مصرح لك بإلغاء هذا الطلب');
        }

        // 1. فحص هل الطلب صادر من طبيب استشاري داخل العيادات
        if (! $request->isDirectExternal()) {
            $msg = 'عذراً، هذا الطلب صادر عن طبيب استشاري داخل العيادات ولا يمكن إلغاؤه من المختبر. الإلغاء يتم حصراً من شاشة الطبيب المعالج.';
            if ($httpRequest->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        // 2. فحص هل الفحص مكتمل ومعتمد بالفعل
        if ($request->status === 'completed') {
            $msg = 'لا يمكن إلغاء الطلب لأنه مكتمل ومعتمد بالنتائج المخبرية بالفعل.';
            if ($httpRequest->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        if ($request->status === 'cancelled') {
            $msg = 'الطلب ملغي بالفعل.';
            if ($httpRequest->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('info', $msg);
        }

        $wasPaid = ($request->payment_status === 'paid');

        // 3. تنفيذ الإلغاء
        $cancelReason = trim($httpRequest->input('cancel_reason', 'إلغاء الطلب المباشر من قسم المختبر بطلب من المريض'));

        $details = is_string($request->details) ? (json_decode($request->details, true) ?: []) : ($request->details ?? []);
        $details['cancelled_at'] = now()->toDateTimeString();
        $details['cancelled_by'] = $user->id;
        $details['cancelled_by_name'] = $user->name;
        $details['cancel_reason'] = $cancelReason;
        $details['cancelled_after_payment'] = $wasPaid;
        if ($wasPaid) {
            $details['refund_status'] = 'pending_refund';
            $receipt = $request->payments()->latest()->first();
            if ($receipt) {
                $details['paid_receipt_id'] = $receipt->id;
                $details['paid_receipt_number'] = $receipt->receipt_number;
                $details['paid_amount'] = (float) $receipt->amount;
            }
        }

        $request->status = 'cancelled';
        $request->payment_status = 'cancelled';
        $request->details = $details;
        $request->save();

        // تحديث زيارة الاستعلامات المباشرة إذا لم يتبق أي طلبات نشطة أخرى
        if ($request->visit && $request->isDirectExternal()) {
            $activeCount = $request->visit->requests()
                ->where('id', '!=', $request->id)
                ->where('status', '!=', 'cancelled')
                ->count();
            if ($activeCount === 0) {
                $request->visit->status = 'cancelled';
                $request->visit->save();
            }
        }

        // تسجيل النشاط
        $patientName = $request->visit?->patient?->name ?? 'مريض';
        $paidNote = $wasPaid ? ' (مسدد مالياً - مستحق استرجاع في الكاشير)' : '';
        \App\Services\UserActivityLogger::log(
            action: 'cancel_external_lab_request',
            category: 'lab',
            description: "إلغاء طلب المختبر الخارجي للمريض: {$patientName} - طلب #{$request->id}{$paidNote} (السبب: {$cancelReason})",
            subject: $request,
            isSensitive: false,
            properties: [
                'cancel_reason' => $cancelReason,
                'was_paid' => $wasPaid,
            ]
        );

        if ($wasPaid) {
            $successMsg = "تم إلغاء الطلب الخارجي #{$request->id} بنجاح. ⚠️ يرجى توجيه المريض إلى الكاشير لاسترداد المبلغ المسدد.";
        } else {
            $successMsg = "تم إلغاء الطلب الخارجي #{$request->id} بنجاح وإزالته من طابور الفحص والكاشير.";
        }

        if ($httpRequest->expectsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }

        return redirect()->route('lab.index')->with($wasPaid ? 'warning' : 'success', $successMsg);
    }

    /**
     * بدء فحص تحليل الطفل الخديج
     */
    public function startNicuOrder(NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك ببدء هذا الفحص');
        }

        $order->update([
            'status' => NicuMedicalOrder::STATUS_IN_PROGRESS,
            'processed_by' => $user->id,
        ]);

        return redirect()->back()->with('success', "تم بدء فحص التحليل للطفل الخديج ({$order->item_name}) بنجاح.");
    }

    /**
     * إكمال واعتماد نتيجة تحليل الطفل الخديج
     */
    public function completeNicuOrder(HttpRequest $request, NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بإدخال نتائج هذا الفحص');
        }

        $validated = $request->validate([
            'result' => 'required|string',
            'unit' => 'nullable|string|max:50',
            'reference_range' => 'nullable|string|max:100',
            'result_status' => 'nullable|in:normal,high,low,abnormal',
            'technician_notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $attachmentPath = $order->attachment_path;
        $attachmentName = $order->attachment_name;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('lab_attachments', $filename, 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $order->update([
            'result' => $validated['result'],
            'unit' => $validated['unit'] ?? $order->unit,
            'reference_range' => $validated['reference_range'] ?? $order->reference_range,
            'result_status' => $validated['result_status'] ?? 'normal',
            'technician_notes' => $validated['technician_notes'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'status' => NicuMedicalOrder::STATUS_COMPLETED,
            'processed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم توثيق واعتماد نتيجة التحليل للطفل الخديج ({$order->item_name}) بنجاح.");
    }

    /**
     * طباعة تقرير تحليل الطفل الخديج الرسمي
     */
    public function printNicuOrder(NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['lab_staff', 'admin', 'doctor', 'nurse', 'receptionist'])) {
            abort(403, 'غير مصرح لك بطباعة هذا الفحص');
        }

        $order->load(['reservation.patient.user', 'reservation.incubator', 'reservation.doctor.user', 'processedByUser', 'labTest']);

        return view('nicu.print.lab-result', compact('order'));
    }

    /**
     * الحساب التلقائي لمعادلات باقة الدهون (Lipid Profile - Friedewald Formula)
     * VLDL = TG / 5
     * LDL = TC - HDL - (TG / 5) (عندما يكون TG < 400 mg/dL)
     */
    protected function autoCalculateLipidResults(array &$testResults): void
    {
        $tg = null;
        $tc = null;
        $hdl = null;
        $vldlKey = null;
        $ldlKey = null;

        foreach ($testResults as $key => $item) {
            $name = mb_strtolower(trim($item['test_name'] ?? $key));
            $val = isset($item['value']) && $item['value'] !== '' ? (float) str_replace(',', '', (string) $item['value']) : null;

            // الدهون الثلاثية (TG)
            if (str_contains($name, 'triglyceride') || $name === 'tg' || str_contains($name, 'دهون ثلاثية')) {
                if ($val !== null && is_numeric($val)) {
                    $tg = $val;
                }
            }
            // الكوليسترول الكلي (TC) - مع استبعاد HDL, LDL, VLDL
            elseif ((str_contains($name, 'cholesterol') || $name === 'tc' || str_contains($name, 'كوليسترول') || str_contains($name, 'كولسترول'))
                    && ! str_contains($name, 'hdl') && ! str_contains($name, 'ldl') && ! str_contains($name, 'vldl')
                    && ! str_contains($name, 'high') && ! str_contains($name, 'low') && ! str_contains($name, 'عالي') && ! str_contains($name, 'منخفض')) {
                if ($val !== null && is_numeric($val)) {
                    $tc = $val;
                }
            }
            // HDL
            elseif (str_contains($name, 'hdl') || (str_contains($name, 'high') && str_contains($name, 'density')) || str_contains($name, 'عالي الكثافة') || str_contains($name, 'عالية الكثافة') || str_contains($name, 'نافع')) {
                if ($val !== null && is_numeric($val)) {
                    $hdl = $val;
                }
            }
            // VLDL
            elseif (str_contains($name, 'vldl') || (str_contains($name, 'very') && str_contains($name, 'low')) || str_contains($name, 'منخفض الكثافة جدا') || str_contains($name, 'منخفضة الكثافة جدا')) {
                $vldlKey = $key;
            }
            // LDL
            elseif ((str_contains($name, 'ldl') || (str_contains($name, 'low') && str_contains($name, 'density')) || str_contains($name, 'منخفض الكثافة') || str_contains($name, 'منخفضة الكثافة') || str_contains($name, 'ضار'))
                    && ! str_contains($name, 'vldl') && ! str_contains($name, 'very') && ! str_contains($name, 'جدا')) {
                $ldlKey = $key;
            }
        }

        // 1. حساب VLDL = TG / 5
        if ($vldlKey !== null && $tg !== null && $tg >= 0) {
            if (! isset($testResults[$vldlKey]['value']) || trim((string) $testResults[$vldlKey]['value']) === '') {
                $testResults[$vldlKey]['value'] = (string) round($tg / 5, 1);
                $testResults[$vldlKey]['notes'] = trim(($testResults[$vldlKey]['notes'] ?? '').' [محسوب آلياً: TG / 5]');
            }
        }

        // 2. حساب LDL = TC - HDL - (TG / 5) بشرط TG < 400
        if ($ldlKey !== null && $tc !== null && $hdl !== null && $tg !== null && $tc > 0 && $hdl > 0 && $tg >= 0) {
            if (! isset($testResults[$ldlKey]['value']) || trim((string) $testResults[$ldlKey]['value']) === '') {
                if ($tg < 400) {
                    $calcLdl = round($tc - $hdl - ($tg / 5), 1);
                    $testResults[$ldlKey]['value'] = (string) $calcLdl;
                    $testResults[$ldlKey]['notes'] = trim(($testResults[$ldlKey]['notes'] ?? '').' [محسوب آلياً: Friedewald Eq]');
                } else {
                    $testResults[$ldlKey]['notes'] = trim(($testResults[$ldlKey]['notes'] ?? '').' [⚠️ تنبيه سريري: TG >= 400 mg/dL - معادلة فريدوالد غير دقيقة]');
                }
            }
        }
    }
}
