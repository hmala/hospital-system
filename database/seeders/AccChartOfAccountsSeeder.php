<?php

namespace Database\Seeders;

use App\Models\Accounting\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AccChartOfAccountsSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = database_path('seeders/data/iraqi_chart_of_accounts.json');

        if (! File::exists($jsonPath)) {
            $this->command->error("الملف غير موجود: {$jsonPath}");

            return;
        }

        $accounts = json_decode(File::get($jsonPath), true);

        // Sort by code length to ensure parents are inserted before children
        usort($accounts, function ($a, $b) {
            $lenA = strlen($a['code']);
            $lenB = strlen($b['code']);
            if ($lenA === $lenB) {
                return strcmp($a['code'], $b['code']);
            }

            return $lenA <=> $lenB;
        });

        $codeToId = [];

        // Preload any existing accounts
        $existing = DB::table('acc_accounts')->where('company_id', 1)->pluck('id', 'code')->toArray();
        $codeToId = $existing;

        DB::beginTransaction();

        try {
            foreach ($accounts as $acc) {
                $code = (string) $acc['code'];
                $parentId = null;

                if (! empty($acc['parent_code']) && isset($codeToId[$acc['parent_code']])) {
                    $parentId = $codeToId[$acc['parent_code']];
                }

                $record = Account::updateOrCreate(
                    [
                        'code' => $code,
                        'company_id' => 1,
                    ],
                    [
                        'name' => $acc['name'],
                        'parent_id' => $parentId,
                        'level' => $acc['level'],
                        'account_type' => $acc['account_type'],
                        'nature' => $acc['nature'],
                        'is_postable' => (bool) $acc['is_postable'],
                        'is_active' => true,
                    ]
                );

                $codeToId[$code] = $record->id;
            }

            // Ensure level-5 FX difference leaf accounts exist under 3355 and 4355
            if (isset($codeToId['3355'])) {
                Account::updateOrCreate(
                    ['code' => '33551', 'company_id' => 1],
                    [
                        'name' => 'خسائر فروقات أسعار الصرف الأجنبي',
                        'parent_id' => $codeToId['3355'],
                        'level' => 5,
                        'account_type' => 'expense',
                        'nature' => 'debit',
                        'is_postable' => true,
                        'is_active' => true,
                    ]
                );
            }

            if (isset($codeToId['4355'])) {
                Account::updateOrCreate(
                    ['code' => '43551', 'company_id' => 1],
                    [
                        'name' => 'أرباح فروقات أسعار الصرف الأجنبي',
                        'parent_id' => $codeToId['4355'],
                        'level' => 5,
                        'account_type' => 'revenue',
                        'nature' => 'credit',
                        'is_postable' => true,
                        'is_active' => true,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
