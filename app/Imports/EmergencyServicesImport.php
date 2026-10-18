<?php

namespace App\Imports;

use App\Models\EmergencyService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyServicesImport implements ToCollection
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

        $firstRow = $rows->first();
        $colMap = $this->detectColumns($firstRow);

        $startIndex = $colMap['has_header'] ? 1 : 0;

        DB::beginTransaction();
        try {
            for ($i = $startIndex; $i < $rows->count(); $i++) {
                $row = $rows[$i];

                $id = isset($colMap['id']) && isset($row[$colMap['id']]) ? trim($row[$colMap['id']]) : null;
                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim($row[$colMap['name']]) : null;

                // Find existing emergency service
                $service = null;
                if (!empty($id) && is_numeric($id)) {
                    $service = EmergencyService::find((int) $id);
                }
                if (!$service && !empty($name)) {
                    $service = EmergencyService::where('name', $name)->first();
                }

                if (!$service) {
                    $this->skippedCount++;
                    continue;
                }

                $updates = [];

                // 1. Price (سعر الكاش)
                if (isset($colMap['price']) && isset($row[$colMap['price']])) {
                    $rawPrice = $this->cleanNumber($row[$colMap['price']]);
                    if ($rawPrice !== null && is_numeric($rawPrice) && $rawPrice >= 0) {
                        $updates['price'] = (float) $rawPrice;
                    }
                }

                // 2. HI Price (سعر الضمان)
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

                // 4. Category (التصنيف / القسم)
                if (isset($colMap['category']) && isset($row[$colMap['category']])) {
                    $cat = trim((string) $row[$colMap['category']]);
                    if (!empty($cat) && $cat !== '-') {
                        $updates['category'] = $cat;
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

                // Zero out police insurance per current hospital rules
                $updates['moi_price'] = 0.00;
                $updates['is_moi_active'] = false;

                if (!empty($updates)) {
                    $service->update($updates);
                    $this->updatedCount++;
                } else {
                    $this->skippedCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("EmergencyServicesImport failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    protected function detectColumns($headerRow): array
    {
        $map = [
            'has_header' => false,
            'id' => null,
            'name' => null,
            'category' => null,
            'price' => null,
            'hi_price' => null,
            'is_hi_active' => null,
            'is_active' => null,
        ];

        $headerArray = is_array($headerRow) ? $headerRow : (method_exists($headerRow, 'toArray') ? $headerRow->toArray() : (array) $headerRow);

        $rowText = implode(' ', array_map('strval', $headerArray));
        if (preg_match('/(معرف|اسم|خدمة|طوارئ|سعر|كاش|ضمان|category|price|id)/iu', $rowText)) {
            $map['has_header'] = true;

            foreach ($headerArray as $idx => $val) {
                $valStr = mb_strtolower(trim((string) $val));

                if (preg_match('/(معرف|id)/iu', $valStr) && $map['id'] === null) {
                    $map['id'] = $idx;
                } elseif (preg_match('/(اسم|service name|خدمة)/iu', $valStr) && $map['name'] === null) {
                    $map['name'] = $idx;
                } elseif (preg_match('/(قسم|تصنيف|category)/iu', $valStr) && $map['category'] === null) {
                    $map['category'] = $idx;
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

        // Standard export order:
        // [0: ID, 1: Name, 2: Category, 3: Price, 4: HI Price, 5: HI Status, 6: Active, 7: UpdatedAt]
        if ($map['id'] === null) $map['id'] = 0;
        if ($map['name'] === null) $map['name'] = 1;
        if ($map['category'] === null) $map['category'] = 2;
        if ($map['price'] === null) $map['price'] = 3;
        if ($map['hi_price'] === null) $map['hi_price'] = 4;
        if ($map['is_hi_active'] === null) $map['is_hi_active'] = 5;
        if ($map['is_active'] === null) $map['is_active'] = 6;

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
