<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\AuditLog;
use App\Models\Accounting\Currency;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingSettingsController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        $defaultCurrency = $currencies->firstWhere('is_default', true) ?? $currencies->first();
        $fiscalYears = FiscalYear::with('periods')->orderByDesc('start_date')->get();

        $revaluations = $this->calculateRevaluations($currencies);

        $currencyLogs = AuditLog::with('user')
            ->where('auditable_type', Currency::class)
            ->latest('id')
            ->take(30)
            ->get();

        return view('accounting.settings.index', compact(
            'currencies',
            'defaultCurrency',
            'fiscalYears',
            'revaluations',
            'currencyLogs'
        ));
    }

    public function updateCurrency(Request $request, Currency $currency)
    {
        $validated = $request->validate([
            'exchange_rate' => 'required|numeric|min:0.0001|max:99999999',
            'is_official' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'reason' => 'required|string|max:500',
        ], [
            'exchange_rate.required' => 'سعر الصرف مطلوب.',
            'exchange_rate.min' => 'يجب أن يكون سعر الصرف أكبر من صفر.',
            'reason.required' => 'يرجى كتابة سبب تعديل العملة أو سعر الصرف للأرشفة والتدقيق.',
        ]);

        $setAsDefault = (bool) ($validated['is_default'] ?? false);
        $newRate = $setAsDefault ? 1.0000 : (float) $validated['exchange_rate'];

        $oldValues = [
            'code' => $currency->code,
            'name' => $currency->name,
            'exchange_rate' => (float) $currency->exchange_rate,
            'is_default' => (bool) $currency->is_default,
            'is_official' => (bool) $currency->is_official,
            'is_active' => (bool) $currency->is_active,
        ];

        DB::transaction(function () use ($request, $currency, $validated, $setAsDefault, $newRate, $oldValues) {
            if ($setAsDefault) {
                Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
            }

            $currency->update([
                'exchange_rate' => $newRate,
                'is_default' => $setAsDefault ? true : $currency->is_default,
                'is_official' => $request->has('is_official') ? (bool) $request->boolean('is_official') : $currency->is_official,
                'is_active' => $setAsDefault ? true : ($request->has('is_active') ? (bool) $request->boolean('is_active') : $currency->is_active),
            ]);

            $newValues = [
                'code' => $currency->code,
                'name' => $currency->name,
                'exchange_rate' => (float) $currency->exchange_rate,
                'rate_diff' => round((float) $currency->exchange_rate - $oldValues['exchange_rate'], 4),
                'is_default' => (bool) $currency->is_default,
                'is_official' => (bool) $currency->is_official,
                'is_active' => (bool) $currency->is_active,
            ];

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'currency_updated',
                'auditable_type' => Currency::class,
                'auditable_id' => $currency->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'reason' => $validated['reason'],
                'ip_address' => $request->ip(),
            ]);
        });

        $reval = $this->calculateRevaluations(collect([$currency->fresh()]))->first();
        $message = "تم تحديث سعر صرف ({$currency->name} - {$currency->code}) إلى "
            .number_format((float) $currency->fresh()->exchange_rate, 2)
            .' وأرشفة التغيير في سجل التدقيق.';

        if ($reval && abs($reval['total_difference']) >= 0.01) {
            $message .= ' تم رصد فروق صرف بقيمة '.number_format(abs($reval['total_difference']), 2).' د.ع في جدول تسوية فروق الصرف أدناه.';
        }

        return redirect()->route('accounting.settings.index')->with('success', $message);
    }

    public function storeCurrency(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:3|alpha|unique:acc_currencies,code',
            'name' => 'required|string|max:50',
            'symbol' => 'required|string|max:10',
            'exchange_rate' => 'required|numeric|min:0.0001|max:99999999',
            'is_official' => 'nullable|boolean',
        ], [
            'code.required' => 'رمز العملة مطلوب (3 أحرف مثل EUR).',
            'code.size' => 'رمز العملة يجب أن يتكون من 3 أحرف إنجليزية.',
            'code.unique' => 'هذه العملة مسجلة مسبقاً.',
            'name.required' => 'اسم العملة مطلوب.',
            'symbol.required' => 'رمز العملة المختصر مطلوب.',
            'exchange_rate.required' => 'سعر الصرف مطلوب.',
        ]);

        $currency = DB::transaction(function () use ($request, $validated) {
            $currency = Currency::create([
                'code' => strtoupper($validated['code']),
                'name' => $validated['name'],
                'symbol' => $validated['symbol'],
                'exchange_rate' => $validated['exchange_rate'],
                'is_default' => false,
                'is_official' => (bool) ($validated['is_official'] ?? false),
                'is_active' => true,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'currency_created',
                'auditable_type' => Currency::class,
                'auditable_id' => $currency->id,
                'old_values' => null,
                'new_values' => [
                    'code' => $currency->code,
                    'name' => $currency->name,
                    'exchange_rate' => (float) $currency->exchange_rate,
                ],
                'reason' => 'إضافة عملة جديدة إلى المنظومة المالية',
                'ip_address' => $request->ip(),
            ]);

            return $currency;
        });

        return redirect()->route('accounting.settings.index')
            ->with('success', "تمت إضافة العملة ({$currency->name} - {$currency->code}) بسعر صرف ".number_format((float) $currency->exchange_rate, 2).' بنجاح.');
    }

    public function revalueCurrency(Request $request, Currency $currency)
    {
        if ($currency->is_default) {
            return back()->with('error', 'لا توجد فروق صرف للعملة الأساسية للنظام.');
        }

        $reval = $this->calculateRevaluations(collect([$currency]))->first();
        if (! $reval || empty($reval['accounts']) || round(abs($reval['total_abs_difference']), 2) <= 0) {
            return back()->with('error', "لا توجد فروق صرف مستحقة للتسوية على عملة ({$currency->name}) حالياً.");
        }

        [$lossAccount, $gainAccount] = $this->ensureFxAccounts();
        if (! $lossAccount || ! $gainAccount) {
            return back()->with('error', 'تعذر العثور على حسابات أرباح أو خسائر فروقات أسعار الصرف (33551 / 43551).');
        }

        $today = now()->toDateString();
        $period = FiscalPeriod::forDate($today);
        if ($period && $period->is_closed) {
            return back()->with('error', "الفترة المالية ({$period->name}) مقفلة ولا يمكن ترحيل قيد التسوية فيها.");
        }
        $fiscalYear = FiscalYear::current();

        $entry = DB::transaction(function () use ($request, $currency, $reval, $lossAccount, $gainAccount, $today, $period, $fiscalYear) {
            $entry = JournalEntry::create([
                'entry_number' => JournalEntry::generateEntryNumber(),
                'date' => $today,
                'fiscal_year_id' => $fiscalYear?->id,
                'fiscal_period_id' => $period?->id,
                'reference_type' => Currency::class,
                'reference_id' => $currency->id,
                'description' => "قيد تسوية فروق أسعار الصرف لعملة ({$currency->name} - {$currency->code}) على سعر ".number_format((float) $currency->exchange_rate, 2),
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'company_id' => 1,
            ]);

            foreach ($reval['accounts'] as $item) {
                $diff = $item['difference'];
                if (abs($diff) < 0.01) {
                    continue;
                }

                $absDiff = round(abs($diff), 2);
                $isGain = $diff > 0;

                // Monetary account adjustment line (currency_amount = 0 keeps FX balance intact while updating IQD book value)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $item['account']->id,
                    'debit' => $isGain ? $absDiff : 0,
                    'credit' => $isGain ? 0 : $absDiff,
                    'currency_id' => $currency->id,
                    'exchange_rate' => $currency->exchange_rate,
                    'currency_amount' => 0,
                    'description' => "تسوية فرق صرف ({$currency->code}) للحساب {$item['account']->name}",
                ]);

                // Counterpart FX Gain (43551) or FX Loss (33551)
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $isGain ? $gainAccount->id : $lossAccount->id,
                    'debit' => $isGain ? 0 : $absDiff,
                    'credit' => $isGain ? $absDiff : 0,
                    'currency_id' => $currency->id,
                    'exchange_rate' => $currency->exchange_rate,
                    'currency_amount' => 0,
                    'description' => $isGain
                        ? "أرباح فروقات أسعار الصرف ({$currency->code})"
                        : "خسائر فروقات أسعار الصرف ({$currency->code})",
                ]);
            }

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'currency_revalued',
                'auditable_type' => Currency::class,
                'auditable_id' => $currency->id,
                'old_values' => ['book_total' => $reval['total_book_base']],
                'new_values' => [
                    'revalued_total' => $reval['total_revalued_base'],
                    'net_difference' => $reval['total_difference'],
                    'journal_entry_id' => $entry->id,
                    'entry_number' => $entry->entry_number,
                    'exchange_rate' => (float) $currency->exchange_rate,
                ],
                'reason' => "ترحيل قيد تسوية فروق الصرف رقم {$entry->entry_number}",
                'ip_address' => $request->ip(),
            ]);

            return $entry;
        });

        return redirect()->route('accounting.settings.index')
            ->with('success', "تم ترحيل قيد تسوية فروق الصرف بنجاح برقم ({$entry->entry_number}).")
            ->with('revaluation_entry_id', $entry->id);
    }

    /**
     * Calculate unrealized FX differences for all non-default currencies across monetary accounts (Asset/Liability).
     */
    private function calculateRevaluations(Collection $currencies): Collection
    {
        $foreignCurrencies = $currencies->filter(fn (Currency $c) => ! $c->is_default);
        if ($foreignCurrencies->isEmpty()) {
            return collect();
        }

        $lines = JournalEntryLine::with('account')
            ->whereIn('currency_id', $foreignCurrencies->pluck('id'))
            ->whereHas('entry', fn ($q) => $q->where('company_id', 1)->where('status', 'posted'))
            ->whereHas('account', fn ($q) => $q->whereIn('account_type', ['asset', 'liability']))
            ->get();

        return $foreignCurrencies->map(function (Currency $currency) use ($lines) {
            $currencyLines = $lines->where('currency_id', $currency->id);
            $byAccount = $currencyLines->groupBy('account_id');

            $accountsData = [];
            $totalBook = 0.0;
            $totalRevalued = 0.0;
            $totalDiff = 0.0;
            $totalAbsDiff = 0.0;

            foreach ($byAccount as $accountLines) {
                $account = $accountLines->first()->account;
                if (! $account) {
                    continue;
                }

                $netFx = 0.0;
                $bookBase = 0.0;

                foreach ($accountLines as $line) {
                    $debit = (float) $line->debit;
                    $credit = (float) $line->credit;
                    $fx = (float) ($line->currency_amount ?? 0);

                    if ($debit > 0) {
                        $netFx += $fx;
                        $bookBase += $debit;
                    } elseif ($credit > 0) {
                        $netFx -= $fx;
                        $bookBase -= $credit;
                    }
                }

                $netFx = round($netFx, 2);
                $bookBase = round($bookBase, 2);
                $revaluedBase = round($netFx * (float) $currency->exchange_rate, 2);
                $diff = round($revaluedBase - $bookBase, 2);

                if (abs($netFx) < 0.01 && abs($diff) < 0.01) {
                    continue;
                }

                $accountsData[] = [
                    'account' => $account,
                    'net_fx' => $netFx,
                    'book_base' => $bookBase,
                    'revalued_base' => $revaluedBase,
                    'difference' => $diff,
                ];

                $totalBook += $bookBase;
                $totalRevalued += $revaluedBase;
                $totalDiff += $diff;
                $totalAbsDiff += abs($diff);
            }

            return [
                'currency' => $currency,
                'accounts' => $accountsData,
                'total_book_base' => round($totalBook, 2),
                'total_revalued_base' => round($totalRevalued, 2),
                'total_difference' => round($totalDiff, 2),
                'total_abs_difference' => round($totalAbsDiff, 2),
            ];
        })->values();
    }

    /**
     * Ensure level-5 FX Gain & Loss accounts exist under 4355 and 3355.
     */
    private function ensureFxAccounts(): array
    {
        $lossAccount = Account::where('code', '33551')->where('company_id', 1)->first();
        if (! $lossAccount) {
            $lossParent = Account::where('code', '3355')->where('company_id', 1)->first();
            if ($lossParent) {
                $lossParent->update(['is_postable' => false]);
                $lossAccount = Account::create([
                    'code' => '33551',
                    'name' => 'خسائر فروقات أسعار الصرف الأجنبي',
                    'parent_id' => $lossParent->id,
                    'level' => 5,
                    'account_type' => $lossParent->account_type,
                    'nature' => $lossParent->nature,
                    'is_postable' => true,
                    'is_active' => true,
                    'company_id' => 1,
                ]);
            }
        }

        $gainAccount = Account::where('code', '43551')->where('company_id', 1)->first();
        if (! $gainAccount) {
            $gainParent = Account::where('code', '4355')->where('company_id', 1)->first();
            if ($gainParent) {
                $gainParent->update(['is_postable' => false]);
                $gainAccount = Account::create([
                    'code' => '43551',
                    'name' => 'أرباح فروقات أسعار الصرف الأجنبي',
                    'parent_id' => $gainParent->id,
                    'level' => 5,
                    'account_type' => $gainParent->account_type,
                    'nature' => $gainParent->nature,
                    'is_postable' => true,
                    'is_active' => true,
                    'company_id' => 1,
                ]);
            }
        }

        return [$lossAccount, $gainAccount];
    }
}
