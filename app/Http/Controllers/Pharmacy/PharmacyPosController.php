<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\HealthInsuranceCategory;
use App\Models\Medicine;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyService;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PharmacyPosController extends Controller
{
    /**
     * شاشة نقطة البيع السريعة (POS)
     */
    public function index(Request $request)
    {
        $insuranceCategories = HealthInsuranceCategory::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $pharmacyServices = PharmacyService::where('is_active', true)
            ->orderBy('name')
            ->get();

        $heldCount = PharmacySale::where('is_held', true)->count();
        $pendingPrescriptionsCount = Prescription::where('status', 'pending')->count();

        $pendingPrescriptions = Prescription::where('status', 'pending')
            ->with(['doctor.user', 'patient.user', 'items.medicine.activeBatches'])
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        return view('pharmacy.pos.index', compact('insuranceCategories', 'pharmacyServices', 'heldCount', 'pendingPrescriptionsCount', 'pendingPrescriptions'));
    }

    /**
     * واجهة البحث السريع اللحظي عن الأدوية والخدمات للـ POS
     * تدعم الباركود والاسم والرمز الوطني، وتعيد البدائل تلقائياً
     */
    public function search(Request $request)
    {
        $query = trim($request->get('q', ''));
        if (strlen($query) < 1) {
            return response()->json(['medicines' => [], 'services' => []]);
        }

        // 1. البحث في الأدوية
        $medicines = Medicine::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('barcode', $query)
                    ->orWhere('sub_barcode', $query)
                    ->orWhere('national_code', $query)
                    ->orWhere('name', 'like', "%{$query}%")
                    ->orWhere('generic_name', 'like', "%{$query}%");
            })
            ->with(['activeBatches' => function ($bq) {
                $bq->orderBy('expiry_date', 'asc');
            }, 'alternatives:id,name,generic_name,dosage_form,strength,sale_price,sub_unit_sale_price,main_unit,sub_unit'])
            ->limit(15)
            ->get();

        // تجهيز بيانات الأدوية مع البدائل الذكية
        $medicineResults = $medicines->map(function ($med) {
            // جلب البدائل: إما المرتبطة يدوياً أو التي تشترك بنفس المادة الفعالة والشكل
            $alternatives = $med->alternatives;

            if ($alternatives->isEmpty() && ! empty($med->generic_name)) {
                $alternatives = Medicine::where('id', '!=', $med->id)
                    ->where('is_active', true)
                    ->where('generic_name', $med->generic_name)
                    ->where('dosage_form', $med->dosage_form)
                    ->limit(5)
                    ->get(['id', 'name', 'generic_name', 'dosage_form', 'strength', 'sale_price', 'sub_unit_sale_price', 'main_unit', 'sub_unit']);
            }

            // إضافة أرصدة البدائل
            $altData = $alternatives->map(function ($alt) {
                return [
                    'id' => $alt->id,
                    'name' => $alt->name,
                    'dosage_form' => $alt->dosage_form,
                    'strength' => $alt->strength,
                    'sale_price' => $alt->sale_price,
                    'sub_unit_sale_price' => $alt->sub_unit_sale_price,
                    'main_unit' => $alt->main_unit,
                    'sub_unit' => $alt->sub_unit,
                    'total_stock' => $alt->total_stock,
                    'total_open_sub_units' => $alt->total_open_sub_units,
                ];
            });

            return [
                'id' => $med->id,
                'type' => 'medicine',
                'name' => $med->name,
                'generic_name' => $med->generic_name,
                'national_code' => $med->national_code,
                'dosage_form' => $med->dosage_form,
                'strength' => $med->strength,
                'barcode' => $med->barcode,
                'sub_barcode' => $med->sub_barcode,
                'main_unit' => $med->main_unit,
                'sub_unit' => $med->sub_unit,
                'sub_units_count' => $med->sub_units_count,
                'sale_price' => $med->sale_price,
                'sub_unit_sale_price' => $med->sub_unit_sale_price,
                'hi_price' => $med->hi_price ?? $med->sale_price,
                'moi_price' => $med->moi_price ?? $med->sale_price,
                'is_insurance_covered' => $med->is_insurance_covered,
                'requires_prescription' => $med->requires_prescription,
                'is_controlled' => $med->is_controlled,
                'total_stock' => $med->total_stock,
                'total_open_sub_units' => $med->total_open_sub_units,
                'total_sub_units_stock' => $med->total_sub_units_stock,
                'is_low_stock' => $med->is_low_stock,
                'alternatives' => $altData,
                'earliest_batch' => $med->activeBatches->first() ? [
                    'id' => $med->activeBatches->first()->id,
                    'batch_number' => $med->activeBatches->first()->batch_number,
                    'expiry_date' => $med->activeBatches->first()->expiry_date->format('Y-m-d'),
                    'days_left' => $med->activeBatches->first()->days_until_expiry,
                ] : null,
            ];
        });

        // 2. البحث في الخدمات الصيدلانية
        $services = PharmacyService::where('is_active', true)
            ->where('name', 'like', "%{$query}%")
            ->limit(5)
            ->get()
            ->map(function ($srv) {
                return [
                    'id' => $srv->id,
                    'type' => 'service',
                    'name' => $srv->name,
                    'price' => $srv->price,
                    'hi_price' => $srv->hi_price ?? $srv->price,
                    'moi_price' => $srv->moi_price ?? $srv->price,
                ];
            });

        return response()->json([
            'medicines' => $medicineResults,
            'services' => $services,
        ]);
    }

    /**
     * قائمة الوصفات الطبية الإلكترونية الواردة الجاهزة للصرف
     */
    public function pendingPrescriptions(Request $request)
    {
        $prescriptions = Prescription::whereIn('status', ['pending', 'in_progress'])
            ->with([
                'patient.user',
                'patient.healthInsuranceCategory',
                'doctor.user',
                'items.medicine',
                'items.suggestedMedicine',
                'visit',
                'emergency.emergencyPatient',
                'emergency.doctor.user',
                'surgery.room',
                'surgery.doctor.user',
                'sale.payment',
            ])
            ->orderBy('created_at', 'desc')
            ->limit(60)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $prescriptions->count(),
            'prescriptions' => $prescriptions->map(function ($rx) {
                $hasApprovedSub = $rx->items->contains(fn ($it) => $it->substitution_status === 'approved');
                $hasPendingSub = $rx->items->contains(fn ($it) => $it->substitution_status === 'pending_approval');
                $hasRejectedSub = $rx->items->contains(fn ($it) => $it->substitution_status === 'rejected');

                $itemsSummary = $rx->items->map(function ($it) {
                    return [
                        'id' => $it->id,
                        'name' => $it->medicine?->name ?? $it->medicine_name,
                        'suggested_name' => $it->suggestedMedicine?->name,
                        'substitution_status' => $it->substitution_status,
                        'quantity' => $it->quantity,
                        'unit_type' => $it->unit_type,
                    ];
                });

                $isPaid = $rx->sale && $rx->sale->payment_status === 'paid';
                $isPendingCashier = $rx->sale && $rx->sale->payment_status === 'pending_cashier';

                $stage = 'new';
                if ($isPaid) {
                    $stage = 'paid_ready_to_dispense';
                } elseif ($isPendingCashier) {
                    $stage = 'pending_cashier';
                } elseif ($hasApprovedSub) {
                    $stage = 'approved_ready';
                } elseif ($hasPendingSub) {
                    $stage = 'awaiting_doctor';
                }

                // بيانات المريض (طوارئ أو عيادة أو رقود)
                $isEmergency = ! is_null($rx->emergency_id);
                $isInpatient = ! is_null($rx->surgery_id);
                $roomNumber = null;
                $roomFloor = null;

                if ($isInpatient && $rx->surgery) {
                    $surg = $rx->surgery;
                    $patientName = optional($rx->patient)->user->name
                        ?? $surg->patient?->user?->name
                        ?? $surg->patient?->name
                        ?? 'مريض رقود';
                    $doctorName = optional($rx->doctor)->user->name
                        ?? optional($surg->doctor)->user->name
                        ?? 'طبيب معالج';
                    $roomNumber = $surg->room?->room_number ?? 'انتظار';
                    $roomFloor = $surg->room?->floor ?? '';
                    $specialization = '🏨 الرقود: غرفة '.$roomNumber.($roomFloor ? " ({$roomFloor})" : '');
                } elseif ($isEmergency && $rx->emergency) {
                    $em = $rx->emergency;
                    $patientName = $em->emergencyPatient?->name
                        ?? optional($em->patient)?->user?->name
                        ?? optional($rx->patient)?->user?->name
                        ?? 'مريض طوارئ';
                    $doctorName = optional($em->doctor)?->user?->name
                        ?? optional($rx->doctor)?->user?->name
                        ?? 'طبيب طوارئ';
                    $specialization = '🚨 قسم الطوارئ';
                } else {
                    $patientName = optional($rx->patient)?->user?->name ?? 'مريض مباشر';
                    $doctorName = optional($rx->doctor)?->user?->name ?? 'طبيب استشاري';
                    $specialization = optional($rx->doctor)?->specialization ?? 'استشارية';
                }

                // التصنيف المالي والتأميني المسجل بالمستشفى من الاستعلامات
                $patient = $rx->patient;
                $rawType = $patient?->insurance_type ?? 'none';
                $insType = match ($rawType) {
                    'hi', 'health_insurance' => 'health_insurance',
                    'moi', 'interior_ministry' => 'interior_ministry',
                    default => 'none',
                };
                $category = $patient?->healthInsuranceCategory;
                $copayPercent = (float) ($category?->medication_copay ?? $patient?->copay_percentage ?? 0);
                $categoryName = $category?->name ?? 'فئة معتمدة';

                $insuranceBadge = match ($insType) {
                    'health_insurance' => "🛡️ ضمان: {$categoryName} ({$copayPercent}%)",
                    'interior_ministry' => '🛡️ داخلية MOI',
                    default => '💵 كاش نقدي',
                };

                return [
                    'id' => $rx->id,
                    'prescription_number' => $rx->prescription_number,
                    'patient_id' => $rx->patient_id,
                    'patient_name' => $patientName,
                    'patient_phone' => optional($rx->patient)?->user?->phone ?? '-',
                    'patient_national_id' => optional($rx->patient)?->user?->national_id ?? '-',
                    'insurance_type' => $insType,
                    'category_name' => $categoryName,
                    'copay_percentage' => $copayPercent,
                    'insurance_badge' => $insuranceBadge,
                    'doctor_name' => $doctorName,
                    'specialization' => $specialization,
                    'is_emergency' => $isEmergency,
                    'is_inpatient' => $isInpatient,
                    'room_number' => $roomNumber,
                    'room_floor' => $roomFloor,
                    'diagnosis' => $rx->diagnosis ?? '-',
                    'items_count' => $rx->items->count(),
                    'items_summary' => $itemsSummary,
                    'stage' => $stage,
                    'sale_id' => $rx->sale_id,
                    'sale_invoice' => $rx->sale?->invoice_number,
                    'payment_status' => $rx->sale?->payment_status ?? 'unbilled',
                    'patient_share' => $rx->sale ? (float) $rx->sale->patient_share : 0,
                    'is_paid' => $isPaid,
                    'dispensing_status' => $rx->sale?->dispensing_status ?? 'pending',
                    'has_approved_sub' => $hasApprovedSub,
                    'has_pending_sub' => $hasPendingSub,
                    'has_rejected_sub' => $hasRejectedSub,
                    'created_at' => $rx->created_at ? $rx->created_at->format('Y-m-d H:i') : '-',
                    'time_ago' => $rx->created_at ? $rx->created_at->diffForHumans() : '-',
                ];
            }),
        ]);
    }

    /**
     * تفاصيل وصفة طبية إلكترونية مع فحص الأرصدة والبدائل اللحظية
     */
    public function getPrescriptionDetails(Prescription $prescription)
    {
        $prescription->load([
            'patient.user',
            'patient.healthInsuranceCategory',
            'doctor.user',
            'items.medicine.activeBatches' => function ($q) {
                $q->orderBy('expiry_date', 'asc');
            },
            'items.medicine.alternatives',
            'items.suggestedMedicine',
            'visit',
            'emergency.emergencyPatient',
            'emergency.doctor.user',
            'surgery.room',
            'surgery.doctor.user',
        ]);

        $isEmergency = ! is_null($prescription->emergency_id);
        $isInpatient = ! is_null($prescription->surgery_id);
        $roomNumber = null;
        $roomFloor = null;

        if ($isInpatient && $prescription->surgery) {
            $surg = $prescription->surgery;
            $patientName = optional($prescription->patient)->user->name
                ?? $surg->patient?->user?->name
                ?? $surg->patient?->name
                ?? 'مريض رقود';
            $doctorName = optional($prescription->doctor)->user->name
                ?? optional($surg->doctor)->user->name
                ?? 'طبيب معالج';
            $roomNumber = $surg->room?->room_number ?? 'انتظار';
            $roomFloor = $surg->room?->floor ?? '';
            $specialization = '🏨 الرقود: غرفة '.$roomNumber.($roomFloor ? " ({$roomFloor})" : '');
        } elseif ($isEmergency && $prescription->emergency) {
            $em = $prescription->emergency;
            $patientName = $em->emergencyPatient?->name
                ?? optional($em->patient)?->user?->name
                ?? optional($prescription->patient)?->user?->name
                ?? 'مريض طوارئ';
            $doctorName = optional($em->doctor)?->user?->name
                ?? optional($prescription->doctor)?->user?->name
                ?? 'طبيب طوارئ';
            $specialization = '🚨 قسم الطوارئ';
        } else {
            $patientName = optional($prescription->patient)?->user?->name ?? 'مريض مباشر';
            $doctorName = optional($prescription->doctor)?->user?->name ?? 'طبيب استشاري';
            $specialization = optional($prescription->doctor)?->specialization ?? 'استشارية';
        }

        $itemsData = $prescription->items->map(function ($item) {
            $med = $item->medicine;
            $suggested = $item->suggestedMedicine;
            $alternatives = collect();
            if ($med) {
                $alternatives = $med->alternatives;
                if ($alternatives->isEmpty() && ! empty($med->generic_name)) {
                    $alternatives = Medicine::where('id', '!=', $med->id)
                        ->where('is_active', true)
                        ->where('generic_name', $med->generic_name)
                        ->where('dosage_form', $med->dosage_form)
                        ->limit(5)
                        ->get();
                }
            }

            $altFormatted = $alternatives->map(function ($alt) {
                return [
                    'id' => $alt->id,
                    'name' => $alt->name,
                    'dosage_form' => $alt->dosage_form,
                    'strength' => $alt->strength,
                    'sale_price' => $alt->sale_price,
                    'sub_unit_sale_price' => $alt->sub_unit_sale_price,
                    'main_unit' => $alt->main_unit,
                    'sub_unit' => $alt->sub_unit,
                    'total_stock' => $alt->total_stock,
                    'total_open_sub_units' => $alt->total_open_sub_units,
                ];
            });

            return [
                'id' => $item->id,
                'medicine_id' => $med ? $med->id : null,
                'name' => $med ? $med->name : ($item->notes ?? 'دواء غير محدد'),
                'generic_name' => $med?->generic_name,
                'dosage_form' => $med?->dosage_form,
                'strength' => $med?->strength,
                'unit_type' => $item->unit_type ?? 'main_unit',
                'main_unit' => $med?->main_unit ?? 'علبة',
                'sub_unit' => $med?->sub_unit ?? 'شريط',
                'quantity' => (float) $item->quantity ?: 1,
                'dosage_frequency' => $item->dosage_frequency,
                'instructions' => $item->instructions,
                'duration_days' => $item->duration_days,
                'sale_price' => $med?->sale_price ?? 0,
                'sub_unit_sale_price' => $med?->sub_unit_sale_price ?? 0,
                'hi_price' => $med?->hi_price ?? $med?->sale_price ?? 0,
                'moi_price' => $med?->moi_price ?? $med?->sale_price ?? 0,
                'is_insurance_covered' => (bool) ($med?->is_insurance_covered ?? true),
                'sub_units_count' => $med?->sub_units_count ?? 1,
                'total_stock' => $med?->total_stock ?? 0,
                'total_open_sub_units' => $med?->total_open_sub_units ?? 0,
                'is_in_stock' => $med && ($med->total_stock > 0 || $med->total_open_sub_units > 0),
                'alternatives' => $altFormatted,
                'suggested_medicine_id' => $item->suggested_medicine_id,
                'suggested_medicine_name' => $suggested?->name,
                'substitution_status' => $item->substitution_status ?? 'none',
                'substitution_reason' => $item->substitution_reason,
                'substitution_response_notes' => $item->substitution_response_notes,
                'substitution_responded_at' => $item->substitution_responded_at?->format('H:i'),
            ];
        });

        // استخراج التصنيف المالي والتأميني المسجل بالمستشفى من الاستعلامات
        $patient = $prescription->patient;
        $rawType = $patient?->insurance_type ?? 'none';
        $insType = match ($rawType) {
            'hi', 'health_insurance' => 'health_insurance',
            'moi', 'interior_ministry' => 'interior_ministry',
            default => 'none',
        };
        $category = $patient?->healthInsuranceCategory;
        $copayPercent = (float) ($category?->medication_copay ?? $patient?->copay_percentage ?? 0);
        $categoryName = $category?->name ?? 'فئة معتمدة';

        $insuranceBadge = match ($insType) {
            'health_insurance' => "🔒 معتمد ومسجل بالاستعلامات: ضمان صحي ({$categoryName} - استقطاع {$copayPercent}%)",
            'interior_ministry' => '🔒 معتمد ومسجل بالاستعلامات: وزارة الداخلية (MOI)',
            default => '🔒 مسجل بالاستعلامات: نقدي كاش (100% نقداً)',
        };

        return response()->json([
            'success' => true,
            'prescription' => [
                'id' => $prescription->id,
                'prescription_number' => $prescription->prescription_number,
                'patient_id' => $prescription->patient_id,
                'patient_name' => $patientName,
                'patient_phone' => optional($prescription->patient)?->user?->phone ?? '',
                'doctor_name' => $doctorName,
                'diagnosis' => $prescription->diagnosis,
                'notes' => $prescription->notes,
                'is_emergency' => $isEmergency,
                'is_inpatient' => $isInpatient,
                'room_number' => $roomNumber,
                'room_floor' => $roomFloor,
                'specialization' => $specialization,
                'insurance_type' => $insType,
                'health_insurance_category_id' => $patient?->health_insurance_category_id,
                'category_name' => $categoryName,
                'copay_percentage' => $copayPercent,
                'insurance_badge' => $insuranceBadge,
                'items' => $itemsData,
            ],
        ]);
    }

    /**
     * إرسال طلب اقتراح بديل دوائي من الصيدلية إلى الطبيب المعالج
     */
    public function suggestAlternative(Request $request, PrescriptionItem $item)
    {
        $validated = $request->validate([
            'suggested_medicine_id' => 'required|exists:medicines,id',
            'substitution_reason' => 'nullable|string|max:255',
        ]);

        $suggestedMed = Medicine::findOrFail($validated['suggested_medicine_id']);

        $item->update([
            'suggested_medicine_id' => $suggestedMed->id,
            'substitution_status' => 'pending_approval',
            'substitution_reason' => $validated['substitution_reason'] ?? 'عدم توفر الصنف الأصلي في الصيدلية - مقترح البديل المكافئ حيوياً',
            'substitution_response_notes' => null,
            'substitution_responded_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "تم إرسال طلب اقتراح البديل ({$suggestedMed->name}) إلى شاشة الطبيب بنجاح. بانتظار الموافقة...",
            'item_id' => $item->id,
            'suggested_medicine_id' => $suggestedMed->id,
            'suggested_medicine_name' => $suggestedMed->name,
            'substitution_status' => 'pending_approval',
        ]);
    }

    /**
     * صرف وتجهيز وصفة طبية إلكترونية فوراً بضغطة زر واحدة وإرسالها للكاشير المركزي للدفع
     * يقوم بحجز وخصم المخزون بنظام FEFO، واحتساب الاستقطاع المالي، وتحويلها للكاشير المركزي
     */
    public function dispensePrescription(Request $request, Prescription $prescription)
    {
        if ($prescription->status === 'dispensed') {
            return response()->json([
                'success' => false,
                'message' => 'هذه الوصفة تم صرفها وتسليمها للمريض مسبقاً.',
            ], 422);
        }

        $prescription->load(['items.medicine.activeBatches', 'patient.user', 'patient.healthInsuranceCategory', 'doctor.user', 'visit']);

        DB::beginTransaction();
        try {
            $userId = Auth::id() ?? 1;
            $itemsData = $request->input('items', []);
            $itemsById = collect($itemsData)->keyBy('id');

            // احتساب التصنيف التأميني للمريض
            $patient = $prescription->patient;
            $rawType = $patient?->insurance_type ?? 'none';
            $insType = match ($rawType) {
                'hi', 'health_insurance' => 'health_insurance',
                'moi', 'interior_ministry' => 'interior_ministry',
                default => 'none',
            };
            $category = $patient?->healthInsuranceCategory;
            $copayPercent = (float) ($category?->medication_copay ?? $patient?->copay_percentage ?? 0);

            $saleType = $prescription->surgery_id ? 'inpatient' : ($prescription->emergency_id ? 'emergency' : 'prescription');

            // إنشاء سجل صرف بالمبيعات محول للكاشير المركزي
            $sale = PharmacySale::create([
                'invoice_number' => PharmacySale::generateInvoiceNumber(),
                'patient_id' => $prescription->patient_id,
                'doctor_id' => $prescription->doctor_id,
                'visit_id' => $prescription->visit_id,
                'surgery_id' => $prescription->surgery_id,
                'patient_name' => optional($prescription->patient)->user->name ?? 'مريض',
                'patient_phone' => optional($prescription->patient)->user->phone ?? null,
                'sale_type' => $saleType,
                'insurance_type' => $insType,
                'health_insurance_category_id' => $category?->id,
                'copay_percentage' => $copayPercent,
                'claim_status' => ($insType !== 'none') ? 'pending' : 'none',
                'payment_status' => 'pending_cashier',
                'dispensing_status' => 'pending',
                'payment_route' => 'central_cashier',
                'is_held' => false,
                'user_id' => $userId,
                'dispensed_by' => null,
                'dispensed_at' => null,
                'notes' => $request->input('notes', 'تجهيز وإرسال للكاشير المركزي للوصفة: '.$prescription->prescription_number),
            ]);

            $totalAmount = 0.00;
            $patientShare = 0.00;
            $insuranceShare = 0.00;
            $dispensedCount = 0;
            $outOfStockCount = 0;

            foreach ($prescription->items as $pItem) {
                $medId = $pItem->medicine_id;
                $qty = (float) $pItem->quantity;
                $unitType = $pItem->unit_type ?? 'main_unit';
                $isAvailable = true;

                // في حال تم تعديل الصنف من الواجهة (توفر/استبدال/كمية)
                if ($itemsById->has($pItem->id)) {
                    $customItem = $itemsById->get($pItem->id);
                    if (isset($customItem['is_available']) && ($customItem['is_available'] === false || $customItem['is_available'] === 'false' || $customItem['is_available'] === 0)) {
                        $isAvailable = false;
                    }
                    if (isset($customItem['status']) && $customItem['status'] === 'out_of_stock') {
                        $isAvailable = false;
                    }
                    if (! empty($customItem['medicine_id'])) {
                        $medId = $customItem['medicine_id'];
                    }
                    if (isset($customItem['quantity']) && (float) $customItem['quantity'] > 0) {
                        $qty = (float) $customItem['quantity'];
                    }
                    if (! empty($customItem['unit_type'])) {
                        $unitType = $customItem['unit_type'];
                    }
                }

                if (! $isAvailable) {
                    $pItem->update([
                        'status' => 'out_of_stock',
                    ]);
                    $outOfStockCount++;

                    continue;
                }

                $medicine = $medId ? Medicine::find($medId) : null;
                $usedBatchId = null;

                if ($medicine) {
                    // احتساب التسعير الديناميكي للبند
                    $pricing = $medicine->calculatePricing($unitType, $insType, $copayPercent);
                    $unitPrice = (float) $pricing['approved_price'];
                    $subtotal = round($unitPrice * $qty, 2);
                    $itemPatientShare = round($pricing['patient_share'] * $qty, 2);
                    $itemInsuranceShare = round($subtotal - $itemPatientShare, 2);

                    $remainingQtyToDeduct = $qty;

                    // خصم المخزون بنظام FEFO إن وجد رصيد
                    $batches = $medicine->activeBatches;
                    foreach ($batches as $batch) {
                        if ($remainingQtyToDeduct <= 0) {
                            break;
                        }
                        $usedBatchId = $batch->id;

                        if ($unitType === 'main_unit') {
                            $deductMain = (int) min($batch->current_quantity, $remainingQtyToDeduct);
                            if ($deductMain > 0) {
                                $batch->deductStock(mainUnits: $deductMain, subUnits: 0, subUnitsCount: $medicine->sub_units_count);
                                $remainingQtyToDeduct -= $deductMain;
                            }
                        } else {
                            $deductSub = (int) $remainingQtyToDeduct;
                            $batch->deductStock(mainUnits: 0, subUnits: $deductSub, subUnitsCount: $medicine->sub_units_count);
                            $remainingQtyToDeduct = 0;
                        }
                    }

                    PharmacySaleItem::create([
                        'sale_id' => $sale->id,
                        'item_type' => 'medicine',
                        'medicine_id' => $medicine->id,
                        'batch_id' => $usedBatchId,
                        'unit_type' => $unitType,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'dosage_instructions' => trim($pItem->dosage_frequency.' '.$pItem->instructions),
                    ]);

                    $totalAmount += $subtotal;
                    $patientShare += $itemPatientShare;
                    $insuranceShare += $itemInsuranceShare;
                }

                // تحديث بند الوصفة
                $pItem->update([
                    'medicine_id' => $medId,
                    'quantity' => $qty,
                    'unit_type' => $unitType,
                    'status' => 'dispensed',
                ]);
                $dispensedCount++;
            }

            $sale->update([
                'total_amount' => $totalAmount,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
            ]);

            // تحديث حالة الوصفة كـ (قيد المعالجة/بانتظار الدفع)
            $totalItemsCount = $prescription->items->count();
            $prescriptionStatus = 'in_progress';
            if ($outOfStockCount > 0 && $dispensedCount === 0) {
                $prescriptionStatus = 'out_of_stock';
            }

            $prescription->update([
                'status' => $prescriptionStatus,
                'sale_id' => $sale->id,
            ]);

            if ($prescription->surgery_id) {
                $itemIds = $prescription->items->pluck('id')->all();
                \App\Models\SurgeryTreatment::whereIn('prescription_item_id', $itemIds)->update([
                    'pharmacy_status' => 'pending_cashier',
                ]);
            }

            DB::commit();

            $statusText = "تم تجهيز الوصفة ({$prescription->prescription_number}) وإرسالها بنجاح إلى الكاشير المركزي للدفع (المطلوب من المريض: ".number_format($patientShare, 2).' د.ع).';

            return response()->json([
                'success' => true,
                'message' => $statusText,
                'prescription_id' => $prescription->id,
                'prescription_number' => $prescription->prescription_number,
                'status' => $prescriptionStatus,
                'dispensed_count' => $dispensedCount,
                'out_of_stock_count' => $outOfStockCount,
                'total_amount' => $totalAmount,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
                'patient_name' => optional($prescription->patient)->user->name ?? 'مريض',
                'sale_id' => $sale->id,
                'print_url' => route('pharmacy.pos.sales.print', $sale->id),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء صرف الوصفة: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * حفظ فاتورة الصرف / البيع (نقدي، ضمان، أو تعليق)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'prescription_id' => 'nullable|exists:prescriptions,id',
            'patient_id' => 'nullable|exists:patients,id',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:50',
            'sale_type' => 'required|in:direct_otc,prescription,emergency,inpatient',
            'insurance_type' => 'required|in:none,health_insurance,interior_ministry',
            'health_insurance_category_id' => 'nullable|exists:health_insurance_categories,id',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_route' => 'nullable|in:pharmacy_cashier,central_cashier',
            'is_held' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|in:medicine,service',
            'items.*.medicine_id' => 'nullable|exists:medicines,id',
            'items.*.service_id' => 'nullable|exists:pharmacy_services,id',
            'items.*.unit_type' => 'required|in:main_unit,sub_unit',
            'items.*.quantity' => 'required|numeric|min:0.1',
            'items.*.dosage_instructions' => 'nullable|string|max:255',
        ]);

        $isHeld = $request->boolean('is_held', false);

        DB::beginTransaction();
        try {
            // توليد رقم الفاتورة
            $invoiceNumber = PharmacySale::generateInvoiceNumber();

            // تجهيز بيانات الفاتورة
            $insuranceType = $validated['insurance_type'];
            $copayPercent = (float) ($validated['copay_percentage'] ?? 0);

            // إذا كان ضمان صحي وتم تحديد فئة، نأخذ نسبة استقطاع الفئة إن لم تُحدد يدوياً
            if ($insuranceType === 'health_insurance' && ! empty($validated['health_insurance_category_id'])) {
                $cat = HealthInsuranceCategory::find($validated['health_insurance_category_id']);
                if ($cat && ! $request->has('copay_percentage')) {
                    $copayPercent = (float) $cat->medication_copay;
                }
            }

            $totalAmount = 0.00;
            $patientShare = 0.00;
            $insuranceShare = 0.00;

            $sale = PharmacySale::create([
                'invoice_number' => $invoiceNumber,
                'patient_id' => $validated['patient_id'] ?? null,
                'patient_name' => $validated['patient_name'] ?? 'مريض مباشر OTC',
                'patient_phone' => $validated['patient_phone'] ?? null,
                'sale_type' => $validated['sale_type'],
                'insurance_type' => $insuranceType,
                'health_insurance_category_id' => $validated['health_insurance_category_id'] ?? null,
                'copay_percentage' => $copayPercent,
                'claim_status' => ($insuranceType !== 'none') ? 'pending' : 'none',
                'payment_status' => 'pending_cashier',
                'dispensing_status' => 'pending',
                'payment_route' => 'central_cashier',
                'is_held' => $isHeld,
                'user_id' => Auth::id() ?? 1,
                'dispensed_by' => null,
                'dispensed_at' => null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // معالجة كل بند بالفاتورة
            foreach ($validated['items'] as $itemData) {
                if ($itemData['item_type'] === 'medicine') {
                    $medicine = Medicine::findOrFail($itemData['medicine_id']);
                    $unitType = $itemData['unit_type']; // main_unit أو sub_unit
                    $qty = (float) $itemData['quantity'];

                    // احتساب التسعير الديناميكي للبند
                    $pricing = $medicine->calculatePricing($unitType, $insuranceType, $copayPercent);
                    $unitPrice = (float) $pricing['approved_price'];
                    $subtotal = round($unitPrice * $qty, 2);
                    $itemPatientShare = round($pricing['patient_share'] * $qty, 2);
                    $itemInsuranceShare = round($subtotal - $itemPatientShare, 2);

                    // إذا لم تكن الفاتورة معلقة، نخصم الرصيد بنظام FEFO
                    $usedBatchId = null;
                    if (! $isHeld) {
                        $remainingQtyToDeduct = $qty;
                        // جلب الوجبات الصالحة بنظام FEFO
                        $batches = $medicine->activeBatches;

                        foreach ($batches as $batch) {
                            if ($remainingQtyToDeduct <= 0) {
                                break;
                            }

                            $usedBatchId = $batch->id;

                            if ($unitType === 'main_unit') {
                                $deductMain = (int) min($batch->current_quantity, $remainingQtyToDeduct);
                                if ($deductMain > 0) {
                                    $batch->deductStock(mainUnits: $deductMain, subUnits: 0, subUnitsCount: $medicine->sub_units_count);
                                    $remainingQtyToDeduct -= $deductMain;
                                }
                            } else {
                                // سحب أشرطة: يحسب الأشرطة المتوفرة + فتح علب إن لزم
                                $deductSub = (int) $remainingQtyToDeduct;
                                $batch->deductStock(mainUnits: 0, subUnits: $deductSub, subUnitsCount: $medicine->sub_units_count);
                                $remainingQtyToDeduct = 0;
                            }
                        }
                    }

                    PharmacySaleItem::create([
                        'sale_id' => $sale->id,
                        'item_type' => 'medicine',
                        'medicine_id' => $medicine->id,
                        'batch_id' => $usedBatchId,
                        'unit_type' => $unitType,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'dosage_instructions' => $itemData['dosage_instructions'] ?? null,
                    ]);

                    $totalAmount += $subtotal;
                    $patientShare += $itemPatientShare;
                    $insuranceShare += $itemInsuranceShare;

                } elseif ($itemData['item_type'] === 'service') {
                    $service = PharmacyService::findOrFail($itemData['service_id']);
                    $qty = (float) $itemData['quantity'];

                    $servicePricing = $service->calculateInsurancePricing($insuranceType, $copayPercent);
                    $unitPrice = (float) $servicePricing['approved_price'];
                    $subtotal = round($unitPrice * $qty, 2);
                    $itemPatientShare = round($servicePricing['patient_share'] * $qty, 2);
                    $itemInsuranceShare = round($subtotal - $itemPatientShare, 2);

                    PharmacySaleItem::create([
                        'sale_id' => $sale->id,
                        'item_type' => 'service',
                        'service_id' => $service->id,
                        'unit_type' => 'main_unit',
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'subtotal' => $subtotal,
                        'dosage_instructions' => $itemData['dosage_instructions'] ?? null,
                    ]);

                    $totalAmount += $subtotal;
                    $patientShare += $itemPatientShare;
                    $insuranceShare += $itemInsuranceShare;
                }
            }

            // تحديث مجاميع الفاتورة
            $sale->update([
                'total_amount' => $totalAmount,
                'patient_share' => $patientShare,
                'insurance_share' => $insuranceShare,
            ]);

            // إذا كانت الفاتورة مرتبطة بوصفة طبية إلكترونية، تحديث حالة الوصفة
            if (! empty($validated['prescription_id'])) {
                $prescription = Prescription::find($validated['prescription_id']);
                if ($prescription) {
                    $prescription->update([
                        'sale_id' => $sale->id,
                        'status' => 'in_progress',
                    ]);
                }
            }

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $isHeld ? 'تم تعليق الفاتورة بنجاح.' : "تم إرسال الفاتورة «{$sale->invoice_number}» بنجاح إلى الكاشير المركزي لتحصيل المبلغ (".number_format($patientShare, 2).' د.ع).',
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => $totalAmount,
                    'patient_share' => $patientShare,
                    'insurance_share' => $insuranceShare,
                    'payment_status' => 'pending_cashier',
                    'payment_route' => 'central_cashier',
                    'print_url' => route('pharmacy.pos.sales.print', $sale->id),
                ]);
            }

            if ($isHeld) {
                return redirect()->route('pharmacy.pos.index')
                    ->with('success', "تم تعليق الفاتورة «{$sale->invoice_number}» بنجاح.");
            }

            return redirect()->route('pharmacy.pos.sales.print', $sale->id)
                ->with('info', "تم إرسال الفاتورة «{$sale->invoice_number}» إلى الكاشير المركزي للدفع.");

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء إتمام الفاتورة: '.$e->getMessage());
        }
    }

    /**
     * قائمة الفواتير المعلقة (Held Bills)
     */
    public function heldBills()
    {
        $heldSales = PharmacySale::where('is_held', true)
            ->with(['items.medicine', 'items.service'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($heldSales);
    }

    /**
     * استئناف فاتورة معلقة بالـ POS
     */
    public function resumeHeldBill(PharmacySale $sale)
    {
        if (! $sale->is_held) {
            return response()->json(['success' => false, 'message' => 'هذه الفاتورة ليست معلقة.'], 400);
        }

        $sale->load(['items.medicine', 'items.service']);

        return response()->json([
            'success' => true,
            'sale' => $sale,
        ]);
    }

    /**
     * حذف فاتورة معلقة
     */
    public function deleteHeldBill(PharmacySale $sale)
    {
        if (! $sale->is_held) {
            return response()->json(['success' => false, 'message' => 'لا يمكن حذف فاتورة غير معلقة.'], 400);
        }

        $sale->delete();

        return response()->json(['success' => true, 'message' => 'تم إلغاء الفاتورة المعلقة بنجاح.']);
    }

    /**
     * طباعة وصل الفاتورة الحراري (80mm)
     */
    public function printReceipt(PharmacySale $sale)
    {
        $sale->load(['items.medicine', 'items.service', 'patient', 'user', 'dispenser']);

        return view('pharmacy.pos.receipt', compact('sale'));
    }

    /**
     * سجل مبيعات وفواتير الصيدلية
     */
    public function history(Request $request)
    {
        $query = PharmacySale::with(['patient', 'user', 'dispenser', 'items']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->sale_type);
        }

        if ($request->filled('insurance_type')) {
            $query->where('insurance_type', $request->insurance_type);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // إحصائيات سريعة لسجل المبيعات
        $stats = [
            'today_sales' => PharmacySale::whereDate('created_at', now()->toDateString())->where('is_held', false)->sum('total_amount'),
            'today_patient_share' => PharmacySale::whereDate('created_at', now()->toDateString())->where('is_held', false)->sum('patient_share'),
            'today_insurance_share' => PharmacySale::whereDate('created_at', now()->toDateString())->where('is_held', false)->sum('insurance_share'),
            'today_count' => PharmacySale::whereDate('created_at', now()->toDateString())->where('is_held', false)->count(),
        ];

        return view('pharmacy.pos.history', compact('sales', 'stats'));
    }

    /**
     * عرض تفاصيل فاتورة مبيعات
     */
    public function showSale(PharmacySale $sale)
    {
        $sale->load(['items.medicine', 'items.service', 'items.batch', 'patient', 'user', 'dispenser', 'healthInsuranceCategory']);

        return view('pharmacy.pos.show', compact('sale'));
    }

    /**
     * تسليم الأدوية للمريض بعد سداد الفاتورة في الكاشير المركزي
     */
    public function completeHandoff(Request $request, PharmacySale $sale)
    {
        if ($sale->payment_status !== 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تسليم الدواء! الفاتورة لم يتم تسديدها بعد في الكاشير المركزي.',
            ], 422);
        }

        $userId = Auth::id() ?? 1;
        $sale->update([
            'dispensing_status' => 'dispensed',
            'dispensed_by' => $userId,
            'dispensed_at' => now(),
        ]);

        if ($sale->prescription) {
            $sale->prescription->update([
                'status' => 'dispensed',
                'dispensed_by' => $userId,
                'dispensed_at' => now(),
            ]);

            if ($sale->prescription->surgery_id) {
                $itemIds = $sale->prescription->items->pluck('id')->all();
                \App\Models\SurgeryTreatment::whereIn('prescription_item_id', $itemIds)->update([
                    'pharmacy_status' => 'dispensed',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "تم تأكيد تسليم وصرف الأدوية للمريض بنجاح للفاتورة «{$sale->invoice_number}».",
        ]);
    }
}
