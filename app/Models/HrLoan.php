<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HrLoan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'hr_employee_id', 'total_amount', 'monthly_installment',
        'paid_amount', 'remaining_amount', 'start_date',
        'status', 'reason', 'notes', 'created_by',
    ];

    protected $casts = [
        'total_amount'        => 'decimal:2',
        'monthly_installment' => 'decimal:2',
        'paid_amount'         => 'decimal:2',
        'remaining_amount'    => 'decimal:2',
        'start_date'          => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * هل السلفة مكتملة السداد؟
     */
    public function isFullyPaid(): bool
    {
        return $this->remaining_amount <= 0;
    }

    /**
     * تسجيل دفع قسط والتحديث التلقائي للأرقام
     */
    public function payInstallment(float $amount): void
    {
        $this->paid_amount      += $amount;
        $this->remaining_amount -= $amount;

        if ($this->remaining_amount <= 0) {
            $this->remaining_amount = 0;
            $this->status = 'completed';
        }
        $this->save();
    }
}
