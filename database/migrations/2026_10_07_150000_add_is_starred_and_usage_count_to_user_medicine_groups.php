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
        Schema::table('user_medicine_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('user_medicine_groups', 'is_starred')) {
                $table->boolean('is_starred')->default(false)->after('is_public')->index();
            }
            if (!Schema::hasColumn('user_medicine_groups', 'usage_count')) {
                $table->unsignedInteger('usage_count')->default(0)->after('is_starred')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_medicine_groups', function (Blueprint $table) {
            if (Schema::hasColumn('user_medicine_groups', 'usage_count')) {
                $table->dropColumn('usage_count');
            }
            if (Schema::hasColumn('user_medicine_groups', 'is_starred')) {
                $table->dropColumn('is_starred');
            }
        });
    }
};
