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
            $table->text('treatment_plan')->nullable()->after('surgical_notes');
            $table->date('follow_up_date')->nullable()->after('treatment_plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn(['treatment_plan', 'follow_up_date']);
        });
    }
};
