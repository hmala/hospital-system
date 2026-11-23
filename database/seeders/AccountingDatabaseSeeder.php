<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class AccountingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccCurrencySeeder::class,
            AccFiscalYearSeeder::class,
            AccCostCenterSeeder::class,
            AccChartOfAccountsSeeder::class,
        ]);
    }
}
