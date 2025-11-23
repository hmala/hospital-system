<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestSurgerySystem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-surgery-system';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Surgery Management System...');

        // Test Surgery model
        $surgeryCount = \App\Models\Surgery::count();
        $this->info("Total surgeries: $surgeryCount");

        // Test LabTest model
        $labTestCount = \App\Models\LabTest::count();
        $this->info("Total lab tests: $labTestCount");

        // Test RadiologyType model
        $radiologyTypeCount = \App\Models\RadiologyType::count();
        $this->info("Total radiology types: $radiologyTypeCount");

        // Test SurgeryLabTest model
        $surgeryLabTestCount = \App\Models\SurgeryLabTest::count();
        $this->info("Total surgery lab tests: $surgeryLabTestCount");

        // Test SurgeryRadiologyTest model
        $surgeryRadiologyTestCount = \App\Models\SurgeryRadiologyTest::count();
        $this->info("Total surgery radiology tests: $surgeryRadiologyTestCount");

        // Test relationships
        $surgery = \App\Models\Surgery::first();
        if ($surgery) {
            $this->info("First surgery: {$surgery->surgery_type}");
            $this->info("Lab tests for this surgery: " . $surgery->labTests()->count());
            $this->info("Radiology tests for this surgery: " . $surgery->radiologyTests()->count());
        }

        $this->info('Test completed successfully!');
    }
}
