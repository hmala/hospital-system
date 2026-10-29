<?php

namespace Tests\Feature;

use App\Models\Emergency;
use App\Models\EmergencyRadiologyRequest;
use App\Models\Patient;
use App\Models\RadiologyType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmergencyRadiologyPrintTest extends TestCase
{
    use RefreshDatabase;

    protected User $staff;

    protected Patient $patient;

    protected Emergency $emergency;

    protected RadiologyType $radType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\HospitalSeeder::class);
        $this->seed(\Database\Seeders\DepartmentSeeder::class);

        Permission::firstOrCreate(['name' => 'view radiology', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'view emergencies', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'radiology_staff', 'guard_name' => 'web']);

        $this->staff = User::factory()->create(['name' => 'موظف أشعة الطوارئ']);
        $this->staff->assignRole('radiology_staff');
        $this->staff->givePermissionTo(['view radiology', 'view emergencies']);

        $patientUser = User::factory()->create(['name' => 'مريض أشعة طوارئ']);
        $this->patient = Patient::create([
            'user_id' => $patientUser->id,
            'gender' => 'male',
            'birth_date' => '1995-01-01',
            'phone' => '07712345678',
        ]);

        $this->emergency = Emergency::create([
            'patient_id' => $this->patient->id,
            'emergency_type' => 'trauma',
            'status' => 'in_progress',
            'priority' => 'critical',
            'symptoms' => 'اشتباه كسر بالساعد',
            'admission_time' => now(),
        ]);

        $this->radType = RadiologyType::create([
            'name' => 'X-Ray Forearm AP/LAT',
            'code' => 'XRAY_FOREARM',
            'base_price' => 15000,
            'is_active' => true,
        ]);
    }

    public function test_emergency_radiology_print_renders_successfully_without_attachments(): void
    {
        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
            'priority' => 'urgent',
            'notes' => 'فحص كسر الساعد',
            'requested_at' => now(),
            'completed_at' => now(),
        ]);

        $radReq->radiologyTypes()->attach($this->radType->id, [
            'result' => 'No visible fracture. Normal soft tissues.',
            'image_path' => null,
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('staff.emergency-radiology.print', $radReq));

        $response->assertStatus(200);
        $response->assertSee('تقرير الأشعة التشخيصية');
        $response->assertSee('مريض أشعة طوارئ');
        $response->assertSee('X-Ray Forearm AP/LAT');
        $response->assertSee('No visible fracture. Normal soft tissues.');
        $response->assertDontSee('لم يتم العثور على مرفقات في العرض');
    }

    public function test_emergency_radiology_print_renders_successfully_with_attachments(): void
    {
        $radReq = EmergencyRadiologyRequest::create([
            'emergency_id' => $this->emergency->id,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
            'priority' => 'critical',
            'notes' => 'كسر مركب',
            'requested_at' => now(),
            'completed_at' => now(),
        ]);

        $radReq->radiologyTypes()->attach($this->radType->id, [
            'result' => 'Displaced fracture of distal radius.',
            'image_path' => 'emergency_radiology/sample_scan.jpg',
        ]);

        $response = $this->actingAs($this->staff)
            ->get(route('staff.emergency-radiology.print', $radReq));

        $response->assertStatus(200);
        $response->assertSee('تقرير الأشعة التشخيصية');
        $response->assertSee('مريض أشعة طوارئ');
        $response->assertSee('Displaced fracture of distal radius.');
        $response->assertSee('صور الأشعة التشخيصية');
        $response->assertSee(route('staff.emergency-radiology.attachment', ['emergencyRadiology' => $radReq, 'typeId' => $this->radType->id]), false);
    }
}
