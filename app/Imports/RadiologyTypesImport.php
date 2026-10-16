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
    public int $createdCount = 0;
    public int $deletedCount = 0;
    public int $skippedCount = 0;
    public array $errors = [];
    public bool $replaceExisting = true;

    public function __construct(bool $replaceExisting = true)
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

            // If replaceExisting is true, clean up items not in Excel before importing to avoid unique code collisions
            if ($this->replaceExisting) {
                $incomingIds = [];
                for ($j = $startIndex; $j < $rows->count(); $j++) {
                    $rId = isset($colMap['id']) && isset($rows[$j][$colMap['id']]) ? trim((string)$rows[$j][$colMap['id']]) : null;
                    if (!empty($rId) && is_numeric($rId)) {
                        $incomingIds[] = (int)$rId;
                    }
                }

                if (!empty($incomingIds)) {
                    $staleItems = RadiologyType::whereNotIn('id', $incomingIds)->get();
                    foreach ($staleItems as $stale) {
                        $hasRequests = $stale->requests()->count() > 0 
                            || DB::table('requests')->where('type', 'radiology')->where('details', 'like', '%"radiology_type_id":' . $stale->id . '%')->exists();

                        if (!$hasRequests) {
                            $stale->delete();
                            $this->deletedCount++;
                        } else {
                            $stale->is_active = false;
                            $stale->save();
                        }
                    }

                    // Temporarily prefix codes on incoming records so that swapped codes between rows don't cause unique constraint violations
                    $concatExpr = DB::connection()->getDriverName() === 'sqlite' 
                        ? "'TMP-' || id || '-' || code" 
                        : "CONCAT('TMP-', id, '-', code)";
                    DB::table('radiology_types')->whereIn('id', $incomingIds)->update(['code' => DB::raw($concatExpr)]);
                }
            } else {
                // Gather incoming IDs to clear codes temporarily
                $incomingIds = [];
                for ($j = $startIndex; $j < $rows->count(); $j++) {
                    $rId = isset($colMap['id']) && isset($rows[$j][$colMap['id']]) ? trim((string)$rows[$j][$colMap['id']]) : null;
                    if (!empty($rId) && is_numeric($rId)) {
                        $incomingIds[] = (int)$rId;
                    }
                }
                if (!empty($incomingIds)) {
                    $concatExpr = DB::connection()->getDriverName() === 'sqlite' 
                        ? "'TMP-' || id || '-' || code" 
                        : "CONCAT('TMP-', id, '-', code)";
                    DB::table('radiology_types')->whereIn('id', $incomingIds)->update(['code' => DB::raw($concatExpr)]);
                }
            }

            for ($i = $startIndex; $i < $rows->count(); $i++) {
                $row = $rows[$i];

                $id = isset($colMap['id']) && isset($row[$colMap['id']]) ? trim((string)$row[$colMap['id']]) : null;
                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim((string)$row[$colMap['name']]) : null;
                $code = isset($colMap['code']) && isset($row[$colMap['code']]) ? trim((string)$row[$colMap['code']]) : null;
                $mainCat = isset($colMap['main_category']) && isset($row[$colMap['main_category']]) ? trim((string)$row[$colMap['main_category']]) : null;

                if (empty($name)) {
                    $this->skippedCount++;
                    continue;
                }

                // Map Category and Subcategory properly
                $catMapping = $this->normalizeCategory($mainCat);
                $subcategory = $catMapping['subcategory'];
                $mainCategory = $catMapping['main_category'];

                // Prices
                $rawPrice = isset($colMap['base_price']) && isset($row[$colMap['base_price']]) ? $this->cleanNumber($row[$colMap['base_price']]) : 0;
                $rawHiPrice = isset($colMap['hi_price']) && isset($row[$colMap['hi_price']]) ? $this->cleanNumber($row[$colMap['hi_price']]) : 0;

                // HI Active Status
                $isHiActive = ((float)$rawHiPrice > 0);
                if (isset($colMap['is_hi_active']) && isset($row[$colMap['is_hi_active']])) {
                    $rawHiStatus = trim((string)$row[$colMap['is_hi_active']]);
                    if (in_array(mb_strtolower($rawHiStatus), ['مشمول', 'مشمول بالضمان', 'نعم', '1', 'yes', 'true'])) {
                        $isHiActive = true;
                    } elseif (in_array(mb_strtolower($rawHiStatus), ['غير مشمول', 'كاش فقط', 'لا', '0', 'no', 'false'])) {
                        $isHiActive = false;
                    }
                }

                // Requires Contrast
                $requiresContrast = false;
                if (isset($colMap['requires_contrast']) && isset($row[$colMap['requires_contrast']])) {
                    $rawContrast = trim((string)$row[$colMap['requires_contrast']]);
                    if (in_array(mb_strtolower($rawContrast), ['نعم', '1', 'yes', 'true'])) {
                        $requiresContrast = true;
                    }
                }

                // Is Active
                $isActive = true;
                if (isset($colMap['is_active']) && isset($row[$colMap['is_active']])) {
                    $rawActive = trim((string)$row[$colMap['is_active']]);
                    if (in_array(mb_strtolower($rawActive), ['معطل', 'ملغي', 'غير نشط', 'لا', '0', 'no', 'false', 'inactive'])) {
                        $isActive = false;
                    }
                }

                // Find existing by ID or Code
                $radiologyType = null;
                if (!empty($id) && is_numeric($id)) {
                    $radiologyType = RadiologyType::find((int)$id);
                }
                if (!$radiologyType && !empty($code) && $code !== '-') {
                    $radiologyType = RadiologyType::where('code', $code)->first();
                }

                $cleanCode = (!empty($code) && $code !== '-') ? $code : null;

                if ($radiologyType) {
                    $radiologyType->name = $name;
                    if ($cleanCode) {
                        $radiologyType->code = $cleanCode;
                    }
                    $radiologyType->subcategory = $subcategory;
                    $radiologyType->main_category = $mainCategory;
                    $radiologyType->base_price = (float)($rawPrice ?: 0);
                    $radiologyType->hi_price = (float)($rawHiPrice ?: 0);
                    $radiologyType->is_hi_active = $isHiActive;
                    $radiologyType->requires_contrast = $requiresContrast;
                    $radiologyType->is_active = $isActive;
                    $radiologyType->estimated_duration = $radiologyType->estimated_duration ?: 15;
                    $radiologyType->save();

                    $processedIds[] = $radiologyType->id;
                    $this->updatedCount++;
                } else {
                    $newType = new RadiologyType();
                    if (!empty($id) && is_numeric($id)) {
                        $newType->id = (int)$id;
                    }
                    $newType->name = $name;
                    $newType->code = $cleanCode;
                    $newType->subcategory = $subcategory;
                    $newType->main_category = $mainCategory;
                    $newType->base_price = (float)($rawPrice ?: 0);
                    $newType->hi_price = (float)($rawHiPrice ?: 0);
                    $newType->is_hi_active = $isHiActive;
                    $newType->requires_contrast = $requiresContrast;
                    $newType->is_active = $isActive;
                    $newType->estimated_duration = 15;
                    $newType->save();

                    $processedIds[] = $newType->id;
                    $this->createdCount++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("RadiologyTypesImport failed: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    protected function normalizeCategory(?string $rawCat): array
    {
        $cat = trim((string)$rawCat);
        return match ($cat) {
            'رنين', 'الرنين' => ['subcategory' => 'الرنين', 'main_category' => 'الرنين'],
            'إيكو', 'إيكو القلب' => ['subcategory' => 'إيكو', 'main_category' => 'إيكو'],
            'مفراس' => ['subcategory' => 'مفراس', 'main_category' => 'مفراس'],
            'سونار' => ['subcategory' => 'سونار', 'main_category' => 'سونار'],
            default => ['subcategory' => 'أشعة', 'main_category' => 'أشعة'],
        };
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
