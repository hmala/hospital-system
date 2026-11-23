<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\User;
use Database\Seeders\AccountingDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingVoucherAndChartTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountingDatabaseSeeder::class);

        $this->user = User::factory()->create();
    }

    public function test_chart_of_accounts_index_screen(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.chart.index'));

        $response->assertStatus(200);
        $response->assertSee('MedFinance IFRS');
        $response->assertSee('شجرة الحسابات والدليل الموحد');
    }

    public function test_get_account_details_json_endpoint(): void
    {
        $account = Account::where('code', '134111')->first();
        $this->assertNotNull($account);

        $response = $this->actingAs($this->user)->getJson(route('accounting.chart.details', $account->id));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'account' => [
                'code' => '134111',
                'name' => 'نقدية لدى صندوق المركز بالعملة المحلية',
                'is_postable' => true,
            ],
        ]);
    }

    public function test_suggest_code_for_sub_account(): void
    {
        $parent = Account::where('code', '13411')->first();
        $this->assertNotNull($parent);

        $response = $this->actingAs($this->user)->getJson(route('accounting.chart.suggest-code', ['parent_id' => $parent->id]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'suggested_code', 'level', 'is_postable']);
    }

    public function test_create_sub_account_enforces_rules(): void
    {
        $parent = Account::where('code', '13411')->first();

        $response = $this->actingAs($this->user)->post(route('accounting.chart.store'), [
            'parent_id' => $parent->id,
            'code' => '1341199',
            'name' => 'صندوق فرعي تجريبي',
        ]);

        $response->assertRedirect(route('accounting.chart.index'));
        $this->assertDatabaseHas('acc_accounts', [
            'code' => '1341199',
            'name' => 'صندوق فرعي تجريبي',
            'is_postable' => true,
        ]);
    }

    public function test_cost_centers_index_screen(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.cost-centers.index'));

        $response->assertStatus(200);
        $response->assertSee('شجرة مراكز التكلفة الميدانية للمستشفى');
        $response->assertSee('صالات العمليات والجراحة والولادة');
    }

    public function test_vouchers_index_and_create_screens(): void
    {
        $response = $this->actingAs($this->user)->get(route('accounting.vouchers.index'));
        $response->assertStatus(200);

        $createResponse = $this->actingAs($this->user)->get(route('accounting.vouchers.create', 'receipt'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('سند قبض (Receipt Voucher)');
    }

    public function test_create_voucher_rejects_unbalanced_entries(): void
    {
        $cash = Account::where('code', '134111')->first();
        $rev = Account::where('code', 'LIKE', '43121%')->where('is_postable', true)->first()
            ?? Account::where('account_type', 'revenue')->where('is_postable', true)->first();

        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), [
            'date' => '2026-11-23',
            'description' => 'سند غير متوازن للتجربة',
            'voucher_type' => 'receipt',
            'lines' => [
                [
                    'account_id' => $cash->id,
                    'debit' => 50000,
                    'credit' => 0,
                    'description' => 'قبض',
                ],
                [
                    'account_id' => $rev->id,
                    'debit' => 0,
                    'credit' => 40000, // Unbalanced by 10,000!
                    'description' => 'إيراد',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['lines']);
    }

    public function test_create_voucher_rejects_non_postable_accounts(): void
    {
        // Level 1 account: not postable
        $headerAccount = Account::where('code', '1')->first();
        $cash = Account::where('code', '134111')->first();

        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), [
            'date' => '2026-11-23',
            'description' => 'محاولة تقييد على حساب رئيسي',
            'voucher_type' => 'receipt',
            'lines' => [
                [
                    'account_id' => $cash->id,
                    'debit' => 10000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $headerAccount->id, // Header level 1!
                    'debit' => 0,
                    'credit' => 10000,
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['lines']);
    }

    public function test_create_balanced_voucher_successfully_and_print(): void
    {
        $cash = Account::where('code', '134111')->first();
        $rev = Account::where('code', 'LIKE', '43121%')->where('is_postable', true)->first()
            ?? Account::where('account_type', 'revenue')->where('is_postable', true)->first();
        $costCenter = CostCenter::where('code', '151')->first();

        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), [
            'date' => '2026-11-23',
            'description' => 'قبض إيراد استشارية كاش',
            'voucher_type' => 'receipt',
            'lines' => [
                [
                    'account_id' => $cash->id,
                    'cost_center_id' => null,
                    'debit' => 75000,
                    'credit' => 0,
                    'description' => 'صندوق المركز',
                ],
                [
                    'account_id' => $rev->id,
                    'cost_center_id' => $costCenter ? $costCenter->id : null,
                    'debit' => 0,
                    'credit' => 75000,
                    'description' => 'إيراد كشفية',
                ],
            ],
        ]);

        $entry = JournalEntry::where('description', 'LIKE', '%قبض إيراد استشارية كاش%')->first();
        $this->assertNotNull($entry);
        $response->assertRedirect(route('accounting.vouchers.print', $entry->id));

        // Test Print View
        $printResponse = $this->actingAs($this->user)->get(route('accounting.vouchers.print', $entry->id));
        $printResponse->assertStatus(200);
        $printResponse->assertSee('75,000');
        $printResponse->assertSee('خمسة وسبعون ألف دينار عراقي'); // Tafqeet check!
    }

    public function test_tafqeet_words_endpoint(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('accounting.vouchers.tafqeet', ['amount' => 1250000]));

        $response->assertStatus(200);
        $response->assertJson([
            'words' => 'فقط مليون ومئتان وخمسون ألف دينار عراقي لا غير',
        ]);
    }

    public function test_reverse_voucher_via_http(): void
    {
        $cash = Account::where('code', '134111')->first();
        $rev = Account::where('account_type', 'revenue')->where('is_postable', true)->first();
        $year = FiscalYear::first();

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'date' => '2026-11-23',
            'fiscal_year_id' => $year->id,
            'description' => 'قيد للتراجع',
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $this->user->id,
            'company_id' => 1,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $cash->id,
            'debit' => 15000,
            'credit' => 0,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $rev->id,
            'debit' => 0,
            'credit' => 15000,
        ]);

        $response = $this->actingAs($this->user)->post(route('accounting.vouchers.reverse', $entry->id), [
            'reason' => 'إلغاء بناء على طلب المريض',
        ]);

        $response->assertRedirect(route('accounting.vouchers.index'));
        $this->assertEquals('reversed', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->reversed_by_entry_id);
    }
}
