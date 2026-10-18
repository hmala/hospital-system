<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierReceiptReturnLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_return_to_cashier_button_always_links_to_cashier_index_even_for_consultation_receptionist()
    {
        // Setup role and permissions
        $role = Role::firstOrCreate(['name' => 'consultation_receptionist', 'guard_name' => 'web']);
        $perms = ['view cashier', 'process consultation payments'];
        foreach ($perms as $p) {
            $perm = Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            $role->givePermissionTo($perm);
        }

        $user = User::factory()->create();
        $user->assignRole('consultation_receptionist');

        $payment = Payment::create([
            'receipt_number' => 'REC-TEST-999',
            'amount' => 15000,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'notes' => 'Test payment',
        ]);

        $response = $this->actingAs($user)->get(route('cashier.receipt', $payment->id));

        $response->assertStatus(200);
        $response->assertSee(route('cashier.index'), false);
        $response->assertSee('العودة للكاشير');
        $response->assertDontSee(route('consultant-availability.index'), false);
    }
}
