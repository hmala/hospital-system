<?php

namespace App\Http\Controllers;

use App\Models\SurgicalOperation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurgicalOperationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage surgical operations')->except(['index']);
        $this->middleware('permission:view surgical operations')->only(['index']);
    }

    /**
     * عرض قائمة العمليات الجراحية
     */
    public function index()
    {
        $operations = SurgicalOperation::orderBy('category')
            ->orderBy('name')
            ->get();

        $canEdit = auth()->check() && auth()->user()->can('manage surgical operations');

        return view('surgical-operations.index', compact('operations', 'canEdit'));
    }

    /**
     * عرض نموذج إضافة عملية جديدة
     */
    public function create()
    {
        if (!auth()->user()->can('manage surgical operations')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $categories = SurgicalOperation::distinct()->pluck('category')->sort();

        return view('surgical-operations.create', compact('categories'));
    }

    /**
     * حفظ عملية جديدة
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('manage surgical operations')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'new_category' => 'nullable|string|max:255',
            'is_active' => 'boolean'
        ]);

        $category = $request->category;
        if ($category === 'new') {
            $category = $request->new_category;
            if (empty($category)) {
                return back()->withInput()->withErrors(['new_category' => 'يجب إدخال اسم الصنف الجديد']);
            }
        }

        SurgicalOperation::create([
            'name' => $request->name,
            'category' => $category,
            'fee' => 0, // السعر دائمًا 0
            'is_active' => $request->has('is_active') ? $request->is_active : true
        ]);

        return redirect()->route('surgical-operations.index')->with('success', 'تم إضافة العملية الجراحية بنجاح: ' . $request->name);
    }

    /**
     * حذف عملية جراحية (soft delete)
     */
    public function destroy(SurgicalOperation $surgicalOperation)
    {
        if (!auth()->user()->can('manage surgical operations')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        // التحقق من عدم وجود عمليات مرتبطة
        if ($surgicalOperation->surgeries()->count() > 0) {
            return redirect()->back()->with('error', 'لا يمكن حذف هذه العملية لأنها مرتبطة بعمليات جراحية موجودة');
        }

        $name = $surgicalOperation->name;
        $surgicalOperation->delete(); // Soft delete

        return redirect()->back()->with('success', 'تم حذف العملية الجراحية بنجاح: ' . $name);
    }

    /**
     * عرض العمليات المحذوفة
     */
    public function trashed()
    {
        if (!auth()->user()->can('manage surgical operations')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $trashedOperations = SurgicalOperation::onlyTrashed()
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return view('surgical-operations.trashed', compact('trashedOperations'));
    }

    /**
     * استعادة عملية محذوفة
     */
    public function restore($id)
    {
        if (!auth()->user()->can('manage surgical operations')) {
            abort(403, 'غير مصرح لك بالوصول إلى هذه الصفحة');
        }

        $surgicalOperation = SurgicalOperation::withTrashed()->findOrFail($id);
        $surgicalOperation->restore();

        return redirect()->back()->with('success', 'تم استعادة العملية الجراحية بنجاح: ' . $surgicalOperation->name);
    }
}
