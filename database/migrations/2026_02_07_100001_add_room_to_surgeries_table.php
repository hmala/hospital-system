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
        Schema::table('surgeries', function (Blueprint $table) {
            $table->foreignId('room_id')->nullable()->after('department_id')->constrained('rooms')->nullOnDelete();
            $table->integer('expected_stay_days')->nullable()->after('room_id');
            $table->decimal('room_fee', 12, 2)->nullable()->after('expected_stay_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropForeign(['room_id']);
            $table->dropColumn(['room_id', 'expected_stay_days', 'room_fee']);
        });
    }
};
