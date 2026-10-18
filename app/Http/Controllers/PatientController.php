<?php
// app/Http/Controllers/PatientController.php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PatientController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('view patients'))) {
            abort(403, 'غير مصرح لك بعرض قائمة المرضى');
        }

        $search = trim($request->get('search', ''));
        $createdBy = $request->get('created_by', '');
        $insuranceType = $request->get('insurance_type', '');
        $gender = $request->get('gender', '');
        $dateFilter = $request->get('date_filter', '');
        $dateFrom = $request->get('date_from', '');
        $dateTo = $request->get('date_to', '');
        $completionStatus = $request->get('completion_status', '');

        $query = Patient::with(['user', 'creator', 'healthInsuranceCategory'])
            ->withCount(['appointments as total_appointments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone', 'like', "%{$search}%");
                })->orWhere('national_id', 'like', "%{$search}%")
                  ->orWhere('insurance_card_no', 'like', "%{$search}%")
                  ->orWhere('insurance_number', 'like', "%{$search}%");
            });
        }

        // فلتر الموظف المنشئ
        if ($createdBy !== '') {
            if ($createdBy === 'system') {
                $query->whereNull('created_by');
            } else {
                $query->where('created_by', $createdBy);
            }
        }

        // فلتر حالة اكتمال الملف
        if ($completionStatus !== '') {
            if ($completionStatus === 'incomplete') {
                $query->where(function ($q) {
                    $q->whereHas('user', function ($uq) {
                        $uq->whereNull('phone')->orWhere('phone', '')->orWhereNull('gender');
                    })->orWhereNull('national_id')->orWhere('national_id', '')
                      ->orWhere(function ($iq) {
                          $iq->where('insurance_type', 'hi')
                             ->where(function ($inq) {
                                 $inq->where(function ($sub) {
                                     $sub->whereNull('insurance_card_no')->orWhere('insurance_card_no', '');
                                 })->where(function ($sub) {
                                     $sub->whereNull('insurance_number')->orWhere('insurance_number', '');
                                 })->where(function ($sub) {
                                     $sub->whereNull('insurance_booklet_number')->orWhere('insurance_booklet_number', '');
                                 });
                             });
                      });
                });
            } elseif ($completionStatus === 'complete') {
                $query->whereHas('user', function ($uq) {
                    $uq->whereNotNull('phone')->where('phone', '!=', '')->whereNotNull('gender');
                })->whereNotNull('national_id')->where('national_id', '!=', '')
                  ->where(function ($iq) {
                      $iq->where('insurance_type', '!=', 'hi')
                         ->orWhere(function ($inq) {
                             $inq->whereNotNull('insurance_card_no')->where('insurance_card_no', '!=', '')
                                ->orWhereNotNull('insurance_number')->where('insurance_number', '!=', '')
                                ->orWhereNotNull('insurance_booklet_number')->where('insurance_booklet_number', '!=', '');
                         });
                  });
            }
        }

        // فلتر حالة الضمان
        if ($insuranceType !== '') {
            if ($insuranceType === 'hi') {
                $query->where('insurance_type', 'hi');
            } elseif ($insuranceType === 'moi') {
                $query->where('insurance_type', 'moi');
            } elseif ($insuranceType === 'covered') {
                $query->where(function ($q) {
                    $q->whereIn('insurance_type', ['hi', 'moi'])
                      ->orWhere('covered_by_insurance', true);
                });
            } elseif ($insuranceType === 'cash') {
                $query->where(function ($q) {
                    $q->where('insurance_type', 'none')
                      ->orWhereNull('insurance_type');
                })->where(function ($q) {
                    $q->where('covered_by_insurance', false)
                      ->orWhereNull('covered_by_insurance');
                });
            }
        }

        // فلتر الجنس
        if ($gender !== '') {
            $query->whereHas('user', function ($uq) use ($gender) {
                $uq->where('gender', $gender);
            });
        }

        // فلتر التاريخ
        if ($dateFilter !== '') {
            if ($dateFilter === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($dateFilter === 'yesterday') {
                $query->whereDate('created_at', Carbon::yesterday());
            } elseif ($dateFilter === 'this_week') {
                $query->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            } elseif ($dateFilter === 'this_month') {
                $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            }
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $patients = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->appends($request->query());

        // AJAX → أرجع JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'patients' => $patients->getCollection()->map(function($p) {
                    $insNum = $p->insurance_card_no ?: ($p->insurance_number ?: $p->insurance_booklet_number);
                    $missingFields = [];
                    if (empty($p->user?->phone)) $missingFields[] = 'phone';
                    if (empty($p->national_id)) $missingFields[] = 'national_id';
                    if (empty($p->user?->gender)) $missingFields[] = 'gender';
                    if (empty($p->age)) $missingFields[] = 'age';
                    if ($p->insurance_type === 'hi' && empty($insNum)) $missingFields[] = 'insurance_number';
                    $isIncomplete = count($missingFields) > 0;

                    return [
                        'id'                   => $p->id,
                        'user'                 => $p->user ? [
                            'name'   => $p->user->name,
                            'phone'  => $p->user->phone,
                            'email'  => $p->user->email,
                            'gender' => $p->user->gender,
                        ] : null,
                        'age'                  => $p->age,
                        'dob'                  => $p->user?->date_of_birth ? $p->user->date_of_birth->format('Y-m-d') : null,
                        'blood_type'           => $p->blood_type,
                        'emergency_contact'    => $p->emergency_contact,
                        'national_id'          => $p->national_id,
                        'insurance_type'       => $p->insurance_type,
                        'covered_by_insurance' => (bool)($p->insurance_type !== 'none' || $p->covered_by_insurance),
                        'insurance_number'     => $insNum,
                        'insurance_category'   => $p->healthInsuranceCategory ? $p->healthInsuranceCategory->name : null,
                        'copay_percentage'     => $p->copay_percentage,
                        'creator_name'         => $p->creator ? $p->creator->name : null,
                        'created_at_formatted' => $p->created_at ? $p->created_at->format('Y-m-d h:i A') : null,
                        'created_at_diff'      => $p->created_at ? $p->created_at->diffForHumans() : null,
                        'total_appointments'   => $p->total_appointments,
                        'last_visit_date'      => $p->getLastVisitDate()
                            ? $p->getLastVisitDate()->format('Y-m-d') : null,
                        'is_incomplete'        => $isIncomplete,
                        'missing_fields'       => $missingFields,
                    ];
                })->values(),
                'pagination'   => $patients->links('vendor.pagination.bootstrap-5')->toHtml(),
                'current_page' => $patients->currentPage(),
                'last_page'    => $patients->lastPage(),
                'total'        => $patients->total(),
            ]);
        }

        // احتساب مؤشرات الأداء السريعة (KPIs)
        $totalCount = Patient::count();
        $hiCount = Patient::where('insurance_type', 'hi')->count();
        $incompleteCount = Patient::where(function ($q) {
            $q->whereHas('user', function ($uq) {
                $uq->whereNull('phone')->orWhere('phone', '')->orWhereNull('gender');
            })->orWhereNull('national_id')->orWhere('national_id', '')
              ->orWhere(function ($iq) {
                  $iq->where('insurance_type', 'hi')
                     ->where(function ($inq) {
                         $inq->where(function ($sub) {
                             $sub->whereNull('insurance_card_no')->orWhere('insurance_card_no', '');
                         })->where(function ($sub) {
                             $sub->whereNull('insurance_number')->orWhere('insurance_number', '');
                         })->where(function ($sub) {
                             $sub->whereNull('insurance_booklet_number')->orWhere('insurance_booklet_number', '');
                         });
                     });
              });
        })->count();
        $completeCount = max(0, $totalCount - $incompleteCount);

        $kpis = [
            'total' => $totalCount,
            'hi' => $hiCount,
            'incomplete' => $incompleteCount,
            'complete' => $completeCount,
        ];

        // جلب قائمة الموظفين الذين أدخلوا مرضى مع موظفي الاستقبال والإدارة لفلترة العرض
        $creatorIds = Patient::whereNotNull('created_by')->distinct()->pluck('created_by')->filter()->toArray();
        $creators = User::whereIn('id', $creatorIds)
            ->orWhereHas('roles', function ($r) {
                $r->whereIn('name', ['admin', 'admin-hsop', 'hospital_admin', 'receptionist']);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('patients.index', compact('patients', 'creators', 'kpis'));
    }

    public function create()
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('create patients'))) {
            abort(403, 'غير مصرح لك بإضافة مريض جديد');
        }

        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        $healthInsuranceCategories = \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('patients.create', compact('countries', 'governorates', 'iraq_id', 'healthInsuranceCategories'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('create patients'))) {
            abort(403, 'غير مصرح لك بإضافة مريض جديد');
        }

        // تنظيف وتحويل أرقام البطاقة الموحدة (دعم الأرقام المشرقية وإزالة المسافات)
        if ($request->filled('national_id')) {
            $nid = $request->input('national_id');
            $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $nid = str_replace($arabicNumbers, $englishNumbers, $nid);
            $request->merge(['national_id' => trim($nid)]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => ['required', 'regex:/^[0-9]+$/', 'unique:patients,national_id'],
            'mother_name' => 'nullable|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'marital_status' => 'required|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_type' => 'nullable|in:none,moi,hi',
            'health_insurance_category_id' => 'nullable|exists:health_insurance_categories,id',
            'insurance_card_no' => 'nullable|string|max:255',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'النوع مطلوب',
            'marital_status.required' => 'الحالة الاجتماعية مطلوبة',
            'national_id.required' => 'رقم البطاقة الموحدة مطلوب',
            'national_id.regex' => 'رقم البطاقة الموحدة يجب أن يحتوي على أرقام فقط بدون أحرف أو رموز',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'national_id.unique' => 'رقم البطاقة الموحدة مستخدم من قبل لمريض آخر',
        ]);

        // التحقق من عدم تكرار الاسم واسم الأم في حال تم إدخال اسم الأم
        if ($request->filled('mother_name')) {
            $existingPatient = Patient::whereHas('user', function($query) use ($request) {
                $query->where('name', $request->name);
            })->where('mother_name', $request->mother_name)->first();

            if ($existingPatient) {
                return back()->withErrors([
                    'duplicate_patient' => 'يوجد مريض آخر بنفس الاسم واسم الأم. يرجى التأكد من صحة البيانات.'
                ])->withInput();
            }
        }
        $email = $request->email;
        if (!$email) {
            $email = 'patient.' . $request->phone . '.' . time() . '@hospital.local';
        }
        // إنشاء مستخدم للمريض
        $user = User::create([
            'name' => $request->name,
            'email' => $email,
            'password' => Hash::make('password'), // كلمة مرور افتراضية
            'role' => 'patient',
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        $insuranceType = $request->input('insurance_type', 'none');
        $isCovered = ($insuranceType !== 'none') ? 1 : ($request->input('covered_by_insurance', 0) ? 1 : 0);
        $hiCategoryId = ($insuranceType === 'hi') ? $request->input('health_insurance_category_id') : null;

        // إنشاء سجل المريض
        $patient = Patient::create([
            'user_id' => $user->id,
            'created_by' => auth()->id(),
            'emergency_contact' => $request->emergency_contact,
            'blood_type' => $request->blood_type,
            'medical_history' => $request->medical_history,
            'allergies' => $request->allergies,
            'current_medications' => $request->current_medications,
            'insurance_company' => $request->insurance_company,
            'insurance_number' => $request->insurance_number,
            'insurance_type' => $insuranceType,
            'health_insurance_category_id' => $hiCategoryId,
            'insurance_card_no' => $request->insurance_card_no ?: $request->insurance_booklet_number,
            'copay_percentage' => $request->filled('copay_percentage') ? (float)$request->copay_percentage : ($insuranceType !== 'none' ? 15.00 : 0.00),
            'national_id' => $request->national_id,
            'first_visit_date' => now(),
            'notes' => $request->notes,
            'mother_name' => $request->mother_name,
            'country_id' => $request->country,
            'governorate' => $request->governorate,
            'district' => $request->district,
            'neighborhood' => $request->neighborhood,
            'marital_status' => $request->marital_status,
            'covered_by_insurance' => $isCovered,
            'insurance_booklet_number' => $request->insurance_booklet_number ?: $request->insurance_card_no,
        ]);

        if ($request->input('action') === 'save_and_book') {
            return redirect()->route('inquiry.create', ['patient_id' => $patient->id])
                ->with('success', 'تم فتح إضبارة المريض بنجاح، يمكنك الآن اختيار الخدمة المطلوبة (كشفية، مختبر، أشعة، صيدلية).');
        }

        return redirect()->route('patients.index')
            ->with('success', 'تم إضافة المريض بنجاح');
    }

    public function show(Patient $patient)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('view patients'))) {
            abort(403, 'غير مصرح لك بعرض بيانات المريض');
        }

        $patient->load(['user', 'appointments.doctor.user', 'appointments.department']);
        
        return view('patients.show', compact('patient'));
    }

    public function edit(Patient $patient)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('edit patients'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات المريض');
        }

        $countries = \App\Models\Country::all();
        $governorates = \App\Models\Governorate::all();
        $iraq = \App\Models\Country::where('name', 'العراق')->first();
        $iraq_id = $iraq ? $iraq->id : null;
        $healthInsuranceCategories = \App\Models\HealthInsuranceCategory::where('is_active', true)->orderBy('sort_order')->get();
        return view('patients.edit', compact('patient', 'countries', 'governorates', 'iraq_id', 'healthInsuranceCategories'));
    }

    public function update(Request $request, Patient $patient)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('edit patients'))) {
            abort(403, 'غير مصرح لك بتعديل بيانات المريض');
        }

        // تنظيف وتحويل أرقام البطاقة الموحدة
        if ($request->filled('national_id')) {
            $nid = $request->input('national_id');
            $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $nid = str_replace($arabicNumbers, $englishNumbers, $nid);
            $request->merge(['national_id' => trim($nid)]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users,email,' . $patient->user_id,
            'phone' => 'required|string|max:15',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'emergency_contact' => 'nullable|string',
            'blood_type' => 'nullable|string|max:10',
            'national_id' => ['required', 'regex:/^[0-9]+$/', 'unique:patients,national_id,' . $patient->id],
            'mother_name' => 'nullable|string|max:255',
            'marital_status' => 'required|in:أعزب,متزوج,مطلق,أرمل',
            'covered_by_insurance' => 'nullable|in:0,1',
            'insurance_type' => 'nullable|in:none,moi,hi',
            'health_insurance_category_id' => 'nullable|exists:health_insurance_categories,id',
            'insurance_card_no' => 'nullable|string|max:255',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'insurance_booklet_number' => 'nullable|string|max:255',
            'country' => 'nullable|exists:countries,id',
            'governorate' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
        ], [
            'name.required' => 'الاسم مطلوب',
            'phone.required' => 'رقم الهاتف مطلوب',
            'date_of_birth.required' => 'تاريخ الميلاد مطلوب',
            'gender.required' => 'النوع مطلوب',
            'marital_status.required' => 'الحالة الاجتماعية مطلوبة',
            'national_id.required' => 'رقم البطاقة الموحدة مطلوب',
            'national_id.regex' => 'رقم البطاقة الموحدة يجب أن يحتوي على أرقام فقط بدون أحرف أو رموز',
            'email.unique' => 'البريد الإلكتروني مستخدم من قبل',
            'national_id.unique' => 'رقم البطاقة الموحدة مستخدم من قبل لمريض آخر',
        ]);

        // التحقق من عدم تكرار الاسم واسم الأم (استثناء المريض الحالي) في حال تم إدخال اسم الأم
        if ($request->filled('mother_name')) {
            $existingPatient = Patient::whereHas('user', function($query) use ($request) {
                $query->where('name', $request->name);
            })->where('mother_name', $request->mother_name)
              ->where('id', '!=', $patient->id)
              ->first();

            if ($existingPatient) {
                return back()->withErrors([
                    'duplicate_patient' => 'يوجد مريض آخر بنفس الاسم واسم الأم. يرجى التأكد من صحة البيانات.'
                ])->withInput();
            }
        }

        // تحديث بيانات المستخدم
        $email = $request->email;
        if (!$email) {
            $email = 'patient.' . $request->phone . '.' . time() . '@hospital.local';
        }
        $patient->user->update([
            'name' => $request->name,
            'email' => $email,
            'phone' => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        $insuranceType = $request->input('insurance_type', 'none');
        $isCovered = ($insuranceType !== 'none') ? 1 : ($request->input('covered_by_insurance', 0) ? 1 : 0);
        $hiCategoryId = ($insuranceType === 'hi') ? $request->input('health_insurance_category_id') : null;

        // تحديث بيانات المريض
        $patient->update([
            'emergency_contact' => $request->emergency_contact,
            'blood_type' => $request->blood_type,
            'national_id' => $request->national_id,
            'mother_name' => $request->mother_name,
            'marital_status' => $request->marital_status,
            'covered_by_insurance' => $isCovered,
            'insurance_type' => $insuranceType,
            'health_insurance_category_id' => $hiCategoryId,
            'insurance_card_no' => $request->insurance_card_no ?: $request->insurance_booklet_number,
            'copay_percentage' => $request->filled('copay_percentage') ? (float)$request->copay_percentage : ($patient->copay_percentage ?? ($insuranceType !== 'none' ? 15.00 : 0.00)),
            'insurance_booklet_number' => $request->insurance_booklet_number ?: $request->insurance_card_no,
            'country_id' => $request->country,
            'governorate' => $request->governorate,
            'district' => $request->district,
            'neighborhood' => $request->neighborhood
        ]);

        $returnUrl = $request->input('return_url');
        if ($returnUrl && !str_contains($returnUrl, '/edit')) {
            return redirect()->to($returnUrl)->with('success', 'تم تحديث بيانات المريض بنجاح');
        }

        return redirect()->route('patients.show', $patient)
            ->with('success', 'تم تحديث بيانات المريض بنجاح');
    }

    /**
     * تحديث سريع وخفيف لإكمال بيانات المريض (الهاتف، رقم الضمان، الجنس، الرقم الوطني) عبر AJAX
     */
    public function quickUpdate(Request $request, Patient $patient)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('edit patients'))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بتعديل بيانات المريض'], 403);
        }

        // تنظيف رقم البطاقة الموحدة
        if ($request->filled('national_id')) {
            $nid = $request->input('national_id');
            $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
            $nid = str_replace($arabicNumbers, $englishNumbers, $nid);
            $request->merge(['national_id' => trim($nid)]);
        }

        $request->validate([
            'phone' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'national_id' => ['nullable', 'regex:/^[0-9]+$/', 'unique:patients,national_id,' . $patient->id],
            'insurance_number' => 'nullable|string|max:100',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:130',
        ], [
            'phone.required' => 'رقم الهاتف مطلوب',
            'gender.required' => 'تحديد الجنس مطلوب',
            'national_id.unique' => 'رقم البطاقة الموحدة مستخدم من قبل لمريض آخر',
            'national_id.regex' => 'رقم البطاقة الموحدة يجب أن يحتوي على أرقام فقط',
            'age.integer' => 'العمر يجب أن يكون رقماً صحيحاً',
        ]);

        // تحديث جدول users
        $userData = [
            'phone' => trim($request->input('phone')),
            'gender' => $request->input('gender'),
        ];
        if ($request->filled('date_of_birth')) {
            $userData['date_of_birth'] = $request->input('date_of_birth');
        } elseif ($request->filled('age') && is_numeric($request->input('age')) && (int)$request->input('age') >= 0) {
            $ageInt = (int)$request->input('age');
            $userData['date_of_birth'] = Carbon::now()->subYears($ageInt)->format('Y-07-01');
        }

        if ($patient->user) {
            $patient->user->update($userData);
        }

        // تحديث جدول patients
        $patientData = [];
        if ($request->filled('national_id')) {
            $patientData['national_id'] = $request->input('national_id');
        }
        if ($request->filled('insurance_number')) {
            $ins = trim($request->input('insurance_number'));
            $patientData['insurance_card_no'] = $ins;
            $patientData['insurance_number'] = $ins;
            $patientData['insurance_booklet_number'] = $ins;
        }
        if (!empty($patientData)) {
            $patient->update($patientData);
        }

        $patient->load(['user', 'healthInsuranceCategory']);

        return response()->json([
            'success' => true,
            'message' => 'تم حفظ وإكمال بيانات المريض بنجاح!',
            'patient' => [
                'id' => $patient->id,
                'name' => $patient->user?->name,
                'phone' => $patient->user?->phone,
                'gender' => $patient->user?->gender,
                'age' => $patient->age,
                'date_of_birth' => $patient->user?->date_of_birth ? $patient->user->date_of_birth->format('Y-m-d') : null,
                'national_id' => $patient->national_id,
                'insurance_number' => $patient->insurance_card_no ?: ($patient->insurance_number ?: $patient->insurance_booklet_number),
            ]
        ]);
    }

    public function destroy(Patient $patient)
    {
        $user = auth()->user();
        if (!$user || (!$user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && !$user->can('delete patients'))) {
            abort(403, 'غير مصرح لك بحذف المريض');
        }

        $patient->delete();
        // يمكنك اختيار حذف المستخدم أيضاً أو تركه
        // $patient->user->delete();

        return redirect()->route('patients.index')
            ->with('success', 'تم حذف المريض بنجاح');
    }

    // بحث المرضى (يُرجع JSON دائماً - مخصص لطلبات AJAX)
    public function search(Request $request)
    {
        $search = trim($request->get('search', ''));

        $query = Patient::with('user')->withCount(['appointments as total_appointments']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                              ->orWhere('email', 'like', "%{$search}%")
                              ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhere('national_id', 'like', "%{$search}%");
            });
        }

        $patients = $query->latest()->paginate(15)->appends($request->query());

        $patientsData = $patients->getCollection()->map(function ($patient) {
            return [
                'id'                  => $patient->id,
                'user'                => $patient->user ? [
                    'name'   => $patient->user->name,
                    'phone'  => $patient->user->phone,
                    'email'  => $patient->user->email,
                    'gender' => $patient->user->gender,
                ] : null,
                'age'                 => $patient->age,
                'blood_type'          => $patient->blood_type,
                'emergency_contact'   => $patient->emergency_contact,
                'national_id'         => $patient->national_id,
                'total_appointments'  => $patient->total_appointments,
                'last_visit_date'     => $patient->getLastVisitDate()
                    ? $patient->getLastVisitDate()->format('Y-m-d')
                    : null,
            ];
        });

        return response()->json([
            'patients'     => $patientsData->values(),
            'pagination'   => $patients->links()->toHtml(),
            'current_page' => $patients->currentPage(),
            'last_page'    => $patients->lastPage(),
            'total'        => $patients->total(),
        ]);
    }
}