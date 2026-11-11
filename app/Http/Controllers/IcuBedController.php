<?php

namespace App\Http\Controllers;

use App\Models\IcuBed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IcuBedController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu beds')) {
            abort(403, 'غير مصرح لك بإدارة أسرة العناية المركزة');
        }

        $beds = IcuBed::with('currentAdmission.patient.user')->orderByNumber()->get();

        return view('icu.beds.index', compact('beds'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu beds')) {
            abort(403, 'غير مصرح لك بإضافة سرير عناية');
        }

        $validated = $request->validate([
            'bed_number' => 'required|string|unique:icu_beds,bed_number|max:50',
            'bed_type' => 'required|string|in:general,isolation,ccu,neuro',
            'room_number' => 'nullable|string|max:50',
            'daily_price' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $validated['status'] = IcuBed::STATUS_AVAILABLE;
        $validated['floor'] = 'الطابق الثالث - العناية المركزة';
        $validated['is_active'] = true;

        IcuBed::create($validated);

        return redirect()->route('icu.beds.index')->with('success', 'تمت إضافة سرير العناية بنجاح.');
    }

    public function markSterilized(IcuBed $bed)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu beds')) {
            abort(403, 'غير مصرح لك بتحديث حالة التعقيم');
        }

        $bed->update([
            'status' => IcuBed::STATUS_AVAILABLE,
        ]);

        return back()->with('success', "تم تأكيد اكتمال تعقيم السرير رقم ({$bed->bed_number}) وأصبح جاهزاً ومتاحاً لاستقبال الحالات.");
    }

    public function toggleStatus(Request $request, IcuBed $bed)
    {
        $user = Auth::user();
        if ($user && ! $user->hasRole(['admin', 'admin-hsop', 'hospital_admin']) && ! $user->can('manage icu beds')) {
            abort(403, 'غير مصرح لك بتغيير حالة السرير');
        }

        $targetStatus = $request->status;
        if (! in_array($targetStatus, [IcuBed::STATUS_AVAILABLE, IcuBed::STATUS_MAINTENANCE, IcuBed::STATUS_STERILIZING])) {
            return back()->with('error', 'حالة غير صالحة');
        }

        $bed->update(['status' => $targetStatus]);

        return back()->with('success', 'تم تعديل حالة السرير بنجاح.');
    }
}
