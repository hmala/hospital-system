<?php


namespace App\Http\Controllers;

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
        if ($user->role === 'lab_staff') {
            $allowedTypes[] = 'lab';
        }
        if ($user->role === 'radiology_staff') {
            $allowedTypes[] = 'radiology';
        }
        if ($user->role === 'pharmacy_staff') {
            $allowedTypes[] = 'pharmacy';
        }

        // السماح للموظفين الآخرين برؤية جميع الأنواع
        $adminRoles = ['receptionist', 'admin', 'doctor'];
        if (in_array($user->role, $adminRoles)) {
            $allowedTypes = ['lab', 'radiology', 'pharmacy'];
        }

        if (empty($allowedTypes)) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // فلترة الطلبات حسب النوع المسموح
        $query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->whereIn('type', $allowedTypes);

        // فلترة حسب النوع المحدد في URL
        if ($type && in_array($type, $allowedTypes)) {
            $query->where('type', $type);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('staff.requests.index', compact('requests', 'allowedTypes', 'type'));
    }

    public function show(MedicalRequest $request)
    {
        $user = auth()->user();

        // التحقق من صلاحية المستخدم لعرض هذا النوع من الطلبات
        $allowedTypes = [];
        if ($user->role === 'lab_staff') {
            $allowedTypes[] = 'lab';
        }
        if ($user->role === 'radiology_staff') {
            $allowedTypes[] = 'radiology';
        }
        if ($user->role === 'pharmacy_staff') {
            $allowedTypes[] = 'pharmacy';
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

        // 1. حفظ التحاليل المختارة (من المودال)
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
        $allowedRoles = ['receptionist', 'admin', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'doctor'];
        if (!in_array($user->role, $allowedRoles)) {
            \Log::warning('تم رفض الوصول لإنشاء زيارة مختبرية', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'allowed_roles' => $allowedRoles
            ]);
            abort(403, 'غير مصرح لك بإنشاء زيارات مختبرية');
        }

        $patients = \App\Models\Patient::with('user')->get();
        $departments = \App\Models\Department::all();

        return view('staff.lab-visits.create', compact('patients', 'departments'));
    }

    public function storeLabVisit(HttpRequest $request)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        $allowedRoles = ['receptionist', 'admin', 'lab_staff', 'radiology_staff', 'pharmacy_staff', 'doctor'];
        if (!in_array($user->role, $allowedRoles)) {
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
        if ($user->role !== 'lab_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بالوصول إلى طلبات المختبر للعمليات');
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
        if ($user->role !== 'lab_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
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
        if ($user->role !== 'lab_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
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

        return redirect()->back()->with('success', 'تم تحديث الطلب بنجاح');
    }

    /**
     * عرض طلبات الأشعة للعمليات
     */
    public function surgeryRadiologyTests(HttpRequest $request)
    {
        $user = Auth::user();
        
        // التحقق من الصلاحية
        if ($user->role !== 'radiology_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بالوصول إلى طلبات الأشعة للعمليات');
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
        if ($user->role !== 'radiology_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
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
        if ($user->role !== 'radiology_staff' && $user->role !== 'admin' && $user->role !== 'doctor') {
            abort(403, 'غير مصرح لك بتحديث هذا الطلب');
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
}
