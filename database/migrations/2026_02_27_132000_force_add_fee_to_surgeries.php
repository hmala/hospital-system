<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // raw statement to avoid Schema cache issues
        if (!Schema::hasColumn('surgeries','surgery_fee')) {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE `surgeries` ADD COLUMN `surgery_fee` decimal(10,2) NOT NULL DEFAULT '0' COMMENT 'رسوم العملية الجراحية' AFTER `payment_id`"
            );
        }
        if (!Schema::hasColumn('surgeries','payment_id')) {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE `surgeries` ADD COLUMN `payment_id` bigint unsigned NULL AFTER `payment_status"
            );
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE `surgeries` ADD CONSTRAINT surgeries_payment_id_foreign FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('surgeries','payment_id')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `surgeries` DROP FOREIGN KEY surgeries_payment_id_foreign');
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `surgeries` DROP COLUMN `payment_id`');
        }
        if (Schema::hasColumn('surgeries','surgery_fee')) {
            \Illuminate\Support\Facades\DB::statement('ALTER TABLE `surgeries` DROP COLUMN `surgery_fee`');
        }
    }
};