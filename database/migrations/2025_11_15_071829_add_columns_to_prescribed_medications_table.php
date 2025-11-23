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
        Schema::table('prescribed_medications', function (Blueprint $table) {
            $table->foreignId('visit_id')->constrained('visits')->onDelete('cascade');
            $table->enum('item_type', ['medication', 'treatment']);
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('times')->nullable();
            $table->string('duration')->nullable();
            $table->text('instructions')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescribed_medications', function (Blueprint $table) {
            $table->dropForeign(['visit_id']);
            $table->dropColumn([
                'visit_id',
                'item_type',
                'name',
                'type',
                'dosage',
                'frequency',
                'times',
                'duration',
                'instructions'
            ]);
        });
    }
};
