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
            if (!Schema::hasColumn('surgeries', 'surgical_operation_id')) {
                $table->foreignId('surgical_operation_id')->nullable()->after('surgery_type')->constrained('surgical_operations')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'surgical_operation_id')) {
                $table->dropForeign(['surgical_operation_id']);
                $table->dropColumn('surgical_operation_id');
            }
        });
    }
};
