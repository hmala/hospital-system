<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Accounting\AuditLog;
use App\Models\Accounting\CostCenter;
use App\Models\Accounting\Currency;
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
        $response->assertSee('وضع النافذة الكاملة (المخطط الشجري)');
        $response->assertSee('+ إضافة فرع');
        $response->assertSee('الحساب الأصل');
        $response->assertDontSee('إضافة ابن');
        $response->assertDontSee('الحساب الأب');
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
                'can_add_branch' => true,
                'has_transactions' => false,
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

    public function test_create_branch_converts_postable_origin_to_non_postable_and_blocks_direct_voucher(): void
    {
        $origin = Account::where('code', '134111')->first();
        $this->assertNotNull($origin);
        $this->assertTrue($origin->is_postable);

        $response = $this->actingAs($this->user)->post(route('accounting.chart.store'), [
            'parent_id' => $origin->id,
            'code' => '1341111',
            'name' => 'صندوق المركز الصباحي',
        ]);

        $response->assertRedirect(route('accounting.chart.index'));

        $origin->refresh();
        $this->assertFalse($origin->is_postable);
        $this->assertFalse($origin->canReceiveEntries());

        $this->assertDatabaseHas('acc_accounts', [
            'code' => '1341111',
            'name' => 'صندوق المركز الصباحي',
            'parent_id' => $origin->id,
            'is_postable' => true,
        ]);

        // Direct voucher posting on the origin account must now be rejected
        $rev = Account::where('account_type', 'revenue')->where('is_postable', true)->first();
        $voucherResponse = $this->actingAs($this->user)->post(route('accounting.vouchers.store'), [
            'date' => '2026-11-25',
            'description' => 'محاولة قيد على حساب أصبح أصلاً',
            'voucher_type' => 'receipt',
            'lines' => [
                ['account_id' => $origin->id, 'debit' => 10000, 'credit' => 0],
                ['account_id' => $rev->id, 'debit' => 0, 'credit' => 10000],
            ],
        ]);

        $voucherResponse->assertSessionHasErrors(['lines']);
    }

    public function test_cannot_add_branch_under_account_with_existing_journal_lines(): void
    {
        $cash = Account::where('code', '134111')->first();
        $rev = Account::where('account_type', 'revenue')->where('is_postable', true)->first();
        $year = FiscalYear::first();

        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'date' => '2026-11-25',
            'fiscal_year_id' => $year?->id,
            'description' => 'قيد سابق على الصندوق',
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $this->user->id,
            'company_id' => 1,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $cash->id,
            'debit' => 5000,
            'credit' => 0,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $rev->id,
            'debit' => 0,
            'credit' => 5000,
        ]);

        $detailsResponse = $this->actingAs($this->user)->getJson(route('accounting.chart.details', $cash->id));
        $detailsResponse->assertStatus(200)->assertJson([
            'account' => [
                'has_transactions' => true,
                'can_add_branch' => false,
            ],
        ]);

        $storeResponse = $this->actingAs($this->user)->post(route('accounting.chart.store'), [
            'parent_id' => $cash->id,
            'code' => '1341119',
            'name' => 'فرع مرفوض',
        ]);

        $storeResponse->assertSessionHasErrors(['parent_id']);
        $this->assertDatabaseMissing('acc_accounts', ['code' => '1341119']);
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

    public function test_accounting_settings_updates_currency_rate_archives_audit_log_and_reflects_on_chart(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $this->assertNotNull($usd);

        $page = $this->actingAs($this->user)->get(route('accounting.settings.index'));
        $page->assertStatus(200);
        $page->assertSee('الإعدادات');
        $page->assertSee('أرشيف تغييرات العملة وأسعار الصرف والتدقيق');

        $updateResponse = $this->actingAs($this->user)->put(route('accounting.settings.currencies.update', $usd->id), [
            'exchange_rate' => 1450,
            'is_official' => 1,
            'is_active' => 1,
            'reason' => 'تحديث سعر الصرف وفق نشرة البنك المركزي',
        ]);

        $updateResponse->assertRedirect(route('accounting.settings.index'));
        $this->assertEquals(1450.0, (float) $usd->fresh()->exchange_rate);

        $audit = AuditLog::where('auditable_type', Currency::class)
            ->where('auditable_id', $usd->id)
            ->where('action', 'currency_updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertEquals($this->user->id, $audit->user_id);
        $this->assertEquals(1320.0, (float) $audit->old_values['exchange_rate']);
        $this->assertEquals(1450.0, (float) $audit->new_values['exchange_rate']);
        $this->assertEquals(130.0, (float) $audit->new_values['rate_diff']);
        $this->assertEquals('تحديث سعر الصرف وفق نشرة البنك المركزي', $audit->reason);

        // Chart KPI card must now display the updated rate 1,450 dynamically
        $chartResponse = $this->actingAs($this->user)->get(route('accounting.chart.index'));
        $chartResponse->assertStatus(200);
        $chartResponse->assertSee('1,450');

        // Settings page must display the archived log entry with user name and reason
        $settingsAfter = $this->actingAs($this->user)->get(route('accounting.settings.index'));
        $settingsAfter->assertSee($this->user->name);
        $settingsAfter->assertSee('تحديث سعر الصرف وفق نشرة البنك المركزي');
    }

    public function test_fx_revaluation_calculates_and_posts_exchange_difference_entry(): void
    {
        $usd = Currency::where('code', 'USD')->first();
        $fxCash = Account::where('code', '134112')->first(); // نقدية لدى صندوق المركز بالعملة الأجنبية
        $rev = Account::where('account_type', 'revenue')->where('is_postable', true)->first();
        $year = FiscalYear::first();

        // Record $100 receipt at 1,320 IQD = 132,000 IQD
        $entry = JournalEntry::create([
            'entry_number' => JournalEntry::generateEntryNumber(),
            'date' => now()->toDateString(),
            'fiscal_year_id' => $year?->id,
            'description' => 'قبض 100 دولار بسعر 1320',
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $this->user->id,
            'company_id' => 1,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $fxCash->id,
            'debit' => 132000,
            'credit' => 0,
            'currency_id' => $usd->id,
            'exchange_rate' => 1320,
            'currency_amount' => 100,
        ]);
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $rev->id,
            'debit' => 0,
            'credit' => 132000,
            'currency_id' => $usd->id,
            'exchange_rate' => 1320,
            'currency_amount' => 100,
        ]);

        // Change USD rate to 1,400 -> $100 becomes 140,000 IQD -> +8,000 IQD FX gain
        $this->actingAs($this->user)->put(route('accounting.settings.currencies.update', $usd->id), [
            'exchange_rate' => 1400,
            'is_official' => 1,
            'is_active' => 1,
            'reason' => 'ارتفاع سعر الصرف',
        ])->assertRedirect(route('accounting.settings.index'));

        $settingsView = $this->actingAs($this->user)->get(route('accounting.settings.index'));
        $settingsView->assertStatus(200);
        $settingsView->assertSee('8,000.00');
        $settingsView->assertSee('ربح صرف');

        // Post FX revaluation entry
        $revalResponse = $this->actingAs($this->user)->post(route('accounting.settings.currencies.revalue', $usd->id));
        $revalResponse->assertRedirect(route('accounting.settings.index'));

        $revalEntry = JournalEntry::where('reference_type', Currency::class)
            ->where('reference_id', $usd->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($revalEntry);
        $this->assertTrue($revalEntry->isBalanced());
        $this->assertEquals(8000.0, $revalEntry->total_debit);

        $gainAccount = Account::where('code', '43551')->first();
        $this->assertNotNull($gainAccount);
        $this->assertDatabaseHas('acc_journal_entry_lines', [
            'journal_entry_id' => $revalEntry->id,
            'account_id' => $fxCash->id,
            'debit' => 8000.00,
            'credit' => 0.00,
        ]);
        $this->assertDatabaseHas('acc_journal_entry_lines', [
            'journal_entry_id' => $revalEntry->id,
            'account_id' => $gainAccount->id,
            'debit' => 0.00,
            'credit' => 8000.00,
        ]);
    }
}
