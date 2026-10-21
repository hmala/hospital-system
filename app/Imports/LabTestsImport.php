<?php

namespace App\Imports;

use App\Models\LabTest;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LabTestsImport implements ToCollection
{
    public int $createdCount = 0;
    public int $updatedCount = 0;
    public int $deletedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    public bool $replaceExisting = false;

    public function __construct(bool $replaceExisting = false)
    {
        $this->replaceExisting = $replaceExisting;
    }

    /**
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Map column indices dynamically from header row
        $firstRow = $rows->first();
        $colMap = $this->detectColumns($firstRow);

        $startIndex = $colMap['has_header'] ? 1 : 0;

        DB::beginTransaction();
        try {
            $processedIds = [];

            // If replaceExisting is requested, handle stale tests not present in incoming Excel
            if ($this->replaceExisting) {
                $incomingIds = [];
                for ($j = $startIndex; $j < $rows->count(); $j++) {
                    $rId = isset($colMap['id']) && isset($rows[$j][$colMap['id']]) ? trim((string)$rows[$j][$colMap['id']]) : null;
                    if (!empty($rId) && is_numeric($rId)) {
                        $incomingIds[] = (int)$rId;
                    }
                }

                if (!empty($incomingIds)) {
                    $staleItems = LabTest::whereNotIn('id', $incomingIds)->get();
                    foreach ($staleItems as $stale) {
                        // Check if referenced in requests, results, or surgery tests
                        $hasRequests = DB::table('requests')->where('type', 'lab')
                            ->where(function($q) use ($stale) {
                                $q->where('details', 'like', '%"test_id":' . $stale->id . '%')
                                  ->orWhere('details', 'like', '%"lab_test_ids":%' . $stale->id . '%')
                                  ->orWhere('details', 'like', '%"' . $stale->id . '"%');
                            })->exists()
                            || DB::table('lab_test_results')->where('lab_test_id', $stale->id)->exists()
                            || DB::table('surgery_lab_tests')->where('lab_test_id', $stale->id)->exists();

                        if (!$hasRequests) {
                            $stale->delete();
                            $this->deletedCount++;
                        } else {
                            $stale->is_active = false;
                            $stale->save();
                        }
                    }
                }
            }

            for ($i = $startIndex; $i < $rows->count(); $i++) {
                $row = $rows[$i];
                $rowNum = $i + 1;

                $id = isset($colMap['id']) && isset($row[$colMap['id']]) ? trim((string)$row[$colMap['id']]) : null;
                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim((string)$row[$colMap['name']]) : null;
                $code = isset($colMap['code']) && isset($row[$colMap['code']]) ? trim((string)$row[$colMap['code']]) : null;
                $mainCat = isset($colMap['main_category']) && isset($row[$colMap['main_category']]) ? trim((string)$row[$colMap['main_category']]) : null;
                $subCat = isset($colMap['subcategory']) && isset($row[$colMap['subcategory']]) ? trim((string)$row[$colMap['subcategory']]) : null;

                if (empty($name)) {
                    $this->skippedCount++;
                    continue;
                }

                // Prices
                $price = 0.0;
                if (isset($colMap['price']) && isset($row[$colMap['price']])) {
                    $rawPrice = $this->cleanNumber($row[$colMap['price']]);
                    if ($rawPrice !== null && $rawPrice >= 0) {
                        $price = (float) $rawPrice;
                    }
                }

                $hiPrice = 0.0;
                if (isset($colMap['hi_price']) && isset($row[$colMap['hi_price']])) {
                    $rawHiPrice = $this->cleanNumber($row[$colMap['hi_price']]);
                    if ($rawHiPrice !== null && $rawHiPrice >= 0) {
                        $hiPrice = (float) $rawHiPrice;
                    }
                }

                // HI Active status
                $isHiActive = ($hiPrice > 0);
                if (isset($colMap['is_hi_active']) && isset($row[$colMap['is_hi_active']])) {
                    $rawHiStatus = trim((string) $row[$colMap['is_hi_active']]);
                    if (in_array(mb_strtolower($rawHiStatus), ['مشمول', 'مشمول بالضمان', 'نعم', '1', 'yes', 'true'])) {
                        $isHiActive = true;
                    } elseif (in_array(mb_strtolower($rawHiStatus), ['غير مشمول', 'كاش فقط', 'لا', '0', 'no', 'false'])) {
                        $isHiActive = false;
                    }
                }

                // Active status
                $isActive = true;
                if (isset($colMap['is_active']) && isset($row[$colMap['is_active']])) {
                    $rawActive = trim((string) $row[$colMap['is_active']]);
                    if (in_array(mb_strtolower($rawActive), ['معطل', 'ملغي', 'غير نشط', 'لا', '0', 'no', 'false', 'inactive'])) {
                        $isActive = false;
                    }
                }

                $cleanCode = (!empty($code) && $code !== '-') ? $code : null;
                $cleanSub = (!empty($subCat) && $subCat !== '-') ? $subCat : null;
                $cleanMain = (!empty($mainCat) && $mainCat !== '-') ? $mainCat : 'other';

                // Find existing test
                $labTest = null;
                if (!empty($id) && is_numeric($id)) {
                    $labTest = LabTest::find((int) $id);
                }

                if ($labTest) {
                    $updates = [];
                    if ($labTest->name !== $name) $updates['name'] = $name;
                    if ($labTest->code !== $cleanCode) $updates['code'] = $cleanCode;
                    if ($cleanMain && $labTest->main_category !== $cleanMain) $updates['main_category'] = $cleanMain;
                    if ($cleanSub && $labTest->subcategory !== $cleanSub) $updates['subcategory'] = $cleanSub;
                    if (abs((float)$labTest->price - $price) > 0.01) $updates['price'] = $price;
                    if (abs((float)$labTest->hi_price - $hiPrice) > 0.01) $updates['hi_price'] = $hiPrice;
                    if ($labTest->is_hi_active !== $isHiActive) $updates['is_hi_active'] = $isHiActive;
                    if ($labTest->is_active !== $isActive) $updates['is_active'] = $isActive;

                    if (!empty($updates)) {
                        $labTest->update($updates);
                    }
                    $this->updatedCount++;
                } else {
                    // Create new test
                    $newAttributes = [
                        'name' => $name,
                        'code' => $cleanCode,
                        'main_category' => $cleanMain,
                        'subcategory' => $cleanSub,
                        'price' => $price,
                        'hi_price' => $hiPrice,
                        'is_hi_active' => $isHiActive,
                        'is_active' => $isActive,
                    ];

                    if (!empty($id) && is_numeric($id) && !LabTest::where('id', (int)$id)->exists()) {
                        $newAttributes['id'] = (int)$id;
                    }

                    LabTest::create($newAttributes);
                    $this->createdCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("LabTestsImport failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    protected function detectColumns($headerRow): array
    {
        $map = [
            'has_header' => false,
            'id' => null,
            'name' => null,
            'code' => null,
            'main_category' => null,
            'subcategory' => null,
            'price' => null,
            'hi_price' => null,
            'is_hi_active' => null,
            'is_active' => null,
        ];

        $headerArray = is_array($headerRow) ? $headerRow : (method_exists($headerRow, 'toArray') ? $headerRow->toArray() : (array) $headerRow);

        $rowText = implode(' ', array_map('strval', $headerArray));
        if (preg_match('/(معرف|اسم|فحص|سعر|كاش|ضمان|code|price|id)/iu', $rowText)) {
            $map['has_header'] = true;

            foreach ($headerArray as $idx => $val) {
                $valStr = mb_strtolower(trim((string) $val));

                if (preg_match('/(معرف|id)/iu', $valStr) && $map['id'] === null) {
                    $map['id'] = $idx;
                } elseif (preg_match('/(رمز|code)/iu', $valStr) && $map['code'] === null) {
                    $map['code'] = $idx;
                } elseif (preg_match('/(اسم|test name)/iu', $valStr) && $map['name'] === null) {
                    $map['name'] = $idx;
                } elseif (preg_match('/(قسم رئيسي|main category|category|فئة)/iu', $valStr) && $map['main_category'] === null) {
                    $map['main_category'] = $idx;
                } elseif (preg_match('/(تصنيف فرعي|subcategory)/iu', $valStr) && $map['subcategory'] === null) {
                    $map['subcategory'] = $idx;
                } elseif (preg_match('/(سعر الكاش|سعر|price|cash)/iu', $valStr) && !str_contains($valStr, 'ضمان') && $map['price'] === null) {
                    $map['price'] = $idx;
                } elseif (preg_match('/(سعر الضمان|ضمان.*سعر|hi_price)/iu', $valStr) && $map['hi_price'] === null) {
                    $map['hi_price'] = $idx;
                } elseif (preg_match('/(حالة الضمان|شمول الضمان)/iu', $valStr) && $map['is_hi_active'] === null) {
                    $map['is_hi_active'] = $idx;
                } elseif (preg_match('/(تفعيل|حالة التفعيل|active)/iu', $valStr) && $map['is_active'] === null) {
                    $map['is_active'] = $idx;
                }
            }
        }

        // Fallbacks if not recognized by header names
        if ($map['id'] === null) $map['id'] = 0;
        if ($map['name'] === null) $map['name'] = 1;
        if ($map['code'] === null) $map['code'] = 2;
        if ($map['main_category'] === null) $map['main_category'] = 3;
        if ($map['subcategory'] === null) $map['subcategory'] = 4;
        if ($map['price'] === null) $map['price'] = 5;
        if ($map['hi_price'] === null) $map['hi_price'] = 6;
        if ($map['is_hi_active'] === null) $map['is_hi_active'] = 7;
        if ($map['is_active'] === null) $map['is_active'] = 9;

        return $map;
    }

    protected function cleanNumber($val): ?float
    {
        if ($val === null || $val === '' || $val === '-') {
            return null;
        }

        if (is_numeric($val)) {
            return (float) $val;
        }

        $cleaned = preg_replace('/[^\d.]/', '', (string) $val);

        if ($cleaned === '' || !is_numeric($cleaned)) {
            return null;
        }

        return (float) $cleaned;
    }
}
