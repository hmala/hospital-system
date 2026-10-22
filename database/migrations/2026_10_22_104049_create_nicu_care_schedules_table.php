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
        Schema::create('nicu_care_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')
                  ->constrained('incubator_reservations')
                  ->onDelete('cascade');
            $table->enum('type', ['feeding', 'medication']);
            $table->string('title'); // e.g. "حليب صناعي سيميلاك نيوشور" or "أمبيسيلين Ampicillin"
            $table->string('dose_or_amount')->nullable(); // e.g. "25 ml" or "50 mg"
            $table->string('route')->nullable(); // e.g. "أنبوب أنفي معدي NGT" or "وريدي IV" or "فموي PO"
            $table->unsignedInteger('interval_hours')->default(3); // e.g. 2, 3, 4, 6, 8, 12, 24
            $table->timestamp('start_time')->nullable();
            $table->timestamp('next_due_at')->nullable();
            $table->timestamp('last_administered_at')->nullable();
            $table->text('instructions')->nullable(); // ملاحظات سريرية وتوصيات الطبيب
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reservation_id', 'is_active', 'next_due_at']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_care_schedules');
    }
};
