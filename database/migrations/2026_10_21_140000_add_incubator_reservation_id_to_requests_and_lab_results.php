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
        Schema::table('requests', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')->nullable()->change();
            if (!Schema::hasColumn('requests', 'incubator_reservation_id')) {
                $table->foreignId('incubator_reservation_id')->nullable()->after('visit_id')->constrained('incubator_reservations')->cascadeOnDelete();
            }
        });

        Schema::table('lab_results', function (Blueprint $table) {
            $table->unsignedBigInteger('visit_id')->nullable()->change();
            if (!Schema::hasColumn('lab_results', 'incubator_reservation_id')) {
                $table->foreignId('incubator_reservation_id')->nullable()->after('visit_id')->constrained('incubator_reservations')->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            if (Schema::hasColumn('lab_results', 'incubator_reservation_id')) {
                $table->dropForeign(['incubator_reservation_id']);
                $table->dropColumn('incubator_reservation_id');
            }
            $table->unsignedBigInteger('visit_id')->nullable(false)->change();
        });

        Schema::table('requests', function (Blueprint $table) {
            if (Schema::hasColumn('requests', 'incubator_reservation_id')) {
                $table->dropForeign(['incubator_reservation_id']);
                $table->dropColumn('incubator_reservation_id');
            }
            $table->unsignedBigInteger('visit_id')->nullable(false)->change();
        });
    }
};
