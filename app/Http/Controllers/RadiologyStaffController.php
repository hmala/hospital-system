<?php

namespace App\Http\Controllers;

use App\Models\Request as MedicalRequest;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Support\Facades\Auth;

class RadiologyStaffController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if (!$user->hasAnyRole(['radiology_staff', 'admin', 'radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general'])) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $category = $this->getRadiologyCategoryForUser($user);

        // Debug: تسجيل معلومات المستخدم والفئة
        \Log::info('RadiologyStaffController - User Roles', [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'roles' => $user->roles->pluck('name')->toArray(),
            'category' => $category,
            'is_admin' => $user->hasRole('admin'),
        ]);

        $query = MedicalRequest::with(['visit.patient.user', 'visit.doctor.user'])
            ->where('type', 'radiology')
            ->whereIn('status', ['pending_service_selection', 'pending', 'in_progress', 'completed']);

        // تطبيق فلترة الفئة بناءً على عمود subtype
        if ($category !== null && !$user->hasRole('admin')) {
            if ($category === 'echo') {
                // للإيكو: نجلب كل طلبات الإيكو ثم نفلتر حسب echo_staff_id
                $query->where('subtype', 'echo');
            } elseif ($category === 'ultrasound') {
                $query->where('subtype', 'ultrasound');
            } elseif ($category === 'mri') {
                $query->where('subtype', 'mri');
            } elseif ($category === 'radiology') {
                // الأشعة العامة تشمل: general أو null
                $query->where(function($q) {
                    $q->where('subtype', 'general')
                      ->orWhereNull('subtype');
                });
            }
        }

        // Debug: تسجيل SQL query المطبق
        \Log::info('RadiologyStaffController - SQL Query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        $allRequests = $query->orderBy('created_at', 'desc')->get();
        
        // فلترة خاصة بالإيكو في PHP
        if ($category === 'echo' && !$user->hasRole('admin')) {
            $allRequests = $allRequests->filter(function($request) use ($user) {
                $details = $request->details;
                
                // التعامل مع double JSON encoding
                if (is_string($details)) {
                    $details = json_decode($details, true);
                    // إذا كان النتيجة string، فهذا يعني double encoding
                    if (is_string($details)) {
                        $details = json_decode($details, true);
                    }
                }
                
                return isset($details['echo_staff_id']) && $details['echo_staff_id'] == $user->id;
            });
        }
        
        // تحويل إلى paginator
        $perPage = 15;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage();
        $currentItems = $allRequests->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $requests = new \Illuminate\Pagination\LengthAwarePaginator(
            $currentItems,
            $allRequests->count(),
            $perPage,
            $currentPage,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
        );

        // Debug: تسجيل عدد الطلبات المعروضة
        \Log::info('RadiologyStaffController - Requests Count', [
            'total' => $requests->total(),
            'category_filter' => $category,
        ]);

        // Debug: تسجيل تفاصيل كل طلب
        foreach ($requests as $req) {
            $details = is_string($req->details) ? json_decode($req->details, true) : $req->details;
            $logData = [
                'request_id' => $req->id,
                'patient_name' => $req->visit?->patient?->user?->name ?? 'Unknown',
                'subtype' => $req->subtype ?? 'NOT SET',
            ];
            if ($req->subtype === 'echo') {
                $logData['echo_staff_id'] = $details['echo_staff_id'] ?? 'NOT SET';
            }
            \Log::info('Request Details', $logData);
        }

        $emergencyRadiologyRequests = \App\Models\EmergencyRadiologyRequest::with(['emergency', 'patient.user', 'radiologyTypes'])
            ->whereIn('status', ['pending', 'in_progress', 'completed'])
            ->orderByRaw("FIELD(priority, 'critical', 'urgent')")
            ->orderBy('requested_at', 'asc')
            ->get();

        return view('radiology-staff.index', compact('requests', 'emergencyRadiologyRequests'));
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
            if (!in_array($requestSubtype, ['general', null], true)) {
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

        if (!$user->hasAnyRole(['radiology_staff', 'admin', 'radiology_echo', 'radiology_ultrasound', 'radiology_mri', 'radiology_general'])) {
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

            if ($request->visit) {
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
