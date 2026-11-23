<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Account::with('parent')
            ->where('company_id', 1);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        // Roots for tree navigation
        $rootAccounts = Account::where('company_id', 1)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with('children');
            }])
            ->orderBy('code')
            ->get();

        $stats = [
            'total' => Account::where('company_id', 1)->count(),
            'postable' => Account::where('company_id', 1)->where('is_postable', true)->count(),
            'headers' => Account::where('company_id', 1)->where('is_postable', false)->count(),
            'assets' => Account::where('company_id', 1)->where('account_type', 'asset')->count(),
            'liabilities' => Account::where('company_id', 1)->where('account_type', 'liability')->count(),
            'equity' => Account::where('company_id', 1)->where('account_type', 'equity')->count(),
            'expenses' => Account::where('company_id', 1)->where('account_type', 'expense')->count(),
            'revenues' => Account::where('company_id', 1)->where('account_type', 'revenue')->count(),
        ];

        return view('accounting.chart.index', compact('rootAccounts', 'stats', 'search'));
    }

    public function getDetails(Account $account): JsonResponse
    {
        $account->load(['parent', 'children']);

        $balance = $account->getBalance();

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
                'is_postable' => $account->is_postable,
                'parent' => $account->parent ? [
                    'id' => $account->parent->id,
                    'code' => $account->parent->code,
                    'name' => $account->parent->name,
                ] : null,
                'children_count' => $account->children->count(),
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
            return response()->json(['success' => false, 'message' => 'الحساب الأب غير موجود'], 404);
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
            'parent_id.required' => 'يجب اختيار الحساب الأب.',
            'code.required' => 'رقم / كود الحساب مطلوب.',
            'code.unique' => 'رقم الحساب هذا مستخدم مسبقاً، يرجى اختيار رقم آخر.',
            'name.required' => 'اسم الحساب مطلوب.',
        ]);

        $parent = Account::findOrFail($validated['parent_id']);
        $level = min(strlen($validated['code']), 6);
        $isPostable = ($level >= 5);

        DB::transaction(function () use ($validated, $parent, $level, $isPostable) {
            // If parent was postable, now it has a child so it ceases to be directly postable
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
            ->with('success', "تمت إضافة الحساب ({$validated['name']} - {$validated['code']}) بنجاح.");
    }
}
