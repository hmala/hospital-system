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
    protected $shift;

    public function __construct($fromDate = null, $toDate = null, $filterType = null, $search = null, $paymentMethod = null, $insuranceStatus = null, $shift = null)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->filterType = $filterType;
        $this->search = $search;
        $this->paymentMethod = $paymentMethod;
        $this->insuranceStatus = $insuranceStatus;
        $this->shift = $shift;
    }

    public function query()
    {
        return ConsultationRevenue::with(['appointment.patient.user', 'appointment.doctor.user', 'department', 'cashier', 'payment'])
            ->whereHas('appointment.doctor', function ($q) {
                $q->where('type', 'consultant');
            })
            ->when($this->fromDate, fn($query) => $query->whereDate('consultation_revenues.paid_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($query) => $query->whereDate('consultation_revenues.paid_at', '<=', $this->toDate))
            ->when($this->shift === 'morning', fn($query) => $query->whereTime('consultation_revenues.paid_at', '<', '15:00:00'))
            ->when($this->shift === 'evening', fn($query) => $query->whereTime('consultation_revenues.paid_at', '>=', '15:00:00'))
            ->when($this->filterType === 'payment', fn($query) => $query->where('consultation_revenues.movement_type', 'payment'))
            ->when($this->filterType === 'refund', fn($query) => $query->where('consultation_revenues.movement_type', 'refund'))
            ->when($this->filterType === 'appointment_paid', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'paid')))
            ->when($this->filterType === 'appointment_refunded', fn($query) => $query->whereHas('appointment', fn($query) => $query->where('payment_status', 'refunded')))
            ->when($this->paymentMethod, fn($query) => $query->where('consultation_revenues.payment_method', $this->paymentMethod))
            ->when($this->insuranceStatus === 'insurance', function($query) {
                $query->where(function($sub) {
                    $sub->whereHas('appointment', function ($q) {
                        $q->whereIn('insurance_type', ['hi', 'moi'])
                          ->orWhere(function($sq) {
                              $sq->whereNotNull('insurance_type')
                                 ->whereNotIn('insurance_type', ['none', '']);
                          });
                    })->orWhereHas('payment', function ($pq) {
                        $pq->whereIn('insurance_type', ['hi', 'moi'])
                           ->orWhere('insurance_share', '>', 0);
                    });
                });
            })
            ->when($this->insuranceStatus === 'cash', function($query) {
                $query->where(function($sub) {
                    $sub->where(function($sq) {
                        $sq->whereHas('appointment', function ($q) {
                            $q->whereNull('insurance_type')
                              ->orWhere('insurance_type', 'none')
                              ->orWhere('insurance_type', '');
                        })->orWhereDoesntHave('appointment');
                    })->where(function($pq) {
                        $pq->whereHas('payment', function ($p) {
                            $p->where(function($pp) {
                                $pp->whereNull('insurance_type')
                                   ->orWhere('insurance_type', 'none')
                                   ->orWhere('insurance_type', '');
                            })->where(function($pp) {
                                $pp->whereNull('insurance_share')
                                   ->orWhere('insurance_share', '<=', 0);
                            });
                        })->orWhereDoesntHave('payment');
                    });
                });
            })
            ->when($this->search, function($query) {
                $s = $this->search;
                $query->where(function($q) use ($s) {
                    $q->where('consultation_revenues.receipt_number', 'like', "%{$s}%")
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
            'فترة الدوام',
            'المريض',
            'الطبيب',
            'القسم',
            'المدفوع نقداً (المريض)',
            'إجمالي الكشفية',
            'تغطية الضمان',
        ];

        if ($canViewShares) {
            $headings[] = 'حصة الطبيب';
            $headings[] = 'حصة المستشفى';
        }

        $headings = array_merge($headings, [
            'نوع الحركة',
            'طريقة الدفع',
            'رقم الايصال',
            'حالة الضمان',
            'الكاشير',
            'تاريخ الإيراد',
        ]);

        return $headings;
    }

    public function map($row): array
    {
        $canViewShares = auth()->user() && (auth()->user()->can('manage doctor commissions') || auth()->user()->hasRole('admin'));

        $actualPaid = $row->patient_paid_amount;
        $totalTariff = (float)$row->total_amount;
        $insuranceShare = (float)$row->insurance_share_amount;
        $shiftLabel = $row->paid_at ? ($row->paid_at->format('H:i:s') < '15:00:00' ? 'صباحي' : 'مسائي') : '—';

        $data = [
            $row->id,
            optional($row->paid_at)->format('Y-m-d H:i:s'),
            $shiftLabel,
            optional($row->appointment?->patient?->user)->name,
            optional($row->appointment?->doctor?->user)->name,
            optional($row->department)->name,
            $actualPaid,
            $totalTariff,
            $insuranceShare,
        ];

        if ($canViewShares) {
            $data[] = $row->doctor_share;
            $data[] = $row->hospital_share;
        }

        $data = array_merge($data, [
            $row->movement_type === 'payment' ? 'قبض' : 'استرجاع',
            $row->payment_method ?? 'cash',
            $row->receipt_number,
            $row->insurance_type_label,
            optional($row->cashier)->name,
            optional($row->revenue_date)->format('Y-m-d'),
        ]);

        return $data;
    }
}
