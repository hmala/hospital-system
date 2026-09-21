<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\HealthInsuranceCategory;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Patient;
use App\Models\PharmacySale;
use App\Models\PharmacySaleItem;
use App\Models\PharmacyService;
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

        return view('pharmacy.pos.index', compact('insuranceCategories', 'pharmacyServices', 'heldCount'));
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

            if ($alternatives->isEmpty() && !empty($med->generic_name)) {
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
     * حفظ فاتورة الصرف / البيع (نقدي، ضمان، أو تعليق)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'patient_id' => 'nullable|exists:patients,id',
            'patient_name' => 'nullable|string|max:255',
            'patient_phone' => 'nullable|string|max:50',
            'sale_type' => 'required|in:direct_otc,prescription,emergency,inpatient',
            'insurance_type' => 'required|in:none,health_insurance,interior_ministry',
            'health_insurance_category_id' => 'nullable|exists:health_insurance_categories,id',
            'copay_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_route' => 'required|in:pharmacy_cashier,central_cashier',
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
            if ($insuranceType === 'health_insurance' && !empty($validated['health_insurance_category_id'])) {
                $cat = HealthInsuranceCategory::find($validated['health_insurance_category_id']);
                if ($cat && !$request->has('copay_percentage')) {
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
                'payment_status' => $isHeld ? 'pending_cashier' : ($validated['payment_route'] === 'pharmacy_cashier' ? 'paid' : 'pending_cashier'),
                'dispensing_status' => $isHeld ? 'pending' : 'dispensed',
                'payment_route' => $validated['payment_route'],
                'is_held' => $isHeld,
                'user_id' => Auth::id() ?? 1,
                'dispensed_by' => $isHeld ? null : (Auth::id() ?? 1),
                'dispensed_at' => $isHeld ? null : now(),
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
                    if (!$isHeld) {
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

            DB::commit();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $isHeld ? 'تم تعليق الفاتورة بنجاح.' : 'تم إتمام عملية البيع والصرف بنجاح.',
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => $totalAmount,
                    'patient_share' => $patientShare,
                    'insurance_share' => $insuranceShare,
                    'print_url' => route('pharmacy.pos.sales.print', $sale->id),
                ]);
            }

            if ($isHeld) {
                return redirect()->route('pharmacy.pos.index')
                    ->with('success', "تم تعليق الفاتورة «{$sale->invoice_number}» بنجاح.");
            }

            return redirect()->route('pharmacy.pos.sales.print', $sale->id);

        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', 'حدث خطأ أثناء إتمام الفاتورة: ' . $e->getMessage());
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
        if (!$sale->is_held) {
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
        if (!$sale->is_held) {
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
}
