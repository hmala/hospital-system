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
        Schema::table('emergencies', function (Blueprint $table) {
            if (!Schema::hasColumn('emergencies', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            }
            if (!Schema::hasColumn('emergencies', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergencies', function (Blueprint $table) {
            if (Schema::hasColumn('emergencies', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('emergencies', 'payment_status')) {
                $table->dropColumn('payment_status');
            }
        });
    }
};
