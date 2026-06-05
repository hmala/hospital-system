<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lab_result_sub_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_result_id')->constrained('lab_results')->onDelete('cascade');
            $table->string('sub_test_name');
            $table->decimal('value', 10, 2)->nullable();
            $table->string('unit')->nullable();
            $table->string('status')->nullable();
            $table->string('reference_range')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lab_result_id', 'sub_test_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_result_sub_results');
    }
};
