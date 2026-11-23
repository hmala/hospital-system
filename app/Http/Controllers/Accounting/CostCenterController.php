<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CostCenter;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = CostCenter::with('parent')
            ->where('company_id', 1);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $rootCenters = CostCenter::where('company_id', 1)
            ->whereNull('parent_id')
            ->with(['children' => function ($q) {
                $q->with('children');
            }])
            ->orderBy('code')
            ->get();

        $stats = [
            'total' => CostCenter::where('company_id', 1)->count(),
            'clusters' => CostCenter::where('company_id', 1)->whereNull('parent_id')->count(),
            'operational' => CostCenter::where('company_id', 1)->where('code', 'LIKE', '1%')->count(),
            'diagnostic' => CostCenter::where('company_id', 1)->where('code', 'LIKE', '2%')->count(),
            'inventory' => CostCenter::where('company_id', 1)->where('code', 'LIKE', '3%')->count(),
            'admin' => CostCenter::where('company_id', 1)->where('code', 'LIKE', '5%')->count(),
        ];

        return view('accounting.cost-centers.index', compact('rootCenters', 'stats', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:acc_cost_centers,id',
            'code' => 'required|string|max:30|unique:acc_cost_centers,code',
            'name' => 'required|string|max:150',
        ], [
            'code.required' => 'كود مركز التكلفة مطلوب.',
            'code.unique' => 'كود مركز التكلفة مستخدم مسبقاً.',
            'name.required' => 'اسم مركز التكلفة مطلوب.',
        ]);

        $level = 1;
        if (! empty($validated['parent_id'])) {
            $parent = CostCenter::find($validated['parent_id']);
            $level = $parent ? $parent->level + 1 : 1;
        }

        CostCenter::create([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'parent_id' => $validated['parent_id'] ?? null,
            'level' => $level,
            'is_active' => true,
            'company_id' => 1,
        ]);

        return redirect()->route('accounting.cost-centers.index')
            ->with('success', "تمت إضافة مركز التكلفة ({$validated['name']}) بنجاح.");
    }
}
