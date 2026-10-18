<?php

namespace App\Exports;

use App\Models\LabTest;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LabTestsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = LabTest::query()->orderBy('id');
        if (\Illuminate\Support\Facades\Schema::hasTable('lab_test_sub_tests')) {
            $query->withCount('subTests');
        }

        if ($this->request) {
            if ($this->request->filled('search')) {
                $search = $this->request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            if ($this->request->filled('subcategory')) {
                $query->where('subcategory', $this->request->subcategory);
            }

            if ($this->request->filled('is_hi_active')) {
                $query->where('is_hi_active', $this->request->is_hi_active);
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'المعرف (# ID)',
            'اسم الفحص المخبري (Test Name)',
            'الرمز الطبي (Code)',
            'القسم الرئيسي (Main Category)',
            'التصنيف الفرعي (Subcategory)',
            'سعر الكاش (د.ع)',
            'سعر الضمان الصحي (د.ع)',
            'حالة الضمان الصحي',
            'الفحوصات الفرعية',
            'حالة التفعيل',
            'تاريخ التحديث',
        ];
    }

    public function map($test): array
    {
        return [
            $test->id,
            $test->name,
            $test->code ?: '-',
            $test->main_category ?: 'المختبر',
            $test->subcategory ?: '-',
            number_format($test->price, 0, '.', ','),
            $test->is_hi_active ? number_format($test->hi_price, 0, '.', ',') : 'غير مشمول',
            $test->is_hi_active ? 'مشمول بالضمان' : 'كاش فقط',
            $test->sub_tests_count > 0 ? ($test->sub_tests_count . ' فحص فرعي') : '-',
            $test->is_active ? 'نشط' : 'معطل',
            $test->updated_at ? $test->updated_at->format('Y-m-d H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Style headings row
        $sheet->getStyle('A1:K1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A8A'], // Navy blue
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setRightToLeft(true);

        return [
            1 => ['row_height' => 30],
        ];
    }
}
