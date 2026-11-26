<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payments') && ! Schema::hasColumn('payments', 'pharmacy_sale_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('pharmacy_sale_id')->nullable()->after('surgery_id')->constrained('pharmacy_sales')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_sale_id']);
            $table->dropColumn('pharmacy_sale_id');
        });
    }
};
