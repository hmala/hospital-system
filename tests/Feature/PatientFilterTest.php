<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PatientFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $receptionistA;
    protected User $receptionistB;

    protected function setUp(): void
    {
        parent::setUp();

        // إنشاء الصلاحيات والأدوار
        Permission::firstOrCreate(['name' => 'view patients']);
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo('view patients');

        $this->admin = User::factory()->create([
            'name' => 'مدير النظام',
            'email' => 'admin@hospital.test',
        ]);
        $this->admin->assignRole('admin');

        $this->receptionistA = User::factory()->create([
            'name' => 'موظف الاستقبال أحمد',
            'email' => 'ahmed@hospital.test',
        ]);
        $this->receptionistB = User::factory()->create([
            'name' => 'موظف الاستقبال سامر',
            'email' => 'samer@hospital.test',
        ]);
    }

    public function test_filter_by_creator(): void
    {
        $patientUserA = User::factory()->create(['name' => 'مريض أحمد']);
        $patientA = Patient::create([
            'user_id' => $patientUserA->id,
            'created_by' => $this->receptionistA->id,
            'insurance_type' => 'none',
        ]);

        $patientUserB = User::factory()->create(['name' => 'مريض سامر']);
        $patientB = Patient::create([
            'user_id' => $patientUserB->id,
            'created_by' => $this->receptionistB->id,
            'insurance_type' => 'none',
        ]);

        // تصفية بحسب أحمد
        $response = $this->actingAs($this->admin)->getJson(route('patients.index', [
            'created_by' => $this->receptionistA->id,
        ]));

        $response->assertOk();
        $data = $response->json('patients');
        $this->assertCount(1, $data);
        $this->assertEquals('مريض أحمد', $data[0]['user']['name']);
        $this->assertEquals('موظف الاستقبال أحمد', $data[0]['creator_name']);
    }

    public function test_filter_by_insurance_type(): void
    {
        $userHI = User::factory()->create(['name' => 'مريض تأمين صحي']);
        Patient::create([
            'user_id' => $userHI->id,
            'insurance_type' => 'hi',
            'copay_percentage' => 15.00,
        ]);

        $userCash = User::factory()->create(['name' => 'مريض كاش']);
        Patient::create([
            'user_id' => $userCash->id,
            'insurance_type' => 'none',
            'covered_by_insurance' => false,
        ]);

        // تصفية بالضمان الصحي فقط
        $response = $this->actingAs($this->admin)->getJson(route('patients.index', [
            'insurance_type' => 'hi',
        ]));

        $response->assertOk();
        $data = $response->json('patients');
        $this->assertCount(1, $data);
        $this->assertEquals('مريض تأمين صحي', $data[0]['user']['name']);

        // تصفية بالكاش
        $responseCash = $this->actingAs($this->admin)->getJson(route('patients.index', [
            'insurance_type' => 'cash',
        ]));
        $responseCash->assertOk();
        $cashData = $responseCash->json('patients');
        $this->assertCount(1, $cashData);
        $this->assertEquals('مريض كاش', $cashData[0]['user']['name']);
    }

    public function test_filter_by_gender(): void
    {
        $maleUser = User::factory()->create(['name' => 'علي حسن', 'gender' => 'male']);
        Patient::create(['user_id' => $maleUser->id]);

        $femaleUser = User::factory()->create(['name' => 'زينب خالد', 'gender' => 'female']);
        Patient::create(['user_id' => $femaleUser->id]);

        $response = $this->actingAs($this->admin)->getJson(route('patients.index', [
            'gender' => 'female',
        ]));

        $response->assertOk();
        $data = $response->json('patients');
        $this->assertCount(1, $data);
        $this->assertEquals('زينب خالد', $data[0]['user']['name']);
    }

    public function test_filter_by_date(): void
    {
        $userToday = User::factory()->create(['name' => 'مريض اليوم']);
        $pToday = Patient::create(['user_id' => $userToday->id]);
        $pToday->created_at = Carbon::today();
        $pToday->save();

        $userOld = User::factory()->create(['name' => 'مريض قديم']);
        $pOld = Patient::create(['user_id' => $userOld->id]);
        $pOld->created_at = Carbon::now()->subMonths(2);
        $pOld->save();

        $response = $this->actingAs($this->admin)->getJson(route('patients.index', [
            'date_filter' => 'today',
        ]));

        $response->assertOk();
        $data = $response->json('patients');
        $this->assertCount(1, $data);
        $this->assertEquals('مريض اليوم', $data[0]['user']['name']);
    }
}
