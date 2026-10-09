<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RadiologyStaffController extends Controller
{
    public function index(HttpRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || !$user->can('view radiology'))) {
            abort(403, 'غير مصرح لك بالوصول إلى قسم الأشعة والتصوير الطبي');
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $request->query('category', $userCategory ?? 'all');
        
        // إذا كان الموظف لديه دور محدد وليس مسؤولاً، يتم قفل الفئة على تخصصه
        if ($userCategory !== null && !$isAdmin) {
            $selectedCategory = $userCategory;
        }

        $activeTab = $request->query('tab', 'waiting'); // الافتراضي: طابور الانتظار
        $search = $request->query('search');
        $dateFilter = $request->query('date', 'today'); // 'today', 'all', or 'YYYY-MM-DD'

        $baseQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user', 'visit.department'])
            ->where('type', 'radiology')
            ->where('status', '!=', 'cancelled');

        // تطبيق فلتر التاريخ
        if ($dateFilter === 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($dateFilter !== 'all' && !empty($dateFilter)) {
            $baseQuery->whereDate('created_at', $dateFilter);
        }

        // تطبيق فلترة التخصص / الفئة
        if ($selectedCategory !== 'all' && !empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $baseQuery->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $baseQuery->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $baseQuery->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $baseQuery->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $baseQuery->where(function($q) {
                    $q->where('subtype', 'general')
                      ->orWhere('subtype', 'radiology')
                      ->orWhereNull('subtype');
                });
            }
        }

        // تطبيق البحث السريع
        if (!empty($search)) {
            $baseQuery->where(function($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('visit.patient.user', function($pq) use ($search) {
                      $pq->where('name', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('visit.patient', function($pq) use ($search) {
                      $pq->where('medical_number', 'LIKE', "%{$search}%")
                         ->orWhere('phone', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('visit.doctor.user', function($dq) use ($search) {
                      $dq->where('name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // حساب الإحصائيات (KPIs) للفئة المحددة
        $countsQuery = clone $baseQuery;
        $allCategoryRequests = $countsQuery->get();

        // فلترة خاصة بالإيكو إن كان موظف إيكو مخصص
        if ($selectedCategory === 'echo' && !$isAdmin && $userCategory === 'echo') {
            $allCategoryRequests = $allCategoryRequests->filter(function($req) use ($user) {
                $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
                return !isset($details['echo_staff_id']) || $details['echo_staff_id'] == $user->id;
            });
        }

        $stats = [
            'total' => $allCategoryRequests->count(),
            'waiting' => $allCategoryRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])->count(),
            'in_progress' => $allCategoryRequests->where('status', 'in_progress')->count(),
            'completed' => $allCategoryRequests->where('status', 'completed')->count(),
            'paid' => $allCategoryRequests->where('payment_status', 'paid')->count(),
            'unpaid' => $allCategoryRequests->where('payment_status', '!=', 'paid')->count(),
        ];

        // فلترة حسب التبويب النشط
        $tabFilteredRequests = $allCategoryRequests;
        if ($activeTab === 'waiting') {
            $tabFilteredRequests = $allCategoryRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled']);
        } elseif ($activeTab === 'in_progress') {
            $tabFilteredRequests = $allCategoryRequests->where('status', 'in_progress');
        } elseif ($activeTab === 'completed') {
            $tabFilteredRequests = $allCategoryRequests->where('status', 'completed');
        }

        // ترتيب الطلبات: المدفوع أولاً ثم حسب الأحدث
        $sortedRequests = $tabFilteredRequests->sortByDesc(function($r) {
            $priorityScore = $r->priority === 'emergency' ? 300 : ($r->priority === 'urgent' ? 200 : 100);
            $payScore = $r->payment_status === 'paid' ? 50 : 0;
            return $priorityScore + $payScore;
        })->values();

        // Pagination
        $perPage = 20;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $currentItems = $sortedRequests->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $requests = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $sortedRequests->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // جلب طلبات طوارئ الأشعة إن وجدت
        $emergencyRadiologyRequests = collect();
        if ($isAdmin || $user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo'])) {
            $emQuery = \App\Models\EmergencyRadiologyRequest::with(['emergency', 'patient.user', 'radiologyTypes'])
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('requested_at', 'desc');

            if ($selectedCategory === 'ultrasound') {
                $emQuery->whereHas('radiologyTypes', fn($q) => $q->where('subcategory', 'LIKE', '%سونار%'));
            } elseif ($selectedCategory === 'mri') {
                $emQuery->whereHas('radiologyTypes', fn($q) => $q->where('subcategory', 'LIKE', '%رنين%'));
            } elseif ($selectedCategory === 'echo') {
                $emQuery->whereHas('radiologyTypes', fn($q) => $q->where('subcategory', 'LIKE', '%إيكو%'));
            } elseif ($selectedCategory === 'radiology') {
                $emQuery->whereHas('radiologyTypes', fn($q) => $q->where('subcategory', 'LIKE', '%أشعة%')->orWhereNull('subcategory'));
            }

            if ($dateFilter === 'today') {
                $emQuery->whereDate('requested_at', today());
            }

            $emergencyRadiologyRequests = $emQuery->get();
        }

        $stats['emergency'] = $emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress'])->count();

        return view('radiology-staff.index', compact(
            'requests',
            'emergencyRadiologyRequests',
            'stats',
            'selectedCategory',
            'userCategory',
            'activeTab',
            'search',
            'dateFilter'
        ));
    }

    public function start(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || !$user->can('process radiology requests'))) {
            abort(403, 'غير مصرح لك ببدء فحص الأشعة');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        $request->status = 'in_progress';
        $request->save();

        return redirect()->route('radiology-staff.show', $request)->with('success', 'تم بدء الفحص بنجاح. يمكنك الآن كتابة التقرير والنتائج.');
    }

    private function getRadiologyCategoryForUser($user)
    {
        if ($user->hasRole('radiology_echo')) {
            return 'echo';
        }
        if ($user->hasRole('radiology_ultrasound')) {
            return 'ultrasound';
        }
        if ($user->hasRole('radiology_mri')) {
            return 'mri';
        }
        if ($user->hasAnyRole(['radiology_general', 'radiology_staff'])) {
            return 'radiology';
        }
        return null;
    }

    private function authorizeMedicalRequestForUser(MedicalRequest $request, $user)
    {
        $category = $this->getRadiologyCategoryForUser($user);
        if ($user->hasRole('admin')) {
            return;
        }
        if (!$category) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $requestSubtype = $request->subtype;

        if ($category === 'radiology') {
            if (!in_array($requestSubtype, ['general', 'radiology', null], true)) {
                abort(403, 'هذا الطلب خارج نطاق صلاحياتك');
            }
        } elseif ($category === 'echo') {
            // للإيكو: يجب أن يكون الطلب من نوع echo والموظف مخصص له
            if ($requestSubtype !== 'echo') {
                abort(403, 'هذا الطلب خارج نطاق صلاحياتك');
            }
            $details = $request->details;
            
            // التعامل مع double JSON encoding
            if (is_string($details)) {
                $details = json_decode($details, true);
                // إذا كان النتيجة string، فهذا يعني double encoding
                if (is_string($details)) {
                    $details = json_decode($details, true);
                }
            }
            
            $echoStaffId = $details['echo_staff_id'] ?? null;
            if ($echoStaffId && $echoStaffId != $user->id) {
                abort(403, 'هذا الطلب مخصص لموظف آخر');
            }
        } elseif ($requestSubtype !== $category) {
            abort(403, 'هذا الطلب خارج نطاق صلاحياتك');
        }
    }

    public function show(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || !$user->can('view radiology'))) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

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
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || !$user->can('process radiology requests'))) {
            abort(403, 'غير مصرح لك بتحديث ومعالجة طلب الأشعة');
        }

        $request->load(['visit']);

        // 1. اختيار أنواع الأشعة (pending_service_selection)
        if ($request->status === 'pending_service_selection' && $httpRequest->has('radiology_type_ids')) {
            $this->authorizeMedicalRequestForUser($request, $user);
            $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
            if (!is_array($details)) $details = [];

            $radiologyTypeIds = $httpRequest->radiology_type_ids;
            $details['radiology_type_ids'] = $radiologyTypeIds;
            $details['services_selected'] = true;
            $details['services_selected_at'] = now()->toDateTimeString();
            $details['services_selected_by'] = auth()->id();

            // تحديد subtype تلقائياً بناءً على أنواع الأشعة المختارة
            $selectedTypes = \App\Models\RadiologyType::whereIn('id', $radiologyTypeIds)->get();
            $subcategories = $selectedTypes->pluck('subcategory')->unique();
            
            // تحديد subtype بناءً على الفئات
            if ($subcategories->count() === 1) {
                $subcategory = $subcategories->first();
                if ($subcategory === 'سونار') {
                    $request->subtype = 'ultrasound';
                } elseif ($subcategory === 'الرنين') {
                    $request->subtype = 'mri';
                } elseif ($subcategory === 'إيكو') {
                    $request->subtype = 'echo';
                } else {
                    $request->subtype = 'general';
                }
            } else {
                // إذا كانت أنواع مختلطة، نجعلها general
                $request->subtype = 'general';
            }

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

            try {
                app(\App\Services\TelegramService::class)->sendResultsReady($request);
            } catch (\Throwable $te) {
                \Illuminate\Support\Facades\Log::warning('Telegram radiology notification error: ' . $te->getMessage());
            }

            $isDoctorVisit = $request->visit && (!empty($request->visit->doctor_id) || !empty($request->visit->appointment_id) || $request->visit->visit_type === 'checkup');
            if ($request->visit && !$isDoctorVisit) {
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
