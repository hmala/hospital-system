<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrEmployeeAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'hr_employee_id',
        'hr_action_setting_id',
        'hr_action_batch_id',
        'action_date',
        'reason',
        'applied_effect_type',
        'applied_effect_value',
        'financial_amount',
        'status',
        'cancellable_until',
        'created_by',
        'hr_payroll_cycle_id',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason',
        'cancel_requested_by',
        'cancel_requested_at',
        'cancel_request_reason',
        'cancel_approved_by',
        'cancel_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'action_date' => 'date',
            'cancellable_until' => 'datetime',
            'cancelled_at' => 'datetime',
            'cancel_requested_at' => 'datetime',
            'cancel_approved_at' => 'datetime',
            'applied_effect_value' => 'decimal:2',
            'financial_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $action) {
            if (! $action->cancellable_until) {
                $action->cancellable_until = now()->addHours(48);
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id');
    }

    public function actionSetting()
    {
        return $this->belongsTo(HrActionSetting::class, 'hr_action_setting_id');
    }

    public function batch()
    {
        return $this->belongsTo(HrActionBatch::class, 'hr_action_batch_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payrollCycle()
    {
        return $this->belongsTo(HrPayrollCycle::class, 'hr_payroll_cycle_id');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function cancelRequester()
    {
        return $this->belongsTo(User::class, 'cancel_requested_by');
    }

    public function cancelApprover()
    {
        return $this->belongsTo(User::class, 'cancel_approved_by');
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isWithin48Hours(): bool
    {
        $deadline = $this->cancellable_until ?? $this->created_at?->copy()->addHours(48);

        return $deadline ? now()->lte($deadline) : false;
    }

    public function hasPendingCancellationRequest(): bool
    {
        return ! is_null($this->cancel_requested_at) && ! $this->isCancelled();
    }
}
