<?php

namespace App\Exports;

use App\Models\EmergencyService;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EmergencyServicesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = EmergencyService::query()->orderBy('id');

        if ($this->request) {
            if ($this->request->filled('search')) {
                $search = $this->request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            if ($this->request->filled('category')) {
                $query->where('category', $this->request->category);
            }

            if ($this->request->filled('status')) {
                if ($this->request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($this->request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            }

            if ($this->request->filled('insurance_filter')) {
                if ($this->request->insurance_filter === 'hi_active') {
                    $query->where('is_hi_active', true);
                } elseif ($this->request->insurance_filter === 'hi_inactive') {
                    $query->where(function ($q) {
                        $q->where('is_hi_active', false)->orWhereNull('is_hi_active');
                    });
                }
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'المعرف (# ID)',
            'اسم خدمة الطوارئ (Service Name)',
            'القسم / التصنيف (Category)',
            'سعر الكاش (د.ع)',
            'سعر الضمان الصحي (د.ع)',
            'حالة الضمان الصحي',
            'حالة التفعيل',
            'تاريخ التحديث',
        ];
    }

    public function map($service): array
    {
        return [
            $service->id,
            $service->name,
            $service->category ?: 'طوارئ عامة',
            number_format($service->price, 0, '.', ','),
            $service->is_hi_active ? number_format($service->hi_price, 0, '.', ',') : 'غير مشمول',
            $service->is_hi_active ? 'مشمول بالضمان' : 'كاش فقط',
            $service->is_active ? 'نشط' : 'معطل',
            $service->updated_at ? $service->updated_at->format('Y-m-d H:i') : '-',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:H1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFDC2626'], // Emergency Red
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->setRightToLeft(true);

        return [];
    }
}
