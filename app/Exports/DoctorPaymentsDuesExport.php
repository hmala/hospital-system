<?php

namespace App\Exports;

use App\Models\DoctorDue;
use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DoctorPaymentsDuesExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected int $doctorId;
    protected $fromDate;
    protected $toDate;

    public function __construct(int $doctorId, $fromDate = null, $toDate = null)
    {
        $this->doctorId = $doctorId;
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
    }

    public function collection(): Collection
    {
        return DoctorDue::with('paidBy')
            ->where('doctor_id', $this->doctorId)
            ->when($this->fromDate, fn($query) => $query->whereDate('created_at', '>=', $this->fromDate))
            ->when($this->toDate, fn($query) => $query->whereDate('created_at', '<=', $this->toDate))
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'رقم السجل',
            'الحالة',
            'تاريخ الإنشاء',
            'تاريخ الدفع',
            'المبلغ',
            'دفعة مسبوقة',
            'منفذ الدفع',
            'ملاحظات',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            ucfirst($row->status),
            optional($row->created_at)->format('Y-m-d H:i:s'),
            optional($row->paid_at)->format('Y-m-d H:i:s'),
            $row->amount,
            $row->payment_id ? 'نعم' : 'لا',
            optional($row->paidBy)->name,
            $row->notes,
        ];
    }
}
