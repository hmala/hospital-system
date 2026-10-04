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
        if (Schema::hasTable('doctors') && !Schema::hasColumn('doctors', 'current_room')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->string('current_room')->nullable()->after('available_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'current_room')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn('current_room');
            });
        }
    }
};
