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
        Schema::create('surgery_treatments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->text('description');
            $table->string('dosage')->nullable();
            $table->text('timing')->nullable();
            $table->integer('duration_value')->nullable();
            $table->enum('duration_unit', ['days', 'weeks', 'months', 'hours', 'doses'])->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_treatments');
    }
};
