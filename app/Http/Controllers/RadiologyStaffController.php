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

        // جلب المريض الحالي قيد الفحص بالداخل والمريض قيد النداء
        $currentPatient = $allCategoryRequests->where('status', 'in_progress')->first();
        $callingPatient = $allCategoryRequests->where('status', 'calling')->first();

        $waitingRequests = $allCategoryRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])
            ->sortBy(function($r) {
                $prio = $r->priority === 'emergency' ? 1 : ($r->priority === 'urgent' ? 2 : 3);
                $pay = $r->payment_status === 'paid' ? 1 : 2;
                return $prio . '_' . $pay . '_' . $r->id;
            })->values();

        $inProgressRequests = $allCategoryRequests->where('status', 'in_progress')->values();
        $completedRequests = $allCategoryRequests->where('status', 'completed')->sortByDesc('updated_at')->values();

        return view('radiology-staff.index', compact(
            'requests',
            'waitingRequests',
            'inProgressRequests',
            'completedRequests',
            'emergencyRadiologyRequests',
            'stats',
            'selectedCategory',
            'userCategory',
            'activeTab',
            'search',
            'dateFilter',
            'currentPatient',
            'callingPatient'
        ));
    }

    public function queueStatus(HttpRequest $httpRequest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || !$user->can('view radiology'))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى قسم الأشعة'], 403);
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $httpRequest->query('category', $userCategory ?? 'all');
        if ($userCategory !== null && !$isAdmin) {
            $selectedCategory = $userCategory;
        }

        $baseQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user', 'visit.department', 'visit.appointment'])
            ->where('type', 'radiology')
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', today());

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

        $allRequests = $baseQuery->get();

        // 1. Current calling or in-progress patient
        $currentCalling = $allRequests->where('status', 'calling')->first();
        $currentExamining = $allRequests->where('status', 'in_progress')->first();
        $currentPatientModel = $currentCalling ?: $currentExamining;

        $currentPatient = null;
        if ($currentPatientModel) {
            $p = $currentPatientModel->visit?->patient;
            $currentPatient = [
                'id' => $currentPatientModel->id,
                'visit_id' => $currentPatientModel->visit_id,
                'queue_number' => $currentPatientModel->visit?->appointment?->queue_number ?? $currentPatientModel->id,
                'name' => $p?->name ?? $p?->user?->name ?? 'مريض',
                'status' => $currentPatientModel->status,
                'status_text' => $currentPatientModel->status === 'in_progress' ? 'قيد الفحص داخل الغرفة' : 'قيد النداء الآن 📢',
                'radiology_names' => $currentPatientModel->radiology_names ?: [$currentPatientModel->description ?: 'فحص تصوير'],
                'doctor_name' => $currentPatientModel->visit?->doctor?->user?->name ?? 'الاستشارية',
                'called_at' => $currentPatientModel->details['called_at'] ?? $currentPatientModel->updated_at->format('H:i'),
            ];
        }

        // 2. Waiting List
        $waitingList = $allRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])
            ->sortBy(function($r) {
                $prio = $r->priority === 'emergency' ? 1 : ($r->priority === 'urgent' ? 2 : 3);
                $pay = $r->payment_status === 'paid' ? 1 : 2;
                return $prio . '_' . $pay . '_' . $r->id;
            })
            ->values()
            ->map(function($r, $idx) {
                $p = $r->visit?->patient;
                return [
                    'id' => $r->id,
                    'visit_id' => $r->visit_id,
                    'turn_index' => $idx + 1,
                    'queue_number' => $r->visit?->appointment?->queue_number ?? ($idx + 1),
                    'name' => $p?->name ?? $p?->user?->name ?? 'مريض',
                    'gender' => $p?->gender === 'male' ? 'ذكر' : ($p?->gender === 'female' ? 'أنثى' : ''),
                    'age' => $p?->age,
                    'medical_number' => $p?->medical_number,
                    'radiology_names' => $r->radiology_names ?: [$r->description ?: 'فحص تصوير'],
                    'subtype' => $r->subtype,
                    'doctor_name' => $r->visit?->doctor?->user?->name ?? 'الاستشارية',
                    'department_name' => $r->visit?->department?->name ?? 'العيادات',
                    'created_time' => $r->created_at ? $r->created_at->format('H:i') : '—',
                    'is_paid' => $r->payment_status === 'paid',
                    'status' => $r->status,
                    'priority' => $r->priority,
                ];
            });

        // 3. In-Progress List
        $inProgressList = $allRequests->where('status', 'in_progress')
            ->values()
            ->map(function($r) {
                $p = $r->visit?->patient;
                return [
                    'id' => $r->id,
                    'visit_id' => $r->visit_id,
                    'queue_number' => $r->visit?->appointment?->queue_number ?? $r->id,
                    'name' => $p?->name ?? $p?->user?->name ?? 'مريض',
                    'radiology_names' => $r->radiology_names ?: [$r->description ?: 'فحص تصوير'],
                    'doctor_name' => $r->visit?->doctor?->user?->name ?? 'الاستشارية',
                    'started_time' => $r->details['started_at'] ?? $r->updated_at->format('H:i'),
                ];
            });

        // 4. Completed List
        $completedList = $allRequests->where('status', 'completed')
            ->sortByDesc('updated_at')
            ->take(15)
            ->values()
            ->map(function($r) {
                $p = $r->visit?->patient;
                return [
                    'id' => $r->id,
                    'visit_id' => $r->visit_id,
                    'queue_number' => $r->visit?->appointment?->queue_number ?? $r->id,
                    'name' => $p?->name ?? $p?->user?->name ?? 'مريض',
                    'radiology_names' => $r->radiology_names ?: [$r->description ?: 'فحص تصوير'],
                    'doctor_name' => $r->visit?->doctor?->user?->name ?? 'الاستشارية',
                    'completed_time' => $r->updated_at->format('H:i'),
                ];
            });

        $stats = [
            'total' => $allRequests->count(),
            'waiting' => $allRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])->count(),
            'in_progress' => $allRequests->where('status', 'in_progress')->count(),
            'completed' => $allRequests->where('status', 'completed')->count(),
            'paid' => $allRequests->where('payment_status', 'paid')->count(),
            'unpaid' => $allRequests->where('payment_status', '!=', 'paid')->count(),
        ];

        $roomNumber = $user->doctor?->current_room ?? ($selectedCategory === 'ultrasound' ? 11 : null);

        return response()->json([
            'success' => true,
            'current_patient' => $currentPatient,
            'waiting_list' => $waitingList,
            'in_progress_list' => $inProgressList,
            'completed_list' => $completedList,
            'stats' => $stats,
            'room_number' => $roomNumber,
        ]);
    }

    public function callNext(HttpRequest $httpRequest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('process radiology requests') && !$user->can('view radiology')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المريض'], 403);
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $httpRequest->query('category', $userCategory ?? 'all');
        if ($userCategory !== null && !$isAdmin) {
            $selectedCategory = $userCategory;
        }

        $query = MedicalRequest::with(['visit.patient.user', 'visit.appointment'])
            ->where('type', 'radiology')
            ->whereIn('status', ['pending', 'scheduled'])
            ->where(function($q) {
                $q->where('payment_status', 'paid')
                  ->orWhere('priority', 'emergency');
            })
            ->whereDate('created_at', today());

        if ($selectedCategory !== 'all' && !empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $query->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $query->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $query->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $query->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $query->where(function($q) {
                    $q->where('subtype', 'general')
                      ->orWhere('subtype', 'radiology')
                      ->orWhereNull('subtype');
                });
            }
        }

        $nextRequest = $query->orderByRaw("CASE WHEN priority = 'emergency' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
            ->orderBy('id', 'asc')
            ->first();

        if (!$nextRequest) {
            return response()->json(['success' => false, 'message' => 'لا يوجد مرضى بانتظار الفحص في الطابور حالياً.'], 404);
        }

        return $this->call($nextRequest);
    }

    public function recall(HttpRequest $httpRequest)
    {
        $currentCalling = MedicalRequest::where('type', 'radiology')
            ->where('status', 'calling')
            ->whereDate('created_at', today())
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$currentCalling) {
            return response()->json(['success' => false, 'message' => 'لا توجد مناداة جارية لإعادة استدعائها.'], 404);
        }

        return $this->call($currentCalling);
    }

    public function skip(HttpRequest $httpRequest)
    {
        $currentCalling = MedicalRequest::where('type', 'radiology')
            ->where('status', 'calling')
            ->whereDate('created_at', today())
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$currentCalling) {
            return response()->json(['success' => false, 'message' => 'لا توجد مناداة جارية لتخطيها.'], 404);
        }

        $currentCalling->status = 'pending';
        $currentCalling->save();

        if ($currentCalling->visit && $currentCalling->visit->appointment) {
            $currentCalling->visit->appointment->status = 'scheduled';
            $currentCalling->visit->appointment->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تأخير دور المريض ونقله لآخر طابور الانتظار.'
        ]);
    }

    public function call(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('process radiology requests') && !$user->can('view radiology')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المريض.'], 403);
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        if ($request->payment_status !== 'paid' && $request->priority !== 'emergency') {
            return response()->json([
                'success' => false, 
                'message' => 'تنبيه: لا يمكن مناداة المريض قبل تسديد رسوم الفحص في الكاشير أولاً.'
            ], 422);
        }

        // إلغاء نداء أي مريض سابق وجعله pending
        MedicalRequest::where('type', 'radiology')
            ->where('status', 'calling')
            ->where('id', '!=', $request->id)
            ->update(['status' => 'pending']);

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($details)) $details = [];
        $details['called_at'] = now()->toDateTimeString();
        $details['called_by'] = $user->id;

        $request->details = $details;
        $request->status = 'calling';
        $request->save();

        // مزامنة الموعد المرتبط لطابور الطبيب وشاشة التلفاز والاستقبال
        // مزامنة الموعد المرتبط لطابور الطبيب وشاشة التلفاز والاستقبال
        \App\Models\Appointment::whereDate('appointment_date', today())
            ->where('status', 'calling')
            ->update(['status' => 'confirmed']);

        $queueNumber = $request->id;

        if ($request->visit) {
            $appointment = $request->visit->appointment ?: ($request->visit->appointment_id ? \App\Models\Appointment::find($request->visit->appointment_id) : null);
            if ($appointment) {
                $appointment->status = 'calling';
                $appointment->called_at = now();
                $appointment->save();
                $queueNumber = $appointment->queue_number ?: $appointment->id;

                try {
                    app(\App\Services\TelegramService::class)->sendTurnAlert($appointment);
                } catch (\Throwable $te) {}
            }
        }

        $patientName = $request->visit?->patient?->name ?? $request->visit?->patient?->user?->name ?? 'المريض';

        return response()->json([
            'success' => true,
            'message' => 'تمت المناداة على المريض (' . $patientName . ') بنجاح.',
            'request_id' => $request->id,
            'patient_name' => $patientName,
            'queue_number' => $queueNumber,
            'called_at' => now()->format('H:i:s'),
        ]);
    }

    public function start(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (!$isAdmin && (!$user || (!$user->can('process radiology requests') && !$user->can('view radiology')))) {
            abort(403, 'غير مصرح لك ببدء فحص الأشعة');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (!is_array($details)) $details = [];
        $details['started_at'] = now()->toDateTimeString();
        $details['started_by'] = $user->id;

        $request->details = $details;
        $request->status = 'in_progress';
        $request->save();

        // مزامنة الزيارة والموعد
        if ($request->visit) {
            $request->visit->update(['status' => 'in_progress']);
            if ($request->visit->appointment_id) {
                \App\Models\Appointment::where('id', $request->visit->appointment_id)->update([
                    'status' => 'in_consultation'
                ]);
            }
        }

        return redirect()->route('radiology-staff.show', $request)->with('success', 'تم إدخال المريض وبدء الفحص بنجاح. يمكنك الآن كتابة التقرير والنتائج.');
    }

    private function getRadiologyCategoryForUser($user)
    {
        if (!$user) return null;
        if ($user->hasRole('radiology_echo')) {
            return 'echo';
        }
        if ($user->hasRole('radiology_ultrasound')) {
            return 'ultrasound';
        }
        if ($user->hasRole('radiology_mri')) {
            return 'mri';
        }
        if ($user->hasRole('radiology_ct')) {
            return 'ct';
        }
        if ($user->hasAnyRole(['radiology_general', 'radiology_staff'])) {
            return 'radiology';
        }
        if ($user->hasRole('doctor') && $user->doctor) {
            $spec = $user->doctor->specialization ?? '';
            $type = $user->doctor->type ?? '';
            if (mb_stripos($spec, 'سونار') !== false || $type === 'sonar') {
                return 'ultrasound';
            }
            if (mb_stripos($spec, 'إيكو') !== false || mb_stripos($spec, 'echo') !== false) {
                return 'echo';
            }
        }
        return null;
    }

    private function authorizeMedicalRequestForUser(MedicalRequest $request, $user)
    {
        if (!$user) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $isAdmin = $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if ($isAdmin) {
            return;
        }

        $category = $this->getRadiologyCategoryForUser($user);
        if (!$category) {
            if ($user->can('process radiology requests') || $user->can('view radiology')) {
                return;
            }
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
