<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HealthInsuranceClaimsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $data;

    protected string $viewMode;

    public function __construct($data, string $viewMode = 'receipt')
    {
        $this->data = $data;
        $this->viewMode = $viewMode;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        if ($this->viewMode === 'patient' || $this->viewMode === 'patient_grouped') {
            return [
                '#',
                'اسم المريض',
                'فئة الضمان',
                'رقم بطاقة / باج الضمان',
                'رقم الهاتف / الملف',
                'عدد الخدمات والوصولات',
                'الأقسام والخدمات',
                'الأطباء المعالجون',
                'السعر الإجمالي (د.ع)',
                'تحمل المريض نقداً (د.ع)',
                'مطالبة الضمان الصحي (د.ع)',
                'نسبة تغطية الضمان',
                'تاريخ آخر حركة',
            ];
        }

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

    public function map($row): array
    {
        if (($this->viewMode === 'patient' || $this->viewMode === 'patient_grouped') && is_array($row)) {
            $patient = $row['patient'] ?? null;
            $gross = (float) ($row['total_gross'] ?? 0);
            $patientPaid = (float) ($row['total_patient_paid'] ?? 0);
            $insuranceShare = (float) ($row['total_insurance_share'] ?? 0);
            $coveragePercent = $gross > 0 ? round(($insuranceShare / $gross) * 100, 1) : 0;

            $catName = $patient?->healthInsuranceCategory?->name
                ? $patient->healthInsuranceCategory->name.($patient->healthInsuranceCategory->code ? ' ('.$patient->healthInsuranceCategory->code.')' : '')
                : 'غير محدد';
            $cardNo = $patient?->insurance_card_no ?: '—';
            $phone = $patient?->user?->phone ?: ($patient?->file_number ?: '—');

            return [
                $row['patient_id'] ?? '—',
                $patient?->name ?? 'غير محدد',
                $catName,
                $cardNo,
                $phone,
                $row['receipts_count'] ?? 0,
                $row['departments'] ?? '—',
                $row['doctors'] ?? '—',
                number_format($gross),
                number_format($patientPaid),
                number_format($insuranceShare),
                $coveragePercent.'%',
                ! empty($row['latest_paid_at']) ? Carbon::parse($row['latest_paid_at'])->format('Y-m-d H:i') : '—',
            ];
        }

        // Receipt mapping (Payment model)
        $payment = $row;
        $gross = (float) ($payment->amount + $payment->insurance_share);
        $patientPaid = (float) ($payment->amount ?? 0);
        $insuranceShare = (float) ($payment->insurance_share ?? 0);

        $copayPercent = $payment->copay_percentage ?? ($gross > 0 ? round(($patientPaid / $gross) * 100, 1) : 0);
        $coveragePercent = $gross > 0 ? round(($insuranceShare / $gross) * 100, 1) : (100 - $copayPercent);

        $patientCategory = $payment->patient?->healthInsuranceCategory?->name
            ? $payment->patient->healthInsuranceCategory->name.($payment->patient->healthInsuranceCategory->code ? ' ('.$payment->patient->healthInsuranceCategory->code.')' : '')
            : 'غير محدد';

        $cardNo = $payment->insurance_card_no ?: ($payment->patient?->insurance_card_no ?: '—');
        $doctorName = $payment->attending_doctor_name ?: '—';

        return [
            $payment->id,
            $payment->receipt_number ?: ('#'.$payment->id),
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
            $copayPercent.'%',
            $coveragePercent.'%',
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
                    'size' => 11,
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '0D6EFD'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }
}
