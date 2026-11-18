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
        if (! Schema::hasTable('surgery_consultations')) {
            Schema::create('surgery_consultations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('surgery_id')->constrained('surgeries')->cascadeOnDelete();
                $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
                $table->string('specialty')->nullable();
                $table->text('reason');
                $table->string('priority', 20)->default('routine'); // routine, urgent, stat
                $table->string('status', 20)->default('pending'); // pending, completed, cancelled
                $table->text('consultant_response')->nullable();
                $table->text('consultant_recommendations')->nullable();
                $table->decimal('fee', 10, 2)->default(0.00);
                $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('responded_at')->nullable();
                $table->timestamps();

                $table->index(['surgery_id', 'status']);
                $table->index(['doctor_id', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_consultations');
    }
};
