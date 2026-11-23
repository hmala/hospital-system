<?php

namespace Database\Seeders;

use App\Models\Accounting\FiscalPeriod;
use App\Models\Accounting\FiscalYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccFiscalYearSeeder extends Seeder
{
    public function run(): void
    {
        $year = FiscalYear::updateOrCreate(
            ['name' => 'السنة المالية 2026', 'company_id' => 1],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-12-31',
                'is_closed' => false,
            ]
        );

        $arabicMonths = [
            1 => 'كانون الثاني',
            2 => 'شباط',
            3 => 'آذار',
            4 => 'نيسان',
            5 => 'أيار',
            6 => 'حزيران',
            7 => 'تموز',
            8 => 'آب',
            9 => 'أيلول',
            10 => 'تشرين الأول',
            11 => 'تشرين الثاني',
            12 => 'كانون الأول',
        ];

        for ($m = 1; $m <= 12; $m++) {
            $startDate = Carbon::create(2026, $m, 1)->startOfMonth()->toDateString();
            $endDate = Carbon::create(2026, $m, 1)->endOfMonth()->toDateString();

            FiscalPeriod::updateOrCreate(
                [
                    'fiscal_year_id' => $year->id,
                    'period_number' => $m,
                ],
                [
                    'name' => "{$arabicMonths[$m]} 2026",
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'is_closed' => false,
                ]
            );
        }
    }
}
