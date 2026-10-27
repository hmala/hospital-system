<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use App\Models\NicuMedicalOrder;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RadiologyStaffController extends Controller
{
    public function index(HttpRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('view radiology'))) {
            abort(403, 'غير مصرح لك بالوصول إلى قسم الأشعة والتصوير الطبي');
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $request->query('category', $userCategory ?? 'all');

        // إذا كان الموظف لديه دور محدد وليس مسؤولاً، يتم قفل الفئة على تخصصه
        if ($userCategory !== null && ! $isAdmin) {
            $selectedCategory = $userCategory;
        }

        $rawTab = $request->query('tab');
        $rawSource = $request->query('source');
        if ($rawSource) {
            $sourceFilter = $rawSource;
        } elseif ($rawTab === 'emergency') {
            $sourceFilter = 'emergency';
        } elseif ($rawTab === 'pending') {
            $sourceFilter = 'pending';
        } else {
            $sourceFilter = 'consultation'; // الافتراضي مثل المختبر: الاستشارية
        }

        $activeTab = $rawTab ?: ($sourceFilter === 'emergency' ? 'emergency' : ($sourceFilter === 'pending' ? 'pending' : ($sourceFilter === 'direct' ? 'direct' : 'station')));
        if ($activeTab === 'waiting') {
            $activeTab = 'station';
        }

        $statusFilter = $request->query('status');
        if (! $statusFilter) {
            if ($activeTab === 'completed') {
                $statusFilter = 'completed';
            } elseif ($activeTab === 'in-progress' || $activeTab === 'inprogress') {
                $statusFilter = 'in_progress';
            } elseif ($activeTab === 'station') {
                $statusFilter = 'all';
            } else {
                $statusFilter = 'all';
            }
        }

        $dateFilter = $request->query('date', 'today'); // 'today', 'yesterday', 'week', 'month', 'all', or 'YYYY-MM-DD'
        if ($request->filled('custom_date')) {
            $dateFilter = $request->query('custom_date');
        }
        $search = $request->query('search');

        $baseQuery = MedicalRequest::with([
                'visit.patient.user', 
                'visit.doctor.user', 
                'visit.department',
                'incubatorReservation.patient.user',
                'incubatorReservation.incubator',
                'incubatorReservation.doctor.user'
            ])
            ->where('type', 'radiology')
            ->whereNotIn('status', ['cancelled', 'postponed']);

        // تطبيق فلتر التاريخ الموحد
        if ($dateFilter === 'today') {
            $baseQuery->whereDate('created_at', today());
        } elseif ($dateFilter === 'yesterday') {
            $baseQuery->whereDate('created_at', today()->subDay());
        } elseif ($dateFilter === 'week') {
            $baseQuery->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($dateFilter === 'month') {
            $baseQuery->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year);
        } elseif ($dateFilter !== 'all' && ! empty($dateFilter)) {
            $baseQuery->whereDate('created_at', $dateFilter);
        }

        // تطبيق فلترة التخصص / الفئة
        if ($selectedCategory !== 'all' && ! empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $baseQuery->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $baseQuery->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $baseQuery->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $baseQuery->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $baseQuery->where(function ($q) {
                    $q->where('subtype', 'general')
                        ->orWhere('subtype', 'radiology')
                        ->orWhereNull('subtype');
                });
            }
        }

        // تطبيق البحث السريع
        if (! empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('visit.patient.user', function ($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('visit.patient', function ($pq) use ($search) {
                        $pq->where('medical_number', 'LIKE', "%{$search}%")
                            ->orWhere('phone', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('visit.doctor.user', function ($dq) use ($search) {
                        $dq->where('name', 'LIKE', "%{$search}%");
                    })
                    ->orWhereHas('incubatorReservation', function ($rq) use ($search) {
                        $rq->where('baby_name', 'like', "%{$search}%")
                            ->orWhere('id', 'like', "%{$search}%")
                            ->orWhereHas('patient.user', fn ($puq) => $puq->where('name', 'like', "%{$search}%"))
                            ->orWhereHas('doctor.user', fn ($duq) => $duq->where('name', 'like', "%{$search}%"));
                    });
            });
        }

        // حساب الإحصائيات (KPIs) للفئة المحددة
        $countsQuery = clone $baseQuery;
        $allCategoryRequests = $countsQuery->get();

        // فلترة خاصة بالإيكو إن كان موظف إيكو مخصص
        if ($selectedCategory === 'echo' && ! $isAdmin && $userCategory === 'echo') {
            $allCategoryRequests = $allCategoryRequests->filter(function ($req) use ($user) {
                $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;

                return ! isset($details['echo_staff_id']) || $details['echo_staff_id'] == $user->id;
            });
        }

        // تصنيف الطلبات حسب المصدر / القسم المحوِل (مثل المختبر)
        $consultationRequests = $allCategoryRequests->filter(fn ($r) => $r->visit && ! empty($r->visit->doctor_id))
            ->sortBy(function ($r) {
                $statusRank = match ($r->status) {
                    'calling' => 1,
                    'pending', 'pending_service_selection', 'scheduled' => 2,
                    'in_progress' => 3,
                    'completed' => 4,
                    default => 5,
                };
                $prioRank = match ($r->priority) {
                    'emergency' => 1,
                    'urgent' => 2,
                    default => 3,
                };

                return $statusRank.'_'.$prioRank.'_'.$r->id;
            })->values();

        $directRequests = $allCategoryRequests->filter(fn ($r) => ! $r->visit || empty($r->visit->doctor_id))->values();

        // جلب طلبات طوارئ الأشعة
        $emergencyRadiologyRequests = collect();
        if ($isAdmin || $user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo'])) {
            $emQuery = \App\Models\EmergencyRadiologyRequest::with([
                'emergency.doctor.user',
                'emergency.patient.user',
                'emergency.emergencyPatient',
                'patient.user',
                'radiologyTypes',
                'payment',
            ])
                ->whereIn('status', ['pending', 'in_progress', 'completed'])
                ->orderByRaw("CASE WHEN priority = 'critical' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                ->orderBy('requested_at', 'desc');

            if ($selectedCategory === 'ultrasound') {
                $emQuery->whereHas('radiologyTypes', fn ($q) => $q->where('subcategory', 'LIKE', '%سونار%'));
            } elseif ($selectedCategory === 'mri') {
                $emQuery->whereHas('radiologyTypes', fn ($q) => $q->where('subcategory', 'LIKE', '%رنين%'));
            } elseif ($selectedCategory === 'echo') {
                $emQuery->whereHas('radiologyTypes', fn ($q) => $q->where('subcategory', 'LIKE', '%إيكو%'));
            } elseif ($selectedCategory === 'radiology') {
                $emQuery->whereHas('radiologyTypes', fn ($q) => $q->where('subcategory', 'LIKE', '%أشعة%')->orWhereNull('subcategory'));
            }

            if ($dateFilter === 'today') {
                $emQuery->whereDate('requested_at', today());
            } elseif ($dateFilter === 'yesterday') {
                $emQuery->whereDate('requested_at', today()->subDay());
            } elseif ($dateFilter === 'week') {
                $emQuery->whereBetween('requested_at', [now()->startOfWeek(), now()->endOfWeek()]);
            } elseif ($dateFilter === 'month') {
                $emQuery->whereMonth('requested_at', now()->month)->whereYear('requested_at', now()->year);
            } elseif ($dateFilter !== 'all' && ! empty($dateFilter)) {
                $emQuery->whereDate('requested_at', $dateFilter);
            }

            if (! empty($search)) {
                $emQuery->where(function ($eq) use ($search) {
                    $eq->where('emergency_id', 'LIKE', "%{$search}%")
                        ->orWhere('notes', 'LIKE', "%{$search}%")
                        ->orWhereHas('patient.user', fn ($puq) => $puq->where('name', 'LIKE', "%{$search}%"))
                        ->orWhereHas('emergency.doctor.user', fn ($duq) => $duq->where('name', 'LIKE', "%{$search}%"));
                });
            }

            $emergencyRadiologyRequests = $emQuery->get();
        }

        $emergencyCount = $emergencyRadiologyRequests->count();
        $emergencyPendingCount = $emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress'])->count();
        $emergencyCompletedCount = $emergencyRadiologyRequests->where('status', 'completed')->count();

        // جلب الطلبات المعلقة من أيام سابقة (التي لم تكتمل بعد)
        $priorPendingQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user', 'visit.department'])
            ->where('type', 'radiology')
            ->whereIn('status', ['pending', 'pending_service_selection', 'scheduled', 'in_progress', 'calling'])
            ->whereDate('created_at', '<', today());

        if ($selectedCategory !== 'all' && ! empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $priorPendingQuery->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $priorPendingQuery->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $priorPendingQuery->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $priorPendingQuery->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $priorPendingQuery->where(function ($q) {
                    $q->where('subtype', 'general')
                        ->orWhere('subtype', 'radiology')
                        ->orWhereNull('subtype');
                });
            }
        }

        if (! empty($search)) {
            $priorPendingQuery->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('description', 'LIKE', "%{$search}%")
                    ->orWhereHas('visit.patient.user', fn ($pq) => $pq->where('name', 'LIKE', "%{$search}%"))
                    ->orWhereHas('visit.patient', fn ($pq) => $pq->where('medical_number', 'LIKE', "%{$search}%")->orWhere('phone', 'LIKE', "%{$search}%"))
                    ->orWhereHas('visit.doctor.user', fn ($dq) => $dq->where('name', 'LIKE', "%{$search}%"));
            });
        }

        if ($selectedCategory === 'echo' && ! $isAdmin && $userCategory === 'echo') {
            $priorPendingRequests = $priorPendingQuery->get()->filter(function ($req) use ($user) {
                $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;

                return ! isset($details['echo_staff_id']) || $details['echo_staff_id'] == $user->id;
            })->values();
        } else {
            $priorPendingRequests = $priorPendingQuery->orderBy('created_at', 'desc')->get();
        }

        $priorPendingCount = $priorPendingRequests->count();

        // حساب عدد الطلبات حسب الأقسام (مماثل لـ Lab تماماً)
        $activeSourceRequests = match ($sourceFilter) {
            'consultation' => $consultationRequests,
            'direct' => $directRequests,
            'pending' => $priorPendingRequests,
            default => $allCategoryRequests,
        };

        $counts = [
            'all' => $allCategoryRequests->count(),
            'consultation' => $consultationRequests->count(),
            'direct' => $directRequests->count(),
            'emergency' => $emergencyCount,
            'pending' => $allCategoryRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled', 'calling'])->count(),
            'in_progress' => $allCategoryRequests->where('status', 'in_progress')->count(),
            'completed' => $allCategoryRequests->where('status', 'completed')->count(),
            'prior_pending' => $priorPendingCount,
            'tab_total' => $sourceFilter === 'emergency' ? $emergencyCount : $activeSourceRequests->count(),
        ];

        $stats = [
            'total' => $allCategoryRequests->count(),
            'waiting' => $counts['pending'],
            'in_progress' => $counts['in_progress'],
            'completed' => $counts['completed'],
            'emergency' => $emergencyPendingCount,
            'prior_pending' => $priorPendingCount,
            'paid' => $allCategoryRequests->where('payment_status', 'paid')->count(),
            'unpaid' => $allCategoryRequests->where('payment_status', '!=', 'paid')->count(),
        ];

        // فلترة القائمة المعروضة حسب حالة الفحص (Status Filter)
        $displayRequests = $activeSourceRequests;
        if ($statusFilter === 'pending') {
            $displayRequests = $displayRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled', 'calling']);
        } elseif ($statusFilter === 'in_progress') {
            $displayRequests = $displayRequests->where('status', 'in_progress');
        } elseif ($statusFilter === 'completed') {
            $displayRequests = $displayRequests->where('status', 'completed');
        }

        // فلترة طلبات الطوارئ بحسب حالة الفحص إن كانت هي التبويب المختار
        if ($sourceFilter === 'emergency') {
            if ($statusFilter === 'pending') {
                $emergencyRadiologyRequests = $emergencyRadiologyRequests->whereIn('status', ['pending', 'in_progress']);
            } elseif ($statusFilter === 'completed') {
                $emergencyRadiologyRequests = $emergencyRadiologyRequests->where('status', 'completed');
            }
        }

        // ترتيب الطلبات: المدفوع والأولوية أولاً
        $sortedRequests = $displayRequests->sortByDesc(function ($r) {
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

        // جلب طلبات أشعة وسونار الخدج إن وجدت
        $nicuRadiologyRequests = collect();
        $useNicuMedicalRequests = false;
        if ($isAdmin || $user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo'])) {
            $nicuRequestsExist = MedicalRequest::where('type', 'radiology')
                ->whereNotNull('incubator_reservation_id')
                ->exists();

            if ($nicuRequestsExist) {
                $useNicuMedicalRequests = true;
                $isSqlite = \Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite';
                $extractSql = $isSqlite ? "json_extract(details, '$.priority')" : "JSON_UNQUOTE(JSON_EXTRACT(details, '$.priority'))";
                $nicuRadQuery = MedicalRequest::with(['incubatorReservation.patient.user', 'incubatorReservation.incubator', 'incubatorReservation.doctor.user'])
                    ->where('type', 'radiology')
                    ->whereNotNull('incubator_reservation_id')
                    ->whereIn('status', ['pending', 'in_progress', 'completed'])
                    ->orderByRaw("CASE WHEN {$extractSql} = 'stat' THEN 1 WHEN {$extractSql} = 'urgent' THEN 2 ELSE 3 END")
                    ->orderBy('created_at', 'desc');

                if ($dateFilter === 'today') {
                    $nicuRadQuery->whereDate('created_at', today());
                }

                if (!empty($search)) {
                    $nicuRadQuery->where(function($nq) use ($search) {
                        $nq->where('id', $search)
                           ->orWhere('description', 'like', "%{$search}%")
                           ->orWhereHas('incubatorReservation', function($rq) use ($search) {
                               $rq->where('baby_name', 'like', "%{$search}%")
                                  ->orWhere('id', 'like', "%{$search}%")
                                  ->orWhereHas('patient.user', fn($puq) => $puq->where('name', 'like', "%{$search}%"));
                           });
                    });
                }

                $nicuRadiologyRequests = $nicuRadQuery->get();
            } else {
                $nicuRadQuery = NicuMedicalOrder::with(['reservation.patient.user', 'reservation.incubator', 'reservation.doctor.user', 'radiologyType'])
                    ->where('order_type', 'radiology')
                    ->whereIn('status', ['pending', 'in_progress', 'completed'])
                    ->orderByRaw("CASE WHEN priority = 'stat' THEN 1 WHEN priority = 'urgent' THEN 2 ELSE 3 END")
                    ->orderBy('ordered_at', 'desc');

                if ($dateFilter === 'today') {
                    $nicuRadQuery->whereDate('ordered_at', today());
                }

                if (!empty($search)) {
                    $nicuRadQuery->where(function($nq) use ($search) {
                        $nq->where('item_name', 'like', "%{$search}%")
                           ->orWhere('id', 'like', "%{$search}%")
                           ->orWhereHas('reservation', function($rq) use ($search) {
                               $rq->where('baby_name', 'like', "%{$search}%")
                                  ->orWhere('id', 'like', "%{$search}%")
                                  ->orWhereHas('patient.user', fn($puq) => $puq->where('name', 'like', "%{$search}%"));
                           });
                    });
                }

                $nicuRadiologyRequests = $nicuRadQuery->get();
            }
        }

        $stats['nicu'] = $nicuRadiologyRequests->whereIn('status', ['pending', 'in_progress'])->count();
        // جلب المريض الحالي قيد الفحص بالداخل والمريض قيد النداء
        $currentPatient = $allCategoryRequests->where('status', 'in_progress')->first();
        $callingPatient = $allCategoryRequests->where('status', 'calling')->first();

        $waitingRequests = $allCategoryRequests->whereIn('status', ['pending', 'pending_service_selection', 'scheduled'])
            ->sortBy(function ($r) {
                $prio = $r->priority === 'emergency' ? 1 : ($r->priority === 'urgent' ? 2 : 3);
                $pay = $r->payment_status === 'paid' ? 1 : 2;

                return $prio.'_'.$pay.'_'.$r->id;
            })->values();

        $inProgressRequests = $allCategoryRequests->where('status', 'in_progress')->values();
        $completedRequests = $allCategoryRequests->where('status', 'completed')->sortByDesc('updated_at')->values();

        return view('radiology-staff.index', compact(
            'requests',
            'waitingRequests',
            'inProgressRequests',
            'completedRequests',
            'priorPendingRequests',
            'emergencyRadiologyRequests',
            'nicuRadiologyRequests',
            'consultationRequests',
            'directRequests',
            'stats',
            'counts',
            'selectedCategory',
            'userCategory',
            'activeTab',
            'sourceFilter',
            'statusFilter',
            'dateFilter',
            'search',
            'currentPatient',
            'callingPatient',
            'useNicuMedicalRequests',
            'isAdmin'
        ));
    }

    public function queueStatus(HttpRequest $httpRequest)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('view radiology'))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك بالوصول إلى قسم الأشعة'], 403);
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $httpRequest->query('category', $userCategory ?? 'all');
        if ($userCategory !== null && ! $isAdmin) {
            $selectedCategory = $userCategory;
        }

        $baseQuery = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user', 'visit.department', 'visit.appointment'])
            ->where('type', 'radiology')
            ->where('status', '!=', 'cancelled')
            ->whereDate('created_at', today());

        if ($selectedCategory !== 'all' && ! empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $baseQuery->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $baseQuery->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $baseQuery->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $baseQuery->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $baseQuery->where(function ($q) {
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
            ->sortBy(function ($r) {
                $prio = $r->priority === 'emergency' ? 1 : ($r->priority === 'urgent' ? 2 : 3);
                $pay = $r->payment_status === 'paid' ? 1 : 2;

                return $prio.'_'.$pay.'_'.$r->id;
            })
            ->values()
            ->map(function ($r, $idx) {
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
                    'doctor_notes' => $r->doctor_notes,
                    'created_time' => $r->created_at ? $r->created_at->format('H:i') : '—',
                    'is_paid' => $r->payment_status === 'paid',
                    'status' => $r->status,
                    'priority' => $r->priority,
                ];
            });

        // 3. In-Progress List
        $inProgressList = $allRequests->where('status', 'in_progress')
            ->values()
            ->map(function ($r) {
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
            ->map(function ($r) {
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
            'prior_pending' => MedicalRequest::where('type', 'radiology')
                ->whereIn('status', ['pending', 'pending_service_selection', 'scheduled', 'in_progress', 'calling'])
                ->whereDate('created_at', '<', today())
                ->when($selectedCategory !== 'all' && ! empty($selectedCategory), function ($q) use ($selectedCategory) {
                    if ($selectedCategory === 'echo') {
                        $q->where('subtype', 'echo');
                    } elseif ($selectedCategory === 'ultrasound') {
                        $q->where('subtype', 'ultrasound');
                    } elseif ($selectedCategory === 'mri') {
                        $q->where('subtype', 'mri');
                    } elseif ($selectedCategory === 'ct') {
                        $q->where('subtype', 'ct');
                    } elseif ($selectedCategory === 'radiology') {
                        $q->where(fn ($sub) => $sub->where('subtype', 'general')->orWhere('subtype', 'radiology')->orWhereNull('subtype'));
                    }
                })->count(),
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

        if (! $isAdmin && (! $user || (! $user->can('process radiology requests') && ! $user->can('view radiology')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المريض'], 403);
        }

        $userCategory = $this->getRadiologyCategoryForUser($user);
        $selectedCategory = $httpRequest->query('category', $userCategory ?? 'all');
        if ($userCategory !== null && ! $isAdmin) {
            $selectedCategory = $userCategory;
        }

        $query = MedicalRequest::with(['visit.patient.user', 'visit.appointment'])
            ->where('type', 'radiology')
            ->whereIn('status', ['pending', 'scheduled'])
            ->where(function ($q) {
                $q->where('payment_status', 'paid')
                    ->orWhere('details->priority', 'emergency');
            })
            ->whereDate('created_at', today());

        if ($selectedCategory !== 'all' && ! empty($selectedCategory)) {
            if ($selectedCategory === 'echo') {
                $query->where('subtype', 'echo');
            } elseif ($selectedCategory === 'ultrasound') {
                $query->where('subtype', 'ultrasound');
            } elseif ($selectedCategory === 'mri') {
                $query->where('subtype', 'mri');
            } elseif ($selectedCategory === 'ct') {
                $query->where('subtype', 'ct');
            } elseif ($selectedCategory === 'radiology') {
                $query->where(function ($q) {
                    $q->where('subtype', 'general')
                        ->orWhere('subtype', 'radiology')
                        ->orWhereNull('subtype');
                });
            }
        }

        $nextRequest = $query->get()
            ->sortBy(function ($r) {
                $prio = $r->priority === 'emergency' ? 1 : ($r->priority === 'urgent' ? 2 : 3);

                return $prio.'_'.str_pad($r->id, 8, '0', STR_PAD_LEFT);
            })
            ->first();

        if (! $nextRequest) {
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

        if (! $currentCalling) {
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

        if (! $currentCalling) {
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
            'message' => 'تم تأخير دور المريض ونقله لآخر طابور الانتظار.',
        ]);
    }

    public function call(MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('process radiology requests') && ! $user->can('view radiology')))) {
            return response()->json(['success' => false, 'message' => 'غير مصرح لك باستدعاء المريض.'], 403);
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        if ($request->payment_status !== 'paid' && $request->priority !== 'emergency') {
            return response()->json([
                'success' => false,
                'message' => 'تنبيه: لا يمكن مناداة المريض قبل تسديد رسوم الفحص في الكاشير أولاً.',
            ], 422);
        }

        // إلغاء نداء أي مريض سابق وجعله pending
        MedicalRequest::where('type', 'radiology')
            ->where('status', 'calling')
            ->where('id', '!=', $request->id)
            ->update(['status' => 'pending']);

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (! is_array($details)) {
            $details = [];
        }
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
                } catch (\Throwable $te) {
                }
            }
        }

        $patientName = $request->visit?->patient?->name ?? $request->visit?->patient?->user?->name ?? 'المريض';

        return response()->json([
            'success' => true,
            'message' => 'تمت المناداة على المريض ('.$patientName.') بنجاح.',
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

        if (! $isAdmin && (! $user || (! $user->can('process radiology requests') && ! $user->can('view radiology')))) {
            abort(403, 'غير مصرح لك ببدء فحص الأشعة');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (! is_array($details)) {
            $details = [];
        }
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
                    'status' => 'in_consultation',
                ]);
            }
        }

        return redirect()->route('radiology-staff.show', $request)->with('success', 'تم إدخال المريض وبدء الفحص بنجاح. يمكنك الآن كتابة التقرير والنتائج.');
    }

    private function getRadiologyCategoryForUser($user)
    {
        if (! $user) {
            return null;
        }
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
        if ($user->hasRole('radiology_general') || $user->hasRole('radiology_staff')) {
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
        if (! $user) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $isAdmin = $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if ($isAdmin || $request->incubator_reservation_id) {
            return;
        }

        $category = $this->getRadiologyCategoryForUser($user);
        if (! $category) {
            if ($user->can('process radiology requests') || $user->can('view radiology')) {
                return;
            }
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        if ($user->hasRole('radiology_staff') && ! $user->hasRole('radiology_general')) {
            return;
        }

        $requestSubtype = $request->subtype;

        if ($category === 'radiology') {
            if (! in_array($requestSubtype, ['general', 'radiology', null], true)) {
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

        if (! $isAdmin && (! $user || ! $user->can('view radiology'))) {
            abort(403, 'غير مصرح لك بعرض هذا الطلب');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        if ($request->type !== 'radiology') {
            abort(403, 'هذا الطلب ليس من نوع الأشعة');
        }

        $request->load([
            'visit.patient.user', 
            'visit.doctor.user',
            'incubatorReservation.patient.user',
            'incubatorReservation.incubator.room',
            'incubatorReservation.doctor.user'
        ]);

        $resultData = [];
        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (! is_array($resultData)) {
                $resultData = ['findings' => (string) $request->result];
            }
        }

        // Check if there is an associated RadiologyRequest
        $radiologyRequest = null;
        if ($request->visit_id) {
            $radiologyRequest = \App\Models\RadiologyRequest::with(['result.radiologist', 'radiologyType'])
                ->where('visit_id', $request->visit_id)
                ->first();
        }

        if (empty($resultData['findings']) && $radiologyRequest && $radiologyRequest->result) {
            $resultData['findings'] = $radiologyRequest->result->findings ?? '';
            $resultData['impression'] = $resultData['impression'] ?? ($radiologyRequest->result->impression ?? '');
            $resultData['recommendations'] = $resultData['recommendations'] ?? ($radiologyRequest->result->recommendations ?? '');
            $resultData['images'] = $resultData['images'] ?? ($radiologyRequest->result->images ?? []);
            $resultData['radiologist'] = $resultData['radiologist'] ?? optional($radiologyRequest->result->radiologist)->name;
        }

        // تحديد أنواع الأشعة المتاحة لهذا الموظف بناءً على تخصصه
        $userCategory = $this->getRadiologyCategoryForUser($user);
        $typesQuery = \App\Models\RadiologyType::where('is_active', true);

        if (! $isAdmin && $userCategory !== null) {
            $categoryMap = [
                'radiology' => 'أشعة',
                'general' => 'أشعة',
                'ultrasound' => 'سونار',
                'mri' => 'الرنين',
                'ct' => 'مفراس',
                'echo' => 'إيكو',
            ];
            $targetSubcategory = $categoryMap[$userCategory] ?? null;
            if ($targetSubcategory) {
                $typesQuery->where('subcategory', $targetSubcategory);
            }
        }

        $availableRadiologyTypes = $typesQuery->orderBy('name')->get();
        $availableRadiologyTypesArray = $availableRadiologyTypes->map(function ($t) {
            return [
                'id' => $t->id,
                'name' => $t->name,
                'code' => $t->code ?? '',
                'price' => (float) ($t->price ?? 0),
                'category' => $t->subcategory ?? 'أشعة',
            ];
        })->values()->toArray();

        return view('radiology-staff.show', compact('request', 'resultData', 'radiologyRequest', 'availableRadiologyTypes', 'availableRadiologyTypesArray', 'userCategory'));
    }

    public function update(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || ! $user->can('process radiology requests'))) {
            abort(403, 'غير مصرح لك بتحديث ومعالجة طلب الأشعة');
        }

        $request->load(['visit']);

        // 1. اختيار أنواع الأشعة (pending_service_selection أو عند عدم وجود فحوصات محددة بعد)
        if ($httpRequest->has('radiology_type_ids') && ($request->status === 'pending_service_selection' || empty($request->radiology_names) || $httpRequest->has('update_services'))) {
            $this->authorizeMedicalRequestForUser($request, $user);
            $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
            if (! is_array($details)) {
                $details = [];
            }

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

        // 2. حفظ نتائج الأشعة (findings, impression, recommendations, images, etc.)
        if ($httpRequest->has('findings') || $httpRequest->has('result_text') || $httpRequest->has('impression') || $httpRequest->hasFile('images')) {
            $existingResult = [];
            if ($request->result) {
                $existingResult = is_string($request->result) ? json_decode($request->result, true) : $request->result;
                if (! is_array($existingResult)) {
                    $existingResult = [];
                }
            }

            $imagePaths = $existingResult['images'] ?? [];
            if ($httpRequest->hasFile('images')) {
                foreach ($httpRequest->file('images') as $image) {
                    if ($image->isValid()) {
                        $path = $image->store('radiology_images', 'public');
                        $imagePaths[] = $path;
                    }
                }
            }

            $findings = $httpRequest->findings ?? $httpRequest->result_text ?? ($existingResult['findings'] ?? '');
            $impression = $httpRequest->impression ?? ($existingResult['impression'] ?? '');
            $recommendations = $httpRequest->recommendations ?? $httpRequest->result_notes ?? ($existingResult['recommendations'] ?? '');
            $newStatus = $httpRequest->status ?? 'completed';

            $request->result = [
                'findings' => $findings,
                'impression' => $impression,
                'recommendations' => $recommendations,
                'images' => $imagePaths,
                'radiologist' => $user->name,
                'reported_at' => now()->format('Y-m-d H:i:s'),
            ];
            $request->status = $newStatus;
            $request->save();

            $patientName = $request->incubatorReservation?->baby_title ?? ($request->visit?->patient?->name ?? 'مريض');
            $cat = $request->subtype ?: 'radiology';
            \App\Services\UserActivityLogger::log(
                action: 'save_radiology_results',
                category: 'radiology',
                description: "حفظ واعتماد تقرير الأشعة والتصوير للمريض: {$patientName} ({$cat}) - طلب #{$request->id}",
                subject: $request,
                isSensitive: false,
                properties: ['status' => $newStatus, 'subtype' => $cat]
            );

            // Sync with NicuMedicalOrder if exists
            if ($request->incubator_reservation_id) {
                NicuMedicalOrder::where('order_group_id', 'REQ-' . $request->id)
                    ->orWhere(function($q) use ($request) {
                        $q->where('reservation_id', $request->incubator_reservation_id)
                          ->where('order_type', 'radiology')
                          ->where('status', '!=', 'completed');
                    })
                    ->update([
                        'status' => $newStatus,
                        'result' => $findings . ($impression ? "\nالانطباع: " . $impression : ''),
                        'completed_at' => $newStatus === 'completed' ? now() : null,
                        'processed_by' => $user->id,
                    ]);
            }

            // Sync with RadiologyRequest if exists
            if ($request->visit_id) {
                $radReq = \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)->first();
                if ($radReq) {
                    $radReq->update(['status' => $newStatus]);
                    if ($radReq->result) {
                        $radReq->result->update([
                            'findings' => $findings,
                            'impression' => $impression,
                            'recommendations' => $recommendations,
                            'images' => $imagePaths,
                            'reported_at' => now(),
                        ]);
                    } else {
                        \App\Models\RadiologyResult::create([
                            'radiology_request_id' => $radReq->id,
                            'radiologist_id' => $user->id,
                            'findings' => $findings,
                            'impression' => $impression,
                            'recommendations' => $recommendations,
                            'images' => $imagePaths,
                            'reported_at' => now(),
                        ]);
                    }
                }
            }

            try {
                if ($newStatus === 'completed') {
                    app(\App\Services\TelegramService::class)->sendResultsReady($request);
                }
            } catch (\Throwable $te) {
                \Illuminate\Support\Facades\Log::warning('Telegram radiology notification error: '.$te->getMessage());
            }

            $isDoctorVisit = $request->visit && (! empty($request->visit->doctor_id) || ! empty($request->visit->appointment_id) || $request->visit->visit_type === 'checkup');
            if ($request->visit && ! $isDoctorVisit && $newStatus === 'completed') {
                $pending = $request->visit->requests()->where('id', '!=', $request->id)->where('status', '!=', 'completed')->count();
                if ($pending === 0) {
                    $request->visit->status = 'completed';
                    $request->visit->save();
                }
            }

            return redirect()->route('radiology-staff.show', $request)->with('success', 'تم حفظ نتائج وتقرير الأشعة بنجاح');
        }

        // 3. تحديث الحالة فقط
        $request->update(['status' => $httpRequest->status ?? 'completed']);

        return redirect()->back()->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    /**
     * Print radiology report
     */
    public function print(MedicalRequest $request)
    {
        $request->load([
            'visit.patient.user', 
            'visit.doctor.user', 
            'visit.department',
            'incubatorReservation.patient.user',
            'incubatorReservation.doctor.user',
            'incubatorReservation.incubator'
        ]);

        // Check if there is an associated RadiologyRequest
        $radiologyReq = null;
        if ($request->visit_id) {
            $radiologyReq = \App\Models\RadiologyRequest::with(['patient.user', 'doctor.user', 'radiologyType', 'result.radiologist'])
                ->where('visit_id', $request->visit_id)
                ->first();
        }

        // Get result data from MedicalRequest first, then fallback to RadiologyResult if empty
        $resultData = [];
        if ($request->result) {
            $resultData = is_string($request->result) ? json_decode($request->result, true) : $request->result;
            if (! is_array($resultData)) {
                $resultData = ['findings' => (string) $request->result];
            }
        }

        // Fallback to RadiologyResult if findings are not in MedicalRequest
        if (empty($resultData['findings']) && $radiologyReq && $radiologyReq->result) {
            $resultData['findings'] = $radiologyReq->result->findings ?? '';
            $resultData['impression'] = $resultData['impression'] ?? ($radiologyReq->result->impression ?? '');
            $resultData['recommendations'] = $resultData['recommendations'] ?? ($radiologyReq->result->recommendations ?? '');
            $resultData['images'] = $resultData['images'] ?? ($radiologyReq->result->images ?? []);
            $resultData['radiologist'] = $resultData['radiologist'] ?? optional($radiologyReq->result->radiologist)->name;
            $resultData['reported_at'] = $resultData['reported_at'] ?? $radiologyReq->result->reported_at;
        }

        // Build unified robust $radiology object for the view
        $radiologyType = null;
        if ($radiologyReq && $radiologyReq->radiologyType) {
            $radiologyType = $radiologyReq->radiologyType;
        } else {
            $radiologyType = (object) [
                'name' => implode(', ', $request->radiology_names) ?: ($request->description ?: 'فحص تصوير'),
                'code' => 'RAD-'.$request->id,
                'category' => $request->subtype ?? 'الأشعة والتصوير',
            ];
        }

        $radiologistObj = (object) [
            'name' => $resultData['radiologist'] ?? auth()->user()->name,
        ];

        $resultObj = (object) [
            'findings' => $resultData['findings'] ?? '',
            'impression' => $resultData['impression'] ?? '',
            'recommendations' => $resultData['recommendations'] ?? '',
            'images' => $resultData['images'] ?? [],
            'reported_at' => ! empty($resultData['reported_at']) ? \Carbon\Carbon::parse($resultData['reported_at']) : ($request->updated_at ?? now()),
            'radiologist' => $radiologistObj,
        ];

        $details = is_string($request->details) ? (json_decode($request->details, true) ?? []) : ($request->details ?? []);
        if (! is_array($details)) {
            $details = [];
        }

        $radiology = (object) [
            'id' => $request->id,
            'patient' => $request->visit?->patient ?? $request->incubatorReservation?->patient ?? $radiologyReq?->patient,
            'doctor' => $request->visit?->doctor ?? $request->incubatorReservation?->doctor ?? $radiologyReq?->doctor,
            'radiologyType' => $radiologyType,
            'clinical_indication' => $details['clinical_indication'] ?? $request->description ?? $radiologyReq?->clinical_indication ?? '',
            'specific_instructions' => $details['notes'] ?? '',
            'notes' => $details['notes'] ?? '',
            'created_at' => $request->created_at,
            'requested_date' => $request->created_at ?? now(),
            'status' => $request->status,
            'result' => $resultObj,
            'payment_status' => $request->payment_status,
            'insurance_type' => $request->insurance_type ?? optional($request->visit)->patient?->insurance_type ?? 'none',
            'medical_request' => $request,
        ];

        return view('radiology.print', compact('radiology', 'request'));
    }

    /**
     * إلغاء الطلب الخارجي المباشر لقسم الأشعة والتصوير
     */
    public function cancel(HttpRequest $httpRequest, MedicalRequest $request)
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);

        if (! $isAdmin && (! $user || (! $user->can('process radiology requests') && ! $user->can('view radiology')))) {
            abort(403, 'غير مصرح لك بإلغاء هذا الطلب');
        }

        $this->authorizeMedicalRequestForUser($request, $user);

        // 1. فحص هل الطلب صادر من طبيب استشاري داخل العيادات
        if (! $request->isDirectExternal()) {
            $msg = 'عذراً، هذا الطلب صادر عن طبيب استشاري داخل العيادات ولا يمكن إلغاؤه من قسم الأشعة. الإلغاء يتم حصراً من شاشة الطبيب المعالج.';
            if ($httpRequest->expectsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        // 2. فحص هل الفحص مكتمل ومعتمد بالفعل
        if ($request->status === 'completed') {
            $msg = 'لا يمكن إلغاء الطلب لأنه مكتمل ومعتمد بتقرير الأشعة بالفعل.';
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
        $cancelReason = trim($httpRequest->input('cancel_reason', 'إلغاء الطلب المباشر من قسم الأشعة والتصوير بطلب من المريض'));

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

        // تحديث جدول radiology_requests إن وجد
        if ($request->visit_id) {
            \App\Models\RadiologyRequest::where('visit_id', $request->visit_id)
                ->where('status', '!=', 'completed')
                ->update(['status' => 'cancelled']);
        }

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
            action: 'cancel_external_radiology_request',
            category: 'radiology',
            description: "إلغاء طلب الأشعة والتصوير الخارجي للمريض: {$patientName} - طلب #{$request->id}{$paidNote} (السبب: {$cancelReason})",
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

        return redirect()->route('radiology-staff.index')->with($wasPaid ? 'warning' : 'success', $successMsg);
    }

    /**
     * بدء فحص أشعة وسونار الطفل الخديج
     */
    public function startNicuOrder(NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo', 'admin'])) {
            abort(403, 'غير مصرح لك ببدء هذا الفحص');
        }

        $order->update([
            'status' => NicuMedicalOrder::STATUS_IN_PROGRESS,
            'processed_by' => $user->id,
        ]);

        return redirect()->back()->with('success', "تم بدء فحص الأشعة للطفل الخديج ({$order->item_name}) بنجاح.");
    }

    /**
     * إكمال واعتماد تقرير أشعة وسونار الطفل الخديج
     */
    public function completeNicuOrder(HttpRequest $request, NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo', 'admin'])) {
            abort(403, 'غير مصرح لك بكتابة تقرير هذا الفحص');
        }

        $validated = $request->validate([
            'result' => 'required|string',
            'technician_notes' => 'nullable|string|max:1000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,dcm|max:15360',
        ]);

        $attachmentPath = $order->attachment_path;
        $attachmentName = $order->attachment_name;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $attachmentPath = $file->storeAs('radiology_attachments', $filename, 'public');
            $attachmentName = $file->getClientOriginalName();
        }

        $order->update([
            'result' => $validated['result'],
            'technician_notes' => $validated['technician_notes'] ?? null,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'status' => NicuMedicalOrder::STATUS_COMPLETED,
            'processed_by' => $user->id,
            'completed_at' => now(),
        ]);

        return redirect()->back()->with('success', "تم توثيق واعتماد تقرير الأشعة للطفل الخديج ({$order->item_name}) بنجاح.");
    }

    /**
     * طباعة تقرير أشعة وسونار الطفل الخديج الرسمي
     */
    public function printNicuOrder(NicuMedicalOrder $order)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['radiology_staff', 'radiology_general', 'radiology_ultrasound', 'radiology_mri', 'radiology_echo', 'admin', 'doctor', 'nurse', 'receptionist'])) {
            abort(403, 'غير مصرح لك بطباعة هذا التقرير');
        }

        $order->load(['reservation.patient.user', 'reservation.incubator', 'reservation.doctor.user', 'processedByUser', 'radiologyType']);

        return view('nicu.print.radiology-result', compact('order'));
    }
}
