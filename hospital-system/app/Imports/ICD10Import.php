<?php

namespace App\Imports;

use App\Models\ICD10Code;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ICD10Import implements ToCollection, WithHeadingRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $rowNumber = 1; // بداية من الصف الثاني (بعد العناوين)

        foreach ($collection as $row) {
            $rowNumber++;

            // التحقق من وجود الرمز وأنه ليس فارغ
            if (empty($row['code']) || !isset($row['code']) || trim($row['code']) === '') {
                continue; // تجاهل الصف الفارغ
            }

            // التحقق من طول الرمز
            if (strlen($row['code']) > 10) {
                throw new \Exception("الصف رقم {$rowNumber}: الرمز يجب أن يكون أقل من 10 أحرف");
            }

            ICD10Code::updateOrCreate(
                ['code' => trim($row['code'])],
                [
                    'description' => isset($row['description']) ? trim($row['description']) : null,
                    'description_ar' => isset($row['description_ar']) ? trim($row['description_ar']) : null,
                    'category' => isset($row['category']) ? trim($row['category']) : null,
                ]
            );
        }
    }
}
