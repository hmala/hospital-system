<?php

namespace App\Imports;

use App\Models\RadiologyType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RadiologyTypesImport implements ToCollection
{
    public int $updatedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];

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
            for ($i = $startIndex; $i < $rows->count(); $i++) {
                $row = $rows[$i];

                $id = isset($colMap['id']) && isset($row[$colMap['id']]) ? trim($row[$colMap['id']]) : null;
                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim($row[$colMap['name']]) : null;
                $code = isset($colMap['code']) && isset($row[$colMap['code']]) ? trim($row[$colMap['code']]) : null;

                // Find existing radiology service
                $radiologyType = null;
                if (!empty($id) && is_numeric($id)) {
                    $radiologyType = RadiologyType::find((int) $id);
                }
                if (!$radiologyType && !empty($code) && $code !== '-') {
                    $radiologyType = RadiologyType::where('code', $code)->first();
                }
                if (!$radiologyType && !empty($name)) {
                    $radiologyType = RadiologyType::where('name', $name)->first();
                }

                if (!$radiologyType) {
                    $this->skippedCount++;
                    continue;
                }

                $updates = [];

                // 1. Base Price (سعر الكاش)
                if (isset($colMap['base_price']) && isset($row[$colMap['base_price']])) {
                    $rawPrice = $this->cleanNumber($row[$colMap['base_price']]);
                    if ($rawPrice !== null && is_numeric($rawPrice) && $rawPrice >= 0) {
                        $updates['base_price'] = (float) $rawPrice;
                    }
                }

                // 2. HI Price (سعر الضمان الصحي)
                if (isset($colMap['hi_price']) && isset($row[$colMap['hi_price']])) {
                    $rawHiPrice = $this->cleanNumber($row[$colMap['hi_price']]);
                    if ($rawHiPrice !== null && is_numeric($rawHiPrice) && $rawHiPrice >= 0) {
                        $updates['hi_price'] = (float) $rawHiPrice;
                    }
                }

                // 3. HI Active status (حالة الضمان)
                if (isset($colMap['is_hi_active']) && isset($row[$colMap['is_hi_active']])) {
                    $rawHiStatus = trim((string) $row[$colMap['is_hi_active']]);
                    if (in_array(mb_strtolower($rawHiStatus), ['مشمول', 'مشمول بالضمان', 'نعم', '1', 'yes', 'true'])) {
                        $updates['is_hi_active'] = true;
                    } elseif (in_array(mb_strtolower($rawHiStatus), ['غير مشمول', 'كاش فقط', 'لا', '0', 'no', 'false'])) {
                        $updates['is_hi_active'] = false;
                    }
                } elseif (isset($updates['hi_price'])) {
                    if ($updates['hi_price'] > 0) {
                        $updates['is_hi_active'] = true;
                    }
                }

                // 4. Requires Contrast (يتطلب صبغة؟)
                if (isset($colMap['requires_contrast']) && isset($row[$colMap['requires_contrast']])) {
                    $rawContrast = trim((string) $row[$colMap['requires_contrast']]);
                    if (in_array(mb_strtolower($rawContrast), ['نعم', '1', 'yes', 'true'])) {
                        $updates['requires_contrast'] = true;
                    } elseif (in_array(mb_strtolower($rawContrast), ['لا', '0', 'no', 'false'])) {
                        $updates['requires_contrast'] = false;
                    }
                }

                // 5. Active Status (حالة التفعيل)
                if (isset($colMap['is_active']) && isset($row[$colMap['is_active']])) {
                    $rawActive = trim((string) $row[$colMap['is_active']]);
                    if (in_array(mb_strtolower($rawActive), ['نشط', 'مفعل', 'نعم', '1', 'yes', 'true', 'active'])) {
                        $updates['is_active'] = true;
                    } elseif (in_array(mb_strtolower($rawActive), ['معطل', 'ملغي', 'غير نشط', 'لا', '0', 'no', 'false', 'inactive'])) {
                        $updates['is_active'] = false;
                    }
                }

                if (!empty($updates)) {
                    $radiologyType->update($updates);
                    $this->updatedCount++;
                } else {
                    $this->skippedCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("RadiologyTypesImport failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    protected function detectColumns($headerRow): array
    {
        $map = [
            'has_header' => false,
            'id' => null,
            'name' => null,
            'main_category' => null,
            'code' => null,
            'base_price' => null,
            'hi_price' => null,
            'is_hi_active' => null,
            'requires_contrast' => null,
            'is_active' => null,
        ];

        $headerArray = is_array($headerRow) ? $headerRow : (method_exists($headerRow, 'toArray') ? $headerRow->toArray() : (array) $headerRow);

        $rowText = implode(' ', array_map('strval', $headerArray));
        if (preg_match('/(معرف|اسم|فحص|سعر|كاش|ضمان|صبغة|code|price|id)/iu', $rowText)) {
            $map['has_header'] = true;

            foreach ($headerArray as $idx => $val) {
                $valStr = mb_strtolower(trim((string) $val));

                if (preg_match('/(معرف|id)/iu', $valStr) && $map['id'] === null) {
                    $map['id'] = $idx;
                } elseif (preg_match('/(رمز|code)/iu', $valStr) && $map['code'] === null) {
                    $map['code'] = $idx;
                } elseif (preg_match('/(اسم|service name)/iu', $valStr) && $map['name'] === null) {
                    $map['name'] = $idx;
                } elseif (preg_match('/(قسم|جهاز|category)/iu', $valStr) && $map['main_category'] === null) {
                    $map['main_category'] = $idx;
                } elseif (preg_match('/(سعر الكاش|سعر|price|cash)/iu', $valStr) && !str_contains($valStr, 'ضمان') && $map['base_price'] === null) {
                    $map['base_price'] = $idx;
                } elseif (preg_match('/(سعر الضمان|ضمان.*سعر|hi_price)/iu', $valStr) && $map['hi_price'] === null) {
                    $map['hi_price'] = $idx;
                } elseif (preg_match('/(حالة الضمان|شمول الضمان)/iu', $valStr) && $map['is_hi_active'] === null) {
                    $map['is_hi_active'] = $idx;
                } elseif (preg_match('/(صبغة|contrast)/iu', $valStr) && $map['requires_contrast'] === null) {
                    $map['requires_contrast'] = $idx;
                } elseif (preg_match('/(تفعيل|حالة التفعيل|active)/iu', $valStr) && $map['is_active'] === null) {
                    $map['is_active'] = $idx;
                }
            }
        }

        // Standard export order:
        // [0: ID, 1: Name, 2: Category, 3: Code, 4: Price, 5: HI Price, 6: HI Status, 7: Contrast, 8: Active, 9: UpdatedAt]
        if ($map['id'] === null) $map['id'] = 0;
        if ($map['name'] === null) $map['name'] = 1;
        if ($map['main_category'] === null) $map['main_category'] = 2;
        if ($map['code'] === null) $map['code'] = 3;
        if ($map['base_price'] === null) $map['base_price'] = 4;
        if ($map['hi_price'] === null) $map['hi_price'] = 5;
        if ($map['is_hi_active'] === null) $map['is_hi_active'] = 6;
        if ($map['requires_contrast'] === null) $map['requires_contrast'] = 7;
        if ($map['is_active'] === null) $map['is_active'] = 8;

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

        $clean = preg_replace('/[^\d.]/', '', (string) $val);
        return $clean !== '' ? (float) $clean : null;
    }
}
