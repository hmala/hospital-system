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
        Schema::table('user_lab_test_stats', function (Blueprint $table) {
            $table->dropColumn(['usage_count', 'last_used_at']);
            $table->dropIndex(['user_id', 'is_favorite', 'usage_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_lab_test_stats', function (Blueprint $table) {
            $table->unsignedInteger('usage_count')->default(0)->after('lab_test_id');
            $table->timestamp('last_used_at')->nullable()->after('is_favorite');
            $table->index(['user_id', 'is_favorite', 'usage_count']);
        });
    }
};
