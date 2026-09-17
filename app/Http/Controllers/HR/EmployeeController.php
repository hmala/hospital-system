<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\Department;
use App\Models\User;
use App\Models\HrLookupOption;
use App\Models\HrFieldRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view employees')->only(['index', 'show', 'downloadDocument']);
        $this->middleware('permission:create employees')->only(['create', 'store', 'uploadDocument']);
        $this->middleware('permission:edit employees')->only(['edit', 'update']);
        $this->middleware('permission:delete employees')->only(['destroy', 'destroyDocument']);
    }

    /**
     * عرض قائمة الموظفين مع الفلاتر والإحصائيات
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'user', 'documents']);

        // البحث بالنص (الاسم، الرقم الوظيفي، الهاتف، رقم الهوية)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('medical_license_number', 'like', "%{$search}%");
            });
        }

        // فلتر نوع الكادر
        if ($request->filled('staff_type')) {
            $query->where('staff_type', $request->staff_type);
        }

        // فلتر القسم
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // فلتر الحالة الوظيفية
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر نوع التعاقد
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->employment_type);
        }

        // فلتر حالة ترخيص ممارسة المهنة
        if ($request->filled('license_status')) {
            if ($request->license_status === 'expiring_soon') {
                $query->whereNotNull('license_expiry_date')
                      ->where('license_expiry_date', '>=', now())
                      ->where('license_expiry_date', '<=', now()->addDays(30));
            } elseif ($request->license_status === 'expired') {
                $query->whereNotNull('license_expiry_date')
                      ->where('license_expiry_date', '<', now());
            }
        }

        $employees = $query->latest()->paginate(20)->withQueryString();

        // إحصائيات سريعة للبطاقات العلوية
        $stats = [
            'total' => Employee::count(),
            'active' => Employee::where('status', 'active')->count(),
            'medical_staff' => Employee::whereIn('staff_type', ['medical', 'nursing', 'technical'])->count(),
            'admin_staff' => Employee::whereIn('staff_type', ['administrative', 'service'])->count(),
            'license_expiring' => Employee::whereNotNull('license_expiry_date')
                ->where('license_expiry_date', '>=', now())
                ->where('license_expiry_date', '<=', now()->addDays(30))
                ->count(),
        ];

        $departments = Department::orderBy('name')->get();
        $employmentTypes = HrLookupOption::where('category', 'employment_type')->where('is_active', true)->orderBy('sort_order')->get();

        return view('hr.employees.index', compact('employees', 'stats', 'departments', 'employmentTypes'));
    }

    /**
     * شاشة إضافة موظف جديد
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $linkedUserIds = Employee::whereNotNull('user_id')->pluck('user_id')->toArray();
        $users = User::whereNotIn('id', $linkedUserIds)->orderBy('name')->get();

        // جلب خيارات القوائم المنسدلة النشطة
        $employmentTypes = HrLookupOption::where('category', 'employment_type')->where('is_active', true)->orderBy('sort_order')->get();
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();

        // جلب خريطة الحقول الإجبارية
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        // توليد رقم وظيفي افتراضي تلقائياً
        $latestId = Employee::withTrashed()->max('id') ?? 0;
        $nextCode = 'EMP-' . str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        return view('hr.employees.create', compact('departments', 'users', 'employmentTypes', 'documentTypes', 'reqMap', 'nextCode'));
    }

    /**
     * حفظ الموظف الجديد مع مستمسكاته
     */
    public function store(Request $request)
    {
        // بناء قواعد التحقق ديناميكياً من جدول إعدادات الحقول
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        $rules = [
            'employee_code' => 'required|string|max:30|unique:employees,employee_code',
            'full_name' => 'required|string|max:255',
            'national_id' => ($reqMap['national_id'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'gender' => ($reqMap['gender'] ?? true) ? 'required|in:male,female' : 'nullable|in:male,female',
            'date_of_birth' => ($reqMap['date_of_birth'] ?? false) ? 'required|date' : 'nullable|date',
            'phone' => ($reqMap['phone'] ?? true) ? 'required|string|max:30' : 'nullable|string|max:30',
            'emergency_phone' => ($reqMap['emergency_phone'] ?? false) ? 'required|string|max:30' : 'nullable|string|max:30',
            'email' => ($reqMap['email'] ?? false) ? 'required|email|max:255' : 'nullable|email|max:255',
            'address' => ($reqMap['address'] ?? false) ? 'required|string' : 'nullable|string',
            'blood_group' => ($reqMap['blood_group'] ?? false) ? 'required|string|max:10' : 'nullable|string|max:10',
            'staff_type' => 'required|in:medical,nursing,technical,administrative,service',
            'job_title' => ($reqMap['job_title'] ?? true) ? 'required|string|max:255' : 'nullable|string|max:255',
            'department_id' => ($reqMap['department_id'] ?? true) ? 'required|exists:departments,id' : 'nullable|exists:departments,id',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id',
            'employment_type' => ($reqMap['employment_type'] ?? true) ? 'required|string|max:100' : 'nullable|string|max:100',
            'hire_date' => ($reqMap['hire_date'] ?? true) ? 'required|date' : 'nullable|date',
            'contract_end_date' => ($reqMap['contract_end_date'] ?? false) ? 'required|date|after_or_equal:hire_date' : 'nullable|date|after_or_equal:hire_date',
            'basic_salary' => ($reqMap['basic_salary'] ?? false) ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'status' => 'required|in:active,on_leave,suspended,resigned,terminated',
            'medical_license_number' => ($reqMap['medical_license_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'license_expiry_date' => ($reqMap['license_expiry_date'] ?? false) ? 'required|date' : 'nullable|date',
            'syndicate_card_number' => ($reqMap['syndicate_card_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'sub_specialty' => ($reqMap['sub_specialty'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'qualification' => ($reqMap['qualification'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'profile_photo' => ($reqMap['profile_photo'] ?? false) ? 'required|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'notes' => 'nullable|string',
            'documents.*.type' => 'nullable|string|max:100',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // 10MB
        ];

        // إذا كان رفع المستمسكات إجبارياً
        if (($reqMap['documents'] ?? false)) {
            $rules['documents'] = 'required|array|min:1';
            $rules['documents.*.file'] = 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240';
        }

        $validated = $request->validate($rules, [
            'employee_code.unique' => 'الرقم الوظيفي مسجل مسبقاً لموظف آخر.',
            'full_name.required' => 'يرجى إدخال اسم الموظف بالكامل.',
            'phone.required' => 'يرجى إدخال رقم هاتف الموظف.',
            'job_title.required' => 'يرجى تحديد المسمى الوظيفي.',
            'department_id.required' => 'يرجى اختيار القسم التابع له الموظف.',
            'employment_type.required' => 'يرجى اختيار نوع التعاقد / التعيين.',
            'hire_date.required' => 'يرجى تحديد تاريخ المباشرة بالعمل.',
            'documents.required' => 'يشترط النظام إرفاق مستمسك رسمي واحد على الأقل.',
        ]);

        // معالجة رفع صورة الموظف
        if ($request->hasFile('profile_photo')) {
            $path = $request->file('profile_photo')->store('employees/photos', 'public');
            $validated['profile_photo'] = $path;
        }

        $employee = Employee::create($validated);

        // معالجة ورفع المستمسكات المرفقة
        $this->processDocumentsUpload($request, $employee);

        return redirect()->route('hr.employees.show', $employee->id)
            ->with('success', "تم تسجيل الموظف '{$employee->full_name}' بنجاح بالرقم الوظيفي: {$employee->employee_code}");
    }

    /**
     * عرض إضبارة الموظف الشاملة مع مستمسكاته
     */
    public function show(Employee $employee)
    {
        $employee->load(['department', 'user', 'documents.uploader']);
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();

        return view('hr.employees.show', compact('employee', 'documentTypes'));
    }

    /**
     * شاشة تعديل بيانات الموظف
     */
    public function edit(Employee $employee)
    {
        $employee->load('documents');
        $departments = Department::orderBy('name')->get();
        $linkedUserIds = Employee::whereNotNull('user_id')
            ->where('id', '!=', $employee->id)
            ->pluck('user_id')
            ->toArray();
        $users = User::whereNotIn('id', $linkedUserIds)->orderBy('name')->get();

        $employmentTypes = HrLookupOption::where('category', 'employment_type')->where('is_active', true)->orderBy('sort_order')->get();
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        return view('hr.employees.edit', compact('employee', 'departments', 'users', 'employmentTypes', 'documentTypes', 'reqMap'));
    }

    /**
     * تحديث بيانات الموظف
     */
    public function update(Request $request, Employee $employee)
    {
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        $rules = [
            'employee_code' => 'required|string|max:30|unique:employees,employee_code,' . $employee->id,
            'full_name' => 'required|string|max:255',
            'national_id' => ($reqMap['national_id'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'gender' => ($reqMap['gender'] ?? true) ? 'required|in:male,female' : 'nullable|in:male,female',
            'date_of_birth' => ($reqMap['date_of_birth'] ?? false) ? 'required|date' : 'nullable|date',
            'phone' => ($reqMap['phone'] ?? true) ? 'required|string|max:30' : 'nullable|string|max:30',
            'emergency_phone' => ($reqMap['emergency_phone'] ?? false) ? 'required|string|max:30' : 'nullable|string|max:30',
            'email' => ($reqMap['email'] ?? false) ? 'required|email|max:255' : 'nullable|email|max:255',
            'address' => ($reqMap['address'] ?? false) ? 'required|string' : 'nullable|string',
            'blood_group' => ($reqMap['blood_group'] ?? false) ? 'required|string|max:10' : 'nullable|string|max:10',
            'staff_type' => 'required|in:medical,nursing,technical,administrative,service',
            'job_title' => ($reqMap['job_title'] ?? true) ? 'required|string|max:255' : 'nullable|string|max:255',
            'department_id' => ($reqMap['department_id'] ?? true) ? 'required|exists:departments,id' : 'nullable|exists:departments,id',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,' . $employee->id,
            'employment_type' => ($reqMap['employment_type'] ?? true) ? 'required|string|max:100' : 'nullable|string|max:100',
            'hire_date' => ($reqMap['hire_date'] ?? true) ? 'required|date' : 'nullable|date',
            'contract_end_date' => ($reqMap['contract_end_date'] ?? false) ? 'required|date|after_or_equal:hire_date' : 'nullable|date|after_or_equal:hire_date',
            'basic_salary' => ($reqMap['basic_salary'] ?? false) ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'status' => 'required|in:active,on_leave,suspended,resigned,terminated',
            'medical_license_number' => ($reqMap['medical_license_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'license_expiry_date' => ($reqMap['license_expiry_date'] ?? false) ? 'required|date' : 'nullable|date',
            'syndicate_card_number' => ($reqMap['syndicate_card_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'sub_specialty' => ($reqMap['sub_specialty'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'qualification' => ($reqMap['qualification'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'notes' => 'nullable|string',
            'documents.*.type' => 'nullable|string|max:100',
            'documents.*.file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
        ];

        $validated = $request->validate($rules);

        if ($request->hasFile('profile_photo')) {
            if ($employee->profile_photo && Storage::disk('public')->exists($employee->profile_photo)) {
                Storage::disk('public')->delete($employee->profile_photo);
            }
            $path = $request->file('profile_photo')->store('employees/photos', 'public');
            $validated['profile_photo'] = $path;
        }

        $employee->update($validated);

        // معالجة أي مستمسكات جديدة تم إرفاقها أثناء التعديل
        $this->processDocumentsUpload($request, $employee);

        return redirect()->route('hr.employees.show', $employee->id)
            ->with('success', "تم تحديث بيانات الموظف '{$employee->full_name}' بنجاح.");
    }

    /**
     * رفع مستمسك جديد مباشرة من شاشة إضبارة الموظف
     */
    public function uploadDocument(Request $request, Employee $employee)
    {
        $request->validate([
            'document_type' => 'required|string|max:100',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'notes' => 'nullable|string|max:500',
        ], [
            'document_type.required' => 'يرجى تحديد نوع المستمسك.',
            'document_file.required' => 'يرجى اختيار ملف المستمسك (PDF أو صورة).',
        ]);

        $file = $request->file('document_file');
        $docType = $request->input('document_type');
        $extension = $file->getClientOriginalExtension();
        $fileSize = $file->getSize();

        // تنظيف نوع المستمسك للاستخدام في اسم الملف
        $cleanType = preg_replace('/[^\p{L}\p{N}_]+/u', '_', trim($docType));
        // توليد اسم ملف منظم: الرمز_نوع_المستمسك_التوقيت.pdf
        $fileName = "{$employee->employee_code}_{$cleanType}_" . time() . ".{$extension}";

        $storedPath = $file->storeAs("employees/documents/{$employee->employee_code}", $fileName, 'public');

        EmployeeDocument::create([
            'employee_id' => $employee->id,
            'employee_code' => $employee->employee_code,
            'document_type' => $docType,
            'file_name' => $fileName,
            'file_path' => $storedPath,
            'file_extension' => $extension,
            'file_size' => $fileSize,
            'notes' => $request->input('notes'),
            'uploaded_by' => auth()->id(),
        ]);

        return redirect()->route('hr.employees.show', $employee->id)
            ->with('success', "تم رفع وتوثيق المستمسك '{$docType}' بنجاح للموظف.");
    }

    /**
     * تحميل أو معاينة المستمسك
     */
    public function downloadDocument(EmployeeDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'الملف غير موجود في الخادم.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * حذف مستمسك رسمي للموظف
     */
    public function destroyDocument(EmployeeDocument $document)
    {
        $employeeId = $document->employee_id;
        $docType = $document->document_type;

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('hr.employees.show', $employeeId)
            ->with('success', "تم حذف المستمسك '{$docType}' بنجاح.");
    }

    /**
     * دالة مساعدة لمعالجة ورفع مصفوفة المستمسكات من نماذج التسجيل والتعديل
     */
    private function processDocumentsUpload(Request $request, Employee $employee)
    {
        if ($request->has('documents') && is_array($request->documents)) {
            foreach ($request->documents as $index => $docData) {
                if (isset($docData['file']) && $request->hasFile("documents.{$index}.file")) {
                    $file = $docData['file'];
                    $docType = $docData['type'] ?? 'مستمسك رسمي';
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getSize();

                    $cleanType = preg_replace('/[^\p{L}\p{N}_]+/u', '_', trim($docType));
                    $fileName = "{$employee->employee_code}_{$cleanType}_" . time() . "_{$index}.{$extension}";

                    $storedPath = $file->storeAs("employees/documents/{$employee->employee_code}", $fileName, 'public');

                    EmployeeDocument::create([
                        'employee_id' => $employee->id,
                        'employee_code' => $employee->employee_code,
                        'document_type' => $docType,
                        'file_name' => $fileName,
                        'file_path' => $storedPath,
                        'file_extension' => $extension,
                        'file_size' => $fileSize,
                        'notes' => $docData['notes'] ?? null,
                        'uploaded_by' => auth()->id(),
                    ]);
                }
            }
        }
    }

    /**
     * حذف أو أرشفة الموظف
     */
    public function destroy(Employee $employee)
    {
        $name = $employee->full_name;
        $employee->delete();

        return redirect()->route('hr.employees.index')
            ->with('success', "تم أرشفة/حذف ملف الموظف '{$name}' بنجاح.");
    }
}
