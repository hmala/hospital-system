<?php

namespace App\Exports;

use App\Models\Visit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VisitsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $request;

    public function __construct($request = null)
    {
        $this->request = $request;
    }

    public function collection()
    {
        $query = Visit::with(['patient.user', 'doctor.user', 'department', 'surgery'])
            ->latest('visit_date')
            ->orderBy('visit_time', 'desc');

        if ($this->request) {
            if ($this->request->filled('search')) {
                $search = $this->request->search;
                $query->where(function ($q) use ($search) {
                    $q->whereHas('patient', function ($pq) use ($search) {
                        $pq->where('file_number', 'like', "%{$search}%")
                           ->orWhere('national_id', 'like', "%{$search}%")
                           ->orWhereHas('user', function ($uq) use ($search) {
                               $uq->where('name', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%");
                           });
                    })
                    ->orWhere('chief_complaint', 'like', "%{$search}%");
                });
            }

            if ($this->request->filled('doctor_id')) {
                $query->where('doctor_id', $this->request->doctor_id);
            }

            if ($this->request->filled('department_id')) {
                $query->where('department_id', $this->request->department_id);
            }

            if ($this->request->filled('status')) {
                $query->where('status', $this->request->status);
            }

            if ($this->request->filled('visit_type')) {
                $query->where('visit_type', $this->request->visit_type);
            }

            if ($this->request->filled('needs_surgery')) {
                if ($this->request->needs_surgery === '1') {
                    $query->where('needs_surgery', true);
                } elseif ($this->request->needs_surgery === '0') {
                    $query->where('needs_surgery', false);
                }
            }

            if ($this->request->filled('date_from')) {
                $query->whereDate('visit_date', '>=', $this->request->date_from);
            }
            if ($this->request->filled('date_to')) {
                $query->whereDate('visit_date', '<=', $this->request->date_to);
            }
        }

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'رقم الزيارة (# ID)',
            'تاريخ الزيارة',
            'وقت الزيارة',
            'اسم المريض',
            'رقم الهاتف',
            'رقم الإضبارة',
            'الطبيب المعالج',
            'العيادة / القسم',
            'نوع الزيارة',
            'حالة الزيارة',
            'الشكوى والأعراض',
            'التشخيص الطبي',
            'هل يحتاج عملية؟',
            'حالة العملية',
        ];
    }

    public function map($visit): array
    {
        $diagnosisText = '-';
        if (is_array($visit->diagnosis)) {
            $diagnosisText = $visit->diagnosis['description'] ?? ($visit->diagnosis['name'] ?? json_encode($visit->diagnosis, JSON_UNESCAPED_UNICODE));
        } elseif (!empty($visit->diagnosis)) {
            $diagnosisText = (string) $visit->diagnosis;
        }

        $surgeryStatus = 'لا يحتاج عملية';
        if ($visit->needs_surgery) {
            $surgeryStatus = $visit->surgery ? 'تم حجز العملية بنجاح' : 'معلقة (بانتظار الحجز)';
        }

        return [
            $visit->id,
            $visit->visit_date ? $visit->visit_date->format('Y-m-d') : '-',
            $visit->visit_time ? $visit->visit_time->format('H:i') : '-',
            optional($visit->patient?->user)->name ?? 'غير معروف',
            optional($visit->patient?->user)->phone ?? '-',
            $visit->patient?->file_number ?? '-',
            optional($visit->doctor?->user)->name ? ('د. ' . $visit->doctor->user->name) : '-',
            $visit->department?->name ?? '-',
            $visit->visit_type_text,
            $visit->status_text,
            $visit->chief_complaint ?: '-',
            $diagnosisText,
            $visit->needs_surgery ? 'نعم' : 'لا',
            $surgeryStatus,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1:N1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E40AF'], // Indigo Blue
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
