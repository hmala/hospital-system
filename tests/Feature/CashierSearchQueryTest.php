<?php

namespace Tests\Feature;

use App\Models\Hospital;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CashierSearchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_search_query_with_text_executes_without_sql_errors(): void
    {
        Permission::firstOrCreate(['name' => 'view cashier']);
        $role = Role::firstOrCreate(['name' => 'cashier']);
        $role->givePermissionTo('view cashier');

        $user = User::factory()->create();
        $user->assignRole('cashier');

        Hospital::create([
            'name' => 'مستشفى الشفاء',
            'owner_name' => 'د. أحمد',
            'phone' => '07700000000',
            'address' => 'بغداد',
            'license_number' => 'HOSP-001',
        ]);

        $patientUser = User::factory()->create(['name' => 'حسين علي']);
        Patient::create([
            'user_id' => $patientUser->id,
            'national_id' => '1234567890',
        ]);

        $response = $this->actingAs($user)->get(route('cashier.index', [
            'date_filter' => 'today',
            'search' => 'حسين',
        ]));

        $response->assertStatus(200);
        $response->assertSee('حسين');
    }
}
