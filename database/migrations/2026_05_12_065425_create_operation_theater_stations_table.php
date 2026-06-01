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
        // محطة صالة العمليات
        Schema::create('operation_theater_stations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surgery_id')->constrained('surgeries')->onDelete('cascade');
            $table->foreignId('or_nurse_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('anesthesiologist_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->text('procedure_notes')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('surgery_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('operation_theater_stations');
    }
};
