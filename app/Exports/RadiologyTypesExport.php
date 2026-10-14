<?php

namespace App\Exports;

use App\Models\RadiologyType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RadiologyTypesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = RadiologyType::orderBy('main_category')->orderBy('name');

        if ($this->request) {
            if ($this->request->filled('search')) {
                $search = $this->request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if ($this->request->filled('category')) {
                $query->where('main_category', $this->request->category);
            }

            if ($this->request->filled('insurance_filter')) {
                if ($this->request->insurance_filter === 'hi_active') {
                    $query->where('is_hi_active', true);
                } elseif ($this->request->insurance_filter === 'none_active') {
                    $query->where('is_hi_active', false);
                }
            }

            if ($this->request->filled('is_active')) {
                $query->where('is_active', $this->request->is_active);
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'المعرف (# ID)',
            'اسم الفحص الشعاعي (Service Name)',
            'القسم التشخيصي (Category)',
            'الرمز الطبي (Code)',
            'سعر الكاش (د.ع)',
            'سعر الضمان الصحي - فئة ب (د.ع)',
            'حالة الضمان الصحي',
            'يتطلب صبغة؟',
            'حالة التفعيل',
            'تاريخ التحديث',
        ];
    }

    public function map($r): array
    {
        return [
            $r->id,
            $r->name,
            $r->main_category ?: 'أشعة',
            $r->code ?: '-',
            number_format($r->base_price, 0, '.', ','),
            $r->is_hi_active ? number_format($r->hi_price, 0, '.', ',') : 'غير مشمول',
            $r->is_hi_active ? 'مشمول بالضمان' : 'كاش فقط',
            $r->requires_contrast ? 'نعم' : 'لا',
            $r->is_active ? 'نشط' : 'معطل',
            $r->updated_at ? $r->updated_at->format('Y-m-d H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF15803D'], // Emerald green
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
