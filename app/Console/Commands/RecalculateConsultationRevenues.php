<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Observers\PaymentObserver;

class RecalculateConsultationRevenues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'consultation:recalculate-shares';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'إعادة حساب حصص الأطباء والمستشفى لجميع دفات المواعيد الاستشارية السابقة';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('جاري إعادة حساب الحركات المالية وحصص الأطباء...');

        $payments = Payment::whereNotNull('appointment_id')
            ->whereNotNull('paid_at')
            ->get();

        $observer = new PaymentObserver();
        $count = 0;

        foreach ($payments as $payment) {
            // Re-trigger observer logic for existing payments
            $observer->created($payment);
            $count++;
        }

        $this->info("تمت إعادة حساب {$count} حركة مالية بنجاح!");
        return Command::SUCCESS;
    }
}
