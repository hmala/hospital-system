<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Currency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $accounts = Account::where('company_id', 1)
            ->withCount(['children', 'lines'])
            ->orderBy('code')
            ->get();

        $treeAccounts = $accounts->map(fn (Account $acc) => [
            'id' => $acc->id,
            'code' => $acc->code,
            'name' => $acc->name,
            'parent_id' => $acc->parent_id,
            'level' => $acc->level,
            'account_type' => $acc->account_type,
            'nature' => $acc->nature,
            'is_postable' => $acc->is_postable && $acc->children_count === 0,
            'children_count' => $acc->children_count,
            'lines_count' => $acc->lines_count,
        ])->values();

        $stats = [
            'total' => $accounts->count(),
            'postable' => $accounts->filter(fn (Account $a) => $a->is_postable && $a->children_count === 0)->count(),
            'headers' => $accounts->filter(fn (Account $a) => ! $a->is_postable || $a->children_count > 0)->count(),
            'assets' => $accounts->where('account_type', 'asset')->count(),
            'liabilities' => $accounts->where('account_type', 'liability')->count(),
            'equity' => $accounts->where('account_type', 'equity')->count(),
            'expenses' => $accounts->where('account_type', 'expense')->count(),
            'revenues' => $accounts->where('account_type', 'revenue')->count(),
        ];

        $defaultCurrency = Currency::getDefault();
        $foreignCurrency = Currency::where('is_default', false)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        return view('accounting.chart.index', compact(
            'treeAccounts',
            'stats',
            'search',
            'defaultCurrency',
            'foreignCurrency'
        ));
    }

    public function getDetails(Account $account): JsonResponse
    {
        $account->load(['parent', 'children'])
            ->loadCount(['children', 'lines']);

        $balance = $account->getBalance();
        $hasChildren = $account->children_count > 0;
        $hasTransactions = $account->lines_count > 0;
        $canPost = $account->is_postable && ! $hasChildren;
        $canAddBranch = ! $hasTransactions;

        // Recent 10 transactions if postable
        $recentLines = $account->lines()
            ->with(['entry', 'costCenter'])
            ->latest('id')
            ->take(10)
            ->get()
            ->map(function ($line) {
                return [
                    'id' => $line->id,
                    'entry_number' => $line->entry ? $line->entry->entry_number : '-',
                    'date' => $line->entry ? $line->entry->date->format('Y-m-d') : '-',
                    'description' => $line->description ?: ($line->entry ? $line->entry->description : ''),
                    'cost_center' => $line->costCenter ? $line->costCenter->name : '-',
                    'debit' => (float) $line->debit,
                    'credit' => (float) $line->credit,
                    'status' => $line->entry ? $line->entry->status : 'posted',
                ];
            });

        return response()->json([
            'success' => true,
            'account' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'level' => $account->level,
                'account_type' => $account->account_type,
                'nature' => $account->nature,
                'is_postable' => $canPost,
                'has_children' => $hasChildren,
                'has_transactions' => $hasTransactions,
                'can_add_branch' => $canAddBranch,
                'add_branch_block_reason' => $canAddBranch
                    ? null
                    : 'لا يمكن إضافة فرع تحت هذا الحساب لوجود قيود محاسبية مسجلة عليه مسبقاً.',
                'parent' => $account->parent ? [
                    'id' => $account->parent->id,
                    'code' => $account->parent->code,
                    'name' => $account->parent->name,
                ] : null,
                'children_count' => $account->children_count,
                'lines_count' => $account->lines_count,
                'balance' => $balance,
                'recent_transactions' => $recentLines,
            ],
        ]);
    }

    public function suggestCode(Request $request): JsonResponse
    {
        $parentId = $request->query('parent_id');
        $parent = Account::find($parentId);

        if (! $parent) {
            return response()->json(['success' => false, 'message' => 'الحساب الأصل غير موجود'], 404);
        }

        if ($parent->lines()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة فرع تحت هذا الحساب لوجود قيود محاسبية مسجلة عليه مسبقاً.',
            ], 422);
        }

        // Find last child code
        $lastChild = Account::where('parent_id', $parent->id)
            ->orderBy('code', 'desc')
            ->first();

        $nextCode = '';
        if ($lastChild && strlen($lastChild->code) > strlen($parent->code)) {
            $suffix = substr($lastChild->code, strlen($parent->code));
            if (is_numeric($suffix)) {
                $nextSeq = (int) $suffix + 1;
                $nextCode = $parent->code.str_pad((string) $nextSeq, strlen($suffix), '0', STR_PAD_LEFT);
            }
        }

        if (! $nextCode) {
            $nextCode = $parent->code.'1';
        }

        $level = min(strlen($nextCode), 6);
        $isPostable = ($level >= 5);

        return response()->json([
            'success' => true,
            'suggested_code' => $nextCode,
            'level' => $level,
            'is_postable' => $isPostable,
            'account_type' => $parent->account_type,
            'nature' => $parent->nature,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'required|exists:acc_accounts,id',
            'code' => 'required|string|max:30|unique:acc_accounts,code',
            'name' => 'required|string|max:255',
        ], [
            'parent_id.required' => 'يجب اختيار الحساب الأصل.',
            'code.required' => 'رقم / كود الحساب مطلوب.',
            'code.unique' => 'رقم الحساب هذا مستخدم مسبقاً، يرجى اختيار رقم آخر.',
            'name.required' => 'اسم الحساب مطلوب.',
        ]);

        $parent = Account::findOrFail($validated['parent_id']);

        if ($parent->lines()->exists()) {
            return back()->withInput()->withErrors([
                'parent_id' => 'لا يمكن إضافة فرع تحت هذا الحساب لوجود قيود محاسبية مسجلة عليه مسبقاً.',
            ]);
        }

        $level = min(strlen($validated['code']), 6);
        $isPostable = ($level >= 5);

        DB::transaction(function () use ($validated, $parent, $level, $isPostable) {
            // Once an account has a branch, it becomes an aggregate origin and cannot accept direct journal entries
            if ($parent->is_postable) {
                $parent->update(['is_postable' => false]);
            }

            Account::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'parent_id' => $parent->id,
                'level' => $level,
                'account_type' => $parent->account_type,
                'nature' => $parent->nature,
                'is_postable' => $isPostable,
                'is_active' => true,
                'company_id' => 1,
            ]);
        });

        return redirect()->route('accounting.chart.index')
            ->with('success', "تمت إضافة الفرع ({$validated['name']} - {$validated['code']}) تحت الأصل ({$parent->name}) بنجاح.");
    }
}
