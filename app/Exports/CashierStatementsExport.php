<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashierStatementsExport implements ShouldAutoSize, WithMultipleSheets
{
    protected $revenues;

    protected $groupedRevenues;

    protected $monthlyDoctorSummary;

    protected $monthNames;

    public function __construct($revenues, $groupedRevenues, $monthlyDoctorSummary, $monthNames)
    {
        $this->revenues = $revenues;
        $this->groupedRevenues = $groupedRevenues;
        $this->monthlyDoctorSummary = $monthlyDoctorSummary;
        $this->monthNames = $monthNames;
    }

    public function sheets(): array
    {
        return [
            new CashierStatementsDetailSheet($this->revenues),
            new CashierStatementsDoctorSummarySheet($this->groupedRevenues),
            new CashierStatementsMonthlySummarySheet($this->monthlyDoctorSummary, $this->monthNames),
        ];
    }
}

class CashierStatementsDetailSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows->map(function ($row) {
            return [
                $row->id,
                $row->appointment?->appointment_date ? \Carbon\Carbon::parse($row->appointment->appointment_date)->format('Y-m-d') : '-',
                $row->examination_count ?? 1,
                $row->serviceType?->name ?? '-',
                $row->doctor?->user?->name ?? '-',
                $row->appointment?->patient?->user?->name ?? $row->patient?->user?->name ?? '-',
                $row->total_amount,
                $row->doctor_share,
                $row->hospital_share,
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'رقم الحركة',
            'تاريخ الموعد',
            'عدد الفحوصات',
            'نوع الخدمة',
            'اسم الطبيب',
            'اسم المريض',
            'المبلغ المسدد',
            'حصة الطبيب',
            'ربح المستشفى',
        ];
    }

    public function title(): string
    {
        return 'التفاصيل';
    }
}

class CashierStatementsDoctorSummarySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows->map(function ($row) {
            return [
                $row->doctor?->user?->name ?? '-',
                $row->first_appointment_date ? \Carbon\Carbon::parse($row->first_appointment_date)->format('Y-m-d') : '-',
                $row->examination_count,
                $row->total_amount,
                $row->doctor_share,
                $row->hospital_share,
            ];
        })->toArray();
    }

    public function headings(): array
    {
        return [
            'اسم الطبيب',
            'أول موعد',
            'عدد الفحوصات',
            'المبلغ المسدد',
            'حصة الطبيب',
            'ربح المستشفى',
        ];
    }

    public function title(): string
    {
        return 'ملخص حسب الطبيب';
    }
}

class CashierStatementsMonthlySummarySheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    protected $rows;

    protected $monthNames;

    public function __construct($rows, $monthNames)
    {
        $this->rows = $rows;
        $this->monthNames = $monthNames;
    }

    public function array(): array
    {
        return $this->rows->map(function ($row) {
            $data = [$row->doctor?->user?->name ?? '-'];

            foreach ($this->monthNames as $monthNumber => $monthName) {
                $data[] = $row->months[$monthNumber] ?? 0;
            }

            $data[] = $row->total;
            $data[] = $row->percent_change;

            return $data;
        })->toArray();
    }

    public function headings(): array
    {
        $headings = ['اسم الطبيب'];

        foreach ($this->monthNames as $monthName) {
            $headings[] = $monthName;
        }

        $headings[] = 'الإجمالي';
        $headings[] = 'النسبة %';

        return $headings;
    }

    public function title(): string
    {
        return 'الملخص الشهري';
    }
}
