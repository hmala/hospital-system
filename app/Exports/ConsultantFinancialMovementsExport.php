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
    protected $search;
    protected $paymentMethod;
    protected $insuranceStatus;

    public function __construct($fromDate = null, $toDate = null, $filterType = null, $search = null, $paymentMethod = null, $insuranceStatus = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->filterType = $filterType;
        $this->search = $search;
        $this->paymentMethod = $paymentMethod;
        $this->insuranceStatus = $insuranceStatus;
    }

    public function query()
    {
        return ConsultationRevenue::with(['appointment.patient.user', 'appointment.doctor.user', 'department', 'cashier'])
            ->whereHas('appointment.doctor', function ($q) {
                $q->where('type', 'consultant');
            })
            ->when($this->fromDate, fn($query) => $query->whereDate('paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($query) => $query->whereDate('paid_at', '<=', $this->toDate))
            ->when($this->filterType === 'payment', fn($query) => $query->where('movement_type', 'payment'))
            ->when($this->filterType === 'refund', fn($query) => $query->where('movement_type', 'refund'))
            ->when($this->filterType === 'appointment_paid', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'paid')))
            ->when($this->filterType === 'appointment_refunded', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'refunded')))
            ->when($this->paymentMethod, fn($query) => $query->where('payment_method', $this->paymentMethod))
            ->when($this->insuranceStatus === 'insurance', fn($query) => $query->whereHas('appointment', fn($query) => $query->whereNotNull('insurance_type')))
            ->when($this->insuranceStatus === 'cash', fn($query) => $query->whereHas('appointment', fn($query) => $query->whereNull('insurance_type')))
            ->when($this->search, function($query) {
                $s = $this->search;
                $query->where(function($q) use ($s) {
                    $q->where('receipt_number', 'like', "%{$s}%")
                      ->orWhereHas('appointment.patient.user', function($uq) use ($s) {
                          $uq->where('name', 'like', "%{$s}%")
                             ->orWhere('phone', 'like', "%{$s}%");
                      });
                });
            });
    }

    public function headings(): array
    {
        $canViewShares = auth()->user() && (auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'));

        $headings = [
            'رقم الحركة',
            'تاريخ الحركة',
            'المريض',
            'الطبيب',
            'القسم',
            'مبلغ الدفعة',
        ];

        if ($canViewShares) {
            $headings[] = 'حصة الطبيب';
            $headings[] = 'حصة المستشفى';
        }

        $headings = array_merge($headings, [
            'نوع الحركة',
            'طريقة الدفع',
            'رقم الايصال',
            'الضمان الصحي',
            'الكاشير',
            'تاريخ الإيراد',
        ]);

        return $headings;
    }

    public function map($row): array
    {
        $canViewShares = auth()->user() && (auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'));

        $data = [
            $row->id,
            optional($row->paid_at)->format('Y-m-d H:i:s'),
            optional($row->appointment?->patient?->user)->name,
            optional($row->appointment?->doctor?->user)->name,
            optional($row->department)->name,
            $row->total_amount,
        ];

        if ($canViewShares) {
            $data[] = $row->doctor_share;
            $data[] = $row->hospital_share;
        }

        $insuranceLabel = $row->appointment?->insurance_type ? 'مشمول: ' . $row->appointment?->insurance_type : 'كاش نقدي';

        $data = array_merge($data, [
            $row->movement_type === 'payment' ? 'قبض' : 'استرجاع',
            $row->payment_method ?? 'cash',
            $row->receipt_number,
            $insuranceLabel,
            optional($row->cashier)->name,
            optional($row->revenue_date)->format('Y-m-d'),
        ]);

        return $data;
    }
}
