<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AccountingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:view accounting']);
    }

    /**
     * لوحة تحكم نظام الحسابات
     */
    public function dashboard()
    {
        $today      = Carbon::today();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();
        $yearStart  = Carbon::now()->startOfYear();
        $yearEnd    = Carbon::now()->endOfYear();

        // ---- الإيرادات ----
        $todayRevenue   = Payment::whereDate('paid_at', $today)->sum('amount');
        $monthRevenue   = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount');
        $yearRevenue    = Payment::whereBetween('paid_at', [$yearStart, $yearEnd])->sum('amount');

        // الإيرادات حسب النوع (الشهر الحالي)
        $revenueByType = Payment::whereBetween('paid_at', [$monthStart, $monthEnd])
            ->whereNotNull('paid_at')
            ->select('payment_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_type')
            ->get()
            ->keyBy('payment_type');

        // ---- المصروفات ----
        $todayExpenses  = Expense::whereDate('expense_date', $today)->where('status', 'approved')->sum('amount');
        $monthExpenses  = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])->where('status', 'approved')->sum('amount');
        $yearExpenses   = Expense::whereBetween('expense_date', [$yearStart, $yearEnd])->where('status', 'approved')->sum('amount');
        $pendingExpensesCount = Expense::where('status', 'pending')->count();

        // المصروفات حسب الفئة (الشهر الحالي)
        $expensesByCategory = Expense::whereBetween('expense_date', [$monthStart, $monthEnd])
            ->where('status', 'approved')
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->get()
            ->keyBy('category');

        // ---- صافي الربح ----
        $todayNet  = $todayRevenue  - $todayExpenses;
        $monthNet  = $monthRevenue  - $monthExpenses;
        $yearNet   = $yearRevenue   - $yearExpenses;

        // ---- الإيرادات اليومية للأيام الـ 30 الماضية (للمخطط) ----
        $last30Days = Payment::whereDate('paid_at', '>=', Carbon::now()->subDays(29))
            ->whereNotNull('paid_at')
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date');

        $chartLabels  = [];
        $chartRevenue = [];
        $chartExpenses = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $chartLabels[]   = Carbon::now()->subDays($i)->format('d/m');
            $chartRevenue[]  = (float) ($last30Days[$date] ?? 0);

            $dayExpenses = Expense::whereDate('expense_date', $date)->where('status', 'approved')->sum('amount');
            $chartExpenses[] = (float) $dayExpenses;
        }

        // ---- آخر 10 مدفوعات ----
        $latestPayments = Payment::with(['patient.user', 'cashier'])
            ->whereNotNull('paid_at')
            ->orderBy('paid_at', 'desc')
            ->limit(10)
            ->get();

        // ---- آخر 10 مصروفات ----
        $latestExpenses = Expense::with('createdBy')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('accounting.dashboard', compact(
            'todayRevenue', 'monthRevenue', 'yearRevenue',
            'todayExpenses', 'monthExpenses', 'yearExpenses',
            'todayNet', 'monthNet', 'yearNet',
            'revenueByType', 'expensesByCategory',
            'pendingExpensesCount',
            'chartLabels', 'chartRevenue', 'chartExpenses',
            'latestPayments', 'latestExpenses'
        ));
    }

    /**
     * قائمة المصروفات
     */
    public function expensesIndex(Request $request)
    {
        $query = Expense::with(['createdBy', 'approvedBy'])->latest();

        if ($request->filled('from_date')) {
            $query->whereDate('expense_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('expense_date', '<=', $request->to_date);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses      = $query->paginate(20);
        $totalAmount   = Expense::when($request->filled('from_date'), fn($q) => $q->whereDate('expense_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn($q) => $q->whereDate('expense_date', '<=', $request->to_date))
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->filled('status'), fn($q) => $q->where('status', $request->status))
            ->where('status', 'approved')
            ->sum('amount');

        $categories    = Expense::CATEGORIES;
        $statuses      = Expense::STATUSES;

        return view('accounting.expenses.index', compact('expenses', 'totalAmount', 'categories', 'statuses'));
    }

    /**
     * نموذج إضافة مصروف جديد
     */
    public function createExpense()
    {
        $this->authorize('create expenses');
        $categories     = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;
        return view('accounting.expenses.create', compact('categories', 'paymentMethods'));
    }

    /**
     * حفظ مصروف جديد
     */
    public function storeExpense(Request $request)
    {
        $this->authorize('create expenses');

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'category'         => 'required|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'payment_method'   => 'required|in:' . implode(',', array_keys(Expense::PAYMENT_METHODS)),
            'expense_date'     => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'vendor'           => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['status']     = 'pending';

        Expense::create($validated);

        return redirect()->route('accounting.expenses.index')
            ->with('success', 'تم إضافة المصروف بنجاح وهو بانتظار الموافقة');
    }

    /**
     * نموذج تعديل مصروف
     */
    public function editExpense(Expense $expense)
    {
        $this->authorize('edit expenses');
        $categories     = Expense::CATEGORIES;
        $paymentMethods = Expense::PAYMENT_METHODS;
        return view('accounting.expenses.edit', compact('expense', 'categories', 'paymentMethods'));
    }

    /**
     * تحديث مصروف
     */
    public function updateExpense(Request $request, Expense $expense)
    {
        $this->authorize('edit expenses');

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string|max:1000',
            'amount'           => 'required|numeric|min:0.01',
            'category'         => 'required|in:' . implode(',', array_keys(Expense::CATEGORIES)),
            'payment_method'   => 'required|in:' . implode(',', array_keys(Expense::PAYMENT_METHODS)),
            'expense_date'     => 'required|date',
            'reference_number' => 'nullable|string|max:100',
            'vendor'           => 'nullable|string|max:255',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $expense->update($validated);

        return redirect()->route('accounting.expenses.index')
            ->with('success', 'تم تحديث المصروف بنجاح');
    }

    /**
     * حذف مصروف
     */
    public function destroyExpense(Expense $expense)
    {
        $this->authorize('delete expenses');
        $expense->delete();
        return redirect()->route('accounting.expenses.index')
            ->with('success', 'تم حذف المصروف بنجاح');
    }

    /**
     * الموافقة على مصروف
     */
    public function approveExpense(Expense $expense)
    {
        $this->authorize('approve expenses');
        $expense->update([
            'status'      => 'approved',
            'approved_by' => Auth::id(),
        ]);
        return redirect()->back()->with('success', 'تمت الموافقة على المصروف');
    }

    /**
     * رفض مصروف
     */
    public function rejectExpense(Expense $expense)
    {
        $this->authorize('approve expenses');
        $expense->update(['status' => 'rejected']);
        return redirect()->back()->with('success', 'تم رفض المصروف');
    }

    /**
     * تقرير الإيرادات
     */
    public function revenueReport(Request $request)
    {
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $toDate   = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // إجمالي الإيرادات
        $totalRevenue = Payment::whereBetween('paid_at', [$fromDate, $toDate])
            ->whereNotNull('paid_at')
            ->sum('amount');

        // الإيرادات حسب النوع
        $revenueByType = Payment::whereBetween('paid_at', [$fromDate, $toDate])
            ->whereNotNull('paid_at')
            ->select('payment_type', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_type')
            ->orderBy('total', 'desc')
            ->get();

        // الإيرادات حسب طريقة الدفع
        $revenueByMethod = Payment::whereBetween('paid_at', [$fromDate, $toDate])
            ->whereNotNull('paid_at')
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        // الإيرادات اليومية
        $dailyRevenue = Payment::whereBetween('paid_at', [$fromDate, $toDate])
            ->whereNotNull('paid_at')
            ->select(DB::raw('DATE(paid_at) as date'), DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // إجمالي المصروفات في نفس الفترة
        $totalExpenses = Expense::whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'approved')
            ->sum('amount');

        $netProfit = $totalRevenue - $totalExpenses;

        $paymentTypeNames = Payment::PAYMENT_TYPES;
        $paymentMethodNames = Payment::PAYMENT_METHODS;

        return view('accounting.reports.revenue', compact(
            'fromDate', 'toDate',
            'totalRevenue', 'totalExpenses', 'netProfit',
            'revenueByType', 'revenueByMethod', 'dailyRevenue',
            'paymentTypeNames', 'paymentMethodNames'
        ));
    }

    /**
     * تقرير المصروفات
     */
    public function expensesReport(Request $request)
    {
        $fromDate = $request->filled('from_date')
            ? Carbon::parse($request->from_date)->startOfDay()
            : Carbon::now()->startOfMonth();
        $toDate   = $request->filled('to_date')
            ? Carbon::parse($request->to_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        $totalExpenses = Expense::whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'approved')
            ->sum('amount');

        $expensesByCategory = Expense::whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'approved')
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        $expensesByMethod = Expense::whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'approved')
            ->select('payment_method', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get();

        $dailyExpenses = Expense::whereBetween('expense_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->where('status', 'approved')
            ->select('expense_date', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get();

        $categoryNames = Expense::CATEGORIES;
        $methodNames   = Expense::PAYMENT_METHODS;

        return view('accounting.reports.expenses', compact(
            'fromDate', 'toDate',
            'totalExpenses',
            'expensesByCategory', 'expensesByMethod', 'dailyExpenses',
            'categoryNames', 'methodNames'
        ));
    }
}
