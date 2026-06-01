<?php


namespace App\Http\Controllers;

use App\Events\SurgeryLabTestUpdated;
use App\Models\Request as MedicalRequest;
use App\Models\LabResult;
use App\Models\LabTest;
use App\Models\Surgery;
use App\Models\SurgeryLabTest;
use App\Models\SurgeryRadiologyTest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class StaffRequestController extends Controller
{
    /**
     * تحديد حالة نتيجة التحليل (طبيعي، مرتفع، منخفض)
     */
    private function determineResultStatus($value, $testName): string
    {
        if (!$value) {
            return 'normal';
        }

        $testName = strtolower($testName);
        $value = floatval($value);

        if (strpos($testName, 'سكر') !== false || strpos($testName, 'glucose') !== false) {
            if ($value < 70) return 'low';
            if ($value > 140) return 'high';
        }
        elseif (strpos($testName, 'ضغط') !== false || strpos($testName, 'pressure') !== false) {
            if ($value > 140) return 'high';
        }
        elseif (strpos($testName, 'كوليسترول') !== false || strpos($testName, 'cholesterol') !== false) {
            if ($value > 200) return 'high';
        }

        return 'normal';
    }
    public function index($type = null)
    {
        $user = Auth::user();

        // إعادة التوجيه للصفحات الجديدة المخصصة
        if ($user->hasRole('lab_staff') && !$user->hasRole('admin')) {
            return redirect()->route('lab.index');
        }
        if ($user->hasAnyRole(['radiology_staff', 'radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general']) && !$user->hasRole('admin')) {
            return redirect()->route('radiology-staff.index');
        }

        // تحديد نوع الطلبات حسب دور المستخدم
        $allowedTypes = [];
        if ($user->hasRole('lab_staff')) {
            $allowedTypes[] = 'lab';
            $allowedTypes[] = 'blood_bank';
        }
        if ($user->hasRole('radiology_staff') || $user->hasAnyRole(['radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general'])) {
            $allowedTypes[] = 'radiology';
        }
        if ($user->hasRole('pharmacy_staff')) {
            $allowedTypes[] = 'pharmacy';
        }
        if ($user->hasRole('emergency_staff')) {
            $allowedTypes[] = 'nursing';
        }

        // السماح للموظفين الآخرين برؤية جميع الأنواع بما في ذلك مصرف الدم والتمريض
        if ($user->hasRole(['receptionist', 'admin', 'doctor'])) {
            $allowedTypes = ['lab', 'radiology', 'pharmacy', 'blood_bank', 'nursing'];
        }

        if (empty($allowedTypes)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // فلترة الطلبات حسب النوع المسموح (تشمل مصرف الدم القديم في حالة lab.details.blood_bank)
        $query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where(function($q) use ($allowedTypes) {
                $q->whereIn('type', $allowedTypes);

                if (in_array('lab', $allowedTypes)) {
                    $q->orWhere(function($inner) {
                        $inner->where('type', 'lab')
                            ->whereJsonContains('details->blood_bank', true);
                    });
                }
            })
            ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed']);

        // فلترة حسب النوع المحدد في URL
        if ($type && in_array($type, $allowedTypes)) {
            if ($type === 'lab') {
                // لعرض تحاليل + طلبات مصرف الدم ضمن نفس تبويب المختبر عند اختيار lab
                $query->where(function($q) {
                    $q->whereIn('type', ['lab', 'blood_bank'])
                      ->orWhere(function($inner) {
                          $inner->where('type', 'lab')
                                ->whereJsonContains('details->blood_bank', true);
                      });
                });
            } elseif ($type === 'blood_bank') {
                // عرض طلبات مصرف الدم المخصصة
                $query->where('type', 'blood_bank');
            } else {
                $query->where('type', $type);
            }
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        // إضافة طلبات الطوارئ إذا كان المستخدم مختبراً أو موظف أشعة أو أدمن
        $emergencyLabRequests = collect();
        $emergencyRadiologyRequests = collect();

        if ($user->hasRole('lab_staff') || $user->hasAnyRole(['admin'])) {
            // show pending, in progress, and completed so that rows remain after completion
            $emergencyLabRequests = \App\Models\EmergencyLabRequest::with(['emergency', 'patient.user', 'labTests'])
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->orderByRaw("FIELD(priority, 'critical', 'urgent')")
                ->orderBy('requested_at', 'asc')
                ->get();
        }

        if ($user->hasRole('radiology_staff') || $user->hasAnyRole(['radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general', 'admin'])) {
            $emergencyRadiologyRequests = \App\Models\EmergencyRadiologyRequest::with(['emergency', 'patient.user', 'radiologyTypes'])
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->orderByRaw("FIELD(priority, 'critical', 'urgent')")
                ->orderBy('requested_at', 'asc')
                ->get();
        }

        return view('staff.requests.index', compact('requests', 'allowedTypes', 'type', 'emergencyLabRequests', 'emergencyRadiologyRequests'));
    }

    /**
     * طباعة نتائج طلب تحاليل من الطوارئ
     */
    public function printEmergencyLab(\App\Models\EmergencyLabRequest $emergencyLab)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }
        $emergencyLab->load(['patient.user', 'labTests']);
        return view('staff.requests.emergency-lab-print', compact('emergencyLab'));
    }

    public function show(MedicalRequest $request)
    {
        $user = auth()->user();

        // التحقق من صلاحية المستخدم لعرض هذا النوع من الطلبات
        $allowedTypes = [];
        if ($user->hasRole('lab_staff')) {
            $allowedTypes[] = 'lab';
            $allowedTypes[] = 'blood_bank';
        }
        if ($user->hasRole('radiology_staff') || $user->hasAnyRole(['radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general'])) {
            $allowedTypes[] = 'radiology';
        }
        if ($user->hasRole('pharmacy_staff')) {
            $allowedTypes[] = 'pharmacy';
        }
        if ($user->hasRole('emergency_staff')) {
            $allowedTypes[] = 'nursing';
        }
        
        // admin يستطيع رؤية كل شيء
        if ($user->hasRole(['admin', 'receptionist', 'doctor'])) {
            $allowedTypes = ['lab', 'radiology', 'pharmacy', 'blood_bank', 'nursing'];
        }

        if (!in_array($request->type, $allowedTypes)) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $bloodBankRequest = null;
        // شرط واضح: فقط عندما type= blood_bank نعتبر الطلب مصارف دم
        $isBloodBankRequest = $request->type === 'blood_bank';

        if ($isBloodBankRequest) {
            $bloodBankRequest = \App\Models\BloodBankRequest::where('request_id', $request->id)->first();
        }

        // استخراج النتائج المحفوظة سابقاً
        $savedTestResults = [];
        $savedNotes = '';
        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (is_array($resultData)) {
                $savedTestResults = $resultData['test_results'] ?? [];
                $savedNotes = $resultData['notes'] ?? '';
            }
        }

        if (in_array($request->type, ['lab', 'blood_bank'])) {
            return view('staff.requests.show-lab', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
        }

        if ($request->type === 'radiology') {
            return view('staff.requests.show-radiology', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
        }

        return view('staff.requests.show', compact('request', 'savedTestResults', 'savedNotes', 'bloodBankRequest'));
    }

    public function update(HttpRequest $httpRequest, MedicalRequest $request)
    {
        Log::info('دخول دالة update', [
            'request_id' => $request->id,
            'request_type' => $request->type,
            'visit_id' => $request->visit_id,
            'has_tests' => $httpRequest->has('tests'),
            'has_test_results' => $httpRequest->has('test_results')
        ]);

        // تحميل النموذج مع العلاقات
        $request->load(['visit']);

        // 1. حفظ اختيار الخدمات (للطلبات pending_service_selection)
        if ($request->status === 'pending_service_selection' && ($httpRequest->has('lab_test_ids') || $httpRequest->has('package_id'))) {
            $details = $request->details ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }
            if (!is_array($details)) {
                $details = [];
            }
            // دعم اختيار باقة: إذا استلمنا package_id نترجمها إلى lab_test_ids
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
            
            // تحديث حالة الزيارة
            $request->visit->status = 'pending_payment';
            $request->visit->save();

            // تسجيل استخدام التحاليل المختارة
            if (!empty($details['lab_test_ids'])) {
                foreach ($details['lab_test_ids'] as $labTestId) {
                    \App\Models\UserLabTestStat::recordUsage(auth()->id(), (int) $labTestId);
                }
            }

            Log::info('تم تحديد الخدمات المطلوبة (مختبر)', [
                'request_id' => $request->id,
                'lab_test_ids' => $httpRequest->lab_test_ids,
                'selected_by' => auth()->user()->name
            ]);

            return redirect()
                ->route('staff.requests.index', ['type' => $request->type])
                ->with('success', 'تم تحديد التحاليل المطلوبة بنجاح. الطلب الآن بانتظار الدفع عند الكاشير.');
        }

        // حفظ بيانات مصرف الدم
        $isBloodBankRequest = $request->type === 'blood_bank';
        if (!$isBloodBankRequest) {
            $details = $request->details;
            if (is_string($details)) {
                $details = json_decode($details, true) ?: [];
            }
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

            $bloodBankDetails = [
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
            ];

            $visit = $request->visit;
            $bloodBankRequest = \App\Models\BloodBankRequest::updateOrCreate(
                ['request_id' => $request->id],
                array_merge($bloodBankDetails, [
                    'visit_id' => $request->visit_id ?? ($visit?->id),
                    'patient_id' => $request->patient_id ?? ($visit?->patient_id),
                    'department_id' => $request->department_id ?? ($visit?->department_id),
                    'doctor_id' => $request->doctor_id ?? ($visit?->doctor_id),
                    'status' => $httpRequest->status ?? ($request->status === 'pending' ? 'in_progress' : $request->status),
                ])
            );

            if ($request->status === 'pending' || $request->status === 'pending_service_selection') {
                $request->status = 'in_progress';
            } else {
                $request->status = $httpRequest->status ?? $request->status;
            }

            $request->payment_status = 'pending';
            $request->save();

            return redirect()->route('staff.requests.show', $request)
                        ->with('success', 'تم حفظ بيانات مصرف الدم بنجاح');
        }

        // حفظ اختيار خدمات الأشعة (للطلبات pending_service_selection)
        if ($request->status === 'pending_service_selection' && $httpRequest->has('radiology_type_ids')) {
            $details = $request->details ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }
            if (!is_array($details)) {
                $details = [];
            }
            
            $details['radiology_type_ids'] = $httpRequest->radiology_type_ids;
            $details['services_selected'] = true;
            $details['services_selected_at'] = now()->toDateTimeString();
            $details['services_selected_by'] = auth()->id();
            
            $request->details = $details;
            $request->status = 'pending';
            $request->payment_status = 'pending';
            $request->save();
            
            // تحديث حالة الزيارة
            $request->visit->status = 'pending_payment';
            $request->visit->save();

            Log::info('تم تحديد الخدمات المطلوبة (أشعة)', [
                'request_id' => $request->id,
                'radiology_type_ids' => $httpRequest->radiology_type_ids,
                'selected_by' => auth()->user()->name
            ]);

            return redirect()
                ->route('staff.requests.index', ['type' => $request->type])
                ->with('success', 'تم تحديد أنواع الأشعة المطلوبة بنجاح. الطلب الآن بانتظار الدفع عند الكاشير.');
        }

        // 2. حفظ التحاليل المختارة (من المودال - للطلبات القديمة)
        if ($httpRequest->has('tests') && is_array($httpRequest->tests)) {
            // تحويل details إلى array إذا كان string
            $details = $request->details ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }
            if (!is_array($details)) {
                $details = [];
            }
            
            $details['tests'] = $httpRequest->tests;
            $request->details = $details;
            $request->status = 'in_progress';
            $request->save();

            Log::info('تم حفظ التحاليل المختارة', [
                'request_id' => $request->id,
                'tests_count' => count($httpRequest->tests),
                'tests' => $httpRequest->tests
            ]);

            return redirect()
                ->route('staff.requests.show', $request)
                ->with('success', 'تم حفظ التحاليل المختارة بنجاح. يمكنك الآن إدخال النتائج.');
        }

        // 2. حفظ نتائج التحاليل (في جدول lab_results)
        if ($httpRequest->has('test_results') && is_array($httpRequest->test_results)) {
            $savedResults = 0;
            $errors = [];
            
            Log::info('بدء حفظ نتائج التحاليل', [
                'request_id' => $request->id,
                'test_results_count' => count($httpRequest->test_results),
                'visit_id' => $request->visit_id
            ]);

            // حذف النتائج السابقة لهذا الطلب
            LabResult::where('request_id', $request->id)->delete();

            $details = $request->details ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }
            if (!is_array($details)) {
                $details = [];
            }

            $sourceType = 'general';
            $packageId = null;
            if (!empty($details['package_id'])) {
                $sourceType = 'package';
                $packageId = $details['package_id'];
            }

            foreach ($httpRequest->test_results as $testName => $data) {
                if (!empty($data['value'])) {
                    try {
                        $labTestId = null;
                        if (isset($details['lab_test_ids']) && is_array($details['lab_test_ids'])) {
                            // ربما لريب، إذا كانت values بإندكس رقمية، لا يوجد رابط مباشر بالاسم؛ يمكن تحسين لاحقاً
                            if (isset($details['lab_test_ids'][$testName])) {
                                $labTestId = $details['lab_test_ids'][$testName];
                            }
                        }

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
                            'lab_test_id' => $labTestId,
                        ]);

                        Log::info("تم حفظ نتيجة التحليل: {$testName}", [
                            'lab_result_id' => $labResult->id
                        ]);

                        $savedResults++;
                    } catch (\Exception $e) {
                        Log::error("خطأ في حفظ نتيجة التحليل: {$testName}", [
                            'error' => $e->getMessage()
                        ]);
                        $errors[] = "خطأ في حفظ {$testName}: " . $e->getMessage();
                    }
                }
            }

            // تحديث حالة الطلب
            $request->status = 'completed';
            $request->result = json_encode([
                'test_results' => $httpRequest->test_results,
                'notes' => $httpRequest->result_notes ?? ''
            ]);
            $request->save();

            // إعادة تحميل العلاقة للحصول على آخر البيانات
            $request->refresh();
            $request->load('visit');

            // تحديث حالة الزيارة إلى completed إذا كانت جميع الطلبات مكتملة
            if ($request->visit) {
                $allRequestsCompleted = $request->visit->requests()
                    ->where('id', '!=', $request->id)
                    ->where('status', '!=', 'completed')
                    ->count() === 0;
                
                if ($allRequestsCompleted) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                    Log::info('تم تحديث حالة الزيارة إلى completed', [
                        'visit_id' => $request->visit->id
                    ]);
                } else {
                    Log::info('لا تزال هناك طلبات غير مكتملة', [
                        'visit_id' => $request->visit->id,
                        'pending_requests' => $request->visit->requests()->where('status', '!=', 'completed')->pluck('id')->toArray()
                    ]);
                }
            }

            $message = "تم حفظ {$savedResults} نتيجة تحليل بنجاح في قاعدة البيانات";
            if (!empty($errors)) {
                $message .= ' مع بعض الأخطاء';
            }

            return redirect()
                ->route('staff.requests.show', $request)
                ->with('success', $message)
                ->with('lab_results_errors', $errors);
        }

        // 3. تحديث حالة الطلب فقط
        $updateData = [
            'status' => $httpRequest->status ?? 'completed',
        ];

        Log::info('بيانات التحديث المعدة', [
            'update_data' => $updateData,
            'http_request_status' => $httpRequest->status ?? 'null',
            'current_request_status' => $request->status ?? 'null'
        ]);

        // التحقق من البيانات المرسلة بشكل مفصل

    
