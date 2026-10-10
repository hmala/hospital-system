<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HospitalTreasuryExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions;
    }

    public function headings(): array
    {
        return [
            '#',
            'رقم السند',
            'التاريخ والوقت',
            'نوع الحركة',
            'البند / التصنيف',
            'الوصف والبيان',
            'المبلغ (د.ع)',
            'طريقة الدفع',
            'الملاحظات',
            'المستخدم المنفذ',
        ];
    }

    public function map($t): array
    {
        return [
            $t->id,
            $t->voucher_number ?: ('#' . $t->id),
            $t->performed_at ? $t->performed_at->format('Y-m-d H:i') : $t->created_at->format('Y-m-d H:i'),
            $t->isInflow() ? 'وارد (+)' : 'صادر (-)',
            $t->category_label,
            $t->description,
            number_format($t->amount),
            $t->payment_method_label,
            $t->notes ?: '-',
            optional($t->performer)->name ?: 'النظام',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
