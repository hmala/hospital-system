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
