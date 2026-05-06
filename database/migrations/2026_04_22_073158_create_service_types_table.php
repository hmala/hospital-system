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
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // مثل 'lab', 'radiology'
            $table->string('label'); // مثل 'تحاليل طبية'
            $table->string('icon')->nullable(); // مثل 'fas fa-flask'
            $table->string('color')->default('primary'); // مثل 'primary', 'info'
            $table->boolean('is_active')->default(true);
            $table->string('required_permission')->nullable(); // مثل 'inquiry.create.lab'
            $table->integer('sort_order')->default(0); // لترتيب العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
