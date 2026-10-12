<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\DoctorDue;
use App\Models\DoctorFinancialAccount;
use App\Models\FinancialTransaction;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Supplier;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\HospitalTreasuryExport;
use App\Models\ConsultationRevenue;

class HospitalTreasuryController extends Controller
{
    /**
     * التحقق من الصلاحيات الإدارية أو المالية
     */
    protected function authorizeTreasuryAccess()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']);
        if (!$isAdmin && (!$user || (!$user->can('view treasury') && !$user->can('view account statements')))) {
            abort(403, 'غير مصرح لك بالوصول إلى خزينة وحسابات المستشفى العامة');
        }
    }

    /**
     * الواجهة الرئيسية لخزينة المستشفى وحركة النقدية
     */
    public function index(Request $request)
    {
        $this->authorizeTreasuryAccess();

        // 1. حساب الرصيد الإجمالي التراكمي للخزينة (كل الوقت)
        $totalAllTimeInflow = FinancialTransaction::inflow()->sum('amount');
        $totalAllTimeOutflow = FinancialTransaction::outflow()->sum('amount');
        $currentTreasuryBalance = $totalAllTimeInflow - $totalAllTimeOutflow;

        // 2. بناء استعلام الحركات مع الفلاتر
        $query = FinancialTransaction::with(['performer', 'related']);

        // فلتر التاريخ
        if ($request->filled('from_date')) {
            $query->whereDate('performed_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('performed_at', '<=', $request->to_date);
        }

        // فلتر نوع الحركة (وارد / صادر)
        if ($request->filled('voucher_type') && $request->voucher_type !== 'all') {
            if ($request->voucher_type === 'inflow') {
                $query->inflow();
            } elseif ($request->voucher_type === 'outflow') {
                $query->outflow();
            }
        }

        // فلتر البند / التصنيف
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // فلتر طريقة الدفع
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }

        // بحث بالرقم أو الوصف أو الملاحظات
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('voucher_number', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        // 3. استعلام أساسي للفترة الزمنية المحددة بدون تقييد بنوع أو بند محدد
        $basePeriodQuery = FinancialTransaction::query();
        if ($request->filled('from_date')) {
            $basePeriodQuery->whereDate('performed_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $basePeriodQuery->whereDate('performed_at', '<=', $request->to_date);
        }

        $periodInflow = (float) (clone $basePeriodQuery)->inflow()->sum('amount');
        $periodOutflow = (float) (clone $basePeriodQuery)->outflow()->sum('amount');
        $periodDoctorPayouts = (float) (clone $basePeriodQuery)->where('category', 'doctor_payout')->sum('amount');
        $periodPurchases = (float) (clone $basePeriodQuery)->where('category', 'purchase')->sum('amount');
        $periodOperational = (float) (clone $basePeriodQuery)->whereIn('category', [
            'operational', 'salary', 'maintenance', 'utilities', 'medical_supplies', 'other'
        ])->sum('amount');

        // 4. مصفوفة إعداد وتوزيع الأقسام (Modular Config-Driven Departments)
        // إذا أردت إضافة أي قسم بالمستقبل، يكفي إضافة سطر واحد هنا
        $departmentsConfig = [
            'consultation' => [
                'name' => 'العيادات الاستشارية',
                'icon' => 'fa-stethoscope',
                'color' => '#0284c7', // Primary Sky
                'bg_class' => 'bg-primary-subtle',
                'text_class' => 'text-primary',
                'border_class' => 'border-primary-subtle',
            ],
            'radiology' => [
                'name' => 'الأشعة والسونار والإيكو',
                'icon' => 'fa-x-ray',
                'color' => '#0d9488', // Teal
                'bg_class' => 'bg-info-subtle',
                'text_class' => 'text-info',
                'border_class' => 'border-info-subtle',
            ],
            'lab' => [
                'name' => 'المختبر والتحاليل الطبية',
                'icon' => 'fa-vial',
                'color' => '#7c3aed', // Purple
                'bg_class' => 'bg-purple-subtle',
                'text_class' => 'text-purple',
                'border_class' => 'border-purple-subtle',
            ],
            'surgery' => [
                'name' => 'العمليات الجراحية والرقود',
                'icon' => 'fa-procedures',
                'color' => '#d97706', // Amber/Orange
                'bg_class' => 'bg-warning-subtle',
                'text_class' => 'text-warning',
                'border_class' => 'border-warning-subtle',
            ],
            'emergency' => [
                'name' => 'قسم الطوارئ والإسعاف',
                'icon' => 'fa-ambulance',
                'color' => '#dc2626', // Danger Red
                'bg_class' => 'bg-danger-subtle',
                'text_class' => 'text-danger',
                'border_class' => 'border-danger-subtle',
            ],
            'pharmacy' => [
                'name' => 'الصيدلية ونقطة البيع',
                'icon' => 'fa-pills',
                'color' => '#059669', // Emerald Green
                'bg_class' => 'bg-success-subtle',
                'text_class' => 'text-success',
                'border_class' => 'border-success-subtle',
            ],
            'general_income' => [
                'name' => 'إيرادات وتحصيلات عامة',
                'icon' => 'fa-coins',
                'color' => '#475569', // Slate
                'bg_class' => 'bg-secondary-subtle',
                'text_class' => 'text-secondary',
                'border_class' => 'border-secondary-subtle',
            ],
        ];

        // احتساب إحصائيات كل قسم
        $departmentStats = [];
        $deptChartLabels = [];
        $deptChartData = [];
        $deptChartColors = [];

        foreach ($departmentsConfig as $catKey => $config) {
            $catQuery = (clone $basePeriodQuery)->where('category', $catKey)->inflow();
            $count = (int) $catQuery->count();
            $gross = (float) $catQuery->sum('amount');
            $percentage = $periodInflow > 0 ? round(($gross / $periodInflow) * 100, 1) : 0;

            // حصص وتكاليف القسم
            $costs = 0;
            $net = $gross;

            if ($catKey === 'consultation') {
                $crQuery = ConsultationRevenue::query();
                if ($request->filled('from_date')) {
                    $crQuery->whereDate('revenue_date', '>=', $request->from_date);
                }
                if ($request->filled('to_date')) {
                    $crQuery->whereDate('revenue_date', '<=', $request->to_date);
                }
                $docShare = (float) $crQuery->sum('doctor_share');
                $hospShare = (float) $crQuery->sum('hospital_share');
                if ($docShare > 0) {
                    $costs = $docShare;
                    $net = $hospShare;
                }
            }

            $departmentStats[$catKey] = array_merge($config, [
                'count' => $count,
                'gross' => $gross,
                'costs' => $costs,
                'net' => $net,
                'percentage' => $percentage,
            ]);

            if ($gross > 0) {
                $deptChartLabels[] = $config['name'];
                $deptChartData[] = $gross;
                $deptChartColors[] = $config['color'];
            }
        }

        // 5. بيانات المنحنى البياني الزمني (Daily Cash Flow Trend)
        $trendStartDate = $request->filled('from_date') 
            ? Carbon::parse($request->from_date)->startOfDay() 
            : now()->subDays(6)->startOfDay();
        $trendEndDate = $request->filled('to_date') 
            ? Carbon::parse($request->to_date)->endOfDay() 
            : now()->endOfDay();

        if ($trendStartDate->diffInDays($trendEndDate) > 30) {
            $trendStartDate = (clone $trendEndDate)->subDays(13)->startOfDay();
        }

        $trendLabels = [];
        $trendInflows = [];
        $trendOutflows = [];

        try {
            $dateRange = CarbonPeriod::create($trendStartDate, $trendEndDate);
            foreach ($dateRange as $dt) {
                $dString = $dt->toDateString();
                $trendLabels[] = $dt->format('m/d');
                $trendInflows[] = (float) FinancialTransaction::inflow()->whereDate('performed_at', $dString)->sum('amount');
                $trendOutflows[] = (float) FinancialTransaction::outflow()->whereDate('performed_at', $dString)->sum('amount');
            }
        } catch (\Throwable $e) {
            $trendLabels = [now()->format('m/d')];
            $trendInflows = [(float) $periodInflow];
            $trendOutflows = [(float) $periodOutflow];
        }

        // 6. توزيع طرق الدفع (Cash vs Digital)
        $paymentMethodStats = [
            'cash' => (float) (clone $basePeriodQuery)->inflow()->where('payment_method', 'cash')->sum('amount'),
            'card' => (float) (clone $basePeriodQuery)->inflow()->where('payment_method', 'card')->sum('amount'),
            'bank_transfer' => (float) (clone $basePeriodQuery)->inflow()->where('payment_method', 'bank_transfer')->sum('amount'),
            'cheque' => (float) (clone $basePeriodQuery)->inflow()->where('payment_method', 'cheque')->sum('amount'),
        ];

        // 7. التحليل المالي والتحويلي لأطباء الاستشارية (Doctor BI & Referrals Analysis)
        // استعلام كشوفات وإيرادات الاستشارية المحصلة (كاش مقابل ضمان) مفصلة بين نقد الدرج ومطالبات الضمان
        $revQuery = DB::table('consultation_revenues')
            ->leftJoin('appointments', 'consultation_revenues.appointment_id', '=', 'appointments.id')
            ->leftJoin('payments', 'consultation_revenues.payment_id', '=', 'payments.id');
        if ($request->filled('from_date')) {
            $revQuery->whereDate('consultation_revenues.revenue_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $revQuery->whereDate('consultation_revenues.revenue_date', '<=', $request->to_date);
        }
        $doctorRevenues = $revQuery->select(
            'consultation_revenues.doctor_id',
            DB::raw("SUM(CASE WHEN appointments.insurance_type IN ('hi', 'moi', 'interior_ministry', 'health_insurance') THEN 1 ELSE 0 END) as insurance_cnt"),
            DB::raw("SUM(CASE WHEN appointments.insurance_type IS NULL OR appointments.insurance_type = '' OR appointments.insurance_type = 'none' THEN 1 ELSE 0 END) as cash_cnt"),
            DB::raw('count(*) as total_cnt'),
            DB::raw("SUM(CASE WHEN appointments.insurance_type IS NULL OR appointments.insurance_type = '' OR appointments.insurance_type = 'none' THEN consultation_revenues.total_amount ELSE 0 END) as cash_revenue"),
            DB::raw("SUM(CASE WHEN appointments.insurance_type IN ('hi', 'moi', 'interior_ministry', 'health_insurance') THEN COALESCE(payments.amount, 0) ELSE 0 END) as insurance_copay"),
            DB::raw("SUM(CASE WHEN appointments.insurance_type IN ('hi', 'moi', 'interior_ministry', 'health_insurance') THEN COALESCE(payments.insurance_share, consultation_revenues.total_amount - COALESCE(payments.amount, 0)) ELSE 0 END) as insurance_claim"),
            DB::raw('SUM(consultation_revenues.total_amount) as gross'),
            DB::raw('SUM(consultation_revenues.doctor_share) as doc_share'),
            DB::raw('SUM(consultation_revenues.hospital_share) as hosp_share')
        )->groupBy('consultation_revenues.doctor_id')->get()->keyBy('doctor_id');

        // ج) استعلام تحويلات الفحوصات التشخيصية (مختبر وأشعة) الناتجة عن زيارات أطباء الاستشارية
        $referralReqQuery = DB::table('requests')
            ->join('visits', 'requests.visit_id', '=', 'visits.id')
            ->whereNotNull('visits.doctor_id');
        if ($request->filled('from_date')) {
            $referralReqQuery->whereDate('requests.created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $referralReqQuery->whereDate('requests.created_at', '<=', $request->to_date);
        }
        $doctorReferrals = $referralReqQuery->select(
            'visits.doctor_id',
            'requests.type',
            DB::raw('count(*) as cnt')
        )->groupBy('visits.doctor_id', 'requests.type')->get();

        $doctorLabReferrals = [];
        $doctorRadReferrals = [];
        foreach ($doctorReferrals as $dr) {
            if ($dr->type === 'lab' || $dr->type === 'blood_bank') {
                $doctorLabReferrals[$dr->doctor_id] = ($doctorLabReferrals[$dr->doctor_id] ?? 0) + $dr->cnt;
            } elseif ($dr->type === 'radiology') {
                $doctorRadReferrals[$dr->doctor_id] = ($doctorRadReferrals[$dr->doctor_id] ?? 0) + $dr->cnt;
            }
        }

        // د) استعلام تحويلات العمليات الجراحية
        $surgQuery = DB::table('surgeries');
        if ($request->filled('from_date')) {
            $surgQuery->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $surgQuery->whereDate('created_at', '<=', $request->to_date);
        }
        $doctorSurgeries = $surgQuery->select('doctor_id', DB::raw('count(*) as cnt'))
            ->whereNotNull('doctor_id')
            ->groupBy('doctor_id')->pluck('cnt', 'doctor_id')->toArray();

        // هـ) تجميع وتنسيق جدول تحليلات الأطباء
        $consultantDoctors = Doctor::with(['user', 'department'])
            ->where(function ($q) {
                $q->where('type', 'consultant')->orWhereNull('type');
            })
            ->where('is_active', true)
            ->get();

        $doctorAnalytics = [];
        $totalDoctorCashAppts = 0;
        $totalDoctorInsuranceAppts = 0;
        $totalDoctorCashRevenue = 0;
        $totalDoctorInsuranceCopay = 0;
        $totalDoctorInsuranceClaim = 0;
        $totalDoctorGross = 0;
        $totalDoctorDocShare = 0;
        $totalDoctorHospShare = 0;
        $totalDoctorLabRefs = 0;
        $totalDoctorRadRefs = 0;
        $totalDoctorSurgRefs = 0;

        foreach ($consultantDoctors as $doc) {
            $docId = $doc->id;
            $rev = $doctorRevenues->get($docId);

            $cashCnt = (int) ($rev->cash_cnt ?? 0);
            $insCnt = (int) ($rev->insurance_cnt ?? 0);
            $totalAppts = (int) ($rev->total_cnt ?? ($cashCnt + $insCnt));

            $cashRevenue = (float) ($rev->cash_revenue ?? 0);
            $insuranceCopay = (float) ($rev->insurance_copay ?? 0);
            $insuranceClaim = (float) ($rev->insurance_claim ?? 0);
            $gross = (float) ($rev->gross ?? 0);
            $docShare = (float) ($rev->doc_share ?? 0);
            $hospShare = (float) ($rev->hosp_share ?? 0);

            $labRefs = (int) ($doctorLabReferrals[$docId] ?? 0);
            $radRefs = (int) ($doctorRadReferrals[$docId] ?? 0);
            $surgRefs = (int) ($doctorSurgeries[$docId] ?? 0);
            $totalRefs = $labRefs + $radRefs + $surgRefs;

            // ندرج الأطباء الذين لديهم حجوزات أو مبالغ أو تحويلات
            if ($totalAppts > 0 || $gross > 0 || $totalRefs > 0) {
                $doctorAnalytics[] = [
                    'id' => $docId,
                    'name' => optional($doc->user)->name ?? 'طبيب',
                    'department' => optional($doc->department)->name ?? ($doc->specialization ?: 'استشارية'),
                    'cash_cnt' => $cashCnt,
                    'insurance_cnt' => $insCnt,
                    'total_appts' => $totalAppts,
                    'cash_revenue' => $cashRevenue,
                    'insurance_copay' => $insuranceCopay,
                    'insurance_claim' => $insuranceClaim,
                    'gross' => $gross,
                    'doc_share' => $docShare,
                    'hosp_share' => $hospShare,
                    'lab_refs' => $labRefs,
                    'rad_refs' => $radRefs,
                    'surg_refs' => $surgRefs,
                    'total_refs' => $totalRefs,
                ];

                $totalDoctorCashAppts += $cashCnt;
                $totalDoctorInsuranceAppts += $insCnt;
                $totalDoctorCashRevenue += $cashRevenue;
                $totalDoctorInsuranceCopay += $insuranceCopay;
                $totalDoctorInsuranceClaim += $insuranceClaim;
                $totalDoctorGross += $gross;
                $totalDoctorDocShare += $docShare;
                $totalDoctorHospShare += $hospShare;
                $totalDoctorLabRefs += $labRefs;
                $totalDoctorRadRefs += $radRefs;
                $totalDoctorSurgRefs += $surgRefs;
            }
        }

        // ترتيب الأطباء حسب إجمالي المقبوض ثم عدد الحالات
        usort($doctorAnalytics, function ($a, $b) {
            if ($b['gross'] != $a['gross']) {
                return $b['gross'] <=> $a['gross'];
            }
            return $b['total_appts'] <=> $a['total_appts'];
        });

        $doctorSummaryTotals = [
            'total_cash_appts' => $totalDoctorCashAppts,
            'total_insurance_appts' => $totalDoctorInsuranceAppts,
            'total_appts' => $totalDoctorCashAppts + $totalDoctorInsuranceAppts,
            'total_cash_revenue' => $totalDoctorCashRevenue,
            'total_insurance_copay' => $totalDoctorInsuranceCopay,
            'total_insurance_claim' => $totalDoctorInsuranceClaim,
            'total_gross' => $totalDoctorGross,
            'total_doc_share' => $totalDoctorDocShare,
            'total_hosp_share' => $totalDoctorHospShare,
            'total_lab_refs' => $totalDoctorLabRefs,
            'total_rad_refs' => $totalDoctorRadRefs,
            'total_surg_refs' => $totalDoctorSurgRefs,
            'total_refs' => $totalDoctorLabRefs + $totalDoctorRadRefs + $totalDoctorSurgRefs,
        ];

        // جلب الحركات المفلترة مرتبة من الأحدث
        $transactions = $query->orderBy('performed_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(25)
            ->withQueryString();

        // بيانات مساعدة للمودالات والفلاتر
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        $suppliers = Supplier::orderBy('name')->get();
        $categories = FinancialTransaction::CATEGORIES;
        $paymentMethods = FinancialTransaction::PAYMENT_METHODS;

        return view('treasury.index', compact(
            'transactions',
            'currentTreasuryBalance',
            'totalAllTimeInflow',
            'totalAllTimeOutflow',
            'periodInflow',
            'periodOutflow',
            'periodDoctorPayouts',
            'periodPurchases',
            'periodOperational',
            'departmentStats',
            'departmentsConfig',
            'deptChartLabels',
            'deptChartData',
            'deptChartColors',
            'trendLabels',
            'trendInflows',
            'trendOutflows',
            'paymentMethodStats',
            'doctorAnalytics',
            'doctorSummaryTotals',
            'doctors',
            'suppliers',
            'categories',
            'paymentMethods'
        ));
    }

    /**
     * تسجيل سند صرف مصروف / مستحقات / مشتريات (Outflow)
     */
    public function storeExpense(Request $request)
    {
        $this->authorizeTreasuryAccess();

        $request->validate([
            'category'       => 'required|string',
            'amount'         => 'required|numeric|min:1',
            'description'    => 'required|string|max:500',
            'payment_method' => 'nullable|string',
            'performed_at'   => 'nullable|date',
            'doctor_id'      => 'nullable|exists:doctors,id',
            'supplier_id'    => 'nullable|exists:suppliers,id',
            'notes'          => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            $amount = (float)$request->amount;
            $performedAt = $request->filled('performed_at') ? Carbon::parse($request->performed_at) : now();
            $voucherNumber = 'EXP-' . now()->format('ymd') . '-' . rand(1000, 9999);

            $relatedType = null;
            $relatedId = null;

            // إذا كان الصرف لمستحقات طبيب
            if ($request->category === 'doctor_payout' && $request->filled('doctor_id')) {
                $doctor = Doctor::with('user')->findOrFail($request->doctor_id);
                $relatedType = Doctor::class;
                $relatedId = $doctor->id;

                // تحديث رصيد حساب الطبيب
                $account = DoctorFinancialAccount::firstOrCreate(['doctor_id' => $doctor->id]);
                $account->balance = round($account->balance - $amount, 2);
                $account->total_paid = round($account->total_paid + $amount, 2);
                $account->last_paid_at = $performedAt;
                $account->save();

                // معالجة المستحقات المعلقة
                $remaining = $amount;
                $pendingDues = DoctorDue::where('doctor_id', $doctor->id)
                    ->where('status', 'pending')
                    ->orderBy('created_at')
                    ->get();

                foreach ($pendingDues as $due) {
                    if ($remaining <= 0) {
                        break;
                    }

                    if ($due->amount <= $remaining) {
                        $remaining = round($remaining - $due->amount, 2);
                        $due->update([
                            'status'     => 'paid',
                            'paid_by_id' => Auth::id(),
                            'paid_at'    => $performedAt,
                        ]);
                    } else {
                        $due->amount = round($due->amount - $remaining, 2);
                        $due->save();

                        DoctorDue::create([
                            'doctor_id'  => $doctor->id,
                            'amount'     => $remaining,
                            'status'     => 'paid',
                            'notes'      => 'صرف جزئي لمستحقات الطبيب - سند ' . $voucherNumber,
                            'paid_by_id' => Auth::id(),
                            'paid_at'    => $performedAt,
                        ]);

                        $remaining = 0;
                    }
                }

                if ($remaining > 0) {
                    DoctorDue::create([
                        'doctor_id'  => $doctor->id,
                        'amount'     => $remaining,
                        'status'     => 'paid',
                        'notes'      => 'صرف للطبيب دون وجود مستحقات سابقة - سند ' . $voucherNumber,
                        'paid_by_id' => Auth::id(),
                        'paid_at'    => $performedAt,
                    ]);
                }
            } elseif ($request->category === 'purchase' && $request->filled('supplier_id')) {
                $supplier = Supplier::findOrFail($request->supplier_id);
                $relatedType = Supplier::class;
                $relatedId = $supplier->id;
            }

            // إنشاء قيد الصرف بالخزينة
            $tx = FinancialTransaction::create([
                'transaction_type' => 'expense',
                'voucher_type'     => 'outflow',
                'category'         => $request->category,
                'voucher_number'   => $voucherNumber,
                'related_type'     => $relatedType,
                'related_id'       => $relatedId,
                'amount'           => $amount,
                'currency'         => 'IQD',
                'payment_method'   => $request->payment_method ?: 'cash',
                'description'      => $request->description,
                'notes'            => $request->notes,
                'performed_by_id'  => Auth::id(),
                'performed_at'     => $performedAt,
            ]);

            \App\Services\UserActivityLogger::log(
                action: 'treasury_expense',
                category: 'treasury',
                description: "إصدار سند صرف مصروفات بمبلغ " . number_format($amount) . " د.ع ({$request->description}) - سند #{$voucherNumber}",
                subject: $tx,
                isSensitive: true,
                properties: ['amount' => $amount, 'voucher' => $voucherNumber, 'category' => $request->category]
            );

            DB::commit();
            return redirect()->route('treasury.index')->with('success', "تم تسجيل سند الصرف رقم {$voucherNumber} بمبلغ " . number_format($amount) . ' د.ع بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ سند الصرف: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * تسجيل سند قبض إيراد عام (Inflow)
     */
    public function storeIncome(Request $request)
    {
        $this->authorizeTreasuryAccess();

        $request->validate([
            'category'       => 'required|string',
            'amount'         => 'required|numeric|min:1',
            'description'    => 'required|string|max:500',
            'payment_method' => 'nullable|string',
            'performed_at'   => 'nullable|date',
            'notes'          => 'nullable|string|max:1000',
        ]);

        try {
            $amount = (float)$request->amount;
            $performedAt = $request->filled('performed_at') ? Carbon::parse($request->performed_at) : now();
            $voucherNumber = 'REV-' . now()->format('ymd') . '-' . rand(1000, 9999);

            $tx = FinancialTransaction::create([
                'transaction_type' => 'hospital_revenue',
                'voucher_type'     => 'inflow',
                'category'         => $request->category,
                'voucher_number'   => $voucherNumber,
                'amount'           => $amount,
                'currency'         => 'IQD',
                'payment_method'   => $request->payment_method ?: 'cash',
                'description'      => $request->description,
                'notes'            => $request->notes,
                'performed_by_id'  => Auth::id(),
                'performed_at'     => $performedAt,
            ]);

            \App\Services\UserActivityLogger::log(
                action: 'treasury_income',
                category: 'treasury',
                description: "إصدار سند قبض إيراد عام بمبلغ " . number_format($amount) . " د.ع ({$request->description}) - سند #{$voucherNumber}",
                subject: $tx,
                isSensitive: false,
                properties: ['amount' => $amount, 'voucher' => $voucherNumber]
            );

            return redirect()->route('treasury.index')->with('success', "تم تسجيل سند القبض رقم {$voucherNumber} بمبلغ " . number_format($amount) . ' د.ع بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء حفظ سند القبض: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * حذف حركة يدوية (غير مرتبطة بدفعات مرضى مباشرة)
     */
    public function destroy(FinancialTransaction $transaction)
    {
        $this->authorizeTreasuryAccess();

        if ($transaction->related_type === Payment::class) {
            return redirect()->back()->with('error', 'لا يمكن حذف الحركات المرتبطة مباشرة بإيصالات دفع المرضى من هذا القسم.');
        }

        $voucherNo = $transaction->voucher_number ?: '#' . $transaction->id;
        $desc = $transaction->description;
        $amt = number_format($transaction->amount);
        $transaction->delete();

        \App\Services\UserActivityLogger::log(
            action: 'delete_treasury_voucher',
            category: 'treasury',
            description: "حذف سند من الخزينة: {$voucherNo} بمبلغ {$amt} د.ع ({$desc})",
            subject: $transaction,
            isSensitive: true
        );

        return redirect()->route('treasury.index')->with('success', "تم حذف السند {$voucherNo} بنجاح.");
    }

    /**
     * تصدير كشف الخزينة إلى Excel
     */
    public function export(Request $request)
    {
        $this->authorizeTreasuryAccess();

        $query = FinancialTransaction::with(['performer', 'related']);

        if ($request->filled('from_date')) {
            $query->whereDate('performed_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('performed_at', '<=', $request->to_date);
        }
        if ($request->filled('voucher_type') && $request->voucher_type !== 'all') {
            if ($request->voucher_type === 'inflow') {
                $query->inflow();
            } elseif ($request->voucher_type === 'outflow') {
                $query->outflow();
            }
        }
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }
        if ($request->filled('payment_method') && $request->payment_method !== 'all') {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('voucher_number', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        $transactions = $query->orderBy('performed_at', 'desc')->get();

        return Excel::download(
            new HospitalTreasuryExport($transactions),
            'hospital_treasury_ledger_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
}
