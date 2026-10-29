<?php

namespace Tests\Feature;

use App\Models\Emergency;
use App\Models\EmergencyLabRequest;
use App\Models\Hospital;
use App\Models\LabTest;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CbcTestNameStandardizationTest extends TestCase
{
    use RefreshDatabase;

    protected $hospital;

    protected $labStaff;

    protected $patient;

    protected $emergency;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'lab_staff']);

        $this->hospital = Hospital::create([
            'name' => 'مستشفى الكفاءات الأهلي',
            'owner_name' => 'الإدارة',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'LIC-CBC-'.rand(100, 999),
        ]);

        $user = User::factory()->create();
        $this->patient = Patient::create([
            'user_id' => $user->id,
            'hospital_id' => $this->hospital->id,
            'name' => 'محمد خلف',
            'gender' => 'male',
            'age' => 45,
            'phone' => '07712345678',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'emergency_type' => 'cardiac',
            'status' => 'in_progress',
            'priority' => 'critical',
            'symptoms' => 'ألم حاد في الصدر',
            'admission_time' => now(),
            'notes' => 'طوارئ',
        ]);

        $labUser = User::factory()->create();
        $labUser->assignRole('lab_staff');
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view lab tests', 'guard_name' => 'web']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'view emergencies', 'guard_name' => 'web']);
        $labUser->givePermissionTo(['view lab tests', 'view emergencies']);
        $this->labStaff = $labUser;
    }

    public function test_cbc_test_name_displays_as_cbc_5_part_diff_in_print_even_with_legacy_keys()
    {
        $cbcTest = LabTest::create([
            'name' => 'CBC (5-Part Diff)',
            'code' => 'cbc_3DIFF',
            'price' => 20000,
            'is_active' => true,
        ]);

        $emergencyLab = EmergencyLabRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
            'priority' => 'urgent',
            'details' => [
                'test_attachments' => [
                    'Complete blood count & Differential' => 'emergency_lab_results/dummy_cbc.pdf',
                ],
                'test_results' => [
                    'Complete blood count & Differential' => [
                        'value' => '',
                        'test_name' => 'Complete blood count & Differential',
                    ],
                ],
            ],
            'requested_at' => now(),
            'completed_at' => now(),
        ]);

        $emergencyLab->labTests()->attach($cbcTest->id);

        $response = $this->actingAs($this->labStaff)
            ->get(route('staff.emergency-lab.print', $emergencyLab));

        $response->assertStatus(200);
        $response->assertSee('CBC (5-Part Diff)');
        $response->assertDontSee('Complete blood count &amp; Differential', false);
        $response->assertSee('مرفق: CBC (5-Part Diff)');
    }
}
