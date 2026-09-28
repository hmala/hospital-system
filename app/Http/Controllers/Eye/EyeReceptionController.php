<?php

namespace App\Http\Controllers\Eye;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Eye\EyeAppointment;
use App\Models\Eye\EyeInvoice;
use App\Models\Eye\EyeInvoiceItem;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EyeReceptionController extends Controller
{
    /**
     * شاشة استقبال وطابور العيون اليومي
     */
    public function index(Request $request)
    {
        $today = Carbon::today();

        $query = EyeAppointment::with(['patient', 'doctor.user', 'latestInvoice'])
            ->whereDate('created_at', $today);

        // فلترة بحسب الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلترة بحسب نوع المراجعة
        if ($request->filled('visit_type')) {
            $query->where('visit_type', $request->visit_type);
        }

        // بحث باسم المريض أو هاتفه أو رقم الموعد
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('appointment_number', 'like', "%{$search}%")
                  ->orWhereHas('patient', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%")
                         ->orWhere('medical_record_number', 'like', "%{$search}%");
                  });
            });
        }

        $appointments = $query->orderBy('queue_number', 'asc')->paginate(20)->withQueryString();

        // إحصائيات سريعة لليوم
        $stats = [
            'total'            => EyeAppointment::whereDate('created_at', $today)->count(),
            'waiting'          => EyeAppointment::whereDate('created_at', $today)->where('status', 'waiting')->count(),
            'in_clinic'        => EyeAppointment::whereDate('created_at', $today)->where('status', 'in_clinic')->count(),
            'in_investigation' => EyeAppointment::whereDate('created_at', $today)->where('status', 'in_investigation')->count(),
            'completed'        => EyeAppointment::whereDate('created_at', $today)->where('status', 'completed')->count(),
        ];

        // قائمة أطباء العيون للاختيار السريع
        $doctors = Doctor::with('user')->where('is_active', true)->get();

        return view('eye.reception.eye_reception_index', compact('appointments', 'stats', 'doctors'));
    }

    /**
     * استمارة حجز موعد عيون جديد
     */
    public function create()
    {
        $doctors = Doctor::with('user')->where('is_active', true)->get();
        return view('eye.reception.eye_reception_create', compact('doctors'));
    }

    /**
     * حفظ موعد العيون وإنشاء فاتورة الكاشير الأولية
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'visit_type'      => 'required|string|in:consultation,optometry,investigation,procedure,follow_up',
            'doctor_id'       => 'nullable|exists:doctors,id',
            'chief_complaint' => 'nullable|string',
            'insurance_type'  => 'required|string|in:cash,health_insurance,moi',
            'fee_amount'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $today = Carbon::today();
            $nextQueue = EyeAppointment::whereDate('created_at', $today)->max('queue_number') + 1;
            $appointmentNumber = 'EYE-' . date('Ymd') . '-' . str_pad($nextQueue, 3, '0', STR_PAD_LEFT);

            $appointment = EyeAppointment::create([
                'appointment_number' => $appointmentNumber,
                'patient_id'         => $request->patient_id,
                'doctor_id'          => $request->doctor_id,
                'visit_type'         => $request->visit_type,
                'queue_number'       => $nextQueue,
                'status'             => 'waiting',
                'chief_complaint'    => $request->chief_complaint,
                'insurance_type'     => $request->insurance_type,
                'created_by'         => Auth::id(),
            ]);

            // إنشاء فاتورة كاشير أولية بالرسوم المقررة
            $fee = (float) ($request->fee_amount ?? 15000);
            $invoiceNumber = 'EYE-INV-' . date('Ymd') . '-' . str_pad($appointment->id, 4, '0', STR_PAD_LEFT);

            $patientShare = $fee;
            $insuranceShare = 0;
            if ($request->insurance_type !== 'cash') {
                $patientShare = $fee * 0.10; // نسبة التحمل 10%
                $insuranceShare = $fee * 0.90;
            }

            $invoice = EyeInvoice::create([
                'invoice_number'     => $invoiceNumber,
                'patient_id'         => $request->patient_id,
                'eye_appointment_id' => $appointment->id,
                'total_amount'       => $fee,
                'patient_share'      => $patientShare,
                'insurance_share'    => $insuranceShare,
                'discount'           => 0,
                'net_amount'         => $patientShare,
                'paid_amount'        => 0,
                'payment_method'     => 'cash',
                'insurance_type'     => $request->insurance_type,
                'status'             => 'pending',
                'cashier_id'         => null,
            ]);

            EyeInvoiceItem::create([
                'eye_invoice_id' => $invoice->id,
                'service_type'   => $request->visit_type,
                'service_id'     => null,
                'description'    => 'رسوم مراجعة: ' . $appointment->visit_type_arabic,
                'quantity'       => 1,
                'unit_price'     => $fee,
                'subtotal'       => $fee,
            ]);

            DB::commit();

            return redirect()->route('eye.reception.index')
                ->with('success', "تم حجز موعد العيون بنجاح برقم ({$appointmentNumber}) ورقم طابور (#{$nextQueue})");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'حدث خطأ أثناء حجز الموعد: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * تحديث حالة المريض في الطابور
     */
    public function updateStatus(Request $request, EyeAppointment $appointment)
    {
        $request->validate([
            'status' => 'required|in:waiting,dilated,in_clinic,in_investigation,completed,cancelled',
        ]);

        $appointment->update([
            'status' => $request->status,
        ]);

        return back()->with('success', "تم تحديث حالة الموعد إلى ({$appointment->status_arabic})");
    }

    /**
     * البحث السريع عن مريض بالاسم أو الهوية أو الهاتف (AJAX)
     */
    public function searchPatients(Request $request)
    {
        $term = $request->get('term', '');
        if (strlen($term) < 2) {
            return response()->json([]);
        }

        $patients = Patient::where('name', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->orWhere('medical_record_number', 'like', "%{$term}%")
            ->select('id', 'name', 'phone', 'gender', 'age', 'insurance_type')
            ->limit(10)
            ->get();

        return response()->json($patients);
    }
}
