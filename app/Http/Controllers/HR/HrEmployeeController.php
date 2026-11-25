<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\HrEmployee;
use App\Models\HrEmployeeDocument;
use App\Models\HrFieldRequirement;
use App\Models\HrLookupOption;
use App\Models\HrShift;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HrEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:view employees')->only(['index', 'show', 'roster']);
        $this->middleware('permission:create employees')->only(['create', 'store']);
        $this->middleware('permission:edit employees')->only(['edit', 'update']);
        $this->middleware('permission:delete employees')->only(['destroy']);
        $this->middleware('permission:manage hr documents')->only(['uploadDocument', 'downloadDocument', 'destroyDocument']);
        $this->middleware('permission:manage hr actions')->only(['storeAction', 'destroyAction', 'cancelAction', 'restoreAction', 'requestCancelAction']);
        $this->middleware('permission:approve hr action cancellation')->only(['approveCancelAction', 'rejectCancelAction']);
        $this->middleware('permission:edit employees')->only(['linkUser']);
        $this->middleware('permission:print employee badges')->only(['badge']);
    }

    /**
     * عرض قائمة الموظفين مع الفلاتر والإحصائيات
     */
    public function index(Request $request)
    {
        $query = HrEmployee::with(['department', 'user', 'documents']);

        // البحث بالنص (الاسم، الرقم الوظيفي، الهاتف، رقم الهوية)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('employee_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('national_id', 'like', "%{$search}%")
                    ->orWhere('nationality', 'like', "%{$search}%")
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
            'total' => HrEmployee::count(),
            'active' => HrEmployee::where('status', 'active')->count(),
            'medical_staff' => HrEmployee::whereIn('staff_type', ['medical', 'nursing', 'technical'])->count(),
            'admin_staff' => HrEmployee::whereIn('staff_type', ['administrative', 'service'])->count(),
            'license_expiring' => HrEmployee::whereNotNull('license_expiry_date')
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
        $users = $this->getAvailableStaffUsers();

        // جلب خيارات القوائم المنسدلة النشطة
        $employmentTypes = HrLookupOption::where('category', 'employment_type')->where('is_active', true)->orderBy('sort_order')->get();
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();
        $nationalities = HrLookupOption::where('category', 'nationality')->where('is_active', true)->orderBy('sort_order')->get();

        // جلب خريطة الحقول الإجبارية
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();
        $shifts = HrShift::where('is_active', true)->orderBy('start_time')->get();

        // توليد رقم وظيفي افتراضي تلقائياً
        $latestId = HrEmployee::withTrashed()->max('id') ?? 0;
        $nextCode = 'EMP-'.str_pad($latestId + 1, 4, '0', STR_PAD_LEFT);

        return view('hr.employees.create', compact('departments', 'users', 'employmentTypes', 'documentTypes', 'nationalities', 'reqMap', 'shifts', 'nextCode'));
    }

    /**
     * حفظ الموظف الجديد مع مستمسكاته
     */
    public function store(Request $request)
    {
        // بناء قواعد التحقق ديناميكياً من جدول إعدادات الحقول
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        $rules = [
            'employee_code' => 'required|string|max:30|unique:hr_employees,employee_code',
            'full_name' => 'required|string|max:255',
            'national_id' => ($reqMap['national_id'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'nationality' => ($reqMap['nationality'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
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
            'user_id' => 'nullable|exists:users,id|unique:hr_employees,user_id',
            'employment_type' => ($reqMap['employment_type'] ?? true) ? 'required|string|max:100' : 'nullable|string|max:100',
            'hire_date' => ($reqMap['hire_date'] ?? true) ? 'required|date' : 'nullable|date',
            'contract_end_date' => ($reqMap['contract_end_date'] ?? false) ? 'required|date|after_or_equal:hire_date' : 'nullable|date|after_or_equal:hire_date',
            'basic_salary' => ($reqMap['basic_salary'] ?? false) ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'salary_calculation_mode' => 'nullable|in:fixed_salary,per_call',
            'custom_call_rate' => 'nullable|numeric|min:0',
            'annual_leave_balance' => 'nullable|integer|min:0|max:365',
            'status' => 'required|in:active,on_leave,suspended,resigned,terminated',
            'medical_license_number' => ($reqMap['medical_license_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'license_expiry_date' => ($reqMap['license_expiry_date'] ?? false) ? 'required|date' : 'nullable|date',
            'syndicate_card_number' => ($reqMap['syndicate_card_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'sub_specialty' => ($reqMap['sub_specialty'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'qualification' => ($reqMap['qualification'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'profile_photo' => ($reqMap['profile_photo'] ?? false) ? 'required|image|mimes:jpeg,png,jpg,webp|max:2048' : 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'notes' => 'nullable|string',
            'schedule_type' => 'nullable|in:fixed,rotating',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
            'is_split_shift' => 'nullable|boolean',
            'second_shift_id' => 'nullable|exists:hr_shifts,id',
            'custom_start_time' => 'nullable',
            'custom_end_time' => 'nullable',
            'weekly_off_days' => 'nullable|array',
            'weekly_off_days.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
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

        if (isset($validated['employment_type'])) {
            $empType = trim($validated['employment_type']);
            if (strcasecmp($empType, 'fulltime') === 0 || strcasecmp($empType, 'full-time') === 0) {
                $validated['employment_type'] = 'full_time';
            }
        }

        if (empty($validated['nationality'])) {
            $validated['nationality'] = 'عراقي';
        }

        $validated['salary_calculation_mode'] = $validated['salary_calculation_mode'] ?? 'fixed_salary';
        $validated['annual_leave_balance'] = $validated['annual_leave_balance'] ?? 21;
        $validated['schedule_type'] = $validated['schedule_type'] ?? 'fixed';
        $validated['is_split_shift'] = $request->boolean('is_split_shift');
        if (! $validated['is_split_shift']) {
            $validated['second_shift_id'] = null;
        }
        $validated['weekly_off_days'] = $request->input('weekly_off_days', ['friday']);
        if (empty($validated['weekly_off_days'])) {
            $validated['weekly_off_days'] = ['friday'];
        }

        $employee = HrEmployee::create($validated);

        // معالجة ورفع المستمسكات المرفقة
        $this->processDocumentsUpload($request, $employee);

        return redirect()->route('hr.employees.show', $employee->id)
            ->with('success', "تم تسجيل الموظف '{$employee->full_name}' بنجاح بالرقم الوظيفي: {$employee->employee_code}");
    }

    /**
     * عرض إضبارة الموظف الشاملة مع مستمسكاته
     */
    public function show(HrEmployee $employee)
    {
        $employee->load(['department', 'user', 'documents.uploader', 'defaultShift', 'secondShift']);
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();
        $actionSettings = \App\Models\HrActionSetting::where('is_active', true)->orderBy('category')->get();
        $availableStaffUsers = $this->getAvailableStaffUsers($employee->id);

        return view('hr.employees.show', compact('employee', 'documentTypes', 'actionSettings', 'availableStaffUsers'));
    }

    /**
     * شاشة تعديل بيانات الموظف
     */
    public function edit(HrEmployee $employee)
    {
        $employee->load('documents');
        $departments = Department::orderBy('name')->get();
        $users = $this->getAvailableStaffUsers($employee->id);

        $employmentTypes = HrLookupOption::where('category', 'employment_type')->where('is_active', true)->orderBy('sort_order')->get();
        $documentTypes = HrLookupOption::where('category', 'document_type')->where('is_active', true)->orderBy('sort_order')->get();
        $nationalities = HrLookupOption::where('category', 'nationality')->where('is_active', true)->orderBy('sort_order')->get();
        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();
        $shifts = HrShift::where('is_active', true)->orderBy('start_time')->get();

        return view('hr.employees.edit', compact('employee', 'departments', 'users', 'employmentTypes', 'documentTypes', 'nationalities', 'reqMap', 'shifts'));
    }

    /**
     * تحديث بيانات الموظف
     */
    public function update(Request $request, HrEmployee $employee)
    {
        if ($request->input('_section') === 'payroll_settings') {
            $validated = $request->validate([
                'payment_method' => 'required|in:cash,bank',
                'bank_name' => 'nullable|string|max:255',
                'bank_account_number' => 'nullable|string|max:255',
                'salary_calculation_mode' => 'nullable|in:fixed_salary,per_call',
                'custom_call_rate' => 'nullable|numeric|min:0',
                'overtime_hourly_rate' => 'required|numeric|min:0',
                'social_security_percentage' => 'nullable|numeric|min:0|max:100',
                'tax_percentage' => 'nullable|numeric|min:0|max:100',
            ]);

            $validated['salary_calculation_mode'] = $validated['salary_calculation_mode'] ?? ($employee->salary_calculation_mode ?? 'fixed_salary');
            $validated['custom_call_rate'] = $request->filled('custom_call_rate') ? $request->custom_call_rate : null;
            $validated['subject_to_social_security'] = $request->boolean('subject_to_social_security');
            $validated['subject_to_tax'] = $request->boolean('subject_to_tax');

            if ($validated['payment_method'] === 'cash') {
                $validated['bank_name'] = null;
                $validated['bank_account_number'] = null;
            }

            $employee->update($validated);

            return back()->with('success', 'تم تحديث إعدادات هيكلة الراتب بنجاح.');
        }

        $reqMap = HrFieldRequirement::pluck('is_required', 'field_key')->toArray();

        $rules = [
            'employee_code' => 'required|string|max:30|unique:hr_employees,employee_code,'.$employee->id,
            'full_name' => 'required|string|max:255',
            'national_id' => ($reqMap['national_id'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
            'nationality' => ($reqMap['nationality'] ?? false) ? 'required|string|max:50' : 'nullable|string|max:50',
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
            'user_id' => 'nullable|exists:users,id|unique:hr_employees,user_id,'.$employee->id,
            'employment_type' => ($reqMap['employment_type'] ?? true) ? 'required|string|max:100' : 'nullable|string|max:100',
            'hire_date' => ($reqMap['hire_date'] ?? true) ? 'required|date' : 'nullable|date',
            'contract_end_date' => ($reqMap['contract_end_date'] ?? false) ? 'required|date|after_or_equal:hire_date' : 'nullable|date|after_or_equal:hire_date',
            'basic_salary' => ($reqMap['basic_salary'] ?? false) ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'salary_calculation_mode' => 'nullable|in:fixed_salary,per_call',
            'custom_call_rate' => 'nullable|numeric|min:0',
            'annual_leave_balance' => 'nullable|integer|min:0|max:365',
            'status' => 'required|in:active,on_leave,suspended,resigned,terminated',
            'medical_license_number' => ($reqMap['medical_license_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'license_expiry_date' => ($reqMap['license_expiry_date'] ?? false) ? 'required|date' : 'nullable|date',
            'syndicate_card_number' => ($reqMap['syndicate_card_number'] ?? false) ? 'required|string|max:100' : 'nullable|string|max:100',
            'sub_specialty' => ($reqMap['sub_specialty'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'qualification' => ($reqMap['qualification'] ?? false) ? 'required|string|max:255' : 'nullable|string|max:255',
            'profile_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'notes' => 'nullable|string',
            'schedule_type' => 'nullable|in:fixed,rotating',
            'default_shift_id' => 'nullable|exists:hr_shifts,id',
            'is_split_shift' => 'nullable|boolean',
            'second_shift_id' => 'nullable|exists:hr_shifts,id',
            'custom_start_time' => 'nullable',
            'custom_end_time' => 'nullable',
            'weekly_off_days' => 'nullable|array',
            'weekly_off_days.*' => 'in:sunday,monday,tuesday,wednesday,thursday,friday,saturday',
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

        if (isset($validated['employment_type'])) {
            $empType = trim($validated['employment_type']);
            if (strcasecmp($empType, 'fulltime') === 0 || strcasecmp($empType, 'full-time') === 0) {
                $validated['employment_type'] = 'full_time';
            }
        }

        if (empty($validated['nationality'])) {
            $validated['nationality'] = 'عراقي';
        }

        $validated['salary_calculation_mode'] = $validated['salary_calculation_mode'] ?? ($employee->salary_calculation_mode ?? 'fixed_salary');
        $validated['custom_call_rate'] = $request->filled('custom_call_rate') ? $request->custom_call_rate : null;
        $validated['annual_leave_balance'] = $validated['annual_leave_balance'] ?? ($employee->annual_leave_balance ?? 21);
        $validated['schedule_type'] = $validated['schedule_type'] ?? ($employee->schedule_type ?? 'fixed');
        $validated['is_split_shift'] = $request->boolean('is_split_shift');
        if (! $validated['is_split_shift']) {
            $validated['second_shift_id'] = null;
        }
        $validated['weekly_off_days'] = $request->input('weekly_off_days', ['friday']);
        if (empty($validated['weekly_off_days'])) {
            $validated['weekly_off_days'] = ['friday'];
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
    public function uploadDocument(Request $request, HrEmployee $employee)
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
        $fileName = "{$employee->employee_code}_{$cleanType}_".time().".{$extension}";

        $storedPath = $file->storeAs("employees/documents/{$employee->employee_code}", $fileName, 'public');

        HrEmployeeDocument::create([
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
    public function downloadDocument(HrEmployeeDocument $document)
    {
        if (! Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'الملف غير موجود في الخادم.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * حذف مستمسك رسمي للموظف
     */
    public function storeAction(\Illuminate\Http\Request $request, HrEmployee $employee)
    {
        $request->validate([
            'hr_action_setting_id' => 'required|exists:hr_action_settings,id',
            'action_date' => 'required|date',
            'reason' => 'required|string',
        ]);

        $setting = \App\Models\HrActionSetting::findOrFail($request->hr_action_setting_id);

        // احتساب الأثر المالي
        $financial_amount = 0;
        if ($setting->effect_type == 'amount') {
            $financial_amount = $setting->effect_value;
        } elseif ($setting->effect_type == 'days') {
            // يومية الموظف = الراتب الأساسي / 30
            $dayValue = $employee->basic_salary / 30;
            $financial_amount = $dayValue * $setting->effect_value;
        } elseif ($setting->effect_type == 'percentage') {
            $financial_amount = $employee->basic_salary * ($setting->effect_value / 100);
        }

        // جعل قيمة العقوبة سالبة لخصمها من الراتب
        if ($setting->category == 'penalty' && $financial_amount > 0) {
            $financial_amount = -$financial_amount;
        }

        \App\Models\HrEmployeeAction::create([
            'hr_employee_id' => $employee->id,
            'hr_action_setting_id' => $setting->id,
            'action_date' => $request->action_date,
            'reason' => $request->reason,
            'applied_effect_type' => $setting->effect_type,
            'applied_effect_value' => $setting->effect_value,
            'financial_amount' => $financial_amount,
            'status' => 'pending',
            'cancellable_until' => now()->addHours(48),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('hr.employees.show', $employee->id)->with('success', 'تم تسجيل الإجراء (العقوبة/المكافأة) بنجاح.');
    }

    public function cancelAction(\Illuminate\Http\Request $request, \App\Models\HrEmployeeAction $action)
    {
        if ($action->status === 'processed') {
            return back()->with('error', 'لا يمكن إلغاء إجراء تم ترحيله بالفعل في مسير الرواتب.');
        }

        $isAdminApprover = auth()->user()->can('approve hr action cancellation');
        if (! $action->isWithin48Hours() && ! $isAdminApprover) {
            return back()->with('error', 'انتهت مهلة الـ 48 ساعة للإلغاء المباشر. يرجى رفع طلب إلغاء لاعتماده من مدير الإدارة.');
        }

        $updateData = [
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $request->input('cancellation_reason') ?: 'إلغاء إداري',
        ];

        if (! $action->isWithin48Hours() && $isAdminApprover) {
            $updateData['cancel_approved_by'] = auth()->id();
            $updateData['cancel_approved_at'] = now();
        }

        $action->update($updateData);
        $action->batch?->refreshStatusFromActions();

        return back()->with('success', 'تم إلغاء الإجراء بنجاح مع الاحتفاظ به في الأرشيف والسجل الرسمي للموظف.');
    }

    public function requestCancelAction(\Illuminate\Http\Request $request, \App\Models\HrEmployeeAction $action)
    {
        if ($action->status === 'processed') {
            return back()->with('error', 'لا يمكن طلب إلغاء إجراء تم ترحيله بالفعل في مسير الرواتب.');
        }

        if ($action->isCancelled()) {
            return back()->with('error', 'هذا الإجراء ملغي مسبقاً.');
        }

        $request->validate([
            'cancel_request_reason' => 'required|string|max:1000',
        ], [
            'cancel_request_reason.required' => 'يرجى كتابة سبب طلب الإلغاء لمدير الإدارة.',
        ]);

        $action->update([
            'cancel_requested_by' => auth()->id(),
            'cancel_requested_at' => now(),
            'cancel_request_reason' => $request->cancel_request_reason,
        ]);

        return back()->with('success', 'تم إرسال طلب الإلغاء إلى مدير الإدارة للموافقة.');
    }

    public function approveCancelAction(\Illuminate\Http\Request $request, \App\Models\HrEmployeeAction $action)
    {
        if ($action->status === 'processed') {
            return back()->with('error', 'لا يمكن إلغاء إجراء تم ترحيله بالفعل في مسير الرواتب.');
        }

        $action->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => $action->cancel_request_reason ?: ($request->input('cancellation_reason') ?: 'موافقة مدير الإدارة على الإلغاء'),
            'cancel_approved_by' => auth()->id(),
            'cancel_approved_at' => now(),
        ]);

        $action->batch?->refreshStatusFromActions();

        return back()->with('success', 'تمت موافقة مدير الإدارة وإلغاء الإجراء بنجاح.');
    }

    public function rejectCancelAction(\App\Models\HrEmployeeAction $action)
    {
        $action->update([
            'cancel_requested_by' => null,
            'cancel_requested_at' => null,
            'cancel_request_reason' => null,
        ]);

        return back()->with('success', 'تم رفض طلب الإلغاء وبقي الإجراء سارياً.');
    }

    public function restoreAction(\App\Models\HrEmployeeAction $action)
    {
        if ($action->status !== 'cancelled') {
            return back()->with('error', 'هذا الإجراء غير ملغي.');
        }

        $action->update([
            'status' => 'pending',
            'cancelled_by' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'cancel_requested_by' => null,
            'cancel_requested_at' => null,
            'cancel_request_reason' => null,
            'cancel_approved_by' => null,
            'cancel_approved_at' => null,
        ]);

        $action->batch?->refreshStatusFromActions();

        return back()->with('success', 'تم استعادة الإجراء وإعادة تفعيله بنجاح.');
    }

    public function destroyAction(\App\Models\HrEmployeeAction $action)
    {
        if ($action->status == 'processed') {
            return back()->with('error', 'لا يمكن حذف إجراء تم ترحيله بالفعل في مسير الرواتب.');
        }

        if (! $action->isWithin48Hours() && ! auth()->user()->can('approve hr action cancellation')) {
            return back()->with('error', 'انتهت مهلة الـ 48 ساعة للإلغاء المباشر. يرجى رفع طلب إلغاء لاعتماده من مدير الإدارة.');
        }

        // إلغاء الإجراء بدلاً من حذفه نهائياً للحفاظ على السجل الإداري
        $action->update([
            'status' => 'cancelled',
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
            'cancellation_reason' => 'تم الإلغاء عبر الحذف',
        ]);

        $action->batch?->refreshStatusFromActions();

        return back()->with('success', 'تم إلغاء الإجراء بنجاح وحفظه في الأرشيف والسجل.');
    }

    public function linkUser(Request $request, HrEmployee $employee)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id|unique:hr_employees,user_id,'.$employee->id,
        ], [
            'user_id.unique' => 'هذا الحساب مرتبط بموظف آخر بالفعل.',
        ]);

        $employee->update([
            'user_id' => $validated['user_id'] ?: null,
        ]);

        $msg = $validated['user_id']
            ? 'تم ربط حساب الموظف بنجاح، ويمكنه الآن تقديم طلبات الإجازة ومتابعة جدوله.'
            : 'تم فك ارتباط حساب الدخول عن الموظف.';

        return back()->with('success', $msg);
    }

    private function getAvailableStaffUsers(?int $exceptEmployeeId = null)
    {
        $linkedUserIds = HrEmployee::whereNotNull('user_id')
            ->when($exceptEmployeeId, fn ($q) => $q->where('id', '!=', $exceptEmployeeId))
            ->pluck('user_id')
            ->toArray();

        return User::query()
            ->whereNotIn('id', $linkedUserIds)
            ->where(function ($q) {
                $q->whereNull('role')->orWhere('role', '!=', 'patient');
            })
            ->whereDoesntHave('roles', function ($rq) {
                $rq->where('name', 'patient');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role']);
    }

    /**
     * طباعة باجة وهوية الموظف الرسمية
     */
    public function badge(HrEmployee $employee)
    {
        $employee->load(['department', 'user']);
        $hospital = \App\Models\Hospital::first();

        return view('hr.employees.badge', compact('employee', 'hospital'));
    }

    public function destroyDocument(HrEmployeeDocument $document)
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
    private function processDocumentsUpload(Request $request, HrEmployee $employee)
    {
        if ($request->has('documents') && is_array($request->documents)) {
            foreach ($request->documents as $index => $docData) {
                if (isset($docData['file']) && $request->hasFile("documents.{$index}.file")) {
                    $file = $docData['file'];
                    $docType = $docData['type'] ?? 'مستمسك رسمي';
                    $extension = $file->getClientOriginalExtension();
                    $fileSize = $file->getSize();

                    $cleanType = preg_replace('/[^\p{L}\p{N}_]+/u', '_', trim($docType));
                    $fileName = "{$employee->employee_code}_{$cleanType}_".time()."_{$index}.{$extension}";

                    $storedPath = $file->storeAs("employees/documents/{$employee->employee_code}", $fileName, 'public');

                    HrEmployeeDocument::create([
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
    public function destroy(HrEmployee $employee)
    {
        $name = $employee->full_name;
        $employee->delete();

        return redirect()->route('hr.employees.index')
            ->with('success', "تم أرشفة/حذف ملف الموظف '{$name}' بنجاح.");
    }

    public function roster(HrEmployee $employee)
    {
        // Get all schedules for this employee for the current month
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $schedules = \App\Models\HrSchedule::where('hr_employee_id', $employee->id)
            ->whereBetween('shift_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->orderBy('shift_date')
            ->get();

        return view('hr.employees.roster', compact('employee', 'schedules', 'startOfMonth', 'endOfMonth'));
    }
}
