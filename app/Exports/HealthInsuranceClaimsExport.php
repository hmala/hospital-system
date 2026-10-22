<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HealthInsuranceClaimsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    protected $claims;

    public function __construct($claims)
    {
        $this->claims = $claims;
    }

    public function collection()
    {
        return $this->claims;
    }

    public function headings(): array
    {
        return [
            '#',
            'رقم الإيصال',
            'تاريخ الخدمة',
            'اسم المريض',
            'فئة الضمان',
            'رقم بطاقة / باج الضمان',
            'نوع التأمين',
            'القسم / نوع الخدمة',
            'الطبيب المعالج / الاستشاري',
            'تفاصيل الفحص والخدمة',
            'السعر الإجمالي (د.ع)',
            'تحمل المريض نقداً (د.ع)',
            'مطالبة الضمان الصحي (د.ع)',
            'نسبة تحمل المريض',
            'نسبة تغطية الضمان',
            'الكاشير / المنفذ',
        ];
    }

    public function map($payment): array
    {
        $gross = (float)($payment->amount + $payment->insurance_share);
        $patientPaid = (float)$payment->amount;
        $insuranceShare = (float)$payment->insurance_share;

        $copayPercent = $payment->copay_percentage ?? ($gross > 0 ? round(($patientPaid / $gross) * 100, 1) : 0);
        $coveragePercent = $gross > 0 ? round(($insuranceShare / $gross) * 100, 1) : (100 - $copayPercent);

        $patientCategory = $payment->patient?->healthInsuranceCategory?->name 
            ? $payment->patient->healthInsuranceCategory->name . ($payment->patient->healthInsuranceCategory->code ? ' (' . $payment->patient->healthInsuranceCategory->code . ')' : '')
            : 'غير محدد';

        $cardNo = $payment->insurance_card_no ?: ($payment->patient?->insurance_card_no ?: '—');
        $doctorName = $payment->attending_doctor_name ?: '—';

        return [
            $payment->id,
            $payment->receipt_number ?: ('#' . $payment->id),
            $payment->paid_at ? $payment->paid_at->format('Y-m-d H:i') : ($payment->created_at ? $payment->created_at->format('Y-m-d H:i') : '—'),
            $payment->patient?->name ?? 'غير محدد',
            $patientCategory,
            $cardNo,
            $payment->insurance_type_label,
            $payment->payment_type_name,
            $doctorName,
            $payment->service_details_text,
            number_format($gross),
            number_format($patientPaid),
            number_format($insuranceShare),
            $copayPercent . '%',
            $coveragePercent . '%',
            $payment->cashier?->name ?: 'النظام',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 11
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D6EFD']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ]
            ],
        ];
    }
}
