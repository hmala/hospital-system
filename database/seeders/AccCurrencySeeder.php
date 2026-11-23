<?php

namespace Database\Seeders;

use App\Models\Accounting\Currency;
use Illuminate\Database\Seeder;

class AccCurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::updateOrCreate(
            ['code' => 'IQD'],
            [
                'name' => 'دينار عراقي',
                'symbol' => 'د.ع',
                'exchange_rate' => 1.0000,
                'is_default' => true,
                'is_official' => true,
                'is_active' => true,
            ]
        );

        Currency::updateOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'دولار أمريكي',
                'symbol' => '$',
                'exchange_rate' => 1320.0000,
                'is_default' => false,
                'is_official' => true,
                'is_active' => true,
            ]
        );
    }
}
