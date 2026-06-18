<?php

namespace App\Exports;

use App\Models\ConsultationRevenue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ConsultantFinancialMovementsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $fromDate;
    protected $toDate;
    protected $filterType;

    public function __construct($fromDate = null, $toDate = null, $filterType = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->filterType = $filterType;
    }

    public function query()
    {
        return ConsultationRevenue::with(['appointment.patient.user', 'appointment.doctor.user', 'department', 'cashier'])
            ->when($this->fromDate, fn($query) => $query->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($query) => $query->whereDate('paid_at', '<=', $this->toDate))
            ->when($this->filterType === 'payment', fn($query) => $query->where('movement_type', 'payment'))
            ->when($this->filterType === 'refund', fn($query) => $query->where('movement_type', 'refund'))
            ->when($this->filterType === 'appointment_paid', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'paid')))
            ->when($this->filterType === 'appointment_refunded', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'refunded')));
    }

    public function headings(): array
    {
        return [
            'رقم الحركة',
            'تاريخ الحركة',
            'المريض',
            'الطبيب',
            'القسم',
            'مبلغ الدفعة',
            'حصة الطبيب',
            'حصة المستشفى',
            'نوع الحركة',
            'طريقة الدفع',
            'رقم الايصال',
            'الكاشير',
            'تاريخ الإيراد',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            optional($row->paid_at)->format('Y-m-d H:i:s'),
            optional($row->appointment?->patient?->user)->name,
            optional($row->appointment?->doctor?->user)->name,
            optional($row->department)->name,
            $row->total_amount,
            $row->doctor_share,
            $row->hospital_share,
            $row->movement_type,
            $row->payment_method,
            $row->receipt_number,
            optional($row->cashier)->name,
            optional($row->revenue_date)->format('Y-m-d'),
        ];
    }
}
