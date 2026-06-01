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
        if (!Schema::hasTable('emergency_vital_signs')) {
            Schema::create('emergency_vital_signs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('emergency_id')->constrained()->onDelete('cascade');
                $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete(); // الممرض/الطبيب الذي سجل القراءة
                $table->string('blood_pressure', 20)->nullable();
            $table->integer('heart_rate')->nullable();
            $table->decimal('temperature', 4, 1)->nullable();
            $table->integer('respiratory_rate')->nullable();
            $table->integer('oxygen_saturation')->nullable();
            $table->decimal('blood_glucose', 5, 1)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['emergency_id', 'created_at']);
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emergency_vital_signs');
    }
};
