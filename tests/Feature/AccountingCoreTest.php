<?php

namespace Tests\Feature;

use App\Models\Accounting\Account;
use App\Models\Accounting\CostCenter;
use App\Models\Accounting\Currency;
use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\FiscalYear;
use App\Models\Accounting\JournalEntry;
use App\Models\Accounting\JournalEntryLine;
use App\Models\User;
use Database\Seeders\AccountingDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingCoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccountingDatabaseSeeder::class);
    }

    public function test_currency_and_official_exchange_rate(): void
    {
        $iqd = Currency::where('code', 'IQD')->first();
        $this->assertNotNull($iqd);
        $this->assertTrue($iqd->is_default);
        $this->assertEquals(1.0, (float) $iqd->exchange_rate);

        $usd = Currency::where('code', 'USD')->first();
        $this->assertNotNull($usd);
        $this->assertTrue($usd->is_official);
        $this->assertEquals(1320.0, (float) $usd->exchange_rate);

        $this->assertEquals(1320.0, Currency::getRate('USD'));
    }

    public function test_account_hierarchy_and_posting_level_rules(): void
    {
        // Level 1 account: '1' الأصول (Header, not postable)
        $root = Account::where('code', '1')->first();
        $this->assertNotNull($root);
        $this->assertFalse($root->is_postable);
        $this->assertEquals(1, $root->level);
        $this->assertEquals('asset', $root->account_type);

        // Find a postable level 5 or 6 account
        $cashCenter = Account::where('code', '134111')->first(); // نقدية لدى صندوق المركز بالعملة المحلية
        $this->assertNotNull($cashCenter);
        $this->assertTrue($cashCenter->is_postable);
        $this->assertGreaterThanOrEqual(5, $cashCenter->level);

        // Parent should be '13411'
        $parent = $cashCenter->parent;
        $this->assertNotNull($parent);
        $this->assertEquals('13411', $parent->code);
    }

    public function test_cost_center_tree_structure(): void
    {
        $orCluster = CostCenter::where('code', '11')->first();
        $this->assertNotNull($orCluster);
        $this->assertEquals('صالات العمليات والجراحة والولادة', $orCluster->name);

        $children = $orCluster->children;
        $this->assertGreaterThanOrEqual(2, $children->count());
    }

    public function test_balanced_journal_entry_creation_and_reversal(): void
    {
        $user = User::factory()->create();
        $year = FiscalYear::where('name', 'السنة المالية 2026')->first();
        $period = FiscalPeriod::where('fiscal_year_id', $year->id)->where('period_number', 11)->first();

        // 1. Postable accounts
        $cashAccount = Account::where('code', '134111')->first(); // مدين
        $revenueAccount = Account::where('code', '431211')->first() // إيراد استشارية
            ?? Account::where('account_type', 'revenue')->where('is_postable', true)->first();

        $costCenter = CostCenter::where('code', '151')->first(); // استشارية الباطنية

        $entryNumber = JournalEntry::generateEntryNumber();

        $entry = JournalEntry::create([
            'entry_number' => $entryNumber,
            'date' => '2026-11-23',
            'fiscal_year_id' => $year->id,
            'fiscal_period_id' => $period ? $period->id : null,
            'reference_type' => 'App\Models\Payment',
            'reference_id' => 99999,
            'description' => 'قيد تجريبي لكشفية مريض في الاستشارية',
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $user->id,
            'company_id' => 1,
        ]);

        // Debit cash
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $cashAccount->id,
            'cost_center_id' => null,
            'debit' => 25000.00,
            'credit' => 0.00,
            'description' => 'قبض كشفية نقدية',
        ]);

        // Credit revenue
        JournalEntryLine::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $revenueAccount->id,
            'cost_center_id' => $costCenter ? $costCenter->id : null,
            'debit' => 0.00,
            'credit' => 25000.00,
            'description' => 'إيراد كشفية استشارية الباطنية',
        ]);

        $this->assertTrue($entry->isBalanced());
        $this->assertEquals(25000.00, $entry->total_debit);
        $this->assertEquals(25000.00, $entry->total_credit);

        // Test reversal
        $reversal = $entry->reverse($user->id, 'خطأ في تصنيف الكشفية');
        $this->assertNotNull($reversal);
        $this->assertEquals('reversed', $entry->fresh()->status);
        $this->assertEquals($reversal->id, $entry->fresh()->reversed_by_entry_id);
        $this->assertTrue($reversal->isBalanced());
        $this->assertEquals(25000.00, $reversal->total_debit);
    }
}
