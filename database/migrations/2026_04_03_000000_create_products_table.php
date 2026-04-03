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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');          // اسم المادة (مثلاً: Amoxicillin 500mg)
            $table->string('code')->unique(); // كود ثابت للمادة (مثلاً: MED-001)
            $table->string('unit');          // الوحدة (قطعة، علبة، كرتون)
            $table->boolean('is_perishable')->default(true); // هل لها تاريخ انتهاء؟
            $table->integer('alert_quantity')->default(10); // حد التنبيه للنقص
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
