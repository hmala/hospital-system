<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Accounting\Currency;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Services\Accounting\TafqeetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = JournalEntry::with(['lines.account', 'lines.costCenter', 'postedBy'])
            ->where('company_id', 1);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $entries = $query->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_entries' => JournalEntry::where('company_id', 1)->count(),
            'posted' => JournalEntry::where('company_id', 1)->where('status', 'posted')->count(),
            'reversed' => JournalEntry::where('company_id', 1)->where('status', 'reversed')->count(),
            'total_debit' => (float) JournalEntryLine::whereHas('entry', fn ($q) => $q->where('status', 'posted'))->sum('debit'),
        ];

        return view('accounting.vouchers.index', compact('entries', 'stats'));
    }

    public function create(string $type = 'journal')
    {
        $validTypes = ['journal', 'receipt', 'payment'];
        if (! in_array($type, $validTypes)) {
            $type = 'journal';
        }

        // Only postable leaf accounts (Level 5 or 6 with no branches)
        $accounts = Account::where('company_id', 1)
            ->postable()
            ->orderBy('code')
            ->get();

        $costCenters = CostCenter::where('company_id', 1)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $currencies = Currency::where('is_active', true)->get();
        $defaultCurrency = Currency::where('is_default', true)->first();
        $usdCurrency = Currency::where('code', 'USD')->first();

        // Cash / Bank accounts for quick receipt/payment selection
        $cashAccounts = Account::where('company_id', 1)
            ->postable()
            ->where('code', 'LIKE', '134%')
            ->orderBy('code')
            ->get();

        return view('accounting.vouchers.create', compact(
            'type',
            'accounts',
            'costCenters',
            'currencies',
            'defaultCurrency',
            'usdCurrency',
            'cashAccounts'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'description' => 'required|string|max:500',
            'voucher_type' => 'required|in:journal,receipt,payment',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:acc_accounts,id',
            'lines.*.cost_center_id' => 'nullable|exists:acc_cost_centers,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
            'currency_id' => 'nullable|exists:acc_currencies,id',
            'exchange_rate' => 'nullable|numeric|min:0.0001',
        ], [
            'date.required' => 'تاريخ السند مطلوب.',
            'description.required' => 'البيان العام للسند مطلوب.',
            'lines.min' => 'يجب إدخال طرفين على الأقل للقيد المحاسبي (مدين ودائن).',
            'lines.*.account_id.required' => 'يجب اختيار حساب لكل طرف في القيد.',
        ]);

        // Calculate totals
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($validated['lines'] as $line) {
            $totalDebit += (float) $line['debit'];
            $totalCredit += (float) $line['credit'];

            // Ensure account is postable and has no child branches
            $acc = Account::find($line['account_id']);
            if ($acc && ! $acc->canReceiveEntries()) {
                return back()->withInput()->withErrors([
                    'lines' => "الحساب ({$acc->name} - {$acc->code}) هو حساب أصل تجميعي (أو متفرّع) ولا يقبل التقييد المباشر. يرجى اختيار فرع تحليلي نهائي (مستوى 5 أو 6 بدون فروع).",
                ]);
            }
        }

        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            return back()->withInput()->withErrors([
                'lines' => 'القيد غير متوازن! إجمالي المدين ('.number_format($totalDebit, 2).') لا يساوي إجمالي الدائن ('.number_format($totalCredit, 2).'). فرق: '.number_format(abs($totalDebit - $totalCredit), 2),
            ]);
        }

        if ($totalDebit <= 0) {
            return back()->withInput()->withErrors([
                'lines' => 'يجب أن يكون مبلغ القيد أكبر من صفر.',
            ]);
        }

        // Fiscal period check
        $period = FiscalPeriod::forDate($validated['date']);
        if ($period && $period->is_closed) {
            return back()->withInput()->withErrors([
                'date' => "الفترة المالية لهذا التاريخ ({$period->name}) مقفلة رسمياً، ولا يمكن إضافة قيود جديدة فيها.",
            ]);
        }

        $fiscalYear = FiscalYear::current();

        // Handle attachment
        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('accounting/vouchers', 'public');
        }

        $entry = DB::transaction(function () use ($validated, $fiscalYear, $period, $attachmentPath) {
            $entryNumber = JournalEntry::generateEntryNumber();

            $entry = JournalEntry::create([
                'entry_number' => $entryNumber,
                'date' => $validated['date'],
                'fiscal_year_id' => $fiscalYear ? $fiscalYear->id : null,
                'fiscal_period_id' => $period ? $period->id : null,
                'reference_type' => 'ManualVoucher',
                'reference_id' => null,
                'description' => $validated['description'].($attachmentPath ? " [مرفق: {$attachmentPath}]" : ''),
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'company_id' => 1,
            ]);

            $currencyId = $validated['currency_id'] ?? null;
            $exchangeRate = $validated['exchange_rate'] ?? 1.0;

            foreach ($validated['lines'] as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'cost_center_id' => $line['cost_center_id'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                    'currency_id' => $currencyId,
                    'exchange_rate' => $exchangeRate,
                    'currency_amount' => $currencyId && $exchangeRate > 0 ? (max($line['debit'], $line['credit']) / $exchangeRate) : null,
                    'description' => $line['description'] ?: $validated['description'],
                ]);
            }

            return $entry;
        });

        return redirect()->route('accounting.vouchers.print', $entry->id)
            ->with('success', "تم حفظ وترحيل السند بنجاح برقم ({$entry->entry_number}).");
    }

    public function print(JournalEntry $entry)
    {
        $entry->load(['lines.account', 'lines.costCenter', 'postedBy', 'fiscalYear', 'fiscalPeriod']);

        $tafqeet = TafqeetService::inIraqiDinars($entry->total_debit);

        return view('accounting.vouchers.print', compact('entry', 'tafqeet'));
    }

    public function reverse(Request $request, JournalEntry $entry)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ], [
            'reason.required' => 'يرجى كتابة سبب عكس القيد الإلزامي للتدقيق والرقابة.',
        ]);

        try {
            $reversal = $entry->reverse(Auth::id(), $validated['reason']);

            return redirect()->route('accounting.vouchers.index')
                ->with('success', "تم عكس القيد ({$entry->entry_number}) بنجاح بموجب القيد العكسي رقم ({$reversal->entry_number}).");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function tafqeet(Request $request): JsonResponse
    {
        $amount = (float) $request->query('amount', 0);
        $words = TafqeetService::inIraqiDinars($amount);

        return response()->json(['words' => $words]);
    }
}
