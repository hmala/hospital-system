<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Surgery;

class BackfillSurgeryFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backfill:surgery-fees {--chunk=100 : Number of records to process per chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the surgery_fee column on existing surgeries using the current operation fee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of surgery fees...');

        $chunkSize = (int) $this->option('chunk');
        $count = 0;

        Surgery::where(function($q) {
            $q->whereNull('surgery_fee')->orWhere('surgery_fee', 0);
        })->chunk($chunkSize, function($surgeries) use (&$count) {
            foreach ($surgeries as $s) {
                $newFee = $s->surgicalOperation->fee ?? 0;
                $s->surgery_fee = $newFee;
                $s->save();
                $count++;
            }
            $this->info("Processed {$surgeries->count()} surgeries...");
        });

        $this->info("Done. Total updated records: {$count}");
        return 0;
    }
}
