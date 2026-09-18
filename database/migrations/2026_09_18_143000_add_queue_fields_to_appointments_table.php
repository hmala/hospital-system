<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'queue_number')) {
                $table->integer('queue_number')->nullable()->after('payment_status');
            }
            if (!Schema::hasColumn('appointments', 'called_at')) {
                $table->timestamp('called_at')->nullable()->after('confirmed_at');
            }
        });

        // Convert status enum to string so we can support 'calling' and 'in_consultation'
        try {
            DB::statement("ALTER TABLE appointments MODIFY COLUMN status VARCHAR(30) DEFAULT 'scheduled'");
        } catch (\Exception $e) {
            // Ignore if already string
        }
    }

    public function down()
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'queue_number')) {
                $table->dropColumn('queue_number');
            }
            if (Schema::hasColumn('appointments', 'called_at')) {
                $table->dropColumn('called_at');
            }
        });
    }
};
