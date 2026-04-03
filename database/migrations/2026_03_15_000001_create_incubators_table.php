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
        Schema::create('incubators', function (Blueprint $table) {
            $table->id();
            $table->string('incubator_number')->unique()->comment('رقم الحاضنة (1-16)');
            $table->enum('incubator_type', ['normal', 'oxygen', 'phototherapy'])
                  ->default('normal')
                  ->comment('نوع الحاضنة: عادية، أكسجين، علاج ضوئي');
            $table->enum('status', ['available', 'occupied', 'maintenance'])
                  ->default('available')
                  ->comment('الحالة: متاحة، محجوزة، صيانة');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null')
                  ->comment('الغرفة المرتبطة (ط6)');
            $table->decimal('daily_fee', 12, 2)->default(0)->comment('الأجرة اليومية');
            $table->text('description')->nullable()->comment('وصف ومواصفات الحاضنة');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');
            $table->boolean('is_active')->default(true)->comment('نشطة/معطلة');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incubators');
    }
};
