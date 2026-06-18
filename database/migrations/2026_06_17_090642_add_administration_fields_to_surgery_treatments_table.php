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
        Schema::table('surgery_treatments', function (Blueprint $table) {
            $table->enum('status', ['planned', 'administered', 'cancelled'])->default('planned')->after('duration_unit');
            $table->foreignId('administered_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('administered_at')->nullable()->after('administered_by');
            $table->text('admin_notes')->nullable()->after('administered_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            $table->dropForeign(['administered_by']);
            $table->dropColumn(['status', 'administered_by', 'administered_at', 'admin_notes']);
        });
    }
};
