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
        Schema::create('surgical_operations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم العملية
            $table->string('category'); // القسم/الجراحة
            $table->decimal('fee', 10, 2)->default(0); // أجر العملية
            $table->text('description')->nullable(); // وصف العملية
            $table->boolean('is_active')->default(true); // فعال أم لا
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgical_operations');
    }
};
