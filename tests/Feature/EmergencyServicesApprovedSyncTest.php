<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\EmergencyService;
use Database\Seeders\EmergencyServicesSeeder;

use Illuminate\Foundation\Testing\RefreshDatabase;

class EmergencyServicesApprovedSyncTest extends TestCase
{
    use RefreshDatabase;
    public function test_approved_emergency_services_are_active_and_match_excel()
    {
        $activeCount = EmergencyService::where('is_active', true)->count();
        $this->assertEquals(96, $activeCount, 'Should have exactly 96 active approved emergency services');

        // Check new service 114
        $s114 = EmergencyService::find(114);
        $this->assertNotNull($s114);
        $this->assertEquals(50000.00, (float)$s114->price);
        $this->assertEquals(30000.00, (float)$s114->hi_price);
        $this->assertTrue((bool)$s114->is_hi_active);
        $this->assertTrue((bool)$s114->is_active);

        // Check service 70 (Chest tube)
        $s70 = EmergencyService::find(70);
        $this->assertNotNull($s70);
        $this->assertEquals(500000.00, (float)$s70->price);
        $this->assertEquals(500000.00, (float)$s70->hi_price);
        $this->assertTrue((bool)$s70->is_hi_active);

        // Check legacy services are inactive
        $s112 = EmergencyService::find(112);
        if ($s112) {
            $this->assertFalse((bool)$s112->is_active);
        }

        $s102 = EmergencyService::find(102);
        if ($s102) {
            $this->assertFalse((bool)$s102->is_active);
        }
    }

    public function test_seeder_runs_successfully_and_preserves_96_services()
    {
        $seeder = new EmergencyServicesSeeder();
        $seeder->run();

        $activeCount = EmergencyService::where('is_active', true)->count();
        $this->assertEquals(96, $activeCount);
    }
}
