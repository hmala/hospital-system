<?php

namespace App\Http\Controllers\Pharmacy;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class MedicineController extends Controller
{
    /**
     * عرض قائمة الأدوية والمستلزمات مع الفلاتر والإحصائيات
     */
    public function index(Request $request)
    {
        $query = Medicine::with(['batches' => function ($q) {
            $q->where('status', 'active')->where('expiry_date', '>=', now()->toDateString());
        }]);

        // بحث بالاسم التجاري أو العلمي أو الباركود أو الرمز الوطني
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('sub_barcode', 'like', "%{$search}%")
                  ->orWhere('national_code', 'like', "%{$search}%");
            });
        }

        // فلترة بالشكل الصيدلاني
        if ($request->filled('dosage_form')) {
            $query->where('dosage_form', $request->dosage_form);
        }

        // فلترة بالشمول التأميني
        if ($request->filled('insurance_covered')) {
            if ($request->insurance_covered === 'yes') {
                $query->where('is_insurance_covered', true);
            } elseif ($request->insurance_covered === 'no') {
                $query->where('is_insurance_covered', false);
            }
        }

        // فلترة باشتراط الوصفة الطبية
        if ($request->filled('requires_prescription')) {
            $query->where('requires_prescription', $request->requires_prescription === 'yes');
        }

        // فلترة بالأدوية الرقابية / المؤثرات العقلية
        if ($request->filled('is_controlled')) {
            $query->where('is_controlled', $request->is_controlled === 'yes');
        }

        // إحصائيات سريعة للبطاقات العلوية
        $stats = [
            'total' => Medicine::count(),
            'insurance_covered' => Medicine::where('is_insurance_covered', true)->count(),
            'controlled' => Medicine::where('is_controlled', true)->count(),
            'forms_count' => Medicine::distinct('dosage_form')->whereNotNull('dosage_form')->count('dosage_form'),
        ];

        // قائمة الأشكال الصيدلانية لخيارات الفلتر
        $dosageForms = Medicine::distinct()->whereNotNull('dosage_form')->pluck('dosage_form');

        $medicines = $query->orderBy('name', 'asc')->paginate(20)->withQueryString();

        return view('pharmacy.medicines.index', compact('medicines', 'stats', 'dosageForms'));
    }

    /**
     * واجهة إضافة دواء جديد
     */
    public function create()
    {
        $existingMedicines = Medicine::where('is_active', true)->orderBy('name')->get();
        return view('pharmacy.medicines.create', compact('existingMedicines'));
    }

    /**
     * حفظ دواء جديد مع إمكانية إدخال رصيد افتتاحي أولي (Initial Batch)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'national_code' => 'nullable|string|max:100',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100|unique:medicines,barcode',
            'sub_barcode' => 'nullable|string|max:100|unique:medicines,sub_barcode',
            'main_unit' => 'required|string|max:50',
            'sub_unit' => 'required|string|max:50',
            'sub_units_count' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'sub_unit_sale_price' => 'nullable|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_insurance_covered' => 'boolean',
            'min_stock_alert' => 'required|integer|min:0',
            'storage_temperature' => 'nullable|string|max:100',
            'requires_prescription' => 'boolean',
            'is_controlled' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',

            // حقول الوجبة الافتتاحية (اختياري)
            'initial_batch_number' => 'nullable|string|max:100',
            'initial_expiry_date' => 'nullable|date',
            'initial_quantity' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $medicineData = collect($validated)->except([
                'initial_batch_number',
                'initial_expiry_date',
                'initial_quantity',
            ])->toArray();

            $medicineData['is_insurance_covered'] = $request->boolean('is_insurance_covered', true);
            $medicineData['requires_prescription'] = $request->boolean('requires_prescription', false);
            $medicineData['is_controlled'] = $request->boolean('is_controlled', false);
            $medicineData['is_active'] = $request->boolean('is_active', true);

            // إذا لم يتم تحديد سعر بيع الشريط، نحسبه تلقائياً
            if (empty($medicineData['sub_unit_sale_price']) || $medicineData['sub_unit_sale_price'] <= 0) {
                $medicineData['sub_unit_sale_price'] = round($medicineData['sale_price'] / max(1, $medicineData['sub_units_count']), 2);
            }

            $medicine = Medicine::create($medicineData);

            // إذا تم تزويد بيانات شحنة افتتاحية أولية
            if (!empty($validated['initial_batch_number']) && !empty($validated['initial_expiry_date']) && !empty($validated['initial_quantity'])) {
                MedicineBatch::create([
                    'medicine_id' => $medicine->id,
                    'batch_number' => $validated['initial_batch_number'],
                    'expiry_date' => $validated['initial_expiry_date'],
                    'initial_quantity' => (int) $validated['initial_quantity'],
                    'current_quantity' => (int) $validated['initial_quantity'],
                    'current_sub_units' => 0,
                    'purchase_price' => $medicine->cost_price,
                    'received_at' => now()->toDateString(),
                    'status' => 'active',
                ]);
            }

            DB::commit();

            return redirect()->route('pharmacy.medicines.show', $medicine->id)
                ->with('success', "تمت إضافة الدواء «{$medicine->name}» بنجاح في دليل الصيدلية.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'حدث خطأ أثناء الحفظ: ' . $e->getMessage());
        }
    }

    /**
     * إضبارة الدواء الشاملة (البيانات، الوجبات FEFO، والبدائل)
     */
    public function show(Medicine $medicine)
    {
        $medicine->load([
            'batches' => function ($q) {
                $q->orderBy('expiry_date', 'asc');
            },
            'alternatives',
            'alternativeFor',
        ]);

        // قائمة بالأدوية الأخرى للاختيار منها كبدائل
        $availableAlternatives = Medicine::where('id', '!=', $medicine->id)
            ->where('is_active', true)
            ->whereNotIn('id', $medicine->alternatives->pluck('id'))
            ->orderBy('name')
            ->get();

        return view('pharmacy.medicines.show', compact('medicine', 'availableAlternatives'));
    }

    /**
     * واجهة تعديل بيانات الدواء
     */
    public function edit(Medicine $medicine)
    {
        return view('pharmacy.medicines.edit', compact('medicine'));
    }

    /**
     * تحديث بيانات الدواء
     */
    public function update(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'generic_name' => 'nullable|string|max:255',
            'national_code' => 'nullable|string|max:100',
            'dosage_form' => 'nullable|string|max:100',
            'strength' => 'nullable|string|max:100',
            'barcode' => "nullable|string|max:100|unique:medicines,barcode,{$medicine->id}",
            'sub_barcode' => "nullable|string|max:100|unique:medicines,sub_barcode,{$medicine->id}",
            'main_unit' => 'required|string|max:50',
            'sub_unit' => 'required|string|max:50',
            'sub_units_count' => 'required|integer|min:1',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'sub_unit_sale_price' => 'nullable|numeric|min:0',
            'hi_price' => 'nullable|numeric|min:0',
            'moi_price' => 'nullable|numeric|min:0',
            'is_insurance_covered' => 'boolean',
            'min_stock_alert' => 'required|integer|min:0',
            'storage_temperature' => 'nullable|string|max:100',
            'requires_prescription' => 'boolean',
            'is_controlled' => 'boolean',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $validated['is_insurance_covered'] = $request->boolean('is_insurance_covered', true);
        $validated['requires_prescription'] = $request->boolean('requires_prescription', false);
        $validated['is_controlled'] = $request->boolean('is_controlled', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        if (empty($validated['sub_unit_sale_price']) || $validated['sub_unit_sale_price'] <= 0) {
            $validated['sub_unit_sale_price'] = round($validated['sale_price'] / max(1, $validated['sub_units_count']), 2);
        }

        $medicine->update($validated);

        return redirect()->route('pharmacy.medicines.show', $medicine->id)
            ->with('success', 'تم تحديث بيانات الدواء بنجاح.');
    }

    /**
     * حذف الدواء (أرشفة آمنة Soft Delete)
     */
    public function destroy(Medicine $medicine)
    {
        $name = $medicine->name;
        $medicine->delete();

        return redirect()->route('pharmacy.medicines.index')
            ->with('success', "تمت أرشفة الدواء «{$name}» بنجاح.");
    }

    /**
     * ربط بديل دوائي مكافئ
     */
    public function addAlternative(Request $request, Medicine $medicine)
    {
        $validated = $request->validate([
            'alternative_medicine_id' => 'required|exists:medicines,id|different:medicine_id',
            'notes' => 'nullable|string|max:255',
        ]);

        if ($medicine->id == $validated['alternative_medicine_id']) {
            return back()->with('error', 'لا يمكن تعيين الدواء كبديل لنفسه.');
        }

        $medicine->alternatives()->syncWithoutDetaching([
            $validated['alternative_medicine_id'] => ['notes' => $validated['notes'] ?? 'بديل علمي مكافئ'],
        ]);

        return back()->with('success', 'تم ربط البديل الدوائي بنجاح.');
    }

    /**
     * فك ربط بديل دوائي
     */
    public function removeAlternative(Medicine $medicine, Medicine $alternative)
    {
        $medicine->alternatives()->detach($alternative->id);
        return back()->with('success', 'تمت إزالة البديل الدوائي بنجاح.');
    }

    /**
     * شاشة استيراد الأدوية من ملفات Excel/CSV
     */
    public function showImport()
    {
        return view('pharmacy.medicines.import');
    }

    /**
     * معالجة استيراد ملف Excel أو CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $file = $request->file('file');
        $importedCount = 0;
        $updatedCount = 0;

        try {
            $data = [];
            $extension = strtolower($file->getClientOriginalExtension());

            if ($extension === 'csv' || $extension === 'txt') {
                $handle = fopen($file->getRealPath(), 'r');
                $header = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) >= 2) {
                        $data[] = $row;
                    }
                }
                fclose($handle);
            } else {
                // استخدام maatwebsite/excel لقراءة جداول xlsx
                $sheets = Excel::toArray([], $file);
                if (!empty($sheets) && !empty($sheets[0])) {
                    $sheet = $sheets[0];
                    $header = array_shift($sheet); // أول سطر رؤوس الأعمدة
                    $data = $sheet;
                }
            }

            DB::beginTransaction();

            foreach ($data as $row) {
                // تخطي الأسطر الفارغة
                if (empty($row[0]) && empty($row[1])) {
                    continue;
                }

                // قراءة الأعمدة بناءً على الترتيب القياسي:
                // 0: national_code, 1: name, 2: generic_name, 3: dosage_form, 4: strength,
                // 5: barcode, 6: main_unit, 7: sub_unit, 8: sub_units_count, 9: cost_price,
                // 10: sale_price, 11: sub_unit_sale_price, 12: hi_price, 13: is_insurance_covered
                $nationalCode = !empty($row[0]) ? trim((string)$row[0]) : null;
                $name = !empty($row[1]) ? trim((string)$row[1]) : (!empty($nationalCode) ? "دواء {$nationalCode}" : 'صنف غير مسمى');
                $genericName = !empty($row[2]) ? trim((string)$row[2]) : null;
                $dosageForm = !empty($row[3]) ? trim((string)$row[3]) : 'حبوب';
                $strength = !empty($row[4]) ? trim((string)$row[4]) : null;
                $barcode = !empty($row[5]) ? trim((string)$row[5]) : null;
                $mainUnit = !empty($row[6]) ? trim((string)$row[6]) : 'علبة';
                $subUnit = !empty($row[7]) ? trim((string)$row[7]) : 'شريط';
                $subUnitsCount = !empty($row[8]) ? max(1, (int)$row[8]) : 1;
                $costPrice = !empty($row[9]) ? (float)$row[9] : 0.00;
                $salePrice = !empty($row[10]) ? (float)$row[10] : 0.00;
                $subUnitSalePrice = !empty($row[11]) ? (float)$row[11] : round($salePrice / $subUnitsCount, 2);
                $hiPrice = !empty($row[12]) ? (float)$row[12] : $salePrice;
                $isCovered = isset($row[13]) ? (in_array(strtolower(trim((string)$row[13])), ['1', 'yes', 'true', 'نعم'])) : true;

                // مطابقة بالرمز الوطني أولاً، ثم بالباركود، ثم بالاسم
                $medicine = null;
                if (!empty($nationalCode)) {
                    $medicine = Medicine::where('national_code', $nationalCode)->first();
                }
                if (!$medicine && !empty($barcode)) {
                    $medicine = Medicine::where('barcode', $barcode)->first();
                }
                if (!$medicine) {
                    $medicine = Medicine::where('name', $name)->first();
                }

                $attributes = [
                    'national_code' => $nationalCode,
                    'name' => $name,
                    'generic_name' => $genericName,
                    'dosage_form' => $dosageForm,
                    'strength' => $strength,
                    'barcode' => $barcode,
                    'main_unit' => $mainUnit,
                    'sub_unit' => $subUnit,
                    'sub_units_count' => $subUnitsCount,
                    'cost_price' => $costPrice,
                    'sale_price' => $salePrice,
                    'sub_unit_sale_price' => $subUnitSalePrice,
                    'hi_price' => $hiPrice,
                    'is_insurance_covered' => $isCovered,
                    'is_active' => true,
                ];

                if ($medicine) {
                    $medicine->update($attributes);
                    $updatedCount++;
                } else {
                    Medicine::create($attributes);
                    $importedCount++;
                }
            }

            DB::commit();

            return redirect()->route('pharmacy.medicines.index')
                ->with('success', "تمت المعالجة بنجاح: تم استيراد {$importedCount} صنف جديد، وتحديث {$updatedCount} صنف موجود.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'فشل استيراد الملف: ' . $e->getMessage());
        }
    }

    /**
     * تحميل نموذج استيراد فارغ CSV
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="medicines_import_template.csv"',
        ];

        $columns = [
            'الرمز الوطني (National Code)',
            'الاسم التجاري (Name)',
            'الاسم العلمي (Generic Name)',
            'الشكل الصيدلاني (Dosage Form)',
            'العيار / التركيز (Strength)',
            'الباركود (Barcode)',
            'الوحدة الكبرى (Main Unit)',
            'الوحدة الصغرى (Sub Unit)',
            'معامل التحويل (Sub Units Count)',
            'سعر التكلفة (Cost Price)',
            'سعر البيع كاش (Sale Price)',
            'سعر بيع الشريط (Sub Unit Sale Price)',
            'سعر الضمان الصحي (HI Price)',
            'مشمول بالضمان (Covered: 1 or 0)',
        ];

        $sampleRow = [
            '01-C00-038',
            'Amoxicillin 500mg Cap',
            'Amoxicillin',
            'كبسول',
            '500mg',
            '628100123456',
            'علبة',
            'شريط',
            '2',
            '2000',
            '3000',
            '1500',
            '2500',
            '1',
        ];

        $callback = function () use ($columns, $sampleRow) {
            $file = fopen('php://output', 'w');
            // إضافة BOM لدعم اللغة العربية في Excel
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns);
            fputcsv($file, $sampleRow);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * واجهة برمجية API للبحث السريع عن الأدوية (لنقطة البيع والوصفات)
     */
    public function searchApi(Request $request)
    {
        $q = trim($request->get('q', ''));
        if (strlen($q) < 1) {
            return response()->json([]);
        }

        $medicines = Medicine::where('is_active', true)
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('generic_name', 'like', "%{$q}%")
                      ->orWhere('barcode', 'like', "%{$q}%")
                      ->orWhere('sub_barcode', 'like', "%{$q}%")
                      ->orWhere('national_code', 'like', "%{$q}%");
            })
            ->with(['activeBatches', 'alternatives:id,name,sale_price,sub_unit_sale_price'])
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($med) {
            return [
                'id' => $med->id,
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
                'hi_price' => $med->hi_price,
                'moi_price' => $med->moi_price,
                'is_insurance_covered' => $med->is_insurance_covered,
                'total_stock' => $med->total_stock,
                'total_open_sub_units' => $med->total_open_sub_units,
                'total_sub_units_stock' => $med->total_sub_units_stock,
                'is_low_stock' => $med->is_low_stock,
                'alternatives' => $med->alternatives,
                'batches' => $med->activeBatches->map(function ($b) {
                    return [
                        'id' => $b->id,
                        'batch_number' => $b->batch_number,
                        'expiry_date' => $b->expiry_date ? $b->expiry_date->format('Y-m-d') : null,
                        'days_until_expiry' => $b->days_until_expiry,
                        'current_quantity' => $b->current_quantity,
                        'current_sub_units' => $b->current_sub_units,
                    ];
                }),
            ];
        });

        return response()->json($results);
    }
}
