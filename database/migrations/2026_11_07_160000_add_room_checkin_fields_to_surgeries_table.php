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
            if (! Schema::hasColumn('surgeries', 'room_checkin_at')) {
                $table->timestamp('room_checkin_at')->nullable()->after('room_id');
            }
            if (! Schema::hasColumn('surgeries', 'room_checkin_by')) {
                $table->unsignedBigInteger('room_checkin_by')->nullable()->after('room_checkin_at');
                $table->foreign('room_checkin_by')->references('id')->on('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'room_checkin_by')) {
                $table->dropForeign(['room_checkin_by']);
                $table->dropColumn('room_checkin_by');
            }
            if (Schema::hasColumn('surgeries', 'room_checkin_at')) {
                $table->dropColumn('room_checkin_at');
            }
        });
    }
};