// ...existing code...

        $request->update($updateData);

        // إضافة لوج للتحقق من تحديث الطلب
        Log::info('بعد تحديث الطلب', [
            'updated_request_id' => $request->id,
            'updated_status' => $request->status ?? 'null',
            'updated_type' => $request->type ?? 'null',
            'updated_result' => $request->result ?? 'null',
            'update_data_used' => $updateData,
            'update_result' => $request->wasChanged() ? 'changed' : 'not changed',
            'changed_fields' => $request->getChanges()
        ]);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    // إنشاء زيارة مختبرية مباشرة
    public function createLabVisit()
    {
        $user = Auth::user();

        // تسجيل دور المستخدم للتشخيص
        \Log::info('محاولة الوصول لإنشاء زيارة مختبرية', [
            'user_id' => $user->id,
            'user_role' => $user->role,
            'user_name' => $user->name
        ]);

        // التحقق من الصلاحية (موظف استقبال أو أدمن أو موظف مختبر أو أي موظف)
        if (!$user->hasRole(['admin', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'doctor'])) {
            \Log::warning('تم رفض الوصول لإنشاء زيارة مختبرية', [
                'user_id' => $user->id,
                'roles' => $user->roles->pluck('name')->toArray()
            ]);
            abort(403, 'غير مصرح لك بإنشاء زيارات مختبرية');
        }

        $patients = \App\Models\Patient::with('user')->whereHas('user')->get();
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('staff.lab-visits.create', compact('patients', 'departments'));
    }

    public function storeLabVisit(HttpRequest $request)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'receptionist', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بإنشاء زيارات مختبرية');
        }

        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'department_id' => 'required|exists:departments,id',
            'visit_date' => 'required|date|after_or_equal:today',
            'visit_time' => 'required|date_format:H:i',
            'chief_complaint' => 'required|string|max:500',
        ]);

        // إنشاء الزيارة المخبرية
        $visit = \App\Models\Visit::create([
            'patient_id' => $request->patient_id,
            'department_id' => $request->department_id,
            'visit_date' => $request->visit_date,
            'visit_time' => $request->visit_time,
            'visit_type' => 'lab',
            'chief_complaint' => $request->chief_complaint,
            'status' => 'in_progress',
            'notes' => 'زيارة مختبرية مباشرة - ' . now()->format('Y-m-d H:i:s')
        ]);

        // إنشاء طلب مختبر فارغ (سيتم تحديد التحاليل في المختبر)
        $labRequest = \App\Models\Request::create([
            'visit_id' => $visit->id,
            'type' => 'lab',
            'description' => 'طلب تحاليل مختبرية - سيتم تحديد التحاليل في المختبر',
            'status' => 'pending',
            'details' => [
                'direct_lab_visit' => true,
                'tests_to_be_selected' => true,
                'priority' => 'normal'
            ]
        ]);

        return redirect()->route('doctor.visits.index')
            ->with('success', 'تم إنشاء الزيارة المخبرية بنجاح. سيتم تحديد التحاليل في المختبر.');
    }

    /**
     * إضافة تحاليل إضافية — ينشئ طلب مختبر جديد على نفس الزيارة
     */
    public function appendLabTests(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'receptionist', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بإضافة تحاليل');
        }

        $httpRequest->validate([
            'extra_lab_test_ids'   => 'required|array|min:1',
            'extra_lab_test_ids.*' => 'integer|exists:lab_tests,id',
        ]);

        $extraTestIds = array_map('intval', $httpRequest->extra_lab_test_ids);

        $details = $request->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }
        if (!is_array($details)) {
            $details = [];
        }

        // إذا الطلب الحالي لم يدفع بعد، أضف التحاليل داخل نفس الطلب
        if ($request->payment_status !== 'paid') {
            $existingIds = array_map('intval', $details['lab_test_ids'] ?? []);
            $details['lab_test_ids'] = array_values(array_unique(array_merge($existingIds, $extraTestIds)));
            $details['services_selected'] = true;
            $details['services_selected_at'] = now()->toDateTimeString();
            $details['services_selected_by'] = $user->id;

            $request->details = $details;
            $request->status = 'pending';
            $request->payment_status = 'pending';
            $request->save();

            if ($request->visit) {
                $request->visit->status = 'pending_payment';
                $request->visit->save();
            }

            Log::info('تم إضافة تحاليل إضافية إلى الطلب الحالي', [
                'request_id' => $request->id,
                'added_tests' => $extraTestIds,
                'by_user' => $user->id,
            ]);

            return redirect()->route('lab.show', $request)
                ->with('success', 'تمت إضافة التحاليل إلى الطلب الحالي بنجاح.');
        }

        $newRequest = \App\Models\Request::create([
            'visit_id'       => $request->visit_id,
            'type'           => 'lab',
            'description'    => 'تحاليل إضافية - مضافة من طلب #' . $request->id,
            'status'         => 'pending',
            'payment_status' => 'pending',
            'details'        => [
                'lab_test_ids'      => $extraTestIds,
                'services_selected' => true,
                'added_from_request'=> $request->id,
                'added_by'          => $user->id,
                'added_at'          => now()->toDateTimeString(),
            ],
        ]);

        if ($request->visit) {
            $request->visit->status = 'pending_payment';
            $request->visit->save();
        }

        Log::info('تم إنشاء طلب تحاليل إضافية', [
            'original_request_id' => $request->id,
            'new_request_id'      => $newRequest->id,
            'added_tests'         => $extraTestIds,
            'by_user'             => $user->id,
        ]);

        return redirect()->route('lab.show', $newRequest)
            ->with('success', 'تم إنشاء طلب تحاليل جديد بنجاح. يجب الدفع عند الكاشير أولاً.');
    }

    /**
     * حذف تحليل من الطلب (قبل الدفع فقط)
     */
    public function removeLabTest(MedicalRequest $request, LabTest $labTest)
    {
        $user = Auth::user();

        // التحقق من الصلاحيات
        if (!$user->hasAnyRole(['admin', 'receptionist', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بحذف التحاليل');
        }

        // التحقق من أن الطلب لم يتم الدفع بعد
        if ($request->payment_status == 'paid') {
            return redirect()->back()->with('error', 'لا يمكن حذف تحليل من طلب مدفوع');
        }

        // التحقق من أن الطلب من نوع lab
        if ($request->type != 'lab') {
            return redirect()->back()->with('error', 'هذا الطلب ليس طلب تحاليل');
        }

        // الحصول على التفاصيل
        $details = $request->details;
        if (is_string($details)) {
            $details = json_decode($details, true) ?? [];
        }
        if (!is_array($details)) {
            $details = [];
        }

        // حذف التحليل من القائمة
        if (isset($details['lab_test_ids']) && is_array($details['lab_test_ids'])) {
            $details['lab_test_ids'] = array_values(
                array_filter($details['lab_test_ids'], fn($id) => $id != $labTest->id)
            );

            if (empty($details['lab_test_ids'])) {
                $request->details = $details;
                $request->status = 'pending_service_selection';
                $request->payment_status = 'pending';
                if ($request->visit) {
                    $request->visit->status = 'scheduled';
                    $request->visit->save();
                }
                $request->save();

                Log::info('تم حذف آخر تحليل من الطلب وأعيد إلى اختيار التحاليل', [
                    'request_id' => $request->id,
                    'lab_test_id' => $labTest->id,
                    'lab_test_name' => $labTest->name,
                    'by_user' => $user->id,
                ]);

                return redirect()->back()->with('success', "تم حذف التحليل الأخير ({$labTest->name})، الطلب الآن بانتظار اختيار التحاليل.");
            }

            $request->details = $details;
            $request->save();

            Log::info('تم حذف تحليل من الطلب', [
                'request_id' => $request->id,
                'lab_test_id' => $labTest->id,
                'lab_test_name' => $labTest->name,
                'by_user' => $user->id,
            ]);

            return redirect()->back()->with('success', "تم حذف التحليل ({$labTest->name}) بنجاح");
        }

        return redirect()->back()->with('error', 'لم يتم العثور على التحليل في الطلب');
    }

    /**
     * طباعة نتائج التحاليل
     */
    public function print(MedicalRequest $request)
    {
        // تحميل العلاقات
        $request->load(['visit.patient.user', 'visit.doctor.user']);

        $requestDetails = $request->details;
        if (is_string($requestDetails)) {
            $decoded = json_decode($requestDetails, true);
            $requestDetails = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($requestDetails)) {
            $requestDetails = [];
        }

        $isBloodBankRequest = $request->type === 'blood_bank' || data_get($requestDetails, 'blood_bank', false) === true;
        $bloodBankRequest = null;

        if ($isBloodBankRequest) {
            $bloodBankRequest = \App\Models\BloodBankRequest::where('request_id', $request->id)->first();
        }

        return view('staff.requests.print', compact('request', 'isBloodBankRequest', 'bloodBankRequest'));
    }

    /**
     * عرض طلبات المختبر للعمليات
     */
    public function surgeryLabTests(HttpRequest $request)
    {
        $user = Auth::user();

        // التحقق من الصلاحية
        if (!$user->hasAnyRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بالوصول إلى طلبات المختبر للعمليات');
        }

        // منع الأطباء الاستشاريين من الوصول إلا إذا كانوا موظفي مختبر أيضاً
        if (!$user->hasRole('lab_staff') && $user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بالوصول إلى تحاليل العمليات الجراحية');
        }

        $query = \App\Models\SurgeryLabTest::with(['surgery.patient.user', 'surgery.doctor.user', 'labTest'])
            ->whereHas('surgery'); // التأكد من وجود عملية صالحة

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('surgery.patient.user', function($patientQuery) use ($search) {
                    $patientQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('surgery', function($surgeryQuery) use ($search) {
                    $surgeryQuery->where('surgery_type', 'like', '%' . $search . '%');
                })
                ->orWhereHas('labTest', function($labTestQuery) use ($search) {
                    $labTestQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('category', 'like', '%' . $search . '%');
                });
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $labTests = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());

        return view('staff.surgery-lab-tests.index', compact('labTests'));
    }

    /**
     * عرض قائمة العمليات التي تحتاج اختيار تحاليل
     */
    public function surgeryLabTestsSelection(HttpRequest $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بعرض ذلك');
        }

        if (!$user->hasRole('lab_staff') && $user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بعرض هذه القائمة');
        }

        $query = Surgery::with(['patient.user', 'doctor.user'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereDoesntHave('labTests', function ($q) {
                $q->whereNotNull('lab_test_id');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('patient.user', function($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        $surgeries = $query->orderBy('scheduled_date', 'desc')->paginate(20)->appends($request->query());

        return view('staff.surgery-lab-tests.selection', compact('surgeries'));
    }

    /**
     * إنشاء طلب عام لاختيار تحاليل لعملية
     */
    public function createSurgeryLabTestSelection(Surgery $surgery)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بهذه العملية');
        }

        if (!$user->hasRole('lab_staff') && $user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بهذه العملية');
        }

        $test = $surgery->labTests()->whereNull('lab_test_id')->first();
        if (!$test) {
            $test = $surgery->labTests()->create([
                'lab_test_id' => null,
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);
        }

        return redirect()->route('staff.surgery-lab-tests.show', $test);
    }

    /**
     * عرض قائمة العمليات التي تحتاج اختيار أشعة
     */
    public function surgeryRadiologyTestsSelection(HttpRequest $request)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بعرض ذلك');
        }

        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بعرض هذه القائمة');
        }

        $query = Surgery::with(['patient.user', 'doctor.user'])
            ->whereIn('status', ['scheduled', 'waiting', 'in_progress'])
            ->whereDoesntHave('radiologyTests', function ($q) {
                $q->whereNotNull('radiology_type_id');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('patient.user', function($patientQuery) use ($search) {
                        $patientQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('surgery_type', 'like', '%' . $search . '%');
            });
        }

        $surgeries = $query->orderBy('scheduled_date', 'desc')->paginate(20)->appends($request->query());

        return view('staff.surgery-radiology-tests.selection', compact('surgeries'));
    }

    /**
     * إنشاء طلب عام لاختيار نوع أشعة لعملية
     */
    public function createSurgeryRadiologyTestSelection(Surgery $surgery)
    {
        $user = Auth::user();
        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بهذه العملية');
        }

        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بهذه العملية');
        }

        $test = $surgery->radiologyTests()->whereNull('radiology_type_id')->first();
        if (!$test) {
            $test = $surgery->radiologyTests()->create([
                'radiology_type_id' => null,
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);
        }

        return redirect()->route('staff.surgery-radiology-tests.show', $test);
    }

    /**
     * عرض تفاصيل طلب مختبر لعملية
     */
    public function showSurgeryLabTest(\App\Models\SurgeryLabTest $test)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        // منع الأطباء الاستشاريين من العرض
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بعرض تحاليل العمليات الجراحية');
        }

        $test->load(['surgery.patient.user', 'surgery.doctor.user', 'labTest']);

        return view('staff.surgery-lab-tests.show', compact('test'));
    }

    /**
     * تحديث طلب مختبر لعملية
     */
    public function updateSurgeryLabTest(HttpRequest $request, \App\Models\SurgeryLabTest $test)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
        }

        // منع الأطباء الاستشاريين من التحديث
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتحديث تحاليل العمليات الجراحية');
        }

        // التحقق من دفع رسوم العملية أولاً
        $test->load('surgery');
        if ($test->surgery && $test->surgery->surgery_fee_paid !== 'paid') {
            return redirect()->back()->with('error', 'لا يمكن إجراء التحليل قبل دفع رسوم العملية الجراحية');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'result' => 'nullable|string',
            'result_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        // حفظ الملف إذا تم رفعه
        if ($request->hasFile('result_file')) {
            $fileName = time() . '_' . $request->file('result_file')->getClientOriginalName();
            $filePath = $request->file('result_file')->storeAs('surgery_lab_results', $fileName, 'public');
            $validated['result_file'] = $filePath;
        }

        // تحديث تاريخ الإكمال إذا تم إكمال الطلب
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $test->update($validated);

        // إرسال حدث التحديث في الوقت الفعلي
        broadcast(new SurgeryLabTestUpdated($test))->toOthers();

        return redirect()->back()->with('success', 'تم تحديث الطلب بنجاح');
    }

    /**
     * تحديد التحاليل للطلب العام
     */
    public function selectTestsForSurgeryLabTest(HttpRequest $request, \App\Models\SurgeryLabTest $test)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
        }

        // التحقق من أن هذا طلب عام (lab_test_id = null)
        if ($test->lab_test_id !== null) {
            return redirect()->back()->with('error', 'هذا الطلب محدد مسبقاً ولا يمكن تغيير تحاليله');
        }

        $validated = $request->validate([
            'lab_test_ids' => 'required|array|min:1',
            'lab_test_ids.*' => 'exists:lab_tests,id',
        ]);

        $labTestIds = $validated['lab_test_ids'];
        
        // حذف الطلب العام
        $surgery = $test->surgery;
        $test->delete();

        // إنشاء طلبات جديدة لكل تحليل
        foreach ($labTestIds as $labTestId) {
            $surgery->labTests()->create([
                'lab_test_id' => $labTestId,
                'status' => 'pending',
                'payment_status' => 'pending'
            ]);
        }

        return redirect()->route('staff.surgery-lab-tests.index')
            ->with('success', 'تم اختيار التحاليل بنجاح. عدد التحاليل: ' . count($labTestIds));
    }

    /**
     * تحديث جميع نتائج تحاليل العملية
     */
    public function updateAllSurgeryLabTests(HttpRequest $request, \App\Models\Surgery $surgery)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بتحديث هذه التحاليل');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,completed',
            'test_results' => 'nullable|array',
            'test_results.*.value' => 'nullable|string',
            'test_results.*.unit' => 'nullable|string',
            'test_results.*.test_id' => 'required|exists:surgery_lab_tests,id',
            'notes' => 'nullable|string',
        ]);

        $testResults = $validated['test_results'] ?? [];
        $status = $validated['status'];
        $completedCount = 0;

        // تحديث كل تحليل
        foreach ($testResults as $testId => $resultData) {
            $surgeryTest = \App\Models\SurgeryLabTest::find($resultData['test_id']);
            if ($surgeryTest && $surgeryTest->surgery_id === $surgery->id) {
                $surgeryTest->update([
                    'result' => $resultData['value'] ?? null,
                    'status' => !empty($resultData['value']) ? 'completed' : 'pending',
                    'completed_at' => !empty($resultData['value']) ? now() : null,
                ]);
                
                if (!empty($resultData['value'])) {
                    $completedCount++;
                }
            }
        }

        // حفظ الملاحظات على مستوى العملية (إذا كان هناك حقل في جدول surgeries)
        // يمكنك إضافة حقل lab_notes في جدول surgeries إذا أردت
        
        return redirect()->back()->with('success', "تم حفظ النتائج بنجاح. عدد التحاليل المكتملة: {$completedCount} من {$surgery->labTests()->count()}");
    }

    /**
     * طباعة نتائج تحاليل العملية
     */
    public function printSurgeryLabTest(\App\Models\SurgeryLabTest $test)
    {
        $user = Auth::user();

        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'lab_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بطباعة هذا الطلب');
        }

        // التحقق من وجود تحاليل مكتملة للعملية
        $completedTests = \App\Models\SurgeryLabTest::where('surgery_id', $test->surgery_id)
            ->where('status', 'completed')
            ->count();

        if ($completedTests === 0) {
            abort(403, 'لا توجد تحاليل مكتملة لهذه العملية');
        }

        // جلب جميع التحاليل المتعلقة بنفس العملية والمكتملة
        $surgeryLabTests = \App\Models\SurgeryLabTest::with(['surgery.patient.user', 'surgery.doctor.user', 'labTest'])
            ->where('surgery_id', $test->surgery_id)
            ->where('status', 'completed')
            ->orderBy('created_at')
            ->get();

        return view('staff.surgery-lab-tests.print', compact('surgeryLabTests', 'test'));
    }

    /**
     * عرض طلبات الأشعة للعمليات
     */
    public function surgeryRadiologyTests(HttpRequest $request)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بالوصول إلى طلبات الأشعة للعمليات');
        }

        // منع الأطباء الاستشاريين من الوصول
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بالوصول إلى أشعة العمليات الجراحية');
        }

        $query = \App\Models\SurgeryRadiologyTest::with(['surgery.patient.user', 'surgery.doctor.user', 'radiologyType'])
            ->whereHas('surgery'); // التأكد من وجود عملية صالحة

        // البحث
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('surgery.patient.user', function($patientQuery) use ($search) {
                    $patientQuery->where('name', 'like', '%' . $search . '%');
                })
                ->orWhereHas('surgery', function($surgeryQuery) use ($search) {
                    $surgeryQuery->where('surgery_type', 'like', '%' . $search . '%');
                })
                ->orWhereHas('radiologyType', function($radiologyTypeQuery) use ($search) {
                    $radiologyTypeQuery->where('name', 'like', '%' . $search . '%')
                                      ->orWhere('category', 'like', '%' . $search . '%');
                });
            });
        }

        // فلترة حسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب التاريخ
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $radiologyTests = $query->orderBy('created_at', 'desc')->paginate(20)->appends($request->query());

        return view('staff.surgery-radiology-tests.index', compact('radiologyTests'));
    }

    /**
     * عرض تفاصيل طلب أشعة لعملية
     */
    public function showSurgeryRadiologyTest(\App\Models\SurgeryRadiologyTest $test)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        // منع الأطباء الاستشاريين من الوصول
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بعرض أشعة العمليات الجراحية');
        }

        $test->load(['surgery.patient.user', 'surgery.doctor.user', 'radiologyType']);

        return view('staff.surgery-radiology-tests.show', compact('test'));
    }

    /**
     * طباعة طلب أشعة لعملية
     */
    public function printSurgeryRadiologyTest(\Illuminate\Http\Request $request, \App\Models\SurgeryRadiologyTest $test)
    {
        $user = Auth::user();

        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بطباعة هذا الطلب');
        }

        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بطباعة أشعة العمليات الجراحية');
        }

        if ($test->status !== 'completed') {
            abort(403, 'لا يمكن طباعة طلب غير مكتمل');
        }

        $test->load(['surgery.patient.user', 'surgery.doctor.user', 'radiologyType']);

        return view('staff.surgery-radiology-tests.print', compact('test'));
    }

    /**
     * تحديث طلب أشعة لعملية
     */
    public function updateSurgeryRadiologyTest(HttpRequest $request, \App\Models\SurgeryRadiologyTest $test)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if (!$user->hasRole(['admin', 'radiology_staff', 'doctor'])) {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
        }

        // منع الأطباء الاستشاريين من التحديث
        if ($user->hasRole('doctor') && $user->doctor && $user->doctor->type === 'consultant') {
            abort(403, 'الأطباء الاستشاريين غير مصرح لهم بتحديث أشعة العمليات الجراحية');
        }

        // التحقق من البيانات
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'radiology_type_id' => 'nullable|exists:radiology_types,id',
            'result' => 'nullable|string',
            'result_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,dcm,dicom|max:10240',
        ]);

        // حفظ الملف إذا تم رفعه
        if ($request->hasFile('result_file')) {
            $fileName = time() . '_' . $request->file('result_file')->getClientOriginalName();
            $filePath = $request->file('result_file')->storeAs('surgery_radiology_results', $fileName, 'public');
            $validated['result_file'] = $filePath;
        }

        // تحديث تاريخ الإكمال إذا تم إكمال الطلب
        if ($validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }

        $test->update($validated);

        // رسالة نجاح مخصصة بناءً على نوع العملية
        $successMessage = 'تم تحديث الطلب بنجاح';
        if ($request->filled('radiology_type_id') && !$test->wasChanged('result')) {
            $successMessage = 'تم اختيار نوع الأشعة بنجاح';
        } elseif ($request->filled('result') || $request->hasFile('result_file')) {
            $successMessage = 'تم حفظ نتائج التصوير بنجاح';
        }

        return redirect()->back()->with('success', $successMessage);
    }

    /**
     * عرض تفاصيل طلب أشعة الطوارئ (نفس تدفق طلب الأشعة العادي)
     */
    public function showEmergencyRadiology(
        \App\Models\EmergencyRadiologyRequest $emergencyRadiology
    ) {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $emergencyRadiology->load(['emergency', 'patient.user', 'radiologyTypes']);

        return view('staff.requests.emergency-radiology-show', compact('emergencyRadiology'));
    }

    /**
     * طباعة طلب أشعة الطوارئ
     */
    public function printEmergencyRadiology(
        \App\Models\EmergencyRadiologyRequest $emergencyRadiology
    ) {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $emergencyRadiology->load(['emergency', 'patient.user', 'radiologyTypes']);

        return view('staff.requests.emergency-radiology-print', compact('emergencyRadiology'));
    }

    /**
     * بدء العمل على طلب أشعة من الطوارئ
     */
    public function startEmergencyRadiology(\App\Models\EmergencyRadiologyRequest $emergencyRadiology)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $emergencyRadiology->update([
            'status' => 'in_progress'
        ]);

        return redirect()->back()->with('success', 'تم بدء العمل على طلب الأشعة');
    }

    /**
     * إكمال طلب أشعة من الطوارئ
     */
    public function completeEmergencyRadiology(HttpRequest $request, \App\Models\EmergencyRadiologyRequest $emergencyRadiology)
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $request->validate([
            'results' => 'nullable|array',
            'results.*' => 'nullable|string',
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($request->has('results')) {
            foreach ($request->results as $typeId => $result) {
                $updateData = ['result' => $result];

                if ($request->hasFile("images.$typeId")) {
                    $file = $request->file("images.$typeId");
                    $fileName = time() . '_' . $typeId . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('emergency_radiology_results', $fileName, 'public');
                    $updateData['image_path'] = $filePath;
                }

                $emergencyRadiology->requestTypes()
                    ->where('radiology_type_id', $typeId)
                    ->update($updateData);
            }
        }

        $emergencyRadiology->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $request->notes
        ]);

        return redirect()->back()->with('success', 'تم إكمال طلب الأشعة بنجاح');
    }

    /**
     * بدء العمل على طلب تحاليل من الطوارئ
     */
    public function startEmergencyLab(\App\Models\EmergencyLabRequest $emergencyLab)
    {
        $user = Auth::user();

        if (!$user->hasRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $emergencyLab->update([
            'status' => 'in_progress'
        ]);

        return redirect()->back()->with('success', 'تم بدء العمل على طلب التحاليل');
    }

    /**
     * إكمال طلب تحاليل من الطوارئ
     */
    public function completeEmergencyLab(HttpRequest $request, \App\Models\EmergencyLabRequest $emergencyLab)
    {
        $user = Auth::user();

        if (!$user->hasRole(['lab_staff', 'admin'])) {
            abort(403, 'غير مصرح لك بهذا الإجراء');
        }

        $request->validate([
            'results' => 'nullable|array',
            'results.*' => 'nullable|string',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($request->has('results')) {
            foreach ($request->results as $testId => $result) {
                $emergencyLab->requestTests()
                    ->where('lab_test_id', $testId)
                    ->update(['result' => $result]);
            }
        }

        $emergencyLab->update([
            'status' => 'completed',
            'completed_at' => now(),
            'notes' => $request->notes
        ]);

        return redirect()->back()->with('success', 'تم إكمال طلب التحاليل بنجاح');
    }
}

