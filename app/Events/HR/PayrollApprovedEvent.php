<?php

namespace App\Events\HR;

use App\Models\HrPayrollCycle;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PayrollApprovedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payrollCycle;

    /**
     * Create a new event instance.
     */
    public function __construct(HrPayrollCycle $payrollCycle)
    {
        $this->payrollCycle = $payrollCycle;
    }
}
