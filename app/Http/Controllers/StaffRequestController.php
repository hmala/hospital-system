<?php


namespace App\Http\Controllers;

use App\Events\SurgeryLabTestUpdated;
use App\Models\Request as MedicalRequest;
use App\Models\LabTestResult;
use App\Models\LabResult;
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

        // تحديد نوع الطلبات حسب دور المستخدم
        $allowedTypes = [];
        if ($user->hasRole('lab_staff')) {
            $allowedTypes[] = 'lab';
        }
        if ($user->hasRole('radiology_staff')) {
            $allowedTypes[] = 'radiology';
        }
        if ($user->hasRole('pharmacy_staff')) {
            $allowedTypes[] = 'pharmacy';
        }

        // السماح للموظفين الآخرين برؤية جميع الأنواع
        if ($user->hasRole(['receptionist', 'admin', 'doctor'])) {
            $allowedTypes = ['lab', 'radiology', 'pharmacy'];
        }

        if (empty($allowedTypes)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // فلترة الطلبات حسب النوع المسموح
        $query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->whereIn('type', $allowedTypes)
            ->where(function($q) {
                // عرض الطلبات المدفوعة أو التي بانتظار تحديد الخدمات
                $q->where('payment_status', 'paid')
                  ->orWhere('status', 'pending_service_selection');
            });

        // فلترة حسب النوع المحدد في URL
        if ($type && in_array($type, $allowedTypes)) {
            $query->where('type', $type);
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

        if ($user->hasRole('radiology_staff') || $user->hasAnyRole(['admin'])) {
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
        }
        if ($user->hasRole('radiology_staff')) {
            $allowedTypes[] = 'radiology';
        }
        if ($user->hasRole('pharmacy_staff')) {
            $allowedTypes[] = 'pharmacy';
        }
        
        // admin يستطيع رؤية كل شيء
        if ($user->hasRole(['admin', 'receptionist', 'doctor'])) {
            $allowedTypes = ['lab', 'radiology', 'pharmacy'];
        }

        if (!in_array($request->type, $allowedTypes)) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $request->load(['visit.patient.user', 'visit.doctor.user']);

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

        return view('staff.requests.show', compact('request', 'savedTestResults', 'savedNotes'));
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
        if ($request->status === 'pending_service_selection' && $httpRequest->has('lab_test_ids')) {
            $details = $request->details ?? [];
            if (is_string($details)) {
                $details = json_decode($details, true) ?? [];
            }
            if (!is_array($details)) {
                $details = [];
            }
            
            $details['lab_test_ids'] = $httpRequest->lab_test_ids;
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

            Log::info('تم تحديد الخدمات المطلوبة (مختبر)', [
                'request_id' => $request->id,
                'lab_test_ids' => $httpRequest->lab_test_ids,
                'selected_by' => auth()->user()->name
            ]);

            return redirect()
                ->route('staff.requests.index', ['type' => $request->type])
                ->with('success', 'تم تحديد التحاليل المطلوبة بنجاح. الطلب الآن بانتظار الدفع عند الكاشير.');
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

            foreach ($httpRequest->test_results as $testName => $data) {
                if (!empty($data['value'])) {
                    try {
                        $labResult = LabResult::create([
                            'visit_id' => $request->visit_id,
                            'request_id' => $request->id,
                            'test_name' => $testName,
                            'value' => $data['value'],
                            'unit' => $data['unit'] ?? '',
                            'status' => (new LabResult)->determineStatus($data['value'], $testName),
                            'reference_range' => (new LabResult)->getReferenceRange($testName),
                            'notes' => $data['notes'] ?? null,
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

        $patients = \App\Models\Patient::with('user')->get();
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
     * طباعة نتائج التحاليل
     */
    public function print(MedicalRequest $request)
    {
        // تحميل العلاقات
        $request->load(['visit.patient.user', 'visit.doctor.user']);

        return view('staff.requests.print', compact('request'));
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

        $query = \App\Models\SurgeryLabTest::with(['surgery.patient.user', 'surgery.doctor.user', 'labTest']);

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

        $query = \App\Models\SurgeryRadiologyTest::with(['surgery.patient.user', 'surgery.doctor.user', 'radiologyType']);

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

        // التحقق من دفع رسوم العملية أولاً
        $test->load('surgery');
        if ($test->surgery && $test->surgery->surgery_fee_paid !== 'paid') {
            return redirect()->back()->with('error', 'لا يمكن إجراء الأشعة قبل دفع رسوم العملية الجراحية');
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,completed,cancelled',
            'result' => 'nullable|string',
            'result_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
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

        return redirect()->back()->with('success', 'تم تحديث الطلب بنجاح');
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

        if (!$user->hasRole(['radiology_staff', 'admin'])) {
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

        if (!$user->hasRole(['radiology_staff', 'admin'])) {
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

