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
        Schema::table('emergency_treatments', function (Blueprint $table) {
            $table->enum('treatment_type', ['medication', 'injection', 'drip', 'oxygen', 'other'])
                ->default('other')
                ->change();
            $table->unsignedTinyInteger('frequency_per_day')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('emergency_treatments', function (Blueprint $table) {
            $table->enum('treatment_type', ['medication', 'procedure', 'therapy', 'observation', 'other'])
                ->default('other')
                ->change();
            $table->dropColumn('frequency_per_day');
        });
    }
};
