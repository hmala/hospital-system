<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->string('billing_status')->default('ready')->after('payment_status');
        });

        Schema::table('surgery_additional_operations', function (Blueprint $table) {
            $table->decimal('fee', 10, 2)->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn('billing_status');
        });

        Schema::table('surgery_additional_operations', function (Blueprint $table) {
            $table->dropColumn('fee');
        });
    }
};
