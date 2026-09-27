<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineAlternative;
use App\Models\MedicineBatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OfficialMedicinesSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = database_path('seeders/official_medicines.csv');

        if (!file_exists($csvPath)) {
            $this->command->error("الملف غير موجود: {$csvPath}");
            return;
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error("تعذر فتح ملف الأدوية: {$csvPath}");
            return;
        }

        // تخطي سطر الترويسة
        $header = fgetcsv($handle);

        $count = 0;
        $batchSize = 250;
        $createdMedIds = [];

        $this->command->info("بدء استيراد قائمة الأدوية الرسمية الكاملة (3,970+ صنف)...");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row) || count($row) < 14) {
                    continue;
                }

                $nationalCode = trim($row[0] ?? '');
                $tradeName    = trim($row[1] ?? '');
                $genericName  = trim($row[2] ?? '');
                $dosageForm   = trim($row[3] ?? '');
                $strength     = trim($row[4] ?? '');
                $barcode      = trim($row[5] ?? '') ?: null;
                $mainUnit     = trim($row[6] ?? '') ?: 'علبة';
                $subUnit      = trim($row[7] ?? '') ?: 'شريط';
                $subUnitsCount = (int) ($row[8] ?? 1) ?: 1;
                $costPrice    = (float) ($row[9] ?? 0);
                $salePrice    = (float) ($row[10] ?? 0);
                $subUnitSalePrice = (float) ($row[11] ?? 0);
                $hiPrice      = (float) ($row[12] ?? 0);
                $isCovered    = (bool) ($row[13] ?? true);

                if (empty($tradeName)) {
                    continue;
                }

                if (empty($barcode)) {
                    $barcode = 'MED' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                }

                // توليد حجم التعبئة تلقائياً
                $packSize = $subUnitsCount . ' ' . $subUnit;

                $med = Medicine::firstOrCreate(
                    [
                        'name' => $tradeName,
                        'national_code' => $nationalCode ?: null,
                        'sub_units_count' => $subUnitsCount,
                    ],
                    [
                        'generic_name' => $genericName,
                        'dosage_form' => $dosageForm,
                        'strength' => $strength,
                        'pack_size' => $packSize,
                        'barcode' => $barcode,
                        'main_unit' => $mainUnit,
                        'sub_unit' => $subUnit,
                        'cost_price' => $costPrice,
                        'sale_price' => $salePrice,
                        'sub_unit_sale_price' => $subUnitSalePrice,
                        'hi_price' => $hiPrice,
                        'is_insurance_covered' => $isCovered,
                        'is_active' => true,
                    ]
                );

                $createdMedIds[] = $med->id;

                if ($med->wasRecentlyCreated || $med->batches()->count() === 0) {
                    MedicineBatch::firstOrCreate(
                        [
                            'medicine_id' => $med->id,
                            'batch_number' => 'INIT-' . strtoupper(Str::random(5)),
                        ],
                        [
                            'expiry_date' => now()->addMonths(mt_rand(12, 36))->toDateString(),
                            'initial_quantity' => 100,
                            'current_quantity' => 100,
                            'current_sub_units' => 0,
                            'purchase_price' => $costPrice ?: ($salePrice * 0.7),
                            'supplier_name' => 'المذخر المركزي / التجهيز المعتمد',
                            'received_at' => now()->toDateString(),
                            'status' => 'active',
                        ]
                    );
                }

                $count++;

                if ($count % $batchSize === 0) {
                    DB::commit();
                    DB::beginTransaction();
                    $this->command->info("تمت معالجة {$count} دواء...");
                }
            }

            fclose($handle);
            DB::commit();

            // 2. بناء شبكة البدائل التلقائية دفعة واحدة فائقة السرعة
            $this->command->info("ربط شبكة البدائل الدوائية الذكية...");
            $groups = Medicine::select('id', 'national_code')
                ->whereNotNull('national_code')
                ->where('national_code', '!=', '')
                ->whereIn('id', $createdMedIds)
                ->get()
                ->groupBy('national_code');

            $altData = [];
            $now = now();
            foreach ($groups as $nationalCode => $group) {
                if ($group->count() > 1) {
                    $ids = $group->pluck('id')->toArray();
                    foreach ($ids as $medId) {
                        foreach ($ids as $altId) {
                            if ($medId !== $altId) {
                                $altData[] = [
                                    'medicine_id' => $medId,
                                    'alternative_medicine_id' => $altId,
                                    'notes' => 'بديل مكافئ علمياً يحمل نفس الرمز الوطني والتركيز',
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ];
                            }
                        }
                    }
                }
            }

            if (!empty($altData)) {
                foreach (array_chunk($altData, 1000) as $chunk) {
                    DB::table('medicine_alternatives')->insertOrIgnore($chunk);
                }
            }

            $this->command->info("اكتمل بنجاح! تم استيراد وتجهيز {$count} دواء مع الشحنات الافتتاحية وشبكة البدائل.");

        } catch (\Exception $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            $this->command->error("حدث خطأ أثناء الاستيراد: " . $e->getMessage());
        }
    }
}
