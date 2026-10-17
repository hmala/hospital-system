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
        if (!Schema::hasTable('lab_test_sub_tests')) {
            Schema::create('lab_test_sub_tests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
                $table->string('name');
                $table->string('unit')->nullable();
                $table->string('reference_range')->nullable();
                $table->string('result_type')->default('numeric');
                $table->integer('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_sub_tests');
    }
};
