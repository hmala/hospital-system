<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\LabTest;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Use package-specific permissions: viewing allowed to users with 'view packages', management to 'create/edit/delete packages'
        $this->middleware('permission:view packages')->only(['index']);
        $this->middleware('permission:create packages')->only(['create', 'store']);
        $this->middleware('permission:edit packages')->only(['edit', 'update']);
        $this->middleware('permission:delete packages')->only(['destroy']);
    }

    public function index()
    {
        $packages = Package::orderBy('name')->paginate(20);
        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        $tests = LabTest::orderBy('name')->get();
        return view('admin.packages.create', compact('tests'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'tests' => 'nullable|array',
            'tests.*' => 'integer|exists:lab_tests,id',
        ]);

        $package = Package::create([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
        ]);

        if (!empty($data['tests'])) {
            $package->labTests()->sync($data['tests']);
        }

        return redirect()->route('admin.packages.index')->with('success', 'تم إنشاء الباقة');
    }

    public function edit(Package $package)
    {
        $tests = LabTest::orderBy('name')->get();
        $selected = $package->labTests()->pluck('lab_tests.id')->toArray();
        return view('admin.packages.edit', compact('package', 'tests', 'selected'));
    }

    public function update(Request $request, Package $package)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'price' => 'nullable|numeric',
            'is_active' => 'nullable|boolean',
            'tests' => 'nullable|array',
            'tests.*' => 'integer|exists:lab_tests,id',
        ]);

        $package->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? $package->code,
            'description' => $data['description'] ?? $package->description,
            'price' => $data['price'] ?? $package->price,
            'is_active' => $data['is_active'] ?? $package->is_active,
        ]);

        $package->labTests()->sync($data['tests'] ?? []);

        return redirect()->route('admin.packages.index')->with('success', 'تم تحديث الباقة');
    }

    public function destroy(Package $package)
    {
        $package->labTests()->detach();
        $package->delete();
        return redirect()->route('admin.packages.index')->with('success', 'تم حذف الباقة');
    }
}
