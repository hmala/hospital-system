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
        Schema::table('blood_bank_requests', function (Blueprint $table) {
            $table->decimal('donor_weight', 8, 2)->nullable()->after('patient_group');
            $table->decimal('recipient_weight', 8, 2)->nullable()->after('donor_weight');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blood_bank_requests', function (Blueprint $table) {
            $table->dropColumn(['donor_weight', 'recipient_weight']);
        });
    }
};
