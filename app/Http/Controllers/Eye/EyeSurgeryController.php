<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Eye\EyeStoreItem;
use App\Models\Eye\EyeStoreMovement;
use App\Models\Eye\EyeSurgery;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EyeSurgeryController extends Controller
{
    /**
     * جدول عمليات وإجراءات وحقن العيون
     */
    public function index(Request $request)
    {
        $query = EyeSurgery::with(['patient', 'doctor.user', 'iolItem']);

        if ($request->filled('date')) {
            $query->whereDate('surgery_date', $request->date);
        } else {
            $query->whereDate('surgery_date', '>=', Carbon::today()->subDays(7));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('procedure_name', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('medical_record_number', 'like', "%{$search}%");
                  });
            });
        }

        $surgeries = $query->orderBy('surgery_date', 'asc')->paginate(20)->withQueryString();

        $stats = [
            'total'       => EyeSurgery::count(),
            'today'       => EyeSurgery::whereDate('surgery_date', Carbon::today())->count(),
            'scheduled'   => EyeSurgery::where('status', 'scheduled')->count(),
            'completed'   => EyeSurgery::where('status', 'completed')->count(),
            'injections'  => EyeSurgery::where('procedure_name', 'like', '%حقن%')->orWhere('procedure_name', 'like', '%Inject%')->count(),
            'cataract'    => EyeSurgery::where('procedure_name', 'like', '%ماء أبيض%')->orWhere('procedure_name', 'like', '%Phaco%')->count(),
        ];

        return view('eye.surgeries.eye_surgery_index', compact('surgeries', 'stats'));
    }

    /**
     * استمارة حجز عملية أو جلسة حقن جديدة
     */
    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        $doctors = Doctor::with('user')->where('is_active', true)->get();

        // جلب العدسات المتاحة في مخزن العيون
        $iolItems = EyeStoreItem::where('category', 'iol_lens')
            ->where('current_stock', '>', 0)
            ->where('is_active', true)
            ->orderBy('diopter')
            ->get();

        // إبر الحقن المتاحة في المخزن
        $injectionItems = EyeStoreItem::where('category', 'retinal_injection')
            ->where('current_stock', '>', 0)
            ->where('is_active', true)
            ->get();

        return view('eye.surgeries.eye_surgery_create', compact('patient', 'doctors', 'iolItems', 'injectionItems'));
    }

    /**
     * حفظ وحجز العملية وخصم العدسة/الإبرة من مخزن العيون إن حُددت
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'        => 'required|exists:patients,id',
            'procedure_name'    => 'required|string',
            'target_eye'        => 'required|in:OD,OS,OU',
            'surgery_date'      => 'required|date',
            'doctor_id'         => 'nullable|exists:doctors,id',
            'anesthesia_type'   => 'required|string',
            'iol_item_id'       => 'nullable|exists:eye_store_items,id',
            'iol_power'         => 'nullable|numeric',
            'iol_serial_number' => 'nullable|string',
            'injection_drug'    => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $surgery = EyeSurgery::create([
                'patient_id'        => $request->patient_id,
                'doctor_id'         => $request->doctor_id,
                'procedure_name'    => $request->procedure_name,
                'target_eye'        => $request->target_eye,
                'anesthesia_type'   => $request->anesthesia_type,
                'iol_item_id'       => $request->iol_item_id,
                'iol_power'         => $request->iol_power,
                'iol_serial_number' => $request->iol_serial_number,
                'injection_drug'    => $request->injection_drug,
                'injection_dose'    => $request->injection_dose,
                'operative_notes'   => $request->operative_notes,
                'status'            => 'scheduled',
                'surgery_date'      => $request->surgery_date,
            ]);

            // خصم العدسة من مخزن العيون فورياً إذا تم اختيارها
            if ($request->filled('iol_item_id')) {
                $iol = EyeStoreItem::lockForUpdate()->find($request->iol_item_id);
                if ($iol && $iol->current_stock > 0) {
                    $newBalance = $iol->current_stock - 1;
                    $iol->update(['current_stock' => $newBalance]);

                    EyeStoreMovement::create([
                        'eye_store_item_id' => $iol->id,
                        'movement_type'     => 'surgery_dispense',
                        'quantity'          => -1,
                        'balance_after'     => $newBalance,
                        'patient_id'        => $surgery->patient_id,
                        'surgery_id'        => $surgery->id,
                        'notes'             => "صرف عدسة لعملية ({$surgery->procedure_name}) رقم (#{$surgery->id})",
                        'created_by'        => Auth::id(),
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('eye.surgeries.show', $surgery)
                ->with('success', "تم حجز العملية بنجاح بتاريخ ({$surgery->surgery_date->format('Y-m-d')})");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء حجز العملية: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * استعراض تفاصيل العملية وملاحظات الجراح
     */
    public function show(EyeSurgery $surgery)
    {
        $surgery->load(['patient', 'doctor.user', 'iolItem']);
        return view('eye.surgeries.eye_surgery_show', compact('surgery'));
    }

    /**
     * تحديث حالة العملية وملاحظات ما بعد الجراحة
     */
    public function updateStatus(Request $request, EyeSurgery $surgery)
    {
        $request->validate([
            'status'          => 'required|in:scheduled,in_progress,completed,cancelled',
            'operative_notes' => 'nullable|string',
            'complications'   => 'nullable|string',
            'postop_plan'     => 'nullable|string',
        ]);

        $surgery->update($request->only(['status', 'operative_notes', 'complications', 'postop_plan']));

        return back()->with('success', 'تم تحديث سجل وملاحظات العملية بنجاح.');
    }
}
