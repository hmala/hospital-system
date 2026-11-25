<?php

use App\Models\Accounting\Account;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $lossParent = Account::where('code', '3355')->where('company_id', 1)->first();
        if ($lossParent) {
            if ($lossParent->is_postable) {
                $lossParent->update(['is_postable' => false]);
            }

            Account::updateOrCreate(
                ['code' => '33551', 'company_id' => 1],
                [
                    'name' => 'خسائر فروقات أسعار الصرف الأجنبي',
                    'parent_id' => $lossParent->id,
                    'level' => 5,
                    'account_type' => $lossParent->account_type,
                    'nature' => $lossParent->nature,
                    'is_postable' => true,
                    'is_active' => true,
                ]
            );
        }

        $gainParent = Account::where('code', '4355')->where('company_id', 1)->first();
        if ($gainParent) {
            if ($gainParent->is_postable) {
                $gainParent->update(['is_postable' => false]);
            }

            Account::updateOrCreate(
                ['code' => '43551', 'company_id' => 1],
                [
                    'name' => 'أرباح فروقات أسعار الصرف الأجنبي',
                    'parent_id' => $gainParent->id,
                    'level' => 5,
                    'account_type' => $gainParent->account_type,
                    'nature' => $gainParent->nature,
                    'is_postable' => true,
                    'is_active' => true,
                ]
            );
        }
    }

    public function down(): void
    {
        Account::whereIn('code', ['33551', '43551'])->where('company_id', 1)->delete();
    }
};
